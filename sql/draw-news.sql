-- Run once before publishing the newsletter form.
-- Independent of historical draw reservations and payments.
CREATE TABLE IF NOT EXISTS CRTSHT_Draw_News (
 ID BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 Name VARCHAR(120) NOT NULL,
 Email VARCHAR(190) NOT NULL,
 ConsentAt DATETIME NOT NULL,
 CreatedAt DATETIME NOT NULL,
 UNIQUE KEY uniq_news_email (Email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
