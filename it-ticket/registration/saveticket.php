<?php
require_once('../conn/db.php');
require_once('../projectmanagement/upload_file.php');
$conn = connectionDB();
$moduleid=1;

header('Content-Type: text/html; charset=utf-8');

// Get and validate POST data
$ticketUserid = isset($_POST['ticketUserid']) ? strtoupper($_POST['ticketUserid']) : '';
$ticketProjectOwner = isset($_POST['ticketProjectOwner']) ? strtoupper($_POST['ticketProjectOwner']) : '';
$ticketClassification = isset($_POST['ticketClassification']) ? strtoupper($_POST['ticketClassification']) : '';
$ticketPriority = isset($_POST['ticketPriority']) ? strtoupper($_POST['ticketPriority']) : '';
$ticketSubject = isset($_POST['ticketSubject']) ? strtoupper($_POST['ticketSubject']) : '';
$ticketTargetDate = isset($_POST['ticketTargetDate']) ? strtoupper($_POST['ticketTargetDate']) : '';
$description = isset($_POST['description']) ? strtoupper($_POST['description']) : '';

// Validate required fields
if (empty($ticketUserid) || empty($ticketProjectOwner) || empty($ticketSubject)) {
    echo "<div class='alert alert-danger fade in'>";
    echo "Please fill in all required fields!";           
    echo "</div>";
    exit;
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
    $auditRemarks3 = "ATTEMPT TO REGISTER NEW TICKET, DUPLICATE | " . $ticketSubject;
    $queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES (?,?,?)";
    $stmt = $conn->prepare($queryaud1);
    $stmt->bind_param("sss", $moduleid, $auditRemarks3, $ticketUserid);
    $stmt->execute();

    echo "<div class='alert alert-danger fade in'>";
    echo "Duplicate Entry, Please check the details again.";           
    echo "</div>";
    exit;
}

// If no duplicate, proceed with insertion
$myqueryxx = "INSERT INTO pm_projecttasktb (createdbyid,classificationid,priorityid,subject,deadline,description,projectid,istask,startdate,enddate)
            VALUES (?,?,?,?,?,?,?,?,?,?)";
$stmt = $conn->prepare($myqueryxx);
$stmt->bind_param("ssssssssss", 
    $ticketUserid,
    $ticketClassification,
    $ticketPriority,
    $ticketSubject,
    $ticketTargetDate,
    $description,
    $ticketProjectOwner,
    $ticketIdentification,
    $ticketStartDate,
    $ticketEndDate
);

if($stmt->execute()) {
    $taskId = $conn->insert_id;
    
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

    // Return success response with a flag
    $response = array(
        'status' => 'success',
        'message' => "New Ticket has been saved!" . (isset($fileMessage) ? $fileMessage : ""),
        'redirect' => 'ticket.php'
    );
    echo json_encode($response);
} else {
    $auditRemarks2 = "ATTEMPT TO REGISTER NEW TICKET, FAILED | " . $ticketSubject;
    $queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES (?,?,?)";
    $stmt = $conn->prepare($queryaud1);
    $stmt->bind_param("sss", $moduleid, $auditRemarks2, $ticketUserid);
    $stmt->execute();

    // Return error response
    $response = array(
        'status' => 'error',
        'message' => "Failed to save information!"
    );
    echo json_encode($response);
}
?>