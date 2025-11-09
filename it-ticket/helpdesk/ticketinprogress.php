<?php
include('../check_session.php');
$moduleid=1;
include('proxy.php');	
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d h:i:s",time());	
$conn = connectionDB();


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
<div class="table-responsive">
	<table class="table table-striped table-bordered table-hover" id="dataTables-ticketprogress">
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
				<th>Duration</th>
				<th>Rate</th>
				<th>Action</th>
			</tr>
		</thead>
		<tbody>
		<?php 
		$counter = 1;
		$myquery = "SELECT pm_projecttasktb.id,pm_projecttasktb.srn_id,pm_projecttasktb.subject,pm_projecttasktb.description,pm_projecttasktb.deadline,
						   pm_projecttasktb.startdate,pm_projecttasktb.enddate,pm_projecttasktb.classificationid,
						   pm_projecttasktb.statusid,pm_projecttasktb.priorityid,pm_projecttasktb.createdbyid,pm_projecttasktb.datetimecreated,
						   pm_projecttasktb.assignee,pm_projecttasktb.projectid,
						   sys_taskclassificationtb.classification,sys_taskstatustb.statusname,sys_priorityleveltb.priorityname,
						   sys_projecttb.projectname,pm_projecttasktb.percentdone
					FROM pm_projecttasktb
					LEFT JOIN sys_taskclassificationtb ON sys_taskclassificationtb.id=pm_projecttasktb.classificationid
					LEFT JOIN sys_taskstatustb ON sys_taskstatustb.id=pm_projecttasktb.statusid
					LEFT JOIN sys_priorityleveltb ON sys_priorityleveltb.id=pm_projecttasktb.priorityid
					LEFT JOIN sys_projecttb ON sys_projecttb.id=pm_projecttasktb.projectid
					WHERE pm_projecttasktb.istask = '2' $rest AND pm_projecttasktb.statusid IN ('3') $rest2
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
				<td><?php echo $row['deadline'];?></td>
				<td><?php echo $seconds_diff." days";?></td>
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

<script type="text/javascript">
 $(document).ready(function() {
       $('#dataTables-ticketprogress').DataTable({
            "aaSorting": [],
			"scrollCollapse": true,	
			"autoWidth": false,
			"responsive": true,
			"bSort" : true,
			"lengthMenu": [ [10, 25, 50, 100, 200, 300, 400, 500], [10, 25, 50, 100, 200, 300, 400, 500] ]
        });
	});
</script>