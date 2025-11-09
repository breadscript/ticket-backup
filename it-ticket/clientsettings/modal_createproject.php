<?php 
include('../check_session.php');
$moduleid=3;
$conn = connectionDB();
?>
<div class="modal-body">
	<!--<div id="signup-box" class="signup-box widget-box no-border">-->
	<div id="signup-box" class="signup-box widget-box no-border">
		<div class="widget-body">
			<!--<div class="widget-main">-->
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true"><small>×</small></button>
				<h4 class="header green lighter bigger">
					<i class="ace-icon fa fa-wrench red"></i>
					New Project Registration
				</h4>
				<p> Enter your details to begin: </p>
				<form id="projectInfo">
					<input type="hidden" class="form-control" placeholder="Email Address" id="projectUserid" name="projectUserid" value="<?php echo $userid;?>"/>
					<fieldset>
						<div class="row">
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Project Name
										<span class="block input-icon input-icon-left">
											<i class="ace-icon fa fa-user"></i>
											<input class="form-control" placeholder="Project Name" id="projectName" name="projectName" />
										</span>
									</label>
								</div>	
							</div>	
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Project Owner
										<span class="block input-icon input-icon-left">
											<select class="form-control" id="projectOwner" name="projectOwner">
											<option value="">Select Owner</option>
												<?php 
													$query66 = "SELECT id,clientname FROM sys_clienttb WHERE clientstatusid = '1'";
													$result66 = mysqli_query($conn, $query66);
													while($row66 = mysqli_fetch_assoc($result66)){
														$clientname=mb_convert_case($row66['clientname'], MB_CASE_TITLE, "UTF-8");
														$clientid=$row66['id'];
												?>
												<option value="<?php echo $clientid;?>"><?php echo $clientname;?></option>
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
											<input class="form-control date-picker" id="projectDateStart" name="projectDateStart" type="text" data-date-format="yyyy-mm-dd" placeholder="yyyy-mm-dd"/>
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Expected End Date
										<span class="block input-icon input-icon-left">
											<i class="ace-icon fa fa-calendar"></i>
											<input class="form-control date-picker" id="projectDateEnd" name="projectDateEnd" type="text" data-date-format="yyyy-mm-dd" placeholder="yyyy-mm-dd"/>
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Project Manager
										<span class="block input-icon input-icon-left">
											<select class="form-control" id="projectManager" name="projectManager">
											<option value="">Select Project Manager</option>
												<?php 
													$query66 = "SELECT id,user_firstname,user_lastname FROM sys_usertb WHERE user_statusid = '1' AND user_groupid = '1'";
													$result66 = mysqli_query($conn, $query66);
													while($row66 = mysqli_fetch_assoc($result66)){
														$pmFname=mb_convert_case($row66['user_firstname'], MB_CASE_TITLE, "UTF-8");
														$pmLname=mb_convert_case($row66['user_lastname'], MB_CASE_TITLE, "UTF-8");
														$pmCompleteName = $pmFname." ".$pmLname;
														$projectManagerId=$row66['id'];
												?>
												<option value="<?php echo $projectManagerId;?>"><?php echo $pmCompleteName;?></option>
												<?php } ?>
											</select>
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-12">
								<div class="form-group">
									<textarea id="remarks" name="editor1" rows="10" cols="80">
									</textarea>
								</div>	
							</div>
						</div>	
						<div class="clearfix">
							<div id="flash3"></div>	
							<div id="insert_search3"></div>	
							<button type="button" class="width-25 pull-right btn btn-sm" id="resetProjectBtn" name="resetProjectBtn" data-dismiss="modal">
								<i class="ace-icon fa fa-refresh"></i>
								<span class="bigger-110">Close</span>
							</button>

							<button type="button" class="width-25 pull-right btn btn-sm btn-success" id="submitProjectBtn" name="submitProjectBtn">
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
<script src="../assets/customjs/clientsettings.js"></script>

<script>
//datepicker plugin
//link
$('.date-picker').datepicker({
	autoclose: true,
	todayHighlight: true
})
CKEDITOR.replace('editor1');

$(".textarea").ace_wysiwyg();

</script>