-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 28, 2024 at 12:00 PM
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
(1, 'RFP', 'Request for Proposal', 'Request for Proposal workflow', 1, '2024-12-28 12:00:00', NULL),
(2, 'ERGR', 'Expense Reimbursement General Request', 'Expense Reimbursement General Request workflow', 1, '2024-12-28 12:00:00', NULL),
(3, 'ERL', 'Expense Reimbursement Liquidation', 'Expense Reimbursement Liquidation workflow', 1, '2024-12-28 12:00:00', NULL),
(4, 'PR', 'Purchase Request', 'Purchase Request workflow', 1, '2024-12-28 12:00:00', NULL),
(5, 'PO', 'Purchase Order', 'Purchase Order workflow', 1, '2024-12-28 12:00:00', NULL),
(6, 'PAW', 'Promotional Activity Workplan', 'Promotional Activity Workplan workflow', 1, '2024-12-28 12:00:00', NULL);

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `work_flow_type`
--
ALTER TABLE `work_flow_type`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
