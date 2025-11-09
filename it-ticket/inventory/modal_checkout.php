<?php 
include('../check_session.php');
$moduleid=4;
$conn = connectionDB();

$asset_tag = $_GET['asset_tag'];

$myquery = "SELECT *,
    id,
    asset_id,
    astID,
    asset_tag,
    current_owner,
    inv_lastname,
    inv_firstname,
    previous_owner,
    license,
    email,
    assignee,
    status,
    statusid,
    department,
    company,
    position,
    asset_type,
    manufacturer,
    model,
    color,
    serial_number,
    sim_number,
    purchase_date,
    purchase_price,
    order_number,
    due_date,
    checkout_notes,
    checkout_date,
    exp_checkout_date,
    datetimecreated,
    createdbyid,
    warranty_start,
    warranty_end,
    emp_location
FROM inv_inventorylisttb
WHERE asset_tag = '$asset_tag'";

$myresult = mysqli_query($conn, $myquery);
$asset = mysqli_fetch_assoc($myresult);

if (empty($asset['asset_tag'])) $asset['asset_tag'] = "N/A";
if (empty($asset['current_owner'])) $asset['current_owner'] = "N/A";
if (empty($asset['inv_lastname'])) $asset['inv_lastname'] = "N/A";
if (empty($asset['inv_firstname'])) $asset['inv_firstname'] = "N/A";
if (empty($asset['previous_owner'])) $asset['previous_owner'] = "N/A";
if (empty($asset['license'])) $asset['license'] = "N/A";
if (empty($asset['email'])) $asset['email'] = "N/A";
if (empty($asset['assignee'])) $asset['assignee'] = "N/A";
if (empty($asset['status'])) $asset['status'] = "N/A";
if (empty($asset['statusid'])) $asset['statusid'] = 0;
if (empty($asset['department'])) $asset['department'] = "N/A";
if (empty($asset['company'])) $asset['company'] = "N/A";
if (empty($asset['position'])) $asset['position'] = "N/A";
if (empty($asset['asset_type'])) $asset['asset_type'] = "N/A";
if (empty($asset['manufacturer'])) $asset['manufacturer'] = "N/A";
if (empty($asset['model'])) $asset['model'] = "N/A";
if (empty($asset['color'])) $asset['color'] = "N/A";
if (empty($asset['serial_number'])) $asset['serial_number'] = "N/A";
if (empty($asset['sim_number'])) $asset['sim_number'] = "N/A";
if (empty($asset['purchase_date'])) $asset['purchase_date'] = "N/A";
if (empty($asset['purchase_price'])) $asset['purchase_price'] = "0.00";
if (empty($asset['order_number'])) $asset['order_number'] = "N/A";
if (empty($asset['due_date'])) $asset['due_date'] = "N/A";
if (empty($asset['checkout_notes'])) $asset['checkout_notes'] = "N/A";
if (empty($asset['warranty_start'])) $asset['warranty_start'] = "N/A";
if (empty($asset['warranty_end'])) $asset['warranty_end'] = "N/A";
if (empty($asset['emp_location'])) $asset['emp_location'] = "N/A";

$asset_tag = mysqli_real_escape_string($conn, $asset['asset_tag']);
$current_owner = mysqli_real_escape_string($conn, $asset['current_owner']);
$inv_lastname = mysqli_real_escape_string($conn, $asset['inv_lastname']);
$inv_firstname = mysqli_real_escape_string($conn, $asset['inv_firstname']);
$previous_owner = mysqli_real_escape_string($conn, $asset['previous_owner']);
$license = mysqli_real_escape_string($conn, $asset['license']);
$email = mysqli_real_escape_string($conn, $asset['email']);
$assignee = mysqli_real_escape_string($conn, $asset['assignee']);
$status = mysqli_real_escape_string($conn, $asset['status']);
$statusid = (int)$asset['statusid'];
$department = mysqli_real_escape_string($conn, $asset['department']);
$company = mysqli_real_escape_string($conn, $asset['company']);
?>

<link rel="stylesheet" href="../assets/css/bootstrap-multiselect.min.css" />

