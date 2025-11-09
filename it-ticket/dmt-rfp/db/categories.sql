-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 28, 2025 at 07:29 AM
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
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_code` varchar(50) NOT NULL,
  `category_id` varchar(50) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `sap_account_code` varchar(20) DEFAULT NULL,
  `sap_account_description` varchar(200) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category_code`, `category_id`, `category_name`, `description`, `sap_account_code`, `sap_account_description`, `is_active`, `created_at`, `updated_at`) VALUES
-- PERSONNEL EXPENSE
(1, 'UNIFORM', 'C001', 'Uniform Expense', 'This pertains to expenses incurred for laundry services and uniform-related costs for employees, including cleaning and issuance of workwear.', '6010000150', 'GAEX-Laundry/Uniform', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),
(2, 'SETTLEMENT', 'C002', 'Personnel Expense - Settlement', 'This pertains to settlement costs arising from disputes, claims, or other legal matters involving the company.', '6010000520', 'GAEX-Settlement', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),

-- MANPOWER
(3, 'OUTSIDE_SERV', 'C003', 'Outside Services', 'This pertaines to payment to services hired by the Company such as payroll outsource, security services and other contracted services.', '6055100230', 'GAEX-Outsource Serv', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),
(4, 'OS_MANPOWER', 'C004', 'OS- Manpower', 'This pertains to outsourced manpower services, including third-party personnel hired for operational support or temporary assignments.', '6055100330', 'GAEX-OS- Manpower', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),
(5, 'OS_COURIER', 'C005', 'OS-Courier', 'This pertains to outsourced courier services used for the delivery and transport of documents, parcels, and other company-related items.', '6055100350', 'GAEX-OS-Courier', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),

-- TRAINING & SEMINAR
(6, 'TRAIN_SEM', 'C006', 'Training and Seminar', 'This pertains to training and seminar expenses for employee development and professional growth.', '6075100010', 'GAEX-Train&Seminar', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),

-- PROFESSIONAL FEES
(7, 'PROF_FEES', 'C007', 'Professional Fees', 'This pertaines to payment to professional services such as CPAs, Lawyers, brokers, Doctors, engineers and etc.', '6015100010', 'GAEX-Consult Serv', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),

-- RENTAL
(8, 'RENT_OTH', 'C008', 'Rent Expense', 'This pertains to payment of rental for admin pruposes such as vehicle, admin building, land and staff house.', '6025100050', 'GAEX-Rent Exp-OTH', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),
(9, 'RENT_OFFICE', 'C009', 'Rent - Office', 'This pertains to rental expenses for office spaces used for business operations.', '6025100080', 'GAEX-Rent-Office', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),
(10, 'RENT_CUSA', 'C010', 'Rent - CUSA', 'This pertaines to common area charges used by the company.', '6025100090', 'GAEX-Rent-Cusa', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),

-- LIGHT AND WATER
(11, 'WATER_EXP', 'C011', 'Light and Water', 'This pertains to water utility expenses incurred for business operations.', '6040000060', 'GAEX-Water expense', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),
(12, 'ELEC_AIRCON', 'C012', 'Electricity and Aircon', 'This pertains to electricity and air conditioning expenses for office and operational areas.', '6040000010', 'GAEX-Elec&Aircon', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),

-- REPAIRS AND MAINTENANCE
(13, 'RM_OTHER', 'C013', 'Repairs and Maintenance (Labor)', 'This pertains to other repair and maintenance expenses not specifically categorized.', '6050000110', 'GAEX-R&M-Other R&M', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),
(14, 'RM_LH_IMPROV', 'C014', 'R&M-LH Improvement', 'This pertains to repair and improvement expenses for leased housing or accommodations.', '6050000040', 'GAEX-R&M-LH improv', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),
(15, 'RM_PLANT_FAC', 'C015', 'R&M-Plant & Facility', 'This pertains to repair and maintenance expenses for plant and facility infrastructure.', '6050000090', 'GAEX-R&M-Plant & fac', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),
(16, 'RM_MATERIALS', 'C016', 'Repairs and Maintenance (Materials)', 'This pertains to repair and maintenance materials and supplies.', '6050000110', 'GAEX-R&M-Other R&M', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),

-- HANDLING AND DELIVERY
(17, 'FREIGHT_DEL', 'C017', 'Freight and Delivery', 'This pertains to shipping and freight expenses for transporting goods and materials.', '6097000130', 'GAEX-Ship&Freight Ex', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),

-- GASOLINE
(18, 'GAS_OIL', 'C018', 'Gasoline and Oil', 'This pertaines to all gasoline and fuel related expense.', '6035100040', 'GAEX-Gas & Oil', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),

