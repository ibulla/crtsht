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
<meta name="description" content="The lore behind CRTSHT: 128 creatures, TGPs, mooncakes, hashes, wallets, a wall, four words and one draw.">
<link rel="stylesheet" href="/site.css?v=7">
<style>
.lore-page{overflow-x:hidden}
.lore-page .wrap{position:relative;z-index:2}
.coin-rain{position:fixed;inset:0;overflow:hidden;pointer-events:none;z-index:0}
.coin-drop{position:absolute;top:-140px;left:var(--x);width:var(--size);opacity:var(--opacity);animation:coinFall var(--duration) linear infinite;animation-delay:var(--delay);will-change:transform}
.coin-drop img{display:block;width:100%;filter:grayscale(.72) contrast(.9);transform-origin:50% 50%;animation:coinTurn var(--spin) linear infinite;animation-delay:var(--spin-delay);will-change:transform}
@keyframes coinFall{0%{transform:translate3d(0,-180px,0)}50%{transform:translate3d(calc(var(--drift) * .42),50vh,0)}100%{transform:translate3d(var(--drift),calc(100vh + 220px),0)}}
@keyframes coinTurn{0%{transform:rotate(var(--start-rot)) translateX(0) translateY(0)}25%{transform:rotate(calc(var(--start-rot) + var(--quarter-turn))) translateX(2px) translateY(-2px)}50%{transform:rotate(calc(var(--start-rot) + var(--half-turn))) translateX(0) translateY(2px)}75%{transform:rotate(calc(var(--start-rot) + var(--three-quarter-turn))) translateX(-2px) translateY(-1px)}100%{transform:rotate(calc(var(--start-rot) + var(--full-turn))) translateX(0) translateY(0)}}
.lore-opening{margin:0 0 calc(var(--pad)*1.45)}
.lore-hero{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:clamp(20px,5vw,72px);align-items:start}
.lore-hero-copy{min-width:0}
.lore-opening .eyebrow{margin-bottom:12px}
.lore-opening .hero-image{display:block}
.lore-opening .hero-coin{width:clamp(112px,16vw,210px);height:auto;margin-top:2px}
.lore-opening .fortune{font-size:clamp(31px,5.4vw,70px);line-height:.93;letter-spacing:-.06em;margin:0 0 22px;max-width:15ch}
.lore-opening .origin{font-size:clamp(14px,1.25vw,18px);line-height:1.55;max-width:64ch;margin:0}
.system-window{border:1px solid var(--fg);margin:30px 0 0;background:rgba(242,242,238,.72);backdrop-filter:blur(2px)}
.system-bar{display:flex;justify-content:space-between;gap:20px;padding:7px 9px;border-bottom:1px solid var(--fg);font-size:10px;text-transform:uppercase;letter-spacing:.08em}
.system-body{padding:18px}.system-body .shhh{font-size:clamp(22px,3vw,40px);letter-spacing:-.045em;margin:0 0 12px}.system-body p{font-size:13px;line-height:1.55;max-width:70ch;margin:0 0 .8em}.system-status{border-top:1px solid var(--line);padding:8px 9px;font-size:10px;letter-spacing:.04em;display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap}
.myth-line{font-size:clamp(20px,2.2vw,31px)!important;line-height:1.15!important;letter-spacing:-.035em;max-width:28ch;margin:20px 0!important}
.relic{display:block;width:100%;margin:22px 0 0;padding:0;border:0;background:none;color:inherit;text-align:left;font:inherit;cursor:zoom-in}.relic img{display:block;width:100%;border:1px solid var(--line);background:#fff}.relic-meta{display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-top:8px;font-size:10px;line-height:1.45;color:var(--muted);text-transform:uppercase;letter-spacing:.06em}.relic:hover .relic-open,.relic:focus-visible .relic-open{text-decoration:underline;color:var(--fg)}
.zoomable-lore{cursor:zoom-in}
.spec-table{border:1px solid var(--line);margin-top:20px;font-size:12px;line-height:1.45}
.spec-row{display:grid;grid-template-columns:118px 24px minmax(0,1fr);align-items:baseline;padding:10px 12px;border-bottom:1px solid var(--line)}
.spec-row:last-child{border-bottom:0}
.spec-key{font-weight:700;letter-spacing:.04em}
.spec-arrow{color:var(--muted)}
.spec-value{overflow-wrap:anywhere}
.public-secret{display:grid;grid-template-columns:1fr 1fr;border-top:1px solid var(--line);border-left:1px solid var(--line);margin:22px 0}.public-secret>div{border-right:1px solid var(--line);border-bottom:1px solid var(--line);padding:16px}.public-secret h3{font-size:11px;text-transform:uppercase;letter-spacing:.08em;margin:0 0 14px}.public-secret p{font-size:12px;line-height:1.55;margin:0 0 .55em}
.cake-pair{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin:20px 0;max-width:none}
.cake-pair a{display:block;min-width:0}
.cake-pair img{display:block;width:100%;aspect-ratio:1;object-fit:cover}
.founder{display:grid;grid-template-columns:minmax(220px,.8fr) minmax(0,1.2fr);gap:var(--pad);align-items:start}.founder img{display:block;width:100%;max-width:520px}.founder-copy{max-width:620px}.founder-copy .name{font-size:clamp(34px,5vw,72px);line-height:.9;letter-spacing:-.06em;margin:0 0 20px}.founder-copy p{font-size:clamp(14px,1.25vw,18px);line-height:1.55;margin:0 0 1em}.lore-last{font-size:clamp(22px,3vw,42px);line-height:1.05;letter-spacing:-.045em;max-width:24ch;margin:30px 0 0}
@media(max-width:700px){.founder,.public-secret{grid-template-columns:1fr}.coin-drop{opacity:calc(var(--opacity) * .72)}.lore-hero{gap:16px}.lore-opening .hero-coin{width:clamp(86px,25vw,112px)}.spec-row{grid-template-columns:88px 18px minmax(0,1fr);padding:9px 10px}}
@media(prefers-reduced-motion:reduce){.coin-rain{display:none}}
</style>
</head>
<body class="lore-page">
<div class="coin-rain" id="coin-rain" aria-hidden="true"></div>
<main class="wrap lore">
<header>
<a class="brand" href="/">CRTSHT</a>
<nav class="nav"><a href="/">Archive</a><a href="/lore" aria-current="page">The Lore</a><a href="/oracle">The Oracle</a></nav>
</header>

<section class="lore-opening">
<div class="lore-hero">
<div class="lore-hero-copy">
<div class="eyebrow">THE LORE</div>
<p class="fortune">The Internet has forgotten.<br>The blockchain didn't.</p>
<p class="origin">CRTSHT began in 2021, when images discovered wallets and almost every browser tab seemed to promise a new economy. From the great autonomous <strong>MORE-Algorithm — My Only Rare Experience</strong> came 128 creatures. They were generated, printed, hashed, minted, pinned, sealed — and then left waiting.</p>
</div>
<img class="hero-image hero-coin" src="/img/TheCoin.png" alt="CRTSHT mooncake coin" fetchpriority="high">
</div>

<div class="system-window" aria-label="Recovered CRTSHT system message">
<div class="system-bar"><span>CRYPTOSHIT / MORE SYSTEM</span><span>RL-HOOK ONLINE</span></div>
<div class="system-body">
<p class="shhh">Shhhh. Take it easy.</p>
<p>Of course your shit is pinned in an InterPlanetary File System, verified on a blockchain, connected to a wallet and surrounded by more metadata than it reasonably needs.</p>
<p>But hey, who cares? There is only one physical original. Hang it on a wall, keep it in a box, pass it on. It should bring you luck and happiness.</p>
</div>
<div class="system-status"><span>WELCOME TO THE INTERPLANETARY FILE SYSTEM</span><span>CONNECTION ESTABLISHED ..........97%</span></div>
</div>
</section>

<section class="chapter">
<h2>The creatures</h2>
<div class="prose">
<p>Before there was a collection, there was a cast.</p>
<p><strong>Kin no unko</strong> appeared first — or at least claims to have. Dr. Slurp wandered in soon after. Somewhere else the Ice-Emoji saga froze mid-expression. Others arrived without names and seem happier that way.</p>
<p>Colours drifted. Eyes changed. Accessories accumulated. Backgrounds mutated. Nothing developed a reliable taxonomy.</p>
<p class="myth-line">They resemble one another just enough to be family, and differ just enough to cause trouble.</p>
<p>Each received a hexadecimal identity from <strong>0x0001</strong> to <strong>0x0080</strong>. No ranking. No rarity chart. No chosen hero. Just 128 small lives inside the same badly behaved universe.</p>
<div class="gallery">
<?php foreach($sampleIds as $id): $img=crt_artwork($id); $meta=crt_metadata($id); if(!$img||!$meta) continue; ?>
<a href="/crtsht/<?= $id ?>"><img loading="lazy" decoding="async" src="<?= crt_e($img) ?>" alt="<?= crt_e(crt_title($id,$meta)) ?>"></a>
<?php endforeach; ?>
</div>
</div>
</section>

<section class="chapter">
<h2>The TGP</h2>
<div class="prose">
<p>MORE generated the creatures as ready-to-print files. The old instructions gave the result a wonderfully overconfident name: <strong>TGP — The Genuine Print</strong>.</p>
<p>One image. One 20 × 20 cm physical original. One hexadecimal identity. Instead of signing the front with a pencil, the system built an unnecessarily elaborate bridge between the object and the network.</p>
<p>The diagram below is not a reconstruction. It survived with the project.</p>
<button type="button" class="relic" data-lightbox-src="/img/2021-genuine-print.jpg" data-lightbox-alt="2021 CRTSHT Genuine Print diagram showing the physical print, RL-HOOK, public metadata and secret key">
<img src="/img/2021-genuine-print.jpg" alt="2021 CRTSHT Genuine Print diagram showing the physical print, RL-HOOK, public metadata and secret key" loading="lazy" decoding="async">
<span class="relic-meta"><span>2021-GENUINE-PRINT.JPG / ORIGINAL PROJECT RELIC</span><span class="relic-open">OPEN FULL SIZE +</span></span>
</button>
<p class="myth-line">Part instruction manual. Part proof. Part promise from a future that arrived differently.</p>
<div class="spec-table" role="table" aria-label="TGP object specification">
<div class="spec-row" role="row"><span class="spec-key" role="cell">TGP</span><span class="spec-arrow" role="cell">→</span><span class="spec-value" role="cell">THE GENUINE PRINT</span></div>
<div class="spec-row" role="row"><span class="spec-key" role="cell">RL-HOOK</span><span class="spec-arrow" role="cell">→</span><span class="spec-value" role="cell">REAL-LIFE CONNECTION</span></div>
<div class="spec-row" role="row"><span class="spec-key" role="cell">FORMAT</span><span class="spec-arrow" role="cell">→</span><span class="spec-value" role="cell">20 × 20 CM</span></div>
<div class="spec-row" role="row"><span class="spec-key" role="cell">EDITION</span><span class="spec-arrow" role="cell">→</span><span class="spec-value" role="cell">1 PHYSICAL ORIGINAL</span></div>
<div class="spec-row" role="row"><span class="spec-key" role="cell">IDENTITY</span><span class="spec-arrow" role="cell">→</span><span class="spec-value" role="cell">HEX / 0x0001—0x0080</span></div>
<div class="spec-row" role="row"><span class="spec-key" role="cell">PRINT</span><span class="spec-arrow" role="cell">→</span><span class="spec-value" role="cell">SHA-256 FINGERPRINT</span></div>
</div>
<p>The technology is very serious. The subject matter is under no obligation to be.</p>
</div>
</section>

<section class="chapter">
<h2>Public / Secret</h2>
<div class="prose">
<p>The 2021 system divided every TGP into two territories: <strong>PUBLIC META</strong> and <strong>SECRET KEY</strong>. Five years later, that distinction is still the cleanest way into the object.</p>
<div class="public-secret">
<div>
<h3>PUBLIC META</h3>
<p>hex identity</p>
<p>TGP fingerprint</p>
<p>Ethereum wallet + token record</p>
<p>IPFS references</p>
<p>four visible words</p>
</div>
<div>
<h3>SECRET KEY</h3>
<p>24-word mnemonic</p>
<p>private wallet material</p>
<p>sealed with the physical object</p>
<p>only 4U</p>
</div>
</div>
<p>The four visible words do not open the wallet. That would be a terrible feature.</p>
<p>They do something much less useful and therefore much more appropriate: they let the archive recognize the physical work and open its private fortune.</p>
<p>The remaining twenty words stay sealed. Some doors are better when they remain doors.</p>
<p><a href="/oracle">Ask The Oracle →</a></p>
</div>
</section>

<section class="chapter">
<h2>The mooncake</h2>
<div class="prose">
<p>Every CRTSHT has a second body: a mooncake. Or, depending on which part of the old system you ask, a <strong>金のうんこ</strong>.</p>
<p>The physical TGP carries the creature. The NFT points to the cake. The cake carries the identity of the same strange object through another route.</p>
<div class="cake-pair">
<?php foreach([1,64] as $id): $cake=crt_cake($id); if($cake): ?><a href="/crtsht/<?= $id ?>"><img loading="lazy" decoding="async" src="<?= crt_e($cake) ?>" alt="Mooncake <?= $id ?>"></a><?php endif; endforeach; ?>
</div>
<div class="codebox">
HEX ID → 0x0001<br>
TGP → SHA-256 FINGERPRINT<br>
WALLET → ETHEREUM PUBLIC ADDRESS<br>
TOKEN IMAGE → IPFS CID<br>
METADATA → IPFS CID<br>
POO → SHORT INTERNAL HASH
</div>
<p>A hash points to an image carrying the signs of another hash, attached to a physical print whose fingerprint lives in the metadata.</p>
<p>The mooncake is not decoration and not quite a certificate. It is closer to an amulet with too much metadata.</p>
</div>
</section>

<section class="chapter">
<h2>The wall</h2>
<div class="prose">
<p>The family was always supposed to meet in real life. An exhibition plan survived from the unfinished project, waiting quietly beside the files.</p>
<p class="myth-line">Together once before our disperse.</p>
<p>The 128 originals can finally occupy the wall that was already drawn for them. It is less a display than a temporary family portrait: complete only until the first work leaves.</p>
<img class="plan zoomable-lore" src="/img/Exhibition-plan.png" alt="Original CRTSHT exhibition plan" loading="lazy" decoding="async" data-lightbox-src="/img/Exhibition-plan.png" data-lightbox-alt="Original CRTSHT exhibition plan">
<p class="caption">EXHIBITION-PLAN.PNG / ORIGINAL PROJECT FILE / CLICK TO ENLARGE</p>
</div>
</section>

<section class="chapter">
<h2>The draw</h2>
<div class="prose">
<p>The complete collection is public. The assignment is not.</p>
<p>Everyone who enters receives one CRTSHT. Chance decides which identity leaves the wall with them. Nobody gets to rescue the prettiest one first.</p>
<div class="ritual">
<div><strong>SEE</strong><p>All 128 works and their records remain visible.</p></div>
<div><strong>DRAW</strong><p>A number is pulled from the box.</p></div>
<div><strong>OWN</strong><p>The TGP, mooncake and sealed key leave together in a pizza box.</p></div>
<div><strong>UNLOCK</strong><p>The four visible words wake The Oracle.</p></div>
</div>
<p style="margin-top:20px">With every draw the wall gets emptier. The archive stays full.</p>
</div>
</section>

<section class="chapter">
<h2>The shit</h2>
<div class="founder">
<img src="/img/About_crtsht.jpg" alt="Marco Spitzbarth holding a physical CRTSHT print" loading="lazy" decoding="async">
<div class="founder-copy">
<div class="eyebrow">MARCO SPITZBARTH / iBULLA</div>
<p class="name">Shit happens.</p>
<p>CRTSHT was initiated in Zürich in 2021 by artist Marco Spitzbarth as part of the MyOnlyRare / MORE experiment. It started with curiosity about digital ownership and ended up producing physical prints, wallets, hashes, mooncakes, NFTs and sealed keys.</p>
<p>The works were made. The blockchain entries were made. The planned encounter never quite happened.</p>
<p>Five years later, the surrounding internet has changed enough to become part of the work. The files are being recovered, the records read again, and the 128 can finally meet before they leave one another.</p>
<p class="lore-last">The archive takes the work seriously. CRTSHT is under no such obligation.</p>
</div>
</div>
</section>

<footer class="footer"><span>CRTSHT / THE LORE</span><span>2021 → 2026 · CONNECTION 97%</span></footer>
</main>

<div class="lightbox" id="lore-lightbox" aria-hidden="true">
<button type="button" aria-label="Close">×</button>
<img src="" alt="">
</div>

<script>
(()=>{
  const layer=document.getElementById('coin-rain');
  if(!layer||window.matchMedia('(prefers-reduced-motion: reduce)').matches)return;
  const mobile=window.innerWidth<700;
  const count=mobile?7:14;
  for(let i=0;i<count;i++){
    const drop=document.createElement('span');
    drop.className='coin-drop';
    const size=(mobile?26:30)+Math.random()*(mobile?44:62);
    const duration=26+Math.random()*28;
    const delay=-(Math.random()*duration);
    const spin=18+Math.random()*24;
    const clockwise=Math.random()>.5;
    const turn=clockwise?360:-360;
    const start=Math.floor(Math.random()*360);
    const drift=(-55+Math.random()*110)+'px';
    drop.style.setProperty('--x',(Math.random()*97)+'%');
    drop.style.setProperty('--size',size+'px');
    drop.style.setProperty('--duration',duration+'s');
    drop.style.setProperty('--delay',delay+'s');
    drop.style.setProperty('--spin',spin+'s');
    drop.style.setProperty('--spin-delay',(-(Math.random()*spin))+'s');
    drop.style.setProperty('--drift',drift);
    drop.style.setProperty('--opacity',(0.035+Math.random()*0.075).toFixed(3));
    drop.style.setProperty('--start-rot',start+'deg');
    drop.style.setProperty('--quarter-turn',(turn*.25)+'deg');
    drop.style.setProperty('--half-turn',(turn*.5)+'deg');
    drop.style.setProperty('--three-quarter-turn',(turn*.75)+'deg');
    drop.style.setProperty('--full-turn',turn+'deg');
    const img=document.createElement('img');
    img.src='/img/TheCoin.png';
    img.alt='';
    img.loading='eager';
    drop.appendChild(img);
    layer.appendChild(drop);
  }
})();

(()=>{
  const lightbox=document.getElementById('lore-lightbox');
  if(!lightbox)return;
  const image=lightbox.querySelector('img');
  const close=()=>{
    lightbox.classList.remove('open');
    lightbox.setAttribute('aria-hidden','true');
    image.removeAttribute('src');
    image.alt='';
  };
  const open=(source,alt)=>{
    image.src=source;
    image.alt=alt||'';
    lightbox.classList.add('open');
    lightbox.setAttribute('aria-hidden','false');
  };
  document.querySelectorAll('[data-lightbox-src]').forEach(el=>{
    el.addEventListener('click',event=>{
      event.preventDefault();
      open(el.dataset.lightboxSrc,el.dataset.lightboxAlt||el.getAttribute('alt')||'');
    });
  });
  lightbox.addEventListener('click',close);
  lightbox.querySelector('button').addEventListener('click',event=>{event.stopPropagation();close();});
  document.addEventListener('keydown',event=>{if(event.key==='Escape'&&lightbox.classList.contains('open'))close();});
})();
</script>
</body></html>