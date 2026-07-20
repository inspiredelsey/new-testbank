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
            $this->ensureSchemaUpdates();
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Ensures all newer tables and optional/integrated columns exist in the active SQLite database.
     */
    private function ensureSchemaUpdates() {
        // Ensure newer tables exist
        $tables = [
            "cases" => "CREATE TABLE IF NOT EXISTS cases (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title VARCHAR(200) NOT NULL,
                scenario_text TEXT NOT NULL,
                category_id INT NOT NULL,
                is_trend BOOLEAN DEFAULT 0,
                created_by INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            )",
            "case_exhibits" => "CREATE TABLE IF NOT EXISTS case_exhibits (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                case_id INT NOT NULL,
                tab_label VARCHAR(100) NOT NULL,
                content TEXT NOT NULL,
                timestamp_label VARCHAR(50) NULL,
                order_index INT DEFAULT 0,
                FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE
            )",
            "ngn_questions" => "CREATE TABLE IF NOT EXISTS ngn_questions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category_id INT NOT NULL,
                case_id INT NULL,
                case_order INT NULL,
                type VARCHAR(50) NOT NULL,
                question_text TEXT NOT NULL,
                question_data TEXT NOT NULL,
                difficulty VARCHAR(20) NOT NULL,
                points DECIMAL(6,2) DEFAULT 1.00,
                scoring_method VARCHAR(30) DEFAULT 'all_or_nothing',
                status VARCHAR(20) DEFAULT 'draft',
                created_by INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
                FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE SET NULL,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            )",
            "exam_questions" => "CREATE TABLE IF NOT EXISTS exam_questions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                exam_id INT NOT NULL,
                question_id INT NOT NULL,
                order_index INT DEFAULT 0,
                points_override DECIMAL(6,2) NULL,
                FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
                FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
            )",
            "exam_rules" => "CREATE TABLE IF NOT EXISTS exam_rules (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                exam_id INT NOT NULL,
                category_id INT NOT NULL,
                difficulty VARCHAR(20) DEFAULT 'any',
                question_count INT,
                FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
            )",
            "gradebook_items" => "CREATE TABLE IF NOT EXISTS gradebook_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                course_id INT NOT NULL,
                item_type TEXT NOT NULL,
                item_id INT NULL,
                title VARCHAR(200) NOT NULL,
                weight DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                max_score DECIMAL(6,2) NOT NULL DEFAULT 100.00,
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
                FOREIGN KEY (item_id) REFERENCES exams(id) ON DELETE SET NULL
            )",
            "gradebook_scores" => "CREATE TABLE IF NOT EXISTS gradebook_scores (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                gradebook_item_id INT NOT NULL,
                user_id INT NOT NULL,
                score DECIMAL(6,2) NOT NULL DEFAULT 0.00,
                recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (gradebook_item_id, user_id),
                FOREIGN KEY (gradebook_item_id) REFERENCES gradebook_items(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )"
        ];

        foreach ($tables as $name => $createSql) {
            try {
                $this->pdo->exec($createSql);
            } catch (PDOException $e) {
                error_log("Failed to create table $name: " . $e->getMessage());
            }
        }

        // Apply column additions (alters)
        $alters = [
            "ALTER TABLE cases ADD COLUMN created_by INTEGER",
            "ALTER TABLE exams ADD COLUMN course_id INTEGER",
            "ALTER TABLE exams ADD COLUMN gradebook_category VARCHAR(50) DEFAULT 'summative'",
            "ALTER TABLE questions ADD COLUMN case_id INTEGER",
            "ALTER TABLE questions ADD COLUMN case_order INTEGER",
            "ALTER TABLE courses ADD COLUMN category_id INTEGER",
            "ALTER TABLE courses ADD COLUMN thumbnail TEXT",
            "ALTER TABLE courses ADD COLUMN pass_percentage DECIMAL(5,2) DEFAULT 50.00",
            "ALTER TABLE exam_attempts ADD COLUMN resolved_question_ids TEXT",
            "CREATE UNIQUE INDEX IF NOT EXISTS idx_attempt_question_unique ON attempt_answers (attempt_id, question_id)"
        ];

        foreach ($alters as $alter) {
            try {
                $this->pdo->exec($alter);
            } catch (PDOException $e) {
                // Ignore column already exists errors or table not found errors
            }
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
            "ALTER TABLE exams ADD COLUMN gradebook_category VARCHAR(50) DEFAULT 'summative'",
            "ALTER TABLE questions ADD COLUMN case_id INTEGER",
            "ALTER TABLE questions ADD COLUMN case_order INTEGER",
            "ALTER TABLE courses ADD COLUMN category_id INTEGER",
            "ALTER TABLE courses ADD COLUMN thumbnail TEXT",
            "ALTER TABLE courses ADD COLUMN pass_percentage DECIMAL(5,2) DEFAULT 50.00"
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
