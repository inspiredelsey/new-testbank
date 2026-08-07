#!/bin/bash

# Ensure PHP binary exists and works
if ! php -v &> /dev/null; then
    if [ -f /usr/bin/php8.2 ]; then
        ln -sf /usr/bin/php8.2 /usr/bin/php
    fi
    if ! php -v &> /dev/null; then
        apt-get update && apt-get install -y php-cli php-sqlite3 php-mysql php-mbstring php-curl
        if [ -f /usr/bin/php8.2 ]; then
            ln -sf /usr/bin/php8.2 /usr/bin/php
        fi
    fi
fi

# Ensure the curl extension is present, independent of the block above —
# that block only runs if PHP itself is missing/broken, so it would never
# fire once PHP is already working without curl. The payment gateway
# integration (Stripe/PayPal/Paystack/Flutterwave) requires curl_init(),
# so check for it explicitly and install it if missing.
if ! php -r "exit(function_exists('curl_init') ? 0 : 1);" &> /dev/null; then
    echo "PHP curl extension not found — installing..."
    apt-get update && apt-get install -y php-curl
    if [ -f /usr/bin/php8.2 ]; then
        ln -sf /usr/bin/php8.2 /usr/bin/php
    fi
    if ! php -r "exit(function_exists('curl_init') ? 0 : 1);" &> /dev/null; then
        echo "WARNING: php-curl still not available after install attempt. Payment gateway features will not work."
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

