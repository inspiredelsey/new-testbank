<?php
/**
 * Analytics Controller - Test Bank LMS
 * Exposes dashboards and details for course and exam analytics.
 */

require_once __DIR__ . '/../models/Analytics.php';
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../models/Exam.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Session.php';

class AnalyticsController {
    private $analyticsModel;
    private $courseModel;
    private $examModel;

    public function __construct() {
        Auth::requireRole(['admin', 'instructor']);
        $this->analyticsModel = new Analytics();
        $this->courseModel = new Course();
        $this->examModel = new Exam();
    }

    /**
     * Handle routing logic.
     */
    public function handleRequest($action = 'dashboard') {
        switch ($action) {
            case 'dashboard':
                $this->handleDashboard();
                break;
            case 'exam-detail':
                $this->handleExamDetail();
                break;
            default:
                header("Location: index.php?route=admin/analytics&action=dashboard");
                exit;
        }
    }

    /**
     * Dashboard view listing course analytics summary.
     */
    private function handleDashboard() {
        $user = Auth::user();
        
        // 1. Fetch available courses based on role
        if ($user['role'] === 'admin') {
            $courses = $this->courseModel->all();
        } else {
            $courses = $this->courseModel->byInstructor($user['id']);
        }

        // 2. Select default/active course
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        if ($courseId <= 0 && !empty($courses)) {
            $courseId = (int)$courses[0]['id'];
        }

        $activeCourse = null;
        $completionRate = null;
        $quizScores = null;
        $timeSpent = null;
        $activityOverTime = null;

        if ($courseId > 0) {
            // Find course detail and verify ownership
            $activeCourse = $this->courseModel->find($courseId);
            if (!$activeCourse) {
                header("Location: index.php?route=admin/analytics&action=dashboard&error=" . urlencode("Course not found."));
                exit;
            }
            $this->requireCourseOwnershipOrAdmin($activeCourse);

            // Retrieve aggregates
            $completionRate = $this->analyticsModel->courseCompletionRate($courseId);
            $quizScores = $this->analyticsModel->courseQuizScoreDistribution($courseId);
            $timeSpent = $this->analyticsModel->studentTimeSpent($courseId);
            $activityOverTime = $this->analyticsModel->courseActivityOverTime($courseId);
            $recentActivity = $this->analyticsModel->recentActivity($courseId);
        }

        // Render dashboard
        include __DIR__ . '/../views/analytics/dashboard.php';
    }

    /**
     * Detailed analysis view for a specific exam.
     */
    private function handleExamDetail() {
        $examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
        $exam = $this->examModel->find($examId);

        if (!$exam) {
            header("Location: index.php?route=admin/analytics&action=dashboard&error=" . urlencode("Exam not found."));
            exit;
        }

        // Load Course context for checking ownership
        $courseId = (int)$exam['course_id'];
        $course = $this->courseModel->find($courseId);
        if (!$course) {
            header("Location: index.php?route=admin/analytics&action=dashboard&error=" . urlencode("Exam's course context not found."));
            exit;
        }

        $this->requireCourseOwnershipOrAdmin($course);

        // Fetch question-level difficulty analytics
        $questionStats = $this->analyticsModel->questionDifficultyAnalysis($examId);

        // Render exam detail view
        include __DIR__ . '/../views/analytics/exam-detail.php';
    }

    /**
     * Enforce course ownership for instructors.
     */
    private function requireCourseOwnershipOrAdmin($course) {
        $user = Auth::user();
        if ($user['role'] !== 'admin' && (int)$course['instructor_id'] !== (int)$user['id']) {
            http_response_code(403);
            echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>";
            echo "<h2 style='color:#dc3545; font-weight: 700;'>403 - Access Forbidden</h2>";
            echo "<p style='color:#6c757d;'>You do not have permission to view analytics for this course because it belongs to another instructor.</p>";
            echo "<p style='margin-top:20px;'><a href='index.php?route=admin/analytics' style='color:#0d6efd; text-decoration:none; font-weight:600;'>&larr; Return to Analytics</a></p>";
            echo "</div>";
            exit;
        }
    }
}
