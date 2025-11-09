<?php
include('../conn/db.php');
include('../check_session.php');

// Establish database connection
$conn = connectionDB();
if (!$conn) {
    die("Database connection failed");
}

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
$status_filter = $_GET['status'] ?? '';
$company_filter = $_GET['company'] ?? '';
$asset_type = $_GET['asset_type'] ?? '';

// Function to get user names from current_owner field
function getCurrentOwnerNames($current_owner, $conn) {
    if (empty($current_owner)) {
        return 'N/A';
    }
    
    $currentOwnerIds = explode(',', $current_owner);
    $currentOwnerNames = [];
    
    foreach ($currentOwnerIds as $ownerId) {
        $ownerId = trim($ownerId);
        if ($ownerId !== '') {
            $user_query = "SELECT user_firstname, user_lastname FROM sys_usertb WHERE id = '" . mysqli_real_escape_string($conn, $ownerId) . "'";
            $user_result = mysqli_query($conn, $user_query);
            if ($user_result && $user_data = mysqli_fetch_assoc($user_result)) {
                $fullName = trim(($user_data['user_firstname'] ?? '') . ' ' . ($user_data['user_lastname'] ?? ''));
                if ($fullName !== '') {
                    $currentOwnerNames[] = strtoupper($fullName);
                }
            } else {
                $currentOwnerNames[] = "UNKNOWN";
            }
        }
    }
    
    return empty($currentOwnerNames) ? 'N/A' : implode(', ', $currentOwnerNames);
}

// Function to format warranty info
function formatWarrantyInfo($warranty_end) {
    if (empty($warranty_end) || $warranty_end == '0000-00-00' || $warranty_end == '0000-00-00 00:00:00') {
        return 'N/A (No Warranty Info)';
    }
    
    $warranty_timestamp = strtotime($warranty_end);
    if ($warranty_timestamp === false || $warranty_timestamp < 0) {
        return 'N/A (Invalid Date)';
    }
    
    $current_date = time();
    $warranty_date = date('M d, Y', $warranty_timestamp);
    
    if ($warranty_timestamp > $current_date) {
        return $warranty_date . ' (Under Warranty)';
    } else {
        return $warranty_date . ' (Expired)';
    }
}

// Function to format PMS date
function formatPmsDate($pms_date) {
    if (empty($pms_date) || $pms_date == '0000-00-00') {
        return 'N/A';
    }
    
    $pms_timestamp = strtotime($pms_date);
    if ($pms_timestamp !== false && $pms_timestamp > 0) {
        return date('M d, Y', $pms_timestamp);
    }
    
    return 'N/A';
}

// Function to escape XML content
function escapeXml($content) {
    return htmlspecialchars($content, ENT_XML1, 'UTF-8');
}

// Get user conditions
$user_conditions = getUserQueryConditions($current_user_id);

// Define asset types
$asset_types = [
    'All Assets' => null,
    'PC Desktop' => 'PC Desktop',
    'Laptop' => 'Laptop', 
    'Smartphone' => 'Smartphone',
    'Tablet' => 'Tablet',
    'Printers' => 'Printers',
    'Accessories' => 'accessories'
];

// Create Excel XML content
$xml_content = '<?xml version="1.0" encoding="UTF-8"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
  <Title>Inventory Export</Title>
  <Author>System</Author>
  <Created>' . date('c') . '</Created>
 </DocumentProperties>
 <Styles>
  <Style ss:ID="Header">
   <Font ss:Bold="1"/>
   <Interior ss:Color="#CCCCCC" ss:Pattern="Solid"/>
  </Style>
 </Styles>';

