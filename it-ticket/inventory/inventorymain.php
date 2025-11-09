<?php
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d h:i:s",time());  
include "blocks/header.php";
$moduleid=4;
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

// Optimized function to get asset counts with single query
function getAssetCounts($conn, $user_conditions = "") {
    $counts = [];
    
    // Single optimized query to get all counts at once
    $query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN asset_type = 'PC Desktop' THEN 1 ELSE 0 END) as pcdesktop,
        SUM(CASE WHEN asset_type = 'Laptop' THEN 1 ELSE 0 END) as laptop,
        SUM(CASE WHEN asset_type = 'Smartphone' THEN 1 ELSE 0 END) as smartphone,
        SUM(CASE WHEN asset_type = 'Tablet' THEN 1 ELSE 0 END) as tablet,
        SUM(CASE WHEN asset_type = 'Printers' THEN 1 ELSE 0 END) as printers,
        SUM(CASE WHEN asset_type NOT IN ('PC Desktop', 'Laptop', 'Smartphone', 'Tablet', 'Printers') THEN 1 ELSE 0 END) as accessories
        FROM inv_inventorylisttb 
        WHERE statusid = 0 $user_conditions";
    
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $counts = [
            'total' => $row['total'],
            'pcdesktop' => $row['pcdesktop'],
            'laptop' => $row['laptop'],
            'smartphone' => $row['smartphone'],
            'tablet' => $row['tablet'],
            'printers' => $row['printers'],
            'accessories' => $row['accessories']
        ];
    }
    
    return $counts;
}

// Optimized function to get status counts with single query
function getStatusCounts($conn, $user_conditions = "") {
    $query = "SELECT 
        SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as available,
        SUM(CASE WHEN status = 'In repair' THEN 1 ELSE 0 END) as inrepair,
        SUM(CASE WHEN status = 'In use' THEN 1 ELSE 0 END) as inuse,
        SUM(CASE WHEN status = 'Lost' THEN 1 ELSE 0 END) as lost,
        SUM(CASE WHEN status = 'Reserved' THEN 1 ELSE 0 END) as reserved,
        SUM(CASE WHEN status = 'Retired' THEN 1 ELSE 0 END) as retired,
        SUM(CASE WHEN status = 'S.U Available' THEN 1 ELSE 0 END) as suavailable,
        SUM(CASE WHEN status = 'S.U Deployed' THEN 1 ELSE 0 END) as sudeployed,
        SUM(CASE WHEN status = 'Upcoming' THEN 1 ELSE 0 END) as upcoming
        FROM inv_inventorylisttb 
        WHERE statusid = 0 $user_conditions";
    
    $result = mysqli_query($conn, $query);
    if ($result) {
        return mysqli_fetch_assoc($result);
    }
    return [];
}

