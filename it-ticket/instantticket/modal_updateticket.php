<?php 
include('../check_session.php');
$moduleid=1;
include('proxy.php');
$conn = connectionDB();

$ticketId=$_GET['id2'];

// Initialize variables to prevent undefined variable warnings
$myTaskProjectId = null;
$myTaskPriorityId = null;
$myTaskStatusId = null;
$myTaskClassId = null;
$myTaskSubject = null;
$myTaskDescription = null;
$myTaskDeadline = null;
$myTaskStartdate = null;
$myTaskEnddate = null;
$myTaskPriorityname = null;
$myTaskAssignee = null;
$myTaskPercentage = null;
$myTaskClass = null;
$myTaskStatus = null;
$myTaskCreatedfName = null;
$myTaskCreatedlName = null;
$myTaskProjectname = null;
$myTaskCreatedbyName = null;

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
			WHERE pm_projecttasktb.id = '$ticketId'";
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
	if (($myTaskPriorityname == '')||($myTaskPriorityname == null)||($myTaskPriorityname == "NULL")){$myTaskPriorityname=NULL;}
	if (($myTaskAssignee == '')||($myTaskAssignee == null)||($myTaskAssignee == "NULL")){$myTaskAssignee=NULL;}
	if (($myTaskPercentage == '')||($myTaskPercentage == null)||($myTaskPercentage == "NULL")){$myTaskPercentage=NULL;}
	if (($myTaskClass == '')||($myTaskClass == null)||($myTaskClass == "NULL")){$myTaskClass=NULL;}
	if (($myTaskStatus == '')||($myTaskStatus == null)||($myTaskStatus == "NULL")){$myTaskStatus=NULL;}
	if (($myTaskCreatedfName == '')||($myTaskCreatedfName == null)||($myTaskCreatedfName == "NULL")){$myTaskCreatedfName=NULL;}
	if (($myTaskCreatedlName == '')||($myTaskCreatedlName == null)||($myTaskCreatedlName == "NULL")){$myTaskCreatedlName=NULL;}
	if (($myTaskProjectname == '')||($myTaskProjectname == null)||($myTaskProjectname == "NULL")){$myTaskProjectname=NULL;}
	
	$myTaskCreatedbyName = $myTaskCreatedfName." ,".$myTaskCreatedlName;
	
	
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
				<form id="ticketInfoUpdate">
					<input type="hidden" class="form-control" id="ticketIdUp2" name="ticketIdUp2" value="<?php echo $ticketId;?>"/>
					<input type="hidden" class="form-control" id="ticketUseridUp2" name="ticketUseridUp2" value="<?php echo $userid;?>"/>
					<fieldset>
						<div class="row">
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Project Owner
										<span class="block input-icon input-icon-left">
											<select class="form-control" id="projectOwnerUp2" name="projectOwnerUp2" disabled>
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
											<select class="form-control" id="ticketClassificationUp2" name="ticketClassificationUp2" disabled>
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
											<select class="form-control" id="ticketPriorityUp2" name="ticketPriorityUp2" disabled>
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
											<input class="form-control" placeholder="ticketSubject" id="ticketSubjectUp2" name="ticketSubjectUp2" value="<?php echo $myTaskSubject;?>" disabled/>
										</span>
									</label>
								</div>	
							</div>
						
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Task Status
										<span class="block input-icon input-icon-left">
											<select class="form-control" id="ticketStatus2" name="ticketStatus2">
												<?php 
													$query66 = "SELECT * FROM sys_taskstatustb WHERE id IN ('1','2','3','4','5','6')";
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
							<div class="col-lg-12">
								<div class="form-group">
									<label class="block clearfix">Description
										<textarea id="descriptionupdate2" name="descriptionupdate2" rows="5" cols="80">
										<?php echo $myTaskDescription;?>
										</textarea>
									</label>
								</div>	
							</div>
						</div>	
						<div class="clearfix">
							<div id="flashticketup"></div>	
							<div id="insert_ticketup"></div>	
							<button type="button" class="width-25 pull-right btn btn-sm" id="resetTicketUpBtn" name="resetTicketUpBtn" data-dismiss="modal">
								<i class="ace-icon fa fa-refresh"></i>
								<span class="bigger-110">Close</span>
							</button>

							<!-- <button type="button" class="width-25 pull-right btn btn-sm btn-success" id="submitTicketUptBtn" name="submitTicketUptBtn">
								<span class="bigger-110">Update</span>
								<i class="ace-icon fa fa-arrow-right icon-on-right"></i>
							</button> -->
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