// Generate worksheets for each asset type
foreach ($asset_types as $sheet_name => $asset_type_filter) {
    $xml_content .= '
 <Worksheet ss:Name="' . escapeXml($sheet_name) . '">
  <Table>';
    
    // Add headers
    $xml_content .= '
   <Row>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Asset Reference Number</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">PO Number</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Asset Tag</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Serial Number</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Checked Out to</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Location</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Company</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Asset Type</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Status</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">OS Version</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Antivirus</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">PMS Date</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Warranty End</Data></Cell>
   </Row>';
    
    // Build query for this asset type
    $where_clause = "WHERE statusid = 0";
    
    if ($asset_type_filter === 'accessories') {
        $where_clause .= " AND asset_type NOT IN ('PC Desktop', 'Laptop', 'Smartphone', 'Tablet', 'Printers')";
    } elseif ($asset_type_filter) {
        $where_clause .= " AND asset_type = '" . mysqli_real_escape_string($conn, $asset_type_filter) . "'";
    }
    
    if ($status_filter && $status_filter !== 'all') {
        $where_clause .= " AND status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
    }
    
    if ($company_filter) {
        $where_clause .= " AND company LIKE '%" . mysqli_real_escape_string($conn, $company_filter) . "%'";
    }
    
    $query = "SELECT * FROM inv_inventorylisttb $where_clause $user_conditions ORDER BY asset_tag ASC";
    $result = mysqli_query($conn, $query);
    
    $row_count = 0;
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $row_count++;
            
            // Asset Reference Number - Only the main asset_id value
            $asset_reference = $row['asset_id'] ?: 'N/A';
            
            // Get user name
            $user_name = getCurrentOwnerNames($row['current_owner'], $conn);
            
            // Format dates
            $warranty_info = formatWarrantyInfo($row['warranty_end']);
            $pms_date = formatPmsDate($row['pms_date']);
            
            // Add data row
            $xml_content .= '
   <Row>
    <Cell><Data ss:Type="String">' . escapeXml($asset_reference) . '</Data></Cell>
    <Cell><Data ss:Type="String">' . escapeXml($row['order_number'] ?: 'N/A') . '</Data></Cell>
    <Cell><Data ss:Type="String">' . escapeXml($row['asset_tag'] ?: 'N/A') . '</Data></Cell>
    <Cell><Data ss:Type="String">' . escapeXml($row['serial_number'] ?: 'N/A') . '</Data></Cell>
    <Cell><Data ss:Type="String">' . escapeXml($user_name) . '</Data></Cell>
    <Cell><Data ss:Type="String">' . escapeXml($row['emp_location'] ?: 'N/A') . '</Data></Cell>
    <Cell><Data ss:Type="String">' . escapeXml($row['company'] ?: 'N/A') . '</Data></Cell>
    <Cell><Data ss:Type="String">' . escapeXml($row['asset_type'] ?: 'N/A') . '</Data></Cell>
    <Cell><Data ss:Type="String">' . escapeXml($row['status'] ?: 'Unknown') . '</Data></Cell>
    <Cell><Data ss:Type="String">' . escapeXml($row['osver'] ?: 'N/A') . '</Data></Cell>
    <Cell><Data ss:Type="String">' . escapeXml($row['antivirus'] ?: 'N/A') . '</Data></Cell>
    <Cell><Data ss:Type="String">' . escapeXml($pms_date) . '</Data></Cell>
    <Cell><Data ss:Type="String">' . escapeXml($warranty_info) . '</Data></Cell>
   </Row>';
        }
    } else {
        $xml_content .= '
   <Row>
    <Cell><Data ss:Type="String">No items found for this asset type.</Data></Cell>
   </Row>';
    }
    
    $xml_content .= '
  </Table>';
    
    // Add AutoFilter (only if there's data)
    if ($row_count > 0) {
        $xml_content .= '
  <AutoFilter x:Range="R1C1:R' . ($row_count + 1) . 'C13" xmlns="urn:schemas-microsoft-com:office:excel" />';
    }
    
    $xml_content .= '
 </Worksheet>';
}

$xml_content .= '
</Workbook>';

// Set headers for download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="IT_ADMIN_INVENTORY_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: cache, must-revalidate');
header('Pragma: public');

// Output the XML content
echo $xml_content;

mysqli_close($conn);
exit;
?>
