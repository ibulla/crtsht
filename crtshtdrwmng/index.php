<?php
declare(strict_types=1);
require dirname(__DIR__) . '/inc/bootstrap.php';

header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Cache-Control: no-store, private');
header('Pragma: no-cache');

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_name('crtsht_draw_admin');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/crtshtdrwmng',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Strict',
]);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function adm_e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function adm_csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
    return (string)$_SESSION['csrf'];
}
function adm_csrf_ok(string $token): bool {
    return $token !== '' && hash_equals((string)($_SESSION['csrf'] ?? ''), $token);
}
function adm_auth_configured(): bool {
    return crt_env('CRTSHT_DRAW_ADMIN_USER') !== '' && crt_env('CRTSHT_DRAW_ADMIN_PASS_HASH') !== '';
}
function adm_logged_in(): bool { return !empty($_SESSION['draw_admin_ok']); }
function adm_redirect(string $to = '/crtshtdrwmng/'): never { header('Location: ' . $to, true, 303); exit; }
function adm_status_class(string $status): string { return in_array($status, ['paid','assigned'], true) ? 'ok' : ($status === 'cancelled' ? 'bad' : 'wait'); }

$loginError = '';
if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', (bool)$p['secure'], (bool)$p['httponly']);
    }
    session_destroy();
    adm_redirect();
}

if (!adm_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $user = trim((string)($_POST['user'] ?? ''));
    $pass = (string)($_POST['pass'] ?? '');
    $expectedUser = crt_env('CRTSHT_DRAW_ADMIN_USER');
    $hash = crt_env('CRTSHT_DRAW_ADMIN_PASS_HASH');
    if (adm_auth_configured() && hash_equals($expectedUser, $user) && password_verify($pass, $hash)) {
        session_regenerate_id(true);
        $_SESSION['draw_admin_ok'] = true;
        $_SESSION['csrf'] = bin2hex(random_bytes(24));
        adm_redirect();
    }
    usleep(350000);
    $loginError = 'Access denied.';
}