-- ADVERTISING AND PROMOTIONS
(19, 'ADV_EXP', 'C019', 'Advertising Expense', 'This pertains to costs incurred in advertising Carmen\'s Best and related brand names, goods, or services to a broad consumer audience.', '6060000190', 'GAEX-BSA-Advertising', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),
(20, 'ADV_SUPPORT', 'C020', 'Advertising Support Expense', 'This pertains to support costs related to advertising Carmen\'s Best and associated brands, including materials and services aimed at reaching a broad consumer audience.', '6060000190', 'GAEX-BSA-AdvrtiseSup', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),

-- MARKETING EXPENSE
(21, 'MKT_PROMO', 'C021', 'Marketing Promotion Activities', 'This pertains to activities to motivate purchases without Advertising or Price Reductions to the Broad Consumer Target Audience. These includes samples and sponsorhips.', '6060000210', 'GAEX-BSA-MarketPromo', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),

-- INSURANCE
(22, 'INS_PROP', 'C022', 'Insurance - Property', 'This pertains to property insurance premiums paid to cover company assets.', '6020000050', 'GAEX-Insurance-Prop', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),
(23, 'INS_GROUP_LIFE', 'C023', 'Group Life Insurance', 'This pertains to payment to insurance company.', '6020000000', 'GAEX-Grp Life Insur', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),
(24, 'INS_VEHICLE', 'C024', 'Insurance - Vehicle', 'This pertains to insurance premiums for company vehicles.', '6020000060', 'GAEX-Insurance-Vhcle', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),

-- MEALS AND TRANSPORTATION
(25, 'MEALS_TRANSPO', 'C025', 'Meals and Transportation', 'This pertains to payment to site visit/meeting meals, travel, transportation, and toll expenses.', '6035100050', 'GAEX-Meals & Transpo', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),

-- COMMUNICATION
(26, 'COMM_EXP', 'C026', 'Communication', 'This pertaines to any communication expense.', '6040000040', 'GAEX-Tel expense', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),

-- MISCELLANEOUS
(27, 'MISC_EXP', 'C027', 'Miscellaneous Expense', 'This pertains to miscellaneous expenses, including medical services and other uncategorized costs with minimal amounts.', '6097000030', 'GAEX-Miscellaneous', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),
(28, 'MEDICAL_SERV', 'C028', 'Medical Services', 'This pertains to medical services expenses.', '6097000030', 'GAEX-Medical Serv', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),
(29, 'LEGAL_FEES', 'C029', 'Legal Expense', 'This pertains to legal fees incurred for professional legal services and representation.', '6015100030', 'GAEX-Legal Fees', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),

-- STORE AND OFFICE SUPPLIES
(30, 'OFFICE_SUPPLIES', 'C030', 'Supplies Used', 'This pertains to payment of office supplies.', '6070000010', 'GAEX-Office Supplies', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),

-- REPRESENTATION AND ENTERTAINMENT
(31, 'REP_ENTERTAIN', 'C031', 'Representation and entertainment', 'This pertains to Company Activities, entertainment and recreational activities.', '6085100000', 'GAEX-Representation', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),

-- SANITARY
(32, 'SANITARY', 'C032', 'Sanitary', 'This pertains to cleaning and housekeeping expenses for maintaining office cleanliness.', '6040000000', 'GAEX-Clean&Housekeep', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),

-- RESEARCH AND DEVELOPMENT
(33, 'RND', 'C033', 'Research and development', 'This pertains to Quality Assurance and R&D expenses.', '6095100010', 'GAEX-Business Dev\'t', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),

-- COMMISSION
(34, 'COMMISSION', 'C034', 'Commission Expense', 'This pertains to sales commission.', '6097000000', 'GAEX-Commission Exp', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),

-- BANK CHARGES
(35, 'BANK_CHARGES', 'C035', 'Bank Charges', 'This pertains to bank charges incurred for financial transactions and services.', '6097000110', 'GAEX-Bank Charges', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),

-- DUES AND SUBSCRIPTIONS
(36, 'DUES_SUBS', 'C036', 'Dues and subscriptions', 'This pertains to subsciption payment of softwares, websites, cloud service, Microsoft and etc.', '6070000040', 'GAEX-Subs Expense', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),

-- MEETING AND CONFERENCE
(37, 'MEETING_CONF', 'C037', 'Meeting and Conference', 'This pertains to meeting expenses including venue, materials, and refreshments.', '6075100000', 'GAEX-Meetings', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38'),

-- TAXES AND LICENSES
(38, 'TAXES_LICENSE', 'C038', 'Taxes and licenses', 'This pertains to payment to government such as business permit, VAT Registration, DST, Vehicle registration, Custom duties, other related government licenses.', '6030000000', 'GAEX-Taxes&License', 1, '2025-08-14 14:19:38', '2025-08-14 14:19:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_category_code` (`category_code`),
  ADD UNIQUE KEY `uniq_category_id` (`category_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
