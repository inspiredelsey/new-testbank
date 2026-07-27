<?php
/**
 * Course Catalog Controller - Test Bank LMS
 * Lets any logged-in user or public visitor browse published courses,
 * filter by categories/search, and view details or enroll.
 */

require_once __DIR__ . '/../../admin/models/Course.php';
require_once __DIR__ . '/../../admin/models/Category.php';
require_once __DIR__ . '/../../admin/models/LearningPathItem.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Database.php';

class CourseCatalogController {
    private $courseModel;
    private $learningPathItemModel;
    private $db;

    public function __construct() {
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
        $user = Auth::getUser(); // null if not logged in
        $search = trim($_GET['search'] ?? '');
        $selectedCategory = intval($_GET['category_id'] ?? 0);
        $sort = trim($_GET['sort'] ?? 'newest');

        $filters = ['status' => 'published'];
        if ($search !== '') {
            $filters['search'] = $search;
        }
        if ($selectedCategory > 0) {
            $filters['category_id'] = $selectedCategory;
        }

        $courses = $this->courseModel->getAll($filters);

        // Sorting
        if ($sort === 'popular') {
            usort($courses, function($a, $b) {
                return intval($b['enrollment_count'] ?? 0) <=> intval($a['enrollment_count'] ?? 0);
            });
        } elseif ($sort === 'title') {
            usort($courses, function($a, $b) {
                return strcasecmp($a['title'], $b['title']);
            });
        }

        // Mark enrollment
        foreach ($courses as &$course) {
            $course['is_enrolled'] = $user ? $this->courseModel->isEnrolled($course['id'], $user['id']) : false;
        }
        unset($course);

        // Categories list
        $categoryModel = new Category();
        $categories = $categoryModel->all();

        // Platform stats
        $statsStmt = $this->db->query("
            SELECT 
                (SELECT COUNT(*) FROM courses WHERE status = 'published') as course_count,
                (SELECT COUNT(*) FROM exams WHERE status = 'published') as exam_count,
                (SELECT COUNT(DISTINCT student_id) FROM course_enrollments) as student_count,
                (SELECT COUNT(*) FROM questions) as question_count
        ");
        $stats = $statsStmt ? $statsStmt->fetch(PDO::FETCH_ASSOC) : [
            'course_count' => count($courses),
            'exam_count' => 0,
            'student_count' => 0,
            'question_count' => 0
        ];

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
