<?php
// Database connection
require_once('../conn/db.php'); // Update this path to match your database connection file
$conn = connectionDB(); // Add this if your connection file returns a connection

session_start();

// Set header for JSON response
header('Content-Type: application/json');

// Enable error reporting but capture errors
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set up error handler to catch all errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

/**
 * Function to handle file uploads for tasks
 * 
 * @param object $conn Database connection
 * @param int $taskId Task ID
 * @param int $userId User ID
 * @param array $files Files array from $_FILES
 * @return array Result with status and file information
 */
function uploadFiles($conn, $taskId, $userId, $files) {
    try {
        if (!$conn) {
            throw new Exception('Database connection failed');
        }

        // Validate parameters
        if (empty($taskId) || empty($userId)) {
            throw new Exception('Missing required parameters: taskId or userId');
        }

        // Validate file upload
        if (!isset($files) || !is_array($files)) {
            throw new Exception('No files uploaded or invalid file data');
        }

        $uploadDir = __DIR__ . '/uploadspm/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception('Failed to create upload directory');
            }
        }

        $uploadedFiles = [];
        $filePaths = [];

        // Process each file
        foreach ($files['name'] as $key => $fileName) {
            if ($files['error'][$key] !== UPLOAD_ERR_OK) {
                continue;
            }

            // Determine file type directory
            $fileType = mime_content_type($files['tmp_name'][$key]);
            $typeDir = strpos($fileType, 'image/') === 0 ? 'images/' :
                      (strpos($fileType, 'application/') === 0 ? 'docfiles/' : 'others/');

            // Create type-specific directory
            $typePath = $uploadDir . $typeDir;
            if (!is_dir($typePath)) {
                if (!mkdir($typePath, 0755, true)) {
                    throw new Exception('Failed to create type directory: ' . $typeDir);
                }
            }

            // Generate safe filename
            $safeFileName = basename($fileName);
            $fileInfo = pathinfo($safeFileName);
            $originalName = $fileInfo['filename'];
            $extension = isset($fileInfo['extension']) ? '.' . $fileInfo['extension'] : '';
            
            // Handle duplicate filenames
            $counter = 1;
            while (file_exists($typePath . $safeFileName)) {
                $safeFileName = $originalName . '_' . $counter . $extension;
                $counter++;
            }

            // Move uploaded file
            if (!move_uploaded_file(
                $files['tmp_name'][$key], 
                $typePath . $safeFileName
            )) {
                throw new Exception('Failed to move uploaded file: ' . $fileName);
            }

            // Store relative path
            $dbFilePath = 'uploadspm/' . $typeDir . $safeFileName;
            $filePaths[] = $dbFilePath;
            $uploadedFiles[] = [
                'name' => $safeFileName,
                'path' => $dbFilePath
            ];
        }

        // Insert into database if files were uploaded
        if (!empty($filePaths)) {
            $filePathString = implode(',', $filePaths);
            
            $sql = "INSERT INTO pm_threadtb (taskid, createdbyid, datetimecreated, type, file_data) 
                    VALUES (?, ?, NOW(), 'file', ?)";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Database prepare failed: ' . $conn->error);
            }
            
            $stmt->bind_param("iis", $taskId, $userId, $filePathString);
            if (!$stmt->execute()) {
                throw new Exception('Database insert failed: ' . $stmt->error);
            }
            
            $stmt->close();
        }

        // Return success result
        return [
            'status' => 'success',
            'message' => 'Files uploaded successfully',
            'files' => $uploadedFiles
        ];

    } catch (Exception $e) {
        // Log error for debugging
        error_log("File upload error: " . $e->getMessage());
        
        // Return error result
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

// Handle direct script access for backward compatibility
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['taskId']) && isset($_POST['userId'])) {
    try {
        $taskId = intval($_POST['taskId']);
        $userId = intval($_POST['userId']);
        
        $result = uploadFiles($conn, $taskId, $userId, $_FILES['attachFile']);
        
        echo json_encode($result);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}

// Restore default error handler
restore_error_handler();
?>