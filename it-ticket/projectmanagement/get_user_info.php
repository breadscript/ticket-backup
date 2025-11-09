<?php
include('../conn/db.php');

if (isset($_GET['username'])) {
    $username = mysqli_real_escape_string($mysqlconn, $_GET['username']);
    
    $sql = "SELECT username, user_firstname, user_lastname, emailadd, photo 
            FROM sys_usertb 
            WHERE username = ?";
            
    $stmt = $mysqlconn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $userData = array(
            'username' => $row['username'],
            'firstname' => $row['user_firstname'],
            'lastname' => $row['user_lastname'],
            'email' => $row['emailadd'],
            'photo' => !empty($row['photo']) ? "../" . $row['photo'] : "../assets/images/avatars/user.png"
        );
        
        header('Content-Type: application/json');
        echo json_encode($userData);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
    }
    
    $stmt->close();
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Username parameter is required']);
}
?> 