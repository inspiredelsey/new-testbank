<?php
/**
 * Authentication Controller
 */

require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Database.php';

class AuthController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function handleRequest($action) {
        $csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';

        switch ($action) {
            case 'login':
                if (Auth::isLoggedIn()) {
                    $this->redirectUserBasedOnRole();
                }

                $error = '';
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!Session::validateCSRF($csrfToken)) {
                        $error = 'CSRF verification failed.';
                    } else {
                        $email = trim($_POST['email'] ?? '');
                        $password = $_POST['password'] ?? '';

                        if (Auth::login($email, $password)) {
                            $this->redirectUserBasedOnRole();
                        } else {
                            $error = 'Invalid email or password. Please try again.';
                        }
                    }
                }
                include __DIR__ . '/../views/auth/login.php';
                exit;

            case 'register':
                if (Auth::isLoggedIn()) {
                    $this->redirectUserBasedOnRole();
                }

                $error = '';
                $success = '';
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!Session::validateCSRF($csrfToken)) {
                        $error = 'CSRF verification failed.';
                    } else {
                        $name = trim($_POST['name'] ?? '');
                        $email = trim($_POST['email'] ?? '');
                        $password = $_POST['password'] ?? '';
                        $role = $_POST['role'] ?? 'student'; // 'student' or 'instructor'

                        if (empty($name) || empty($email) || empty($password)) {
                            $error = 'Please fill in all required fields.';
                        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $error = 'Invalid email address format.';
                        } else if (!in_array($role, ['student', 'instructor'])) {
                            $error = 'Invalid user role selected.';
                        } else {
                            // Check for email duplicate
                            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                            $stmt->execute([$email]);
                            if (intval($stmt->fetchColumn()) > 0) {
                                $error = 'An account with this email already exists.';
                            } else {
                                $hash = password_hash($password, PASSWORD_DEFAULT);
                                $stmt = $this->db->prepare("INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, ?, 'active')");
                                if ($stmt->execute([$name, $email, $hash, $role])) {
                                    $success = 'Account created successfully! You can now log in below.';
                                    include __DIR__ . '/../views/auth/login.php';
                                    exit;
                                } else {
                                    $error = 'Failed to create account. Please try again later.';
                                }
                            }
                        }
                    }
                }
                include __DIR__ . '/../views/auth/register.php';
                exit;

            case 'logout':
                Auth::logout();
                header("Location: index.php?route=login&success=Logged out successfully");
                exit;
        }
    }

    private function redirectUserBasedOnRole() {
        if (Auth::isInstructor()) {
            header("Location: index.php?route=admin/exams");
        } else {
            header("Location: index.php?route=student/dashboard");
        }
        exit;
    }
}
