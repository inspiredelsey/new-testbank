<?php
/**
 * Test Bank Application Configuration Sample
 * Copy this file to config.php and fill in your credentials.
 */

return [
    'db' => [
        'type' => 'mysql', // 'mysql' or 'sqlite' - default is MySQL
        'host' => '127.0.0.1', // CHANGE THESE FOR YOUR ENVIRONMENT
        'port' => '3306',
        'name' => 'testbank',
        'user' => 'root',
        'pass' => '',
        'sqlite_path' => __DIR__ . '/../testbank.sqlite',
    ],
    'app' => [
        'url' => 'http://localhost:3000',
        'name' => 'Test Bank LMS',
        'session_lifetime' => 86400, // 24 hours
    ],
    'defaults' => [
        'admin_email' => 'admin@testbank.com',
        'admin_password' => 'admin123',
    ],
    'payments' => [
        'paystack' => [
            'secret_key' => 'sk_test_your_actual_key_here',
        ],
        'flutterwave' => [
            'secret_key' => 'FLWSECK_TEST-your_actual_key_here',
        ],
    ]
];
