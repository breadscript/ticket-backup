-- Enhanced PAW Database Schema
-- Updated to support new requirements and features

-- Add new columns to existing paw_main table
ALTER TABLE `paw_main` 
ADD COLUMN `pwp_number` varchar(50) DEFAULT NULL AFTER `paw_no`,
ADD COLUMN `baseline_sales_source` varchar(100) DEFAULT NULL AFTER `total_value`,
ADD COLUMN `channel_manager_verified` tinyint(1) DEFAULT 0 AFTER `baseline_sales_source`,
ADD COLUMN `internal_approval_complete` tinyint(1) DEFAULT 0 AFTER `channel_manager_verified`,
ADD COLUMN `actual_incremental_sales` decimal(15,2) DEFAULT NULL AFTER `total_amount`,
ADD COLUMN `actual_total_sales` decimal(15,2) DEFAULT NULL AFTER `actual_incremental_sales`,
ADD COLUMN `variance_analysis` text AFTER `actual_total_sales`,
ADD COLUMN `reconciliation_status` enum('Pending','In Progress','Completed','Discrepancy','NTE Issued') DEFAULT 'Pending' AFTER `variance_analysis`,
ADD COLUMN `reconciliation_notes` text AFTER `reconciliation_status`,
ADD COLUMN `signed_trade_letter` tinyint(1) DEFAULT 0 AFTER `reconciliation_notes`,
ADD COLUMN `signed_display_contract` tinyint(1) DEFAULT 0 AFTER `signed_trade_letter`,
ADD COLUMN `signed_agreement` tinyint(1) DEFAULT 0 AFTER `signed_display_contract`;

-- Add new approver fields to paw_concurrence table
ALTER TABLE `paw_concurrence`
ADD COLUMN `channel_manager_approver` varchar(100) DEFAULT NULL AFTER `paw_id`,
ADD COLUMN `trade_marketing_approver` varchar(100) DEFAULT NULL AFTER `channel_manager_approver`,
ADD COLUMN `finance_approver` varchar(100) DEFAULT NULL AFTER `trade_marketing_approver`,
ADD COLUMN `sales_head_approver` varchar(100) DEFAULT NULL AFTER `finance_approver`,
ADD COLUMN `cco_approver` varchar(100) DEFAULT NULL AFTER `sales_head_approver`;

-- Create new table for signed documents
CREATE TABLE IF NOT EXISTS `paw_signed_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paw_id` int(11) NOT NULL,
  `document_type` enum('trade_letter','display_contract','agreement','other') NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `paw_id` (`paw_id`),
  CONSTRAINT `paw_signed_documents_ibfk_1` FOREIGN KEY (`paw_id`) REFERENCES `paw_main` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create new table for PWP assignment tracking
CREATE TABLE IF NOT EXISTS `paw_pwp_assignment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paw_id` int(11) NOT NULL,
  `pwp_number` varchar(50) NOT NULL,
  `assigned_by` varchar(100) NOT NULL,
  `assigned_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `central_database_logged` tinyint(1) DEFAULT 0,
  `tlc_folder_path` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pwp_number` (`pwp_number`),
  KEY `paw_id` (`paw_id`),
  CONSTRAINT `paw_pwp_assignment_ibfk_1` FOREIGN KEY (`paw_id`) REFERENCES `paw_main` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create new table for reconciliation tracking
CREATE TABLE IF NOT EXISTS `paw_reconciliation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paw_id` int(11) NOT NULL,
  `reconciliation_date` date DEFAULT NULL,
  `reconciled_by` varchar(100) DEFAULT NULL,
  `discrepancy_amount` decimal(15,2) DEFAULT NULL,
  `discrepancy_reason` text,
  `nte_issued` tinyint(1) DEFAULT 0,
  `nte_date` date DEFAULT NULL,
  `nte_issued_by` varchar(100) DEFAULT NULL,
  `resolution_date` date DEFAULT NULL,
  `resolution_notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `paw_id` (`paw_id`),
  CONSTRAINT `paw_reconciliation_ibfk_1` FOREIGN KEY (`paw_id`) REFERENCES `paw_main` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create new table for approval workflow tracking
CREATE TABLE IF NOT EXISTS `paw_approval_workflow` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paw_id` int(11) NOT NULL,
  `sequence_order` int(11) NOT NULL,
  `approver_role` varchar(100) NOT NULL,
  `approver_name` varchar(100) NOT NULL,
  `status` enum('Pending','Approved','Rejected','Returned') DEFAULT 'Pending',
  `received_date` date DEFAULT NULL,
  `approved_date` date DEFAULT NULL,
  `remarks` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `paw_id` (`paw_id`),
  CONSTRAINT `paw_approval_workflow_ibfk_1` FOREIGN KEY (`paw_id`) REFERENCES `paw_main` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create indexes for better performance
CREATE INDEX `idx_paw_status` ON `paw_main` (`status`);
CREATE INDEX `idx_paw_company` ON `paw_main` (`company`);
CREATE INDEX `idx_paw_dates` ON `paw_main` (`promo_from_date`, `promo_to_date`);
CREATE INDEX `idx_paw_reconciliation` ON `paw_main` (`reconciliation_status`);
CREATE INDEX `idx_pwp_number` ON `paw_main` (`pwp_number`);

-- Add comments to tables for documentation
ALTER TABLE `paw_main` COMMENT = 'Enhanced PAW main table with new requirements support';
ALTER TABLE `paw_concurrence` COMMENT = 'Enhanced approval workflow with specific approvers';
ALTER TABLE `paw_signed_documents` COMMENT = 'Signed document tracking for reconciliation';
ALTER TABLE `paw_pwp_assignment` COMMENT = 'PWP number assignment and central database logging';
ALTER TABLE `paw_reconciliation` COMMENT = 'Reconciliation tracking and NTE management';
ALTER TABLE `paw_approval_workflow` COMMENT = 'Detailed approval workflow tracking';
