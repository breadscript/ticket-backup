<?php
// Clear any previous output
ob_clean();

// Set JSON header
header('Content-Type: application/json');

try {
    // Include database connection
    include('../conn/db.php');
    
    // Check if $conn is set
    if (!$mysqlconn) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
        $status = mysqli_real_escape_string($mysqlconn, $_POST['status']);
        
        // Map status to ID and color
        switch($status) {
            case 'available':
                $status_id = 1; // Active
                $color = 'green';
                break;
            case 'pending':
                $status_id = 2; // Pending
                $color = 'orange';
                break;
            case 'inactive':
                $status_id = 3; // Inactive
                $color = 'grey';
                break;
            case 'deleted':
                $status_id = 4; // Deleted
                $color = 'red';
                break;
            default:
                $status_id = 1; // Default to Active
                $color = 'green';
        }
        
        // Get the user ID from session or however you're tracking the current user
        $user_id = 1; // Replace with actual user ID
        
        // Update both status and user_statusid
        $sql = "UPDATE sys_usertb SET status = $status_id, user_statusid = $status_id WHERE id = $user_id";
        
        if (mysqli_query($mysqlconn, $sql)) {
            // Verify the update
            $verify_sql = "SELECT user_statusid FROM sys_usertb WHERE id = $user_id";
            $result = mysqli_query($mysqlconn, $verify_sql);
            $row = mysqli_fetch_assoc($result);
            
            echo json_encode([
                'success' => true,
                'status_id' => $status_id,
                'color' => $color,
                'current_status' => $row['user_statusid']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => mysqli_error($mysqlconn),
                'query' => $sql
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid request method or missing status'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

exit();
?>
