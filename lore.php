<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
$sampleIds = [1,16,32,48,64,80,96,128];
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>The Lore / CRTSHT</title>
<meta name="description" content="The lore behind CRTSHT: 128 creatures, their mooncakes, hashes, wall and draw.">
<link rel="stylesheet" href="/site.css?v=1">
</head>
<body><main class="wrap lore">
<header>
<a class="brand" href="/">CRTSHT</a>
<nav class="nav"><a href="/">Archive</a><a href="/lore" aria-current="page">The Lore</a><a href="/oracle">The Oracle</a></nav>
</header>
<section class="intro">
<div class="eyebrow">THE LORE</div>
<h1>A small mythology for a very serious blockchain.</h1>
<p>CRTSHT started in 2021, when almost every image on the internet suddenly wanted a wallet.</p>
</section>

<section class="chapter">
<h2>The creatures</h2>
<div class="prose">
<p>One hundred and twenty-eight square beings appeared. Some came back as <strong>Kin no unko</strong>. Dr. Slurp wandered in. The Ice-Emoji saga froze mid-expression. Eyes changed, colours drifted, accessories accumulated, backgrounds mutated.</p>
<p>They share a family resemblance, but no two are the same. Each was printed once at 20 × 20 cm and given a hexadecimal name: <strong>0x0001</strong> through <strong>0x0080</strong>.</p>
<div class="gallery">
<?php foreach($sampleIds as $id): $img=crt_artwork($id); $meta=crt_metadata($id); if(!$img||!$meta) continue; ?>
<a href="/crtsht/<?= $id ?>"><img loading="lazy" src="<?= crt_e($img) ?>" alt="<?= crt_e(crt_title($id,$meta)) ?>"></a>
<?php endforeach; ?>
</div>
</div>
</section>

<section class="chapter">
<h2>The wall</h2>
<div class="prose">
<p>The family was always supposed to meet in real life. An exhibition plan from 2021 survived even while the project itself stayed unfinished.</p>
<p>Before the works scatter, the 128 originals can finally occupy the wall that was already waiting for them.</p>
<img class="plan" src="/Exhibition-plan.png" alt="Original CRTSHT exhibition plan">
<p class="caption">EXHIBITION-PLAN.PNG / archived with the original project</p>
</div>
</section>

<section class="chapter">
<h2>The mooncake</h2>
<div class="prose">
<p>Every CRTSHT has a second body: a printed mooncake. It looks like an object from the same strange universe, but it also behaves like a checksum.</p>
<p>The cake carries the work's hex ID, Ethereum address, print hash and a short internal POO hash. The cake image itself was archived alongside the token data. A hash points to an image carrying another hash.</p>
<div class="cake-pair">
<?php foreach([1,64] as $id): $cake=crt_cake($id); if($cake): ?><a href="/crtsht/<?= $id ?>"><img src="<?= crt_e($cake) ?>" alt="Mooncake <?= $id ?>"></a><?php endif; endforeach; ?>
</div>
<div class="codebox">
HEX ID → 0x0001<br>
PRINT → SHA-256 fingerprint<br>
WALLET → Ethereum public address<br>
POO → short internal hash<br>
IMAGE → IPFS content identifier
</div>
<p>The cake is not decoration and not quite a certificate. It is closer to an amulet with too much metadata.</p>
</div>
</section>

<section class="chapter">
<h2>The key</h2>
<div class="prose">
<p>On the back of each physical work sit four visible words from a 24-word recovery phrase. The remaining words and private material stay sealed.</p>
<p>Four words are enough to wake the archive, but not enough to open the wallet. They belong to the object as a small ritual of possession.</p>
<p><a href="/oracle">Ask The Oracle →</a></p>
</div>
</section>

<section class="chapter">
<h2>The draw</h2>
<div class="prose">
<p>The complete collection is public. The assignment is not.</p>
<p>Everyone who enters receives one CRTSHT. Chance only decides which identity leaves the wall with them. No one picks the prettiest one first; the draw does the choosing.</p>
<div class="ritual">
<div><strong>SEE</strong><p>All 128 works and their records remain visible.</p></div>
<div><strong>DRAW</strong><p>A number is pulled from the box.</p></div>
<div><strong>OWN</strong><p>The original, mooncake and sealed key leave together in a pizza box.</p></div>
<div><strong>UNLOCK</strong><p>The four visible words open the object's fortune.</p></div>
</div>
<p style="margin-top:20px">The draw chooses identity, not whether someone gets a work. With every draw the wall gets emptier. The archive stays full.</p>
</div>
</section>

<footer class="footer"><span>CRTSHT / THE LORE</span><span>2021 → 2026</span></footer>
</main></body></html>
