<?php
// Simple path fix - go up one directory from inventory to root
include('../conn/db.php');
include('../check_session.php');

// Simple database connection check
$conn = connectionDB();
if (!$conn) {
    die("Database connection failed");
}

// Get the asset ID and ensure it's properly sanitized
$asset_id = isset($_GET['asset_id']) ? mysqli_real_escape_string($conn, $_GET['asset_id']) : '';

// Modified query to get asset data with proper joins
$query = "SELECT a.*, 
          GROUP_CONCAT(DISTINCT CONCAT(u1.user_firstname, ' ', u1.user_lastname) SEPARATOR ', ') as assignee_names,
          GROUP_CONCAT(DISTINCT CONCAT(u2.user_firstname, ' ', u2.user_lastname) SEPARATOR ', ') as current_owner_names,
          GROUP_CONCAT(DISTINCT CONCAT(u3.user_firstname, ' ', u3.user_lastname) SEPARATOR ', ') as previous_owner_names
          FROM inv_inventorylisttb a
          LEFT JOIN sys_usertb u1 ON FIND_IN_SET(u1.id, a.assignee)
          LEFT JOIN sys_usertb u2 ON FIND_IN_SET(u2.id, a.current_owner)
          LEFT JOIN sys_usertb u3 ON FIND_IN_SET(u3.id, a.previous_owner)
          WHERE a.asset_id = ?
          GROUP BY a.asset_id";

// Prepare and execute the statement with error checking
$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    die("Query preparation failed: " . mysqli_error($conn));
}

// Bind the parameter with error checking
if (!mysqli_stmt_bind_param($stmt, "s", $asset_id)) {
    die("Parameter binding failed: " . mysqli_stmt_error($stmt));
}

// Execute the statement with error checking
if (!mysqli_stmt_execute($stmt)) {
    die("Statement execution failed: " . mysqli_stmt_error($stmt));
}

// Get the result with error checking
$result = mysqli_stmt_get_result($stmt);
if (!$result) {
    die("Getting result failed: " . mysqli_stmt_error($stmt));
}

// Debug output (remove in production)
error_log("Asset ID being queried: " . $asset_id);
error_log("Number of rows found: " . mysqli_num_rows($result));

if ($result && mysqli_num_rows($result) > 0) {
    $asset = mysqli_fetch_assoc($result);
    error_log("Asset data: " . print_r($asset, true));

    // Include header block
    include('blocks/header.php');

    // Helper to get user names for a comma-separated list of IDs
    function getUserNames($conn, $idString) {
        $ids = array_filter(array_map('intval', explode(',', $idString)));
        if (empty($ids)) return [];
        $sql = "SELECT id, user_firstname, user_lastname FROM sys_usertb WHERE id IN (" . implode(',', $ids) . ")";
        $result = mysqli_query($conn, $sql);
        $names = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $names[$row['id']] = $row['user_firstname'] . ' ' . $row['user_lastname'];
        }
        // Return names in the order of the original IDs
        $ordered = [];
        foreach ($ids as $id) {
            $ordered[] = $names[$id] ?? 'Unknown User';
        }
        return $ordered;
    }

    // Get names for each field
    $assigneeNames = !empty($asset['assignee']) ? getUserNames($conn, $asset['assignee']) : [];
    $currentOwnerNames = !empty($asset['current_owner']) ? getUserNames($conn, $asset['current_owner']) : [];
    $previousOwnerNames = !empty($asset['previous_owner']) ? getUserNames($conn, $asset['previous_owner']) : [];
    $createdByName = !empty($asset['createdbyid']) ? getUserNames($conn, $asset['createdbyid']) : [];
