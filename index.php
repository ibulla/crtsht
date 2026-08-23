<?php
declare(strict_types=1);

const TOTAL = 128;
const SITE = 'CRTSHT';

function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function metadata(int $id): ?array {
    if ($id < 1 || $id > TOTAL) return null;
    $file = __DIR__ . '/JSON_1-128/' . $id . '.json';
    if (!is_file($file)) return null;
    $data = json_decode((string)file_get_contents($file), true);
    return is_array($data) ? $data : null;
}
function attrs(array $meta): array {
    $out = [];
    foreach (($meta['attributes'] ?? []) as $a) {
        if (isset($a['trait_type'])) $out[(string)$a['trait_type']] = (string)($a['value'] ?? '');
    }
    return $out;
}
function artworkTitle(int $id, ?array $meta = null): string {
    if ($meta && !empty($meta['name'])) return (string)$meta['name'];
    return '0x' . str_pad(strtolower(dechex($id)), 4, '0', STR_PAD_LEFT);
}
function localImage(int $id): ?string {
    $files = glob(__DIR__ . '/shitpix_jpg_1-128/' . $id . '-*.jpg');
    if (!$files) return null;
    return '/shitpix_jpg_1-128/' . rawurlencode(basename($files[0]));
}
function cid(array $meta): string {
    $uri = (string)($meta['image'] ?? '');
    return str_starts_with($uri, 'ipfs://') ? substr($uri, 7) : '';
}
function routeId(): ?int {
    $path = trim((string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? ''), '/');
    if (preg_match('~^(?:crtsht/)?(\\d{1,3})$~', $path, $m)) return (int)$m[1];
    return null;
}
function envv(string $key): string {
    $v = getenv($key);
    return $v === false ? '' : trim((string)$v);
}
function dbRecord(int $id): ?array {
    $host = envv('CRTSHT_DB_HOST');
    $user = envv('CRTSHT_DB_USER');
    $pass = envv('CRTSHT_DB_PASS');
    $name = envv('CRTSHT_DB_NAME');
    if ($host === '' || $user === '' || $name === '') return null;
    mysqli_report(MYSQLI_REPORT_OFF);
    $db = @new mysqli($host, $user, $pass, $name);
    if ($db->connect_errno) return null;
    $db->set_charset('utf8mb4');
    $stmt = $db->prepare('SELECT `ID`,`ShitID`,`ETH_Adr`,`Hasher_Druck` FROM `ShitID` WHERE `ID` = ? LIMIT 1');
    if (!$stmt) { $db->close(); return null; }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close(); $db->close();
    return is_array($row) ? $row : null;
}
function httpJson(string $url): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_CONNECTTIMEOUT=>4, CURLOPT_TIMEOUT=>8, CURLOPT_FOLLOWLOCATION=>true, CURLOPT_USERAGENT=>'CRTSHT archive/2026']);
    $body = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE); curl_close($ch);
    if (!is_string($body) || $code < 200 || $code >= 300) return null;
    $data = json_decode($body, true);
    return is_array($data) ? $data : null;
}
function ethereumRecord(string $address): ?array {
    $key = envv('ETHERSCAN_API_KEY');
    if ($address === '' || $key === '') return null;
    $url = 'https://api.etherscan.io/v2/api?chainid=1&module=account&action=tokennfttx&address=' . rawurlencode($address) . '&startblock=0&endblock=999999999&sort=asc&apikey=' . rawurlencode($key);
    $data = httpJson($url);
    if (!$data || ($data['status'] ?? '') !== '1' || empty($data['result']) || !is_array($data['result'])) return null;
    return is_array($data['result'][0] ?? null) ? $data['result'][0] : null;
}

