<?php
require_once('../conn/db.php');
require_once('../check_session.php');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_POST['taskId']) || empty($_POST['taskId'])) {
    $response['message'] = 'Subtask ID is required';
    echo json_encode($response);
    exit;
}

$taskId = mysqli_real_escape_string($mysqlconn, $_POST['taskId']);

$query = "UPDATE pm_projecttasktb SET statusid = 5 WHERE id = '$taskId' AND type = 'subtask'";
$result = mysqli_query($mysqlconn, $query);

if ($result) {
    $response['success'] = true;
    $response['message'] = 'Subtask deleted successfully';
} else {
    $response['message'] = 'Error deleting subtask: ' . mysqli_error($mysqlconn);
}

echo json_encode($response);