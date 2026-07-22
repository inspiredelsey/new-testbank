<?php
/**
 * ExamQuestion and ExamRule Model
 */

require_once __DIR__ . '/../../includes/Database.php';

class ExamQuestion {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get fixed-pick questions for an exam, ordered
     */
    public function forExam($examId) {
        $stmt = $this->db->prepare("
            SELECT eq.*, q.question_text, q.type, q.difficulty, q.points as q_points
            FROM exam_questions eq
            JOIN questions q ON eq.question_id = q.id
            WHERE eq.exam_id = ?
            ORDER BY eq.order_index ASC, eq.id ASC
        ");
        $stmt->execute([$examId]);
        return $stmt->fetchAll();
    }

    /**
     * Add a fixed question to the exam
     */
    public function addQuestion($examId, $questionId, $orderIndex, $pointsOverride = null) {
        // Prevent adding duplicate fixed questions to the same exam
        $stmtCheck = $this->db->prepare("SELECT id FROM exam_questions WHERE exam_id = ? AND question_id = ?");
        $stmtCheck->execute([$examId, $questionId]);
        if ($stmtCheck->fetch()) {
            return false; // Already added
        }

        $stmt = $this->db->prepare("
            INSERT INTO exam_questions (exam_id, question_id, order_index, points_override)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([
            $examId,
            $questionId,
            $orderIndex,
            $pointsOverride !== null && $pointsOverride !== '' ? floatval($pointsOverride) : null
        ]);
    }

    /**
     * Remove a fixed question from the exam
     */
    public function removeQuestion($id) {
        $stmt = $this->db->prepare("DELETE FROM exam_questions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Reorder the fixed questions for an exam
     */
    public function reorder($examId, array $orderedIds) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE exam_questions SET order_index = ? WHERE exam_id = ? AND id = ?");
            foreach ($orderedIds as $index => $id) {
                $stmt->execute([$index, $examId, $id]);
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Get rules for an exam
     */
    public function rulesForExam($examId) {
        $stmt = $this->db->prepare("
            SELECT er.*, c.name as category_name
            FROM exam_rules er
            JOIN categories c ON er.category_id = c.id
            WHERE er.exam_id = ?
            ORDER BY er.id ASC
        ");
        $stmt->execute([$examId]);
        return $stmt->fetchAll();
    }

    /**
     * Add a random-pull rule to the exam
     */
    public function addRule($examId, $categoryId, $difficulty, $count) {
        $stmt = $this->db->prepare("
            INSERT INTO exam_rules (exam_id, category_id, difficulty, question_count)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([
            $examId,
            $categoryId,
            $difficulty ?: 'any',
            intval($count)
        ]);
    }

    /**
     * Remove a rule from the exam
     */
    public function removeRule($id) {
        $stmt = $this->db->prepare("DELETE FROM exam_rules WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Resolves the complete set of questions (fixed + random-pull rule selections)
     */
    public function resolveQuestionSet($examId) {
        // 1. Start with fixed-pick questions in order
        $fixedQuestions = $this->forExam($examId);
        $resolvedQuestionIds = [];
        foreach ($fixedQuestions as $fq) {
            $resolvedQuestionIds[] = intval($fq['question_id']);
        }

        // 2. Resolve random pull rules in order
        $rules = $this->rulesForExam($examId);
        foreach ($rules as $rule) {
            $catId = intval($rule['category_id']);
            $difficulty = strtolower(trim($rule['difficulty'] ?? 'any'));
            $count = intval($rule['question_count']);

            // Fetch eligible published questions
            $query = "SELECT id FROM questions WHERE (category_id = ? OR category_id IN (SELECT id FROM categories WHERE parent_id = ?)) AND status = 'published'";
            $params = [$catId, $catId];

            if ($difficulty !== 'any' && !empty($difficulty)) {
                $query .= " AND difficulty = ?";
                $params[] = $difficulty;
            }

            // Exclude already resolved questions to avoid duplicates
            if (!empty($resolvedQuestionIds)) {
                $placeholders = implode(',', array_fill(0, count($resolvedQuestionIds), '?'));
                $query .= " AND id NOT IN (" . $placeholders . ")";
                $params = array_merge($params, $resolvedQuestionIds);
            }

            // SQLite RANDOM() vs MySQL RAND()
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            $randFunc = ($driver === 'sqlite') ? "RANDOM()" : "RAND()";
            $query .= " ORDER BY " . $randFunc . " LIMIT " . $count;

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $pulledIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($pulledIds as $pId) {
                $resolvedQuestionIds[] = intval($pId);
            }
        }

        // 3. Fallback: If no fixed questions or rules exist for this exam, pull available published questions automatically
        if (empty($resolvedQuestionIds)) {
            // Fetch exam category & course
            $stmtExam = $this->db->prepare("SELECT category_id, course_id FROM exams WHERE id = ?");
            $stmtExam->execute([$examId]);
            $examInfo = $stmtExam->fetch(PDO::FETCH_ASSOC);

            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            $randFunc = ($driver === 'sqlite') ? "RANDOM()" : "RAND()";

            if ($examInfo && !empty($examInfo['category_id'])) {
                $catId = intval($examInfo['category_id']);
                $stmtFallbackCat = $this->db->prepare("
                    SELECT id FROM questions 
                    WHERE (category_id = ? OR category_id IN (SELECT id FROM categories WHERE parent_id = ?)) 
                    AND status = 'published'
                    ORDER BY {$randFunc} LIMIT 10
                ");
                $stmtFallbackCat->execute([$catId, $catId]);
                $fallbackIds = $stmtFallbackCat->fetchAll(PDO::FETCH_COLUMN);
                foreach ($fallbackIds as $fid) {
                    $resolvedQuestionIds[] = intval($fid);
                }
            }

            // If category fallback yielded nothing, pull published questions globally
            if (empty($resolvedQuestionIds)) {
                $stmtFallbackGlobal = $this->db->query("
                    SELECT id FROM questions 
                    WHERE status = 'published'
                    ORDER BY {$randFunc} LIMIT 5
                ");
                $fallbackIds = $stmtFallbackGlobal->fetchAll(PDO::FETCH_COLUMN);
                foreach ($fallbackIds as $fid) {
                    $resolvedQuestionIds[] = intval($fid);
                }
            }
        }

        // Return array of unique question IDs
        return array_values(array_unique($resolvedQuestionIds));
    }
}
