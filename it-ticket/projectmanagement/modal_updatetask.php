<?php 
include('../check_session.php');
$moduleid=4;
include('proxy.php');
$conn = connectionDB();

$taskId=$_GET['id2'];

$myquery = "SELECT pm_projecttasktb.subject,pm_projecttasktb.description,
					pm_projecttasktb.deadline,pm_projecttasktb.startdate,pm_projecttasktb.enddate,
					sys_priorityleveltb.priorityname,
					pm_projecttasktb.assignee,pm_projecttasktb.percentdone,
					sys_taskclassificationtb.classification,sys_taskstatustb.statusname,
					sys_usertb.user_firstname,sys_usertb.user_lastname,sys_projecttb.projectname,
					pm_projecttasktb.classificationid,pm_projecttasktb.statusid,
					pm_projecttasktb.priorityid,pm_projecttasktb.projectid,
					pm_projecttasktb.resolution
			FROM pm_projecttasktb
			LEFT JOIN sys_taskclassificationtb ON sys_taskclassificationtb.id=pm_projecttasktb.classificationid
			LEFT JOIN sys_taskstatustb ON sys_taskstatustb.id=pm_projecttasktb.statusid
			LEFT JOIN sys_priorityleveltb ON sys_priorityleveltb.id=pm_projecttasktb.priorityid
			LEFT JOIN sys_usertb ON sys_usertb.id=pm_projecttasktb.createdbyid
			LEFT JOIN sys_projecttb ON sys_projecttb.id=pm_projecttasktb.projectid
			WHERE pm_projecttasktb.id = '$taskId'";
