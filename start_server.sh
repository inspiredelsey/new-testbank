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
# Explicit -d flags here because this app runs on PHP's built-in server
# (not Apache/PHP-FPM), so neither .htaccess nor .user.ini have any effect —
# these are the only settings that actually reach this PHP process. Without
# this, PHP's stock Ubuntu defaults (commonly 2M/8M) silently reject any
# document/thumbnail upload larger than that, even though the app's own
# validation logic already allows up to 20MB for documents.
exec php \
    -d upload_max_filesize=25M \
    -d post_max_size=30M \
    -d max_execution_time=300 \
    -d memory_limit=256M \
    -S 0.0.0.0:3000

