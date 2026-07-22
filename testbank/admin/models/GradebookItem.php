<?php
/**
 * Gradebook Item Model
 */

require_once __DIR__ . '/../../includes/Database.php';

class GradebookItem {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get the sum of weights for a course (optionally excluding a specific item)
     */
    public function getWeightSum($courseId, $excludeItemId = null) {
        $query = "SELECT SUM(weight) FROM gradebook_items WHERE course_id = ?";
        $params = [$courseId];
        if ($excludeItemId !== null) {
            $query .= " AND id != ?";
            $params[] = $excludeItemId;
        }
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return floatval($stmt->fetchColumn() ?: 0.0);
    }

    /**
     * Get all gradebook items for a course
     */
    public function all($courseId) {
        $stmt = $this->db->prepare("
            SELECT gi.*, e.title as exam_title 
            FROM gradebook_items gi
            LEFT JOIN exams e ON gi.item_id = e.id AND gi.item_type = 'quiz'
            WHERE gi.course_id = ?
            ORDER BY gi.id ASC
        ");
        $stmt->execute([$courseId]);
        return $stmt->fetchAll();
    }

    /**
     * Find a single gradebook item
     */
    public function find($id) {
        $stmt = $this->db->prepare("
            SELECT gi.*, e.title as exam_title 
            FROM gradebook_items gi
            LEFT JOIN exams e ON gi.item_id = e.id AND gi.item_type = 'quiz'
            WHERE gi.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Create a new manual gradebook item
     */
    public function create($data) {
        $courseId = intval($data['course_id']);
        $weight = floatval($data['weight'] ?? 0.00);
        $maxScore = floatval($data['max_score'] ?? 100.00);
        $title = trim($data['title']);
        $itemType = $data['item_type'] ?? 'manual';
        $itemId = isset($data['item_id']) && $data['item_id'] !== '' ? intval($data['item_id']) : null;

        // Weight validation (running total cannot exceed 100)
        $currentSum = $this->getWeightSum($courseId);
        if ($currentSum + $weight > 100.00) {
            throw new Exception("The total weight of all gradebook items in this course cannot exceed 100%. Currently it is {$currentSum}%. Adding {$weight}% would make it " . ($currentSum + $weight) . "%.");
        }

        if ($weight < 0 || $weight > 100) {
            throw new Exception("Weight must be between 0 and 100.");
        }

        if ($maxScore <= 0) {
            throw new Exception("Maximum score must be a positive number.");
        }

        if (empty($title)) {
            throw new Exception("Title is required.");
        }

        $stmt = $this->db->prepare("
            INSERT INTO gradebook_items (course_id, item_type, item_id, title, weight, max_score)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$courseId, $itemType, $itemId, $title, $weight, $maxScore]);
        return $this->db->lastInsertId();
    }

    /**
     * Update a gradebook item
     */
    public function update($id, $data) {
        $item = $this->find($id);
        if (!$item) {
            throw new Exception("Gradebook item not found.");
        }

        $courseId = intval($item['course_id']);
        $weight = floatval($data['weight'] ?? 0.00);
        $title = trim($data['title']);
        $maxScore = floatval($data['max_score'] ?? 100.00);

        // Weight validation (running total cannot exceed 100)
        $currentSum = $this->getWeightSum($courseId, $id);
        if ($currentSum + $weight > 100.00) {
            throw new Exception("The total weight of all gradebook items in this course cannot exceed 100%. Currently other items sum to {$currentSum}%. Setting this weight to {$weight}% would make the total " . ($currentSum + $weight) . "%.");
        }

        if ($weight < 0 || $weight > 100) {
            throw new Exception("Weight must be between 0 and 100.");
        }

        if ($maxScore <= 0) {
            throw new Exception("Maximum score must be a positive number.");
        }

        if (empty($title)) {
            throw new Exception("Title is required.");
        }

        $stmt = $this->db->prepare("
            UPDATE gradebook_items 
            SET title = ?, weight = ?, max_score = ?
            WHERE id = ?
        ");
        return $stmt->execute([$title, $weight, $maxScore, $id]);
    }

    /**
     * Delete a gradebook item
     */
    public function delete($id) {
        // Delete cascading scores is handled by DB rules, but let's delete explicitly to be safe
        $this->db->beginTransaction();
        try {
            $stmt1 = $this->db->prepare("DELETE FROM gradebook_scores WHERE gradebook_item_id = ?");
            $stmt1->execute([$id]);

            $stmt2 = $this->db->prepare("DELETE FROM gradebook_items WHERE id = ?");
            $stmt2->execute([$id]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Add or update a manual score for a student on a manual gradebook item
     */
    public function addManualScore($gradebookItemId, $userId, $score) {
        $item = $this->find($gradebookItemId);
        if (!$item) {
            throw new Exception("Gradebook item not found.");
        }

        if ($item['item_type'] !== 'manual') {
            throw new Exception("Cannot manually award scores to quiz-based items.");
        }

        $score = floatval($score);
        $maxScore = floatval($item['max_score']);

        if ($score < 0) {
            throw new Exception("Score cannot be negative.");
        }

        if ($score > $maxScore) {
            throw new Exception("Score {$score} cannot exceed the item's maximum score of {$maxScore}.");
        }

        // Upsert manual score
        $stmtCheck = $this->db->prepare("
            SELECT id FROM gradebook_scores 
            WHERE gradebook_item_id = ? AND user_id = ?
        ");
        $stmtCheck->execute([$gradebookItemId, $userId]);
        $existingId = $stmtCheck->fetchColumn();

        $success = false;
        if ($existingId) {
            $stmtUpdate = $this->db->prepare("
                UPDATE gradebook_scores 
                SET score = ?, recorded_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $success = $stmtUpdate->execute([$score, $existingId]);
        } else {
            $stmtInsert = $this->db->prepare("
                INSERT INTO gradebook_scores (gradebook_item_id, user_id, score) 
                VALUES (?, ?, ?)
            ");
            $success = $stmtInsert->execute([$gradebookItemId, $userId, $score]);
        }

        if ($success) {
            try {
                $courseId = $item['course_id'];
                require_once __DIR__ . '/../../includes/CertificateGenerator.php';
                CertificateGenerator::checkAndIssue($userId, $courseId);
            } catch (Exception $e) {
                error_log("Failed to check/issue certificate in addManualScore: " . $e->getMessage());
            }
        }

        return $success;
    }

    /**
     * Get all scores for a specific gradebook item
     */
    public function getScoresByItem($gradebookItemId) {
        $stmt = $this->db->prepare("
            SELECT * FROM gradebook_scores 
            WHERE gradebook_item_id = ?
        ");
        $stmt->execute([$gradebookItemId]);
        return $stmt->fetchAll();
    }

    /**
     * Get all scores for a course grouped by user_id and gradebook_item_id
     */
    public function getScoresForCourse($courseId) {
        $stmt = $this->db->prepare("
            SELECT gs.* 
            FROM gradebook_scores gs
            JOIN gradebook_items gi ON gs.gradebook_item_id = gi.id
            WHERE gi.course_id = ?
        ");
        $stmt->execute([$courseId]);
        $scores = $stmt->fetchAll();

        $matrix = [];
        foreach ($scores as $s) {
            $matrix[$s['user_id']][$s['gradebook_item_id']] = floatval($s['score']);
        }
        return $matrix;
    }
}
