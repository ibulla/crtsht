<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';

header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Cache-Control: no-store, private');

$sessionId = trim((string)($_GET['session_id'] ?? ''));
$reservation = null;
if ($sessionId !== '' && preg_match('/^cs_[A-Za-z0-9_]+$/', $sessionId)) {
    $db = crt_db();
    if ($db) {
        $stmt = $db->prepare('SELECT ReservationCode,Quantity,Status,StripePaymentStatus FROM CRTSHT_Draw_Reservations WHERE StripeCheckoutSessionID=? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $sessionId);
            $stmt->execute();
            $res = $stmt->get_result();
            $reservation = $res ? $res->fetch_assoc() : null;
            if ($res) $res->free();
            $stmt->close();
        }
        $db->close();
    }
}

$paid = is_array($reservation) && (string)$reservation['Status'] === 'paid';
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>CRTSHT / PAYMENT</title>
<link rel="stylesheet" href="/site.css?v=8">
<style>.pay-wrap{max-width:760px;margin:8vh auto;padding:var(--pad)}.pay-box{border:1px solid var(--fg);padding:clamp(18px,4vw,42px)}.pay-box h1{font-size:clamp(42px,8vw,92px);line-height:.86;letter-spacing:-.07em;margin:0 0 24px}.pay-box p{font-size:13px;line-height:1.6;max-width:58ch}.pay-meta{border-top:1px solid var(--line);margin-top:24px;padding-top:14px;font-size:10px;text-transform:uppercase;letter-spacing:.07em}.pay-box a{color:inherit}</style>
</head>
<body><main class="pay-wrap"><div class="pay-box">
<?php if ($paid): ?>
<h1>YOU'RE IN.</h1>
<p>Your payment is confirmed. Your CRTSHT remains unassigned until The Draw.</p>
<?php if (is_array($reservation)): ?><div class="pay-meta"><?=crt_e((string)$reservation['ReservationCode'])?> · <?=crt_e((string)$reservation['Quantity'])?>× DRAW <?=((int)$reservation['Quantity']===1?'ENTRY':'ENTRIES')?></div><?php endif; ?>
<?php else: ?>
<h1>PAYMENT<br>RECEIVED.</h1>
<p>Stripe returned you to CRTSHT. Payment confirmation is being verified independently by the payment webhook. Do not submit a second reservation just because this page still says pending.</p>
<div class="pay-meta">STATUS / VERIFYING</div>
<?php endif; ?>
<p><a href="/draw">← RETURN TO THE DRAW</a></p>
</div></main></body></html>
