-- Workflow schema for approvers and process logs
-- Run this in your MySQL/MariaDB server (adjust database name if needed)

-- USE your_database_name;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- =========================================================
-- Table: work_flow_template
-- Defines approval template per document type/company/department
-- =========================================================
CREATE TABLE IF NOT EXISTS `work_flow_template` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `work_flow_id` VARCHAR(50) NOT NULL,              -- e.g., RFP, ERL, ERGR
  `department` VARCHAR(150) NULL,                   -- e.g., TLCI-IT (can be NULL or empty for global)
  `company` VARCHAR(150) NOT NULL,                  -- e.g., TLCI
  `sequence` INT NOT NULL,                          -- approval order (1..N)
  `actor_id` VARCHAR(150) NOT NULL,                 -- display name or user key (e.g., BIMSY, Accounting 1)
  `action` VARCHAR(150) NOT NULL,                   -- role/action key (e.g., Cost_Center_Head)
  `is_parellel` TINYINT(1) NOT NULL DEFAULT 0,      -- 1 if parallel approvals allowed at this sequence
  `global` TINYINT(1) NOT NULL DEFAULT 0,           -- 1 if applies globally
  `amount_from` DECIMAL(14,2) NULL,                 -- optional lower bound
  `amount_to` DECIMAL(14,2) NULL,                   -- optional upper bound
  `Note` VARCHAR(500) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lookup` (`work_flow_id`, `company`, `department`, `sequence`),
  KEY `idx_amount_range` (`amount_from`, `amount_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================================
