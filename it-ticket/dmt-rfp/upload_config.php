<?php
/**
 * Upload Configuration for Financial Request System
 * Defines paths and settings for file uploads
 */

// Base upload directory (relative to dmt-rfp folder)
define('UPLOAD_BASE_DIR', 'uploads/');

// Financial requests upload directories
define('FINANCIAL_REQUESTS_DIR', UPLOAD_BASE_DIR . 'financial_requests/');
define('ATTACHMENTS_DIR', FINANCIAL_REQUESTS_DIR . 'attachments/');
define('SUPPORTING_DOCS_DIR', FINANCIAL_REQUESTS_DIR . 'supporting_docs/');

// File type configurations
define('ALLOWED_ATTACHMENT_TYPES', [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif'
]);

// File size limits (in bytes)
define('MAX_FILE_SIZE', 200 * 1024 * 1024); // 200MB
define('MAX_TOTAL_SIZE', 50 * 1024 * 1024); // 50MB total

// Helper function to generate unique filename
function generateUniqueFilename($originalName, $directory) {
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $timestamp = date('Ymd_His');
    $random = bin2hex(random_bytes(8));
    return $timestamp . '_' . $random . '.' . $extension;
}

// Helper function to get full upload path
function getUploadPath($filename, $type = 'attachment') {
    switch ($type) {
        case 'attachment':
            return ATTACHMENTS_DIR . $filename;
        case 'supporting':
            return SUPPORTING_DOCS_DIR . $filename;
        default:
            return ATTACHMENTS_DIR . $filename;
    }
}

// Helper function to create upload directories if they don't exist
function ensureUploadDirectories() {
    $dirs = [UPLOAD_BASE_DIR, FINANCIAL_REQUESTS_DIR, ATTACHMENTS_DIR, SUPPORTING_DOCS_DIR];
    
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

// Helper function to validate file type
function isValidFileType($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return array_key_exists($extension, ALLOWED_ATTACHMENT_TYPES);
}

// Helper function to validate file size
function isValidFileSize($filesize) {
    return $filesize <= MAX_FILE_SIZE;
}

// Helper function to get file icon class
function getFileIconClass($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    switch ($extension) {
        case 'pdf':
            return 'fa-file-pdf-o';
        case 'doc':
        case 'docx':
            return 'fa-file-word-o';
        case 'xls':
        case 'xlsx':
            return 'fa-file-excel-o';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
            return 'fa-file-image-o';
        default:
            return 'fa-file';
    }
}
?>
