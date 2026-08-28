<?php
/*
 * Copy these keys into private/config.php and fill in your real values.
 * private/config.php is ignored by git and must never be committed.
 */
return [
    // Existing CRTSHT config stays here ...

    // SMTP server
    'CRTSHT_SMTP_HOST' => 'smtp.example.com',
    'CRTSHT_SMTP_PORT' => '587',
    'CRTSHT_SMTP_SECURITY' => 'tls', // tls | ssl | none
    'CRTSHT_SMTP_USER' => 'mailbox@example.com',
    'CRTSHT_SMTP_PASS' => 'YOUR-SMTP-PASSWORD',

    // Sender identity
    'CRTSHT_MAIL_FROM' => 'mailbox@example.com',
    'CRTSHT_MAIL_FROM_NAME' => 'CRTSHT',
    'CRTSHT_MAIL_REPLY_TO' => 'mailbox@example.com',

    // Internal notification recipient
    'CRTSHT_MAIL_ADMIN' => 'you@example.com',

    // Payment information shown in reservation mails
    'CRTSHT_PAYMENT_TWINT' => '+41 XX XXX XX XX',
    'CRTSHT_PAYMENT_IBAN' => 'CHxx xxxx xxxx xxxx xxxx x',
    'CRTSHT_PAYMENT_NAME' => 'Your name / account holder',

    // Used for links in assignment/result mail
    'CRTSHT_SITE_URL' => 'https://cryptoshit.info',
];
