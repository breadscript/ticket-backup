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
	<!--<div id="signup-box" class="signup-box widget-box no-border">-->
	<div id="signup-box" class="signup-box widget-box no-border">
		<div class="widget-body">
			<!--<div class="widget-main">-->
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true"><small>×</small></button>
				<h4 class="header green lighter bigger">
					<i class="ace-icon fa fa-users blue"></i>
					New Task Registration
				</h4>
				<p> Enter your details to begin: </p>
				<form id="taskInfo" enctype="multipart/form-data">
					<input type="hidden" class="form-control" id="taskUserid" name="taskUserid" value="<?php echo $userid;?>"/>
					<fieldset>
						<div class="row">
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Project Name
										<span class="block input-icon input-icon-left">
											<select class="form-control" id="taskProjectOwner" name="taskProjectOwner">
												<option value="">Select Owner</option>
												<?php 
													$query66 = "SELECT a.id,a.projectname, b.clientname												
													FROM sys_projecttb a
													LEFT JOIN  sys_clienttb b on a.clientid = b.id
													";
													
													$result66 = mysqli_query($conn, $query66);
													while($row66 = mysqli_fetch_assoc($result66)){
														$projectname=mb_convert_case($row66['projectname'], MB_CASE_TITLE, "UTF-8");
														$projectid=$row66['id'];
														$clientname=mb_convert_case($row66['clientname'], MB_CASE_TITLE, "UTF-8");
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
											<select class="form-control" id="taskClassification" name="taskClassification">
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
											<select class="form-control" id="taskPriority" name="taskPriority">
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
											<i class="ace-icon fa fa-user"></i>
											<input class="form-control" placeholder="taskSubject" id="taskSubject" name="taskSubject" />
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Assignee
										<span class="block input-icon input-icon-left">
											<select multiple="" name = "taskAssignee" id="taskAssignee2" class="chosen-select form-control" id="form-field-select-taskAssignee" data-placeholder="Choose a State...">
												<!--<option value="">Select Assignee</option>-->
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
								<!--<div class="form-group">
									<label class="block clearfix">Assignee
										<span class="block input-icon input-icon-left">
											<input id="taskAssignee" name = "taskAssignee" type="text" class="form-control tags" value="" />
										</span>
									</label>
								</div>-->
								<div class="form-group">
									<label class="block clearfix">Target Date
										<span class="block input-icon input-icon-left">
										<i class="ace-icon fa fa-calendar"></i>
											<input class="form-control date-picker" id="taskTargetDate" name="taskTargetDate" type="text" data-date-format="yyyy-mm-dd" placeholder="yyyy-mm-dd"/>
										</span>
									</label>
								</div>
								<div class="form-group">
									<label class="block clearfix">Start Date
										<span class="block input-icon input-icon-left">
										<i class="ace-icon fa fa-calendar"></i>
											<input class="form-control date-picker" id="taskStartDate" name="taskStartDate" type="text" data-date-format="yyyy-mm-dd" placeholder="yyyy-mm-dd"/>
											<input class="form-control date-picker" id="taskEndDate" name="taskEndDate" type="hidden" data-date-format="yyyy-mm-dd" placeholder="yyyy-mm-dd" value="1900-01-01"/>
										</span>
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
							
							<div class="col-lg-6">
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
							<button type="button" class="width-25 pull-right btn btn-sm" id="resetTaskBtn" name="resetTaskBtn" data-dismiss="modal">
								<i class="ace-icon fa fa-refresh"></i>
								<span class="bigger-110">Close</span>
							</button>

							<button type="button" class="width-25 pull-right btn btn-sm btn-success" id="submitTasktBtn" name="submitTasktBtn">
								<span class="bigger-110">Register</span>
								<i class="ace-icon fa fa-arrow-right icon-on-right"></i>
							</button>
						</div>
					</fieldset>
				</form>
			<!--</div>-->
		</div>
	</div>
</div>
<script src="../assets/customjs/projectmanagement.js"></script>
<!-- jQuery Tags Input -->
<script src="../assets/customjs/jquery.tagsinput.js"></script>
<script src="../assets/js/jquery-ui.custom.min.js"></script>
<script src="../assets/js/chosen.jquery.min.js"></script>


<script type="text/javascript">
	$(document).ready(function() {
        $('#taskAssignee').tagsInput({
          width: 'auto'
        });
    });
	CKEDITOR.replace('description');
	$('.date-picker').datepicker({
		autoclose: true,
		todayHighlight: true
	})
	
	if(!ace.vars['touch']) {
					$('.chosen-select').chosen({allow_single_deselect:true}); 
					
					
					$('#chosen-multiple-style .btn').on('click', function(e){
						var target = $(this).find('input[type=radio]');
						var which = parseInt(target.val());
						if(which == 2) $('#form-field-select-taskAssignee').addClass('tag-input-style');
						 else $('#form-field-select-taskAssignee').removeClass('tag-input-style');
					});
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


