<?php
include('check_session.php');
$user_id = $_SESSION['userid'] ?? 0;

// Fetch unread notifications count
$notifications_sql = "SELECT COUNT(*) as count 
                     FROM pm_thread_likes l
                     JOIN pm_threadtb t ON l.thread_id = t.id
                     WHERE t.createdbyid = ? AND l.read_at IS NULL";
$notifications_stmt = mysqli_prepare($mysqlconn, $notifications_sql);
mysqli_stmt_bind_param($notifications_stmt, "i", $user_id);
mysqli_stmt_execute($notifications_stmt);
$notifications_result = mysqli_stmt_get_result($notifications_stmt);
$notifications_count = mysqli_fetch_assoc($notifications_result)['count'];

// Fetch unread messages count
$messages_sql = "SELECT COUNT(DISTINCT t.id) as count
                 FROM pm_threadtb t
                 JOIN pm_projecttasktb pt ON t.taskid = pt.id
                 WHERE (
                    pt.createdbyid = ? 
                    OR t.parent_id IN (SELECT id FROM pm_threadtb WHERE createdbyid = ?)
                    OR t.message LIKE CONCAT('%@', ?, '%')
                 )
                 AND t.createdbyid != ?
                 AND t.read_at IS NULL";
$messages_stmt = mysqli_prepare($mysqlconn, $messages_sql);
$username = $_SESSION['username'] ?? '';
mysqli_stmt_bind_param($messages_stmt, "iiss", $user_id, $user_id, $username, $user_id);
mysqli_stmt_execute($messages_stmt);
$messages_result = mysqli_stmt_get_result($messages_stmt);
$messages_count = mysqli_fetch_assoc($messages_result)['count'];

// Return the counts as JSON
header('Content-Type: application/json');
echo json_encode([
    'notifications' => (int)$notifications_count,
    'messages' => (int)$messages_count
]); 