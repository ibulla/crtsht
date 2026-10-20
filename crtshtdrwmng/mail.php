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
$subject = trim((string)($_POST['subject'] ?? 'CRTSHT / FINAL DRAW · 31 OCTOBER'));
$body = trim((string)($_POST['body'] ?? "Hi {name},\n\nThe FINAL CRTSHT Draw is on Saturday, 31 October at 19:00.\n\nENDSAFTER, Hardturmstrasse 307, 8005 Zürich.\n\nhttps://cryptoshit.info/draw\n\nSee you there (or let an innocent hand do the drawing).\n\nCRTSHT"));
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

// One recipient per request. The admin session owns the immutable recipient snapshot.
// Mark as 'sending' before SMTP so a lost response cannot trigger an automatic duplicate.
if (in_array($action, ['queue_start','queue_next','queue_status','queue_cancel'], true)) {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    $reply = static function(array $data, int $code=200): never {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    };
    if (!hash_equals($csrf,(string)($_POST['csrf'] ?? ''))) $reply(['error'=>'Security token expired.'],403);
    if ($action==='queue_start') {
        if ((string)($_POST['confirm'] ?? '')!=='SEND') $reply(['error'=>'Type SEND to confirm.'],400);
        if ($subject==='' || mb_strlen($subject)>140 || preg_match('/[\\r\\n]/',$subject) || $body==='' || mb_strlen($body)>6000) $reply(['error'=>'Invalid subject or message.'],400);
        if (!$recipients || count($recipients)>500) $reply(['error'=>'Invalid audience size.'],400);
        if (!empty($_SESSION['mail_queue']) && !empty($_SESSION['mail_queue']['active'])) $reply(['error'=>'A campaign is already active. Finish or stop it first.'],409);
        $queue=[];
        foreach ($recipients as $r) $queue[]=['id'=>(int)$r['ID'],'email'=>(string)$r['Email'],'name'=>(string)$r['Name'],'status'=>'pending'];
        $_SESSION['mail_queue']=['id'=>bin2hex(random_bytes(12)),'active'=>true,'subject'=>$subject,'body'=>$body,'items'=>$queue,'created'=>time()];
    }
    if (empty($_SESSION['mail_queue'])) $reply(['error'=>'No campaign found. Start a new campaign.'],404);
    $q=&$_SESSION['mail_queue'];
    if ($action==='queue_cancel') $q['active']=false;
    if ($action==='queue_next') {
        if (empty($q['active'])) $reply(['error'=>'Campaign stopped.'],409);
        $idx=null;
        foreach ($q['items'] as $i=>$item) if ($item['status']==='pending') { $idx=$i; break; }
        if ($idx!==null) {
            $q['items'][$idx]['status']='sending';
            $item=$q['items'][$idx];
            $campaignId=$q['id'];
            $mailSubject=$q['subject'];
            $mailBody=str_replace('{name}',$item['name'],$q['body']);
            // Release session lock while SMTP is in progress. Other requests cannot
            // resend this item because it is already marked as 'sending'.
            session_write_close();
            $sent=crt_mail_send($item['email'],$mailSubject,$mailBody);
            session_start();
            if (isset($_SESSION['mail_queue']) && $_SESSION['mail_queue']['id']===$campaignId && isset($_SESSION['mail_queue']['items'][$idx])) {
                $_SESSION['mail_queue']['items'][$idx]['status']=!empty($sent['ok'])?'sent':'failed';
                if (empty($sent['ok'])) error_log('CRTSHT bulk mail failed reservation '.$item['id'].': '.(string)($sent['error'] ?? 'unknown'));
            }
            $q=&$_SESSION['mail_queue'];
        }
        if (!in_array('pending',array_column($q['items'],'status'),true)) $q['active']=false;
    }
    $counts=array_count_values(array_column($q['items'],'status'));
    $reply(['id'=>$q['id'],'active'=>(bool)$q['active'],'total'=>count($q['items']),
        'sent'=>$counts['sent']??0,'failed'=>$counts['failed']??0,
        'pending'=>$counts['pending']??0,'sending'=>$counts['sending']??0,
        'last'=>isset($idx,$item)?['email'=>$item['email'],'status'=>$q['items'][$idx]['status']]:null,
        'created'=>$q['created']]);
}
$rid=filter_var($_POST['reservation_id'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if ($action==='payment') {
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

    }
}
$db->close();
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>CRTSHT / MAIL CONTROL</title>
<style>*{box-sizing:border-box}body{background:#f2f2ee;color:#111;font:14px/1.5 ui-monospace,SFMono-Regular,Menlo,monospace;max-width:960px;margin:30px auto;padding:0 18px}a{color:inherit}h1{font-size:clamp(30px,6vw,60px);letter-spacing:-.06em}h2{font-size:22px}section{border:1px solid #111;padding:clamp(16px,3vw,28px);margin:20px 0}label{display:block;font-size:12px;margin:14px 0 6px;font-weight:bold}input,textarea,select{display:block;width:100%;font:16px/1.5 inherit;font-size:16px;padding:12px;background:#fff;border:1px solid #888;border-radius:0}textarea{min-height:240px;resize:vertical}button{padding:14px 18px;background:#111;color:#fff;border:0;font:inherit;cursor:pointer;margin-top:14px}.muted{color:#555;font-size:12px}.error{padding:14px;background:#111;color:#fff}.notice{padding:14px;border:1px solid #111}.recipients{max-height:180px;overflow:auto;border-top:1px solid #bbb;margin-top:12px;padding-top:8px}.recipients div{padding:3px 0}.confirm{max-width:160px}.links{display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap}</style></head><body>
<div class="links"><a href="/crtshtdrwmng/">← DRAW CONTROL</a><span>PRIVATE / NO AUTOMATIC SENDING</span></div><h1>MAIL CONTROL.</h1>
<p>Operational messages to registered participants only. Do not use this tool for unsolicited promotional campaigns. One message per unique email address. Always preview the audience and text before sending.</p>
<?php if($error):?><p class="error" role="alert"><?=mc_e($error)?></p><?php endif;?>
<?php if($notice):?><p class="notice" role="status"><?=mc_e($notice)?></p><?php endif;?>
<section><h2>01 / PARTICIPANT UPDATE</h2><form method="post" id="campaign-form" onsubmit="return false"><input type="hidden" name="csrf" value="<?=mc_e($csrf)?>"><input type="hidden" name="action" value="queue_start">
<label for="audience">Audience</label><select id="audience" name="audience" onchange="this.form.querySelector('[name=confirm]').value='';this.form.querySelector('[name=action]').value='preview';this.form.onsubmit=null;this.form.submit()">
<?php foreach(['active'=>'All active reservations','paid'=>'Paid reservations','02'=>'Draw 02 participants','03'=>'Draw 03 participants'] as $v=>$name):?><option value="<?=$v?>" <?=$filter===$v?'selected':''?>><?=mc_e($name)?></option><?php endforeach;?></select>
<label for="subject">Subject</label><input id="subject" name="subject" maxlength="140" value="<?=mc_e($subject)?>" required>
<label for="body">Message (plain text, {name} personalizes the greeting)</label><textarea id="body" name="body" maxlength="6000" required><?=mc_e($body)?></textarea>
<p><strong><?=count($recipients)?> distinct recipients</strong> · current audience</p>
<div class="recipients"><?php foreach($recipients as $r):?><div><?=mc_e((string)$r['Name'])?> — <?=mc_e((string)$r['Email'])?> · Draw <?=mc_e((string)$r['DrawBatch'])?></div><?php endforeach;?></div>
<p class="muted">Check the recipient list. Messages are sent individually (no exposed recipient list). The campaign sends one message per AJAX request. Keep this tab open until completion. Progress is retained in your admin session if you reload; ambiguous interrupted sends are never automatically retried.</p>
<label for="confirm">Type SEND to confirm</label><input class="confirm" id="confirm" name="confirm" autocomplete="off" placeholder="SEND" required>
<button type="button" id="start">START SENDING ONE BY ONE</button> <button type="button" id="stop" disabled>STOP AFTER CURRENT MAIL</button>
<div id="progress" role="status" aria-live="polite" class="notice" style="margin-top:18px" hidden></div>
</form></section>
<section><h2>02 / BANK TRANSFER REMINDER</h2><p>Resends the existing payment-details email to a single unpaid reservation. Amount, IBAN and reference come from the existing configuration and reservation.</p>
<form method="post"><input type="hidden" name="csrf" value="<?=mc_e($csrf)?>"><input type="hidden" name="action" value="payment">
<label for="reservation_id">Reservation database ID</label><input id="reservation_id" name="reservation_id" type="number" min="1" value="<?=$rid?:''?>" required>
<label for="payment_confirm">Type SEND to confirm</label><input class="confirm" id="payment_confirm" name="confirm" placeholder="SEND" autocomplete="off" required>
<button type="submit" onclick="return confirm('Resend payment details to this reservation?')">RESEND BANK DETAILS</button></form></section><script>
(() => {
const form=document.getElementById('campaign-form'), start=document.getElementById('start'), stop=document.getElementById('stop'), progress=document.getElementById('progress');
let running=false;
const csrf=form.querySelector('[name=csrf]').value;
async function request(action, data) {
 const p=new URLSearchParams({...data,action,csrf});
 const response=await fetch('/crtshtdrwmng/mail.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded','Accept':'application/json'},body:p});
 const result=await response.json();
 if(!response.ok||result.error) throw Error(result.error||'Request failed');
 return result;
}
function render(q) {
 progress.hidden=false;
 const completed=q.sent+q.failed;
 progress.textContent=completed+' / '+q.total+' attempted · '+q.sent+' accepted by SMTP · '+q.failed+' failed · '+q.pending+' pending'+(q.sending?' · '+q.sending+' in progress':'')+(q.active?' · KEEP THIS TAB OPEN':' · STOPPED / COMPLETE')+(q.last?' · Last: '+q.last.email+' ('+q.last.status+')':'');
 start.disabled=q.active;stop.disabled=!q.active;
}
async function loop(q) {
 if(running)return; running=true;
 try {
  while(q.active) { q=await request('queue_next',{});render(q); }
 } catch(e) { progress.hidden=false;progress.textContent='Sending paused: '+e.message+'. Reload to check status before resuming. Automatic retry disabled.'; }
 finally {running=false;}
}
start.addEventListener('click',async()=>{
 if(!form.reportValidity())return;
 if(form.querySelector('[name=confirm]').value!=='SEND'){alert('Type SEND to confirm.');return;}
 if(!confirm('Start sending to the displayed audience, one by one?'))return;
 try{
  const q=await request('queue_start',Object.fromEntries(new FormData(form)));
  render(q);loop(q);
 }catch(e){alert(e.message);}
});
stop.addEventListener('click',async()=>{
 try{const q=await request('queue_cancel',{});render(q);}catch(e){alert(e.message);}
});
request('queue_status',{}).then(q=>{render(q);if(q.active)loop(q);}).catch(()=>{});
})();
</script></body></html>