<style>
/* Make control dimensions uniform with .form-control */
.multiselect-native-select .btn-group{ width:100%; display:block; }
.multiselect-native-select .btn-group > .multiselect{ width:100%; }
.multiselect-container{ width:100%; max-width:none; }
.multiselect.btn{
    background:#fff !important;
    color:#333 !important;
    border:1px solid #ccc !important;
    box-shadow:none !important;
    text-align:left;
    width:100%;
    padding:6px 12px; /* match .form-control */
    padding-right:24px;
    line-height:1.42857143; /* match bootstrap inputs */
    min-height:34px; /* match default input height */
}
.multiselect.btn:focus,
.multiselect.btn:hover,
.multiselect.btn:active,
.open>.dropdown-toggle.multiselect.btn{
    background:#fff !important;
    color:#333 !important;
    border-color:#ccc !important;
    box-shadow:none !important;
}
.multiselect .caret{ display:none; }
.multiselect-container>li.active>a,
.multiselect-container>li.active>a:focus,
.multiselect-container>li.active>a:hover{ background-color:transparent; color:inherit; }
.multiselect-container>li>a:hover{ background-color:transparent; }
.multiselect-filter .input-group-addon,
.multiselect-clear-filter{ display:none !important; }
.multiselect-filter .multiselect-search{
    border:1px solid #ccc;
    box-shadow:none;
    height:34px; /* align with input height */
    padding:6px 12px;
}
.multiselect-container>li>a>label{
    padding:4px 8px;
    font-weight:normal;
}
/* Neutralize the clear button */
#clearCurrentOwnerBtn{
    background:#fff !important;
    color:#333 !important;
    border:1px solid #ccc !important;
    box-shadow:none !important;
    height:34px; /* align with input height */
    padding:6px 12px;
}

/* Make dropdown menu width match the trigger button */
.multiselect-native-select .btn-group .dropdown-menu{
    width:100%;
    max-width:none;
}
</style>

