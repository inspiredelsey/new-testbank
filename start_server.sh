#!/bin/bash
# Exit on any error during installation
set -e

echo "=== System Check & Initialization ==="

# 1. Install PHP and modules if not present
if ! command -v php &> /dev/null || ! php -m | grep -q -i pdo_mysql; then
    echo "PHP or PDO MySQL driver is not installed. Installing php-cli, php-sqlite3, and php-mysql..."
    # Clear any potential locks
    killall -9 dpkg apt-get apt 2>/dev/null || true
    rm -f /var/lib/dpkg/lock-frontend /var/lib/dpkg/lock /var/lib/apt/lists/lock || true
    
    # Configure any interrupted dpkg runs
    echo "Running dpkg configure..."
    DEBIAN_FRONTEND=noninteractive dpkg --configure -a --force-confdef --force-confold || true
    
    # Run apt update & install php
    echo "Running apt-get update..."
    apt-get update
    
    echo "Installing php-cli, php-sqlite3, and php-mysql..."
    DEBIAN_FRONTEND=noninteractive apt-get install -y -o Dpkg::Options::="--force-confdef" -o Dpkg::Options::="--force-confold" php-cli php-sqlite3 php-mysql
fi

# 2. Verify and repair SQLite database
echo "Verifying database health..."
php -r '
$db_file = "testbank/testbank.sqlite";
if (file_exists($db_file)) {
    try {
        $pdo = new PDO("sqlite:" . $db_file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Execute a test query to verify integrity
        $pdo->query("SELECT name FROM sqlite_master LIMIT 1")->fetchAll();
        echo "Database is healthy and verified.\n";
    } catch (Exception $e) {
        echo "Database check failed: " . $e->getMessage() . "\n";
        echo "Deleting corrupted/malformed database to trigger fresh re-initialization...\n";
        unlink($db_file);
    }
} else {
    echo "Database file does not exist yet. It will be created and seeded automatically on first application access.\n";
}
'

# 3. Start PHP development server on port 3000
echo "Starting PHP development server on 0.0.0.0:3000..."
exec php -S 0.0.0.0:3000