?>
<!-- Add these styles right after header include -->
<style>
    .sidebar {
        border-style: none !important;
    }
    .nav-list > li {
        border-style: none !important;
    }
    .nav-list > li > a {
        border-style: none !important;
    }
    .sidebar.responsive {
        border-style: none !important;
    }
    #sidebar.sidebar.responsive.ace-save-state {
        border-style: none !important;
    }
    .file-preview-img {
        max-width: 100%;
        max-height: 150px;
        border-radius: 6px;
        box-shadow: 0 2px 8px #ccc;
    }
    .carousel-arrow:hover {
        background: #2679b5 !important;
        color: #fff !important;
    }
    
    .timeline-container {
        position: relative;
        padding: 20px 0;
    }
    
    .timeline-item {
        margin-bottom: 10px;
    }
    .timeline-toggle {
        cursor: pointer;
        padding: 8px;
        background: #f8f9fa;
        border: none;
        display: flex;
        align-items: center;
        width: 100%;
        text-align: left;
        border-radius: 4px;
    }
    .timeline-toggle i.fa-chevron-down {
        margin-right: 10px;
        transition: transform 0.3s;
    }
    .timeline-toggle.collapsed i.fa-chevron-down {
        transform: rotate(-90deg);
    }
    .timeline-time {
        margin-left: 10px;
        color: #666;
    }
    .timeline-content {
        padding: 15px;
        border-left: 2px solid #2679b5;
        margin-left: 20px;
    }
    
    .timeline-title {
        font-weight: bold;
        margin-bottom: 8px;
        color: #2c3e50;
    }
    
    .timeline-details {
        margin-bottom: 8px;
    }
    
    .timeline-user {
        color: #666;
        font-size: 0.9em;
        font-style: italic;
    }

    /* New styles for highlighting changes */
    .label-info {
        background-color: #3498db;
        padding: 4px 8px;
        border-radius: 3px;
        font-size: 0.85em;
    }

    .label-default {
        background-color: #95a5a6;
        padding: 4px 8px;
        border-radius: 3px;
        font-size: 0.85em;
    }

    /* Highlight different types of changes */
    .timeline-item[data-type="UPDATE"] {
        border-left-color: #3498db;
    }
    
    .timeline-item[data-type="UPDATE"]:before {
        background: #3498db;
    }

    .timeline-item[data-type="CREATE"] {
        border-left-color: #2ecc71;
    }
    
    .timeline-item[data-type="CREATE"]:before {
        background: #2ecc71;
    }

    .timeline-item[data-type="DELETE"] {
        border-left-color: #e74c3c;
    }
    
    .timeline-item[data-type="DELETE"]:before {
        background: #e74c3c;
    }

    /* New styles for field changes */
    .field-change {
        margin: 8px 0;
        padding: 8px;
        background: #fff;
        border-radius: 4px;
        border-left: 3px solid #3498db;
    }

    .field-name {
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 4px;
    }

    .value-change {
        display: flex;
        gap: 10px;
        font-size: 0.9em;
    }

    .old-value {
        color: #e74c3c;
        text-decoration: line-through;
        flex: 1;
    }

    .new-value {
        color: #27ae60;
        flex: 1;
    }

    .change-arrow {
        color: #7f8c8d;
        padding: 0 5px;
    }

    .no-change {
        color: #7f8c8d;
        font-style: italic;
    }

    /* Add these new styles */
    .checkout-summary {
        background: #f8f9fa;
        border-left: 4px solid #28a745;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .checkout-summary h5 {
        color: #28a745;
        margin-top: 0;
        margin-bottom: 15px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .checkout-detail {
        padding: 8px 0;
        color: #495057;
        border-bottom: 1px dashed #dee2e6;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .checkout-detail:last-child {
        border-bottom: none;
    }

    .checkout-detail i {
        width: 20px;
        color: #6c757d;
    }

    .field-change {
        background: #fff;
        border-left: 3px solid #17a2b8;
        padding: 10px;
        margin: 10px 0;
        border-radius: 4px;
    }

    .field-name {
        font-weight: bold;
        color: #495057;
        margin-bottom: 8px;
    }

    .value-change {
        display: flex;
        align-items: center;
        gap: 15px;
        font-size: 0.9em;
    }

    .old-value {
        color: #dc3545;
        flex: 1;
    }

    .new-value {
        color: #28a745;
        flex: 1;
    }

    .change-arrow {
        color: #6c757d;
    }

    .timeline-time {
        position: absolute;
        top: 10px;
        left: 110px;
        color: #6c757d;
        font-size: 0.9em;
    }

    .timeline-user {
        margin-top: 10px;
        color: #6c757d;
        font-size: 0.9em;
        font-style: italic;
    }

    /* Add these new styles right after the existing styles */
    .field-change {
        background: #fff;
        border-left: 3px solid #3498db;
        padding: 15px;
        margin: 10px 0;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .field-change:hover {
        transform: translateX(5px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.15);
    }

    .field-name {
        font-size: 1.1em;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .field-name i {
        color: #3498db;
    }

    .value-change {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 4px;
    }

    .old-value, .new-value {
        flex: 1;
        padding: 8px;
        border-radius: 3px;
    }

    .old-value {
        background: rgba(231,76,60,0.1);
    }

    .new-value {
        background: rgba(46,204,113,0.1);
    }

    .change-arrow {
        color: #95a5a6;
        font-size: 1.2em;
    }

    .text-success {
        color: #27ae60 !important;
        font-weight: 500;
    }

    .text-danger {
        color: #c0392b !important;
        text-decoration: line-through;
        opacity: 0.8;
    }

    .text-primary {
        color: #2980b9 !important;
        font-weight: 500;
    }

    .text-info {
        color: #16a085 !important;
        font-weight: 500;
    }

    .text-warning {
        color: #f39c12 !important;
        font-weight: 500;
    }

    .text-muted {
        color: #7f8c8d !important;
        font-size: 0.9em;
    }

    .checkout-detail {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 0;
    }

    .checkout-detail i {
        width: 20px;
        text-align: center;
        color: #7f8c8d;
    }

    .checkout-detail strong {
        min-width: 150px;
        color: #34495e;
    }

    .change-summary-row {
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding: 5px 0;
    }

    .change-values {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-left: 25px;
        font-size: 0.9em;
    }

    .change-arrow {
        color: #666;
    }

    .timeline-toggle {
        width: 100%;
        text-align: left;
        padding: 10px;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 4px;
    }

    .timeline-toggle:hover {
        background: #e9ecef;
    }

    .timeline-toggle .fa-chevron-down {
        transition: transform 0.3s ease;
    }

    .timeline-toggle.collapsed .fa-chevron-down {
        transform: rotate(-90deg);
    }

    .text-danger i, .text-success i {
        margin-right: 4px;
        font-size: 0.9em;
    }

    /* Add these new styles for the history preview tab */
    #history-preview-tab {
        max-height: 600px; /* Adjust this value based on your needs */
        overflow-y: auto;
        padding-right: 10px; /* Add some padding for the scrollbar */
    }

    /* Style the scrollbar for better appearance */
    #history-preview-tab::-webkit-scrollbar {
        width: 8px;
    }

    #history-preview-tab::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    #history-preview-tab::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }

    #history-preview-tab::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    /* Ensure the timeline container doesn't overflow */
    .timeline-container {
        position: relative;
        padding: 20px 0;
        width: 100%;
    }

    /* Keep the date headers sticky while scrolling */
    .timeline-date-header {
        position: sticky;
        top: 0;
        background: #fff;
        padding: 10px 0;
        margin: 0;
        z-index: 1;
        border-bottom: 1px solid #eee;
    }
</style>

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
                    <li><a href="inventory.php">Inventory</a></li>
                    <li class="active">Asset Details</li>
                </ul>
            </div>

            <div class="page-content">
                <div class="row">
                    <!-- Left Column: All Asset Details -->
                    <div class="col-md-4">
                        <div class="page-header">
                            <h1 style="display: flex; align-items: center; gap: 8px;">
                                <span>Asset Details</span>
                                <i class="ace-icon fa fa-angle-double-right"></i>
                                <span style="display: inline-block;"><?php echo !empty($asset['asset_tag']) ? htmlspecialchars($asset['asset_tag']) : 'N/A'; ?></span>
                            </h1>
                        </div>
                        <div class="profile-user-info profile-user-info-striped" style="background: #f9f9f9; border-radius: 8px; padding-bottom: 10px;">
                            <!-- Identification -->
                            <hr class="hr-8 dotted">

                            <div class="profile-info-row">
                                <div class="profile-info-name">AST ID</div>
                                <div class="profile-info-value">
                                    <span><?php echo !empty($asset['asset_id']) ? htmlspecialchars($asset['asset_id']) : 'N/A'; ?></span>
                                </div>
                            </div>
                            
                            <div class="profile-info-row">
                                <div class="profile-info-name">Asset Tag</div>
                                <div class="profile-info-value">
                                    <span><?php echo !empty($asset['asset_tag']) ? htmlspecialchars($asset['asset_tag']) : 'N/A'; ?></span>
                                </div>
                            </div>

                            <!-- <div class="profile-info-row">
                                <div class="profile-info-name">Assignee</div>
                                <div class="profile-info-value">
                                    <span><?php echo !empty($assigneeNames) ? htmlspecialchars(implode(', ', $assigneeNames)) : 'N/A'; ?></span>
                                </div>
                            </div> -->
                            
                           
                            <!-- Status & Condition -->
                            <hr class="hr-8 dotted">
                            <div class="profile-info-row">
                                <div class="profile-info-name">Status</div>
                                <div class="profile-info-value">
                                    <span class="label label-<?php echo !empty($asset['status']) && strtolower($asset['status']) == 'active' ? 'success' : 'warning'; ?>">
                                        <?php echo !empty($asset['status']) ? htmlspecialchars($asset['status']) : 'N/A'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="profile-info-row">
                                <div class="profile-info-name">Warranty End Date</div>
                                <div class="profile-info-value">
                                    <?php 
                                    if (empty($asset['warranty_end'])) {
                                        echo '<div style="color: #000000">N/A</div>';
                                        echo '<div style="color: #dc3545"><i><strong>No Warranty Info</strong></i></div>';
                                    } else {
                                        $warranty_end = strtotime($asset['warranty_end']);
                                        $current_date = time();
                                        if ($warranty_end > $current_date) {
                                            echo '<div style="color: #000000">' . date('F d, Y', $warranty_end) . '</div>';
                                            echo '<div style="color: #28a745"><i><strong>Under Warranty</strong></i></div>';
                                        } else {
                                            echo '<div style="color: #000000">' . date('F d, Y', $warranty_end) . '</div>';
                                            echo '<div style="color: #dc3545"><i><strong>Warranty Expired</strong></i></div>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="profile-info-row">
                                <div class="profile-info-name">Condition Notes</div>
                                <div class="profile-info-value">
                                    <textarea class="form-control" rows="4" readonly style="resize: none; background-color: #f9f9f9; padding: 10px; line-height: 1.5; font-family: inherit; white-space: pre-wrap; word-wrap: break-word; page-break-inside: avoid;"><?php 
                                        if (!empty($asset['condition_notes'])) {
                                            // Clean HTML tags and preserve line breaks
                                            $notes = preg_replace('/<[^>]*>/', '', $asset['condition_notes']);
                                            // Convert line breaks to proper HTML line breaks
                                            $notes = nl2br(trim($notes));
                                            // Remove any extra spaces but preserve intentional line breaks
                                            $notes = preg_replace('/[ \t]+/', ' ', $notes);
                                            // Ensure proper spacing after periods
                                            $notes = preg_replace('/\.(?=\S)/', '. ', $notes);
                                            echo htmlspecialchars($notes);
                                        } else {
                                            echo 'N/A';
                                        }
                                    ?></textarea>
                                </div>
                            </div>

                             <!-- Ownership -->
                             <hr class="hr-8 dotted" style="page-break-after: always;">
                            <div class="profile-info-row">
                                <div class="profile-info-name">Check Out To</div>
                                <div class="profile-info-value">
                                    <span>
                                        <?php echo !empty($currentOwnerNames) ? htmlspecialchars(implode(', ', $currentOwnerNames)) : 'N/A'; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name">Check Out Date</div>
                                <div class="profile-info-value">
                                    <span>
                                        <?php echo !empty($asset['checkout_date']) ? date('M d, Y', strtotime($asset['checkout_date'])) : 'N/A'; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="profile-info-row">
                                <div class="profile-info-name">Expected Check Out Date</div>
                                <div class="profile-info-value">
                                    <span>
                                        <?php echo !empty($asset['exp_checkout_date']) ? date('M d, Y', strtotime($asset['exp_checkout_date'])) : 'N/A'; ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Department & Company -->
                            <div class="profile-info-row">
                                <div class="profile-info-name">Department & Company</div>
                                <div class="profile-info-value">
                                    <?php
                                    // Check if there are any logs for department or company changes
                                    $dept_company_query = "SELECT COUNT(*) as count FROM inv_logs 
                                                         WHERE asset_id_ref = ? 
                                                         AND (field_name = 'department' OR field_name = 'company')";
                                    $dept_company_stmt = mysqli_prepare($conn, $dept_company_query);
                                    mysqli_stmt_bind_param($dept_company_stmt, "s", $asset['asset_id']);
                                    mysqli_stmt_execute($dept_company_stmt);
                                    $dept_company_result = mysqli_stmt_get_result($dept_company_stmt);
                                    $has_changes = mysqli_fetch_assoc($dept_company_result)['count'] > 0;
                                    mysqli_stmt_close($dept_company_stmt);

                                    if ($has_changes) {
                                        echo '<span style="margin-right: 16px;">';
                                        echo '<strong>Department:</strong> ' . (!empty($asset['department']) ? htmlspecialchars($asset['department']) : 'N/A');
                                        echo '&nbsp;|&nbsp;';
                                        echo '<strong>Company:</strong> ' . (!empty($asset['company']) ? htmlspecialchars($asset['company']) : 'N/A');
                                        echo '</span>';
                                    } else {
                                        echo '<span style="color: #666; font-style: italic;">No department or company changes recorded</span>';
                                    }
                                    ?>
                                </div>
                            </div>

                            <!-- Purchase Info -->
                            <hr class="hr-8 dotted">
                            <div class="profile-info-row">
                                <div class="profile-info-name">Purchase Date</div>
                                <div class="profile-info-value">
                                    <span><?php echo !empty($asset['purchase_date']) ? date('M d, Y', strtotime($asset['purchase_date'])) : 'N/A'; ?></span>
                                </div>
                            </div>
                            <div class="profile-info-row">
                                <div class="profile-info-name">Purchase Price</div>
                                <div class="profile-info-value">
                                    <span><?php echo !empty($asset['purchase_price']) ? '₱' . number_format($asset['purchase_price'], 2) : 'N/A'; ?></span>
                                </div>
                            </div>
                            <div class="profile-info-row">
                                <div class="profile-info-name">Order Number</div>
                                <div class="profile-info-value">
                                    <span><?php echo !empty($asset['order_number']) ? htmlspecialchars($asset['order_number']) : 'N/A'; ?></span>
                                </div>
                            </div>
                            <!-- <div class="profile-info-row">
                                <div class="profile-info-name">Due Date</div>
                                <div class="profile-info-value">
                                    <span><?php echo !empty($asset['due_date']) ? date('M d, Y', strtotime($asset['due_date'])) : 'N/A'; ?></span>
                                </div>
                            </div> -->
                            <!-- Technical Details -->
                            <hr class="hr-8 dotted">
                            <div class="profile-info-row">
                                <div class="profile-info-name">Asset Type</div>
                                <div class="profile-info-value">
                                    <span><?php echo !empty($asset['asset_type']) ? htmlspecialchars($asset['asset_type']) : 'N/A'; ?></span>
                                </div>
                            </div>
                            <div class="profile-info-row">
                                <div class="profile-info-name">Manufacturer</div>
                                <div class="profile-info-value">
                                    <span><?php echo !empty($asset['manufacturer']) ? htmlspecialchars($asset['manufacturer']) : 'N/A'; ?></span>
                                </div>
                            </div>
                            <div class="profile-info-row">
                                <div class="profile-info-name">Model</div>
                                <div class="profile-info-value">
                                    <textarea class="form-control" rows="3" readonly style="resize: none; background-color: #f9f9f9; padding: 10px; line-height: 1.5; font-family: inherit; white-space: pre-wrap; word-wrap: break-word; page-break-inside: avoid;"><?php 
                                        if (!empty($asset['model'])) {
                                            // Clean HTML tags and preserve line breaks
                                            $model = preg_replace('/<[^>]*>/', '', $asset['model']);
                                            // Convert line breaks to proper HTML line breaks
                                            $model = nl2br(trim($model));
                                            // Remove any extra spaces but preserve intentional line breaks
                                            $model = preg_replace('/[ \t]+/', ' ', $model);
                                            echo htmlspecialchars($model);
                                        } else {
                                            echo 'N/A';
                                        }
                                    ?></textarea>
                                </div>
                            </div>
                            <div class="profile-info-row">
                                <div class="profile-info-name">Color</div>
                                <div class="profile-info-value">
                                    <span><?php echo !empty($asset['color']) ? htmlspecialchars($asset['color']) : 'N/A'; ?></span>
                                </div>
                            </div>
                            <div class="profile-info-row">
                                <div class="profile-info-name">Serial Number</div>
                                <div class="profile-info-value">
                                    <span><?php echo !empty($asset['serial_number']) ? htmlspecialchars($asset['serial_number']) : 'N/A'; ?></span>
                                </div>
                            </div>
                            <div class="profile-info-row">
                                <div class="profile-info-name">SIM Number</div>
                                <div class="profile-info-value">
                                    <span><?php echo !empty($asset['sim_number']) ? htmlspecialchars($asset['sim_number']) : 'N/A'; ?></span>
                                </div>
                            </div>
                            <!-- Created Info -->
                            <!-- <hr class="hr-8 dotted">
                            <div class="profile-info-row">
                                <div class="profile-info-name">Created Date</div>
                                <div class="profile-info-value">
                                    <span><?php echo !empty($asset['datetimecreated']) ? date('M d, Y H:i:s', strtotime($asset['datetimecreated'])) : 'N/A'; ?></span>
                                </div>
                            </div>
                            <div class="profile-info-row">
                                <div class="profile-info-name">Created By</div>
                                <div class="profile-info-value">
                                    <span><?php echo !empty($createdByName) ? htmlspecialchars(implode(', ', $createdByName)) : 'N/A'; ?></span>
                                </div>
                            </div> -->
                        </div>
                    </div>
                    <!-- Right Column: Tabs for Document Preview and Uploaded Documents -->
                    <div class="col-md-8">
                        <div class="page-header">
                            <h1>Asset Files</h1>
                        </div>
                        <!-- Tabs Navigation -->
                        <ul class="nav nav-tabs padding-12" role="tablist">
                            <li class="active">
                                <a href="#history-preview-tab" role="tab" data-toggle="tab">
                                    <i class="ace-icon fa fa-history"></i>
                                    History Preview
                                </a>
                            </li>
                            <li>
                                <a href="#uploaded-docs-tab" role="tab" data-toggle="tab">
                                    <i class="ace-icon fa fa-files-o"></i>
                                    Uploaded Documents
                                </a>
                            </li>
                        </ul>
                        <!-- Tabs Content -->
                        <div class="tab-content" style="background: #fff; border: 1px solid #e1e1e1; border-top: none; padding: 16px;">
                            <!-- History Preview Tab -->
                            <div class="tab-pane active" id="history-preview-tab">
                                <?php
                                // Modified query to include field changes
                                $history_query = "SELECT l.*, u.user_firstname, u.user_lastname 
                                                FROM inv_logs l 
                                                LEFT JOIN sys_usertb u ON l.userid = u.id 
                                                WHERE l.asset_id_ref = ? 
                                                ORDER BY l.datetimecreated DESC";
                                
                                $history_stmt = mysqli_prepare($conn, $history_query);
                                if ($history_stmt) {
                                    mysqli_stmt_bind_param($history_stmt, "s", $asset['id']);
                                    mysqli_stmt_execute($history_stmt);
                                    $history_result = mysqli_stmt_get_result($history_stmt);
                                    
                                    if (mysqli_num_rows($history_result) > 0) {
                                        echo '<div class="timeline-container">';
                                        $current_date = '';
                                        
                                        while ($history = mysqli_fetch_assoc($history_result)) {
                                            $date = date('M d, Y h:i:s A', strtotime($history['datetimecreated']));
                                            $user = htmlspecialchars($history['user_firstname'] . ' ' . $history['user_lastname']);
                                            
                                            // Add date header if it's a new date
                                            $day = date('M d, Y', strtotime($history['datetimecreated']));
                                            if ($current_date !== $day) {
                                                if ($current_date !== '') {
                                                    echo '</div>'; // Close previous date group
                                                }
                                                echo '<h4 class="timeline-date-header">' . date('M d, Y (l)', strtotime($history['datetimecreated'])) . '</h4>';
                                                echo '<div class="timeline-date-group">';
                                                $current_date = $day;
                                            }
                                            
                                            // Create collapsible timeline item
                                            echo '<div class="timeline-item" data-type="' . htmlspecialchars($history['type']) . '">';
                                            
                                            // Toggle button with summary
                                            echo '<button class="timeline-toggle collapsed" type="button" 
                                                  data-toggle="collapse" data-target="#timelineItem' . $history['id'] . '" 
                                                  aria-expanded="false" aria-controls="timelineItem' . $history['id'] . '">';
                                            echo '<i class="fa fa-chevron-down"></i>';
                                            echo '<span class="timeline-summary">';
                                            
                                            // Show a simplified summary in the toggle button
                                            if (!empty($history['field_name'])) {
                                                $action_type = htmlspecialchars($history['type']);
                                                $icon_class = '';
                                                switch($action_type) {
                                                    case 'UPDATE':
                                                        $icon_class = 'fa-pencil';
                                                        break;
                                                    case 'CREATE':
                                                        $icon_class = 'fa-plus-circle';
                                                        break;
                                                    case 'DELETE':
                                                        $icon_class = 'fa-trash';
                                                        break;
                                                    default:
                                                        $icon_class = 'fa-info-circle';
                                                }
                                                echo '<i class="fa ' . $icon_class . '"></i> <strong>[' . $action_type . ']</strong> ' . 
                                                     htmlspecialchars($history['field_name']);
                                            } elseif (strpos($history['remarks'], 'Asset checked out:') !== false) {
                                                echo '<i class="fa fa-check-circle"></i> <strong>[UPDATE]</strong>';
                                            }
                                            
                                            echo '</span>';
                                            echo '<span class="timeline-time">' . date('h:i:s A', strtotime($history['datetimecreated'])) . '</span>';
                                            echo '</button>';
                                            
                                            // Collapsible content
                                            echo '<div class="collapse" id="timelineItem' . $history['id'] . '">';
                                            echo '<div class="timeline-content">';
                                            
                                            // Move the detailed change information here
                                            if (!empty($history['field_name'])) {
                                                $old_value = (isset($history['old_value']) && $history['old_value'] !== '') ? 
                                                    htmlspecialchars($history['old_value']) : 'not set';
                                                $new_value = (isset($history['new_value']) && $history['new_value'] !== '') ? 
                                                    htmlspecialchars($history['new_value']) : 'not set';

                                                // Filter out "not set" → value changes
                                                if (!($old_value === 'not set' && $new_value !== 'not set')) {
                                                    echo '<div class="change-summary-row">';
                                                    echo '<div class="change-values">';
                                                    echo '<span class="text-danger"><i class="fa fa-minus-circle"></i> ' . $old_value . '</span>';
                                                    echo '<span class="change-arrow"><i class="fa fa-arrow-right"></i></span>';
                                                    echo '<span class="text-success"><i class="fa fa-plus-circle"></i> ' . $new_value . '</span>';
                                                    echo '</div>';
                                                    echo '</div>';
                                                }
                                            } elseif (strpos($history['remarks'], 'Asset checked out:') !== false) {
                                                // Get the previous log entry for comparison
                                                $prev_log_query = "SELECT remarks 
                                                                 FROM inv_logs 
                                                                 WHERE asset_id_ref = ? 
                                                                 AND datetimecreated < ?
                                                                 AND remarks LIKE '%Asset checked out:%'
                                                                 ORDER BY datetimecreated DESC 
                                                                 LIMIT 1";
                                                $prev_log_stmt = mysqli_prepare($conn, $prev_log_query);
                                                mysqli_stmt_bind_param($prev_log_stmt, "ss", $asset['id'], $history['datetimecreated']);
                                                mysqli_stmt_execute($prev_log_stmt);
                                                $prev_log_result = mysqli_stmt_get_result($prev_log_stmt);
                                                $prev_log = mysqli_fetch_assoc($prev_log_result);
                                                mysqli_stmt_close($prev_log_stmt);

                                                // Parse current and previous remarks
                                                $current_values = [];
                                                $prev_values = [];
                                                
                                                // Parse current remarks
                                                $remarks_parts = explode('|', $history['remarks']);
                                                foreach ($remarks_parts as $part) {
                                                    $part = trim($part);
                                                    if (strpos($part, ':') !== false) {
                                                        list($field, $value) = explode(':', $part, 2);
                                                        $current_values[trim($field)] = trim($value);
                                                    }
                                                }

                                                // Parse previous remarks if they exist
                                                if ($prev_log) {
                                                    $prev_parts = explode('|', $prev_log['remarks']);
                                                    foreach ($prev_parts as $part) {
                                                        $part = trim($part);
                                                        if (strpos($part, ':') !== false) {
                                                            list($field, $value) = explode(':', $part, 2);
                                                            $prev_values[trim($field)] = trim($value);
                                                        }
                                                    }
                                                }

                                                // Compare and collect changes, but filter out "not set" → value
                                                $changes = [];
                                                foreach ($current_values as $field => $value) {
                                                    $prev_value = isset($prev_values[$field]) ? $prev_values[$field] : 'not set';
                                                    if (!($prev_value === 'not set' && $value !== 'not set' && $value !== '')) {
                                                        if ($prev_value !== $value) {
                                                            $changes[] = $field . ': ' . 
                                                                        '<span class="text-danger">' . $prev_value . '</span>' . 
                                                                        ' → ' . 
                                                                        '<span class="text-success">' . $value . '</span>';
                                                        }
                                                    }
                                                }

                                                // Display the detailed changes inside the collapsed content
                                                if (!empty($changes)) {
                                                    echo '<div class="change-details">';
                                                    foreach ($changes as $change) {
                                                        echo '<div class="change-item">' . $change . '</div>';
                                                    }
                                                    echo '</div>';
                                                }
                                            }
                                            
                                            echo '<div class="timeline-user" style="color: #000;">Changed by: ' . $user . '</div>';
                                            echo '</div>'; // end timeline-content
                                            echo '</div>'; // end collapse
                                            echo '</div>'; // end timeline-item
                                        }
                                        
                                        // Close last date group
                                        if ($current_date !== '') {
                                            echo '</div>';
                                        }
                                        
                                        echo '</div>'; // end timeline-container
                                    } else {
                                        echo '<div class="alert alert-info">No changes recorded for this asset.</div>';
                                    }
                                    mysqli_stmt_close($history_stmt);
                                }
                                ?>
                            </div>
                            <!-- Uploaded Documents Tab -->
                            <div class="tab-pane" id="uploaded-docs-tab">
                                <?php
                                // Fetch all PDF/Word files for this asset
                                $docs = [];
                                $file_stmt = mysqli_prepare($conn, "SELECT * FROM inv_asset_files WHERE asset_id = ? AND (file_data LIKE '%.pdf' OR file_data LIKE '%.doc' OR file_data LIKE '%.docx' OR file_data LIKE '%.htm' OR file_data LIKE '%.html') ORDER BY datetimecreated DESC");
                                if ($file_stmt) {
                                    mysqli_stmt_bind_param($file_stmt, "s", $asset['asset_id']);
                                    mysqli_stmt_execute($file_stmt);
                                    $file_result = mysqli_stmt_get_result($file_stmt);
                                    while ($row = mysqli_fetch_assoc($file_result)) {
                                        $docs[] = $row;
                                    }
                                    mysqli_stmt_close($file_stmt);
                                }
                                ?>
                                <?php if (count($docs) > 0): ?>
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>File Name</th>
                                                <th>Uploaded At</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($docs as $row): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars(basename($row['file_data'])); ?></td>
                                                    <td><?php echo htmlspecialchars($row['datetimecreated']); ?></td>
                                                <td>
                                                    <?php
                                                    $rawPath = $row['file_data'];
                                                    // Normalize Windows backslashes to URL-friendly forward slashes
                                                    $rawPath = str_replace('\\', '/', $rawPath);
                                                    // Build absolute URL for viewers (handles relative stored paths)
                                                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                                                    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
                                                    $absoluteUrl = $rawPath;
                                                    if ($host && (strpos($rawPath, 'http://') !== 0 && strpos($rawPath, 'https://') !== 0)) {
                                                        $absoluteUrl = $scheme . '://' . $host . '/' . ltrim($rawPath, '/');
                                                    }
                                                    $fileUrlEscaped = htmlspecialchars($rawPath, ENT_QUOTES, 'UTF-8');
                                                    // Build view URL via secure endpoint (inline)
                                                    $viewUrl = 'download_asset_file.php?inline=1&f=' . urlencode(base64_encode($rawPath));
                                                    $pathForExt = parse_url($rawPath, PHP_URL_PATH);
                                                    $ext = strtolower(pathinfo($pathForExt ?: $rawPath, PATHINFO_EXTENSION));
                                                    if ($ext === 'pdf' || $ext === 'htm' || $ext === 'html'): ?>
                                                        <a href="<?php echo $viewUrl; ?>" target="_blank" class="btn btn-xs btn-info">View</a>
                                                    <?php endif; ?>
                                                    <?php
                                                        // Build encoded absolute URL for reliable downloads (handles spaces and special chars)
                                                        $downloadUrl = $absoluteUrl;
                                                        $parsedUrl = parse_url($absoluteUrl);
                                                        if ($parsedUrl && isset($parsedUrl['path'])) {
                                                            $segments = array_filter(explode('/', $parsedUrl['path']), 'strlen');
                                                            $segments = array_map('rawurlencode', $segments);
                                                            $encodedPath = '/' . implode('/', $segments);
                                                            $downloadUrl = (isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '')
                                                                . (isset($parsedUrl['host']) ? $parsedUrl['host'] : '')
                                                                . (isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '')
                                                                . $encodedPath
                                                                . (isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '');
                                                        }
                                                    ?>
                                                    <a href="download_asset_file.php?f=<?php echo urlencode(base64_encode($rawPath)); ?>" class="btn btn-xs btn-success">
                                                        <i class="fa fa-download"></i> Download
                                                    </a>
                                                </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="alert alert-info">No PDF or Word documents uploaded for this asset.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add this script before including footer -->
<script type="text/javascript">
    window.addEventListener('load', function() {
        try {
            // Remove any existing border styles
            var sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.style.border = 'none';
            }
            
            var navItems = document.querySelectorAll('.nav-list > li');
            navItems.forEach(function(item) {
                item.style.border = 'none';
            });

            // Initialize ace settings if available
            if (typeof ace !== 'undefined' && ace.settings) {
                ace.settings.loadState('sidebar');
            }
        } catch(e) {
            console.log('Error initializing sidebar:', e);
        }
    });
</script>

<!-- Add this after your jQuery and Bootstrap JS includes if not already present -->
<script>
$(function() {
    // Activate the correct tab on click
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        window.location.hash = $(e.target).attr('href');
    });
    // On page load, activate tab from hash
    var hash = window.location.hash;
    if (hash) {
        $('.nav-tabs a[href="' + hash + '"]').tab('show');
    }
});
</script>

