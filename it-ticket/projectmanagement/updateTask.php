<?php
require('../conn/db.php');
require_once '../phpmailer/src/PHPMailer.php';
require_once '../phpmailer/src/SMTP.php';
require_once '../phpmailer/src/Exception.php';
require_once('email_utils.php');
require_once('task_notification.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$conn = connectionDB();
$moduleid = 4;
$nowdateunix = date("Y-m-d", time());

// Professional resolution validation function
function validateAndCleanResolution($resolution) {
    // Allow all special characters and preserve them
    $result = array(
        'valid' => false,
        'cleaned' => '',
        'error' => ''
    );
    
    // Basic null/empty check
    if (empty($resolution) || $resolution === null || $resolution === 'NULL') {
        $result['error'] = 'Resolution cannot be empty.';
        return $result;
    }
    
    // Convert HTML to plain text and decode entities for both validation and storage
    $plainTextResolution = html_entity_decode(strip_tags($resolution), ENT_QUOTES, 'UTF-8');
    
    // Remove all types of whitespace for meaningful content check
    $meaningfulContent = preg_replace('/\s+/', '', $plainTextResolution);
    
    // Check if resolution contains only whitespace
    if (empty($meaningfulContent)) {
        $result['error'] = 'Resolution cannot contain only whitespace characters. Please provide meaningful content.';
        return $result;
    }
    
    // Check minimum length (at least 10 meaningful characters)
    if (strlen($meaningfulContent) < 10) {
        $result['error'] = 'Resolution must contain at least 10 meaningful characters. Current: ' . strlen($meaningfulContent) . ' characters.';
        return $result;
    }
    
    // Check maximum length (reasonable limit)
    if (strlen($plainTextResolution) > 5000) {
        $result['error'] = 'Resolution is too long. Maximum 5,000 characters allowed.';
        return $result;
    }
    
    // Clean up extra whitespace but preserve line breaks and structure
    $cleanedResolution = preg_replace('/[ \t]+/', ' ', $plainTextResolution); // Replace multiple spaces/tabs with single space
    $cleanedResolution = preg_replace('/\n\s*\n/', "\n\n", $cleanedResolution); // Clean up multiple line breaks
    $cleanedResolution = trim($cleanedResolution);
    
    // All validations passed - return cleaned plain text resolution
    $result['valid'] = true;
    $result['cleaned'] = $cleanedResolution; // Return plain text only
    return $result;
}

function alphanumericAndSpace($string) {
    return preg_replace("/[^,;a-zA-Z0-9 _-]|[,; ]$/s", "", $string);
}

// Get and sanitize input parameters - apply strtoupper AFTER escaping
$taskIdUp = strtoupper(mysqli_real_escape_string($conn, $_GET['taskIdUp2']));
$taskUseridUp = strtoupper(mysqli_real_escape_string($conn, $_GET['taskUseridUp2']));
$projectOwnerUp = strtoupper(mysqli_real_escape_string($conn, $_GET['projectOwnerUp2']));
$taskClassificationUp = strtoupper(mysqli_real_escape_string($conn, $_GET['taskClassificationUp2']));
$taskPriorityUp = strtoupper(mysqli_real_escape_string($conn, $_GET['taskPriorityUp2']));

// Handle subject with proper encoding
$taskSubjectUp = isset($_GET['taskSubjectUp2']) ? urldecode($_GET['taskSubjectUp2']) : '';
$taskSubjectUp = mysqli_real_escape_string($conn, $taskSubjectUp);

// Handle assignee with proper validation
$taskAssigneeUp = isset($_GET['taskAssigneeUp2']) ? strtoupper(mysqli_real_escape_string($conn, $_GET['taskAssigneeUp2'])) : '';

$taskTargetDateUp = strtoupper(mysqli_real_escape_string($conn, $_GET['taskTargetDateUp2']));
$taskStartDateUp = strtoupper(mysqli_real_escape_string($conn, $_GET['taskStartDateUp2']));
$taskEndDateUp = strtoupper(mysqli_real_escape_string($conn, $_GET['taskEndDateUp2']));

$descriptionupdate = isset($_GET['descriptionupdate2']) ? urldecode($_GET['descriptionupdate2']) : '';
$descriptionupdate = mysqli_real_escape_string($conn, $descriptionupdate);

$taskStatus = strtoupper(mysqli_real_escape_string($conn, $_GET['taskStatus2']));

// Handle resolution with special characters preserved
$resolution = isset($_GET['resolution']) ? urldecode($_GET['resolution']) : '';
// Don't escape yet - validate first, then escape for SQL

// Validate and clean the resolution
$resolutionValidation = validateAndCleanResolution($resolution);

if (!$resolutionValidation['valid']) {
    $resolution = '';
} else {
    $resolution = $resolutionValidation['cleaned'];
}

// Now escape for SQL after validation
$resolution = mysqli_real_escape_string($conn, $resolution);

// Handle NULL values for SQL query
if ($resolution === '' || $resolution === 'NULL') {
    $resolution = "NULL";
}
if ($descriptionupdate === '' || $descriptionupdate === null) {
    $descriptionupdate = "NULL";
}

// Wrap nullable text fields
$descriptionupdate = ($descriptionupdate === "NULL") ? "NULL" : "'$descriptionupdate'";
$resolution = ($resolution === "NULL") ? "NULL" : "'$resolution'";

// Enhanced validation: If task status is "Done" (status ID 6), resolution must be valid
if ($taskStatus == 6) {
    if ($resolution === "NULL") {
        echo "<div class='alert alert-danger fade in'>" .
             "<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>" .
             "<strong>Validation Error:</strong> A meaningful resolution is required before marking the task as DONE. " .
             "Please provide a detailed explanation of how the task was completed." .
             "</div>";
        exit();
    }
    
    // If there was a validation error, show it
    if (!$resolutionValidation['valid']) {
        echo "<div class='alert alert-danger fade in'>" .
             "<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>" .
             "<strong>Resolution Validation Error:</strong> " . htmlspecialchars($resolutionValidation['error']) .
             "</div>";
        exit();
    }
}

// OPTIMIZED: Single query to get current task data
$myquery = "SELECT enddate, statusid FROM pm_projecttasktb WHERE id = '$taskIdUp'";
$myresult = mysqli_query($conn, $myquery);
$row = mysqli_fetch_assoc($myresult);
$currentEndDate = $row['enddate'];
$currentStatusId = $row['statusid'];

// OPTIMIZED: Streamlined date logic
if ($taskStatus == 6) {
    $taskEndDateUp = ($currentStatusId == 6) ? $currentEndDate : $nowdateunix;
    
    // Calculate performance stats only when completing task
    $date1 = date_create($taskStartDateUp);
    $date2 = date_create($taskEndDateUp);
    $date22 = date_create($taskTargetDateUp);
    
    $datefirrer = abs(date_diff($date1, $date2)->format("%R%a"));
    $datefirrer2 = abs(date_diff($date1, $date22)->format("%R%a"));
    
    if ($date22 > $date2) {
        $percentstat = 3; // ahead of time
    } elseif ($datefirrer == $datefirrer2) {
        $percentstat = 1; // exact
    } elseif ($datefirrer > $datefirrer2) {
        $percentstat = 2; // late
    } else {
        $percentstat = 3; // ahead of time
    }
} else {
    $taskEndDateUp = "1900-01-01";
    $percentstat = 0;
}

/*-------for audit trails--------*/
$auditRemarks1 = "UPDATED TASK | " . $taskSubjectUp;
$auditRemarks2 = "ATTEMPT TO UPDATE TASK, FAILED | " . $taskSubjectUp;

// Ensure assignee is not empty or NULL
if (empty($taskAssigneeUp) || $taskAssigneeUp == "NULL") {
    $taskAssigneeUp = "NULL";
    $assigneeForSQL = "NULL";
} else {
    $assigneeForSQL = "'$taskAssigneeUp'";
}

// OPTIMIZED: Single transaction for all updates
mysqli_autocommit($conn, false);

try {
    // Main task update
    $myqueryxx = "UPDATE pm_projecttasktb SET 
                    classificationid = '$taskClassificationUp',
                    priorityid = '$taskPriorityUp',
                    subject = '$taskSubjectUp',
                    assignee = $assigneeForSQL,
                    deadline = '$taskTargetDateUp',
                    startdate = '$taskStartDateUp',
                    enddate = '$taskEndDateUp',
                    description = $descriptionupdate,
                    statusid = '$taskStatus',
                    percentdone = '$percentstat',
                    isupdatedbyclient = '0',
                    projectid = '$projectOwnerUp',
                    resolution = $resolution
                  WHERE id = '$taskIdUp'";
    
    $myresultxx = mysqli_query($conn, $myqueryxx);
    
    if (!$myresultxx) {
        throw new Exception("Task update failed");
    }
    
    // OPTIMIZED: Project completion check (only when task is completed)
    if ($taskStatus == 6) {
        $projectQuery = "SELECT 
                            (SELECT COUNT(*) FROM pm_projecttasktb WHERE projectid = '$projectOwnerUp' AND statusid = '6') AS doneitem,
                            (SELECT COUNT(*) FROM pm_projecttasktb WHERE projectid = '$projectOwnerUp') AS countall";
        $projectResult = mysqli_query($conn, $projectQuery);
        $projectRow = mysqli_fetch_assoc($projectResult);
        
        if ($projectRow['doneitem'] == $projectRow['countall']) {
            $myqueryxxup = "UPDATE sys_projecttb SET statusid = '6' WHERE id = '$projectOwnerUp'";
            mysqli_query($conn, $myqueryxxup);
        }
    }
    
    // OPTIMIZED: Handle task assignees (batch delete and insert)
    if (!empty($taskAssigneeUp) && $taskAssigneeUp != "NULL") {
        // Delete existing assignees
        mysqli_query($conn, "DELETE FROM pm_taskassigneetb WHERE taskid = '$taskIdUp'");
        
        // Prepare batch insert
        $assignees = array_filter(array_map('trim', explode(",", $taskAssigneeUp)));
        if (!empty($assignees)) {
            $insertValues = array();
            foreach ($assignees as $assignee) {
                $assignee = mysqli_real_escape_string($conn, $assignee);
                $insertValues[] = "('$taskIdUp', '$assignee')";
            }
            
            if (!empty($insertValues)) {
                $batchInsert = "INSERT INTO pm_taskassigneetb (taskid, assigneeid) VALUES " . implode(',', $insertValues);
                mysqli_query($conn, $batchInsert);
            }
        }
    }
    
    // Audit trail
    $queryaud1 = "INSERT INTO sys_audit (module, remarks, userid) VALUES ('$moduleid', '$auditRemarks1', '$taskUseridUp')";
    mysqli_query($conn, $queryaud1);
    
    // Commit all changes
    mysqli_commit($conn);
    
    // INSTANT RESPONSE - Send success immediately
    echo "<div class='alert alert-info fade in'>Changes have been saved!</div>";
    
    // BACKGROUND EMAIL PROCESSING - Fire and forget
    $emailData = array(
        'taskId' => $taskIdUp,
        'taskSubject' => $taskSubjectUp,
        'taskAssignee' => $taskAssigneeUp,
        'taskUserid' => $taskUseridUp
    );
    
    // Method 1: fastcgi_finish_request (fastest)
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
        sendTaskUpdateNotification($conn, $taskIdUp, $taskSubjectUp, $taskAssigneeUp, $taskUseridUp);
    } 
    // Method 2: cURL (non-blocking)
    elseif (function_exists('curl_init')) {
        $encodedData = base64_encode(json_encode($emailData));
        $emailUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/process_email_queue.php";
        
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $emailUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'data=' . $encodedData,
            CURLOPT_TIMEOUT => 1,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_NOSIGNAL => 1,
            CURLOPT_FRESH_CONNECT => true,
            CURLOPT_FORBID_REUSE => true
        ));
        curl_exec($ch);
        curl_close($ch);
    }
    // Method 3: Queue file (ultimate fallback)
    else {
        $queueDir = __DIR__ . '/email_queue/';
        if (!is_dir($queueDir)) {
            mkdir($queueDir, 0755, true);
        }
        $queueFile = $queueDir . $taskIdUp . '_' . time() . '.json';
        file_put_contents($queueFile, json_encode($emailData));
    }
    
} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    
    $queryaud2 = "INSERT INTO sys_audit (module, remarks, userid) VALUES ('$moduleid', '$auditRemarks2', '$taskUseridUp')";
    mysqli_query($conn, $queryaud2);
    
    echo "<div class='alert alert-danger fade in'>Failed to save information!</div>";
    error_log("Task update error: " . $e->getMessage());
}

// Restore autocommit
mysqli_autocommit($conn, true);
mysqli_close($conn);
?>

<script type="text/javascript">
    // OPTIMIZED: Reduced reload time for faster user experience
    setTimeout(function(){location.reload();}, 10);
</script>