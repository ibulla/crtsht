<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax'
    ]);
    session_start();
}

function draw_batch(): array {
    $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Zurich'));
    $draw1 = new DateTimeImmutable('2026-09-25 18:00:00', new DateTimeZone('Europe/Zurich'));
    $draw2 = new DateTimeImmutable('2026-10-17 18:00:00', new DateTimeZone('Europe/Zurich'));
    if ($now < $draw1) return ['01', '25.09.2026', 'FIRST DISPERSAL'];
    if ($now < $draw2) return ['02', '17.10.2026', 'SECOND DISPERSAL'];
    return ['03', '31.10.2026', 'FINAL DISPERSAL'];
}

function draw_reserved_slots(): ?int {
    $db = crt_db();
    if (!$db) return null;
    $sql = "SELECT COUNT(*) AS total FROM CRTSHT_Draw_Entries e INNER JOIN CRTSHT_Draw_Reservations r ON r.ID=e.ReservationID WHERE r.Status IN ('reserved','paid')";
    $result = $db->query($sql);
    $total = $result ? (int)($result->fetch_assoc()['total'] ?? 0) : null;
    if ($result) $result->free();
    $db->close();
    return $total;
}

function draw_clean(string $value, int $max): string {
    $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    return mb_substr($value, 0, $max, 'UTF-8');
}

[$currentBatch, $nextDrawDate, $currentDrawName] = draw_batch();
$csrf = $_SESSION['draw_csrf'] ?? '';
if (!is_string($csrf) || strlen($csrf) < 32) {
    $csrf = bin2hex(random_bytes(24));
    $_SESSION['draw_csrf'] = $csrf;
}

