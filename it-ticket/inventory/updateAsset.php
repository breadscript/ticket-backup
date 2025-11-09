<?php
include('../check_session.php');
$moduleid = 4;
$conn = connectionDB();

// Add the getUserNames function at the top of the file
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

// Helper function to format value or return "Not set"
function formatValue($value) {
    return !empty($value) ? $value : "Not set";
}

// Helper function to format price
function formatPrice($price) {
    return !empty($price) ? '₱' . number_format((float)$price, 2) : "Not set";
}

// Helper function to format date
function formatDate($date) {
    return !empty($date) ? date('Y-m-d', strtotime($date)) : "Not set";
}

// Get and sanitize input data
$assetUserid = isset($_POST['assetUserid']) ? mysqli_real_escape_string($conn, $_POST['assetUserid']) : '';
$asset_tag = isset($_POST['asset_tag']) ? mysqli_real_escape_string($conn, $_POST['asset_tag']) : '';

// Handle multiple values for assignee, current_owner, and previous_owner
$assignee = isset($_POST['assignee']) ? implode(',', $_POST['assignee']) : '';
$current_owner = isset($_POST['current_owner']) ? implode(',', $_POST['current_owner']) : '';
$previous_owner = isset($_POST['previous_owner']) ? implode(',', $_POST['previous_owner']) : '';
$accessories = isset($_POST['accessories']) && is_array($_POST['accessories']) && !empty($_POST['accessories']) 
    ? implode(',', array_filter($_POST['accessories'])) 
    : '';

// Handle NULL for accessories
$accessories_sql = empty($accessories) ? "NULL" : "'$accessories'";

// Debug logging
error_log('POST data: ' . print_r($_POST, true));
error_log('Accessories value: ' . $accessories);
error_log('Accessories SQL: ' . $accessories_sql);

$status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : '';
$department = isset($_POST['department']) ? mysqli_real_escape_string($conn, $_POST['department']) : '';
$company = isset($_POST['company']) ? mysqli_real_escape_string($conn, $_POST['company']) : '';
$asset_type = isset($_POST['asset_type']) ? mysqli_real_escape_string($conn, $_POST['asset_type']) : '';
$manufacturer = isset($_POST['manufacturer']) ? mysqli_real_escape_string($conn, $_POST['manufacturer']) : '';
$model = isset($_POST['model']) ? mysqli_real_escape_string($conn, $_POST['model']) : '';
$color = isset($_POST['color']) ? mysqli_real_escape_string($conn, $_POST['color']) : '';
$serial_number = isset($_POST['serial_number']) ? mysqli_real_escape_string($conn, $_POST['serial_number']) : '';
$sim_number = isset($_POST['sim_number']) ? mysqli_real_escape_string($conn, $_POST['sim_number']) : '';
$chasis_number = isset($_POST['chasis_number']) ? mysqli_real_escape_string($conn, $_POST['chasis_number']) : '';
$charger_number = isset($_POST['charger_number']) ? mysqli_real_escape_string($conn, $_POST['charger_number']) : '';
$purchase_date = isset($_POST['purchase_date']) ? mysqli_real_escape_string($conn, $_POST['purchase_date']) : '';
$purchase_price = isset($_POST['purchase_price']) ? mysqli_real_escape_string($conn, $_POST['purchase_price']) : '';
$order_number = isset($_POST['order_number']) ? mysqli_real_escape_string($conn, $_POST['order_number']) : '';
$condition_notes = isset($_POST['condition_notes']) ? mysqli_real_escape_string($conn, $_POST['condition_notes']) : '';
$warranty_start = isset($_POST['warranty_start']) ? mysqli_real_escape_string($conn, $_POST['warranty_start']) : '';
$warranty_end = isset($_POST['warranty_end']) ? mysqli_real_escape_string($conn, $_POST['warranty_end']) : '';
$pms_date = isset($_POST['pms_date']) ? mysqli_real_escape_string($conn, $_POST['pms_date']) : '';
$license = isset($_POST['license']) ? mysqli_real_escape_string($conn, $_POST['license']) : '';
$osver = isset($_POST['osver']) ? mysqli_real_escape_string($conn, $_POST['osver']) : '';
$antivirus = isset($_POST['antivirus']) ? mysqli_real_escape_string($conn, $_POST['antivirus']) : '';
$anydesk_address = isset($_POST['anydesk_address']) ? mysqli_real_escape_string($conn, $_POST['anydesk_address']) : '';
// Handle bag checkbox (1 if checked, 0 if unchecked)
$bag = isset($_POST['bag']) && $_POST['bag'] == '1' ? 1 : 0;

// Handle NULL values
$previous_owner_sql = $previous_owner === "" ? "NULL" : "'$previous_owner'";
$condition_notes_sql = $condition_notes === "" ? "NULL" : "'$condition_notes'";
$current_owner_sql = $current_owner === "" ? "NULL" : "'$current_owner'";
$assignee_sql = $assignee === "" ? "NULL" : "'$assignee'";
$license_sql = $license === "" ? "NULL" : "'$license'";
$osver_sql = $osver === "" ? "NULL" : "'$osver'";
$antivirus_sql = $antivirus === "" ? "NULL" : "'$antivirus'";
$anydesk_address_sql = $anydesk_address === "" ? "NULL" : "'$anydesk_address'";
$chasis_number_sql = $chasis_number === "" ? "NULL" : "'$chasis_number'";
$charger_number_sql = $charger_number === "" ? "NULL" : "'$charger_number'";

