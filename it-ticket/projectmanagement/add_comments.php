<?php
include('../conn/db.php');
session_start();

    // Require PHPMailer
    require_once '../phpmailer/src/PHPMailer.php';
    require_once '../phpmailer/src/SMTP.php';
    require_once '../phpmailer/src/Exception.php';
    require_once('task_notification.php');


    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;

require_once('email_utils.php');

function addComment($mysqlconn, $taskId, $userId, $message, $subject = '', $parentId = null, $files = null) {
    $filePath = ''; // Single string to store all file paths
    
    // Handle multiple files
    if ($files) {
        $uploadDir = __DIR__ . '/uploadspm/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filePaths = []; // Temporary array to collect paths
        foreach ($files['name'] as $key => $fileName) {
            if ($files['error'][$key] === UPLOAD_ERR_OK) {
                // Determine file type directory
                $fileType = mime_content_type($files['tmp_name'][$key]);
                if (strpos($fileType, 'image/') === 0) {
                    $typeDir = 'images/';
                } else if (strpos($fileType, 'application/') === 0) {
                    $typeDir = 'docfiles/';
                } else {
                    $typeDir = '';
                }

                // Create type-specific directory if it doesn't exist
                if ($typeDir && !is_dir($uploadDir . $typeDir)) {
                    if (!mkdir($uploadDir . $typeDir, 0755, true)) {
                        continue; // Skip this file if directory creation fails
                    }
                }

                $safeFileName = basename($fileName);
                $filePath = $uploadDir . $typeDir . $safeFileName;
                
                if (move_uploaded_file($files['tmp_name'][$key], $filePath)) {
                    // Store relative path
                    $filePaths[] = 'uploadspm/' . $typeDir . $safeFileName;
                }
            }
        }
        
        // Join all file paths with a comma separator
        $filePath = !empty($filePaths) ? implode(',', $filePaths) : '';
    }

    // Skip if no message and no files
    if (empty($message) && empty($filePath)) {
        return ['status' => 'skip', 'message' => 'Empty message and no files'];
    }

    // Store original message for mention processing
    $originalMessage = $message; 
    
    // Normalize line endings before escaping
    $message = str_replace(["\r\n", "\r", "\n"], "\n", $message);
    $subject = mysqli_real_escape_string($mysqlconn, $subject);
    $message = mysqli_real_escape_string($mysqlconn, $message);
    $datetimecreated = date('Y-m-d H:i:s');
    $type = $parentId ? 'reply' : 'comment';

    $sql = "INSERT INTO pm_threadtb 
            (taskid, createdbyid, datetimecreated, subject, message, type, parent_id, file_data) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $mysqlconn->prepare($sql);
    if (!$stmt) {
        return ['status' => 'error', 'message' => 'Database error'];
    }

    $stmt->bind_param("iissssis", $taskId, $userId, $datetimecreated, $subject, $message, $type, $parentId, $filePath);
    
    if ($stmt->execute()) {
        // Trigger asynchronous email notifications
        triggerAsyncNotification('comment', $taskId, $originalMessage, $subject, $userId);
        
        return ['status' => 'success', 'message' => 'Comment added successfully'];
    } else {
        return ['status' => 'error', 'message' => 'Failed to add comment'];
    }
}

// Function to trigger asynchronous notifications
function triggerAsyncNotification($type, $taskId, $message, $subject, $userId, $parentId = null) {
    // Prepare the data for the async request
    $data = [
        'type' => $type,
        'taskId' => $taskId,
        'message' => $message,
        'subject' => $subject,
        'userId' => $userId
    ];
    
    if ($parentId) {
        $data['parentId'] = $parentId;
    }
    
    // Convert data to JSON
    $jsonData = json_encode($data);
    
    // Set up the curl request to the background processor
    $ch = curl_init($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . 
                    dirname($_SERVER['PHP_SELF']) . '/process_email_queue.php');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1); // Set a very short timeout - we don't need to wait for a response
    
    // Execute the request in the background
    curl_exec($ch);
    curl_close($ch);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $taskId = isset($_POST['taskId']) ? intval($_POST['taskId']) : 0;
    $userId = $_SESSION['userid'] ?? 0;
    $message = $_POST['message'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $parentId = isset($_POST['parent_comment_id']) ? intval($_POST['parent_comment_id']) : null;

    if ($userId === 0) {
        die('User not logged in');
    }

    $result = addComment(
        $mysqlconn, 
        $taskId, 
        $userId, 
        $message, 
        $subject, 
        $parentId, 
        !empty($_FILES['attachment']) ? $_FILES['attachment'] : null
    );

    if ($result['status'] === 'success') {
        header('Location: threadPage.php?taskId=' . $taskId);
        exit();
    } else {
        echo "Failed to add comment or upload files";
    }
}
?>