<div class="modal-body">
    <div id="signup-box" class="signup-box widget-box no-border">
        <div class="widget-body">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><small>×</small></button>
            <h4 class="header green lighter bigger">
                <i class="ace-icon fa fa-cubes blue"></i>
                Check Out User Information
            </h4>
            <p>Modify asset details below:</p>

            <form id="assetInfoUpdate" enctype="multipart/form-data">
                <input type="hidden" class="form-control" id="asset_tag" name="asset_tag" value="<?php echo htmlspecialchars($asset['asset_tag']); ?>"/>
                <input type="hidden" class="form-control" id="assetUserid" name="assetUserid" value="<?php echo $userid; ?>"/>
                <input type="hidden" class="form-control" id="asset_id" name="asset_id" value="<?php echo htmlspecialchars($asset['asset_id']); ?>"/>

                <fieldset>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="block clearfix">Asset Type
                                    <span class="block">
                                        <select class="form-control" id="asset_type" name="asset_type" data-placeholder="Select asset type" disabled>
                                            <option value=""><?php echo ($asset['asset_type'] === "N/A" || $asset['asset_type'] === '') ? "Select asset type" : htmlspecialchars($asset['asset_type']); ?></option>
                                            <option value="Computers" <?php echo ($asset['asset_type'] === "Computers") ? 'selected' : ''; ?>>Computers</option>
                                            <option value="Mobile Phones" <?php echo ($asset['asset_type'] === "Mobile Phones") ? 'selected' : ''; ?>>Mobile Phones</option>
                                            <option value="Tablets" <?php echo ($asset['asset_type'] === "Tablets") ? 'selected' : ''; ?>>Tablets</option>
                                            <option value="Printers" <?php echo ($asset['asset_type'] === "Printers") ? 'selected' : ''; ?>>Printers</option>
                                            <option value="Accessories" <?php echo ($asset['asset_type'] === "Accessories") ? 'selected' : ''; ?>>Accessories</option>
                                        </select>
                                    </span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="block clearfix">Company
                                    <span class="block">
                                        <select class="form-control" id="company" name="company" data-placeholder="Select company">
                                            <option value="" <?php echo ($asset['company'] === 'N/A' || $asset['company'] === '') ? 'selected' : ''; ?>>Select company</option>
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
                                <label class="block clearfix">Check Out To User: <span style="color: red;">*</span>
                                    <span class="block">
                                        <div class="clearfix" style="display:flex; gap:8px; align-items:flex-start;">
                                            <select multiple name="current_owner[]" id="current_owner" class="form-control" style="flex:1;" data-placeholder="Choose current owner...">
                                            <?php 
                                                    $queryUsers = "SELECT id, user_firstname, user_lastname FROM sys_usertb WHERE user_statusid = '1' ORDER BY user_firstname, user_lastname";
                                                $resultUsers = mysqli_query($conn, $queryUsers);
                                                
                                                $selectedOwners = [];
                                                if ($asset['current_owner'] !== "N/A" && !empty($asset['current_owner'])) {
                                                    $selectedOwners = explode(',', $asset['current_owner']);
                                                    $selectedOwners = array_filter($selectedOwners, function($value) {
                                                        return !empty(trim($value));
                                                    });
                                                }
                                                
                                                while($row = mysqli_fetch_assoc($resultUsers)){
                                                    $name = mb_convert_case($row['user_firstname'], MB_CASE_TITLE, "UTF-8") . " " . mb_convert_case($row['user_lastname'], MB_CASE_TITLE, "UTF-8");
                                                    $selected = in_array($row['id'], $selectedOwners) ? 'selected' : '';
                                                    echo "<option value=\"{$row['id']}\" $selected>$name</option>";
                                                }
                                            ?>
                                        </select>
                                            <button type="button" id="clearCurrentOwnerBtn" class="btn btn-default btn-sm" style="white-space:nowrap;">Remove</button>
                                        </div>
                                    </span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="block clearfix">Position
                                    <span class="block">
                                        <input class="form-control" id="position" name="position" type="text" placeholder="Enter position" value="<?php echo (isset($asset['position']) && $asset['position'] !== "N/A") ? htmlspecialchars($asset['position']) : ""; ?>"/>
                                    </span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="block clearfix">Location
                                    <span class="block">
                                    <select class="form-control" id="location" name="emp_location" data-placeholder="Select location">
                                        <option value="" <?php echo ($asset['emp_location'] === 'N/A' || $asset['emp_location'] === '') ? 'selected' : ''; ?>>Select location</option>
                                        <option value="BAY FARM" <?php echo ($asset['emp_location'] === 'BAY FARM') ? 'selected' : ''; ?>>BAY FARM</option>
                                        <option value="CB LTI" <?php echo ($asset['emp_location'] === 'CB LTI') ? 'selected' : ''; ?>>CB LTI</option>
                                        <option value="DC BULACAN" <?php echo ($asset['emp_location'] === 'DC BULACAN') ? 'selected' : ''; ?>>DC BULACAN</option>
                                        <option value="LASPINAS HUB" <?php echo ($asset['emp_location'] === 'LASPINAS HUB') ? 'selected' : ''; ?>>LASPINAS HUB</option>
                                        <option value="MPAV MAKATI" <?php echo ($asset['emp_location'] === 'MPAV MAKATI') ? 'selected' : ''; ?>>MPAV MAKATI</option>
                                        <option value="MAPLASAN H.O" <?php echo ($asset['emp_location'] === 'MAPLASAN H.O') ? 'selected' : ''; ?>>MAPLASAN H.O</option>
                                        <option value="MKT HUB AXELUM" <?php echo ($asset['emp_location'] === 'MKT HUB AXELUM') ? 'selected' : ''; ?>>MKT HUB AXELUM</option>
                                        <option value="MPFF BULACAN" <?php echo ($asset['emp_location'] === 'MPFF BULACAN') ? 'selected' : ''; ?>>MPFF BULACAN</option>
                                        <option value="QC HUB" <?php echo ($asset['emp_location'] === 'QC HUB') ? 'selected' : ''; ?>>QC HUB</option>
                                        <option value="ROCKWELL" <?php echo ($asset['emp_location'] === 'ROCKWELL') ? 'selected' : ''; ?>>ROCKWELL</option>
                                        <option value="SHANGRI-LA" <?php echo ($asset['emp_location'] === 'SHANGRI-LA') ? 'selected' : ''; ?>>SHANGRI-LA</option>
                                        <option value="SM MAKATI" <?php echo ($asset['emp_location'] === 'SM MAKATI') ? 'selected' : ''; ?>>SM MAKATI</option>
                                        <option value="SM NORTH EDSA" <?php echo ($asset['emp_location'] === 'SM NORTH EDSA') ? 'selected' : ''; ?>>SM NORTH EDSA</option>
                                        <option value="UHDFI CDO" <?php echo ($asset['emp_location'] === 'UHDFI CDO') ? 'selected' : ''; ?>>UHDFI CDO</option>
                                        <option value="UHDFI CEBU" <?php echo ($asset['emp_location'] === 'UHDFI CEBU') ? 'selected' : ''; ?>>UHDFI CEBU</option>
                                        <option value="UHDFI DAVAO" <?php echo ($asset['emp_location'] === 'UHDFI DAVAO') ? 'selected' : ''; ?>>UHDFI DAVAO</option>
                                        <option value="UHDFI MARAMAG" <?php echo ($asset['emp_location'] === 'UHDFI MARAMAG') ? 'selected' : ''; ?>>UHDFI MARAMAG</option>
                                        <option value="UHDFI GENSAN" <?php echo ($asset['emp_location'] === 'UHDFI GENSAN') ? 'selected' : ''; ?>>UHDFI GENSAN</option>
                                    </select>
                                    </span>
                                </label>
                            </div>
                          
                            <div class="form-group">
                                <label class="block clearfix">Status
                                    <span class="block">
                                        <select class="form-control" id="status" name="status" data-placeholder="Select status">
                                            <option value="In use" selected>In-use</option>
                                            <option value="Upcoming">Upcoming</option>
                                        </select>
                                    </span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="block clearfix">Department 
                                    <span class="block">
                                        <select class="form-control" id="department" name="department">
                                            <option value="" <?php echo ($asset['department'] === 'N/A' || $asset['department'] === '') ? 'selected' : ''; ?>>Select Department</option>
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
                                        </select>
                                    </span>
                                </label>
                            </div>
                            </div>

                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="block clearfix">Check Out Date
                                    <span class="block">
                                        <input class="form-control date-picker" id="checkout_date" name="checkout_date" type="text" data-date-format="yyyy-mm-dd" placeholder="Select checkout date" value="<?php echo (isset($asset['checkout_date']) && $asset['checkout_date'] !== "N/A") ? htmlspecialchars($asset['checkout_date']) : ""; ?>"/>
                                    </span>
                                </label>
                            </div>

                            <!-- <div class="form-group">
                                <label class="block clearfix">Expected Check Out Date
                                    <span class="block">
                                        <input class="form-control date-picker" id="exp_checkout_date" name="exp_checkout_date" type="text" data-date-format="yyyy-mm-dd" placeholder="Select expected checkout date" value="<?php echo (isset($asset['exp_checkout_date']) && $asset['exp_checkout_date'] !== "N/A") ? htmlspecialchars($asset['exp_checkout_date']) : ""; ?>"/>
                                    </span>
                                </label>
                            </div> -->
                                                                              
                            <div class="form-group">
                                <label class="block clearfix">Notes
                                    <textarea id="checkout_notes" name="checkout_notes" rows="5" class="form-control" placeholder="Enter checkout notes..."><?php echo $asset['checkout_notes'] === "N/A" ? "" : htmlspecialchars($asset['checkout_notes']); ?></textarea>
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

                        <button type="button" class="width-25 pull-right btn btn-sm btn-success" id="updateCheckoutBtn" name="updateCheckoutBtn">
                            <span class="bigger-110">Update</span>
                            <i class="ace-icon fa fa-check icon-on-right"></i>
                        </button>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>
