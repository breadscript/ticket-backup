<?php
// Asynchronous Financial Request Email Processor
header('Content-Type: application/json');

require_once __DIR__ . '/../conn/db.php';
require_once __DIR__ . '/task_notification.php';

// Handle both JSON and form data
$docNumber = '';
$triggerNotifications = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log all POST data
    error_log("DEBUG: POST data received: " . print_r($_POST, true));
    
    // Check if it's form data (from JavaScript fetch)
    if (isset($_POST['doc_number']) && isset($_POST['trigger_notifications'])) {
        $docNumber = (string)$_POST['doc_number'];
        $triggerNotifications = true;
        
        error_log("DEBUG: Processing notification request for doc: $docNumber");
        
        // Check if we have enhanced notification data
        if (isset($_POST['request_type'])) {
            $docType = (string)$_POST['request_type'];
            error_log("DEBUG: Request type: $docType");
        }
        if (isset($_POST['decider_ident'])) {
            $decider = (string)$_POST['decider_ident'];
            error_log("DEBUG: Decider: $decider");
        }
        if (isset($_POST['sequence'])) {
            $sequence = (int)$_POST['sequence'];
            error_log("DEBUG: Sequence: $sequence");
        }
        if (isset($_POST['action'])) {
            $event = (string)$_POST['action'];
            error_log("DEBUG: Action: $event");
        }
    } else {
        // Handle JSON data (original format)
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (is_array($data)) {
            $docType = isset($data['doc_type']) ? (string)$data['doc_type'] : '';
            $docNumber = isset($data['doc_number']) ? (string)$data['doc_number'] : '';
            $event = isset($data['event']) ? (string)$data['event'] : '';
            $sequence = isset($data['sequence']) ? (int)$data['sequence'] : null;
            $decider = isset($data['decider']) ? (string)$data['decider'] : null;
            $remarks = isset($data['remarks']) ? (string)$data['remarks'] : null;
        }
    }
}

if ($docNumber === '') {
    echo json_encode(['status' => 'ignored', 'reason' => 'missing doc_number']);
    exit;
}

$conn = connectionDB();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'reason' => 'db']);
    exit;
}

if ($triggerNotifications) {
    // Use enhanced data if available, otherwise fallback to database lookup
    if (!isset($docType) || !isset($decider) || !isset($event) || !isset($sequence)) {
        // Get document details from database
        $stmt = mysqli_prepare($conn, "SELECT request_type FROM financial_requests WHERE doc_number = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $docNumber);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                if (!isset($docType)) {
                    $docType = $row['request_type'];
                }
                
                // Get requestor info from session or database if not provided
                if (!isset($decider)) {
                    session_start();
                    $decider = '';
                    if (isset($_SESSION['userfirstname']) || isset($_SESSION['userlastname'])) {
                        $decider = trim((string)($_SESSION['userfirstname'] ?? '').' '.(string)($_SESSION['userlastname'] ?? ''));
                    } elseif (isset($_SESSION['username'])) {
                        $decider = (string)$_SESSION['username'];
                    } elseif (isset($_SESSION['userid'])) {
                        $decider = (string)$_SESSION['userid'];
                    } else {
                        $decider = 'Requestor';
                    }
                }
                
                // Set defaults if not provided
                if (!isset($event)) {
                    $event = 'SUBMIT';
                }
                if (!isset($sequence)) {
                    $sequence = 2;
                }
            } else {
                error_log("DEBUG: No financial request found for doc_number: $docNumber");
                mysqli_stmt_close($stmt);
                echo json_encode(['status' => 'error', 'reason' => 'document_not_found']);
                exit;
            }
            mysqli_stmt_close($stmt);
        } else {
            error_log("DEBUG: Failed to prepare statement for doc_number: $docNumber");
            echo json_encode(['status' => 'error', 'reason' => 'database_error']);
            exit;
        }
    }
    
    // Debug logging
    error_log("DEBUG: Triggering email notification for doc: $docNumber, type: $docType, decider: $decider, event: $event, sequence: $sequence");
    
    // Check if the notification function exists
    if (function_exists('sendFinancialRequestNotification')) {
        error_log("DEBUG: sendFinancialRequestNotification function exists, calling it");
        // Fire the email notification
        $result = @sendFinancialRequestNotification($conn, $docType, $docNumber, $event, $sequence, $decider, null);
        
        // Debug logging
        error_log("DEBUG: Email notification result: " . ($result ? 'success' : 'failed'));
    } else {
        error_log("DEBUG: sendFinancialRequestNotification function does not exist!");
        $result = false;
    }
} else {
    // Original JSON format handling
    if (isset($docType) && isset($event)) {
        @sendFinancialRequestNotification($conn, $docType, $docNumber, $event, $sequence, $decider, ['remarks' => $remarks]);
    }
}

@mysqli_close($conn);
echo json_encode(['status' => 'processing']);
exit;
?>


