<?php
declare(strict_types=1);
require __DIR__ . '/inc/selected-mail.php';
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Cache-Control: no-store, private');
$code = strtoupper(trim((string)($_GET['reservation'] ?? '')));
$offer = null;
if (preg_match('/^W-[A-F0-9]{16}$/', $code)) {
    $db = crt_db();
    if (!$db) { http_response_code(503); exit('Database unavailable.'); }
    crt_selected_db_timezone($db);
    $stmt = $db->prepare("SELECT o.ID,o.OfferedPriceCHF,o.Status,o.PaidAt,w.CRTSHT_ID,w.SaleStatus,w.ReservationExpiresAt,w.WinnerOfferID,
       (w.ReservationExpiresAt > NOW()) AS IsCurrent
       FROM CRTSHT_Selected_Offers o INNER JOIN CRTSHT_Selected_Works w ON w.ID=o.SelectedWorkID
       WHERE o.ReservationCode=? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s',$code);$stmt->execute();
        $offer=$stmt->get_result()->fetch_assoc() ?: null;$stmt->close();
    }
    $db->close();
}
if (!$offer) http_response_code(404);
$paid=$offer && $offer['SaleStatus']==='sold' && $offer['PaidAt']!==null;
$open=$offer && $offer['Status']==='accepted' && $offer['SaleStatus']==='reserved'
    && (int)$offer['WinnerOfferID']===(int)$offer['ID'] && (int)$offer['IsCurrent']===1;
$iban=crt_env('CRTSHT_PAYMENT_IBAN');
$bankReady=$iban!=='' && $open;
$stripeReady=crt_stripe_ready() && $open;
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>CRTSHT / SELECTED WORKS PAYMENT</title><link rel="stylesheet" href="/site.css?v=13">
<style>.sp{max-width:1000px;margin:auto;padding:clamp(20px,5vw,50px)}.sp h1{font-size:clamp(44px,8vw,95px);letter-spacing:-.07em;line-height:.9;margin:25px 0}.sp p{line-height:1.55}.sp .intro{max-width:66ch}.pay-grid{display:grid;grid-template-columns:1fr 1fr;gap:15px;margin:30px 0}.pay-card{border:1px solid currentColor;padding:24px}.pay-card h2{font-size:clamp(26px,3vw,44px);letter-spacing:-.04em}.pay-card button{background:currentColor;padding:0;cursor:pointer}.pay-button{display:inline-block;background:#111;color:white;border:1px solid #111;padding:14px 20px;font:inherit;cursor:pointer}.details{border-top:1px solid #888;padding-top:16px;white-space:pre-wrap;overflow-wrap:anywhere;font-size:12px}.muted{opacity:.7}@media(max-width:700px){.pay-grid{grid-template-columns:1fr}}</style></head><body>
<main class="sp"><p><a href="/selected-works">← SELECTED WORKS</a></p>
<?php if(!$offer):?><h1>NOT FOUND.</h1><p>This payment reference is not valid.</p>
<?php elseif($paid):?><h1>PAID.<br>YOURS.</h1><p>Payment is confirmed for CRTSHT #<?=(int)$offer['CRTSHT_ID']?>. No further payment is needed.</p>
<?php elseif(!$open):?><h1>PAYMENT<br>CLOSED.</h1><p>This selected-work reservation is no longer payable. Please contact CRTSHT if you have already sent a bank transfer.</p>
<?php else:?>
<h1>THIS ONE<br>IS YOURS.</h1>
<p class="intro">Your purchase offer for <a href="/crtsht/<?=(int)$offer['CRTSHT_ID']?>"><strong>CRTSHT #<?=(int)$offer['CRTSHT_ID']?></strong></a> was accepted. Complete payment before the deadline to secure the work.</p>
<p><strong><?=crt_e(crt_mail_price($offer['OfferedPriceCHF']))?></strong> · Reference: <?=crt_e($code)?> · Deadline: <?=crt_e((string)$offer['ReservationExpiresAt'])?> (Europe/Zurich)</p>
<section class="pay-grid">
<div class="pay-card"><h2>CARD / TWINT.</h2><p>Secure online payment using the existing Stripe Checkout.</p>
<?php if($stripeReady):?><form method="post" action="/stripe/selected-start.php"><input type="hidden" name="reservation" value="<?=crt_e($code)?>"><button class="pay-button" type="submit">PAY WITH STRIPE →</button></form>
<?php else:?><p class="muted">Online payment is currently unavailable.</p><?php endif;?></div>
<div class="pay-card"><h2>BANK TRANSFER.</h2><p>Transfer the amount using your unique reference. Bank payments are confirmed manually after receipt.</p>
<?php if($bankReady):?><div class="details">AMOUNT    <?=crt_e(crt_mail_price($offer['OfferedPriceCHF'])) . "\n"?>
IBAN      <?=crt_e($iban) . "\n"?>
<?php if(crt_env('CRTSHT_PAYMENT_NAME')!==''):?>NAME      <?=crt_e(crt_env('CRTSHT_PAYMENT_NAME')) . "\n"?><?php endif;?>
<?php if(crt_env('CRTSHT_PAYMENT_ADDR')!==''):?>ADDRESS   <?=crt_e(crt_env('CRTSHT_PAYMENT_ADDR')) . "\n"?><?php endif;?>
<?php if(crt_env('CRTSHT_PAYMENT_CITY')!==''):?>CITY      <?=crt_e(crt_env('CRTSHT_PAYMENT_CITY')) . "\n"?><?php endif;?>
REFERENCE <?=crt_e($code)?></div>
<?php else:?><p class="muted">Bank-transfer details are not configured.</p><?php endif;?></div></section>
<p class="muted">The payment deadline refers to receipt of payment. If your transfer is still in transit, contact us before the deadline.</p>
<?php endif;?></main></body></html>
