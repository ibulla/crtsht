<?php
declare(strict_types=1);
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/stripe.php';

/** Selected Works mail uses the exact existing SMTP transport and payment configuration. */
function crt_selected_payment_url(string $code): string {
    $base = rtrim(crt_env('CRTSHT_PUBLIC_URL') ?: (crt_env('CRTSHT_SITE_URL') ?: 'https://cryptoshit.info'), '/');
    return $base . '/selected-payment/' . rawurlencode($code);
}

function crt_selected_mail_offer_accepted(array $offer): array {
    $code = (string)$offer['ReservationCode'];
    $name = trim((string)$offer['Name']);
    $id = (int)$offer['CRTSHT_ID'];
    $deadline = (string)$offer['ReservationExpiresAt'];
    $body = "CRTSHT / SELECTED WORKS\nYOUR OFFER HAS BEEN ACCEPTED.\n\n"
        . ($name !== '' ? "Hi {$name},\n\n" : '')
        . "Your purchase offer has been selected for CRTSHT #{$id}.\n\n"
        . "WORK          CRTSHT #{$id}\n"
        . "PRICE         " . crt_mail_price($offer['OfferedPriceCHF']) . "\n"
        . "RESERVATION   {$code}\n"
        . "PAYMENT UNTIL {$deadline} (Zurich local time)\n\n"
        . "COMPLETE PAYMENT\n" . crt_selected_payment_url($code) . "\n\n"
        . "Choose card / TWINT via Stripe Checkout or bank transfer. "
        . "The work is held for you until the stated payment deadline. "
        . "Please use the reservation code as the reference for a bank transfer.\n\n"
        . "Thank you,\nCRTSHT\n";
    return crt_mail_send((string)$offer['Email'], 'CRTSHT / your work is selected / #' . $id, $body);
}

function crt_selected_mail_paid(array $offer): array {
    $id = (int)$offer['CRTSHT_ID'];
    $code = (string)$offer['ReservationCode'];
    $body = "CRTSHT / SELECTED WORKS\nPAYMENT CONFIRMED.\n\n"
        . "WORK         CRTSHT #{$id}\nRESERVATION  {$code}\n"
        . "AMOUNT       " . crt_mail_price($offer['OfferedPriceCHF']) . "\n"
        . "STATUS       PAID / SOLD\n\n"
        . "Your selected CRTSHT has been paid for. We'll contact you about the handover.\n\n"
        . "WORK RECORD\n"
        . rtrim(crt_env('CRTSHT_SITE_URL') ?: 'https://cryptoshit.info', '/') . '/crtsht/' . $id . "\n";
    return crt_mail_send((string)$offer['Email'], 'CRTSHT / payment confirmed / #' . $id, $body);
}
