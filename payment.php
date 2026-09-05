<?php
declare(strict_types=1);

require __DIR__ . '/inc/stripe.php';
require_once __DIR__ . '/inc/mailer.php';

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

$csrf = (string)($_SESSION['draw_csrf'] ?? '');
if (strlen($csrf) < 32) {
    $csrf = bin2hex(random_bytes(24));
    $_SESSION['draw_csrf'] = $csrf;
}

$reservationCode = strtoupper(trim((string)($_GET['reservation'] ?? '')));
if (!preg_match('/^R-[A-F0-9]{10}$/', $reservationCode)) {
    http_response_code(404);
    $reservation = null;
} else {
    $db = crt_db();
    if (!$db) {
        http_response_code(503);
        exit('Database unavailable.');
    }

    $stmt = $db->prepare('SELECT ID,ReservationCode,DrawBatch,Quantity,Status,UnitPrice,TotalPrice,PaidAt,StripePaymentStatus FROM CRTSHT_Draw_Reservations WHERE ReservationCode=? LIMIT 1');
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

    if (!is_array($reservation)) http_response_code(404);
}

$status = is_array($reservation) ? (string)$reservation['Status'] : '';
$total = is_array($reservation) ? (float)$reservation['TotalPrice'] : 0.0;
$quantity = is_array($reservation) ? (int)$reservation['Quantity'] : 0;
$iban = crt_env('CRTSHT_PAYMENT_IBAN');
$accountName = crt_env('CRTSHT_PAYMENT_NAME');
$accountAddr = crt_env('CRTSHT_PAYMENT_ADDR');
$accountCity = crt_env('CRTSHT_PAYMENT_CITY');
$bankReady = $iban !== '' && $total > 0;
$stripeReady = crt_stripe_ready() && $status === 'reserved' && $total > 0;
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>CRTSHT / COMPLETE PAYMENT</title>
<link rel="stylesheet" href="/site.css?v=8">
<style>
.payment-page{max-width:1180px}
.payment-hero{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(280px,.9fr);gap:var(--pad);align-items:end;margin-bottom:calc(var(--pad)*1.2)}
.payment-hero h1{font-size:clamp(42px,7vw,104px);line-height:.84;letter-spacing:-.075em;margin:.08em 0 .2em;max-width:9ch}.payment-hero p{font-size:13px;line-height:1.6;max-width:58ch}
.payment-meta{display:grid;grid-template-columns:repeat(4,1fr);border-top:1px solid var(--fg);border-left:1px solid var(--line);margin-bottom:calc(var(--pad)*1.2)}.payment-meta>div{padding:12px;border-right:1px solid var(--line);border-bottom:1px solid var(--line)}.payment-meta span{display:block;font-size:9px;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:5px}.payment-meta strong{font-size:13px}
.payment-options{display:grid;grid-template-columns:1fr 1fr;gap:14px}.payment-box{border:1px solid var(--fg);padding:clamp(18px,3vw,32px);display:flex;flex-direction:column;justify-content:space-between;min-height:320px}.payment-box h2{font-size:clamp(28px,4vw,56px);line-height:.9;letter-spacing:-.06em;margin:8px 0 18px}.payment-box p{font-size:12px;line-height:1.55}.payment-box button,.payment-button{display:block;width:100%;font:inherit;font-size:11px;text-align:center;border:0;background:var(--fg);color:var(--bg);padding:13px;cursor:pointer;text-decoration:none;margin-top:18px}.bank-data{border-top:1px solid var(--line);margin-top:18px;padding-top:12px;font-size:11px;line-height:1.7}.bank-data b{display:inline-block;min-width:82px}.payment-state{border:1px solid var(--fg);padding:clamp(24px,5vw,56px)}.payment-state h1{font-size:clamp(48px,8vw,110px);line-height:.84;letter-spacing:-.075em;margin:0 0 24px}.payment-state p{font-size:13px;line-height:1.6}.payment-note{color:var(--muted)}
@media(max-width:760px){.payment-hero,.payment-options{grid-template-columns:1fr}.payment-meta{grid-template-columns:1fr 1fr}}
</style>
</head>
<body><main class="wrap payment-page">
<header><a class="brand" href="/">CR¥P70$H!7</a><nav class="nav"><a href="/">Archive</a><a href="/lore">The Lore</a><a href="/oracle">The Oracle</a><a href="/draw">The Draw</a></nav></header>

