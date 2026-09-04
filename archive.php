<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$legalCompany = crt_env('CRTSHT_LEGAL_COMPANY');
$legalName = crt_env('CRTSHT_LEGAL_NAME') ?: 'Marco Spitzbarth';
$legalAddr = crt_env('CRTSHT_LEGAL_ADDR') ?: 'Zollstrasse 57';
$legalCity = crt_env('CRTSHT_LEGAL_CITY') ?: '8005 Zürich';
$legalCountry = crt_env('CRTSHT_LEGAL_COUNTRY') ?: 'Switzerland';
$legalEmail = crt_env('CRTSHT_LEGAL_EMAIL');
$legalPhone = crt_env('CRTSHT_LEGAL_PHONE') ?: '+41 (0)76 394 39 82';
$legalOperator = $legalCompany !== '' ? $legalCompany : $legalName;
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>CRTSHT / 128</title>
<meta name="description" content="CRTSHT — 128 unique physical works generated, printed and minted on Ethereum in 2021. Reassembled in 2026.">
<link rel="stylesheet" href="/site.css?v=7">
<style>
.legal-strip{border-top:1px solid var(--fg);margin-top:calc(var(--pad)*1.4);padding:14px 0 0;display:grid;grid-template-columns:minmax(150px,.45fr) minmax(0,1.55fr);gap:var(--pad);font-size:11px;line-height:1.6}.legal-strip strong{font-size:12px;letter-spacing:.06em}.legal-strip .legal-meta{max-width:76ch}.legal-strip a{text-decoration:underline}.legal-strip a:hover{text-decoration:none}@media(max-width:700px){.legal-strip{grid-template-columns:1fr;gap:8px}}
</style>
</head>
<body><main class="wrap">
<header>
<a class="brand" href="/">(RYP705H17.1NF0</a>
<nav class="nav"><a href="/" aria-current="page">Archive</a><a href="/lore">The Lore</a><a href="/oracle">The Oracle</a><a href="/draw">The Draw</a><a href="/legal">Legal / Imprint</a></nav>
</header>
<section class="intro">
<h1>KEEP YOUR SHIT TOGETHER</h1>
<p>Before it disperse.</p>
<p class="quiet">128 physical originals · pixel square · minted and sealed for posterity as 金のうんこ</p>
</section>
<section class="grid">
<?php for ($id=1; $id<=CRTSHT_TOTAL; $id++): $meta=crt_metadata($id); if(!$meta) continue; $img=crt_artwork($id); $title=crt_title($id,$meta); $aboveFold=$id<=12; ?>
<a class="card" href="/crtsht/<?= $id ?>">
<div><?php if($img): ?><img <?= $aboveFold ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"' ?> decoding="async" src="<?= crt_e($img) ?>" alt="<?= crt_e($title) ?>"><?php endif; ?></div>
<div class="num"><span><?= crt_e($title) ?></span><span><?= $id ?>/128</span></div>
</a>
<?php endfor; ?>
</section>
<section class="legal-strip" aria-label="Legal and merchant information">
<div><a href="/legal"><strong>LEGAL / IMPRINT →</strong></a></div>
<div class="legal-meta"><strong><?= crt_e($legalOperator) ?></strong><?php if($legalCompany !== '' && $legalName !== ''): ?> · <?= crt_e($legalName) ?><?php endif; ?><br><?= crt_e($legalAddr) ?> · <?= crt_e($legalCity) ?> · <?= crt_e($legalCountry) ?><?php if($legalEmail !== ''): ?><br><a href="mailto:<?= crt_e($legalEmail) ?>"><?= crt_e($legalEmail) ?></a><?php endif; ?><?php if($legalPhone !== ''): ?><?= $legalEmail !== '' ? ' · ' : '<br>' ?><?= crt_e($legalPhone) ?><?php endif; ?><br>Physical artworks · prices in CHF · Switzerland available as delivery destination.</div>
</section>
<footer class="footer"><span>CRTSHT / <a href="https://ibulla.com" target="_blank" rel="noopener">iBulla</a></span><span><a href="/legal">LEGAL / IMPRINT</a> · The shit is real. The archive is meta.</span></footer>
</main>
<script>
document.querySelectorAll('.card').forEach(card=>{
  card.addEventListener('pointerdown',()=>card.classList.add('is-pressed'),{passive:true});
  card.addEventListener('pointercancel',()=>card.classList.remove('is-pressed'),{passive:true});
});
</script>
</body></html>