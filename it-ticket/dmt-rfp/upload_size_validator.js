// Upload Size Validator - Handles large file upload warnings and validation
// This file provides client-side validation for large file uploads

(function() {
    'use strict';
    
    // Configuration
    var config = {
        maxFileSize: 200 * 1024 * 1024, // 200MB
        warningThreshold: 100 * 1024 * 1024, // 100MB warning threshold
        maxTotalSize: 500 * 1024 * 1024 // 500MB total upload limit
    };
    
    // Format file size for display
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Validate file size and show warnings
    function validateFileUpload(files, callback) {
        var totalSize = 0;
        var largeFiles = [];
        var oversizedFiles = [];
        
        // Check each file
        for (var i = 0; i < files.length; i++) {
            var file = files[i];
            totalSize += file.size;
            
            if (file.size > config.maxFileSize) {
                oversizedFiles.push({
                    name: file.name,
                    size: file.size
                });
            } else if (file.size > config.warningThreshold) {
                largeFiles.push({
                    name: file.name,
                    size: file.size
                });
            }
        }
        
        // Check for oversized files
        if (oversizedFiles.length > 0) {
            var message = 'The following files exceed the 200MB limit:\n\n';
            oversizedFiles.forEach(function(file) {
                message += '• ' + file.name + ' (' + formatFileSize(file.size) + ')\n';
            });
            message += '\nPlease reduce file sizes or split large files.';
            
            Swal.fire({
                title: 'Files Too Large',
                text: message,
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return false;
        }
        
        // Check total size
        if (totalSize > config.maxTotalSize) {
            Swal.fire({
                title: 'Total Upload Too Large',
                html: 'Total upload size: <strong>' + formatFileSize(totalSize) + '</strong><br><br>' +
                      'Maximum allowed: <strong>' + formatFileSize(config.maxTotalSize) + '</strong><br><br>' +
                      'Please reduce the number of files or their sizes.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return false;
        }
        
        // Show warning for large uploads
        if (totalSize > config.warningThreshold || largeFiles.length > 0) {
            var message = 'You are uploading <strong>' + formatFileSize(totalSize) + '</strong> of files.';
            
            if (largeFiles.length > 0) {
                message += '<br><br>Large files detected:<br>';
                largeFiles.forEach(function(file) {
                    message += '• ' + file.name + ' (' + formatFileSize(file.size) + ')<br>';
                });
            }
            
            message += '<br><br><strong>Important:</strong><br>' +
                      '• Large uploads may take several minutes<br>' +
                      '• Ensure your internet connection is stable<br>' +
                      '• Do not close the browser during upload<br>' +
                      '• Consider uploading files individually if issues occur';
            
            Swal.fire({
                title: 'Large Upload Detected',
                html: message,
                icon: 'warning',
                confirmButtonText: 'Continue Upload',
                showCancelButton: true,
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                width: '500px'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (callback) callback(true);
                } else {
                    if (callback) callback(false);
                }
            });
            return 'pending'; // Indicates user confirmation is needed
        }
        
        // No warnings needed
        if (callback) callback(true);
        return true;
    }
    
    // Enhanced file selection handler for item attachments
    function handleItemFileSelection(input) {
        var files = input.files;
        if (files.length === 0) return;
        
        var result = validateFileUpload(files, function(proceed) {
            if (!proceed) {
                // User cancelled or files were invalid
                input.value = '';
                return;
            }
            
            // Proceed with normal file handling
            // This will be handled by the existing function
            if (typeof window.originalHandleItemFileSelection === 'function') {
                window.originalHandleItemFileSelection(input);
            }
        });
        
        if (result === 'pending') {
            // User confirmation is pending, don't proceed yet
            return;
        } else if (result === false) {
            // Files were invalid, clear the input
            input.value = '';
            return;
        }
        
        // Files are valid, proceed normally
        if (typeof window.originalHandleItemFileSelection === 'function') {
            window.originalHandleItemFileSelection(input);
        }
    }
    
    // Enhanced file selection handler for supporting documents
    function handleSupportingFileSelection(input) {
        var files = input.files;
        if (files.length === 0) return;
        
        var result = validateFileUpload(files, function(proceed) {
            if (!proceed) {
                // User cancelled or files were invalid
                input.value = '';
                return;
            }
            
            // Proceed with normal file handling
            if (typeof window.originalHandleSupportingFileSelection === 'function') {
                window.originalHandleSupportingFileSelection(input);
            }
        });
        
        if (result === 'pending') {
            // User confirmation is pending, don't proceed yet
            return;
        } else if (result === false) {
            // Files were invalid, clear the input
            input.value = '';
            return;
        }
        
        // Files are valid, proceed normally
        if (typeof window.originalHandleSupportingFileSelection === 'function') {
            window.originalHandleSupportingFileSelection(input);
        }
    }
    
    // Initialize the upload validator
    function init() {
        // Store original functions if they exist
        if (typeof window.handleItemFileSelection === 'function') {
            window.originalHandleItemFileSelection = window.handleItemFileSelection;
        }
        if (typeof window.handleSupportingFileSelection === 'function') {
            window.originalHandleSupportingFileSelection = window.handleSupportingFileSelection;
        }
        
        // Override the functions
        window.handleItemFileSelection = handleItemFileSelection;
        window.handleSupportingFileSelection = handleSupportingFileSelection;
        
        console.log('Upload Size Validator initialized');
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Export for global access
    window.UploadSizeValidator = {
        validateFileUpload: validateFileUpload,
        formatFileSize: formatFileSize,
        config: config
    };
    
})();
