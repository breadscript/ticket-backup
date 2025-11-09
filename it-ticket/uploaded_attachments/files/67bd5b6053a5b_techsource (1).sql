-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 25, 2025 at 02:52 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `techsource`
--

-- --------------------------------------------------------

--
-- Table structure for table `pm_projecttasktb`
--

CREATE TABLE `pm_projecttasktb` (
  `id` int(11) NOT NULL,
  `subject` text NOT NULL,
  `description` text NOT NULL,
  `deadline` date NOT NULL,
  `startdate` date NOT NULL,
  `enddate` date NOT NULL,
  `classificationid` int(11) NOT NULL,
  `statusid` int(11) NOT NULL DEFAULT 1,
  `priorityid` int(11) NOT NULL,
  `createdbyid` int(11) NOT NULL,
  `datetimecreated` datetime NOT NULL DEFAULT current_timestamp(),
  `updatedbyid` int(11) DEFAULT NULL,
  `datetimeupdated` datetime DEFAULT NULL,
  `assignee` text DEFAULT NULL,
  `projectid` int(11) NOT NULL,
  `percentdone` float NOT NULL DEFAULT 0,
  `istask` int(11) NOT NULL,
  `isupdatedbyclient` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `pm_projecttasktb`
--

INSERT INTO `pm_projecttasktb` (`id`, `subject`, `description`, `deadline`, `startdate`, `enddate`, `classificationid`, `statusid`, `priorityid`, `createdbyid`, `datetimecreated`, `updatedbyid`, `datetimeupdated`, `assignee`, `projectid`, `percentdone`, `istask`, `isupdatedbyclient`) VALUES
(1, 'Support Ticket: #1', '<p>Support Ticket: #12</p>', '2025-01-30', '2025-01-31', '1900-01-01', 4, 1, 4, 7, '2025-01-31 15:32:38', NULL, NULL, '1,8', 1, 0, 1, 0),
(2, 'Support #2', '<p>Support #2</p>', '2025-01-31', '2025-01-31', '1900-01-01', 1, 1, 1, 1, '2025-01-31 15:39:40', NULL, NULL, '7', 1, 0, 1, 0),
(3, 'Inquiry #1', '<p>Inquiry #1123123123</p>', '2025-01-31', '2025-01-31', '1900-01-01', 2, 1, 4, 8, '2025-01-31 15:45:43', NULL, NULL, '7', 8, 0, 2, 0),
(4, 'Inquiry #2', '<p>Inquiry #2</p>', '2025-01-31', '2025-01-31', '1900-01-01', 4, 1, 1, 8, '2025-01-31 15:59:12', NULL, NULL, '4,5', 8, 0, 2, 0),
(5, 'TEST1', '<p>TEST</p>', '2025-02-22', '2025-02-12', '2025-02-13', 5, 6, 3, 1, '2025-02-12 16:30:22', NULL, NULL, '7', 1, 3, 1, 0),
(6, 'TEST2', '<p>TEST2</p>', '2025-02-22', '2025-02-13', '1900-01-01', 2, 1, 1, 1, '2025-02-13 09:24:18', NULL, NULL, '7', 2, 0, 1, 0),
(7, 'tet 1', '<p>test</p>', '2025-02-20', '2025-02-21', '1900-01-01', 2, 1, 2, 8, '2025-02-14 11:47:34', NULL, NULL, '2', 2, 0, 2, 0),
(8, 'testken', '<p>qweqweqwe</p>', '2025-02-18', '2025-02-18', '1900-01-01', 2, 1, 3, 1, '2025-02-18 15:01:02', NULL, NULL, '3', 3, 0, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `pm_taskassigneetb`
--

CREATE TABLE `pm_taskassigneetb` (
  `id` int(11) NOT NULL,
  `taskid` int(11) NOT NULL,
  `assigneeid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `pm_taskassigneetb`
--

INSERT INTO `pm_taskassigneetb` (`id`, `taskid`, `assigneeid`) VALUES
(2, 2, 7),
(12, 6, 7),
(13, 5, 7),
(14, 4, 4),
(15, 4, 5),
(27, 1, 1),
(28, 1, 8),
(31, 7, 2),
(35, 8, 3),
(36, 3, 7);

-- --------------------------------------------------------

--
-- Table structure for table `pm_threadtb`
--

CREATE TABLE `pm_threadtb` (
  `id` int(11) NOT NULL,
  `taskid` int(11) NOT NULL,
  `createdbyid` int(11) NOT NULL,
  `datetimecreated` datetime NOT NULL DEFAULT current_timestamp(),
  `subject` text NOT NULL,
  `message` text NOT NULL,
  `type` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `file_data` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `pm_threadtb`
--

INSERT INTO `pm_threadtb` (`id`, `taskid`, `createdbyid`, `datetimecreated`, `subject`, `message`, `type`, `parent_id`, `file_data`) VALUES
(1, 8, 1, '2017-04-23 14:30:22', 'FPS ISSUE', 'THE FPS IS NOT COMPATIBLE WITH THE JAVASCRIPT INSTALLED', 'comment', NULL, NULL),
(2, 8, 1, '2017-04-23 14:31:03', 'VIRUS DETECTION', 'VIRUS DELETED THE EXE FILE THAT ALLOW THE FPS TO RUN', 'comment', NULL, NULL),
(3, 10, 1, '2025-01-29 02:19:04', 'REPLY 1', 'REPLY 1', 'comment', NULL, NULL),
(4, 10, 1, '2025-01-29 02:19:37', 'REPLY 2', 'REPLY 2', 'comment', NULL, NULL),
(5, 10, 1, '2025-01-29 02:24:17', 'RESOLUTION', 'RESOLUTION', 'comment', NULL, NULL),
(6, 10, 7, '2025-01-29 02:30:17', 'RESO 1', 'RESO 1', 'comment', NULL, NULL),
(7, 11, 7, '2025-01-29 02:32:22', 'INITIAL FINDINGS', 'THE ISSUE IS ABOUT YOUR BUTTONS.', 'comment', NULL, NULL),
(8, 1, 1, '2025-01-29 13:41:26', 'FINDINGS 1', 'FINDINGS 1', 'comment', NULL, NULL),
(9, 1, 7, '2025-01-31 15:12:25', 'FINDINGS 2', 'FINDINGS 2', 'comment', NULL, NULL),
(10, 1, 1, '2025-02-18 04:11:36', '', 'test', 'comment', NULL, NULL),
(11, 1, 1, '2025-02-18 04:14:22', '', 'test2', 'comment', NULL, NULL),
(12, 1, 1, '2025-02-18 04:20:02', '', 'test3', 'comment', NULL, NULL),
(13, 1, 1, '2025-02-18 04:23:32', '123', '123', 'comment', NULL, NULL),
(14, 1, 1, '2025-02-18 04:27:02', '', 'test4', 'comment', NULL, NULL),
(15, 1, 1, '2025-02-18 04:29:43', 'Support Ticket: #1', 'test5', 'comment', NULL, NULL),
(16, 1, 1, '2025-02-18 08:02:44', 'testken', '55555555555555555555555555555555555555', 'comment', NULL, NULL),
(17, 8, 1, '2025-02-18 08:08:17', 'testken', '123123', 'comment', NULL, NULL),
(18, 8, 1, '2025-02-18 08:08:44', 'testken', 'ndifvnsidbfihsdnfjb', 'comment', NULL, NULL),
(19, 8, 1, '2025-02-20 02:29:22', '', '12312312', 'comment', NULL, NULL),
(20, 8, 1, '2025-02-20 02:29:56', 'testken', '456456456', 'comment', NULL, NULL),
(21, 8, 1, '2025-02-20 02:30:37', 'testken', '123123123', 'comment', NULL, NULL),
(22, 8, 1, '2025-02-20 02:30:49', 'testken', '66666666666666666666', 'comment', NULL, NULL),
(23, 8, 1, '2025-02-20 02:54:51', 'testken', '7777777777777', 'comment', NULL, NULL),
(24, 3, 1, '2025-02-20 08:24:58', 'Inquiry #1', 'test1', 'comment', NULL, NULL),
(25, 3, 1, '2025-02-20 08:37:23', 'Inquiry #1', 'test2', 'comment', NULL, NULL),
(26, 3, 1, '2025-02-20 08:38:29', 'Inquiry #1', 'test3', 'comment', NULL, NULL),
(27, 3, 1, '2025-02-20 08:45:17', 'Inquiry #1', 'test4', 'comment', NULL, NULL),
(28, 3, 7, '2025-02-20 08:48:09', 'Inquiry #1', 'test5', 'comment', NULL, NULL),
(29, 3, 7, '2025-02-20 08:48:19', 'Inquiry #1', 'test6', 'comment', NULL, NULL),
(30, 3, 7, '2025-02-20 08:48:27', 'Inquiry #1', 'test7', 'comment', NULL, NULL),
(31, 3, 7, '2025-02-20 08:57:39', 'Inquiry #1', 'test8', 'comment', NULL, NULL),
(32, 3, 7, '2025-02-20 09:08:51', 'Inquiry #1', 'test9', 'comment', NULL, NULL),
(33, 3, 7, '2025-02-20 09:12:19', 'Inquiry #1', 'test10', 'comment', NULL, NULL),
(34, 3, 7, '2025-02-20 09:25:46', 'Inquiry #1', 'test11', 'comment', NULL, NULL),
(35, 3, 7, '2025-02-20 09:31:53', 'Inquiry #1', 'test12', 'comment', NULL, NULL),
(36, 3, 7, '2025-02-20 09:32:18', 'Inquiry #1', 'test13', 'comment', NULL, NULL),
(37, 3, 7, '2025-02-21 01:06:53', 'Inquiry #1', 'test14', 'comment', NULL, NULL),
(38, 3, 7, '2025-02-21 01:13:56', 'Inquiry #1', 'test15', 'comment', NULL, NULL),
(39, 3, 7, '2025-02-21 01:19:46', 'Inquiry #1', 'test16', 'comment', NULL, NULL),
(40, 3, 7, '2025-02-21 01:22:18', 'Inquiry #1', 'test17', 'comment', NULL, NULL),
(41, 3, 1, '2025-02-21 01:28:19', 'Inquiry #1', '@undefined test reply', 'comment', NULL, NULL),
(42, 3, 1, '2025-02-21 01:35:58', 'Inquiry #1', '@undefined: @undefined test reply\\r\\n', 'comment', NULL, NULL),
(43, 3, 1, '2025-02-21 01:39:49', 'Inquiry #1', '@123 ', 'comment', NULL, NULL),
(44, 3, 1, '2025-02-21 01:42:52', 'Inquiry #1', '@AGENT1 testreply3', 'comment', NULL, NULL),
(45, 3, 7, '2025-02-21 01:45:26', 'Inquiry #1', 'test111', 'comment', NULL, NULL),
(46, 3, 1, '2025-02-21 01:51:21', 'Inquiry #1', '@AGENT1 testestset', 'comment', NULL, NULL),
(47, 3, 7, '2025-02-21 06:35:51', 'Inquiry #1', 'test18', 'comment', NULL, NULL),
(48, 3, 1, '2025-02-21 06:41:34', '', 'tests', 'comment', NULL, NULL),
(49, 3, 1, '2025-02-21 06:45:53', 'Inquiry #1', 'test19', 'comment', NULL, NULL),
(50, 3, 1, '2025-02-21 06:49:49', '', '2222', 'comment', NULL, NULL),
(51, 3, 1, '2025-02-21 06:56:41', 'Inquiry #1', 'test2', 'comment', NULL, NULL),
(52, 3, 7, '2025-02-21 06:59:28', 'Inquiry #1', 'test3', 'comment', NULL, NULL),
(53, 3, 1, '2025-02-21 07:08:57', 'Inquiry #1', 'qweqweqwe', NULL, NULL, NULL),
(54, 3, 1, '2025-02-21 07:09:19', 'Inquiry #1', '123123123', 'comment', NULL, NULL),
(55, 3, 1, '2025-02-21 07:16:35', '', 'testset', 'reply', 54, NULL),
(56, 3, 1, '2025-02-21 07:20:25', 'Inquiry #1', 'test', 'comment', NULL, NULL),
(57, 3, 1, '2025-02-21 07:20:39', 'Reply', 'testsetse', 'reply', 56, NULL),
(58, 3, 1, '2025-02-21 07:24:40', 'Reply', '123', 'reply', 24, NULL),
(59, 3, 1, '2025-02-21 07:26:37', 'Reply', '123123', 'reply', 24, NULL),
(60, 3, 1, '2025-02-21 07:27:16', 'Reply', '345345', 'reply', 24, NULL),
(61, 3, 1, '2025-02-21 07:27:26', 'Inquiry #1', 'werwer', 'comment', NULL, NULL),
(62, 3, 1, '2025-02-21 07:27:31', 'Reply', 'sdfsdfsd', 'reply', 61, NULL),
(63, 3, 1, '2025-02-21 07:40:07', 'Inquiry #1', 'test2', 'comment', 0, NULL),
(64, 3, 1, '2025-02-21 07:40:20', 'Inquiry #1', 'test3', 'comment', 0, NULL),
(65, 3, 1, '2025-02-21 07:41:25', 'Inquiry #1', 'test', 'comment', 0, NULL),
(66, 3, 1, '2025-02-21 07:41:51', 'Inquiry #1', 'testsetset', 'comment', 0, NULL),
(67, 3, 1, '2025-02-21 07:42:28', 'Inquiry #1', 'testset', 'comment', 0, NULL),
(68, 3, 1, '2025-02-21 07:43:33', 'Inquiry #1', 'test3333', 'comment', 0, NULL),
(69, 3, 1, '2025-02-21 07:44:26', 'Inquiry #1', 'test1111', 'comment', 0, NULL),
(70, 3, 1, '2025-02-21 07:45:38', 'Inquiry #1', 'testse', 'comment', 0, NULL),
(71, 3, 7, '2025-02-21 07:45:50', 'Inquiry #1', 'testsetset', 'comment', 0, NULL),
(72, 3, 1, '2025-02-21 07:45:54', 'Inquiry #1', '12312312', 'comment', 0, NULL),
(73, 3, 1, '2025-02-21 07:47:48', 'Inquiry #1', 'testests', 'comment', 0, NULL),
(74, 3, 1, '2025-02-21 07:48:07', 'Reply', 'asdfasdf', 'reply', 24, NULL),
(75, 3, 1, '2025-02-21 07:49:46', 'Inquiry #1', '123123', 'comment', 0, NULL),
(76, 3, 7, '2025-02-21 07:50:02', 'Inquiry #1', 'sdfsdf', 'comment', 0, NULL),
(77, 3, 1, '2025-02-21 07:50:15', 'Inquiry #1', 'sdfsdf', 'comment', 0, NULL),
(78, 3, 1, '2025-02-21 07:54:21', 'Inquiry #1', '123123', 'comment', 0, NULL),
(79, 3, 1, '2025-02-21 08:04:05', 'Inquiry #1', 'test', 'comment', 0, NULL),
(80, 3, 1, '2025-02-21 08:04:21', 'Inquiry #1', 'asdf', 'comment', 0, NULL),
(81, 3, 1, '2025-02-21 08:08:46', 'No Subject', 'qweqwe', 'reply', 61, NULL),
(82, 3, 1, '2025-02-21 08:08:52', 'Inquiry #1', 'asdfas', 'comment', 0, NULL),
(83, 3, 1, '2025-02-21 08:14:54', 'Inquiry #1', 'qweqwe', 'comment', 0, NULL),
(84, 3, 1, '2025-02-21 08:15:30', 'Inquiry #1', 'sdfsdf', NULL, NULL, NULL),
(85, 3, 7, '2025-02-21 08:15:38', 'Inquiry #1', '123123123', NULL, NULL, NULL),
(86, 3, 7, '2025-02-21 08:15:42', 'Inquiry #1', 'sdfg', NULL, NULL, NULL),
(87, 3, 1, '2025-02-21 08:15:45', 'Inquiry #1', '123123', NULL, NULL, NULL),
(88, 3, 1, '2025-02-21 08:19:20', 'Inquiry #1', '12312312', 'comment', NULL, NULL),
(89, 3, 1, '2025-02-21 08:57:18', 'Inquiry #1', 'test', 'comment', NULL, NULL),
(90, 3, 1, '2025-02-21 09:06:27', 'Inquiry #1', 'werwer', NULL, NULL, NULL),
(91, 3, 1, '2025-02-21 09:06:37', 'Inquiry #1', 'asdasd', 'comment', NULL, NULL),
(92, 3, 1, '2025-02-21 09:11:24', 'Inquiry #1', '456456', 'comment', NULL, NULL),
(93, 3, 1, '2025-02-21 09:11:32', 'Inquiry #1', '@123 fasdfasdf', 'comment', 24, NULL),
(94, 3, 1, '2025-02-21 09:13:51', 'Inquiry #1', '@123 werwer', 'comment', 92, NULL),
(95, 3, 1, '2025-02-21 09:13:54', 'Inquiry #1', 'asdasd', 'comment', NULL, NULL),
(96, 3, 1, '2025-02-21 09:14:37', 'Inquiry #1', '@123 asdasd', 'comment', 94, NULL),
(97, 3, 1, '2025-02-21 09:27:14', 'Inquiry #1', '@123 12312312', 'comment', 94, NULL),
(98, 3, 7, '2025-02-21 09:29:49', 'Inquiry #1', '@123 qweqwe', 'comment', 96, NULL),
(99, 3, 1, '2025-02-21 09:43:00', 'Inquiry #1', '123123123', 'comment', NULL, NULL),
(100, 3, 1, '2025-02-21 09:43:17', 'Inquiry #1', '@123 reply123123123', 'reply', 99, NULL),
(101, 1, 1, '2025-02-24 01:13:30', 'Support Ticket: #1', '@123 test reply\\r\\n', 'reply', 16, NULL),
(102, 1, 1, '2025-02-24 01:13:36', 'Support Ticket: #1', '@123 test', 'reply', 101, NULL),
(103, 1, 1, '2025-02-24 01:13:45', 'Support Ticket: #1', '@123 123123', 'reply', 16, NULL),
(104, 1, 1, '2025-02-24 01:47:42', 'Support Ticket: #1', 'test 1', 'comment', NULL, NULL),
(105, 3, 7, '2025-02-24 01:48:53', 'Inquiry #1', '321', 'comment', NULL, NULL),
(106, 3, 1, '2025-02-24 01:49:07', 'Inquiry #1', '@AGENT1 HELLO', 'reply', 105, NULL),
(107, 1, 1, '2025-02-24 02:54:23', 'Support Ticket: #1', '@123 test hehe\\r\\n', 'reply', 16, NULL),
(108, 1, 1, '2025-02-24 02:54:33', 'Support Ticket: #1', '@123 ', 'reply', 16, NULL),
(109, 1, 1, '2025-02-24 02:54:41', 'Support Ticket: #1', '@123 werwer', 'reply', 107, NULL),
(110, 3, 7, '2025-02-24 04:22:52', 'Inquiry #1', 'test1', 'comment', NULL, NULL),
(111, 3, 1, '2025-02-24 04:23:10', 'Inquiry #1', '@AGENT1 hello', 'reply', 110, NULL),
(112, 3, 1, '2025-02-24 04:44:26', 'Inquiry #1', '@123 test ', 'reply', 111, NULL),
(113, 3, 1, '2025-02-24 06:17:21', 'Inquiry #1', 'test1111111', 'comment', NULL, NULL),
(114, 3, 1, '2025-02-24 07:47:08', 'Inquiry #1', '123123', 'comment', NULL, NULL),
(115, 3, 1, '2025-02-24 08:17:52', 'Inquiry #1', 'test111111', 'comment', NULL, NULL),
(116, 3, 1, '2025-02-24 08:18:16', 'Inquiry #1', '@123 tes22222', 'reply', 115, NULL),
(117, 3, 7, '2025-02-24 08:19:40', 'Inquiry #1', 'TEST AGENT1', 'comment', NULL, NULL),
(118, 3, 1, '2025-02-24 09:53:39', 'Inquiry #1', 'test', 'comment', NULL, NULL),
(119, 3, 1, '2025-02-25 02:45:41', 'Inquiry #1', 'test', 'comment', NULL, NULL),
(120, 3, 1, '2025-02-25 02:47:37', 'Inquiry #1', 'test', 'comment', NULL, NULL),
(121, 3, 1, '2025-02-25 02:49:45', 'Inquiry #1', 'test', 'comment', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sys_audit`
--

CREATE TABLE `sys_audit` (
  `id` int(11) NOT NULL,
  `module` int(11) NOT NULL,
  `remarks` varchar(250) DEFAULT NULL,
  `userid` int(11) NOT NULL,
  `datetimecreated` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `sys_audit`
--

INSERT INTO `sys_audit` (`id`, `module`, `remarks`, `userid`, `datetimecreated`) VALUES
(1, 2, 'REGISTER NEW CLIENT | REINLAB CORPORATION', 1, '2017-04-23 12:36:15'),
(2, 2, 'REGISTER NEW CLIENT | BRGY LOOC ADMIN OFFICE', 1, '2017-04-23 12:40:03'),
(3, 2, 'REGISTER NEW CLIENT | SOUTHERN GLOBAL SERVICES MULTI PURPOSE COOPERATIVE', 1, '2017-04-23 12:52:24'),
(4, 2, 'REGISTER NEW CLIENT | BAEK GEUM PHILIPPINES CORPORATION', 1, '2017-04-23 12:57:40'),
(5, 5, 'REGISTER NEW USER | TS_BALTAZAR', 1, '2017-04-23 12:59:28'),
(6, 5, 'REGISTER NEW USER | TS_PREDONDO', 1, '2017-04-23 13:07:29'),
(7, 5, 'REGISTER NEW USER | REP-C.AQUINO', 1, '2017-04-23 13:10:48'),
(8, 2, 'UPDATED CLIENT INFO | BAEK GEUM PHILIPPINES CORPORATION', 1, '2017-04-23 13:11:19'),
(9, 2, 'UPDATED CLIENT INFO | BRGY LOOC ADMIN OFFICE', 1, '2017-04-23 13:11:24'),
(10, 2, 'UPDATED CLIENT INFO | REINLAB CORPORATION', 1, '2017-04-23 13:11:29'),
(11, 2, 'UPDATED CLIENT INFO | SOUTHERN GLOBAL SERVICES MULTI PURPOSE COOPERATIVE', 1, '2017-04-23 13:11:34'),
(12, 3, 'REGISTER NEW PROJECT | HUMAN RESOURCE INFORMATION SYSTEM', 1, '2017-04-23 13:20:03'),
(13, 3, 'REGISTER NEW PROJECT | TIMEKEEPING SYSTEM', 1, '2017-04-23 13:23:44'),
(14, 3, 'REGISTER NEW PROJECT | E-LEARNING', 1, '2017-04-23 13:26:37'),
(15, 3, 'REGISTER NEW PROJECT | EMPLOYEE PORTAL', 1, '2017-04-23 13:30:42'),
(16, 3, 'REGISTER NEW PROJECT | BARANGAY MANAGEMENT SYSTEM', 1, '2017-04-23 13:33:35'),
(17, 2, 'REGISTER NEW CLIENT | ATM COOPERATIVE', 1, '2017-04-23 13:35:13'),
(18, 2, 'UPDATED CLIENT INFO | ATM COOPERATIVE', 1, '2017-04-23 13:35:24'),
(19, 3, 'REGISTER NEW PROJECT | POS SYSTEM', 1, '2017-04-23 13:37:16'),
(20, 3, 'REGISTER NEW PROJECT | REINLAB CORPORATION DYNAMIC WEBSITE', 1, '2017-04-23 13:39:15'),
(21, 3, 'UPDATED PROJECT INFO | REINLAB CORPORATION DYNAMIC WEBSITE', 1, '2017-04-23 13:39:37'),
(22, 4, 'REGISTER NEW TASK | DESIGN USER INTERFACE', 1, '2017-04-23 13:42:24'),
(23, 4, 'UPDATED TASK | DESIGN USER INTERFACE', 1, '2017-04-23 13:43:09'),
(24, 4, 'UPDATED TASK | DESIGN USER INTERFACE', 1, '2017-04-23 13:44:27'),
(25, 3, 'UPDATED PROJECT INFO | REINLAB CORPORATION DYNAMIC WEBSITE', 1, '2017-04-23 13:51:37'),
(26, 4, 'REGISTER NEW TASK | ADMIN PANEL', 1, '2017-04-23 13:53:47'),
(27, 4, 'REGISTER NEW TASK | IMPLEMENTATION', 1, '2017-04-23 14:01:51'),
(28, 4, 'UPDATED TASK | ADMIN PANEL', 1, '2017-04-23 14:02:17'),
(29, 4, 'UPDATED TASK | IMPLEMENTATION', 1, '2017-04-23 14:02:39'),
(30, 4, 'UPDATED TASK | ADMIN PANEL', 1, '2017-04-23 14:03:56'),
(31, 4, 'UPDATED TASK | IMPLEMENTATION', 1, '2017-04-23 14:04:16'),
(32, 4, 'UPDATED TASK | IMPLEMENTATION', 1, '2017-04-23 14:04:43'),
(33, 4, 'REGISTER NEW TASK | ADMIN PANEL', 1, '2017-04-23 14:12:37'),
(34, 4, 'REGISTER NEW TASK | INVENTORY MANAGEMENT', 1, '2017-04-23 14:14:57'),
(35, 4, 'REGISTER NEW TASK | POS TERMINAL', 1, '2017-04-23 14:17:17'),
(36, 4, 'REGISTER NEW TASK | TEST AND IMPLEMENTATION', 1, '2017-04-23 14:19:11'),
(37, 4, 'UPDATED TASK | ADMIN PANEL', 1, '2017-04-23 14:21:44'),
(38, 4, 'UPDATED TASK | INVENTORY MANAGEMENT', 1, '2017-04-23 14:22:50'),
(39, 4, 'UPDATED TASK | POS TERMINAL', 1, '2017-04-23 14:22:57'),
(40, 4, 'UPDATED TASK | TEST AND IMPLEMENTATION', 1, '2017-04-23 14:23:03'),
(41, 4, 'UPDATED TASK | ADMIN PANEL', 1, '2017-04-23 14:24:21'),
(42, 4, 'UPDATED TASK | ADMIN PANEL', 1, '2017-04-23 14:25:15'),
(43, 4, 'UPDATED TASK | INVENTORY MANAGEMENT', 1, '2017-04-23 14:25:21'),
(44, 4, 'UPDATED TASK | POS TERMINAL', 1, '2017-04-23 14:25:26'),
(45, 4, 'UPDATED TASK | TEST AND IMPLEMENTATION', 1, '2017-04-23 14:25:31'),
(46, 4, 'REGISTER NEW TASK | FINGER PRINT SCANNER INTEGRATION', 1, '2017-04-23 14:29:29'),
(47, 4, 'UPDATED TASK | FINGER PRINT SCANNER INTEGRATION', 1, '2017-04-23 14:31:31'),
(48, 1, 'REGISTER NEW TICKET | TEST1', 1, '2025-01-29 02:15:10'),
(49, 1, 'UPDATED TICKET | TEST1', 9, '2025-01-29 02:15:27'),
(50, 1, 'UPDATED TICKET | TEST1', 9, '2025-01-29 02:18:18'),
(51, 4, 'REGISTER NEW TASK | TEST1', 1, '2025-01-29 02:18:43'),
(52, 5, 'REGISTER NEW USER | AGENT1', 1, '2025-01-29 02:27:04'),
(53, 4, 'UPDATED TASK | TEST1', 1, '2025-01-29 02:29:51'),
(54, 4, 'UPDATED TASK | TEST1', 7, '2025-01-29 02:30:31'),
(55, 1, 'REGISTER NEW TICKET | TEST 2', 1, '2025-01-29 02:31:24'),
(56, 4, 'UPDATED TASK | TEST 2', 7, '2025-01-29 02:31:55'),
(57, 4, 'UPDATED TASK | TEST 2', 7, '2025-01-29 02:32:36'),
(58, 5, 'REGISTER NEW USER | NNOBI', 1, '2025-01-29 13:16:41'),
(59, 3, 'REGISTER NEW PROJECT | GENERAL SERVICES DEPARTMENT', 1, '2025-01-29 13:20:33'),
(60, 1, 'REGISTER NEW TICKET | BUILDING PERMIT APPLICATION', 8, '2025-01-29 13:21:48'),
(61, 4, 'UPDATED TASK | BUILDING PERMIT APPLICATION', 7, '2025-01-29 13:22:49'),
(62, 4, 'UPDATED TASK | BUILDING PERMIT APPLICATION', 7, '2025-01-29 13:22:59'),
(63, 4, 'UPDATED TASK | BUILDING PERMIT APPLICATION', 7, '2025-01-29 13:23:48'),
(64, 1, 'REGISTER NEW TICKET | BUILDING PERMIT APPLICATION', 8, '2025-01-29 13:28:14'),
(65, 4, 'REGISTER NEW TASK | MONITORING OF BUILDING PERMIT APPLICATION', 7, '2025-01-29 13:29:12'),
(66, 4, 'UPDATED TASK | MONITORING OF BUILDING PERMIT APPLICATION', 1, '2025-01-29 13:39:20'),
(67, 4, 'UPDATED TASK | MONITORING OF BUILDING PERMIT APPLICATION', 7, '2025-01-29 13:39:43'),
(68, 4, 'UPDATED TASK | MONITORING OF BUILDING PERMIT APPLICATION', 1, '2025-01-29 13:40:00'),
(69, 4, 'UPDATED TASK | BUILDING PERMIT APPLICATION', 1, '2025-01-30 09:12:57'),
(70, 4, 'UPDATED TASK | BUILDING PERMIT APPLICATION', 7, '2025-01-30 09:13:37'),
(71, 4, 'REGISTER NEW TASK | TEST 1', 1, '2025-01-30 09:20:39'),
(72, 1, 'REGISTER NEW TICKET | TWST 2', 7, '2025-01-30 09:22:02'),
(73, 4, 'UPDATED TASK | TWST 2', 1, '2025-01-30 09:24:40'),
(74, 4, 'UPDATED TASK | BUILDING PERMIT APPLICATION', 1, '2025-01-30 09:33:14'),
(75, 1, 'REGISTER NEW TICKET | T1', 8, '2025-01-31 12:20:31'),
(76, 1, 'REGISTER NEW TICKET | TASK2', 8, '2025-01-31 12:26:37'),
(77, 4, 'REGISTER NEW TASK | SUPPORT ', 1, '2025-01-31 13:52:24'),
(78, 4, 'REGISTER NEW TASK | SUPPORT ', 1, '2025-01-31 13:54:40'),
(79, 4, 'REGISTER NEW TASK | SUPPORT 1', 1, '2025-01-31 13:55:32'),
(80, 1, 'REGISTER NEW TICKET | INQUIRY 1', 8, '2025-01-31 15:04:17'),
(81, 1, 'REGISTER NEW TICKET | INQUIRY 2', 8, '2025-01-31 15:04:52'),
(82, 4, 'UPDATED TASK | INQUIRY 1', 1, '2025-01-31 15:06:09'),
(83, 1, 'UPDATED TICKET | INQUIRY 1', 1, '2025-01-31 15:06:49'),
(84, 4, 'UPDATED TASK | INQUIRY 1', 1, '2025-01-31 15:07:18'),
(85, 4, 'REGISTER NEW TASK | SUPPORT ', 7, '2025-01-31 15:13:20'),
(86, 4, 'REGISTER NEW TASK | SUPPORT ', 7, '2025-01-31 15:15:11'),
(87, 4, 'ATTEMPT TO REGISTER NEW TASK, DUPLICATE | SUPPORT ', 7, '2025-01-31 15:17:23'),
(88, 4, 'ATTEMPT TO REGISTER NEW TASK, DUPLICATE | SUPPORT ', 7, '2025-01-31 15:20:46'),
(89, 4, 'ATTEMPT TO REGISTER NEW TASK, DUPLICATE | SUPPORT ', 7, '2025-01-31 15:22:47'),
(90, 4, 'ATTEMPT TO REGISTER NEW TASK, DUPLICATE | Support ', 7, '2025-01-31 15:23:49'),
(91, 4, 'ATTEMPT TO REGISTER NEW TASK, DUPLICATE | Support ', 7, '2025-01-31 15:25:04'),
(92, 4, 'REGISTER NEW TASK | Support ', 7, '2025-01-31 15:26:31'),
(93, 4, 'REGISTER NEW TASK | Support #1', 7, '2025-01-31 15:28:00'),
(94, 4, 'UPDATED TASK | SUPPORT ', 7, '2025-01-31 15:29:24'),
(95, 4, 'REGISTER NEW TASK | Support Ticket: #1', 7, '2025-01-31 15:32:38'),
(96, 4, 'REGISTER NEW TASK | Support #2', 1, '2025-01-31 15:39:41'),
(97, 4, 'UPDATED TASK | Support Ticket: #1', 1, '2025-01-31 15:39:54'),
(98, 4, 'UPDATED TASK | Support Ticket: #1', 7, '2025-01-31 15:40:18'),
(99, 1, 'REGISTER NEW TICKET | Inquiry #1', 8, '2025-01-31 15:45:43'),
(100, 4, 'UPDATED TASK | Inquiry #1', 1, '2025-01-31 15:46:07'),
(101, 1, 'REGISTER NEW TICKET | Inquiry #2', 8, '2025-01-31 15:59:12'),
(102, 4, 'UPDATED TASK | Inquiry #2', 1, '2025-01-31 15:59:30'),
(103, 4, 'REGISTER NEW TASK | TEST1', 1, '2025-02-12 16:30:22'),
(104, 4, 'UPDATED TASK | TEST1', 1, '2025-02-12 16:31:44'),
(105, 4, 'UPDATED TASK | TEST1', 1, '2025-02-12 16:32:30'),
(106, 4, 'REGISTER NEW TASK | TEST2', 1, '2025-02-13 09:24:18'),
(107, 4, 'UPDATED TASK | TEST1', 1, '2025-02-13 16:26:19'),
(108, 4, 'UPDATED TASK | Inquiry #2', 1, '2025-02-14 11:44:37'),
(109, 1, 'REGISTER NEW TICKET | tet 1', 8, '2025-02-14 11:47:34'),
(110, 4, 'UPDATED TASK | tet 1', 1, '2025-02-14 11:48:21'),
(111, 4, 'UPDATED TASK | tet 1', 1, '2025-02-14 12:19:10'),
(112, 4, 'UPDATED TASK | Inquiry #1', 1, '2025-02-14 16:39:33'),
(113, 4, 'UPDATED TASK | Inquiry #1', 1, '2025-02-14 16:39:39'),
(114, 4, 'REGISTER NEW TASK | testken', 1, '2025-02-18 15:01:02'),
(115, 4, 'UPDATED TASK | Support Ticket: #1', 1, '2025-02-18 15:44:48'),
(116, 4, 'UPDATED TASK | Support Ticket: #1', 1, '2025-02-20 13:54:29'),
(117, 4, 'UPDATED TASK | Support Ticket: #133', 1, '2025-02-20 14:03:36'),
(118, 4, 'UPDATED TASK | Support Ticket: #1', 1, '2025-02-20 14:03:44'),
(119, 4, 'UPDATED TASK | tet 1', 1, '2025-02-20 14:59:05'),
(120, 4, 'UPDATED TASK | tet 1', 1, '2025-02-20 14:59:17'),
(121, 4, 'UPDATED TASK | tet 1', 1, '2025-02-20 14:59:25'),
(122, 4, 'UPDATED TASK | testken', 1, '2025-02-20 14:59:52'),
(123, 4, 'UPDATED TASK | testken', 1, '2025-02-20 15:00:02'),
(124, 4, 'UPDATED TASK | testken', 1, '2025-02-20 15:00:26'),
(125, 4, 'UPDATED TASK | testken', 1, '2025-02-20 15:00:39'),
(126, 4, 'UPDATED TASK | Inquiry #1', 1, '2025-02-24 15:40:15');

-- --------------------------------------------------------

--
-- Table structure for table `sys_authoritymodulecategorytb`
--

CREATE TABLE `sys_authoritymodulecategorytb` (
  `id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `modulecategoryid` int(11) NOT NULL,
  `statid` int(11) NOT NULL DEFAULT 1,
  `datecreated` datetime NOT NULL DEFAULT current_timestamp(),
  `createdby_userid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `sys_authoritymodulecategorytb`
--

INSERT INTO `sys_authoritymodulecategorytb` (`id`, `userid`, `modulecategoryid`, `statid`, `datecreated`, `createdby_userid`) VALUES
(1, 1, 4, 1, '2017-01-26 00:00:00', 1),
(2, 1, 1, 1, '2017-01-26 00:00:00', 1),
(3, 1, 2, 1, '2017-01-26 00:00:00', 1),
(4, 1, 3, 1, '2017-01-26 00:00:00', 1),
(5, 2, 4, 1, '2017-02-15 00:00:00', 1),
(6, 7, 1, 1, '2025-01-28 00:00:00', 1),
(7, 7, 3, 1, '2025-01-28 00:00:00', 1),
(8, 8, 1, 1, '2025-01-29 00:00:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `sys_authoritymoduletb`
--

CREATE TABLE `sys_authoritymoduletb` (
  `id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `moduleid` int(11) NOT NULL,
  `authoritystatusid` int(11) NOT NULL,
  `moduleorderno` int(11) NOT NULL,
  `datecreated` datetime NOT NULL DEFAULT current_timestamp(),
  `createdby_userid` int(11) NOT NULL,
  `statid` int(11) NOT NULL DEFAULT 1,
  `modulecategoryid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `sys_authoritymoduletb`
--

INSERT INTO `sys_authoritymoduletb` (`id`, `userid`, `moduleid`, `authoritystatusid`, `moduleorderno`, `datecreated`, `createdby_userid`, `statid`, `modulecategoryid`) VALUES
(1, 1, 6, 1, 1, '2017-01-26 00:00:00', 1, 1, 4),
(2, 1, 1, 1, 1, '2017-01-26 00:00:00', 1, 1, 1),
(3, 1, 2, 1, 1, '2017-01-26 00:00:00', 1, 1, 2),
(4, 1, 3, 1, 1, '2017-01-26 00:00:00', 1, 1, 2),
(5, 1, 4, 1, 1, '2017-01-26 00:00:00', 1, 1, 3),
(6, 1, 5, 1, 1, '2017-01-26 00:00:00', 1, 1, 4),
(7, 2, 5, 1, 1, '2017-02-15 00:00:00', 1, 1, 4),
(8, 1, 7, 1, 1, '2017-04-13 00:00:00', 1, 1, 3),
(9, 7, 1, 1, 1, '2025-01-28 00:00:00', 1, 1, 1),
(10, 7, 4, 1, 1, '2025-01-28 00:00:00', 1, 1, 3),
(11, 8, 1, 1, 1, '2025-01-29 00:00:00', 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `sys_authoritystatustb`
--

CREATE TABLE `sys_authoritystatustb` (
  `id` int(11) NOT NULL,
  `authoritystatus` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `sys_authoritystatustb`
--

INSERT INTO `sys_authoritystatustb` (`id`, `authoritystatus`) VALUES
(1, 'Full (RW)'),
(2, 'Read - Only (R)');

-- --------------------------------------------------------

--
-- Table structure for table `sys_clientstatustb`
--

CREATE TABLE `sys_clientstatustb` (
  `id` int(11) NOT NULL,
  `status` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `sys_clientstatustb`
--

INSERT INTO `sys_clientstatustb` (`id`, `status`) VALUES
(1, 'ACTIVE'),
(2, 'PENDING'),
(3, 'IN-ACTIVE'),
(4, 'DELETED');

-- --------------------------------------------------------

--
-- Table structure for table `sys_clienttb`
--

CREATE TABLE `sys_clienttb` (
  `id` int(255) NOT NULL,
  `clientname` varchar(250) DEFAULT NULL,
  `clientaddressstreet` varchar(250) DEFAULT NULL,
  `clientaddresscity` varchar(250) DEFAULT NULL,
  `clientaddressstate` varchar(250) DEFAULT NULL,
  `clientaddresscountry` varchar(250) DEFAULT NULL,
  `clientemail` varchar(250) DEFAULT NULL,
  `clientcontactnumber` varchar(50) DEFAULT NULL,
  `clientcontactperson` varchar(250) DEFAULT NULL,
  `clientcontactpersonposition` varchar(250) DEFAULT NULL,
  `clientstatusid` int(11) NOT NULL DEFAULT 2,
  `createdbyid` int(255) NOT NULL,
  `datetimecreated` datetime NOT NULL DEFAULT current_timestamp(),
  `updatedbyid` int(255) DEFAULT NULL,
  `datetimeupdated` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `sys_clienttb`
--

INSERT INTO `sys_clienttb` (`id`, `clientname`, `clientaddressstreet`, `clientaddresscity`, `clientaddressstate`, `clientaddresscountry`, `clientemail`, `clientcontactnumber`, `clientcontactperson`, `clientcontactpersonposition`, `clientstatusid`, `createdbyid`, `datetimecreated`, `updatedbyid`, `datetimeupdated`) VALUES
(0, 'REDBELITSOLUTIONS', NULL, NULL, NULL, NULL, 'techsource.itsoultions@gmail.com', NULL, NULL, NULL, 2, 1, '2017-04-23 13:03:14', 1, '2017-04-23 13:03:14'),
(1, 'REINLAB CORPORATION', 'BRGY PULONG STA CRUZ', 'STA ROSA', 'NULL', 'PHILIPPINES', 'CHERRIELYN@REINLAB.COM.PH', '09266932727', 'CHERRIELYN AQUINO', 'HR SUPERVISOR', 1, 1, '2017-04-23 12:36:15', 1, '2017-04-23 12:36:15'),
(2, 'BRGY LOOC ADMIN OFFICE', 'BRGY LOOC', 'CALAMBA', 'NULL', 'PHILIPPINES', 'NA@GMAIL.COM', '09178011142', 'SIR HARRY', 'ADMIN OFFICER', 1, 1, '2017-04-23 12:40:03', 1, '2017-04-23 12:40:03'),
(3, 'SOUTHERN GLOBAL SERVICES MULTI PURPOSE COOPERATIVE', 'PUROK 1, BRGY MILAGROSA (TULO)', 'CALAMBA', 'LAGUNA', 'PHILIPPINES', 'JAYPEEMONTECILLO@GMAIL.COM', '09176274067', 'JAYPE MONTECILLO', 'MANAGER', 1, 1, '2017-04-23 12:52:24', 1, '2017-04-23 12:52:24'),
(4, 'BAEK GEUM PHILIPPINES CORPORATION', 'UNIT 1,2 ', 'CALAMBA', 'LAGUNA', 'PHILIPPINES', 'ADVILLENA@BGTNA.PH', '0000000000', 'ALFREDO VILLENA', 'HR MANAGER', 1, 1, '2017-04-23 12:57:40', 1, '2017-04-23 12:57:40'),
(6, 'ATM COOPERATIVE', 'LAGUNA TECHNO PARK', 'BINAN', 'NULL', 'PHILIPPINES', 'NA@GMAIL.COM', '09998252174', 'MS. JOBELLE', 'COOP ADMIN', 1, 1, '2017-04-23 13:35:13', 1, '2017-04-23 13:35:13');

-- --------------------------------------------------------

--
-- Table structure for table `sys_loginlogstb`
--

CREATE TABLE `sys_loginlogstb` (
  `id` int(11) NOT NULL,
  `userid` int(11) NOT NULL DEFAULT 0,
  `logintime` datetime NOT NULL DEFAULT current_timestamp(),
  `lastaccess` datetime DEFAULT NULL,
  `logid` int(11) NOT NULL,
  `isol` int(11) NOT NULL,
  `dateonly` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `sys_loginlogstb`
--

INSERT INTO `sys_loginlogstb` (`id`, `userid`, `logintime`, `lastaccess`, `logid`, `isol`, `dateonly`) VALUES
(1, 6, '2017-04-23 13:12:05', '2017-04-23 13:12:11', 1, 1, '2017-04-23'),
(2, 1, '2017-04-23 13:12:21', '2025-02-25 09:36:55', 2, 1, '2017-04-23'),
(3, 1, '2017-04-23 13:14:08', NULL, 2, 0, '2017-04-23'),
(4, 5, '2017-04-23 13:14:23', NULL, 2, 0, '2017-04-23'),
(5, 1, '2017-04-23 13:14:29', NULL, 2, 0, '2017-04-23'),
(6, 1, '2017-04-23 22:03:11', NULL, 2, 0, '2017-04-23'),
(7, 1, '2017-04-24 19:18:12', NULL, 2, 0, '2017-04-24'),
(8, 1, '2017-04-28 17:26:49', NULL, 2, 0, '2017-04-28'),
(9, 1, '2017-04-29 14:07:12', NULL, 2, 0, '2017-04-29'),
(10, 1, '2017-05-01 10:35:56', NULL, 2, 0, '2017-05-01'),
(11, 1, '2017-05-08 21:32:22', NULL, 2, 0, '2017-05-08'),
(12, 1, '2017-05-09 15:23:33', NULL, 2, 0, '2017-05-09'),
(13, 1, '2017-05-09 15:26:39', NULL, 2, 0, '2017-05-09'),
(14, 1, '2017-05-15 21:51:18', NULL, 2, 0, '2017-05-15'),
(15, 3, '2017-05-15 22:44:06', NULL, 2, 0, '2017-05-15'),
(16, 1, '2017-05-15 22:44:25', NULL, 2, 0, '2017-05-15'),
(17, 1, '2017-05-15 22:45:00', NULL, 2, 0, '2017-05-15'),
(18, 1, '2017-05-16 21:36:50', NULL, 2, 0, '2017-05-16'),
(19, 1, '2017-05-16 21:50:45', NULL, 2, 0, '2017-05-16'),
(20, 1, '2017-05-16 22:44:03', NULL, 2, 0, '2017-05-16'),
(21, 1, '2017-05-17 10:04:53', NULL, 2, 0, '2017-05-17'),
(22, 1, '2017-07-26 18:03:57', NULL, 2, 0, '2017-07-26'),
(23, 1, '2017-09-14 21:07:24', NULL, 2, 0, '2017-09-14'),
(24, 1, '2025-01-29 01:28:47', NULL, 2, 0, '2025-01-29'),
(25, 2, '2025-01-29 01:30:41', NULL, 2, 0, '2025-01-29'),
(26, 1, '2025-01-29 01:39:58', NULL, 2, 0, '2025-01-29'),
(27, 2, '2025-01-29 02:16:59', NULL, 2, 0, '2025-01-29'),
(28, 1, '2025-01-29 02:17:36', NULL, 2, 0, '2025-01-29'),
(29, 1, '2025-01-29 02:23:51', NULL, 2, 0, '2025-01-29'),
(30, 2, '2025-01-29 02:25:03', NULL, 2, 0, '2025-01-29'),
(31, 1, '2025-01-29 02:25:42', NULL, 2, 0, '2025-01-29'),
(32, 7, '2025-01-29 02:28:48', NULL, 2, 0, '2025-01-29'),
(33, 8, '2025-01-29 13:17:44', NULL, 2, 0, '2025-01-29'),
(34, 1, '2025-01-31 12:10:30', NULL, 2, 0, '2025-01-31'),
(35, 7, '2025-01-31 12:17:23', NULL, 2, 0, '2025-01-31'),
(36, 1, '2025-02-12 14:34:22', NULL, 2, 0, '2025-02-12'),
(37, 1, '2025-02-12 16:20:17', NULL, 2, 0, '2025-02-12'),
(38, 1, '2025-02-13 08:01:26', NULL, 2, 0, '2025-02-13'),
(39, 6, '2025-02-13 11:16:33', NULL, 2, 0, '2025-02-13'),
(40, 1, '2025-02-13 11:17:05', NULL, 2, 0, '2025-02-13'),
(41, 6, '2025-02-13 11:18:47', NULL, 2, 0, '2025-02-13'),
(42, 1, '2025-02-13 11:25:07', NULL, 2, 0, '2025-02-13'),
(43, 6, '2025-02-13 16:05:38', NULL, 2, 0, '2025-02-13'),
(44, 1, '2025-02-13 16:25:03', NULL, 2, 0, '2025-02-13'),
(45, 1, '2025-02-14 08:06:37', NULL, 2, 0, '2025-02-14'),
(46, 6, '2025-02-14 11:41:50', NULL, 2, 0, '2025-02-14'),
(47, 6, '2025-02-14 11:42:11', NULL, 2, 0, '2025-02-14'),
(48, 2, '2025-02-14 11:43:42', NULL, 2, 0, '2025-02-14'),
(49, 1, '2025-02-14 11:44:07', NULL, 2, 0, '2025-02-14'),
(50, 7, '2025-02-14 11:45:18', NULL, 2, 0, '2025-02-14'),
(51, 2, '2025-02-14 11:45:44', NULL, 2, 0, '2025-02-14'),
(52, 8, '2025-02-14 11:47:09', NULL, 2, 0, '2025-02-14'),
(53, 1, '2025-02-14 11:47:46', NULL, 2, 0, '2025-02-14'),
(54, 1, '2025-02-14 16:37:39', NULL, 2, 0, '2025-02-14'),
(55, 2, '2025-02-17 10:52:50', NULL, 2, 0, '2025-02-17'),
(56, 1, '2025-02-17 10:53:07', NULL, 2, 0, '2025-02-17'),
(57, 1, '2025-02-17 14:03:35', NULL, 2, 0, '2025-02-17'),
(58, 1, '2025-02-17 16:17:25', NULL, 2, 0, '2025-02-17'),
(59, 1, '2025-02-18 10:50:14', NULL, 2, 0, '2025-02-18'),
(60, 2, '2025-02-20 15:35:36', NULL, 2, 0, '2025-02-20'),
(61, 7, '2025-02-20 15:36:43', NULL, 2, 0, '2025-02-20'),
(62, 7, '2025-02-20 15:37:56', NULL, 2, 0, '2025-02-20'),
(63, 7, '2025-02-21 08:06:34', NULL, 2, 0, '2025-02-21'),
(64, 1, '2025-02-24 08:13:06', NULL, 2, 0, '2025-02-24'),
(65, 1, '2025-02-24 08:34:06', NULL, 2, 0, '2025-02-24'),
(66, 2, '2025-02-24 08:48:04', NULL, 2, 0, '2025-02-24'),
(67, 7, '2025-02-24 08:48:28', NULL, 2, 0, '2025-02-24'),
(68, 1, '2025-02-24 09:14:22', NULL, 2, 0, '2025-02-24'),
(69, 1, '2025-02-24 10:12:48', NULL, 2, 0, '2025-02-24'),
(70, 1, '2025-02-24 10:54:36', NULL, 2, 0, '2025-02-24'),
(71, 1, '2025-02-24 10:56:47', NULL, 2, 0, '2025-02-24'),
(72, 7, '2025-02-24 11:22:12', NULL, 2, 0, '2025-02-24'),
(73, 1, '2025-02-24 11:58:16', NULL, 2, 0, '2025-02-24'),
(74, 1, '2025-02-24 13:00:41', NULL, 2, 0, '2025-02-24'),
(75, 7, '2025-02-24 15:19:22', NULL, 2, 0, '2025-02-24'),
(76, 2, '2025-02-25 08:37:52', NULL, 2, 0, '2025-02-25'),
(77, 3, '2025-02-25 08:41:12', NULL, 2, 0, '2025-02-25'),
(78, 8, '2025-02-25 08:46:28', NULL, 2, 0, '2025-02-25'),
(79, 2, '2025-02-25 08:54:05', NULL, 2, 0, '2025-02-25'),
(80, 5, '2025-02-25 08:57:13', NULL, 2, 0, '2025-02-25'),
(81, 5, '2025-02-25 09:03:06', NULL, 2, 0, '2025-02-25'),
(82, 5, '2025-02-25 09:03:53', NULL, 2, 0, '2025-02-25'),
(83, 5, '2025-02-25 09:05:05', NULL, 2, 0, '2025-02-25'),
(84, 2, '2025-02-25 09:07:31', NULL, 2, 0, '2025-02-25'),
(85, 9, '2025-02-25 09:09:26', NULL, 2, 0, '2025-02-25'),
(86, 9, '2025-02-25 09:12:13', NULL, 2, 0, '2025-02-25'),
(87, 9, '2025-02-25 09:14:34', NULL, 2, 0, '2025-02-25'),
(88, 9, '2025-02-25 09:15:23', NULL, 2, 0, '2025-02-25'),
(89, 5, '2025-02-25 09:32:57', NULL, 2, 0, '2025-02-25'),
(90, 1, '2025-02-25 09:36:33', NULL, 2, 0, '2025-02-25'),
(91, 1, '2025-02-25 09:37:04', NULL, 2, 0, '2025-02-25');

-- --------------------------------------------------------

--
-- Table structure for table `sys_modulecategorytb`
--

CREATE TABLE `sys_modulecategorytb` (
  `id` int(11) NOT NULL,
  `modulecategory` varchar(255) DEFAULT NULL,
  `statid` int(11) NOT NULL DEFAULT 1,
  `modulecategoryorderno` int(11) DEFAULT NULL,
  `datecreated` datetime NOT NULL DEFAULT current_timestamp(),
  `createdby_userid` int(11) DEFAULT NULL,
  `modulecategorylogo` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `sys_modulecategorytb`
--

INSERT INTO `sys_modulecategorytb` (`id`, `modulecategory`, `statid`, `modulecategoryorderno`, `datecreated`, `createdby_userid`, `modulecategorylogo`) VALUES
(1, 'Helpdesk', 1, 1, '2016-11-25 14:16:13', 1, 'menu-icon fa fa-desktop'),
(2, 'Client Settings', 1, 2, '2016-12-12 08:31:46', 1, 'menu-icon fa fa-cogs'),
(3, 'Project Mngt.', 1, 3, '2016-12-14 12:08:43', 1, 'menu-icon fa fa-calendar-plus-o'),
(4, 'Admin Panel', 1, 4, '2017-01-26 11:44:08', 1, 'menu-icon fa fa-calendar-plus-o');

-- --------------------------------------------------------

--
-- Table structure for table `sys_moduletb`
--

CREATE TABLE `sys_moduletb` (
  `id` int(11) NOT NULL,
  `modulecategoryid` int(11) DEFAULT NULL,
  `modulename` varchar(255) DEFAULT NULL,
  `statid` int(11) NOT NULL DEFAULT 1,
  `modulepath` varchar(255) DEFAULT NULL,
  `moduleorderno` int(11) DEFAULT NULL,
  `datecreated` datetime NOT NULL DEFAULT current_timestamp(),
  `createdby_userid` int(11) NOT NULL,
  `modulepath_index` varchar(255) NOT NULL,
  `modulepath_module` varchar(255) NOT NULL,
  `secondlevel` int(11) NOT NULL DEFAULT 0,
  `firstlevel` int(11) NOT NULL DEFAULT 0,
  `usergroupmasterid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `sys_moduletb`
--

INSERT INTO `sys_moduletb` (`id`, `modulecategoryid`, `modulename`, `statid`, `modulepath`, `moduleorderno`, `datecreated`, `createdby_userid`, `modulepath_index`, `modulepath_module`, `secondlevel`, `firstlevel`, `usergroupmasterid`) VALUES
(1, 1, 'Helpdesk', 1, 'helpdesk.php', 1, '2016-11-25 14:29:35', 1, 'helpdesk/', '', 0, 0, 1),
(2, 2, 'Client Registration', 1, 'clientsettings.php', 1, '2016-12-12 08:34:06', 1, 'clientsettings/', '', 0, 0, 1),
(3, 2, 'Project Registration', 1, 'projectsettings.php', 2, '2016-12-13 15:27:34', 1, 'clientsettings/', '', 0, 0, 1),
(4, 3, 'Project Manager', 1, 'projectmanagement.php', 1, '2016-12-14 12:09:56', 1, 'projectmanagement/', '', 0, 0, 1),
(5, 4, 'User Account', 1, 'userpanel.php', 1, '2017-01-26 11:43:31', 1, 'admin/', '', 0, 0, 1),
(6, 4, 'Authority Panel', 1, 'authoritypanel.php', 2, '2017-01-26 15:39:06', 1, 'admin/', '', 0, 0, 1),
(7, 3, 'Reports', 1, 'reports.php', 2, '2017-04-13 08:58:21', 1, 'projectmanagement/', '', 0, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `sys_paymentmodetb`
--

CREATE TABLE `sys_paymentmodetb` (
  `id` int(11) NOT NULL,
  `paymentmodename` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `sys_paymentmodetb`
--

INSERT INTO `sys_paymentmodetb` (`id`, `paymentmodename`) VALUES
(1, 'One Time Payment'),
(2, 'Installment 2 terms'),
(3, 'installment 3 terms'),
(4, 'installment 4 terms');

-- --------------------------------------------------------

--
-- Table structure for table `sys_priorityleveltb`
--

CREATE TABLE `sys_priorityleveltb` (
  `id` int(11) NOT NULL,
  `priorityname` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `sys_priorityleveltb`
--

INSERT INTO `sys_priorityleveltb` (`id`, `priorityname`) VALUES
(1, 'LOW'),
(2, 'MEDIUM'),
(3, 'HIGH'),
(4, 'CRITICAL');

-- --------------------------------------------------------

--
-- Table structure for table `sys_projecttb`
--

CREATE TABLE `sys_projecttb` (
  `id` int(255) NOT NULL,
  `projectname` varchar(250) DEFAULT NULL,
  `projectstartdate` date DEFAULT NULL,
  `projectenddate` date DEFAULT NULL,
  `clientid` int(255) NOT NULL,
  `datecreated` datetime NOT NULL DEFAULT current_timestamp(),
  `createdbyid` int(255) NOT NULL,
  `updatedbyid` int(255) DEFAULT NULL,
  `datetimeupdated` datetime DEFAULT NULL,
  `statusid` int(11) DEFAULT 1,
  `description` varchar(1000) NOT NULL,
  `projectmanagerid` int(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `sys_projecttb`
--

INSERT INTO `sys_projecttb` (`id`, `projectname`, `projectstartdate`, `projectenddate`, `clientid`, `datecreated`, `createdbyid`, `updatedbyid`, `datetimeupdated`, `statusid`, `description`, `projectmanagerid`) VALUES
(1, 'HUMAN RESOURCE INFORMATION SYSTEM', '2016-02-01', '2016-08-01', 1, '2017-04-23 13:20:03', 1, NULL, NULL, 3, '<P>DEVELOPMENT OF HUMAN RESOURCE INFORMATION SYSTEM (HRIS) WHICH INCLUDES THE FOLLOWING MODULES</P><P>1. EMPLOYEE MANAGEMENT MODULE</P><P>2. TIMEKEEPING MODULE</P><P>3. PIECERATE MANAGEMENT MODULE</P><P>4. PAYROLL MANAGEMENT MODULE</P><P>5. REPORTS</P>', 5),
(2, 'TIMEKEEPING SYSTEM', '2017-06-01', '2017-10-31', 4, '2017-04-23 13:23:44', 1, NULL, NULL, 3, '<P>NOTE: THIS PROJECT IS THE CONTINUATION OF THE EXISTING TIMEKEEPING OF THE CLIENT. HOWEVER, WE WILL REPLACE THE EXISTING WITH OUR WORKING TIMEKEEPING SYSTEM AND MODIFY IT BASE ON THE SYSTEM SPECIFICATION OF THE CLIENT.</P><P>1. MODIFY THE EXISTING TIMEKEEPING MODULE FROM HRIS</P><P>2. CREATE SUMMARY REPORT OF TIMEKEEPING</P>', 5),
(3, 'E-LEARNING', '2017-05-01', '2017-09-30', 3, '2017-04-23 13:26:37', 1, NULL, NULL, 6, '<P>DEVELOPMENT OF E-LEARNING SYSTEM FOR SGS.</P><P>1. AUTOMATED EXAMINATION (MULTIPLE CHOICES)</P><P>2. CREATION OF EXAM</P><P>3. CREATION OF COURSES AND SUBJECTS</P><P>4. UPLOADING OF LEARNING MATERIALS</P><P>5. CREATION OF GROUP</P><P>6. ASSIGNING OF EMPLOYEES PER GROUP</P><P>7. ASSIGNING COURSES PER GROUP/ EMPLOYEES</P>', 5),
(4, 'EMPLOYEE PORTAL', '2017-05-01', '2017-09-30', 3, '2017-04-23 13:30:42', 1, NULL, NULL, 1, '<P>DEVELOPMENT OF EMPLOYEE PORTAL FOR SGS</P><P>1. LINK ALL TIMEKEEPING DATA AND EMPLOYEE DATA USING SYNCHRONIZATION (SCHEDULED SYNCH).</P><P>2. EMPLOYEE PROFLE</P><P>&NBSP; &NBSP; &NBSP; &NBSP;A.) EMPLOYMENT INFORMATION</P><P>&NBSP; &NBSP; &NBSP; &NBSP;B.) PERSONAL INFORMATION</P><P>&NBSP; &NBSP; &NBSP; &NBSP;C.) TIMEKEEPING DATA (RAW AND PROCESSED)</P><P>&NBSP; &NBSP; &NBSP; &NBSP;E.) COURSE TRACKING</P><P>3. COMPANY ANNOUNCEMENTS/ MESSAGES</P>', 5),
(5, 'BARANGAY MANAGEMENT SYSTEM', '2017-03-01', '2017-03-31', 2, '2017-04-23 13:33:35', 1, NULL, NULL, 6, '<P>RESTORE THE EXISTING SYSTEM THAT THEY BOUGHT FROM DIFFERENT VENDOR.</P><P>1.) FINGER PRINT SCANNER INTEGRATION</P>', 5),
(6, 'POS SYSTEM', '2017-02-01', '2017-02-28', 6, '2017-04-23 13:37:16', 1, NULL, NULL, 6, '<P>DEVELOP A POS SYSTEM FOR ATM COOPERATIVE STORE</P><P>1.) INVENTORY MANAGEMENT</P><P>2.) POINT OF SALES USING BARCODE SCANNER AND RFID</P><P>3.) INTEGRATE WITH HRIS SYSTEM OF THE COMPANY</P><P>4.) SALES REPORT</P>', 5),
(7, 'REINLAB CORPORATION DYNAMIC WEBSITE', '2016-11-01', '2016-11-30', 1, '2017-04-23 13:39:15', 1, 1, '2017-04-23 01:51:37', 6, '<P>DEVELOP A DYNAMIC WEBSITE FOR REINLAB CORPORATION</P><P>REINLAB.COM.PH</P>', 5),
(8, 'GENERAL SERVICES DEPARTMENT', '2025-01-29', '2025-01-29', 4, '2025-01-29 13:20:33', 1, NULL, NULL, 3, '<P>THIS PROJECT WILL BE USED FOR THE CARMENSBEST GENERAL SERVICES DEPARTMENT SUPPORT TICKETS.</P>', 1);

-- --------------------------------------------------------

--
-- Table structure for table `sys_statustb`
--

CREATE TABLE `sys_statustb` (
  `id` int(11) NOT NULL,
  `statusname` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `sys_statustb`
--

INSERT INTO `sys_statustb` (`id`, `statusname`) VALUES
(1, 'ACTIVE'),
(2, 'IN-ACTIVE');

-- --------------------------------------------------------

--
-- Table structure for table `sys_taskclassificationtb`
--

CREATE TABLE `sys_taskclassificationtb` (
  `id` int(11) NOT NULL,
  `classification` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `sys_taskclassificationtb`
--

INSERT INTO `sys_taskclassificationtb` (`id`, `classification`) VALUES
(1, 'Feature'),
(2, 'Enhancement'),
(3, 'Bug/Error'),
(4, 'Support/Maintenance'),
(5, 'Others');

-- --------------------------------------------------------

--
-- Table structure for table `sys_taskstatustb`
--

CREATE TABLE `sys_taskstatustb` (
  `id` int(11) NOT NULL,
  `statusname` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `sys_taskstatustb`
--

INSERT INTO `sys_taskstatustb` (`id`, `statusname`) VALUES
(1, 'New'),
(2, 'Rejected'),
(3, 'InProg'),
(4, 'Pending'),
(5, 'Cancelled'),
(6, 'Done');

-- --------------------------------------------------------

--
-- Table structure for table `sys_usergrouptb`
--

CREATE TABLE `sys_usergrouptb` (
  `id` int(11) NOT NULL,
  `groupname` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `sys_usergrouptb`
--

INSERT INTO `sys_usergrouptb` (`id`, `groupname`) VALUES
(1, 'ADMIN'),
(2, 'AGENT/DEV'),
(3, 'HELPDESK'),
(4, 'CLIENT');

-- --------------------------------------------------------

--
-- Table structure for table `sys_userleveltb`
--

CREATE TABLE `sys_userleveltb` (
  `id` int(11) NOT NULL,
  `levelname` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `sys_userleveltb`
--

INSERT INTO `sys_userleveltb` (`id`, `levelname`) VALUES
(1, 'SUPER ADMIN'),
(2, 'ADMINISTRATOR'),
(3, 'STANDARD USER'),
(4, 'CLIENT');

-- --------------------------------------------------------

--
-- Table structure for table `sys_usertb`
--

CREATE TABLE `sys_usertb` (
  `id` int(255) NOT NULL,
  `user_firstname` varchar(50) DEFAULT NULL,
  `user_lastname` varchar(50) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(20) DEFAULT NULL,
  `user_statusid` int(50) DEFAULT NULL,
  `user_levelid` int(10) DEFAULT NULL,
  `sessiontokencode` varchar(50) DEFAULT NULL,
  `user_groupid` int(11) DEFAULT NULL,
  `datecreated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `createdbyid` int(255) DEFAULT NULL,
  `companyid` int(11) NOT NULL,
  `emailadd` varchar(255) NOT NULL,
  `status` varchar(20) DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `sys_usertb`
--

INSERT INTO `sys_usertb` (`id`, `user_firstname`, `user_lastname`, `username`, `password`, `user_statusid`, `user_levelid`, `sessiontokencode`, `user_groupid`, `datecreated`, `createdbyid`, `companyid`, `emailadd`, `status`) VALUES
(1, 'REDBEL', 'ITSOLUTIONS', '1', '1', 1, 1, 'GMWPm0EHkxVyUj7', 3, '2025-02-25 09:37:04', 1, 0, 'ASD@GMAIL.COM', '4'),
(2, 'JUNELYN', 'BELISARIO', 'A', 'A', 1, 4, 'h0IYfpgrhO1bkph', 4, '2025-02-25 09:07:31', 1, 0, 'ASDSA@GMAIL.COM', 'available'),
(3, 'DIANA LYN', 'BAUTISTA', 'TS-DL.BAUSTISTA', '1', 1, 2, 'JIJpbaj4E4KZITz', 1, '2025-02-25 08:41:12', 1, 0, 'MS.DIANALYNBAUTISTA@GMAIL.COM', 'available'),
(4, 'DENVER', 'BALTAZAR', 'TS-D.BALTAZAR', '1234', 1, 3, NULL, 2, '2017-04-23 13:12:31', 1, 0, 'BALTAZAR.DENVERF@GMAIL.COM', 'available'),
(5, 'PHILIP', 'REDONDO', 'TS-P.REDONDO', '1234', 1, 2, 'jYMbh5GsjXcx4HN', 3, '2025-02-25 09:32:57', 1, 0, 'PHILIP.REDONDO20@GMAIL.COM', 'available'),
(6, 'CHERRIELYN', 'AQUINO', 'REP-C.AQUINO', '1234', 1, 4, 'hJSF77sebVWJFk1', 4, '2025-02-14 11:42:11', 1, 1, 'CHERRIELYN@REINLAB.COM.PH', 'available'),
(7, 'AGENT1', 'AGENT1', 'AGENT1', '1234', 1, 3, 'hujgKy3JY0mUZRc', 2, '2025-02-24 15:19:22', 1, 1, 'E@MAIL.COM', 'available'),
(8, 'NOBITA', 'NOBI', 'NNOBI', '1234', 1, 3, 'GmwYWLSHJTx818S', 4, '2025-02-25 08:46:28', 1, 4, 'NNOBI@MAIL.COM', 'available'),
(9, 'PHILIP', 'PNC', 'PNC-USER', '1', 1, 1, '10w9zZ8eOfQVnc8', 3, '2025-02-25 09:15:23', 1, 0, '', 'available');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pm_projecttasktb`
--
ALTER TABLE `pm_projecttasktb`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pm_taskassigneetb`
--
ALTER TABLE `pm_taskassigneetb`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pm_threadtb`
--
ALTER TABLE `pm_threadtb`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sys_audit`
--
ALTER TABLE `sys_audit`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sys_authoritymodulecategorytb`
--
ALTER TABLE `sys_authoritymodulecategorytb`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sys_authoritymoduletb`
--
ALTER TABLE `sys_authoritymoduletb`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sys_authoritystatustb`
--
ALTER TABLE `sys_authoritystatustb`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sys_clientstatustb`
--
ALTER TABLE `sys_clientstatustb`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sys_clienttb`
--
ALTER TABLE `sys_clienttb`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sys_loginlogstb`
--
ALTER TABLE `sys_loginlogstb`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sys_modulecategorytb`
--
ALTER TABLE `sys_modulecategorytb`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sys_moduletb`
--
ALTER TABLE `sys_moduletb`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sys_paymentmodetb`
--
ALTER TABLE `sys_paymentmodetb`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sys_priorityleveltb`
--
ALTER TABLE `sys_priorityleveltb`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sys_projecttb`
--
ALTER TABLE `sys_projecttb`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sys_statustb`
--
ALTER TABLE `sys_statustb`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sys_taskclassificationtb`
--
ALTER TABLE `sys_taskclassificationtb`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sys_taskstatustb`
--
ALTER TABLE `sys_taskstatustb`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sys_usergrouptb`
--
ALTER TABLE `sys_usergrouptb`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sys_userleveltb`
--
ALTER TABLE `sys_userleveltb`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sys_usertb`
--
ALTER TABLE `sys_usertb`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pm_projecttasktb`
--
ALTER TABLE `pm_projecttasktb`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `pm_taskassigneetb`
--
ALTER TABLE `pm_taskassigneetb`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `pm_threadtb`
--
ALTER TABLE `pm_threadtb`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT for table `sys_audit`
--
ALTER TABLE `sys_audit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT for table `sys_authoritymodulecategorytb`
--
ALTER TABLE `sys_authoritymodulecategorytb`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `sys_authoritymoduletb`
--
ALTER TABLE `sys_authoritymoduletb`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `sys_authoritystatustb`
--
ALTER TABLE `sys_authoritystatustb`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sys_clientstatustb`
--
ALTER TABLE `sys_clientstatustb`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sys_clienttb`
--
ALTER TABLE `sys_clienttb`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sys_loginlogstb`
--
ALTER TABLE `sys_loginlogstb`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `sys_modulecategorytb`
--
ALTER TABLE `sys_modulecategorytb`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sys_moduletb`
--
ALTER TABLE `sys_moduletb`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `sys_paymentmodetb`
--
ALTER TABLE `sys_paymentmodetb`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sys_priorityleveltb`
--
ALTER TABLE `sys_priorityleveltb`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sys_projecttb`
--
ALTER TABLE `sys_projecttb`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `sys_statustb`
--
ALTER TABLE `sys_statustb`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sys_taskclassificationtb`
--
ALTER TABLE `sys_taskclassificationtb`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sys_taskstatustb`
--
ALTER TABLE `sys_taskstatustb`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sys_usergrouptb`
--
ALTER TABLE `sys_usergrouptb`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sys_userleveltb`
--
ALTER TABLE `sys_userleveltb`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sys_usertb`
--
ALTER TABLE `sys_usertb`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
