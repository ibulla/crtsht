<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/stripe.php';

header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Cache-Control: no-store, private');

if (!crt_stripe_ready()) {
    http_response_code(503);
    exit('Stripe checkout is not enabled.');
}

$reservationCode = strtoupper(trim((string)($_POST['reservation'] ?? $_GET['reservation'] ?? '')));
if (!preg_match('/^R-[A-F0-9]{10}$/', $reservationCode)) {
    http_response_code(400);
    exit('Invalid reservation code.');
}

$db = crt_db();
if (!$db) {
    http_response_code(503);
    exit('Database unavailable.');
}

try {
    $db->begin_transaction();

    $stmt = $db->prepare('SELECT ID,ReservationCode,DrawBatch,Quantity,Name,Email,Status,UnitPrice,TotalPrice FROM CRTSHT_Draw_Reservations WHERE ReservationCode=? LIMIT 1 FOR UPDATE');
    if (!$stmt) throw new RuntimeException('Reservation lookup failed.');
    $stmt->bind_param('s', $reservationCode);
    $stmt->execute();
    $res = $stmt->get_result();
    $reservation = $res ? $res->fetch_assoc() : null;
    if ($res) $res->free();
    $stmt->close();

    if (!is_array($reservation)) throw new RuntimeException('Reservation not found.');
    if ((string)$reservation['Status'] === 'paid') throw new RuntimeException('This reservation is already paid.');
    if ((string)$reservation['Status'] !== 'reserved') throw new RuntimeException('This reservation cannot be paid online.');

    $quantity = (int)$reservation['Quantity'];
    if ($quantity < 1 || $quantity > 3) throw new RuntimeException('Invalid reservation quantity.');

    $total = (float)$reservation['TotalPrice'];
    if ($total <= 0) {
        $key = 'price_' . $quantity . '_chf';
        $stmt = $db->prepare('SELECT SettingValue FROM CRTSHT_Draw_Settings WHERE SettingKey=? LIMIT 1');
        if (!$stmt) throw new RuntimeException('Price lookup failed.');
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        if ($res) $res->free();
        $stmt->close();
        $total = is_array($row) ? (float)$row['SettingValue'] : 0.0;
        if ($total <= 0) throw new RuntimeException('No checkout price is configured for this reservation.');

        $unit = $total / $quantity;
        $stmt = $db->prepare('UPDATE CRTSHT_Draw_Reservations SET TotalPrice=?,UnitPrice=? WHERE ID=?');
        if (!$stmt) throw new RuntimeException('Could not snapshot reservation price.');
        $rid = (int)$reservation['ID'];
        $stmt->bind_param('ddi', $total, $unit, $rid);
        $stmt->execute();
        $stmt->close();
    }

    $amountCents = crt_stripe_chf_cents($total);
    if ($amountCents < 50) throw new RuntimeException('Checkout amount is invalid.');

    $base = crt_stripe_base_url();
    $rid = (int)$reservation['ID'];
    $params = [
        'mode' => 'payment',
        'client_reference_id' => $reservationCode,
        'customer_email' => (string)$reservation['Email'],
        'success_url' => $base . '/stripe/success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $base . '/stripe/cancel.php?reservation=' . rawurlencode($reservationCode),
        'line_items' => [[
            'quantity' => 1,
            'price_data' => [
                'currency' => 'chf',
                'unit_amount' => $amountCents,
                'product_data' => [
                    'name' => 'CRTSHT / THE DRAW — ' . $quantity . '×',
                    'description' => $quantity . ' draw ' . ($quantity === 1 ? 'entry' : 'entries') . '. Physical CRTSHT allocation happens later during The Draw.',
                ],
            ],
        ]],
        'metadata' => [
            'crtsht_reservation_id' => (string)$rid,
            'crtsht_reservation_code' => $reservationCode,
            'crtsht_quantity' => (string)$quantity,
            'crtsht_draw_batch' => (string)$reservation['DrawBatch'],
        ],
        'invoice_creation' => ['enabled' => 'true'],
    ];

    $session = crt_stripe_request('POST', '/v1/checkout/sessions', $params, 'crtsht-checkout-' . $rid . '-' . $amountCents);
    $sessionId = (string)($session['id'] ?? '');
    $checkoutUrl = (string)($session['url'] ?? '');
    if ($sessionId === '' || $checkoutUrl === '') throw new RuntimeException('Stripe did not return a Checkout URL.');

    $stmt = $db->prepare('UPDATE CRTSHT_Draw_Reservations SET StripeCheckoutSessionID=?,StripePaymentStatus=?,StripeUpdatedAt=NOW() WHERE ID=?');
    if (!$stmt) throw new RuntimeException('Could not attach Stripe session to reservation.');
    $stripeStatus = 'open';
    $stmt->bind_param('ssi', $sessionId, $stripeStatus, $rid);
    $stmt->execute();
    $stmt->close();

    $db->commit();
    $db->close();

    header('Location: ' . $checkoutUrl, true, 303);
    exit;
} catch (Throwable $e) {
    try { $db->rollback(); } catch (Throwable $ignore) {}
    $db->close();
    error_log('CRTSHT Stripe checkout start failed for ' . $reservationCode . ': ' . $e->getMessage());
    http_response_code(400);
    $detail = !crt_stripe_is_live() ? '<p><strong>Sandbox detail:</strong> ' . crt_e($e->getMessage()) . '</p>' : '';
    echo '<!doctype html><meta charset="utf-8"><title>CRTSHT / CHECKOUT</title><p>Checkout could not be started.</p>' . $detail . '<p><a href="/draw">Return to The Draw</a></p>';
}