// Function to generate table row with enhanced asset reference number
function generateTableRow($row, $conn) {
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
    
    ?>
    <tr>
        <td title="<?php echo htmlspecialchars($asset_reference); ?>"><?php echo htmlspecialchars($asset_reference); ?></td>    
        <td><?php echo $row['order_number'] ?: 'N/A'; ?></td>                                                   
        <td><?php echo $row['asset_tag'] ?: 'N/A'; ?></td>
        <td><?php echo $row['serial_number'] ?: 'N/A'; ?></td>
        <td><?php 
            if (empty($row['current_owner'])) {
                echo 'N/A';
            } else {
                $user_query = "SELECT user_firstname, user_lastname FROM sys_usertb WHERE id = '" . mysqli_real_escape_string($conn, $row['current_owner']) . "'";
                $user_result = mysqli_query($conn, $user_query);
                if ($user_result && $user_data = mysqli_fetch_assoc($user_result)) {
                    echo htmlspecialchars($user_data['user_firstname'] . ' ' . $user_data['user_lastname']);
                } else {
                    echo 'N/A';
                }
            }
        ?></td>                                                         
        <td><?php echo $row['emp_location'] ?: 'N/A'; ?></td> 
        <td><?php echo $row['company'] ?: 'N/A'; ?></td>
        <td><?php echo $row['asset_type'] ?: 'N/A'; ?></td>                                                                                                                                                                                                                                     
        <td>
            <span class="status-badge status-<?php echo strtolower(str_replace([' ', '.'], ['', ''], $row['status'])); ?>">
                <?php echo $row['status'] ?: 'Unknown'; ?>
            </span>
        </td>                                                                                                   
        <td><?php echo $row['osver'] ?: 'N/A'; ?></td>
        <td><?php echo $row['antivirus'] ?: 'N/A'; ?></td>
        <td><?php echo $row['pms_date'] ?: 'N/A'; ?></td>
        <td class="warranty-info"><?php 
        if (empty($row['warranty_end']) || $row['warranty_end'] == '0000-00-00' || $row['warranty_end'] == '0000-00-00 00:00:00') {
            echo '<div class="warranty-date" style="color: #000000">N/A</div>';
            echo '<div class="warranty-status" style="color: #dc3545"><strong>No Warranty Info</strong></div>';
        } else {
            $warranty_end = strtotime($row['warranty_end']);
            if ($warranty_end === false || $warranty_end < 0) {
                echo '<div class="warranty-date" style="color: #000000">N/A</div>';
                echo '<div class="warranty-status" style="color: #dc3545"><strong>Invalid Date</strong></div>';
            } else {
                $current_date = time();
                if ($warranty_end > $current_date) {
                    echo '<div class="warranty-date" style="color: #000000">' . date('M d, Y', $warranty_end) . '</div>';
                    echo '<div class="warranty-status" style="color: #28a745"><strong>Under Warranty</strong></div>';
                } else {
                    echo '<div class="warranty-date" style="color: #000000">' . date('M d, Y', $warranty_end) . '</div>';
                    echo '<div class="warranty-status" style="color: #dc3545"><strong>Expired</strong></div>';
                }
            }
        }
    ?></td>
        <td>
        <div class="btn-group btn-group-xs" role="group">
        <button type="button" class="btn btn-success btn-xs" onclick="showCheckout('<?php echo $row['asset_tag']; ?>');" data-toggle="modal" data-target="#myModal_checkout" title="Check Out">
            <i class="ace-icon fa fa-pencil"></i>
        </button>
        <button type="button" class="btn btn-info btn-xs" onclick="showUpdateAsset('<?php echo $row['asset_tag']; ?>');" data-toggle="modal" data-target="#myModal_updateTask" title="Edit">
            <i class="ace-icon fa fa-edit"></i>
        </button>
        <button type="button" class="btn btn-info btn-xs" onclick="showDocsModal('<?php echo $row['asset_id']; ?>');" data-toggle="modal" data-target="#myModal_docs" title="Attach Files">
            <i class="ace-icon fa fa-upload"></i> 
        </button>
                <a href="assetInfoPage.php?asset_id=<?php echo $row['asset_id']; ?>" class="btn btn-xs btn-danger" title="View Details">
                    <i class="ace-icon fa fa-eye"></i>
                </a> 
            </div>
        </td>
    </tr>
    <?php
}

// Function to generate table content
function generateTableContent($asset_type = null, $table_id, $conn, $user_conditions) {
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
    
    if($result && mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            generateTableRow($row, $conn);
        }
    } else {
        $message = $asset_type ? "No " . ($asset_type === 'accessories' ? 'Accessory' : $asset_type) . " assets found" : "No inventory items found";
        echo '<tr><td colspan="14" class="dataTables_empty">' . $message . '</td></tr>';
    }
}

// Get database connection
$conn = connectionDB();
$user_conditions = getUserQueryConditions($current_user_id);
$asset_counts = getAssetCounts($conn, $user_conditions);
$status_counts = getStatusCounts($conn, $user_conditions);

?>
<!DOCTYPE html>
<style>
.input-xs {
    height: 22px;
    padding: 0px 1px;
    font-size: 10px;
    line-height: 1;
    border-radius: 0px;
}

/* Enhanced table styling for single line rows with ellipsis */
.dataTables-table {
    font-size: 12px;
    width: 100% !important;
    table-layout: fixed;
}

.dataTables-table td {
    padding: 8px 6px;
    vertical-align: middle;
    text-align: left;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    border: 1px solid #ddd;
    max-width: 0;
}

