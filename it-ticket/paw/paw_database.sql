-- PAW Database Schema
-- Create database tables for Promotional Activity Workplan system

-- Main PAW table
CREATE TABLE IF NOT EXISTS `paw_main` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paw_no` varchar(50) NOT NULL,
  `paw_date` date NOT NULL,
  `company` varchar(100) NOT NULL,
  `internal_order_no` varchar(100) DEFAULT NULL,
  `brand_skus` text,
  `lead_brand_skus` text,
  `participating_brands` text,
  `sales_group` varchar(100) DEFAULT NULL,
  `sharing_brand_skus` text,
  `specific_scheme` text,
  `activity_title` text,
  `activity_mechanics` text,
  `target_incremental_value` decimal(15,2) DEFAULT 0.00,
  `current_value_wo_promo` decimal(15,2) DEFAULT 0.00,
  `total_value` decimal(15,2) DEFAULT 0.00,
  `promo_from_date` date DEFAULT NULL,
  `promo_to_date` date DEFAULT NULL,
  `ave_selling_price` decimal(15,2) DEFAULT 0.00,
  `target_no_outlets` int(11) DEFAULT 0,
  `outlet_details` text,
  `area_coverage` text,
  `cost_summary_total` decimal(15,2) DEFAULT 0.00,
  `expense_wo_promo` decimal(15,2) DEFAULT 0.00,
  `expense_w_promo` decimal(15,2) DEFAULT 0.00,
  `sales_wo_promo` decimal(15,2) DEFAULT 0.00,
  `sales_w_promo` decimal(15,2) DEFAULT 0.00,
  `ratio_wo_promo` varchar(50) DEFAULT NULL,
  `ratio_w_promo` varchar(50) DEFAULT NULL,
  `justification` text,
  `billing_brand_sales_group` varchar(100) DEFAULT NULL,
  `charge_account_number` varchar(100) DEFAULT NULL,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `submitted_signature` varchar(100) DEFAULT NULL,
  `submitted_name` varchar(100) DEFAULT NULL,
  `attachments_specify` text,
  `status` enum('draft','submitted','approved','rejected') DEFAULT 'draft',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `paw_no` (`paw_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PAW Activities table
CREATE TABLE IF NOT EXISTS `paw_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paw_id` int(11) NOT NULL,
  `listing_fee` tinyint(1) DEFAULT 0,
  `rentables` tinyint(1) DEFAULT 0,
  `product_sampling` tinyint(1) DEFAULT 0,
  `merchandising` tinyint(1) DEFAULT 0,
  `special_events` tinyint(1) DEFAULT 0,
  `product_donation` tinyint(1) DEFAULT 0,
  `other` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `paw_id` (`paw_id`),
  CONSTRAINT `paw_activities_ibfk_1` FOREIGN KEY (`paw_id`) REFERENCES `paw_main` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PAW Objectives table
CREATE TABLE IF NOT EXISTS `paw_objectives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paw_id` int(11) NOT NULL,
  `product_availability` tinyint(1) DEFAULT 0,
  `trial` tinyint(1) DEFAULT 0,
  `incremental_sales` tinyint(1) DEFAULT 0,
  `market_development` tinyint(1) DEFAULT 0,
  `move_out_programs` tinyint(1) DEFAULT 0,
  `others` tinyint(1) DEFAULT 0,
  `others_specify` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `paw_id` (`paw_id`),
  CONSTRAINT `paw_objectives_ibfk_1` FOREIGN KEY (`paw_id`) REFERENCES `paw_main` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PAW Outlets table
CREATE TABLE IF NOT EXISTS `paw_outlets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paw_id` int(11) NOT NULL,
  `supermarkets` tinyint(1) DEFAULT 0,
  `cstores` tinyint(1) DEFAULT 0,
  `horeca` tinyint(1) DEFAULT 0,
  `retail` tinyint(1) DEFAULT 0,
  `ecom` tinyint(1) DEFAULT 0,
  `others` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `paw_id` (`paw_id`),
  CONSTRAINT `paw_outlets_ibfk_1` FOREIGN KEY (`paw_id`) REFERENCES `paw_main` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PAW Cost Items table
CREATE TABLE IF NOT EXISTS `paw_cost_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paw_id` int(11) NOT NULL,
  `item_description` varchar(255) NOT NULL,
  `quantity` decimal(10,2) DEFAULT 0.00,
  `unit_price` decimal(15,2) DEFAULT 0.00,
  `total_price` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `paw_id` (`paw_id`),
  CONSTRAINT `paw_cost_items_ibfk_1` FOREIGN KEY (`paw_id`) REFERENCES `paw_main` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PAW Concurrence table
CREATE TABLE IF NOT EXISTS `paw_concurrence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paw_id` int(11) NOT NULL,
  `channel_manager_received` date DEFAULT NULL,
  `channel_manager_released` date DEFAULT NULL,
  `trade_marketing_received` date DEFAULT NULL,
  `trade_marketing_released` date DEFAULT NULL,
  `sales_head_received` date DEFAULT NULL,
  `sales_head_released` date DEFAULT NULL,
  `finance_manager_received` date DEFAULT NULL,
  `finance_manager_released` date DEFAULT NULL,
  `cco_received` date DEFAULT NULL,
  `cco_released` date DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `paw_id` (`paw_id`),
  CONSTRAINT `paw_concurrence_ibfk_1` FOREIGN KEY (`paw_id`) REFERENCES `paw_main` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PAW Attachments table
CREATE TABLE IF NOT EXISTS `paw_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paw_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `paw_id` (`paw_id`),
  CONSTRAINT `paw_attachments_ibfk_1` FOREIGN KEY (`paw_id`) REFERENCES `paw_main` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample company data if not exists
INSERT IGNORE INTO `company` (`company_code`, `company_name`, `company_id`) VALUES
('MPAV', 'Metro Pacific Agro Ventures', 1),
('MPIC', 'Metro Pacific Investments Corporation', 2),
('MPTC', 'Metro Pacific Tollways Corporation', 3);

-- Insert sample department data if not exists
INSERT IGNORE INTO `department` (`company_code`, `department_code`, `department_name`, `department_id`) VALUES
('MPAV', 'MKT', 'Marketing', 1),
('MPAV', 'SALES', 'Sales', 2),
('MPAV', 'FIN', 'Finance', 3),
('MPAV', 'OPS', 'Operations', 4);

-- Insert sample categories if not exists
INSERT IGNORE INTO `categories` (`category_code`, `category_name`, `is_active`) VALUES
('PROMO', 'Promotional Materials', 1),
('EVENT', 'Event Management', 1),
('ADV', 'Advertising', 1),
('SAMPLE', 'Product Sampling', 1),
('MERCH', 'Merchandising', 1);
