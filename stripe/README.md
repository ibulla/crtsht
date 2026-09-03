# CRTSHT / STRIPE CHECKOUT

This module is intentionally isolated from the public Draw workflow. Nothing in `draw.php` points here yet.

## Architecture

1. The existing Draw creates `CRTSHT_Draw_Reservations` and `CRTSHT_Draw_Entries` exactly as before.
2. `/stripe/start.php?reservation=R-XXXXXXXXXX` validates an existing `reserved` reservation, freezes its CHF package price if necessary, creates a Stripe-hosted Checkout Session, stores the Stripe Session ID, and redirects to Stripe.
3. Stripe returns the browser to `/stripe/success.php`, but that page is **not** authoritative.
4. `/stripe/webhook.php` verifies the Stripe signature and is the only automated path that changes a reservation from `reserved` to `paid`.
5. Before marking paid, the webhook checks the CRTSHT reservation metadata, CHF currency and exact stored total amount. It then marks still-reserved entries as `paid`. Artwork allocation remains a later Draw operation.
6. Stripe event IDs are stored in `CRTSHT_Stripe_Events` so webhook retries are idempotent.

## Server configuration

The existing `inc/bootstrap.php` loads `private/config.php`, which is already ignored by Git.
Add the following values to that private file only:

```php
return [
    // existing CRTSHT config ...
    'CRTSHT_STRIPE_ENABLED' => '1',
    'CRTSHT_PUBLIC_URL' => 'https://cryptoshit.info',
    'STRIPE_SECRET_KEY' => 'sk_test_...',
    'STRIPE_WEBHOOK_SECRET' => 'whsec_...',
];
```

Do not commit either secret.

## Database

Run `stripe/schema.sql` once before accessing the Stripe endpoints.
It only adds Stripe metadata columns to `CRTSHT_Draw_Reservations` and creates the event-deduplication table.

## Stripe Dashboard / test setup

Use a Stripe test/sandbox secret key first.
Create a webhook endpoint for:

`https://cryptoshit.info/stripe/webhook.php`

Subscribe to:

- `checkout.session.completed`
- `checkout.session.async_payment_succeeded`
- `checkout.session.async_payment_failed`
- `checkout.session.expired`

Copy the endpoint signing secret to `STRIPE_WEBHOOK_SECRET`.
Enable the payment methods you want Stripe Checkout to offer, including cards and TWINT for eligible CHF payments. Checkout uses Stripe's hosted payment-method presentation rather than CRTSHT collecting card data.

Checkout Sessions are created with `invoice_creation[enabled]=true` so a successful one-time payment can create a Stripe invoice. This can later be adjusted independently from the Draw flow if receipts are preferred for some buyers.

## First test

1. Keep `main` untouched and deploy the `stripe-checkout-modular` branch only to a staging/test location if possible.
2. Create a normal Draw reservation using the existing public flow.
3. Ensure that reservation has a positive package price (or that the matching global price is configured).
4. Open `/stripe/start.php?reservation=R-XXXXXXXXXX` with that reservation code.
5. Complete Stripe Checkout in test mode.
6. Verify in `/crtshtdrwmng/` that the reservation and its unassigned entries changed to `paid` only after the webhook arrived.
7. Verify cancel/failure paths leave the reservation `reserved`.
8. Confirm no `AssignedCRTSHT` value is created by any payment path.

## Go-live switch

Only after end-to-end testing:

- replace the test key and webhook secret with the live values in `private/config.php`;
- configure the live webhook endpoint;
- confirm cards/TWINT are active in the live Stripe account;
- run one low-risk live checkout;
- then change the public Draw success/action flow to send newly-created reservations into `/stripe/start.php`.

That final public integration should be a small, reversible edit. Until that edit is made, the existing CRTSHT reservation workflow remains the as-is workflow.
