<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
header('X-Robots-Tag: index, follow', true);
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>CRTSHT / LEGAL</title>
<link rel="stylesheet" href="/site.css?v=8">
<style>
.legal{max-width:980px;margin:0 auto;padding:clamp(24px,5vw,70px) var(--pad)}
.legal-head{border-bottom:1px solid var(--fg);padding-bottom:24px;margin-bottom:42px}
.legal-head h1{font-size:clamp(58px,12vw,150px);line-height:.78;letter-spacing:-.08em;margin:12px 0 20px}
.legal-grid{display:grid;grid-template-columns:minmax(180px,.45fr) minmax(0,1fr);gap:30px;border-top:1px solid var(--fg);padding:26px 0}
.legal-grid h2{font-size:12px;letter-spacing:.08em;margin:0;text-transform:uppercase}
.legal-copy{max-width:66ch}.legal-copy p{font-size:13px;line-height:1.65;margin:0 0 16px}
.legal-copy a{color:inherit}.legal-meta{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace}
@media(max-width:700px){.legal-grid{grid-template-columns:1fr;gap:14px}}
</style>
</head>
<body>
<main class="legal">
<header class="legal-head"><div class="eyebrow">CRTSHT / LEGAL NOTICE</div><h1>LEGAL<br>SHIT.</h1><p>Operator, sales and privacy information for cryptoshit.info.</p></header>

<section class="legal-grid"><h2>Operator / Seller</h2><div class="legal-copy legal-meta">
<p><strong>Spitzbarth Juwelier GmbH</strong><br>Neumarkt 8<br>8001 Zürich<br>Switzerland</p>
<p>Email: <a href="mailto:info@spitzbarth.com">info@spitzbarth.com</a><br>Website: cryptoshit.info</p>
<p>CRTSHT is an art project by Marco Spitzbarth / iBulla.</p>
</div></section>

<section class="legal-grid"><h2>The Draw / Sales</h2><div class="legal-copy">
<p>CRTSHT is a limited edition of 128 physical artworks. A paid draw entry entitles the buyer to one physical CRTSHT. The specific artwork is not selected at checkout: it is assigned by chance at the scheduled draw.</p>
<p>Prices are shown in Swiss francs (CHF). Switzerland is available as a delivery destination. Payment can be made through the payment methods offered at checkout or by bank transfer after requesting payment details.</p>
<p>A reservation is not active for the draw until payment has been received and confirmed. The scheduled draw and later delivery of the physical work are therefore separate from the moment of payment.</p>
</div></section>

<section class="legal-grid"><h2>Privacy</h2><div class="legal-copy">
<p>For reservations, payment, the draw and delivery, we process the information supplied by the buyer, including name, email address, mobile number and postal address. We use this information only insofar as necessary to administer the purchase, payment, draw, customer communication and delivery, and to meet applicable legal and accounting obligations.</p>
<p>Online card and TWINT payments are processed by Stripe. Payment information entered on Stripe's hosted checkout is processed by Stripe; CRTSHT does not receive or store full card details.</p>
<p>Questions concerning personal data or a purchase can be sent to the contact address above.</p>
</div></section>

<section class="legal-grid"><h2>Project / Records</h2><div class="legal-copy">
<p>The blockchain, NFT and archive records presented on this website form part of the artistic work and its provenance. Historical 2021 metadata is preserved as archival material. Ownership of a physical CRTSHT does not imply transfer of copyright or other intellectual-property rights unless expressly agreed otherwise.</p>
</div></section>

<footer class="footer"><span>CRTSHT / iBulla</span><span><a href="/">BACK TO ARCHIVE</a> · <a href="/draw">THE DRAW</a></span></footer>
</main>
</body>
</html>
