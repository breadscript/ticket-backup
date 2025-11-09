<?php
require('../conn/db.php');
$conn = connectionDB();
$moduleid=4;

$nowdateunix = date("Y-m-d",time());	

function alphanumericAndSpace( $string ) {
   return preg_replace( "/[^,;a-zA-Z0-9 _-]|[,; ]$/s", "", $string );
}

$taskIdUp=strtoupper($_GET['taskIdUp2']);
$taskUseridUp=strtoupper($_GET['taskUseridUp2']);
$projectOwnerUp=strtoupper($_GET['projectOwnerUp2']);
$taskClassificationUp=strtoupper($_GET['taskClassificationUp2']);
$taskPriorityUp=strtoupper($_GET['taskPriorityUp2']);
$taskSubjectUp=urldecode($_GET['taskSubjectUp2']);
$taskAssigneeUp=strtoupper($_GET['taskAssigneeUp2']);
$taskTargetDateUp=strtoupper($_GET['taskTargetDateUp2']);	//deadline
$taskStartDateUp=strtoupper($_GET['taskStartDateUp2']);		//startdate
$taskEndDateUp=strtoupper($_GET['taskEndDateUp2']);			//actualend
$descriptionupdate=urldecode($_GET['descriptionupdate2']);
$taskStatus=strtoupper($_GET['taskStatus2']);


$myquery = "SELECT enddate,statusid FROM pm_projecttasktb WHERE id = '$taskIdUp'";
$myresult = mysqli_query($conn, $myquery);
while($row = mysqli_fetch_assoc($myresult)){
	$currentEndDate = $row['enddate'];	
	$currentStatusId = $row['statusid'];	
}

if (($descriptionupdate == '')||($descriptionupdate == null)){$descriptionupdate="NULL";}

if($taskStatus == 6){
	if($currentStatusId == 6){$taskEndDateUp = $currentEndDate;}else{$taskEndDateUp = $nowdateunix;}
}else{
	$taskEndDateUp = "1900-01-01";
}
if($taskStatus == 6){
	// echo $diff = abs(strtotime($taskEndDateUp) - strtotime($taskTargetDateUp));
	/*------get hours---------*/
	// $ts1 = strtotime($taskTargetDateUp);
	// $ts2 = strtotime($taskEndDateUp);
	
	//===date from target to actual date finished
	$date1=date_create($taskStartDateUp);
	$date2=date_create($taskEndDateUp);
	$diff=date_diff($date1,$date2);
	$datefirrer = abs($diff->format("%R%a"));
	//===start date to expected date
	$date11=date_create($taskStartDateUp);
	$date22=date_create($taskTargetDateUp);
	// $diff2=date_diff($date11,$date22);
	$diff2=date_diff($date11,$date22);
	$datefirrer2 = abs($diff2->format("%R%a"));
	
	if($date22 > $date2){
		// echo "advanced";
		$percentstat = 3;	//aheadoftime
	}else{
		// echo "notadvanced";
		if($datefirrer == $datefirrer2){
			$percentstat = 1;	//exact
		}elseif($datefirrer > $datefirrer2){
			$percentstat = 2;	//late
		}else{
			$percentstat = 3;	//aheadoftime
		}
	}
	
}else{$percentstat = 0;}

/*-------for audit trails--------*/
$auditRemarks1 = "UPDATED TASK"." | ".$taskSubjectUp;
$auditRemarks2 = "ATTEMPT TO UPDATE TASK, FAILED"." | ".$taskSubjectUp;
$auditRemarks3 = "ATTEMPT TO UPDATE TASK, DUPLICATE"." | ".$taskSubjectUp;

// $num_rows = mysqli_num_rows($myresult);

	// $sql=mysqli_query($conn,"CALL insertTaskInfo('$taskUserid','$taskClassification','$taskPriority','$taskSubject','$taskAssignee','$taskTargetDate','$taskStartDate','$taskEndDate','$description','$taskProjectOwner')");
	
	$myqueryxx = "UPDATE pm_projecttasktb
							SET classificationid = '$taskClassificationUp',
							priorityid = '$taskPriorityUp',
							subject = '$taskSubjectUp',
							assignee = '$taskAssigneeUp',
							deadline = '$taskTargetDateUp',
							startdate = '$taskStartDateUp',
							enddate = '$taskEndDateUp',
							description = '$descriptionupdate',
							statusid = '$taskStatus',
							percentdone = '$percentstat',
							isupdatedbyclient = '0',
							projectid = '$projectOwnerUp'
					WHERE id = '$taskIdUp' ";
	$myresultxx = mysqli_query($conn, $myqueryxx);
	
	
	if($taskStatus == 6){
	
	$myquery2 = "SELECT COUNT(statusid) AS doneitem
					FROM pm_projecttasktb WHERE projectid = '$projectOwnerUp' AND statusid = '6'";
	$myresult2 = mysqli_query($conn, $myquery2);
	while($row2 = mysqli_fetch_assoc($myresult2)){
		$projectCounter = $row2['doneitem'];
	}	
											
	$myquery3 = "SELECT COUNT(statusid) AS countall
					FROM pm_projecttasktb WHERE projectid = '$projectOwnerUp'";
	$myresult3 = mysqli_query($conn, $myquery3);
	while($row3 = mysqli_fetch_assoc($myresult3)){
		$projectCounterAll = $row3['countall'];
	}	
	
	if($projectCounter == $projectCounterAll){
		$myqueryxxup = "UPDATE sys_projecttb
								SET statusid = '6'
								WHERE id = '$projectOwnerUp'";
		$myresultxxup = mysqli_query($conn, $myqueryxxup);	
	}
	
	}
	
	$myquery21 = "DELETE FROM pm_taskassigneetb WHERE taskid = '$taskIdUp'";
	$myresult21 = mysqli_query($conn, $myquery21);
	
	$taskAssigneeUp = explode(",", $taskAssigneeUp);
	foreach ($taskAssigneeUp as $key => $value) {
		$myquery2 = "INSERT INTO pm_taskassigneetb (taskid,assigneeid) VALUES ('$taskIdUp','$value')";
		$myresult2 = mysqli_query($conn, $myquery2);	
	}
	
	if($myresultxx){
		$queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES ('$moduleid','$auditRemarks1','$taskUseridUp')";
		$resaud1 = mysqli_query($conn, $queryaud1);
		
		echo "<div class='alert alert-info fade in'>";
		echo "Changes has been saved!";   		
		echo "</div>";
	}else{
		$queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES ('$moduleid','$auditRemarks2','$taskUseridUp')";
		$resaud1 = mysqli_query($conn, $queryaud1);
		
		echo "<div class='alert alert-danger fade in'>";
		echo "Failed to save information!";   		
		echo "</div>";
	}

?>

<script type="text/javascript">
	setTimeout(function(){location.reload();}, 2000);
</script>