$myresult = mysqli_query($conn, $myquery);
while($row = mysqli_fetch_assoc($myresult)){
	$myTaskProjectId = $row['projectid'];	
	$myTaskPriorityId = $row['priorityid'];	
	$myTaskStatusId = $row['statusid'];	
	$myTaskClassId = $row['classificationid'];	
	$myTaskSubject = $row['subject'];	
	$myTaskDescription = $row['description'];	
	$myTaskDeadline = $row['deadline'];	
	$myTaskStartdate = $row['startdate'];	
	$myTaskEnddate = $row['enddate'];	
	$myTaskPriorityname = $row['priorityname'];	
	$myTaskAssignee = $row['assignee'];	
	$myTaskPercentage = $row['percentdone'];	
	$myTaskClass = $row['classification'];	
	$myTaskStatus = $row['statusname'];	
	$myTaskCreatedfName = $row['user_firstname'];
	$myTaskCreatedlName = $row['user_lastname'];
	$myTaskProjectname = $row['projectname'];	
	$myTaskResolution = $row['resolution'];
	
	if (($myTaskSubject == '')||($myTaskSubject == null)||($myTaskSubject == "NULL")){$myTaskSubject=NULL;}
	if (($myTaskDescription == '')||($myTaskDescription == null)||($myTaskDescription == "NULL")){$myTaskDescription=NULL;}
	if (($myTaskDeadline == '')||($myTaskDeadline == null)||($myTaskDeadline == "NULL")){$myTaskDeadline="1900-01-01";}
	if (($myTaskStartdate == '')||($myTaskStartdate == null)||($myTaskStartdate == "NULL")){$myTaskStartdate="1900-01-01";}
	if (($myTaskEnddate == '')||($myTaskEnddate == null)||($myTaskEnddate == "NULL")){$myTaskEnddate="1900-01-01";}
	if($myTaskEnddate == "1900-01-01"){$myTaskEnddate = null;}
	if($myTaskDeadline == "1900-01-01"){$myTaskDeadline = null;}
	if($myTaskStartdate == "1900-01-01"){$myTaskStartdate = null;}
	if (($myTaskPriorityname == '')||($myTaskPriorityname == null)||($myTaskPriorityname == "NULL")){$myTaskPriorityname=NULL;}
	if (($myTaskAssignee == '')||($myTaskAssignee == null)||($myTaskAssignee == "NULL")){$myTaskAssignee=NULL;}
	if (($myTaskPercentage == '')||($myTaskPercentage == null)||($myTaskPercentage == "NULL")){$myTaskPercentage=NULL;}
	if (($myTaskClass == '')||($myTaskClass == null)||($myTaskClass == "NULL")){$myTaskClass=NULL;}
	if (($myTaskStatus == '')||($myTaskStatus == null)||($myTaskStatus == "NULL")){$myTaskStatus=NULL;}
	if (($myTaskCreatedfName == '')||($myTaskCreatedfName == null)||($myTaskCreatedfName == "NULL")){$myTaskCreatedfName=NULL;}
	if (($myTaskCreatedlName == '')||($myTaskCreatedlName == null)||($myTaskCreatedlName == "NULL")){$myTaskCreatedlName=NULL;}
	if (($myTaskProjectname == '')||($myTaskProjectname == null)||($myTaskProjectname == "NULL")){$myTaskProjectname=NULL;}
	if (($myTaskResolution == '')||($myTaskResolution == null)||($myTaskResolution == "NULL")){$myTaskResolution=NULL;}
	
	$myTaskCreatedbyName = $myTaskCreatedfName." ,".$myTaskCreatedlName;
	
	//-----------------multiple-----------------------
	
		// $myTaskAssignee = explode(",", $myTaskAssignee);
		
		// foreach ($myTaskAssignee as $key => $value) {
			// echo "id:".$value."</br>";
		// }
		
	
}

	
?>
<!-- jQuery Tags Input -->
<link href="../assets/customjs/jquery.tagsinput.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/chosen.min.css" />
<div class="modal-body">
	<div id="signup-box" class="signup-box widget-box no-border">
		<div class="widget-body">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true"><small>×</small></button>
			<h4 class="header green lighter bigger">
				<i class="ace-icon fa fa-users blue"></i>
				Manage&nbsp;&nbsp;<?php echo $myTaskSubject;?>
			</h4>
			<p> Enter your details to begin: </p>
			<form id="taskInfoUpdate">
				<input type="hidden" class="form-control" id="taskIdUp2" name="taskIdUp2" value="<?php echo $taskId;?>"/>
				<input type="hidden" class="form-control" id="taskUseridUp2" name="taskUseridUp2" value="<?php echo $userid;?>"/>
				<fieldset>
					<div class="row">
						<!-- Left Column -->
						<div class="col-md-6">
							<div class="form-group">
								<label class="block clearfix">Project Owner</label>
								<span class="block input-icon input-icon-left">
									<select class="form-control" id="projectOwnerUp2" name="projectOwnerUp2">
										<?php 
											$query66 = "SELECT id,projectname FROM sys_projecttb";
											$result66 = mysqli_query($conn, $query66);
											while($row66 = mysqli_fetch_assoc($result66)){
												$projectname=mb_convert_case($row66['projectname'], MB_CASE_TITLE, "UTF-8");
												$projectid=$row66['id'];
										?>
										<option value="<?php echo $projectid;?>"
										<?php 
											if ($myTaskProjectId == $projectid){
												echo "selected";
											}
										?>><?php echo $projectname;?></option>
										<?php } ?>
									</select>
								</span>
							</div>
							
							<div class="form-group">
								<label class="block clearfix">Classification</label>
								<span class="block input-icon input-icon-left">
									<select class="form-control" id="taskClassificationUp2" name="taskClassificationUp2">
										<option value="">Select Classification</option>
										<?php 
											$query66 = "SELECT id,classification FROM sys_taskclassificationtb";
											$result66 = mysqli_query($conn, $query66);
											while($row66 = mysqli_fetch_assoc($result66)){
												$classification=mb_convert_case($row66['classification'], MB_CASE_TITLE, "UTF-8");
												$classid=$row66['id'];
										?>
										<option value="<?php echo $classid;?>"
										<?php 
											if ($myTaskClassId == $classid){
												echo "selected";
											}
										?>><?php echo $classification;?></option>
										<?php } ?>
									</select>
								</span>
							</div>
							
							<div class="form-group">
								<label class="block clearfix">Priority Level</label>
								<span class="block input-icon input-icon-left">
									<select class="form-control" id="taskPriorityUp2" name="taskPriorityUp2">
										<option value="">Select Priority</option>
										<?php 
											$query66 = "SELECT id,priorityname FROM sys_priorityleveltb";
											$result66 = mysqli_query($conn, $query66);
											while($row66 = mysqli_fetch_assoc($result66)){
												$priorityname=mb_convert_case($row66['priorityname'], MB_CASE_TITLE, "UTF-8");
												$priorityid=$row66['id'];
										?>
										<option value="<?php echo $priorityid;?>"
										<?php 
											if ($myTaskPriorityId == $priorityid){
												echo "selected";
											}
										?>><?php echo $priorityname;?></option>
										<?php } ?>
									</select>
								</span>
							</div>
							
							<div class="form-group">
								<label class="block clearfix">Subject</label>
								<span class="block input-icon input-icon-left">
									<i class="ace-icon fa fa-user"></i>
									<input class="form-control" placeholder="taskSubject" id="taskSubjectUp2" name="taskSubjectUp2" value="<?php echo $myTaskSubject;?>"/>
								</span>
							</div>
							
							<div class="form-group">
								<label class="block clearfix">Task Status</label>
								<span class="block input-icon input-icon-left">
									<select class="form-control" id="taskStatus2" name="taskStatus2">
										<?php 
											$query66 = "SELECT * FROM sys_taskstatustb";
											$result66 = mysqli_query($conn, $query66);
											while($row66 = mysqli_fetch_assoc($result66)){
												$statusname=mb_convert_case($row66['statusname'], MB_CASE_TITLE, "UTF-8");
												$statusid=$row66['id'];
										?>
										<option value="<?php echo $statusid;?>"
										<?php 
											if ($myTaskStatusId == $statusid){
												echo "selected";
											}
										?>><?php echo $statusname;?></option>
										<?php } ?>
									</select>
								</span>
							</div>
							
							<div class="form-group">
								<label class="block clearfix">Description</label>
								<textarea id="descriptionupdate2" name="descriptionupdate2" rows="5" cols="80"><?php echo $myTaskDescription;?></textarea>
							</div>
						</div>
						
						<!-- Right Column -->
						<div class="col-md-6">
							<div class="form-group">
								<label class="block clearfix">Assignee</label>
								<select multiple="" name="taskAssigneeUp2" id="taskAssigneeUp2" class="chosen-select form-control" data-placeholder="Choose assignees...">
									<?php 
										$query66 = "SELECT id,user_firstname,user_lastname FROM sys_usertb WHERE user_statusid = '1' AND user_groupid = '2' ";
										$result66 = mysqli_query($conn, $query66);
										while($row66 = mysqli_fetch_assoc($result66)){
											$user_firstname=mb_convert_case($row66['user_firstname'], MB_CASE_TITLE, "UTF-8");
											$user_lastname=mb_convert_case($row66['user_lastname'], MB_CASE_TITLE, "UTF-8");
											$engrid=$row66['id'];
											$name = $user_firstname." , ".$user_lastname;
									?>
									<option value="<?php echo $engrid;?>"
									<?php 
										$query66x = "SELECT assigneeid FROM pm_taskassigneetb WHERE taskid = '$taskId' ";
										$result66x = mysqli_query($conn, $query66x);
										while($row66x = mysqli_fetch_assoc($result66x)){
											$assigneeid=$row66x['assigneeid'];
											
											if ($assigneeid == $engrid){
												echo "selected";
											}
											
										}
									?>><?php echo $name;?></option>
									<?php } ?>
								</select>
							</div>
							
							<div class="form-group">
								<label class="block clearfix">Target Date</label>
								<span class="block input-icon input-icon-left">
									<i class="ace-icon fa fa-calendar"></i>
									<input class="form-control date-picker" id="taskTargetDateUp2" name="taskTargetDateUp2" type="text" data-date-format="yyyy-mm-dd" placeholder="yyyy-mm-dd" value="<?php echo $myTaskDeadline;?>"/>
								</span>
							</div>
							
							<div class="form-group">
								<label class="block clearfix">Start Date</label>
								<span class="block input-icon input-icon-left">
									<i class="ace-icon fa fa-calendar"></i>
									<input class="form-control date-picker" id="taskStartDateUp2" name="taskStartDateUp2" type="text" data-date-format="yyyy-mm-dd" placeholder="yyyy-mm-dd" value="<?php echo $myTaskStartdate;?>"/>
								</span>
							</div>
							
							<div class="form-group">
								<label class="block clearfix">Actual End Date</label>
								<span class="block input-icon input-icon-left">
									<i class="ace-icon fa fa-calendar"></i>
									<input class="form-control date-picker" id="taskEndDateUp2" name="taskEndDateUp2" type="text" data-date-format="yyyy-mm-dd" placeholder="yyyy-mm-dd" value="<?php echo $myTaskEnddate;?>" disabled />
								</span>
							</div>
							
							<div class="form-group">
								<label class="block clearfix">Resolution</label>
								<div class="resolution-alert" id="resolutionAlert" style="color: #dc3545; margin-bottom: 10px; font-weight: bold;">
									Resolution is required before marking as DONE.
								</div>
								<textarea id="resolution" name="resolution" rows="5" cols="80" <?php echo ($_SESSION['usergroupid'] == 4) ? 'disabled' : ''; ?>><?php echo $myTaskResolution;?></textarea>
							</div>

							<div class="form-group">
								<label class="block clearfix">Attach File</label>
								<span class="block input-icon input-icon-left">
									<div class="input-group">
										<input type="file" class="form-control" id="attachFile" name="attachFile[]" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt">
										<span class="input-group-btn">
											<button type="button" class="btn btn-sm btn-primary" id="uploadBtn" name="uploadBtn">
												<span class="bigger-110">Upload</span>
												<i class="ace-icon fa fa-arrow-up icon-on-right"></i>
											</button>
										</span>
									</div>
									<div id="fileNameDisplay" style="margin-top: 5px; font-size: 12px; color: #666;">
										No files selected
									</div>
								</span>
							</div>
						</div>
					</div>
					
					<!-- File Upload Section -->
					<div class="row">
						<div class="col-md-6">
							
						</div>
						
						<div class="col-md-6">
							<div class="form-group">
								<label class="block clearfix">Uploaded Files</label>
								<span class="block input-icon input-icon-left">
									<select class="form-control" id="uploadedFiles" name="uploadedFiles">
										<?php 
											$query66 = "SELECT * FROM pm_threadtb WHERE taskid = '$taskId' AND file_data IS NOT NULL AND file_data != ''";
											$result66 = mysqli_query($conn, $query66);
											if(mysqli_num_rows($result66) > 0) {
												while($row66 = mysqli_fetch_assoc($result66)){
													$fileContent = $row66['file_data'];
													$fileid = $row66['id'];
													
													// Split multiple files if they're comma-separated
													$files = explode(',', $fileContent);
													
													foreach($files as $file) {
														$file = trim($file); // Remove any whitespace
														if(!empty($file)) {
															// Extract filename from path
															$filename = basename($file);
															$displayName = $filename ? $filename : "File ID: $fileid";
															?>
															<option value="<?php echo $fileid . '|' . $file; ?>">
																<?php echo $displayName; ?>
															</option>
															<?php
														}
													}
												}
											} else { ?>
												<option value="">There is no uploaded file/s yet.</option>
										<?php } ?>
									</select>
								</span>
							</div>
						</div>
					</div>
					
					<!-- Action Buttons -->
					<div class="row">
						<div class="col-md-12">
							<div class="clearfix">
								<div id="flashtaskup"></div>	
								<div id="insert_taskup"></div>
								
								<a class="width-25 pull-right btn btn-sm btn-danger" href="threadPage.php?taskId=<?php echo $taskId; ?>" id="openThread" style="margin-left: 5px;">
									<i class="ace-icon fa fa-arrow-right"></i>
									Open Thread
								</a>
								
								<button type="button" class="width-25 pull-right btn btn-sm" id="resetTaskUpBtn" name="resetTaskUpBtn" data-dismiss="modal" style="margin-left: 5px;">
									<i class="ace-icon fa fa-times"></i>
									<span class="bigger-110">Close</span>
								</button>

								<button type="button" class="width-25 pull-right btn btn-sm btn-success" id="submitTaskUptBtn" name="submitTaskUptBtn">
									<i class="ace-icon fa fa-check"></i>
									<span class="bigger-110">Update</span>
								</button>
							</div>
						</div>
					</div>
				</fieldset>
			</form>
		</div>
	</div>
