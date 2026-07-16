<?php
/**
 * Exam Model
 */

require_once __DIR__ . '/../../includes/Database.php';

class Exam {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll($filters = []) {
        $query = "
            SELECT e.*, c.name as category_name, u.name as creator_name 
            FROM exams e
            LEFT JOIN categories c ON e.category_id = c.id
            LEFT JOIN users u ON e.created_by = u.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['status'])) {
            $query .= " AND e.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['category_id'])) {
            $query .= " AND e.category_id = ?";
            $params[] = $filters['category_id'];
        }
        if (!empty($filters['search'])) {
            $query .= " AND e.title LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }

        $query .= " ORDER BY e.id DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM exams WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO exams (title, description, category_id, duration_minutes, pass_percentage, 
                               shuffle_questions, shuffle_options, max_attempts, start_date, end_date, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['title'],
            $data['description'] ?? null,
            !empty($data['category_id']) ? $data['category_id'] : null,
            $data['duration_minutes'] ?? 60,
            $data['pass_percentage'] ?? 50.00,
            !empty($data['shuffle_questions']) ? 1 : 0,
            !empty($data['shuffle_options']) ? 1 : 0,
            $data['max_attempts'] ?? 0,
            !empty($data['start_date']) ? $data['start_date'] : null,
            !empty($data['end_date']) ? $data['end_date'] : null,
            $data['status'] ?? 'draft',
            $data['created_by'] ?? null
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE exams 
            SET title = ?, description = ?, category_id = ?, duration_minutes = ?, pass_percentage = ?, 
                shuffle_questions = ?, shuffle_options = ?, max_attempts = ?, start_date = ?, end_date = ?, status = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['title'],
            $data['description'] ?? null,
            !empty($data['category_id']) ? $data['category_id'] : null,
            $data['duration_minutes'] ?? 60,
            $data['pass_percentage'] ?? 50.00,
            !empty($data['shuffle_questions']) ? 1 : 0,
            !empty($data['shuffle_options']) ? 1 : 0,
            $data['max_attempts'] ?? 0,
            !empty($data['start_date']) ? $data['start_date'] : null,
            !empty($data['end_date']) ? $data['end_date'] : null,
            $data['status'] ?? 'draft',
            $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM exams WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Get manual questions assigned to the exam
     */
    public function getQuestions($examId) {
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
     * Assign manually selected questions to exam
     */
    public function saveQuestions($examId, $questionIds, $pointsOverrides = []) {
        $this->db->beginTransaction();
        try {
            // Delete existing manual assignments
            $stmtDel = $this->db->prepare("DELETE FROM exam_questions WHERE exam_id = ?");
            $stmtDel->execute([$examId]);

            if (!empty($questionIds)) {
                $stmtIns = $this->db->prepare("
                    INSERT INTO exam_questions (exam_id, question_id, order_index, points_override)
                    VALUES (?, ?, ?, ?)
                ");
                foreach ($questionIds as $index => $qId) {
                    $override = isset($pointsOverrides[$qId]) && $pointsOverrides[$qId] !== '' ? floatval($pointsOverrides[$qId]) : null;
                    $stmtIns->execute([$examId, $qId, $index, $override]);
                }
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Get random-pull rules for an exam
     */
    public function getRules($examId) {
        $stmt = $this->db->prepare("
            SELECT er.*, c.name as category_name
            FROM exam_rules er
            JOIN categories c ON er.category_id = c.id
            WHERE er.exam_id = ?
        ");
        $stmt->execute([$examId]);
        return $stmt->fetchAll();
    }

    /**
     * Save random pull rules
     */
    public function saveRules($examId, $rules) {
        $this->db->beginTransaction();
        try {
            $stmtDel = $this->db->prepare("DELETE FROM exam_rules WHERE exam_id = ?");
            $stmtDel->execute([$examId]);

            if (!empty($rules)) {
                $stmtIns = $this->db->prepare("
                    INSERT INTO exam_rules (exam_id, category_id, difficulty, question_count)
                    VALUES (?, ?, ?, ?)
                ");
                foreach ($rules as $r) {
                    if (empty($r['category_id']) || empty($r['question_count']) || intval($r['question_count']) <= 0) {
                        continue;
                    }
                    $stmtIns->execute([
                        $examId,
                        $r['category_id'],
                        $r['difficulty'] ?? 'any',
                        intval($r['question_count'])
                    ]);
                }
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Resolves all manual questions and random pull rules into a concrete, static set of question IDs.
     * Used when a student starts an attempt to lock in the randomized/assigned questions.
     */
    public function resolveQuestionSet($examId) {
        // 1. Get manual question IDs
        $manualQuestions = $this->getQuestions($examId);
        $questionIds = [];
        foreach ($manualQuestions as $mq) {
            $questionIds[] = intval($mq['question_id']);
        }

        // 2. Resolve random pull rules
        $rules = $this->getRules($examId);
        foreach ($rules as $rule) {
            $catId = intval($rule['category_id']);
            $difficulty = $rule['difficulty'];
            $count = intval($rule['question_count']);

            $query = "SELECT id FROM questions WHERE (category_id = ? OR category_id IN (SELECT id FROM categories WHERE parent_id = ?)) AND status = 'published'";
            $params = [$catId, $catId];

            if ($difficulty !== 'any') {
                $query .= " AND difficulty = ?";
                $params[] = $difficulty;
            }

            // Exclude already added manual questions
            if (!empty($questionIds)) {
                $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
                $query .= " AND id NOT IN (" . $placeholders . ")";
                $params = array_merge($params, $questionIds);
            }

            // Get random set
            // Note: SQLite supports RANDOM(), MySQL supports RAND(). Our Database layer handles it!
            $isFallback = Database::getInstance()->isFallback();
            $randFunc = $isFallback ? "RANDOM()" : "RAND()";
            $query .= " ORDER BY " . $randFunc . " LIMIT " . $count;

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $pulledIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($pulledIds as $pId) {
                $questionIds[] = intval($pId);
            }
        }

        // 3. Shuffle if required
        $exam = $this->getById($examId);
        if ($exam && $exam['shuffle_questions']) {
            shuffle($questionIds);
        }

        return $questionIds;
    }
}
