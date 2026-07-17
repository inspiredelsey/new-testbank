<?php
/**
 * CSRF Protection Helper
 */

require_once __DIR__ . '/Session.php';

class Csrf {
    /**
     * Generate a new CSRF token and store it in session.
     * 
     * @return string
     */
    public static function generateToken() {
        Session::start();
        $token = bin2hex(random_bytes(32));
        Session::set('csrf_token', $token);
        return $token;
    }

    /**
     * Validate a token against the one stored in session.
     * Regenerates/removes the token after validation for one-time-use.
     * 
     * @param string|null $token
     * @return bool
     */
    public static function validateToken($token) {
        Session::start();
        if (empty($token)) {
            return false;
        }
        $stored = Session::get('csrf_token');
        
        // One-time-use: remove the token immediately
        Session::remove('csrf_token');

        return !empty($stored) && hash_equals($stored, $token);
    }
}