// *** CRITICAL FIX: Get old values BEFORE updating the database ***
$fields = [
    'current_owner', 'previous_owner', 'status', 'department', 'company', 
    'asset_type', 'manufacturer', 'model', 'color', 'serial_number', 
    'sim_number', 'chasis_number', 'charger_number', 'purchase_date', 'purchase_price', 'order_number', 
    'condition_notes', 'assignee', 'warranty_start', 'warranty_end', 
    'pms_date', 'license', 'accessories', 'osver', 'antivirus', 'anydesk_address', 'bag'
];

// Get old values BEFORE the update
$old_values_query = "SELECT " . implode(',', $fields) . " FROM inv_inventorylisttb WHERE asset_tag = '$asset_tag'";
$old_values_result = mysqli_query($conn, $old_values_query);
$old_values = mysqli_fetch_assoc($old_values_result);

// New values from POST data
$new_values = [
    'current_owner' => $current_owner,
    'previous_owner' => $previous_owner,
    'status' => $status,
    'department' => $department,
    'company' => $company,
    'asset_type' => $asset_type,
    'manufacturer' => $manufacturer,
    'model' => $model,
    'color' => $color,
    'serial_number' => $serial_number,
    'sim_number' => $sim_number,
    'chasis_number' => $chasis_number,
    'charger_number' => $charger_number,
    'purchase_date' => $purchase_date,
    'purchase_price' => $purchase_price,
    'order_number' => $order_number,
    'condition_notes' => $condition_notes,
    'assignee' => $assignee,
    'warranty_start' => $warranty_start,
    'warranty_end' => $warranty_end,
    'pms_date' => $pms_date,
    'license' => $license,
    'accessories' => $accessories,
    'osver' => $osver,
    'antivirus' => $antivirus,
    'anydesk_address' => $anydesk_address,
    'bag' => $bag
];

// Build the update query (preserve existing values when incoming is empty)
$query = "UPDATE inv_inventorylisttb SET 
    current_owner    = COALESCE(NULLIF('$current_owner',''), current_owner),
    previous_owner   = COALESCE(NULLIF('$previous_owner',''), previous_owner),
    status           = COALESCE(NULLIF('$status',''), status),
    department       = COALESCE(NULLIF('$department',''), department),
    company          = COALESCE(NULLIF('$company',''), company),
    asset_type       = COALESCE(NULLIF('$asset_type',''), asset_type),
    manufacturer     = COALESCE(NULLIF('$manufacturer',''), manufacturer),
    model            = COALESCE(NULLIF('$model',''), model),
    color            = COALESCE(NULLIF('$color',''), color),
    serial_number    = COALESCE(NULLIF('$serial_number',''), serial_number),
    sim_number       = COALESCE(NULLIF('$sim_number',''), sim_number),
    chasis_number    = COALESCE(NULLIF('$chasis_number',''), chasis_number),
    charger_number   = COALESCE(NULLIF('$charger_number',''), charger_number),
    purchase_date    = COALESCE(NULLIF('$purchase_date',''), purchase_date),
    purchase_price   = COALESCE(NULLIF('$purchase_price',''), purchase_price),
    order_number     = COALESCE(NULLIF('$order_number',''), order_number),
    condition_notes  = COALESCE(NULLIF('$condition_notes',''), condition_notes),
    assignee         = COALESCE(NULLIF('$assignee',''), assignee),
    warranty_start   = COALESCE(NULLIF('$warranty_start',''), warranty_start),
    warranty_end     = COALESCE(NULLIF('$warranty_end',''), warranty_end),
    pms_date         = COALESCE(NULLIF('$pms_date',''), pms_date),
    license          = COALESCE(NULLIF('$license',''), license),
    accessories      = COALESCE(NULLIF('$accessories',''), accessories),
    osver            = COALESCE(NULLIF('$osver',''), osver),
    antivirus        = COALESCE(NULLIF('$antivirus',''), antivirus),
    anydesk_address  = COALESCE(NULLIF('$anydesk_address',''), anydesk_address),
    bag              = $bag
WHERE asset_tag = '$asset_tag'";

// Debug log the final SQL query
error_log('Update query: ' . $query);

// Execute the update query
$result = mysqli_query($conn, $query);

