<?php
include('check_session.php'); 
$user_id = $_SESSION['userid'] ?? 0;

// Fetch recent likes for the user's comments
$likes_sql = "SELECT l.*, t.message, t.taskid, u.username, u.user_firstname, u.user_lastname, u.photo,
              CASE WHEN l.read_at IS NULL THEN 1 ELSE 0 END as is_unread
              FROM pm_thread_likes l
              JOIN pm_threadtb t ON l.thread_id = t.id
              JOIN sys_usertb u ON l.user_id = u.id
              WHERE t.createdbyid = ?
              ORDER BY l.created_at DESC
              LIMIT 5";
              
$likes_stmt = mysqli_prepare($mysqlconn, $likes_sql);
mysqli_stmt_bind_param($likes_stmt, "i", $user_id);
mysqli_stmt_execute($likes_stmt);
$likes_result = mysqli_stmt_get_result($likes_stmt);

$recent_likes = [];
$unread_count = 0;
while ($row = mysqli_fetch_assoc($likes_result)) {
    $recent_likes[] = $row;
    if ($row['is_unread']) {
        $unread_count++;
    }
}

// Fetch user information
$sql = "SELECT * FROM sys_usertb WHERE id = $user_id";
$result = mysqli_query($mysqlconn, $sql);
$user = mysqli_fetch_assoc($result);
$moduleid = 11;

// Map status ID to color
$currentColor = 'green'; // Default color
switch($user['user_statusid'] ?? 1) {
    case 1:
        $currentColor = 'green';
        break;
    case 2:
        $currentColor = 'orange';
        break;
    case 3:
        $currentColor = 'grey';
        break;
    case 4:
        $currentColor = 'red';
        break;
}

// Fetch messages (comments, replies, and mentions) for the user
$messages_sql = "SELECT DISTINCT t.*, 
                 u.username, u.photo, u.user_firstname, u.user_lastname,
                 pt.subject as task_subject,
                 CASE 
                    WHEN t.parent_id IS NOT NULL THEN 'reply'
                    WHEN t.message LIKE CONCAT('%@', ?, '%') THEN 'mention'
                    ELSE 'comment'
                 END as notification_type,
                 CASE WHEN t.read_at IS NULL THEN 1 ELSE 0 END as is_unread
                 FROM pm_threadtb t
                 JOIN sys_usertb u ON t.createdbyid = u.id
                 JOIN pm_projecttasktb pt ON t.taskid = pt.id
                 WHERE (
                    pt.createdbyid = ? 
                    OR t.parent_id IN (SELECT id FROM pm_threadtb WHERE createdbyid = ?)
                    OR t.message LIKE CONCAT('%@', ?, '%')
                 )
                 AND t.createdbyid != ?
                 ORDER BY is_unread DESC, t.datetimecreated DESC
                 LIMIT 10";

$messages_stmt = mysqli_prepare($mysqlconn, $messages_sql);
$username = $user['username'] ?? '';
mysqli_stmt_bind_param($messages_stmt, "siiss", $username, $user_id, $user_id, $username, $user_id);
mysqli_stmt_execute($messages_stmt);
$messages_result = mysqli_stmt_get_result($messages_stmt);

$messages = [];
$unread_messages = [];
$read_messages = [];
$unread_messages_count = 0;

while ($row = mysqli_fetch_assoc($messages_result)) {
    $messages[] = $row;
    if ($row['is_unread']) {
        $unread_messages[] = $row;
        $unread_messages_count++;
    } else {
        $read_messages[] = $row;
    }
}

// Fetch notifications (likes) for the user
$notifications_sql = "SELECT l.*, t.message, t.taskid, u.username, u.user_firstname, u.user_lastname, u.photo,
                     CASE WHEN l.read_at IS NULL THEN 1 ELSE 0 END as is_unread
                     FROM pm_thread_likes l
                     JOIN pm_threadtb t ON l.thread_id = t.id
                     JOIN sys_usertb u ON l.user_id = u.id
                     WHERE t.createdbyid = ?
                     ORDER BY l.created_at DESC
                     LIMIT 10";
                     
$notifications_stmt = mysqli_prepare($mysqlconn, $notifications_sql);
mysqli_stmt_bind_param($notifications_stmt, "i", $user_id);
mysqli_stmt_execute($notifications_stmt);
$notifications_result = mysqli_stmt_get_result($notifications_stmt);

$unread_notifications = [];
$read_notifications = [];
$unread_notifications_count = 0;

while ($row = mysqli_fetch_assoc($notifications_result)) {
    if ($row['is_unread']) {
        $unread_notifications[] = $row;
        $unread_notifications_count++;
    } else {
        $read_notifications[] = $row;
    }
}

function time_elapsed_string($datetime) {
    $now = time();
    $created = strtotime($datetime);
    $diff = $now - $created;

    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } else {
        return floor($diff / 86400) . ' days ago';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	  
    <title>Disbursement | RFP</title>

    <!-- Bootstrap -->
    <link href="../srv-v2/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="../srv-v2/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- NProgress -->
    <link href="../srv-v2/vendors/nprogress/nprogress.css" rel="stylesheet">
    <!-- iCheck -->
    <link href="../srv-v2/vendors/iCheck/skins/flat/green.css" rel="stylesheet">
    <!-- bootstrap-wysiwyg -->
    <link href="../srv-v2/vendors/google-code-prettify/bin/prettify.min.css" rel="stylesheet">
    <!-- Select2 -->
    <link href="../srv-v2/vendors/select2/dist/css/select2.min.css" rel="stylesheet">
    <!-- Switchery -->
    <link href="../srv-v2/vendors/switchery/dist/switchery.min.css" rel="stylesheet">
    <!-- starrr -->
    <link href="../srv-v2/vendors/starrr/dist/starrr.css" rel="stylesheet">
    <!-- bootstrap-daterangepicker -->
    <link href="../srv-v2/vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">

    <!-- Custom Theme Style -->
    <link href="../srv-v2/build/css/custom.min.css" rel="stylesheet">


	</head>