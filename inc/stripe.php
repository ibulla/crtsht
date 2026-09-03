<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

/**
 * Isolated Stripe support for CRTSHT.
 *
 * No Stripe secret belongs in Git. bootstrap.php already loads private/config.php
 * into the environment, so keep all values there on the server.
 */
function crt_stripe_enabled(): bool {
    return in_array(strtolower(crt_env('CRTSHT_STRIPE_ENABLED')), ['1','true','yes','on'], true);
}

function crt_stripe_secret_key(): string {
    return crt_env('STRIPE_SECRET_KEY');
}

function crt_stripe_webhook_secret(): string {
    return crt_env('STRIPE_WEBHOOK_SECRET');
}

function crt_stripe_ready(): bool {
    return crt_stripe_enabled() && crt_stripe_secret_key() !== '';
}

function crt_stripe_is_live(): bool {
    return str_starts_with(crt_stripe_secret_key(), 'sk_live_');
}

function crt_stripe_base_url(): string {
    $configured = rtrim(crt_env('CRTSHT_PUBLIC_URL'), '/');
    if ($configured !== '') return $configured;
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'cryptoshit.info');
    return ($https ? 'https://' : 'http://') . $host;
}

function crt_stripe_request(string $method, string $path, array $params = [], string $idempotencyKey = ''): array {
    $secret = crt_stripe_secret_key();
    if ($secret === '') throw new RuntimeException('Stripe secret key is not configured.');

    $url = 'https://api.stripe.com' . $path;
    $headers = [
        'Authorization: Bearer ' . $secret,
        'Content-Type: application/x-www-form-urlencoded',
    ];
    if ($idempotencyKey !== '') $headers[] = 'Idempotency-Key: ' . $idempotencyKey;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_USERAGENT => 'CRTSHT/2026 Stripe Checkout',
    ]);

    $method = strtoupper($method);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, '', '&', PHP_QUERY_RFC3986));
    } elseif ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($params) curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, '', '&', PHP_QUERY_RFC3986));
    } elseif ($params) {
        curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986));
    }

    $body = curl_exec($ch);
    $curlError = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if (!is_string($body) || $body === '') {
        throw new RuntimeException('Stripe request failed' . ($curlError !== '' ? ': ' . $curlError : '.'));
    }

    $json = json_decode($body, true);
    if (!is_array($json)) throw new RuntimeException('Stripe returned an unreadable response.');
    if ($status < 200 || $status >= 300) {
        $message = (string)($json['error']['message'] ?? 'Stripe request failed.');
        throw new RuntimeException($message);
    }
    return $json;
}

function crt_stripe_verify_webhook(string $payload, string $signatureHeader, int $tolerance = 300): bool {
    $secret = crt_stripe_webhook_secret();
    if ($secret === '' || $payload === '' || $signatureHeader === '') return false;

    $timestamp = null;
    $signatures = [];
    foreach (explode(',', $signatureHeader) as $part) {
        [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
        if ($key === 't' && ctype_digit($value)) $timestamp = (int)$value;
        if ($key === 'v1' && preg_match('/^[a-f0-9]{64}$/i', $value)) $signatures[] = strtolower($value);
    }
    if (!$timestamp || !$signatures) return false;
    if (abs(time() - $timestamp) > $tolerance) return false;

    $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    foreach ($signatures as $signature) {
        if (hash_equals($expected, $signature)) return true;
    }
    return false;
}

function crt_stripe_chf_cents(float $amount): int {
    return (int)round($amount * 100, 0, PHP_ROUND_HALF_UP);
}
