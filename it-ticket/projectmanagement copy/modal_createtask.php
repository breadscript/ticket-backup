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
				<form id="taskInfo">
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
													$query66 = "SELECT id,projectname FROM sys_projecttb WHERE statusid <> 6";
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
													$query66 = "SELECT id,user_firstname,user_lastname FROM sys_usertb WHERE user_statusid = '1' ";
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
</script>


