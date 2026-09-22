<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

header('X-Robots-Tag: index, follow', true);

$pressDir = __DIR__ . '/press';
$imageDir = $pressDir . '/images';
$pressEmail = crt_env('CRTSHT_PRESS_EMAIL');
if ($pressEmail === '') $pressEmail = crt_env('CRTSHT_LEGAL_EMAIL');

function press_size(string $path): string {
    $bytes = @filesize($path);
    if (!is_int($bytes) || $bytes < 0) return '';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 0) . ' KB';
    return $bytes . ' B';
}

function press_label(string $filename): string {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $upper = strtoupper($name);
    if (str_contains($upper, 'HANDOUT')) return 'HANDOUT / DE + EN';
    if (str_contains($upper, 'PRESS_RELEASE_DE') || str_contains($upper, 'PRESSETEXT_DE')) return 'PRESS RELEASE / DE';
    if (str_contains($upper, 'PRESS_RELEASE_EN') || str_contains($upper, 'PRESSETEXT_EN')) return 'PRESS RELEASE / EN';
    if (str_ends_with(strtoupper($filename), '.ZIP')) return 'COMPLETE PRESS KIT / ZIP';
    return strtoupper(str_replace(['_', '-'], ' ', $name));
}

function press_caption(string $filename): string {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $name = preg_replace('/^CRTSHT[_-]?\d*[_-]?/i', '', $name) ?? $name;
    $name = trim(str_replace(['_', '-'], ' ', $name));
    return $name !== '' ? $name : 'CRTSHT press image';
}

$downloads = [];
if (is_dir($pressDir)) {
    $files = scandir($pressDir) ?: [];
    foreach ($files as $filename) {
        if ($filename === '.' || $filename === '..' || str_starts_with($filename, '.')) continue;
        $path = $pressDir . '/' . $filename;
        if (!is_file($path)) continue;
        if (!preg_match('/\.(pdf|txt|zip)$/i', $filename)) continue;
        if (strcasecmp($filename, 'README.txt') === 0 || strcasecmp($filename, 'README.md') === 0) continue;
        $downloads[] = [
            'name' => $filename,
            'label' => press_label($filename),
            'size' => press_size($path)
        ];
    }
    usort($downloads, static function(array $a, array $b): int {
        $priority = static function(string $name): int {
            $u = strtoupper($name);
            if (str_contains($u, 'HANDOUT')) return 1;
            if (str_contains($u, 'PRESS_RELEASE_DE') || str_contains($u, 'PRESSETEXT_DE')) return 2;
            if (str_contains($u, 'PRESS_RELEASE_EN') || str_contains($u, 'PRESSETEXT_EN')) return 3;
            if (str_ends_with($u, '.ZIP')) return 9;
            return 5;
        };
        return [$priority($a['name']), $a['name']] <=> [$priority($b['name']), $b['name']];
    });
}

$hero = null;
foreach ([
    ['dir' => $imageDir, 'url' => '/press/images/'],
    ['dir' => $pressDir, 'url' => '/press/']
] as $heroLocation) {
    if (!is_dir($heroLocation['dir'])) continue;
    $heroFiles = scandir($heroLocation['dir']) ?: [];
    foreach ($heroFiles as $filename) {
        if (!preg_match('/^CRTSHT[_-]?Hero\.(jpe?g|png|webp)$/i', $filename)) continue;
        $hero = [
            'name' => $filename,
            'url' => $heroLocation['url'] . rawurlencode($filename)
        ];
        break 2;
    }
}

