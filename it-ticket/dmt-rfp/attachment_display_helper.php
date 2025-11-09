<?php
/**
 * Helper functions for displaying attachments in forms
 */

require_once "attachment_handler.php";

/**
 * Helper function to format file size
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Standalone function to display attachments (for backward compatibility)
 */
function displayAttachments($docNumber, $itemId = null, $attachmentType = null, $isEditable = false) {
    global $mysqlconn;
    
    if (!$mysqlconn) {
        echo '<div class="text-muted">Database connection not available</div>';
        return;
    }
    
    $attachmentHandler = new AttachmentHandler($mysqlconn);
    $result = $attachmentHandler->getAttachments($docNumber, $attachmentType, $itemId);
    
    if (!$result['success'] || empty($result['attachments'])) {
        echo '<div class="modern-attachment-container" style="max-height: 80px; overflow-y: auto; border: 1px solid #e0e0e0; padding: 20px; border-radius: 8px; background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center;">';
        echo '<div style="color: #6c757d; font-style: italic; padding: 20px;">';
        echo '<i class="fa fa-paperclip" style="font-size: 24px; margin-bottom: 8px; display: block; opacity: 0.5;"></i>';
        echo 'No attachments available';
        echo '</div>';
        echo '</div>';
        return;
    }
    
    $attachments = $result['attachments'];
    
    echo '<div class="modern-attachment-container" style="max-height: 80px; overflow-y: auto; border: 1px solid #e0e0e0; padding: 8px; border-radius: 8px; background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
    foreach ($attachments as $attachment) {
        $fileName = htmlspecialchars($attachment['original_filename']);
        $fileExt = strtolower($attachment['file_extension']);
        $fileSize = isset($attachment['file_size']) ? formatFileSize($attachment['file_size']) : '';
        $uploadDate = isset($attachment['uploaded_at']) ? date('M d, Y', strtotime($attachment['uploaded_at'])) : '';
        
        // Set appropriate icon and color based on file extension
        $iconClass = 'fa-file';
        $iconColor = '#6c757d';
        
        if (in_array($fileExt, ['pdf'])) {
            $iconClass = 'fa-file-pdf-o';
            $iconColor = '#dc3545';
        } elseif (in_array($fileExt, ['doc', 'docx'])) {
            $iconClass = 'fa-file-word-o';
            $iconColor = '#007bff';
        } elseif (in_array($fileExt, ['xls', 'xlsx'])) {
            $iconClass = 'fa-file-excel-o';
            $iconColor = '#28a745';
        } elseif (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif'])) {
            $iconClass = 'fa-file-image-o';
            $iconColor = '#ffc107';
        } elseif (in_array($fileExt, ['zip', 'rar', '7z'])) {
            $iconClass = 'fa-file-archive-o';
            $iconColor = '#6f42c1';
        }
        
        echo '<div class="modern-attachment-item" style="margin: 6px 0; padding: 8px; background: white; border-radius: 6px; border: 1px solid #e9ecef; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: all 0.2s ease; position: relative; cursor: pointer;" onmouseover="this.style.transform=\'translateY(-1px)\'; this.style.boxShadow=\'0 2px 6px rgba(0,0,0,0.15)\'" onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 1px 3px rgba(0,0,0,0.1)\'">';
        
        // File icon and name row
        echo '<div style="display: flex; align-items: center; margin-bottom: 4px;">';
        echo '<i class="fa ' . $iconClass . '" style="margin-right: 10px; color: ' . $iconColor . '; font-size: 18px; width: 24px; text-align: center;"></i>';
        echo '<div style="flex: 1; min-width: 0;">';
        echo '<div style="font-weight: 500; color: #495057; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="' . $fileName . '">' . $fileName . '</div>';
        echo '</div>';
        
        if ($isEditable) {
            echo '<button type="button" class="btn btn-xs btn-outline-danger" style="padding: 3px 8px; font-size: 11px; border-radius: 4px; margin-left: 8px; z-index: 2; position: relative;" onclick="event.stopPropagation(); deleteAttachment(' . $attachment['id'] . ')" title="Delete attachment">';
            echo '<i class="fa fa-trash"></i>';
            echo '</button>';
        }
        echo '</div>';
        
        // File details row
        echo '<div style="display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: #6c757d; margin-left: 34px;">';
        echo '<span><i class="fa fa-hdd-o" style="margin-right: 4px;"></i>' . $fileSize . '</span>';
        if ($uploadDate) {
            echo '<span><i class="fa fa-clock-o" style="margin-right: 4px;"></i>' . $uploadDate . '</span>';
        }
        echo '</div>';
        
        // Download link overlay
        echo '<a href="download_attachment.php?id=' . $attachment['id'] . '" target="_blank" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; z-index: 1; text-decoration: none;" title="View/Download ' . $fileName . '"></a>';
        
        echo '</div>';
    }
    echo '</div>';
}

class AttachmentDisplayHelper {
    
