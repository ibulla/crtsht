<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
header('X-Robots-Tag: index, follow', true);

$legalCompany = crt_env('CRTSHT_LEGAL_COMPANY');
$legalName = crt_env('CRTSHT_LEGAL_NAME') ?: 'Marco Spitzbarth';
$legalAddr = crt_env('CRTSHT_LEGAL_ADDR') ?: 'Zollstrasse 57';
$legalCity = crt_env('CRTSHT_LEGAL_CITY') ?: '8005 Zürich';
$legalCountry = crt_env('CRTSHT_LEGAL_COUNTRY') ?: 'Switzerland';
$legalEmail = crt_env('CRTSHT_LEGAL_EMAIL');
$legalPhone = crt_env('CRTSHT_LEGAL_PHONE') ?: '+41 (0)76 394 39 82';
$legalUid = crt_env('CRTSHT_LEGAL_UID');
$legalWebsite = crt_env('CRTSHT_LEGAL_WEBSITE') ?: 'https://cryptoshit.info';
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>CRTSHT / LEGAL</title>
<link rel="stylesheet" href="/site.css?v=8">
<style>
.legal{max-width:1180px;margin:0 auto;padding:var(--pad)}
.legal > header.project-header{display:flex;justify-content:space-between;align-items:baseline;border-bottom:1px solid var(--fg);padding-bottom:12px;margin-bottom:var(--pad)}
.project-header .brand{font-size:clamp(25px,4vw,58px);font-weight:700;letter-spacing:-.07em;text-decoration:none}
.project-header .nav{display:flex;gap:14px;flex-wrap:wrap;font-size:11px;text-transform:uppercase;letter-spacing:.06em}
.project-header .nav a{color:inherit;text-decoration:none}.project-header .nav a:hover{text-decoration:underline}.project-header .nav a[aria-current="page"]{text-decoration:underline}
.legal-head{border-bottom:1px solid var(--fg);padding-bottom:24px;margin-bottom:42px}
.legal-head h1{font-size:clamp(58px,12vw,150px);line-height:.78;letter-spacing:-.08em;margin:12px 0 20px}
.legal-grid{display:grid;grid-template-columns:minmax(180px,.45fr) minmax(0,1fr);gap:30px;border-top:1px solid var(--fg);padding:26px 0}
.legal-grid h2{font-size:12px;letter-spacing:.08em;margin:0;text-transform:uppercase}
.legal-copy{max-width:66ch}.legal-copy p{font-size:13px;line-height:1.65;margin:0 0 16px}
.legal-copy a{color:inherit;text-decoration:underline}.legal-meta{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace}
@media(max-width:700px){.legal-grid{grid-template-columns:1fr;gap:14px}}
</style>
</head>
<body>
<main class="legal">
<header class="project-header">
<a class="brand" href="/">CR¥P70$H!7</a>
<nav class="nav"><a href="/">Archive</a><a href="/lore">The Lore</a><a href="/oracle">The Oracle</a><a href="/draw">The Draw</a><a href="/legal" aria-current="page">Legal / Imprint</a></nav>
</header>
<header class="legal-head"><div class="eyebrow">CRTSHT / LEGAL NOTICE</div><h1>LEGAL<br>SHIT.</h1><p>Operator, sales and privacy information for cryptoshit.info.</p></header>

<section class="legal-grid"><h2>Operator / Seller</h2><div class="legal-copy legal-meta">
<p><?php if($legalCompany !== ''): ?><strong><?=crt_e($legalCompany)?></strong><br><?php endif; ?><strong><?=crt_e($legalName)?></strong><br><?=crt_e($legalAddr)?><br><?=crt_e($legalCity)?><br><?=crt_e($legalCountry)?></p>
<p><?php if($legalEmail !== ''): ?>Email: <a href="mailto:<?=crt_e($legalEmail)?>"><?=crt_e($legalEmail)?></a><br><?php endif; ?><?php if($legalPhone !== ''): ?>Phone: <?=crt_e($legalPhone)?><br><?php endif; ?><?php if($legalUid !== ''): ?>UID: <?=crt_e($legalUid)?><br><?php endif; ?>Website: <a href="<?=crt_e($legalWebsite)?>"><?=crt_e(preg_replace('~^https?://~','',$legalWebsite) ?? $legalWebsite)?></a></p>
<p>CRTSHT is an art project by Marco Spitzbarth / <a href="https://ibulla.com" target="_blank" rel="noopener">iBulla</a>.</p>
</div></section>

<section class="legal-grid"><h2>The Draw / Sales</h2><div class="legal-copy">
<p>CRTSHT is a limited edition of 128 physical artworks. A paid draw entry entitles the buyer to one physical CRTSHT. The specific artwork is not selected at checkout: it is assigned by chance at the scheduled draw.</p>
<p><strong>All prices are shown in Swiss francs (CHF). Switzerland is available as a delivery destination.</strong> Payment can be made through the payment methods offered at checkout or by bank transfer after requesting payment details.</p>
<p>A reservation is not active for the draw until payment has been received and confirmed. The scheduled draw and later delivery of the physical work are therefore separate from the moment of payment.</p>
</div></section>

<section class="legal-grid"><h2>Privacy</h2><div class="legal-copy">
<p>For reservations, payment, the draw and delivery, we process the information supplied by the buyer, including name, email address, mobile number and postal address. We use this information only insofar as necessary to administer the purchase, payment, draw, customer communication and delivery, and to meet applicable legal and accounting obligations.</p>
<p>Online card and TWINT payments are processed by Stripe. Payment information entered on Stripe's hosted checkout is processed by Stripe; CRTSHT does not receive or store full card details.</p>
<p>Questions concerning personal data or a purchase can be sent to the contact address above.</p>
</div></section>

<section class="legal-grid"><h2>Project / Records</h2><div class="legal-copy">
<p>The blockchain, NFT and archive records presented on this website form part of the artistic work and its provenance. Historical 2021 metadata is preserved as archival material. Ownership of a physical CRTSHT does not imply transfer of copyright or other intellectual-property rights unless expressly agreed otherwise.</p>
</div></section>

<footer class="footer"><span>CRTSHT / <a href="https://ibulla.com" target="_blank" rel="noopener">iBulla</a></span><span><a href="/">BACK TO ARCHIVE</a> · <a href="/draw">THE DRAW</a></span></footer>
</main>
</body>
</html>