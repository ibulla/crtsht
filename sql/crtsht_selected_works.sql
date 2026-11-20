-- CRTSHT / SELECTED WORKS (2026)
-- Additive migration. Does not alter historical metadata or existing DRAW reservations.
-- Apply after the existing DRAW schema. MySQL 8 / InnoDB.

CREATE TABLE IF NOT EXISTS CRTSHT_Selected_Works (
  ID BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  CRTSHT_ID SMALLINT UNSIGNED NOT NULL,
  PriceCHF DECIMAL(12,2) NOT NULL,
  Location VARCHAR(255) NULL,
  ExhibitionUntil DATETIME NULL,
  OfferDeadline DATETIME NOT NULL,
  SaleStatus ENUM('draft','open','closed','reserved','sold','withdrawn') NOT NULL DEFAULT 'draft',
  WinnerOfferID BIGINT UNSIGNED NULL,
  ReservationExpiresAt DATETIME NULL,
  CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UpdatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (ID),
  UNIQUE KEY uq_selected_crtsht (CRTSHT_ID),
  KEY idx_selected_deadline (SaleStatus, OfferDeadline),
  CONSTRAINT chk_selected_crtsht_id CHECK (CRTSHT_ID BETWEEN 1 AND 128),
  CONSTRAINT chk_selected_price CHECK (PriceCHF > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS CRTSHT_Selected_Offers (
  ID BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  SelectedWorkID BIGINT UNSIGNED NOT NULL,
  OfferCode VARCHAR(32) NOT NULL,
  Name VARCHAR(120) NOT NULL,
  Email VARCHAR(190) NOT NULL,
  Mobile VARCHAR(50) NOT NULL DEFAULT '',
  Address VARCHAR(190) NOT NULL DEFAULT '',
  PLZ VARCHAR(24) NOT NULL DEFAULT '',
  City VARCHAR(120) NOT NULL DEFAULT '',
  Country VARCHAR(120) NOT NULL DEFAULT '',
  OfferedPriceCHF DECIMAL(12,2) NOT NULL,
  Status ENUM('pending','accepted','rejected','withdrawn','expired') NOT NULL DEFAULT 'pending',
  ReservationCode VARCHAR(32) NULL,
  StripeCheckoutSessionID VARCHAR(255) NULL,
  StripePaymentStatus VARCHAR(32) NULL,
  PaidAt DATETIME NULL,
  DecisionMethod ENUM('manual','random') NULL,
  DecisionNote TEXT NULL,
  DecidedAt DATETIME NULL,
  CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UpdatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (ID),
  UNIQUE KEY uq_selected_offer_code (OfferCode),
  UNIQUE KEY uq_selected_reservation_code (ReservationCode),
  UNIQUE KEY uq_selected_stripe_session (StripeCheckoutSessionID),
  KEY idx_selected_offers_work_status (SelectedWorkID,Status),
  CONSTRAINT fk_selected_offer_work FOREIGN KEY (SelectedWorkID)
    REFERENCES CRTSHT_Selected_Works(ID) ON DELETE RESTRICT,
  CONSTRAINT chk_selected_offer_price CHECK (OfferedPriceCHF > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS CRTSHT_Selected_Audit (
  ID BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  SelectedWorkID BIGINT UNSIGNED NOT NULL,
  OfferID BIGINT UNSIGNED NULL,
  Actor VARCHAR(120) NOT NULL,
  Action VARCHAR(80) NOT NULL,
  DetailJSON JSON NULL,
  CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (ID),
  KEY idx_selected_audit_work (SelectedWorkID,CreatedAt),
  CONSTRAINT fk_selected_audit_work FOREIGN KEY (SelectedWorkID)
    REFERENCES CRTSHT_Selected_Works(ID) ON DELETE RESTRICT,
  CONSTRAINT fk_selected_audit_offer FOREIGN KEY (OfferID)
    REFERENCES CRTSHT_Selected_Offers(ID) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE CRTSHT_Selected_Works
  ADD CONSTRAINT fk_selected_winner_offer FOREIGN KEY (WinnerOfferID)
    REFERENCES CRTSHT_Selected_Offers(ID) ON DELETE RESTRICT;

-- The application must verify WinnerOfferID belongs to the same SelectedWorkID.
-- Historical CRTSHT_Draw_Entries / AssignedCRTSHT record the original DRAW allocation:
-- those records remain intact and MUST NOT prevent a secondary-market resale.
-- Only one active Selected Works sale is allowed per artwork; work-row locks and
-- WinnerOfferID guard the current reservation/payment transaction.
-- uq_selected_crtsht currently supports one resale cycle per artwork. Future repeat
-- resales need separate transaction/cycle records; do not overwrite provenance.