<script>
$(document).ready(function() {
    // Handle toggle button icon rotation
    $('.timeline-toggle').on('click', function() {
        $(this).toggleClass('collapsed');
    });

    // Optional: Add smooth animation to collapse
    $('.collapse').on('show.bs.collapse', function() {
        $(this).closest('.timeline-item')
               .find('.timeline-toggle')
               .removeClass('collapsed');
    });

    $('.collapse').on('hide.bs.collapse', function() {
        $(this).closest('.timeline-item')
               .find('.timeline-toggle')
               .addClass('collapsed');
    });
});
</script>

<?php
    // Include footer block
    include('blocks/footer.php');
} else {
    // Include header for error page
    include('blocks/header.php');
?>
<!-- Add the same styles for error page -->
<style>
    .sidebar {
        border-style: none !important;
    }
    .nav-list > li {
        border-style: none !important;
    }
    .nav-list > li > a {
        border-style: none !important;
    }
    .sidebar.responsive {
        border-style: none !important;
    }
    #sidebar.sidebar.responsive.ace-save-state {
        border-style: none !important;
    }
</style>

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
                    <li><a href="inventory.php">Inventory</a></li>
                    <li class="active">Asset Details</li>
                </ul>
            </div>

            <div class="page-content">
                <div style="margin: 20px; padding: 20px; border: 1px solid #dc3545; border-radius: 4px;">
                    <h4 style="color: #dc3545;">Asset Not Found</h4>
                    <p>The asset with ID <?php echo htmlspecialchars($asset_id); ?> could not be found.</p>
                    <p><a href="inventory.php" style="color: #0056b3;">Return to Inventory List</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add the same script for error page -->
<script type="text/javascript">
    window.addEventListener('load', function() {
        try {
            var sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.style.border = 'none';
            }
            
            var navItems = document.querySelectorAll('.nav-list > li');
            navItems.forEach(function(item) {
                item.style.border = 'none';
            });

            if (typeof ace !== 'undefined' && ace.settings) {
                ace.settings.loadState('sidebar');
            }
        } catch(e) {
            console.log('Error initializing sidebar:', e);
        }
    });
</script>

<?php
    // Include footer
    include('blocks/footer.php');
}

// Clean up
mysqli_stmt_close($stmt);
?>