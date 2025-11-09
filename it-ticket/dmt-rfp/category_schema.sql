-- Category master data schema
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_code` VARCHAR(50) NOT NULL,
  `category_id` VARCHAR(50) NOT NULL,
  `category_name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_category_code` (`category_code`),
  UNIQUE KEY `uniq_category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed categories
INSERT INTO `categories` (`category_code`, `category_id`, `category_name`, `description`, `is_active`) VALUES
('FOOD', 'C001', 'Food & Beverages', NULL, 1),
('TRANS', 'C002', 'Transportation', NULL, 1),
('LODGE', 'C003', 'Accommodation / Lodging', NULL, 1),
('UTIL', 'C004', 'Utilities (Electricity, Water, Gas)', NULL, 1),
('OFFSUP', 'C005', 'Office Supplies', NULL, 1),
('COMM', 'C006', 'Communication (Phone, Internet)', NULL, 1),
('FUEL', 'C007', 'Fuel / Gasoline', NULL, 1),
('EQP', 'C008', 'Equipment Purchase', NULL, 1),
('EQPMR', 'C009', 'Equipment Maintenance & Repair', NULL, 1),
('TRAIN', 'C010', 'Training & Seminars', NULL, 1),
('MKT', 'C011', 'Marketing & Advertising', NULL, 1),
('HOSP', 'C012', 'Entertainment & Hospitality', NULL, 1),
('MED', 'C013', 'Medical & Health Expenses', NULL, 1),
('INS', 'C014', 'Insurance Premiums', NULL, 1),
('PROF', 'C015', 'Professional Fees (Consultants, Legal, Audit)', NULL, 1),
('SUBS', 'C016', 'Memberships & Subscriptions', NULL, 1),
('SHIP', 'C017', 'Courier & Shipping Services', NULL, 1),
('GIFT', 'C018', 'Gifts & Giveaways', NULL, 1),
('TRAVL', 'C019', 'Travel Allowance', NULL, 1),
('MISC', 'C020', 'Miscellaneous / Other Expenses', NULL, 1)
ON DUPLICATE KEY UPDATE `category_name` = VALUES(`category_name`);