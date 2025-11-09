-- Company master data schema
CREATE TABLE IF NOT EXISTS `company` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_name` VARCHAR(150) NOT NULL,
  `company_code` VARCHAR(50) NOT NULL,
  `company_id` VARCHAR(100) NOT NULL,
  `company_address` VARCHAR(255) NOT NULL,
  `company_tin` VARCHAR(50) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_company_code` (`company_code`),
  UNIQUE KEY `uniq_company_id` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


INSERT INTO `company` (
  `company_name`,
  `company_code`,
  `company_id`,
  `company_address`,
  `company_tin`
) VALUES
('Metro Pacific Agricultural Venture', 'MPAV', '10', 'N/A', 'N/A'),
('The Laguna Creamery Inc.', 'TLCI', '20', 'N/A', 'N/A'),
('Metro Pacific Fresh Farm', 'MPFF', '30', 'N/A', 'N/A'),
('Metro Pacific Dairy Farm', 'MPDF', '40', 'N/A', 'N/A'),
('Universal Harvester Dairy Farm Inc.', 'UHDFI', '50', 'N/A', 'N/A'),
('MPNAT', 'MPNAT', '60', 'N/A', 'N/A');


TLCI-IT	10
TLCI-FIN	20
TLCI-ACC	30
TLCI-LOG	40
TLCI-PR	50
TLCI-SALES	60
TLCI-ENGR	70

MPAV-IT	11
MPAV-FIN	21
MPAV-ACC	31
MPAV-LOG	41
MPAV-PR	51
MPAV-SALES	61
MPAV-ENGR	71

MPFF-IT	12
MPFF-FIN	22
MPFF-ACC	32
MPFF-LOG	42
MPFF-PR	52
MPFF-SALES	62
MPFF-ENGR	72

MPDF-IT	13
MPDF-FIN	23
MPDF-ACC	33
MPDF-LOG	43
MPDF-PR	53
MPDF-SALES	63
MPDF-ENGR	73

UHDFI-IT	14
UHDFI-FIN	24
UHDFI-ACC	34
UHDFI-LOG	44
UHDFI-PR	54
UHDFI-SALES	64
UHDFI-ENGR	74

MPNAT-IT	15
MPNAT-FIN	25
MPNAT-ACC	35
MPNAT-LOG	45
MPNAT-PR	55
MPNAT-SALES	65
MPNAT-ENGR	75

