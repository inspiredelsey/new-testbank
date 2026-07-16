<?php
/**
 * Test Bank Application Configuration
 */

return [
    'db' => [
        'type' => 'mysql', // 'mysql' or 'sqlite'
        'host' => 'localhost',
        'port' => '3306',
        'name' => 'testbank',
        'user' => 'testbank_user',
        'pass' => 'testbank_pass',
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
    ]
];