</div>
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/customjs/projectmanagement.js"></script>
<!-- jQuery Tags Input -->
<script src="../assets/customjs/jquery.tagsinput.js"></script>
<script src="../assets/js/chosen.jquery.min.js"></script>
<script type="text/javascript">
	
	$(document).ready(function() {
        $('#taskAssigneeupdate2').tagsInput({
          width: 'auto'
        });
    });
	CKEDITOR.replace('descriptionupdate2');
	CKEDITOR.replace('resolution');
	$('.date-picker').datepicker({
		autoclose: true,
		todayHighlight: true
	})
	if(!ace.vars['touch']) {
					$('.chosen-select').chosen({allow_single_deselect:true}); 
					
					
					$('#chosen-multiple-style .btn').on('click', function(e){
						var target = $(this).find('input[type=radio]');
						var which = parseInt(target.val());
						if(which == 2) $('#form-field-select-taskAssigneeUp').addClass('tag-input-style');
						 else $('#form-field-select-taskAssigneeUp').removeClass('tag-input-style');
					});
				}
</script>


<script type="text/javascript">
    $(document).ready(function() {
        // Handle file selection display
        $('#attachFile').on('change', function() {
            const fileInput = this;
            const fileNameDisplay = $('#fileNameDisplay');
            
            if (fileInput.files.length > 10) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Too Many Files',
                    text: 'Maximum 10 files can be uploaded at once',
                    confirmButtonColor: '#3085d6'
                });
                fileInput.value = '';
                fileNameDisplay.text('No files selected').css('color', '#666');
                return;
            }
            
            if (fileInput.files.length > 0) {
                const fileNames = fileInput.files.length >= 4 
                    ? `${fileInput.files.length} files selected`
                    : Array.from(fileInput.files).map(file => file.name).join(', ');
                fileNameDisplay.text(fileNames).css('color', '#333');
            } else {
                fileNameDisplay.text('No files selected').css('color', '#666');
            }
        });

        // Handle file upload
        $('#uploadBtn').click(function() {
            const fileInput = $('#attachFile')[0];
            const files = fileInput.files;
            
            if (files.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Files Selected',
                    text: 'Please select files to upload',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            const formData = new FormData();
            
            for (let i = 0; i < files.length; i++) {
                formData.append('attachFile[]', files[i]);
            }
            
            formData.append('taskId', $('#taskIdUp2').val());
            formData.append('userId', $('#taskUseridUp2').val());
            formData.append('taskSubjectUp2', $('#taskSubjectUp2').val());
            
            // Show loading state
            Swal.fire({
                title: 'Uploading...',
                text: 'Please wait while we upload your files',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Disable upload button
            $('#uploadBtn').prop('disabled', true);
            
            // Send AJAX request
            $.ajax({
                url: 'upload_file.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Upload response:', response);
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Files uploaded successfully',
                            confirmButtonColor: '#3085d6'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Upload Failed',
                            text: response.message || 'Unknown error occurred',
                            confirmButtonColor: '#3085d6'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Upload error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    
                    let errorMessage = 'An error occurred during upload';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            errorMessage = response.message;
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Upload Failed',
                        text: errorMessage,
                        confirmButtonColor: '#3085d6'
                    });
                },
                complete: function() {
                    // Re-enable upload button
                    $('#uploadBtn').prop('disabled', false);
                }
            });
        });
        
        // Modify the checkTaskStatus function
        function checkTaskStatus() {
            var statusText = $("#taskStatus2 option:selected").text().trim();
            var $resolutionAlert = $("#resolutionAlert");
            var resolutionContent = CKEDITOR.instances.resolution.getData();
            
            // Professional whitespace validation
            // Remove HTML tags and decode entities
            var cleanContent = $('<div>').html(resolutionContent).text();
            // Remove all types of whitespace (spaces, tabs, newlines, etc.)
            var meaningfulContent = cleanContent.replace(/\s+/g, '').trim();
            
            if (statusText === "Done") {
                // Enable resolution when status is Done
                CKEDITOR.instances.resolution.setReadOnly(false);
                
                // Check if there's meaningful content (not just whitespace)
                if (!meaningfulContent || meaningfulContent.length === 0) {
                    // If status is Done and resolution is empty or only whitespace
                    $("#submitTaskUptBtn").prop("disabled", true)
                        .addClass("btn-default")
                        .removeClass("btn-success");
                    $resolutionAlert.show();
                } else if (meaningfulContent.length < 10) {
                    // If content is too short (less than 10 meaningful characters)
                    $("#submitTaskUptBtn").prop("disabled", true)
                        .addClass("btn-default")
                        .removeClass("btn-success");
                    $resolutionAlert.html('<i class="ace-icon fa fa-exclamation-triangle"></i> Resolution must contain at least 10 characters.').show();
                } else {
                    // If status is Done and resolution has meaningful content
                    $("#submitTaskUptBtn").prop("disabled", false)
                        .removeClass("btn-default")
                        .addClass("btn-success");
                    $resolutionAlert.hide();
                }
            } else {
                // If status is not Done
                CKEDITOR.instances.resolution.setReadOnly(true);
                $("#submitTaskUptBtn").prop("disabled", false)
                    .removeClass("btn-default")
                    .addClass("btn-success");
                $resolutionAlert.hide();
            }
        }
        
        // Initialize CKEDITOR with proper state on page load
        CKEDITOR.on('instanceReady', function(evt) {
            if (evt.editor.name === 'resolution') {
                // Get initial status and content
                var statusText = $("#taskStatus2 option:selected").text().trim();
                var initialContent = evt.editor.getData();
                
                // Professional whitespace validation for initial content
                var cleanInitialContent = $('<div>').html(initialContent).text();
                var meaningfulInitialContent = cleanInitialContent.replace(/\s+/g, '').trim();
                
                if (statusText === "Done") {
                    // If status is already Done, enable resolution editor
                    evt.editor.setReadOnly(false);
                    
                    // Check if there's meaningful initial content
                    if (meaningfulInitialContent && meaningfulInitialContent.length >= 10) {
                        $("#submitTaskUptBtn").prop("disabled", false)
                            .removeClass("btn-default")
                            .addClass("btn-success");
                        $("#resolutionAlert").hide();
                    } else {
                        $("#submitTaskUptBtn").prop("disabled", true)
                            .addClass("btn-default")
                            .removeClass("btn-success");
                        $("#resolutionAlert").show();
                    }
                } else {
                    // For other statuses, disable resolution editor
                    evt.editor.setReadOnly(true);
                }
            }
        });
        
        // Add event listener for status change
        $("#taskStatus2").on("change", function() {
            checkTaskStatus();
        });
        
        // Check when resolution content changes
        CKEDITOR.instances.resolution.on('change', function() {
            checkTaskStatus();
        });
        
        // Add paste event handling to enable button when text is pasted
        CKEDITOR.instances.resolution.on('paste', function() {
            // Use setTimeout to ensure the paste content is processed
            setTimeout(function() {
                checkTaskStatus();
            }, 100);
        });
        
        // Also handle keyup events for immediate feedback
        CKEDITOR.instances.resolution.on('keyup', function() {
            checkTaskStatus();
        });
        
        // Handle content change events (covers more scenarios)
        CKEDITOR.instances.resolution.on('contentDom', function() {
            var editor = this;
            editor.document.on('keyup', function() {
                checkTaskStatus();
            });
        });
    });
</script>

<style>
.resolution-alert {
    position: relative;
    padding: 10px;
    background-color: #fff3cd;
    border: 1px solid #ffeeba;
    border-radius: 4px;
    color: #856404;
    margin-bottom: 10px;
    display: none;
}

.resolution-alert::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 20px;
    border-width: 10px 10px 0;
    border-style: solid;
    border-color: #ffeeba transparent transparent;
}
</style>
