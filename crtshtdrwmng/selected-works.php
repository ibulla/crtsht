<?php
declare(strict_types=1);
require dirname(__DIR__) . '/inc/bootstrap.php';
require_once dirname(__DIR__) . '/inc/selected-mail.php';
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Cache-Control: no-store, private');
session_name('crtsht_draw_admin');
session_set_cookie_params(['path'=>'/crtshtdrwmng','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Strict']);
if(session_status()!==PHP_SESSION_ACTIVE)session_start();
if(empty($_SESSION['draw_admin_ok'])){header('Location: /crtshtdrwmng/',true,303);exit;}
function h(string $s):string{return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
$db=crt_db();if(!$db){http_response_code(503);exit('Database unavailable.');}
crt_selected_db_timezone($db);
$csrf=$_SESSION['csrf']??'';if(!is_string($csrf)||strlen($csrf)<32)$_SESSION['csrf']=$csrf=bin2hex(random_bytes(24));
$error='';$message='';$afterCommitMail=null;$artworkLock=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  if(!hash_equals($csrf,(string)($_POST['csrf']??'')))throw new RuntimeException('Invalid CSRF token.');
  $action=(string)($_POST['action']??'');
  // Serialize edits and reallocation within Selected Works only; DRAW provenance remains independent.
  if($action==='save_work'){
   $lockId=filter_var($_POST['crtsht_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>128]]);
   if(!$lockId)throw new RuntimeException('Invalid CRTSHT ID.');
   crt_selected_lock_artwork($db,$lockId);$artworkLock=$lockId;
  }elseif($action==='decide'){
   $lockWorkId=filter_var($_POST['work_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
   if(!$lockWorkId)throw new RuntimeException('Invalid work.');
   $stmt=$db->prepare('SELECT CRTSHT_ID FROM CRTSHT_Selected_Works WHERE ID=?');
   if(!$stmt)throw new RuntimeException('Artwork lookup failed.');
   $stmt->bind_param('i',$lockWorkId);$stmt->execute();$lockRow=$stmt->get_result()->fetch_assoc();$stmt->close();
   if(!$lockRow)throw new RuntimeException('Artwork not found.');
   $lockId=(int)$lockRow['CRTSHT_ID'];
   crt_selected_lock_artwork($db,$lockId);$artworkLock=$lockId;
  }
  $db->begin_transaction();
  if($action==='save_work'){
   $cid=filter_var($_POST['crtsht_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>128]]);
   $price=(string)($_POST['price']??'');
   $deadline=(string)($_POST['deadline']??'');
   $exhibition=(string)($_POST['exhibition']??'');
   $location=trim((string)($_POST['location']??''));
   $status=(string)($_POST['status']??'draft');
   if(!$cid||!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/',$price)||(float)$price<=0||mb_strlen($location)>255||!in_array($status,['draft','open','closed','withdrawn'],true))throw new RuntimeException('Invalid work configuration.');
   $tz=new DateTimeZone('Europe/Zurich');
   $dt=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$deadline,$tz);
   if(!$dt||$dt->format('Y-m-d\TH:i')!==$deadline)throw new RuntimeException('Invalid deadline.');
   $until=null;
   if($exhibition!==''){$e=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$exhibition,$tz);if(!$e||$e->format('Y-m-d\TH:i')!==$exhibition)throw new RuntimeException('Invalid exhibition date.');$until=$e->format('Y-m-d H:i:s');}
   $date=$dt->format('Y-m-d H:i:s');
   $stmt=$db->prepare('SELECT ID,SaleStatus FROM CRTSHT_Selected_Works WHERE CRTSHT_ID=? FOR UPDATE');$stmt->bind_param('i',$cid);$stmt->execute();$existing=$stmt->get_result()->fetch_assoc();$stmt->close();
   if($existing && in_array($existing['SaleStatus'],['reserved','sold'],true))throw new RuntimeException('Reserved or sold works cannot be reopened.');
   if($existing){
    $id=(int)$existing['ID'];$stmt=$db->prepare('UPDATE CRTSHT_Selected_Works SET PriceCHF=?,Location=?,ExhibitionUntil=?,OfferDeadline=?,SaleStatus=? WHERE ID=?');$stmt->bind_param('sssssi',$price,$location,$until,$date,$status,$id);
   }else{
    $stmt=$db->prepare('INSERT INTO CRTSHT_Selected_Works (CRTSHT_ID,PriceCHF,Location,ExhibitionUntil,OfferDeadline,SaleStatus) VALUES (?,?,?,?,?,?)');$stmt->bind_param('isssss',$cid,$price,$location,$until,$date,$status);
   }
   if(!$stmt->execute())throw new RuntimeException('Save failed.');$stmt->close();$message='Work saved.';
  }elseif($action==='decide'){
   $workId=filter_var($_POST['work_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
   $mode=(string)($_POST['mode']??'');
   if(!$workId||!in_array($mode,['manual','random'],true))throw new RuntimeException('Invalid decision.');
   $stmt=$db->prepare('SELECT ID,CRTSHT_ID,SaleStatus,OfferDeadline FROM CRTSHT_Selected_Works WHERE ID=? FOR UPDATE');$stmt->bind_param('i',$workId);$stmt->execute();$work=$stmt->get_result()->fetch_assoc();$stmt->close();
   if(!$work||!in_array($work['SaleStatus'],['open','closed'],true))throw new RuntimeException('Work is not open for allocation.');
   $stmt=$db->prepare('SELECT OfferDeadline<=NOW() AS past FROM CRTSHT_Selected_Works WHERE ID=?');
   $stmt->bind_param('i',$workId);$stmt->execute();$past=(int)$stmt->get_result()->fetch_assoc()['past'];$stmt->close();
   if(!$past)throw new RuntimeException('Winner selection is available after the deadline.');
   $stmt=$db->prepare("SELECT ID FROM CRTSHT_Selected_Offers WHERE SelectedWorkID=? AND Status='pending' ORDER BY ID FOR UPDATE");$stmt->bind_param('i',$workId);$stmt->execute();$ids=array_map('intval',array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC),'ID'));$stmt->close();
   if(!$ids)throw new RuntimeException('No pending offers.');
   $winner=$mode==='random'?$ids[random_int(0,count($ids)-1)]:(int)($_POST['offer_id']??0);
   if(!in_array($winner,$ids,true))throw new RuntimeException('Selected offer is not eligible.');
   $stmt=$db->prepare("UPDATE CRTSHT_Selected_Offers SET Status=CASE WHEN ID=? THEN 'accepted' ELSE 'rejected' END,DecisionMethod=CASE WHEN ID=? THEN ? ELSE NULL END,DecidedAt=NOW() WHERE SelectedWorkID=? AND Status='pending'");
   $stmt->bind_param('iisi',$winner,$winner,$mode,$workId);if(!$stmt->execute())throw new RuntimeException('Offer selection failed.');$stmt->close();
   $reservationCode='W-'.strtoupper(bin2hex(random_bytes(8)));
   $stmt=$db->prepare("UPDATE CRTSHT_Selected_Offers SET ReservationCode=? WHERE ID=? AND SelectedWorkID=? AND Status='accepted'");
   if(!$stmt)throw new RuntimeException('Reservation reference creation failed.');
   $stmt->bind_param('sii',$reservationCode,$winner,$workId);
   if(!$stmt->execute()||$stmt->affected_rows!==1)throw new RuntimeException('Reservation reference creation failed.');$stmt->close();
   $stmt=$db->prepare("UPDATE CRTSHT_Selected_Works SET WinnerOfferID=?,SaleStatus='reserved',ReservationExpiresAt=DATE_ADD(NOW(),INTERVAL 48 HOUR) WHERE ID=?");$stmt->bind_param('ii',$winner,$workId);$stmt->execute();$stmt->close();
   $actor='admin';$actionLog='winner_selected';$detail=json_encode(['mode'=>$mode,'winner_offer_id'=>$winner],JSON_THROW_ON_ERROR);
   $stmt=$db->prepare('INSERT INTO CRTSHT_Selected_Audit (SelectedWorkID,OfferID,Actor,Action,DetailJSON) VALUES (?,?,?,?,?)');$stmt->bind_param('iisss',$workId,$winner,$actor,$actionLog,$detail);$stmt->execute();$stmt->close();
   $message='Winner selected; reference '.$reservationCode.'. Send the invitation below.';
  }elseif($action==='extend_payment'||$action==='release_reservation'){
   $wid=filter_var($_POST['work_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
   if(!$wid)throw new RuntimeException('Invalid work.');
   $stmt=$db->prepare('SELECT * FROM CRTSHT_Selected_Works WHERE ID=? FOR UPDATE');
   if(!$stmt)throw new RuntimeException('Work lookup failed.');
   $stmt->bind_param('i',$wid);$stmt->execute();$work=$stmt->get_result()->fetch_assoc();$stmt->close();
   if(!$work||$work['SaleStatus']!=='reserved'||!(int)$work['WinnerOfferID'])throw new RuntimeException('No active reservation.');
   $winner=(int)$work['WinnerOfferID'];
   $stmt=$db->prepare('SELECT * FROM CRTSHT_Selected_Offers WHERE ID=? AND SelectedWorkID=? FOR UPDATE');
   $stmt->bind_param('ii',$winner,$wid);$stmt->execute();$offer=$stmt->get_result()->fetch_assoc();$stmt->close();
   if(!$offer||$offer['Status']!=='accepted'||$offer['PaidAt']!==null)throw new RuntimeException('Reservation is already settled.');
   if($action==='extend_payment'){
    $stmt=$db->prepare("UPDATE CRTSHT_Selected_Works SET ReservationExpiresAt=DATE_ADD(GREATEST(NOW(),ReservationExpiresAt),INTERVAL 48 HOUR) WHERE ID=? AND SaleStatus='reserved'");
    $stmt->bind_param('i',$wid);if(!$stmt->execute()||$stmt->affected_rows!==1)throw new RuntimeException('Could not extend reservation.');$stmt->close();
    $message='Payment window extended by 48 hours. Resend the payment invitation if needed.';
   }else{
    if(($_POST['confirm_release']??'')!=='NO PAYMENT RECEIVED')throw new RuntimeException('Confirm that no bank or Stripe payment was received.');
    $sid=(string)($offer['StripeCheckoutSessionID']??'');
    if($sid!==''){
     if(!crt_stripe_ready()||!preg_match('/^cs_[A-Za-z0-9_]+$/',$sid))throw new RuntimeException('Stripe verification unavailable.');
     $remote=crt_stripe_request('GET','/v1/checkout/sessions/'.rawurlencode($sid));
     $state=(string)($remote['status']??'');
     if($state==='open'){$remote=crt_stripe_request('POST','/v1/checkout/sessions/'.rawurlencode($sid).'/expire');$state=(string)($remote['status']??'');}
     if($state!=='expired')throw new RuntimeException('Stripe checkout is not expired. Wait for webhook or reconcile manually.');
    }
    $stmt=$db->prepare("UPDATE CRTSHT_Selected_Offers SET Status='expired' WHERE ID=? AND Status='accepted' AND PaidAt IS NULL");
    $stmt->bind_param('i',$winner);if(!$stmt->execute()||$stmt->affected_rows!==1)throw new RuntimeException('Could not release winning offer.');$stmt->close();
    $stmt=$db->prepare("UPDATE CRTSHT_Selected_Offers SET Status='pending' WHERE SelectedWorkID=? AND Status='rejected'");
    $stmt->bind_param('i',$wid);if(!$stmt->execute())throw new RuntimeException('Could not restore other offers.');$stmt->close();
    $stmt=$db->prepare("UPDATE CRTSHT_Selected_Works SET WinnerOfferID=NULL,SaleStatus='closed',ReservationExpiresAt=NULL WHERE ID=? AND WinnerOfferID=? AND SaleStatus='reserved'");
    $stmt->bind_param('ii',$wid,$winner);if(!$stmt->execute()||$stmt->affected_rows!==1)throw new RuntimeException('Could not reopen selection.');$stmt->close();
    $message='Old reservation released. Other offers are eligible for a new selection.';
   }
   $actor='admin';$auditAction=$action;$detail=json_encode(['offer_id'=>$winner],JSON_THROW_ON_ERROR);
   $stmt=$db->prepare('INSERT INTO CRTSHT_Selected_Audit (SelectedWorkID,OfferID,Actor,Action,DetailJSON) VALUES (?,?,?,?,?)');
   if(!$stmt)throw new RuntimeException('Audit unavailable.');
   $stmt->bind_param('iisss',$wid,$winner,$actor,$auditAction,$detail);
   if(!$stmt->execute())throw new RuntimeException('Audit failed.');$stmt->close();
  }elseif($action==='send_invite'||$action==='confirm_bank'){
   $wid=filter_var($_POST['work_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
   if(!$wid)throw new RuntimeException('Invalid work.');
   $stmt=$db->prepare('SELECT * FROM CRTSHT_Selected_Works WHERE ID=? FOR UPDATE');
   if(!$stmt)throw new RuntimeException('Work lookup failed.');
   $stmt->bind_param('i',$wid);$stmt->execute();$work=$stmt->get_result()->fetch_assoc();$stmt->close();
   if(!$work||$work['SaleStatus']!=='reserved'||!(int)$work['WinnerOfferID'])throw new RuntimeException('No active winning reservation.');
   $winner=(int)$work['WinnerOfferID'];
   $stmt=$db->prepare("SELECT o.*, w.CRTSHT_ID, w.ReservationExpiresAt FROM CRTSHT_Selected_Offers o JOIN CRTSHT_Selected_Works w ON w.ID=o.SelectedWorkID WHERE o.ID=? AND o.SelectedWorkID=? FOR UPDATE");
   if(!$stmt)throw new RuntimeException('Winner lookup failed.');
   $stmt->bind_param('ii',$winner,$wid);$stmt->execute();$offer=$stmt->get_result()->fetch_assoc();$stmt->close();
   if(!$offer||$offer['Status']!=='accepted'||!preg_match('/^W-[A-F0-9]{16}$/',(string)$offer['ReservationCode']))throw new RuntimeException('No payable winning offer.');
   if($action==='send_invite'){
    $stmt=$db->prepare('SELECT (ReservationExpiresAt>NOW()) AS active FROM CRTSHT_Selected_Works WHERE ID=?');
    $stmt->bind_param('i',$wid);$stmt->execute();$active=(int)$stmt->get_result()->fetch_assoc()['active'];$stmt->close();
    if(!$active)throw new RuntimeException('Payment window expired. Extend it before mailing.');
    $afterCommitMail=['type'=>'invite','offer'=>$offer,'wid'=>$wid,'winner'=>$winner];
    $message='Payment invitation prepared.';
   }else{
    // Before marking a bank transfer, expire any still-open online checkout to prevent double payment.
    $sid=(string)($offer['StripeCheckoutSessionID']??'');
    if($sid!==''){
     if(!crt_stripe_ready())throw new RuntimeException('Stripe must be available to verify outstanding online checkout.');
     if(!preg_match('/^cs_[A-Za-z0-9_]+$/',$sid))throw new RuntimeException('Invalid Stripe session.');
     $remote=crt_stripe_request('GET','/v1/checkout/sessions/'.rawurlencode($sid));
     $status=(string)($remote['status']??'');
     if($status==='open'){$remote=crt_stripe_request('POST','/v1/checkout/sessions/'.rawurlencode($sid).'/expire');$status=(string)($remote['status']??'');}
     if($status!=='expired')throw new RuntimeException('Stripe checkout not expired. Resolve online payment before recording bank receipt.');
    }
    $stmt=$db->prepare("UPDATE CRTSHT_Selected_Offers SET PaidAt=NOW(),StripePaymentStatus='bank_paid' WHERE ID=? AND PaidAt IS NULL AND Status='accepted'");
    if(!$stmt)throw new RuntimeException('Bank payment update failed.');
    $stmt->bind_param('i',$winner);if(!$stmt->execute()||$stmt->affected_rows!==1)throw new RuntimeException('Already paid or state changed.');$stmt->close();
    $stmt=$db->prepare("UPDATE CRTSHT_Selected_Works SET SaleStatus='sold' WHERE ID=? AND WinnerOfferID=? AND SaleStatus='reserved'");
    if(!$stmt)throw new RuntimeException('Work sale update failed.');
    $stmt->bind_param('ii',$wid,$winner);if(!$stmt->execute()||$stmt->affected_rows!==1)throw new RuntimeException('Unable to mark work sold.');$stmt->close();
    $actor='admin';$actionLog='bank_paid';$detail=json_encode(['reference'=>$offer['ReservationCode']],JSON_THROW_ON_ERROR);
    $stmt=$db->prepare('INSERT INTO CRTSHT_Selected_Audit (SelectedWorkID,OfferID,Actor,Action,DetailJSON) VALUES (?,?,?,?,?)');
    $stmt->bind_param('iisss',$wid,$winner,$actor,$actionLog,$detail);if(!$stmt->execute())throw new RuntimeException('Audit failed.');$stmt->close();
    $afterCommitMail=['type'=>'paid','offer'=>$offer,'wid'=>$wid,'winner'=>$winner];
    $message='Bank payment recorded; work marked sold.';
   }
  }else throw new RuntimeException('Unknown action.');
  $db->commit();
  if($afterCommitMail){
   $mail=$afterCommitMail['type']==='invite'?crt_selected_mail_offer_accepted($afterCommitMail['offer']):crt_selected_mail_paid($afterCommitMail['offer']);
   $ok=(bool)($mail['ok']??false);
   $message.= $ok?' Customer email sent.':' Customer email FAILED; please retry or contact customer manually.';
   if(!$ok)error_log('CRTSHT selected admin mail failed: '.($mail['error']??'unknown'));
   $actor='admin';$logAction=$afterCommitMail['type']==='invite'?'invitation_sent':'bank_paid_email';
   $json=json_encode(['mail_ok'=>$ok],JSON_THROW_ON_ERROR);
   $stmt=$db->prepare('INSERT INTO CRTSHT_Selected_Audit (SelectedWorkID,OfferID,Actor,Action,DetailJSON) VALUES (?,?,?,?,?)');
   if($stmt){$stmt->bind_param('iisss',$afterCommitMail['wid'],$afterCommitMail['winner'],$actor,$logAction,$json);$stmt->execute();$stmt->close();}
  }
 }catch(Throwable $e){$db->rollback();$error=$e->getMessage();}
 if($artworkLock!==null)crt_selected_unlock_artwork($db,(int)$artworkLock);
}
$works=[];$res=$db->query('SELECT w.*, (SELECT COUNT(*) FROM CRTSHT_Selected_Offers o WHERE o.SelectedWorkID=w.ID) OfferCount FROM CRTSHT_Selected_Works w ORDER BY w.ID DESC');if($res){$works=$res->fetch_all(MYSQLI_ASSOC);$res->free();}
$offers=[];$res=$db->query('SELECT o.ID,o.SelectedWorkID,o.OfferCode,o.ReservationCode,o.Name,o.Email,o.OfferedPriceCHF,o.Status,o.CreatedAt FROM CRTSHT_Selected_Offers o ORDER BY o.CreatedAt DESC');if($res){foreach($res->fetch_all(MYSQLI_ASSOC) as $o)$offers[(int)$o['SelectedWorkID']][]=$o;$res->free();}
$db->close();
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>CRTSHT / SELECTED WORKS CONTROL</title><style>body{font:14px ui-monospace,monospace;background:#111;color:#eee;max-width:1000px;margin:auto;padding:25px}a{color:inherit}section{border-top:1px solid #888;padding:18px 0}input,select,button{padding:9px;margin:5px;background:#222;color:#fff;border:1px solid #777}button{cursor:pointer}label{display:inline-block;margin:5px}small{color:#aaa}.msg{padding:12px;border:1px solid}table{width:100%;border-collapse:collapse}td,th{text-align:left;border-bottom:1px solid #444;padding:8px}</style></head><body><p><a href="/crtshtdrwmng/">← DRAW CONTROL</a></p><h1>SELECTED WORKS / SECONDARY MARKET CONTROL</h1>
<p>Works already allocated in the historical DRAW can be listed and resold here. Keep their original DRAW assignment intact. Before opening a resale listing, verify ownership or consignment authority and availability of the physical work.</p>
<?php if($error):?><p class="msg"><?=h($error)?></p><?php endif;?><?php if($message):?><p class="msg"><?=h($message)?></p><?php endif;?>
<section><h2>ADD / UPDATE WORK</h2><form method="post"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="save_work">
<label>CRTSHT ID <input name="crtsht_id" type="number" min="1" max="128" required></label>
<label>Fixed price CHF <input name="price" type="number" min=".01" step=".01" required></label>
<label>Offer deadline (Zurich) <input name="deadline" type="datetime-local" required></label>
<label>Exhibition until <input name="exhibition" type="datetime-local"></label>
<label>Location <input name="location" maxlength="255"></label>
<label>Status <select name="status"><option>draft</option><option>open</option><option>closed</option><option>withdrawn</option></select></label>
<button>Save work</button></form><small>Historical DRAW allocations remain eligible for resale. Existing works can be updated by entering their CRTSHT ID. Existing offers retain their originally submitted price. For a new resale after a completed sale, a separate sale-cycle workflow will be needed.</small></section>
<?php foreach($works as $w):$wid=(int)$w['ID'];?><section><h2>CRTSHT #<?=(int)$w['CRTSHT_ID']?> · <?=h($w['SaleStatus'])?> · CHF <?=h((string)$w['PriceCHF'])?></h2>
<p>Deadline: <?=h((string)$w['OfferDeadline'])?> · <?= (int)$w['OfferCount']?> offer(s) · Winner offer: <?=h((string)($w['WinnerOfferID']??'—'))?></p>
<?php if($w['SaleStatus']==='reserved'&&$w['WinnerOfferID']):?>
<p><strong>PAYMENT RESERVED</strong> until <?=h((string)$w['ReservationExpiresAt'])?> (Zurich). Send an offer-acceptance email with the payment link; bank receipts can be confirmed below.</p>
<form method="post" style="display:inline-block"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="send_invite"><input type="hidden" name="work_id" value="<?=$wid?>"><button type="submit" onclick="return confirm('Send payment invitation to the selected buyer?')">EMAIL PAYMENT LINK</button></form>
<form method="post" style="display:inline-block"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="confirm_bank"><input type="hidden" name="work_id" value="<?=$wid?>"><button type="submit" onclick="return confirm('Confirm bank transfer has arrived? This marks the work SOLD.')">CONFIRM BANK PAID</button></form>
<form method="post" style="display:inline-block"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="extend_payment"><input type="hidden" name="work_id" value="<?=$wid?>"><button type="submit">EXTEND +48H</button></form>
<form method="post" style="display:inline-block"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="release_reservation"><input type="hidden" name="work_id" value="<?=$wid?>"><input type="hidden" name="confirm_release" value="NO PAYMENT RECEIVED"><button type="submit" onclick="return confirm('Release allocation ONLY if no payment has arrived or is pending? The old buyer payment link will stop working.')">RELEASE / RESELECT</button></form>
<?php endif;?>
<?php if(!empty($offers[$wid])):?><table><tr><th>Offer</th><th>Buyer</th><th>Price</th><th>Status</th></tr>
<?php foreach($offers[$wid] as $o):?><tr><td><?=h($o['OfferCode'])?> (#<?=(int)$o['ID']?>)<?php if($o['ReservationCode']):?><br><small><a target="_blank" href="/selected-payment/<?=rawurlencode((string)$o['ReservationCode'])?>"><?=h((string)$o['ReservationCode'])?></a></small><?php endif;?></td><td><?=h($o['Name'])?> · <?=h($o['Email'])?></td><td>CHF <?=h((string)$o['OfferedPriceCHF'])?></td><td><?=h($o['Status'])?></td></tr><?php endforeach;?></table><?php endif;?>
<?php if(in_array($w['SaleStatus'],['open','closed'],true)):?>
<form method="post"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="decide"><input type="hidden" name="work_id" value="<?=$wid?>">
<label>Selection <select name="mode"><option value="manual">Manual</option><option value="random">Random</option></select></label>
<label>Offer ID (manual only) <input name="offer_id" type="number" min="1"></label><button onclick="return confirm('Allocate this work? This decision is recorded and all other pending offers are rejected.')">Confirm winner</button></form>
<?php endif;?></section><?php endforeach;?></body></html>
