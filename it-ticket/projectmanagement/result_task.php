<?php
require('../conn/db.php');
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d");	
$nowdateunix2 = date("Y-m-d h:i:s",time());	
// ERROR_REPORTING(0);

$subjectName=strtoupper(trim($_GET['subjectName']));
$projectOwnerId2=$_GET['projectOwnerId2'];
$projectClassification=$_GET['projectClassification'];
$taskPriority=$_GET['taskPriority'];
$targetdatefrom=$_GET['targetdatefrom'];
$targetdateto=$_GET['targetdateto'];
$startdatefrom=$_GET['startdatefrom'];
$startdateto=$_GET['startdateto'];
$actualdatefrom=$_GET['actualdatefrom'];
$actualdateto=$_GET['actualdateto'];
$taskStatus=$_GET['taskStatus'];
$taskType=$_GET['taskType'];
$taskAssignee = isset($_GET['taskAssignee']) ? $_GET['taskAssignee'] : null;


if (($subjectName == '')||($subjectName == null)||($subjectName== 'NULL')||($subjectName== 'null')){$subjectName=null;}
if (($projectOwnerId2 == '')||($projectOwnerId2 == null)||($projectOwnerId2== 'NULL')||($projectOwnerId2== 'null')){$projectOwnerId2=null;}
if (($projectClassification == '')||($projectClassification == null)||($projectClassification== 'NULL')||($projectClassification== 'null')){$projectClassification=null;}
if (($taskPriority == '')||($taskPriority == null)||($taskPriority== 'NULL')||($taskPriority== 'null')){$taskPriority=null;}
if (($targetdatefrom == '')||($targetdatefrom == null)||($targetdatefrom== 'NULL')||($targetdatefrom== 'null')){$targetdatefrom=null;}
if (($targetdateto == '')||($targetdateto == null)||($targetdateto== 'NULL')||($targetdateto== 'null')){$targetdateto=null;}
if (($startdatefrom == '')||($startdatefrom == null)||($startdatefrom== 'NULL')||($startdatefrom== 'null')){$startdatefrom=null;}
if (($startdateto == '')||($startdateto == null)||($startdateto== 'NULL')||($startdateto== 'null')){$startdateto=null;}
if (($actualdatefrom == '')||($actualdatefrom == null)||($actualdatefrom== 'NULL')||($actualdatefrom== 'null')){$actualdatefrom=null;}
if (($actualdateto == '')||($actualdateto == null)||($actualdateto== 'NULL')||($actualdateto== 'null')){$actualdateto=null;}
if (($taskStatus == '')||($taskStatus == null)||($taskStatus== 'NULL')||($taskStatus== 'null')){$taskStatus=null;}
if (($taskType == '')||($taskType == null)||($taskType== 'NULL')||($taskType== 'null')){$taskType=null;}
if (($taskAssignee == '')||($taskAssignee == null)||($taskAssignee== 'NULL')||($taskAssignee== 'null')){$taskAssignee=null;}




$rest="";
	
	
	if($subjectName!="")
	 {
		if($rest==""){$rest.=" WHERE pm_projecttasktb.subject LIKE '%$subjectName%' ";}
		else
		{$rest.=" AND pm_projecttasktb.subject  LIKE '%$subjectName%'  ";}
	}
	if($projectOwnerId2!="")
	 {
		if($rest==""){$rest.=" WHERE pm_projecttasktb.projectid = '$projectOwnerId2' ";}
		else
		{$rest.=" AND pm_projecttasktb.projectid  = '$projectOwnerId2'  ";}
	}
	if($projectClassification!="")
	 {
		if($rest==""){$rest.=" WHERE pm_projecttasktb.classificationid = '$projectClassification' ";}
		else
		{$rest.=" AND pm_projecttasktb.classificationid  = '$projectClassification'  ";}
	}
	if($taskPriority!="")
	 {
		if($rest==""){$rest.=" WHERE pm_projecttasktb.priorityid = '$taskPriority' ";}
		else
		{$rest.=" AND pm_projecttasktb.priorityid  = '$taskPriority'  ";}
	}
	
	if($targetdatefrom!="")
	 {
		 if (($targetdateto == '')||($targetdateto == null)||($targetdateto== 'NULL')||($targetdateto== 'null')){$targetdateto=$nowdateunix;}
		if($rest==""){$rest.=" WHERE pm_projecttasktb.deadline >= '$targetdatefrom' AND pm_projecttasktb.deadline <= '$targetdateto' ";}
		else
		{$rest.=" AND pm_projecttasktb.deadline >= '$targetdatefrom'  AND pm_projecttasktb.deadline <= '$targetdateto' ";}
	}
	if($startdatefrom!="")
	 {
		 if (($startdateto == '')||($startdateto == null)||($startdateto== 'NULL')||($startdateto== 'null')){$startdateto=$nowdateunix;}
		if($rest==""){$rest.=" WHERE pm_projecttasktb.startdate >= '$startdatefrom' AND pm_projecttasktb.startdate <= '$startdateto' ";}
		else
		{$rest.=" AND pm_projecttasktb.startdate >= '$startdatefrom'  AND pm_projecttasktb.startdate <= '$startdateto' ";}
	}
	if($actualdatefrom!="")
	 {
		 if (($actualdateto == '')||($actualdateto == null)||($actualdateto== 'NULL')||($actualdateto== 'null')){$actualdateto=$nowdateunix;}
		if($rest==""){$rest.=" WHERE pm_projecttasktb.enddate >= '$actualdatefrom' AND pm_projecttasktb.enddate <= '$actualdateto' ";}
		else
		{$rest.=" AND pm_projecttasktb.enddate >= '$actualdatefrom'  AND pm_projecttasktb.enddate <= '$actualdateto' ";}
	}
	if($taskStatus!="")
	 {
		if($rest==""){$rest.=" WHERE pm_projecttasktb.statusid = '$taskStatus' ";}
		else
		{$rest.=" AND pm_projecttasktb.statusid  = '$taskStatus'  ";}
	}
	if($taskType!="")
	 {
		if($rest==""){$rest.=" WHERE pm_projecttasktb.istask = '$taskType' ";}
		else
		{$rest.=" AND pm_projecttasktb.istask  = '$taskType'  ";}
	}
	if($taskAssignee!="")
	 {
		if($rest==""){$rest.=" WHERE pm_taskassigneetb.assigneeid = '$taskAssignee' ";}
		else
		{$rest.=" AND pm_taskassigneetb.assigneeid = '$taskAssignee' ";}
	}
	
	
	// echo "Rest:".$rest."</br>";
?>
<div class="row">
	<div class="col-xs-12">
		<!-- PAGE CONTENT BEGINS -->
		<div class="row">
			<div class="col-xs-12">
				<a href="exportResult_task.php?searchstring=<?php echo $rest; ?>" target="_blank" class="btn btn-sm bg-danger">Export to Excel</a> 
				<table id="simple-table" class="table  table-bordered table-hover">
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
							<th>Resolution</th>
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
										   sys_projecttb.projectname,pm_projecttasktb.percentdone,pm_taskassigneetb.assigneeid,pm_projecttasktb.resolution
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
							<td><?php 
								echo (isset($row['resolution']) && !empty($row['resolution']) && $row['resolution'] !== 'NULL') 
									? strip_tags(htmlspecialchars_decode($row['resolution'])) 
									: 'No resolution yet'; 
							?></td>
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