if ($result) {
    // Success
    echo "<div class='alert alert-success fade in'>";
    echo "Asset has been updated successfully!";
    echo "</div>";
    
    // Debug log
    error_log('Update successful for asset_tag: ' . $asset_tag);
    
    // Get asset_id from database using asset_tag
    $get_asset_id = "SELECT id FROM inv_inventorylisttb WHERE asset_tag = '$asset_tag'";
    $asset_id_result = mysqli_query($conn, $get_asset_id);
    $asset_id_row = mysqli_fetch_assoc($asset_id_result);
    $asset_id = $asset_id_row['id'];

    // Get user names for current owner
    $current_owner_names = getUserNames($conn, $current_owner);

    // Create a formatted log entry with all asset information
    $logRemarks = implode(' | ', [
        "Asset checked out: " . $asset_tag,
        "Asset Type: " . formatValue($asset_type),
        "Manufacturer: " . formatValue($manufacturer),
        "Model: " . formatValue($model),
        "Serial Number: " . formatValue($serial_number),
        "SIM Number: " . formatValue($sim_number),
        "Chasis Number: " . formatValue($chasis_number),
        "Charger Number: " . formatValue($charger_number),
        "Color: " . formatValue($color),
        "Company: " . formatValue($company),
        "Checked Out To: " . formatValue($current_owner_names),
        "Accessories: " . formatValue($accessories),
        "Status: " . formatValue($status),
        "Department: " . formatValue($department),
        "License: " . formatValue($license),
        "OS Version: " . formatValue($osver),
        "Antivirus: " . formatValue($antivirus),
        "Purchase Date: " . formatDate($purchase_date),
        "Purchase Price: " . formatPrice($purchase_price),
        "Order Number: " . formatValue($order_number),
        "Warranty Start: " . formatDate($warranty_start),
        "Warranty End: " . formatDate($warranty_end),
        "PMS Date: " . formatDate($pms_date),
        "Condition Notes: " . formatValue($condition_notes),
        "Anydesk Address: " . formatValue($anydesk_address),
        "Has Bag: " . ($bag == 1 ? 'Yes' : 'No')
    ]);

    // Insert the main log entry
    $mainLogQuery = "INSERT INTO inv_logs (module, remarks, userid, asset_id_ref, datetimecreated, type) 
                     VALUES ('Inventory', '$logRemarks', '$assetUserid', '$asset_id', NOW(), 'UPDATE')";
    mysqli_query($conn, $mainLogQuery);

    // Field display names for logs
    $field_display_names = [
        'current_owner' => 'Current Owner',
        'previous_owner' => 'Previous Owner',
        'status' => 'Status',
        'department' => 'Department',
        'company' => 'Company',
        'asset_type' => 'Asset Type',
        'manufacturer' => 'Manufacturer',
        'model' => 'Model',
        'color' => 'Color',
        'serial_number' => 'Serial Number',
        'sim_number' => 'SIM Number',
        'chasis_number' => 'Chasis Number',
        'charger_number' => 'Charger Number',
        'purchase_date' => 'Purchase Date',
        'purchase_price' => 'Purchase Price',
        'order_number' => 'Order Number',
        'condition_notes' => 'Condition Notes',
        'assignee' => 'Assignee',
        'warranty_start' => 'Warranty Start',
        'warranty_end' => 'Warranty End',
        'pms_date' => 'PMS Date',
        'license' => 'License',
        'accessories' => 'Accessories',
        'osver' => 'OS Version',
        'antivirus' => 'Antivirus',
        'anydesk_address' => 'Anydesk Address',
        'bag' => 'Has Bag'
    ];

    // Add field-by-field log entries
    foreach ($fields as $field) {
        $old_value = isset($old_values[$field]) ? $old_values[$field] : '';
        $new_value = isset($new_values[$field]) ? $new_values[$field] : '';

        // Handle date fields
        if (in_array($field, ['purchase_date', 'warranty_start', 'warranty_end', 'pms_date'])) {
            $old_value = ($old_value === "NULL" || $old_value === null || $old_value === '') ? '' : date('Y-m-d', strtotime($old_value));
            $new_value = ($new_value === "NULL" || $new_value === null || $new_value === '') ? '' : date('Y-m-d', strtotime($new_value));
        }
        
        // Handle boolean fields (bag)
        if ($field === 'bag') {
            $old_value = ($old_value === "NULL" || $old_value === null || $old_value === '') ? '0' : (string)$old_value;
            $new_value = ($new_value === "NULL" || $new_value === null || $new_value === '') ? '0' : (string)$new_value;
            // Convert 0/1 to Yes/No for display
            $old_value_display = ($old_value == '1') ? 'Yes' : 'No';
            $new_value_display = ($new_value == '1') ? 'Yes' : 'No';
            $old_value = $old_value_display;
            $new_value = $new_value_display;
        }

        // Only log if there's a change
        if ($old_value != $new_value) {
            $display_name = $field_display_names[$field];
            $old_value_escaped = mysqli_real_escape_string($conn, $old_value);
            $new_value_escaped = mysqli_real_escape_string($conn, $new_value);
            
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
                         " . ($old_value_escaped === '' ? "NULL" : "'$old_value_escaped'") . ", 
                         " . ($new_value_escaped === '' ? "NULL" : "'$new_value_escaped'") . ")";
            
            mysqli_query($conn, $logQuery);
            
            // Log any SQL errors
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
    
    // Debug log
    error_log('Update failed for asset_tag: ' . $asset_tag . ' Error: ' . mysqli_error($conn));
}

// Close database connection
mysqli_close($conn);
?>