<?php 
include('../check_session.php');
$moduleid=4;
$conn = connectionDB();

$asset_tag = $_GET['asset_tag'];

// Query to fetch the asset details with all fields
$myquery = "SELECT * FROM inv_inventorylisttb WHERE asset_tag = '$asset_tag'";
$myresult = mysqli_query($conn, $myquery);
$asset = mysqli_fetch_assoc($myresult);

// Check if asset was found
if (!$asset) {
    echo "<div class='alert alert-danger'>Asset not found. Please check the asset tag.</div>";
    exit;
}

// Handle NULL values properly - don't set defaults
foreach ($asset as $key => $value) {
    if ($value === null || $value === 'N/A') {
        $asset[$key] = '';
    }
}

// Special handling for purchase_price
if (empty($asset['purchase_price']) || $asset['purchase_price'] === '0.00') {
    $asset['purchase_price'] = '';
}

// Escape all values for display
foreach ($asset as $key => $value) {
    if ($key !== 'purchase_price') { // Skip purchase_price as it needs special handling
        $asset[$key] = htmlspecialchars($value);
    }
}

// Format purchase price if it exists
if (!empty($asset['purchase_price'])) {
    $asset['purchase_price'] = number_format((float)$asset['purchase_price'], 2, '.', '');
}

// Query to fetch accessories from inv_inventorylisttb
$query = "SELECT *
FROM inv_inventorylisttb
WHERE statusid = 0 
AND asset_type NOT IN ('PC Desktop', 'Laptop', 'Smartphone', 'Tablet', 'Printer')";

$result = mysqli_query($conn, $query);

$acc_query = "SELECT COUNT(*) as count FROM inv_inventorylisttb WHERE statusid = 0 AND asset_type NOT IN ('PC Desktop', 'Laptop', 'Smartphone', 'Tablet', 'Printer')";
?>

<!-- jQuery Tags Input -->
<link href="../assets/customjs/jquery.tagsinput.css" rel="stylesheet">
<!-- page specific plugin styles -->
<link rel="stylesheet" href="../assets/css/chosen.min.css" />

