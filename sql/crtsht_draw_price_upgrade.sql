-- CRTSHT DRAW / pricing upgrade
-- Run once after sql/crtsht_draw.sql if your tables already exist.

ALTER TABLE `CRTSHT_Draw_Reservations`
  ADD COLUMN `UnitPrice` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `Quantity`,
  ADD COLUMN `TotalPrice` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `UnitPrice`;

CREATE TABLE `CRTSHT_Draw_Settings` (
  `SettingKey` VARCHAR(64) NOT NULL,
  `SettingValue` VARCHAR(255) NOT NULL,
  `UpdatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`SettingKey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `CRTSHT_Draw_Settings` (`SettingKey`,`SettingValue`)
VALUES ('entry_price_chf','0.00')
ON DUPLICATE KEY UPDATE `SettingKey`=VALUES(`SettingKey`);

DROP TRIGGER IF EXISTS `trg_crtsht_draw_reservation_price`;
DELIMITER $$
CREATE TRIGGER `trg_crtsht_draw_reservation_price`
BEFORE INSERT ON `CRTSHT_Draw_Reservations`
FOR EACH ROW
BEGIN
  DECLARE v_price DECIMAL(10,2) DEFAULT 0.00;

  SELECT CAST(`SettingValue` AS DECIMAL(10,2))
  INTO v_price
  FROM `CRTSHT_Draw_Settings`
  WHERE `SettingKey`='entry_price_chf'
  LIMIT 1;

  IF NEW.`UnitPrice` IS NULL OR NEW.`UnitPrice` <= 0 THEN
    SET NEW.`UnitPrice` = COALESCE(v_price, 0.00);
  END IF;

  SET NEW.`TotalPrice` = NEW.`Quantity` * NEW.`UnitPrice`;
END$$
DELIMITER ;

-- Existing reservations have no historical public price available automatically.
-- Set them manually in the backend if needed.
