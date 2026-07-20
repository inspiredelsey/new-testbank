<?php
/**
 * Gradebook Controller (Admin/Instructor)
 */

require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../models/Enrollment.php';
require_once __DIR__ . '/../models/GradebookItem.php';
require_once __DIR__ . '/../../includes/GradebookCalculator.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Session.php';

class GradebookController {
    private $courseModel;
    private $enrollmentModel;
    private $gradebookItemModel;

    public function __construct() {
        Auth::requireRole(['admin', 'instructor']);
        $this->courseModel = new Course();
        $this->enrollmentModel = new Enrollment();
        $this->gradebookItemModel = new GradebookItem();
    }

    /**
     * Dispatch routing requests based on action parameter.
     */
    public function handleRequest($action = 'index') {
        $csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';

        switch ($action) {
            case 'index':
            case 'list':
                $this->handleIndex();
                break;

            case 'manage':
                $this->handleManage();
                break;

            case 'add_item':
                $this->handleAddItem($csrfToken);
                break;

            case 'edit_item':
                $this->handleEditItem($csrfToken);
                break;

            case 'delete_item':
                $this->handleDeleteItem($csrfToken);
                break;

            case 'grid':
                $this->handleGrid();
                break;

            case 'enter_score':
                $this->handleEnterScore($csrfToken);
                break;

            default:
                header("Location: index.php?route=admin/gradebook&action=index");
                exit;
        }
    }

    /**
     * Validate ownership of the course
     */
    private function requireCourseOwnership($courseId) {
        $user = Auth::user();
        if ($user['role'] === 'admin') {
            return true;
        }
        $course = $this->courseModel->find($courseId);
        if (!$course || (int)$course['instructor_id'] !== (int)$user['id']) {
            header("Location: index.php?route=admin/courses&error=" . urlencode("Access Denied: You do not own this course."));
            exit;
        }
        return true;
    }

    /**
     * List all courses for gradebook selection
     */
    private function handleIndex() {
        $user = Auth::user();
        if ($user['role'] === 'admin') {
            $courses = $this->courseModel->all();
        } else {
            $courses = $this->courseModel->byInstructor($user['id']);
        }
        
        $csrfToken = Session::getCSRFToken();
        include __DIR__ . '/../views/gradebook/courses_list.php';
    }

    /**
     * Setup & CRUD view for gradebook items
     */
    private function handleManage() {
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        $this->requireCourseOwnership($courseId);

        $course = $this->courseModel->find($courseId);
        if (!$course) {
            header("Location: index.php?route=admin/gradebook&error=" . urlencode("Course not found."));
            exit;
        }

        $items = $this->gradebookItemModel->all($courseId);
        $weightSum = $this->gradebookItemModel->getWeightSum($courseId);
        
        $warning = '';
        if ($weightSum < 100.00) {
            $warning = "Warning: The running weight total of all gradebook items is currently " . number_format($weightSum, 2) . "%, which is under 100%. Please adjust weights or add items to reach 100% for correct final course grades.";
        }

        $csrfToken = Session::getCSRFToken();
        include __DIR__ . '/../views/gradebook/manage.php';
    }

