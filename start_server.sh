#!/bin/bash

# Ensure PHP binary exists and works
if ! php -v &> /dev/null; then
    if [ -f /usr/bin/php8.2 ]; then
        ln -sf /usr/bin/php8.2 /usr/bin/php
    fi
    if ! php -v &> /dev/null; then
        apt-get update && apt-get install -y php-cli php-sqlite3 php-mysql php-mbstring
        if [ -f /usr/bin/php8.2 ]; then
            ln -sf /usr/bin/php8.2 /usr/bin/php
        fi
    fi
fi

# Ensure PHP PDO extensions are configured in cli ini
if [ -d /etc/php/8.2/cli/conf.d ] && [ ! -f /etc/php/8.2/cli/conf.d/99-extensions.ini ]; then
    cat << 'EOF' > /etc/php/8.2/cli/conf.d/99-extensions.ini
extension_dir = "/usr/lib/php/20220829/"
extension=pdo.so
extension=pdo_sqlite.so
extension=sqlite3.so
extension=mysqlnd.so
extension=pdo_mysql.so
extension=mysqli.so
extension=mbstring.so
extension=gd.so
extension=xml.so
extension=dom.so
EOF
fi

# Ensure config.php exists
if [ ! -f testbank/config/config.php ] && [ -f testbank/config/config.sample.php ]; then
    cp testbank/config/config.sample.php testbank/config/config.php
fi

echo "Starting Test Bank LMS server on 0.0.0.0:3000..."
exec php -S 0.0.0.0:3000


