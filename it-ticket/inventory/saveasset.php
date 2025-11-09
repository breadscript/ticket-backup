<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require('../conn/db.php');
require_once('../projectmanagement/upload_file.php');

$conn = connectionDB();
$moduleid=4;

$nowdateunix = date("Y-m-d",time());    

function alphanumericAndSpace($string) {
   return preg_replace("/[^,;a-zA-Z0-9 _-]|[,; ]$/s", "", $string);
}

// Debug incoming data
error_log("POST Data: " . print_r($_POST, true));
error_log("GET Data: " . print_r($_GET, true));

// Get data from POST or GET
$assetUserid = isset($_POST['assetUserid']) ? strtoupper($_POST['assetUserid']) : (isset($_GET['assetUserid']) ? strtoupper($_GET['assetUserid']) : '');
$asset_tag = isset($_POST['asset_tag']) ? $_POST['asset_tag'] : (isset($_GET['asset_tag']) ? $_GET['asset_tag'] : '');

// Handle multiple values for current_owner, previous_owner, and assignee
$current_owner = isset($_POST['current_owner']) ? implode(',', $_POST['current_owner']) : (isset($_GET['current_owner']) ? $_GET['current_owner'] : '');
$previous_owner = isset($_POST['previous_owner']) ? implode(',', $_POST['previous_owner']) : (isset($_GET['previous_owner']) ? $_GET['previous_owner'] : '');
$assignee = isset($_POST['assignee']) ? implode(',', $_POST['assignee']) : '';

$status = isset($_POST['status']) ? $_POST['status'] : (isset($_GET['status']) ? $_GET['status'] : '');
$department = isset($_POST['department']) ? $_POST['department'] : (isset($_GET['department']) ? $_GET['department'] : '');
$company = isset($_POST['company']) ? $_POST['company'] : (isset($_GET['company']) ? $_GET['company'] : '');
$asset_type = isset($_POST['asset_type']) ? $_POST['asset_type'] : (isset($_GET['asset_type']) ? $_GET['asset_type'] : '');
$manufacturer = isset($_POST['manufacturer']) ? $_POST['manufacturer'] : (isset($_GET['manufacturer']) ? $_GET['manufacturer'] : '');
$model = isset($_POST['model']) ? $_POST['model'] : (isset($_GET['model']) ? $_GET['model'] : '');
$color = isset($_POST['color']) ? $_POST['color'] : (isset($_GET['color']) ? $_GET['color'] : '');
$serial_number = isset($_POST['serial_number']) ? $_POST['serial_number'] : (isset($_GET['serial_number']) ? $_GET['serial_number'] : '');
$chasis_number = isset($_POST['chasis_number']) ? $_POST['chasis_number'] : (isset($_GET['chasis_number']) ? $_GET['chasis_number'] : '');
$charger_number = isset($_POST['charger_number']) ? $_POST['charger_number'] : (isset($_GET['charger_number']) ? $_GET['charger_number'] : '');
$sim_number = isset($_POST['sim_number']) ? $_POST['sim_number'] : (isset($_GET['sim_number']) ? $_GET['sim_number'] : '');
$purchase_date = isset($_POST['purchase_date']) ? $_POST['purchase_date'] : (isset($_GET['purchase_date']) ? $_GET['purchase_date'] : '');
$purchase_price = isset($_POST['purchase_price']) ? $_POST['purchase_price'] : (isset($_GET['purchase_price']) ? $_GET['purchase_price'] : '');
$order_number = isset($_POST['order_number']) ? $_POST['order_number'] : (isset($_GET['order_number']) ? $_GET['order_number'] : '');
$due_date = isset($_POST['due_date']) ? $_POST['due_date'] : (isset($_GET['due_date']) ? $_GET['due_date'] : '');
$condition_notes = isset($_POST['condition_notes']) ? $_POST['condition_notes'] : (isset($_GET['condition_notes']) ? $_GET['condition_notes'] : '');
$warranty_start = isset($_POST['warranty_start']) ? $_POST['warranty_start'] : (isset($_GET['warranty_start']) ? $_GET['warranty_start'] : '');
$warranty_end = isset($_POST['warranty_end']) ? $_POST['warranty_end'] : (isset($_GET['warranty_end']) ? $_GET['warranty_end'] : '');
$osver = isset($_POST['os_version']) ? $_POST['os_version'] : (isset($_GET['os_version']) ? $_GET['os_version'] : '');
$antivirus = isset($_POST['antivirus_version']) ? $_POST['antivirus_version'] : (isset($_GET['antivirus_version']) ? $_GET['antivirus_version'] : '');
$anydesk_address = isset($_POST['anydesk_address']) ? $_POST['anydesk_address'] : (isset($_GET['anydesk_address']) ? $_GET['anydesk_address'] : '');

// Debug the processed values
echo "<div style='display:none;'>";
echo "Debug Values:<br>";
echo "UserID: " . $assetUserid . "<br>";
echo "Asset Tag: " . $asset_tag . "<br>";
echo "Current Owner: " . $current_owner . "<br>";
echo "Status: " . $status . "<br>";
echo "</div>";

/*-------for audit trails--------*/
$auditRemarks1 = "REGISTER NEW ASSET" . " | " . $asset_tag;
$auditRemarks2 = "ATTEMPT TO REGISTER NEW ASSET, FAILED" . " | " . $asset_tag;
$auditRemarks3 = "ATTEMPT TO REGISTER NEW ASSET, DUPLICATE" . " | " . $asset_tag;

// Check if asset_tag already exists
$myquery = "SELECT asset_tag FROM inv_inventorylisttb WHERE asset_tag = '$asset_tag' LIMIT 1";
$myresult = mysqli_query($conn, $myquery);
$num_rows = mysqli_num_rows($myresult);

