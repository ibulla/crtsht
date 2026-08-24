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
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        if (!$matches) $error = 'Shit, something went wrong.';
    }
}
$revealed = count($matches) > 0;
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>The Oracle / CRTSHT</title>
<meta name="description" content="Four words open one CRTSHT mooncake.">
<link rel="stylesheet" href="/site.css?v=6">
<style>
.oracle-system{border:1px solid var(--fg);margin-top:24px;background:rgba(255,255,255,.18)}
.oracle-system-bar{display:flex;justify-content:space-between;gap:18px;padding:7px 9px;border-bottom:1px solid var(--fg);font-size:10px;text-transform:uppercase;letter-spacing:.08em;flex-wrap:wrap}
.oracle-system-body{padding:11px 9px;font-size:10px;line-height:1.75;letter-spacing:.04em}
.oracle-system-body span{display:block}
.oracle-system-body b{font-weight:500;display:inline-block;min-width:126px}
.oracle-note{font-size:12px!important;color:var(--muted);max-width:58ch}
.oracle-reveal .oracle-system{width:min(100%,620px);margin-top:28px}
</style>
</head>
<body><main class="wrap oracle<?= $revealed ? ' oracle-revealed' : '' ?>">
<header>
<a class="brand" href="/">CRTSHT</a>
<nav class="nav"><a href="/">Archive</a><a href="/lore">The Lore</a><a href="/oracle" aria-current="page">The Oracle</a></nav>
</header>

<?php if($revealed): ?>
<?php foreach($matches as $match): $id=(int)$match['id']; $cake=crt_cake($id); ?>
<section class="oracle-reveal">
<div class="eyebrow">金のうんこ OPEN / THE ORACLE SAYS</div>
<h1>“<?= crt_e(crt_oracle_fortune($id, (string)$match['key'], $fortuneMap)) ?>”</h1>
<div class="oracle-identity">
<?php if($cake): ?><a href="/crtsht/<?= $id ?>"><img src="<?= crt_e($cake) ?>" alt="Mooncake <?= $id ?>"></a><?php endif; ?>
<div><span><?= crt_e((string)$match['title']) ?></span><a href="/crtsht/<?= $id ?>">Open the record →</a></div>
</div>
<div class="oracle-system" aria-label="Oracle system status">
<div class="oracle-system-bar"><span>CRTSHT / ORACLE</span><span>CONNECTION ESTABLISHED ..........97%</span></div>
<div class="oracle-system-body">
<span><b>PUBLIC META</b> MATCHED</span>
<span><b>MOONCAKE</b> OPEN</span>
<span><b>FORTUNE</b> UNLOCKED</span>
<span><b>SECRET KEY</b> SEALED</span>
</div>
</div>
</section>
<?php endforeach; ?>
<?php else: ?>
<section class="intro">
<div class="eyebrow">THE ORACLE</div>
<h1>Four words.<br>One mooncake.</h1>
<p>The 2021 instructions said the first four words could open your <strong>金のうんこ</strong> “and more…”. This is the and more.</p>
<p class="oracle-note">Enter the four visible words from the physical CRTSHT. The Oracle checks them against the collection. The other twenty words stay sealed; they are neither needed nor wanted here.</p>
<form class="oracle-form" method="post" action="/oracle" autocomplete="off">
<input name="w1" placeholder="1. word" value="<?= crt_e($words[0]) ?>" required>
<input name="w2" placeholder="2. word" value="<?= crt_e($words[1]) ?>" required>
<input name="w3" placeholder="3. word" value="<?= crt_e($words[2]) ?>" required>
<input name="w4" placeholder="4. word" value="<?= crt_e($words[3]) ?>" required>
<button type="submit">OPEN</button>
</form>
<?php if($error !== ''): ?><div class="error"><?= crt_e($error) ?></div><?php endif; ?>
<div class="oracle-system" aria-label="Oracle waiting status">
<div class="oracle-system-bar"><span>CRTSHT / ORACLE</span><span>WAITING FOR RL-HOOK</span></div>
<div class="oracle-system-body">
<span><b>PUBLIC META</b> WAITING</span>
<span><b>WORDS</b> 4 REQUIRED</span>
<span><b>SECRET KEY</b> NOT REQUIRED</span>
<span><b>CONNECTION</b> ..........00%</span>
</div>
</div>
</section>
<?php endif; ?>

<footer class="footer"><span>CRTSHT / THE ORACLE</span><span><?= $revealed ? 'FORTUNE REVEALED · 97%' : '4 WORDS · 20 SEALED' ?></span></footer>
</main></body></html>