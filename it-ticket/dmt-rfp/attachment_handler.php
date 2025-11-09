<?php
/**
 * Financial Request Attachment Handler
 * Handles file uploads and database operations for financial request attachments
 */

// Include database connection
require_once "blocks/inc.resource.php";

class AttachmentHandler {
    private $mysqlconn;
    private $uploadDir = 'uploads/financial_requests/attachments/';
    private $maxFileSize = 200 * 1024 * 1024; // 200MB
    private $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif'];
    private $allowedMimeTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'image/jpeg',
        'image/png',
        'image/gif'
    ];

    public function __construct($mysqlconn) {
        $this->mysqlconn = $mysqlconn;
        
        // Create upload directory structure if it doesn't exist
        $dirs = [
            'uploads/',
            'uploads/financial_requests/',
            'uploads/financial_requests/attachments/',
            'uploads/financial_requests/supporting_docs/'
        ];
        
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    // private function debugLog($message, $data = null) {
    //     $debug_entry = [
    //         'timestamp' => date('Y-m-d H:i:s'),
    //         'class' => 'AttachmentHandler',
    //         'message' => $message,
    //         'data' => $data
    //     ];
    //     
    //     // Save to debug file
    //     $debug_file = 'attachment_debug_' . date('Y-m-d_H-i-s') . '.json';
    //     file_put_contents($debug_file, json_encode($debug_entry, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
    // }

    /**
     * Upload multiple files for a financial request
     */
    public function uploadAttachments($docNumber, $files, $attachmentType = 'item_attachment', $itemId = null) {
        // Set the correct upload directory based on attachment type
        if ($attachmentType === 'supporting_document') {
            $this->uploadDir = 'uploads/financial_requests/supporting_docs/';
        } else {
            $this->uploadDir = 'uploads/financial_requests/attachments/';
        }
        
        $uploadedFiles = [];
        $errors = [];

        // Debug: Log the files structure received
        // $this->debugLog('Files structure received in uploadAttachments', $files);
        
        // Handle both single file and multiple file uploads
        if (!is_array($files['name'])) {
            // Single file upload
            $files = [
                'name' => [$files['name']],
                'type' => [$files['type']],
                'tmp_name' => [$files['tmp_name']],
                'error' => [$files['error']],
                'size' => [$files['size']]
            ];
            // $this->debugLog('Converted single file to array structure');
        }

        // Log the number of files being processed
        // $this->debugLog('Processing ' . count($files['name']) . ' files for doc_number: ' . $docNumber . ', item_id: ' . $itemId);

        for ($i = 0; $i < count($files['name']); $i++) {
            // Skip empty file names
            if (empty($files['name'][$i])) {
                continue;
            }

            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $result = $this->uploadSingleFile(
                    $docNumber,
                    $files['name'][$i],
                    $files['type'][$i],
                    $files['tmp_name'][$i],
                    $files['size'][$i],
                    $attachmentType,
                    $itemId
                );

                if ($result['success']) {
                    $uploadedFiles[] = $result['data'];
                    // $this->debugLog('Successfully uploaded file: ' . $files['name'][$i]);
                } else {
                    $errors[] = $result['error'];
                    // $this->debugLog('Failed to upload file: ' . $files['name'][$i] . ' - ' . $result['error']);
                }
            } else {
                $errorMsg = "Upload error for file: " . $files['name'][$i] . " (Error code: " . $files['error'][$i] . ")";
                $errors[] = $errorMsg;
                // $this->debugLog($errorMsg);
            }
        }

        $success = count($uploadedFiles) > 0;
        // $this->debugLog('Upload result - Success: ' . ($success ? 'true' : 'false') . ', Files uploaded: ' . count($uploadedFiles) . ', Errors: ' . count($errors));

        return [
            'success' => $success,
            'uploaded_files' => $uploadedFiles,
            'errors' => $errors
        ];
    }

    /**
     * Upload a single file
     */
    private function uploadSingleFile($docNumber, $originalName, $mimeType, $tmpName, $fileSize, $attachmentType, $itemId = null) {
        try {
            
            // Validate file
            $validation = $this->validateFile($originalName, $mimeType, $fileSize);
            if (!$validation['valid']) {
                return ['success' => false, 'error' => $validation['error']];
            }

            // Generate unique filename
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $storedFilename = uniqid() . '_' . time() . '.' . $extension;
            $filePath = $this->uploadDir . $storedFilename;

            // Move uploaded file
            if (!move_uploaded_file($tmpName, $filePath)) {
                return ['success' => false, 'error' => 'Failed to move uploaded file'];
            }

            // Get current user
            $uploadedBy = $_SESSION['username'] ?? 'system';

            // Save to database
            $stmt = mysqli_prepare($this->mysqlconn, 
                "INSERT INTO financial_request_attachments 
                (doc_number, item_id, attachment_type, original_filename, stored_filename, file_path, file_size, mime_type, file_extension, uploaded_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            if (!$stmt) {
                unlink($filePath); // Clean up file
                return ['success' => false, 'error' => 'Database prepare failed: ' . mysqli_error($this->mysqlconn)];
            }

            mysqli_stmt_bind_param($stmt, 'sissssisss', 
                $docNumber, $itemId, $attachmentType, $originalName, $storedFilename, $filePath, $fileSize, $mimeType, $extension, $uploadedBy
            );

            if (!mysqli_stmt_execute($stmt)) {
                unlink($filePath); // Clean up file
                mysqli_stmt_close($stmt);
                return ['success' => false, 'error' => 'Database insert failed: ' . mysqli_error($this->mysqlconn)];
            }

            $attachmentId = mysqli_insert_id($this->mysqlconn);
            mysqli_stmt_close($stmt);

            return [
                'success' => true,
                'data' => [
                    'id' => $attachmentId,
                    'original_filename' => $originalName,
                    'stored_filename' => $storedFilename,
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'mime_type' => $mimeType,
                    'file_extension' => $extension
                ]
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Upload failed: ' . $e->getMessage()];
        }
    }

    /**
     * Validate uploaded file
     */
    private function validateFile($filename, $mimeType, $fileSize) {
        // Check file size
        if ($fileSize > $this->maxFileSize) {
            return ['valid' => false, 'error' => 'File size exceeds 200MB limit'];
        }

        // Check file extension
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            return ['valid' => false, 'error' => 'File type not allowed'];
        }

        // Check MIME type
        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            return ['valid' => false, 'error' => 'Invalid file type'];
        }

        return ['valid' => true];
    }

    /**
     * Get attachments for a document
     */
    public function getAttachments($docNumber, $attachmentType = null, $itemId = null) {
        $sql = "SELECT * FROM financial_request_attachments WHERE doc_number = ? AND is_active = 1";
        $params = [$docNumber];
        $types = 's';

        if ($attachmentType) {
            $sql .= " AND attachment_type = ?";
            $params[] = $attachmentType;
            $types .= 's';
        }

        if ($itemId) {
            $sql .= " AND item_id = ?";
            $params[] = $itemId;
            $types .= 'i';
        }

        $sql .= " ORDER BY uploaded_at ASC";

        $stmt = mysqli_prepare($this->mysqlconn, $sql);
        if (!$stmt) {
            return ['success' => false, 'error' => 'Database prepare failed: ' . mysqli_error($this->mysqlconn)];
        }

        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $attachments = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $attachments[] = $row;
        }

        mysqli_stmt_close($stmt);

        return ['success' => true, 'attachments' => $attachments];
    }

    /**
     * Delete an attachment
     */
    public function deleteAttachment($attachmentId) {
        try {
            // Get attachment info first
            $stmt = mysqli_prepare($this->mysqlconn, "SELECT file_path FROM financial_request_attachments WHERE id = ? AND is_active = 1");
            if (!$stmt) {
                return ['success' => false, 'error' => 'Database prepare failed'];
            }

            mysqli_stmt_bind_param($stmt, 'i', $attachmentId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $attachment = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if (!$attachment) {
                return ['success' => false, 'error' => 'Attachment not found'];
            }

            // Soft delete in database
            $stmt = mysqli_prepare($this->mysqlconn, "UPDATE financial_request_attachments SET is_active = 0, updated_at = NOW() WHERE id = ?");
            if (!$stmt) {
                return ['success' => false, 'error' => 'Database prepare failed'];
            }

            mysqli_stmt_bind_param($stmt, 'i', $attachmentId);
            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if (!$success) {
                return ['success' => false, 'error' => 'Failed to delete attachment from database'];
            }

            // Delete physical file
            if (file_exists($attachment['file_path'])) {
                unlink($attachment['file_path']);
            }

            return ['success' => true];

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Delete failed: ' . $e->getMessage()];
        }
    }

    /**
     * Delete all attachments for a document
     */
    public function deleteAllAttachments($docNumber) {
        try {
            // Get all attachments for the document
            $attachments = $this->getAttachments($docNumber);
            if (!$attachments['success']) {
                return $attachments;
            }

            $deletedCount = 0;
            foreach ($attachments['attachments'] as $attachment) {
                $result = $this->deleteAttachment($attachment['id']);
                if ($result['success']) {
                    $deletedCount++;
                }
            }

            return ['success' => true, 'deleted_count' => $deletedCount];

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Delete all failed: ' . $e->getMessage()];
        }
    }

    /**
     * Get file icon class based on extension
     */
    public static function getFileIcon($extension) {
        $extension = strtolower($extension);
        
        if (in_array($extension, ['pdf'])) {
            return 'fa-file-pdf-o';
        } elseif (in_array($extension, ['doc', 'docx'])) {
            return 'fa-file-word-o';
        } elseif (in_array($extension, ['xls', 'xlsx'])) {
            return 'fa-file-excel-o';
        } elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            return 'fa-file-image-o';
        } else {
            return 'fa-file';
        }
    }

    /**
     * Format file size
     */
    public static function formatFileSize($bytes) {
        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}
?>
