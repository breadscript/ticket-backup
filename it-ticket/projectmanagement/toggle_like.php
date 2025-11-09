<?php
include('../conn/db.php');

// Ensure request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Get POST data
$comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

if (!$comment_id || !$user_id) {
    http_response_code(400);
    exit('Missing required parameters');
}

// Check if user already liked the comment
$check_sql = "SELECT id FROM pm_thread_likes WHERE thread_id = ? AND user_id = ?";
$stmt = mysqli_prepare($mysqlconn, $check_sql);
mysqli_stmt_bind_param($stmt, "ii", $comment_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    // Unlike - remove the like
    $sql = "DELETE FROM pm_thread_likes WHERE thread_id = ? AND user_id = ?";
    $action = 'unliked';
} else {
    // Like - add new like
    $sql = "INSERT INTO pm_thread_likes (thread_id, user_id, created_at) VALUES (?, ?, NOW())";
    $action = 'liked';
}

$stmt = mysqli_prepare($mysqlconn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $comment_id, $user_id);

if (mysqli_stmt_execute($stmt)) {
    // Get updated like count
    $count_sql = "SELECT COUNT(*) as count FROM pm_thread_likes WHERE thread_id = ?";
    $stmt = mysqli_prepare($mysqlconn, $count_sql);
    mysqli_stmt_bind_param($stmt, "i", $comment_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $count = mysqli_fetch_assoc($result)['count'];
    
    echo json_encode([
        'success' => true,
        'action' => $action,
        'likes' => $count
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
} 