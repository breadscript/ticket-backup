<?php 
// Include database connection file instead of session check
include('../conn/db.php'); // Adjust this path based on your actual database connection file location
// Or if your connection file is in a different location, use the correct path:
// include('../config/db.php'); 
// include('../database/connection.php');

$moduleid=1;
$conn = connectionDB();
$rest = "";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Ticket</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/321/assets/img/favicon.ico">
    <!-- CSS dependencies -->
    <link href="../assets/customjs/jquery.tagsinput.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/chosen.min.css" />
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../assets/css/font-awesome.min.css" />
    <!-- jQuery UI CDN CSS -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <style>
    body {
        background: #23272b !important;
    }
    /* Add this CSS to hide CKEditor warning banner */
    .cke_notification_warning {
        display: none !important;
    }
    .file-upload-wrapper {
        border: 2px dashed #ddd;
        padding: 15px;
        border-radius: 6px;
        background: #f9f9f9;
    }
    .custom-file-input {
        margin-bottom: 10px;
    }
    .file-list {
        margin-top: 10px;
        font-size: 13px;
        color: #666;
        max-height: 150px;
        overflow-y: auto;
    }
    .file-row {
        display: flex;
        align-items: center;
        padding: 4px 8px;
        background: white;
        border: 1px solid #eee;
        margin-bottom: 4px;
        border-radius: 4px;
    }
    .file-row i.fa-file-o {
        margin-right: 8px;
        color: #666;
    }
    .file-row .remove-file {
        margin-left: auto;
        color: #dc3545;
        border: none;
        background: none;
        padding: 0 4px;
    }
    .file-row .remove-file:hover {
        color: #bd2130;
    }
    .upload-btn {
        margin-right: 8px;
    }
    #clearFiles {
        float: right;
    }
    /* Add this for a smaller, centered container */
    .custom-ticket-container {
        max-width: 500px;
        margin: 40px auto;
        padding-left: 0;
        padding-right: 0;
    }
    .form-bordered {
        border: 2px solid #888;
        border-radius: 8px;
        padding: 2rem;
        background: #fff;
        color: #222;
    }
    .red {
        color: #d9534f !important;
    }
    .white {
        color: #fff !important;
    }
    .file-upload-wrapper {
        border: 2px dashed #ddd;
        border-radius: 6px;
        background: #f9f9f9;
    }
    .card {
        border-radius: 16px;
    }
    .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }
    .form-group {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 16px 12px 8px 12px;
        margin-bottom: 1.5rem;
        background: #f8f9fa;
    }
    .form-group label {
        display: block;
        font-weight: 600;
        padding-bottom: 4px;
        margin-bottom: 8px;
    }
    </style>
    <!-- Add SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body style="min-height: 100vh;">
    <div class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="custom-ticket-container" style="width: 100%; max-width: 430px;">
            <!-- Heading -->
            <h1 class="text-center mb-4">
                <i class="fa red" aria-hidden="true"></i>
                <span class="red"></span>
                <span class="white" id="id-text2">OPEN VOICE CHANNEL</span>
                <p style="font-size: 0.4em; color: red;"><em>Note: The concerns to be submitted will be anonymous.</em></p>
                                                    
            </h1>
            <!-- Card -->
            <div class="card shadow-lg border-0" style="border-radius: 16px;">
                <div class="card-header bg-dark text-white text-center" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                </div>
                <div class="card-body p-4">
                    <form id="ticketInfo">
                        <div class="form-group mb-3">
                            <label for="ticketSubject" class="fw-bold">Subject</label>
                            <input class="form-control" placeholder="Enter a subject" id="ticketSubject" name="ticketSubject" required />
                        </div>

                        <div class="form-group mb-3">
                            <label for="ticketProjectOwner" class="fw-bold">Company Name</label>
                            <select class="form-control" id="ticketProjectOwner" name="ticketProjectOwner">
                            <option value="">Select Company</option>
                                <?php 
                                    $query66 = "SELECT id,clientname FROM sys_clienttb $rest";
                                    $result66 = mysqli_query($conn, $query66);
                                        while($row66 = mysqli_fetch_assoc($result66)){
                                            $clientname=mb_convert_case($row66['clientname'], MB_CASE_TITLE, "UTF-8");
                                            $clientid=$row66['id'];
                                        ?>
                                    <option value="<?php echo $clientid;?>"><?php echo $clientname;?></option>
                                <?php } ?>
                            </select>
                        </div>
                        
                      

                        <div class="form-group mb-3">
                            <label class="fw-bold">Attach Files</label>
                            <div class="file-upload-wrapper p-2 mb-2">
                                <div class="d-flex align-items-center mb-2">
                                    <button class="btn btn-primary btn-sm upload-btn me-2" type="button" onclick="document.getElementById('attachFile').click();">
                                        Add Files
                                    </button>
                                    <button class="btn btn-default btn-sm" type="button" id="clearFiles">
                                        Clear
                                    </button>
                                </div>
                                <input type="file" class="form-control" id="attachFile" name="attachFile[]" multiple 
                                    accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt" style="display: none;">
                                <div id="fileNameDisplay" class="file-list">
                                    <div class="text-muted">No files selected</div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="description" class="fw-bold">Description</label>
                            <textarea id="description" name="description" rows="5" class="form-control" placeholder="Describe your issue in detail..." required></textarea>
                            <small class="form-text text-muted">You can use rich text formatting (bold, lists, links, etc.)</small>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-block" id="submitTicketBtn" name="submitTicketBtn">
                                Submit Ticket
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- JS dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-validator/0.5.3/js/bootstrapValidator.min.js"></script>
    <script src="../assets/customjs/ticketing.js"></script>
    <script src="../assets/customjs/jquery.tagsinput.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="../assets/js/chosen.jquery.min.js"></script>
    <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
    <script type="text/javascript">
        CKEDITOR.replace('description', {
            toolbar: [
                { name: 'clipboard', items: [ 'Cut', 'Copy', 'Paste', 'Undo', 'Redo' ] },
                { name: 'styles', items: [ 'Format', 'Font', 'FontSize' ] },
                { name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline', 'Strike', '-', 'RemoveFormat' ] },
                { name: 'colors', items: [ 'TextColor', 'BGColor' ] },
                { name: 'paragraph', items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote' ] },
                { name: 'links', items: [ 'Link', 'Unlink' ] },
                { name: 'insert', items: [ 'Image', 'Table', 'HorizontalRule', 'SpecialChar' ] },
                { name: 'tools', items: [ 'Maximize' ] }
            ],
            height: 200
        });
        $('.date-picker').datepicker({
            autoclose: true,
            todayHighlight: true
        });

        // Set current date as default value for the date-picker
        $(document).ready(function() {
            var today = new Date().toISOString().split('T')[0];
            $('#ticketTargetDate').val(today);
        });

        // File change handler
        $('#attachFile').on('change', function() {
            const files = this.files;
            const fileDisplay = $('#fileNameDisplay');
            
            if (files.length === 0) {
                fileDisplay.html('<div class="text-muted">No files selected</div>');
                return;
            }
            
            let fileList = '';
            for (let i = 0; i < files.length; i++) {
                fileList += `<div class="file-row" data-index="${i}">
                    <i class="fa fa-file-o"></i> ${files[i].name}
                    <button type="button" class="btn btn-xs btn-link remove-file" style="color: red; padding: 0 4px;">
                        <i class="fa fa-times"></i>
                    </button>
                </div>`;
            }
            
            fileDisplay.html(fileList);
        });

        // Individual file removal
        $('#fileNameDisplay').on('click', '.remove-file', function(e) {
            e.preventDefault();
            const fileIndex = $(this).parent().data('index');
            const input = $('#attachFile')[0];
            
            const dt = new DataTransfer();
            const { files } = input;
            
            for (let i = 0; i < files.length; i++) {
                if (i !== fileIndex) {
                    dt.items.add(files[i]);
                }
            }
            
            input.files = dt.files;
            $('#attachFile').trigger('change');
        });

        // Clear all files
        $('#clearFiles').on('click', function() {
            $('#attachFile').val('');
            $('#fileNameDisplay').html('<div class="text-muted">No files selected</div>');
        });

        // Form submission handler
        $('#ticketInfo').on('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            var formData = new FormData(this);
            formData.append('description', CKEDITOR.instances.description.getData());
            
            // Submit form via AJAX
            $.ajax({
                url: 'saveticket.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // Parse the response if it's a string
                    if (typeof response === 'string') {
                        try {
                            response = JSON.parse(response);
                        } catch (e) {
                            console.error('Error parsing response:', e);
                        }
                    }

                    // Debug log
                    console.log('Response:', response);

                    if (response && response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message || 'Ticket submitted successfully!',
                            confirmButtonColor: '#28a745'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Clear form
                                $('#ticketInfo')[0].reset();
                                CKEDITOR.instances.description.setData('');
                                $('#fileNameDisplay').html('<div class="text-muted">No files selected</div>');
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: (response && response.message) || 'An error occurred while submitting the ticket.',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', xhr.responseText);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred while submitting the ticket. Please try again.',
                        confirmButtonColor: '#dc3545'
                    });
                }
            });
        });

        // Test SweetAlert directly
        $('#submitTicketBtn').on('click', function(e) {
            e.preventDefault();
            
            // Check if subject is empty
            const subject = $('#ticketSubject').val().trim();
            if (!subject) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Subject',
                    text: 'Please enter a subject for your ticket.',
                    confirmButtonColor: '#ffc107'
                }).then(() => {
                    window.location.reload();
                });
                return;
            }
            
            // Show SweetAlert directly
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Ticket submitted successfully!',
                confirmButtonColor: '#28a745'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Clear form
                    $('#ticketInfo')[0].reset();
                    CKEDITOR.instances.description.setData('');
                    $('#fileNameDisplay').html('<div class="text-muted">No files selected</div>');
                    // Refresh the page
                    window.location.reload();
                }
            });
        });

        // Add this right after the SweetAlert2 script tag
        document.addEventListener('DOMContentLoaded', function() {
            // Test if SweetAlert2 is loaded
            if (typeof Swal === 'undefined') {
                console.error('SweetAlert2 is not loaded!');
            } else {
                console.log('SweetAlert2 is loaded successfully');
            }
        });
    </script>
</body>
</html>