    /**
     * Display existing attachments for a document
     */
    public static function displayExistingAttachments($mysqlconn, $docNumber, $attachmentType = null, $itemId = null) {
        $attachmentHandler = new AttachmentHandler($mysqlconn);
        $result = $attachmentHandler->getAttachments($docNumber, $attachmentType, $itemId);
        
        if (!$result['success'] || empty($result['attachments'])) {
            return '';
        }
        
        $html = '<div class="existing-attachments" style="margin-top: 10px;">';
        $html .= '<strong>Current ' . ($attachmentType ? str_replace('_', ' ', $attachmentType) : '') . ':</strong><br>';
        
        foreach ($result['attachments'] as $attachment) {
            $fileName = htmlspecialchars($attachment['original_filename']);
            $fileSize = AttachmentHandler::formatFileSize($attachment['file_size']);
            $iconClass = AttachmentHandler::getFileIcon($attachment['file_extension']);
            $filePath = htmlspecialchars($attachment['file_path']);
            $attachmentId = $attachment['id'];
            
            $html .= '<div style="margin: 5px 0; padding: 5px; background: #f9f9f9; border-radius: 3px; display: flex; align-items: center; justify-content: space-between;">';
            $html .= '<div style="display: flex; align-items: center;">';
            $html .= '<i class="fa ' . $iconClass . '" style="margin-right: 8px; color: #666;"></i>';
            $html .= '<a href="download_attachment.php?id=' . $attachmentId . '" target="_blank" style="text-decoration: none; color: #333;">';
            $html .= '<span style="font-weight: 500;">' . $fileName . '</span>';
            $html .= '</a>';
            $html .= '<span style="color: #666; margin-left: 10px;">(' . $fileSize . ')</span>';
            $html .= '</div>';
            $html .= '<button type="button" class="btn btn-xs btn-danger" onclick="deleteAttachment(' . $attachmentId . ')" style="padding: 2px 6px; font-size: 10px;">';
            $html .= '<i class="fa fa-trash"></i>';
            $html .= '</button>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Display attachments in a simple list format
     */
    public static function displayAttachmentList($mysqlconn, $docNumber, $attachmentType = null) {
        $attachmentHandler = new AttachmentHandler($mysqlconn);
        $result = $attachmentHandler->getAttachments($docNumber, $attachmentType);
        
        if (!$result['success'] || empty($result['attachments'])) {
            return '<p>No attachments found.</p>';
        }
        
        $html = '<div class="attachment-list">';
        
        foreach ($result['attachments'] as $attachment) {
            $fileName = htmlspecialchars($attachment['original_filename']);
            $fileSize = AttachmentHandler::formatFileSize($attachment['file_size']);
            $iconClass = AttachmentHandler::getFileIcon($attachment['file_extension']);
            $filePath = htmlspecialchars($attachment['file_path']);
            $uploadedAt = date('M j, Y g:i A', strtotime($attachment['uploaded_at']));
            $uploadedBy = htmlspecialchars($attachment['uploaded_by']);
            
            $html .= '<div style="border: 1px solid #ddd; padding: 10px; margin: 5px 0; border-radius: 4px;">';
            $html .= '<div style="display: flex; align-items: center; justify-content: space-between;">';
            $html .= '<div style="display: flex; align-items: center;">';
            $html .= '<i class="fa ' . $iconClass . '" style="margin-right: 10px; color: #666; font-size: 16px;"></i>';
            $html .= '<div>';
            $html .= '<a href="download_attachment.php?id=' . $attachment['id'] . '" target="_blank" style="text-decoration: none; color: #333; font-weight: 500;">';
            $html .= $fileName;
            $html .= '</a><br>';
            $html .= '<small style="color: #666;">' . $fileSize . ' • Uploaded by ' . $uploadedBy . ' on ' . $uploadedAt . '</small>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<a href="download_attachment.php?id=' . $attachment['id'] . '" target="_blank" class="btn btn-xs btn-primary">';
            $html .= '<i class="fa fa-download"></i> Download';
            $html .= '</a>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Generate JavaScript for attachment deletion
     */
    public static function getAttachmentDeleteScript() {
        return '
        <script>
        function deleteAttachment(attachmentId) {
            if (confirm("Are you sure you want to delete this attachment?")) {
                fetch("delete_attachment.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        attachment_id: attachmentId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the attachment element from the page
                        const attachmentElement = document.querySelector(\'[onclick="deleteAttachment(\' + attachmentId + \')"]\').closest(\'div\');
                        if (attachmentElement) {
                            attachmentElement.remove();
                        }
                        
                        // Show success message
                        if (typeof Swal !== "undefined") {
                            Swal.fire("Success", "Attachment deleted successfully", "success");
                        } else {
                            alert("Attachment deleted successfully");
                        }
                    } else {
                        if (typeof Swal !== "undefined") {
                            Swal.fire("Error", data.message || "Failed to delete attachment", "error");
                        } else {
                            alert("Failed to delete attachment: " + (data.message || "Unknown error"));
                        }
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    if (typeof Swal !== "undefined") {
                        Swal.fire("Error", "An error occurred while deleting the attachment", "error");
                    } else {
                        alert("An error occurred while deleting the attachment");
                    }
                });
            }
        }
        </script>';
    }
}
?>
