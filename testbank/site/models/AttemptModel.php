<?php
/**
 * AttemptModel for student-facing operations
 */

require_once __DIR__ . '/../../includes/Database.php';

class AttemptModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Start a new attempt for a student
     */
    public function start($examId, $userId) {
        // Fetch exam to validate settings and dates
        $stmt = $this->db->prepare("SELECT * FROM exams WHERE id = ?");
        $stmt->execute([$examId]);
        $exam = $stmt->fetch();
        if (!$exam) {
            throw new Exception("Exam not found.");
        }

        if ($exam['status'] !== 'published') {
            throw new Exception("This exam is currently not published.");
        }

        // Check availability dates
        $now = date('Y-m-d H:i:s');
        if (!empty($exam['start_date']) && $now < $exam['start_date']) {
            throw new Exception("This exam is not open yet.");
        }
        if (!empty($exam['end_date']) && $now > $exam['end_date']) {
            throw new Exception("This exam's availability has ended.");
        }

        // Check maximum attempt limits
        $maxAttempts = intval($exam['max_attempts']);
        if ($maxAttempts > 0) {
            $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM exam_attempts WHERE user_id = ? AND exam_id = ?");
            $stmtCount->execute([$userId, $examId]);
            $existingCount = intval($stmtCount->fetchColumn());
            if ($existingCount >= $maxAttempts) {
                throw new Exception("Maximum attempt limit reached for this exam.");
            }
        }

        // Resolve question set (fixed + random rules)
        require_once __DIR__ . '/../../admin/models/ExamQuestion.php';
        $examQuestionModel = new ExamQuestion();
        $questionIds = $examQuestionModel->resolveQuestionSet($examId);
        if (empty($questionIds)) {
            throw new Exception("This exam does not have any questions resolved.");
        }

        $resolvedQuestionIdsJson = json_encode($questionIds);
        $startedAt = date('Y-m-d H:i:s');

        // Insert new exam attempt
        $stmtInsert = $this->db->prepare("
            INSERT INTO exam_attempts (exam_id, user_id, started_at, status, resolved_question_ids)
            VALUES (?, ?, ?, 'in_progress', ?)
        ");
        $stmtInsert->execute([$examId, $userId, $startedAt, $resolvedQuestionIdsJson]);
        $attemptId = $this->db->lastInsertId();

        // Pre-initialize answers rows for each question. This is always a
        // fresh $attemptId (just created above), so no duplicate row can
        // possibly already exist — a plain INSERT is correct and portable
        // across both MySQL and SQLite (no upsert syntax needed here).
        $stmtAnsInit = $this->db->prepare("
            INSERT INTO attempt_answers (attempt_id, question_id, answer_data, needs_manual_grading)
            VALUES (?, ?, NULL, 0)
        ");
        foreach ($questionIds as $qId) {
            $stmtAnsInit->execute([$attemptId, $qId]);
        }

        // Log quiz started
        require_once __DIR__ . '/../../includes/ActivityLogger.php';
        ActivityLogger::log($userId, 'quiz_started', $exam['course_id'], 'exam', $examId);

        return $this->getAttempt($attemptId);
    }

    /**
     * Get a specific attempt by ID along with its resolved questions in the correct order
     */
    public function getAttempt($attemptId) {
        $stmt = $this->db->prepare("
            SELECT ea.*, e.title as exam_title, e.description as exam_description, 
                   e.duration_minutes, e.pass_percentage, e.max_attempts, e.shuffle_questions, e.shuffle_options,
                   u.name as student_name
            FROM exam_attempts ea
            JOIN exams e ON ea.exam_id = e.id
            JOIN users u ON ea.user_id = u.id
            WHERE ea.id = ?
        ");
        $stmt->execute([$attemptId]);
        $attempt = $stmt->fetch();
        if (!$attempt) {
            return null;
        }

        // Decode question IDs
        $qIds = json_decode($attempt['resolved_question_ids'] ?? '[]', true);
        if (empty($qIds)) {
            $qIds = json_decode($attempt['resolved_questions'] ?? '[]', true) ?: [];
        }

        $attempt['questions'] = [];
        if (!empty($qIds)) {
            $placeholders = implode(',', array_fill(0, count($qIds), '?'));
            $stmtQ = $this->db->prepare("
                SELECT q.*, c.name as category_name
                FROM questions q
                LEFT JOIN categories c ON q.category_id = c.id
                WHERE q.id IN ($placeholders)
            ");
            $stmtQ->execute($qIds);
            $questionsRaw = $stmtQ->fetchAll();

            $questionsKeyed = [];
            foreach ($questionsRaw as $q) {
                // Fetch options for MCQ types
                $stmtOpt = $this->db->prepare("SELECT * FROM question_options WHERE question_id = ? ORDER BY order_index ASC, id ASC");
                $stmtOpt->execute([$q['id']]);
                $q['options'] = $stmtOpt->fetchAll();
                $questionsKeyed[$q['id']] = $q;
            }

            // Put in exact resolved ordering
            foreach ($qIds as $id) {
                if (isset($questionsKeyed[$id])) {
                    $q = $questionsKeyed[$id];
                    // Shuffle options if enabled for exam and matching certain types
                    if (!empty($q['options']) && $attempt['shuffle_options'] && in_array($q['type'], ['mcq_single', 'mcq_multi_sata', 'mcq_multi', 'mcq_extended'])) {
                        shuffle($q['options']);
                    }
                    $attempt['questions'][] = $q;
                }
            }

            // Shuffle questions if shuffle_questions setting is enabled for the exam
            if ($attempt['shuffle_questions']) {
                shuffle($attempt['questions']);
            }
        }

        return $attempt;
    }

    /**
     * Get all currently saved answers for an attempt keyed by question_id
     */
    public function getAnswers($attemptId) {
        $stmt = $this->db->prepare("SELECT * FROM attempt_answers WHERE attempt_id = ?");
        $stmt->execute([$attemptId]);
        $rows = $stmt->fetchAll();

        $answers = [];
        foreach ($rows as $row) {
            $answers[$row['question_id']] = $row;
        }
        return $answers;
    }

    /**
     * Save/upsert an answer to a question in the database
     */
    public function saveAnswer($attemptId, $questionId, $answerData) {
        // Ensure the attempt is in progress before saving
        $stmtCheck = $this->db->prepare("SELECT status FROM exam_attempts WHERE id = ?");
        $stmtCheck->execute([$attemptId]);
        $status = $stmtCheck->fetchColumn();
        if ($status !== 'in_progress') {
            return false;
        }

        // Fetch question type to determine manual grading
        $stmtQ = $this->db->prepare("SELECT type FROM questions WHERE id = ?");
        $stmtQ->execute([$questionId]);
        $qType = $stmtQ->fetchColumn();

        $needsManualGrading = ($qType === 'essay') ? 1 : 0;
        $answerDataJson = json_encode($answerData);

        // Portable upsert (works identically on MySQL and SQLite): the
        // pre-initialization step above guarantees a row already exists
        // for every (attempt_id, question_id) pair on a valid attempt, so
        // this is normally an UPDATE — but check first rather than assume,
        // and fall back to INSERT if no row is found for any reason.
        $existsStmt = $this->db->prepare("SELECT id FROM attempt_answers WHERE attempt_id = ? AND question_id = ?");
        $existsStmt->execute([$attemptId, $questionId]);
        $existingId = $existsStmt->fetchColumn();

        if ($existingId) {
            $updateStmt = $this->db->prepare("
                UPDATE attempt_answers
                SET answer_data = ?, needs_manual_grading = ?
                WHERE id = ?
            ");
            return $updateStmt->execute([$answerDataJson, $needsManualGrading, $existingId]);
        } else {
            $insertStmt = $this->db->prepare("
                INSERT INTO attempt_answers (attempt_id, question_id, answer_data, needs_manual_grading)
                VALUES (?, ?, ?, ?)
            ");
            return $insertStmt->execute([$attemptId, $questionId, $answerDataJson, $needsManualGrading]);
        }
    }

    /**
     * Submit/finalize the attempt
     */
    public function submit($attemptId) {
        $submittedAt = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("
            UPDATE exam_attempts
            SET status = 'submitted', submitted_at = ?
            WHERE id = ? AND status = 'in_progress'
        ");
        return $stmt->execute([$submittedAt, $attemptId]);
    }

    /**
     * Get remaining time in seconds for the attempt
     */
    public function getTimeRemaining($attemptId) {
        $stmt = $this->db->prepare("
            SELECT ea.started_at, e.duration_minutes, ea.status
            FROM exam_attempts ea
            JOIN exams e ON ea.exam_id = e.id
            WHERE ea.id = ?
        ");
        $stmt->execute([$attemptId]);
        $attempt = $stmt->fetch();

        if (!$attempt || $attempt['status'] !== 'in_progress') {
            return 0;
        }

        $startedAt = strtotime($attempt['started_at']);
        $durationSeconds = intval($attempt['duration_minutes']) * 60;
        $expiresAt = $startedAt + $durationSeconds;
        $now = time();

        $remaining = $expiresAt - $now;
        return $remaining > 0 ? $remaining : 0;
    }
}