.dataTables-table th {
    padding: 10px 6px;
    font-size: 11px;
    font-weight: bold;
    text-align: left;
    background-color: #f8f9fa;
    border: 1px solid #ddd;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Column width specifications for better alignment */
.dataTables-table th:nth-child(1), .dataTables-table td:nth-child(1) { width: 12%; }  /* Asset Reference Number */
.dataTables-table th:nth-child(2), .dataTables-table td:nth-child(2) { width: 6%; }   /* PO Number */
.dataTables-table th:nth-child(3), .dataTables-table td:nth-child(3) { width: 8%; }   /* Asset Tag */
.dataTables-table th:nth-child(4), .dataTables-table td:nth-child(4) { width: 8%; }   /* Serial Number */
.dataTables-table th:nth-child(5), .dataTables-table td:nth-child(5) { width: 10%; } /* Checked Out to */
.dataTables-table th:nth-child(6), .dataTables-table td:nth-child(6) { width: 8%; }  /* Location */
.dataTables-table th:nth-child(7), .dataTables-table td:nth-child(7) { width: 10%; } /* Company */
.dataTables-table th:nth-child(8), .dataTables-table td:nth-child(8) { width: 8%; }  /* Asset Type */
.dataTables-table th:nth-child(9), .dataTables-table td:nth-child(9) { width: 8%; }  /* Status */
.dataTables-table th:nth-child(10), .dataTables-table td:nth-child(10) { width: 6%; } /* OS Version */
.dataTables-table th:nth-child(11), .dataTables-table td:nth-child(11) { width: 6%; } /* Antivirus */
.dataTables-table th:nth-child(12), .dataTables-table td:nth-child(12) { width: 6%; } /* PMS Update */
.dataTables-table th:nth-child(13), .dataTables-table td:nth-child(13) { width: 8%; } /* Warranty End */
.dataTables-table th:nth-child(14), .dataTables-table td:nth-child(14) { width: 10%; } /* Action */

/* Action buttons alignment - keep on single line */
.btn-group-xs {
    display: flex;
    flex-wrap: nowrap;
    gap: 2px;
    white-space: nowrap;
}

.btn-group-xs .btn {
    padding: 3px 6px;
    font-size: 10px;
    line-height: 1.2;
    margin: 1px;
    white-space: nowrap;
    flex-shrink: 0;
}

.btn-group-xs .btn i {
    font-size: 9px;
}

/* Status badge styling with ellipsis */
.status-badge {
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: bold;
    display: inline-block;
    text-align: center;
    min-width: 60px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

.status-available { background-color: #d4edda; color: #155724; }
.status-inuse { background-color: #cce5ff; color: #004085; }
.status-inrepair { background-color: #fff3cd; color: #856404; }
.status-lost { background-color: #f8d7da; color: #721c24; }
.status-reserved { background-color: #e2e3e5; color: #383d41; }
.status-retired { background-color: #f8d7da; color: #721c24; }
.status-suavailable { background-color: #d1ecf1; color: #0c5460; }
.status-sudeployed { background-color: #d4edda; color: #155724; }
.status-upcoming { background-color: #e2e3e5; color: #383d41; }

/* Table responsive wrapper */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

/* DataTables specific styling */
.dataTables_wrapper {
    width: 100%;
}

.dataTables_length,
.dataTables_filter {
    margin-bottom: 10px;
}

.dataTables_length select,
.dataTables_filter input {
    padding: 4px 8px;
    border: 1px solid #ccc;
    border-radius: 3px;
}

/* Pagination alignment */
.dataTables_paginate {
    text-align: right;
}

.dataTables_info {
    text-align: left;
    padding-top: 8px;
}

/* Empty state styling */
.dataTables_empty {
    text-align: center;
    font-style: italic;
    color: #666;
    padding: 20px;
}

/* Warranty end styling for single line with ellipsis */
.warranty-info {
    text-align: center;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.warranty-date {
    font-size: 10px;
    font-weight: bold;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.warranty-status {
    font-size: 9px;
    font-style: italic;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Ensure all table content stays on single line */
.dataTables-table td * {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Special handling for action buttons - they should not have ellipsis */
.dataTables-table td:last-child {
    white-space: nowrap;
    overflow: visible;
}

.dataTables-table td:last-child * {
    white-space: nowrap;
    overflow: visible;
    text-overflow: initial;
}

/* Tooltip for truncated text */
.dataTables-table td[title] {
    cursor: help;
}

/* Ensure table rows are single height */
.dataTables-table tr {
    height: 40px;
}

.dataTables-table td {
    height: 40px;
    line-height: 1.2;
}

/* Filter button active state styling */
.btn-warning {
    background-color: #ffc107 !important;
    border-color: #ffc107 !important;
    color: #212529 !important;
}

.btn-warning:hover {
    background-color: #e0a800 !important;
    border-color: #d39e00 !important;
}

/* Filter button text styling */
#companyFilterText, #statusFilterText {
    font-weight: 500;
}
</style>
<html lang="en">
    <head>
        <!-- SweetAlert2 CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
        <!-- SweetAlert2 JS -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    </head>
    <body class="no-skin">
        
        <div class="row">
            <div class="col-lg-12">
                
            <div id="myModal_updateTask" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="static" data-keyboard="false"></div>    
                <div id="insert_openThread" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="false"></div>            
                <div id="myModal_createAsset" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="static" data-keyboard="false"></div>   
                <div id="myModal_checkout" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="static" data-keyboard="false"></div>
                <div id="myModal_docs" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="static" data-keyboard="false"></div>  
            

                <input type="hidden" class="form-control" placeholder="Subject" id="user_id" name="user_id" value="<?php echo $userid;?>"/>
                <input type="hidden" class="form-control" placeholder="Subject" id="mygroup" name="mygroup" value="<?php echo $myusergroupid;?>"/>
            </div>
        </div>
        <div class="main-container ace-save-state" id="main-container">
            <?php include "blocks/navigation.php";?>

            <div class="main-content">
                <div class="main-content-inner">
                    <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                        <ul class="breadcrumb">
                            <li>
                                <i class="ace-icon fa fa-home home-icon"></i>
                                <a href="../index.php">Home</a>
                            </li>
                            <li>Complete Asset Inventory</li>
                                    
                            <li class="active"><button class="btn btn-purple input-xs" onclick="showAddAsset(0);" data-toggle="modal"  data-target="#myModal_createAsset" <?php if($authoritystatusidz == 2){echo "disabled";}?>><i class="ace-icon fa fa-floppy-o bigger-120"></i>Add Asset</button></li>
    
        

                        </ul><!-- /.breadcrumb -->

                    </div>
                    <div class="page-content">
                        <div class="row">
                            <div class="col-xs-6">
                                <!-- This space is intentionally left empty to maintain layout -->
                            </div>
                            <div class="col-xs-6 text-right" style="padding-top: 5px;">
                                <div class="btn-group" style="margin-left: 10px; margin-top: -5px;">
                                    <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="background-color: #4a90e2; border: none; border-radius: 4px; padding: 6px 12px; font-size: 13px; height: 32px; line-height: 1.4;" id="companyFilterBtn">
                                        <i class="ace-icon fa fa-filter"></i> <span id="companyFilterText">Filter Company</span> <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a href="#" onclick="filterCompany('all')">Show All</a></li>
                                        <li class="divider"></li>
                                        <?php 
                                            $query66 = "SELECT id,clientname FROM sys_clienttb WHERE clientstatusid = '1'";
                                            $result66 = mysqli_query($conn, $query66);
                                            while($row66 = mysqli_fetch_assoc($result66)){
                                                $clientname=mb_convert_case($row66['clientname'], MB_CASE_TITLE, "UTF-8");
                                                $clientid=$row66['id'];
                                        ?>
                                        <li><a href="#" onclick="filterCompany('<?php echo $clientname; ?>')"><?php echo $clientname; ?></a></li>
                                        <?php } ?>
                                    </ul>
                                </div>
                                
                                <div class="btn-group" style="margin-left: 10px; margin-top: -5px;">
                                    <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="background-color: #17a2b8; border: none; border-radius: 4px; padding: 6px 12px; font-size: 13px; height: 32px; line-height: 1.4;" id="statusFilterBtn">
                                        <i class="ace-icon fa fa-filter"></i> <span id="statusFilterText">Filter Status</span> <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a href="#" onclick="filterStatus('Available')">Available <span style="color: #4a90e2; font-weight: bold;"><?php echo $status_counts['available'] ?? 0; ?></span></a></li>
                                        <li><a href="#" onclick="filterStatus('In repair')">In-repair <span style="color: #4a90e2; font-weight: bold;"><?php echo $status_counts['inrepair'] ?? 0; ?></span></a></li>
                                        <li><a href="#" onclick="filterStatus('In use')">In-use <span style="color: #4a90e2; font-weight: bold;"><?php echo $status_counts['inuse'] ?? 0; ?></span></a></li>
                                        <li><a href="#" onclick="filterStatus('Lost')">Lost <span style="color: #4a90e2; font-weight: bold;"><?php echo $status_counts['lost'] ?? 0; ?></span></a></li>
                                        <li><a href="#" onclick="filterStatus('Reserved')">Reserved <span style="color: #4a90e2; font-weight: bold;"><?php echo $status_counts['reserved'] ?? 0; ?></span></a></li>
                                        <li><a href="#" onclick="filterStatus('Retired')">Retired <span style="color: #4a90e2; font-weight: bold;"><?php echo $status_counts['retired'] ?? 0; ?></span></a></li>
                                        <li><a href="#" onclick="filterStatus('S.U Available')">S.U Available <span style="color: #4a90e2; font-weight: bold;"><?php echo $status_counts['suavailable'] ?? 0; ?></span></a></li>
                                        <li><a href="#" onclick="filterStatus('S.U Deployed')">S.U Deployed <span style="color: #4a90e2; font-weight: bold;"><?php echo $status_counts['sudeployed'] ?? 0; ?></span></a></li>
                                        <li><a href="#" onclick="filterStatus('Upcoming')">Upcoming <span style="color: #4a90e2; font-weight: bold;"><?php echo $status_counts['upcoming'] ?? 0; ?></span></a></li>
                                        <li class="divider"></li>
                                        <li><a href="#" onclick="filterStatus('all')">Show All</a></li>
                                    </ul>
                                </div>
                                
                                <button type="button" class="btn btn-success btn-sm" onclick="exportToCSV()" style="margin-left: 10px; margin-top: -5px; height: 32px; line-height: 1.4;">
                                    <i class="ace-icon fa fa-download"></i> Export 
                                </button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                <ul class="nav nav-tabs" id="tabUL">
                                    <li class="active"><a href="#allAssets" data-toggle="tab">All Assets <span class="badge"><?php echo $asset_counts['total'] ?? 0; ?></span></a></li>
                                    <li><a href="#pcdesktop" data-toggle="tab">PC Desktop <span class="badge"><?php echo $asset_counts['pcdesktop'] ?? 0; ?></span></a></li>
                                    <li><a href="#laptop" data-toggle="tab">Laptop <span class="badge"><?php echo $asset_counts['laptop'] ?? 0; ?></span></a></li>
                                    <li><a href="#smartphones" data-toggle="tab">Smart Phones <span class="badge"><?php echo $asset_counts['smartphone'] ?? 0; ?></span></a></li>
                                    <li><a href="#tablets" data-toggle="tab">Tablets <span class="badge"><?php echo $asset_counts['tablet'] ?? 0; ?></span></a></li>
                                    <li><a href="#printers" data-toggle="tab">Printers <span class="badge"><?php echo $asset_counts['printers'] ?? 0; ?></span></a></li>
                                    <li><a href="#accessories" data-toggle="tab">Accessories <span class="badge"><?php echo $asset_counts['accessories'] ?? 0; ?></span></a></li>
                                </ul>

                                <div class="tab-content">
                                    <!-- All Assets Tab -->
                                    <div class="tab-pane fade in active" id="allAssets">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered table-hover dataTables-table" id="dataTables-allAssets">
                                                <thead>
                                                    <tr>
                                                        <th>Asset Reference Number</th>     
                                                        <th>PO Number</th>                                          
                                                        <th>Asset Tag</th>  
                                                        <th>Serial Number</th>
                                                        <th>Checked Out to</th>
                                                        <th>Location</th>                                           
                                                        <th>Company</th>                                                
                                                        <th>Asset Type</th>                                                                                                                                                                                                                                                             
                                                        <th>Status</th>   
                                                        <th>OS Version</th>
                                                        <th>Antivirus</th>
                                                        <th>PMS Update</th>
                                                        <th>Warranty End</th>                                                                                                            
                                                        <th>Action</th>                       
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php generateTableContent(null, 'dataTables-allAssets', $conn, $user_conditions); ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <!-- PC Desktop Tab -->
                                    <div class="tab-pane fade in" id="pcdesktop">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered table-hover dataTables-table" id="dataTables-pcdesktop">
                                                <thead>
                                                    <tr>
                                                        <th>Asset Reference Number</th>     
                                                        <th>PO Number</th>                                          
                                                        <th>Asset Tag</th>  
                                                        <th>Serial Number</th>
                                                        <th>Checked Out to</th>
                                                        <th>Location</th>                                           
                                                        <th>Company</th>                                                
                                                        <th>Asset Type</th>                                                                                                                                                                                                                                                             
                                                        <th>Status</th>   
                                                        <th>OS Version</th>
                                                        <th>Antivirus</th>
                                                        <th>PMS Update</th>
                                                        <th>Warranty End</th>                                                                                                            
                                                        <th>Action</th>                       
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php generateTableContent('PC Desktop', 'dataTables-pcdesktop', $conn, $user_conditions); ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <!-- Laptop Tab -->
                                    <div class="tab-pane fade in" id="laptop">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered table-hover dataTables-table" id="dataTables-laptop">
                                                <thead>
                                                    <tr>
                                                        <th>Asset Reference Number</th>     
                                                        <th>PO Number</th>                                          
                                                        <th>Asset Tag</th>  
                                                        <th>Serial Number</th>
                                                        <th>Checked Out to</th>
                                                        <th>Location</th>                                           
                                                        <th>Company</th>                                                
                                                        <th>Asset Type</th>                                                                                                                                                                                                                                                             
                                                        <th>Status</th>   
                                                        <th>OS Version</th>
                                                        <th>Antivirus</th>
                                                        <th>PMS Update</th>
                                                        <th>Warranty End</th>                                                                                                            
                                                        <th>Action</th>                       
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php generateTableContent('Laptop', 'dataTables-laptop', $conn, $user_conditions); ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <!-- Smartphones Tab -->
                                    <div class="tab-pane fade in" id="smartphones">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered table-hover dataTables-table" id="dataTables-smartphones">
                                                <thead>
                                                    <tr>
                                                        <th>Asset Reference Number</th>     
                                                        <th>PO Number</th>                                          
                                                        <th>Asset Tag</th>  
                                                        <th>Serial Number</th>
                                                        <th>Checked Out to</th>
                                                        <th>Location</th>                                           
                                                        <th>Company</th>                                                
                                                        <th>Asset Type</th>                                                                                                                                                                                                                                                             
                                                        <th>Status</th>   
                                                        <th>OS Version</th>
                                                        <th>Antivirus</th>
                                                        <th>PMS Update</th>
                                                        <th>Warranty End</th>                                                                                                            
                                                        <th>Action</th>                       
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php generateTableContent('Smartphone', 'dataTables-smartphones', $conn, $user_conditions); ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <!-- Tablets Tab -->
                                    <div class="tab-pane fade in" id="tablets">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered table-hover dataTables-table" id="dataTables-tablets">
                                                <thead>
                                                    <tr>
                                                        <th>Asset Reference Number</th>     
                                                        <th>PO Number</th>                                          
                                                        <th>Asset Tag</th>  
                                                        <th>Serial Number</th>
                                                        <th>Checked Out to</th>
                                                        <th>Location</th>                                           
                                                        <th>Company</th>                                                
                                                        <th>Asset Type</th>                                                                                                                                                                                                                                                             
                                                        <th>Status</th>   
                                                        <th>OS Version</th>
                                                        <th>Antivirus</th>
                                                        <th>PMS Update</th>
                                                        <th>Warranty End</th>                                                                                                            
                                                        <th>Action</th>                       
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php generateTableContent('Tablet', 'dataTables-tablets', $conn, $user_conditions); ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <!-- Printers Tab -->
                                    <div class="tab-pane fade in" id="printers">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered table-hover dataTables-table" id="dataTables-printers">
                                                <thead>
                                                    <tr>
                                                        <th>Asset Reference Number</th>     
                                                        <th>PO Number</th>                                          
                                                        <th>Asset Tag</th>  
                                                        <th>Serial Number</th>
                                                        <th>Checked Out to</th>
                                                        <th>Location</th>                                           
                                                        <th>Company</th>                                                
                                                        <th>Asset Type</th>                                                                                                                                                                                                                                                             
                                                        <th>Status</th>   
                                                        <th>OS Version</th>
                                                        <th>Antivirus</th>
                                                        <th>PMS Update</th>
                                                        <th>Warranty End</th>                                                                                                            
                                                        <th>Action</th>                       
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php generateTableContent('Printers', 'dataTables-printers', $conn, $user_conditions); ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <!-- Accessories Tab -->
                                    <div class="tab-pane fade in" id="accessories">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered table-hover dataTables-table" id="dataTables-accessories">
                                                <thead>
                                                    <tr>
                                                        <th>Asset Reference Number</th>     
                                                        <th>PO Number</th>                                          
                                                        <th>Asset Tag</th>  
                                                        <th>Serial Number</th>
                                                        <th>Checked Out to</th>
                                                        <th>Location</th>                                           
                                                        <th>Company</th>                                                
                                                        <th>Asset Type</th>                                                                                                                                                                                                                                                             
                                                        <th>Status</th>   
                                                        <th>OS Version</th>
                                                        <th>Antivirus</th>
                                                        <th>PMS Update</th>
                                                        <th>Warranty End</th>                                                                                                            
                                                        <th>Action</th>                       
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php generateTableContent('accessories', 'dataTables-accessories', $conn, $user_conditions); ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- /.page-content -->
                </div>
            </div><!-- /.main-content -->

            <?php include "blocks/footer.php"; ?>

            
        </div><!-- /.main-container -->
    
    </body>
</html>

<script src="../assets/customjs/inventory.js"></script>
<!-- Validator-->
<script type="text/javascript" src="../assets/customjs/bootstrapValidator.min.js"></script>  
<script type="text/javascript" src="../assets/customjs/bootstrapValidator.js"></script> 

    
        <!-- Your inline scripts -->
        <script type="text/javascript">
            // Global filter variables
            var currentStatusFilter = null;
            var currentCompanyFilter = null;

            // Normalize status text for exact matching
            function normalizeStatus(txt) {
                return (txt || '').toString().toLowerCase().replace(/[^a-z]/g, '');
            }

            // Normalize generic text (trim + lowercase) for contains matching
            function normalizeText(txt) {
                return (txt || '').toString().trim().toLowerCase();
            }

            // Function to update tab counts based on current filters
            function updateTabCounts() {
                var tables = [
                    { id: '#dataTables-allAssets', badge: 'allAssets' },
                    { id: '#dataTables-pcdesktop', badge: 'pcdesktop' },
                    { id: '#dataTables-laptop', badge: 'laptop' },
                    { id: '#dataTables-smartphones', badge: 'smartphones' },
                    { id: '#dataTables-tablets', badge: 'tablets' },
                    { id: '#dataTables-printers', badge: 'printers' },
                    { id: '#dataTables-accessories', badge: 'accessories' }
                ];

                tables.forEach(function(table) {
                    if ($.fn.DataTable.isDataTable(table.id)) {
                        var tableInstance = $(table.id).DataTable();
                        var visibleRows = tableInstance.rows({search: 'applied'}).count();
                        
                        // Update the badge count for this tab
                        var tabLink = $('#tabUL a[href="#' + table.badge + '"]');
                        var badge = tabLink.find('.badge');
                        if (badge.length === 0) {
                            tabLink.append(' <span class="badge">' + visibleRows + '</span>');
                        } else {
                            badge.text(visibleRows);
                        }
                    }
                });
            }

            // Global DataTables search function for status filtering
            $.fn.dataTable.ext.search.push(function (settings, data) {
                // Only apply to our inventory tables
                var tableId = settings.nTable.id;
                var isInventoryTable = [
                    'dataTables-allAssets',
                    'dataTables-pcdesktop',
                    'dataTables-laptop',
                    'dataTables-smartphones',
                    'dataTables-tablets',
                    'dataTables-printers',
                    'dataTables-accessories'
                ].indexOf(tableId) !== -1;

                if (!isInventoryTable) return true;

                // Apply status filter
                if (currentStatusFilter) {
                    var statusHtml = data[8] || '';
                    var statusText = $('<div>').html(statusHtml).text().trim();
                    var normalizedStatus = normalizeStatus(statusText);
                    if (normalizedStatus !== currentStatusFilter) {
                        return false;
                    }
                }

                // Apply company filter
                if (currentCompanyFilter) {
                    var companyHtml = data[6] || '';
                    var companyText = $('<div>').html(companyHtml).text().trim().toLowerCase();
                    if (companyText.indexOf(currentCompanyFilter) === -1) {
                        return false;
                    }
                }

                return true;
            });

            $(document).ready(function() {
                // Initialize all DataTables with optimized settings
                var tableIds = [
                    '#dataTables-allAssets',
                    '#dataTables-pcdesktop', 
                    '#dataTables-laptop',
                    '#dataTables-smartphones',
                    '#dataTables-tablets',
                    '#dataTables-printers',
                    '#dataTables-accessories'
                ];

                // Initialize each table
                tableIds.forEach(function(tableId) {
                    if ($.fn.DataTable.isDataTable(tableId)) {
                        $(tableId).DataTable().destroy();
                    }
                    
                    $(tableId).DataTable({
                        responsive: true,
                        "order": [],
                        "pageLength": 25,
                        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                        "dom": '<"top"lf>rt<"bottom"ip><"clear">',
                        "scrollX": true,
                        "autoWidth": false,
                        "deferRender": true,
                        "columnDefs": [
                            { "width": "12%", "targets": 0 },   // Asset Reference Number
                            { "width": "6%", "targets": 1 },    // PO Number
                            { "width": "8%", "targets": 2 },    // Asset Tag
                            { "width": "8%", "targets": 3 },    // Serial Number
                            { "width": "10%", "targets": 4 },   // Checked Out to
                            { "width": "8%", "targets": 5 },    // Location
                            { "width": "10%", "targets": 6 },   // Company
                            { "width": "8%", "targets": 7 },    // Asset Type
                            { "width": "8%", "targets": 8 },    // Status
                            { "width": "6%", "targets": 9 },    // OS Version
                            { "width": "6%", "targets": 10 },   // Antivirus
                            { "width": "6%", "targets": 11 },   // PMS Update
                            { "width": "8%", "targets": 12 },   // Warranty End
                            { "width": "10%", "targets": 13 }   // Action
                        ]
                    });
                });
    
                // Update tab counts after DataTable initialization
                setTimeout(updateTabCounts, 100);
                
                // navigation click actions 
                $('.scroll-link').on('click', function(event){
                    event.preventDefault();
                    var sectionID = $(this).attr("data-id");
                    scrollToID('#' + sectionID, 750);
                }); 
                
                // Disable legacy pcdesktop AJAX/tab handler if present on this page
                $('#tabUL a[href="#pcdesktop"]').off('click');
                if (window.showPCDesktop) { window.showPCDesktop = function(){}; }
                
                // Tab click handlers
                $('#tabUL a').click(function (e) {
                    e.preventDefault();
                    $(this).tab('show');
                    
                    // Preserve current filter states when switching tabs
                    var currentCompanyText = $('#companyFilterText').text();
                    var currentStatusText = $('#statusFilterText').text();
                    
                    // Reapply company filter if active
                    if (currentCompanyText !== 'Filter Company' && currentCompanyText.includes('Company:')) {
                        var company = currentCompanyText.replace('Company: ', '');
                        filterCompany(company);
                    }
                    
                    // Reapply status filter if active
                    if (currentStatusText !== 'Filter Status' && currentStatusText.includes('Status:')) {
                        var status = currentStatusText.replace('Status: ', '');
                        filterStatus(status);
                    }
                    
                    // Update counts when switching tabs
                    setTimeout(updateTabCounts, 100);
                });

                // store the currently selected tab in the hash value
                $("ul.nav-tabs > li > a").on("shown.bs.tab", function (e) {
                    var id = $(e.target).attr("href").substr(1);
                    window.location.hash = id;
                });
            
                // on load of the page: switch to the currently selected tab
                var hash = window.location.hash;
                $('#tabUL a[href="' + hash + '"]').tab('show');
            });

            function filterStatus(status) {
                // Update the button text to show selected status
                var statusText = status === 'all' ? 'Filter Status' : 'Status: ' + status;
                $('#statusFilterText').text(statusText);
                
                // Add visual indicator for active filter
                if (status === 'all') {
                    $('#statusFilterBtn').removeClass('btn-warning').addClass('btn-info');
                    currentStatusFilter = null; // clear filter
                } else {
                    $('#statusFilterBtn').removeClass('btn-info').addClass('btn-warning');
                    // Set normalized filter for exact matching
                    currentStatusFilter = normalizeStatus(status);
                }
                
                // Redraw all tables to apply the global filter
                var tables = [
                    '#dataTables-allAssets',
                    '#dataTables-pcdesktop', 
                    '#dataTables-laptop',
                    '#dataTables-smartphones',
                    '#dataTables-tablets',
                    '#dataTables-printers',
                    '#dataTables-accessories'
                ];

                tables.forEach(function(tableId) {
                    if ($.fn.DataTable.isDataTable(tableId)) {
                        $(tableId).DataTable().draw();
                    }
                });
                
                // Update tab counts after filtering
                setTimeout(updateTabCounts, 100);
            }

            function filterCompany(company) {
                // Update the button text to show selected company
                var companyText = company === 'all' ? 'Filter Company' : 'Company: ' + company;
                $('#companyFilterText').text(companyText);
                
                // Add visual indicator for active filter
                if (company === 'all') {
                    $('#companyFilterBtn').removeClass('btn-warning').addClass('btn-primary');
                    currentCompanyFilter = null; // clear filter
                } else {
                    $('#companyFilterBtn').removeClass('btn-primary').addClass('btn-warning');
                    currentCompanyFilter = normalizeText(company); // set normalized filter
                }
                
                // Redraw all tables to apply the global filter
                var tables = [
                    '#dataTables-allAssets',
                    '#dataTables-pcdesktop', 
                    '#dataTables-laptop',
                    '#dataTables-smartphones',
                    '#dataTables-tablets',
                    '#dataTables-printers',
                    '#dataTables-accessories'
                ];

                tables.forEach(function(tableId) {
                    if ($.fn.DataTable.isDataTable(tableId)) {
                        $(tableId).DataTable().draw();
                    }
                });
                
                // Update tab counts after filtering
                setTimeout(updateTabCounts, 100);
            }

            function exportToCSV() {
                // Get the currently active tab
                var activeTab = $('.tab-pane.active');
                var tableId = activeTab.find('table').attr('id');
                
                // Get current filters
                var statusFilter = currentStatusFilter || '';
                var companyFilter = currentCompanyFilter || '';
                var assetType = '';
                
                // Determine asset type based on active tab
                if (tableId === 'dataTables-pcdesktop') {
                    assetType = 'PC Desktop';
                } else if (tableId === 'dataTables-laptop') {
                    assetType = 'Laptop';
                } else if (tableId === 'dataTables-smartphones') {
                    assetType = 'Smartphone';
                } else if (tableId === 'dataTables-tablets') {
                    assetType = 'Tablet';
                } else if (tableId === 'dataTables-printers') {
                    assetType = 'Printers';
                } else if (tableId === 'dataTables-accessories') {
                    assetType = 'accessories';
                }
                
                // Create export URL with parameters
                var exportUrl = 'export_inventory_excel.php';
                var params = [];
                
                if (statusFilter) {
                    params.push('status=' + encodeURIComponent(statusFilter));
                }
                if (companyFilter) {
                    params.push('company=' + encodeURIComponent(companyFilter));
                }
                if (assetType) {
                    params.push('asset_type=' + encodeURIComponent(assetType));
                }
                
                if (params.length > 0) {
                    exportUrl += '?' + params.join('&');
                }
                
                // Create a temporary link to trigger download
                var link = document.createElement('a');
                link.href = exportUrl;
                link.download = 'inventory_export_' + new Date().toISOString().slice(0,10) + '.xls';
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        </script>