if (!adm_logged_in()) {
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>CRTSHT / DRAW CONTROL</title>
<style>:root{--bg:#111;--fg:#f2f2ee;--muted:#888;--line:#444}*{box-sizing:border-box}html{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;background:var(--bg);color:var(--fg)}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:20px}.login{width:min(430px,100%);border:1px solid var(--fg)}.bar{padding:8px 10px;border-bottom:1px solid var(--fg);font-size:10px;letter-spacing:.08em;text-transform:uppercase;display:flex;justify-content:space-between}.body{padding:20px}h1{font-size:32px;letter-spacing:-.06em;margin:0 0 24px}label{display:block;font-size:10px;text-transform:uppercase;letter-spacing:.08em;margin:13px 0 6px}input{width:100%;background:transparent;color:var(--fg);border:1px solid var(--line);padding:12px;font:inherit}button{width:100%;margin-top:16px;background:var(--fg);color:var(--bg);border:0;padding:12px;font:inherit;cursor:pointer}.err,.note{font-size:11px;line-height:1.5;margin-top:14px}.err{color:#ffb0b0}.note{color:var(--muted)}</style></head><body><div class="login"><div class="bar"><span>CRTSHT / DRAW CONTROL</span><span>PRIVATE</span></div><div class="body"><h1>KEEP IT<br>TOGETHER.</h1>
<?php if(!adm_auth_configured()): ?><div class="err">Admin login is not configured. Add CRTSHT_DRAW_ADMIN_USER and CRTSHT_DRAW_ADMIN_PASS_HASH to private/config.php.</div><?php else: ?>
<form method="post" autocomplete="off"><input type="hidden" name="action" value="login"><label>User</label><input name="user" autocomplete="username" required autofocus><label>Password</label><input type="password" name="pass" autocomplete="current-password" required><button type="submit">OPEN CONTROL</button></form><?php if($loginError): ?><div class="err"><?=adm_e($loginError)?></div><?php endif; ?><?php endif; ?>
<div class="note">Unlinked administrative endpoint. Session-only access. No indexing.</div></div></div></body></html><?php exit; }

$db = crt_db();
if (!$db) { http_response_code(503); exit('Database unavailable.'); }
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'login') {
    if (!adm_csrf_ok((string)($_POST['csrf'] ?? ''))) {
        $error = 'Security token expired. Reload and try again.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        try {
            if ($action === 'reservation_status') {
                $rid = filter_var($_POST['reservation_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
                $status = (string)($_POST['status'] ?? '');
                if (!$rid || !in_array($status, ['reserved','paid','cancelled'], true)) throw new RuntimeException('Invalid reservation update.');
                $db->begin_transaction();
                $paidAt = $status === 'paid' ? date('Y-m-d H:i:s') : null;
                $stmt = $db->prepare('UPDATE CRTSHT_Draw_Reservations SET Status=?, PaidAt=CASE WHEN ?="paid" THEN COALESCE(PaidAt, NOW()) ELSE PaidAt END WHERE ID=?');
                if (!$stmt) throw new RuntimeException('Prepare failed.');
                $stmt->bind_param('ssi', $status, $status, $rid); $stmt->execute(); $stmt->close();
                $entryStatus = $status === 'paid' ? 'paid' : ($status === 'cancelled' ? 'cancelled' : 'reserved');
                $stmt = $db->prepare("UPDATE CRTSHT_Draw_Entries SET Status=? WHERE ReservationID=? AND Status<>'assigned'");
                if (!$stmt) throw new RuntimeException('Prepare failed.');
                $stmt->bind_param('si', $entryStatus, $rid); $stmt->execute(); $stmt->close();
                $db->commit();
                $message = 'Reservation updated.';
            } elseif ($action === 'reservation_edit') {
                $rid = filter_var($_POST['reservation_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
                if (!$rid) throw new RuntimeException('Invalid reservation.');
                $fields = [];
                foreach (['name'=>120,'email'=>190,'mobile'=>50,'address'=>190,'plz'=>24,'city'=>120,'country'=>120,'payment_note'=>255] as $key=>$max) {
                    $v = trim((string)($_POST[$key] ?? ''));
                    if (mb_strlen($v) > $max) $v = mb_substr($v, 0, $max);
                    $fields[$key] = $v;
                }
                if ($fields['name']==='' || !filter_var($fields['email'], FILTER_VALIDATE_EMAIL) || $fields['mobile']==='' || $fields['address']==='' || $fields['plz']==='' || $fields['city']==='' || $fields['country']==='') throw new RuntimeException('Please provide valid buyer data.');
                $stmt = $db->prepare('UPDATE CRTSHT_Draw_Reservations SET Name=?,Email=?,Mobile=?,Address=?,PLZ=?,City=?,Country=?,PaymentNote=? WHERE ID=?');
                if (!$stmt) throw new RuntimeException('Prepare failed.');
                $stmt->bind_param('ssssssssi',$fields['name'],$fields['email'],$fields['mobile'],$fields['address'],$fields['plz'],$fields['city'],$fields['country'],$fields['payment_note'],$rid);
                $stmt->execute(); $stmt->close();
                $message = 'Buyer data saved.';
            } elseif ($action === 'entry_assign') {
                $eid = filter_var($_POST['entry_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
                $raw = trim((string)($_POST['crtsht_id'] ?? ''));
                if (!$eid) throw new RuntimeException('Invalid entry.');
                $assigned = $raw === '' ? null : filter_var($raw, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>128]]);
                if ($raw !== '' && $assigned === false) throw new RuntimeException('CRTSHT ID must be 1–128.');
                if ($assigned === null) {
                    $stmt = $db->prepare("UPDATE CRTSHT_Draw_Entries SET AssignedCRTSHT=NULL,AssignedAt=NULL,Status=CASE WHEN Status='assigned' THEN 'paid' ELSE Status END WHERE ID=?");
                    $stmt->bind_param('i',$eid);
                } else {
                    $stmt = $db->prepare("UPDATE CRTSHT_Draw_Entries e JOIN CRTSHT_Draw_Reservations r ON r.ID=e.ReservationID SET e.AssignedCRTSHT=?,e.AssignedAt=NOW(),e.Status='assigned' WHERE e.ID=? AND r.Status='paid'");
                    $stmt->bind_param('ii',$assigned,$eid);
                }
                if (!$stmt) throw new RuntimeException('Prepare failed.');
                if (!$stmt->execute()) {
                    if ($stmt->errno === 1062) throw new RuntimeException('That CRTSHT is already assigned to another ticket.');
                    throw new RuntimeException('Assignment failed.');
                }
                if ($assigned !== null && $stmt->affected_rows < 1) throw new RuntimeException('Only paid reservations can receive a CRTSHT.');
                $stmt->close();
                $message = $assigned === null ? 'Assignment removed.' : 'CRTSHT assigned.';
            }
        } catch (Throwable $e) {
            if ($db->errno || $db->thread_id) { try { $db->rollback(); } catch (Throwable $ignore) {} }
            $error = $e->getMessage();
        }
    }
}

$viewId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) ?: 0;
$summary = ['total'=>0,'reserved'=>0,'paid'=>0,'assigned'=>0,'cancelled'=>0,'amount_paid'=>0,'amount_open'=>0];
$q = $db->query("SELECT COUNT(*) total, SUM(r.Status='reserved') reserved, SUM(r.Status='paid') paid, SUM(e.Status='assigned') assigned, SUM(r.Status='cancelled') cancelled FROM CRTSHT_Draw_Entries e JOIN CRTSHT_Draw_Reservations r ON r.ID=e.ReservationID");
if ($q) { $summary = array_merge($summary, $q->fetch_assoc() ?: []); $q->free(); }
$aq = $db->query("SELECT COALESCE(SUM(CASE WHEN Status='paid' THEN TotalPrice ELSE 0 END),0) amount_paid, COALESCE(SUM(CASE WHEN Status='reserved' THEN TotalPrice ELSE 0 END),0) amount_open FROM CRTSHT_Draw_Reservations");
if ($aq) { $summary = array_merge($summary, $aq->fetch_assoc() ?: []); $aq->free(); }

$detail = null; $entries = [];
if ($viewId) {
    $stmt = $db->prepare('SELECT * FROM CRTSHT_Draw_Reservations WHERE ID=? LIMIT 1');
    $stmt->bind_param('i',$viewId); $stmt->execute(); $detail = $stmt->get_result()->fetch_assoc() ?: null; $stmt->close();
    if ($detail) {
        $stmt = $db->prepare('SELECT ID,DrawBatch,Status,AssignedCRTSHT,AssignedAt,CreatedAt FROM CRTSHT_Draw_Entries WHERE ReservationID=? ORDER BY ID');
        $stmt->bind_param('i',$viewId); $stmt->execute(); $res=$stmt->get_result(); while($row=$res->fetch_assoc()) $entries[]=$row; $stmt->close();
    }
}

$list=[];
$res=$db->query("SELECT r.*, GROUP_CONCAT(e.ID ORDER BY e.ID SEPARATOR ',') EntryIDs, GROUP_CONCAT(COALESCE(e.AssignedCRTSHT,'-') ORDER BY e.ID SEPARATOR ',') AssignedIDs FROM CRTSHT_Draw_Reservations r LEFT JOIN CRTSHT_Draw_Entries e ON e.ReservationID=r.ID GROUP BY r.ID ORDER BY r.CreatedAt DESC, r.ID DESC");
if($res){while($row=$res->fetch_assoc())$list[]=$row;$res->free();}
$db->close();
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>CRTSHT / DRAW CONTROL</title>
<style>:root{--bg:#f2f2ee;--fg:#111;--muted:#777;--line:#c9c9c2}*{box-sizing:border-box}html{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;background:var(--bg);color:var(--fg)}body{margin:0;padding:18px}a{color:inherit}.top{display:flex;justify-content:space-between;gap:18px;align-items:center;border-bottom:1px solid var(--fg);padding-bottom:12px;margin-bottom:18px}.brand{font-size:clamp(24px,4vw,46px);font-weight:800;letter-spacing:-.07em;text-decoration:none}.small{font-size:10px;text-transform:uppercase;letter-spacing:.08em}.stats{display:grid;grid-template-columns:repeat(7,1fr);border-left:1px solid var(--line);border-top:1px solid var(--line);margin-bottom:18px}.stat{padding:12px;border-right:1px solid var(--line);border-bottom:1px solid var(--line)}.stat b{font-size:26px;display:block}.stat span{font-size:9px;text-transform:uppercase;letter-spacing:.08em;color:var(--muted)}.flash{padding:10px 12px;border:1px solid var(--fg);margin-bottom:16px;font-size:11px}.flash.bad{background:#111;color:#fff}.table{width:100%;border-collapse:collapse;font-size:11px}.table th,.table td{text-align:left;padding:9px 8px;border-bottom:1px solid var(--line);vertical-align:top}.table th{text-transform:uppercase;letter-spacing:.06em;font-size:9px}.pill{display:inline-block;border:1px solid var(--fg);border-radius:100px;padding:3px 7px;text-transform:uppercase;font-size:9px}.pill.ok{background:var(--fg);color:var(--bg)}.pill.bad{text-decoration:line-through}.muted{color:var(--muted)}.detail{border-top:1px solid var(--fg);margin:22px 0;padding-top:18px;display:grid;grid-template-columns:minmax(180px,.5fr) minmax(0,1.5fr);gap:24px}.detail h1{margin:0;font-size:clamp(28px,4vw,54px);letter-spacing:-.06em}.box{border:1px solid var(--fg);padding:14px;margin-bottom:14px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}.grid .full{grid-column:1/-1}label{font-size:9px;text-transform:uppercase;letter-spacing:.07em;color:var(--muted)}input,select,button{font:inherit;font-size:11px}input,select{width:100%;background:transparent;border:1px solid var(--line);padding:9px;margin-top:4px}button{background:var(--fg);color:var(--bg);border:0;padding:9px 12px;cursor:pointer}.actions{display:flex;gap:8px;align-items:end;flex-wrap:wrap}.entrybox{display:grid;grid-template-columns:70px 90px 1fr auto;gap:10px;align-items:end;border-bottom:1px solid var(--line);padding:10px 0}.entrybox:last-child{border-bottom:0}.entrybox form{display:contents}.back{font-size:10px;text-transform:uppercase}.nowrap{white-space:nowrap}@media(max-width:850px){.stats{grid-template-columns:repeat(2,1fr)}.detail{grid-template-columns:1fr}.table th:nth-child(4),.table td:nth-child(4),.table th:nth-child(6),.table td:nth-child(6){display:none}.grid{grid-template-columns:1fr}.grid .full{grid-column:auto}.entrybox{grid-template-columns:60px 1fr}.entrybox form{display:grid;grid-template-columns:1fr auto;gap:8px;grid-column:1/-1}}</style></head><body>
<div class="top"><a class="brand" href="/crtshtdrwmng/">CRTSHT / DRAW CONTROL</a><div class="small"><a href="/draw" target="_blank">PUBLIC DRAW ↗</a> &nbsp; <a href="?logout=1">LOGOUT</a></div></div>
<div class="stats"><div class="stat"><b><?=128-(int)$summary['reserved']-(int)$summary['paid']?></b><span>unreserved slots</span></div><div class="stat"><b><?=(int)$summary['reserved']?></b><span>reserved tickets</span></div><div class="stat"><b><?=(int)$summary['paid']?></b><span>paid tickets</span></div><div class="stat"><b>CHF <?=adm_e(number_format((float)$summary['amount_open'],0,'.',"'"))?></b><span>open amount</span></div><div class="stat"><b>CHF <?=adm_e(number_format((float)$summary['amount_paid'],0,'.',"'"))?></b><span>paid amount</span></div><div class="stat"><b><?=(int)$summary['assigned']?></b><span>assigned works</span></div><div class="stat"><b><?=128-(int)$summary['assigned']?></b><span>unassigned works</span></div></div>
<?php if($message):?><div class="flash"><?=adm_e($message)?></div><?php endif;?><?php if($error):?><div class="flash bad"><?=adm_e($error)?></div><?php endif;?>
<?php if($detail): ?>
<div class="detail"><div><a class="back" href="/crtshtdrwmng/">← ALL RESERVATIONS</a><h1><?=adm_e($detail['ReservationCode'])?></h1><p class="small">#<?=$detail['ID']?> · <?=$detail['Quantity']?> ticket<?=$detail['Quantity']==1?'':'s'?> · Draw <?=$detail['DrawBatch']?></p><span class="pill <?=adm_status_class($detail['Status'])?>"><?=adm_e($detail['Status'])?></span></div><div>
<div class="box"><form method="post"><input type="hidden" name="csrf" value="<?=adm_e(adm_csrf())?>"><input type="hidden" name="action" value="reservation_edit"><input type="hidden" name="reservation_id" value="<?=$detail['ID']?>"><div class="grid">
<label>Name<input name="name" value="<?=adm_e($detail['Name'])?>" required></label><label>Email<input type="email" name="email" value="<?=adm_e($detail['Email'])?>" required></label><label>Mobile<input name="mobile" value="<?=adm_e($detail['Mobile'])?>" required></label><label>Address<input name="address" value="<?=adm_e($detail['Address'])?>" required></label><label>PLZ<input name="plz" value="<?=adm_e($detail['PLZ'])?>" required></label><label>City<input name="city" value="<?=adm_e($detail['City'])?>" required></label><label>Country<input name="country" value="<?=adm_e($detail['Country'])?>" required></label><label>Payment note<input name="payment_note" value="<?=adm_e((string)$detail['PaymentNote'])?>"></label><div class="full"><button type="submit">SAVE BUYER DATA</button></div></div></form></div>
<div class="box"><div class="small">PAYMENT</div><p><strong>TOTAL / CHF <?=adm_e(number_format((float)$detail['TotalPrice'],2,'.',"'"))?></strong><br><span class="muted"><?= $detail['Status']==='paid' ? 'PAID / CHF '.adm_e(number_format((float)$detail['TotalPrice'],2,'.',"'")) : ($detail['Status']==='reserved' ? 'OPEN / CHF '.adm_e(number_format((float)$detail['TotalPrice'],2,'.',"'")) : 'NO OPEN AMOUNT') ?></span></p></div>
<div class="box"><form method="post" class="actions"><input type="hidden" name="csrf" value="<?=adm_e(adm_csrf())?>"><input type="hidden" name="action" value="reservation_status"><input type="hidden" name="reservation_id" value="<?=$detail['ID']?>"><label>Status<select name="status"><option value="reserved" <?=$detail['Status']==='reserved'?'selected':''?>>RESERVED</option><option value="paid" <?=$detail['Status']==='paid'?'selected':''?>>PAID</option><option value="cancelled" <?=$detail['Status']==='cancelled'?'selected':''?>>CANCELLED</option></select></label><button type="submit">UPDATE STATUS</button><span class="small muted">Paid: <?=adm_e((string)($detail['PaidAt'] ?: '—'))?></span></form></div>
<div class="box"><div class="small">DRAW TICKETS / ASSIGN CRTSHT</div><?php foreach($entries as $e):?><div class="entrybox"><b>#<?=str_pad((string)$e['ID'],4,'0',STR_PAD_LEFT)?></b><span class="pill <?=adm_status_class($e['Status'])?>"><?=adm_e($e['Status'])?></span><form method="post"><input type="hidden" name="csrf" value="<?=adm_e(adm_csrf())?>"><input type="hidden" name="action" value="entry_assign"><input type="hidden" name="entry_id" value="<?=$e['ID']?>"><label>CRTSHT ID<input type="number" min="1" max="128" name="crtsht_id" value="<?=adm_e((string)($e['AssignedCRTSHT'] ?? ''))?>" placeholder="1–128"></label><button type="submit"><?=$e['AssignedCRTSHT']?'CHANGE':'ASSIGN'?></button></form><?php if($e['AssignedCRTSHT']):?><a class="small nowrap" target="_blank" href="/crtsht/<?=$e['AssignedCRTSHT']?>">OPEN 0x<?=str_pad(dechex((int)$e['AssignedCRTSHT']),4,'0',STR_PAD_LEFT)?> ↗</a><?php endif;?></div><?php endforeach;?></div>
</div></div>
<?php else: ?>
<table class="table"><thead><tr><th>Reservation</th><th>Status</th><th>Amount</th><th>Buyer</th><th>Contact</th><th>Tickets</th><th>Assigned</th><th>Created</th></tr></thead><tbody><?php foreach($list as $r):?><tr><td><a href="?id=<?=$r['ID']?>"><b><?=adm_e($r['ReservationCode'])?></b></a><div class="muted">#<?=$r['ID']?> · Draw <?=adm_e($r['DrawBatch'])?></div></td><td><span class="pill <?=adm_status_class($r['Status'])?>"><?=adm_e($r['Status'])?></span></td><td class="nowrap"><strong>CHF <?=adm_e(number_format((float)$r['TotalPrice'],2,'.',"'"))?></strong><div class="muted"><?= $r['Status']==='paid' ? 'PAID' : ($r['Status']==='reserved' ? 'OPEN' : '—') ?></div></td><td><?=adm_e($r['Name'])?><div class="muted"><?=adm_e($r['City'])?> · <?=adm_e($r['Country'])?></div></td><td><a href="mailto:<?=adm_e($r['Email'])?>"><?=adm_e($r['Email'])?></a><div class="muted"><?=adm_e($r['Mobile'])?></div></td><td><?=$r['Quantity']?>× <span class="muted">#<?=adm_e(str_replace(',', ', #', (string)$r['EntryIDs']))?></span></td><td><?=adm_e((string)$r['AssignedIDs'])?></td><td class="nowrap"><?=adm_e($r['CreatedAt'])?></td></tr><?php endforeach;?></tbody></table>
<?php endif; ?>
</body></html>