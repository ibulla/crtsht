<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';
require_once dirname(__DIR__) . '/inc/mailer.php';

header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Cache-Control: no-store, private');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax'
    ]);
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$reservationCode = strtoupper(trim((string)($_POST['reservation'] ?? '')));
$postedCsrf = (string)($_POST['csrf'] ?? '');
$sessionCsrf = (string)($_SESSION['draw_csrf'] ?? '');

if ($sessionCsrf === '' || !hash_equals($sessionCsrf, $postedCsrf)) {
    http_response_code(400);
    exit('Security token expired. Please return to The Draw.');
}
if (!preg_match('/^R-[A-F0-9]{10}$/', $reservationCode)) {
    http_response_code(400);
    exit('Invalid reservation code.');
}

$db = crt_db();
if (!$db) {
    http_response_code(503);
    exit('Database unavailable.');
}

$stmt = $db->prepare('SELECT * FROM CRTSHT_Draw_Reservations WHERE ReservationCode=? LIMIT 1');
if (!$stmt) {
    $db->close();
    http_response_code(500);
    exit('Reservation lookup failed.');
}
$stmt->bind_param('s', $reservationCode);
$stmt->execute();
$res = $stmt->get_result();
$reservation = $res ? $res->fetch_assoc() : null;
if ($res) $res->free();
$stmt->close();
$db->close();

if (!is_array($reservation)) {
    http_response_code(404);
    exit('Reservation not found.');
}
if ((string)$reservation['Status'] !== 'reserved') {
    http_response_code(409);
    exit('Payment details are only available for open reservations.');
}
if ((float)$reservation['TotalPrice'] <= 0) {
    http_response_code(409);
    exit('No payment amount is stored for this reservation.');
}

$mail = crt_mail_payment_details_customer($reservation);
$ok = (bool)($mail['ok'] ?? false);
if (!$ok) {
    error_log('CRTSHT payment details mail failed for ' . $reservationCode . ': ' . (string)($mail['error'] ?? 'unknown error'));
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>CRTSHT / PAYMENT DETAILS</title>
<link rel="stylesheet" href="/site.css?v=8">
<style>.pay-wrap{max-width:760px;margin:8vh auto;padding:var(--pad)}.pay-box{border:1px solid var(--fg);padding:clamp(18px,4vw,42px)}.pay-box h1{font-size:clamp(42px,8vw,92px);line-height:.86;letter-spacing:-.07em;margin:0 0 24px}.pay-box p{font-size:13px;line-height:1.6;max-width:58ch}.pay-meta{border-top:1px solid var(--line);margin-top:24px;padding-top:14px;font-size:10px;text-transform:uppercase;letter-spacing:.07em}.pay-box a{color:inherit}</style>
</head>
<body><main class="pay-wrap"><div class="pay-box">
<?php if ($ok): ?>
<h1>CHECK<br>YOUR MAIL.</h1>
<p>Bank-transfer / invoice payment details have been sent to the email address stored with your reservation.</p>
<div class="pay-meta"><?=crt_e($reservationCode)?> · CHF <?=crt_e(number_format((float)$reservation['TotalPrice'],2,'.',"'"))?> · PAYMENT OPEN</div>
<?php else: ?>
<h1>MAIL<br>FAILED.</h1>
<p>Your reservation is still stored, but the payment details email could not be sent. Please contact us with your reservation code.</p>
<div class="pay-meta"><?=crt_e($reservationCode)?></div>
<?php endif; ?>
<p><a href="/">← BACK TO THE ARCHIVE</a></p>
</div></main></body></html>
