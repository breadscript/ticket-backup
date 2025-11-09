<?php
include('../conn/db.php');
session_start();

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $sql = "SELECT file_data FROM pm_threadtb WHERE id = ?";
    $stmt = $mysqlconn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment');
        echo $row['file_data'];
        exit();
    }
}

header('HTTP/1.1 404 Not Found');
echo 'File not found';
?>