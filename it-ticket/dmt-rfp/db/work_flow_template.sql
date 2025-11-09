-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 28, 2025 at 07:30 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `techsources`
--

-- --------------------------------------------------------

--
-- Table structure for table `work_flow_type`
--

CREATE TABLE `work_flow_type` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type_code` varchar(10) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `work_flow_type`
--

INSERT INTO `work_flow_type` (`id`, `type_code`, `type_name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'RFP', 'Request for Proposal', 'Request for Proposal workflow', 1, '2025-08-28 07:30:00', NULL),
(2, 'ERGR', 'Expense Reimbursement General Request', 'Expense Reimbursement General Request workflow', 1, '2025-08-28 07:30:00', NULL),
(3, 'ERL', 'Expense Reimbursement Liquidation', 'Expense Reimbursement Liquidation workflow', 1, '2025-08-28 07:30:00', NULL),
(4, 'PR', 'Purchase Request', 'Purchase Request workflow', 1, '2025-08-28 07:30:00', NULL),
(5, 'PO', 'Purchase Order', 'Purchase Order workflow', 1, '2025-08-28 07:30:00', NULL),
(6, 'PAW', 'Promotional Activity Workplan', 'Promotional Activity Workplan workflow', 1, '2025-08-28 07:30:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `work_flow_template`
--

CREATE TABLE `work_flow_template` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `work_flow_id` varchar(50) NOT NULL,
  `work_flow_type_id` bigint(20) UNSIGNED NULL,
  `department` varchar(150) DEFAULT NULL,
  `company` varchar(150) NOT NULL,
  `sequence` int(11) NOT NULL,
  `actor_id` varchar(150) NOT NULL,
  `action` varchar(150) NOT NULL,
  `is_parellel` tinyint(1) NOT NULL DEFAULT 0,
  `global` tinyint(1) NOT NULL DEFAULT 0,
  `amount_from` decimal(14,2) DEFAULT NULL,
  `amount_to` decimal(14,2) DEFAULT NULL,
  `Note` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `work_flow_template`
--

INSERT INTO `work_flow_template` (`id`, `work_flow_id`, `work_flow_type_id`, `department`, `company`, `sequence`, `actor_id`, `action`, `is_parellel`, `global`, `amount_from`, `amount_to`, `Note`, `created_at`, `updated_at`) VALUES
-- RFP Workflow (work_flow_type_id = 1)
(1, 'RFP', 1, '', 'TLCI', 1, 'Requestor', 'Requestor', 0, 1, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
-- Level 2: Cost Center Heads
(2, 'RFP', 1, 'TLCI-FPA', 'TLCI', 2, 'rnazarea', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(3, 'RFP', 1, 'TLCI-ACC', 'TLCI', 2, 'vmestidio', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(4, 'RFP', 1, 'TLCI-TAX', 'TLCI', 2, 'vmestidio', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(5, 'RFP', 1, 'TLCI-TRE', 'TLCI', 2, 'acbarit', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(6, 'RFP', 1, 'TLCI-HR', 'TLCI', 2, 'JOROXAS', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(7, 'RFP', 1, 'TLCI-GSD', 'TLCI', 2, 'JOROXAS', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(8, 'RFP', 1, 'TLCI-LOG', 'TLCI', 2, 'agred', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(9, 'RFP', 1, 'TLCI-RND', 'TLCI', 2, 'FVLACERNA@CARMENSBEST.COM.PH', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(10, 'RFP', 1, 'TLCI-PROC', 'TLCI', 2, 'rhdula123', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(11, 'RFP', 1, 'TLCI-SALES', 'TLCI', 2, 'TOGATCHALIAN', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(12, 'RFP', 1, 'TLCI-MKT', 'TLCI', 2, 'missybanaria', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(13, 'RFP', 1, 'TLCI-IT', 'TLCI', 2, 'OZREDONDO', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(14, 'RFP', 1, 'TLCI-FRAN', 'TLCI', 2, 'jpdedios', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(15, 'RFP', 1, 'TLCI-ICE', 'TLCI', 2, 'EABIAS', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(16, 'RFP', 1, 'TLCI-FARM', 'TLCI', 2, 'MMBORJA', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(17, 'RFP', 1, 'TLCI-DAIRY-LTI', 'TLCI', 2, 'EABIAS', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(18, 'RFP', 1, 'TLCI-DAIRY-BAY', 'TLCI', 2, 'EABIAS', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(19, 'RFP', 1, 'TLCI-RETAIL-MOA', 'TLCI', 2, 'jpdedios', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(20, 'RFP', 1, 'TLCI-RETAIL-SMN', 'TLCI', 2, 'jpdedios', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(21, 'RFP', 1, 'TLCI-RETAIL-SMM', 'TLCI', 2, 'jpdedios', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(22, 'RFP', 1, 'TLCI-RETAIL-SHANG', 'TLCI', 2, 'jpdedios', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(23, 'RFP', 1, 'TLCI-ECOMM', 'TLCI', 2, 'jpdedios', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
-- Level 3: Accounting 1 (Parallel)
(24, 'RFP', 1, '', 'TLCI', 3, 'JDREBONG', 'Accounting_1', 1, 1, NULL, NULL, 'One Approval Only', '2025-08-28 07:30:00', NULL),
(25, 'RFP', 1, '', 'TLCI', 3, 'crystaljesca', 'Accounting_1', 1, 1, NULL, NULL, 'One Approval Only', '2025-08-28 07:30:00', NULL),
(26, 'RFP', 1, '', 'TLCI', 3, 'FJALCAZAR', 'Accounting_1', 1, 1, NULL, NULL, 'One Approval Only', '2025-08-28 07:30:00', NULL),
-- Level 4: Accounting 2
(27, 'RFP', 1, '', 'TLCI', 4, 'MTLACO', 'Accounting_2', 0, 1, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
-- Level 5: Accounting Manager
(28, 'RFP', 1, '', 'TLCI', 5, 'vmestidio', 'Accounting_Manager', 0, 1, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
-- Level 6: Cashier - Assistant (not parallel)
(29, 'RFP', 1, '', 'TLCI', 6, 'MGPILARES', 'Cashier_Assistant', 0, 1, NULL, NULL, 'One Approval Only', '2025-08-28 07:30:00', NULL),
-- Level 7: Cashier - Specialist (just view only)
-- Levels 7-9 removed: Cashier Specialist/Supervisor/Manager are view-only and not part of approval flow

-- ERGR Workflow (work_flow_type_id = 2)
(36, 'ERGR', 2, '', 'TLCI', 1, 'Requestor', 'Requestor', 0, 1, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
-- Level 2: Cost Center Heads
(37, 'ERGR', 2, 'TLCI-FPA', 'TLCI', 2, 'rnazarea', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(38, 'ERGR', 2, 'TLCI-ACC', 'TLCI', 2, 'vmestidio', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(39, 'ERGR', 2, 'TLCI-TAX', 'TLCI', 2, 'vmestidio', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(40, 'ERGR', 2, 'TLCI-TRE', 'TLCI', 2, 'acbarit', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(41, 'ERGR', 2, 'TLCI-HR', 'TLCI', 2, 'JOROXAS', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(42, 'ERGR', 2, 'TLCI-GSD', 'TLCI', 2, 'JOROXAS', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(43, 'ERGR', 2, 'TLCI-LOG', 'TLCI', 2, 'agred', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(44, 'ERGR', 2, 'TLCI-RND', 'TLCI', 2, 'FVLACERNA@CARMENSBEST.COM.PH', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(45, 'ERGR', 2, 'TLCI-PROC', 'TLCI', 2, 'rhdula123', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(46, 'ERGR', 2, 'TLCI-SALES', 'TLCI', 2, 'TOGATCHALIAN', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(47, 'ERGR', 2, 'TLCI-MKT', 'TLCI', 2, 'missybanaria', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(48, 'ERGR', 2, 'TLCI-IT', 'TLCI', 2, 'OZREDONDO', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(49, 'ERGR', 2, 'TLCI-FRAN', 'TLCI', 2, 'jpdedios', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(50, 'ERGR', 2, 'TLCI-ICE', 'TLCI', 2, 'EABIAS', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(51, 'ERGR', 2, 'TLCI-FARM', 'TLCI', 2, 'MMBORJA', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(52, 'ERGR', 2, 'TLCI-DAIRY-LTI', 'TLCI', 2, 'EABIAS', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(53, 'ERGR', 2, 'TLCI-DAIRY-BAY', 'TLCI', 2, 'EABIAS', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(54, 'ERGR', 2, 'TLCI-RETAIL-MOA', 'TLCI', 2, 'jpdedios', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(55, 'ERGR', 2, 'TLCI-RETAIL-SMN', 'TLCI', 2, 'jpdedios', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(56, 'ERGR', 2, 'TLCI-RETAIL-SMM', 'TLCI', 2, 'jpdedios', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(57, 'ERGR', 2, 'TLCI-RETAIL-SHANG', 'TLCI', 2, 'jpdedios', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(58, 'ERGR', 2, 'TLCI-ECOMM', 'TLCI', 2, 'jpdedios', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
-- Level 3: Accounting 1 (Parallel)
(59, 'ERGR', 2, '', 'TLCI', 3, 'JDREBONG', 'Accounting_1', 1, 1, NULL, NULL, 'One Approval Only', '2025-08-28 07:30:00', NULL),
(60, 'ERGR', 2, '', 'TLCI', 3, 'crystaljesca', 'Accounting_1', 1, 1, NULL, NULL, 'One Approval Only', '2025-08-28 07:30:00', NULL),
(61, 'ERGR', 2, '', 'TLCI', 3, 'FJALCAZAR', 'Accounting_1', 1, 1, NULL, NULL, 'One Approval Only', '2025-08-28 07:30:00', NULL),
-- Level 4: Accounting 2
(62, 'ERGR', 2, '', 'TLCI', 4, 'MTLACO', 'Accounting_2', 0, 1, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
-- Level 5: Accounting Manager
(63, 'ERGR', 2, '', 'TLCI', 5, 'vmestidio', 'Accounting_Manager', 0, 1, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
-- Level 6: Cashier - Assistant (Parallel)
(64, 'ERGR', 2, '', 'TLCI', 6, 'MGPILARES', 'Cashier_Assistant', 0, 1, NULL, NULL, 'One Approval Only', '2025-08-28 07:30:00', NULL),
-- Level 7: Cashier - Specialist
-- Levels 7-9 removed: Cashier Specialist/Supervisor/Manager are view-only and not part of approval flow

-- ERL Workflow (work_flow_type_id = 3)
(71, 'ERL', 3, '', 'TLCI', 1, 'Requestor', 'Requestor', 0, 1, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
-- Level 2: Cost Center Heads
(72, 'ERL', 3, 'TLCI-FPA', 'TLCI', 2, 'rnazarea', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(73, 'ERL', 3, 'TLCI-ACC', 'TLCI', 2, 'vmestidio', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(74, 'ERL', 3, 'TLCI-TAX', 'TLCI', 2, 'vmestidio', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(75, 'ERL', 3, 'TLCI-TRE', 'TLCI', 2, 'acbarit', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(76, 'ERL', 3, 'TLCI-HR', 'TLCI', 2, 'JOROXAS', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(77, 'ERL', 3, 'TLCI-GSD', 'TLCI', 2, 'JOROXAS', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(78, 'ERL', 3, 'TLCI-LOG', 'TLCI', 2, 'agred', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(79, 'ERL', 3, 'TLCI-RND', 'TLCI', 2, 'FVLACERNA@CARMENSBEST.COM.PH', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(80, 'ERL', 3, 'TLCI-PROC', 'TLCI', 2, 'rhdula123', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(81, 'ERL', 3, 'TLCI-SALES', 'TLCI', 2, 'TOGATCHALIAN', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(82, 'ERL', 3, 'TLCI-MKT', 'TLCI', 2, 'missybanaria', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(83, 'ERL', 3, 'TLCI-IT', 'TLCI', 2, 'OZREDONDO', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(84, 'ERL', 3, 'TLCI-FRAN', 'TLCI', 2, 'jpdedios', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(85, 'ERL', 3, 'TLCI-ICE', 'TLCI', 2, 'EABIAS', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(86, 'ERL', 3, 'TLCI-FARM', 'TLCI', 2, 'MMBORJA', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(87, 'ERL', 3, 'TLCI-DAIRY-LTI', 'TLCI', 2, 'EABIAS', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(88, 'ERL', 3, 'TLCI-DAIRY-BAY', 'TLCI', 2, 'EABIAS', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(89, 'ERL', 3, 'TLCI-RETAIL-MOA', 'TLCI', 2, 'jpdedios', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(90, 'ERL', 3, 'TLCI-RETAIL-SMN', 'TLCI', 2, 'jpdedios', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(91, 'ERL', 3, 'TLCI-RETAIL-SMM', 'TLCI', 2, 'jpdedios', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(92, 'ERL', 3, 'TLCI-RETAIL-SHANG', 'TLCI', 2, 'jpdedios', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(93, 'ERL', 3, 'TLCI-ECOMM', 'TLCI', 2, 'jpdedios', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
-- Level 3: Accounting 1 (Parallel)
(94, 'ERL', 3, '', 'TLCI', 3, 'JDREBONG', 'Accounting_1', 1, 1, NULL, NULL, 'One Approval Only', '2025-08-28 07:30:00', NULL),
(95, 'ERL', 3, '', 'TLCI', 3, 'crystaljesca', 'Accounting_1', 1, 1, NULL, NULL, 'One Approval Only', '2025-08-28 07:30:00', NULL),
(96, 'ERL', 3, '', 'TLCI', 3, 'FJALCAZAR', 'Accounting_1', 1, 1, NULL, NULL, 'One Approval Only', '2025-08-28 07:30:00', NULL),
-- Level 4: Accounting 2
(97, 'ERL', 3, '', 'TLCI', 4, 'MTLACO', 'Accounting_2', 0, 1, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
-- Level 5: Accounting Manager
(98, 'ERL', 3, '', 'TLCI', 5, 'vmestidio', 'Accounting_Manager', 0, 1, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
-- Level 6: Cashier - Assistant (Parallel)
(99, 'ERL', 3, '', 'TLCI', 6, 'MGPILARES', 'Cashier_Assistant', 0, 1, NULL, NULL, 'One Approval Only', '2025-08-28 07:30:00', NULL),

-- MPAV WORKFLOWS START HERE
-- RFP Workflow for MPAV (work_flow_type_id = 1)
(106, 'RFP', 1, '', 'MPAV', 1, 'Requestor', 'Requestor', 0, 1, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
-- Level 2: Cost Center Heads for MPAV
(107, 'RFP', 1, 'MPAV-FIN', 'MPAV', 2, 'AFLORES', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(108, 'RFP', 1, 'MPAV-ACC', 'MPAV', 2, 'CDOMINGUEZ', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
  (109, 'RFP', 1, 'MPAV-LOG', 'MPAV', 2, 'JCASTILLO', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(110, 'RFP', 1, 'MPAV-PR', 'MPAV', 2, 'DCRUZ', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(111, 'RFP', 1, 'MPAV-SALES', 'MPAV', 2, 'PGONZALES', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(112, 'RFP', 1, 'MPAV-ENGR', 'MPAV', 2, 'STORRES', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(113, 'RFP', 1, 'MPAV-IT', 'MPAV', 2, 'RVILLANUEVA', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
-- Level 3: Accounting 1 (Parallel) for MPAV
(114, 'RFP', 1, '', 'MPAV', 3, 'ACCOUNTONE', 'Accounting_1', 1, 1, NULL, NULL, 'One Approval Only', '2025-08-28 07:30:00', NULL),
(115, 'RFP', 1, '', 'MPAV', 3, 'ACCOUNTTWO', 'Accounting_1', 1, 1, NULL, NULL, 'One Approval Only', '2025-08-28 07:30:00', NULL),
(116, 'RFP', 1, '', 'MPAV', 3, 'ACCOUNTTHREE', 'Accounting_1', 1, 1, NULL, NULL, 'One Approval Only', '2025-08-28 07:30:00', NULL),
-- Level 4: Accounting 2 for MPAV
(117, 'RFP', 1, '', 'MPAV', 4, 'CONTROLLER', 'Accounting_2', 0, 1, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
-- Level 5: Accounting Manager for MPAV (using Controller as Accounting Manager)
(118, 'RFP', 1, '', 'MPAV', 5, 'CONTROLLER', 'Accounting_Manager', 0, 1, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
-- Level 6: Cashier - Assistant for MPAV
(119, 'RFP', 1, '', 'MPAV', 6, 'CASHIER', 'Cashier_Assistant', 0, 1, NULL, NULL, 'One Approval Only', '2025-08-28 07:30:00', NULL),

-- ERGR Workflow for MPAV (work_flow_type_id = 2)
(120, 'ERGR', 2, '', 'MPAV', 1, 'Requestor', 'Requestor', 0, 1, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
-- Level 2: Cost Center Heads for MPAV
(121, 'ERGR', 2, 'MPAV-FIN', 'MPAV', 2, 'AFLORES', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(122, 'ERGR', 2, 'MPAV-ACC', 'MPAV', 2, 'CDOMINGUEZ', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(123, 'ERGR', 2, 'MPAV-LOG', 'MPAV', 2, 'JCASTILLO', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(124, 'ERGR', 2, 'MPAV-PR', 'MPAV', 2, 'DCRUZ', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(125, 'ERGR', 2, 'MPAV-SALES', 'MPAV', 2, 'PGONZALES', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(126, 'ERGR', 2, 'MPAV-ENGR', 'MPAV', 2, 'STORRES', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(127, 'ERGR', 2, 'MPAV-IT', 'MPAV', 2, 'RVILLANUEVA', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
-- Level 3: Accounting 1 (Parallel) for MPAV
(128, 'ERGR', 2, '', 'MPAV', 3, 'ACCOUNTONE', 'Accounting_1', 1, 1, NULL, NULL, 'One Approval Only', '2025-08-28 07:30:00', NULL),
(129, 'ERGR', 2, '', 'MPAV', 3, 'ACCOUNTTWO', 'Accounting_1', 1, 1, NULL, NULL, 'One Approval Only', '2025-08-28 07:30:00', NULL),
(130, 'ERGR', 2, '', 'MPAV', 3, 'ACCOUNTTHREE', 'Accounting_1', 1, 1, NULL, NULL, 'One Approval Only', '2025-08-28 07:30:00', NULL),
-- Level 4: Accounting 2 for MPAV
(131, 'ERGR', 2, '', 'MPAV', 4, 'CONTROLLER', 'Accounting_2', 0, 1, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
-- Level 5: Accounting Manager for MPAV
(132, 'ERGR', 2, '', 'MPAV', 5, 'CONTROLLER', 'Accounting_Manager', 0, 1, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
-- Level 6: Cashier - Assistant for MPAV
(133, 'ERGR', 2, '', 'MPAV', 6, 'CASHIER', 'Cashier_Assistant', 0, 1, NULL, NULL, 'One Approval Only', '2025-08-28 07:30:00', NULL),

-- ERL Workflow for MPAV (work_flow_type_id = 3)
(134, 'ERL', 3, '', 'MPAV', 1, 'Requestor', 'Requestor', 0, 1, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
-- Level 2: Cost Center Heads for MPAV
(135, 'ERL', 3, 'MPAV-FIN', 'MPAV', 2, 'AFLORES', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(136, 'ERL', 3, 'MPAV-ACC', 'MPAV', 2, 'CDOMINGUEZ', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(137, 'ERL', 3, 'MPAV-LOG', 'MPAV', 2, 'JCASTILLO', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(138, 'ERL', 3, 'MPAV-PR', 'MPAV', 2, 'DCRUZ', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(139, 'ERL', 3, 'MPAV-SALES', 'MPAV', 2, 'PGONZALES', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(140, 'ERL', 3, 'MPAV-ENGR', 'MPAV', 2, 'STORRES', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
(141, 'ERL', 3, 'MPAV-IT', 'MPAV', 2, 'RVILLANUEVA', 'Cost_Center_Head', 0, 0, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
-- Level 3: Accounting 1 (Parallel) for MPAV
(142, 'ERL', 3, '', 'MPAV', 3, 'ACCOUNTONE', 'Accounting_1', 1, 1, NULL, NULL, 'One Approval Only', '2025-08-28 07:30:00', NULL),
(143, 'ERL', 3, '', 'MPAV', 3, 'ACCOUNTTWO', 'Accounting_1', 1, 1, NULL, NULL, 'One Approval Only', '2025-08-28 07:30:00', NULL),
(144, 'ERL', 3, '', 'MPAV', 3, 'ACCOUNTTHREE', 'Accounting_1', 1, 1, NULL, NULL, 'One Approval Only', '2025-08-28 07:30:00', NULL),
-- Level 4: Accounting 2 for MPAV
(145, 'ERL', 3, '', 'MPAV', 4, 'CONTROLLER', 'Accounting_2', 0, 1, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
-- Level 5: Accounting Manager for MPAV
(146, 'ERL', 3, '', 'MPAV', 5, 'CONTROLLER', 'Accounting_Manager', 0, 1, NULL, NULL, NULL, '2025-08-28 07:30:00', NULL),
-- Level 6: Cashier - Assistant for MPAV
(147, 'ERL', 3, '', 'MPAV', 6, 'CASHIER', 'Cashier_Assistant', 0, 1, NULL, NULL, 'One Approval Only', '2025-08-28 07:30:00', NULL);



--
-- Indexes for dumped tables
--

--
-- Indexes for table `work_flow_type`
--
ALTER TABLE `work_flow_type`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_type_code` (`type_code`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `work_flow_template`
--
ALTER TABLE `work_flow_template`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lookup` (`work_flow_id`,`company`,`department`,`sequence`),
  ADD KEY `idx_amount_range` (`amount_from`,`amount_to`),
  ADD KEY `idx_work_flow_type_id` (`work_flow_type_id`);

--
-- Foreign key constraints for table `work_flow_template`
--
ALTER TABLE `work_flow_template` 
ADD CONSTRAINT `fk_work_flow_template_type` 
FOREIGN KEY (`work_flow_type_id`) REFERENCES `work_flow_type`(`id`) 
ON DELETE SET NULL ON UPDATE CASCADE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `work_flow_type`
--
ALTER TABLE `work_flow_type`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `work_flow_template`
--
ALTER TABLE `work_flow_template`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
