-- Normalized Financial Requests schema
-- Base table keeps Header and Footer fields
-- Child tables hold multiple Item Details and Advanced (Breakdown) rows

-- Drop child tables first to avoid FK issues (for rebuilds)
DROP TABLE IF EXISTS `financial_request_breakdowns`;
DROP TABLE IF EXISTS `financial_request_items`;
DROP TABLE IF EXISTS `financial_requests`;

CREATE TABLE IF NOT EXISTS `financial_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_type` ENUM('RFP','ERL','ERGR') NOT NULL,

  -- Header fields
  `company` VARCHAR(150) NULL,
  `doc_type` VARCHAR(150) NULL,
  `doc_number` VARCHAR(150) NULL,
  `doc_date` DATE NULL,
  `cost_center` VARCHAR(150) NULL,
  `expenditure_type` ENUM('capex','opex') NULL,
  `balance` DECIMAL(14,2) NULL,
  `budget` DECIMAL(14,2) NULL,
  `currency` VARCHAR(10) NULL,
  `amount_figures` DECIMAL(14,2) NULL,

  -- Footer fields
  `payee` BIGINT NULL,  -- Changed from VARCHAR(255) to BIGINT to store user ID from sys_usertb
  `amount_in_words` VARCHAR(500) NULL,
  `payment_for` TEXT NULL,
  `special_instructions` TEXT NULL,
  `supporting_document_path` VARCHAR(500) NULL,
  `is_budgeted` TINYINT(1) NULL,
  `from_company` VARCHAR(150) NULL,
  `to_company` VARCHAR(150) NULL,
  `credit_to_payroll` TINYINT(1) NULL,
  `issue_check` TINYINT(1) NULL,

  -- Meta
  `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `created_by_user_id` BIGINT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_request_type` (`request_type`),
  KEY `idx_doc_number` (`doc_number`),
  KEY `idx_doc_date` (`doc_date`),
  KEY `idx_payee` (`payee`),
  KEY `idx_company` (`company`),
  CONSTRAINT `fk_financial_requests_payee` FOREIGN KEY (`payee`) REFERENCES `sys_usertb`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Item Details: multiple rows per financial request
CREATE TABLE IF NOT EXISTS `financial_request_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `doc_number` BIGINT UNSIGNED NOT NULL,

  `category` VARCHAR(150) NULL,
  `attachment_path` VARCHAR(500) NULL,
  `description` TEXT NULL,
  `amount` DECIMAL(14,2) NULL,
  `reference_number` VARCHAR(150) NULL,
  `po_number` VARCHAR(150) NULL,
  `due_date` DATE NULL,
  `cash_advance` TINYINT(1) NULL,
  `form_of_payment` ENUM('corporate_check','wire_transfer','cash','manager_check','credit_to_account','others') NULL,
  `budget_consumption` DECIMAL(14,2) NULL,

  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_doc_number` (`doc_number`),
  KEY `idx_due_date` (`due_date`),
  CONSTRAINT `fk_items_financial_request` FOREIGN KEY (`doc_number`) REFERENCES `financial_requests`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Advanced (Breakdown): multiple rows per financial request
CREATE TABLE IF NOT EXISTS `financial_request_breakdowns` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `doc_number` BIGINT UNSIGNED NOT NULL,

  `reference_number_2` VARCHAR(150) NULL,
  `date` DATE NULL,
  `amount2` DECIMAL(14,2) NULL,

  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_doc_number` (`doc_number`),
  KEY `idx_date` (`date`),
  CONSTRAINT `fk_breakdowns_financial_request` FOREIGN KEY (`doc_number`) REFERENCES `financial_requests`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Optional view for quick table listing
DROP VIEW IF EXISTS `vw_financial_requests_summary`;
CREATE VIEW `vw_financial_requests_summary` AS
SELECT fr.doc_number,
       fr.request_type,
       fr.company,
       fr.doc_type,
       fr.doc_number,
       fr.doc_date,
       fr.cost_center,
       fr.expenditure_type,
       fr.currency,
       fr.payee,
       fr.status,
       fr.created_at
FROM financial_requests fr;


