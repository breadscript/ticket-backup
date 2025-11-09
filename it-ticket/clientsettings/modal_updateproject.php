<?php 
include('../check_session.php');
$moduleid=3;
include('proxy.php');
$conn = connectionDB();

$projectId=$_GET['id2'];

$myquery = "SELECT sys_projecttb.id,sys_projecttb.projectname,sys_projecttb.projectstartdate,sys_projecttb.projectenddate,
				   sys_projecttb.clientid,sys_projecttb.datecreated,sys_projecttb.createdbyid,sys_projecttb.statusid,
				   sys_clientstatustb.status,sys_clienttb.clientname,sys_usertb.user_firstname,sys_usertb.user_lastname,
				   sys_projecttb.description,sys_projecttb.projectmanagerid
			FROM sys_projecttb
			LEFT JOIN sys_clienttb ON sys_clienttb.id=sys_projecttb.clientid
			LEFT JOIN sys_clientstatustb ON sys_projecttb.statusid=sys_clientstatustb.id
			LEFT JOIN sys_usertb ON sys_usertb.id=sys_projecttb.projectmanagerid
			WHERE sys_projecttb.id = '$projectId'";
$myresult = mysqli_query($conn, $myquery);
while($row = mysqli_fetch_assoc($myresult)){
	$myProjectId = $row['id'];	
	$myProjectName = $row['projectname'];	
	$myProjectStartdate = $row['projectstartdate'];	
	$myProjectEnddate = $row['projectenddate'];	
	$myProjectClientId = $row['clientid'];	
	$myProjectStatusId = $row['statusid'];	
	$myProjectDescription = $row['description'];	
	$myProjectManagerId = $row['projectmanagerid'];	
	
	if (($myProjectName == '')||($myProjectName == null)||($myProjectName == "NULL")){$myProjectName=NULL;}
	if (($myProjectDescription == '')||($myProjectDescription == null)||($myProjectDescription == "NULL")){$myProjectDescription=NULL;}
}

	
?>
<div class="modal-body">
	<!--<div id="signup-box" class="signup-box widget-box no-border">-->
	<div id="signup-box" class="signup-box widget-box no-border">
		<div class="widget-body">
			<!--<div class="widget-main">-->
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true"><small>×</small></button>
				<h4 class="header green lighter bigger">
					<i class="ace-icon fa fa-users blue"></i>
					Update&nbsp;&nbsp;<?php echo $myProjectName;?>
				</h4>
				<p> Enter your details to begin: </p>
				<form id="updateProjectInfo">
					<input type="hidden" class="form-control" id="projectId2" name="projectId2" value="<?php echo $myProjectId;?>"/>
					<input type="hidden" class="form-control" id="projectUserid2" name="projectUserid2" value="<?php echo $userid;?>"/>
					<fieldset>
						<div class="row">
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Project Name
										<span class="block input-icon input-icon-left">
											<i class="ace-icon fa fa-user"></i>
											<input class="form-control" placeholder="Project Name" id="projectName2" name="projectName2" value="<?php echo $myProjectName;?>" />
										</span>
									</label>
								</div>	
							</div>	
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Project Owner
										<span class="block input-icon input-icon-left">
											<select class="form-control" id="projectOwner2" name="projectOwner2">
												<?php 
													$query66 = "SELECT id,clientname FROM sys_clienttb WHERE clientstatusid = '1'";
													$result66 = mysqli_query($conn, $query66);
													while($row66 = mysqli_fetch_assoc($result66)){
														$clientname=mb_convert_case($row66['clientname'], MB_CASE_TITLE, "UTF-8");
														$clientid=$row66['id'];
												?>
												<option value="<?php echo $clientid;?>"
												<?php 
													if ($myProjectClientId == $clientid){
														echo "selected";
													}
												?>><?php echo $clientname;?></option>
												<?php } ?>
											</select>
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Date Start
										<span class="block input-icon input-icon-left">
											<i class="ace-icon fa fa-calendar"></i>
											<input class="form-control date-picker" id="projectDateStart2" name="projectDateStart2" type="text" data-date-format="yyyy-mm-dd" placeholder="yyyy-mm-dd" value="<?php echo $myProjectStartdate;?>" />
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Expected End Date
										<span class="block input-icon input-icon-left">
											<i class="ace-icon fa fa-calendar"></i>
											<input class="form-control date-picker" id="projectDateEnd2" name="projectDateEnd2" type="text" data-date-format="yyyy-mm-dd" placeholder="yyyy-mm-dd" value="<?php echo $myProjectEnddate;?>" />
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Project Manager
										<span class="block input-icon input-icon-left">
											<select class="form-control" id="projectManager2" name="projectManager2">
												<?php 
													$query66 = "SELECT id,user_firstname,user_lastname FROM sys_usertb";
													$result66 = mysqli_query($conn, $query66);
													while($row66 = mysqli_fetch_assoc($result66)){
														$pmFname=mb_convert_case($row66['user_firstname'], MB_CASE_TITLE, "UTF-8");
														$pmLname=mb_convert_case($row66['user_lastname'], MB_CASE_TITLE, "UTF-8");
														$pmCompleteName = $pmFname." ".$pmLname;
														$projectManagerId=$row66['id'];
												?>
												<option value="<?php echo $projectManagerId;?>"
												<?php 
													if ($myProjectManagerId == $projectManagerId){
														echo "selected";
													}
												?>><?php echo $pmCompleteName;?></option>
												<?php } ?>
											</select>
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Project Status
										<span class="block input-icon input-icon-left">
											<select class="form-control" id="projectStatus2" name="projectStatus2">
												<?php 
													$query66 = "SELECT * FROM sys_taskstatustb";
													$result66 = mysqli_query($conn, $query66);
													while($row66 = mysqli_fetch_assoc($result66)){
														$statusname=mb_convert_case($row66['statusname'], MB_CASE_TITLE, "UTF-8");
														$statusid=$row66['id'];
												?>
												<option value="<?php echo $statusid;?>"
												<?php 
													if ($myProjectStatusId == $statusid){
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
									<textarea id="remarks22" name="editor22" rows="10" cols="80"><?php echo $myProjectDescription; ?>
									</textarea>
								</div>	
							</div>
						</div>	
						<?php if($authoritystatusidz == 1){?>
						<div class="clearfix">
							<div id="flashup2"></div>	
							<div id="insert_searchup2"></div>	
							<button type="button" class="width-25 pull-right btn btn-sm" id="resetProjectBtn" name="resetProjectBtn" data-dismiss="modal">
								<i class="ace-icon fa fa-refresh"></i>
								<span class="bigger-110">Close</span>
							</button>

							<button type="button" class="width-25 pull-right btn btn-sm btn-success" id="submitUpdateProjectBtn" name="submitUpdateProjectBtn">
								<span class="bigger-110">Update</span>
								<i class="ace-icon fa fa-arrow-right icon-on-right"></i>
							</button>
						</div>
						<?php }else{
							echo "<div class='alert alert-alert fade in'>";
							echo "Access : Read Only";   		
							echo "</div>";
						} ?>
					</fieldset>
				</form>
			<!--</div>-->
		</div>
	</div>
</div>
<script src="../assets/customjs/clientsettings.js"></script>
<script>
//datepicker plugin
//link
$('.date-picker').datepicker({
	autoclose: true,
	todayHighlight: true
})
CKEDITOR.replace('editor22');

$(".textarea").ace_wysiwyg();

</script>