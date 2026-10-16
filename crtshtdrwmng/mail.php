<?php
declare(strict_types=1);
require dirname(__DIR__) . '/inc/bootstrap.php';
require_once dirname(__DIR__) . '/inc/mailer.php';
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Cache-Control: no-store, private');
session_name('crtsht_draw_admin');
session_set_cookie_params(['lifetime'=>0,'path'=>'/crtshtdrwmng','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off','httponly'=>true,'samesite'=>'Strict']);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['draw_admin_ok'])) { header('Location: /crtshtdrwmng/', true, 303); exit; }
function mc_e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
$csrf = (string)$_SESSION['csrf'];
$db = crt_db();
if (!$db) { http_response_code(503); exit('Database unavailable'); }
$filter = (string)($_POST['audience'] ?? 'active');
$allowed = ['active','paid','02','03'];
if (!in_array($filter,$allowed,true)) $filter='active';
$subject = trim((string)($_POST['subject'] ?? 'CRTSHT / NEXT DRAW'));
$body = trim((string)($_POST['body'] ?? "Hi {name},\n\nThe next CRTSHT Draw is on Saturday, 17 October at 19:00.\n\nENDSAFTER, Hardturmstrasse 307, 8005 Zürich.\n\nhttps://cryptoshit.info/draw\n\nSee you there (or let an innocent hand do the drawing).\n\nCRTSHT"));
$action=(string)($_POST['action'] ?? '');
$error=''; $notice=''; $recipients=[];
if ($filter==='paid') $where="Status='paid'";
elseif ($filter==='02' || $filter==='03') $where="Status IN ('reserved','paid') AND DrawBatch='".$filter."'";
else $where="Status IN ('reserved','paid')";
$result=$db->query("SELECT ID,Name,Email,Status,DrawBatch,ReservationCode FROM CRTSHT_Draw_Reservations WHERE ".$where." ORDER BY ID");
if (!$result) { http_response_code(500); exit('Recipient lookup failed'); }
$seen=[];
while ($row=$result->fetch_assoc()) {
    $email=strtolower(trim((string)$row['Email']));
    if (!filter_var($email,FILTER_VALIDATE_EMAIL) || isset($seen[$email])) continue;
    $seen[$email]=true; $recipients[]=$row;
}
$result->free();
$rid=filter_var($_POST['reservation_id'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if ($action==='payment' || $action==='send') {
    if (!hash_equals($csrf,(string)($_POST['csrf'] ?? ''))) $error='Security token expired. Reload and retry.';
    elseif ((string)($_POST['confirm'] ?? '')!=='SEND') $error='Type SEND to confirm.';
    elseif ($action==='payment') {
        if (!$rid) $error='Choose a reservation.';
        else {
            $stmt=$db->prepare('SELECT * FROM CRTSHT_Draw_Reservations WHERE ID=? LIMIT 1');
            if (!$stmt) $error='Reservation lookup failed.';
            else {
                $stmt->bind_param('i',$rid); $stmt->execute(); $r=$stmt->get_result()->fetch_assoc(); $stmt->close();
                if (!$r || $r['Status']!=='reserved' || (float)$r['TotalPrice']<=0) $error='Payment details can only be resent for an unpaid active reservation.';
                else {
                    $sent=crt_mail_payment_details_customer($r);
                    if (!empty($sent['ok'])) $notice='Bank-transfer details sent to '.(string)$r['Email'].'.';
                    else $error='Mail failed. Please check server logs.';
                }
            }
        }
    } else {
        if ($subject==='' || mb_strlen($subject)>140 || preg_match('/[\r\n]/',$subject)) $error='Enter a valid subject (max 140 characters).';
        elseif ($body==='' || mb_strlen($body)>6000) $error='Enter a message (max 6000 characters).';
        elseif (count($recipients)===0) $error='No recipients.';
        elseif (count($recipients)>20) $error='For safety, this tool sends to at most 20 distinct addresses per operation. Narrow the audience; larger campaigns need a queued sender with delivery logs.';
        else {
            $ok=0; $failed=0;
            foreach ($recipients as $r) {
                $personal=str_replace('{name}',(string)$r['Name'],$body);
                $result=crt_mail_send((string)$r['Email'],$subject,$personal);
                if (!empty($result['ok'])) $ok++;
                else { $failed++; error_log('CRTSHT admin mail failure reservation '.(int)$r['ID'].': '.(string)($result['error'] ?? 'unknown')); }
            }
            $notice="Mail attempt completed: {$ok} accepted by SMTP, {$failed} failed. SMTP acceptance does not guarantee delivery.";
        }
    }
}
$db->close();
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>CRTSHT / MAIL CONTROL</title>
<style>*{box-sizing:border-box}body{background:#f2f2ee;color:#111;font:14px/1.5 ui-monospace,SFMono-Regular,Menlo,monospace;max-width:960px;margin:30px auto;padding:0 18px}a{color:inherit}h1{font-size:clamp(30px,6vw,60px);letter-spacing:-.06em}h2{font-size:22px}section{border:1px solid #111;padding:clamp(16px,3vw,28px);margin:20px 0}label{display:block;font-size:12px;margin:14px 0 6px;font-weight:bold}input,textarea,select{display:block;width:100%;font:16px/1.5 inherit;font-size:16px;padding:12px;background:#fff;border:1px solid #888;border-radius:0}textarea{min-height:240px;resize:vertical}button{padding:14px 18px;background:#111;color:#fff;border:0;font:inherit;cursor:pointer;margin-top:14px}.muted{color:#555;font-size:12px}.error{padding:14px;background:#111;color:#fff}.notice{padding:14px;border:1px solid #111}.recipients{max-height:180px;overflow:auto;border-top:1px solid #bbb;margin-top:12px;padding-top:8px}.recipients div{padding:3px 0}.confirm{max-width:160px}.links{display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap}</style></head><body>
<div class="links"><a href="/crtshtdrwmng/">← DRAW CONTROL</a><span>PRIVATE / NO AUTOMATIC SENDING</span></div><h1>MAIL CONTROL.</h1>
<p>Operational messages to registered participants only. Do not use this tool for unsolicited promotional campaigns. One message per unique email address. Always preview the audience and text before sending.</p>
<?php if($error):?><p class="error" role="alert"><?=mc_e($error)?></p><?php endif;?>
<?php if($notice):?><p class="notice" role="status"><?=mc_e($notice)?></p><?php endif;?>
<section><h2>01 / PARTICIPANT UPDATE</h2><form method="post"><input type="hidden" name="csrf" value="<?=mc_e($csrf)?>"><input type="hidden" name="action" value="send">
<label for="audience">Audience</label><select id="audience" name="audience" onchange="this.form.querySelector('[name=confirm]').value='';this.form.querySelector('[name=action]').value='preview';this.form.submit()">
<?php foreach(['active'=>'All active reservations','paid'=>'Paid reservations','02'=>'Draw 02 participants','03'=>'Draw 03 participants'] as $v=>$name):?><option value="<?=$v?>" <?=$filter===$v?'selected':''?>><?=mc_e($name)?></option><?php endforeach;?></select>
<label for="subject">Subject</label><input id="subject" name="subject" maxlength="140" value="<?=mc_e($subject)?>" required>
<label for="body">Message (plain text, {name} personalizes the greeting)</label><textarea id="body" name="body" maxlength="6000" required><?=mc_e($body)?></textarea>
<p><strong><?=count($recipients)?> distinct recipients</strong> · current audience</p>
<div class="recipients"><?php foreach($recipients as $r):?><div><?=mc_e((string)$r['Name'])?> — <?=mc_e((string)$r['Email'])?> · Draw <?=mc_e((string)$r['DrawBatch'])?></div><?php endforeach;?></div>
<p class="muted">Check the recipient list. Messages are sent individually (no exposed recipient list). For more than 20 recipients, use a dedicated queued sender with per-recipient logs rather than a long-running browser request.</p>
<label for="confirm">Type SEND to confirm</label><input class="confirm" id="confirm" name="confirm" autocomplete="off" placeholder="SEND" required>
<button type="submit" onclick="return confirm('Send this message to the displayed audience?')">SEND PARTICIPANT UPDATE</button></form></section>
<section><h2>02 / BANK TRANSFER REMINDER</h2><p>Resends the existing payment-details email to a single unpaid reservation. Amount, IBAN and reference come from the existing configuration and reservation.</p>
<form method="post"><input type="hidden" name="csrf" value="<?=mc_e($csrf)?>"><input type="hidden" name="action" value="payment">
<label for="reservation_id">Reservation database ID</label><input id="reservation_id" name="reservation_id" type="number" min="1" value="<?=$rid?:''?>" required>
<label for="payment_confirm">Type SEND to confirm</label><input class="confirm" id="payment_confirm" name="confirm" placeholder="SEND" autocomplete="off" required>
<button type="submit" onclick="return confirm('Resend payment details to this reservation?')">RESEND BANK DETAILS</button></form></section></body></html>