$id = routeId();
$detail = $id !== null && $id >= 1 && $id <= TOTAL;
$meta = $detail ? metadata($id) : null;
if ($detail && !$meta) { http_response_code(404); $detail = false; }
$title = $detail && $meta ? artworkTitle($id, $meta) : SITE;
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($detail ? $title.' / CRTSHT' : 'CRTSHT / 128') ?></title>
<meta name="description" content="CRTSHT — 128 unique physical prints recorded on Ethereum in 2021. A physical, cryptographic and networked artwork.">
<style>
:root{--bg:#f2f2ee;--fg:#111;--muted:#777;--line:#c9c9c2;--pad:clamp(18px,3vw,44px)}
*{box-sizing:border-box}html{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono",monospace;background:var(--bg);color:var(--fg)}body{margin:0}.wrap{padding:var(--pad);max-width:1800px;margin:auto}a{color:inherit;text-decoration:none}a:hover{text-decoration:underline}
header{display:flex;justify-content:space-between;align-items:baseline;border-bottom:1px solid var(--fg);padding-bottom:12px;margin-bottom:var(--pad)}.brand{font-size:clamp(25px,4vw,58px);font-weight:700;letter-spacing:-.07em}.small,.label{font-size:11px;text-transform:uppercase;letter-spacing:.08em}.muted{color:var(--muted)}
.intro{display:grid;grid-template-columns:minmax(0,2fr) minmax(260px,1fr);gap:var(--pad);margin:0 0 calc(var(--pad)*1.5)}.intro h1{font-size:clamp(30px,5vw,78px);line-height:.92;letter-spacing:-.065em;margin:0;max-width:14ch}.intro p{margin:0 0 1em;line-height:1.5;max-width:62ch}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(145px,1fr));border-top:1px solid var(--line);border-left:1px solid var(--line)}.card{padding:12px;border-right:1px solid var(--line);border-bottom:1px solid var(--line);min-height:195px;display:flex;flex-direction:column;justify-content:space-between;transition:background .15s}.card:hover{background:#fff}.card img{width:100%;aspect-ratio:1;object-fit:cover;filter:grayscale(1);margin-bottom:10px}.card:hover img{filter:none}.num{display:flex;justify-content:space-between;font-size:12px}
.detail{display:grid;grid-template-columns:minmax(280px,1.1fr) minmax(320px,.9fr);gap:var(--pad)}.art{position:sticky;top:var(--pad)}.art img{width:100%;display:block;background:#ddd}.record{border-top:1px solid var(--fg)}.row{display:grid;grid-template-columns:135px 1fr;gap:16px;padding:10px 0;border-bottom:1px solid var(--line);font-size:12px}.value{overflow-wrap:anywhere}.record h1{font-size:clamp(34px,5vw,72px);letter-spacing:-.06em;margin:.25em 0 .5em}.section-head{margin:34px 0 8px;font-size:11px;text-transform:uppercase;letter-spacing:.08em}.status{display:inline-block;border:1px solid var(--fg);padding:5px 8px;border-radius:100px;font-size:10px;text-transform:uppercase;letter-spacing:.08em}.copy{cursor:pointer}.copy:hover{background:#fff}.note{font-size:13px;line-height:1.55;margin:28px 0}.ipfs-status{font-size:10px;margin-top:8px}.footer{border-top:1px solid var(--fg);margin-top:calc(var(--pad)*1.5);padding-top:12px;display:flex;justify-content:space-between;gap:20px;font-size:11px}
@media(max-width:760px){.intro,.detail{grid-template-columns:1fr}.art{position:static}.row{grid-template-columns:100px 1fr}.grid{grid-template-columns:repeat(2,1fr)}}
</style>
</head><body><main class="wrap">
<header><a class="brand" href="/">CRTSHT</a><span class="small">1—128 / ETHEREUM / 2021—2026</span></header>
<?php if (!$detail): ?>
<section class="intro"><h1>128 physical works. One record each.</h1><div><p>CRTSHT is a series of 128 unique 25 × 25 cm prints created and recorded on Ethereum in 2021. Each physical work carries its own public address, a sealed private key and the first four words of its recovery phrase.</p><p>The PDF used to produce each print was hashed; that SHA-256 fingerprint is preserved in the NFT metadata together with an IPFS image reference. The physical object is therefore not an illustration of the token: it holds the key to it.</p><p class="muted">The archive was recovered in 2026. Blockchain records remain immutable; network infrastructure does not. Original IPFS identifiers are shown unchanged and are resolved through current gateways with a local archival fallback.</p></div></section>
<section class="grid"><?php for($i=1;$i<=TOTAL;$i++): $m=metadata($i); if(!$m) continue; $img=localImage($i); $hex=artworkTitle($i,$m); ?><a class="card" href="/crtsht/<?= $i ?>"><div><?php if($img): ?><img loading="lazy" src="<?= e($img) ?>" alt="<?= e($hex) ?>"><?php endif; ?></div><div class="num"><span><?= e($hex) ?></span><span><?= $i ?>/128</span></div></a><?php endfor; ?></section>
<?php else: $a=attrs($meta); $c=cid($meta); $fallback=localImage($id); $birthday=(int)($a['birthday']??0); $db=dbRecord($id); $ethAddress=trim((string)($db['ETH_Adr']??'')); $eth=$ethAddress!==''?ethereumRecord($ethAddress):null; ?>
<section class="detail"><div class="art"><?php if($c): ?><img id="ipfs-image" data-cid="<?= e($c) ?>" data-fallback="<?= e($fallback ?? '') ?>" src="https://dweb.link/ipfs/<?= e($c) ?>" alt="<?= e($title) ?>"><div id="ipfs-status" class="ipfs-status muted">RESOLVING IPFS / <?= e($c) ?></div><?php elseif($fallback): ?><img src="<?= e($fallback) ?>" alt="<?= e($title) ?>"><?php endif; ?></div><div><div class="small"><a href="/">← INDEX</a></div><div class="record"><h1><?= e($title) ?></h1>
<div class="row"><span class="label">archive id</span><span class="value"><?= $id ?>/128 · /crtsht/<?= $id ?></span></div><div class="row"><span class="label">status</span><span><span class="status">archived</span></span></div><div class="row"><span class="label">series</span><span class="value"><?= e($a['MINT'] ?? '1-128') ?></span></div><?php if($birthday): ?><div class="row"><span class="label">birthday</span><span class="value"><?= e(gmdate('Y-m-d H:i:s', $birthday)) ?> UTC</span></div><?php endif; ?><div class="row"><span class="label">print hash</span><span class="value copy" title="click to copy" data-copy><?= e($a['PRINT HASH'] ?? '') ?></span></div><div class="row"><span class="label">poo</span><span class="value"><?= e($a['POO'] ?? '') ?></span></div><div class="row"><span class="label">author</span><span class="value"><?= e($a['AUTHOR'] ?? '') ?></span></div>
<div class="section-head">ETHEREUM RECORD</div>
<?php if($ethAddress!==''): ?><div class="row"><span class="label">address</span><span class="value"><a target="_blank" rel="noopener" href="https://etherscan.io/address/<?= e($ethAddress) ?>"><?= e($ethAddress) ?> ↗</a></span></div><?php else: ?><div class="row"><span class="label">address</span><span class="value muted">awaiting secure database connection</span></div><?php endif; ?>
<?php if($eth): $tx=(string)($eth['hash']??''); $contract=(string)($eth['contractAddress']??''); $tokenId=(string)($eth['tokenID']??''); $ts=(int)($eth['timeStamp']??0); ?><div class="row"><span class="label">network</span><span class="value">Ethereum Mainnet</span></div><div class="row"><span class="label">token</span><span class="value"><?= e((string)($eth['tokenName']??'')) ?><?php if(($eth['tokenSymbol']??'')!==''): ?> / <?= e((string)$eth['tokenSymbol']) ?><?php endif; ?></span></div><div class="row"><span class="label">token id</span><span class="value"><?= e($tokenId) ?></span></div><div class="row"><span class="label">contract</span><span class="value"><a target="_blank" rel="noopener" href="https://etherscan.io/token/<?= e($contract) ?>?a=<?= e($tokenId) ?>"><?= e($contract) ?> ↗</a></span></div><div class="row"><span class="label">block</span><span class="value"><a target="_blank" rel="noopener" href="https://etherscan.io/block/<?= e((string)($eth['blockNumber']??'')) ?>"><?= e((string)($eth['blockNumber']??'')) ?> ↗</a></span></div><?php if($ts): ?><div class="row"><span class="label">timestamp</span><span class="value"><?= e(gmdate('Y-m-d H:i:s',$ts)) ?> UTC</span></div><?php endif; ?><div class="row"><span class="label">transaction</span><span class="value"><a target="_blank" rel="noopener" href="https://etherscan.io/tx/<?= e($tx) ?>"><?= e($tx) ?> ↗</a></span></div><div class="row"><span class="label">from</span><span class="value"><?= e((string)($eth['from']??'')) ?></span></div><div class="row"><span class="label">to</span><span class="value"><?= e((string)($eth['to']??'')) ?></span></div><div class="row"><span class="label">confirmations</span><span class="value"><?= e((string)($eth['confirmations']??'')) ?></span></div><?php elseif($ethAddress!==''): ?><div class="row"><span class="label">chain data</span><span class="value muted">address recovered · Etherscan API not configured or unavailable</span></div><?php endif; ?>
<div class="section-head">NETWORK / METADATA</div><div class="row"><span class="label">ipfs uri</span><span class="value copy" title="click to copy" data-copy><?= e($meta['image'] ?? '') ?></span></div><?php if($c): ?><div class="row"><span class="label">gateway</span><span class="value"><a target="_blank" rel="noopener" href="https://ipfs.io/ipfs/<?= e($c) ?>">open current IPFS gateway ↗</a></span></div><?php endif; ?><div class="row"><span class="label">metadata</span><span class="value"><a href="/JSON_1-128/<?= $id ?>.json" target="_blank">original JSON ↗</a></span></div></div><p class="note"><?= e($meta['description'] ?? '') ?></p><p class="note muted">The original 2021 metadata remains untouched. Ethereum and IPFS information shown here is a live archival reading of that record; the presentation layer was reconstructed in 2026.</p></div></section>
<?php endif; ?><footer class="footer"><span>CRTSHT / iBulla</span><span>PHYSICAL · HASH · NFT · IPFS · KEY</span></footer></main>
<script>document.querySelectorAll('[data-copy]').forEach(el=>el.addEventListener('click',async()=>{try{await navigator.clipboard.writeText(el.textContent.trim());const old=el.textContent;el.textContent='COPIED';setTimeout(()=>el.textContent=old,700)}catch(e){}}));const img=document.getElementById('ipfs-image');if(img){const cid=img.dataset.cid,status=document.getElementById('ipfs-status'),fallback=img.dataset.fallback;const gateways=[`https://ipfs.io/ipfs/${cid}`,`https://${cid}.ipfs.dweb.link/`];let n=0;img.addEventListener('load',()=>{status.textContent='IPFS / RESOLVED / '+new URL(img.src).hostname});img.addEventListener('error',()=>{if(n<gateways.length){status.textContent='IPFS / TRYING GATEWAY '+(n+1);img.src=gateways[n++]}else if(fallback){status.textContent='IPFS / GATEWAYS UNAVAILABLE / LOCAL ARCHIVAL COPY';img.src=fallback}else{status.textContent='IPFS / UNAVAILABLE'}})}</script></body></html>