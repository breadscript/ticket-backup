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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pm_threadtb`
--
ALTER TABLE `pm_threadtb`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pm_threadtb`
--
ALTER TABLE `pm_threadtb`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
