<?php
/**
 * Database Connection Singleton
 *
 * Production path: MySQL, using /sql/schema.sql directly (the single source of truth).
 *
 * Local/dev fallback: if MySQL can't be reached (e.g. previewing inside Google AI Studio,
 * which has no MySQL server available), this falls back to a local SQLite file — but it
 * is NOT a second hand-maintained schema. It parses the exact same /sql/schema.sql and
 * converts it to SQLite-compatible syntax on the fly, so there is still only one place
 * the schema is defined. This fallback is loud (logs a clear warning) rather than silent,
 * and is intended for local development/preview only — real deployments should run MySQL.
 */

class Database {
    private static $instance = null;
    private $pdo;
    private $config;
    private $usingFallback = false;

    private function __construct() {
        $configPath = __DIR__ . '/../config/config.php';
        if (!file_exists($configPath)) {
            throw new Exception("Configuration file not found. Please copy config.sample.php to config.php.");
        }
        $this->config = require $configPath;
        $dbConfig = $this->config['db'];

        if (($dbConfig['type'] ?? 'mysql') !== 'mysql') {
            throw new Exception("Unsupported db.type '{$dbConfig['type']}' in config.php. Only 'mysql' is supported (the SQLite path is an automatic dev-only fallback, not a configurable option).");
        }

        try {
            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $this->initializeMySQLSchema();
        } catch (PDOException $e) {
            // Try creating the database if it doesn't exist yet, still on MySQL.
            try {
                $baseDsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};charset=utf8mb4";
                $basePdo = new PDO($baseDsn, $dbConfig['user'], $dbConfig['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                $dbNameClean = str_replace('`', '``', $dbConfig['name']);
                $basePdo->exec("CREATE DATABASE IF NOT EXISTS `$dbNameClean` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                $basePdo->exec("USE `$dbNameClean`;");
                $this->pdo = $basePdo;
                $this->initializeMySQLSchema();
            } catch (PDOException $e2) {
                // MySQL is genuinely unreachable. Fall back to local SQLite for dev/preview
                // convenience — loudly, and derived from the same schema.sql, not a duplicate.
                error_log(
                    "==================================================================\n" .
                    "WARNING: Could not connect to MySQL (" . $e2->getMessage() . ").\n" .
                    "Falling back to a local SQLite database for DEVELOPMENT/PREVIEW ONLY.\n" .
                    "Data in this mode is NOT what will run in production. Configure a\n" .
                    "real MySQL connection in testbank/config/config.php before deploying.\n" .
                    "=================================================================="
                );
                $this->initializeSQLiteFallback();
            }
        }
    }

    /**
     * Initializes the MySQL database schema from the single canonical /sql/schema.sql
     * and seeds the default admin user, only if the schema hasn't been applied yet.
     */
    private function initializeMySQLSchema() {
        $stmt = $this->pdo->query("SHOW TABLES LIKE 'users'");
        if (!$stmt->fetch()) {
            $sqlPath = __DIR__ . '/../../sql/schema.sql';
            if (!file_exists($sqlPath)) {
                throw new Exception("sql/schema.sql not found — cannot initialize the database.");
            }
            $this->pdo->exec(file_get_contents($sqlPath));
        }
        $this->seedAdminIfEmpty();
    }

    /**
     * Dev-only fallback: opens (or creates) a local SQLite file at /testbank/data/dev.sqlite,
     * and — if it has no tables yet — builds its schema by converting /sql/schema.sql on the
     * fly. This is the ONLY place SQLite-specific schema exists, and it's generated, not
     * hand-maintained, so it cannot drift from the canonical MySQL schema.
     */
    private function initializeSQLiteFallback() {
        $this->usingFallback = true;
        $dataDir = __DIR__ . '/../data';
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0775, true);
        }
        $sqlitePath = $dataDir . '/dev.sqlite';

        $this->pdo = new PDO('sqlite:' . $sqlitePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $this->pdo->exec('PRAGMA foreign_keys = ON;');

        // Alias the two MySQL-only SQL functions actually used elsewhere in the app
        // (RAND() and NOW()) so those call sites work unmodified against SQLite too.
        $this->pdo->sqliteCreateFunction('RAND', function () {
            return mt_rand() / mt_getrandmax();
        }, 0);
        $this->pdo->sqliteCreateFunction('NOW', function () {
            return date('Y-m-d H:i:s');
        }, 0);

        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        if (!$stmt->fetch()) {
            $sqlPath = __DIR__ . '/../../sql/schema.sql';
            if (!file_exists($sqlPath)) {
                throw new Exception("sql/schema.sql not found — cannot initialize the fallback database.");
            }
            $statements = $this->convertMySQLSchemaToSQLite(file_get_contents($sqlPath));
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if ($statement === '') {
                    continue;
                }
                try {
                    $this->pdo->exec($statement);
                } catch (PDOException $ex) {
                    // Surface conversion problems loudly rather than silently skipping —
                    // a failed statement here means the dev database is incomplete.
                    error_log("SQLite fallback schema statement failed: " . $ex->getMessage() . "\nStatement: " . $statement);
                }
            }
        }
        $this->seedAdminIfEmpty();
    }

    /**
     * Converts the canonical MySQL schema.sql into a sequence of SQLite-compatible
     * statements. This is intentionally the ONLY place MySQL->SQLite syntax translation
     * happens, and it operates on the real schema file rather than a separately
     * maintained copy, so the two can never drift apart again.
     *
     * @param string $sql the raw contents of sql/schema.sql
     * @return array of individual SQL statements to execute in order
     */
    private function convertMySQLSchemaToSQLite($sql) {
        $out = [];

        // Strip full-line SQL comments BEFORE splitting into statements — a
        // "-- N. Table name" comment sitting directly above a CREATE TABLE
        // would otherwise break the anchored match below.
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);

        // Split on statement-terminating semicolons. schema.sql doesn't contain
        // semicolons inside string literals in CREATE TABLE bodies, so this is safe.
        $rawStatements = array_filter(array_map('trim', explode(";", $sql)));

        foreach ($rawStatements as $stmt) {
            // Skip MySQL-only statements that have no SQLite equivalent/need.
            if (preg_match('/^CREATE DATABASE/i', $stmt) || preg_match('/^USE\s+/i', $stmt)) {
                continue;
            }

            // Convert FOREIGN_KEY_CHECKS toggling to the SQLite equivalent.
            if (preg_match('/^SET\s+FOREIGN_KEY_CHECKS\s*=\s*(\d)/i', $stmt, $m)) {
                $out[] = "PRAGMA foreign_keys = " . ($m[1] === '0' ? 'OFF' : 'ON');
                continue;
            }

            // Normalize backtick-quoted identifiers to double-quoted (SQLite standard).
            $stmt = preg_replace('/`([^`]+)`/', '"$1"', $stmt);

            if (preg_match('/^DROP TABLE/i', $stmt)) {
                $out[] = $stmt;
                continue;
            }

            if (preg_match('/^CREATE TABLE\s+"?(\w+)"?/i', $stmt, $tableMatch)) {
                $tableName = $tableMatch[1];

                // Strip the trailing ENGINE=...DEFAULT CHARSET=...COLLATE=... clause.
                $stmt = preg_replace('/\)\s*ENGINE\s*=.*$/is', ')', $stmt);

                // Extract inline INDEX / KEY / UNIQUE KEY clauses into separate
                // CREATE INDEX statements — SQLite doesn't support them inline.
                // These must be queued and appended AFTER this table's own CREATE
                // TABLE statement, not before — an index can't reference a table
                // that doesn't exist yet.
                $indexStatements = [];
                if (preg_match_all('/,\s*(UNIQUE\s+KEY|KEY|INDEX)\s+(\w+)\s*\(([^)]+)\)/i', $stmt, $idxMatches, PREG_SET_ORDER)) {
                    foreach ($idxMatches as $idx) {
                        $isUnique = stripos($idx[1], 'unique') !== false;
                        $idxName = $idx[2];
                        $cols = $idx[3];
                        $indexStatements[] = ($isUnique ? "CREATE UNIQUE INDEX " : "CREATE INDEX ") .
                                             $idxName . " ON " . $tableName . " (" . $cols . ")";
                    }
                    $stmt = preg_replace('/,\s*(UNIQUE\s+KEY|KEY|INDEX)\s+(\w+)\s*\(([^)]+)\)/i', '', $stmt);
                }

                // Type/syntax conversions.
                $stmt = preg_replace('/\bINT\s+AUTO_INCREMENT\s+PRIMARY\s+KEY\b/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $stmt);
                $stmt = preg_replace('/ENUM\s*\([^)]*\)/i', 'TEXT', $stmt);
                $stmt = preg_replace('/\bJSON\b/i', 'TEXT', $stmt);
                $stmt = preg_replace('/\s+ON\s+UPDATE\s+CURRENT_TIMESTAMP/i', '', $stmt);

                // Clean up any now-dangling trailing comma before the closing paren
                // left behind after stripping inline indexes.
                $stmt = preg_replace('/,(\s*)\)\s*$/s', '$1)', $stmt);

                $out[] = trim($stmt);
                // Indexes go right after their own table, never before.
                foreach ($indexStatements as $idxStmt) {
                    $out[] = $idxStmt;
                }
                continue;
            }

            // Anything else (stray statements) — pass through unchanged.
            if ($stmt !== '') {
                $out[] = $stmt;
            }
        }

        return $out;
    }

    private function seedAdminIfEmpty() {
        $checkUser = $this->pdo->query("SELECT COUNT(*) as cnt FROM users")->fetch();
        if ($checkUser && intval($checkUser['cnt']) === 0) {
            $adminEmail = $this->config['defaults']['admin_email'] ?? 'admin@testbank.com';
            $adminPass = password_hash($this->config['defaults']['admin_password'] ?? 'admin123', PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, 'admin', 'active')");
            $stmt->execute(['Administrator', $adminEmail, $adminPass]);
        }
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

    /**
     * True if this request is running on the local SQLite dev fallback rather than
     * real MySQL. Views can use this to show a visible "dev mode" banner if desired.
     */
    public function isFallback() {
        return $this->usingFallback;
    }
}