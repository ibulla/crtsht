<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>The Draw / CRTSHT</title>
<meta name="description" content="Enter the CRTSHT draw. You choose to own one. Chance chooses which.">
<link rel="stylesheet" href="/site.css?v=8">
<style>
.draw-page{max-width:1180px}.draw-hero{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(280px,.85fr);gap:var(--pad);align-items:end;margin-bottom:calc(var(--pad)*1.25)}
.draw-hero h1{font-size:clamp(48px,8vw,124px);line-height:.82;letter-spacing:-.075em;margin:.08em 0 .22em;max-width:8ch}.draw-lead{font-size:clamp(18px,2.2vw,31px);line-height:1.08;letter-spacing:-.035em;max-width:19ch;margin:0}.draw-copy{font-size:13px;line-height:1.58;max-width:62ch}.draw-copy p{margin:0 0 1em}
.system-window{border:1px solid var(--fg);margin:0 0 calc(var(--pad)*1.3);background:rgba(242,242,238,.72)}.system-bar{display:flex;justify-content:space-between;gap:20px;padding:7px 9px;border-bottom:1px solid var(--fg);font-size:10px;text-transform:uppercase;letter-spacing:.08em;flex-wrap:wrap}.system-body{padding:18px}.system-status{border-top:1px solid var(--line);padding:8px 9px;font-size:10px;letter-spacing:.04em;display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap}.crt-blink{animation:crtBlink 2.3s steps(1,end) infinite}@keyframes crtBlink{0%,67%,100%{opacity:1}68%,82%{opacity:0}}
.terminal-title{font-size:clamp(30px,4.5vw,66px);line-height:.92;letter-spacing:-.06em;margin:0 0 18px;max-width:14ch}.terminal-copy{font-size:13px;line-height:1.55;max-width:68ch;margin:0 0 22px}.entry-grid{display:grid;grid-template-columns:repeat(3,1fr);border-top:1px solid var(--line);border-left:1px solid var(--line);margin-top:20px}.entry{border-right:1px solid var(--line);border-bottom:1px solid var(--line);padding:16px;min-height:150px;display:flex;flex-direction:column;justify-content:space-between}.entry strong{font-size:clamp(30px,4vw,54px);letter-spacing:-.06em}.entry span{font-size:10px;text-transform:uppercase;letter-spacing:.08em}.entry p{font-size:11px;line-height:1.45;color:var(--muted);margin:8px 0 0}
.terminal-action{display:flex;justify-content:space-between;align-items:center;gap:18px;margin-top:18px;flex-wrap:wrap}.terminal-button{display:inline-flex;align-items:center;justify-content:center;background:var(--fg);color:var(--bg);border:0;padding:13px 18px;font:inherit;font-size:12px;letter-spacing:.06em;text-transform:uppercase;cursor:not-allowed}.terminal-note{font-size:10px;color:var(--muted);max-width:54ch;line-height:1.5}
.draws{border-top:1px solid var(--fg);margin-top:calc(var(--pad)*1.35)}.draw-row{display:grid;grid-template-columns:120px 1fr auto;gap:20px;align-items:baseline;padding:16px 0;border-bottom:1px solid var(--line)}.draw-row .date{font-size:11px;text-transform:uppercase;letter-spacing:.08em}.draw-row strong{font-size:clamp(22px,3vw,38px);letter-spacing:-.045em}.draw-row .state{font-size:10px;text-transform:uppercase;letter-spacing:.08em;border:1px solid var(--fg);border-radius:100px;padding:5px 8px}.draw-note{font-size:13px;line-height:1.55;max-width:70ch;margin:20px 0 0}.receipt{margin-top:calc(var(--pad)*1.35);display:grid;grid-template-columns:minmax(190px,.55fr) minmax(0,1.45fr);gap:var(--pad);border-top:1px solid var(--fg);padding-top:22px}.receipt h2{font-size:clamp(28px,4vw,58px);line-height:.92;letter-spacing:-.055em;margin:0}.receipt-box{border:1px solid var(--fg);font-size:11px}.receipt-line{display:grid;grid-template-columns:130px 1fr;gap:16px;padding:9px 11px;border-bottom:1px solid var(--line)}.receipt-line:last-child{border-bottom:0}.receipt-line span:first-child{color:var(--muted);text-transform:uppercase;letter-spacing:.05em}
@media(max-width:760px){.draw-hero,.receipt{grid-template-columns:1fr}.entry-grid{grid-template-columns:1fr}.draw-row{grid-template-columns:1fr auto}.draw-row .date{grid-column:1/-1}.receipt-line{grid-template-columns:105px 1fr}}
@media(prefers-reduced-motion:reduce){.crt-blink{animation:none}}
</style>
</head>
<body><main class="wrap draw-page">
<header>
<a class="brand" href="/">CR¥P70$H!7.DR4W</a>
<nav class="nav"><a href="/">Archive</a><a href="/lore">The Lore</a><a href="/oracle">The Oracle</a><a href="/draw" aria-current="page">Draw</a></nav>
</header>
<section class="draw-hero">
<div><div class="eyebrow">128 WORKS / 128 OWNERS / CHANCE DECIDES</div><h1>ENTER THE DRAW.</h1><p class="draw-lead">You choose to own one. Chance chooses which.</p></div>
<div class="draw-copy"><p>A draw entry reserves one physical CRTSHT. It does not reserve a number, colour, face or favourite.</p><p>At the next draw, every entry is matched with one remaining work by chance. The result becomes part of the object's provenance.</p></div>
</section>
<section class="system-window" aria-label="CRTSHT draw terminal">
<div class="system-bar"><span>CRTSHT / DRAW TERMINAL</span><span class="crt-blink">ENTRY SYSTEM PREPARING</span></div>
<div class="system-body">
<div class="eyebrow">BATCH 01 / PRE-OPEN</div><h2 class="terminal-title">SECURE A PLACE IN THE DISPERSAL.</h2>
<p class="terminal-copy">One entry equals one genuine 20 × 20 cm physical original, with its recovered Ethereum record, wallet material, Mooncake and packaging. Entries are numbered in the order they arrive. The artwork itself remains unknown until the draw.</p>
<div class="entry-grid" aria-label="Draw entry quantities">
<div class="entry"><div><span>ONE / ENTRY</span><strong>1×</strong></div><p>One entry number. One unknown CRTSHT.</p></div>
<div class="entry"><div><span>PAIR / ENTRIES</span><strong>2×</strong></div><p>Two consecutive entry numbers. Two independent draws.</p></div>
<div class="entry"><div><span>TRIO / ENTRIES</span><strong>3×</strong></div><p>Three consecutive entry numbers. Chance gets three attempts.</p></div>
</div>
<div class="terminal-action"><button class="terminal-button" type="button" disabled>DRAW ENTRY / COMING ONLINE</button><span class="terminal-note">Checkout is intentionally not live yet. Price, payment and delivery rules will be connected here before entries open.</span></div>
</div>
<div class="system-status"><span>OBJECT UNKNOWN / ENTRY RECORDED / DRAW PENDING</span><span>DRAW 01 · 25.09.2026</span></div>
</section>
<section class="draws">
<div class="draw-row"><span class="date">25.09.2026</span><strong>DRAW 01 / FIRST DISPERSAL</strong><span class="state">NEXT</span></div>
<div class="draw-row"><span class="date">31.10.2026</span><strong>DRAW 02 / FINAL DISPERSAL</strong><span class="state">FINAL</span></div>
<p class="draw-note">Entries collected before each cut-off enter the next draw. After Draw 01, the terminal reopens for the remaining works. The final draw takes place on 31 October, before the room disappears.</p>
</section>
<section class="receipt">
<div><div class="eyebrow">AFTER ENTRY</div><h2>YOUR PLACE, NOT YOUR SHIT.</h2></div>
<div><div class="receipt-box" aria-label="Example draw receipt">
<div class="receipt-line"><span>ENTRY</span><strong>#0027</strong></div>
<div class="receipt-line"><span>BATCH</span><span>DRAW 01</span></div>
<div class="receipt-line"><span>ENTERED</span><span>TIMESTAMPED</span></div>
<div class="receipt-line"><span>OBJECT</span><span>UNKNOWN</span></div>
<div class="receipt-line"><span>STATUS</span><span>WAITING FOR DRAW</span></div>
<div class="receipt-line"><span>NEXT DRAW</span><span>25.09.2026</span></div>
</div></div>
</section>
<footer class="footer"><span>CRTSHT / THE DRAW</span><span>OPEN → QUEUED → ASSIGNED</span></footer>
</main></body></html>