<?php
include('check_session.php');

// Check if message_id is provided
if (!isset($_POST['message_id']) || !is_numeric($_POST['message_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid message ID']);
    exit;
}

$message_id = (int)$_POST['message_id'];
$user_id = $_SESSION['userid'] ?? 0;

// Update the read_at timestamp for the message
$sql = "UPDATE pm_threadtb 
        SET read_at = NOW() 
        WHERE id = ? 
        AND (
            taskid IN (SELECT id FROM pm_projecttasktb WHERE createdbyid = ?)
            OR parent_id IN (SELECT id FROM pm_threadtb WHERE createdbyid = ?)
            OR message LIKE CONCAT('%@', ?, '%')
        )";

$stmt = mysqli_prepare($mysqlconn, $sql);
$username = $_SESSION['username'] ?? '';
mysqli_stmt_bind_param($stmt, "iiis", $message_id, $user_id, $user_id, $username);

if (mysqli_stmt_execute($stmt)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to mark message as read']);
} 