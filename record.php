<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

function short_wallet(string $address): string {
    if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $address)) return $address;
    return substr($address, 0, 6) . '....' . substr($address, -4);
}

$path = trim((string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? ''), '/');
$id = 0;
if (preg_match('~^(?:crtsht/)?(\d{1,3})$~', $path, $m)) $id = (int)$m[1];
if ($id < 1 || $id > CRTSHT_TOTAL) { http_response_code(404); exit('404'); }

$meta = crt_metadata($id);
if (!$meta) { http_response_code(404); exit('404'); }

$title = crt_title($id,$meta);
$attrs = crt_attrs($meta);
$art = crt_artwork($id);
$cake = crt_cake($id);
$cid = crt_cid($meta); // token metadata image = mooncake
$dbrow = crt_db_record($id);
$wallet = trim((string)($dbrow['ETH_Adr'] ?? ''));
$mint = $wallet !== '' ? crt_mint_record($wallet) : null;
$contract = $mint ? (string)($mint['contractAddress'] ?? '') : '';
$tokenId = $mint ? (string)($mint['tokenID'] ?? '') : '';
$currentOwner = ($contract !== '' && $tokenId !== '') ? crt_owner_of($contract,$tokenId) : null;
$ownerState = $currentOwner ? (strtolower($currentOwner) === strtolower($wallet) ? 'ORIGINAL WALLET' : 'TRANSFERRED') : 'UNKNOWN';
$birthday = (int)($attrs['birthday'] ?? 0);
$jsonCid = trim((string)($dbrow['IPFS_JSON'] ?? ''));
$description = trim((string)($meta['description'] ?? ''));
$prettyJson = json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if (!is_string($prettyJson)) $prettyJson = '{}';
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= crt_e($title) ?> / CRTSHT</title>
<meta name="description" content="<?= crt_e($title) ?> — CRTSHT <?= $id ?>/128. Physical work, Ethereum record and IPFS metadata.">
<link rel="stylesheet" href="/site.css?v=3">
</head>
<body><main class="wrap">
<header>
<a class="brand" href="/">CRTSHT</a>
<nav class="nav"><a href="/">Archive</a><a href="/lore">The Lore</a><a href="/oracle">The Oracle</a></nav>
</header>
<section class="detail">
<div class="art">
<?php if($art): ?>
<img class="zoomable" id="artwork-image" decoding="async" fetchpriority="high" src="<?= crt_e($art) ?>" alt="<?= crt_e($title) ?>">
<?php endif; ?>
<div class="ipfs-status">PHYSICAL ARTWORK / LOCAL ARCHIVE</div>
</div>
<div>
<div class="small"><a href="/">← Archive</a></div>
<div class="record">
<div class="titleline"><?php if($cake): ?><img class="cake-icon" src="<?= crt_e($cake) ?>" alt="Mooncake <?= $id ?>"><?php endif; ?><h1><?= crt_e($title) ?></h1></div>
<p class="muted" style="font-size:12px;line-height:1.5;margin:0 0 22px">20 × 20 cm physical original · <?= $id ?>/128 · minted 2021</p>

<div class="section-head">OBJECT</div>
<div class="row"><span class="label">archive id</span><span><?= $id ?>/128 · /crtsht/<?= $id ?></span></div>
<div class="row"><span class="label">print hash</span><span class="value copy" data-copy><?= crt_e($attrs['PRINT HASH'] ?? '') ?></span></div>
<?php if($birthday): ?><div class="row"><span class="label">birthday</span><span><?= crt_e(gmdate('Y-m-d H:i:s',$birthday)) ?> UTC</span></div><?php endif; ?>
<div class="row"><span class="label">poo</span><span><?= crt_e($attrs['POO'] ?? '') ?></span></div>
<div class="row"><span class="label">author</span><span><?= crt_e($attrs['AUTHOR'] ?? 'iBulla.com') ?></span></div>

