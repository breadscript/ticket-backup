<?php
include('../conn/db.php');

// Check if asset_id was provided
if(isset($_POST['asset_id']) && !empty($_POST['asset_id'])) {
    // Get the asset ID from the POST request
    $asset_id = $_POST['asset_id'];
    
    // Connect to the database
    $conn = connectionDB();
    
    // Prepare the update query (soft delete by changing status to 2)
    $query = "UPDATE inv_inventorylisttb SET statusid = '2' WHERE asset_id = ?";
    
    // Prepare statement
    $stmt = $conn->prepare($query);
    
    if($stmt) {
        // Bind parameters
        $stmt->bind_param("s", $asset_id);
        
        // Execute the statement
        if($stmt->execute()) {
            // Success
            echo json_encode(array('success' => true, 'message' => 'Asset has been deleted successfully.'));
        } else {
            // Error with execution
            echo json_encode(array('success' => false, 'message' => 'Failed to delete asset: ' . $stmt->error));
        }
        
        // Close the statement
        $stmt->close();
    } else {
        // Error with statement preparation
        echo json_encode(array('success' => false, 'message' => 'Failed to prepare statement: ' . $conn->error));
    }
    
    // Close the connection
    $conn->close();
} else {
    // Asset ID not provided
    echo json_encode(array('success' => false, 'message' => 'Asset ID is required.'));
}
?>
