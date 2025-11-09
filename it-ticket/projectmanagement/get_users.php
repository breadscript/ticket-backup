<?php
// Turn off error display in the output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering to catch any unexpected output
ob_start();

include('../conn/db.php');
session_start();

// Function to return error as JSON and exit
function return_error($message) {
    // Clear any output that might have been generated
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    return_error('Not authenticated');
}

try {
    // Get the query parameter
    $query = isset($_GET['query']) ? $_GET['query'] : '';

    // Sanitize the input
    $query = mysqli_real_escape_string($mysqlconn, $query);

    // Prepare the SQL query to search for users - use LOWER() for case-insensitive search
    // Use user_statusid = 1 for active users
    $sql = "SELECT id, username, user_firstname, user_lastname, photo,
            CONCAT(user_firstname, ' ', user_lastname) as fullname
            FROM sys_usertb 
            WHERE (LOWER(username) LIKE LOWER(?) OR LOWER(user_firstname) LIKE LOWER(?) OR LOWER(user_lastname) LIKE LOWER(?))
            AND user_statusid = 1
            ORDER BY user_firstname, user_lastname
            LIMIT 10";

    $stmt = $mysqlconn->prepare($sql);
    if (!$stmt) {
        return_error('Database error: ' . $mysqlconn->error);
    }

    // Add wildcards for LIKE query
    $searchParam = "%$query%";
    $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
    
    if (!$stmt->execute()) {
        return_error('Query execution error: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        // Format the data for autocomplete - preserve original case
        $users[] = [
            'id' => $row['id'],
            'value' => $row['username'], // This is what autocomplete uses for matching
            'username' => $row['username'], // Preserve original case
            'name' => $row['fullname'],
            'avatar' => !empty($row['photo']) ? "../" . $row['photo'] : "../assets/images/avatars/user.png" // Use user's photo if available
        ];
    }

    // If query is empty, return all active users (limited to 10)
    if (empty($query) && empty($users)) {
        $sql = "SELECT id, username, user_firstname, user_lastname, photo,
                CONCAT(user_firstname, ' ', user_lastname) as fullname
                FROM sys_usertb 
                WHERE user_statusid = 1
                ORDER BY user_firstname, user_lastname
                LIMIT 10";
                
        $result = $mysqlconn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = [
                    'id' => $row['id'],
                    'value' => $row['username'],
                    'username' => $row['username'],
                    'name' => $row['fullname'],
                    'avatar' => !empty($row['photo']) ? "../" . $row['photo'] : "../assets/images/avatars/user.png"
                ];
            }
        }
    }

    // Clear any unexpected output
    ob_end_clean();
    
    // Return the results as JSON
    header('Content-Type: application/json');
    echo json_encode($users);
    exit;
    
} catch (Exception $e) {
    return_error('Server error: ' . $e->getMessage());
}
?> 