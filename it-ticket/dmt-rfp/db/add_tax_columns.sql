-- Add new tax-related columns to financial_request_items table
-- For RFP, ERL and ERGR request types

ALTER TABLE `financial_request_items` 
ADD COLUMN `gross_amount` decimal(14,2) DEFAULT NULL COMMENT 'Gross amount before taxes',
ADD COLUMN `vatable` enum('yes','no') DEFAULT NULL COMMENT 'Whether the item is subject to VAT',
ADD COLUMN `vat_amount` decimal(14,2) DEFAULT NULL COMMENT 'VAT amount (12% of gross if vatable)',
ADD COLUMN `withholding_tax` varchar(100) DEFAULT NULL COMMENT 'Type of withholding tax applied',
ADD COLUMN `amount_withhold` decimal(14,2) DEFAULT NULL COMMENT 'Amount withheld for tax',
ADD COLUMN `net_payable_amount` decimal(14,2) DEFAULT NULL COMMENT 'Final amount to be paid after taxes';

-- Add new columns for ERGR and ERL request types
ALTER TABLE `financial_request_items` 
ADD COLUMN `carf_no` varchar(150) DEFAULT NULL COMMENT 'CARF Number',
ADD COLUMN `pcv_no` varchar(150) DEFAULT NULL COMMENT 'PCV Number',
ADD COLUMN `invoice_date` date DEFAULT NULL COMMENT 'Invoice Date',
ADD COLUMN `invoice_number` varchar(150) DEFAULT NULL COMMENT 'Invoice Number',
ADD COLUMN `supplier_name` varchar(255) DEFAULT NULL COMMENT 'Supplier/Store Name',
ADD COLUMN `tin` varchar(50) DEFAULT NULL COMMENT 'Tax Identification Number',
ADD COLUMN `address` text DEFAULT NULL COMMENT 'Supplier Address';

-- Add index for better performance on tax calculations
ALTER TABLE `financial_request_items` 
ADD INDEX `idx_tax_fields` (`vatable`, `withholding_tax`);

-- Add index for ERGR/ERL specific fields
ALTER TABLE `financial_request_items` 
ADD INDEX `idx_er_fields` (`carf_no`, `pcv_no`, `invoice_number`);
