<?php
require('../conn/db.php');
require_once('../projectmanagement/upload_file.php');
require_once '../phpmailer/src/PHPMailer.php';
require_once '../phpmailer/src/SMTP.php';
require_once '../phpmailer/src/Exception.php';
require_once('email_utils.php');
require_once('task_notification.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$conn = connectionDB();
$moduleid=4;

$nowdateunix = date("Y-m-d",time());    

ini_set('display_errors', 0); // Don't show errors to users
ini_set('log_errors', 1);     // But do log them
error_log("Starting saveTask.php script execution");

function alphanumericAndSpace( $string ) {
   return preg_replace( "/[^,;a-zA-Z0-9 _-]|[,; ]$/s", "", $string );
}

// Debug incoming data
error_log("POST Data: " . print_r($_POST, true));
error_log("GET Data: " . print_r($_GET, true));

// Debug incoming files
if(isset($_FILES['attachFile'])) {
    error_log("Files Data: " . print_r($_FILES['attachFile'], true));
}

// Update how we get the data - check both POST and GET
$taskUserid = isset($_POST['taskUserid']) ? strtoupper($_POST['taskUserid']) : (isset($_GET['taskUserid']) ? strtoupper($_GET['taskUserid']) : '');
$taskClassification = isset($_POST['taskClassification']) ? strtoupper($_POST['taskClassification']) : (isset($_GET['taskClassification']) ? strtoupper($_GET['taskClassification']) : '');
$taskPriority = isset($_POST['taskPriority']) ? strtoupper($_POST['taskPriority']) : (isset($_GET['taskPriority']) ? strtoupper($_GET['taskPriority']) : '');
$taskSubject = isset($_POST['taskSubject']) ? urldecode($_POST['taskSubject']) : (isset($_GET['taskSubject']) ? urldecode($_GET['taskSubject']) : '');
$taskAssignee = isset($_POST['taskAssignee2']) ? strtoupper($_POST['taskAssignee2']) : (isset($_GET['taskAssignee2']) ? strtoupper($_GET['taskAssignee2']) : '');
$taskTargetDate = isset($_POST['taskTargetDate']) ? strtoupper($_POST['taskTargetDate']) : (isset($_GET['taskTargetDate']) ? strtoupper($_GET['taskTargetDate']) : '');
$taskStartDate = isset($_POST['taskStartDate']) ? strtoupper($_POST['taskStartDate']) : (isset($_GET['taskStartDate']) ? strtoupper($_GET['taskStartDate']) : '');
$taskEndDate = isset($_POST['taskEndDate']) ? strtoupper($_POST['taskEndDate']) : (isset($_GET['taskEndDate']) ? strtoupper($_GET['taskEndDate']) : '');
$description = isset($_POST['description']) ? urldecode($_POST['description']) : (isset($_GET['description']) ? urldecode($_GET['description']) : '');
$taskProjectOwner = isset($_POST['taskProjectOwner']) ? strtoupper($_POST['taskProjectOwner']) : (isset($_GET['taskProjectOwner']) ? strtoupper($_GET['taskProjectOwner']) : '');

// Debug the processed values
echo "<div style='display:none;'>";
echo "Debug Values:<br>";
echo "UserID: " . $taskUserid . "<br>";
echo "Classification: " . $taskClassification . "<br>";
echo "Subject: " . $taskSubject . "<br>";
echo "Project: " . $taskProjectOwner . "<br>";
echo "</div>";

$taskIdentification = 1;    //task is from pm
if (($description == '')||($description == null)){$description="NULL";}

$myqueryxxup1 = "SELECT statusid FROM sys_projecttb WHERE id = '$taskProjectOwner'";
$myresultxxup1 = mysqli_query($conn, $myqueryxxup1);
while($row66 = mysqli_fetch_assoc($myresultxxup1)){
    $statusidnix=$row66['statusid'];
}

if($statusidnix <> 6){

/*-------for audit trails--------*/
$auditRemarks1 = "REGISTER NEW TASK"." | ".$taskSubject;
$auditRemarks2 = "ATTEMPT TO REGISTER NEW TASK, FAILED"." | ".$taskSubject;
$auditRemarks3 = "ATTEMPT TO REGISTER NEW TASK, DUPLICATE"." | ".$taskSubject;

$myquery = "SELECT subject FROM pm_projecttasktb WHERE subject = '$taskSubject' AND projectid = '$taskProjectOwner' AND classificationid = '$taskClassification' LIMIT 1";
$myresult = mysqli_query($conn, $myquery);
$num_rows = mysqli_num_rows($myresult);

    if($num_rows == 0){
        // Get current count of tasks
        $countQuery = "SELECT COUNT(*) as total FROM pm_projecttasktb";
        $countResult = mysqli_query($conn, $countQuery);
        $countRow = mysqli_fetch_assoc($countResult);
        $currentCount = $countRow['total'] + 1;
        
        // Generate SRN
        $srn_id = "SRN:" . date('YmdHis') . $currentCount;
        
        // Modified insert query to include srn_id
        $myqueryxx = "INSERT INTO pm_projecttasktb (createdbyid, classificationid, priorityid, subject, assignee, deadline, startdate, enddate, description, projectid, istask, srn_id)
                    VALUES ('$taskUserid', '$taskClassification', '$taskPriority', '$taskSubject', '$taskAssignee', '$taskTargetDate', '$taskStartDate', '$taskEndDate', '$description', '$taskProjectOwner', '$taskIdentification', '$srn_id')";
        $myresultxx = mysqli_query($conn, $myqueryxx);
        
        $lastid = mysqli_insert_id($conn); 
        $taskAssignee = explode(",", $taskAssignee);
        
        foreach ($taskAssignee as $key => $value) {
            // echo "assignee1:".$value."</br>";
            $myquery2 = "INSERT INTO pm_taskassigneetb (taskid,assigneeid) VALUES ('$lastid','$value')";
            $myresult2 = mysqli_query($conn, $myquery2);
        }
        
        if($myresultxx){
            // Handle file uploads if present
            $fileMessage = "";
            if(isset($_FILES['attachFile']) && !empty($_FILES['attachFile']['name'][0])) {
                try {
                    // Check if the upload_file.php is properly included
                    if (!function_exists('uploadFiles')) {
                        throw new Exception("Upload function not available");
                    }
                    
                    // Validate file before uploading
                    $maxFileSize = 10 * 1024 * 1024; // 10MB limit
                    foreach ($_FILES['attachFile']['size'] as $key => $size) {
                        if ($size > $maxFileSize) {
                            throw new Exception("File size exceeds limit (10MB)");
                        }
                    }
                    
                    $uploadResult = uploadFiles($conn, $lastid, $taskUserid, $_FILES['attachFile']);
                    if ($uploadResult['status'] === 'success') {
                        $fileMessage = " with " . count($uploadResult['files']) . " attached file(s)";
                    } else {
                        $fileMessage = " (Note: " . $uploadResult['message'] . ")";
                    }
                } catch (Exception $e) {
                    error_log("File upload failed: " . $e->getMessage());
                    $fileMessage = " (file upload failed: " . $e->getMessage() . ")";
                }
            }
            
            if($statusidnix == 1){
            $myqueryxxup = "UPDATE sys_projecttb
                                    SET statusid = '3'
                                    WHERE id = '$taskProjectOwner'";
            $myresultxxup = mysqli_query($conn, $myqueryxxup);  
            }
            
            
            // $sql2=mysqli_query($conn,"CALL insertAudit('$moduleid','$auditRemarks1','$taskUserid')");
            $queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES ('$moduleid','$auditRemarks1','$taskUserid')";
            $resaud1 = mysqli_query($conn, $queryaud1);
            
            // Send email notification
            try {
                $emailSent = sendTaskCreateNotification($conn, $lastid, $taskSubject, $taskAssignee, $taskUserid);
                if (!$emailSent) {
                    error_log("Failed to send task notification email");
                }
            } catch (Exception $e) {
                error_log("Error sending task notification: " . $e->getMessage());
            }
            
            echo "<div id='successMessage' style='text-align: center; padding: 20px; background-color: #4CAF50; color: white; margin: 20px;'>";
            echo "<h3>Task Saved Successfully!" . $fileMessage . "</h3>";
            echo "<p>Redirecting to task list...</p>";
            echo "</div>";
            
            echo "<script type='text/javascript'>
                // Immediately execute when script loads
                (function() {
                    // Ensure jQuery is loaded
                    if (typeof jQuery === 'undefined') {
                        console.error('jQuery not loaded');
                        return;
                    }

                    // Disable form resubmission on refresh
                    if (window.history.replaceState) {
                        window.history.replaceState(null, null, window.location.href);
                    }

                    // Function to clean up modal
                    function cleanupModal() {
                        try {
                            // Remove all event listeners from the form
                            jQuery('form').each(function() {
                                jQuery(this).off();
                                this.onsubmit = null;
                            });

                            // Disable all form elements
                            jQuery('form :input').prop('disabled', true);

                            // Close bootstrap modal with both methods
                            var modals = jQuery('.modal');
                            modals.each(function() {
                                jQuery(this).modal('hide').remove();
                            });

                            // Clean up modal artifacts
                            jQuery('body').removeClass('modal-open').css('padding-right', '');
                            jQuery('.modal-backdrop').remove();

                            // Force any remaining modals closed
                            jQuery('.modal').hide().remove();
                        } catch (e) {
                            console.error('Modal cleanup error:', e);
                        }
                    }

                    // Execute cleanup
                    cleanupModal();

                    // Redirect after delay
                    setTimeout(function() {
                        window.location.href = '../projectmanagement/projectmanagement.php';
                    }, 1500);

                })();
            </script>";
        }else{
            $queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES ('$moduleid','$auditRemarks2','$taskUserid')";
            $resaud1 = mysqli_query($conn, $queryaud1);
            // $sql2=mysqli_query($conn,"CALL insertAudit('$moduleid','$auditRemarks2','$taskUserid')");
            echo "<div style='text-align: center; padding: 20px; background-color: #f44336; color: white; margin: 20px;'>";
            echo "<h3>Failed to save task</h3>";
            echo "</div>";
        }
    }else{
        $queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES ('$moduleid','$auditRemarks3','$taskUserid')";
        $resaud1 = mysqli_query($conn, $queryaud1);
        
        // Debug query to see what exists
        $debugQuery = "SELECT * FROM pm_projecttasktb 
                      WHERE subject = '$taskSubject' 
                      AND projectid = '$taskProjectOwner' 
                      AND classificationid = '$taskClassification'";
        $debugResult = mysqli_query($conn, $debugQuery);
        $existingTask = mysqli_fetch_assoc($debugResult);
        
        echo "<div class='alert alert-danger fade in'>";
        echo "Duplicate Entry!<br>";
        // echo "Subject: " . htmlspecialchars($taskSubject) . "<br>";
        // echo "Project: " . htmlspecialchars($taskProjectOwner) . "<br>";
        // echo "Classification: " . htmlspecialchars($taskClassification) . "<br>";
        // echo "Existing Task ID: " . htmlspecialchars($existingTask['id']) . "<br>";
        // echo "Created By: " . htmlspecialchars($existingTask['createdbyid']) . "<br>";
        // echo "Created On: " . htmlspecialchars($existingTask['created_at']) . "<br>";
        // echo "Debug Query: " . htmlspecialchars($debugQuery);
        echo "</div>";
    }
}else{
    echo "<div class='alert alert-danger fade in'>";
    echo "Project has been closed, to proceed adding new task for this, call your PM.";         
    echo "</div>";
}
?>