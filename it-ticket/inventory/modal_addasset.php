<?php 
include('../check_session.php');
$moduleid=4;
$conn = connectionDB();
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
					New Asset Registration
				</h4>
				<p> Enter asset details to begin: </p>
				<form id="assetInfo" enctype="multipart/form-data">
					<input type="hidden" class="form-control" id="assetUserid" name="assetUserid" value="<?php echo $userid;?>"/>
					<fieldset>
						<div class="row">
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Asset Tag
										<span class="block input-icon input-icon-left">
											<!-- <i class="ace-icon fa fa-tag"></i> -->
											<input class="form-control" placeholder="Asset Tag" id="asset_tag" name="asset_tag" />
										</span>
									</label>
								</div>
								<!-- <div class="form-group">
									<label class="block clearfix">Assignee
										<span class="block input-icon input-icon-left">
											<select multiple="" name="assignee[]" id="assignee" class="chosen-select form-control" data-placeholder="Choose Assignee...">
												<?php 
													$query66 = "SELECT id,user_firstname,user_lastname FROM sys_usertb WHERE user_statusid = '1' AND user_groupid = '2' ";
													$result66 = mysqli_query($conn, $query66);
													while($row66 = mysqli_fetch_assoc($result66)){
														$user_firstname=mb_convert_case($row66['user_firstname'], MB_CASE_TITLE, "UTF-8");
														$user_lastname=mb_convert_case($row66['user_lastname'], MB_CASE_TITLE, "UTF-8");
														$engrid=$row66['id'];
														$name = $user_firstname." , ".$user_lastname;
												?>
												<option value="<?php echo $engrid;?>"><?php echo $name;?></option>
												<?php } ?>				
											</select>
										</span>
									</label>
								</div>
								<div class="form-group">
									<label class="block clearfix">Current Owner
										<span class="block input-icon input-icon-left">
											<select multiple="" name="current_owner[]" id="current_owner" class="chosen-select form-control" data-placeholder="Choose Current Owner">
												<?php 
													$query66 = "SELECT id,user_firstname,user_lastname FROM sys_usertb";
													$result66 = mysqli_query($conn, $query66);
													while($row66 = mysqli_fetch_assoc($result66)){
														$user_firstname=mb_convert_case($row66['user_firstname'], MB_CASE_TITLE, "UTF-8");
														$user_lastname=mb_convert_case($row66['user_lastname'], MB_CASE_TITLE, "UTF-8");
														$engrid=$row66['id'];
														$name = $user_firstname." , ".$user_lastname;
												?>
												<option value="<?php echo $engrid;?>"><?php echo $name;?></option>
												<?php } ?>				
											</select>
										</span>
									</label>
								</div>
                                <div class="form-group">
									<label class="block clearfix">Previous Owner
										<span class="block input-icon input-icon-left">
											<select multiple="" name="previous_owner[]" id="previous_owner" class="chosen-select form-control" data-placeholder="Choose Previous Owner">
												<?php 
													$query67 = "SELECT id,user_firstname,user_lastname FROM sys_usertb";
													$result67 = mysqli_query($conn, $query67);
													while($row67 = mysqli_fetch_assoc($result67)){
														$user_firstname=mb_convert_case($row67['user_firstname'], MB_CASE_TITLE, "UTF-8");
														$user_lastname=mb_convert_case($row67['user_lastname'], MB_CASE_TITLE, "UTF-8");
														$engrid=$row67['id'];
														$name = $user_firstname." , ".$user_lastname;
												?>
												<option value="<?php echo $engrid;?>"><?php echo $name;?></option>
												<?php } ?>				
											</select>
										</span>
									</label>
								</div> -->
                                
								<div class="form-group">
									<label class="block clearfix">Status
										<span class="block input-icon input-icon-left">
											<select class="form-control" id="status" name="status">
												<option value="Available" selected>Available</option>
												<option value="In repair">In-repair</option>
												<option value="In use">In-use</option>
												<option value="Lost">Lost</option>
												<option value="Reserved">Reserved</option>
												<option value="Retired">Retired</option>
												<option value="S.U Available">S.U Available</option>
												<option value="S.U Deployed">S.U Deployed</option>
												<option value="Upcoming">Upcoming</option>
											</select>
										</span>
									</label>
								</div>
								<div class="form-group">
									<label class="block clearfix">Department
										<span class="block input-icon input-icon-left">
											<!-- <i class="ace-icon fa fa-building"></i> -->
											<select class="form-control" id="department" name="department">
												<option value="">Select Department</option>
												<option value="Commercial">Commercial</option>
												<option value="Eng">Eng</option>
												<option value="Farm">Farm</option>
												<option value="Finance and Accounting">Finance and Accounting</option>
												<option value="HR Admin">HR Admin</option>
												<option value="I.T">I.T</option>
												<option value="Ice cream">Ice cream</option>
												<option value="Planning and Logistic">Planning and Logistic</option>
												<option value="Procurement">Procurement</option>
												<option value="QA/R&D">QA/R&D</option>
												<option value="Milking">Milking</option>
												<option value="Warehouse">Warehouse</option>
												<option value="Processing">Processing</option>
												<option value="Others">Others</option>
											</select>
										</span>
									</label>
								</div>
								<div class="form-group">
									<label class="block clearfix">Company
										<span class="block input-icon input-icon-left">
											<!-- <i class="ace-icon fa fa-building-o"></i> -->
											<select class="form-control" id="company" name="company">
												<option value="">Select Company</option>
												<?php 
													if ($userid == 271) {
														// For user ID 271, exclude specific companies
														$query66 = "SELECT id,clientname FROM sys_clienttb WHERE clientstatusid = '1' AND clientname NOT IN ('The Laguna Creamery Inc.', 'BMC', 'Metro Pacific Agro Ventures Inc.', 'Metro Pacific Fresh Farms Inc', 'Metro Pacific Dairy Farms, Inc.', 'Metro Pacific Nova Agro Tech, Inc.')";
													} elseif ($userid == 383) {
														// For user ID 383, only show Metro Pacific Fresh Farms Inc
														$query66 = "SELECT id,clientname FROM sys_clienttb WHERE clientstatusid = '1' AND clientname = 'Metro Pacific Fresh Farms Inc'";
													} else {
														// For all other users, show all companies
														$query66 = "SELECT id,clientname FROM sys_clienttb WHERE clientstatusid = '1'";
													}
													$result66 = mysqli_query($conn, $query66);
													while($row66 = mysqli_fetch_assoc($result66)){
														$clientname=mb_convert_case($row66['clientname'], MB_CASE_TITLE, "UTF-8");
														$clientid=$row66['id'];
												?>
												<option value="<?php echo $clientname;?>"><?php echo $clientname;?></option>
												<?php } ?>
											</select>
										</span>
									</label>
								</div>
								<div class="form-group">
									<label class="block clearfix">Asset Type
										<span class="block input-icon input-icon-left">
											<!-- <i class="ace-icon fa fa-laptop"></i> -->
											<select class="form-control" id="asset_type" name="asset_type">
												<option value="">Select Asset Type</option>
												<option value="Computers">PC Desktop</option>
												<option value="Computers">Laptop</option>
												<option value="Mobile Phones">Smartphone</option>
												<option value="Tablets">Tablets</option>
												<option value="Printers">Printers</option>										
												<option value="Accessories">Accessories</option>	
											</select>
										</span>
									</label>
								</div>
								<div class="form-group">
									<label class="block clearfix">Manufacturer
										<span class="block input-icon input-icon-left">
											<!-- <i class="ace-icon fa fa-industry"></i> -->
											<select class="form-control" id="manufacturer" name="manufacturer">
												<option value="">Select Manufacturer</option>
												<option value="A4 Tech">A4 Tech</option>
												<option value="ACER">ACER</option>
												<option value="Apple">Apple</option>
												<option value="ASUS">ASUS</option>
												<option value="Brother">Brother</option>
												<option value="DELL">DELL</option>
												<option value="EPSON">EPSON</option>
												<option value="HP">HP</option>
												<option value="LENOVO">LENOVO</option>
												<option value="PLDT/SMART">PLDT/SMART</option>
												<option value="SAMSUNG">SAMSUNG</option>
												<option value="Smart">Smart</option>
												<option value="Other">Other</option>
											</select>
										</span>
									</label>
								</div>
                                <div class="form-group">
									<label class="block clearfix">Model
										<span class="block input-icon input-icon-left">
											<!-- <i class="ace-icon fa fa-info-circle"></i> -->
											<textarea class="form-control" placeholder="Model" id="model" name="model" rows="3"></textarea>
										</span>
									</label>
								</div>
								<div class="form-group">
									<label class="block clearfix">Color
										<span class="block input-icon input-icon-left">
											<!-- <i class="ace-icon fa fa-paint-brush"></i> -->
											<input class="form-control" placeholder="Color" id="color" name="color" />
										</span>
									</label>
								</div>
								<div class="form-group">
									<label class="block clearfix">Chasi Serial Number
										<span class="block input-icon input-icon-left">
										
											<input class="form-control" placeholder="Serial Number" id="serial_number" name="serial_number" />
										</span>
									</label>
								</div>
								<!-- <div class="form-group">
									<label class="block clearfix">Chasis Number
										<span class="block input-icon input-icon-left">
											<i class="ace-icon fa fa-barcode"></i>
											<input class="form-control" placeholder="Chasis Number" id="chasis_number" name="chasis_number" />
										</span>
									</label>
								</div> -->
								<div class="form-group">
									<label class="block clearfix">Charger Serial Number
										<span class="block input-icon input-icon-left">
											<!-- <i class="ace-icon fa fa-barcode"></i> -->
											<input class="form-control" placeholder="Charger Number" id="charger_number" name="charger_number" />
										</span>
									</label>
								</div>
                                <div class="form-group">
									<label class="block clearfix">SIM Number
										<span class="block input-icon input-icon-left">
											<!-- <i class="ace-icon fa fa-phone"></i> -->
											<input class="form-control" placeholder="SIM Number" id="sim_number" name="sim_number" />
										</span>
									</label>
								</div>
								<div class="form-group">
									<label class="block clearfix">OS Version
										<span class="block input-icon input-icon-left">
											<select class="form-control" id="os_version" name="os_version">
												<option value="" disabled selected>Select OS Version</option>
												<option value="windows10_home">Windows 10 Home</option>
												<option value="windows10_pro">Windows 10 Pro</option>
												<option value="windows10_pro_workstations">Windows 10 Pro for Workstations</option>
												<option value="windows10_enterprise">Windows 10 Enterprise</option>
												<option value="windows10_education">Windows 10 Education</option>
												<option value="windows10_pro_education">Windows 10 Pro Education</option>
												<option value="windows10_s">Windows 10 S / S Mode</option>
												<option value="windows10_mobile">Windows 10 Mobile</option>
												<option value="windows10_mobile_enterprise">Windows 10 Mobile Enterprise</option>
												<option value="windows10_iot_core">Windows 10 IoT Core</option>
												<option value="windows10_iot_enterprise">Windows 10 IoT Enterprise</option>
												<option value="windows10_team">Windows 10 Team</option>
												<option value="windows10_ltsc">Windows 10 LTSC</option>
												<option value="windows11_home">Windows 11 Home</option>
												<option value="windows11_pro">Windows 11 Pro</option>
												<option value="windows11_pro_workstations">Windows 11 Pro for Workstations</option>
												<option value="windows11_enterprise">Windows 11 Enterprise</option>
												<option value="windows11_education">Windows 11 Education</option>
												<option value="windows11_pro_education">Windows 11 Pro Education</option>
												<option value="windows11_se">Windows 11 SE</option>
												<option value="windows11_iot_enterprise">Windows 11 IoT Enterprise</option>
												<option value="windows11_ltsc">Windows 11 LTSC (upcoming)</option>
											</select>
										</span>
									</label>
								</div>
								<div class="form-group">
									<label class="block clearfix">Antivirus Version
										<span class="block input-icon input-icon-left">
											<select class="form-control" id="antivirus_version" name="antivirus_version">
												<option value="" disabled selected>Select Antivirus Version</option>
												<option value="sophos">Sophos Endpoint</option>
												<option value="escan">eScan</option>											
												<option value="others">Others</option>
											</select>
										</span>
									</label>
								</div>
								<div class="form-group">
									<label class="block clearfix">Anydesk Address
										<span class="block input-icon input-icon-left">
											<input class="form-control" placeholder="Anydesk Address" id="anydesk_address" name="anydesk_address" />
										</span>
									</label>
								</div>
								




							</div>	
							<div class="col-lg-6">
                                
                                
                                
                                <div class="form-group">
									<label class="block clearfix">Purchase Price
										<span class="block input-icon input-icon-left">
											<!-- <i class="ace-icon fa fa-money"></i> -->
											<input class="form-control" placeholder="Purchase Price" id="purchase_price" name="purchase_price" />
										</span>
									</label>
								</div>
                                <div class="form-group">
									<label class="block clearfix">PO Number
										<span class="block input-icon input-icon-left">
											<!-- <i class="ace-icon fa fa-shopping-cart"></i> -->
											<input class="form-control" placeholder="Order Number" id="order_number" name="order_number" />
										</span>
									</label>
								</div>
                                <!-- <div class="form-group">
									<label class="block clearfix">Due Date
										<span class="block input-icon input-icon-left">
										<i class="ace-icon fa fa-calendar"></i>
											<input class="form-control date-picker" id="due_date" name="due_date" type="text" data-date-format="yyyy-mm-dd" placeholder="yyyy-mm-dd"/>
										</span>
									</label>
								</div> -->
								<div class="form-group">
									<label class="block clearfix">Received Date
										<span class="block input-icon input-icon-left">
										<!-- <i class="ace-icon fa fa-calendar"></i> -->
											<input class="form-control date-picker" id="purchase_date" name="purchase_date" type="text" data-date-format="yyyy-mm-dd" placeholder="yyyy-mm-dd"/>
										</span>
									</label>
								</div>
								<div class="form-group">
									<label class="block clearfix">Warranty Start Date
										<span class="block input-icon input-icon-left">
										<!-- <i class="ace-icon fa fa-calendar"></i> -->
											<input class="form-control date-picker" id="warranty_start" name="warranty_start" type="text" data-date-format="yyyy-mm-dd" placeholder="yyyy-mm-dd"/>
										</span>
									</label>
								</div>
								<div class="form-group">
									<label class="block clearfix">Warranty End Date
										<span class="block input-icon input-icon-left">
										<!-- <i class="ace-icon fa fa-calendar"></i> -->
											<input class="form-control date-picker" id="warranty_end" name="warranty_end" type="text" data-date-format="yyyy-mm-dd" placeholder="yyyy-mm-dd"/>
										</span>
									</label>
								</div>
                                <div class="form-group">
									<label class="block clearfix"> Condition Notes
										<textarea id="condition_notes" name="condition_notes" rows="5" cols="80"></textarea>
									</label>
								</div>

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
							<div id="insert_search5"></div>	
							<button type="button" class="width-25 pull-right btn btn-sm" id="resetAssetBtn" name="resetAssetBtn" data-dismiss="modal">
								<i class="ace-icon fa fa-refresh"></i>
								<span class="bigger-110">Close</span>
							</button>

							<button type="button" class="width-25 pull-right btn btn-sm btn-success" id="submitAssetBtn" name="submitAssetBtn">
								<span class="bigger-110">Register</span>
								<i class="ace-icon fa fa-arrow-right icon-on-right"></i>
							</button>
						</div>
					</fieldset>
				</form>
		</div>
	</div>
</div>
<script src="../assets/customjs/inventory.js"></script>
<!-- jQuery Tags Input -->
<script src="../assets/customjs/jquery.tagsinput.js"></script>
<script src="../assets/js/jquery-ui.custom.min.js"></script>
<script src="../assets/js/chosen.jquery.min.js"></script>


<script type="text/javascript">
	CKEDITOR.replace('condition_notes');
	$('.date-picker').datepicker({
		autoclose: true,
		todayHighlight: true
	})
	
	if(!ace.vars['touch']) {
		$('.chosen-select').chosen({allow_single_deselect:true}); 
	}

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


