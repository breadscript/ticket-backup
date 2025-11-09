<?php
include('../check_session.php');
require_once('task_notification.php');
$conn = connectionDB();

// Ensure clean output buffer at start
if (ob_get_length()) ob_clean();

// Initialize response array
$response = array(
    'success' => false,
    'message' => '',
    'debug' => '' // For development purposes
);

try {
    // Validate required fields
    $required_fields = array(
        'taskProjectOwner' => 'Project Name',
        'taskClassification' => 'Classification',
        'subtaskSubject' => 'Subject',
        'subtaskPriority' => 'Priority Level',
        'subtaskAssignee' => 'Assignee',
        'subtaskTargetDate' => 'Target Date',
        'subtaskStartDate' => 'Start Date'
    );

    foreach ($required_fields as $field => $label) {
        if (empty($_POST[$field])) {
            throw new Exception($label . " is required");
        }
    }

    // Sanitize inputs
    $subject = mysqli_real_escape_string($conn, $_POST['subtaskSubject']);
    $priority = mysqli_real_escape_string($conn, $_POST['subtaskPriority']);
    $targetDate = mysqli_real_escape_string($conn, $_POST['subtaskTargetDate']); 
    $startDate = mysqli_real_escape_string($conn, $_POST['subtaskStartDate']);
    $endDate = mysqli_real_escape_string($conn, $_POST['subtaskEndDate']);
    $description = mysqli_real_escape_string($conn, $_POST['subtaskDescription']);
    $parentTaskId = mysqli_real_escape_string($conn, $_POST['parentTaskId']);
    $projectId = mysqli_real_escape_string($conn, $_POST['taskProjectOwner']);
    $classificationId = mysqli_real_escape_string($conn, $_POST['taskClassification']);
    $userId = $_SESSION['userid'];
    error_log("Current User ID: " . $userId); // Add this line to log the user ID
    $response['debug_userid'] = $userId; // Add this line to include userId in response

    // For the assignees array
    $assignees = isset($_POST['subtaskAssignee']) ? $_POST['subtaskAssignee'] : array();
    $escapedAssignees = array();
    foreach ($assignees as $assignee) {
        $escapedAssignees[] = mysqli_real_escape_string($conn, $assignee);
    }
    $assigneesString = implode(',', $escapedAssignees);

    // Get parent task's classification ID and project ID
    $parentQuery = "SELECT classificationid, projectid, istask FROM pm_projecttasktb WHERE id = '$parentTaskId'";
    $parentResult = mysqli_query($conn, $parentQuery);
    
    if (!$parentResult) {
        throw new Exception("Error fetching parent task details: " . mysqli_error($conn));
    }
    
    $parentData = mysqli_fetch_assoc($parentResult);
    $classificationId = $parentData['classificationid'];
    $projectId = $parentData['projectid'];
    $isTask = $parentData['istask'];

    // Get current timestamp
    $currentDateTime = date('Y-m-d H:i:s');

    // Generate SRN ID for subtask
    $countQuery = "SELECT COUNT(*) as total FROM pm_projecttasktb";
    $countResult = mysqli_query($conn, $countQuery);
    $countRow = mysqli_fetch_assoc($countResult);
    $currentCount = $countRow['total'] + 1;
    
    // Generate SRN
    $srn_id = "SRN:" . date('YmdHis') . $currentCount;

    // Insert query
    $query = "INSERT INTO pm_projecttasktb (
        subject,
        description,
        deadline,
        startdate,
        enddate,
        priorityid,
        statusid,
        createdbyid,
        datetimecreated,
        assignee,
        parent_id,
        istask,
        type,
        classificationid,
        projectid,
        srn_id
    ) VALUES (
        '$subject',
        '$description',
        '$targetDate',
        '$startDate',
        '$endDate',
        '$priority',
        '1',
        '$userId',
        '$currentDateTime',
        '$assigneesString',
        '$parentTaskId',
        '$isTask',
        'subtask',
        '$classificationId',
        '$projectId',
        '$srn_id'
    )";

    if (mysqli_query($conn, $query)) {
        $lastId = mysqli_insert_id($conn);
        
        // Send notification for the new subtask
        try {
            $emailSent = sendSubtaskCreateNotification($conn, $lastId, $subject, $assignees, $userId, $parentTaskId);
            if (!$emailSent) {
                error_log("Failed to send subtask notification email");
            }
        } catch (Exception $e) {
            error_log("Error sending subtask notification: " . $e->getMessage());
        }
        
        $response['success'] = true;
        $response['message'] = "Subtask created successfully";
        $response['last_id'] = $lastId;
        $response['reload'] = true;
    } else {
        throw new Exception("Error creating subtask: " . mysqli_error($conn));
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    $response['debug'] = error_get_last(); // For development purposes
}

// Close database connection
mysqli_close($conn);

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Ensure there's no whitespace or other output before the JSON
echo json_encode($response);
exit;
?>