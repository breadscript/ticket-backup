<?php
date_default_timezone_set('Asia/Manila');
include "conn/db.php";
include('proxy.php');

// Get current user ID from session
$current_user_id = $_SESSION['userid'] ?? 0;

// Function to get user-specific query conditions
function getUserQueryConditions($user_id) {
    if ($user_id == 271) {
        return "AND company NOT IN ('The Laguna Creamery Inc.', 'BMC', 'Metro Pacific Agro Ventures Inc.', 'Metro Pacific Fresh Farms Inc', 'Metro Pacific Dairy Farms, Inc.', 'Metro Pacific Nova Agro Tech, Inc.')";
    } elseif ($user_id == 383) {
        return "AND company = 'Metro Pacific Fresh Farms Inc'";
    }
    return "";
}

// Get parameters
$asset_type = $_POST['asset_type'] ?? null;
$user_conditions = getUserQueryConditions($current_user_id);

// Build query
$where_clause = "WHERE statusid = 0";
if ($asset_type) {
    if ($asset_type === 'accessories') {
        $where_clause .= " AND asset_type NOT IN ('PC Desktop', 'Laptop', 'Smartphone', 'Tablet', 'Printers')";
    } else {
        $where_clause .= " AND asset_type = '$asset_type'";
    }
}

$query = "SELECT * FROM inv_inventorylisttb $where_clause $user_conditions ORDER BY datetimecreated DESC, asset_id DESC";
$result = mysqli_query($conn, $query);

$data = [];
if($result && mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        // Create complete asset reference number
        $asset_reference = '';
        if (!empty($row['asset_id'])) {
            $asset_reference = $row['asset_id'];
            
            // Add additional reference components if available
            $reference_parts = [];
            
            // Add asset tag if different from asset_id
            if (!empty($row['asset_tag']) && $row['asset_tag'] != $row['asset_id']) {
                $reference_parts[] = $row['asset_tag'];
            }
            
            // Add order number if available
            if (!empty($row['order_number'])) {
                $reference_parts[] = 'PO:' . $row['order_number'];
            }
            
            // Add serial number if available
            if (!empty($row['serial_number'])) {
                $reference_parts[] = 'SN:' . $row['serial_number'];
            }
            
            // Combine all parts
            if (!empty($reference_parts)) {
                $asset_reference .= ' (' . implode(' | ', $reference_parts) . ')';
            }
        } else {
            $asset_reference = 'N/A';
        }
        
        // Get user name for current owner
        $user_name = 'N/A';
        if (!empty($row['current_owner'])) {
            $user_query = "SELECT user_firstname, user_lastname FROM sys_usertb WHERE id = '" . mysqli_real_escape_string($conn, $row['current_owner']) . "'";
            $user_result = mysqli_query($conn, $user_query);
            if ($user_result && $user_data = mysqli_fetch_assoc($user_result)) {
                $user_name = $user_data['user_firstname'] . ' ' . $user_data['user_lastname'];
            }
        }
        
        // Format warranty end
        $warranty_info = '';
        if (empty($row['warranty_end']) || $row['warranty_end'] == '0000-00-00' || $row['warranty_end'] == '0000-00-00 00:00:00') {
            $warranty_info = 'N/A - No Warranty Info';
        } else {
            $warranty_end = strtotime($row['warranty_end']);
            if ($warranty_end === false || $warranty_end < 0) {
                $warranty_info = 'N/A - Invalid Date';
            } else {
                $current_date = time();
                if ($warranty_end > $current_date) {
                    $warranty_info = date('M d, Y', $warranty_end) . ' - Under Warranty';
                } else {
                    $warranty_info = date('M d, Y', $warranty_end) . ' - Expired';
                }
            }
        }
        
        $data[] = [
            $asset_reference,
            $row['order_number'] ?: 'N/A',
            $row['asset_tag'] ?: 'N/A',
            $row['serial_number'] ?: 'N/A',
            $user_name,
            $row['emp_location'] ?: 'N/A',
            $row['company'] ?: 'N/A',
            $row['asset_type'] ?: 'N/A',
            $row['status'] ?: 'Unknown',
            $row['osver'] ?: 'N/A',
            $row['antivirus'] ?: 'N/A',
            $row['pms_date'] ?: 'N/A',
            $warranty_info,
            '<div class="btn-group btn-group-xs" role="group">
                <button type="button" class="btn btn-success btn-xs" onclick="showCheckout(\'' . $row['asset_tag'] . '\');" data-toggle="modal" data-target="#myModal_checkout" title="Check Out">
                    <i class="ace-icon fa fa-pencil"></i>
                </button>
                <button type="button" class="btn btn-info btn-xs" onclick="showUpdateAsset(\'' . $row['asset_tag'] . '\');" data-toggle="modal" data-target="#myModal_updateTask" title="Edit">
                    <i class="ace-icon fa fa-edit"></i>
                </button>
                <a href="assetInfoPage.php?asset_id=' . $row['asset_id'] . '" class="btn btn-xs btn-danger" title="View Details">
                    <i class="ace-icon fa fa-eye"></i>
                </a>
            </div>'
        ];
    }
}

// Return JSON response in DataTables format
header('Content-Type: application/json');
echo json_encode([
    'data' => $data,
    'recordsTotal' => count($data),
    'recordsFiltered' => count($data)
]);
?>