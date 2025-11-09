-- Add created_by_user_id field to financial_requests table
-- Run this if the field doesn't exist yet

ALTER TABLE `financial_requests` 
ADD COLUMN `created_by_user_id` bigint(20) DEFAULT NULL AFTER `status`;

-- Add index for better performance
ALTER TABLE `financial_requests` 
ADD INDEX `idx_created_by_user_id` (`created_by_user_id`);

-- Add foreign key constraint (optional - uncomment if you want referential integrity)
-- ALTER TABLE `financial_requests` 
-- ADD CONSTRAINT `fk_financial_requests_created_by` 
-- FOREIGN KEY (`created_by_user_id`) REFERENCES `sys_usertb`(`id`) 
-- ON DELETE SET NULL ON UPDATE CASCADE;

-- Verify the field was added
DESCRIBE `financial_requests`;
