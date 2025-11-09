<?php
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d h:i:s",time());	
include "blocks/header.php";
$moduleid=1;
include('proxy.php');

		$rest="";	
		//if($mycompanyid!='0')
		// {
		//	if($rest==""){$rest.="AND sys_projecttb.clientid = '$mycompanyid'";}
		//	else
		//	{$rest.=" AND sys_projecttb.clientid = '$mycompanyid'";}
		//}
		//else{
			$rest.="";
		//}

		$rest2="";	

		if($userlevelid == 1){
			$rest2.="";
		}else{
			$rest2.=" AND pm_projecttasktb.createdbyid = '$userid'";
		}

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
				<div id="myModal_createTicket" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="false"></div>	
				<div id="myModal_updateTicket" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="false"></div>	
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
							<li>Ticket Management </li>
							<li class="active"><button class="btn btn-purple input-xs" onclick="showcreateticket(0);" data-toggle="modal"  data-target="#myModal_createTicket" <?php if($authoritystatusidz == 2){echo "disabled";}?>><i class="ace-icon fa fa-floppy-o bigger-120"></i>Create Ticket</button></li>
						</ul><!-- /.breadcrumb -->

					</div>

					<div class="page-content">
						<div class="row">
							<div class="col-xs-12">
								
							</div>
							<div class="col-xs-12">
								<ul class="nav nav-tabs" id="tabUL">
									<li class="active"><a href="#ticketlist" data-toggle="tab">New</a></li>
									<li><a href="#ticketprogress" data-toggle="tab">In-progress</a></li>
									<li><a href="#ticketpending" data-toggle="tab">Pending</a></li>
									<li><a href="#ticketreject" data-toggle="tab">Rejected</a></li>
									<li><a href="#ticketcancelled" data-toggle="tab">Cancelled</a></li>
									<li><a href="#ticketdone" data-toggle="tab">Done</a></li>
								</ul>
								<div class="tab-content">
									<div class="tab-pane fade in active" id="ticketlist">
										<div class="table-responsive">
											<table class="table table-striped table-bordered table-hover" id="dataTables-myticket">
												<thead>
													<tr>
														<th>Task ID</th>
														<th>Created By</th>
														<th>Tracker</th>
														<th>Status</th>
														<th>Priority</th>
														<th>Subject</th>
														<th>Project</th>
														<th>Deadline(expected)</th>
														<!--<th>Duration</th>-->
														<th>Rate</th>
														<th>Action</th>
													</tr>
												</thead>
												<tbody>
												<?php 
												$conn = connectionDB();
												$counter = 1;
												$myquery = "SELECT pm_projecttasktb.id,pm_projecttasktb.srn_id,pm_projecttasktb.subject,pm_projecttasktb.description,pm_projecttasktb.deadline,
																   pm_projecttasktb.startdate,pm_projecttasktb.enddate,pm_projecttasktb.classificationid,
																   pm_projecttasktb.statusid,pm_projecttasktb.priorityid,pm_projecttasktb.createdbyid,pm_projecttasktb.datetimecreated,
																   pm_projecttasktb.assignee,pm_projecttasktb.projectid,
																   sys_taskclassificationtb.classification,sys_taskstatustb.statusname,sys_priorityleveltb.priorityname,
																   sys_projecttb.projectname,pm_projecttasktb.percentdone,sys_projecttb.clientid
															FROM pm_projecttasktb
															LEFT JOIN sys_taskclassificationtb ON sys_taskclassificationtb.id=pm_projecttasktb.classificationid
															LEFT JOIN sys_taskstatustb ON sys_taskstatustb.id=pm_projecttasktb.statusid
															LEFT JOIN sys_priorityleveltb ON sys_priorityleveltb.id=pm_projecttasktb.priorityid
															LEFT JOIN sys_projecttb ON sys_projecttb.id=pm_projecttasktb.projectid
															WHERE pm_projecttasktb.istask = '2' $rest AND pm_projecttasktb.statusid IN ('1') $rest2
															ORDER BY pm_projecttasktb.datetimecreated ASC";
												$myresult = mysqli_query($conn, $myquery);
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
													if(($myTaskEnddate == null)||($myTaskEnddate == '1900-01-1')){$ts2 = strtotime($nowdateunix);}else{$ts2 = strtotime($myTaskEnddate);}

													// $seconds_diff = $ts2 - $ts1;
													$seconds_diff = date("d",$ts2 - $ts1); 
													 // from today
													if($myTaskStatusid == 6){$myClass = 'success';}elseif($myTaskStatusid == 2){$myClass = 'info';}elseif($myTaskStatusid == 3){$myClass = 'warning';}else{$myClass = 'danger';}
												?>
													<tr>
														<td><?php echo $row['srn_id'];?></td>
														<td>
															<?php 
																$createdbyid = mysqli_real_escape_string($conn, $row['createdbyid']);
																$user_query = mysqli_query($conn, "SELECT user_firstname, user_lastname FROM sys_usertb WHERE id = '$createdbyid'");
																if($user_query) {
																	$user_data = mysqli_fetch_assoc($user_query);
																	echo $user_data ? $user_data['user_firstname'].' '.$user_data['user_lastname'] : 'Unknown User';
																} else {
																	echo 'Error: ' . mysqli_error($conn);
																}
															?>
														</td>
														<td><?php echo $row['classification'];?></td>
														<td><?php echo $row['statusname'];?></td>
														<td><?php echo $row['priorityname'];?></td>
														<td><?php echo $row['subject'];?></td>
														<td><?php echo $row['projectname'];?></td>
														<td><?php echo "startd:".$row['startdate']."</br>"."deadline:".$row['deadline']."</br>"."deadline:".$row['enddate'];?></td>
														<!--<td><?php echo $seconds_diff." days";?></td>-->
														<td><?php echo $myEvaluation;?></td>
														<td>
															<div class="btn-group">
																<button type="button" class="btn btn-xs btn-info" onclick="showUpdateTicket('<?php echo $taskId;?>');" data-toggle="modal" data-target="#myModal_updateTicket">
																	<i class="ace-icon fa fa-pencil bigger-120"></i> Edit
																</button>
																<a href="../projectmanagement/threadPage.php?taskId=<?php echo $taskId; ?>" class="btn btn-xs btn-danger" onclick="event.stopPropagation();">
																	<i class="ace-icon fa fa-arrow-right icon-on-right"></i> Open Thread
																</a>
															</div>
														</td>
													</tr>
												<?php
													$counter ++;
													}
												?>
												</tbody>
											</table>
										</div>
									</div>
									<div class="tab-pane fade in" id="ticketprogress">
										<div id="insert_ticketprocess"></div>					                    	
										<div id="insertbody_ticketprocess"></div>
									</div>
									<div class="tab-pane fade in" id="ticketpending">
										<div id="insert_ticketpending"></div>					                    	
										<div id="insertbody_ticketpending"></div>
									</div>
									<div class="tab-pane fade in" id="ticketreject">
										<div id="insert_ticketrejected"></div>					                    	
										<div id="insertbody_ticketrejected"></div>
									</div>
									<div class="tab-pane fade in" id="ticketcancelled">
										<div id="insert_ticketcancelled"></div>					                    	
										<div id="insertbody_ticketcancelled"></div>
									</div>
									<div class="tab-pane fade in" id="ticketdone">
										<div id="insert_ticketdone"></div>					                    	
										<div id="insertbody_ticketdone"></div>
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

<script src="../assets/customjs/ticketing.js"></script>
<!-- Validator-->
<script type="text/javascript" src="../assets/customjs/bootstrapValidator.min.js"></script>  
<script type="text/javascript" src="../assets/customjs/bootstrapValidator.js"></script> 
<!-- inline scripts related to this page -->
<script type="text/javascript">
 $(document).ready(function() {
        $('#dataTables-myticket').DataTable({
            "aaSorting": [],
			"scrollCollapse": true,	
			"autoWidth": true,
			"responsive": true,
			"bSort" : true,
			"lengthMenu": [ [10, 25, 50, 100, 200, 300, 400, 500], [10, 25, 50, 100, 200, 300, 400, 500] ]
        });
	});
	$(document).ready(function() {
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
		if(hash == "#ticketlist") {
			// showsearch();
		}
		if(hash == "#ticketprogress") {
			showticketprogress();
		}
		if(hash == "#ticketpending") {
			showticketpending();
		}
		if(hash == "#ticketreject") {
			showticketrejected();
		}
		if(hash == "#ticketcancelled") {
			showticketcancelled();
		}
		if(hash == "#ticketdone") {
			showticketdone();
		}
		
	});
	
	
	
</script>