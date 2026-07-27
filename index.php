<?php
/**
 * Test Bank LMS - Primary Front Controller Routing Gateway
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/testbank/includes/Session.php';
require_once __DIR__ . '/testbank/includes/Auth.php';

// Initialize session and token securely
Session::start();

$route = $_GET['route'] ?? '';

// API / AJAX Check (save_answer is a POST request, does not use layout headers/footers)
if ($route === 'student/exam/save_answer') {
    require_once __DIR__ . '/testbank/admin/controllers/StudentController.php';
    $controller = new StudentController();
    $controller->handleRequest('save_answer');
    exit;
}

// Global Auth redirection
if (empty($route)) {
    if (Auth::isLoggedIn()) {
        if (Auth::isInstructor()) {
            header("Location: index.php?route=admin/courses");
        } else {
            header("Location: index.php?route=student/dashboard");
        }
    } else {
        // Anonymous visitors now land on the public course catalog
        // (browse/search freely) instead of being forced to log in first.
        // Logged-in behavior above is completely unchanged.
        header("Location: index.php?route=courses");
    }
    exit;
}

if ($route === 'admin') {
    if (Auth::isInstructor()) {
        header("Location: index.php?route=admin/courses");
    } else {
        header("Location: index.php?route=student/dashboard");
    }
    exit;
}

// Route Dispatcher Map
switch ($route) {
    // ---------------- AUTH ROUTES ----------------
    case 'login':
    case 'staff-login':
    case 'register':
    case 'logout':
        require_once __DIR__ . '/testbank/admin/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->handleRequest($route);
        break;

    // ---------------- ADMIN / INSTRUCTOR ROUTES ----------------
    case 'admin/download_schema':
        Auth::requireRole(['admin', 'instructor']);
        $file = __DIR__ . '/sql/complete_schema.sql';
        if (file_exists($file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="complete_schema.sql"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            exit;
        } else {
            http_response_code(404);
            echo "Database schema file not found.";
            exit;
        }

    case 'admin/courses':
        require_once __DIR__ . '/testbank/admin/controllers/CourseController.php';
        $controller = new CourseController();
        $action = $_GET['action'] ?? 'index';
        $controller->handleRequest($action);
        break;

    case 'admin/enrollments':
        require_once __DIR__ . '/testbank/admin/controllers/EnrollmentController.php';
        $controller = new EnrollmentController();
        $action = $_GET['action'] ?? 'manage';
        $controller->handleRequest($action);
        break;

    case 'admin/documents':
        require_once __DIR__ . '/testbank/admin/controllers/DocumentController.php';
        $controller = new DocumentController();
        $action = $_GET['action'] ?? 'list';
        $controller->handleRequest($action);
        break;

    case 'admin/links':
        require_once __DIR__ . '/testbank/admin/controllers/LinkController.php';
        $controller = new LinkController();
        $action = $_GET['action'] ?? 'list';
        $controller->handleRequest($action);
        break;

    case 'admin/learning-path':
        require_once __DIR__ . '/testbank/admin/controllers/LearningPathController.php';
        $controller = new LearningPathController();
        $action = $_GET['action'] ?? 'list';
        $controller->handleRequest($action);
        break;

    case 'admin/categories':
        require_once __DIR__ . '/testbank/admin/controllers/CategoryController.php';
        $controller = new CategoryController();
        $action = $_GET['action'] ?? 'index';
        $controller->handleRequest($action);
        break;

    case 'admin/questions':
        require_once __DIR__ . '/testbank/admin/controllers/QuestionController.php';
        $controller = new QuestionController();
        $action = $_GET['action'] ?? 'list';
        $controller->handleRequest($action);
        break;

    case 'admin/cases':
        require_once __DIR__ . '/testbank/admin/controllers/CaseStudyController.php';
        $controller = new CaseStudyController();
        $action = $_GET['action'] ?? 'list';
        $controller->handleRequest($action);
        break;

    case 'admin/exams':
        require_once __DIR__ . '/testbank/admin/controllers/ExamController.php';
        $controller = new ExamController();
        $action = $_GET['action'] ?? 'index';
        $controller->handleRequest($action);
        break;

    case 'admin/attempts':
        require_once __DIR__ . '/testbank/admin/controllers/AdminAttemptController.php';
        $controller = new AdminAttemptController();
        $action = $_GET['action'] ?? 'index';
        $controller->handleRequest($action);
        break;

    case 'admin/essay-grading':
        require_once __DIR__ . '/testbank/admin/controllers/EssayGradingController.php';
        $controller = new EssayGradingController();
        $action = $_GET['action'] ?? 'list';
        $controller->handleRequest($action);
        break;

    case 'admin/gradebook':
        require_once __DIR__ . '/testbank/admin/controllers/GradebookController.php';
        $controller = new GradebookController();
        $action = $_GET['action'] ?? 'index';
        $controller->handleRequest($action);
        break;

    case 'admin/certificates':
        require_once __DIR__ . '/testbank/admin/controllers/CertificateController.php';
        $controller = new CertificateController();
        $action = $_GET['action'] ?? 'list';
        $controller->handleRequest($action);
        break;

    case 'admin/users':
        require_once __DIR__ . '/testbank/admin/controllers/UserController.php';
        $controller = new UserController();
        $action = $_GET['action'] ?? 'list';
        $controller->handleRequest($action);
        break;

    case 'admin/analytics':
        require_once __DIR__ . '/testbank/admin/controllers/AnalyticsController.php';
        $controller = new AnalyticsController();
        $action = $_GET['action'] ?? 'dashboard';
        $controller->handleRequest($action);
        break;

    // ---------------- STUDENT ROUTES ----------------
    case 'courses':
        require_once __DIR__ . '/testbank/site/controllers/CourseCatalogController.php';
        $controller = new CourseCatalogController();
        $controller->handleRequest('list');
        break;

    case 'course/details':
        require_once __DIR__ . '/testbank/site/controllers/CourseCatalogController.php';
        $controller = new CourseCatalogController();
        $controller->handleRequest('details');
        break;

    case 'course/checkout':
        require_once __DIR__ . '/testbank/site/controllers/CourseCatalogController.php';
        $controller = new CourseCatalogController();
        $controller->handleRequest('checkout');
        break;

    case 'course/checkout_submit':
        require_once __DIR__ . '/testbank/site/controllers/CourseCatalogController.php';
        $controller = new CourseCatalogController();
        $controller->handleRequest('checkout_submit');
        break;

    case 'course/checkout/callback':
        require_once __DIR__ . '/testbank/site/controllers/CourseCatalogController.php';
        $controller = new CourseCatalogController();
        $controller->handleRequest('checkout_callback');
        break;

    case 'student/course/view':
        require_once __DIR__ . '/testbank/site/controllers/LearningPathController.php';
        $controller = new LearningPathController();
        $controller->handleRequest('view');
        break;

    case 'student/learning-path/access':
        require_once __DIR__ . '/testbank/site/controllers/LearningPathController.php';
        $controller = new LearningPathController();
        $controller->handleRequest('access');
        break;

    case 'student/course/complete_lp_item':
        require_once __DIR__ . '/testbank/site/controllers/LearningPathController.php';
        $controller = new LearningPathController();
        $controller->handleRequest('complete_lp_item');
        break;

    case 'student/dashboard':
        require_once __DIR__ . '/testbank/admin/controllers/StudentController.php';
        $controller = new StudentController();
        $controller->handleRequest('dashboard');
        break;

    case 'student/exam/instructions':
        require_once __DIR__ . '/testbank/admin/controllers/StudentController.php';
        $controller = new StudentController();
        $controller->handleRequest('instructions');
        break;

    case 'student/exam/start':
        require_once __DIR__ . '/testbank/site/controllers/AttemptController.php';
        $controller = new AttemptController();
        $controller->handleRequest('start');
        break;

    case 'student/exam/take':
        require_once __DIR__ . '/testbank/site/controllers/AttemptController.php';
        $controller = new AttemptController();
        $controller->handleRequest('take');
        break;

    case 'student/exam/submit':
        require_once __DIR__ . '/testbank/site/controllers/AttemptController.php';
        $controller = new AttemptController();
        $controller->handleRequest('submit');
        break;

    case 'student/exam/pending':
        require_once __DIR__ . '/testbank/site/controllers/AttemptController.php';
        $controller = new AttemptController();
        $controller->handleRequest('pending');
        break;

    case 'student/exam/review':
        require_once __DIR__ . '/testbank/site/controllers/AttemptController.php';
        $controller = new AttemptController();
        $controller->handleRequest('review');
        break;

    case 'student/gradebook':
        require_once __DIR__ . '/testbank/site/controllers/GradebookController.php';
        $controller = new GradebookController();
        $action = $_GET['action'] ?? 'mygrades';
        $controller->handleRequest($action);
        break;

    case 'student/certificates':
        require_once __DIR__ . '/testbank/site/controllers/CertificateController.php';
        $controller = new CertificateController();
        $action = $_GET['action'] ?? 'mycertificates';
        $controller->handleRequest($action);
        break;

    // ---------------- MAILBOX ROUTES ----------------
    case 'site/mailbox':
    case 'mailbox':
    case 'student/mailbox':
    case 'admin/mailbox':
        require_once __DIR__ . '/testbank/site/controllers/MailboxController.php';
        $controller = new MailboxController();
        $action = $_GET['action'] ?? 'inbox';
        $controller->handleRequest($action);
        break;

    default:
        // Page Not Found fallback
        http_response_code(404);
        echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>";
        echo "<h2>404 - Page Not Found</h2>";
        echo "<p>The requested route does not exist.</p>";
        echo "<p><a href='index.php'>Return Home</a></p>";
        echo "</div>";
        exit;
}
