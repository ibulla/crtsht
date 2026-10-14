<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

header('X-Robots-Tag: index, follow', true);

$pressDir = __DIR__ . '/press';
$imageDir = $pressDir . '/images';
$pressEmail = crt_env('CRTSHT_PRESS_EMAIL');
if ($pressEmail === '') $pressEmail = crt_env('CRTSHT_LEGAL_EMAIL');
$dispersedData = crt_draw_assignments();
$dispersalAvailable = is_array($dispersedData);
$dispersedCount = $dispersalAvailable ? count($dispersedData) : null;
$remainingCount = $dispersalAvailable ? CRTSHT_TOTAL - $dispersedCount : null;
$pressNow = new DateTimeImmutable('now', new DateTimeZone('Europe/Zurich'));
if ($pressNow < new DateTimeImmutable('2026-10-17 19:00:00', new DateTimeZone('Europe/Zurich'))) {
    $pressSchedule = [['NEXT DRAW','17.10.2026'],['FINAL DRAW','31.10.2026']];
} elseif ($pressNow < new DateTimeImmutable('2026-10-31 19:00:00', new DateTimeZone('Europe/Zurich'))) {
    $pressSchedule = [['FINAL DRAW','31.10.2026']];
} elseif ($pressNow < new DateTimeImmutable('2026-11-01 00:00:00', new DateTimeZone('Europe/Zurich'))) {
    $pressSchedule = [['FINAL DRAW LIVE','LATE ENTRY OPEN']];
} else {
    $pressSchedule = [['DRAW PERIOD','ENDED']];
}

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
<title>Aktuell verschwinden 128 Bilder aus einem Zürcher Kunstraum! / CRTSHT Press</title>
<meta name="description" content="In Zürich verschwinden gerade 128 Bilder aus einem Kunstraum. Nicht gestohlen. Gezogen. Press material for SHIT HAPPENS! — CRTSHT / Marco Spitzbarth (iBulla), Zürich 2026.">
<meta property="og:type" content="article">
<meta property="og:title" content="Aktuell verschwinden 128 Bilder aus einem Zürcher Kunstraum!">
<meta property="og:description" content="Nicht gestohlen. Gezogen. 128 physische Originale werden bei SHIT HAPPENS! nach und nach aus einem Zürcher Kunstraum verteilt.">
<meta property="og:url" content="https://cryptoshit.info/press">
<?php if($hero): ?><meta property="og:image" content="https://cryptoshit.info<?=crt_e($hero['url'])?>"><?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<link rel="canonical" href="https://cryptoshit.info/press">
<link rel="stylesheet" href="/site.css?v=10">
<style>
.press{max-width:1280px}
.press-hero-image{margin:0 0 calc(var(--pad)*.9);border:1px solid var(--line);overflow:hidden;background:rgba(255,255,255,.2)}
.press-hero-image img{display:block;width:100%;aspect-ratio:925/385;object-fit:cover}
.press-hero{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(260px,.65fr);gap:var(--pad);align-items:end;padding:12px 0 calc(var(--pad)*1.15)}
.press-hero h1{font-size:clamp(42px,7.2vw,108px);line-height:.84;letter-spacing:-.075em;margin:.08em 0 .2em;max-width:13ch}
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
<div class="eyebrow">PRESS RELEASE / SHIT HAPPENS! / 14.10.2026</div>
<h1>AKTUELL VERSCHWINDEN 128 BILDER AUS EINEM ZÜRCHER KUNSTRAUM!</h1>
<p class="lead"><strong>Nicht gestohlen. Gezogen.</strong> In der Ausstellung SHIT HAPPENS! verschwinden 128 physische Originale nach und nach von einer Wand. Was zurückbleibt, wird Teil des Werks.</p>
</div>
<div class="aside">
<strong>CRTSHT / MARCO SPITZBARTH (iBulla)</strong>
2021—2026<br>
25.09—31.10.2026<br>
ENDSAFTER · Hardturmstrasse 307 · Zürich<br><br>
<?php if($dispersalAvailable): ?><strong><?= $dispersedCount ?> / 128 DISPERSED</strong><?php else: ?><strong>DISPERSAL STATUS UNAVAILABLE</strong><?php endif; ?>
<?php foreach($pressSchedule as [$label,$value]): ?><?= crt_e($label) ?> · <?= crt_e($value) ?><br><?php endforeach; ?>
</div>
</section>