<div class="section-head">ETHEREUM</div>
<?php if($wallet !== ''): ?><div class="row"><span class="label">original wallet</span><span><a target="_blank" rel="noopener" title="<?= crt_e($wallet) ?>" href="https://etherscan.io/address/<?= crt_e($wallet) ?>"><?= crt_e(short_wallet($wallet)) ?> ↗</a></span></div><?php endif; ?>
<?php if($mint): $tx=(string)($mint['hash']??''); $ts=(int)($mint['timeStamp']??0); $from=(string)($mint['from']??''); $to=(string)($mint['to']??''); ?>
<div class="row"><span class="label">network</span><span>Ethereum Mainnet</span></div>
<div class="row"><span class="label">token</span><span><?php if($contract): ?><a target="_blank" rel="noopener" href="https://etherscan.io/token/<?= crt_e($contract) ?>"><?= crt_e((string)($mint['tokenName']??'MORE')) ?><?= ($mint['tokenSymbol']??'')!=='' ? ' / '.crt_e((string)$mint['tokenSymbol']) : '' ?> ↗</a><?php else: ?><?= crt_e((string)($mint['tokenName']??'')) ?><?php endif; ?></span></div>
<div class="row"><span class="label">token id</span><span><?= crt_e($tokenId) ?></span></div>
<div class="row"><span class="label">contract</span><span class="value"><a target="_blank" rel="noopener" href="https://etherscan.io/token/<?= crt_e($contract) ?>?a=<?= crt_e($tokenId) ?>"><?= crt_e($contract) ?> ↗</a></span></div>
<div class="row"><span class="label">owner now</span><span><?php if($currentOwner): ?><a target="_blank" rel="noopener" title="<?= crt_e($currentOwner) ?>" href="https://etherscan.io/address/<?= crt_e($currentOwner) ?>"><?= crt_e(short_wallet($currentOwner)) ?> ↗</a> <span class="status"><?= crt_e($ownerState) ?></span><?php else: ?><span class="muted">lookup unavailable</span><?php endif; ?></span></div>
<div class="row"><span class="label">mint block</span><span><a target="_blank" rel="noopener" href="https://etherscan.io/block/<?= crt_e((string)($mint['blockNumber']??'')) ?>"><?= crt_e((string)($mint['blockNumber']??'')) ?> ↗</a></span></div>
<?php if($ts): ?><div class="row"><span class="label">mint time</span><span><?= crt_e(gmdate('Y-m-d H:i:s',$ts)) ?> UTC</span></div><?php endif; ?>
<div class="row"><span class="label">mint tx</span><span class="value"><a target="_blank" rel="noopener" title="<?= crt_e($tx) ?>" href="https://etherscan.io/tx/<?= crt_e($tx) ?>"><?= crt_e(substr($tx,0,10).'....'.substr($tx,-6)) ?> ↗</a></span></div>
<div class="row"><span class="label">from</span><span><?php if($from): ?><a target="_blank" rel="noopener" title="<?= crt_e($from) ?>" href="https://etherscan.io/address/<?= crt_e($from) ?>"><?= crt_e(short_wallet($from)) ?> ↗</a><?php endif; ?></span></div>
<div class="row"><span class="label">to</span><span><?php if($to): ?><a target="_blank" rel="noopener" title="<?= crt_e($to) ?>" href="https://etherscan.io/address/<?= crt_e($to) ?>"><?= crt_e(short_wallet($to)) ?> ↗</a><?php endif; ?></span></div>
<div class="row"><span class="label">confirmations</span><span><?= crt_e((string)($mint['confirmations']??'')) ?></span></div>
<?php elseif($wallet !== ''): ?>
<div class="row"><span class="label">chain data</span><span class="muted">wallet recovered · transaction lookup unavailable</span></div>
<?php endif; ?>

<div class="section-head">NETWORK</div>
<?php if($cid): ?><div class="row"><span class="label">mooncake image cid</span><span class="value"><a target="_blank" rel="noopener" href="https://ipfs.io/ipfs/<?= crt_e($cid) ?>"><?= crt_e($cid) ?> ↗</a></span></div><?php endif; ?>
<?php if($jsonCid): ?><div class="row"><span class="label">metadata cid</span><span class="value"><a target="_blank" rel="noopener" href="https://ipfs.io/ipfs/<?= crt_e($jsonCid) ?>"><?= crt_e($jsonCid) ?> ↗</a></span></div><?php endif; ?>
<div class="row"><span class="label">json</span><span class="value"><a target="_blank" href="/JSON_1-128/<?= $id ?>.json">original 2021 metadata ↗</a><?php if($description !== ''): ?><span class="metadata-description"><?= crt_e($description) ?></span><?php endif; ?><details class="json-reveal"><summary>Reveal JSON</summary><pre><?= crt_e($prettyJson) ?></pre></details></span></div>
</div>
</div>
</div>
</section>

<?php if($cake): ?>
<section class="mooncake-exit">
<a href="/oracle" aria-label="Ask The Oracle with the physical key for <?= crt_e($title) ?>">
<img id="mooncake-image" loading="lazy" decoding="async" src="<?= crt_e($cake) ?>" alt="Mooncake for <?= crt_e($title) ?>">
<?php if($cid): ?><span id="mooncake-ipfs-status" class="ipfs-status" data-cid="<?= crt_e($cid) ?>">TOKEN IMAGE / CHECKING IPFS</span><?php endif; ?>
<span class="eyebrow">THE PHYSICAL KEY</span>
<strong>Have the original?<br>Ask The Oracle →</strong>
</a>
</section>
<?php endif; ?>

<footer class="footer"><span><?= crt_e($title) ?> / CRTSHT</span><span><?= $id ?>/128</span></footer>
</main>
<div class="lightbox" id="lightbox"><button aria-label="Close">×</button><img alt="Full artwork"></div>
<script>
document.querySelectorAll('[data-copy]').forEach(el=>el.addEventListener('click',async()=>{try{await navigator.clipboard.writeText(el.textContent.trim());const old=el.textContent;el.textContent='COPIED';setTimeout(()=>el.textContent=old,700)}catch(e){}}));
const ipfsStatus=document.getElementById('mooncake-ipfs-status');
if(ipfsStatus){
  const cid=ipfsStatus.dataset.cid;
  const gateways=[`https://dweb.link/ipfs/${cid}`,`https://ipfs.io/ipfs/${cid}`,`https://w3s.link/ipfs/${cid}`];
  let i=0;
  const probe=()=>{
    if(i>=gateways.length){ipfsStatus.textContent='TOKEN IMAGE / IPFS GATEWAYS UNAVAILABLE';return;}
    const url=gateways[i++], test=new Image();
    test.onload=()=>{try{ipfsStatus.textContent='TOKEN IMAGE / IPFS VERIFIED / '+new URL(url).hostname}catch(e){ipfsStatus.textContent='TOKEN IMAGE / IPFS VERIFIED'}};
    test.onerror=probe;
    test.src=url;
  };
  if('requestIdleCallback' in window) requestIdleCallback(probe,{timeout:1200}); else setTimeout(probe,250);
}
const lb=document.getElementById('lightbox'),lbi=lb.querySelector('img');document.querySelectorAll('.zoomable').forEach(z=>z.addEventListener('click',()=>{lbi.src=z.src;lb.classList.add('open')}));lb.addEventListener('click',()=>lb.classList.remove('open'));document.addEventListener('keydown',e=>{if(e.key==='Escape')lb.classList.remove('open')});
</script>
</body></html>