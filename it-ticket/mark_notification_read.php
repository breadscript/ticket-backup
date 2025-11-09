<?php
include('check_session.php');

// Check if notification_id is provided
if (!isset($_POST['notification_id']) || !is_numeric($_POST['notification_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit;
}

$notification_id = (int)$_POST['notification_id'];
$user_id = $_SESSION['userid'] ?? 0;

// Update the read_at timestamp for the notification
$sql = "UPDATE pm_thread_likes l
        JOIN pm_threadtb t ON l.thread_id = t.id
        SET l.read_at = NOW() 
        WHERE l.id = ? 
        AND t.createdbyid = ?";

$stmt = mysqli_prepare($mysqlconn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $notification_id, $user_id);

if (mysqli_stmt_execute($stmt)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
} 