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
-- Table structure for table `work_flow_process`
--

CREATE TABLE `work_flow_process` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `doc_type` varchar(50) NOT NULL,
  `work_flow_type_id` bigint(20) UNSIGNED NULL,
  `doc_number` varchar(150) NOT NULL,
  `sequence` int(11) NOT NULL,
  `actor_id` varchar(150) NOT NULL,
  `action` varchar(150) NOT NULL,
  `status` varchar(60) NOT NULL DEFAULT 'Waiting for Approval',
  `decided_by` varchar(150) DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  `remarks` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--


-- Triggers `work_flow_process`
--
DELIMITER $$
CREATE TRIGGER `tr_wfp_ins_action` AFTER INSERT ON `work_flow_process` FOR EACH ROW BEGIN
  INSERT INTO work_flow_action_log
    (`process_id`,`doc_type`,`work_flow_type_id`,`doc_number`,`sequence`,`actor_id`,`action`,`prev_status`,`new_status`,`event`,`remarks`,`created_by`,`ip_address`)
  VALUES
    (NEW.id, NEW.doc_type, NEW.work_flow_type_id, NEW.doc_number, NEW.sequence, NEW.actor_id, NEW.action, NULL, NEW.status, 'INSERT', NEW.remarks, NEW.decided_by, NULL);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_wfp_upd_action` AFTER UPDATE ON `work_flow_process` FOR EACH ROW BEGIN
  IF (OLD.status <> NEW.status) OR (OLD.remarks <> NEW.remarks) OR (OLD.decided_by <> NEW.decided_by) OR (OLD.decided_at <> NEW.decided_at) OR (OLD.work_flow_type_id <> NEW.work_flow_type_id) THEN
    INSERT INTO work_flow_action_log
      (`process_id`,`doc_type`,`work_flow_type_id`,`doc_number`,`sequence`,`actor_id`,`action`,`prev_status`,`new_status`,`event`,`remarks`,`created_by`,`ip_address`)
    VALUES
      (NEW.id, NEW.doc_type, NEW.work_flow_type_id, NEW.doc_number, NEW.sequence, NEW.actor_id, NEW.action, OLD.status, NEW.status, 'UPDATE', NEW.remarks, NEW.decided_by, NULL);
  END IF;
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `work_flow_process`
--
ALTER TABLE `work_flow_process`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_doc` (`doc_type`,`doc_number`,`sequence`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_work_flow_type_id` (`work_flow_type_id`);

--
-- Foreign key constraints for table `work_flow_process`
--
ALTER TABLE `work_flow_process` 
ADD CONSTRAINT `fk_work_flow_process_type` 
FOREIGN KEY (`work_flow_type_id`) REFERENCES `work_flow_type`(`id`) 
ON DELETE SET NULL ON UPDATE CASCADE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `work_flow_process`
--
ALTER TABLE `work_flow_process`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=189;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
