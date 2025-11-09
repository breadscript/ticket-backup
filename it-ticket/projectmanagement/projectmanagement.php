<?php
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d h:i:s",time());	
include "blocks/header.php";
$moduleid=4;
include('proxy.php');
// $myusergroupid;

// Function to truncate text with ellipsis
function truncateText($text, $maxLength = 30) {
    if (strlen($text) <= $maxLength) {
        return $text;
    }
    return substr($text, 0, $maxLength) . '...';
}

?>
<!DOCTYPE html>
<link rel="../stylesheet" href="../css/bootstrapValidator.min.css"/> 
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.18/css/bootstrap-select.min.css">
<style>
.input-xs {
    height: 22px;
    padding: 0px 1px;
    font-size: 10px;
    line-height: 1; //If Placeholder of the input is moved up, rem/modify this.
    border-radius: 0px;
    }

/* Add CSS for truncated text with tooltip */
.truncated-text {
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    cursor: help;
}
</style>
<html lang="en">
	<body class="no-skin">
		
		<div class="row">
            <div class="col-lg-12">
				<div id="myModal_createTask" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="false"></div>	
				<div id="myModal_updateTask" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="false"></div>	
				<div id="insert_openThread" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="false"></div>	
				<div id="showopenCreateThread" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="false"></div>	
				<input type="hidden" class="form-control" placeholder="Subject" id="user_id" name="user_id" value="<?php echo $userid;?>"/>
				<input type="hidden" class="form-control" placeholder="Subject" id="mygroup" name="mygroup" value="<?php echo $myusergroupid;?>"/>
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
							<li>Task Management </li>
							<li class="active"><button class="btn btn-purple input-xs" onclick="showcreatetask(0);" data-toggle="modal"  data-target="#myModal_createTask" <?php if($authoritystatusidz == 2){echo "disabled";}?>><i class="ace-icon fa fa-floppy-o bigger-120"></i>Create task</button></li>
						</ul><!-- /.breadcrumb -->

					</div>

					<div class="page-content">
						<div class="row">
							<div class="col-xs-8">
								<!-- This space is intentionally left empty to maintain layout -->
							</div>
							<div class="col-xs-4 text-right" style="padding-top: 5px;">
								
							</div>
							<div class="col-xs-12">
								<ul class="nav nav-tabs" id="tabUL">
									<li class="active"><a href="#taskfrompm" data-toggle="tab">Assignement</a></li>
									<li><a href="#taskfrompmdone" data-toggle="tab">Assignement(Done)</a></li>
									<li><a href="#taskfrompmreject" data-toggle="tab">Assignement(Rejected)</a></li>
									<li><a href="#taskfromticket" data-toggle="tab">Ticket</a></li>
									<li><a href="#taskfromticketdone" data-toggle="tab">Ticket(Done)</a></li>
									<li><a href="#taskfromticketreject" data-toggle="tab">Ticket(Reject)</a></li>
								</ul>
								<div class="tab-content">
									<div class="tab-pane fade in active" id="taskfrompm">
										<div class="table-responsive">
										<table class="table table-striped table-bordered table-hover" id="dataTables-myticket">
												<thead>
													<tr>
														<th>Task ID</th>
														<th>Created By</th>
														<th>Date Created</th>
														<th>Tracker</th>
														<th>Status</th>
														<th>Priority</th>
														<th>Subject</th>
														<th>Assignee</th>
														<th>Project</th>
														<th>Deadline(expected)</th>
														<th>Note</th>
														<th>Action</th>
													</tr>
												</thead>
												<tbody>
												<?php 
												$conn = connectionDB();
												$counter = 1;
												
												if($myusergroupid == 3 || $myusergroupid == 2 || $myusergroupid == 1){
													// Admin or manager can see all tasks
													$myquery = "SELECT DISTINCT pm_projecttasktb.id,pm_projecttasktb.subject,pm_projecttasktb.description,pm_projecttasktb.deadline,
																	   pm_projecttasktb.startdate,pm_projecttasktb.enddate,pm_projecttasktb.classificationid,
																	   pm_projecttasktb.statusid,pm_projecttasktb.priorityid,pm_projecttasktb.createdbyid,pm_projecttasktb.datetimecreated,
																	   pm_projecttasktb.assignee,pm_projecttasktb.projectid,
																	   sys_taskclassificationtb.classification,sys_taskstatustb.statusname,sys_priorityleveltb.priorityname,
																	   sys_projecttb.projectname,pm_projecttasktb.percentdone,pm_projecttasktb.srn_id
																FROM pm_projecttasktb
																LEFT JOIN sys_taskclassificationtb ON sys_taskclassificationtb.id=pm_projecttasktb.classificationid
																LEFT JOIN sys_taskstatustb ON sys_taskstatustb.id=pm_projecttasktb.statusid
																LEFT JOIN sys_priorityleveltb ON sys_priorityleveltb.id=pm_projecttasktb.priorityid
																LEFT JOIN sys_projecttb ON sys_projecttb.id=pm_projecttasktb.projectid
																WHERE pm_projecttasktb.istask = '1' AND pm_projecttasktb.statusid IN ('1','3','4','5')";
													if(isset($_GET['filter_user']) && !empty($_GET['filter_user'])) {
														$filter_user = mysqli_real_escape_string($conn, $_GET['filter_user']);
														if($filter_user == 'unassigned') {
															$myquery .= " AND (pm_projecttasktb.assignee IS NULL OR pm_projecttasktb.assignee = '')";
														} else {
															$myquery .= " AND (
																pm_projecttasktb.createdbyid = '$filter_user' OR
																pm_projecttasktb.assignee = '$filter_user' OR 
																pm_projecttasktb.assignee LIKE '$filter_user,%' OR 
																pm_projecttasktb.assignee LIKE '%,$filter_user,%' OR 
																pm_projecttasktb.assignee LIKE '%,$filter_user'
															)";
														}
													}
													$myquery .= " ORDER BY pm_projecttasktb.datetimecreated ASC";
													$myresult = mysqli_query($conn, $myquery);
												}else{
													// Regular users only see their assigned tasks
													$myquery2 = "SELECT taskid FROM pm_taskassigneetb WHERE assigneeid = '$userid' ";
													$myresult2 = mysqli_query($conn, $myquery2);
													
													// Build an array of task IDs
													$taskIds = array();
													while($row2 = mysqli_fetch_assoc($myresult2)){
														$taskIds[] = $row2['taskid'];
													}
													
													// If we have task IDs, create a query with all of them
													if(!empty($taskIds)) {
														$taskIdList = implode(',', $taskIds);
														$myquery = "SELECT DISTINCT pm_projecttasktb.id,pm_projecttasktb.subject,pm_projecttasktb.description,pm_projecttasktb.deadline,
																	   pm_projecttasktb.startdate,pm_projecttasktb.enddate,pm_projecttasktb.classificationid,
																	   pm_projecttasktb.statusid,pm_projecttasktb.priorityid,pm_projecttasktb.createdbyid,pm_projecttasktb.datetimecreated,
																	   pm_projecttasktb.assignee,pm_projecttasktb.projectid,
																	   sys_taskclassificationtb.classification,sys_taskstatustb.statusname,sys_priorityleveltb.priorityname,
																	   sys_projecttb.projectname,pm_projecttasktb.percentdone,pm_projecttasktb.srn_id
																FROM pm_projecttasktb
																LEFT JOIN sys_taskclassificationtb ON sys_taskclassificationtb.id=pm_projecttasktb.classificationid
																LEFT JOIN sys_taskstatustb ON sys_taskstatustb.id=pm_projecttasktb.statusid
																LEFT JOIN sys_priorityleveltb ON sys_priorityleveltb.id=pm_projecttasktb.priorityid
																LEFT JOIN sys_projecttb ON sys_projecttb.id=pm_projecttasktb.projectid
																WHERE pm_projecttasktb.istask = '1' AND pm_projecttasktb.id IN ($taskIdList) AND pm_projecttasktb.statusid IN ('1','3','4','5')";
														if(isset($_GET['filter_user']) && !empty($_GET['filter_user'])) {
															$filter_user = mysqli_real_escape_string($conn, $_GET['filter_user']);
															if($filter_user == 'unassigned') {
																$myquery .= " AND (pm_projecttasktb.assignee IS NULL OR pm_projecttasktb.assignee = '')";
															} else {
																$myquery .= " AND (
																	pm_projecttasktb.createdbyid = '$filter_user' OR
																	pm_projecttasktb.assignee = '$filter_user' OR 
																	pm_projecttasktb.assignee LIKE '$filter_user,%' OR 
																	pm_projecttasktb.assignee LIKE '%,$filter_user,%' OR 
																	pm_projecttasktb.assignee LIKE '%,$filter_user'
																)";
															}
														}
														$myquery .= " ORDER BY pm_projecttasktb.datetimecreated ASC";
														$myresult = mysqli_query($conn, $myquery);
													} else {
														// No tasks found
														$myresult = false;
													}
												}
												
												// Check if we have results before trying to fetch
												if($myresult && mysqli_num_rows($myresult) > 0) {
													while($row = mysqli_fetch_assoc($myresult)){
														$taskId = $row['id'];	
														$myTaskStatusid = $row['statusid'];	
														$myTaskDeadline = $row['deadline'];	
														$myTaskStartdate = $row['startdate'];		
														$myTaskEnddate = $row['enddate'];	
														$myTaskPercentage = $row['percentdone'];	
														if($myTaskPercentage == 1){
															$myEvaluation = "Exact Delivery";	//exact
														}elseif($myTaskPercentage == 2){
															$myEvaluation = "Late Delivery";	//late
														}elseif($myTaskPercentage == 3){
															$myEvaluation = "Ahead Delivery";	//aheadoftime
														}else{
															$myEvaluation = "In-progress";	//aheadoftime
														}
														/*------get hours---------*/
														$ts1 = strtotime($myTaskStartdate);
														// $ts2 = strtotime($nowdateunix);
														if(($myTaskEnddate == null)||($myTaskEnddate == '1900-01-01')){$ts2 = strtotime($nowdateunix);}else{$ts2 = strtotime($myTaskEnddate);echo "trueee";}

														// $seconds_diff = $ts2 - $ts1;
														// $seconds_diff = date("d",$ts2 - $ts1); 
														// $seconds_diff = $ts2 - $ts1;
														if($ts1 > $ts2){
															$seconds_diff = date("d",$ts1 - $ts2); 
														}elseif($ts1 < $ts2){
															$seconds_diff = date("d",$ts2 - $ts1); 
														}
														 // from today
														if($myTaskStatusid == 6){$myClass = 'success';}elseif($myTaskStatusid == 2){$myClass = 'info';}elseif($myTaskStatusid == 3){$myClass = 'warning';}else{$myClass = 'danger';}
													?>
														<tr>
															<td>
																<span class="srn-value"><?php echo htmlspecialchars($row['srn_id']);?></span>
																<button class="btn btn-xs btn-copy" onclick="copySrn(this, '<?php echo htmlspecialchars($row['srn_id']);?>')" style="background: transparent; border: none; color: #666; padding: 2px 5px;">
																	<i class="ace-icon fa fa-link"></i>
																</button>
															</td>
															<td>
																<?php 
																	$createdbyid = mysqli_real_escape_string($conn, $row['createdbyid']);
																	$user_query = mysqli_query($conn, "SELECT user_firstname, user_lastname FROM sys_usertb WHERE id = '$createdbyid'");
																	if($user_query) {
																		$user_data = mysqli_fetch_assoc($user_query);
																		$creatorName = $user_data ? $user_data['user_firstname'].' '.$user_data['user_lastname'] : 'Unknown User';
																		echo '<div class="truncated-text" title="' . htmlspecialchars($creatorName) . '">' . truncateText($creatorName, 20) . '</div>';
																	} else {
																		echo '<div class="truncated-text" title="Error: ' . mysqli_error($conn) . '">' . truncateText('Error: ' . mysqli_error($conn), 20) . '</div>';
																	}
																?>
															</td>											
															<td>
																<div class="truncated-text" title="<?php echo date('M d, Y h:i A', strtotime($row['datetimecreated'])); ?>">
																	<?php echo truncateText(date('M d, Y h:i A', strtotime($row['datetimecreated'])), 18); ?>
																</div>
															</td>
															<td><?php echo $row['classification'];?></td>
															<td><?php echo $row['statusname'];?></td>
															<td><?php echo $row['priorityname'];?></td>
															<td>
																<div class="truncated-text" title="<?php echo htmlspecialchars($row['subject']); ?>">
																	<?php echo truncateText($row['subject'], 30); ?>
																</div>
															</td>
															<td>
																<?php
																	$assigneeIds = explode(',', $row['assignee']); // Assuming $row['assignee'] is a comma-separated string of IDs
																	$assigneeNames = [];

																	foreach ($assigneeIds as $assigneeId) {
																		$assigneeQuery = "SELECT user_firstname, user_lastname FROM sys_usertb WHERE id = '$assigneeId'";
																		$assigneeResult = mysqli_query($conn, $assigneeQuery);
																		if ($assigneeRow = mysqli_fetch_assoc($assigneeResult)) {
																			$assigneeNames[] = '* ' . implode(' ', [$assigneeRow['user_firstname'], $assigneeRow['user_lastname']]);
																		} else {
																			$assigneeNames[] = "* Unknown Assignee";
																		}
																	}

																	$assigneeText = implode('<br>', $assigneeNames);
																	echo '<div class="truncated-text" title="' . htmlspecialchars(strip_tags($assigneeText)) . '">' . truncateText(strip_tags($assigneeText), 25) . '</div>';
																?>
															</td>
															<td>
																<div class="truncated-text" title="<?php echo htmlspecialchars($row['projectname']); ?>">
																	<?php echo truncateText($row['projectname'], 20); ?>
																</div>
															</td>
															<td><?php
																	echo $row['deadline'];
																	// echo "deadline:".$row['deadline']."</br>";
																	// echo "startdate:".$row['startdate']."</br>";
																	// echo "enddate:".$row['enddate']."</br>";
															?></td>
															<!--<td><?php echo $seconds_diff." days";?></td>-->
															<td>
																<div class="truncated-text" title="<?php echo htmlspecialchars($myEvaluation); ?>">
																	<?php echo truncateText($myEvaluation, 15); ?>
																</div>
															</td>
															<td>
																<div class="btn-group">
																	<button type="button" class="btn btn-xs btn-info" onclick="showUpdateTask('<?php echo $taskId;?>');" data-toggle="modal" data-target="#myModal_updateTask">
																		<i class="ace-icon fa fa-pencil bigger-120"></i> Edit
																	</button>
																	<a href="threadPage.php?taskId=<?php echo $taskId; ?>" class="btn btn-xs btn-danger">
																		<i class="ace-icon fa fa-arrow-right icon-on-right"></i> >>
																	</a>
																</div>
															</td>
														</tr>
													<?php
														$counter ++;
													}
												} else {
													// Empty state - use a single cell that spans all columns
													echo '<tr><td colspan="11" class="text-center">No active tasks found</td></tr>';
												}
												?>
												</tbody>
											</table>
										</div>
									</div>
									<div class="tab-pane fade in" id="taskfrompmdone">
										<div id="insert_assignmentdone"></div>					                    	
										<div id="insertbody_assignmentdone"></div>
									</div>
									<div class="tab-pane fade in" id="taskfrompmreject">
										<div id="insert_taskreject"></div>					                    	
										<div id="insertbody_taskreject"></div>
									</div>
									<div class="tab-pane fade in" id="taskfromticket">
									
										<div id="insert_tickets"></div>					                    	
										<div id="insertbody_tickets"></div>
									</div>
									<div class="tab-pane fade in" id="taskfromticketdone">
										<div id="insert_taskdone"></div>					                    	
										<div id="insertbody_taskdone"></div>
									</div>
									<div class="tab-pane fade in" id="taskfromticketreject">
										<div id="insert_ticketreject"></div>					                    	
										<div id="insertbody_ticketreject"></div>
									</div>
								</div>
								
								
								
							</div>
						</div>
					</div><!-- /.page-content -->
				</div>
			</div><!-- /.main-content -->

			<?php include "blocks/footer.php"; ?>

			
		</div><!-- /.main-container -->

		<!-- Add these scripts before your custom scripts -->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.18/js/bootstrap-select.min.js"></script>
		
		<!-- Then your existing scripts -->
		<script src="../assets/customjs/projectmanagement.js"></script>
		<script type="text/javascript" src="../assets/customjs/bootstrapValidator.min.js"></script>  
		<script type="text/javascript" src="../assets/customjs/bootstrapValidator.js"></script> 
		
		<!-- Your inline scripts -->
		<script type="text/javascript">
			$(document).ready(function() {
				// Check if we have a "No active tasks found" message
				var noTasksMessage = $('#dataTables-myticket tbody tr td:first-child').text().trim() === 'No active tasks found';
				
				// Only initialize DataTables if there are actual data rows
				if (!noTasksMessage && $('#dataTables-myticket tbody tr').length > 0) {
					try {
						// Destroy existing DataTable instance if it exists
						if ($.fn.DataTable.isDataTable('#dataTables-myticket')) {
							$('#dataTables-myticket').DataTable().destroy();
						}
						
						// Initialize DataTables with full features
						$('#dataTables-myticket').DataTable({
							"aaSorting": [],
							"scrollCollapse": true,    
							"autoWidth": true,
							"responsive": true,
							"bSort": true,
							"lengthMenu": [[10, 25, 50, 100, 200, 300, 400, 500], [10, 25, 50, 100, 200, 300, 400, 500]]
						});
					} catch (e) {
						console.error("DataTables initialization error:", e);
					}
				} else {
					// For empty tables, remove DataTable class and add simple styling
					$('#dataTables-myticket').removeClass('dataTable').addClass('simple-table');
					// Hide DataTables elements that might have been created
					$('.dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate').hide();
				}
				
				// navigation click actions	
				$('.scroll-link').on('click', function(event){
					event.preventDefault();
					var sectionID = $(this).attr("data-id");
					scrollToID('#' + sectionID, 750);
				});	
				
				$('#tabUL a[href="#taskfrompm"]').on('click', function(event){				
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
				if(hash == "#taskfrompmdone") {
					showtasksfrompmdone();
				}
				if(hash == "#taskfromticket") {
					showtasksfromticket();
				}
				if(hash == "#taskfromticketdone") {
					showtasksticketdone();
				}
				if(hash == "#taskfrompmreject") {
					showtasksfrompmreject();
				}
				if(hash == "#taskfromticketreject") {
					showtasksticketreject();
				}
				
				
				
			});

			// Assignee filter functionality
			$('#assigneeFilter').on('change', function() {
				var selectedAssignee = $(this).val();
				var currentUrl = window.location.href;
				
				// Remove existing filter_user parameter if it exists
				currentUrl = currentUrl.replace(/([&\?])filter_user=[^&]+(&|$)/g, '$1');
				
				// Clean up the URL by removing any empty parameters and duplicate separators
				currentUrl = currentUrl.replace(/\?&/g, '?').replace(/&&/g, '&');
				
				// Add the new filter parameter
				if (selectedAssignee) {
					if (currentUrl.indexOf('?') > -1) {
						currentUrl += '&filter_user=' + selectedAssignee;
					} else {
						currentUrl += '?filter_user=' + selectedAssignee;
					}
				}
				
				// Remove trailing & or ? if present
				currentUrl = currentUrl.replace(/[&\?]$/g, '');
				
				// Redirect to the new URL
				window.location.href = currentUrl;
			});
		</script>

		<script>
				function copySrn(button, srn) {
					// Clean: remove "SRN:" prefix and keep only the numeric part
					var cleaned = String(srn).replace(/^\s*SRN:\s*/i, '').trim();
					var match = cleaned.match(/\d+/);
					var toCopy = match ? match[0] : cleaned;

					navigator.clipboard.writeText(toCopy)
						.then(() => {
							const notification = document.createElement('div');
							notification.textContent = 'SRN Copied!';
							notification.style.position = 'absolute';
							notification.style.padding = '5px 10px';
							notification.style.backgroundColor = '#4CAF50';
							notification.style.color = 'white';
							notification.style.borderRadius = '3px';
							notification.style.zIndex = '9999';
							notification.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
							notification.style.fontSize = '12px';
							
							const rect = button.getBoundingClientRect();
							const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
							const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
							notification.style.top = (rect.top + scrollTop - 5) + 'px';
							notification.style.left = (rect.right + scrollLeft + 5) + 'px';
							
							document.body.appendChild(notification);
							setTimeout(() => document.body.removeChild(notification), 2000);
						})
						.catch(err => { console.error('Could not copy SRN: ', err); });
				}
		</script>


	</body>
</html>