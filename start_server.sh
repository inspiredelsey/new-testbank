#!/bin/bash

# Ensure PHP binary exists
if ! command -v php &> /dev/null; then
    if [ -f /usr/bin/php8.2 ]; then
        ln -sf /usr/bin/php8.2 /usr/bin/php
    else
        apt-get update && apt-get install -y php-cli php-sqlite3 php-mysql php-mbstring
        ln -sf /usr/bin/php8.2 /usr/bin/php || true
    fi
fi

# Ensure PHP extensions INI is configured
if [ ! -f /etc/php/8.2/cli/conf.d/20-extensions.ini ]; then
    mkdir -p /etc/php/8.2/cli/conf.d/
    cat << 'EOF' > /etc/php/8.2/cli/conf.d/20-extensions.ini
extension=pdo.so
extension=pdo_sqlite.so
extension=mysqlnd.so
extension=pdo_mysql.so
extension=mbstring.so
extension=sqlite3.so
extension=mysqli.so
EOF
fi

# Ensure config.php exists
if [ ! -f testbank/config/config.php ] && [ -f testbank/config/config.sample.php ]; then
    cp testbank/config/config.sample.php testbank/config/config.php
fi

echo "Starting Test Bank LMS server on 0.0.0.0:3000..."
exec php -S 0.0.0.0:3000