</div>

<script src="../assets/js/jquery-ui.custom.min.js"></script>
<script src="../assets/js/bootstrap-multiselect.min.js"></script>

<script type="text/javascript">
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
    
    function coButtonTextList() {
    var $opts = $('#current_owner').find('option:selected');
    if ($opts.length === 0) return 'No User';
    if ($opts.length === 1) return $opts.first().text();
    // show up to 3 names, then "+N more"
    var names = $opts.map(function(){ return $(this).text(); }).get();
    if (names.length <= 3) return names.join(', ');
    return names.slice(0,3).join(', ') + ' +' + (names.length - 3) + ' more';
}

$(document).ready(function () {
        // Proactively disable any BootstrapValidator on this form to avoid runtime errors
        var $form = $('#assetInfoUpdate');
        try {
            if ($form.data('bootstrapValidator')) {
                $form.bootstrapValidator('destroy');
            }
        } catch (e) {}
        $form.removeData('bootstrapValidator');
        // Unbind validator namespaced events if attached
        $form.off('.bv');
        $form.find('input, select, textarea').off('.bv')
            .attr('data-bv-excluded', 'true');
        // Run a delayed cleanup in case other scripts attach later
        setTimeout(function(){
            try {
                if ($form.data('bootstrapValidator')) {
                    $form.bootstrapValidator('destroy');
                }
            } catch (e) {}
            $form.removeData('bootstrapValidator');
            $form.off('.bv');
            $form.find('input, select, textarea').off('.bv')
                .attr('data-bv-excluded', 'true');
        }, 300);

    setTimeout(function () {
        if ($('#current_owner').data('multiselect')) {
            $('#current_owner').multiselect('destroy');
        }
        var container = null;

        $('#current_owner').multiselect({
            enableFiltering: true,
            enableCaseInsensitiveFiltering: true,
            includeSelectAllOption: false,
            maxHeight: 260,
            numberDisplayed: 2,
            nonSelectedText: 'No User',
            nSelectedText: ' users selected',
            buttonClass: 'multiselect btn',
            buttonWidth: '100%',
            dropRight: false,
            buttonText: function () { return coButtonTextList(); },
            onInitialized: function (select, c) {
                container = c;
                var txt = coButtonTextList();
                // Use plugin's span holder when present
                var $btn = c.find('button.multiselect');
                var $span = $btn.find('span.multiselect-selected-text');
                if ($span.length) { $span.text(txt); } else { $btn.text(txt); }
                $btn.attr('title', txt);
            },
            onChange: function () {
                var txt = coButtonTextList();
                if (container) {
                    var $btn = container.find('button.multiselect');
                    var $span = $btn.find('span.multiselect-selected-text');
                    if ($span.length) { $span.text(txt); } else { $btn.text(txt); }
                    $btn.attr('title', txt);
                }
            },
            templates: {
                // keep default button; only simplify filter and items
                filter: '<div class="multiselect-filter"><input class="form-control multiselect-search" type="text" placeholder="Search" /></div>',
                li: '<li><a tabindex="0"><label></label></a></li>'
            }
        });

        // Clear button support (if you added it)
        $('#clearCurrentOwnerBtn').off('click').on('click', function(e){
            e.preventDefault();
            $('#current_owner').multiselect('deselectAll', false).multiselect('refresh');
            var txt = 'No User';
            if (container) {
                var $btn = container.find('button.multiselect');
                var $span = $btn.find('span.multiselect-selected-text');
                if ($span.length) { $span.text(txt); } else { $btn.text(txt); }
                $btn.attr('title', txt);
            }
        });
    }, 50);
});

    $('#updateCheckoutBtn').off('click').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        if ($btn.prop('disabled')) return;

        var currentOwner = $('#current_owner').val() || [];

        var formData = new FormData($('#assetInfoUpdate')[0]);

        formData.delete('current_owner[]');
        currentOwner.forEach(function(v){ formData.append('current_owner[]', v); });

            $btn.prop('disabled', true);
            
            $.ajax({
                type: "POST",
                url: "updateCheckout.php",
                data: formData,
                processData: false,
                contentType: false,
                cache: false,
                dataType: 'html',
                beforeSend: function() {
                $("#flash5").show().html('<div class="alert alert-info">Updating... Please wait.</div>');
                },
                success: function(response) {
                    $("#flash5").hide();
                    $("#insert_search5").show().html(response);
                    
                    if (response.includes('Asset has been updated successfully')) {
                    setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        $btn.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                $("#flash5").html('<div class="alert alert-danger">Error updating. Please try again.</div>');
                    $btn.prop('disabled', false);
            }
        });
    });
</script>

<style>
.multiselect.btn{
    background:#fff; color:#333; border:1px solid #ccc; box-shadow:none;
    text-align:left; width:100%; padding-right:24px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;  /* show selected names */
    height:34px; line-height:1.42857143; padding-left:12px; padding-right:24px;
}
</style>