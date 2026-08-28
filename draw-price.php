<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$db = crt_db();
if (!$db) {
    http_response_code(503);
    echo json_encode(['ok'=>false]);
    exit;
}

$out = ['ok'=>true, 'currency'=>'CHF', 'unit_price'=>null, 'total_price'=>null];

$reservation = strtoupper(trim((string)($_GET['reservation'] ?? '')));
if ($reservation !== '' && preg_match('/^R-[A-F0-9]{10}$/', $reservation)) {
    $stmt = $db->prepare('SELECT UnitPrice,TotalPrice FROM CRTSHT_Draw_Reservations WHERE ReservationCode=? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $reservation);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        if ($row) {
            $out['unit_price'] = (float)$row['UnitPrice'];
            $out['total_price'] = (float)$row['TotalPrice'];
        }
        $stmt->close();
    }
} else {
    $stmt = $db->prepare("SELECT SettingValue FROM CRTSHT_Draw_Settings WHERE SettingKey='entry_price_chf' LIMIT 1");
    if ($stmt) {
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        if ($row) $out['unit_price'] = (float)$row['SettingValue'];
        $stmt->close();
    }
}
$db->close();

echo json_encode($out, JSON_UNESCAPED_SLASHES);