<div class="modal-body">
    <div id="signup-box" class="signup-box widget-box no-border">
        <div class="widget-body">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><small>×</small></button>
            <h4 class="header green lighter bigger">
                <i class="ace-icon fa fa-cubes blue"></i>
                Update Asset Information
            </h4>
            <p>Modify asset details below:</p>
            <form id="assetInfoUpdate" enctype="multipart/form-data">
                <input type="hidden" class="form-control" id="asset_tag" name="asset_tag" value="<?php echo htmlspecialchars($asset['asset_tag']); ?>"/>
                <input type="hidden" class="form-control" id="assetUserid" name="assetUserid" value="<?php echo $userid; ?>"/>
                <fieldset>
                    <div class="row">
                        <div class="col-lg-6">
                            <!-- Left Column -->
                            <div class="form-group">
                                <label class="block clearfix">Asset Type
                                    <span class="block">
                                        <select class="form-control" id="asset_type" name="asset_type" data-placeholder="Select Asset Type">
                                            <option value="">Select Asset Type</option>
                                            <option value="PC Desktop" <?php echo ($asset['asset_type'] === "PC Desktop") ? 'selected' : ''; ?>>PC Desktop</option>
                                            <option value="Laptop" <?php echo ($asset['asset_type'] === "Laptop") ? 'selected' : ''; ?>>Laptop</option>
                                            <option value="Smartphone" <?php echo ($asset['asset_type'] === "Smartphone") ? 'selected' : ''; ?>>Smartphone</option>
                                            <option value="Tablets" <?php echo ($asset['asset_type'] === "Tablets") ? 'selected' : ''; ?>>Tablets</option>
                                            <option value="Printers" <?php echo ($asset['asset_type'] === "Printers") ? 'selected' : ''; ?>>Printers</option>
                                            <option value="Accessories" <?php echo ($asset['asset_type'] === "Accessories") ? 'selected' : ''; ?>>Accessories</option>
                                        </select>
                                    </span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="block clearfix">Manufacturer
                                    <span class="block">
                                        <select class="form-control" id="manufacturer" name="manufacturer" data-placeholder="Select Manufacturer">
                                            <option value="">Select Manufacturer</option>
                                            <option value="A4 Tech" <?php echo ($asset['manufacturer'] === "A4 Tech") ? 'selected' : ''; ?>>A4 Tech</option>
                                            <option value="ACER" <?php echo ($asset['manufacturer'] === "ACER") ? 'selected' : ''; ?>>ACER</option>
                                            <option value="Apple" <?php echo ($asset['manufacturer'] === "Apple") ? 'selected' : ''; ?>>Apple</option>
                                            <option value="ASUS" <?php echo ($asset['manufacturer'] === "ASUS") ? 'selected' : ''; ?>>ASUS</option>
                                            <option value="Brother" <?php echo ($asset['manufacturer'] === "Brother") ? 'selected' : ''; ?>>Brother</option>
                                            <option value="DELL" <?php echo ($asset['manufacturer'] === "DELL") ? 'selected' : ''; ?>>DELL</option>
                                            <option value="EPSON" <?php echo ($asset['manufacturer'] === "EPSON") ? 'selected' : ''; ?>>EPSON</option>
                                            <option value="HP" <?php echo ($asset['manufacturer'] === "HP") ? 'selected' : ''; ?>>HP</option>
                                            <option value="LENOVO" <?php echo ($asset['manufacturer'] === "LENOVO") ? 'selected' : ''; ?>>LENOVO</option>
                                            <option value="PLDT/SMART" <?php echo ($asset['manufacturer'] === "PLDT/SMART") ? 'selected' : ''; ?>>PLDT/SMART</option>
                                            <option value="SAMSUNG" <?php echo ($asset['manufacturer'] === "SAMSUNG") ? 'selected' : ''; ?>>SAMSUNG</option>
                                            <option value="Smart" <?php echo ($asset['manufacturer'] === "Smart") ? 'selected' : ''; ?>>Smart</option>
                                            <option value="Other" <?php echo ($asset['manufacturer'] === "Other") ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="block clearfix">Model
                                    <span class="block">
                                        <textarea class="form-control" rows="3" placeholder="Enter model" id="model" name="model"><?php echo htmlspecialchars($asset['model']); ?></textarea>
                                    </span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="block clearfix">Chasis Serial Number
                                    <span class="block">
                                        <input class="form-control" placeholder="Enter serial number" id="serial_number" name="serial_number" value="<?php echo htmlspecialchars($asset['serial_number']); ?>" />
                                    </span>
                                </label>
                            </div>

                            <!-- <div class="form-group">
                                <label class="block clearfix">Chasis Number
                                    <span class="block">
                                        <input class="form-control" placeholder="Enter chassis number" id="chasis_number" name="chasis_number" value="<?php echo htmlspecialchars($asset['chasis_number']); ?>" />
                                    </span>
                                </label>
                            </div> -->

                            <div class="form-group">
                                <label class="block clearfix">Serial No. Charger
                                    <span class="block">
                                        <input class="form-control" placeholder="Enter charger serial number" id="charger_number" name="charger_number" value="<?php echo htmlspecialchars($asset['charger_number']); ?>" />
                                    </span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="block clearfix">SIM Number
                                    <span class="block">
                                        <input class="form-control" placeholder="Enter SIM number" id="sim_number" name="sim_number" value="<?php echo htmlspecialchars($asset['sim_number']); ?>" />
                                    </span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="block clearfix">Color
                                    <span class="block">
                                        <input class="form-control" placeholder="Enter color" id="color" name="color" value="<?php echo htmlspecialchars($asset['color']); ?>" />
                                    </span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="block clearfix">Company
                                    <span class="block">
                                        <select class="form-control" id="company" name="company" data-placeholder="Select Company">
                                            <option value="">Select Company</option>
                                            <?php 
                                                $query66 = "SELECT id, clientname FROM sys_clienttb WHERE clientstatusid = '1'";
                                                $result66 = mysqli_query($conn, $query66);
                                                while($row66 = mysqli_fetch_assoc($result66)) {
                                                    $clientname = mb_convert_case($row66['clientname'], MB_CASE_TITLE, "UTF-8");
                                                    $selected = ($asset['company'] === $clientname) ? 'selected' : '';
                                                    echo "<option value=\"$clientname\" $selected>$clientname</option>";
                                                }
                                            ?>
                                        </select>
                                    </span>
                                </label>
                            </div>

                            <div class="form-group">
                               <label class="block clearfix">Current Checked Out To:
                                   <span class="block">
                                       <?php
                                           // Build map of active user id => Name
                                           $queryUsers = "SELECT id, user_firstname, user_lastname FROM sys_usertb WHERE user_statusid = '1'";
                                           $resultUsers = mysqli_query($conn, $queryUsers);
                                           $nameById = [];
                                           while ($row = mysqli_fetch_assoc($resultUsers)) {
                                               $nameById[$row['id']] = mb_convert_case($row['user_firstname'], MB_CASE_TITLE, "UTF-8") . " " . mb_convert_case($row['user_lastname'], MB_CASE_TITLE, "UTF-8");
                                           }

                                           // Resolve selected owners to names
                                           $selectedOwners = ($asset['current_owner'] === "N/A" || $asset['current_owner'] === "" || $asset['current_owner'] === null)
                                               ? []
                                               : explode(',', $asset['current_owner']);

                                           $ownerNames = [];
                                           foreach ($selectedOwners as $ownerId) {
                                               $ownerId = trim($ownerId);
                                               if ($ownerId !== '' && isset($nameById[$ownerId])) {
                                                   $ownerNames[] = $nameById[$ownerId];
                                               }
                                           }

                                           $currentOwnerDisplay = empty($ownerNames) ? '' : implode(', ', $ownerNames);
                                       ?>
                                       <input type="text"
                                              id="current_owner"
                                              name="current_owner"
                                              class="form-control"
                                              value="<?php echo htmlspecialchars($currentOwnerDisplay, ENT_QUOTES, 'UTF-8'); ?>"
                                              disabled>
                                   </span>
                               </label>
                           </div>

                            <div class="form-group">
                                    <label class="block clearfix">OS Version
                                        <span class="block">
                                            <select class="form-control" id="osver" name="osver">
                                                <option value="">Select OS Version</option>
                                                <option value="windows10_home" <?php echo ($asset['osver'] === "windows10_home") ? 'selected' : ''; ?>>Windows 10 Home</option>
                                                <option value="windows10_pro" <?php echo ($asset['osver'] === "windows10_pro") ? 'selected' : ''; ?>>Windows 10 Pro</option>
                                                <option value="windows10_pro_workstations" <?php echo ($asset['osver'] === "windows10_pro_workstations") ? 'selected' : ''; ?>>Windows 10 Pro for Workstations</option>
                                                <option value="windows10_enterprise" <?php echo ($asset['osver'] === "windows10_enterprise") ? 'selected' : ''; ?>>Windows 10 Enterprise</option>
                                                <option value="windows10_education" <?php echo ($asset['osver'] === "windows10_education") ? 'selected' : ''; ?>>Windows 10 Education</option>
                                                <option value="windows10_pro_education" <?php echo ($asset['osver'] === "windows10_pro_education") ? 'selected' : ''; ?>>Windows 10 Pro Education</option>
                                                <option value="windows10_s" <?php echo ($asset['osver'] === "windows10_s") ? 'selected' : ''; ?>>Windows 10 S / S Mode</option>
                                                <option value="windows10_mobile" <?php echo ($asset['osver'] === "windows10_mobile") ? 'selected' : ''; ?>>Windows 10 Mobile</option>
                                                <option value="windows10_mobile_enterprise" <?php echo ($asset['osver'] === "windows10_mobile_enterprise") ? 'selected' : ''; ?>>Windows 10 Mobile Enterprise</option>
                                                <option value="windows10_iot_core" <?php echo ($asset['osver'] === "windows10_iot_core") ? 'selected' : ''; ?>>Windows 10 IoT Core</option>
                                                <option value="windows10_iot_enterprise" <?php echo ($asset['osver'] === "windows10_iot_enterprise") ? 'selected' : ''; ?>>Windows 10 IoT Enterprise</option>
                                                <option value="windows10_team" <?php echo ($asset['osver'] === "windows10_team") ? 'selected' : ''; ?>>Windows 10 Team</option>
                                                <option value="windows10_ltsc" <?php echo ($asset['osver'] === "windows10_ltsc") ? 'selected' : ''; ?>>Windows 10 LTSC</option>
                                                <option value="windows11_home" <?php echo ($asset['osver'] === "windows11_home") ? 'selected' : ''; ?>>Windows 11 Home</option>
                                                <option value="windows11_pro" <?php echo ($asset['osver'] === "windows11_pro") ? 'selected' : ''; ?>>Windows 11 Pro</option>
                                                <option value="windows11_pro_workstations" <?php echo ($asset['osver'] === "windows11_pro_workstations") ? 'selected' : ''; ?>>Windows 11 Pro for Workstations</option>
                                                <option value="windows11_enterprise" <?php echo ($asset['osver'] === "windows11_enterprise") ? 'selected' : ''; ?>>Windows 11 Enterprise</option>
                                                <option value="windows11_education" <?php echo ($asset['osver'] === "windows11_education") ? 'selected' : ''; ?>>Windows 11 Education</option>
                                                <option value="windows11_pro_education" <?php echo ($asset['osver'] === "windows11_pro_education") ? 'selected' : ''; ?>>Windows 11 Pro Education</option>
                                                <option value="windows11_se" <?php echo ($asset['osver'] === "windows11_se") ? 'selected' : ''; ?>>Windows 11 SE</option>
                                                <option value="windows11_iot_enterprise" <?php echo ($asset['osver'] === "windows11_iot_enterprise") ? 'selected' : ''; ?>>Windows 11 IoT Enterprise</option>
                                                <option value="windows11_ltsc" <?php echo ($asset['osver'] === "windows11_ltsc") ? 'selected' : ''; ?>>Windows 11 LTSC (upcoming)</option>
                                            </select>
                                        </span>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label class="block clearfix">Antivirus Version
                                        <span class="block">
                                            <select class="form-control" id="antivirus" name="antivirus">
                                                <option value="">Select Antivirus</option>
                                                <option value="sophos" <?php echo ($asset['antivirus'] === "sophos") ? 'selected' : ''; ?>>Sophos Endpoint</option>
                                                <option value="escan" <?php echo ($asset['antivirus'] === "escan") ? 'selected' : ''; ?>>eScan</option>                                            
                                                <option value="others" <?php echo ($asset['antivirus'] === "others") ? 'selected' : ''; ?>>Others</option>
                                            </select>
                                        </span>
                                    </label>
                                </div>

                            <div class="form-group">
                                <label class="block clearfix">Accessories
                                    <span class="block">
                                        <select multiple name="accessories[]" id="accessories" class="chosen-select form-control" data-placeholder="Choose Accessories...">
                                            <option value="">Select accessories...</option>
                                            <?php 
                                                $queryAccessories = "SELECT asset_tag, asset_type, manufacturer, model 
                                                   FROM inv_inventorylisttb 
                                                   WHERE statusid = 0 
                                                   AND asset_type NOT IN ('PC Desktop', 'Laptop', 'Smartphone', 'Tablet', 'Printer')
                                                   ORDER BY asset_type, manufacturer, model";
                                                
                                                $resultAccessories = mysqli_query($conn, $queryAccessories);
                                                // Only explode if accessories is not null and not empty
                                                $selectedAccessories = (!empty($asset['accessories']) && $asset['accessories'] !== "N/A") 
                                                    ? explode(',', $asset['accessories']) 
                                                    : [];
                                                
                                                while($row = mysqli_fetch_assoc($resultAccessories)) {
                                                    $displayText = $row['asset_tag'] . ' - ' . $row['asset_type'];
                                                    if (!empty($row['manufacturer'])) {
                                                        $displayText .= ' (' . $row['manufacturer'];
                                                        if (!empty($row['model'])) {
                                                            $displayText .= ' ' . $row['model'];
                                                        }
                                                        $displayText .= ')';
                                                    }
                                                    $selected = in_array($row['asset_tag'], $selectedAccessories) ? 'selected' : '';
                                                    echo "<option value=\"{$row['asset_tag']}\" $selected>$displayText</option>";
                                                }
                                            ?>
                                        </select>
                                    </span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="block clearfix">Anydesk Address
                                    <span class="block">
                                        <input class="form-control" placeholder="Enter AnyDesk address" id="anydesk_address" name="anydesk_address" value="<?php echo htmlspecialchars($asset['anydesk_address']); ?>" />
                                    </span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="block clearfix">
                                    <span class="block">
                                        <input type="checkbox" id="bag" name="bag" value="1" <?php echo (isset($asset['bag']) && $asset['bag'] == 1) ? 'checked' : ''; ?> />
                                        <span style="margin-left: 8px;">Has Bag?</span>
                                    </span>
                                </label>
                            </div>   

                        </div>

                        <div class="col-lg-6">
                            <!-- Right Column -->
                            <div class="form-group">
                                <label class="block clearfix">Status
                                    <span class="block">
                                        <select class="form-control" id="status" name="status">
                                            <option value="">Select Status</option>
                                            <?php
                                            $statuses = ['Available', 'In repair', 'In use', 'Lost', 'Reserved', 'Retired', 'S.U Available', 'S.U Deployed', 'Upcoming'];
                                            foreach ($statuses as $statusOption) {
                                                $selected = ($asset['status'] === $statusOption) ? 'selected' : '';
                                                echo "<option value=\"$statusOption\" $selected>$statusOption</option>";
                                            }
                                            ?>
                                        </select>
                                    </span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="block clearfix">Department Assigned
                                    <span class="block">
                                        <select class="form-control" id="department" name="department">
                                            <option value="">Select Department</option>
                                            <option value="Commercial" <?php echo ($asset['department'] === "Commercial") ? 'selected' : ''; ?>>Commercial</option>
                                            <option value="Eng" <?php echo ($asset['department'] === "Eng") ? 'selected' : ''; ?>>Eng</option>
                                            <option value="Farm" <?php echo ($asset['department'] === "Farm") ? 'selected' : ''; ?>>Farm</option>
                                            <option value="Finance and Accounting" <?php echo ($asset['department'] === "Finance and Accounting") ? 'selected' : ''; ?>>Finance and Accounting</option>
                                            <option value="HR Admin" <?php echo ($asset['department'] === "HR Admin") ? 'selected' : ''; ?>>HR Admin</option>
                                            <option value="I.T" <?php echo ($asset['department'] === "I.T") ? 'selected' : ''; ?>>I.T</option>
                                            <option value="Ice cream" <?php echo ($asset['department'] === "Ice cream") ? 'selected' : ''; ?>>Ice cream</option>
                                            <option value="Planning and Logistic" <?php echo ($asset['department'] === "Planning and Logistic") ? 'selected' : ''; ?>>Planning and Logistic</option>
                                            <option value="Procurement" <?php echo ($asset['department'] === "Procurement") ? 'selected' : ''; ?>>Procurement</option>
                                            <option value="QA/R&D" <?php echo ($asset['department'] === "QA/R&D") ? 'selected' : ''; ?>>QA/R&D</option>
                                            <option value="Milking" <?php echo ($asset['department'] === "Milking") ? 'selected' : ''; ?>>Milking</option>
                                            <option value="Warehouse" <?php echo ($asset['department'] === "Warehouse") ? 'selected' : ''; ?>>Warehouse</option>
                                            <option value="Processing" <?php echo ($asset['department'] === "Processing") ? 'selected' : ''; ?>>Processing</option> 
                                            <option value="Others" <?php echo ($asset['department'] === "Others") ? 'selected' : ''; ?>>Others</option>
                                        </select>
                                    </span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="block clearfix">License
                                    <span class="block">
                                        <input class="form-control" 
                                            placeholder="Enter license" 
                                            id="license" 
                                            name="license" 
                                            value="<?php echo $asset['license']; ?>" />
                                    </span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="block clearfix">Purchase Date
                                    <span class="block">
                                        <input class="form-control date-picker" 
                                            id="purchase_date" 
                                            name="purchase_date" 
                                            type="text" 
                                            data-date-format="yyyy-mm-dd" 
                                            placeholder="Select purchase date" 
                                            />
                                    </span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="block clearfix">Purchase Price
                                    <span class="block">
                                        <input class="form-control" 
                                            placeholder="Enter purchase price" 
                                            id="purchase_price" 
                                            name="purchase_price" 
                                            value="<?php echo $asset['purchase_price']; ?>" />
                                    </span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="block clearfix">Order Number
                                    <span class="block">
                                        <input class="form-control" placeholder="Enter order number" id="order_number" name="order_number" value="<?php echo htmlspecialchars($asset['order_number']); ?>" />
                                    </span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="block clearfix">Warranty Start
                                    <span class="block">
                                        <input class="form-control date-picker" id="warranty_start" name="warranty_start" type="text" data-date-format="yyyy-mm-dd" placeholder="Select warranty start date"/>
                                    </span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="block clearfix">Warranty End
                                    <span class="block">    
                                        <input class="form-control date-picker" id="warranty_end" name="warranty_end" type="text" data-date-format="yyyy-mm-dd" placeholder="Select warranty end date"/>
                                    </span>
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label class="block clearfix">PMS Date
                                    <span class="block">    
                                        <input class="form-control date-picker" id="pms_date" name="pms_date" type="text" data-date-format="yyyy-mm-dd" placeholder="Select PMS date" value="<?php echo htmlspecialchars($asset['pms_date']); ?>"/>
                                    </span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="block clearfix">Condition Notes
                                    <textarea id="condition_notes" 
                                        name="condition_notes" 
                                        rows="5" 
                                        cols="80"
                                        placeholder="Enter condition notes"><?php echo $asset['condition_notes']; ?></textarea>
                                </label>
                            </div>                  
                        </div>
                    </div>    
                    <div class="clearfix">
                        <div id="flash5"></div>    
                        <div id="insert_search5"></div>    
                        <button type="button" class="width-25 pull-right btn btn-sm" id="resetAssetBtn" name="resetAssetBtn" data-dismiss="modal">
                            <i class="ace-icon fa fa-times"></i>
                            <span class="bigger-110">Cancel</span>
                        </button>

                        <button type="button" class="width-25 pull-right btn btn-sm btn-success" id="updateAssetBtn" name="updateAssetBtn">
                            <span class="bigger-110">Update</span>
                            <i class="ace-icon fa fa-check icon-on-right"></i>
                        </button>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>
