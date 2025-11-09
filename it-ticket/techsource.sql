-- phpMyAdmin SQL Dump
-- version 4.2.11
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Oct 27, 2017 at 01:15 PM
-- Server version: 5.6.21
-- PHP Version: 7.0.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `techsource`
--

-- --------------------------------------------------------

--
-- Table structure for table `pm_projecttasktb`
--

CREATE TABLE IF NOT EXISTS `pm_projecttasktb` (
`id` int(11) NOT NULL,
  `subject` text NOT NULL,
  `description` text NOT NULL,
  `deadline` date NOT NULL,
  `startdate` date NOT NULL,
  `enddate` date NOT NULL,
  `classificationid` int(11) NOT NULL,
  `statusid` int(11) NOT NULL DEFAULT '1',
  `priorityid` int(11) NOT NULL,
  `createdbyid` int(11) NOT NULL,
  `datetimecreated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedbyid` int(11) DEFAULT NULL,
  `datetimeupdated` datetime DEFAULT NULL,
  `assignee` text,
  `projectid` int(11) NOT NULL,
  `percentdone` float NOT NULL DEFAULT '0',
  `istask` int(11) NOT NULL,
  `isupdatedbyclient` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `pm_projecttasktb`
--

INSERT INTO `pm_projecttasktb` (`id`, `subject`, `description`, `deadline`, `startdate`, `enddate`, `classificationid`, `statusid`, `priorityid`, `createdbyid`, `datetimecreated`, `updatedbyid`, `datetimeupdated`, `assignee`, `projectid`, `percentdone`, `istask`, `isupdatedbyclient`) VALUES
(1, 'DESIGN USER INTERFACE', '<P>DESIGN OVER ALL USER INTERFACE OF THE WEBSITE.</P><P>NOTE: PREPARE THE UAT DOCUMENT FOR SIGNATURE.</P>', '2016-11-04', '2016-11-01', '2016-11-04', 1, 6, 3, 1, '2017-04-23 13:42:24', NULL, NULL, '2', 7, 1, 1, 0),
(2, 'ADMIN PANEL', '<P>1.) CREATE ADMIN PANEL TO EDIT ALL THE CONTENTS OF THE WEBSITE.</P>', '2016-11-11', '2016-11-07', '2016-11-10', 1, 6, 3, 1, '2017-04-23 13:53:47', NULL, NULL, '2', 7, 3, 1, 0),
(3, 'IMPLEMENTATION', '<P>DEPLOY THE WEBSITE TO CLOUD HOSTING</P>', '2016-11-22', '2016-11-21', '2016-11-22', 4, 6, 3, 1, '2017-04-23 14:01:50', NULL, NULL, '4', 7, 1, 1, 0),
(4, 'ADMIN PANEL', '<P>CREATE ADMIN PANEL</P><P>1.) USER SETTINGS</P><P>2.) MEMBER MANAGEMENT (CREDIT)</P><P>3.) REPORTS</P>', '2017-02-07', '2017-02-01', '2017-02-00', 1, 6, 3, 1, '2017-04-23 14:12:37', NULL, NULL, '5', 6, 1, 1, 0),
(5, 'INVENTORY MANAGEMENT', '<P>1.) REGISTRATION OF ITEMS</P><P>2.) UPDATING OF ITEMS</P><P>3.) PURGING OF ITEM COUNTS</P>', '2017-02-11', '2017-02-07', '2017-02-11', 1, 6, 3, 1, '2017-04-23 14:14:57', NULL, NULL, '5', 6, 1, 1, 0),
(6, 'POS TERMINAL', '<P>CREATE POS TERMINAL&AMP;NBSP;</P><P>1.) PAYMENTS</P><P>2.) ITEM CHECKING&AMP;NBSP;</P><P>3.) AUTO CREDIT FOR COOP MEMBERS</P><P>4.) AUTO CREDIT RESET EVERY 1ST AND 16TH OF THE MONTH OR NEAREST IF LONG WEEKENDS</P>', '2017-02-18', '2017-02-11', '2017-02-18', 1, 6, 4, 1, '2017-04-23 14:17:17', NULL, NULL, '5', 6, 1, 1, 0),
(7, 'TEST AND IMPLEMENTATION', '<P>TEST AND IMPLEMENT THE SYSTEM TO ALL TERMINAL AND STORES</P><P>NOTE: PREPARE THE UAT DOCUMENTS FOR SIGNATURE</P>', '2016-11-30', '2016-11-23', '2016-11-30', 4, 6, 4, 1, '2017-04-23 14:19:10', NULL, NULL, '4,5', 6, 1, 1, 0),
(8, 'FINGER PRINT SCANNER INTEGRATION', '<P>GET FPS FROM ADMIN OFFICE,</P><P>1.) INTEGRATE FPS TO THE SYSTEM</P><P>2.) ALLOW CONFIRMATION AND VALIDATION USING FPS</P>', '2017-03-31', '2017-03-15', '2017-04-23', 3, 6, 4, 1, '2017-04-23 14:29:29', NULL, NULL, '4', 5, 2, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `pm_taskassigneetb`
--

CREATE TABLE IF NOT EXISTS `pm_taskassigneetb` (
`id` int(11) NOT NULL,
  `taskid` int(11) NOT NULL,
  `assigneeid` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `pm_taskassigneetb`
--

INSERT INTO `pm_taskassigneetb` (`id`, `taskid`, `assigneeid`) VALUES
(3, 1, 2),
(8, 2, 2),
(10, 3, 4),
(22, 4, 5),
(23, 5, 5),
(24, 6, 5),
(25, 7, 4),
(26, 7, 5),
(28, 8, 4);

-- --------------------------------------------------------

--
-- Table structure for table `pm_threadtb`
--

CREATE TABLE IF NOT EXISTS `pm_threadtb` (
`id` int(11) NOT NULL,
  `taskid` int(11) NOT NULL,
  `createdbyid` int(11) NOT NULL,
  `datetimecreated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `subject` text NOT NULL,
  `message` text NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `pm_threadtb`
--

INSERT INTO `pm_threadtb` (`id`, `taskid`, `createdbyid`, `datetimecreated`, `subject`, `message`) VALUES
(1, 8, 1, '2017-04-23 14:30:22', 'FPS ISSUE', 'THE FPS IS NOT COMPATIBLE WITH THE JAVASCRIPT INSTALLED'),
(2, 8, 1, '2017-04-23 14:31:03', 'VIRUS DETECTION', 'VIRUS DELETED THE EXE FILE THAT ALLOW THE FPS TO RUN');

-- --------------------------------------------------------

--
-- Table structure for table `sys_audit`
--

CREATE TABLE IF NOT EXISTS `sys_audit` (
`id` int(11) NOT NULL,
  `module` int(11) NOT NULL,
  `remarks` varchar(250) DEFAULT NULL,
  `userid` int(11) NOT NULL,
  `datetimecreated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8;

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
(47, 4, 'UPDATED TASK | FINGER PRINT SCANNER INTEGRATION', 1, '2017-04-23 14:31:31');

-- --------------------------------------------------------

--
-- Table structure for table `sys_authoritymodulecategorytb`
--

CREATE TABLE IF NOT EXISTS `sys_authoritymodulecategorytb` (
`id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `modulecategoryid` int(11) NOT NULL,
  `statid` int(11) NOT NULL DEFAULT '1',
  `datecreated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createdby_userid` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `sys_authoritymodulecategorytb`
--

INSERT INTO `sys_authoritymodulecategorytb` (`id`, `userid`, `modulecategoryid`, `statid`, `datecreated`, `createdby_userid`) VALUES
(1, 1, 4, 1, '2017-01-26 00:00:00', 1),
(2, 1, 1, 1, '2017-01-26 00:00:00', 1),
(3, 1, 2, 1, '2017-01-26 00:00:00', 1),
(4, 1, 3, 1, '2017-01-26 00:00:00', 1),
(5, 2, 4, 1, '2017-02-15 00:00:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `sys_authoritymoduletb`
--

CREATE TABLE IF NOT EXISTS `sys_authoritymoduletb` (
`id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `moduleid` int(11) NOT NULL,
  `authoritystatusid` int(11) NOT NULL,
  `moduleorderno` int(11) NOT NULL,
  `datecreated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createdby_userid` int(11) NOT NULL,
  `statid` int(11) NOT NULL DEFAULT '1',
  `modulecategoryid` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

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
(8, 1, 7, 1, 1, '2017-04-13 00:00:00', 1, 1, 3);

-- --------------------------------------------------------

--
-- Table structure for table `sys_authoritystatustb`
--

CREATE TABLE IF NOT EXISTS `sys_authoritystatustb` (
`id` int(11) NOT NULL,
  `authoritystatus` varchar(40) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

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

CREATE TABLE IF NOT EXISTS `sys_clientstatustb` (
`id` int(11) NOT NULL,
  `status` varchar(255) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

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

CREATE TABLE IF NOT EXISTS `sys_clienttb` (
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
  `clientstatusid` int(11) NOT NULL DEFAULT '2',
  `createdbyid` int(255) NOT NULL,
  `datetimecreated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedbyid` int(255) DEFAULT NULL,
  `datetimeupdated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

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

CREATE TABLE IF NOT EXISTS `sys_loginlogstb` (
`id` int(11) NOT NULL,
  `userid` int(11) NOT NULL DEFAULT '0',
  `logintime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lastaccess` datetime DEFAULT NULL,
  `logid` int(11) NOT NULL,
  `isol` int(11) NOT NULL,
  `dateonly` date NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `sys_loginlogstb`
--

INSERT INTO `sys_loginlogstb` (`id`, `userid`, `logintime`, `lastaccess`, `logid`, `isol`, `dateonly`) VALUES
(1, 6, '2017-04-23 13:12:05', '2017-04-23 13:12:11', 1, 1, '2017-04-23'),
(2, 1, '2017-04-23 13:12:21', '2017-07-26 18:04:01', 2, 1, '2017-04-23'),
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
(23, 1, '2017-09-14 21:07:24', NULL, 2, 0, '2017-09-14');

-- --------------------------------------------------------

--
-- Table structure for table `sys_modulecategorytb`
--

CREATE TABLE IF NOT EXISTS `sys_modulecategorytb` (
`id` int(11) NOT NULL,
  `modulecategory` varchar(255) DEFAULT NULL,
  `statid` int(11) NOT NULL DEFAULT '1',
  `modulecategoryorderno` int(11) DEFAULT NULL,
  `datecreated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createdby_userid` int(11) DEFAULT NULL,
  `modulecategorylogo` varchar(255) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

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

CREATE TABLE IF NOT EXISTS `sys_moduletb` (
`id` int(11) NOT NULL,
  `modulecategoryid` int(11) DEFAULT NULL,
  `modulename` varchar(255) DEFAULT NULL,
  `statid` int(11) NOT NULL DEFAULT '1',
  `modulepath` varchar(255) DEFAULT NULL,
  `moduleorderno` int(11) DEFAULT NULL,
  `datecreated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createdby_userid` int(11) NOT NULL,
  `modulepath_index` varchar(255) NOT NULL,
  `modulepath_module` varchar(255) NOT NULL,
  `secondlevel` int(11) NOT NULL DEFAULT '0',
  `firstlevel` int(11) NOT NULL DEFAULT '0',
  `usergroupmasterid` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

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

CREATE TABLE IF NOT EXISTS `sys_paymentmodetb` (
`id` int(11) NOT NULL,
  `paymentmodename` varchar(250) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

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

CREATE TABLE IF NOT EXISTS `sys_priorityleveltb` (
`id` int(11) NOT NULL,
  `priorityname` varchar(255) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

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

CREATE TABLE IF NOT EXISTS `sys_projecttb` (
`id` int(255) NOT NULL,
  `projectname` varchar(250) DEFAULT NULL,
  `projectstartdate` date DEFAULT NULL,
  `projectenddate` date DEFAULT NULL,
  `clientid` int(255) NOT NULL,
  `datecreated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createdbyid` int(255) NOT NULL,
  `updatedbyid` int(255) DEFAULT NULL,
  `datetimeupdated` datetime DEFAULT NULL,
  `statusid` int(11) DEFAULT '1',
  `description` varchar(1000) NOT NULL,
  `projectmanagerid` int(255) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `sys_projecttb`
--

INSERT INTO `sys_projecttb` (`id`, `projectname`, `projectstartdate`, `projectenddate`, `clientid`, `datecreated`, `createdbyid`, `updatedbyid`, `datetimeupdated`, `statusid`, `description`, `projectmanagerid`) VALUES
(1, 'HUMAN RESOURCE INFORMATION SYSTEM', '2016-02-01', '2016-08-01', 1, '2017-04-23 13:20:03', 1, NULL, NULL, 1, '<P>DEVELOPMENT OF HUMAN RESOURCE INFORMATION SYSTEM (HRIS) WHICH INCLUDES THE FOLLOWING MODULES</P><P>1. EMPLOYEE MANAGEMENT MODULE</P><P>2. TIMEKEEPING MODULE</P><P>3. PIECERATE MANAGEMENT MODULE</P><P>4. PAYROLL MANAGEMENT MODULE</P><P>5. REPORTS</P>', 5),
(2, 'TIMEKEEPING SYSTEM', '2017-06-01', '2017-10-31', 4, '2017-04-23 13:23:44', 1, NULL, NULL, 1, '<P>NOTE: THIS PROJECT IS THE CONTINUATION OF THE EXISTING TIMEKEEPING OF THE CLIENT. HOWEVER, WE WILL REPLACE THE EXISTING WITH OUR WORKING TIMEKEEPING SYSTEM AND MODIFY IT BASE ON THE SYSTEM SPECIFICATION OF THE CLIENT.</P><P>1. MODIFY THE EXISTING TIMEKEEPING MODULE FROM HRIS</P><P>2. CREATE SUMMARY REPORT OF TIMEKEEPING</P>', 5),
(3, 'E-LEARNING', '2017-05-01', '2017-09-30', 3, '2017-04-23 13:26:37', 1, NULL, NULL, 1, '<P>DEVELOPMENT OF E-LEARNING SYSTEM FOR SGS.</P><P>1. AUTOMATED EXAMINATION (MULTIPLE CHOICES)</P><P>2. CREATION OF EXAM</P><P>3. CREATION OF COURSES AND SUBJECTS</P><P>4. UPLOADING OF LEARNING MATERIALS</P><P>5. CREATION OF GROUP</P><P>6. ASSIGNING OF EMPLOYEES PER GROUP</P><P>7. ASSIGNING COURSES PER GROUP/ EMPLOYEES</P>', 5),
(4, 'EMPLOYEE PORTAL', '2017-05-01', '2017-09-30', 3, '2017-04-23 13:30:42', 1, NULL, NULL, 1, '<P>DEVELOPMENT OF EMPLOYEE PORTAL FOR SGS</P><P>1. LINK ALL TIMEKEEPING DATA AND EMPLOYEE DATA USING SYNCHRONIZATION (SCHEDULED SYNCH).</P><P>2. EMPLOYEE PROFLE</P><P>&NBSP; &NBSP; &NBSP; &NBSP;A.) EMPLOYMENT INFORMATION</P><P>&NBSP; &NBSP; &NBSP; &NBSP;B.) PERSONAL INFORMATION</P><P>&NBSP; &NBSP; &NBSP; &NBSP;C.) TIMEKEEPING DATA (RAW AND PROCESSED)</P><P>&NBSP; &NBSP; &NBSP; &NBSP;E.) COURSE TRACKING</P><P>3. COMPANY ANNOUNCEMENTS/ MESSAGES</P>', 5),
(5, 'BARANGAY MANAGEMENT SYSTEM', '2017-03-01', '2017-03-31', 2, '2017-04-23 13:33:35', 1, NULL, NULL, 6, '<P>RESTORE THE EXISTING SYSTEM THAT THEY BOUGHT FROM DIFFERENT VENDOR.</P><P>1.) FINGER PRINT SCANNER INTEGRATION</P>', 5),
(6, 'POS SYSTEM', '2017-02-01', '2017-02-28', 6, '2017-04-23 13:37:16', 1, NULL, NULL, 6, '<P>DEVELOP A POS SYSTEM FOR ATM COOPERATIVE STORE</P><P>1.) INVENTORY MANAGEMENT</P><P>2.) POINT OF SALES USING BARCODE SCANNER AND RFID</P><P>3.) INTEGRATE WITH HRIS SYSTEM OF THE COMPANY</P><P>4.) SALES REPORT</P>', 5),
(7, 'REINLAB CORPORATION DYNAMIC WEBSITE', '2016-11-01', '2016-11-30', 1, '2017-04-23 13:39:15', 1, 1, '2017-04-23 01:51:37', 6, '<P>DEVELOP A DYNAMIC WEBSITE FOR REINLAB CORPORATION</P><P>REINLAB.COM.PH</P>', 5);

-- --------------------------------------------------------

--
-- Table structure for table `sys_statustb`
--

CREATE TABLE IF NOT EXISTS `sys_statustb` (
`id` int(11) NOT NULL,
  `statusname` varchar(50) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

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

CREATE TABLE IF NOT EXISTS `sys_taskclassificationtb` (
`id` int(11) NOT NULL,
  `classification` varchar(255) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

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

CREATE TABLE IF NOT EXISTS `sys_taskstatustb` (
`id` int(11) NOT NULL,
  `statusname` varchar(255) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

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

CREATE TABLE IF NOT EXISTS `sys_usergrouptb` (
`id` int(11) NOT NULL,
  `groupname` varchar(50) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `sys_usergrouptb`
--

INSERT INTO `sys_usergrouptb` (`id`, `groupname`) VALUES
(1, 'ADMIN'),
(2, 'DEVELOPER'),
(3, 'PROJECT MANAGER'),
(4, 'CLIENT');

-- --------------------------------------------------------

--
-- Table structure for table `sys_userleveltb`
--

CREATE TABLE IF NOT EXISTS `sys_userleveltb` (
`id` int(11) NOT NULL,
  `levelname` varchar(50) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

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

CREATE TABLE IF NOT EXISTS `sys_usertb` (
`id` int(255) NOT NULL,
  `user_firstname` varchar(50) DEFAULT NULL,
  `user_lastname` varchar(50) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(20) DEFAULT NULL,
  `user_statusid` int(50) DEFAULT NULL,
  `user_levelid` int(10) DEFAULT NULL,
  `sessiontokencode` varchar(50) DEFAULT NULL,
  `user_groupid` int(11) DEFAULT NULL,
  `datecreated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `createdbyid` int(255) DEFAULT NULL,
  `companyid` int(11) NOT NULL,
  `emailadd` varchar(255) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `sys_usertb`
--

INSERT INTO `sys_usertb` (`id`, `user_firstname`, `user_lastname`, `username`, `password`, `user_statusid`, `user_levelid`, `sessiontokencode`, `user_groupid`, `datecreated`, `createdbyid`, `companyid`, `emailadd`) VALUES
(1, 'REDBEL', 'ITSOLUTIONS', 'SA', '1', 1, 1, '1FqnIGZfTrMNjyE', 1, '2017-09-14 21:07:24', 1, 0, 'ASD@GMAIL.COM'),
(2, 'JUNELYN', 'BELISARIO', 'TS-J.BELISARIO', '1234', 1, 1, 'FqhsPhGyRRmmbqT', 2, '2017-04-23 13:12:56', 1, 0, 'ASDSA@GMAIL.COM'),
(3, 'DIANA LYN', 'BAUTISTA', 'TS-DL.BAUSTISTA', '1', 1, 2, 'G6G8ENcXemxvtt4', 1, '2017-05-15 22:44:06', 1, 0, 'MS.DIANALYNBAUTISTA@GMAIL.COM'),
(4, 'DENVER', 'BALTAZAR', 'TS-D.BALTAZAR', '1234', 1, 3, NULL, 2, '2017-04-23 13:12:31', 1, 0, 'BALTAZAR.DENVERF@GMAIL.COM'),
(5, 'PHILIP', 'REDONDO', 'TS-P.REDONDO', '1234', 1, 2, 'ZMX2Rw0QqJ1wpW2', 3, '2017-04-23 13:14:23', 1, 0, 'PHILIP.REDONDO20@GMAIL.COM'),
(6, 'CHERRIELYN', 'AQUINO', 'REP-C.AQUINO', '1234', 1, 4, 'GOOf1X0OmZbseyj', 4, '2017-04-23 13:12:05', 1, 1, 'CHERRIELYN@REINLAB.COM.PH');

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
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT for table `pm_taskassigneetb`
--
ALTER TABLE `pm_taskassigneetb`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=29;
--
-- AUTO_INCREMENT for table `pm_threadtb`
--
ALTER TABLE `pm_threadtb`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `sys_audit`
--
ALTER TABLE `sys_audit`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=48;
--
-- AUTO_INCREMENT for table `sys_authoritymodulecategorytb`
--
ALTER TABLE `sys_authoritymodulecategorytb`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `sys_authoritymoduletb`
--
ALTER TABLE `sys_authoritymoduletb`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT for table `sys_authoritystatustb`
--
ALTER TABLE `sys_authoritystatustb`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `sys_clientstatustb`
--
ALTER TABLE `sys_clientstatustb`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `sys_clienttb`
--
ALTER TABLE `sys_clienttb`
MODIFY `id` int(255) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT for table `sys_loginlogstb`
--
ALTER TABLE `sys_loginlogstb`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=24;
--
-- AUTO_INCREMENT for table `sys_modulecategorytb`
--
ALTER TABLE `sys_modulecategorytb`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `sys_moduletb`
--
ALTER TABLE `sys_moduletb`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=8;
--
-- AUTO_INCREMENT for table `sys_paymentmodetb`
--
ALTER TABLE `sys_paymentmodetb`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `sys_priorityleveltb`
--
ALTER TABLE `sys_priorityleveltb`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `sys_projecttb`
--
ALTER TABLE `sys_projecttb`
MODIFY `id` int(255) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=8;
--
-- AUTO_INCREMENT for table `sys_statustb`
--
ALTER TABLE `sys_statustb`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `sys_taskclassificationtb`
--
ALTER TABLE `sys_taskclassificationtb`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `sys_taskstatustb`
--
ALTER TABLE `sys_taskstatustb`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT for table `sys_usergrouptb`
--
ALTER TABLE `sys_usergrouptb`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `sys_userleveltb`
--
ALTER TABLE `sys_userleveltb`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `sys_usertb`
--
ALTER TABLE `sys_usertb`
MODIFY `id` int(255) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
