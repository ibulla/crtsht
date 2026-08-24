<?php
declare(strict_types=1);

const TOTAL = 128;
const SITE = 'CRTSHT';

$privateConfig = __DIR__ . '/private/config.php';
if (is_file($privateConfig)) {
    $cfg = require $privateConfig;
    if (is_array($cfg)) {
        foreach ($cfg as $k => $v) {
            if (is_string($k) && $k !== '' && $v !== null) {
                putenv($k . '=' . (string)$v);
                $_ENV[$k] = (string)$v;
            }
        }
    }
    unset($cfg);
}

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function envv(string $k): string { $v = getenv($k); return $v === false ? '' : trim((string)$v); }
function metadata(int $id): ?array {
    $f = __DIR__ . '/JSON_1-128/' . $id . '.json';
    if ($id < 1 || $id > TOTAL || !is_file($f)) return null;
    $d = json_decode((string)file_get_contents($f), true);
    return is_array($d) ? $d : null;
}
function attrs(array $m): array {
    $o = [];
    foreach (($m['attributes'] ?? []) as $a) {
        if (isset($a['trait_type'])) $o[(string)$a['trait_type']] = (string)($a['value'] ?? '');
    }
    return $o;
}
function artworkTitle(int $id, ?array $m = null): string {
    return $m && !empty($m['name']) ? (string)$m['name'] : '0x' . str_pad(strtolower(dechex($id)), 4, '0', STR_PAD_LEFT);
}
function localImage(int $id): ?string {
    $f = glob(__DIR__ . '/shitpix_jpg_1-128/' . $id . '-*.jpg');
    return $f ? '/shitpix_jpg_1-128/' . rawurlencode(basename($f[0])) : null;
}
function cakeImage(int $id): ?string {
    $f = __DIR__ . '/coin_1-128/' . $id . '.jpg';
    return is_file($f) ? '/coin_1-128/' . $id . '.jpg' : null;
}
function cid(array $m): string {
    $u = (string)($m['image'] ?? '');
    return str_starts_with($u, 'ipfs://') ? substr($u, 7) : '';
}
function routeId(): ?int {
    $p = trim((string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? ''), '/');
    return preg_match('~^(?:crtsht/)?(\\d{1,3})$~', $p, $m) ? (int)$m[1] : null;
}
function db(): ?mysqli {
    $h = envv('CRTSHT_DB_HOST'); $u = envv('CRTSHT_DB_USER'); $p = envv('CRTSHT_DB_PASS'); $n = envv('CRTSHT_DB_NAME');
    if ($h === '' || $u === '' || $n === '') return null;
    mysqli_report(MYSQLI_REPORT_OFF);
    $d = @new mysqli($h, $u, $p, $n);
    if ($d->connect_errno) return null;
    $d->set_charset('utf8mb4');
    return $d;
}
function dbRecord(int $id): ?array {
    $d = db(); if (!$d) return null;
    $s = $d->prepare('SELECT `ID`,`ShitID`,`ETH_Adr`,`Hasher_Druck`,`LoginWhirlpool` FROM `ShitID` WHERE `ID`=? LIMIT 1');
    if (!$s) { $d->close(); return null; }
    $s->bind_param('i', $id); $s->execute(); $r = $s->get_result(); $row = $r ? $r->fetch_assoc() : null;
    $s->close(); $d->close();
    return is_array($row) ? $row : null;
}
function httpJson(string $url): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>4,CURLOPT_TIMEOUT=>8,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_USERAGENT=>'CRTSHT archive/2026']);
    $b = curl_exec($ch); $c = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE); curl_close($ch);
    if (!is_string($b) || $c < 200 || $c >= 300) return null;
    $j = json_decode($b, true);
    return is_array($j) ? $j : null;
}
function ethereumTransfers(string $a): ?array {
    $k = envv('ETHERSCAN_API_KEY');
    if ($a === '' || $k === '') return null;
    $j = httpJson('https://api.etherscan.io/v2/api?chainid=1&module=account&action=tokennfttx&address=' . rawurlencode($a) . '&startblock=0&endblock=999999999&sort=asc&apikey=' . rawurlencode($k));
    return $j && ($j['status'] ?? '') === '1' && is_array($j['result'] ?? null) ? $j['result'] : null;
}
function mintRecord(string $a): ?array {
    $r = ethereumTransfers($a); if (!$r) return null;
    foreach ($r as $x) if (is_array($x) && strtolower((string)($x['to'] ?? '')) === strtolower($a)) return $x;
    return is_array($r[0] ?? null) ? $r[0] : null;
}
function rpcCall(string $method, array $params): ?string {
    $eps = array_values(array_unique(array_filter([envv('ETH_RPC_URL'),'https://cloudflare-eth.com','https://ethereum-rpc.publicnode.com'])));
    $payload = json_encode(['jsonrpc'=>'2.0','id'=>1,'method'=>$method,'params'=>$params]);
    if (!is_string($payload)) return null;
    foreach ($eps as $url) {
        $ch = curl_init($url);
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$payload,CURLOPT_HTTPHEADER=>['Content-Type: application/json'],CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>6,CURLOPT_USERAGENT=>'CRTSHT archive/2026']);
        $b = curl_exec($ch); $c = (int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE); curl_close($ch);
        if (!is_string($b) || $c < 200 || $c >= 300) continue;
        $j = json_decode($b,true);
        if (is_array($j) && isset($j['result']) && is_string($j['result'])) return $j['result'];
    }
    return null;
}
function ownerOf(string $contract, string $tokenId): ?string {
    if (!preg_match('/^0x[a-fA-F0-9]{40}$/',$contract) || !ctype_digit($tokenId)) return null;
    $data = '0x6352211e' . str_pad(dechex((int)$tokenId),64,'0',STR_PAD_LEFT);
    $r = rpcCall('eth_call',[['to'=>$contract,'data'=>$data],'latest']);
    if (!$r || !preg_match('/^0x[a-fA-F0-9]{64}$/',$r)) return null;
    $o = '0x' . substr($r,-40);
    return strtolower($o) === '0x0000000000000000000000000000000000000000' ? null : $o;
}
function fortune(string $key, int $id): string {
    $f = [
        'What you draw is not random once you decide to keep it.',
        'The object knows something the screen does not.',
        'Keep the key. Forget the price.',
        'A good archive never tells you where the story ends.',
        'The future has poor metadata. Keep your own.',
        'Your next good decision will feel slightly unreasonable.',
        'Something you kept will outlive something you chased.',
        'A hidden route becomes useful when you stop looking for the shortest one.',
        'The work changes when it leaves the wall. Let it.',
        'Chance is only the beginning of ownership.',
        'Trust the strange detail you almost ignored.',
        'One block after another is still a journey.',
        'Today is a good day to keep something offline.',
        'Open what is sealed only when you really need it.',
        'Scarcity ends. A good story keeps circulating.',
        'The secret is not the image. It is the encounter.',
        'A key matters because something remains closed.',
        'Keep one impossible idea alive a little longer.',
        'The thing you receive may be the thing you were meant to notice.',
        'No algorithm can tell you why this one became yours.',
        'The archive stays complete. The collection does not.',
        'Ownership begins where browsing ends.',
        'You found a number. Now give it a history.',
        'The shortest proof is sometimes the object in your hand.'
    ];
    $h = hash('sha256',$key.'.'.$id.'.fortune');
    return $f[hexdec(substr($h,0,8)) % count($f)];
}
function verifyWords(string $title, array $words, string $stored): bool {
    if ($stored === '' || count($words) !== 4) return false;
    $w = array_map(fn($x)=>strtolower(trim((string)$x)),$words);
    foreach ($w as $x) if ($x === '') return false;
    $derived = substr(hash('whirlpool',strtolower($title).'.'.implode('.',$w)),0,8);
    return hash_equals(strtolower($stored),strtolower($derived));
}
function displayDescription(string $title): string {
    return 'This MORE-Coin verifies the physical CRTSHT ' . $title . ' as ONE mooncake and one unique artwork by iBulla.com. Minted on Ethereum in 2021, it ties the printed data to its hash, wallet and token history. The meta is public. The key belongs to the object.';
}