</div>

<script src="../assets/customjs/projectmanagement.js"></script>
<!-- jQuery Tags Input -->
<script src="../assets/customjs/jquery.tagsinput.js"></script>
<script src="../assets/js/jquery-ui.custom.min.js"></script>
<script src="../assets/js/chosen.jquery.min.js"></script>

<script type="text/javascript">
    CKEDITOR.replace('condition_notes');
    $('.date-picker').datepicker({
        autoclose: true,
        todayHighlight: true,
        startDate: '2000-01-01',
        endDate: '+50y',
        format: 'yyyy-mm-dd',
        clearBtn: true,
        todayBtn: 'linked',
        orientation: 'bottom auto'
    });
    
    if(!ace.vars['touch']) {
        $('.chosen-select').each(function() {
            var $select = $(this);
            if ($select.find('option').length > 0) {
                $select.chosen({
                    allow_single_deselect: true,
                    search_contains: true,
                    no_results_text: 'No users found',
                    width: '100%',
                    placeholder_text_multiple: 'Select users...',
                    placeholder_text_single: 'Select a user...',
                    inherit_select_classes: true,
                    display_selected_options: true,
                    display_disabled_options: true,
                    enable_split_word_search: true,
                    group_search: true,
                    search_contains: true,
                    single_backstroke_delete: false,
                    max_selected_options: 10
                });
            }
        });
    }

    // Initialize form validation
    $('#assetInfoUpdate').bootstrapValidator({
        message: 'This value is not valid',
        live: 'enabled',
        submitButtons: 'button[name="updateAssetBtn"]',
        feedbackIcons: {
            required: 'glyphicon glyphicon-asterisk',
            valid: 'glyphicon glyphicon-ok',
            invalid: 'glyphicon glyphicon-remove'
        },
        fields: {
            // Add validation for accessories
            'accessories[]': {
                validators: {
                    callback: {
                        message: 'Please select valid accessories',
                        callback: function(value, validator, $field) {
                            // Allow empty selection
                            if (!value || value.length === 0) {
                                return true;
                            }
                            // Validate that all selected values are valid
                            return value.every(function(v) {
                                return v !== null && v !== undefined && v !== '';
                            });
                        }
                    }
                }
            },
            // Keep existing validators
            email: {
                validators: {
                    emailAddress: { 
                        message: 'Please enter a valid email address',
                        enabled: function($field) {
                            return $field.val() !== '';
                        }
                    }
                }
            },
            purchase_date: {
                validators: {
                    date: {
                        format: 'YYYY-MM-DD',
                        message: 'The date is not valid',
                        enabled: function($field) {
                            return $field.val() !== ''; // Only validate if not empty
                        }
                    }
                }
            },
            purchase_price: {
                validators: {
                    numeric: {
                        message: 'The value must be numeric',
                        enabled: function($field) {
                            return $field.val() !== ''; // Only validate if not empty
                        }
                    }
                }
            },
            due_date: {
                validators: {
                    date: {
                        format: 'YYYY-MM-DD',
                        message: 'The date is not valid',
                        enabled: function($field) {
                            return $field.val() !== ''; // Only validate if not empty
                        }
                    }
                }
            },
            warranty_start: {
                validators: {
                    date: {
                        format: 'YYYY-MM-DD',
                        message: 'The date is not valid',
                        enabled: function($field) {
                            return $field.val() !== ''; // Only validate if not empty
                        }
                    }
                }
            },
            warranty_end: {
                validators: {
                    date: {
                        format: 'YYYY-MM-DD',
                        message: 'The date is not valid',
                        enabled: function($field) {
                            return $field.val() !== ''; // Only validate if not empty
                        }
                    }
                }
            },
            // Bag checkbox - no validation needed, just ensure it's registered
            bag: {
                validators: {
                    callback: {
                        message: '',
                        callback: function(value, validator, $field) {
                            // Always return true - no validation needed for checkbox
                            return true;
                        }
                    }
                }
            }
        }
    });

    // Revalidate on change (exclude checkboxes)
    $('#assetInfoUpdate input:not([type="checkbox"]), #assetInfoUpdate select').on('change keyup', function() {
        var fieldName = $(this).attr('name');
        if (fieldName) {
            $('#assetInfoUpdate').bootstrapValidator('revalidateField', fieldName);
        }
    });
    $('.date-picker').on('changeDate', function() {
        $('#assetInfoUpdate').bootstrapValidator('revalidateField', $(this).attr('name'));
    });

    // Override update button click to validate before AJAX
    $('#updateAssetBtn').click(function(e) {
        var $btn = $(this);
        if ($btn.prop('disabled')) return;

        // Get form data
        var formData = new FormData($('#assetInfoUpdate')[0]);
        
        // Debug logging for accessories
        var accessories = $('#accessories').val() || [];
        console.log('Selected accessories before submission:', accessories);
        
        // Log the entire FormData for debugging
        for (var pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        // Skip current_owner - it's a disabled display field, the actual value is already in the database
        // Remove it from FormData if it exists (it shouldn't since it's disabled)
        formData.delete('current_owner');
        
        // Explicitly handle CKEditor content (FormData from element might not capture it correctly)
        var editorContent = CKEDITOR.instances.condition_notes.getData();
        formData.delete('condition_notes'); // Remove any existing entries
        formData.append('condition_notes', editorContent);

        $('#assetInfoUpdate').bootstrapValidator('validate');
        var bootstrapValidator = $('#assetInfoUpdate').data('bootstrapValidator');
        
        if (bootstrapValidator.isValid()) {
            $btn.prop('disabled', true);

            $.ajax({
                type: "POST",
                url: "updateAsset.php",
                data: formData,
                processData: false,
                contentType: false,
                cache: false,
                dataType: 'html',
                beforeSend: function() {
                    $("#flash5").show().html('<div class="alert alert-info">Updating asset information... Please wait.</div>');
                },
                success: function(response) {
                    console.log('Server response:', response);
                    $("#flash5").hide();
                    $("#insert_search5").show().html(response);
                    
                    if (response.includes('Asset has been updated successfully')) {
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $btn.prop('disabled', false);
                    }
                    $('.chosen-select').each(function() {
                        var $select = $(this);
                        if ($select.find('option').length > 0) {
                            $select.chosen('destroy').chosen({
                                allow_single_deselect: true,
                                search_contains: true,
                                no_results_text: 'No users found',
                                width: '100%',
                                placeholder_text_multiple: 'Select users...',
                                placeholder_text_single: 'Select a user...',
                                inherit_select_classes: true,
                                display_selected_options: true,
                                display_disabled_options: true,
                                enable_split_word_search: true,
                                group_search: true,
                                search_contains: true,
                                single_backstroke_delete: false,
                                max_selected_options: 10
                            });
                        }
                    });
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", error);
                    console.error("Status:", status);
                    console.error("Response:", xhr.responseText);
                    $("#flash5").html('<div class="alert alert-danger">Error updating asset information. Please try again.</div>');
                    $btn.prop('disabled', false);
                }
            });
        }
    });

    // Store original values when form loads
    $(document).ready(function() {
        $('#assetInfoUpdate').find('input, select, textarea').each(function() {
            var $field = $(this);
            var fieldName = $field.attr('name');
            
            // Skip hidden fields and buttons
            if (!$field.is(':visible') || $field.is('button')) return;
            
            // Store original value
            if (fieldName === 'condition_notes') {
                $field.data('original-value', CKEDITOR.instances.condition_notes.getData());
            } else if ($field.is('select[multiple]')) {
                // For multi-select, store a sorted string representation for comparison
                var originalValues = $field.val() || [];
                $field.data('original-value', originalValues.sort().join(','));
            } else {
                $field.data('original-value', $field.val());
            }
        });

        // Store original value for accessories
        var originalAccessories = $('#accessories').val() || [];
        $('#accessories').data('original-value', originalAccessories.sort().join(','));
    });

    // Add change event handler for accessories
    $('#accessories').on('change', function() {
        $('#assetInfoUpdate').bootstrapValidator('revalidateField', 'accessories[]');
    });
</script>