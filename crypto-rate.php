<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function spot(string $pair): ?float {
    $url = 'https://api.coinbase.com/v2/prices/' . rawurlencode($pair) . '/spot';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_USERAGENT => 'CRTSHT draw/2026'
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if (!is_string($body) || $code < 200 || $code >= 300) return null;
    $data = json_decode($body, true);
    $amount = $data['data']['amount'] ?? null;
    return is_numeric($amount) && (float)$amount > 0 ? (float)$amount : null;
}

$cache = sys_get_temp_dir() . '/crtsht_crypto_chf.json';
$payload = null;
if (is_file($cache) && (time() - (int)filemtime($cache)) < 45) {
    $cached = json_decode((string)file_get_contents($cache), true);
    if (is_array($cached)) $payload = $cached;
}

if (!$payload) {
    $btc = spot('BTC-CHF');
    $eth = spot('ETH-CHF');
    if ($btc && $eth) {
        $payload = ['ok'=>true, 'btc_chf'=>$btc, 'eth_chf'=>$eth, 'updated_at'=>gmdate('c'), 'source'=>'Coinbase spot'];
        @file_put_contents($cache, json_encode($payload, JSON_UNESCAPED_SLASHES));
    } else {
        $payload = ['ok'=>false];
    }
}

echo json_encode($payload, JSON_UNESCAPED_SLASHES);
