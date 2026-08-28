-- CRTSHT DRAW / independent package prices upgrade
-- Run once if sql/crtsht_draw_price_upgrade.sql has already been applied.

INSERT INTO `CRTSHT_Draw_Settings` (`SettingKey`,`SettingValue`)
VALUES
  ('price_1_chf','420.00'),
  ('price_2_chf','777.00'),
  ('price_3_chf','999.00')
ON DUPLICATE KEY UPDATE `SettingKey`=VALUES(`SettingKey`);

DROP TRIGGER IF EXISTS `trg_crtsht_draw_reservation_price`;
DELIMITER $$
CREATE TRIGGER `trg_crtsht_draw_reservation_price`
BEFORE INSERT ON `CRTSHT_Draw_Reservations`
FOR EACH ROW
BEGIN
  DECLARE v_total DECIMAL(10,2) DEFAULT 0.00;
  DECLARE v_key VARCHAR(64);

  SET v_key = CONCAT('price_', NEW.`Quantity`, '_chf');

  SELECT CAST(`SettingValue` AS DECIMAL(10,2))
  INTO v_total
  FROM `CRTSHT_Draw_Settings`
  WHERE `SettingKey` = v_key
  LIMIT 1;

  IF NEW.`TotalPrice` IS NULL OR NEW.`TotalPrice` <= 0 THEN
    SET NEW.`TotalPrice` = COALESCE(v_total, 0.00);
  END IF;

  IF NEW.`Quantity` > 0 THEN
    SET NEW.`UnitPrice` = NEW.`TotalPrice` / NEW.`Quantity`;
  ELSE
    SET NEW.`UnitPrice` = 0.00;
  END IF;
END$$
DELIMITER ;

-- The public/default package prices can then be changed from:
-- /crtshtdrwmng/price.php
--
-- Existing reservations retain their stored TotalPrice.
