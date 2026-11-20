<?php
declare(strict_types=1);
require dirname(__DIR__) . '/inc/bootstrap.php';
header('X-Robots-Tag: noindex, nofollow, noarchive',true);
header('Cache-Control: no-store, private');
$sid=(string)($_GET['session_id']??'');
$offer=null;
if(preg_match('/^cs_[A-Za-z0-9_]+$/',$sid)){
    $db=crt_db();
    if($db){
        $stmt=$db->prepare('SELECT o.Status,o.PaidAt,w.CRTSHT_ID,w.SaleStatus FROM CRTSHT_Selected_Offers o JOIN CRTSHT_Selected_Works w ON w.ID=o.SelectedWorkID WHERE o.StripeCheckoutSessionID=?');
        if($stmt){$stmt->bind_param('s',$sid);$stmt->execute();$offer=$stmt->get_result()->fetch_assoc();$stmt->close();}
        $db->close();
    }
}
$paid=$offer&&$offer['SaleStatus']==='sold'&&$offer['PaidAt']!==null;
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>CRTSHT / PAYMENT</title><link rel="stylesheet" href="/site.css?v=13">
<style>main{max-width:780px;margin:10vh auto;padding:30px}h1{font-size:clamp(48px,10vw,100px);line-height:.88;letter-spacing:-.07em}p{line-height:1.5}</style></head><body><main>
<?php if($paid):?><h1>PAID.<br>YOURS.</h1><p>Payment for CRTSHT #<?=(int)$offer['CRTSHT_ID']?> is confirmed. We will contact you about the handover.</p>
<?php else:?><h1>PAYMENT<br>VERIFYING.</h1><p>Stripe returned you to CRTSHT. We are verifying payment independently through the secure webhook. Please do not pay twice.</p><?php endif;?>
<p><a href="/selected-works">← SELECTED WORKS</a></p></main></body></html>
