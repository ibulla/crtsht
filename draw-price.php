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

$out = ['ok'=>true, 'currency'=>'CHF', 'prices'=>null, 'unit_price'=>null, 'total_price'=>null];

$reservation = strtoupper(trim((string)($_GET['reservation'] ?? '')));
if ($reservation !== '' && preg_match('/^R-[A-F0-9]{10}$/', $reservation)) {
    $stmt = $db->prepare('SELECT Quantity,UnitPrice,TotalPrice FROM CRTSHT_Draw_Reservations WHERE ReservationCode=? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $reservation);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        if ($row) {
            $out['quantity'] = (int)$row['Quantity'];
            $out['unit_price'] = (float)$row['UnitPrice'];
            $out['total_price'] = (float)$row['TotalPrice'];
        }
        $stmt->close();
    }
} else {
    $prices = [1=>0.0, 2=>0.0, 3=>0.0];
    $res = $db->query("SELECT SettingKey,SettingValue FROM CRTSHT_Draw_Settings WHERE SettingKey IN ('price_1_chf','price_2_chf','price_3_chf')");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            if (preg_match('/^price_([123])_chf$/', (string)$row['SettingKey'], $m)) {
                $prices[(int)$m[1]] = (float)$row['SettingValue'];
            }
        }
        $res->free();
    }
    $out['prices'] = ['1'=>$prices[1], '2'=>$prices[2], '3'=>$prices[3]];
}
$db->close();

echo json_encode($out, JSON_UNESCAPED_SLASHES);
