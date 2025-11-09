<?php
// This file processes email notifications in the background
require_once '../conn/db.php';
require_once '../phpmailer/src/PHPMailer.php';
require_once '../phpmailer/src/SMTP.php';
require_once '../phpmailer/src/Exception.php';
require_once('task_notification.php');
require_once('email_utils.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Get database connection
$mysqlconn = connectionDB();

// OPTIMIZED: Fast HTTP POST handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['data'])) {
    // Immediate response for speed
    http_response_code(200);
    echo json_encode(['status' => 'processing']);
    
    // Flush output to client immediately
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        ob_end_flush();
        flush();
    }
    
    // Now process email in background
    try {
        $emailData = json_decode(base64_decode($_POST['data']), true);
        
        if ($emailData && isset($emailData['taskId'])) {
            $emailSent = sendTaskUpdateNotification(
                $mysqlconn, 
                $emailData['taskId'], 
                $emailData['taskSubject'], 
                $emailData['taskAssignee'], 
                $emailData['taskUserid']
            );
            
            error_log($emailSent ? 
                "Task update email sent successfully for task ID: " . $emailData['taskId'] :
                "Failed to send task update email for task ID: " . $emailData['taskId']
            );
        }
    } catch (Exception $e) {
        error_log("Error processing task update email: " . $e->getMessage());
    }
    exit;
}
// Check if this is a JSON input request (your existing method)
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the notification data from the request (your existing code)
    $data = json_decode(file_get_contents('php://input'), true);

    if (!empty($data)) {
        try {
            // Process the notification based on the type (your existing functionality)
            switch ($data['type']) {
                case 'comment':
                    sendCommentNotification(
                        $mysqlconn, 
                        $data['taskId'], 
                        $data['subject'], 
                        $data['message'], 
                        $data['userId']
                    );
                    break;
                case 'reply':
                    sendReplyNotification(
                        $mysqlconn, 
                        $data['taskId'], 
                        $data['subject'], 
                        $data['message'], 
                        $data['userId'], 
                        $data['parentId']
                    );
                    break;
                case 'mention':
                    processMentions(
                        $mysqlconn, 
                        $data['message'], 
                        $data['taskId'], 
                        $data['subject'], 
                        $data['userId']
                    );
                    break;
                case 'task_update':
                    // Handle task update notifications
                    $emailSent = sendTaskUpdateNotification(
                        $mysqlconn, 
                        $data['taskId'], 
                        $data['subject'], 
                        $data['assignee'], 
                        $data['userId']
                    );
                    break;
                default:
                    echo json_encode(['status' => 'error', 'message' => 'Unknown notification type']);
                    exit;
            }
            
            // Return a success response
            echo json_encode(['status' => 'processing']);
            
        } catch (Exception $e) {
            error_log("Error processing notification: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No data provided']);
    }
}
// Handle file-based queue processing (fallback method)
else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['process_queue'])) {
    $queueDir = __DIR__ . '/email_queue/';
    
    // Check if queue directory exists
    if (!is_dir($queueDir)) {
        echo json_encode(['status' => 'info', 'message' => 'No queue directory found']);
        exit;
    }
    
    $processedCount = 0;
    $failedCount = 0;
    
    // Get all queue files
    $queueFiles = glob($queueDir . '*.json');
    
    if (empty($queueFiles)) {
        echo json_encode(['status' => 'info', 'message' => 'No emails in queue']);
        exit;
    }
    
    foreach ($queueFiles as $queueFile) {
        try {
            // Read queue file
            $emailData = json_decode(file_get_contents($queueFile), true);
            
            if (!$emailData) {
                unlink($queueFile); // Remove invalid file
                $failedCount++;
                continue;
            }
            
            // Send email notification
            $emailSent = sendTaskUpdateNotification(
                $mysqlconn, 
                $emailData['taskId'], 
                $emailData['taskSubject'], 
                $emailData['taskAssignee'], 
                $emailData['taskUserid']
            );
            
            if ($emailSent) {
                unlink($queueFile); // Remove processed file
                $processedCount++;
            } else {
                $failedCount++;
                
                // Remove old files (older than 1 hour)
                if ((time() - filemtime($queueFile)) > 3600) {
                    unlink($queueFile);
                }
            }
            
        } catch (Exception $e) {
            error_log("Error processing queue file " . basename($queueFile) . ": " . $e->getMessage());
            $failedCount++;
        }
    }
    
    echo json_encode([
        'status' => 'completed', 
        'processed' => $processedCount, 
        'failed' => $failedCount
    ]);
}
else {
    // Default response for invalid requests
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method or missing data']);
}

// Close database connection
if (isset($mysqlconn)) {
    mysqli_close($mysqlconn);
}
?>