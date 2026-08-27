<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=86400');

$tx = strtolower(trim((string)($_GET['tx'] ?? '')));
if (!preg_match('/^0x[a-f0-9]{64}$/', $tx)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'invalid transaction hash']);
    exit;
}

function gas_rpc_receipt(string $tx): ?array {
    $endpoints = array_values(array_unique(array_filter([
        crt_env('ETH_RPC_URL'),
        'https://cloudflare-eth.com',
        'https://ethereum-rpc.publicnode.com'
    ])));
    $payload = json_encode(['jsonrpc'=>'2.0','id'=>1,'method'=>'eth_getTransactionReceipt','params'=>[$tx]]);
    if (!is_string($payload)) return null;

    foreach ($endpoints as $url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_USERAGENT => 'CRTSHT archive/2026'
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if (!is_string($body) || $code < 200 || $code >= 300) continue;
        $data = json_decode($body, true);
        if (is_array($data) && is_array($data['result'] ?? null)) return $data['result'];
    }
    return null;
}

$receipt = gas_rpc_receipt($tx);
if (!$receipt) {
    http_response_code(503);
    echo json_encode(['ok'=>false,'error'=>'receipt unavailable']);
    exit;
}

$gasUsedHex = (string)($receipt['gasUsed'] ?? '');
$priceHex = (string)($receipt['effectiveGasPrice'] ?? '');
if (!preg_match('/^0x[a-f0-9]+$/', $gasUsedHex) || !preg_match('/^0x[a-f0-9]+$/', $priceHex)) {
    http_response_code(503);
    echo json_encode(['ok'=>false,'error'=>'fee data unavailable']);
    exit;
}

$gasUsed = hexdec($gasUsedHex);
$priceWei = hexdec($priceHex);
$feeWei = $gasUsed * $priceWei;
$feeEth = $feeWei / 1e18;
$priceGwei = $priceWei / 1e9;

echo json_encode([
    'ok'=>true,
    'tx'=>$tx,
    'gas_used'=>$gasUsed,
    'gas_price_gwei'=>round($priceGwei, 4),
    'fee_eth'=>number_format($feeEth, 8, '.', '')
], JSON_UNESCAPED_SLASHES);
