<?php
/**
 * Session Management Class
 */

class Session {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            $config = @include __DIR__ . '/../config/config.php';
            $lifetime = $config['app']['session_lifetime'] ?? 86400;

            ini_set('session.cookie_lifetime', $lifetime);
            ini_set('session.gc_maxlifetime', $lifetime);
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            
            // SameSite cookie support for iframe environment (requires Secure and SameSite=None)
            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path' => '/',
                'domain' => '',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'None'
            ]);

            session_start();
        }
        
        // Generate CSRF token if not exists
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    public static function get($key, $default = null) {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function delete($key) {
        self::start();
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    public static function destroy() {
        self::start();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function regenerate() {
        self::start();
        session_regenerate_id(true);
    }

    public static function validateCSRF($token) {
        self::start();
        return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }

    public static function getCSRFToken() {
        self::start();
        return $_SESSION['csrf_token'] ?? '';
    }
}