$images = [];
if (is_dir($imageDir)) {
    $files = scandir($imageDir) ?: [];
    foreach ($files as $filename) {
        if ($filename === '.' || $filename === '..' || str_starts_with($filename, '.')) continue;
        $path = $imageDir . '/' . $filename;
        if (!is_file($path) || !preg_match('/\.(jpe?g|png|webp)$/i', $filename)) continue;
        if (preg_match('/^CRTSHT[_-]?Hero\.(jpe?g|png|webp)$/i', $filename)) continue;
        $images[] = [
            'name' => $filename,
            'caption' => press_caption($filename),
            'size' => press_size($path)
        ];
    }
    usort($images, static fn(array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));
}
?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Press / SHIT HAPPENS! / CRTSHT</title>
<meta name="description" content="Press material for SHIT HAPPENS! — CRTSHT / Marco Spitzbarth (iBulla), 2021–2026. 128 physical works, Ethereum provenance, ENDSAFTER Zürich.">
<link rel="stylesheet" href="/site.css?v=8">
<style>
.press{max-width:1280px}
.press-hero-image{margin:0 0 calc(var(--pad)*.9);border:1px solid var(--line);overflow:hidden;background:rgba(255,255,255,.2)}
.press-hero-image img{display:block;width:100%;aspect-ratio:925/385;object-fit:cover}
.press-hero{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(260px,.65fr);gap:var(--pad);align-items:end;padding:12px 0 calc(var(--pad)*1.15)}
.press-hero h1{font-size:clamp(58px,11vw,162px);line-height:.76;letter-spacing:-.085em;margin:.08em 0 .16em;max-width:8ch}
.press-hero .lead{font-size:clamp(15px,1.45vw,20px);line-height:1.5;max-width:58ch;margin:0}
.press-hero .aside{font-size:11px;line-height:1.7;text-transform:uppercase;letter-spacing:.06em}
.press-hero .aside strong{display:block;font-size:13px;color:var(--fg);margin-bottom:8px}
.press-facts{display:grid;grid-template-columns:repeat(4,1fr);border-top:1px solid var(--fg);border-left:1px solid var(--line);margin-bottom:calc(var(--pad)*1.25)}
.press-fact{padding:12px;border-right:1px solid var(--line);border-bottom:1px solid var(--line);min-height:78px}
.press-fact span{display:block;font-size:9px;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:7px}
.press-fact strong{display:block;font-size:12px;line-height:1.45}
.press-section{display:grid;grid-template-columns:minmax(170px,.45fr) minmax(0,1.55fr);gap:var(--pad);border-top:1px solid var(--fg);padding:24px 0 calc(var(--pad)*1.2)}
.press-section h2{font-size:clamp(26px,3.8vw,58px);line-height:.92;letter-spacing:-.06em;margin:0}
.press-copy{max-width:780px}
.press-copy h3{font-size:clamp(22px,3vw,42px);line-height:.98;letter-spacing:-.045em;margin:0 0 22px}
.press-copy p{font-size:clamp(14px,1.1vw,17px);line-height:1.62;margin:0 0 1em}
.press-copy .standfirst{font-size:clamp(17px,1.4vw,21px);line-height:1.5}
.press-copy .copy-action{font:inherit;font-size:10px;text-transform:uppercase;letter-spacing:.08em;border:1px solid var(--fg);background:transparent;padding:8px 11px;cursor:pointer;margin:8px 0 22px}
.press-copy .copy-action:hover,.press-copy .copy-action:focus-visible{background:var(--fg);color:var(--bg)}
.downloads{border-top:1px solid var(--line)}
.download-row{display:grid;grid-template-columns:minmax(0,1fr) auto auto;gap:18px;align-items:center;border-bottom:1px solid var(--line);padding:12px 0;font-size:11px}
.download-row strong{font-size:12px}
.download-row .size{color:var(--muted);white-space:nowrap}
.download-row a{text-decoration:underline;white-space:nowrap}
.press-empty{font-size:11px;line-height:1.6;color:var(--muted);border-top:1px solid var(--line);padding-top:12px}
.press-gallery{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.press-image{border:1px solid var(--line);background:rgba(255,255,255,.2)}
.press-image a{display:block}
.press-image img{display:block;width:100%;aspect-ratio:4/3;object-fit:cover;filter:grayscale(.2);transition:filter .15s}
.press-image:hover img{filter:none}
.press-image-meta{display:flex;justify-content:space-between;gap:16px;padding:9px 10px;border-top:1px solid var(--line);font-size:9px;line-height:1.45;text-transform:uppercase;letter-spacing:.05em}
.press-image-meta span:last-child{color:var(--muted);white-space:nowrap}
.press-contact{font-size:12px;line-height:1.7}
.press-contact strong{font-size:14px}
.press-quote{font-size:clamp(28px,4.2vw,64px)!important;line-height:.94!important;letter-spacing:-.06em;max-width:15ch;margin:28px 0!important}
@media(max-width:820px){.press-hero,.press-section{grid-template-columns:1fr}.press-facts{grid-template-columns:repeat(2,1fr)}.press-gallery{grid-template-columns:1fr}}
@media(max-width:520px){.press-facts{grid-template-columns:1fr}.download-row{grid-template-columns:1fr auto}.download-row .size{display:none}}
@media print{.nav,.copy-action,.press-gallery,.downloads{display:none!important}.press{max-width:none}.press-section{break-inside:avoid}.press-hero h1{font-size:72pt}}
</style>
</head>
<body><main class="wrap press">
<header>
<a class="brand" href="/">CR¥P70$H!7.1NF0</a>
<nav class="nav"><a href="/">Archive</a><a href="/lore">The Lore</a><a href="/oracle">The Oracle</a><a href="/draw">The Draw</a><a href="/press" aria-current="page">Press</a><a href="/legal">Legal</a></nav>
</header>

<?php if($hero): ?>
<figure class="press-hero-image"><img src="<?=crt_e($hero['url'])?>" alt="CRTSHT / SHIT HAPPENS! — Marco Spitzbarth (iBulla)" fetchpriority="high" decoding="async"></figure>
<?php endif; ?>

<section class="press-hero">
<div>
<div class="eyebrow">PRESS / EXHIBITION MATERIAL / 2026</div>
<h1>SHIT<br>HAPPENS!</h1>
<p class="lead">128 algorithmically generated images. 128 physical originals. 128 wallets. Created, minted and printed in 2021. Reassembled in Zürich in 2026 — together before they disperse.</p>
</div>
<div class="aside">
<strong>CRTSHT / MARCO SPITZBARTH (iBulla)</strong>
2021—2026<br>
25.09—31.10.2026<br>
ENDSAFTER · Hardturmstrasse 307 · Zürich<br><br>
3 DRAWS<br>
25.09 · 17.10 · 31.10.2026
</div>
</section>

<section class="press-facts" aria-label="CRTSHT facts">
<div class="press-fact"><span>Project</span><strong>CRTSHT / CryptoShit</strong></div>
<div class="press-fact"><span>Artist</span><strong>Marco Spitzbarth (iBulla)</strong></div>
<div class="press-fact"><span>Created / Recovered</span><strong>2021 / 2026</strong></div>
<div class="press-fact"><span>Total supply</span><strong>128 unique works</strong></div>
<div class="press-fact"><span>Physical</span><strong>20 × 20 cm · Alu-Dibond · Genuine Print</strong></div>
<div class="press-fact"><span>Network</span><strong>Ethereum · IPFS</strong></div>
<div class="press-fact"><span>Exhibition</span><strong>SHIT HAPPENS!</strong></div>
<div class="press-fact"><span>Venue</span><strong>ENDSAFTER · Zürich</strong></div>
</section>

<section class="press-section">
<h2>Downloads</h2>
<div>
<?php if($downloads): ?><div class="downloads">
<?php foreach($downloads as $file): ?>
<div class="download-row"><strong><?=crt_e($file['label'])?></strong><span class="size"><?=crt_e($file['size'])?></span><a href="/press/<?=rawurlencode($file['name'])?>" download>DOWNLOAD ↓</a></div>
<?php endforeach; ?>
</div><?php else: ?><p class="press-empty">PRESS FILES ARE BEING ASSEMBLED. The texts below are ready to quote and copy.</p><?php endif; ?>
</div>
</section>

<section class="press-section">
<h2>Press images</h2>
<div>
<?php if($images): ?><div class="press-gallery">
<?php foreach($images as $image): $url='/press/images/'.rawurlencode($image['name']); ?>
<figure class="press-image"><a href="<?=$url?>" download><img loading="lazy" decoding="async" src="<?=$url?>" alt="<?=crt_e($image['caption'])?>"></a><figcaption class="press-image-meta"><span><?=crt_e($image['caption'])?></span><span><?=crt_e($image['size'])?> · HI-RES ↓</span></figcaption></figure>
<?php endforeach; ?>
</div><?php else: ?><p class="press-empty">INSTALLATION AND PORTRAIT IMAGES WILL APPEAR HERE AS THEY ARE ADDED.</p><?php endif; ?>
</div>
</section>

<section class="press-section" id="de">
<h2>Pressetext<br>DE</h2>
<article class="press-copy" data-copy-source>
<h3>SHIT HAPPENS! — CRTSHT / Marco Spitzbarth (iBulla), 2021—2026</h3>
<p class="standfirst"><strong>128 physische Unikate aus dem NFT-Boom von 2021 treffen 2026 erstmals aufeinander.</strong>CRTSHT untersucht, wie sich digitale und physische Welten überlagern, Spuren ineinander hinterlassen und wie aus einem Zukunftsversprechen langsam ein archäologisches Artefakt wird.</p>
<p>2021, als gefühlt jedes JPEG im Internet tokenisiert wurde, entstanden 128 kleine CryptoShits. Die digitalen Bilder wurden auf Ethereum gemintet und gleichzeitig als 20 × 20 cm grosse Genuine Prints auf Alu-Dibond in die physische Welt übersetzt. Jedes Werk erhielt eine eigene Wallet, einen NFT und eine entsprechend komplizierte digitale Identität. Danach passierte fünf Jahre lang: wenig. Die Prints blieben verpackt. Die Blockchain lief weiter.</p>
<p>2026 kommen erstmals alle 128 physischen Arbeiten zusammen. Was 2021 noch als Bewegung in die andere Richtung gedacht war »vom realen Objekt ins Metaverse, vom Besitz zum Token, vom Bild zum Hash« wird heute rückwärts gelesen. Der NFT ist dabei weniger Zukunftsversprechen als <strong>Provenienz einer vergangenen Utopie</strong>: eine digitale Spur aus einer Zeit, in der Blockchain, Dezentralisierung und digitales Eigentum kurz davor schienen, alles zu verändern.</p>
<p>CRTSHT bringt diese Spur zurück an ihr physisches Objekt. Auf der Rückseite jedes Prints befinden sich vier sichtbare Seedwörter. Sie führen zu einem digitalen Oracle und öffnen einen persönlichen Fortune Cookie. Der vollständige Schlüssel bleibt versiegelt und gehört zum Werk. Bild, Wallet, NFT, Seedwörter, Mooncake, Verpackung und Blockchain-Historie bilden zusammen ein kleines archäologisches Artefakt aus dem Jahr 2021 — irgendwo zwischen Glücksbringer, Archiv, Zugangsschlüssel und unnötig komplizierter Kryptographie.</p>
<p><strong>Im Zentrum steht die Frage, wie sich physische und digitale Welten gegenseitig beeinflussen, überlagern und Spuren ineinander hinterlassen.</strong> Man muss Crypto dafür weder verstehen noch mögen. Es ist eigentlich schon Geschichte.</p>
<p>Zu Beginn der Ausstellung hängen alle 128 Arbeiten gemeinsam an einer Wand. Anschliessend werden sie in drei Draws verteilt, nicht nach Rarity, Geschmack, Marktwert oder Algorithmus. Die Teilnehmenden ziehen eine Nummer und damit ihr CRTSHT. Das gezogene Original wird von der Wand genommen, verpackt und verlässt die Ausstellung zusammen mit seiner digitalen Vergangenheit. Mit jedem Draw wird die Wand leerer.</p>
<p><strong>SHIT HAPPENS!</strong><br>25.09—31.10.2026 · ENDSAFTER · Hardturmstrasse 307 · Zürich<br>Draws: 25.09 · 17.10 · 31.10.2026 · cryptoshit.info</p>
<button class="copy-action" type="button">COPY PRESS TEXT / DE</button>
</article>
</section>

<section class="press-section" id="en">
<h2>Press text<br>EN</h2>
<article class="press-copy" data-copy-source>
<h3>SHIT HAPPENS! — CRTSHT / Marco Spitzbarth (iBulla), 2021—2026</h3>
<p class="standfirst"><strong>128 algorithmically generated images, 128 physical originals, 128 wallets.</strong> Five years after CRTSHT was minted on Ethereum and produced as Genuine Prints, the works are shown together for the first time — before being dispersed again through a draw.</p>
<p>In 2021, when almost every JPEG on the internet seemed to be getting tokenized, 128 small CryptoShits appeared. The digital images were minted on Ethereum and simultaneously translated back into the physical world as 20 × 20 cm aluminium Genuine Prints. Each work received its own wallet, an NFT and a suitably complicated digital identity. Then, for five years, very little happened. The prints remained packed away. The blockchain kept running.</p>
<p>In 2026 all 128 physical works come together for the first time. What in 2021 appeared to be a movement in the opposite direction — from the real object into the metaverse, from possession to token, from image to hash — can now be read in reverse. The NFT becomes less a promise of the future than the <strong>provenance of a past utopia</strong>: a digital trace of a moment when blockchain, decentralisation and digital ownership briefly seemed about to change everything.</p>
<p>CRTSHT reconnects that trace with its physical object. Four visible seed words are printed on the back of each work. They lead to a digital Oracle and unlock a personal Fortune Cookie. The complete key remains sealed and forms part of the work. Image, wallet, NFT, seed words, mooncake, packaging and blockchain history together form a small archaeological artefact from 2021 — somewhere between lucky charm, archive, access key and unnecessarily complicated cryptography.</p>
<p><strong>At the centre is the question of how physical and digital worlds influence and overlap with one another, leaving traces in each other.</strong> You do not need to understand crypto. You do not even have to like it. It is already history anyway.</p>
<p>At the beginning of the exhibition, all 128 works hang together on one wall. They are then dispersed across three draws — not according to rarity, taste, market value or algorithm. Participants draw a number and with it their CRTSHT. Each selected original is removed from the wall, packed and leaves the exhibition together with its digital past. With every draw, the wall becomes emptier.</p>
<p><strong>SHIT HAPPENS!</strong><br>25.09—31.10.2026 · ENDSAFTER · Hardturmstrasse 307 · Zürich<br>Draws: 25.09 · 17.10 · 31.10.2026 · cryptoshit.info</p>
<button class="copy-action" type="button">COPY PRESS TEXT / EN</button>
</article>
</section>

<section class="press-section">
<h2>Credit / Contact</h2>
<div class="press-contact">
<p><strong>Marco Spitzbarth (iBulla)</strong><br>CRTSHT / SHIT HAPPENS!<br><a href="https://cryptoshit.info">cryptoshit.info</a> · <a href="https://ibulla.com" target="_blank" rel="noopener">ibulla.com ↗</a><?php if($pressEmail!==''): ?><br><a href="mailto:<?=crt_e($pressEmail)?>"><?=crt_e($pressEmail)?></a><?php endif; ?></p>
<p>Unless otherwise noted with the supplied image files: <strong>Courtesy Marco Spitzbarth / iBulla, CRTSHT, 2026.</strong></p>
<p class="press-quote">TOGETHER BEFORE WE DISPERSE.</p>
</div>
</section>

<footer class="footer"><span>CRTSHT / PRESS · <a href="https://ibulla.com" target="_blank" rel="noopener">iBulla</a></span><span><a href="/legal">LEGAL / IMPRINT</a> · THE SHIT IS REAL. THE ARCHIVE IS META.</span></footer>
</main>
<script>
document.querySelectorAll('.copy-action').forEach(function(button){
  button.addEventListener('click', async function(){
    var source=button.closest('[data-copy-source]');
    if(!source)return;
    var clone=source.cloneNode(true);
    clone.querySelectorAll('button').forEach(function(el){el.remove();});
    var text=clone.innerText.trim();
    try{
      await navigator.clipboard.writeText(text);
      var old=button.textContent;
      button.textContent='COPIED';
      setTimeout(function(){button.textContent=old;},900);
    }catch(e){}
  });
});
</script>
</body>
</html>
