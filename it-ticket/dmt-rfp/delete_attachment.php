<?php
/**
 * Delete attachment endpoint
 */

// Start output buffering to capture any unwanted output
ob_start();

// Start session for authentication
session_start();

// Include database connection and attachment handler
require_once "blocks/inc.resource.php";
require_once "attachment_handler.php";

// Get any output that might have been generated and discard it
$unwantedOutput = ob_get_clean();

// Set JSON response header
header('Content-Type: application/json; charset=utf-8');

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Get form data (not JSON)
$attachmentId = isset($_POST['attachment_id']) ? (int)$_POST['attachment_id'] : 0;

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($attachmentId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing or invalid attachment_id'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Initialize attachment handler
    $attachmentHandler = new AttachmentHandler($mysqlconn);
    
    // Delete the attachment
    $result = $attachmentHandler->deleteAttachment($attachmentId);
    
    if ($result['success']) {
        $response = ['success' => true, 'message' => 'Attachment deleted successfully'];
    } else {
        http_response_code(500);
        $response = ['success' => false, 'message' => $result['error']];
    }
    
} catch (Exception $e) {
    error_log('Delete attachment error: ' . $e->getMessage());
    http_response_code(500);
    $response = ['success' => false, 'message' => 'An error occurred while deleting the attachment'];
}

// Ensure clean JSON output
echo json_encode($response, JSON_UNESCAPED_UNICODE);

exit;
?>
