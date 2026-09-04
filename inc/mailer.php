<?php
declare(strict_types=1);

/**
 * Minimal SMTP transport for CRTSHT transactional mail.
 * Configuration is read through crt_env() from private/config.php.
 * No credentials belong in this repository.
 */

function crt_mail_configured(): bool {
    return crt_env('CRTSHT_SMTP_HOST') !== ''
        && crt_env('CRTSHT_MAIL_FROM') !== '';
}

function crt_mail_read_response($socket): array {
    $lines = [];
    $code = 0;
    while (!feof($socket)) {
        $line = fgets($socket, 1024);
        if ($line === false) break;
        $lines[] = rtrim($line, "\r\n");
        if (preg_match('/^(\d{3})([ -])/', $line, $m)) {
            $code = (int)$m[1];
            if ($m[2] === ' ') break;
        }
    }
    return [$code, implode("\n", $lines)];
}

function crt_mail_command($socket, string $command, array $expected): array {
    if ($command !== '') fwrite($socket, $command . "\r\n");
    [$code, $response] = crt_mail_read_response($socket);
    return [in_array($code, $expected, true), $code, $response];
}

function crt_mail_header_value(string $value): string {
    return trim(str_replace(["\r", "\n"], '', $value));
}

function crt_mail_address(string $email, string $name = ''): string {
    $email = crt_mail_header_value($email);
    $name = crt_mail_header_value($name);
    return $name === '' ? '<' . $email . '>' : '"' . addcslashes($name, '"\\') . '" <' . $email . '>';
}

/**
 * @return array{ok:bool,error:string}
 */
