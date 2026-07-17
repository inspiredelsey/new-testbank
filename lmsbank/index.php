<?php
/**
 * LMS Bank - Primary Front Controller Routing Gateway
 */

require_once __DIR__ . '/includes/Session.php';
require_once __DIR__ . '/includes/Auth.php';

Session::start();

if (Auth::check()) {
    $user = Auth::user();
    if ($user && in_array($user['role'], ['admin', 'instructor'])) {
        header("Location: /lmsbank/admin/index.php");
    } else {
        header("Location: /lmsbank/site/index.php");
    }
} else {
    header("Location: /lmsbank/site/views/login.php");
}
exit;