$form = [
    'quantity' => '1', 'name' => '', 'email' => '', 'mobile' => '',
    'address' => '', 'plz' => '', 'city' => '', 'country' => ''
];
$error = '';
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $default) {
        if ($key === 'quantity') continue;
        $form[$key] = (string)($_POST[$key] ?? '');
    }
    $form['quantity'] = (string)($_POST['quantity'] ?? '1');

    $postedCsrf = (string)($_POST['csrf'] ?? '');
    $honeypot = trim((string)($_POST['company'] ?? ''));
    $quantity = filter_var($form['quantity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 3]]);

    $name = draw_clean($form['name'], 120);
    $email = strtolower(draw_clean($form['email'], 190));
    $mobile = draw_clean($form['mobile'], 50);
    $address = draw_clean($form['address'], 190);
    $plz = draw_clean($form['plz'], 24);
    $city = draw_clean($form['city'], 120);
    $country = draw_clean($form['country'], 120);

    if (!hash_equals($csrf, $postedCsrf) || $honeypot !== '') {
        $error = 'The terminal rejected this request. Please reload the page and try again.';
    } elseif ($quantity === false) {
        $error = 'Choose one, two or three draw entries.';
    } elseif ($name === '' || $email === '' || $mobile === '' || $address === '' || $plz === '' || $city === '' || $country === '') {
        $error = 'Please complete all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/^[0-9+() .\/-]{6,50}$/', $mobile)) {
        $error = 'Please enter a valid mobile number.';
    } else {
        $db = crt_db();
        if (!$db) {
            $error = 'The draw terminal cannot reach the archive database right now.';
        } else {
            $lockName = 'crtsht_draw_capacity_v1';
            $lockResult = $db->query("SELECT GET_LOCK('" . $db->real_escape_string($lockName) . "', 5) AS locked");
            $locked = $lockResult ? (int)($lockResult->fetch_assoc()['locked'] ?? 0) === 1 : false;
            if ($lockResult) $lockResult->free();

            if (!$locked) {
                $error = 'The terminal is busy assigning another entry. Please submit again.';
            } else {
                try {
                    $db->begin_transaction();

                    $countResult = $db->query("SELECT COUNT(*) AS total FROM CRTSHT_Draw_Entries e INNER JOIN CRTSHT_Draw_Reservations r ON r.ID=e.ReservationID WHERE r.Status IN ('reserved','paid') FOR UPDATE");
                    if (!$countResult) throw new RuntimeException('capacity');
                    $used = (int)($countResult->fetch_assoc()['total'] ?? 0);
                    $countResult->free();

                    if ($used + (int)$quantity > CRTSHT_TOTAL) {
                        $db->rollback();
                        $remaining = max(0, CRTSHT_TOTAL - $used);
                        $error = $remaining === 0 ? 'All 128 CRTSHT slots are reserved.' : 'Only ' . $remaining . ' draw slot' . ($remaining === 1 ? '' : 's') . ' remain.';
                    } else {
                        $reservationCode = 'R-' . strtoupper(bin2hex(random_bytes(5)));
                        $status = 'reserved';
                        $stmt = $db->prepare('INSERT INTO CRTSHT_Draw_Reservations (ReservationCode, DrawBatch, Quantity, Name, Email, Mobile, Address, PLZ, City, Country, Status, CreatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
                        if (!$stmt) throw new RuntimeException('prepare reservation');
                        $q = (int)$quantity;
                        $stmt->bind_param('ssissssssss', $reservationCode, $currentBatch, $q, $name, $email, $mobile, $address, $plz, $city, $country, $status);
                        if (!$stmt->execute()) throw new RuntimeException('insert reservation');
                        $reservationId = (int)$stmt->insert_id;
                        $stmt->close();

                        $entryStmt = $db->prepare('INSERT INTO CRTSHT_Draw_Entries (ReservationID, DrawBatch, Status, CreatedAt) VALUES (?, ?, \'reserved\', NOW())');
                        if (!$entryStmt) throw new RuntimeException('prepare entries');
                        $entryIds = [];
                        for ($i = 0; $i < $q; $i++) {
                            $entryStmt->bind_param('is', $reservationId, $currentBatch);
                            if (!$entryStmt->execute()) throw new RuntimeException('insert entry');
                            $entryIds[] = (int)$entryStmt->insert_id;
                        }
                        $entryStmt->close();

                        $db->commit();
                        $success = [
                            'code' => $reservationCode,
                            'entries' => $entryIds,
                            'quantity' => $q,
                            'batch' => $currentBatch,
                            'draw_date' => $nextDrawDate,
                            'name' => $name,
                            'email' => $email
                        ];
                        $_SESSION['draw_csrf'] = bin2hex(random_bytes(24));
                        $csrf = $_SESSION['draw_csrf'];
                    }
                } catch (Throwable $e) {
                    $db->rollback();
                    $error = 'The reservation could not be stored. Please try again.';
                } finally {
                    $db->query("SELECT RELEASE_LOCK('" . $db->real_escape_string($lockName) . "')");
                }
            }
            $db->close();
        }
    }
}

$reservedSlots = draw_reserved_slots();
$remainingSlots = $reservedSlots === null ? null : max(0, CRTSHT_TOTAL - $reservedSlots);
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>The Draw / CRTSHT</title>
<meta name="description" content="Reserve a CRTSHT draw entry. Every ticket is matched with one real physical CRTSHT. Maximum 128 slots.">
<link rel="stylesheet" href="/site.css?v=8">
<style>
.draw-page{max-width:1180px}.draw-hero{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(280px,.85fr);gap:var(--pad);align-items:end;margin-bottom:calc(var(--pad)*1.25)}
.draw-hero h1{font-size:clamp(34px,6vw,92px);line-height:.82;letter-spacing:-.075em;margin:.08em 0 .22em;max-width:8ch}.draw-lead{font-size:clamp(18px,2.2vw,31px);line-height:1.08;letter-spacing:-.035em;max-width:19ch;margin:0}.draw-copy{font-size:13px;line-height:1.58;max-width:62ch}.draw-copy p{margin:0 0 1em}
.system-window{border:1px solid var(--fg);margin:0 0 calc(var(--pad)*1.3);background:rgba(242,242,238,.72)}.system-bar{display:flex;justify-content:space-between;gap:20px;padding:7px 9px;border-bottom:1px solid var(--fg);font-size:10px;text-transform:uppercase;letter-spacing:.08em;flex-wrap:wrap}.system-body{padding:18px}.system-status{border-top:1px solid var(--line);padding:8px 9px;font-size:10px;letter-spacing:.04em;display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap}.crt-blink{animation:crtBlink 2.3s steps(1,end) infinite}@keyframes crtBlink{0%,67%,100%{opacity:1}68%,82%{opacity:0}}
.terminal-title{font-size:clamp(30px,4.5vw,66px);line-height:.92;letter-spacing:-.06em;margin:0 0 18px;max-width:14ch}.terminal-copy{font-size:13px;line-height:1.55;max-width:72ch;margin:0 0 22px}.capacity{font-size:11px;text-transform:uppercase;letter-spacing:.07em;margin:12px 0 0}.capacity strong{font-size:inherit}.entry-grid{display:grid;grid-template-columns:repeat(3,1fr);border-top:1px solid var(--line);border-left:1px solid var(--line);margin-top:20px}.entry{position:relative;border-right:1px solid var(--line);border-bottom:1px solid var(--line);padding:0;min-height:150px}.entry input{position:absolute;opacity:0;pointer-events:none}.entry label{height:100%;padding:16px;display:flex;flex-direction:column;justify-content:space-between;cursor:pointer;transition:background .12s,color .12s}.entry input:checked+label{background:var(--fg);color:var(--bg)}.entry input:focus-visible+label{outline:2px solid var(--fg);outline-offset:-3px}.entry strong{display:block;font-size:clamp(30px,4vw,54px);letter-spacing:-.06em}.entry span{font-size:10px;text-transform:uppercase;letter-spacing:.08em}.entry p{font-size:11px;line-height:1.45;color:var(--muted);margin:8px 0 0}.entry input:checked+label p{color:inherit;opacity:.72}
.draw-form{margin-top:22px;border-top:1px solid var(--line);padding-top:18px}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.field{display:flex;flex-direction:column;gap:6px}.field-wide{grid-column:1/-1}.field label{font-size:10px;text-transform:uppercase;letter-spacing:.07em}.field input{width:100%;font:inherit;font-size:12px;padding:12px;background:transparent;color:var(--fg);border:1px solid var(--line);border-radius:0}.field input:focus{outline:1px solid var(--fg);border-color:var(--fg)}.hp{position:absolute!important;left:-9999px!important;width:1px!important;height:1px!important;overflow:hidden!important}.form-consent{font-size:10px;line-height:1.5;color:var(--muted);max-width:72ch;margin:14px 0}.terminal-action{display:flex;justify-content:space-between;align-items:center;gap:18px;margin-top:18px;flex-wrap:wrap}.terminal-button{display:inline-flex;align-items:center;justify-content:center;background:var(--fg);color:var(--bg);border:1px solid var(--fg);padding:13px 18px;font:inherit;font-size:12px;letter-spacing:.06em;text-transform:uppercase;cursor:pointer}.terminal-button:hover{background:transparent;color:var(--fg)}.terminal-note{font-size:10px;color:var(--muted);max-width:54ch;line-height:1.5}.draw-error{border:1px solid var(--fg);padding:12px;margin:0 0 18px;font-size:12px;line-height:1.5}
.draws{border-top:1px solid var(--fg);margin-top:calc(var(--pad)*1.35)}.draw-row{display:grid;grid-template-columns:120px 1fr auto;gap:20px;align-items:baseline;padding:16px 0;border-bottom:1px solid var(--line)}.draw-row .date{font-size:11px;text-transform:uppercase;letter-spacing:.08em}.draw-row strong{font-size:clamp(22px,3vw,38px);letter-spacing:-.045em}.draw-row .state{font-size:10px;text-transform:uppercase;letter-spacing:.08em;border:1px solid var(--fg);border-radius:100px;padding:5px 8px}.draw-note{font-size:13px;line-height:1.55;max-width:72ch;margin:20px 0 0}.receipt{margin-top:calc(var(--pad)*1.35);display:grid;grid-template-columns:minmax(190px,.55fr) minmax(0,1.45fr);gap:var(--pad);border-top:1px solid var(--fg);padding-top:22px}.receipt h2{font-size:clamp(28px,4vw,58px);line-height:.92;letter-spacing:-.055em;margin:0}.receipt-box{border:1px solid var(--fg);font-size:11px}.receipt-line{display:grid;grid-template-columns:130px 1fr;gap:16px;padding:9px 11px;border-bottom:1px solid var(--line)}.receipt-line:last-child{border-bottom:0}.receipt-line span:first-child{color:var(--muted);text-transform:uppercase;letter-spacing:.05em}.success-title{font-size:clamp(34px,5vw,72px);line-height:.9;letter-spacing:-.06em;margin:0 0 18px;max-width:11ch}.entry-numbers{display:flex;gap:7px;flex-wrap:wrap}.entry-number{border:1px solid var(--fg);border-radius:100px;padding:5px 8px}
@media(max-width:760px){.draw-hero,.receipt{grid-template-columns:1fr}.entry-grid,.form-grid{grid-template-columns:1fr}.field-wide{grid-column:auto}.draw-row{grid-template-columns:1fr auto}.draw-row .date{grid-column:1/-1}.receipt-line{grid-template-columns:105px 1fr}}
@media(prefers-reduced-motion:reduce){.crt-blink{animation:none}}
</style>
</head>
<body><main class="wrap draw-page">
<header>
<a class="brand" href="/">CR¥P70$H!7.DR4W</a>
<nav class="nav"><a href="/">Archive</a><a href="/lore">The Lore</a><a href="/oracle">The Oracle</a><a href="/draw" aria-current="page">Draw</a></nav>
</header>

<?php if ($success): ?>
<section class="system-window" aria-label="CRTSHT reservation confirmed">
<div class="system-bar"><span>CRTSHT / DRAW TERMINAL</span><span>RESERVATION STORED</span></div>
<div class="system-body">
<div class="eyebrow">ENTRY CONFIRMED / PAYMENT PENDING</div>
<h1 class="success-title">SUCCESS! NOW THE SHIT GETS REAL.</h1>
<p class="terminal-copy">Your reservation is stored. <?= $success['quantity'] === 1 ? 'This ticket receives' : 'Each of these tickets receives' ?> one independent draw for one real, remaining CRTSHT. Nothing is duplicated and the draw cannot exceed the 128 physical originals.</p>
<div class="receipt-box">
<div class="receipt-line"><span>RESERVATION</span><strong><?= crt_e($success['code']) ?></strong></div>
<div class="receipt-line"><span>ENTRY</span><span class="entry-numbers"><?php foreach($success['entries'] as $entryId): ?><strong class="entry-number">#<?= str_pad((string)$entryId, 4, '0', STR_PAD_LEFT) ?></strong><?php endforeach; ?></span></div>
<div class="receipt-line"><span>BATCH</span><span>DRAW <?= crt_e($success['batch']) ?></span></div>
<div class="receipt-line"><span>OBJECT</span><span>UNKNOWN</span></div>
<div class="receipt-line"><span>STATUS</span><span>RESERVED / PAYMENT PENDING</span></div>
<div class="receipt-line"><span>NEXT DRAW</span><span><?= crt_e($success['draw_date']) ?></span></div>
<div class="receipt-line"><span>BUYER</span><span><?= crt_e($success['name']) ?></span></div>
<div class="receipt-line"><span>MAIL</span><span><?= crt_e($success['email']) ?></span></div>
</div>
<p class="terminal-note" style="margin-top:14px">Your place is reserved in the backend until payment is confirmed. Confirmation emails will be connected as the next step.</p>
</div>
<div class="system-status"><span>OPEN → RESERVED → PAID → ASSIGNED</span><span>DRAW <?= crt_e($success['batch']) ?> · <?= crt_e($success['draw_date']) ?></span></div>
</section>
<?php else: ?>
<section class="draw-hero">
<div><div class="eyebrow">128 WORKS / 128 OWNERS / CHANCE DECIDES</div><h1>ENTER THE DRAW.</h1><p class="draw-lead">You want to own one. Chance chooses which.</p></div>
<div class="draw-copy"><p>A draw entry reserves one physical CRTSHT. It does not reserve a number, colour, face or favourite.</p><p><strong>Every ticket receives one draw for one real CRTSHT.</strong> There are exactly 128 physical originals, therefore no more than 128 valid draw entries can exist.</p></div>
</section>
<section class="system-window" aria-label="CRTSHT draw terminal">
<div class="system-bar"><span>CRTSHT / DRAW TERMINAL</span><span class="crt-blink">RESERVATIONS OPEN</span></div>
<div class="system-body">
<div class="eyebrow">BATCH <?= crt_e($currentBatch) ?> / <?= crt_e($currentDrawName) ?></div><h2 class="terminal-title">SECURE A PLACE IN THE DISPERSAL.</h2>
<p class="terminal-copy">One entry equals one genuine 20 × 20 cm physical original, with its recovered Ethereum record, wallet material, Mooncake and packaging. Entries are numbered in the order they arrive. The artwork itself remains unknown until the draw.</p>
<p class="capacity">CAPACITY / <?php if($remainingSlots === null): ?><strong>128 TOTAL</strong><?php else: ?><strong><?= $remainingSlots ?> OF 128 SLOTS AVAILABLE</strong><?php endif; ?></p>
<?php if($error !== ''): ?><div class="draw-error"><?= crt_e($error) ?></div><?php endif; ?>
<form class="draw-form" method="post" action="/draw" autocomplete="on">
<input type="hidden" name="csrf" value="<?= crt_e($csrf) ?>">
<div class="hp" aria-hidden="true"><label>Company<input type="text" name="company" tabindex="-1" autocomplete="off"></label></div>
<div class="entry-grid" aria-label="Draw entry quantities">
<div class="entry"><input id="qty1" type="radio" name="quantity" value="1" <?= $form['quantity']==='1'?'checked':'' ?>><label for="qty1"><div><span>ONE / ENTRY</span><strong>1×</strong></div><p>One entry. One unknown CRTSHT.</p></label></div>
<div class="entry"><input id="qty2" type="radio" name="quantity" value="2" <?= $form['quantity']==='2'?'checked':'' ?>><label for="qty2"><div><span>PAIR / ENTRIES</span><strong>2×</strong></div><p>Two independent draws.</p></label></div>
<div class="entry"><input id="qty3" type="radio" name="quantity" value="3" <?= $form['quantity']==='3'?'checked':'' ?>><label for="qty3"><div><span>TRIO / ENTRIES</span><strong>3×</strong></div><p>Three consecutive, independent draws.</p></label></div>
</div>
<div class="form-grid">
<div class="field field-wide"><label for="name">Name*</label><input id="name" name="name" maxlength="120" value="<?= crt_e($form['name']) ?>" autocomplete="name" required></div>
<div class="field"><label for="email">Mail*</label><input id="email" type="email" name="email" maxlength="190" value="<?= crt_e($form['email']) ?>" autocomplete="email" required></div>
<div class="field"><label for="mobile">Mobile*</label><input id="mobile" type="tel" name="mobile" maxlength="50" value="<?= crt_e($form['mobile']) ?>" autocomplete="tel" required></div>
<div class="field field-wide"><label for="address">Address*</label><input id="address" name="address" maxlength="190" value="<?= crt_e($form['address']) ?>" autocomplete="street-address" required></div>
<div class="field"><label for="plz">PLZ / Postal code*</label><input id="plz" name="plz" maxlength="24" value="<?= crt_e($form['plz']) ?>" autocomplete="postal-code" required></div>
<div class="field"><label for="city">City*</label><input id="city" name="city" maxlength="120" value="<?= crt_e($form['city']) ?>" autocomplete="address-level2" required></div>
<div class="field field-wide"><label for="country">Country*</label><input id="country" name="country" maxlength="120" value="<?= crt_e($form['country']) ?>" autocomplete="country-name" required></div>
</div>
<p class="form-consent">Submitting stores this reservation and temporarily holds the selected number of CRTSHT draw slots. The reservation remains marked <strong>PAYMENT PENDING</strong> until it is manually confirmed as paid. Your data is used to manage the reservation, draw and delivery of the work.</p>
<div class="terminal-action"><button class="terminal-button" type="submit" <?= $remainingSlots === 0 ? 'disabled' : '' ?>>RESERVE DRAW <?= $form['quantity']==='1' ? 'ENTRY' : 'ENTRIES' ?></button><span class="terminal-note">No artwork is selected here. Every valid ticket is matched by chance with one remaining physical CRTSHT at its scheduled draw.</span></div>
</form>
</div>
<div class="system-status"><span>OBJECT UNKNOWN / ENTRY RESERVED / PAYMENT PENDING</span><span>DRAW <?= crt_e($currentBatch) ?> · <?= crt_e($nextDrawDate) ?></span></div>
</section>
<?php endif; ?>

<section class="draws">
<div class="draw-row"><span class="date">25.09.2026</span><strong>DRAW 01 / FIRST DISPERSAL</strong><span class="state">01</span></div>
<div class="draw-row"><span class="date">17.10.2026</span><strong>DRAW 02 / SECOND DISPERSAL</strong><span class="state">02</span></div>
<div class="draw-row"><span class="date">31.10.2026</span><strong>DRAW 03 / FINAL DISPERSAL</strong><span class="state">FINAL</span></div>
<p class="draw-note">Reservations enter the next scheduled draw once payment has been confirmed. After each draw, the terminal continues with the remaining physical works. The system stops at 128 entries because there are only 128 CRTSHTs to disperse.</p>
</section>

<?php if (!$success): ?>
<section class="receipt">
<div><div class="eyebrow">READY.</div><h2>THE SHIT GETS REAL.</h2></div>
<div><div class="receipt-box" aria-label="Example draw receipt">
<div class="receipt-line"><span>ENTRY</span><strong>#0027</strong></div>
<div class="receipt-line"><span>BATCH</span><span>DRAW <?= crt_e($currentBatch) ?></span></div>
<div class="receipt-line"><span>ENTERED</span><span>TIMESTAMPED</span></div>
<div class="receipt-line"><span>OBJECT</span><span>UNKNOWN</span></div>
<div class="receipt-line"><span>STATUS</span><span>RESERVED / PAYMENT PENDING</span></div>
<div class="receipt-line"><span>NEXT DRAW</span><span><?= crt_e($nextDrawDate) ?></span></div>
</div></div>
</section>
<?php endif; ?>
<footer class="footer"><span>CRTSHT / THE DRAW</span><span>OPEN → RESERVED → PAID → ASSIGNED</span></footer>
</main>
<script>
(()=>{
 const radios=[...document.querySelectorAll('input[name="quantity"]')];
 const button=document.querySelector('.terminal-button');
 if(!radios.length||!button)return;
 const update=()=>{const q=radios.find(r=>r.checked)?.value||'1';button.textContent='RESERVE DRAW '+(q==='1'?'ENTRY':'ENTRIES');};
 radios.forEach(r=>r.addEventListener('change',update));update();
})();
</script>
</body></html>