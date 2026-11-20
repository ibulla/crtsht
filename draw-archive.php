<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/selected-common.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off','samesite'=>'Lax']);
    session_start();
}
$csrf = $_SESSION['draw_news_csrf'] ?? '';
if (!is_string($csrf) || strlen($csrf)<32) $_SESSION['draw_news_csrf']=$csrf=bin2hex(random_bytes(24));
$error='';$done=false;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name=trim((string)($_POST['name']??''));
    $email=strtolower(trim((string)($_POST['email']??'')));
    $token=(string)($_POST['csrf']??'');
    if (!hash_equals($csrf,$token) || trim((string)($_POST['website']??''))!=='') $error='Request rejected. Please reload.';
    elseif (mb_strlen($name)>120 || $name==='' || mb_strlen($email)>190 || !filter_var($email,FILTER_VALIDATE_EMAIL)) $error='Please enter your name and a valid email address.';
    elseif (($_POST['consent']??'')!=='yes') $error='Please confirm that you want to receive updates.';
    else {
        $db=crt_db();
        if (!$db) $error='Signup temporarily unavailable. Please try again later.';
        else {
            $stmt=$db->prepare('INSERT INTO CRTSHT_Draw_News (Name,Email,ConsentAt,CreatedAt) VALUES (?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE Name=VALUES(Name),ConsentAt=NOW()');
            if (!$stmt) $error='Signup temporarily unavailable. Please try again later.';
            else {
                $stmt->bind_param('ss',$name,$email);
                if ($stmt->execute()) {$done=true;$_SESSION['draw_news_csrf']=$csrf=bin2hex(random_bytes(24));}
                else $error='Signup temporarily unavailable. Please try again later.';
                $stmt->close();
            }
            $db->close();
        }
    }
}
$selectedForDraw=[];
$selectedDb=crt_db();
if($selectedDb){
    try{
        crt_selected_db_timezone($selectedDb);
        $selectedResult=$selectedDb->query("SELECT w.ID,w.CRTSHT_ID,w.PriceCHF,w.Location,w.OfferDeadline
          FROM CRTSHT_Selected_Works w
          WHERE w.SaleStatus='open' AND w.OfferDeadline>NOW()
          AND NOT EXISTS(SELECT 1 FROM CRTSHT_Draw_Entries e WHERE e.AssignedCRTSHT=w.CRTSHT_ID)
          ORDER BY w.OfferDeadline,w.CRTSHT_ID");
        if($selectedResult){$selectedForDraw=$selectedResult->fetch_all(MYSQLI_ASSOC);$selectedResult->free();}
    }catch(Throwable $e){error_log('CRTSHT draw selected-work listing failed: '.$e->getMessage());}
    $selectedDb->close();
}
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>SHIT HAPPENED. / CRTSHT DRAW</title>
<meta name="description" content="CRTSHT Series 001 at ENDSAFTER Zürich has ended. Three draws, September–October 2026.">
<link rel="stylesheet" href="/site.css?v=13">
<style>
.archive{max-width:1100px}.archive h1{font-size:clamp(54px,11vw,160px);line-height:.82;letter-spacing:-.085em;margin:24px 0 32px}.archive-lead{font-size:clamp(18px,2.5vw,34px);letter-spacing:-.04em;max-width:26ch;line-height:1.1}.archive-grid{display:grid;grid-template-columns:1fr 1fr;gap:var(--pad);border-top:1px solid var(--fg);margin-top:48px;padding-top:22px}.archive h2{font-size:clamp(28px,4vw,58px);line-height:.94;letter-spacing:-.055em;margin:0 0 18px}.archive p{line-height:1.55}.archive-dates{list-style:none;margin:20px 0;padding:0;border-top:1px solid var(--line)}.archive-dates li{color:#888;text-decoration:line-through;text-decoration-color:#888;text-decoration-thickness:1px;padding:10px 0;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;gap:12px;font-size:12px}.past-price{color:#888!important;font-size:clamp(13px,1.5vw,17px)!important;text-decoration:line-through;text-decoration-color:#888;text-decoration-thickness:1px;margin:16px 0 22px}.price{font-size:clamp(45px,7vw,100px);line-height:1;letter-spacing:-.07em;margin:12px 0}.subtle{color:var(--muted);font-size:12px}.news{border-top:1px solid var(--fg);margin-top:54px;padding-top:22px}.news-form{display:grid;grid-template-columns:1fr 1fr auto;gap:8px;margin-top:20px}.news-form input{font:inherit;min-width:0;padding:14px;background:transparent;border:1px solid var(--fg);font-size:14px}.news-form button{font:inherit;background:var(--fg);color:var(--bg);border:1px solid var(--fg);padding:14px 18px;cursor:pointer}.news-form button:hover{background:transparent;color:var(--fg)}.consent{grid-column:1/-1;font-size:12px;line-height:1.5;display:flex;gap:10px;align-items:start}.consent input{margin-top:2px}.feedback{border:1px solid var(--fg);padding:12px;margin-top:14px}.hidden-field{position:absolute;left:-9999px}.archive-end{margin-top:48px;border-top:1px solid var(--fg);padding:16px 0;font-size:12px}
@media(max-width:720px){.archive-grid,.news-form{grid-template-columns:1fr}.consent{grid-column:1}.archive h1{overflow-wrap:anywhere}}
</style></head><body><main class="wrap archive">
<header><a class="brand" href="/">CR¥P70$H!7.DR4W</a><nav class="nav"><a href="/">Archive</a><a href="/lore">The Lore</a><a href="/oracle">The Oracle</a><a href="/draw" aria-current="page">The Draw</a></nav></header>
<div class="eyebrow">SERIES 001 / ENDSAFTER / COMPLETED</div>
<h1>SHIT<br>HAPPENED.</h1>
<p class="archive-lead">Three draws. One temporary space. The first dispersal is over.</p>
<div class="archive-grid">
<section><div class="eyebrow">THE FIRST SERIES</div><h2>IT ENDSAFTER THIS.</h2>
<p>ENDSAFTER<br>Hardturmstrasse 307<br>8005 Zürich, Switzerland</p>
<ul class="archive-dates"><li><span>25.09.2026</span><strong>DRAW 01</strong></li><li><span>17.10.2026</span><strong>DRAW 02</strong></li><li><span>31.10.2026</span><strong>DRAW 03 / FINAL</strong></li></ul></section>
<section><div class="eyebrow">CRTSHT / PRICE HISTORY</div><p class="past-price">CHF 128 / ENDSAFTER / 2026</p><hr style="border:0;border-top:1px solid var(--line);margin:24px 0"><div class="eyebrow">NEXT DRAW / NOT ANNOUNCED</div></section>
</div>
<section class="news" id="selected-works"><div class="eyebrow">SELECTED WORKS / NEW</div><h2>CHOOSE THE WORK.<br>CHANCE MAY FOLLOW.</h2>
<p>These are individual CRTSHT works offered at a fixed price. Submit a binding purchase offer before the closing date. The artist awards the purchase opportunity after the deadline, manually or by draw.</p>
<?php if($selectedForDraw):?><div class="archive-grid" style="grid-template-columns:repeat(<?=(count($selectedForDraw)>1?2:1)?>,minmax(0,1fr));gap:24px">
<?php foreach($selectedForDraw as $sw):$cid=(int)$sw['CRTSHT_ID'];$pic=crt_artwork($cid); ?>
<article style="min-width:0;border:1px solid var(--line);padding:clamp(12px,2vw,22px)">
<?php if($pic):?><a href="/selected-works#work-<?=(int)$sw['ID']?>"><img loading="lazy" src="<?=crt_e($pic)?>" alt="CRTSHT #<?=$cid?>" style="width:100%;max-height:400px;object-fit:contain"></a><?php endif;?>
<div class="eyebrow" style="margin-top:18px">CRTSHT / #<?=str_pad((string)$cid,3,'0',STR_PAD_LEFT)?></div>
<h3 style="font-size:clamp(22px,3vw,42px);margin:12px 0"><?=crt_e(crt_title($cid,crt_metadata($cid)))?></h3>
<p>FIXED PRICE / <strong>CHF <?=crt_e(number_format((float)$sw['PriceCHF'],2,'.',"'"))?></strong></p>
<p>OFFERS CLOSE / <?=crt_e((string)$sw['OfferDeadline'])?> (Zurich)</p>
<?php if(!empty($sw['Location'])):?><p>ON VIEW / <?=crt_e((string)$sw['Location'])?></p><?php endif;?>
<p><a href="/selected-works#work-<?=(int)$sw['ID']?>"><strong>MAKE AN OFFER →</strong></a></p>
</article><?php endforeach;?></div>
<?php else:?><p class="subtle">There are no individual works currently open for offers.</p><?php endif;?>
<p><a href="/selected-works"><strong>EXPLORE SELECTED WORKS →</strong></a></p></section>
<section class="news"><div class="eyebrow">WHAT NEXT?</div><h2>TRUST YOUR LUCK.<br>AGAIN?</h2>
<p>There may be another draw. There may not. Leave your email and we'll let you know if something happens.</p>
<?php if($done): ?><div class="feedback" role="status">YOU'RE ON THE LIST. NOTHING ELSE TO DO.</div><?php else: ?>
<?php if($error!==''): ?><div class="feedback" role="alert"><?=crt_e($error)?></div><?php endif; ?>
<form class="news-form" method="post" action="/draw">
<input type="hidden" name="csrf" value="<?=crt_e($csrf)?>">
<div class="hidden-field" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
<input name="name" maxlength="120" autocomplete="name" aria-label="Your name" placeholder="YOUR NAME" required>
<input type="email" name="email" maxlength="190" autocomplete="email" aria-label="Email address" placeholder="EMAIL ADDRESS" required>
<button type="submit">KEEP ME POSTED →</button>
<label class="consent"><input type="checkbox" name="consent" value="yes" required> I agree to receive occasional CRTSHT updates by email. No draw reservation is created. You can unsubscribe at any time by replying to an update.</label>
</form><?php endif; ?>
</section><div class="archive-end"><strong>THE DRAW IS OVER. THE RECORD REMAINS.</strong> <a href="/">EXPLORE THE ARCHIVE →</a></div>
</main></body></html>
