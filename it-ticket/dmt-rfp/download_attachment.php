<?php
/**
 * Download attachment with original filename
 */

date_default_timezone_set('Asia/Manila');
require_once "blocks/inc.resource.php";
require_once "attachment_handler.php";

// Check if attachment ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo 'Attachment ID is required';
    exit;
}

$attachmentId = (int)$_GET['id'];

try {
    // Get attachment details from database
    $stmt = mysqli_prepare($mysqlconn, "SELECT * FROM financial_request_attachments WHERE id = ? AND is_active = 1");
    if (!$stmt) {
        throw new Exception('Database prepare failed');
    }

    mysqli_stmt_bind_param($stmt, 'i', $attachmentId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $attachment = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$attachment) {
        http_response_code(404);
        echo 'Attachment not found';
        exit;
    }

    // Get file info
    $originalFilename = $attachment['original_filename'];
    $fileSize = $attachment['file_size'];
    $mimeType = $attachment['mime_type'];
    $fileExtension = strtolower($attachment['file_extension'] ?? pathinfo($originalFilename, PATHINFO_EXTENSION));
    
    // Resolve file path - handle both relative and absolute paths
    $filePath = $attachment['file_path'];
    
    // If the path is relative, make it absolute from the script directory
    if (!file_exists($filePath)) {
        // Try relative to script directory
        $relativePath = __DIR__ . '/' . $filePath;
        if (file_exists($relativePath)) {
            $filePath = $relativePath;
        } else {
            // Try with uploads directory
            $uploadsPath = __DIR__ . '/uploads/' . basename($filePath);
            if (file_exists($uploadsPath)) {
                $filePath = $uploadsPath;
            }
        }
    }
    
    // Check if file exists after path resolution
    if (!file_exists($filePath)) {
        http_response_code(404);
        echo 'File not found on server. Path: ' . htmlspecialchars($filePath);
        exit;
    }

    // Security check: ensure the file path is within allowed directories
    $allowedPath = realpath(__DIR__ . '/uploads/');
    $realFilePath = realpath($filePath);
    
    if (!$realFilePath || strpos($realFilePath, $allowedPath) !== 0) {
        http_response_code(403);
        echo 'Access denied';
        exit;
    }

    // Validate and set proper MIME type
    if (empty($mimeType) || $mimeType === 'application/octet-stream') {
        // Set MIME type based on file extension
        switch ($fileExtension) {
            case 'pdf':
                $mimeType = 'application/pdf';
                break;
            case 'doc':
                $mimeType = 'application/msword';
                break;
            case 'docx':
                $mimeType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                break;
            case 'xls':
                $mimeType = 'application/vnd.ms-excel';
                break;
            case 'xlsx':
                $mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                break;
            case 'jpg':
            case 'jpeg':
                $mimeType = 'image/jpeg';
                break;
            case 'png':
                $mimeType = 'image/png';
                break;
            case 'gif':
                $mimeType = 'image/gif';
                break;
            default:
                $mimeType = 'application/octet-stream';
        }
    }

    // Get actual file size
    $actualFileSize = filesize($filePath);
    if ($actualFileSize === false) {
        http_response_code(500);
        echo 'Unable to determine file size';
        exit;
    }

    // Set headers for download/viewing
    header('Content-Type: ' . $mimeType);
    
    // For PDFs, allow inline viewing; for other files, force download
    if ($fileExtension === 'pdf') {
        header('Content-Disposition: inline; filename="' . $originalFilename . '"');
    } else {
        header('Content-Disposition: attachment; filename="' . $originalFilename . '"');
    }
    
    header('Content-Length: ' . $actualFileSize);
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Additional headers for better browser compatibility
    header('Accept-Ranges: bytes');
    header('X-Content-Type-Options: nosniff');

    // Clear any output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Output the file
    readfile($filePath);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo 'Error: ' . $e->getMessage();
    exit;
}
?>
