<?php
/**
 * Save Withholding Tax
 * Handles creation of new withholding tax entries
 */

session_start();
date_default_timezone_set('Asia/Manila');

// Include database connection
require_once '../conn/db.php';

// Set JSON header
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'tax' => null
];

try {
    // Check if user is logged in
    if (!isset($_SESSION['userid']) || !isset($_SESSION['username'])) {
        $response['message'] = 'Unauthorized access. Please log in.';
        echo json_encode($response);
        exit;
    }

    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
        $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $response['message'] = 'Invalid security token. Please refresh the page and try again.';
        echo json_encode($response);
        exit;
    }

    // Validate required fields
    if (empty($_POST['tax_code']) || empty($_POST['tax_name']) || 
        !isset($_POST['tax_rate']) || empty($_POST['tax_type'])) {
        $response['message'] = 'All required fields must be filled.';
        echo json_encode($response);
        exit;
    }

    // Sanitize and validate inputs
    $taxCode = strtoupper(trim($_POST['tax_code']));
    $taxName = trim($_POST['tax_name']);
    $taxRate = floatval($_POST['tax_rate']);
    $taxType = trim($_POST['tax_type']);
    $description = isset($_POST['description']) ? trim($_POST['description']) : null;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $createdBy = (int)$_SESSION['userid'];

    // Validate tax code format (only uppercase letters and numbers)
    if (!preg_match('/^[A-Z0-9]+$/', $taxCode)) {
        $response['message'] = 'Tax code must contain only uppercase letters and numbers.';
        echo json_encode($response);
        exit;
    }

    // Validate tax rate
    if ($taxRate < 0 || $taxRate > 100) {
        $response['message'] = 'Tax rate must be between 0 and 100.';
        echo json_encode($response);
        exit;
    }

    // Validate tax type
    if (!in_array($taxType, ['withholding', 'final'])) {
        $response['message'] = 'Invalid tax type selected.';
        echo json_encode($response);
        exit;
    }

    // Check database connection
    if (!isset($mysqlconn) || !$mysqlconn) {
        $response['message'] = 'Database connection failed.';
        echo json_encode($response);
        exit;
    }

    // Check if tax code already exists
    $checkStmt = mysqli_prepare($mysqlconn, 
        "SELECT id FROM withholding_taxes WHERE tax_code = ?");
    
    if (!$checkStmt) {
        $response['message'] = 'Database error: ' . mysqli_error($mysqlconn);
        echo json_encode($response);
        exit;
    }

    mysqli_stmt_bind_param($checkStmt, 's', $taxCode);
    mysqli_stmt_execute($checkStmt);
    mysqli_stmt_store_result($checkStmt);

    if (mysqli_stmt_num_rows($checkStmt) > 0) {
        $response['message'] = 'Tax code "' . htmlspecialchars($taxCode) . '" already exists.';
        mysqli_stmt_close($checkStmt);
        echo json_encode($response);
        exit;
    }
    mysqli_stmt_close($checkStmt);

    // Insert new withholding tax
    $insertStmt = mysqli_prepare($mysqlconn, 
        "INSERT INTO withholding_taxes 
        (tax_code, tax_name, tax_rate, tax_type, description, is_active, created_by, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");

    if (!$insertStmt) {
        $response['message'] = 'Database error: ' . mysqli_error($mysqlconn);
        echo json_encode($response);
        exit;
    }

    mysqli_stmt_bind_param($insertStmt, 'ssdssii', 
        $taxCode, $taxName, $taxRate, $taxType, $description, $isActive, $createdBy);

    if (mysqli_stmt_execute($insertStmt)) {
        $insertId = mysqli_insert_id($mysqlconn);
        
        $response['success'] = true;
        $response['message'] = 'Withholding tax added successfully.';
        $response['tax'] = [
            'id' => $insertId,
            'tax_code' => $taxCode,
            'tax_name' => $taxName,
            'tax_rate' => $taxRate,
            'tax_type' => $taxType,
            'description' => $description,
            'is_active' => $isActive
        ];

        // Log the action
        error_log("New withholding tax created: {$taxCode} by user {$_SESSION['username']}");
    } else {
        $response['message'] = 'Failed to save withholding tax: ' . mysqli_error($mysqlconn);
    }

    mysqli_stmt_close($insertStmt);

} catch (Exception $e) {
    $response['message'] = 'An error occurred: ' . $e->getMessage();
    error_log("Error in save_withholding_tax.php: " . $e->getMessage());
}

// Output JSON response
echo json_encode($response);
exit;

