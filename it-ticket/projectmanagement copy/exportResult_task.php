<?php
header('content-type: application/octet-stream'); 
header('content-disposition: attachment; filename=dailyattendance.xls'); 
header('content-transfer-encoding: binary'); 

require('../conn/db.php');
$nowdateunix = date("Y-m-d",time());	
$nowdateunix2 = date("Y-m-d h:i:s",time());
$rest=trim($_GET['searchstring']);
if($rest == null)
{
	$rest="";
}




?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Project Reports</title>
</head>
<body>
<div class="row">
	<div class="col-xs-12">
		<!-- PAGE CONTENT BEGINS -->
		<div class="row">
			<div class="col-xs-12">
				<table border="1">
					<thead>
						<tr>
							<th></th>
							<th>Tracker</th>
							<th>Status</th>
							<th>Priority</th>
							<th>Subject</th>
							<th>Assignee</th>
							<th>Project</th>
							<th>Deadline(expected)</th>
							<th>Duration</th>
							<th>Note</th>
							<th>Remarks</th>
						</tr>
					</thead>
					<tbody>
					<?php 
						$conn = connectionDB();
						$counter = 1;
						// WHERE pm_projecttasktb.istask = '1' AND pm_projecttasktb.statusid IN ('1','3','4','5')
						$myquery = "SELECT pm_projecttasktb.id,pm_projecttasktb.subject,pm_projecttasktb.description,pm_projecttasktb.deadline,
										   pm_projecttasktb.startdate,pm_projecttasktb.enddate,pm_projecttasktb.classificationid,
										   pm_projecttasktb.statusid,pm_projecttasktb.priorityid,pm_projecttasktb.createdbyid,pm_projecttasktb.datetimecreated,
										   pm_projecttasktb.assignee,pm_projecttasktb.projectid,
										   sys_taskclassificationtb.classification,sys_taskstatustb.statusname,sys_priorityleveltb.priorityname,
										   sys_projecttb.projectname,pm_projecttasktb.percentdone,pm_taskassigneetb.assigneeid
									FROM pm_projecttasktb
									LEFT JOIN sys_taskclassificationtb ON sys_taskclassificationtb.id=pm_projecttasktb.classificationid
									LEFT JOIN sys_taskstatustb ON sys_taskstatustb.id=pm_projecttasktb.statusid
									LEFT JOIN sys_priorityleveltb ON sys_priorityleveltb.id=pm_projecttasktb.priorityid
									LEFT JOIN sys_projecttb ON sys_projecttb.id=pm_projecttasktb.projectid
									LEFT JOIN pm_taskassigneetb ON pm_projecttasktb.id=pm_taskassigneetb.taskid
									$rest
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
							$ts2 = strtotime($nowdateunix2);

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
							<td><?php echo $counter;?></td>
							<td><?php echo $row['classification'];?></td>
							<td><?php echo $row['statusname'];?></td>
							<td><?php echo $row['priorityname'];?></td>
							<td><?php echo $row['subject'];?></td>
							<td>
								<?php
									$count = 0;
									$myqueryxx2 = "SELECT pm_taskassigneetb.taskid,pm_taskassigneetb.assigneeid,
												sys_usertb.user_firstname,sys_usertb.user_lastname
												FROM pm_taskassigneetb
												LEFT JOIN sys_usertb ON sys_usertb.id=pm_taskassigneetb.assigneeid
												WHERE pm_taskassigneetb.taskid = '$taskId' ";
									$myresultxx2 = mysqli_query($conn, $myqueryxx2);
									while($rowxx2 = mysqli_fetch_assoc($myresultxx2)){
										if ($count++ > 0) echo ",";
										echo $rowxx2['user_firstname'];
									}
								?>
							</td>
							<td><?php echo $row['projectname'];?></td>
							<td><?php echo $row['deadline'];?></td>
							<td><?php echo $seconds_diff." days";?></td>
							<td><?php echo $myEvaluation;?></td>
							<td><?php echo $row['description'];?></td>
						</tr>
					</tbody>
					<?php
						$counter ++;
							}
						?>
				</table>
			</div><!-- /.span -->
		</div><!-- /.row -->

								
	</div>
</div>
</body>
    
</html>    