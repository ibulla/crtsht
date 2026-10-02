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
$dispersed = crt_draw_assignments();
$dispersedCount = count($dispersed);
$remainingCount = CRTSHT_TOTAL - $dispersedCount;
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>CRTSHT / 128</title>
<meta name="description" content="CRTSHT by Marco Spitzbarth (iBulla) — 128 unique physical artworks generated, printed and minted on Ethereum in 2021, reassembled and dispersed by draw in Zürich in 2026.">
<meta property="og:type" content="website">
<meta property="og:title" content="CRTSHT — Together Before We Disperse">
<meta property="og:description" content="128 physical originals. Generated in 2021. Reassembled in 2026. You choose to own one. Chance chooses which.">
<meta property="og:image" content="https://cryptoshit.info/img/About_crtsht.jpg">
<meta property="og:url" content="https://cryptoshit.info/">
<meta name="twitter:card" content="summary_large_image">
<link rel="canonical" href="https://cryptoshit.info/">
<link rel="stylesheet" href="/site.css?v=12">
<style>
.legal-strip{border-top:1px solid var(--fg);margin-top:calc(var(--pad)*1.4);padding:14px 0 0;display:grid;grid-template-columns:minmax(150px,.45fr) minmax(0,1.55fr);gap:var(--pad);font-size:11px;line-height:1.6}.legal-strip strong{font-size:12px;letter-spacing:.06em}.legal-strip .legal-meta{max-width:76ch}.legal-strip a{text-decoration:underline}.legal-strip a:hover{text-decoration:none}@media(max-width:700px){.legal-strip{grid-template-columns:1fr;gap:8px}}
</style>
<script type="application/ld+json">{"@context":"https://schema.org","@type":"VisualArtwork","name":"CRTSHT","alternateName":"CryptoShit","creator":{"@type":"Person","name":"Marco Spitzbarth","alternateName":"iBulla"},"dateCreated":"2021","artform":"Generative art / physical print / blockchain provenance","artMedium":"Genuine print on aluminium Dibond","description":"A series of 128 unique physical artworks generated, printed and minted on Ethereum in 2021 and reassembled for dispersal by draw in Zürich in 2026.","url":"https://cryptoshit.info/"}</script>
</head>
<body><main class="wrap">
<header>
<a class="brand" href="/">(RYP705H17.1NF0</a>
<nav class="nav"><a href="/" aria-current="page">Archive</a><a href="/lore">The Lore</a><a href="/oracle">The Oracle</a><a href="/draw">The Draw</a><a href="/press">Press</a><a href="/legal">Legal / Imprint</a></nav>
</header>
<section class="archive-hero">
<div class="archive-hero-copy">
<div class="eyebrow">CRTSHT / 2021—2026 / 128 PHYSICAL ORIGINALS</div>
<h1>TOGETHER BEFORE<br>WE DISPERSE.</h1>
<p class="archive-deck">128 algorithmic creatures were generated, printed, hashed and minted on Ethereum in 2021. In 2026 they finally meet in real life — then leave the wall one by one through chance.</p>
<div class="hero-actions"><a class="action action-primary" href="/draw">ENTER THE DRAW →</a><a class="action" href="/lore">READ THE LORE</a></div>
<div class="archive-facts"><span><b>128</b> originals</span><span><b>20×20</b> cm</span><span><b>3</b> draws</span><span><b>1</b> complete archive</span></div>
</div>
<a class="archive-hero-image" href="/lore" aria-label="See the CRTSHT project story"><img src="/img/About_crtsht.jpg" alt="Marco Spitzbarth with the CRTSHT installation" fetchpriority="high"><span>THE WORK IS PHYSICAL. THE META IS NOT. →</span></a>
</section>
<section class="archive-bridge">
<div><span class="eyebrow">THE MECHANISM</span><strong>YOU CHOOSE TO OWN ONE.<br>CHANCE CHOOSES WHICH.</strong></div>
<div class="archive-steps"><span><b>01</b> Browse all 128</span><span><b>02</b> Reserve a voucher</span><span><b>03</b> Draw a number</span><span><b>04</b> Take the original home</span></div>
</section>
<div class="collection-heading"><div><span class="eyebrow">THE ARCHIVE</span><h2>ALL 128 / STILL COMPLETE HERE.</h2><div class="archive-state" aria-label="Current dispersal state"><span><b>128</b> GENERATED</span><span><b><?= $dispersedCount ?></b> DISPERSED</span><span><b><?= $remainingCount ?></b> STILL HERE</span></div></div><a href="/draw">GET YOUR SHIT. DONE. →</a></div>
<section class="grid">
<?php for ($id=1; $id<=CRTSHT_TOTAL; $id++): $meta=crt_metadata($id); if(!$meta) continue; $img=crt_artwork($id); $title=crt_title($id,$meta); $aboveFold=$id<=12; $assignment=$dispersed[$id]??null; $isDispersed=is_array($assignment); $assignedAt=$isDispersed?trim((string)($assignment['AssignedAt']??'')):''; $drawBatch=$isDispersed?trim((string)($assignment['DrawBatch']??'')):''; ?>
<a class="card<?= $isDispersed ? ' is-dispersed' : '' ?>" href="/crtsht/<?= $id ?>"<?= $isDispersed ? ' data-state="dispersed"' : '' ?>>
<div class="card-art"><?php if($img): ?><img <?= $aboveFold ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"' ?> decoding="async" src="<?= crt_e($img) ?>" alt="<?= crt_e($title) ?>"><?php endif; ?><?php if($isDispersed): ?><span class="dispersed-mark">DISPERSED</span><?php endif; ?></div>
<div class="num"><span><?= crt_e($title) ?></span><span><?= $id ?>/128</span></div>
<?php if($isDispersed): ?><div class="dispersed-meta"><?= $drawBatch !== '' ? 'DRAW ' . crt_e($drawBatch) : 'DRAWN' ?><?php if($assignedAt !== ''): ?> · <?= crt_e(date('d.m.y', strtotime($assignedAt))) ?><?php endif; ?></div><?php endif; ?>
</a>
<?php endfor; ?>
</section>
<section class="legal-strip" aria-label="Legal and merchant information">
<div><a href="/legal"><strong>LEGAL / IMPRINT →</strong></a></div>
<div class="legal-meta"><strong><?= crt_e($legalOperator) ?></strong><?php if($legalCompany !== '' && $legalName !== ''): ?> · <?= crt_e($legalName) ?><?php endif; ?><br><?= crt_e($legalAddr) ?> · <?= crt_e($legalCity) ?> · <?= crt_e($legalCountry) ?><?php if($legalEmail !== ''): ?><br><a href="mailto:<?= crt_e($legalEmail) ?>"><?= crt_e($legalEmail) ?></a><?php endif; ?><?php if($legalPhone !== ''): ?><?= $legalEmail !== '' ? ' · ' : '<br>' ?><?= crt_e($legalPhone) ?><?php endif; ?><br>Physical artworks · prices in CHF · Switzerland available as delivery destination.</div>
</section>
<footer class="footer"><span>CRTSHT / <a href="https://ibulla.com" target="_blank" rel="noopener">iBulla</a></span><span><a href="/press">PRESS</a> · <a href="/legal">LEGAL / IMPRINT</a> · The shit is real. The archive is meta.</span></footer>
<a class="mobile-draw-cta" href="/draw">ENTER THE DRAW <span>→</span></a>
</main>
<script>
document.querySelectorAll('.card').forEach(card=>{
  card.addEventListener('pointerdown',()=>card.classList.add('is-pressed'),{passive:true});
  card.addEventListener('pointercancel',()=>card.classList.remove('is-pressed'),{passive:true});
});
const header=document.querySelector('header');
const floatingDraw=document.querySelector('.mobile-draw-cta');
if(header&&floatingDraw){
  const visibility=new IntersectionObserver(([entry])=>{
    floatingDraw.classList.toggle('is-visible',!entry.isIntersecting);
  },{threshold:0});
  visibility.observe(header);
}
</script>
</body></html>