-- Table: work_flow_process
-- Holds per-document approval instances and current statuses
-- =========================================================
CREATE TABLE IF NOT EXISTS `work_flow_process` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `doc_type` VARCHAR(50) NOT NULL,                  -- e.g., RFP, ERL, ERGR
  `doc_number` VARCHAR(150) NOT NULL,               -- e.g., RFP-0991
  `sequence` INT NOT NULL,                          -- sequence number matching template
  `actor_id` VARCHAR(150) NOT NULL,                 -- who is/was responsible at this step
  `action` VARCHAR(150) NOT NULL,                   -- role/action key matching template
  `status` VARCHAR(60) NOT NULL DEFAULT 'Waiting for Approval', -- e.g., Waiting for Approval, Approved, Declined, Return to Approver, Return to Requestor, Done
  `decided_by` VARCHAR(150) NULL,                   -- who took the action (can be same as actor_id)
  `decided_at` DATETIME NULL,
  `remarks` VARCHAR(500) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_doc` (`doc_type`, `doc_number`, `sequence`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================================
-- Table: work_flow_action_log
-- Append-only audit log of any insert/update on work_flow_process
-- =========================================================
CREATE TABLE IF NOT EXISTS `work_flow_action_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `process_id` BIGINT UNSIGNED NULL,
  `doc_type` VARCHAR(50) NOT NULL,
  `doc_number` VARCHAR(150) NOT NULL,
  `sequence` INT NOT NULL,
  `actor_id` VARCHAR(150) NOT NULL,
  `action` VARCHAR(150) NOT NULL,
  `prev_status` VARCHAR(60) NULL,
  `new_status` VARCHAR(60) NULL,
  `event` VARCHAR(60) NOT NULL,                     -- INSERT, UPDATE, APPROVE, DECLINE, RETURN, etc.
  `remarks` VARCHAR(500) NULL,
  `created_by` VARCHAR(150) NULL,                   -- user who performed the event
  `ip_address` VARCHAR(45) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_log_doc` (`doc_type`, `doc_number`, `sequence`),
  KEY `idx_event_time` (`event`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================================================
-- Triggers to auto-log process inserts/updates
-- =========================================================
DROP TRIGGER IF EXISTS `tr_wfp_ins_action`;
DELIMITER $$
CREATE TRIGGER `tr_wfp_ins_action` AFTER INSERT ON `work_flow_process`
FOR EACH ROW
BEGIN
  INSERT INTO work_flow_action_log
    (`process_id`,`doc_type`,`doc_number`,`sequence`,`actor_id`,`action`,`prev_status`,`new_status`,`event`,`remarks`,`created_by`,`ip_address`)
  VALUES
    (NEW.id, NEW.doc_type, NEW.doc_number, NEW.sequence, NEW.actor_id, NEW.action, NULL, NEW.status, 'INSERT', NEW.remarks, NEW.decided_by, NULL);
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `tr_wfp_upd_action`;
DELIMITER $$
CREATE TRIGGER `tr_wfp_upd_action` AFTER UPDATE ON `work_flow_process`
FOR EACH ROW
BEGIN
  IF (OLD.status <> NEW.status) OR (OLD.remarks <> NEW.remarks) OR (OLD.decided_by <> NEW.decided_by) OR (OLD.decided_at <> NEW.decided_at) THEN
    INSERT INTO work_flow_action_log
      (`process_id`,`doc_type`,`doc_number`,`sequence`,`actor_id`,`action`,`prev_status`,`new_status`,`event`,`remarks`,`created_by`,`ip_address`)
    VALUES
      (NEW.id, NEW.doc_type, NEW.doc_number, NEW.sequence, NEW.actor_id, NEW.action, OLD.status, NEW.status, 'UPDATE', NEW.remarks, NEW.decided_by, NULL);
  END IF;
END$$
DELIMITER ;

-- =========================================================
-- Seed data: RFP flow (example based on your document)
-- Adjust department/company/actor_id values to match your directory of users
-- =========================================================
INSERT INTO `work_flow_template` (`work_flow_id`,`department`,`company`,`sequence`,`actor_id`,`action`,`is_parellel`,`global`,`amount_from`,`amount_to`,`Note`)
VALUES
('RFP','',        'TLCI', 1, 'Requestor',     'Requestor',                 0, 1, NULL, NULL, NULL),
('RFP','TLCI-IT', 'TLCI', 2, 'BIMSY',        'Cost_Center_Head',          0, 0, NULL, NULL, NULL),
('RFP','TLCI-FIN','TLCI', 2, 'FM1',          'Cost_Center_Head',          0, 0, NULL, NULL, NULL),
('RFP','TLCI-ACC','TLCI', 2, 'FM2',          'Cost_Center_Head',          0, 0, NULL, NULL, NULL),
('RFP','TLCI-LOG','TLCI', 2, 'ARWIN',        'Cost_Center_Head',          0, 0, NULL, NULL, NULL),
('RFP','TLCI-PR', 'TLCI', 2, 'MIA',          'Cost_Center_Head',          0, 0, NULL, NULL, NULL),
('RFP','TLCI-SALES','TLCI',2,'TOBY',         'Cost_Center_Head',          0, 0, NULL, NULL, NULL),
('RFP','TLCI-ENGR','TLCI',2,'NAT',           'Cost_Center_Head',          0, 0, NULL, NULL, NULL),
('RFP','',        'TLCI', 3, 'Accounting 1', 'Accounting_Approver_1',     1, 1, NULL, NULL, 'One Approval Only'),
('RFP','',        'TLCI', 3, 'Accounting 2', 'Accounting_Approver_1_Sub', 1, 1, NULL, NULL, 'One Approval Only'),
('RFP','',        'TLCI', 4, 'Accounting 3', 'Accounting_Approver_2',     0, 1, NULL, NULL, NULL),
('RFP','',        'TLCI', 5, 'Controller',   'Accounting_Controller_1',    0, 1, NULL, NULL, NULL),
('RFP','',        'TLCI', 6, 'Cashier',      'Accounting_Cashier',         0, 1, NULL, NULL, NULL);


INSERT INTO `work_flow_template` (`work_flow_id`,`department`,`company`,`sequence`,`actor_id`,`action`,`is_parellel`,`global`,`amount_from`,`amount_to`,`Note`)
VALUES
('ERGR','',        'TLCI', 1, 'Requestor',     'Requestor',                 0, 1, NULL, NULL, NULL),
('ERGR','TLCI-IT', 'TLCI', 2, 'BIMSY',        'Cost_Center_Head',          0, 0, NULL, NULL, NULL),
('ERGR','TLCI-FIN','TLCI', 2, 'FM1',          'Cost_Center_Head',          0, 0, NULL, NULL, NULL),
('ERGR','TLCI-ACC','TLCI', 2, 'FM2',          'Cost_Center_Head',          0, 0, NULL, NULL, NULL),
('ERGR','TLCI-LOG','TLCI', 2, 'ARWIN',        'Cost_Center_Head',          0, 0, NULL, NULL, NULL),
('ERGR','TLCI-PR', 'TLCI', 2, 'MIA',          'Cost_Center_Head',          0, 0, NULL, NULL, NULL),
('ERGR','TLCI-SALES','TLCI',2,'TOBY',         'Cost_Center_Head',          0, 0, NULL, NULL, NULL),
('ERGR','TLCI-ENGR','TLCI',2,'NAT',           'Cost_Center_Head',          0, 0, NULL, NULL, NULL),
('ERGR','',        'TLCI', 3, 'Accounting 1', 'Accounting_Approver_1',     1, 1, NULL, NULL, 'One Approval Only'),
('ERGR','',        'TLCI', 3, 'Accounting 2', 'Accounting_Approver_1_Sub', 1, 1, NULL, NULL, 'One Approval Only'),
('ERGR','',        'TLCI', 4, 'Accounting 3', 'Accounting_Approver_2',     0, 1, NULL, NULL, NULL),
('ERGR','',        'TLCI', 5, 'Controller',   'Accounting_Controller_1',    0, 1, NULL, NULL, NULL),
('ERGR','',        'TLCI', 6, 'Cashier',      'Accounting_Cashier',         0, 1, NULL, NULL, NULL);

INSERT INTO `work_flow_template` (`work_flow_id`,`department`,`company`,`sequence`,`actor_id`,`action`,`is_parellel`,`global`,`amount_from`,`amount_to`,`Note`)
VALUES
('ERL','',        'TLCI', 1, 'Requestor',     'Requestor',                 0, 1, NULL, NULL, NULL),
('ERL','TLCI-IT', 'TLCI', 2, 'BIMSY',        'Cost_Center_Head',          0, 0, NULL, NULL, NULL),
('ERL','TLCI-FIN','TLCI', 2, 'FM1',          'Cost_Center_Head',          0, 0, NULL, NULL, NULL),
('ERL','TLCI-ACC','TLCI', 2, 'FM2',          'Cost_Center_Head',          0, 0, NULL, NULL, NULL),
('ERL','TLCI-LOG','TLCI', 2, 'ARWIN',        'Cost_Center_Head',          0, 0, NULL, NULL, NULL),
('ERL','TLCI-PR', 'TLCI', 2, 'MIA',          'Cost_Center_Head',          0, 0, NULL, NULL, NULL),
('ERL','TLCI-SALES','TLCI',2,'TOBY',         'Cost_Center_Head',          0, 0, NULL, NULL, NULL),
('ERL','TLCI-ENGR','TLCI',2,'NAT',           'Cost_Center_Head',          0, 0, NULL, NULL, NULL),
('ERL','',        'TLCI', 3, 'Accounting 1', 'Accounting_Approver_1',     1, 1, NULL, NULL, 'One Approval Only'),
('ERL','',        'TLCI', 3, 'Accounting 2', 'Accounting_Approver_1_Sub', 1, 1, NULL, NULL, 'One Approval Only'),
('ERL','',        'TLCI', 4, 'Accounting 3', 'Accounting_Approver_2',     0, 1, NULL, NULL, NULL),
('ERL','',        'TLCI', 5, 'Controller',   'Accounting_Controller_1',    0, 1, NULL, NULL, NULL),
('ERL','',        'TLCI', 6, 'Cashier',      'Accounting_Cashier',         0, 1, NULL, NULL, NULL);


-- Example: initialize a process for a given document from template
-- (When a new document is created, you can call something similar from PHP)
-- Replace placeholders :doc_type, :doc_number, :department, :company
--
-- INSERT INTO work_flow_process (doc_type, doc_number, sequence, actor_id, action, status)
-- SELECT t.work_flow_id, :doc_number, t.sequence, t.actor_id, t.action,
--        CASE WHEN t.sequence = 1 THEN 'Done' ELSE 'Waiting for Approval' END
-- FROM work_flow_template t
-- WHERE t.work_flow_id = :doc_type AND t.company = :company AND (t.department = :department OR t.department = '' OR t.department IS NULL)
-- ORDER BY t.sequence, t.id;

-- To approve a step (example):
-- UPDATE work_flow_process
-- SET status = 'Approved', decided_by = :user, decided_at = NOW(), remarks = :remarks
-- WHERE doc_type = :doc_type AND doc_number = :doc_number AND sequence = :sequence AND actor_id = :actor;

-- To mark next sequence as active (if you manage an "active" flag, you can extend the schema)


