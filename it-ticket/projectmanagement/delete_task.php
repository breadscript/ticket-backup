<?php
include '../conn/db.php'; // Include your database connection

// Check if taskId is provided
if (!isset($_POST['taskId'])) {
    echo json_encode(['success' => false, 'message' => 'Task ID not provided']);
    exit;
}

$taskId = intval($_POST['taskId']);

try {
    // Begin transaction
    $mysqlconn->begin_transaction();

    // Delete from your actual task table
    $stmt = $mysqlconn->prepare("DELETE FROM pm_projecttasktb WHERE id = ?");
    $stmt->bind_param("i", $taskId);
    $result = $stmt->execute();

    if ($result) {
        // Commit transaction
        $mysqlconn->commit();
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Failed to delete task");
    }

} catch (Exception $e) {
    // Rollback transaction on error
    $mysqlconn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$mysqlconn->close();
?>