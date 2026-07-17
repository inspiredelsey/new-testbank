<?php
/**
 * Database Connection Singleton
 */

class Database {
    private static $pdo = null;

    private function __construct() {
        // Private constructor to prevent direct instantiation
    }

    /**
     * Returns a single shared PDO instance.
     *
     * @return PDO
     * @throws Exception
     */
    public static function getInstance() {
        if (self::$pdo === null) {
            $configPath = __DIR__ . '/../config/config.php';
            if (!file_exists($configPath)) {
                throw new Exception("Configuration file not found. Please ensure /config/config.php exists.");
            }
            require_once $configPath;

            try {
                // Construct MySQL DSN
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                // Fail gracefully and fallback to SQLite in sandboxed environments if MySQL is unavailable
                error_log("MySQL connection failed: " . $e->getMessage() . ". Falling back to SQLite.");
                try {
                    $sqlitePath = __DIR__ . '/../lmsbank.sqlite';
                    self::$pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]);
                    self::$pdo->exec("PRAGMA foreign_keys = ON;");

                    // Check if schema needs to be initialized
                    $schemaExists = false;
                    try {
                        $tableCheck = self::$pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetch();
                        if ($tableCheck) {
                            $schemaExists = true;
                        }
                    } catch (PDOException $ex) {
                        // Table does not exist or querying failed
                    }

                    if (!$schemaExists) {
                        self::initializeSQLiteSchema();
                    }
                } catch (PDOException $sqliteEx) {
                    // Do not leak internal details/credentials in error messages
                    throw new Exception("Database connection failed. Please contact the administrator.");
                }
            }
        }
        return self::$pdo;
    }

    /**
     * Converts MySQL schema to SQLite compatible syntax and imports schema and seed data.
     */
    private static function initializeSQLiteSchema() {
        $files = [
            __DIR__ . '/../sql/schema.sql',
            __DIR__ . '/../sql/seed.sql'
        ];

        foreach ($files as $sqlPath) {
            if (!file_exists($sqlPath)) {
                continue;
            }
            $sqlContent = file_get_contents($sqlPath);

            // Convert MySQL-specific directives to SQLite
            $sqlContent = preg_replace('/CREATE DATABASE IF NOT EXISTS \w+;/', '', $sqlContent);
            $sqlContent = preg_replace('/USE \w+;/', '', $sqlContent);
            $sqlContent = preg_replace('/SET FOREIGN_KEY_CHECKS = \d;/', '', $sqlContent);

            // Remove MySQL table options
            $sqlContent = preg_replace('/ENGINE\s*=\s*\w+/i', '', $sqlContent);
            $sqlContent = preg_replace('/DEFAULT\s+CHARSET\s*=\s*[\w_-]+/i', '', $sqlContent);
            $sqlContent = preg_replace('/COLLATE\s*=\s*[\w_-]+/i', '', $sqlContent);

            // Convert AUTO_INCREMENT to PRIMARY KEY AUTOINCREMENT
            $sqlContent = preg_replace('/`id`\s+INT\s+AUTO_INCREMENT/i', '`id` INTEGER PRIMARY KEY AUTOINCREMENT', $sqlContent);
            $sqlContent = preg_replace('/id\s+INT\s+AUTO_INCREMENT/i', 'id INTEGER PRIMARY KEY AUTOINCREMENT', $sqlContent);
            $sqlContent = preg_replace('/INT\s+AUTO_INCREMENT/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sqlContent);

            // Convert ENUM type to TEXT
            $sqlContent = preg_replace('/ENUM\([^)]+\)/i', 'TEXT', $sqlContent);

            // Convert UNIQUE KEY to UNIQUE constraint
            $sqlContent = preg_replace('/UNIQUE KEY\s+[`"\'\w-]+\s*\(([^)]+)\)/i', 'UNIQUE ($1)', $sqlContent);

            // Process line-by-line to filter out index declarations, alter statements, and duplicate primary keys
            $lines = explode("\n", $sqlContent);
            $cleanLines = [];
            foreach ($lines as $line) {
                $trimmed = trim($line);
                
                // Skip standalone MySQL indexes/keys (non-unique/non-primary)
                if (preg_match('/^(INDEX|KEY)\s+[`"\'\w-]+\s*\(/i', $trimmed)) {
                    continue;
                }
                
                // Skip duplicate PRIMARY KEY (`id`) line as id is already set to INTEGER PRIMARY KEY AUTOINCREMENT
                if (preg_match('/^PRIMARY\s+KEY\s*\(\s*`?id`?\s*\),?/i', $trimmed)) {
                    continue;
                }
                
                if (preg_match('/^\s*ALTER\s+TABLE/i', $line)) {
                    continue; // Skip alter table
                }
                $cleanLines[] = $line;
            }
            $sqlContent = implode("\n", $cleanLines);

            // Remove trailing commas inside parenthesis
            $sqlContent = preg_replace('/,\s*\)/', "\n)", $sqlContent);

            // Split into separate statements by semicolon
            $statements = explode(';', $sqlContent);
            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if (!empty($stmt)) {
                    try {
                        self::$pdo->exec($stmt);
                    } catch (PDOException $e) {
                        error_log("SQLite init warning: " . $e->getMessage() . " on statement: " . $stmt);
                    }
                }
            }
        }
    }
}
