<?php
include('../conn/db.php'); // adjust path if needed

// Use CSV format to avoid Excel security warning
header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=IT_DEPT_SMARTPHONE_SIM_DETAILS.csv");
header("Pragma: no-cache");
header("Expires: 0");

$conn = connectionDB();

// Enhanced query to get all smartphone details
$query = "SELECT 
    inv.asset_id,
    inv.asset_tag, 
    inv.asset_type, 
    inv.manufacturer, 
    inv.model, 
    inv.serial_number, 
    inv.sim_number, 
    inv.current_owner,
    inv.company,
    inv.status,
    inv.warranty_end,
    sys.user_firstname, 
    sys.user_lastname 
FROM inv_inventorylisttb inv
LEFT JOIN sys_usertb sys ON inv.current_owner = sys.id 
WHERE inv.statusid = 0 
AND inv.asset_type = 'Smartphone' 
ORDER BY inv.asset_tag ASC";

$result = mysqli_query($conn, $query);

// Function to escape CSV fields
function escapeCsvField($field) {
    // Convert to string and handle null values
    $field = (string)$field;
    if ($field === '') {
        return 'N/A';
    }
    
    // If field contains comma, newline, or quote, wrap in quotes and escape quotes
    if (strpos($field, ',') !== false || strpos($field, '\n') !== false || strpos($field, '"') !== false) {
        return '"' . str_replace('"', '""', $field) . '"';
    }
    
    return $field;
}

// Output CSV headers
echo "Asset Reference Number,Asset Tag,SIM Number,Serial Number,Checked Out To,Company,Asset Type,Manufacturer,Model,Status,Warranty End\n";

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Handle current owner name
        $owner_fullname = '';
        if (!empty($row['current_owner'])) {
            // Handle multiple owners (comma-separated IDs)
            $currentOwnerIds = explode(',', $row['current_owner']);
            $currentOwnerNames = [];
            
            foreach ($currentOwnerIds as $ownerId) {
                $ownerId = trim($ownerId);
                if ($ownerId !== '') {
                    // If we have firstname/lastname from the main query
                    if (!empty($row['user_firstname']) || !empty($row['user_lastname'])) {
                        $fullName = trim(($row['user_firstname'] ?? '') . ' ' . ($row['user_lastname'] ?? ''));
                        if ($fullName !== '') {
                            $currentOwnerNames[] = strtoupper($fullName);
                        }
                    } else {
                        // Fallback: query for the owner name
                        $ownerQuery = "SELECT user_firstname, user_lastname FROM sys_usertb WHERE id = '" . mysqli_real_escape_string($conn, $ownerId) . "'";
                        $ownerResult = mysqli_query($conn, $ownerQuery);
                        if ($ownerRow = mysqli_fetch_assoc($ownerResult)) {
                            $fullName = trim(($ownerRow['user_firstname'] ?? '') . ' ' . ($ownerRow['user_lastname'] ?? ''));
                            $currentOwnerNames[] = strtoupper($fullName);
                        } else {
                            $currentOwnerNames[] = "UNKNOWN";
                        }
                    }
                }
            }
            $owner_fullname = implode(', ', $currentOwnerNames);
        }
        
        if (empty($owner_fullname) || $owner_fullname === ' ') {
            $owner_fullname = 'N/A';
        }
        
        // Format warranty end date
        $warranty_info = '';
        if (!empty($row['warranty_end'])) {
            $warranty_end = strtotime($row['warranty_end']);
            $current_date = time();
            $warranty_date = date('F d, Y', $warranty_end);
            
            if ($warranty_end > $current_date) {
                $warranty_info = $warranty_date . ' (Under Warranty)';
            } else {
                $warranty_info = $warranty_date . ' (Warranty Expired)';
            }
        } else {
            $warranty_info = 'N/A (No Warranty Info)';
        }
        
        // Prepare CSV row data
        $csvRow = [
            $row['asset_id'] ?? 'N/A',
            $row['asset_tag'] ?? 'N/A',
            $row['sim_number'] ?? 'N/A',
            $row['serial_number'] ?? 'N/A',
            $owner_fullname,
            $row['company'] ?? 'N/A',
            $row['asset_type'] ?? 'N/A',
            $row['manufacturer'] ?? 'N/A',
            $row['model'] ?? 'N/A',
            $row['status'] ?? 'Unknown',
            $warranty_info
        ];
        
        // Escape and output CSV row
        $escapedRow = array_map('escapeCsvField', $csvRow);
        echo implode(',', $escapedRow) . "\n";
    }
} else {
    // No data found
    echo "No smartphone inventory items found.\n";
}

// Close database connection
mysqli_close($conn);
exit;
?>