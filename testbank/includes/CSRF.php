<?php
/**
 * CSRF Helper Class wrapping Session CSRF token methods
 */
require_once __DIR__ . '/Session.php';

class CSRF {
    public static function getToken() {
        return Session::getCSRFToken();
    }

    public static function validateToken($token) {
        return Session::validateCSRF($token);
    }
}
