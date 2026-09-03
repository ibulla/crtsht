-- CRTSHT / modular Stripe Checkout migration
-- Run once against the existing CRTSHT database before testing stripe/start.php.
-- This only adds payment metadata; existing Draw tables and workflow remain intact.

ALTER TABLE CRTSHT_Draw_Reservations
    ADD COLUMN StripeCheckoutSessionID VARCHAR(255) NULL AFTER PaymentNote,
    ADD COLUMN StripePaymentIntentID VARCHAR(255) NULL AFTER StripeCheckoutSessionID,
    ADD COLUMN StripeCustomerID VARCHAR(255) NULL AFTER StripePaymentIntentID,
    ADD COLUMN StripeInvoiceID VARCHAR(255) NULL AFTER StripeCustomerID,
    ADD COLUMN StripePaymentStatus VARCHAR(32) NULL AFTER StripeInvoiceID,
    ADD COLUMN StripeLastEventID VARCHAR(255) NULL AFTER StripePaymentStatus,
    ADD COLUMN StripeUpdatedAt DATETIME NULL AFTER StripeLastEventID,
    ADD UNIQUE KEY uq_crtsht_stripe_checkout_session (StripeCheckoutSessionID);

CREATE TABLE IF NOT EXISTS CRTSHT_Stripe_Events (
    ID BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    StripeEventID VARCHAR(255) NOT NULL,
    EventType VARCHAR(120) NOT NULL,
    ProcessedAt DATETIME NOT NULL,
    PRIMARY KEY (ID),
    UNIQUE KEY uq_crtsht_stripe_event (StripeEventID),
    KEY idx_crtsht_stripe_event_type (EventType)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
