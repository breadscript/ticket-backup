-- Withholding Taxes Table Schema
-- This table stores withholding tax and final tax configurations

CREATE TABLE IF NOT EXISTS `withholding_taxes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tax_code` VARCHAR(50) NOT NULL,
  `tax_name` VARCHAR(200) NOT NULL,
  `tax_rate` DECIMAL(5,2) NOT NULL COMMENT 'Tax rate in percentage (e.g., 15.00 for 15%)',
  `tax_type` ENUM('withholding', 'final') NOT NULL DEFAULT 'withholding',
  `description` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` INT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_tax_code` (`tax_code`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed initial withholding taxes
INSERT INTO `withholding_taxes` (`tax_code`, `tax_name`, `tax_rate`, `tax_type`, `description`, `is_active`) VALUES
('NONE', 'None', 0.00, 'withholding', 'No withholding tax applied', 1),
('WI011', 'PROFESSIONALS/TALENT FEES', 15.00, 'withholding', '15% Withholding Tax on Professional/Talent Fees', 1),
('WC100', 'RENTALS', 5.00, 'withholding', '5% Withholding Tax on Rentals', 1),
('WC158', 'GOODS', 1.00, 'withholding', '1% Withholding Tax on Goods', 1),
('WC160', 'SERVICES', 2.00, 'withholding', '2% Withholding Tax on Services', 1),
('WC516', 'COMM, REBATES, DISCOUNTS', 15.00, 'withholding', '15% Withholding Tax on Commission, Rebates, Discounts', 1),
('EWT02', 'Expanded Withholding Tax', 2.00, 'withholding', '2% Expanded Withholding Tax', 1),
('FT05', 'Final Tax', 5.00, 'final', '5% Final Tax', 1),
('FT10', 'Final Tax', 10.00, 'final', '10% Final Tax', 1),
('FT15', 'Final Tax', 15.00, 'final', '15% Final Tax', 1),
('FT20', 'Final Tax', 20.00, 'final', '20% Final Tax', 1),
('FT25', 'Final Tax', 25.00, 'final', '25% Final Tax', 1),
('FT30', 'Final Tax', 30.00, 'final', '30% Final Tax', 1)
ON DUPLICATE KEY UPDATE 
  `tax_name` = VALUES(`tax_name`),
  `tax_rate` = VALUES(`tax_rate`),
  `tax_type` = VALUES(`tax_type`),
  `description` = VALUES(`description`);

