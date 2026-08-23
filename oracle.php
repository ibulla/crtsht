<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

$fortuneFile = __DIR__ . '/inc/fortunes.php';
$fortuneMap = is_file($fortuneFile) ? require $fortuneFile : [];
if (!is_array($fortuneMap)) $fortuneMap = [];

function crt_oracle_fortune(int $id, string $key, array $fortuneMap): string {
    $custom = trim((string)($fortuneMap[$id] ?? ''));
    return $custom !== '' ? $custom : crt_fortune($key, $id);
}

$words = ['', '', '', ''];
$matches = [];
$searched = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searched = true;
    $words = [
        (string)($_POST['w1'] ?? ''),
        (string)($_POST['w2'] ?? ''),
        (string)($_POST['w3'] ?? ''),
        (string)($_POST['w4'] ?? '')
    ];
    $normalized = crt_normalize_words($words);
    if (count(array_filter($normalized, fn($w) => $w !== '')) !== 4) {
        $error = 'The Oracle needs four words.';
    } else {
        $matches = crt_oracle_matches($words);
        if (!$matches) $error = 'Nothing answered.';
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>The Oracle / CRTSHT</title>
<meta name="description" content="Four visible seed words are enough to find a CRTSHT mooncake. The remaining twenty stay sealed.">
<link rel="stylesheet" href="/site.css?v=1">
</head>
<body><main class="wrap oracle">
<header>
<a class="brand" href="/">CRTSHT</a>
<nav class="nav"><a href="/">Archive</a><a href="/lore">The Lore</a><a href="/oracle" aria-current="page">The Oracle</a></nav>
</header>
<section class="intro">
<div class="eyebrow">THE ORACLE</div>
<h1>Four words.<br>One mooncake.</h1>
<p>Every physical CRTSHT carries four visible words from its 24-word recovery phrase. Enter them here. The Oracle tries them against the entire collection.</p>
<p class="quiet">The other twenty words remain sealed on the object. They are neither requested nor revealed here.</p>
<form class="oracle-form" method="post" action="/oracle" autocomplete="off">
<input name="w1" placeholder="1. word" value="<?= crt_e($words[0]) ?>" required>
<input name="w2" placeholder="2. word" value="<?= crt_e($words[1]) ?>" required>
<input name="w3" placeholder="3. word" value="<?= crt_e($words[2]) ?>" required>
<input name="w4" placeholder="4. word" value="<?= crt_e($words[3]) ?>" required>
<button type="submit">ASK</button>
</form>
<?php if($error !== ''): ?><div class="error"><?= crt_e($error) ?></div><?php endif; ?>
</section>

<?php foreach($matches as $match): $id=(int)$match['id']; $cake=crt_cake($id); ?>
<section class="answer">
<div><?php if($cake): ?><img src="<?= crt_e($cake) ?>" alt="Mooncake <?= $id ?>"><?php endif; ?></div>
<div>
<div class="eyebrow">THE COLLECTION ANSWERS</div>
<h2 style="font-size:clamp(30px,5vw,64px);letter-spacing:-.055em;margin:6px 0 14px"><?= crt_e((string)$match['title']) ?></h2>
<p class="fortune">“<?= crt_e(crt_oracle_fortune($id, (string)$match['key'], $fortuneMap)) ?>”</p>
<p><a href="/crtsht/<?= $id ?>">Open the record →</a></p>
</div>
</section>
<?php endforeach; ?>

<footer class="footer"><span>CRTSHT / THE ORACLE</span><span>4 visible · 20 sealed</span></footer>
</main></body></html>