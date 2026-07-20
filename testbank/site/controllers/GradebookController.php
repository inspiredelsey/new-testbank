<?php
/**
 * Gradebook Controller (Student/Site)
 */

require_once __DIR__ . '/../../admin/models/Course.php';
require_once __DIR__ . '/../../admin/models/Enrollment.php';
require_once __DIR__ . '/../../includes/GradebookCalculator.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Session.php';

class GradebookController {
    private $courseModel;
    private $enrollmentModel;

    public function __construct() {
        Auth::requireLogin();
        $this->courseModel = new Course();
        $this->enrollmentModel = new Enrollment();
    }

    /**
     * Dispatch student routing requests
     */
    public function handleRequest($action = 'mygrades') {
        switch ($action) {
            case 'mygrades':
            case 'index':
                $this->handleMyGrades();
                break;

            default:
                header("Location: index.php?route=student/dashboard");
                exit;
        }
    }

    /**
     * Shows student grades for a specific course, or a list of courses if none specified
     */
    private function handleMyGrades() {
        $user = Auth::user();
        $userId = $user['id'];
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

        if ($courseId > 0) {
            // View grades for a specific course
            // First verify student enrollment (admins/instructors bypass check)
            if ($user['role'] === 'student') {
                $enrollments = $this->enrollmentModel->forUser($userId);
                $isEnrolled = false;
                foreach ($enrollments as $e) {
                    if ((int)$e['course_id'] === $courseId) {
                        $isEnrolled = true;
                        break;
                    }
                }
                if (!$isEnrolled) {
                    header("Location: index.php?route=student/gradebook&error=" . urlencode("Access Denied: You are not enrolled in this course."));
                    exit;
                }
            }

            $course = $this->courseModel->find($courseId);
            if (!$course) {
                header("Location: index.php?route=student/gradebook&error=" . urlencode("Course not found."));
                exit;
            }

            // Compute the weighted final grade and breakdown
            $gradeData = GradebookCalculator::finalGrade($userId, $courseId);

            $csrfToken = Session::getCSRFToken();
            include __DIR__ . '/../views/gradebook/mygrades.php';
        } else {
            // List all enrolled courses with quick final grades
            $enrollments = $this->enrollmentModel->forUser($userId);
            
            $coursesWithGrades = [];
            foreach ($enrollments as $e) {
                $cId = (int)$e['course_id'];
                $gradeData = GradebookCalculator::finalGrade($userId, $cId);
                $coursesWithGrades[] = [
                    'course_id' => $cId,
                    'course_title' => $e['course_title'],
                    'instructor_name' => $e['instructor_name'],
                    'enrolled_at' => $e['enrolled_at'],
                    'grade_data' => $gradeData
                ];
            }

            $csrfToken = Session::getCSRFToken();
            include __DIR__ . '/../views/gradebook/courses_list.php';
        }
    }
}
