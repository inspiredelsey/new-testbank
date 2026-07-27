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

# Ensure config.php exists
if [ ! -f testbank/config/config.php ] && [ -f testbank/config/config.sample.php ]; then
    cp testbank/config/config.sample.php testbank/config/config.php
fi

echo "Starting Test Bank LMS server on 0.0.0.0:3000..."
exec php -S 0.0.0.0:3000

