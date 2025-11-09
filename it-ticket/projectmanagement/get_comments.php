<?php
include('../conn/db.php');

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

function buildCommentTree($comments, $parentId = null) {
    $branch = array();
    foreach ($comments as $comment) {
        if ($comment['parent_id'] == $parentId) {
            // Process file_data into array if it exists
            if (!empty($comment['file_data'])) {
                $comment['files'] = explode(',', $comment['file_data']);
            } else {
                $comment['files'] = [];
            }
            
            $children = buildCommentTree($comments, $comment['id']);
            if ($children) {
                $comment['replies'] = $children;
            }
            $branch[] = $comment;
        }
    }
    return $branch;
}

$task_id = isset($_GET['taskId']) ? intval($_GET['taskId']) : 0;

$sql = "SELECT t.*, u.username, u.photo, u.user_firstname, u.user_lastname, u.emailadd,
        (SELECT COUNT(*) FROM pm_thread_likes WHERE thread_id = t.id) as like_count,
        EXISTS(SELECT 1 FROM pm_thread_likes WHERE thread_id = t.id AND user_id = ?) as user_liked
        FROM pm_threadtb t
        JOIN sys_usertb u ON t.createdbyid = u.id
        JOIN pm_projecttasktb pt ON t.taskid = pt.id
        WHERE t.taskid = ?
        AND (t.type IS NULL OR t.type != 'file')
        AND DATE(t.datetimecreated) >= DATE(pt.datetimecreated)
        ORDER BY t.datetimecreated DESC";

$stmt = mysqli_prepare($mysqlconn, $sql);
$user_id = isset($_GET['userId']) ? intval($_GET['userId']) : 0;
mysqli_stmt_bind_param($stmt, "ii", $user_id, $task_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$comments = array();
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $comments[] = array(
            'id' => $row['id'],
            'parent_id' => $row['parent_id'],
            'username' => $row['username'],
            'firstname' => $row['user_firstname'],
            'lastname' => $row['user_lastname'],
            'email' => $row['emailadd'],
            'message' => $row['message'],
            'time' => time_elapsed_string($row['datetimecreated']),
            'datetimecreated' => date('F j, Y', strtotime($row['datetimecreated'])),
            'file_data' => $row['file_data'],
            'photo' => !empty($row['photo']) ? "../" . $row['photo'] : "../assets/images/avatars/user.png",
            'likes' => intval($row['like_count']),
            'user_liked' => $row['user_liked'] == 1
        );
    }
}

// Build hierarchical comment tree
$commentTree = buildCommentTree($comments);

header('Content-Type: application/json');
echo json_encode($commentTree);
?>