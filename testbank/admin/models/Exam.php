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

    /**
     * Get all exams (admin view)
     */
    public function all($filters = []) {
        $query = "
            SELECT e.*, c.name as category_name, u.name as creator_name, co.title as course_title
            FROM exams e
            LEFT JOIN categories c ON e.category_id = c.id
            LEFT JOIN users u ON e.created_by = u.id
            LEFT JOIN courses co ON e.course_id = co.id
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
        if (!empty($filters['course_id'])) {
            $query .= " AND e.course_id = ?";
            $params[] = $filters['course_id'];
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

    /**
     * Get exams created by an instructor or belonging to a course owned by the instructor
     */
    public function byInstructor($instructorId, $filters = []) {
        $query = "
            SELECT e.*, c.name as category_name, u.name as creator_name, co.title as course_title
            FROM exams e
            LEFT JOIN categories c ON e.category_id = c.id
            LEFT JOIN users u ON e.created_by = u.id
            LEFT JOIN courses co ON e.course_id = co.id
            WHERE (e.created_by = ? OR co.instructor_id = ?)
        ";
        $params = [$instructorId, $instructorId];

        if (!empty($filters['status'])) {
            $query .= " AND e.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['category_id'])) {
            $query .= " AND e.category_id = ?";
            $params[] = $filters['category_id'];
        }
        if (!empty($filters['course_id'])) {
            $query .= " AND e.course_id = ?";
            $params[] = $filters['course_id'];
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

    /**
     * Alias for find($id)
     */
    public function getById($id) {
        return $this->find($id);
    }

    /**
     * Alias for all($filters)
     */
    public function getAll($filters = []) {
        return $this->all($filters);
    }

    /**
     * Find an exam by its ID
     */
    public function find($id) {
        $stmt = $this->db->prepare("
            SELECT e.*, co.instructor_id as course_instructor_id 
            FROM exams e 
            LEFT JOIN courses co ON e.course_id = co.id
            WHERE e.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Create a new exam
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO exams (course_id, title, description, category_id, duration_minutes, pass_percentage, 
                               shuffle_questions, shuffle_options, max_attempts, start_date, end_date, 
                               gradebook_category, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            !empty($data['course_id']) ? intval($data['course_id']) : null,
            $data['title'],
            $data['description'] ?? null,
            !empty($data['category_id']) ? intval($data['category_id']) : null,
            intval($data['duration_minutes']),
            floatval($data['pass_percentage']),
            !empty($data['shuffle_questions']) ? 1 : 0,
            !empty($data['shuffle_options']) ? 1 : 0,
            intval($data['max_attempts']),
            !empty($data['start_date']) ? $data['start_date'] : null,
            !empty($data['end_date']) ? $data['end_date'] : null,
            $data['gradebook_category'] ?? 'summative',
            $data['status'] ?? 'draft',
            $data['created_by'] ?? null
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * Update an exam
     */
    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE exams 
            SET course_id = ?, title = ?, description = ?, category_id = ?, duration_minutes = ?, pass_percentage = ?, 
                shuffle_questions = ?, shuffle_options = ?, max_attempts = ?, start_date = ?, end_date = ?, 
                gradebook_category = ?, status = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            !empty($data['course_id']) ? intval($data['course_id']) : null,
            $data['title'],
            $data['description'] ?? null,
            !empty($data['category_id']) ? intval($data['category_id']) : null,
            intval($data['duration_minutes']),
            floatval($data['pass_percentage']),
            !empty($data['shuffle_questions']) ? 1 : 0,
            !empty($data['shuffle_options']) ? 1 : 0,
            intval($data['max_attempts']),
            !empty($data['start_date']) ? $data['start_date'] : null,
            !empty($data['end_date']) ? $data['end_date'] : null,
            $data['gradebook_category'] ?? 'summative',
            $data['status'] ?? 'draft',
            $id
        ]);
    }

    /**
     * Set the status of an exam
     */
    public function setStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE exams SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    /**
     * Delete an exam. Block if there are existing exam attempts.
     */
    public function delete($id) {
        // Block if any exam_attempts reference it
        // (This table exists, let's query it!)
        $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM exam_attempts WHERE exam_id = ?");
        $stmtCheck->execute([$id]);
        $attemptCount = intval($stmtCheck->fetchColumn());

        if ($attemptCount > 0) {
            throw new Exception("Cannot delete exam: there are already {$attemptCount} student attempts recorded.");
        }

        // Transactions are used to ensure cascade deletion of rules and questions (even though DB has ON DELETE CASCADE, it is good to execute cleanly)
        $this->db->beginTransaction();
        try {
            // Clear exam questions
            $stmt1 = $this->db->prepare("DELETE FROM exam_questions WHERE exam_id = ?");
            $stmt1->execute([$id]);

            // Clear exam rules
            $stmt2 = $this->db->prepare("DELETE FROM exam_rules WHERE exam_id = ?");
            $stmt2->execute([$id]);

            // Delete the exam
            $stmt3 = $this->db->prepare("DELETE FROM exams WHERE id = ?");
            $stmt3->execute([$id]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Resolve question set for exam using ExamQuestion model
     */
    public function resolveQuestionSet($examId) {
        require_once __DIR__ . '/ExamQuestion.php';
        $eqModel = new ExamQuestion();
        return $eqModel->resolveQuestionSet($examId);
    }
}
