<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require('../conn/db.php');
require_once('../projectmanagement/upload_file.php');
require_once '../phpmailer/src/PHPMailer.php';
require_once '../phpmailer/src/SMTP.php';
require_once '../phpmailer/src/Exception.php';
require_once('../projectmanagement/email_utils.php');
require_once('../projectmanagement/task_notification.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$conn = connectionDB();
$moduleid=1;

// Set proper headers for JSON response
header('Content-Type: application/json');

try {
    // Get and validate POST data
    $ticketUserid = '153';  // Hardcode the value to 153
    $ticketProjectOwner = isset($_POST['ticketProjectOwner']) ? trim($_POST['ticketProjectOwner']) : '3';  // Get from POST instead of hardcoding
    $ticketClassification = '4';  // Hardcode the value to 4
    $ticketPriority = '3';
    $ticketSubject = isset($_POST['ticketSubject']) ? trim($_POST['ticketSubject']) : '';
    $ticketTargetDate = isset($_POST['ticketTargetDate']) ? trim($_POST['ticketTargetDate']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';

    // Get company name from the selected company ID
    $companyQuery = "SELECT clientname FROM sys_clienttb WHERE id = ?";
    $stmt = $conn->prepare($companyQuery);
    $stmt->bind_param("s", $ticketProjectOwner);
    $stmt->execute();
    $companyResult = $stmt->get_result();
    $companyRow = $companyResult->fetch_assoc();
    $companyName = $companyRow ? mb_convert_case($companyRow['clientname'], MB_CASE_TITLE, "UTF-8") : '';

    // Debug logging
    error_log("Starting ticket save process");
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));

    // Validate required fields
    if (empty($ticketUserid) || empty($ticketProjectOwner) || empty($ticketSubject)) {
        throw new Exception("Please fill in all required fields!");
    }

    $ticketStartDate = date('Y-m-d');
    $ticketEndDate = "0000-00-00";
    $ticketIdentification = 2;
    if (empty($description)) {$description="NULL";}

    // Check for duplicate entries first
    $myquery = "SELECT subject FROM pm_projecttasktb2 WHERE subject = ? AND projectid = ? AND classificationid = ? LIMIT 1";
    $stmt = $conn->prepare($myquery);
    $stmt->bind_param("sss", $ticketSubject, $ticketProjectOwner, $ticketClassification);
    $stmt->execute();
    $result = $stmt->get_result();
    $num_rows = $result->num_rows;
    $stmt->close();

    if($num_rows > 0) {
        throw new Exception("Duplicate Entry, Please check the details again.");
    }

    // Get current count of tasks for SRN generation
    $countQuery = "SELECT COUNT(*) as total FROM pm_projecttasktb2";
    $countResult = mysqli_query($conn, $countQuery);
    $countRow = mysqli_fetch_assoc($countResult);
    $currentCount = $countRow['total'] + 1;
    
    // Generate SRN
    $srn_id = "SRN:" . date('YmdHis') . $currentCount;

    // Modified insert query to include company_name
    $myqueryxx = "INSERT INTO pm_projecttasktb2 (createdbyid,classificationid,priorityid,subject,deadline,description,projectid,istask,startdate,enddate,srn_id,company_name)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = $conn->prepare($myqueryxx);
    $stmt->bind_param("ssssssssssss", 
        $ticketUserid,
        $ticketClassification,
        $ticketPriority,
        $ticketSubject,
        $ticketTargetDate,
        $description,
        $ticketProjectOwner,
        $ticketIdentification,
        $ticketStartDate,
        $ticketEndDate,
        $srn_id,
        $companyName
    );

    if(!$stmt->execute()) {
        throw new Exception("Database error: " . $stmt->error);
    }

    $taskId = $conn->insert_id;
    $fileMessage = "";
    
    // Handle file uploads if present
    if(isset($_FILES['attachFile']) && !empty($_FILES['attachFile']['name'][0])) {
        try {
            $uploadResult = uploadFiles($conn, $taskId, $ticketUserid, $_FILES['attachFile']);
            if ($uploadResult['status'] === 'success') {
                $fileMessage = " with " . count($uploadResult['files']) . " attached file(s)";
            }
        } catch (Exception $e) {
            error_log("File upload failed: " . $e->getMessage());
            $fileMessage = " (file upload failed: " . $e->getMessage() . ")";
        }
    }

    // Update project status if needed
    $statusQuery = "SELECT statusid FROM sys_projecttb WHERE id = ?";
    $stmt = $conn->prepare($statusQuery);
    $stmt->bind_param("s", $ticketProjectOwner);
    $stmt->execute();
    $statusResult = $stmt->get_result();
    $statusRow = $statusResult->fetch_assoc();
    
    if($statusRow['statusid'] == 1 || $statusRow['statusid'] == 6) {
        $updateQuery = "UPDATE sys_projecttb SET statusid = '3' WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("s", $ticketProjectOwner);
        $stmt->execute();
    }
    
    // Add audit trail
    $auditRemarks1 = "REGISTER NEW TICKET | " . $ticketSubject;
    $queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES (?,?,?)";
    $stmt = $conn->prepare($queryaud1);
    $stmt->bind_param("sss", $moduleid, $auditRemarks1, $ticketUserid);
    $stmt->execute();

    // Send email notification
    try {
        error_log("Attempting to send ManCom notification email");
        
        // Send ManCom notification
        $mancomEmailSent = sendManComNotification($conn, $taskId, $ticketSubject, $ticketUserid, $_FILES);
        if (!$mancomEmailSent) {
            error_log("Failed to send ManCom notification email - returned false");
            $fileMessage .= " (Note: Email notification failed to send)";
        } else {
            error_log("ManCom notification email sent successfully");
            $fileMessage .= " (Email notification sent)";
        }
    } catch (Exception $e) {
        error_log("Error sending notifications: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        $fileMessage .= " (Email notification failed: " . $e->getMessage() . ")";
    }

    // Before sending response, log it
    $response = [
        'status' => 'success',
        'message' => "New Ticket has been saved!" . $fileMessage,
        'taskId' => $taskId
    ];
    error_log("Sending response: " . print_r($response, true));
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in saveticket.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    $response = [
        'status' => 'error',
        'message' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ];
    error_log("Sending error response: " . print_r($response, true));
    echo json_encode($response);
}

// Make sure nothing else is output after the JSON
exit();
?>