<?php if (!is_array($reservation)): ?>
<section class="payment-state"><div class="eyebrow">CRTSHT / PAYMENT TERMINAL</div><h1>NOT<br>FOUND.</h1><p>This payment link does not match an active CRTSHT reservation.</p><p><a href="/draw">← RETURN TO THE DRAW</a></p></section>

<?php elseif ($status === 'paid'): ?>
<section class="payment-state"><div class="eyebrow">CRTSHT / PAYMENT TERMINAL</div><h1>PAID.<br>DRAW ACTIVE.</h1><p>Your reservation <strong><?=crt_e($reservationCode)?></strong> is already paid. No further payment is required.</p><p class="payment-note">You will receive your CRTSHT assignment after the scheduled draw.</p><p><a href="/draw">← THE DRAW</a></p></section>

<?php elseif ($status !== 'reserved'): ?>
<section class="payment-state"><div class="eyebrow">CRTSHT / PAYMENT TERMINAL</div><h1>PAYMENT<br>CLOSED.</h1><p>Reservation <strong><?=crt_e($reservationCode)?></strong> currently has status <strong><?=crt_e(strtoupper($status))?></strong> and cannot be paid through this page.</p><p><a href="/draw">← RETURN TO THE DRAW</a></p></section>

<?php else: ?>
<section class="payment-hero"><div><div class="eyebrow">CRTSHT / PAYMENT TERMINAL</div><h1>COMPLETE PAYMENT.</h1></div><div><p>Your place is reserved. Choose how to complete payment. The artwork itself remains unknown until The Draw.</p><p class="payment-note">This link remains usable if you closed the browser after making the reservation.</p></div></section>

<section class="payment-meta">
<div><span>Reservation</span><strong><?=crt_e($reservationCode)?></strong></div>
<div><span>Draw</span><strong>DRAW <?=crt_e((string)$reservation['DrawBatch'])?></strong></div>
<div><span>Quantity</span><strong><?=$quantity?>×</strong></div>
<div><span>Total</span><strong><?=crt_e(crt_mail_price($total))?></strong></div>
</section>

<section class="payment-options">
<div class="payment-box"><div><div class="eyebrow">OPTION 01 / ONLINE</div><h2>CARD / TWINT.</h2><p>Pay securely through Stripe. Once Stripe confirms the payment, your reservation automatically changes to <strong>PAID / DRAW ACTIVE</strong>.</p></div>
<?php if ($stripeReady): ?><form method="post" action="/stripe/start.php"><input type="hidden" name="reservation" value="<?=crt_e($reservationCode)?>"><button type="submit">PAY NOW / CARD + TWINT →</button></form><?php else: ?><p class="payment-note">Online payment is currently unavailable. Your reservation remains stored.</p><?php endif; ?>
</div>

<div class="payment-box"><div><div class="eyebrow">OPTION 02 / BANK</div><h2>BANK TRANSFER.</h2><p>Transfer the exact amount and use your reservation code as the payment reference.</p>
<?php if ($bankReady): ?><div class="bank-data"><div><b>AMOUNT</b> <?=crt_e(crt_mail_price($total))?></div><div><b>IBAN</b> <?=crt_e($iban)?></div><?php if($accountName!==''):?><div><b>NAME</b> <?=crt_e($accountName)?></div><?php endif;?><?php if($accountAddr!==''):?><div><b>ADDRESS</b> <?=crt_e($accountAddr)?></div><?php endif;?><?php if($accountCity!==''):?><div><b>CITY</b> <?=crt_e($accountCity)?></div><?php endif;?><div><b>REFERENCE</b> <?=crt_e($reservationCode)?></div></div><?php else: ?><p class="payment-note">Bank-transfer details are currently unavailable.</p><?php endif; ?></div>
<?php if ($bankReady): ?><form method="post" action="/payment/mail.php"><input type="hidden" name="reservation" value="<?=crt_e($reservationCode)?>"><input type="hidden" name="csrf" value="<?=crt_e($csrf)?>"><button type="submit">EMAIL BANK DETAILS →</button></form><?php endif; ?>
</div>
</section>
<?php endif; ?>

<footer class="footer"><span>CRTSHT / <a href="https://ibulla.com" target="_blank" rel="noopener">iBulla</a></span><span><a href="/legal">LEGAL / IMPRINT</a></span></footer>
</main></body></html>