$id = routeId();
$detail = $id !== null && $id >= 1 && $id <= TOTAL;
$meta = $detail ? metadata($id) : null;
if ($detail && !$meta) { http_response_code(404); $detail = false; }
$title = $detail && $meta ? artworkTitle($id,$meta) : SITE;
$unlockState = ''; $unlocked = false; $privateFortune = '';
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($detail ? $title.' / CRTSHT' : 'CRTSHT / 128') ?></title>
<meta name="description" content="CRTSHT — 128 physical works. 128 owners. One draw. Recorded on Ethereum in 2021, completed through chance in 2026.">
<meta property="og:title" content="<?= e($detail ? $title.' / CRTSHT' : 'CRTSHT — 128 WORKS / ONE DRAW') ?>">
<meta property="og:description" content="You choose to own one. Chance chooses which.">
<style>
:root{--bg:#f2f2ee;--fg:#111;--muted:#777;--line:#c9c9c2;--pad:clamp(18px,3vw,44px)}
*{box-sizing:border-box}html{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono",monospace;background:var(--bg);color:var(--fg)}body{margin:0}.wrap{padding:var(--pad);max-width:1800px;margin:auto}a{color:inherit;text-decoration:none}a:hover{text-decoration:underline}
header{display:flex;justify-content:space-between;align-items:baseline;border-bottom:1px solid var(--fg);padding-bottom:12px;margin-bottom:var(--pad)}.brand{font-size:clamp(25px,4vw,58px);font-weight:700;letter-spacing:-.07em}.small,.label,.eyebrow{font-size:11px;text-transform:uppercase;letter-spacing:.08em}.muted{color:var(--muted)}
.hero{display:grid;grid-template-columns:minmax(0,1.45fr) minmax(300px,.75fr);gap:var(--pad);margin:0 0 calc(var(--pad)*1.25)}.hero h1{font-size:clamp(46px,8vw,124px);line-height:.82;letter-spacing:-.075em;margin:.08em 0 .28em;max-width:10ch}.hero .lead{font-size:clamp(18px,2.2vw,31px);line-height:1.08;letter-spacing:-.035em;max-width:20ch;margin:0}.hero-copy{align-self:end}.hero-copy p{margin:0 0 1em;line-height:1.52;max-width:64ch}.manifest{border-top:1px solid var(--fg);border-bottom:1px solid var(--fg);display:grid;grid-template-columns:repeat(4,1fr);margin-bottom:calc(var(--pad)*1.5)}.step{padding:16px 16px 18px 0;border-right:1px solid var(--line);margin-right:16px}.step:last-child{border-right:0}.step strong{display:block;font-size:clamp(20px,2.3vw,34px);letter-spacing:-.05em;margin:5px 0 8px}.step p{font-size:12px;line-height:1.45;margin:0;color:var(--muted)}
.disperse{display:grid;grid-template-columns:minmax(220px,.65fr) minmax(0,1.35fr);gap:var(--pad);padding:0 0 calc(var(--pad)*1.4)}.disperse h2{font-size:clamp(28px,4vw,64px);line-height:.9;letter-spacing:-.06em;margin:0}.disperse p{font-size:clamp(15px,1.5vw,20px);line-height:1.45;margin:0;max-width:66ch}.collection-head{display:flex;justify-content:space-between;gap:20px;align-items:end;border-bottom:1px solid var(--fg);padding-bottom:10px}.collection-head h2{font-size:clamp(22px,3vw,42px);letter-spacing:-.05em;margin:0}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(145px,1fr));border-left:1px solid var(--line)}.card{padding:12px;border-right:1px solid var(--line);border-bottom:1px solid var(--line);min-height:195px;display:flex;flex-direction:column;justify-content:space-between;transition:background .15s}.card:hover{background:#fff}.card img{width:100%;aspect-ratio:1;object-fit:cover;filter:grayscale(1);margin-bottom:10px;transition:filter .2s}.card:hover img{filter:none}.num{display:flex;justify-content:space-between;font-size:12px}
.detail{display:grid;grid-template-columns:minmax(280px,1.1fr) minmax(320px,.9fr);gap:var(--pad)}.art{position:sticky;top:var(--pad)}.art img{width:100%;display:block;background:#ddd;cursor:zoom-in}.record{border-top:1px solid var(--fg)}.row{display:grid;grid-template-columns:135px 1fr;gap:16px;padding:10px 0;border-bottom:1px solid var(--line);font-size:12px}.value{overflow-wrap:anywhere}.titleline{display:flex;align-items:center;gap:16px;margin:.3em 0 .45em}.titleline h1{font-size:clamp(34px,5vw,72px);letter-spacing:-.06em;margin:0}.cake-icon{width:58px;height:58px;object-fit:cover;border-radius:50%}.detail-lead{font-size:15px;line-height:1.5;max-width:62ch;margin:0 0 24px}.section-head{margin:34px 0 8px;font-size:11px;text-transform:uppercase;letter-spacing:.08em}.status{display:inline-block;border:1px solid var(--fg);padding:5px 8px;border-radius:100px;font-size:10px;text-transform:uppercase;letter-spacing:.08em}.copy{cursor:pointer}.note{font-size:13px;line-height:1.55;margin:28px 0}.ipfs-status{font-size:10px;margin-top:8px}
.key-section{display:grid;grid-template-columns:minmax(180px,280px) 1fr;gap:28px;border-top:1px solid var(--fg);margin-top:46px;padding-top:20px}.key-section img{width:100%;display:block}.key-copy h2{font-size:clamp(30px,4vw,58px);line-height:.92;letter-spacing:-.055em;margin:6px 0 18px;max-width:13ch}.key-copy>p{max-width:64ch;line-height:1.52}.seedform{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;max-width:620px;margin-top:20px}.seedform input{width:100%;font:inherit;background:transparent;border:1px solid var(--line);padding:12px}.seedform button{grid-column:1/-1;font:inherit;background:var(--fg);color:var(--bg);border:0;padding:12px;cursor:pointer}.fortune{font-size:clamp(24px,3.2vw,46px);line-height:1.02;letter-spacing:-.04em;max-width:19ch;margin:22px 0}.error{padding:12px;border:1px solid var(--fg);margin-top:12px}.footer{border-top:1px solid var(--fg);margin-top:calc(var(--pad)*1.5);padding-top:12px;display:flex;justify-content:space-between;gap:20px;font-size:11px}
.lightbox{position:fixed;inset:0;background:rgba(0,0,0,.94);display:none;align-items:center;justify-content:center;z-index:100;padding:20px}.lightbox.open{display:flex}.lightbox img{max-width:96vw;max-height:96vh;object-fit:contain}.lightbox button{position:fixed;top:18px;right:20px;background:none;border:0;color:#fff;font:inherit;font-size:24px;cursor:pointer}
@media(max-width:900px){.manifest{grid-template-columns:repeat(2,1fr)}.step:nth-child(2){border-right:0}.hero{grid-template-columns:1fr}.hero-copy{align-self:auto}}
@media(max-width:760px){.detail,.key-section,.disperse{grid-template-columns:1fr}.art{position:static}.row{grid-template-columns:100px 1fr}.grid{grid-template-columns:repeat(2,1fr)}.seedform{grid-template-columns:1fr}.cake-icon{width:44px;height:44px}.manifest{grid-template-columns:1fr}.step{border-right:0;border-bottom:1px solid var(--line);margin-right:0}.step:last-child{border-bottom:0}.collection-head{align-items:start;flex-direction:column}}
</style>
</head>
<body><main class="wrap">
<header><a class="brand" href="/">CRTSHT</a><span class="small">128 / ETHEREUM / PHYSICAL / 2021—2026</span></header>
<?php if (!$detail): ?>
<section class="hero">
<div><div class="eyebrow">128 WORKS / 128 OWNERS / ONE DRAW</div><h1>You choose to own one.</h1><p class="lead">Chance chooses which.</p></div>
<div class="hero-copy"><p>CRTSHT is a series of 128 unique 20 × 20 cm physical works created in 2021. Each one was given its own Ethereum NFT, wallet, print hash and mooncake token.</p><p>The entire collection is visible here. Nothing is hidden except the part that matters: <strong>which one becomes yours.</strong></p><p class="muted">CRTSHT began during the NFT boom and returns in 2026 as a physical draw, a blockchain record and an experiment in what ownership means after the hype.</p></div>
</section>
<section class="manifest">
<div class="step"><span class="eyebrow">01</span><strong>SEE</strong><p>Every artwork, hash, NFT, wallet and network record is public. Browse the complete set before anything leaves the wall.</p></div>
<div class="step"><span class="eyebrow">02</span><strong>DRAW</strong><p>You do not win a CRTSHT. You decide to own one. The draw decides which number enters your hands.</p></div>
<div class="step"><span class="eyebrow">03</span><strong>OWN</strong><p>Your 20 × 20 cm original leaves the series together with its mooncake token and physical key — packed, appropriately, in a pizza box.</p></div>
<div class="step"><span class="eyebrow">04</span><strong>UNLOCK</strong><p>Four words on the back open a private fortune online. The complete 24-word recovery phrase and private key remain sealed with the object.</p></div>
</section>
<section class="disperse"><h2>The sale dismantles the work.</h2><p>At the beginning, all 128 originals exist together as one complete installation. With every draw, one work disappears from the wall and enters a new life. The physical collection disperses. <strong>The archive is what remains complete.</strong></p></section>
<div class="collection-head"><h2>THE COMPLETE SERIES</h2><span class="small">BROWSE ALL 128 · CLICK FOR RECORD</span></div>
<section class="grid">
<?php for($i=1;$i<=TOTAL;$i++): $m=metadata($i); if(!$m)continue; $img=localImage($i); $hex=artworkTitle($i,$m); ?>
<a class="card" href="/crtsht/<?= $i ?>"><div><?php if($img): ?><img loading="lazy" src="<?= e($img) ?>" alt="<?= e($hex) ?>"><?php endif; ?></div><div class="num"><span><?= e($hex) ?></span><span><?= $i ?>/128</span></div></a>
<?php endfor; ?>
</section>
<?php else:
$a=attrs($meta); $c=cid($meta); $fallback=localImage($id); $cake=cakeImage($id); $birthday=(int)($a['birthday']??0); $dbrow=dbRecord($id); $ethAddress=trim((string)($dbrow['ETH_Adr']??'')); $eth=$ethAddress!==''?mintRecord($ethAddress):null; $contract=$eth?(string)($eth['contractAddress']??''):''; $tokenId=$eth?(string)($eth['tokenID']??''):''; $currentOwner=($contract!==''&&$tokenId!=='')?ownerOf($contract,$tokenId):null; $ownerState=$currentOwner?(strtolower($currentOwner)===strtolower($ethAddress)?'ORIGINAL WALLET':'TRANSFERRED'):'UNKNOWN';
if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='fortune'){
    $words=[(string)($_POST['w1']??''),(string)($_POST['w2']??''),(string)($_POST['w3']??''),(string)($_POST['w4']??'')];
    $stored=(string)($dbrow['LoginWhirlpool']??'');
    if(verifyWords($title,$words,$stored)){ $unlocked=true; $privateFortune=fortune($stored,$id); }
    else { $unlockState='These four words do not open this mooncake.'; }
}
?>
<section class="detail">
<div class="art">
<?php if($c): ?><img class="zoomable" id="ipfs-image" data-cid="<?= e($c) ?>" data-fallback="<?= e($fallback??'') ?>" src="https://dweb.link/ipfs/<?= e($c) ?>" alt="<?= e($title) ?>"><div id="ipfs-status" class="ipfs-status muted">RESOLVING IPFS / <?= e($c) ?></div><?php elseif($fallback): ?><img class="zoomable" src="<?= e($fallback) ?>" alt="<?= e($title) ?>"><?php endif; ?>
</div>
<div>
<div class="small"><a href="/">← COMPLETE SERIES</a></div>
<div class="record">
<div class="titleline"><?php if($cake): ?><img class="cake-icon" src="<?= e($cake) ?>" alt="Mooncake <?= $id ?>"><?php endif; ?><h1><?= e($title) ?></h1></div>
<p class="detail-lead"><?= e(displayDescription($title)) ?></p>
<div class="section-head">PHYSICAL / ARCHIVE RECORD</div>
<div class="row"><span class="label">archive id</span><span class="value"><?= $id ?>/128 · /crtsht/<?= $id ?></span></div>
<div class="row"><span class="label">format</span><span class="value">20 × 20 cm · unique physical print</span></div>
<div class="row"><span class="label">state</span><span><span class="status">PUBLIC RECORD</span></span></div>
<div class="row"><span class="label">series</span><span class="value"><?= e($a['MINT']??'1-128') ?></span></div>
<?php if($birthday): ?><div class="row"><span class="label">birthday</span><span class="value"><?= e(gmdate('Y-m-d H:i:s',$birthday)) ?> UTC</span></div><?php endif; ?>
<div class="row"><span class="label">print hash</span><span class="value copy" data-copy><?= e($a['PRINT HASH']??'') ?></span></div>
<div class="row"><span class="label">poo</span><span class="value"><?= e($a['POO']??'') ?></span></div>
<div class="row"><span class="label">author</span><span class="value">iBulla.com</span></div>

<div class="section-head">ETHEREUM RECORD</div>
<?php if($ethAddress!==''): ?><div class="row"><span class="label">original wallet</span><span class="value"><a target="_blank" rel="noopener" href="https://etherscan.io/address/<?= e($ethAddress) ?>"><?= e($ethAddress) ?> ↗</a></span></div><?php endif; ?>
<?php if($eth): $tx=(string)($eth['hash']??''); $ts=(int)($eth['timeStamp']??0); ?>
<div class="row"><span class="label">network</span><span>Ethereum Mainnet</span></div>
<div class="row"><span class="label">token</span><span><?= e((string)($eth['tokenName']??'')) ?><?= ($eth['tokenSymbol']??'')!=='' ? ' / '.e((string)$eth['tokenSymbol']) : '' ?></span></div>
<div class="row"><span class="label">token id</span><span><?= e($tokenId) ?></span></div>
<div class="row"><span class="label">contract</span><span class="value"><a target="_blank" rel="noopener" href="https://etherscan.io/token/<?= e($contract) ?>?a=<?= e($tokenId) ?>"><?= e($contract) ?> ↗</a></span></div>
<div class="row"><span class="label">owner now</span><span class="value"><?php if($currentOwner): ?><a target="_blank" rel="noopener" href="https://etherscan.io/address/<?= e($currentOwner) ?>"><?= e($currentOwner) ?> ↗</a> <span class="status"><?= e($ownerState) ?></span><?php else: ?><span class="muted">lookup unavailable</span><?php endif; ?></span></div>
<div class="row"><span class="label">mint block</span><span><a target="_blank" rel="noopener" href="https://etherscan.io/block/<?= e((string)($eth['blockNumber']??'')) ?>"><?= e((string)($eth['blockNumber']??'')) ?> ↗</a></span></div>
<?php if($ts): ?><div class="row"><span class="label">mint time</span><span><?= e(gmdate('Y-m-d H:i:s',$ts)) ?> UTC</span></div><?php endif; ?>
<div class="row"><span class="label">mint tx</span><span class="value"><a target="_blank" rel="noopener" href="https://etherscan.io/tx/<?= e($tx) ?>"><?= e($tx) ?> ↗</a></span></div>
<div class="row"><span class="label">from</span><span class="value"><?= e((string)($eth['from']??'')) ?></span></div>
<div class="row"><span class="label">to</span><span class="value"><?= e((string)($eth['to']??'')) ?></span></div>
<div class="row"><span class="label">confirmations</span><span><?= e((string)($eth['confirmations']??'')) ?></span></div>
<?php endif; ?>

<div class="section-head">NETWORK / METADATA</div>
<div class="row"><span class="label">ipfs uri</span><span class="value copy" data-copy><?= e($meta['image']??'') ?></span></div>
<?php if($c): ?><div class="row"><span class="label">gateway</span><span><a target="_blank" rel="noopener" href="https://ipfs.io/ipfs/<?= e($c) ?>">open IPFS ↗</a></span></div><?php endif; ?>
<div class="row"><span class="label">metadata</span><span><a href="/JSON_1-128/<?= $id ?>.json" target="_blank" rel="noopener">original 2021 JSON ↗</a></span></div>
</div>
<p class="note muted">The original 2021 metadata is preserved unchanged, including its historical URLs and language. This page is the 2026 presentation layer: it restores the network record without rewriting the token.</p>
</div>
</section>

<section class="key-section">
<?php if($cake): ?><div><img src="<?= e($cake) ?>" alt="<?= e($title) ?> mooncake"><p class="small muted">MOONCAKE / <?= $title ?> / <?= $id ?>/128</p></div><?php endif; ?>
<div class="key-copy"><div class="eyebrow">PHYSICAL KEY</div><h2>The record is public. Your fortune is not.</h2><p>The reverse of the original carries four visible words from its 24-word recovery phrase. They are enough to prove that the physical object is in your hands — and to open one private message here.</p><p>The complete recovery phrase and private key remain sealed on the artwork. You never need to expose them to use this archive.</p>
<?php if($unlocked): ?><span class="status">MOONCAKE OPEN</span><p class="fortune">“<?= e($privateFortune) ?>”</p><p class="small muted">YOUR FORTUNE / CRTSHT ARCHIVE LAYER / 2026</p>
<?php else: ?><form class="seedform" method="post" action="/crtsht/<?= $id ?>#key" id="key" autocomplete="off"><input type="hidden" name="action" value="fortune"><input name="w1" placeholder="1. word" required><input name="w2" placeholder="2. word" required><input name="w3" placeholder="3. word" required><input name="w4" placeholder="4. word" required><button type="submit">OPEN YOUR MOONCAKE</button></form><?php if($unlockState!==''): ?><div class="error"><?= e($unlockState) ?></div><?php endif; ?><?php endif; ?>
</div>
</section>
<?php endif; ?>
<footer class="footer"><span>CRTSHT / iBulla</span><span>SEE · DRAW · OWN · UNLOCK</span></footer>
</main>
<div class="lightbox" id="lightbox"><button aria-label="Close">×</button><img alt="Full artwork"></div>
<script>
document.querySelectorAll('[data-copy]').forEach(el=>el.addEventListener('click',async()=>{try{await navigator.clipboard.writeText(el.textContent.trim());const o=el.textContent;el.textContent='COPIED';setTimeout(()=>el.textContent=o,700)}catch(e){}}));
const img=document.getElementById('ipfs-image');if(img){const cid=img.dataset.cid,status=document.getElementById('ipfs-status'),fallback=img.dataset.fallback,g=[`https://ipfs.io/ipfs/${cid}`,`https://w3s.link/ipfs/${cid}`];let n=0;img.addEventListener('load',()=>{try{status.textContent='IPFS / RESOLVED / '+new URL(img.src).hostname}catch(e){}});img.addEventListener('error',()=>{if(n<g.length)img.src=g[n++];else if(fallback){status.textContent='IPFS / GATEWAYS UNAVAILABLE / LOCAL ARCHIVAL COPY';img.src=fallback}else status.textContent='IPFS / UNAVAILABLE'})}
const lb=document.getElementById('lightbox'),lbi=lb.querySelector('img');document.querySelectorAll('.zoomable').forEach(z=>z.addEventListener('click',()=>{lbi.src=z.src;lb.classList.add('open')}));lb.addEventListener('click',()=>lb.classList.remove('open'));document.addEventListener('keydown',e=>{if(e.key==='Escape')lb.classList.remove('open')});
</script>
</body></html>
