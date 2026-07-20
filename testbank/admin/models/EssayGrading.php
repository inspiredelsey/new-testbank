<?php
/**
 * Model for Instructor Manual Essay Grading operations.
 */

require_once __DIR__ . '/../../includes/Database.php';

class EssayGrading {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Retrieves all pending essay answers that need manual grading.
     * If instructorId is provided, filters to exams or courses associated with that instructor.
     */
    public function pendingQueue($instructorId = null) {
        $sql = "
            SELECT aa.id as attempt_answer_id, aa.attempt_id, aa.question_id, aa.answer_data, aa.points_awarded,
                   q.question_text, q.points as max_points,
                   e.id as exam_id, e.title as exam_title,
                   u.name as student_name,
                   c.title as course_title,
                   ea.submitted_at
            FROM attempt_answers aa
            JOIN exam_attempts ea ON aa.attempt_id = ea.id
            JOIN exams e ON ea.exam_id = e.id
            JOIN questions q ON aa.question_id = q.id
            JOIN users u ON ea.user_id = u.id
            LEFT JOIN courses c ON e.course_id = c.id
            WHERE aa.needs_manual_grading = 1
        ";

        $params = [];
        if ($instructorId !== null) {
            $sql .= " AND (e.created_by = ? OR e.course_id IN (SELECT id FROM courses WHERE instructor_id = ?))";
            $params[] = $instructorId;
            $params[] = $instructorId;
        }

        $sql .= " ORDER BY ea.submitted_at DESC, aa.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve a single essay answer details for grading
     */
    public function getEssayAnswer($attemptAnswerId) {
        $stmt = $this->db->prepare("
            SELECT aa.id as attempt_answer_id, aa.attempt_id, aa.question_id, aa.answer_data, aa.points_awarded,
                   q.question_text, q.points as max_points, q.case_id,
                   e.title as exam_title,
                   u.name as student_name,
                   ea.submitted_at
            FROM attempt_answers aa
            JOIN exam_attempts ea ON aa.attempt_id = ea.id
            JOIN exams e ON ea.exam_id = e.id
            JOIN questions q ON aa.question_id = q.id
            JOIN users u ON ea.user_id = u.id
            WHERE aa.id = ?
        ");
        $stmt->execute([$attemptAnswerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Scores a student's essay answer and updates attempt status if all essays are completed.
     */
    public function gradeEssay($attemptAnswerId, $pointsAwarded) {
        // 1. Get the attempt_id from the answer
        $stmt = $this->db->prepare("SELECT attempt_id, question_id FROM attempt_answers WHERE id = ?");
        $stmt->execute([$attemptAnswerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }

        $attemptId = $row['attempt_id'];
        $questionId = $row['question_id'];

        // 2. Validate max points of the question
        $stmtQ = $this->db->prepare("SELECT points FROM questions WHERE id = ?");
        $stmtQ->execute([questionId]);
        $maxPoints = floatval($stmtQ->fetchColumn() ?: 0.0);
        
        $pointsAwarded = max(0.0, min($maxPoints, floatval($pointsAwarded)));

        // 3. Update the answer row
        // is_correct heuristic: if pointsAwarded > 0, we count it as correct (for status display, though essays are fuzzy)
        $isCorrect = ($pointsAwarded > 0.0) ? 1 : 0;
        
        $stmtUpdate = $this->db->prepare("
            UPDATE attempt_answers 
            SET points_awarded = ?, is_correct = ?, needs_manual_grading = 0 
            WHERE id = ?
        ");
        $success = $stmtUpdate->execute([$pointsAwarded, $isCorrect, $attemptAnswerId]);

        if ($success) {
            // 4. Re-grade attempt to finalize total points/percentage and flip status to 'graded' if no essays remain
            require_once __DIR__ . '/../../includes/Grader.php';
            Grader::gradeAttempt($attemptId);
            return true;
        }

        return false;
    }
}
