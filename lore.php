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
<meta name="description" content="The lore behind CRTSHT: 128 creatures, mooncakes, hashes, wallets, a wall and one draw.">
<link rel="stylesheet" href="/site.css?v=6">
<style>
.lore-opening{margin:0 0 calc(var(--pad)*1.4)}
.lore-opening .eyebrow{margin-bottom:12px}
.lore-opening img{display:block;width:min(100%,760px);margin:0 0 18px}
.lore-opening .fortune{font-size:clamp(28px,5vw,64px);line-height:.95;letter-spacing:-.055em;margin:0 0 18px;max-width:12ch}
.lore-opening .origin{font-size:clamp(14px,1.25vw,18px);line-height:1.55;max-width:62ch;margin:0}
.founder{display:grid;grid-template-columns:minmax(220px,.8fr) minmax(0,1.2fr);gap:var(--pad);align-items:start}
.founder img{display:block;width:100%;max-width:520px}
.founder-copy{max-width:620px}
.founder-copy .name{font-size:clamp(34px,5vw,72px);line-height:.9;letter-spacing:-.06em;margin:0 0 20px}
.founder-copy p{font-size:clamp(14px,1.25vw,18px);line-height:1.55;margin:0 0 1em}
@media(max-width:700px){.founder{grid-template-columns:1fr}.lore-opening .fortune{max-width:14ch}}
</style>
</head>
<body><main class="wrap lore">
<header>
<a class="brand" href="/">CRTSHT</a>
<nav class="nav"><a href="/">Archive</a><a href="/lore" aria-current="page">The Lore</a><a href="/oracle">The Oracle</a></nav>
</header>

<section class="lore-opening">
<div class="eyebrow">THE LORE</div>
<img src="/img/cryptoshit_question.jpg" alt="CRTSHT question mark pile" fetchpriority="high">
<p class="fortune">The internet may forget.<br>The blockchain can't. By design.</p>
<p class="origin">CRTSHT started in 2021, when images became assets, wallets became identities and permanence became a promise. One hundred and twenty-eight physical works, their mooncakes and their cryptographic traces survived the hype. In 2026 they meet again.</p>
</section>

<section class="chapter">
<h2>The creatures</h2>
<div class="prose">
<p>One hundred and twenty-eight square beings appeared. Some came back as <strong>Kin no unko</strong>. Dr. Slurp wandered in. The Ice-Emoji saga froze mid-expression. Eyes changed, colours drifted, accessories accumulated, backgrounds mutated.</p>
<p>They share a family resemblance, but no two are the same. Each was printed once at 20 × 20 cm and given a hexadecimal name: <strong>0x0001</strong> through <strong>0x0080</strong>.</p>
<div class="gallery">
<?php foreach($sampleIds as $id): $img=crt_artwork($id); $meta=crt_metadata($id); if(!$img||!$meta) continue; ?>
<a href="/crtsht/<?= $id ?>"><img loading="lazy" decoding="async" src="<?= crt_e($img) ?>" alt="<?= crt_e(crt_title($id,$meta)) ?>"></a>
<?php endforeach; ?>
</div>
</div>
</section>

<section class="chapter">
<h2>The wall</h2>
<div class="prose">
<p>The family was always supposed to meet in real life. An exhibition plan survived from the unfinished project.</p>
<p>Before the originals disperse, the complete series can finally occupy the wall that was already waiting for it.</p>
<img class="plan" src="/img/Exhibition-plan.png" alt="Original CRTSHT exhibition plan" loading="lazy" decoding="async">
<p class="caption">EXHIBITION-PLAN.PNG / original project file</p>
</div>
</section>

<section class="chapter">
<h2>The mooncake</h2>
<div class="prose">
<p>Every CRTSHT has a second body: a mooncake. The physical print shows the creature; the token shows the cake.</p>
<p>The mooncake image is the image stored on IPFS and referenced by the 2021 NFT metadata. Around it sits the rest of the system: hex identity, Ethereum wallet, print fingerprint, token record and a short internal POO hash.</p>
<div class="cake-pair">
<?php foreach([1,64] as $id): $cake=crt_cake($id); if($cake): ?><a href="/crtsht/<?= $id ?>"><img loading="lazy" decoding="async" src="<?= crt_e($cake) ?>" alt="Mooncake <?= $id ?>"></a><?php endif; endforeach; ?>
</div>
<div class="codebox">
PRINT → SHA-256 fingerprint<br>
HEX ID → 0x0001<br>
WALLET → Ethereum public address<br>
TOKEN IMAGE → IPFS CID<br>
METADATA → IPFS CID<br>
POO → short internal hash
</div>
<p>The cake is not decoration and not quite a certificate. It is closer to an amulet with too much metadata.</p>
</div>
</section>

<section class="chapter">
<h2>The key</h2>
<div class="prose">
<p>Each physical original carries a public wallet address and four visible words from its recovery phrase. The complete 24-word seed and private material remain sealed on the back.</p>
<p>The four visible words do not unlock the wallet. They unlock something much less useful: a private fortune belonging to that particular CRTSHT.</p>
<p><a href="/oracle">Ask The Oracle →</a></p>
</div>
</section>

<section class="chapter">
<h2>The draw</h2>
<div class="prose">
<p>The complete collection is public. The assignment is not.</p>
<p>Everyone who enters receives one CRTSHT. Chance decides which identity leaves the wall with them.</p>
<div class="ritual">
<div><strong>SEE</strong><p>All 128 works and their records remain visible.</p></div>
<div><strong>DRAW</strong><p>A number is pulled from the box.</p></div>
<div><strong>OWN</strong><p>The original, mooncake and sealed key leave together in a pizza box.</p></div>
<div><strong>UNLOCK</strong><p>The four visible words open the object's fortune.</p></div>
</div>
<p style="margin-top:20px">With every draw the wall gets emptier. The archive stays full.</p>
</div>
</section>

<section class="chapter">
<h2>The founder</h2>
<div class="founder">
<img src="/img/About_crtsht.jpg" alt="Marco Spitzbarth holding a physical CRTSHT print" loading="lazy" decoding="async">
<div class="founder-copy">
<div class="eyebrow">MARCO SPITZBARTH / iBULLA</div>
<p class="name">Shit happens.</p>
<p>CRTSHT was initiated in Zürich in 2021 by artist Marco Spitzbarth under <a href="https://ibulla.com" target="_blank" rel="noopener">iBulla.com ↗</a>, shaped by curiosity around digital ownership, provenance and the new rituals forming around NFTs.</p>
<p>The works were made, printed and minted. Wallets, hashes, mooncakes and sealed keys became part of the same system. The project itself remained unfinished — while its blockchain record quietly stayed intact.</p>
<p>A fortunate chain of circumstances brought CRTSHT back into view. What started in 2021 can now finally come together in real life.</p>
</div>
</div>
</section>

<footer class="footer"><span>CRTSHT / THE LORE</span><span>2021 → 2026</span></footer>
</main></body></html>