    /**
     * Action: Add manual gradebook item
     */
    private function handleAddItem($csrfToken) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?route=admin/gradebook");
            exit;
        }

        if (!Session::validateCSRF($csrfToken)) {
            header("Location: index.php?route=admin/gradebook&error=" . urlencode("CSRF validation failed."));
            exit;
        }

        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $this->requireCourseOwnership($courseId);

        $data = [
            'course_id' => $courseId,
            'title' => trim($_POST['title'] ?? ''),
            'weight' => isset($_POST['weight']) ? floatval($_POST['weight']) : 0.00,
            'max_score' => isset($_POST['max_score']) ? floatval($_POST['max_score']) : 100.00,
            'item_type' => 'manual',
            'item_id' => null
        ];

        try {
            $this->gradebookItemModel->create($data);
            header("Location: index.php?route=admin/gradebook&action=manage&course_id={$courseId}&success=" . urlencode("Manual gradebook item added successfully."));
            exit;
        } catch (Exception $e) {
            header("Location: index.php?route=admin/gradebook&action=manage&course_id={$courseId}&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Action: Edit gradebook item
     */
    private function handleEditItem($csrfToken) {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $item = $this->gradebookItemModel->find($id);
        if (!$item) {
            header("Location: index.php?route=admin/gradebook&error=" . urlencode("Gradebook item not found."));
            exit;
        }

        $courseId = (int)$item['course_id'];
        $this->requireCourseOwnership($courseId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCSRF($csrfToken)) {
                header("Location: index.php?route=admin/gradebook&action=manage&course_id={$courseId}&error=" . urlencode("CSRF validation failed."));
                exit;
            }

            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'weight' => isset($_POST['weight']) ? floatval($_POST['weight']) : 0.00,
                'max_score' => isset($_POST['max_score']) ? floatval($_POST['max_score']) : 100.00
            ];

            try {
                $this->gradebookItemModel->update($id, $data);
                header("Location: index.php?route=admin/gradebook&action=manage&course_id={$courseId}&success=" . urlencode("Gradebook item updated successfully."));
                exit;
            } catch (Exception $e) {
                header("Location: index.php?route=admin/gradebook&action=edit_item&id={$id}&error=" . urlencode($e->getMessage()));
                exit;
            }
        }

        $course = $this->courseModel->find($courseId);
        $csrfToken = Session::getCSRFToken();
        include __DIR__ . '/../views/gradebook/edit_item.php';
    }

    /**
     * Action: Delete manual gradebook item
     */
    private function handleDeleteItem($csrfToken) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?route=admin/gradebook");
            exit;
        }

        if (!Session::validateCSRF($csrfToken)) {
            header("Location: index.php?route=admin/gradebook&error=" . urlencode("CSRF validation failed."));
            exit;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $item = $this->gradebookItemModel->find($id);
        if (!$item) {
            header("Location: index.php?route=admin/gradebook&error=" . urlencode("Gradebook item not found."));
            exit;
        }

        $courseId = (int)$item['course_id'];
        $this->requireCourseOwnership($courseId);

        if ($item['item_type'] === 'quiz') {
            header("Location: index.php?route=admin/gradebook&action=manage&course_id={$courseId}&error=" . urlencode("Quiz items cannot be deleted directly. Unpublish or delete the quiz to remove it."));
            exit;
        }

        try {
            $this->gradebookItemModel->delete($id);
            header("Location: index.php?route=admin/gradebook&action=manage&course_id={$courseId}&success=" . urlencode("Gradebook item deleted successfully."));
            exit;
        } catch (Exception $e) {
            header("Location: index.php?route=admin/gradebook&action=manage&course_id={$courseId}&error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    /**
     * Displays a spreadsheet-style grade grid for all enrolled students
     */
    private function handleGrid() {
        $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
        $this->requireCourseOwnership($courseId);

        $course = $this->courseModel->find($courseId);
        if (!$course) {
            header("Location: index.php?route=admin/gradebook&error=" . urlencode("Course not found."));
            exit;
        }

        $items = $this->gradebookItemModel->all($courseId);
        $students = $this->enrollmentModel->forCourse($courseId);
        $scoresMatrix = $this->gradebookItemModel->getScoresForCourse($courseId);

        // Precompute final grade calculations for each student
        $studentFinalGrades = [];
        foreach ($students as $student) {
            $studentFinalGrades[$student['user_id']] = GradebookCalculator::finalGrade($student['user_id'], $courseId);
        }

        $csrfToken = Session::getCSRFToken();
        include __DIR__ . '/../views/gradebook/grid.php';
    }

    /**
     * Action: Enter manual score for a student
     */
    private function handleEnterScore($csrfToken) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?route=admin/gradebook");
            exit;
        }

        if (!Session::validateCSRF($csrfToken)) {
            header("Location: index.php?route=admin/gradebook&error=" . urlencode("CSRF validation failed."));
            exit;
        }

        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $this->requireCourseOwnership($courseId);

        $gradebookItemId = isset($_POST['gradebook_item_id']) ? (int)$_POST['gradebook_item_id'] : 0;
        $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $scoreInput = $_POST['score'] ?? '';

        $item = $this->gradebookItemModel->find($gradebookItemId);
        if (!$item || (int)$item['course_id'] !== $courseId) {
            header("Location: index.php?route=admin/gradebook&action=grid&course_id={$courseId}&error=" . urlencode("Invalid gradebook item."));
            exit;
        }

        if ($scoreInput === '') {
            // Treat empty as deleting/clearing the score or setting it to 0? Let's check.
            // Standard approach: if they want to clear, delete row, but setting to 0 or throwing validation is safer.
            // Let's require a number.
            header("Location: index.php?route=admin/gradebook&action=grid&course_id={$courseId}&error=" . urlencode("Please provide a valid numeric score."));
            exit;
        }

        $score = floatval($scoreInput);

        try {
            $this->gradebookItemModel->addManualScore($gradebookItemId, $userId, $score);
            header("Location: index.php?route=admin/gradebook&action=grid&course_id={$courseId}&success=" . urlencode("Score recorded successfully."));
            exit;
        } catch (Exception $e) {
            header("Location: index.php?route=admin/gradebook&action=grid&course_id={$courseId}&error=" . urlencode($e->getMessage()));
            exit;
        }
    }
}
