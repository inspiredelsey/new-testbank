<?php
/**
 * Analytics Model - Test Bank LMS
 * Performs robust aggregate SQL-based reporting for courses, exams, and students.
 */

require_once __DIR__ . '/../../includes/Database.php';

class Analytics {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Helper to resolve platform-specific datetime difference in seconds.
     */
    private function getTimeDiffSql($startCol, $endCol) {
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            return "(strftime('%s', $endCol) - strftime('%s', $startCol))";
        } else {
            return "TIMESTAMPDIFF(SECOND, $startCol, $endCol)";
        }
    }

    /**
     * Compute course enrollment, status, and learning path progress aggregates.
     */
    public function courseCompletionRate($courseId) {
        // Enrollment status counts
        $stmtEnrollments = $this->db->prepare("
            SELECT 
                COUNT(*) as total_enrolled,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN status = 'dropped' THEN 1 ELSE 0 END) as dropped_count
            FROM course_enrollments
            WHERE course_id = ?
        ");
        $stmtEnrollments->execute([$courseId]);
        $summary = $stmtEnrollments->fetch(PDO::FETCH_ASSOC) ?: [
            'total_enrolled' => 0,
            'completed_count' => 0,
            'active_count' => 0,
            'dropped_count' => 0
        ];

        // Fetch learning path progress for all students
        $stmtProgress = $this->db->prepare("
            SELECT 
                u.id as user_id,
                u.name as student_name,
                COUNT(lpi.id) as total_items,
                SUM(CASE WHEN lpp.status = 'completed' THEN 1 ELSE 0 END) as completed_items
            FROM course_enrollments ce
            JOIN users u ON ce.student_id = u.id
            LEFT JOIN learning_path_items lpi ON ce.course_id = lpi.course_id
            LEFT JOIN learning_path_progress lpp ON u.id = lpp.user_id AND lpi.id = lpp.learning_path_item_id
            WHERE ce.course_id = ?
            GROUP BY u.id, u.name
        ");
        $stmtProgress->execute([$courseId]);
        $studentProgressList = $stmtProgress->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $totalStudentsWithProgress = count($studentProgressList);
        $averageProgressPercent = 0;
        if ($totalStudentsWithProgress > 0) {
            $totalPercent = 0;
            foreach ($studentProgressList as $sp) {
                $total = intval($sp['total_items']);
                $completed = intval($sp['completed_items']);
                $pct = $total > 0 ? round(($completed / $total) * 100) : 0;
                $totalPercent += $pct;
            }
            $averageProgressPercent = round($totalPercent / $totalStudentsWithProgress);
        }

        return [
            'enrollment_summary' => $summary,
            'students_progress' => $studentProgressList,
            'average_progress_percent' => $averageProgressPercent
        ];
    }

    /**
     * Compute score distribution and averages for quizzes in a course.
     */
    public function courseQuizScoreDistribution($courseId) {
        $stmtExams = $this->db->prepare("
            SELECT 
                e.id as exam_id,
                e.title as exam_title,
                COUNT(ea.id) as total_attempts,
                SUM(CASE WHEN ea.percentage < 60 THEN 1 ELSE 0 END) as range_f,
                SUM(CASE WHEN ea.percentage >= 60 AND ea.percentage < 70 THEN 1 ELSE 0 END) as range_d,
                SUM(CASE WHEN ea.percentage >= 70 AND ea.percentage < 80 THEN 1 ELSE 0 END) as range_c,
                SUM(CASE WHEN ea.percentage >= 80 AND ea.percentage < 90 THEN 1 ELSE 0 END) as range_b,
                SUM(CASE WHEN ea.percentage >= 90 THEN 1 ELSE 0 END) as range_a,
                AVG(ea.percentage) as average_percentage,
                MIN(ea.percentage) as min_percentage,
                MAX(ea.percentage) as max_percentage
            FROM exams e
            LEFT JOIN exam_attempts ea ON e.id = ea.exam_id AND ea.status IN ('graded', 'submitted')
            WHERE e.course_id = ?
            GROUP BY e.id, e.title
        ");
        $stmtExams->execute([$courseId]);
        return $stmtExams->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Analyze individual question statistics (difficulty, correct attempts ratio).
     */
    public function questionDifficultyAnalysis($examId) {
        $stmt = $this->db->prepare("
            SELECT 
                q.id as question_id,
                q.question_text,
                q.type as question_type,
                COUNT(aa.id) as times_answered,
                SUM(CASE WHEN aa.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
                AVG(aa.points_awarded) as avg_points,
                q.points as max_points
            FROM questions q
            JOIN attempt_answers aa ON q.id = aa.question_id
            JOIN exam_attempts ea ON aa.attempt_id = ea.id
            WHERE ea.exam_id = ? AND ea.status IN ('graded', 'submitted')
            GROUP BY q.id, q.question_text, q.type, q.points
        ");
        $stmt->execute([$examId]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($questions as &$q) {
            $times = intval($q['times_answered']);
            $correct = intval($q['correct_answers']);
            $q['success_rate'] = $times > 0 ? round(($correct / $times) * 100, 1) : 0;
            
            if ($times === 0) {
                $q['difficulty'] = 'N/A';
            } elseif ($q['success_rate'] < 40) {
                $q['difficulty'] = 'Hard';
            } elseif ($q['success_rate'] > 80) {
                $q['difficulty'] = 'Easy';
            } else {
                $q['difficulty'] = 'Medium';
            }
        }
        return $questions;
    }

    /**
     * Aggregate time spent on exam attempts and total actions logged for students.
     */
    public function studentTimeSpent($courseId) {
        $timeDiffSql = $this->getTimeDiffSql('ea.started_at', 'ea.submitted_at');

        $stmt = $this->db->prepare("
            SELECT 
                u.id as user_id,
                u.name as student_name,
                u.email as student_email,
                (SELECT COUNT(*) FROM activity_log WHERE user_id = u.id AND course_id = ?) as activity_count,
                SUM(CASE WHEN ea.started_at IS NOT NULL AND ea.submitted_at IS NOT NULL THEN {$timeDiffSql} ELSE 0 END) as exam_time_spent
            FROM course_enrollments ce
            JOIN users u ON ce.student_id = u.id
            LEFT JOIN exam_attempts ea ON u.id = ea.user_id
            LEFT JOIN exams e ON ea.exam_id = e.id AND e.course_id = ce.course_id
            WHERE ce.course_id = ?
            GROUP BY u.id, u.name, u.email
        ");
        $stmt->execute([$courseId, $courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Aggregate activity counts over the last N days for trend chart.
     */
    public function courseActivityOverTime($courseId, $days = 30) {
        $startDate = date('Y-m-d H:i:s', strtotime("-$days days"));

        $stmt = $this->db->prepare("
            SELECT 
                DATE(created_at) as activity_date,
                COUNT(*) as activity_count,
                SUM(CASE WHEN action = 'login' THEN 1 ELSE 0 END) as login_count,
                SUM(CASE WHEN action = 'quiz_started' THEN 1 ELSE 0 END) as quiz_start_count,
                SUM(CASE WHEN action = 'quiz_submitted' THEN 1 ELSE 0 END) as quiz_submit_count,
                SUM(CASE WHEN action = 'document_viewed' THEN 1 ELSE 0 END) as document_view_count,
                SUM(CASE WHEN action = 'link_opened' THEN 1 ELSE 0 END) as link_open_count,
                SUM(CASE WHEN action = 'learning_path_item_completed' THEN 1 ELSE 0 END) as lp_complete_count,
                SUM(CASE WHEN action = 'certificate_issued' THEN 1 ELSE 0 END) as certificate_issue_count
            FROM activity_log
            WHERE course_id = ? AND created_at >= ?
            GROUP BY DATE(created_at)
            ORDER BY activity_date ASC
        ");
        $stmt->execute([$courseId, $startDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Retrieve the last N recent activity log entries for a course.
     */
    public function recentActivity($courseId, $limit = 20) {
        $stmt = $this->db->prepare("
            SELECT al.*, u.name as student_name, u.email as student_email
            FROM activity_log al
            JOIN users u ON al.user_id = u.id
            WHERE al.course_id = ?
            ORDER BY al.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $courseId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
