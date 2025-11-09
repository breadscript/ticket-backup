<?php
// Prevent any output before JSON
ob_clean();
error_reporting(0);
ini_set('display_errors', 0);

require_once 'db.php';

// Set header for JSON response
header('Content-Type: application/json');

try {
    // Get database connection
    $mysqlconn = connectionDB();
    
    if (!$mysqlconn) {
        throw new Exception("Database connection failed");
    }

    // Function to generate next sequential PR number based on company
    function generateNextPRNumberByCompany($mysqlconn, $company = null) {
        // Company mapping with their PR number formats and patterns
        $company_config = [
            'METRO PACIFIC FRESH FARMS INC' => [
                'code' => 'MPFF',
                'format' => '2025%', // 2025 followed by 7 digits
                'pattern' => '/^2025(\d{7})$/',
                'next_format' => function($num) { return '2025' . str_pad($num, 7, '0', STR_PAD_LEFT); },
                'start_number' => 1 // Starting from 20250000001
            ],
            'METRO PACIFIC AGRO VENTURES INC.' => [
                'code' => 'MPAV',
                'format' => 'IT-MPAV-%', // IT-MPAV- followed by 6 digits
                'pattern' => '/^IT-MPAV-(\d{6})$/',
                'next_format' => function($num) { return 'IT-MPAV-' . str_pad($num, 6, '0', STR_PAD_LEFT); },
                'start_number' => 1
            ],
            'THE LAGUNA CREAMERY INC.' => [
                'code' => 'TLCI',
                'format' => '5%', // 5 followed by 9 digits
                'pattern' => '/^5(\d{9})$/',
                'next_format' => function($num) { return '5' . str_pad($num, 9, '0', STR_PAD_LEFT); },
                'start_number' => 1 // Starting from 5000000001
            ],
            'UNIVERSAL HARVESTER DAIRY FARM INC.' => [
                'code' => 'UHDFI',
                'format' => 'PR-IT-%', // PR-IT- followed by 6 digits
                'pattern' => '/^PR-IT-(\d{6})$/',
                'next_format' => function($num) { return 'PR-IT-' . str_pad($num, 6, '0', STR_PAD_LEFT); },
                'start_number' => 1
            ],
            'METRO PACIFIC DAIRY FARMS, INC.' => [
                'code' => 'MPDF',
                'format' => 'IT-MPDF-%', // IT-MPDF- followed by 5 digits
                'pattern' => '/^IT-MPDF-(\d{5})$/',
                'next_format' => function($num) { return 'IT-MPDF-' . str_pad($num, 5, '0', STR_PAD_LEFT); },
                'start_number' => 1
            ],
            'METRO PACIFIC NOVA AGRO TECH, INC.' => [
                'code' => 'MPNAT',
                'format' => 'IT-MPNAT-%', // IT-MPNAT- followed by 5 digits
                'pattern' => '/^IT-MPNAT-(\d{5})$/',
                'next_format' => function($num) { return 'IT-MPNAT-' . str_pad($num, 5, '0', STR_PAD_LEFT); },
                'start_number' => 1
            ]
        ];
        
        // Default to UHDFI if no company specified
        $config = $company ? ($company_config[$company] ?? $company_config['UNIVERSAL HARVESTER DAIRY FARM INC.']) : $company_config['UNIVERSAL HARVESTER DAIRY FARM INC.'];
        
        // Get the last PR number from database for this company's format
        // First try to get by company code
        $sql = "SELECT pr_num FROM purchase_req WHERE pr_num LIKE '" . $config['format'] . "' ORDER BY pr_num DESC LIMIT 1";
        $result = mysqli_query($mysqlconn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $last_pr = $row['pr_num'];
            
            // Extract the numeric part using the company's pattern
            if (preg_match($config['pattern'], $last_pr, $matches)) {
                $last_number = intval($matches[1]);
                $next_number = $last_number + 1;
                return $config['next_format']($next_number);
            }
        }
        
        // If no existing PR numbers or invalid format, start with the company's start number
        return $config['next_format']($config['start_number']);
    }

    // Get company from POST data
    $company = isset($_POST['company']) ? $_POST['company'] : null;
    
    // Generate and return the next PR number
    $next_pr_number = generateNextPRNumberByCompany($mysqlconn, $company);
    
    echo json_encode([
        'success' => true,
        'next_pr_number' => $next_pr_number,
        'company' => $company
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'next_pr_number' => 'PR-IT-000001' // Fallback
    ]);
} catch (Error $e) {
    echo json_encode([
        'success' => false,
        'message' => 'PHP Error: ' . $e->getMessage(),
        'next_pr_number' => 'PR-IT-000001' // Fallback
    ]);
}

if (isset($mysqlconn)) {
    mysqli_close($mysqlconn);
}
?> 