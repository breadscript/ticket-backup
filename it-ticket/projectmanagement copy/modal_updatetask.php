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
					pm_projecttasktb.priorityid,pm_projecttasktb.projectid
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
	<!--<div id="signup-box" class="signup-box widget-box no-border">-->
	<div id="signup-box" class="signup-box widget-box no-border">
		<div class="widget-body">
			<!--<div class="widget-main">-->
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
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Project Owner
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
									</label>
								</div>	
							</div>	
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Classification
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
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Priority Level
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
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Subject
										<span class="block input-icon input-icon-left">
											<i class="ace-icon fa fa-user"></i>
											<input class="form-control" placeholder="taskSubject" id="taskSubjectUp2" name="taskSubjectUp2" value="<?php echo $myTaskSubject;?>"/>
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Assignee
										<select multiple="" name = "taskAssigneeUp2" id="taskAssigneeUp2" class="chosen-select form-control" id="form-field-select-taskAssigneeUp" data-placeholder="Choose a State...">
											<?php 
												// $myTaskAssignee = explode(",", $myTaskAssignee);
													// foreach ($myTaskAssignee as $key => $value) {
													// $amyposition.="'".$value."',";
												// }
													$query66 = "SELECT id,user_firstname,user_lastname FROM sys_usertb WHERE user_statusid = '1' ";
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
									</label>
								</div>
								<div class="form-group">
									<label class="block clearfix">Target Date
										<span class="block input-icon input-icon-left">
										<i class="ace-icon fa fa-calendar"></i>
											<input class="form-control date-picker" id="taskTargetDateUp2" name="taskTargetDateUp2" type="text" data-date-format="yyyy-mm-dd" placeholder="yyyy-mm-dd" value="<?php echo $myTaskDeadline;?>"/>
										</span>
									</label>
								</div>
								<div class="form-group">
									<label class="block clearfix">Start Date
										<span class="block input-icon input-icon-left">
										<i class="ace-icon fa fa-calendar"></i>
											<input class="form-control date-picker" id="taskStartDateUp2" name="taskStartDateUp2" type="text" data-date-format="yyyy-mm-dd" placeholder="yyyy-mm-dd" value="<?php echo $myTaskStartdate;?>"/>
										</span>
									</label>
								</div>
								<div class="form-group">
									<label class="block clearfix">Actual End Date
										<span class="block input-icon input-icon-left">
										<i class="ace-icon fa fa-calendar"></i>
											<input class="form-control date-picker" id="taskEndDateUp2" name="taskEndDateUp2" type="text" data-date-format="yyyy-mm-dd" placeholder="yyyy-mm-dd" value="<?php echo $myTaskEnddate;?>" disabled />
										</span>
									</label>
								</div>
							</div>
							
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Description
										<textarea id="descriptionupdate2" name="descriptionupdate2" rows="5" cols="80">
										<?php echo $myTaskDescription;?>
										</textarea>
									</label>
								</div>	
							</div>
							
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Task Status
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
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<a class="width-55 pull-right btn btn-sm btn-danger" href="modalnewpage.php?taskId=<?php echo $taskId; ?>" id="openThread">
									Open 123<i class="ace-icon fa fa-arrow-right icon-on-right"></i>
								</a>
							</div>
						</div>	
						<div class="clearfix">
							<div id="flashtaskup"></div>	
							<div id="insert_taskup"></div>	
							<button type="button" class="width-25 pull-right btn btn-sm" id="resetTaskUpBtn" name="resetTaskUpBtn" data-dismiss="modal">
								<i class="ace-icon fa fa-refresh"></i>
								<span class="bigger-110">Close</span>
							</button>

							<button type="button" class="width-25 pull-right btn btn-sm btn-success" id="submitTaskUptBtn" name="submitTaskUptBtn">
								<span class="bigger-110">Update</span>
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
<script src="../assets/js/chosen.jquery.min.js"></script>
<script type="text/javascript">
	$(document).ready(function() {
        $('#taskAssigneeupdate2').tagsInput({
          width: 'auto'
        });
    });
	CKEDITOR.replace('descriptionupdate2');
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