function crt_mail_send(string $to, string $subject, string $body, ?string $replyTo = null): array {
    if (!crt_mail_configured()) return ['ok' => false, 'error' => 'SMTP is not configured.'];
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return ['ok' => false, 'error' => 'Invalid recipient address.'];

    $host = crt_env('CRTSHT_SMTP_HOST');
    $port = (int)(crt_env('CRTSHT_SMTP_PORT') ?: '587');
    $security = strtolower(crt_env('CRTSHT_SMTP_SECURITY') ?: 'tls');
    $user = crt_env('CRTSHT_SMTP_USER');
    $pass = crt_env('CRTSHT_SMTP_PASS');
    $from = crt_env('CRTSHT_MAIL_FROM');
    $fromName = crt_env('CRTSHT_MAIL_FROM_NAME') ?: 'CRTSHT';
    $configuredReply = crt_env('CRTSHT_MAIL_REPLY_TO');
    $replyTo = $replyTo ?: ($configuredReply !== '' ? $configuredReply : $from);

    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) return ['ok' => false, 'error' => 'Invalid sender address.'];
    if ($replyTo !== null && !filter_var($replyTo, FILTER_VALIDATE_EMAIL)) return ['ok' => false, 'error' => 'Invalid reply-to address.'];
    if ($port < 1 || $port > 65535) return ['ok' => false, 'error' => 'Invalid SMTP port.'];
    if (!in_array($security, ['tls', 'ssl', 'none'], true)) return ['ok' => false, 'error' => 'Invalid SMTP security mode.'];

    $remote = ($security === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $errno = 0; $errstr = '';
    $socket = @stream_socket_client($remote, $errno, $errstr, 10, STREAM_CLIENT_CONNECT);
    if (!$socket) return ['ok' => false, 'error' => 'SMTP connection failed: ' . ($errstr ?: (string)$errno)];
    stream_set_timeout($socket, 10);

    try {
        [$ok,, $response] = crt_mail_command($socket, '', [220]);
        if (!$ok) throw new RuntimeException('SMTP greeting rejected: ' . $response);

        $helo = preg_replace('/[^A-Za-z0-9.-]/', '', (string)($_SERVER['SERVER_NAME'] ?? 'cryptoshit.info')) ?: 'cryptoshit.info';
        [$ok,, $response] = crt_mail_command($socket, 'EHLO ' . $helo, [250]);
        if (!$ok) throw new RuntimeException('EHLO rejected: ' . $response);

        if ($security === 'tls') {
            [$ok,, $response] = crt_mail_command($socket, 'STARTTLS', [220]);
            if (!$ok) throw new RuntimeException('STARTTLS rejected: ' . $response);
            if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('TLS negotiation failed.');
            }
            [$ok,, $response] = crt_mail_command($socket, 'EHLO ' . $helo, [250]);
            if (!$ok) throw new RuntimeException('EHLO after TLS rejected: ' . $response);
        }

        if ($user !== '') {
            [$ok,, $response] = crt_mail_command($socket, 'AUTH LOGIN', [334]);
            if (!$ok) throw new RuntimeException('SMTP authentication unavailable: ' . $response);
            [$ok,, $response] = crt_mail_command($socket, base64_encode($user), [334]);
            if (!$ok) throw new RuntimeException('SMTP username rejected: ' . $response);
            [$ok,, $response] = crt_mail_command($socket, base64_encode($pass), [235]);
            if (!$ok) throw new RuntimeException('SMTP password rejected: ' . $response);
        }

        [$ok,, $response] = crt_mail_command($socket, 'MAIL FROM:<' . $from . '>', [250]);
        if (!$ok) throw new RuntimeException('Sender rejected: ' . $response);
        [$ok,, $response] = crt_mail_command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
        if (!$ok) throw new RuntimeException('Recipient rejected: ' . $response);
        [$ok,, $response] = crt_mail_command($socket, 'DATA', [354]);
        if (!$ok) throw new RuntimeException('SMTP DATA rejected: ' . $response);

        $subject = crt_mail_header_value($subject);
        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . crt_mail_address($from, $fromName),
            'To: <' . $to . '>',
            'Reply-To: <' . $replyTo . '>',
            'Subject: ' . $subject,
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $helo . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'X-Mailer: CRTSHT/2026'
        ];

        $body = str_replace(["\r\n", "\r"], "\n", $body);
        $body = str_replace("\n.", "\n..", $body);
        $payload = implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n", "\r\n", $body) . "\r\n.\r\n";
        fwrite($socket, $payload);
        [$code, $response] = crt_mail_read_response($socket);
        if ($code !== 250) throw new RuntimeException('Message rejected: ' . $response);
        @fwrite($socket, "QUIT\r\n");
        fclose($socket);
        return ['ok' => true, 'error' => ''];
    } catch (Throwable $e) {
        @fwrite($socket, "QUIT\r\n");
        fclose($socket);
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

function crt_mail_price($value): string {
    if ($value === null || $value === '' || !is_numeric($value)) return '—';
    return 'CHF ' . number_format((float)$value, 2, '.', "'");
}

function crt_mail_entry_list(array $ids): string {
    return implode(', ', array_map(static fn($id) => '#' . str_pad((string)(int)$id, 4, '0', STR_PAD_LEFT), $ids));
}

function crt_mail_payment_block(string $reservationCode, $total): string {
    $twint = crt_env('CRTSHT_PAYMENT_TWINT');
    $iban = crt_env('CRTSHT_PAYMENT_IBAN');
    $accountName = crt_env('CRTSHT_PAYMENT_NAME');
    if ($twint === '' && $iban === '') return '';

    $body = "PAYMENT\n\nPlease transfer " . crt_mail_price($total) . " using one of the following:\n\n";
    if ($twint !== '') {
        $body .= "TWINT\n{$twint}\n\n";
    }
    if ($iban !== '') {
        $body .= "BANK TRANSFER\nIBAN  {$iban}\n";
        if ($accountName !== '') $body .= "NAME  {$accountName}\n";
        $body .= "\n";
    }
    $body .= "REFERENCE\n{$reservationCode}\n\n"
        . "Please use the reservation code as the payment reference.\n\n";
    return $body;
}

function crt_mail_reservation_customer(array $reservation, array $entryIds, string $drawDate): array {
    $code = (string)($reservation['ReservationCode'] ?? '');
    $name = trim((string)($reservation['Name'] ?? ''));
    $quantity = (int)($reservation['Quantity'] ?? count($entryIds));
    $unit = $reservation['UnitPrice'] ?? null;
    $total = $reservation['TotalPrice'] ?? null;
    $body = "CRTSHT / DRAW TERMINAL\nRESERVATION STORED\n\n"
        . ($name !== '' ? "Hi {$name},\n\n" : '')
        . "Your place in the CRTSHT draw is reserved.\n\n"
        . "RESERVATION  {$code}\n"
        . "VOUCHER      " . crt_mail_entry_list($entryIds) . "\n"
        . "BATCH        DRAW " . (string)($reservation['DrawBatch'] ?? '') . "\n"
        . "NEXT DRAW    {$drawDate}\n"
        . "QUANTITY     {$quantity}\n"
        . "UNIT PRICE   " . crt_mail_price($unit) . "\n"
        . "TOTAL        " . crt_mail_price($total) . "\n"
        . "STATUS       RESERVED / PAYMENT PENDING\n\n"
        . "Your voucher" . ($quantity === 1 ? ' is' : 's are') . " reserved. "
        . ($quantity === 1 ? 'It becomes' : 'They become') . " active for the draw once payment has been received.\n"
        . "You will receive a confirmation when payment is registered.\n\n"
        . "One voucher = one genuine physical CRTSHT.\n"
        . "YOU CHOOSE TO OWN ONE. CHANCE CHOOSES WHICH.\n\n"
        . "cryptoshit.info\n";
    return crt_mail_send((string)($reservation['Email'] ?? ''), 'CRTSHT / reservation ' . $code, $body);
}

function crt_mail_payment_details_customer(array $reservation): array {
    $code = (string)($reservation['ReservationCode'] ?? '');
    $name = trim((string)($reservation['Name'] ?? ''));
    $total = $reservation['TotalPrice'] ?? null;
    $body = "CRTSHT / PAYMENT DETAILS\n\n"
        . ($name !== '' ? "Hi {$name},\n\n" : '')
        . "Here are the payment details for your CRTSHT draw reservation.\n\n"
        . "RESERVATION  {$code}\n"
        . "TOTAL        " . crt_mail_price($total) . "\n\n"
        . crt_mail_payment_block($code, $total)
        . "Your draw entry becomes active once payment has been received and confirmed.\n\n"
        . "cryptoshit.info\n";
    return crt_mail_send((string)($reservation['Email'] ?? ''), 'CRTSHT / payment details / ' . $code, $body);
}

function crt_mail_reservation_admin(array $reservation, array $entryIds): array {
    $admin = crt_env('CRTSHT_MAIL_ADMIN');
    if ($admin === '') return ['ok' => true, 'error' => ''];
    $body = "CRTSHT / NEW DRAW RESERVATION\n\n"
        . "RESERVATION  " . (string)($reservation['ReservationCode'] ?? '') . "\n"
        . "VOUCHER      " . crt_mail_entry_list($entryIds) . "\n"
        . "BATCH        DRAW " . (string)($reservation['DrawBatch'] ?? '') . "\n"
        . "QUANTITY     " . (string)($reservation['Quantity'] ?? '') . "\n"
        . "UNIT PRICE   " . crt_mail_price($reservation['UnitPrice'] ?? null) . "\n"
        . "TOTAL        " . crt_mail_price($reservation['TotalPrice'] ?? null) . "\n\n"
        . "NAME         " . (string)($reservation['Name'] ?? '') . "\n"
        . "MAIL         " . (string)($reservation['Email'] ?? '') . "\n"
        . "MOBILE       " . (string)($reservation['Mobile'] ?? '') . "\n"
        . "ADDRESS      " . (string)($reservation['Address'] ?? '') . "\n"
        . "             " . trim((string)($reservation['PLZ'] ?? '') . ' ' . (string)($reservation['City'] ?? '')) . "\n"
        . "COUNTRY      " . (string)($reservation['Country'] ?? '') . "\n\n"
        . "REFERENCE    " . (string)($reservation['ReservationCode'] ?? '') . "\n"
        . "STATUS       RESERVED / PAYMENT PENDING\n";
    return crt_mail_send($admin, 'CRTSHT / new reservation ' . (string)($reservation['ReservationCode'] ?? ''), $body, (string)($reservation['Email'] ?? ''));
}

function crt_mail_paid_customer(array $reservation, array $entryIds): array {
    $code = (string)($reservation['ReservationCode'] ?? '');
    $body = "CRTSHT / DRAW TERMINAL\nPAYMENT CONFIRMED\n\n"
        . "RESERVATION  {$code}\n"
        . "VOUCHER      " . crt_mail_entry_list($entryIds) . "\n"
        . "BATCH        DRAW " . (string)($reservation['DrawBatch'] ?? '') . "\n"
        . "TOTAL        " . crt_mail_price($reservation['TotalPrice'] ?? null) . "\n"
        . "STATUS       PAID / DRAW ACTIVE\n\n"
        . "Your voucher" . (count($entryIds) === 1 ? ' is' : 's are') . " now active for the draw.\n"
        . "The object remains unknown.\n\n"
        . "YOU CHOOSE TO OWN ONE. CHANCE CHOOSES WHICH.\n\n"
        . "cryptoshit.info/draw\n";
    return crt_mail_send((string)($reservation['Email'] ?? ''), 'CRTSHT / payment confirmed / ' . $code, $body);
}

function crt_mail_assignment_customer(array $reservation, int $entryId, int $crtshtId): array {
    $recordUrl = rtrim(crt_env('CRTSHT_SITE_URL') ?: 'https://cryptoshit.info', '/') . '/crtsht/' . $crtshtId;
    $body = "CRTSHT / DRAW TERMINAL\nYOUR SHIT HAS BEEN DETERMINED.\n\n"
        . "RESERVATION  " . (string)($reservation['ReservationCode'] ?? '') . "\n"
        . "VOUCHER      #" . str_pad((string)$entryId, 4, '0', STR_PAD_LEFT) . "\n"
        . "DRAW         " . (string)($reservation['DrawBatch'] ?? '') . "\n"
        . "CRTSHT       #" . str_pad((string)$crtshtId, 3, '0', STR_PAD_LEFT) . "\n"
        . "STATUS       ASSIGNED\n\n"
        . "Chance has chosen. This CRTSHT is yours.\n\n"
        . "RECORD\n{$recordUrl}\n\n"
        . "The public record now carries the draw provenance with it.\n";
    return crt_mail_send((string)($reservation['Email'] ?? ''), 'YOUR SHIT HAS BEEN DETERMINED. / CRTSHT #' . str_pad((string)$crtshtId, 3, '0', STR_PAD_LEFT), $body);
}
