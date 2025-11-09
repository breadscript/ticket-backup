<?php
// send_email_http.php
// This script handles email sending via HTTP request

// Prevent browser caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require('../conn/db.php');
require_once '../phpmailer/src/PHPMailer.php';
require_once '../phpmailer/src/SMTP.php';
require_once '../phpmailer/src/Exception.php';
require_once('email_utils.php');
require_once('task_notification.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Check if data is provided
if (!isset($_POST['data'])) {
    http_response_code(400);
    echo "No data provided";
    exit;
}

try {
    // Decode the data
    $emailData = json_decode(base64_decode($_POST['data']), true);
    
    if (!$emailData) {
        http_response_code(400);
        echo "Invalid data format";
        exit;
    }
    
    $conn = connectionDB();
    
    // Extract data
    $taskIdUp = $emailData['taskId'];
    $taskSubjectUp = $emailData['taskSubject'];
    $taskAssigneeUp = $emailData['taskAssignee'];
    $taskUseridUp = $emailData['taskUserid'];
    
    // Send email notification
    $emailSent = sendTaskUpdateNotification($conn, $taskIdUp, $taskSubjectUp, $taskAssigneeUp, $taskUseridUp);
    
    if ($emailSent) {
        error_log("HTTP email sent successfully for task ID: " . $taskIdUp);
        echo "Email sent successfully";
    } else {
        error_log("Failed to send HTTP email for task ID: " . $taskIdUp);
        http_response_code(500);
        echo "Failed to send email";
    }
    
    // Close database connection
    mysqli_close($conn);
    
} catch (Exception $e) {
    error_log("HTTP email script error: " . $e->getMessage());
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
?>