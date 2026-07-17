<?php
require_once __DIR__ . '/../../includes/Session.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Csrf.php';

Session::start();

// Handle GET logout action
$action = $_GET['action'] ?? '';
if ($action === 'logout') {
    Auth::logout();
    header("Location: /lmsbank/site/views/login.php");
    exit;
}

// Handle POST login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    
    if (!Csrf::validateToken($token)) {
        Session::set('login_error', 'Security token validation failed. Please try again.');
        header("Location: /lmsbank/site/views/login.php");
        exit;
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        Session::set('login_error', 'Please fill in all fields.');
        header("Location: /lmsbank/site/views/login.php");
        exit;
    }

    if (Auth::attempt($email, $password)) {
        $user = Auth::user();
        if ($user && in_array($user['role'], ['admin', 'instructor'])) {
            header("Location: /lmsbank/admin/index.php");
        } else {
            header("Location: /lmsbank/site/index.php");
        }
        exit;
    } else {
        Session::set('login_error', 'Invalid email or password, or account is disabled.');
        header("Location: /lmsbank/site/views/login.php");
        exit;
    }
} else {
    header("Location: /lmsbank/site/views/login.php");
    exit;
}
