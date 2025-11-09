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
    $ticketUserid = isset($_POST['ticketUserid']) ? trim($_POST['ticketUserid']) : '';
    $ticketProjectOwner = isset($_POST['ticketProjectOwner']) ? trim($_POST['ticketProjectOwner']) : '';
    $ticketClassification = isset($_POST['ticketClassification']) ? trim($_POST['ticketClassification']) : '';
    $ticketPriority = isset($_POST['ticketPriority']) ? trim($_POST['ticketPriority']) : '';
    $ticketSubject = isset($_POST['ticketSubject']) ? trim($_POST['ticketSubject']) : '';
    $ticketTargetDate = isset($_POST['ticketTargetDate']) ? trim($_POST['ticketTargetDate']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';

    // Debug logging
    error_log("Starting ticket save process");
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));

    // Validate required fields
    if (empty($ticketUserid) || empty($ticketProjectOwner) || empty($ticketSubject)) {
        throw new Exception("Please fill in all required fields!");
    }

    $ticketStartDate = "1900-01-01";
    $ticketEndDate = "1900-01-01";
    $ticketIdentification = 2;
    if (empty($description)) {$description="NULL";}

    // Check for duplicate entries first
    $myquery = "SELECT subject FROM pm_projecttasktb WHERE subject = ? AND projectid = ? AND classificationid = ? LIMIT 1";
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
    $countQuery = "SELECT COUNT(*) as total FROM pm_projecttasktb";
    $countResult = mysqli_query($conn, $countQuery);
    $countRow = mysqli_fetch_assoc($countResult);
    $currentCount = $countRow['total'] + 1;
    
    // Generate SRN
    $srn_id = "SRN:" . date('YmdHis') . $currentCount;

    // Modified insert query to include srn_id
    $myqueryxx = "INSERT INTO pm_projecttasktb (createdbyid,classificationid,priorityid,subject,deadline,description,projectid,istask,startdate,enddate,srn_id)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = $conn->prepare($myqueryxx);
    $stmt->bind_param("sssssssssss", 
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
        $srn_id
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
        $emailSent = sendTicketNotification($conn, $taskId, $ticketSubject, $ticketUserid);
        if (!$emailSent) {
            error_log("Failed to send ticket notification email");
        }
    } catch (Exception $e) {
        error_log("Error sending ticket notification: " . $e->getMessage());
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