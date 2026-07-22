<?php
/**
 * Attempt Model
 */

require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/Exam.php';
require_once __DIR__ . '/ExamQuestion.php';

class Attempt {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT ea.*, e.title as exam_title, e.duration_minutes, e.pass_percentage, u.name as student_name
            FROM exam_attempts ea
            JOIN exams e ON ea.exam_id = e.id
            JOIN users u ON ea.user_id = u.id
            WHERE ea.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get attempts for a specific student
     */
    public function getStudentHistory($userId, $examId = null) {
        $query = "
            SELECT ea.*, e.title as exam_title, e.duration_minutes 
            FROM exam_attempts ea
            JOIN exams e ON ea.exam_id = e.id
            WHERE ea.user_id = ?
        ";
        $params = [$userId];

        if ($examId !== null) {
            $query .= " AND ea.exam_id = ?";
            $params[] = $examId;
        }

        $query .= " ORDER BY ea.started_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Check if a student has attempts left for an exam
     */
    public function countAttempts($userId, $examId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM exam_attempts WHERE user_id = ? AND exam_id = ?");
        $stmt->execute([$userId, $examId]);
        return intval($stmt->fetchColumn());
    }

    /**
     * Start a new attempt, resolving the static question list at this point
     */
    public function startAttempt($userId, $examId) {
        $examModel = new Exam();
        $exam = $examModel->getById($examId);
        if (!$exam) throw new Exception("Exam not found.");

        // Check attempt limits
        if (intval($exam['max_attempts']) > 0) {
            $existingCount = $this->countAttempts($userId, $examId);
            if ($existingCount >= intval($exam['max_attempts'])) {
                throw new Exception("You have reached the maximum number of attempts for this exam.");
            }
        }

        // Resolve question set
        $questionIds = $examModel->resolveQuestionSet($examId);
        if (empty($questionIds)) {
            throw new Exception("This exam does not contain any questions yet.");
        }

        $resolvedQuestionsJson = json_encode($questionIds);

        $stmt = $this->db->prepare("
            INSERT INTO exam_attempts (exam_id, user_id, started_at, status, resolved_question_ids)
            VALUES (?, ?, NOW(), 'in_progress', ?)
        ");
        $stmt->execute([$examId, $userId, $resolvedQuestionsJson]);
        $attemptId = $this->db->lastInsertId();

        // Initialize attempt_answers row for each question in the resolved set
        $stmtAnswer = $this->db->prepare("
            INSERT INTO attempt_answers (attempt_id, question_id, answer_data, needs_manual_grading)
            VALUES (?, ?, NULL, 0)
        ");
        foreach ($questionIds as $qId) {
            $stmtAnswer->execute([$attemptId, $qId]);
        }

        // Log quiz started
        require_once __DIR__ . '/../../includes/ActivityLogger.php';
        ActivityLogger::log($userId, 'quiz_started', $exam['course_id'], 'exam', $examId);

        return $attemptId;
    }

    /**
     * Get answers saved so far for an attempt
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
     * Save an answer to a question (autosave / प्रगति)
     */
    public function saveAnswer($attemptId, $questionId, $answerData) {
        // First ensure attempt is still active
        $attempt = $this->getById($attemptId);
        if (!$attempt || $attempt['status'] !== 'in_progress') {
            return false;
        }

        $jsonAnswer = json_encode($answerData);

        // Check if question is essay (marks needs_manual_grading)
        $stmtQ = $this->db->prepare("SELECT type FROM questions WHERE id = ?");
        $stmtQ->execute([$questionId]);
        $qType = $stmtQ->fetchColumn();
        $needsManual = ($qType === 'essay') ? 1 : 0;

        $stmt = $this->db->prepare("
            UPDATE attempt_answers 
            SET answer_data = ?, needs_manual_grading = ? 
            WHERE attempt_id = ? AND question_id = ?
        ");
        return $stmt->execute([$jsonAnswer, $needsManual, $attemptId, $questionId]);
    }

    /**
     * Get time remaining in seconds for an active attempt
     */
    public function getTimeRemaining($attemptId) {
        $attempt = $this->getById($attemptId);
        if (!$attempt || $attempt['status'] !== 'in_progress') {
            return 0;
        }

        $durationMinutes = intval($attempt['duration_minutes']);
        $startTime = strtotime($attempt['started_at']);
        $endTime = $startTime + ($durationMinutes * 60);
        $currentTime = time();

        $remaining = $endTime - $currentTime;
        return $remaining > 0 ? $remaining : 0;
    }

    /**
     * Grading queue: list of essay answers needing grading
     */
    public function getGradingQueue($instructorUserId = null) {
        $query = "
            SELECT aa.*, q.question_text, q.points as max_points, ea.started_at, u.name as student_name, e.title as exam_title
            FROM attempt_answers aa
            JOIN questions q ON aa.question_id = q.id
            JOIN exam_attempts ea ON aa.attempt_id = ea.id
            JOIN exams e ON ea.exam_id = e.id
            JOIN users u ON ea.user_id = u.id
            WHERE aa.needs_manual_grading = 1 AND ea.status = 'submitted'
        ";
        $params = [];

        if ($instructorUserId !== null) {
            $query .= " AND e.created_by = ?";
            $params[] = $instructorUserId;
        }

        $query .= " ORDER BY ea.started_at ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Award score to an essay answer and finalize
     */
    public function gradeEssayAnswer($answerId, $pointsAwarded) {
        $stmt = $this->db->prepare("UPDATE attempt_answers SET points_awarded = ?, needs_manual_grading = 0, is_correct = ? WHERE id = ?");
        
        // Find maximum points for this question to calculate is_correct
        $stmtMax = $this->db->prepare("SELECT q.points, aa.attempt_id FROM attempt_answers aa JOIN questions q ON aa.question_id = q.id WHERE aa.id = ?");
        $stmtMax->execute([$answerId]);
        $row = $stmtMax->fetch();
        if (!$row) return false;

        $isCorrect = (floatval($pointsAwarded) >= (floatval($row['points']) / 2)) ? 1 : 0; // standard correct definition

        $stmt->execute([$pointsAwarded, $isCorrect, $answerId]);

        // Recalculate total score for this attempt
        require_once __DIR__ . '/../../includes/Grader.php';
        Grader::recalculateAttemptScore($row['attempt_id']);

        return true;
    }

    /**
     * Stats per exam: List of attempts, average score, pass percentage, etc.
     */
    public function getExamResultsDashboard($examId) {
        $stmt = $this->db->prepare("
            SELECT ea.*, u.name as student_name, u.email as student_email
            FROM exam_attempts ea
            JOIN users u ON ea.user_id = u.id
            WHERE ea.exam_id = ? AND ea.status = 'graded'
            ORDER BY ea.percentage DESC
        ");
        $stmt->execute([$examId]);
        $attempts = $stmt->fetchAll();

        // Calculate distribution
        $distribution = [
            'fail' => 0, // < pass%
            'pass_low' => 0, // pass% to 75%
            'pass_mid' => 0, // 75% to 90%
            'pass_high' => 0 // 90%+
        ];

        $stmtExam = $this->db->prepare("SELECT pass_percentage FROM exams WHERE id = ?");
        $stmtExam->execute([$examId]);
        $passPercentage = floatval($stmtExam->fetchColumn() ?: 50.00);

        $totalScore = 0;
        $passedCount = 0;
        $totalCount = count($attempts);

        foreach ($attempts as $att) {
            $pct = floatval($att['percentage']);
            $totalScore += $pct;

            if ($pct < $passPercentage) {
                $distribution['fail']++;
            } else {
                $passedCount++;
                if ($pct < 75) {
                    $distribution['pass_low']++;
                } else if ($pct < 90) {
                    $distribution['pass_mid']++;
                } else {
                    $distribution['pass_high']++;
                }
            }
        }

        $avgScore = $totalCount > 0 ? ($totalScore / $totalCount) : 0;
        $passRate = $totalCount > 0 ? ($passedCount / $totalCount) * 100 : 0;

        return [
            'attempts' => $attempts,
            'summary' => [
                'total_attempts' => $totalCount,
                'average_score' => $avgScore,
                'pass_rate' => $passRate,
            ],
            'distribution' => $distribution
        ];
    }

    /**
     * Item analysis: difficulty analysis per question (% of students who got it correct)
     */
    public function getQuestionDifficultyAnalysis($examId) {
        // Find all questions associated with this exam's graded attempts
        $stmt = $this->db->prepare("
            SELECT q.id, q.question_text, q.type, q.points,
                   SUM(CASE WHEN aa.is_correct = 1 THEN 1 ELSE 0 END) as correct_count,
                   COUNT(aa.id) as total_answers
            FROM attempt_answers aa
            JOIN questions q ON aa.question_id = q.id
            JOIN exam_attempts ea ON aa.attempt_id = ea.id
            WHERE ea.exam_id = ? AND ea.status = 'graded'
            GROUP BY q.id
        ");
        $stmt->execute([$examId]);
        $rows = $stmt->fetchAll();

        $analysis = [];
        foreach ($rows as $row) {
            $total = intval($row['total_answers']);
            $correct = intval($row['correct_count']);
            $successRate = $total > 0 ? ($correct / $total) * 100 : 0.00;
            
            $analysis[] = [
                'question_id' => $row['id'],
                'question_text' => $row['question_text'],
                'type' => $row['type'],
                'points' => $row['points'],
                'success_rate' => $successRate,
                'correct_count' => $correct,
                'total_count' => $total,
                // A lower success rate means HIGHER difficulty
                'difficulty_index' => 100 - $successRate
            ];
        }

        // Sort by success rate ascending (hardest questions first)
        usort($analysis, function($a, $b) {
            return $a['success_rate'] <=> $b['success_rate'];
        });

        return $analysis;
    }
}
