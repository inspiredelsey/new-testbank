<?php
/**
 * Case Study Model - Test Bank LMS
 * Manages CRUD for cases and case_exhibits.
 */

require_once __DIR__ . '/../../includes/Database.php';

class CaseStudy {
    private $db;

    public function __construct() {
        // Tables are created once from the canonical /sql/schema.sql — no per-request schema checks here.
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Retrieve all case studies with optional category filter
     */
    public function all($categoryId = null) {
        $sql = "SELECT c.*, cat.name as category_name, u.name as creator_name,
                (SELECT COUNT(*) FROM case_exhibits ce WHERE ce.case_id = c.id) as exhibit_count,
                (SELECT COUNT(*) FROM questions q WHERE q.case_id = c.id) as question_count
                FROM cases c
                LEFT JOIN categories cat ON c.category_id = cat.id
                LEFT JOIN users u ON c.created_by = u.id";
        
        $params = [];
        if ($categoryId) {
            $sql .= " WHERE c.category_id = ?";
            $params[] = $categoryId;
        }
        
        $sql .= " ORDER BY c.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Find a case study by ID
     */
    public function find($id) {
        $stmt = $this->db->prepare("SELECT c.*, cat.name as category_name, u.name as creator_name
                                    FROM cases c
                                    LEFT JOIN categories cat ON c.category_id = cat.id
                                    LEFT JOIN users u ON c.created_by = u.id
                                    WHERE c.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Create a new case study
     */
    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO cases (title, scenario_text, category_id, is_trend, created_by)
                                    VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['title'],
            $data['scenario_text'],
            $data['category_id'],
            !empty($data['is_trend']) ? 1 : 0,
            $data['created_by'] ?? null
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * Update a case study
     */
    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE cases SET title = ?, scenario_text = ?, category_id = ?, is_trend = ?
                                    WHERE id = ?");
        return $stmt->execute([
            $data['title'],
            $data['scenario_text'],
            $data['category_id'],
            !empty($data['is_trend']) ? 1 : 0,
            $id
        ]);
    }

    /**
     * Delete a case study. Blocks if any questions reference it.
     */
    public function delete($id) {
        // Block deletion if referenced by questions
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM questions WHERE case_id = ?");
        $stmt->execute([$id]);
        $count = (int)$stmt->fetchColumn();

        if ($count > 0) {
            throw new Exception("Cannot delete this case study because it has " . $count . " active questions attached to it. Please detach or delete the questions first.");
        }

        $stmt = $this->db->prepare("DELETE FROM cases WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Fetch all exhibits for a case, ordered by order_index
     */
    public function exhibitsForCase($caseId) {
        $stmt = $this->db->prepare("SELECT * FROM case_exhibits WHERE case_id = ? ORDER BY order_index ASC, id ASC");
        $stmt->execute([$caseId]);
        return $stmt->fetchAll();
    }

    /**
     * Get a single exhibit by ID
     */
    public function findExhibit($id) {
        $stmt = $this->db->prepare("SELECT * FROM case_exhibits WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Add a new exhibit tab to a case
     */
    public function addExhibit($caseId, $data) {
        // Get the next order_index
        $stmt = $this->db->prepare("SELECT MAX(order_index) FROM case_exhibits WHERE case_id = ?");
        $stmt->execute([$caseId]);
        $maxOrder = (int)$stmt->fetchColumn();
        $nextOrder = $maxOrder + 1;

        $stmt = $this->db->prepare("INSERT INTO case_exhibits (case_id, tab_label, content, timestamp_label, order_index)
                                    VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([
            $caseId,
            $data['tab_label'],
            $data['content'],
            $data['timestamp_label'] ?? null,
            $nextOrder
        ]);
    }

    /**
     * Update an exhibit tab
     */
    public function updateExhibit($id, $data) {
        $stmt = $this->db->prepare("UPDATE case_exhibits SET tab_label = ?, content = ?, timestamp_label = ?
                                    WHERE id = ?");
        return $stmt->execute([
            $data['tab_label'],
            $data['content'],
            $data['timestamp_label'] ?? null,
            $id
        ]);
    }

    /**
     * Delete an exhibit
     */
    public function deleteExhibit($id) {
        $stmt = $this->db->prepare("DELETE FROM case_exhibits WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Reorder exhibits
     */
    public function reorderExhibits($caseId, array $orderedIds) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE case_exhibits SET order_index = ? WHERE id = ? AND case_id = ?");
            foreach ($orderedIds as $index => $id) {
                $stmt->execute([$index, $id, $caseId]);
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
