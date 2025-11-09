<?php
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d h:i:s",time());	
include "blocks/header.php";
$moduleid=7;
include('proxy.php');
// $myusergroupid;

?>
<!DOCTYPE html>
<link rel="../stylesheet" href="../css/bootstrapValidator.min.css"/> 
<style>
.input-xs {
    height: 22px;
    padding: 0px 1px;
    font-size: 10px;
    line-height: 1; //If Placeholder of the input is moved up, rem/modify this.
    border-radius: 0px;
    }
</style>
<html lang="en">
	<body class="no-skin">
		
		<div class="row">
            <div class="col-lg-12">
				<div id="myModal_createTask" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="false"></div>	
				<div id="myModal_updateTask" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="false"></div>	
			</div>
		</div>
		<div class="main-container ace-save-state" id="main-container">
			<?php include "blocks/navigation.php";?>

			<div class="main-content">
				<div class="main-content-inner">
					<div class="breadcrumbs ace-save-state" id="breadcrumbs">
						<ul class="breadcrumb">
							<li>
								<i class="ace-icon fa fa-home home-icon"></i>
								<a href="../index.php">Home</a>
							</li>
							<li>Reports </li>
						</ul><!-- /.breadcrumb -->

					</div>

					<div class="page-content">
						<div class="row">
							<div class="col-xs-12">
								
							</div>
							<div class="col-xs-12">
								<ul class="nav nav-tabs" id="tabUL">
									<!--<li class="active"><a href="#taskfrompm" data-toggle="tab">Assignement</a></li>
									<li><a href="#taskfrompmdone" data-toggle="tab">Assignement(Done)</a></li>
									-->
									<li class="active"><a href="#clientReports" data-toggle="tab">Client Reports</a></li>
									<li><a href="#projectReports" data-toggle="tab">Project Reports</a></li>
									<li><a href="#taskReports" data-toggle="tab">Task Reports</a></li>
									
								</ul>
								<div class="tab-content">
									<div class="tab-pane fade in active" id="clientReports">
										<form id="clientReports">
											<fieldset>
												<div class="row">
													<div class="col-lg-4">
														<div class="form-group">
															
																<span class="block input-icon input-icon-left">
																	<i class="ace-icon fa fa-user"></i>
																	<input class="form-control" placeholder="Client's Name" id="clientName" name="clientName" />
																</span>
															
														</div>	
													</div>
													<div class="col-lg-4">
														<div class="form-group">
															
																<span class="block input-icon input-icon-left">
																	<select class="form-control" id="statusType" name="statusType">
																		<option value="">Status</option>
																		<?php 
																			$query66 = "SELECT id,status FROM sys_clientstatustb";
																			$result66 = mysqli_query($conn, $query66);
																			while($row66 = mysqli_fetch_assoc($result66)){
																				$priorityname=mb_convert_case($row66['status'], MB_CASE_TITLE, "UTF-8");
																				$prioritynameid=$row66['id'];
																		?>
																		<option value="<?php echo $prioritynameid;?>"><?php echo $priorityname;?></option>
																		<?php } ?>
																	</select>
																</span>
															
														</div>	
													</div>
													<div class="clearfix">
														<button type="button" class="width-25 pull-left btn btn-sm btn-success" id="submitSearchBtn1" name="submitSearchBtn1">
															<span class="bigger-110">Search</span>
															<i class="ace-icon fa fa-arrow-right icon-on-right"></i>
														</button>
													</div>
												</div>	
												
											</fieldset>
										</form>
											<div id="flashdtrx"></div>
											<div id="insert_searchdtrx" align="left"></div>
									</div>
									
									<div class="tab-pane fade in" id="projectReports">
										<!--<div id="insert_assignmentdonexxx"></div>					                    	
										<div id="insertbody_assignmentdonexxx"></div>-->
										<form id="projectReportsxxx">
											<fieldset>
												<div class="row">
													<div class="col-lg-3">
														<div class="form-group">
															<label class="block clearfix">Project Name
																<span class="block input-icon input-icon-left">
																	<i class="ace-icon fa fa-user"></i>
																	<input class="form-control" placeholder="Project Name" id="projectName2" name="projectName2" />
																</span>
															</label>
														</div>	
													</div>
													<div class="col-lg-3">
														<div class="form-group">
															<label class="block clearfix">Project Owner
															<span class="block input-icon input-icon-left">
																<select class="form-control" id="projectOwnerId" name="projectOwnerId">
																	<option value="">Owner</option>
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
													<div class="col-lg-3">
														<div class="form-group">
															<label class="block clearfix">Project Manager
																<span class="block input-icon input-icon-left">
																	<select class="form-control" id="projectManager" name="projectManager">
																	<option value="">Select Project Manager</option>
																		<?php 
																			$query66 = "SELECT id,user_firstname,user_lastname FROM sys_usertb WHERE user_statusid = '1'";
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
													<div class="col-lg-3">
														<div class="form-group">
															<label class="block clearfix">Project Status
																<span class="block input-icon input-icon-left">
																	<select class="form-control" id="projectStatus2" name="projectStatus2">
																	<option value="">Select Status</option>
																		<?php 
																			$query66 = "SELECT * FROM sys_taskstatustb";
																			$result66 = mysqli_query($conn, $query66);
																			while($row66 = mysqli_fetch_assoc($result66)){
																				$statusname=mb_convert_case($row66['statusname'], MB_CASE_TITLE, "UTF-8");
																				$statusid=$row66['id'];
																		?>
																		<option value="<?php echo $statusid;?>"><?php echo $statusname;?></option>
																		<?php } ?>
																	</select>
																</span>
															</label>
														</div>	
													</div>
													<div class="col-lg-2">
														<div class="form-group">
															<label class="block clearfix">Date Started From
																<span class="block input-icon input-icon-left">
																	<i class="ace-icon fa fa-calendar"></i>
																	<input class="form-control date-picker" name="datestartedfrom" id="datestartedfrom" type="text" data-date-format="yyyy-mm-dd" />
																</span>
															</label>
														</div>	
													</div>
													<div class="col-lg-2">
														<div class="form-group"> 
															<label class="block clearfix">Date Started To
																<span class="block input-icon input-icon-left">
																	<i class="ace-icon fa fa-calendar"></i>
																	<input class="form-control date-picker" name="datestartedto" id="datestartedto" type="text" data-date-format="yyyy-mm-dd" />
																</span>
															</label>
														</div>	
													</div>
													<div class="col-lg-2">
														<div class="form-group">
															<label class="block clearfix">Expected End Date From
																<span class="block input-icon input-icon-left">
																	<i class="ace-icon fa fa-calendar"></i>
																	<input class="form-control date-picker" name="datewillendfrom" id="datewillendfrom" type="text" data-date-format="yyyy-mm-dd" />
																</span>
															</label>
														</div>	
													</div>
													<div class="col-lg-2">
														<div class="form-group">
															<label class="block clearfix">Expected End Date To
																<span class="block input-icon input-icon-left">
																	<i class="ace-icon fa fa-calendar"></i>
																	<input class="form-control date-picker" name="datewillendto" id="datewillendto" type="text" data-date-format="yyyy-mm-dd" />
																</span>
															</label>
														</div>	
													</div>
													<div class="col-lg-3">
														<div class="form-group">
															<label class="block clearfix"> &nbsp;
																<button  type="button" class="btn btn-outline btn-primary btn-sm btn-block " name="searchBtnProject" id="searchBtnProject">Search</button>
															</label>
														</div>	
													</div>
													
												</div>	
											</fieldset>
										</form>
											<div id="flash1"></div>
											<div id="insert_search1" align="left"></div>
									</div>
									<div class="tab-pane fade in" id="taskReports">
										<form id="taskreports">
											<fieldset>
												<div class="row">
													<div class="col-lg-3">
														<div class="form-group">
															<label class="block clearfix">Subject
																<span class="block input-icon input-icon-left">
																	<i class="ace-icon fa fa-user"></i>
																	<input class="form-control" placeholder="Subject" id="subjectName" name="subjectName" />
																</span>
															</label>
														</div>	
													</div>
													<div class="col-lg-3">
														<div class="form-group">
															<label class="block clearfix">Project Owner
															<span class="block input-icon input-icon-left">
																<select class="form-control" id="projectOwnerId2" name="projectOwnerId2">
																	<option value="">Owner</option>
																	<?php 
																		$query66 = "SELECT id,projectname FROM sys_projecttb";
																		$result66 = mysqli_query($conn, $query66);
																		while($row66 = mysqli_fetch_assoc($result66)){
																			$clientname=mb_convert_case($row66['projectname'], MB_CASE_TITLE, "UTF-8");
																			$clientid=$row66['id'];
																	?>
																	<option value="<?php echo $clientid;?>"><?php echo $clientname;?></option>
																	<?php } ?>
																</select>
															</span>
															</label>
														</div>	
													</div>
													<div class="col-lg-3">
														<div class="form-group">
															<label class="block clearfix">Classification
																<span class="block input-icon input-icon-left">
																	<select class="form-control" id="projectClassification" name="projectClassification">
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
													<div class="col-lg-3">
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
													<div class="col-lg-3">
														<div class="form-group">
															<label class="block clearfix">Target Date From
																<span class="block input-icon input-icon-left">
																	<i class="ace-icon fa fa-calendar"></i>
																	<input class="form-control date-picker" name="targetdatefrom" id="targetdatefrom" type="text" data-date-format="yyyy-mm-dd" />
																</span>
															</label>
														</div>	
													</div>
													<div class="col-lg-3">
														<div class="form-group"> 
															<label class="block clearfix">Target Date To
																<span class="block input-icon input-icon-left">
																	<i class="ace-icon fa fa-calendar"></i>
																	<input class="form-control date-picker" name="targetdateto" id="targetdateto" type="text" data-date-format="yyyy-mm-dd" />
																</span>
															</label>
														</div>	
													</div>
													<div class="col-lg-3">
														<div class="form-group">
															<label class="block clearfix">Start Date From
																<span class="block input-icon input-icon-left">
																	<i class="ace-icon fa fa-calendar"></i>
																	<input class="form-control date-picker" name="startdatefrom" id="startdatefrom" type="text" data-date-format="yyyy-mm-dd" />
																</span>
															</label>
														</div>	
													</div>
													<div class="col-lg-3">
														<div class="form-group">
															<label class="block clearfix">Start Date To
																<span class="block input-icon input-icon-left">
																	<i class="ace-icon fa fa-calendar"></i>
																	<input class="form-control date-picker" name="startdateto" id="startdateto" type="text" data-date-format="yyyy-mm-dd" />
																</span>
															</label>
														</div>	
													</div>
													<div class="col-lg-3">
														<div class="form-group">
															<label class="block clearfix">Actual Date From
																<span class="block input-icon input-icon-left">
																	<i class="ace-icon fa fa-calendar"></i>
																	<input class="form-control date-picker" name="actualdatefrom" id="actualdatefrom" type="text" data-date-format="yyyy-mm-dd" />
																</span>
															</label>
														</div>	
													</div>
													<div class="col-lg-3">
														<div class="form-group">
															<label class="block clearfix">Actual Date To
																<span class="block input-icon input-icon-left">
																	<i class="ace-icon fa fa-calendar"></i>
																	<input class="form-control date-picker" name="actualdateto" id="actualdateto" type="text" data-date-format="yyyy-mm-dd" />
																</span>
															</label>
														</div>	
													</div>
													<div class="col-lg-3">
														<div class="form-group">
															<label class="block clearfix">Task Status
																<span class="block input-icon input-icon-left">
																	<select class="form-control" id="taskStatus" name="taskStatus">
																	<option value="">Select Status</option>
																		<?php 
																			$query66 = "SELECT * FROM sys_taskstatustb";
																			$result66 = mysqli_query($conn, $query66);
																			while($row66 = mysqli_fetch_assoc($result66)){
																				$statusname=mb_convert_case($row66['statusname'], MB_CASE_TITLE, "UTF-8");
																				$statusid=$row66['id'];
																		?>
																		<option value="<?php echo $statusid;?>"><?php echo $statusname;?></option>
																		<?php } ?>
																	</select>
																</span>
															</label>
														</div>	
													</div>
													<div class="col-lg-3">
														<div class="form-group">
															<label class="block clearfix">Task Type
																<span class="block input-icon input-icon-left">
																	<select class="form-control" id="taskType" name="taskType">
																	<option value="">Select Type</option>
																	<option value="1">Assignment</option>
																	<option value="2">Ticker</option>
																	</select>
																</span>
															</label>
														</div>	
													</div>
													<div class="col-lg-3">
														<div class="form-group">
															<label class="block clearfix"> &nbsp;
																<button  type="button" class="btn btn-outline btn-primary btn-sm btn-block " name="searchBtnTask" id="searchBtnTask">Search</button>
															</label>
														</div>	
													</div>
													
												</div>	
											</fieldset>
										</form>
											<div id="flash2"></div>
											<div id="insert_search2" align="left"></div>
									</div>
								</div>
								
								
								
							</div>
						</div>
					</div><!-- /.page-content -->
				</div>
			</div><!-- /.main-content -->

			<?php include "blocks/footer.php"; ?>

			
		</div><!-- /.main-container -->

		
	</body>
