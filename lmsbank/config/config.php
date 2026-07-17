<?php
/**
 * Configuration File for LMS Bank
 * 
 * CHANGE THESE FOR YOUR ENVIRONMENT
 */

// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'lmsbank_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', '3306');

// Site Configuration
define('SITE_URL', 'http://localhost/lmsbank');
define('SITE_NAME', 'LMS Bank');

// Error Reporting (Turn off in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);
