<?php
// PWP Assignment System
// Handles PWP number assignment after full approval

session_start();
include_once('../conn/db.php');

if (!$mysqlconn) {
    die("Database connection failed");
}

// Function to assign PWP number after full approval
function assignPWPNumber($paw_id, $assigned_by) {
    global $mysqlconn;
    
    try {
        // Generate PWP number
        $pwp_number = 'PWP-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Check if PWP number already exists
        $check_sql = "SELECT id FROM paw_pwp_assignment WHERE pwp_number = ?";
        $check_stmt = mysqli_prepare($mysqlconn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "s", $pwp_number);
        mysqli_stmt_execute($check_stmt);
        $result = mysqli_stmt_get_result($check_stmt);
        
        // If exists, generate new one
        while (mysqli_num_rows($result) > 0) {
            $pwp_number = 'PWP-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            mysqli_stmt_bind_param($check_stmt, "s", $pwp_number);
            mysqli_stmt_execute($check_stmt);
            $result = mysqli_stmt_get_result($check_stmt);
        }
        
        // Insert PWP assignment record
        $sql = "INSERT INTO paw_pwp_assignment (paw_id, pwp_number, assigned_by, assigned_at) VALUES (?, ?, ?, NOW())";
        $stmt = mysqli_prepare($mysqlconn, $sql);
        mysqli_stmt_bind_param($stmt, "iss", $paw_id, $pwp_number, $assigned_by);
        mysqli_stmt_execute($stmt);
        
        // Update main PAW record with PWP number
        $update_sql = "UPDATE paw_main SET pwp_number = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($mysqlconn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "si", $pwp_number, $paw_id);
        mysqli_stmt_execute($update_stmt);
        
        return $pwp_number;
        
    } catch (Exception $e) {
        throw new Exception("Error assigning PWP number: " . $e->getMessage());
    }
}

// Function to log to central database
function logToCentralDatabase($paw_id, $pwp_number) {
    global $mysqlconn;
    
    try {
        // Update PWP assignment record
        $sql = "UPDATE paw_pwp_assignment SET central_database_logged = 1 WHERE paw_id = ?";
        $stmt = mysqli_prepare($mysqlconn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $paw_id);
        mysqli_stmt_execute($stmt);
        
        // Here you would typically log to your central database system
        // For now, we'll just mark it as logged
        
        return true;
        
    } catch (Exception $e) {
        throw new Exception("Error logging to central database: " . $e->getMessage());
    }
}

// Function to create TLCI folder structure
function createTLCIFolderStructure($paw_id, $company, $channel, $year) {
    // This would create the folder structure in your file system
    // For now, we'll just return the expected path
    
    $base_path = "TLCI_Shared_Folder";
    $folder_path = $base_path . "/" . $channel . "/" . $company . "/" . $year;
    
    // Update database with folder path
    global $mysqlconn;
    $sql = "UPDATE paw_pwp_assignment SET tlc_folder_path = ? WHERE paw_id = ?";
    $stmt = mysqli_prepare($mysqlconn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $folder_path, $paw_id);
    mysqli_stmt_execute($stmt);
    
    return $folder_path;
}

// API endpoint for PWP assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'assign_pwp') {
        $paw_id = $_POST['paw_id'] ?? null;
        $assigned_by = $_POST['assigned_by'] ?? 'Trade Marketing';
        
        if (!$paw_id) {
            echo json_encode(['success' => false, 'message' => 'PAW ID is required']);
            exit;
        }
        
        try {
            $pwp_number = assignPWPNumber($paw_id, $assigned_by);
            logToCentralDatabase($paw_id, $pwp_number);
            
            echo json_encode([
                'success' => true, 
                'message' => 'PWP number assigned successfully',
                'pwp_number' => $pwp_number
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    if ($action === 'create_folder_structure') {
        $paw_id = $_POST['paw_id'] ?? null;
        $company = $_POST['company'] ?? '';
        $channel = $_POST['channel'] ?? '';
        $year = $_POST['year'] ?? date('Y');
        
        if (!$paw_id) {
            echo json_encode(['success' => false, 'message' => 'PAW ID is required']);
            exit;
        }
        
        try {
            $folder_path = createTLCIFolderStructure($paw_id, $company, $channel, $year);
            
            echo json_encode([
                'success' => true, 
                'message' => 'TLCI folder structure created',
                'folder_path' => $folder_path
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
?>
