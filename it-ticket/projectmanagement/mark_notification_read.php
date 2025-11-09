<?php
include('../conn/db.php');
include('../check_session.php');

header('Content-Type: application/json');

$response = array('success' => false, 'message' => '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
    $user_id = $_SESSION['userid'] ?? 0;

    if ($notification_id && $user_id) {
        // Update the read_at timestamp for the notification
        $sql = "UPDATE pm_thread_likes 
                SET read_at = NOW() 
                WHERE id = ? 
                AND EXISTS (
                    SELECT 1 
                    FROM pm_threadtb t 
                    WHERE t.id = pm_thread_likes.thread_id 
                    AND t.createdbyid = ?
                )";
        
        $stmt = mysqli_prepare($mysqlconn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $notification_id, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $response['success'] = true;
                $response['message'] = 'Notification marked as read';
            } else {
                $response['message'] = 'No notification found or already read';
            }
        } else {
            $response['message'] = 'Database error: ' . mysqli_error($mysqlconn);
        }
        mysqli_stmt_close($stmt);
    } else {
        $response['message'] = 'Invalid notification ID or user ID';
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
?> 