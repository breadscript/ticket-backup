<?php
include('../check_session.php');
$moduleid = 4;
$conn = connectionDB();

// Helper function to get user names from IDs
function getUserNames($conn, $idString) {
    if (empty($idString)) return '';
    $ids = array_filter(array_map('intval', explode(',', $idString)));
    if (empty($ids)) return '';
    
    $sql = "SELECT id, user_firstname, user_lastname FROM sys_usertb WHERE id IN (" . implode(',', $ids) . ")";
    $result = mysqli_query($conn, $sql);
    if (!$result) return '';
    
    $names = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $names[] = $row['user_firstname'] . ' ' . $row['user_lastname'];
    }
    return implode(', ', $names);
}

// Get and sanitize input data
$assetUserid = mysqli_real_escape_string($conn, $_POST['assetUserid']);
$asset_tag = mysqli_real_escape_string($conn, $_POST['asset_tag']);
$asset_id = mysqli_real_escape_string($conn, $_POST['asset_id']);

// Get asset_id from database using asset_tag
$get_asset_id = "SELECT id FROM inv_inventorylisttb WHERE asset_tag = '$asset_tag'";
$asset_id_result = mysqli_query($conn, $get_asset_id);
$asset_id_row = mysqli_fetch_assoc($asset_id_result);
$asset_id = $asset_id_row['id'];

// Add new fields
$checkout_date = mysqli_real_escape_string($conn, $_POST['checkout_date']);
$exp_checkout_date = mysqli_real_escape_string($conn, $_POST['exp_checkout_date']);
$status = mysqli_real_escape_string($conn, $_POST['status']);
$department = mysqli_real_escape_string($conn, $_POST['department']);
$company = mysqli_real_escape_string($conn, $_POST['company']);
$position = isset($_POST['position']) ? mysqli_real_escape_string($conn, $_POST['position']) : '';
$emp_location = isset($_POST['emp_location']) ? mysqli_real_escape_string($conn, $_POST['emp_location']) : '';

// Handle multiple values for current_owner
$current_owner = isset($_POST['current_owner']) ? implode(',', $_POST['current_owner']) : '';

// Handle NULL values
$current_owner_sql = $current_owner === "" ? "NULL" : "'$current_owner'";
$position_sql = $position === "" ? "NULL" : "'$position'";
$emp_location_sql = $emp_location === "" ? "NULL" : "'$emp_location'";

// Handle date fields
$checkout_date_sql = empty($checkout_date) ? "NULL" : "'$checkout_date'";
$exp_checkout_date_sql = empty($exp_checkout_date) ? "NULL" : "'$exp_checkout_date'";

// Update query
$query = "UPDATE inv_inventorylisttb SET 
    current_owner = $current_owner_sql,
    status = '$status',
    department = '$department',
    company = '$company',
    position = $position_sql,
    emp_location = $emp_location_sql,
    checkout_date = $checkout_date_sql,
    exp_checkout_date = $exp_checkout_date_sql
WHERE asset_tag = '$asset_tag'";

$result = mysqli_query($conn, $query);

if ($result) {
    // Success
    echo "<div class='alert alert-success fade in'>";
    echo "Asset has been updated successfully!";
    echo "</div>";
    
    // Get user names for current owner
    $current_owner_names = getUserNames($conn, $current_owner);

    // Create a formatted log entry with checkout information
    $logRemarks = implode(' | ', [
        "Asset checked out: " . $asset_tag,
        "Status: " . $status,
        "Department: " . $department,
        "Company: " . $company,
        "Position: " . ($position ? $position : "Not set"),
        "Location: " . ($emp_location ? $emp_location : "Not set"),
        "Check Out To: " . $current_owner_names,
        "Check Out Date: " . ($checkout_date ? $checkout_date : "Not set"),
        "Expected Check Out Date: " . ($exp_checkout_date ? $exp_checkout_date : "Not set")
    ]);

    // Insert the main log entry
    $mainLogQuery = "INSERT INTO inv_logs (module, remarks, userid, asset_id_ref, datetimecreated, type) 
                     VALUES ('Inventory', '$logRemarks', '$assetUserid', '$asset_id', NOW(), 'UPDATE')";
    mysqli_query($conn, $mainLogQuery);

    // Get old values before update for comparison
    $fields = [
        'current_owner',
        'status',
        'department',
        'company',
        'position',
        'emp_location',
        'checkout_date',
        'exp_checkout_date'
    ];

    // Field display names for logs
    $field_display_names = [
        'current_owner' => 'Check Out To',
        'status' => 'Status',
        'department' => 'Department',
        'company' => 'Company',
        'position' => 'Position',
        'emp_location' => 'Location',
        'checkout_date' => 'Check Out Date',
        'exp_checkout_date' => 'Expected Check Out Date'
    ];

    // Get old values
    $old_values_query = "SELECT " . implode(',', $fields) . " FROM inv_inventorylisttb WHERE asset_tag = '$asset_tag'";
    $old_values_result = mysqli_query($conn, $old_values_query);
    $old_values = mysqli_fetch_assoc($old_values_result);

    // New values from POST data
    $new_values = [
        'current_owner' => $current_owner,
        'status' => $status,
        'department' => $department,
        'company' => $company,
        'position' => $position,
        'emp_location' => $emp_location,
        'checkout_date' => $checkout_date,
        'exp_checkout_date' => $exp_checkout_date
    ];

    // Add field-by-field log entries
    foreach ($fields as $field) {
        $old_value = isset($old_values[$field]) ? mysqli_real_escape_string($conn, $old_values[$field]) : '';
        $new_value = isset($new_values[$field]) ? mysqli_real_escape_string($conn, $new_values[$field]) : '';

        // Special handling for current_owner
        if ($field === 'current_owner') {
            $old_value = getUserNames($conn, $old_value);
            $new_value = getUserNames($conn, $new_values[$field]);
        }

        // Handle date fields
        if (in_array($field, ['checkout_date', 'exp_checkout_date'])) {
            $old_value = ($old_value === "NULL" || $old_value === null || $old_value === '') ? '' : date('Y-m-d', strtotime($old_value));
            $new_value = ($new_value === "NULL" || $new_value === null || $new_value === '') ? '' : date('Y-m-d', strtotime($new_value));
        }

        // Only log if there's a change
        if ($old_value != $new_value) {
            $display_name = $field_display_names[$field];
            
            $logQuery = "INSERT INTO inv_logs 
                        (module, remarks, userid, asset_id_ref, datetimecreated, type, field_name, old_value, new_value) 
                        VALUES 
                        ('Inventory', 
                         'Changed $display_name', 
                         '$assetUserid', 
                         '$asset_id', 
                         NOW(), 
                         'UPDATE', 
                         '$display_name', 
                         " . ($old_value === '' ? "NULL" : "'$old_value'") . ", 
                         " . ($new_value === '' ? "NULL" : "'$new_value'") . ")";
            
            mysqli_query($conn, $logQuery);
            
            if (mysqli_error($conn)) {
                error_log("SQL Error in log entry: " . mysqli_error($conn));
                error_log("Query was: " . $logQuery);
            }
        }
    }

} else {
    // Error
    echo "<div class='alert alert-danger fade in'>";
    echo "Failed to update asset information!<br>";
    echo "SQL Error: " . mysqli_error($conn);
    echo "</div>";
}

// Close database connection
mysqli_close($conn);
?>