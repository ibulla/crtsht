<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>CRTSHT / 128</title>
<meta name="description" content="CRTSHT — 128 unique physical works generated, printed and minted on Ethereum in 2021. Reassembled in 2026.">
<link rel="stylesheet" href="/site.css?v=1">
</head>
<body><main class="wrap">
<header>
<a class="brand" href="/">CRTSHT</a>
<nav class="nav"><a href="/" aria-current="page">Archive</a><a href="/lore">The Lore</a><a href="/oracle">The Oracle</a></nav>
</header>
<section class="intro">
<h1>128 works.<br>Still here.</h1>
<p>Generated, printed and minted on Ethereum in 2021. Reassembled in 2026.</p>
<p class="quiet">20 × 20 cm · one physical original each · look first, the rest is lore.</p>
</section>
<section class="grid">
<?php for ($id=1; $id<=CRTSHT_TOTAL; $id++): $meta=crt_metadata($id); if(!$meta) continue; $img=crt_artwork($id); $title=crt_title($id,$meta); ?>
<a class="card" href="/crtsht/<?= $id ?>">
<div><?php if($img): ?><img loading="lazy" src="<?= crt_e($img) ?>" alt="<?= crt_e($title) ?>"><?php endif; ?></div>
<div class="num"><span><?= crt_e($title) ?></span><span><?= $id ?>/128</span></div>
</a>
<?php endfor; ?>
</section>
<footer class="footer"><span>CRTSHT / iBulla</span><span>The wall may empty. The archive will not.</span></footer>
</main></body></html>
