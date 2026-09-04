<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/bootstrap.php';

header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Cache-Control: no-store, private');

$code = strtoupper(trim((string)($_GET['reservation'] ?? '')));
$valid = preg_match('/^R-[A-F0-9]{10}$/', $code) === 1;
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>CRTSHT / CHECKOUT CANCELLED</title><link rel="stylesheet" href="/site.css?v=8"><style>.pay-wrap{max-width:760px;margin:8vh auto;padding:var(--pad)}.pay-box{border:1px solid var(--fg);padding:clamp(18px,4vw,42px)}.pay-box h1{font-size:clamp(42px,8vw,92px);line-height:.86;letter-spacing:-.07em;margin:0 0 24px}.pay-box p{font-size:13px;line-height:1.6;max-width:58ch}.pay-box a{color:inherit}.pay-meta{border-top:1px solid var(--line);margin-top:24px;padding-top:14px;font-size:10px;text-transform:uppercase;letter-spacing:.07em}</style></head><body><main class="pay-wrap"><div class="pay-box"><h1>NO SHIT<br>HAPPENED.</h1><p>The Stripe checkout was cancelled. Your existing CRTSHT reservation has not been marked as paid or cancelled.</p><?php if($valid):?><div class="pay-meta"><?=crt_e($code)?> · STILL RESERVED</div><p><a href="/stripe/start.php?reservation=<?=rawurlencode($code)?>">TRY PAYMENT AGAIN →</a></p><?php endif;?><p><a href="/draw">← RETURN TO THE DRAW</a></p></div></main></body></html>