</html>

<script src="../assets/customjs/reports.js"></script>
<!-- Validator-->
<script type="text/javascript" src="../assets/customjs/bootstrapValidator.min.js"></script>  
<script type="text/javascript" src="../assets/customjs/bootstrapValidator.js"></script> 
<!-- inline scripts related to this page -->
<script type="text/javascript">
jQuery(function($) {
				
				
				//to translate the daterange picker, please copy the "examples/daterange-fr.js" contents here before initialization
				$('.date-picker').datepicker({
					autoclose: true,
					todayHighlight: true
				})
				//show datepicker when clicking on the icon
				.next().on(ace.click_event, function(){
					$(this).prev().focus();
				});
				
			
			
				
				
				$(document).one('ajaxloadstart.page', function(e) {
					autosize.destroy('textarea[class*=autosize]')
					
					$('.limiterBox,.autosizejs').remove();
					$('.daterangepicker.dropdown-menu,.colorpicker.dropdown-menu,.bootstrap-datetimepicker-widget.dropdown-menu').remove();
				});
			
			});
	$(document).ready(function() {
		// navigation click actions	
		$('.scroll-link').on('click', function(event){
			event.preventDefault();
			var sectionID = $(this).attr("data-id");
			scrollToID('#' + sectionID, 750);
		});	
		
		$('#tabUL a[href="#clientReports"]').on('click', function(event){				
				//gototop(); 
		});
	

		//-----------store tab values to has-----------------
		$('#tabUL a').click(function (e) {
			e.preventDefault();
			$(this).tab('show');
		});

		// store the currently selected tab in the hash value
		$("ul.nav-tabs > li > a").on("shown.bs.tab", function (e) {
			var id = $(e.target).attr("href").substr(1);
			window.location.hash = id;
		});
    
		 // on load of the page: switch to the currently selected tab
		var hash = window.location.hash;
		$('#tabUL a[href="' + hash + '"]').tab('show');
		// if(hash == "#projectReports") {
			// showprojectreports();
		// }
		// if(hash == "#taskReports") {
			// showtaskreports();
		// }
		
		
		
	});
</script>