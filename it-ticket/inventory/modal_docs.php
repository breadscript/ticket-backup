<?php 
include('../check_session.php');
$moduleid=4;
$conn = connectionDB();

// Get asset_id from GET param
$asset_id = isset($_GET['asset_id']) ? mysqli_real_escape_string($conn, $_GET['asset_id']) : '';
$asset_data = [];
if ($asset_id !== '') {
    $query = "SELECT checkout_date, current_owner, department, model, serial_number, charger_number, condition_notes, manufacturer, bag, position, asset_type FROM inv_inventorylisttb WHERE asset_id = '$asset_id' LIMIT 1";
    $result = mysqli_query($conn, $query);
    if ($result) {
        $asset_data = mysqli_fetch_assoc($result) ?: [];
    }
}
// Get current logged user full name from database using userid
$logged_user_fullname = '';
if (isset($userid) && !empty($userid)) {
    $user_query = "SELECT user_firstname, user_lastname FROM sys_usertb WHERE id = '" . mysqli_real_escape_string($conn, $userid) . "' LIMIT 1";
    $user_result = mysqli_query($conn, $user_query);
    if ($user_result && $user_row = mysqli_fetch_assoc($user_result)) {
        $logged_user_fullname = trim($user_row['user_firstname'] . ' ' . $user_row['user_lastname']);
    }
}
// Fallback to session variables if database query fails
if (empty($logged_user_fullname)) {
    if (isset($_SESSION['userfirstname']) && isset($_SESSION['userlastname'])) {
        $logged_user_fullname = trim($_SESSION['userfirstname'] . ' ' . $_SESSION['userlastname']);
    } elseif (isset($_SESSION['user_firstname']) && isset($_SESSION['user_lastname'])) {
        $logged_user_fullname = trim($_SESSION['user_firstname'] . ' ' . $_SESSION['user_lastname']);
    }
}

// Convert asset owner id to a name if needed (current_owner is usually an id)
$owner_name = '';
if (!empty($asset_data['current_owner'])) {
    $oid = intval($asset_data['current_owner']);
    $q = mysqli_query($conn, "SELECT CONCAT(user_firstname, ' ', user_lastname) as name FROM sys_usertb WHERE id = $oid LIMIT 1");
    if ($row = mysqli_fetch_assoc($q)) { $owner_name = $row['name']; }
}
if ($owner_name === '' && !empty($asset_data['current_owner'])) $owner_name = $asset_data['current_owner'];

