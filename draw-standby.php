<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function se(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function sclean(string $v,int $max): string { $v=trim(preg_replace('/\s+/u',' ',$v)??$v); return mb_substr($v,0,$max,'UTF-8'); }
function sbatch(): array {
    $now=new DateTimeImmutable('now',new DateTimeZone('Europe/Zurich'));
    if($now<new DateTimeImmutable('2026-09-25 18:00:00',new DateTimeZone('Europe/Zurich')))return ['01','25.09.2026'];
    if($now<new DateTimeImmutable('2026-10-17 18:00:00',new DateTimeZone('Europe/Zurich')))return ['02','17.10.2026'];
    return ['03','31.10.2026'];
}

if($_SERVER['REQUEST_METHOD']!=='POST'){header('Location: /draw',true,303);exit;}
$csrf=(string)($_SESSION['draw_csrf']??'');
if($csrf===''||!hash_equals($csrf,(string)($_POST['csrf']??''))||trim((string)($_POST['company']??''))!==''){http_response_code(400);exit('Invalid request.');}
$q=filter_var($_POST['quantity']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>3]]);
$name=sclean((string)($_POST['name']??''),120);$email=strtolower(sclean((string)($_POST['email']??''),190));$mobile=sclean((string)($_POST['mobile']??''),50);$address=sclean((string)($_POST['address']??''),190);$plz=sclean((string)($_POST['plz']??''),24);$city=sclean((string)($_POST['city']??''),120);$country=sclean((string)($_POST['country']??''),120);
if($q===false||$name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||$mobile===''||$address===''||$plz===''||$city===''||$country===''){http_response_code(422);exit('Please complete the form correctly.');}
$db=crt_db();if(!$db){http_response_code(503);exit('Database unavailable.');}
$active=0;$r=$db->query("SELECT COUNT(*) total FROM CRTSHT_Draw_Entries e JOIN CRTSHT_Draw_Reservations r ON r.ID=e.ReservationID WHERE r.Status IN ('reserved','paid')");if($r){$active=(int)($r->fetch_assoc()['total']??0);$r->free();}
if($active<CRTSHT_TOTAL){$db->close();header('Location: /draw',true,303);exit;}
[$batch,$drawDate]=sbatch();$code='R-'.strtoupper(bin2hex(random_bytes(5)));
$total=0.0;$key='price_'.$q.'_chf';$stmt=$db->prepare('SELECT SettingValue FROM CRTSHT_Draw_Settings WHERE SettingKey=? LIMIT 1');if($stmt){$stmt->bind_param('s',$key);$stmt->execute();$res=$stmt->get_result();$row=$res?$res->fetch_assoc():null;if($row)$total=(float)$row['SettingValue'];$stmt->close();}
$status='standby';$stmt=$db->prepare('INSERT INTO CRTSHT_Draw_Standby (ReservationCode,DrawBatch,Quantity,TotalPrice,Name,Email,Mobile,Address,PLZ,City,Country,Status,CreatedAt) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW())');if(!$stmt){$db->close();http_response_code(500);exit('Standby unavailable.');}$stmt->bind_param('ssidssssssss',$code,$batch,$q,$total,$name,$email,$mobile,$address,$plz,$city,$country,$status);if(!$stmt->execute()){$stmt->close();$db->close();http_response_code(500);exit('Standby unavailable.');}$stmt->close();$db->close();$_SESSION['draw_csrf']=bin2hex(random_bytes(24));
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Standby / CRTSHT</title><link rel="stylesheet" href="/site.css?v=8"><style>.standby{max-width:900px}.box{border:1px solid var(--fg);margin-top:30px}.bar{padding:8px 10px;border-bottom:1px solid var(--fg);font-size:10px;text-transform:uppercase;display:flex;justify-content:space-between}.body{padding:20px}.body h1{font-size:clamp(42px,8vw,90px);line-height:.86;letter-spacing:-.07em;margin:.1em 0 .35em}.row{display:grid;grid-template-columns:130px 1fr;gap:16px;padding:9px 0;border-bottom:1px solid var(--line);font-size:11px}.note{font-size:11px;line-height:1.55;margin-top:16px;color:var(--muted)}</style></head><body><main class="wrap standby"><header><a class="brand" href="/">CR¥P70$H!7.STANDBY</a><nav class="nav"><a href="/draw">Draw</a></nav></header><section class="box"><div class="bar"><span>CRTSHT / STANDBY TERMINAL</span><span>NO SLOT HELD</span></div><div class="body"><div class="eyebrow">128 / 128 CURRENTLY HELD</div><h1>YOU'RE NEXT<br>IF SHIT HAPPENS.</h1><div class="row"><span>RESERVATION</span><strong><?=se($code)?></strong></div><div class="row"><span>REQUEST</span><span><?=$q?>× CRTSHT</span></div><div class="row"><span>PRICE</span><span>CHF <?=se(number_format($total,2,'.',"'"))?></span></div><div class="row"><span>STATUS</span><strong>STANDBY / NO SLOT HELD</strong></div><div class="row"><span>NEXT DRAW</span><span><?=se($drawDate)?></span></div><p class="note">Your request is timestamped in chronological standby. If a reserved ticket is released, this request can be promoted to a real draw reservation. The CHF package price shown here is fixed for this standby request; no payment should be made until the slot is confirmed.</p></div></section></main></body></html>