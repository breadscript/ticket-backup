<?php
include('../check_session.php');
$moduleid = 4;
include('proxy.php');
require_once('task_notification.php'); // Include the task notification file
$conn = connectionDB();

// Ensure clean output buffer
if (ob_get_length()) ob_clean();

// Initialize response array
$response = array(
    'success' => false,
    'message' => ''
);

try {
    // Get task ID
    $taskId = isset($_POST['id']) ? mysqli_real_escape_string($conn, $_POST['id']) : null;
    if (!$taskId) {
        throw new Exception("Task ID is required");
    }

    // Get parent task ID
    $parentTaskId = isset($_POST['parent_id']) ? mysqli_real_escape_string($conn, $_POST['parent_id']) : null;

    // Get and process assignees
    $assignees = isset($_POST['taskAssigneeUp2']) ? $_POST['taskAssigneeUp2'] : array();
    $assigneeString = !empty($assignees) ? implode(',', array_map('intval', $assignees)) : '';

    // Prepare other fields for update
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $deadline = mysqli_real_escape_string($conn, $_POST['deadline']);
    $startdate = mysqli_real_escape_string($conn, $_POST['startdate']);
    $enddate = mysqli_real_escape_string($conn, $_POST['enddate']);
    $projectid = mysqli_real_escape_string($conn, $_POST['projectid']);
    $classificationid = mysqli_real_escape_string($conn, $_POST['classificationid']);
    $priorityid = mysqli_real_escape_string($conn, $_POST['priorityid']);
    $statusid = mysqli_real_escape_string($conn, $_POST['statusid']);
    $userId = $_SESSION['userid']; // Get the current user ID from session

    // Update query
    $update_query = "UPDATE pm_projecttasktb SET 
        subject = '$subject',
        description = '$description',
        deadline = " . ($deadline ? "'$deadline'" : "NULL") . ",
        startdate = " . ($startdate ? "'$startdate'" : "NULL") . ",
        enddate = " . ($enddate ? "'$enddate'" : "NULL") . ",
        projectid = '$projectid',
        classificationid = '$classificationid',
        priorityid = '$priorityid',
        statusid = '$statusid',
        assignee = " . ($assigneeString ? "'$assigneeString'" : "NULL") . "
        WHERE id = '$taskId'";

    if (mysqli_query($conn, $update_query)) {
        // Update assignees in the task assignee table
        $update_assignees = "UPDATE pm_taskassigneetb SET assigneeid = CASE taskid ";
        foreach ($assignees as $assignee) {
            $update_assignees .= "WHEN taskid = '$taskId' THEN '$assignee' ";
        }
        $update_assignees .= "END WHERE taskid = '$taskId'";
        mysqli_query($conn, $update_assignees);

        // Get the creator ID from the task
        $creatorQuery = "SELECT createdbyid FROM pm_projecttasktb WHERE id = '$taskId'";
        $creatorResult = mysqli_query($conn, $creatorQuery);
        $creatorData = mysqli_fetch_assoc($creatorResult);
        $creatorId = $creatorData['createdbyid'] ?? null;

        // Send email notification with parent task ID and creator ID
        try {
            // Add debug logging
            error_log("Sending subtask update notification for task ID: $taskId");
            error_log("Current user ID: $userId");
            error_log("Parent task ID: $parentTaskId");
            error_log("Assignee string: $assigneeString");
            
            $emailSent = sendSubtaskUpdateNotification($conn, $taskId, $subject, $assigneeString, $userId, $parentTaskId);
            if (!$emailSent) {
                error_log("Failed to send subtask update notification email");
            } else {
                error_log("Successfully sent subtask update notification");
            }
        } catch (Exception $e) {
            error_log("Error sending subtask update notification: " . $e->getMessage());
        }
        
        // Add audit trail
        $auditRemarks = "UPDATED SUBTASK | " . $subject;
        $audit_query = "INSERT INTO `sys_audit` (module, remarks, userid) VALUES ('$moduleid', '$auditRemarks', '$userId')";
        mysqli_query($conn, $audit_query);
        
        $response['success'] = true;
        $response['message'] = "Subtask updated successfully";
    } else {
        throw new Exception("Error updating task: " . mysqli_error($conn));
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

// Close database connection
mysqli_close($conn);

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>