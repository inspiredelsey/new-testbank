<?php
/**
 * PaymentGateway
 *
 * Direct REST API integration with Stripe, PayPal, Paystack, and
 * Flutterwave via cURL — deliberately NOT using any of their Composer
 * SDKs. This project already had a serious incident where a mismatched
 * vendor library (TCPDF) silently broke certificate generation; adding
 * four more large third-party dependencies carries the same risk. All
 * four gateways have simple, well-documented JSON REST APIs that a
 * handful of cURL calls can drive directly, with zero dependency-
 * installation risk.
 *
 * TEST/SANDBOX MODE ONLY, as configured in config.php's 'payments' section.
 */

class PaymentGateway {
    private static function config() {
        $configPath = __DIR__ . '/../config/config.php';
        $config = require $configPath;
        return $config['payments'] ?? [];
    }

    private static function appUrl() {
        if (!empty($_SERVER['HTTP_HOST'])) {
            $proto = ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            return $proto . '://' . $_SERVER['HTTP_HOST'];
        }
        $configPath = __DIR__ . '/../config/config.php';
        $config = require $configPath;
        return rtrim($config['app']['url'] ?? '', '/');
    }

    // ================= Generic HTTP helper =================
    private static function request($method, $url, $headers = [], $body = null, $isFormEncoded = false) {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $isFormEncoded && is_array($body) ? http_build_query($body) : (is_array($body) ? json_encode($body) : $body));
            }
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                throw new Exception("Payment gateway request failed: $error");
            }
            $decoded = json_decode($response, true);
            return ['status' => $httpCode, 'body' => $decoded, 'raw' => $response];
        }

        // Fallback using stream_context_create & file_get_contents
        $content = '';
        if ($body !== null) {
            if ($isFormEncoded && is_array($body)) {
                $content = http_build_query($body);
            } elseif (is_array($body) || is_object($body)) {
                $content = json_encode($body);
            } else {
                $content = (string)$body;
            }
        }

        $opts = [
            'http' => [
                'method'  => $method,
                'header'  => implode("\r\n", $headers),
                'content' => $content,
                'timeout' => 20,
                'ignore_errors' => true,
            ]
        ];

        $context = stream_context_create($opts);
        $response = @file_get_contents($url, false, $context);

        if ($response === false && !isset($http_response_header)) {
            $err = error_get_last();
            throw new Exception("Payment gateway request failed: " . ($err['message'] ?? 'Network connection error'));
        }

        $httpCode = 0;
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $headerLine) {
                if (preg_match('#HTTP/[1-9]\.[0-9]\s+(\d{3})#i', $headerLine, $matches)) {
                    $httpCode = intval($matches[1]);
                }
            }
        }

        $decoded = json_decode($response, true);
        return ['status' => $httpCode, 'body' => $decoded, 'raw' => $response];
    }

    // ================= STRIPE =================

    public static function createStripeSession($token, $courseTitle, $amount, $currency) {
        $cfg = self::config()['stripe'] ?? [];
        $secretKey = $cfg['secret_key'] ?? '';
        if (empty($secretKey) || strpos($secretKey, 'CHANGE_ME') !== false) {
            throw new Exception('Stripe is not configured. Add a real test secret key to config.php.');
        }

        $appUrl = self::appUrl();
        $successUrl = $appUrl . '/index.php?route=course/checkout/callback&gateway=stripe&token=' . urlencode($token) . '&session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = $appUrl . '/index.php?route=course/checkout&id=0&cancelled=1&token=' . urlencode($token);

        $body = [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'line_items[0][quantity]' => 1,
            'line_items[0][price_data][currency]' => strtolower($currency),
            'line_items[0][price_data][unit_amount]' => intval(round($amount * 100)),
            'line_items[0][price_data][product_data][name]' => $courseTitle,
            'metadata[token]' => $token,
        ];

        $result = self::request('POST', 'https://api.stripe.com/v1/checkout/sessions', [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/x-www-form-urlencoded',
        ], $body, true);

        if ($result['status'] !== 200 || empty($result['body']['url'])) {
            $msg = $result['body']['error']['message'] ?? 'Unknown Stripe error';
            throw new Exception('Stripe checkout session creation failed: ' . $msg);
        }

        return [
            'session_id' => $result['body']['id'],
            'redirect_url' => $result['body']['url'],
        ];
    }

    public static function verifyStripeSession($sessionId) {
        $cfg = self::config()['stripe'] ?? [];
        $secretKey = $cfg['secret_key'] ?? '';

        $result = self::request('GET', 'https://api.stripe.com/v1/checkout/sessions/' . urlencode($sessionId), [
            'Authorization: Bearer ' . $secretKey,
        ]);

        if ($result['status'] !== 200) {
            return ['paid' => false, 'error' => 'Could not retrieve Stripe session.'];
        }

        $paid = ($result['body']['payment_status'] ?? '') === 'paid';
        return ['paid' => $paid, 'raw' => $result['body']];
    }

    // ================= PAYPAL =================

    private static function paypalBaseUrl() {
        $cfg = self::config()['paypal'] ?? [];
        $mode = $cfg['mode'] ?? 'sandbox';
        return $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
    }

    private static function getPayPalAccessToken() {
        $cfg = self::config()['paypal'] ?? [];
        $clientId = $cfg['client_id'] ?? '';
        $clientSecret = $cfg['client_secret'] ?? '';
        if (empty($clientId) || strpos($clientId, 'CHANGE_ME') !== false) {
            throw new Exception('PayPal is not configured. Add real sandbox credentials to config.php.');
        }

        $authHeader = 'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret);
        $result = self::request('POST', self::paypalBaseUrl() . '/v1/oauth2/token', [
            $authHeader,
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
        ], 'grant_type=client_credentials', true);

        if ($result['status'] !== 200 || empty($result['body']['access_token'])) {
            throw new Exception('Could not authenticate with PayPal.');
        }
        return $result['body']['access_token'];
    }

    public static function createPayPalOrder($token, $courseTitle, $amount, $currency) {
        $accessToken = self::getPayPalAccessToken();
        $appUrl = self::appUrl();

        $body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'description' => $courseTitle,
                'amount' => [
                    'currency_code' => strtoupper($currency),
                    'value' => number_format($amount, 2, '.', ''),
                ],
                'custom_id' => $token,
            ]],
            'application_context' => [
                'return_url' => $appUrl . '/index.php?route=course/checkout/callback&gateway=paypal&token=' . urlencode($token),
                'cancel_url' => $appUrl . '/index.php?route=course/checkout&id=0&cancelled=1&token=' . urlencode($token),
                'user_action' => 'PAY_NOW',
            ],
        ];

        $result = self::request('POST', self::paypalBaseUrl() . '/v2/checkout/orders', [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ], $body);

        if ($result['status'] !== 201 || empty($result['body']['links'])) {
            $msg = $result['body']['message'] ?? 'Unknown PayPal error';
            throw new Exception('PayPal order creation failed: ' . $msg);
        }

        $approveUrl = null;
        foreach ($result['body']['links'] as $link) {
            if ($link['rel'] === 'approve') {
                $approveUrl = $link['href'];
                break;
            }
        }
        if (!$approveUrl) {
            throw new Exception('PayPal did not return an approval URL.');
        }

        return [
            'order_id' => $result['body']['id'],
            'redirect_url' => $approveUrl,
        ];
    }

    public static function capturePayPalOrder($orderId) {
        $accessToken = self::getPayPalAccessToken();

        $result = self::request('POST', self::paypalBaseUrl() . "/v2/checkout/orders/{$orderId}/capture", [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ], (object)[]);

        $completed = ($result['body']['status'] ?? '') === 'COMPLETED';
        return ['paid' => $completed, 'raw' => $result['body']];
    }

    // ================= PAYSTACK =================

    public static function createPaystackSession($token, $courseTitle, $amount, $currency, $email) {
        $cfg = self::config()['paystack'] ?? [];
        $secretKey = $cfg['secret_key'] ?? '';
        if (empty($secretKey) || strpos($secretKey, 'CHANGE_ME') !== false) {
            throw new Exception('Paystack is not configured. Add a real test secret key to config.php.');
        }

        $appUrl = self::appUrl();
        $callbackUrl = $appUrl . '/index.php?route=course/checkout/callback&gateway=paystack&token=' . urlencode($token);

        $body = [
            'email' => $email,
            'amount' => intval(round($amount * 100)),
            'currency' => strtoupper($currency),
            'reference' => $token,
            'callback_url' => $callbackUrl,
            'metadata' => ['token' => $token, 'course_title' => $courseTitle],
        ];

        $result = self::request('POST', 'https://api.paystack.co/transaction/initialize', [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json',
        ], $body);

        if ($result['status'] !== 200 || empty($result['body']['data']['authorization_url'])) {
            $msg = $result['body']['message'] ?? 'Unknown Paystack error';
            throw new Exception('Paystack transaction initialization failed: ' . $msg);
        }

        return [
            'reference' => $result['body']['data']['reference'],
            'redirect_url' => $result['body']['data']['authorization_url'],
        ];
    }

    public static function verifyPaystackTransaction($reference) {
        $cfg = self::config()['paystack'] ?? [];
        $secretKey = $cfg['secret_key'] ?? '';

        $result = self::request('GET', 'https://api.paystack.co/transaction/verify/' . urlencode($reference), [
            'Authorization: Bearer ' . $secretKey,
        ]);

        if ($result['status'] !== 200) {
            return ['paid' => false, 'error' => 'Could not verify Paystack transaction.'];
        }

        $paid = ($result['body']['data']['status'] ?? '') === 'success';
        return ['paid' => $paid, 'raw' => $result['body']['data'] ?? null];
    }

    // ================= FLUTTERWAVE =================

    public static function createFlutterwaveSession($token, $courseTitle, $amount, $currency, $email, $name) {
        $cfg = self::config()['flutterwave'] ?? [];
        $secretKey = $cfg['secret_key'] ?? '';
        if (empty($secretKey) || strpos($secretKey, 'CHANGE_ME') !== false) {
            throw new Exception('Flutterwave is not configured. Add a real test secret key to config.php.');
        }

        $appUrl = self::appUrl();
        $redirectUrl = $appUrl . '/index.php?route=course/checkout/callback&gateway=flutterwave&token=' . urlencode($token);

        $body = [
            'tx_ref' => $token,
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => strtoupper($currency),
            'redirect_url' => $redirectUrl,
            'customer' => [
                'email' => $email,
                'name' => $name ?: $email,
            ],
            'customizations' => [
                'title' => $courseTitle,
            ],
        ];

        $result = self::request('POST', 'https://api.flutterwave.com/v3/payments', [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json',
        ], $body);

        if ($result['status'] !== 200 || empty($result['body']['data']['link'])) {
            $msg = $result['body']['message'] ?? 'Unknown Flutterwave error';
            throw new Exception('Flutterwave payment initialization failed: ' . $msg);
        }

        return [
            'redirect_url' => $result['body']['data']['link'],
        ];
    }

    public static function verifyFlutterwaveTransaction($txRef) {
        $cfg = self::config()['flutterwave'] ?? [];
        $secretKey = $cfg['secret_key'] ?? '';

        $result = self::request('GET', 'https://api.flutterwave.com/v3/transactions/verify_by_reference?tx_ref=' . urlencode($txRef), [
            'Authorization: Bearer ' . $secretKey,
        ]);

        if ($result['status'] !== 200) {
            return ['paid' => false, 'error' => 'Could not verify Flutterwave transaction.'];
        }

        $data = $result['body']['data'] ?? null;
        $paid = $data && ($data['status'] ?? '') === 'successful';
        return ['paid' => $paid, 'raw' => $data];
    }
}