if($num_rows == 0) {
    // Get current count of assets
    $countQuery = "SELECT COUNT(*) as total FROM inv_inventorylisttb";
    $countResult = mysqli_query($conn, $countQuery);
    $countRow = mysqli_fetch_assoc($countResult);
    $currentCount = $countRow['total'] + 1;
    
    // Generate asset ID
    $asset_id = "ASSET:" . date('YmdHis') . $currentCount;
    
    // Generate astID
    $astID = "AST:" . date('YmdHis') . $currentCount;
    
    // Handle multiple values for owners and assignee
    $current_owner = isset($_POST['current_owner']) ? implode(',', $_POST['current_owner']) : '';
    $previous_owner = isset($_POST['previous_owner']) ? implode(',', $_POST['previous_owner']) : '';
    $assignee = isset($_POST['assignee']) ? implode(',', $_POST['assignee']) : '';
    
    // Get current datetime
    $datetimecreated = date('Y-m-d H:i:s');
    
    // Set default statusid
    $statusid = 0;
    
    // Insert query
    $myqueryxx = "INSERT INTO inv_inventorylisttb (
    asset_id, asset_tag, current_owner, previous_owner, status, 
    department, company, asset_type, manufacturer, model, 
    color, serial_number, sim_number, purchase_date, purchase_price, 
    order_number, due_date, condition_notes, datetimecreated, createdbyid,
    astID, assignee, statusid,
    warranty_start,
    warranty_end,
    osver,
    antivirus,
    anydesk_address,
    chasis_number,
    charger_number
) VALUES (
    '$asset_id', '$asset_tag', '$current_owner', '$previous_owner', '$status', 
    '$department', '$company', '$asset_type', '$manufacturer', '$model', 
    '$color', '$serial_number', '$sim_number', '$purchase_date', '$purchase_price', 
    '$order_number', '$due_date', '$condition_notes', '$datetimecreated', '$assetUserid',
    '$astID', '$assignee', '$statusid',
    '$warranty_start',
    '$warranty_end',
    '$osver',
    '$antivirus',
    '$anydesk_address',
    '$chasis_number',
    '$charger_number'
)";
    $myresultxx = mysqli_query($conn, $myqueryxx);
    
    $lastid = mysqli_insert_id($conn);
    
    if($myresultxx) {
        // Handle file uploads if present
        $fileMessage = "";
        if(isset($_FILES['attachFile']) && !empty($_FILES['attachFile']['name'][0])) {
            try {
                $uploadResult = uploadFiles($conn, $lastid, $assetUserid, $_FILES['attachFile']);
                if ($uploadResult['status'] === 'success') {
                    $fileMessage = " with " . count($uploadResult['files']) . " attached file(s)";
                }
            } catch (Exception $e) {
                error_log("File upload failed: " . $e->getMessage());
                $fileMessage = " (file upload failed: " . $e->getMessage() . ")";
            }
        }
        
        // Audit trail
        $queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES ('$moduleid','$auditRemarks1','$assetUserid')";
        $resaud1 = mysqli_query($conn, $queryaud1);
        
        echo "<div id='successMessage' style='text-align: center; padding: 20px; background-color: #4CAF50; color: white; margin: 20px;'>";
        echo "<h3>Asset Registered Successfully!" . $fileMessage . "</h3>";
        echo "<p>Redirecting to asset list...</p>";
        echo "</div>";
        
        echo "<script type='text/javascript'>
            // Immediately execute when script loads
            (function() {
                // Ensure jQuery is loaded
                if (typeof jQuery === 'undefined') {
                    console.error('jQuery not loaded');
                    return;
                }

                // Disable form resubmission on refresh
                if (window.history.replaceState) {
                    window.history.replaceState(null, null, window.location.href);
                }

                // Function to clean up modal
                function cleanupModal() {
                    try {
                        // Remove all event listeners from the form
                        jQuery('form').each(function() {
                            jQuery(this).off();
                            this.onsubmit = null;
                        });

                        // Disable all form elements
                        jQuery('form :input').prop('disabled', true);

                        // Close bootstrap modal with both methods
                        var modals = jQuery('.modal');
                        modals.each(function() {
                            jQuery(this).modal('hide').remove();
                        });

                        // Clean up modal artifacts
                        jQuery('body').removeClass('modal-open').css('padding-right', '');
                        jQuery('.modal-backdrop').remove();

                        // Force any remaining modals closed
                        jQuery('.modal').hide().remove();
                    } catch (e) {
                        console.error('Modal cleanup error:', e);
                    }
                }

                // Execute cleanup
                cleanupModal();

                // Redirect after delay
                setTimeout(function() {
                    window.location.href = '../inventory/inventory.php';
                }, 1500);

            })();
        </script>";
    } else {
        $queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES ('$moduleid','$auditRemarks2','$assetUserid')";
        $resaud1 = mysqli_query($conn, $queryaud1);
        echo "<div style='text-align: center; padding: 20px; background-color: #f44336; color: white; margin: 20px;'>";
        echo "<h3>Failed to register asset</h3>";
        echo "</div>";
    }
} else {
    $queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES ('$moduleid','$auditRemarks3','$assetUserid')";
    $resaud1 = mysqli_query($conn, $queryaud1);
    
    // Debug query to see what exists
    $debugQuery = "SELECT * FROM inv_inventorylisttb WHERE asset_tag = '$asset_tag'";
    $debugResult = mysqli_query($conn, $debugQuery);
    $existingAsset = mysqli_fetch_assoc($debugResult);
    
    echo "<div class='alert alert-danger fade in'>";
    echo "Duplicate Entry! Asset tag already exists.";
    echo "</div>";
}
?>