<?php
/**
 * Database Connection Singleton
 */

class Database {
    private static $instance = null;
    private $pdo;
    private $config;
    private $usedFallback = false;

    private function __construct() {
        $configPath = __DIR__ . '/../config/config.php';
        if (!file_exists($configPath)) {
            throw new Exception("Configuration file not found. Please copy config.sample.php to config.php.");
        }
        $this->config = require $configPath;
        $dbConfig = $this->config['db'];

        if ($dbConfig['type'] === 'mysql') {
            try {
                $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']};charset=utf8mb4";
                $this->pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                // Fall back to SQLite if MySQL fails
                error_log("MySQL connection failed: " . $e->getMessage() . ". Falling back to SQLite.");
                $this->setupSQLiteFallback();
            }
        } else {
            $this->setupSQLiteFallback();
        }
    }

    private function setupSQLiteFallback() {
        $this->usedFallback = true;
        $sqlitePath = $this->config['db']['sqlite_path'] ?? (__DIR__ . '/../testbank.sqlite');
        
        try {
            $this->pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $this->pdo->exec("PRAGMA foreign_keys = ON;");
            
            // Robust check to see if the schema (specifically users table) exists
            $schemaExists = false;
            try {
                $tableCheck = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetch();
                if ($tableCheck) {
                    $schemaExists = true;
                }
            } catch (PDOException $e) {
                // Ignore errors and re-initialize
            }

            if (!$schemaExists) {
                $this->initializeSQLiteSchema();
            }
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    private function initializeSQLiteSchema() {
        $files = [
            __DIR__ . '/../../sql/schema.sql',
            __DIR__ . '/../../sql/lms_additions.sql'
        ];

        foreach ($files as $sqlPath) {
            if (!file_exists($sqlPath)) {
                continue;
            }
            $sqlContent = file_get_contents($sqlPath);
            
            // Convert MySQL schema to SQLite compatible syntax
            $sqlContent = preg_replace('/CREATE DATABASE IF NOT EXISTS \w+;/', '', $sqlContent);
            $sqlContent = preg_replace('/USE \w+;/', '', $sqlContent);
            $sqlContent = preg_replace('/SET FOREIGN_KEY_CHECKS = \d;/', '', $sqlContent);
            
            // Remove MySQL table options without stripping the semicolons at the end of statements
            $sqlContent = preg_replace('/ENGINE\s*=\s*\w+/i', '', $sqlContent);
            $sqlContent = preg_replace('/DEFAULT\s+CHARSET\s*=\s*[\w-]+/i', '', $sqlContent);
            $sqlContent = preg_replace('/COLLATE\s*=\s*[\w-]+/i', '', $sqlContent);
            $sqlContent = preg_replace('/COLLATE\s+[\w-]+/i', '', $sqlContent);
            
            // SQLite doesn't support ON UPDATE CURRENT_TIMESTAMP
            $sqlContent = preg_replace('/ON\s+UPDATE\s+CURRENT_TIMESTAMP/i', '', $sqlContent);
            
            // Convert INT AUTO_INCREMENT to INTEGER PRIMARY KEY
            $sqlContent = preg_replace('/id INT AUTO_INCREMENT PRIMARY KEY/i', 'id INTEGER PRIMARY KEY AUTOINCREMENT', $sqlContent);
            $sqlContent = preg_replace('/id\s+INT\s+NOT\s+NULL\s+AUTO_INCREMENT\s+PRIMARY\s+KEY/i', 'id INTEGER PRIMARY KEY AUTOINCREMENT', $sqlContent);
            $sqlContent = preg_replace('/INT\s+AUTO_INCREMENT\s+PRIMARY\s+KEY/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sqlContent);
            $sqlContent = preg_replace('/INT\s+AUTO_INCREMENT/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sqlContent);
            
            // SQLite doesn't support ENUM, map it to TEXT
            $sqlContent = preg_replace('/ENUM\([^)]+\)/i', 'TEXT', $sqlContent);
            
            // Convert UNIQUE KEY to UNIQUE constraint
            $sqlContent = preg_replace('/UNIQUE KEY\s+\w+\s*\(([^)]+)\)/i', 'UNIQUE ($1)', $sqlContent);
            
            // Let's process line-by-line
            $lines = explode("\n", $sqlContent);
            $cleanLines = [];
            foreach ($lines as $line) {
                if (preg_match('/^\s*INDEX\s+idx_/i', $line)) {
                    continue; // Skip inline index declarations
                }
                if (preg_match('/^\s*ALTER\s+TABLE/i', $line)) {
                    continue; // Skip ALTER TABLE statements (we'll run SQLite-compatible ones separately)
                }
                $cleanLines[] = $line;
            }
            $sqlContent = implode("\n", $cleanLines);
            
            // Remove trailing commas before closing parenthesis
            $sqlContent = preg_replace('/,\s*\)/', "\n)", $sqlContent);
            
            // Split by semicolon and run statements
            $statements = explode(';', $sqlContent);
            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if (!empty($stmt)) {
                    try {
                        $this->pdo->exec($stmt);
                    } catch (PDOException $e) {
                        error_log("SQLite init warning: " . $e->getMessage() . " on statement: " . $stmt);
                    }
                }
            }
        }

        // Apply SQLite-compatible ALTER TABLE additions to ensure optional/integrated columns exist
        $alters = [
            "ALTER TABLE exams ADD COLUMN course_id INTEGER",
            "ALTER TABLE questions ADD COLUMN case_id INTEGER",
            "ALTER TABLE questions ADD COLUMN case_order INTEGER"
        ];
        foreach ($alters as $alter) {
            try {
                $this->pdo->exec($alter);
            } catch (PDOException $e) {
                // Ignore column already exists errors
            }
        }
        
        // Seed an admin user
        $adminEmail = $this->config['defaults']['admin_email'] ?? 'admin@testbank.com';
        $adminPass = password_hash($this->config['defaults']['admin_password'] ?? 'admin123', PASSWORD_DEFAULT);
        
        $stmt = $this->pdo->prepare("INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, 'admin', 'active')");
        $stmt->execute(['Administrator', $adminEmail, $adminPass]);

        // Seed some starter categories
        $this->pdo->exec("INSERT INTO categories (name, slug, description) VALUES ('General Science', 'general-science', 'Basic science questions')");
        $this->pdo->exec("INSERT INTO categories (name, slug, description) VALUES ('Web Development', 'web-development', 'HTML, CSS, JavaScript, PHP')");
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function isFallback() {
        return $this->usedFallback;
    }
}
