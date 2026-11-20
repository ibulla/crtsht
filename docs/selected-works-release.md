# CRTSHT / Selected Works — Release checklist

## Scope
Specific works (1–128) with fixed price, exhibition location and offer deadline,
binding buyer offers, manual or random allocation, and payment by the existing
Stripe Checkout (card/TWINT if enabled in Stripe) or bank transfer.

## Prerequisites
- Additive SQL migration `sql/crtsht_selected_works.sql` applied once. Confirm all
  three Selected Works tables exist. It has already been run by the site owner.
- Existing `CRTSHT_*` DB/SMTP/Stripe settings remain in `private/config.php`.
- HTTPS and Apache rewrite rules enabled.
- `CRTSHT_PAYMENT_IBAN` set for bank transfer option.
- `CRTSHT_PUBLIC_URL` points to the correct origin for payment links.
- Stripe webhook continues to point to **the existing** `/stripe/webhook.php`.
- Card/TWINT availability is controlled by the Stripe account; the new mode uses
  the same configured Checkout payment methods as the existing DRAW.
- Before enabling any live cards, verify with a non-live Stripe key or on staging.

## Guided staging test (do not use live customer payments to test)
1. In `/crtshtdrwmng/selected-works.php`, create a **draft** work not previously
   assigned in DRAW, then set status to **open**, price and a near-future deadline
   in Europe/Zurich.
2. Visit `/draw`. Verify the specific work, image, price, location, deadline,
   offer link. Submit two offers using different test email addresses.
3. Verify a second offer using the same email + work is rejected, and required
   binding-offer consent is enforced.
4. After the deadline, select the winner **manually** in admin. Verify one
   accepted offer, the remaining rejected, a 48-hour reservation window and a
   unique `W-...` reservation code. Confirm the work is no longer offered.
5. Click **EMAIL PAYMENT LINK** and verify SMTP delivery. Open the link and
   verify price/work/reference + bank details match the stored winning offer.
6. In Stripe **test mode**, complete a test card Checkout. Verify signed webhook
   updates the selected work to `sold`, the offer has PaidAt, the audit log
   records payment, and the confirmation email arrives. A second webhook event
   must not send a second paid mail or alter another work.
7. On a different work, test **bank transfer**: mark bank paid manually only
   after actual receipt (or stage DB simulation). Verify `sold` and email.
8. Verify status `reserved` blocks DRAW assignment. An already DRAW-assigned
   work must not be published for sale.
9. Test **EXTEND +48H** and **RELEASE / RESELECT** using a staging reservation
   without any received/in-flight payments. Verify previous payment URL is closed.
10. Confirm the original DRAW reservation checkout, Stripe webhook and payment
    status continue to function (regression).
11. Check server PHP error logs, SMTP logs, and Stripe webhook dashboard for
    any errors before enabling the live offer form.

## Release and rollback
- Never re-run the one-time ALTER foreign key statement: the migration is already installed.
- Do not merge before PHP lint and end-to-end staging checks are both green.
- Publish with **zero works in open status**, then activate only the intended
  works from admin; this is the operational rollout switch.
- Rollback code by reverting the merge, without dropping the Selected Works tables.
  Keeping data preserves accepted offers and audit records.
- Do **not** reallocate works if bank payments could still be in transit.

## Known limitations
- Stripe/TWINT needs an actual payment-method integration test on the configured account.
- Full transactional mail delivery is not guaranteed if SMTP fails after the DB
  transaction; admin can resend a winner payment invitation.
- Payments received after release need manual reconciliation; never reallocate
  without checking card and bank payments.
- Time values are stored/displayed in Europe/Zurich local time (DATETIME);
  test on DST boundaries if scheduled close dates span the clock change.
- Refunds are administered in Stripe; no self-service refund mechanism exists.
- These statements describe planned/implemented behavior, not proof of a live test.