// Add current_owner_name in all cases
$asset_data['current_owner_name'] = $owner_name;
$asset_data['logged_user_fullname'] = $logged_user_fullname;
?>
<script>
window.assetFormData = <?php echo json_encode($asset_data, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
</script>
<!-- jQuery Tags Input -->
<link href="../assets/customjs/jquery.tagsinput.css" rel="stylesheet">
<!-- page specific plugin styles -->
        <link rel="stylesheet" href="../assets/css/chosen.min.css" />
<div class="modal-body">
    <div id="signup-box" class="signup-box widget-box no-border">
        <div class="widget-body">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><small>×</small></button>
                <h4 class="header green lighter bigger">
                    <i class="ace-icon fa fa-paperclip blue"></i>
                    Attach Files
                </h4>
                <p> Select files to attach. </p>
                <form id="docsForm" enctype="multipart/form-data">
                    <input type="hidden" class="form-control" id="assetUserid" name="assetUserid" value="<?php echo $userid;?>"/>
                    <input type="hidden" class="form-control" id="asset_id" name="asset_id" value="<?php echo isset($_GET['asset_id']) ? htmlspecialchars($_GET['asset_id'], ENT_QUOTES, 'UTF-8') : '';?>"/>
                    <fieldset>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label class="block clearfix">Attach Files</label>
                                    <div class="file-upload-wrapper">
                                        <div class="custom-file-input">
                                            <input type="file" class="form-control" id="attachFile" name="attachFile[]" multiple 
                                                accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt" style="display: none;">
                                            <button class="btn btn-primary btn-sm upload-btn" type="button" onclick="document.getElementById('attachFile').click();">
                                                <i class="fa fa-paperclip"></i> Add Files
                                            </button>
                                            <button class="btn btn-default btn-sm float-right" type="button" id="clearFiles">
                                                <i class="fa fa-times"></i> Clear
                                            </button>
                                        </div>
                                        <div id="fileNameDisplay" class="file-list">
                                            <div class="text-muted">No files selected</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>  
                        <div class="clearfix">
                            <div id="flash5"></div> 
                            <button type="button" class="width-25 pull-right btn btn-sm btn-warning" id="extractAccountabilityFormBtn" name="extractAccountabilityFormBtn" style="margin-right:8px;">
                                <i class="ace-icon fa fa-file-pdf-o"></i>
                                <span class="bigger-110">Extract AF</span>
                            </button>
                            <div id="insert_search5"></div> 
                            <!-- <button type="button" class="width-25 pull-right btn btn-sm" id="resetAssetBtn" name="resetAssetBtn" data-dismiss="modal">
                                <i class="ace-icon fa fa-refresh"></i>
                                <span class="bigger-110">Close</span>
                            </button> -->
                        

                        <button type="button" class="width-25 pull-right btn btn-sm btn-success" id="submitAssetBtn" name="submitAssetBtn">
                                <span class="bigger-110">Upload</span>
                                <i class="ace-icon fa fa-arrow-right icon-on-right"></i>
                            </button>
                        </div>
                    </fieldset>
                </form>
        </div>
    </div>
</div>
<!-- inventory.js removed to avoid binding asset validators/CKEditor on this modal -->
<!-- jQuery Tags Input -->
<script src="../assets/customjs/jquery.tagsinput.js"></script>
<script src="../assets/js/jquery-ui.custom.min.js"></script>
<script src="../assets/js/chosen.jquery.min.js"></script>


<script type="text/javascript">
    $('#submitAssetBtn').on('click', function() {
        var $btn = $(this);
        if ($btn.prop('disabled')) { return; }

        var assetId = $('#asset_id').val();
        var userId = $('#assetUserid').val();
        var files = $('#attachFile')[0].files;

        if (!assetId) {
            $("#insert_search5").show().html('<div class="alert alert-warning">Missing asset ID.</div>');
            return;
        }
        if (!files || files.length === 0) {
            $("#insert_search5").show().html('<div class="alert alert-warning">Please select at least one file.</div>');
            return;
        }

        var formData = new FormData();
        formData.append('asset_id', assetId);
        formData.append('createdbyid', userId);
        for (var i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }

        $btn.prop('disabled', true);
        $("#flash5").show().html('<div class="alert alert-info">Uploading... Please wait.</div>');

        $.ajax({
            type: 'POST',
            url: 'upload_asset_files.php',
            data: formData,
            processData: false,
            contentType: false,
            cache: false,
            dataType: 'json',
            success: function(resp) {
                if (resp && resp.success) {
                    $("#flash5").html('<div class="alert alert-success">Files uploaded successfully.</div>');
                    setTimeout(function(){ $('.modal').modal('hide'); window.location.reload(); }, 1000);
                } else {
                    var msg = (resp && resp.message) ? resp.message : 'Upload failed.';
                    $("#insert_search5").show().html('<div class="alert alert-danger">' + msg + '</div>');
                    $btn.prop('disabled', false);
                }
            },
            error: function(xhr) {
                var txt = xhr && xhr.responseText ? xhr.responseText : 'Unexpected error';
                $("#insert_search5").show().html('<div class="alert alert-danger">' + txt + '</div>');
                $btn.prop('disabled', false);
            }
        });
    });

    $('#attachFile').on('change', function() {
        const files = this.files;
        const fileDisplay = $('#fileNameDisplay');
        
        if (files.length === 0) {
            fileDisplay.html('<div class="text-muted">No files selected</div>');
            return;
        }
        
        let fileList = '';
        for (let i = 0; i < files.length; i++) {
            fileList += `<div class="file-row" data-index="${i}">
                <i class="fa fa-file-o"></i> ${files[i].name}
                <button type="button" class="btn btn-xs btn-link remove-file" style="color: red; padding: 0 4px;">
                    <i class="fa fa-times"></i>
                </button>
            </div>`;
        }
        
        fileDisplay.html(fileList);
    });

    $('#fileNameDisplay').on('click', '.remove-file', function(e) {
        e.preventDefault();
        const fileIndex = $(this).parent().data('index');
        const input = $('#attachFile')[0];
        
        const dt = new DataTransfer();
        const { files } = input;
        
        for (let i = 0; i < files.length; i++) {
            if (i !== fileIndex) {
                dt.items.add(files[i]);
            }
        }
        
        input.files = dt.files;
        $('#attachFile').trigger('change');
    });

    $('#clearFiles').on('click', function() {
        $('#attachFile').val('');
        $('#fileNameDisplay').html('<div class="text-muted">No files selected</div>');
    });

    // Accountability Form: open printable window to save as PDF
    function generateAccountabilityFormHTML(data, baseUrl) {
        data = data || window.assetFormData || {};
        baseUrl = baseUrl || window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/');
        function safe(v) { return (v!==null&&v!==undefined) ? v : ''; }
        var dateIssued = safe(data.checkout_date);
        var empName = safe(data.current_owner_name);
        var position = safe(data.position);
        var dept = safe(data.department);
        var cond = safe(data.condition_notes);
        // Table rows - dynamic based on asset_type
        var rows = '';
        var at = (safe(data.asset_type) + '').toUpperCase();
        var mainLabel = 'ASSET';
        if (at.indexOf('LAPTOP') > -1) mainLabel = 'LAPTOP';
        else if (at.indexOf('DESKTOP') > -1 || at.indexOf('PC') > -1) mainLabel = 'DESKTOP';
        else if (at.indexOf('SMARTPHONE') > -1 || at.indexOf('MOBILE') > -1 || at.indexOf('PHONE') > -1) mainLabel = 'SMARTPHONE';
        else if (at.indexOf('TABLET') > -1) mainLabel = 'TABLET';
        else if (at.indexOf('PRINTER') > -1) mainLabel = 'PRINTER';
        else if (at.indexOf('ACCESSOR') > -1) mainLabel = 'ACCESSORY';

        // Main item row
        if (safe(data.serial_number) || safe(data.model)) {
            rows += `<tr><td>1</td><td>${mainLabel}</td><td>${safe(data.model)}</td><td>${safe(data.serial_number)}</td><td>${cond}</td></tr>`;
        }
        // Optional charger row
        if (safe(data.charger_number)) {
            rows += `<tr><td>1</td><td>CHARGER</td><td>${safe(data.manufacturer)}</td><td>${safe(data.charger_number)}</td><td>${cond}</td></tr>`;
        }
        // Optional bag row (mainly for laptops)
        if (safe(data.bag) == '1' || safe(data.bag) == 1) {
            rows += `<tr><td>1</td><td>BAG</td><td>${safe(data.manufacturer)}</td><td>N/A</td><td>${cond}</td></tr>`;
        }
        // If no data rows, add one empty row to prevent empty table
        if (!rows) {
            rows = '<tr><td></td><td></td><td></td><td></td><td></td></tr>';
        }
        // Conformed and released by
        var conformed = empName;
        var released = safe(data.logged_user_fullname);
        return `
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>IT Accountability Form</title>
    <style>
        @page { size: A4; margin: 16mm 14mm; }
        body { font-family: Calibri, Arial, Helvetica, sans-serif; color: #000; }
        .header { display:flex; justify-content:space-between; align-items:flex-start; }
        .brand { display:flex; align-items:center; }
        .logo { width:62px; height:62px; border:1px solid #cfcfcf; border-radius:50%; display:flex; align-items:center; justify-content:center; margin-right:10px; overflow:hidden; }
        .logo img { width:100%; height:100%; object-fit:contain; }
        .wordmark { display:flex; align-items:center; height:62px; }
        .wordmark img { max-height:100%; max-width:300px; height:auto; }
        .addr { text-align:right; font-size:11px; line-height:1.35; }
        .doc-title { text-align:center; font-weight:700; margin:10px 0 8px; }
        .meta { width:100%; margin-top:8px; font-size:11.5px; border-collapse:collapse; }
        .meta td { padding:2px 6px; vertical-align:bottom; }
        .meta .label { font-weight:700; font-size:10.5px; color:#000; }
        .line { display:inline-block; min-width:140px; border-bottom:1px solid #000; height:14px; vertical-align:bottom; }
        .meta .smallline { min-width:120px; }
        .table { width:100%; border-collapse:collapse; font-size:12px; margin-top:10px; }
        .table th, .table td { border:1px solid #000; padding:6px; height:24px; }
        .table th { text-transform:uppercase; font-weight:700; font-size:11px; background:#f2f2f2; }
        .terms { margin-top:12px; font-size:11.5px; }
        .terms .heading { font-weight:700; margin-bottom:6px; }
        .terms ol { margin:0 0 0 18px; padding:0; }
        .softwrap { margin-top:12px; }
        .softwrap .heading { font-weight:700; font-size:11.5px; margin-bottom:8px; }
        .checks { display:grid; grid-template-columns:repeat(3, 1fr); gap:8px 18px; font-size:11.5px; }
        .footer { display:flex; justify-content:space-between; margin-top:22px; font-size:11.5px; align-items:flex-end; }
        .sigbox { width:48%; }
        .siglabel { font-weight:700; margin-bottom:6px; }
        .sigline { border-top:1px solid #000; height:0; margin-top:24px; }
        .sigcap { text-align:center; margin-top:6px; font-size:11px; }
        .approved { text-align:center; margin-top:6px; font-size:11px; }
        .no-print { margin-top:18px; text-align:center; }
        @media print { .no-print { display:none; } }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">
            <div class="logo"><img src="${baseUrl}imgs/tlcilogo.png" alt="Logo" /></div>
            <div class="wordmark"><img src="${baseUrl}imgs/tlciwordlogo-removebg-preview.png" alt="Company Name" /></div>
        </div>
        <div class="addr">
            Lot 11 Block 1, Westside Villagefront<br/>
            Brentville, Brgy. Mamplasan<br/>
            Biñan, Laguna, Philippines<br/>
            Tel. no: (049) 559-8070
        </div>
    </div>

    <div class="doc-title">IT ACCOUNTABILITY FORM</div>

    <table class="meta">
        <tr>
            <td class="label">DATE ISSUED:</td>
            <td><span class="line smallline">${dateIssued}</span></td>
            <td class="label">POSITION:</td>
            <td><span class="line smallline">${position}</span></td>
        </tr>
        <tr>
            <td class="label">COMPLETE NAME:</td>
            <td><span class="line">${empName}</span></td>
            <td class="label">DEPARTMENT:</td>
            <td><span class="line">${dept}</span></td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th style="width:12%">QUANTITY</th>
                <th style="width:28%">PARTICULARS</th>
                <th style="width:22%">BRAND/MODEL</th>
                <th style="width:20%">Serial Number</th>
                <th style="width:18%">CONDITION/NOTE</th>
            </tr>
        </thead>
        <tbody>
            ${rows}
        </tbody>
    </table>

    <div class="terms">
        <div class="heading">Terms and Condition:</div>
        <ol>
            <li>That I hereby acknowledge that the above-mentioned equipment/s is/are company's properties lend to me by the management and is/are received in good condition;</li>
            <li>That I shall be responsible for loss/or damage on the equipment while in my custody and agree to pay full or its depreciated value in case of damage/loss or if it was stolen from my possession;</li>
            <li>The company expected me to return the above equipment/s in good working condition upon my separation or cessation of my service or tenure or for whatever reason;</li>
            <li>That I am aware that the company reserve the right to execute any legal means in case of my failure to comply with the above terms.</li>
        </ol>
    </div>

    <div class="softwrap">
        <div class="heading">Software & Application Installed / Configured</div>
        <div class="checks">
            <div><input type="checkbox"> Acrobat Reader</div>
            <div><input type="checkbox"> Centralized Printer</div>
            <div><input type="checkbox"> One drive</div>
            <div><input type="checkbox" checked> Viber</div>
            <div><input type="checkbox"> Antivirus</div>
            <div><input type="checkbox"> Google Drive</div>
            <div><input type="checkbox" checked> AnyDesk</div>
            <div><input type="checkbox" checked> S.A.P</div>
            <div><input type="checkbox" checked> WINRAR</div>
            <div><input type="checkbox" checked> Belarc</div>
            <div><input type="checkbox" checked> M.Office H.B / 365</div>
            <div><input type="checkbox" checked> Telegram</div>
            <div><input type="checkbox" checked> M.S Teams</div>
            <div><input type="checkbox"> V.P.N</div>
        </div>
    </div>

    <div class="footer">
        <div class="sigbox">
            <div class="siglabel">Conformed:</div>
            <div class="sigcap">${conformed}</div>
            <div class="sigline"></div>
            <div class="sigcap">Name and Signature of Recipient</div>
        </div>
        <div class="sigbox">
            <div class="siglabel">Checked and Released By:</div>
            <div class="approved">${released}</div>
            <div class="sigline"></div>
            <div class="sigcap">Name and Signature</div>
        </div>
    </div>

    <div class="no-print">
        <button onclick="window.print()">Print / Save as PDF</button>
        <button onclick="window.close()">Close</button>
    </div>
</body>
</html>`;
    }

    $('#extractAccountabilityFormBtn').on('click', function(){
        var d = window.assetFormData || {};
        var baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/');
        var w = window.open('', '_blank', 'width=900,height=700');
        w.document.write(generateAccountabilityFormHTML(d, baseUrl));
        w.document.close();
        setTimeout(function(){ try { w.focus(); } catch(e) {} }, 200);
    });
</script>

<style>
.file-upload-wrapper {
    border: 2px dashed #ddd;
    padding: 15px;
    border-radius: 6px;
    background: #f9f9f9;
}

.custom-file-input {
    margin-bottom: 10px;
}

.file-list {
    margin-top: 10px;
    font-size: 13px;
    color: #666;
    max-height: 150px;
    overflow-y: auto;
}

.file-row {
    display: flex;
    align-items: center;
    padding: 4px 8px;
    background: white;
    border: 1px solid #eee;
    margin-bottom: 4px;
    border-radius: 4px;
}

.file-row i.fa-file-o {
    margin-right: 8px;
    color: #666;
}

.file-row .remove-file {
    margin-left: auto;
    color: #dc3545;
    border: none;
    background: none;
    padding: 0 4px;
}

.file-row .remove-file:hover {
    color: #bd2130;
}

.upload-btn {
    margin-right: 8px;
}

#clearFiles {
    float: right;
}
</style>