<section class="press-facts" aria-label="CRTSHT facts">
<div class="press-fact"><span>Project</span><strong>CRTSHT / CryptoShit</strong></div>
<div class="press-fact"><span>Artist</span><strong>Marco Spitzbarth (iBulla)</strong></div>
<div class="press-fact"><span>Created / Recovered</span><strong>2021 / 2026</strong></div>
<div class="press-fact"><span>Current state</span><strong><?php if($dispersalAvailable): ?><?= $dispersedCount ?> dispersed · <?= $remainingCount ?> still here<?php else: ?>status temporarily unavailable<?php endif; ?></strong></div>
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
<h3>Aktuell verschwinden 128 Bilder aus einem Zürcher Kunstraum!</h3>
<p class="standfirst">Die Serie CRTSHT wurde 2021 vom Zürcher Künstler Marco Spitzbarth (iBulla) algorithmisch generiert und mit allerlei kryptischen Informationen in der realen und virtuellen Welt verankert. Wer «down the rabbit hole» will, bekommt mit dieser Arbeit mehr als ein Unikat für die Wand, nämlich gleich einen eigenen Schlüssel in die Hand.</p>
<p>Fünf Jahre später sind die 128 Arbeiten erstmals gemeinsam im Kunstraum ENDSAFTER in Zürich zu sehen. Allerdings nicht für lange.</p>
<p>Die Arbeiten werden in drei Ziehungen, den sogenannten «Draws», verteilt. Wer online oder vor Ort ein Los erstanden hat, kommt früher oder später zum Zug und erhält sein eigenes Original CRTSHT. Jedes Los gewinnt. Wer anwesend ist, zieht selbst, alle anderen überlassen ihr Glück einer unschuldigen Hand.</p>
<p>Mit jeder Ziehung verschwindet ein weiteres Bild von der Wand. An seiner Stelle bleibt eine nummerierte Relief-Plakette zurück. Die Ausstellung löst sich nach und nach auf.</p>
<p>Doch damit ist die Sache noch lange nicht erledigt.</p>
<p>Zu jedem 20 × 20 cm grossen Originalprint gehört ein Inlay mit allerlei Artefakten und Anspielungen auf die Zeit des grossen NFT-Hypes um 2021. Auf der Rückseite finden sich ein Mooncake und vier Seedwörter, mit denen sich ein Orakel öffnen lässt. Dazu kommt ein versiegelter Private Key. Alles zusammen wandert in eine Pizzaschachtel und verlässt den Kunstraum mit seinem neuen Halter. What?</p>
<p>Mit CRTSHT rüttelt Marco Spitzbarth an den kryptischen Siegeln des Unverständnisses, die sich vor Technologien wie Blockchain und Non-Fungible Tokens auftürmen. Wer sich darauf einlässt, entdeckt eine Welt aus virtuellen Protokollen, mathematischen Gewissheiten und digitalen Versprechen, die sich auf eigentümliche Weise mit der physischen Welt verweben.</p>
<p><strong>Wer selbst einen CryptoShit besitzen möchte, kann sich jetzt noch für die nächste Ziehung anmelden.</strong></p>
<p>Am einfachsten online: <a href="/draw">https://cryptoshit.info/draw</a></p>
<p><strong>SHIT HAPPENS! – CRTSHT</strong><br>Marco Spitzbarth (iBulla)<br>25. September – 31. Oktober 2026<br>ENDSAFTER, Hardturmstrasse 307, Zürich</p>
<p>Nächste Draws: 17. und 31. Oktober 2026</p>
<p>Pressebilder und weitere Informationen: <a href="/press">https://cryptoshit.info/press</a></p>
<button class="copy-action" type="button">COPY PRESS TEXT / DE</button>
</article>
</section>

<section class="press-section" id="en">
<h2>Press text<br>EN</h2>
<article class="press-copy" data-copy-source>
<h3>128 Pictures Are Currently Disappearing from an Art Space in Zurich!</h3>
<p class="standfirst">The CRTSHT series was generated algorithmically in 2021 by Zurich-based artist Marco Spitzbarth (iBulla), then anchored in both the real and virtual worlds with all sorts of cryptic information. Anyone wanting to go “down the rabbit hole” gets more than a one-of-a-kind work for the wall – they get a key of their own.</p>
<p>Five years later, all 128 works are on display together for the first time at ENDSAFTER, an art space in Zurich. But not for long.</p>
<p>The works are distributed through three drawings, known as “draws”. Anyone who has bought a ticket online or on site will sooner or later get their turn and receive their own original CRTSHT. Every ticket wins. Those who are there draw for themselves; everyone else leaves their luck to an innocent hand.</p>
<p>With each draw, another picture disappears from the wall. A numbered relief plaque remains in its place. Little by little, the exhibition dissolves.</p>
<p>But that is far from the end of it.</p>
<p>Each 20 × 20 cm original print comes with an inlay full of artefacts and references to the great NFT hype of 2021. On the back are a mooncake and four seed words that unlock an oracle. There is also a sealed private key. Everything goes into a pizza box and leaves the art space with its new holder. What?</p>
<p>With CRTSHT, Marco Spitzbarth rattles the cryptic seals of incomprehension that have built up around technologies such as blockchain and non-fungible tokens. Those who venture inside discover a world of virtual protocols, mathematical certainties and digital promises intertwined with the physical world in peculiar ways.</p>
<p><strong>Anyone who wants to own a CryptoShit can still register for the next draw.</strong></p>
<p>The easiest way is online: <a href="/draw">https://cryptoshit.info/draw</a></p>
<p><strong>SHIT HAPPENS! – CRTSHT</strong><br>Marco Spitzbarth (iBulla)<br>25 September – 31 October 2026<br>ENDSAFTER, Hardturmstrasse 307, Zurich</p>
<p>Next draws: 17 and 31 October 2026</p>
<p>Press images and further information: <a href="/press">https://cryptoshit.info/press</a></p>
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
