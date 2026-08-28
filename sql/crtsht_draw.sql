-- CRTSHT DRAW / reservation + ticket schema
-- MySQL 8+ / MariaDB compatible
-- Run once in the same database used by inc/bootstrap.php.

CREATE TABLE `CRTSHT_Draw_Reservations` (
  `ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ReservationCode` VARCHAR(32) NOT NULL,
  `DrawBatch` CHAR(2) NOT NULL,
  `Quantity` TINYINT UNSIGNED NOT NULL,
  `Name` VARCHAR(120) NOT NULL,
  `Email` VARCHAR(190) NOT NULL,
  `Mobile` VARCHAR(50) NOT NULL,
  `Address` VARCHAR(190) NOT NULL,
  `PLZ` VARCHAR(24) NOT NULL,
  `City` VARCHAR(120) NOT NULL,
  `Country` VARCHAR(120) NOT NULL,
  `Status` ENUM('reserved','paid','cancelled') NOT NULL DEFAULT 'reserved',
  `PaidAt` DATETIME NULL DEFAULT NULL,
  `PaymentNote` VARCHAR(255) NULL DEFAULT NULL,
  `CreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uq_crtsht_draw_reservation_code` (`ReservationCode`),
  KEY `idx_crtsht_draw_reservation_status` (`Status`),
  KEY `idx_crtsht_draw_reservation_batch` (`DrawBatch`),
  KEY `idx_crtsht_draw_reservation_email` (`Email`),
  CONSTRAINT `chk_crtsht_draw_quantity` CHECK (`Quantity` BETWEEN 1 AND 3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `CRTSHT_Draw_Entries` (
  `ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ReservationID` BIGINT UNSIGNED NOT NULL,
  `DrawBatch` CHAR(2) NOT NULL,
  `Status` ENUM('reserved','paid','assigned','cancelled') NOT NULL DEFAULT 'reserved',
  `AssignedCRTSHT` SMALLINT UNSIGNED NULL DEFAULT NULL,
  `AssignedAt` DATETIME NULL DEFAULT NULL,
  `CreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uq_crtsht_draw_assigned_work` (`AssignedCRTSHT`),
  KEY `idx_crtsht_draw_entry_reservation` (`ReservationID`),
  KEY `idx_crtsht_draw_entry_batch_status` (`DrawBatch`,`Status`),
  CONSTRAINT `fk_crtsht_draw_entry_reservation`
    FOREIGN KEY (`ReservationID`) REFERENCES `CRTSHT_Draw_Reservations` (`ID`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `chk_crtsht_draw_assigned_work`
    CHECK (`AssignedCRTSHT` IS NULL OR (`AssignedCRTSHT` BETWEEN 1 AND 128))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PAYMENT WORKFLOW EXAMPLE
-- After manually confirming payment for reservation ID 27:
--
-- START TRANSACTION;
-- UPDATE `CRTSHT_Draw_Reservations`
-- SET `Status`='paid', `PaidAt`=NOW()
-- WHERE `ID`=27 AND `Status`='reserved';
--
-- UPDATE `CRTSHT_Draw_Entries`
-- SET `Status`='paid'
-- WHERE `ReservationID`=27 AND `Status`='reserved';
-- COMMIT;

-- CAPACITY CHECK
-- Reserved + paid slots count against the physical limit of 128.
-- SELECT COUNT(*) AS used_slots
-- FROM `CRTSHT_Draw_Entries` e
-- INNER JOIN `CRTSHT_Draw_Reservations` r ON r.`ID`=e.`ReservationID`
-- WHERE r.`Status` IN ('reserved','paid');
