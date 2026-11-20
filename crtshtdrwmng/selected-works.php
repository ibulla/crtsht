<?php
declare(strict_types=1);
require dirname(__DIR__) . '/inc/bootstrap.php';
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Cache-Control: no-store, private');
session_name('crtsht_draw_admin');
session_set_cookie_params(['path'=>'/crtshtdrwmng','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Strict']);
if(session_status()!==PHP_SESSION_ACTIVE)session_start();
if(empty($_SESSION['draw_admin_ok'])){header('Location: /crtshtdrwmng/',true,303);exit;}
function h(string $s):string{return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
$db=crt_db();if(!$db){http_response_code(503);exit('Database unavailable.');}
$csrf=$_SESSION['csrf']??'';if(!is_string($csrf)||strlen($csrf)<32)$_SESSION['csrf']=$csrf=bin2hex(random_bytes(24));
$error='';$message='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  if(!hash_equals($csrf,(string)($_POST['csrf']??'')))throw new RuntimeException('Invalid CSRF token.');
  $action=(string)($_POST['action']??'');
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
   $stmt=$db->prepare('SELECT COUNT(*) n FROM CRTSHT_Draw_Entries WHERE AssignedCRTSHT=?');$stmt->bind_param('i',$cid);$stmt->execute();$assigned=(int)$stmt->get_result()->fetch_assoc()['n'];$stmt->close();
   if($assigned && $status==='open')throw new RuntimeException('Already allocated in DRAW.');
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
   if(!$work||!in_array($work['SaleStatus'],['open','closed'],true)||strtotime($work['OfferDeadline'])>time())throw new RuntimeException('Work must be past its deadline and unallocated.');
   $cid=(int)$work['CRTSHT_ID'];$stmt=$db->prepare('SELECT COUNT(*) n FROM CRTSHT_Draw_Entries WHERE AssignedCRTSHT=?');$stmt->bind_param('i',$cid);$stmt->execute();$assigned=(int)$stmt->get_result()->fetch_assoc()['n'];$stmt->close();
   if($assigned)throw new RuntimeException('Work allocated in DRAW.');
   $stmt=$db->prepare("SELECT ID FROM CRTSHT_Selected_Offers WHERE SelectedWorkID=? AND Status='pending' ORDER BY ID FOR UPDATE");$stmt->bind_param('i',$workId);$stmt->execute();$ids=array_map('intval',array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC),'ID'));$stmt->close();
   if(!$ids)throw new RuntimeException('No pending offers.');
   $winner=$mode==='random'?$ids[random_int(0,count($ids)-1)]:(int)($_POST['offer_id']??0);
   if(!in_array($winner,$ids,true))throw new RuntimeException('Selected offer is not eligible.');
   $stmt=$db->prepare("UPDATE CRTSHT_Selected_Offers SET Status=CASE WHEN ID=? THEN 'accepted' ELSE 'rejected' END,DecisionMethod=CASE WHEN ID=? THEN ? ELSE NULL END,DecidedAt=NOW() WHERE SelectedWorkID=? AND Status='pending'");
   $stmt->bind_param('iisi',$winner,$winner,$mode,$workId);$stmt->execute();$stmt->close();
   $stmt=$db->prepare("UPDATE CRTSHT_Selected_Works SET WinnerOfferID=?,SaleStatus='reserved',ReservationExpiresAt=DATE_ADD(NOW(),INTERVAL 48 HOUR) WHERE ID=?");$stmt->bind_param('ii',$winner,$workId);$stmt->execute();$stmt->close();
   $actor='admin';$actionLog='winner_selected';$detail=json_encode(['mode'=>$mode,'winner_offer_id'=>$winner],JSON_THROW_ON_ERROR);
   $stmt=$db->prepare('INSERT INTO CRTSHT_Selected_Audit (SelectedWorkID,OfferID,Actor,Action,DetailJSON) VALUES (?,?,?,?,?)');$stmt->bind_param('iisss',$workId,$winner,$actor,$actionLog,$detail);$stmt->execute();$stmt->close();
   $message='Winner selected. Payment invitation is NOT sent yet.';
  }else throw new RuntimeException('Unknown action.');
  $db->commit();
 }catch(Throwable $e){$db->rollback();$error=$e->getMessage();}
}
$works=[];$res=$db->query('SELECT w.*, (SELECT COUNT(*) FROM CRTSHT_Selected_Offers o WHERE o.SelectedWorkID=w.ID) OfferCount FROM CRTSHT_Selected_Works w ORDER BY w.ID DESC');if($res){$works=$res->fetch_all(MYSQLI_ASSOC);$res->free();}
$offers=[];$res=$db->query('SELECT o.ID,o.SelectedWorkID,o.OfferCode,o.Name,o.Email,o.OfferedPriceCHF,o.Status,o.CreatedAt FROM CRTSHT_Selected_Offers o ORDER BY o.CreatedAt DESC');if($res){foreach($res->fetch_all(MYSQLI_ASSOC) as $o)$offers[(int)$o['SelectedWorkID']][]=$o;$res->free();}
$db->close();
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>CRTSHT / SELECTED WORKS CONTROL</title><style>body{font:14px ui-monospace,monospace;background:#111;color:#eee;max-width:1000px;margin:auto;padding:25px}a{color:inherit}section{border-top:1px solid #888;padding:18px 0}input,select,button{padding:9px;margin:5px;background:#222;color:#fff;border:1px solid #777}button{cursor:pointer}label{display:inline-block;margin:5px}small{color:#aaa}.msg{padding:12px;border:1px solid}table{width:100%;border-collapse:collapse}td,th{text-align:left;border-bottom:1px solid #444;padding:8px}</style></head><body><p><a href="/crtshtdrwmng/">← DRAW CONTROL</a></p><h1>SELECTED WORKS / CONTROL</h1>
<?php if($error):?><p class="msg"><?=h($error)?></p><?php endif;?><?php if($message):?><p class="msg"><?=h($message)?></p><?php endif;?>
<section><h2>ADD / UPDATE WORK</h2><form method="post"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="save_work">
<label>CRTSHT ID <input name="crtsht_id" type="number" min="1" max="128" required></label>
<label>Fixed price CHF <input name="price" type="number" min=".01" step=".01" required></label>
<label>Offer deadline (Zurich) <input name="deadline" type="datetime-local" required></label>
<label>Exhibition until <input name="exhibition" type="datetime-local"></label>
<label>Location <input name="location" maxlength="255"></label>
<label>Status <select name="status"><option>draft</option><option>open</option><option>closed</option><option>withdrawn</option></select></label>
<button>Save work</button></form><small>Existing works can be updated by entering their CRTSHT ID. Existing offers retain their originally submitted price.</small></section>
<?php foreach($works as $w):$wid=(int)$w['ID'];?><section><h2>CRTSHT #<?=(int)$w['CRTSHT_ID']?> · <?=h($w['SaleStatus'])?> · CHF <?=h((string)$w['PriceCHF'])?></h2>
<p>Deadline: <?=h((string)$w['OfferDeadline'])?> · <?= (int)$w['OfferCount']?> offer(s) · Winner offer: <?=h((string)($w['WinnerOfferID']??'—'))?></p>
<?php if(!empty($offers[$wid])):?><table><tr><th>Offer</th><th>Buyer</th><th>Price</th><th>Status</th></tr>
<?php foreach($offers[$wid] as $o):?><tr><td><?=h($o['OfferCode'])?> (#<?=(int)$o['ID']?>)</td><td><?=h($o['Name'])?> · <?=h($o['Email'])?></td><td>CHF <?=h((string)$o['OfferedPriceCHF'])?></td><td><?=h($o['Status'])?></td></tr><?php endforeach;?></table><?php endif;?>
<?php if(in_array($w['SaleStatus'],['open','closed'],true)):?>
<form method="post"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="decide"><input type="hidden" name="work_id" value="<?=$wid?>">
<label>Selection <select name="mode"><option value="manual">Manual</option><option value="random">Random</option></select></label>
<label>Offer ID (manual only) <input name="offer_id" type="number" min="1"></label><button onclick="return confirm('Allocate this work? This decision is recorded and all other pending offers are rejected.')">Confirm winner</button></form>
<?php endif;?></section><?php endforeach;?></body></html>
