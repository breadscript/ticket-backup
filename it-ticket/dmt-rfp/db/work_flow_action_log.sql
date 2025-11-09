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
-- Table structure for table `work_flow_action_log`
--

CREATE TABLE `work_flow_action_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `process_id` bigint(20) UNSIGNED DEFAULT NULL,
  `doc_type` varchar(50) NOT NULL,
  `work_flow_type_id` bigint(20) UNSIGNED NULL,
  `doc_number` varchar(150) NOT NULL,
  `sequence` int(11) NOT NULL,
  `actor_id` varchar(150) NOT NULL,
  `action` varchar(150) NOT NULL,
  `prev_status` varchar(60) DEFAULT NULL,
  `new_status` varchar(60) DEFAULT NULL,
  `event` varchar(60) NOT NULL,
  `remarks` varchar(500) DEFAULT NULL,
  `created_by` varchar(150) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--


--
-- Indexes for table `work_flow_action_log`
--
ALTER TABLE `work_flow_action_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_log_doc` (`doc_type`,`doc_number`,`sequence`),
  ADD KEY `idx_event_time` (`event`,`created_at`),
  ADD KEY `idx_work_flow_type_id` (`work_flow_type_id`);

--
-- Foreign key constraints for table `work_flow_action_log`
--
ALTER TABLE `work_flow_action_log` 
ADD CONSTRAINT `fk_work_flow_action_log_type` 
FOREIGN KEY (`work_flow_type_id`) REFERENCES `work_flow_type`(`id`) 
ON DELETE SET NULL ON UPDATE CASCADE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `work_flow_action_log`
--
ALTER TABLE `work_flow_action_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=604;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
