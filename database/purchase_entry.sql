-- Purchase Entry & Reporting System
-- Import this file into MySQL (phpMyAdmin, mysql CLI, or MAMP/XAMPP tools).
-- Default database name: purchase_entry

CREATE DATABASE IF NOT EXISTS `purchase_entry`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `purchase_entry`;

DROP TABLE IF EXISTS `purchases`;

CREATE TABLE `purchases` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `amount` int(10) NOT NULL,
  `buyer` varchar(255) NOT NULL,
  `receipt_id` varchar(20) NOT NULL,
  `items` varchar(255) NOT NULL,
  `buyer_email` varchar(50) NOT NULL,
  `buyer_ip` varchar(20) DEFAULT NULL,
  `note` text NOT NULL,
  `city` varchar(20) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `hash_key` varchar(255) DEFAULT NULL,
  `entry_at` date DEFAULT NULL,
  `entry_by` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_entry_at` (`entry_at`),
  KEY `idx_entry_by` (`entry_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample rows so the report page is populated on first run.
-- hash_key = SHA-512(receipt_id + server salt from config/app.php)
INSERT INTO `purchases`
  (`amount`, `buyer`, `receipt_id`, `items`, `buyer_email`, `buyer_ip`, `note`, `city`, `phone`, `hash_key`, `entry_at`, `entry_by`)
VALUES
(
  2500,
  'Karim Ahmed',
  'ABCDE',
  'Laptop Bag, Mouse',
  'karim@example.com',
  '127.0.0.1',
  'First purchase for office supplies this quarter.',
  'Dhaka',
  '8801712345678',
  'a66b444bfdbcc0802f31a31f42061f766624a6d4e19655d53e186a462d8a220c99ee96679303763c80311c9a0194dfd6728684e4e35658a4b33c55a32c560628',
  '2026-07-20',
  1
),
(
  890,
  'Nusrat Jahan',
  'FGHIJ',
  'Notebook, Pen Set',
  'nusrat@example.com',
  '127.0.0.1',
  'Stationery refill for the design team.',
  'Chittagong',
  '8801811223344',
  '72e5d590e0659fbec2f0ad1e6cd363b6d1ecb0629185d2f118bdf768c1a845da1955489267fc518178ae313e1427d52ce2a1d8fa42b2d3549ce5f93ec8027be5',
  '2026-07-22',
  2
),
(
  4500,
  'Rafi Hasan',
  'KLMNO',
  'Monitor Stand',
  'rafi@example.com',
  '192.168.1.10',
  'Ergonomic stand for remote workstation setup.',
  'Sylhet',
  '8801912345670',
  '35ade4b8893cf8803651f6575b0bc0fcc74a27091556c6286fde75af0194507098665dd093c3c9a5cc0a992cbdae9d9ffe37d91dbfd52cc8e6821ee6f1dafcc5',
  '2026-07-25',
  1
),
(
  1200,
  'Sadia Islam',
  'PQRST',
  'USB Hub, Cable',
  'sadia@example.com',
  '10.0.0.5',
  'Accessories for new hire onboarding kit.',
  'Khulna',
  '8801611122233',
  '5588d8c2626279106cebb7e0c8bd5d8d31406af50af8d33da92354b38a4ccd5927993e23d9c730f4d099baca2437dd458dc814528f11db676c1c4c6ff44f5b5c',
  '2026-07-28',
  3
),
(
  3200,
  'Imran Khan',
  'UVWXY',
  'Keyboard, Headset',
  'imran@example.com',
  '127.0.0.1',
  'Replacement peripherals after hardware audit.',
  'Rajshahi',
  '8801555666777',
  '3728bb67b0c534f91bde684a4d36a2581c0b27c584909428e0f5c8f90a99cb3575f10085df76d1fc7f67275edd206396faebbe85bf9bbb2c337a7dbf48855532',
  '2026-07-30',
  2
);
