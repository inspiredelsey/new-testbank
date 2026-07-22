<?php
/**
 * Authentication and Authorization Class
 */

require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/Database.php';

class Auth {
    public static function login($email, $password) {
        Session::start();
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && $user['status'] === 'active' && password_verify($password, $user['password_hash'])) {
            Session::regenerate();
            Session::set('user_id', $user['id']);
            Session::set('user_name', $user['name']);
            Session::set('user_email', $user['email']);
            Session::set('user_role', $user['role']);
            
            require_once __DIR__ . '/ActivityLogger.php';
            ActivityLogger::log($user['id'], 'login');
            
            return true;
        }
        return false;
    }

    public static function logout() {
        Session::destroy();
    }

    public static function isLoggedIn() {
        return Session::get('user_id') !== null;
    }

    public static function getUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        return [
            'id' => Session::get('user_id'),
            'name' => Session::get('user_name'),
            'email' => Session::get('user_email'),
            'role' => Session::get('user_role')
        ];
    }

    public static function user() {
        return self::getUser();
    }

    public static function getRole() {
        return Session::get('user_role');
    }

    public static function hasRole($roles) {
        if (!self::isLoggedIn()) {
            return false;
        }
        $userRole = self::getRole();
        if (is_array($roles)) {
            return in_array($userRole, $roles);
        }
        return $userRole === $roles;
    }

    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header("Location: index.php?route=login");
            exit;
        }
    }

    public static function requireRole($roles) {
        self::requireLogin();
        if (!self::hasRole($roles)) {
            // Render basic forbidden page
            http_response_code(403);
            echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>";
            echo "<h2>Access Forbidden</h2>";
            echo "<p>You do not have permission to access this page.</p>";
            echo "<p><a href='index.php'>Return Home</a></p>";
            echo "</div>";
            exit;
        }
    }

    public static function isAdmin() {
        return self::hasRole('admin');
    }

    public static function isInstructor() {
        return self::hasRole('instructor') || self::hasRole('admin');
    }

    public static function isStudent() {
        return self::hasRole('student');
    }
}
