<?php
/**
 * Course Catalog Controller - Test Bank LMS
 * Lets any logged-in user (student or instructor) browse published courses
 * and view a course's public details page before enrolling. Distinct from
 * LearningPathController's 'student/course/view', which shows the actual
 * course CONTENT to students already enrolled — this controller is the
 * "browse and decide" experience that comes before enrollment.
 */

require_once __DIR__ . '/../../admin/models/Course.php';
require_once __DIR__ . '/../../admin/models/LearningPathItem.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Database.php';

class CourseCatalogController {
    private $courseModel;
    private $learningPathItemModel;
    private $db;

    public function __construct() {
        // No blanket Auth::requireLogin() here — this catalog is now the
        // public landing experience too. $user will simply be null for
        // anonymous visitors; all methods below are written to handle that.
        $this->courseModel = new Course();
        $this->learningPathItemModel = new LearningPathItem();
        $this->db = Database::getInstance()->getConnection();
    }

    public function handleRequest($action = 'list') {
        switch ($action) {
            case 'details':
                $this->handleDetails();
                break;
            case 'list':
            default:
                $this->handleList();
                break;
        }
    }

    private function handleList() {
        $user = Auth::getUser(); // null if not logged in — that's fine here
        $search = trim($_GET['search'] ?? '');

        $filters = ['status' => 'published'];
        if ($search !== '') {
            $filters['search'] = $search;
        }
        $courses = $this->courseModel->getAll($filters);

        // Mark which courses this user is already enrolled in, so the
        // catalog can show "Enrolled" instead of "View Details" where
        // relevant. Anonymous visitors are never "enrolled" in anything.
        foreach ($courses as &$course) {
            $course['is_enrolled'] = $user ? $this->courseModel->isEnrolled($course['id'], $user['id']) : false;
        }
        unset($course);

        include __DIR__ . '/../views/courses/catalog.php';
    }

    private function handleDetails() {
        $user = Auth::getUser(); // null if not logged in
        $courseId = intval($_GET['id'] ?? 0);
        if ($courseId <= 0) {
            $this->renderError('Invalid course.');
            return;
        }

        $course = $this->courseModel->find($courseId);
        if (!$course || $course['status'] !== 'published') {
            $this->renderError('This course is not available.');
            return;
        }

        $isEnrolled = $user ? $this->courseModel->isEnrolled($courseId, $user['id']) : false;
        $pathItems = $this->learningPathItemModel->forCourse($courseId);

        // Count of published exams attached to this course, for a quick
        // "what's involved" summary on the details page.
        $examStmt = $this->db->prepare("SELECT COUNT(*) FROM exams WHERE course_id = ? AND status = 'published'");
        $examStmt->execute([$courseId]);
        $examCount = intval($examStmt->fetchColumn());

        // Enrolled student count, shown as light social proof.
        $enrollStmt = $this->db->prepare("SELECT COUNT(*) FROM course_enrollments WHERE course_id = ? AND status = 'active'");
        $enrollStmt->execute([$courseId]);
        $enrolledCount = intval($enrollStmt->fetchColumn());

        include __DIR__ . '/../views/courses/details.php';
    }

    private function renderError($msg) {
        http_response_code(404);
        echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>";
        echo "<h2>Not Found</h2>";
        echo "<p>" . htmlspecialchars($msg) . "</p>";
        echo "<p><a href='index.php?route=courses'>Back to Course Catalog</a></p>";
        echo "</div>";
    }
}
