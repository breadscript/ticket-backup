<?php
// Enable error reporting for debugging but prevent output
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json'); // Set JSON header


try {
    // Include your database connection
    require_once('../conn/db.php'); // Adjust path as needed
    
    // Validate taskId
    $taskId = isset($_GET['taskId']) ? intval($_GET['taskId']) : 0;
    if ($taskId <= 0) {
        throw new Exception('Invalid task ID');
    }

    // Query to get files
    $query = "SELECT t.id, t.file_data, t.datetimecreated, u.user_firstname, u.user_lastname 
              FROM pm_threadtb t 
              LEFT JOIN sys_usertb u ON t.createdbyid = u.id
              WHERE t.taskid = ? AND t.file_data IS NOT NULL AND t.file_data != ''";
    
    $stmt = mysqli_prepare($mysqlconn, $query);
    if (!$stmt) {
        throw new Exception('Query preparation failed: ' . mysqli_error($mysqlconn));
    }

    mysqli_stmt_bind_param($stmt, 'i', $taskId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $files = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Split the comma-separated file paths
        $filePaths = explode(',', $row['file_data']);
        
        // Create an entry for each file
        foreach ($filePaths as $filePath) {
            if (!empty($filePath)) {
                $files[] = [
                    'id' => $row['id'],
                    'file_data' => trim($filePath), // Remove any whitespace
                    'datetimecreated' => $row['datetimecreated'],
                    'username' => $row['user_firstname'] . ' ' . $row['user_lastname'],
                    // Add can_delete logic if needed
                    'can_delete' => true // Or implement your permission logic here
                ];
            }
        }
    }

    echo json_encode([
        'success' => true,
        'files' => $files
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>