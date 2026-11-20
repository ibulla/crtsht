<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/selected-common.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off','samesite'=>'Lax']);
    session_start();
}
$db = crt_db();
if (!$db) { http_response_code(503); exit('Selected works are temporarily unavailable.'); }
crt_selected_db_timezone($db);
$csrf = $_SESSION['selected_csrf'] ?? '';
if (!is_string($csrf) || strlen($csrf) < 32) $_SESSION['selected_csrf'] = $csrf = bin2hex(random_bytes(24));
$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $workId = filter_var($_POST['work_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    $name = trim((string)($_POST['name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $mobile = trim((string)($_POST['mobile'] ?? ''));
    $address = trim((string)($_POST['address'] ?? ''));
    $plz = trim((string)($_POST['plz'] ?? ''));
    $city = trim((string)($_POST['city'] ?? ''));
    $country = trim((string)($_POST['country'] ?? ''));
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? '')) || trim((string)($_POST['company'] ?? '')) !== '') $error = 'Invalid request.';
    elseif (!$workId || $name === '' || mb_strlen($name)>120 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>190 ||
      mb_strlen($mobile)>50 || mb_strlen($address)>190 || mb_strlen($plz)>24 || mb_strlen($city)>120 || mb_strlen($country)>120) $error = 'Please check your contact details.';
    else {
        try {
            $db->begin_transaction();
            $stmt=$db->prepare("SELECT ID,CRTSHT_ID,PriceCHF FROM CRTSHT_Selected_Works WHERE ID=? AND SaleStatus='open' AND OfferDeadline>NOW() FOR UPDATE");
            $stmt->bind_param('i',$workId); $stmt->execute(); $work=$stmt->get_result()->fetch_assoc(); $stmt->close();
            if (!$work) throw new RuntimeException('Offers for this work are closed.');
            $cid=(int)$work['CRTSHT_ID'];
            $stmt=$db->prepare("SELECT COUNT(*) AS n FROM CRTSHT_Draw_Entries WHERE AssignedCRTSHT=?");
            $stmt->bind_param('i',$cid);$stmt->execute();$assigned=(int)$stmt->get_result()->fetch_assoc()['n'];$stmt->close();
            if ($assigned>0) throw new RuntimeException('This work is already allocated.');
            $code='O-'.strtoupper(bin2hex(random_bytes(8)));
            $price=(string)$work['PriceCHF'];
            $stmt=$db->prepare("INSERT INTO CRTSHT_Selected_Offers (SelectedWorkID,OfferCode,Name,Email,Mobile,Address,PLZ,City,Country,OfferedPriceCHF) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('isssssssss',$workId,$code,$name,$email,$mobile,$address,$plz,$city,$country,$price);
            if (!$stmt->execute()) throw new RuntimeException('Unable to store offer.');$stmt->close();
            $db->commit();
            $success=$code;
            $_SESSION['selected_csrf']=bin2hex(random_bytes(24));
            $csrf=$_SESSION['selected_csrf'];
        } catch(Throwable $e) { $db->rollback(); error_log('Selected offer: '.$e->getMessage()); $error='Offer could not be submitted. Please try again.'; }
    }
}
$works=[];
$res=$db->query("SELECT w.ID,w.CRTSHT_ID,w.PriceCHF,w.Location,w.ExhibitionUntil,w.OfferDeadline FROM CRTSHT_Selected_Works w WHERE w.SaleStatus='open' AND w.OfferDeadline>NOW() AND NOT EXISTS (SELECT 1 FROM CRTSHT_Draw_Entries e WHERE e.AssignedCRTSHT=w.CRTSHT_ID) ORDER BY w.OfferDeadline,w.CRTSHT_ID");
if ($res) {while($row=$res->fetch_assoc())$works[]=$row;$res->free();}
$db->close();
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Selected Works / CRTSHT</title><link rel="stylesheet" href="/site.css?v=13">
<style>.selected{max-width:1080px;margin:auto;padding:clamp(18px,3vw,44px)}.work{border-top:1px solid #111;padding:24px 0;display:grid;grid-template-columns:minmax(180px,1fr) minmax(280px,1fr);gap:30px}.work img{width:100%;max-width:400px}.work input{display:block;width:100%;padding:10px;margin:6px 0 12px;border:1px solid #888;background:transparent;color:inherit}.work button{padding:12px 20px;background:#111;color:#fff;border:0;cursor:pointer}.muted{opacity:.7}.message{padding:16px;border:1px solid}@media(max-width:700px){.work{grid-template-columns:1fr}}</style></head><body>
<main class="selected"><p><a href="/draw">← THE DRAW</a></p><h1>SELECTED WORKS</h1>
<p>Choose a specific work. Submit an offer at the displayed fixed price before the deadline. The artist decides who receives the purchase opportunity after the deadline; selection may be manual or random. No payment is taken now. Only the selected buyer receives a payment invitation.</p>
<?php if($error!==''):?><p class="message" role="alert"><?=crt_e($error)?></p><?php endif;?>
<?php if($success!==''):?><p class="message" role="status">Your offer was recorded. Reference: <?=crt_e($success)?>. This is not yet a reservation or purchase confirmation.</p><?php endif;?>
<?php if(!$works):?><p>There are no selected works open for offers at the moment.</p><?php endif;?>
<?php foreach($works as $work): $cid=(int)$work['CRTSHT_ID']; $imgs=glob(__DIR__.'/shitpix_jpg_1-128/'.$cid.'-*.jpg'); $img=$imgs?'/shitpix_jpg_1-128/'.rawurlencode(basename($imgs[0])):null;?>
<section class="work"><div><?php if($img):?><a href="/crtsht/<?=$cid?>"><img src="<?=crt_e($img)?>" alt="CRTSHT <?= $cid ?>" loading="lazy"></a><?php endif;?><h2><a href="/crtsht/<?=$cid?>">CRTSHT #<?=$cid?></a></h2>
<p>FIXED PRICE / CHF <?=number_format((float)$work['PriceCHF'],2,'.',"'")?></p>
<p>OFFERS CLOSE / <?=crt_e((string)$work['OfferDeadline'])?> (Europe/Zurich)</p>
<?php if($work['Location']):?><p>ON VIEW / <?=crt_e((string)$work['Location'])?></p><?php endif;?>
<?php if($work['ExhibitionUntil']):?><p>EXHIBITION UNTIL / <?=crt_e((string)$work['ExhibitionUntil'])?></p><?php endif;?></div>
<form method="post"><h3>MAKE AN OFFER</h3><input type="hidden" name="csrf" value="<?=crt_e($csrf)?>"><input type="hidden" name="work_id" value="<?=(int)$work['ID']?>">
<div style="position:absolute;left:-9999px" aria-hidden="true"><input name="company" tabindex="-1" autocomplete="off"></div>
<label>Name <input name="name" required maxlength="120" autocomplete="name"></label>
<label>Email <input name="email" type="email" required maxlength="190" autocomplete="email"></label>
<label>Mobile <input name="mobile" maxlength="50" autocomplete="tel"></label>
<label>Address <input name="address" maxlength="190" autocomplete="street-address"></label>
<label>Postal code <input name="plz" maxlength="24" autocomplete="postal-code"></label>
<label>City <input name="city" maxlength="120" autocomplete="address-level2"></label>
<label>Country <input name="country" maxlength="120" autocomplete="country-name"></label>
<p class="muted">I offer CHF <?=number_format((float)$work['PriceCHF'],2,'.',"'")?> for this specific work. Submission does not guarantee allocation. Payment is requested only after acceptance.</p>
<button type="submit">SUBMIT OFFER →</button></form></section>
<?php endforeach;?></main></body></html>
