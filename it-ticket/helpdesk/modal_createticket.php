<?php 
include('../check_session.php');
$moduleid=1;
$conn = connectionDB();
// echo "usergroupid:".$usergroupid;
$rest = "";
//if($usergroupid == 4){
//	$query = "SELECT companyid FROM sys_usertb WHERE id = '$userid'";
//	$result = mysqli_query($conn, $query);
//	while($row = mysqli_fetch_assoc($result)){
//		$companyid=$row['companyid'];
//	}	
//	$rest.="WHERE clientid = '$companyid' AND statusid IN ('1','2','3','4','5','6')";
//}else{
$rest = "";
//}

?>
<!-- jQuery Tags Input -->
<link href="../assets/customjs/jquery.tagsinput.css" rel="stylesheet">
<!-- page specific plugin styles -->
		<link rel="stylesheet" href="../assets/css/chosen.min.css" />
<div class="modal-body">
	<!--<div id="signup-box" class="signup-box widget-box no-border">-->
	<div id="signup-box" class="signup-box widget-box no-border">
		<div class="widget-body">
			<!--<div class="widget-main">-->
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true"><small>×</small></button>
				<h4 class="header green lighter bigger">
					<i class="ace-icon fa fa-users blue"></i>
					File a Ticket
				</h4>
				<!-- <p> Enter your ticket details to begin: </p> -->
				<form id="ticketInfo">
					<input type="hidden" class="form-control" id="ticketUserid" name="ticketUserid" value="<?php echo $userid;?>"/>
					<fieldset>
						<div class="row">
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Project Name
										<span class="block input-icon input-icon-left">
											<select class="form-control" id="ticketProjectOwner" name="ticketProjectOwner">
												<option value="">Select Owner</option>
												<?php 
													$query66 = "SELECT id,projectname FROM sys_projecttb $rest";
													$result66 = mysqli_query($conn, $query66);
													while($row66 = mysqli_fetch_assoc($result66)){
														$projectname=mb_convert_case($row66['projectname'], MB_CASE_TITLE, "UTF-8");
														$projectid=$row66['id'];
												?>
												<option value="<?php echo $projectid;?>"><?php echo $projectname;?></option>
												<?php } ?>
											</select>
										</span>
									</label>
								</div>	
							</div>	
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Classification
										<span class="block input-icon input-icon-left">
											<select class="form-control" id="ticketClassification" name="ticketClassification">
												<option value="">Select Classification</option>
												<?php 
													$query66 = "SELECT id,classification FROM sys_taskclassificationtb";
													$result66 = mysqli_query($conn, $query66);
													while($row66 = mysqli_fetch_assoc($result66)){
														$classification=mb_convert_case($row66['classification'], MB_CASE_TITLE, "UTF-8");
														$classificationid=$row66['id'];
												?>
												<option value="<?php echo $classificationid;?>"><?php echo $classification;?></option>
												<?php } ?>
											</select>
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Priority Level
										<span class="block input-icon input-icon-left">
											<select class="form-control" id="ticketPriority" name="ticketPriority">
												<option value="">Select Priority</option>
												<?php 
													$query66 = "SELECT id,priorityname FROM sys_priorityleveltb";
													$result66 = mysqli_query($conn, $query66);
													while($row66 = mysqli_fetch_assoc($result66)){
														$priorityname=mb_convert_case($row66['priorityname'], MB_CASE_TITLE, "UTF-8");
														$prioritynameid=$row66['id'];
												?>
												<option value="<?php echo $prioritynameid;?>"><?php echo $priorityname;?></option>
												<?php } ?>
											</select>
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Subject
										<span class="block input-icon input-icon-left">
											<input class="form-control" placeholder="Enter Subject" id="ticketSubject" name="ticketSubject" />
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Target Date
										<span class="block input-icon input-icon-left">
										<i class="ace-icon fa fa-calendar"></i>
											<input class="form-control date-picker" id="ticketTargetDate" name="ticketTargetDate" type="text" data-date-format="yyyy-mm-dd" placeholder="yyyy-mm-dd"/>
										</span>
									</label>
								</div>
							</div>
							
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Attach Files</label>
									<div class="file-upload-wrapper">
										<div class="custom-file-input">
											<input type="file" class="form-control" id="attachFile" name="attachFile[]" multiple 
												accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt" style="display: none;">
											<button class="btn btn-link btn-sm upload-btn" type="button" onclick="document.getElementById('attachFile').click();" title="Add files">
												<i class="fa fa-upload"></i>
											</button>
											<button class="btn btn-link btn-sm clear-btn" type="button" id="clearFiles" title="Clear all files">
												<i class="fa fa-times"></i>
											</button>
										</div>
										<div id="fileNameDisplay" class="file-list">
											<div class="text-muted">No files selected</div>
										</div>
									</div>
								</div>
							</div>
							<div class="col-lg-12">
								<div class="form-group">
									<label class="block clearfix">Description
										<textarea id="description" name="description" rows="5" cols="80"></textarea>
									</label>
								</div>	
							</div>
						</div>	
						<div class="clearfix">
							<div id="flash5"></div>	
							<div id="insert_search5"></div>	
							<button type="button" class="pull-right btn btn-sm btn-danger" id="resetTicketBtn" name="resetTicketBtn" data-dismiss="modal" style="width: 80px;">
								<i class="ace-icon fa fa-trash"></i>
								<span class="bigger-110"></span>
							</button>

							<button type="button" class="width-25 pull-right btn btn-sm btn-primary" id="submitTicketBtn" name="submitTicketBtn">
								<span class="bigger-110">Submit</span>
							</button>
						</div>
					</fieldset>
				</form>
			<!--</div>-->
		</div>
	</div>
</div>
<script src="../assets/customjs/ticketing.js"></script>
<!-- jQuery Tags Input -->
<script src="../assets/customjs/jquery.tagsinput.js"></script>
<script src="../assets/js/jquery-ui.custom.min.js"></script>
<script src="../assets/js/chosen.jquery.min.js"></script>


<script type="text/javascript">
	CKEDITOR.replace('description');
	$('.date-picker').datepicker({
		autoclose: true,
		todayHighlight: true
	});

	// Set current date as default value for the date-picker
	$(document).ready(function() {
		var today = new Date().toISOString().split('T')[0];
		$('#ticketTargetDate').val(today);
	});
</script>

<!-- Add these styles to the head section or your CSS file -->
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
    border: none;
    background: none;
    color: #007bff;
    padding: 4px 8px;
    transition: color 0.2s ease;
    font-size: 14px;
}

.upload-btn:hover {
    background: none;
    border: none;
    color: #0056b3;
}

.upload-btn:focus {
    outline: none;
    box-shadow: none;
}

.clear-btn {
    float: right;
    color: #999;
    border: none;
    background: none;
    padding: 4px 8px;
    font-size: 14px;
    transition: color 0.2s ease;
}

.clear-btn:hover {
    color: #666;
    background: none;
    border: none;
}

.clear-btn:focus {
    outline: none;
    box-shadow: none;
}
.has-error .cke {
    border: 1px solid #a94442 !important;
    border-radius: 4px;
}

.has-error .cke_top {
    border-bottom: 1px solid #a94442 !important;
}

#description-error {
    display: block;
    margin-top: 5px;
    font-size: 12px;
}

#description-error i {
    margin-right: 5px;
}
</style>

<!-- Add this JavaScript before the closing body tag -->
<script>
// File change handler
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

// Individual file removal
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

// Clear all files
$('#clearFiles').on('click', function() {
    $('#attachFile').val('');
    $('#fileNameDisplay').html('<div class="text-muted">No files selected</div>');
});
</script>


