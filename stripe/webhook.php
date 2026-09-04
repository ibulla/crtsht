<?php
declare(strict_types=1);

require dirname(__DIR__) . '/inc/stripe.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$payload = (string)file_get_contents('php://input');
$signature = (string)($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');

if (!crt_stripe_verify_webhook($payload, $signature)) {
    http_response_code(400);
    echo json_encode(['ok'=>false]);
    exit;
}

$event = json_decode($payload, true);
if (!is_array($event) || empty($event['id']) || empty($event['type'])) {
    http_response_code(400);
    echo json_encode(['ok'=>false]);
    exit;
}

$eventId = (string)$event['id'];
$type = (string)$event['type'];
$session = $event['data']['object'] ?? null;
if (!is_array($session)) {
    echo json_encode(['ok'=>true, 'ignored'=>true]);
    exit;
}

$handledTypes = [
    'checkout.session.completed',
    'checkout.session.async_payment_succeeded',
    'checkout.session.async_payment_failed',
    'checkout.session.expired',
];
if (!in_array($type, $handledTypes, true)) {
    echo json_encode(['ok'=>true, 'ignored'=>true]);
    exit;
}

$db = crt_db();
if (!$db) {
    http_response_code(503);
    echo json_encode(['ok'=>false]);
    exit;
}

try {
    $db->begin_transaction();

    $stmt = $db->prepare('INSERT IGNORE INTO CRTSHT_Stripe_Events (StripeEventID,EventType,ProcessedAt) VALUES (?,?,NOW())');
    if (!$stmt) throw new RuntimeException('Event log unavailable.');
    $stmt->bind_param('ss', $eventId, $type);
    $stmt->execute();
    $inserted = $stmt->affected_rows === 1;
    $stmt->close();
    if (!$inserted) {
        $db->commit();
        $db->close();
        echo json_encode(['ok'=>true, 'duplicate'=>true]);
        exit;
    }

    $metadata = is_array($session['metadata'] ?? null) ? $session['metadata'] : [];
    $rid = filter_var($metadata['crtsht_reservation_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) ?: 0;
    $code = strtoupper(trim((string)($metadata['crtsht_reservation_code'] ?? '')));
    if (!$rid || !preg_match('/^R-[A-F0-9]{10}$/', $code)) throw new RuntimeException('Missing CRTSHT reservation metadata.');

    $stmt = $db->prepare('SELECT ID,ReservationCode,Quantity,TotalPrice,Status FROM CRTSHT_Draw_Reservations WHERE ID=? AND ReservationCode=? LIMIT 1 FOR UPDATE');
    if (!$stmt) throw new RuntimeException('Reservation lookup failed.');
    $stmt->bind_param('is', $rid, $code);
    $stmt->execute();
    $res = $stmt->get_result();
    $reservation = $res ? $res->fetch_assoc() : null;
    if ($res) $res->free();
    $stmt->close();
    if (!is_array($reservation)) throw new RuntimeException('Reservation mismatch.');

    $sessionId = (string)($session['id'] ?? '');
    $paymentIntent = (string)($session['payment_intent'] ?? '');
    $customer = (string)($session['customer'] ?? '');
    $invoice = (string)($session['invoice'] ?? '');
    $paymentStatus = (string)($session['payment_status'] ?? '');
    $currency = strtolower((string)($session['currency'] ?? ''));
    $amountTotal = isset($session['amount_total']) ? (int)$session['amount_total'] : -1;
    $expectedAmount = crt_stripe_chf_cents((float)$reservation['TotalPrice']);

    if ($sessionId === '') throw new RuntimeException('Missing Checkout Session ID.');

    $isPaidEvent = $type === 'checkout.session.async_payment_succeeded' || ($type === 'checkout.session.completed' && $paymentStatus === 'paid');
    if ($isPaidEvent) {
        if ($currency !== 'chf' || $amountTotal !== $expectedAmount || $expectedAmount <= 0) {
            throw new RuntimeException('Stripe amount or currency mismatch.');
        }

        $newStatus = 'paid';
        $stmt = $db->prepare('UPDATE CRTSHT_Draw_Reservations SET Status=CASE WHEN Status="reserved" THEN "paid" ELSE Status END,PaidAt=COALESCE(PaidAt,NOW()),StripeCheckoutSessionID=?,StripePaymentIntentID=?,StripeCustomerID=?,StripeInvoiceID=?,StripePaymentStatus=?,StripeLastEventID=?,StripeUpdatedAt=NOW() WHERE ID=?');
        if (!$stmt) throw new RuntimeException('Reservation payment update failed.');
        $stmt->bind_param('ssssssi', $sessionId, $paymentIntent, $customer, $invoice, $newStatus, $eventId, $rid);
        $stmt->execute();
        $stmt->close();

        $entryStatus = 'paid';
        $stmt = $db->prepare("UPDATE CRTSHT_Draw_Entries SET Status=? WHERE ReservationID=? AND Status='reserved'");
        if (!$stmt) throw new RuntimeException('Entry payment update failed.');
        $stmt->bind_param('si', $entryStatus, $rid);
        $stmt->execute();
        $stmt->close();
    } else {
        $mapped = $type === 'checkout.session.expired' ? 'expired' : ($type === 'checkout.session.async_payment_failed' ? 'failed' : ($paymentStatus !== '' ? $paymentStatus : 'open'));
        $stmt = $db->prepare('UPDATE CRTSHT_Draw_Reservations SET StripeCheckoutSessionID=?,StripePaymentIntentID=?,StripeCustomerID=?,StripeInvoiceID=?,StripePaymentStatus=?,StripeLastEventID=?,StripeUpdatedAt=NOW() WHERE ID=?');
        if (!$stmt) throw new RuntimeException('Stripe status update failed.');
        $stmt->bind_param('ssssssi', $sessionId, $paymentIntent, $customer, $invoice, $mapped, $eventId, $rid);
        $stmt->execute();
        $stmt->close();
    }

    $db->commit();
    $db->close();
    echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
    try { $db->rollback(); } catch (Throwable $ignore) {}
    $db->close();
    error_log('CRTSHT Stripe webhook failed for ' . $eventId . ': ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok'=>false]);
}
