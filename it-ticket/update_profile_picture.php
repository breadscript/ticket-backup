<?php
// Include database connection
require_once 'conn/db.php';

// Check if a session is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize response array
$response = array(
    'status' => 'error',
    'message' => 'An error occurred'
);

// Check if user is logged in
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    $response['message'] = 'User not logged in';
    echo json_encode($response);
    exit;
}

$user_id = intval($_SESSION['userid']);

// Check if file was uploaded
if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'No file uploaded or upload error';
    echo json_encode($response);
    exit;
}

// Validate file type
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$file_type = $_FILES['profile_picture']['type'];
if (!in_array($file_type, $allowed_types)) {
    $response['message'] = 'Invalid file type. Please upload a JPG, PNG, or GIF image';
    echo json_encode($response);
    exit;
}

// Validate file size (max 2MB)
if ($_FILES['profile_picture']['size'] > 2 * 1024 * 1024) {
    $response['message'] = 'File size exceeds 2MB limit';
    echo json_encode($response);
    exit;
}

// Create upload directory if it doesn't exist
$upload_dir = 'assets/images/profile_pictures/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename
$file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
$new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_extension;
$upload_path = $upload_dir . $new_filename;

// Move uploaded file
if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
    // Connect to database
    $conn = connectionDB();
    
    // Store the relative path in the database
    $photo_url = 'assets/images/profile_pictures/' . $new_filename;
    $update_sql = "UPDATE sys_usertb SET photo = ? WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "si", $photo_url, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Update session with new photo URL
        $_SESSION['userphoto'] = $photo_url;
        
        $response['status'] = 'success';
        $response['message'] = 'Profile picture updated successfully';
        $response['photo_url'] = $photo_url;
    } else {
        $response['message'] = 'Database update failed: ' . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
} else {
    $response['message'] = 'Failed to move uploaded file';
}

// Return JSON response
echo json_encode($response);
?> 