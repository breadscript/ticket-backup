<?php
// Get Attachments API Endpoint
// Returns existing attachments for a given doc_number

// Start output buffering to capture any unwanted output
ob_start();

session_start();
require_once "blocks/inc.resource.php";
require_once "attachment_handler.php";

// Get any output that might have been generated and discard it
$unwantedOutput = ob_get_clean();

// Set JSON response header
header('Content-Type: application/json; charset=utf-8');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Basic input validation
$docNumber = filter_input(INPUT_POST, 'doc_number', FILTER_SANITIZE_STRING);
$attachmentType = filter_input(INPUT_POST, 'attachment_type', FILTER_SANITIZE_STRING);
$itemId = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);

if (!$docNumber) {
    echo json_encode(['success' => false, 'message' => 'Document number is required'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validate attachment type if provided
if ($attachmentType && !in_array($attachmentType, ['item_attachment', 'supporting_document'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid attachment type'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $attachmentHandler = new AttachmentHandler($mysqlconn);
    
    // Get attachments (correct parameter order: docNumber, attachmentType, itemId)
    $result = $attachmentHandler->getAttachments($docNumber, $attachmentType, $itemId);
    
    if (!$result['success']) {
        $response = ['success' => false, 'message' => $result['error'] ?? 'Failed to retrieve attachments'];
    } else {
        $attachments = $result['attachments'];
        
        // Format attachments for JSON response
        $formattedAttachments = [];
        foreach ($attachments as $attachment) {
            $formattedAttachments[] = [
                'id' => $attachment['id'],
                'original_filename' => $attachment['original_filename'],
                'file_size' => $attachment['file_size'],
                'mime_type' => $attachment['mime_type'],
                'file_extension' => $attachment['file_extension'],
                'uploaded_at' => $attachment['uploaded_at'],
                'attachment_type' => $attachment['attachment_type'],
                'item_id' => $attachment['item_id']
            ];
        }
        
        $response = [
            'success' => true,
            'attachments' => $formattedAttachments,
            'count' => count($formattedAttachments)
        ];
    }
    
} catch (Exception $e) {
    error_log('Get attachments error: ' . $e->getMessage());
    $response = ['success' => false, 'message' => 'Server error occurred'];
}

// Ensure clean JSON output
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>
