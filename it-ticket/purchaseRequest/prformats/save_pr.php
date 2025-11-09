<?php
require_once 'db.php';
session_start();

// Set header for JSON response
header('Content-Type: application/json');

// Function to generate next sequential PR number
function generateNextPRNumber($mysqlconn) {
    // Get the last PR number from database
    $sql = "SELECT pr_num FROM purchase_req WHERE pr_num LIKE 'PR-IT-%' ORDER BY CAST(SUBSTRING(pr_num, 7) AS UNSIGNED) DESC LIMIT 1";
    $result = mysqli_query($mysqlconn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $last_pr = $row['pr_num'];
        
        // Extract the numeric part (after PR-IT-)
        if (preg_match('/PR-IT-(\d+)/', $last_pr, $matches)) {
            $last_number = intval($matches[1]);
            $next_number = $last_number + 1;
            return 'PR-IT-' . str_pad($next_number, 6, '0', STR_PAD_LEFT);
        }
    }
    
    // If no existing PR numbers or invalid format, start with PR-IT-241024
    return 'PR-IT-241024';
}

try {
    // Check if data was sent
    if (!isset($_POST['pr_data'])) {
        throw new Exception('No data received');
    }

    // Decode the JSON data
    $pr_data = json_decode($_POST['pr_data'], true);
    
    if (!$pr_data) {
        throw new Exception('Invalid data format');
    }

    // Validate required fields
    if (empty($pr_data['prNumber'])) {
        throw new Exception('PR Number is required');
    }

    if (empty($pr_data['date'])) {
        throw new Exception('Date is required');
    }

    if (empty($pr_data['requestedBy'])) {
        throw new Exception('Requested By is required');
    }

    // Get current user ID from session (assuming you have user session)
    $created_by_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Default to 1 if no session

    // Generate reference ID (you can modify this as needed)
    $ref_id = 'PR-' . date('YmdHis') . '-' . rand(1000, 9999);

    // Use user-entered PR number or generate sequential one
    $user_pr_number = trim($pr_data['prNumber']);
    if (empty($user_pr_number) || $user_pr_number === 'PR-IT-000000' || $user_pr_number === 'PR-IT-241024') {
        // Generate next sequential PR number if field is empty or has default value
        $pr_number = mysqli_real_escape_string($mysqlconn, generateNextPRNumber($mysqlconn));
    } else {
        // Use the user-entered PR number
        $pr_number = mysqli_real_escape_string($mysqlconn, $user_pr_number);
    }
    $date = mysqli_real_escape_string($mysqlconn, $pr_data['date']);
    $requested_by = mysqli_real_escape_string($mysqlconn, $pr_data['requestedBy']);
    $approved_by = mysqli_real_escape_string($mysqlconn, $pr_data['approvedBy'] ?? '');
    
    // Determine company code based on selected client
    $company_mapping = [
        'METRO PACIFIC FRESH FARMS INC' => 'MPFF',
        'METRO PACIFIC AGRO VENTURES INC.' => 'MPAV',
        'THE LAGUNA CREAMERY INC.' => 'TLCI',
        'UNIVERSAL HARVESTER DAIRY FARM INC.' => 'UHDFI',
        'METRO PACIFIC DAIRY FARMS, INC.' => 'MPDF',
        'METRO PACIFIC NOVA AGRO TECH, INC.' => 'MPNAT'
    ];
    
    $selected_client = $pr_data['client'] ?? '';
    $company = $company_mapping[$selected_client] ?? 'UHDFI'; // Default to UHDFI if not found

    // Generate file path for the PDF
    $company_folder_mapping = [
        'METRO PACIFIC FRESH FARMS INC' => 'mpff',
        'METRO PACIFIC AGRO VENTURES INC.' => 'mpav',
        'THE LAGUNA CREAMERY INC.' => 'tlci',
        'UNIVERSAL HARVESTER DAIRY FARM INC.' => 'uhdfi',
        'METRO PACIFIC DAIRY FARMS, INC.' => 'mpdf',
        'METRO PACIFIC NOVA AGRO TECH, INC.' => 'mpnat'
    ];
    
    $company_folder = $company_folder_mapping[$selected_client] ?? 'uhdfi';
    // Remove timestamp - just use PR number
    $file_path = 'pr_files/' . strtolower($company_folder) . '/' . $pr_number . '.html';

    // Insert main PR record with first item (if any) and file path
    if (!empty($pr_data['items'])) {
        $first_item = $pr_data['items'][0];
        $quantity = mysqli_real_escape_string($mysqlconn, $first_item['quantity'] ?? '');
        $unit = mysqli_real_escape_string($mysqlconn, $first_item['unit'] ?? '');
        $description = mysqli_real_escape_string($mysqlconn, $first_item['description'] ?? '');
        $specification = mysqli_real_escape_string($mysqlconn, $first_item['specification'] ?? '');
        $purpose = mysqli_real_escape_string($mysqlconn, $first_item['purpose'] ?? '');
        $budget = mysqli_real_escape_string($mysqlconn, $first_item['budget'] ?? '');
        
        $main_sql = "INSERT INTO purchase_req (
            ref_id, pr_num, datetimecreated, createdbyid, company, statusid, 
            date, requested_by, approved_by, quantity, unit, description, 
            specification, purpose, budget_allocation, file
        ) VALUES (
            '$ref_id', '$pr_number', NOW(), $created_by_id, '$company', 1,
            '$date', '$requested_by', '$approved_by', '$quantity', '$unit', '$description',
            '$specification', '$purpose', '$budget', '$file_path'
        )";
    } else {
        // No items, insert just the main PR record
        $main_sql = "INSERT INTO purchase_req (
            ref_id, pr_num, datetimecreated, createdbyid, company, statusid, 
            date, requested_by, approved_by, file
        ) VALUES (
            '$ref_id', '$pr_number', NOW(), $created_by_id, '$company', 1,
            '$date', '$requested_by', '$approved_by', '$file_path'
        )";
    }

    if (!mysqli_query($mysqlconn, $main_sql)) {
        throw new Exception('Error saving PR data: ' . mysqli_error($mysqlconn));
    }

    $pr_id = mysqli_insert_id($mysqlconn);

    // Insert additional items (starting from second item) if any
    if (!empty($pr_data['items']) && count($pr_data['items']) > 1) {
        for ($i = 1; $i < count($pr_data['items']); $i++) {
            $item = $pr_data['items'][$i];
            $quantity = mysqli_real_escape_string($mysqlconn, $item['quantity'] ?? '');
            $unit = mysqli_real_escape_string($mysqlconn, $item['unit'] ?? '');
            $description = mysqli_real_escape_string($mysqlconn, $item['description'] ?? '');
            $specification = mysqli_real_escape_string($mysqlconn, $item['specification'] ?? '');
            $purpose = mysqli_real_escape_string($mysqlconn, $item['purpose'] ?? '');
            $budget = mysqli_real_escape_string($mysqlconn, $item['budget'] ?? '');

            // Insert additional item record
            $item_sql = "INSERT INTO purchase_req (
                ref_id, pr_num, datetimecreated, createdbyid, company, statusid,
                date, requested_by, approved_by, quantity, unit, description, 
                specification, purpose, budget_allocation, file
            ) VALUES (
                '$ref_id', '$pr_number', NOW(), $created_by_id, '$company', 1,
                '$date', '$requested_by', '$approved_by', '$quantity', '$unit', '$description',
                '$specification', '$purpose', '$budget', '$file_path'
            )";

            if (!mysqli_query($mysqlconn, $item_sql)) {
                throw new Exception('Error saving additional item data: ' . mysqli_error($mysqlconn));
            }
        }
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Purchase Request saved successfully',
        'pr_id' => $pr_id,
        'ref_id' => $ref_id,
        'pr_number' => $pr_number,
        'file_path' => $file_path
    ]);

} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

mysqli_close($mysqlconn);
?> 