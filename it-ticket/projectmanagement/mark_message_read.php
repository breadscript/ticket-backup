<?php
include('../conn/db.php');
include('../check_session.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message_id = isset($_POST['message_id']) ? intval($_POST['message_id']) : 0;
    $user_id = $_SESSION['userid'] ?? 0;

    if ($message_id && $user_id) {
        // Update the read_at timestamp for the message
        $sql = "UPDATE pm_threadtb 
                SET read_at = NOW() 
                WHERE id = ? 
                AND (
                    -- Message is on user's task
                    EXISTS (
                        SELECT 1 
                        FROM pm_projecttasktb pt 
                        WHERE pt.id = pm_threadtb.taskid 
                        AND pt.createdbyid = ?
                    )
                    -- Message is a reply to user's comment
                    OR parent_id IN (SELECT id FROM pm_threadtb WHERE createdbyid = ?)
                    -- Message mentions the user
                    OR message LIKE CONCAT('%@', (SELECT username FROM sys_usertb WHERE id = ?), '%')
                )";
        
        $stmt = mysqli_prepare($mysqlconn, $sql);
        mysqli_stmt_bind_param($stmt, "iiii", $message_id, $user_id, $user_id, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Check if any rows were affected
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            
            // Log the result for debugging
            error_log("Mark message read: message_id=$message_id, user_id=$user_id, affected_rows=$affected_rows");
            
            echo json_encode(['success' => true, 'affected_rows' => $affected_rows]);
        } else {
            error_log("Mark message read error: " . mysqli_error($mysqlconn));
            echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($mysqlconn)]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?> 