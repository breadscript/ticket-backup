-- Update existing financial_requests records to populate created_by_user_id
-- This script maps the payee field to the corresponding user ID from sys_usertb

-- First, let's see what payee values we have and their corresponding user IDs
SELECT DISTINCT 
    fr.payee,
    u.id as user_id,
    CONCAT(u.user_firstname, ' ', u.user_lastname) as full_name
FROM financial_requests fr
LEFT JOIN sys_usertb u ON (
    u.user_firstname = SUBSTRING_INDEX(fr.payee, ' ', 1) 
    OR u.user_lastname = SUBSTRING_INDEX(fr.payee, ' ', -1)
    OR CONCAT(u.user_firstname, ' ', u.user_lastname) = fr.payee
    OR u.username = fr.payee
)
WHERE fr.created_by_user_id IS NULL
ORDER BY fr.payee;

-- Update records where payee matches user firstname + lastname
UPDATE financial_requests fr
JOIN sys_usertb u ON CONCAT(u.user_firstname, ' ', u.user_lastname) = fr.payee
SET fr.created_by_user_id = u.id
WHERE fr.created_by_user_id IS NULL;

-- Update records where payee matches username
UPDATE financial_requests fr
JOIN sys_usertb u ON u.username = fr.payee
SET fr.created_by_user_id = u.id
WHERE fr.created_by_user_id IS NULL;

-- Update records where payee matches just the firstname
UPDATE financial_requests fr
JOIN sys_usertb u ON u.user_firstname = fr.payee
SET fr.created_by_user_id = u.id
WHERE fr.created_by_user_id IS NULL;

-- Update records where payee matches just the lastname
UPDATE financial_requests fr
JOIN sys_usertb u ON u.user_lastname = fr.payee
SET fr.created_by_user_id = u.id
WHERE fr.created_by_user_id IS NULL;

-- For any remaining NULL values, set to a default user (you can change this to a specific user ID)
-- UPDATE financial_requests SET created_by_user_id = 1 WHERE created_by_user_id IS NULL;

-- Verify the updates
SELECT 
    fr.doc_number,
    fr.request_type,
    fr.doc_number,
    fr.payee,
    fr.created_by_user_id,
    CONCAT(u.user_firstname, ' ', u.user_lastname) as creator_name
FROM financial_requests fr
LEFT JOIN sys_usertb u ON fr.created_by_user_id = u.id
ORDER BY fr.doc_number;

-- Show any records that still have NULL created_by_user_id
SELECT 
    id,
    request_type,
    doc_number,
    payee,
    created_by_user_id
FROM financial_requests 
WHERE created_by_user_id IS NULL;
