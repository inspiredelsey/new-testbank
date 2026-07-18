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
        header("Location: index.php?route=login");
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
    case 'register':
    case 'logout':
        require_once __DIR__ . '/testbank/admin/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->handleRequest($route);
        break;

    // ---------------- ADMIN / INSTRUCTOR ROUTES ----------------
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

    case 'admin/categories':
        require_once __DIR__ . '/testbank/admin/controllers/CategoryController.php';
        $controller = new CategoryController();
        $action = $_GET['action'] ?? 'index';
        $controller->handleRequest($action);
        break;

    case 'admin/questions':
        require_once __DIR__ . '/testbank/admin/controllers/QuestionController.php';
        $controller = new QuestionController();
        $action = $_GET['action'] ?? 'index';
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

    case 'admin/users':
        require_once __DIR__ . '/testbank/admin/controllers/UserController.php';
        $controller = new UserController();
        $action = $_GET['action'] ?? 'list';
        $controller->handleRequest($action);
        break;

    // ---------------- STUDENT ROUTES ----------------
    case 'student/course/view':
        require_once __DIR__ . '/testbank/admin/controllers/StudentController.php';
        $controller = new StudentController();
        $controller->handleRequest('course_view');
        break;

    case 'student/course/complete_lp_item':
        require_once __DIR__ . '/testbank/admin/controllers/StudentController.php';
        $controller = new StudentController();
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
        require_once __DIR__ . '/testbank/admin/controllers/StudentController.php';
        $controller = new StudentController();
        $controller->handleRequest('start');
        break;

    case 'student/exam/take':
        require_once __DIR__ . '/testbank/admin/controllers/StudentController.php';
        $controller = new StudentController();
        $controller->handleRequest('take');
        break;

    case 'student/exam/submit':
        require_once __DIR__ . '/testbank/admin/controllers/StudentController.php';
        $controller = new StudentController();
        $controller->handleRequest('submit');
        break;

    case 'student/exam/review':
        require_once __DIR__ . '/testbank/admin/controllers/StudentController.php';
        $controller = new StudentController();
        $controller->handleRequest('review');
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
