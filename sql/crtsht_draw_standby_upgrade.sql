-- CRTSHT DRAW / standby queue
-- Run once after the draw reservation tables exist.

CREATE TABLE `CRTSHT_Draw_Standby` (
  `ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ReservationCode` VARCHAR(32) NOT NULL,
  `DrawBatch` CHAR(2) NOT NULL,
  `Quantity` TINYINT UNSIGNED NOT NULL,
  `TotalPrice` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `Name` VARCHAR(120) NOT NULL,
  `Email` VARCHAR(190) NOT NULL,
  `Mobile` VARCHAR(50) NOT NULL,
  `Address` VARCHAR(190) NOT NULL,
  `PLZ` VARCHAR(24) NOT NULL,
  `City` VARCHAR(120) NOT NULL,
  `Country` VARCHAR(120) NOT NULL,
  `Status` ENUM('standby','promoted','cancelled') NOT NULL DEFAULT 'standby',
  `PromotedReservationID` BIGINT UNSIGNED NULL DEFAULT NULL,
  `CreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uq_crtsht_draw_standby_code` (`ReservationCode`),
  KEY `idx_crtsht_draw_standby_status_created` (`Status`,`CreatedAt`),
  CONSTRAINT `chk_crtsht_draw_standby_quantity` CHECK (`Quantity` BETWEEN 1 AND 3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
