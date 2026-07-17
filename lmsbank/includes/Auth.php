<?php
/**
 * User Authentication Helper
 */

require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/Database.php';

class Auth {
    /**
     * Attempt to log in a user with the given credentials.
     * 
     * @param string $email
     * @param string $password
     * @return bool
     */
    public static function attempt($email, $password) {
        Session::start();
        
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT id, name, email, password_hash, role, status FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();
            
            if ($user && $user['status'] === 'active' && password_verify($password, $user['password_hash'])) {
                // Store user session data
                Session::set('user_id', $user['id']);
                Session::set('user_name', $user['name']);
                Session::set('user_email', $user['email']);
                Session::set('user_role', $user['role']);
                return true;
            }
        } catch (Exception $e) {
            error_log("Authentication attempt error: " . $e->getMessage());
        }
        
        return false;
    }

    /**
     * Log out the current user and destroy session.
     */
    public static function logout() {
        Session::destroy();
    }

    /**
     * Check if a user is currently logged in.
     * 
     * @return bool
     */
    public static function check() {
        return Session::has('user_id');
    }

    /**
     * Return the current logged-in user's session data as an array.
     * 
     * @return array|null
     */
    public static function user() {
        if (!self::check()) {
            return null;
        }
        return [
            'id' => Session::get('user_id'),
            'name' => Session::get('user_name'),
            'email' => Session::get('user_email'),
            'role' => Session::get('user_role')
        ];
    }

    /**
     * Require that a user is logged in, redirecting to the login page if not.
     */
    public static function requireLogin() {
        if (!self::check()) {
            header("Location: /lmsbank/site/views/login.php");
            exit;
        }
    }

    /**
     * Require the user to have one of the allowed roles.
     * 
     * @param string|array $roles
     */
    public static function requireRole($roles) {
        self::requireLogin();
        
        $allowedRoles = is_array($roles) ? $roles : [$roles];
        $userRole = Session::get('user_role');
        
        if (!in_array($userRole, $allowedRoles)) {
            http_response_code(403);
            echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>";
            echo "<h2>Access Forbidden</h2>";
            echo "<p>You do not have permission to access this page.</p>";
            echo "<p><a href='/lmsbank/index.php'>Return Home</a></p>";
            echo "</div>";
            exit;
        }
    }
}
