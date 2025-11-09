<?php
// Clear any previous output
ob_clean();

// Set JSON header
header('Content-Type: application/json');

try {
    // Include database connection
    include('../conn/db.php');
    
    if (!$mysqlconn) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user_id = 1; // Replace with actual user ID
        $field = mysqli_real_escape_string($mysqlconn, $_POST['name']);
        $value = mysqli_real_escape_string($mysqlconn, $_POST['value']);
        
        // Map field names to database columns
        $field_map = [
            'username' => 'username',
            'firstname' => 'firstname',
            'lastname' => 'lastname',
            'about' => 'about',
            'city' => 'city',
            'age' => 'age',
            'signup' => 'signup_date',
            'email' => 'email'
        ];
        
        if (isset($field_map[$field])) {
            $db_field = $field_map[$field];
            $sql = "UPDATE sys_usertb SET $db_field = '$value' WHERE id = $user_id";
            
            if (mysqli_query($mysqlconn, $sql)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ]);
            } else {
                throw new Exception("Error updating profile: " . mysqli_error($mysqlconn));
            }
        } else {
            throw new Exception("Invalid field name");
        }
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

exit();
?>