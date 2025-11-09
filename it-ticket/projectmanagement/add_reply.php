<?php
// add_reply.php
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

function addReply($mysqlconn, $taskId, $userId, $message, $subject = '', $parentId, $file = null) {
    $filePath = '';
    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        // Use absolute path instead of relative
        $uploadDir = __DIR__ . '/uploadspm/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Determine file type directory
        $fileType = mime_content_type($file['tmp_name']);
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
                return ['status' => 'error', 'message' => 'Failed to create type-specific directory'];
            }
        }

        $fileName = basename($file['name']);
        $filePath = $uploadDir . $typeDir . $fileName;
        
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['status' => 'error', 'message' => 'File upload failed'];
        }

        // Store relative path in database
        $filePath = 'uploadspm/' . $typeDir . $fileName;
    }

    // Skip empty messages without files
    if (empty($message) && empty($filePath)) {
        return ['status' => 'skip', 'message' => 'Empty message and no file'];
    }

    // Store original message for mention processing
    $originalMessage = $message;
    
    $subject = mysqli_real_escape_string($mysqlconn, $subject);
    $message = mysqli_real_escape_string($mysqlconn, $message);
    $datetimecreated = date('Y-m-d H:i:s');

    $sql = "INSERT INTO pm_threadtb 
            (taskid, createdbyid, datetimecreated, subject, message, type, parent_id, file_data) 
            VALUES (?, ?, ?, ?, ?, 'reply', ?, ?)";
    
    $stmt = $mysqlconn->prepare($sql);
    if (!$stmt) {
        return ['status' => 'error', 'message' => 'Database error'];
    }

    $stmt->bind_param("iisssss", $taskId, $userId, $datetimecreated, $subject, $message, $parentId, $filePath);
    
    if ($stmt->execute()) {
        // Trigger asynchronous email notifications
        triggerAsyncNotification('reply', $taskId, $originalMessage, $subject, $userId, $parentId);
        
        return ['status' => 'success', 'message' => 'Reply added successfully'];
    } else {
        return ['status' => 'error', 'message' => 'Failed to add reply'];
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

    if (!$parentId) {
        die('Parent comment ID is required');
    }

    $success = false;

    // Handle message with or without files
    if (!empty($message) || empty($_FILES['attachment']['name'][0])) {
        $result = addReply($mysqlconn, $taskId, $userId, $message, $subject, $parentId);
        $success = ($result['status'] === 'success');
    }

    // Handle multiple file uploads as separate replies
    if (!empty($_FILES['attachment']['name'][0])) {
        foreach ($_FILES['attachment']['name'] as $key => $value) {
            if ($_FILES['attachment']['error'][$key] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['attachment']['name'][$key],
                    'type' => $_FILES['attachment']['type'][$key],
                    'tmp_name' => $_FILES['attachment']['tmp_name'][$key],
                    'error' => $_FILES['attachment']['error'][$key],
                    'size' => $_FILES['attachment']['size'][$key],
                ];
                
                $result = addReply($mysqlconn, $taskId, $userId, '', $subject, $parentId, $file);
                if ($result['status'] === 'success') {
                    $success = true;
                }
            }
        }
    }

    if ($success) {
        header('Location: threadPage.php?taskId=' . $taskId);
        exit();
    } else {
        echo "Failed to add reply or upload files";
    }
}
?>