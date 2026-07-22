<?php
/**
 * Shared inclusion path for Message Model & Mailbox Helpers
 */
require_once __DIR__ . '/../site/models/Message.php';

if (!function_exists('mb_strimwidth')) {
    function mb_strimwidth($string, $start, $width, $trimmarker = '', $encoding = null) {
        $sub = substr($string, $start);
        if (strlen($sub) <= $width) {
            return $sub;
        }
        $markerLen = strlen($trimmarker);
        $targetLen = $width - $markerLen;
        if ($targetLen < 0) {
            return substr($trimmarker, 0, $width);
        }
        return substr($sub, 0, $targetLen) . $trimmarker;
    }
}

if (!function_exists('safeTruncate')) {
    function safeTruncate($string, $length = 90, $trimmarker = '...') {
        if (function_exists('mb_strimwidth')) {
            return mb_strimwidth($string, 0, $length, $trimmarker);
        }
        if (strlen($string) <= $length) {
            return $string;
        }
        return substr($string, 0, max(0, $length - strlen($trimmarker))) . $trimmarker;
    }
}
