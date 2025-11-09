<?php
require('../conn/db.php');
$conn = connectionDB();
$moduleid=4;

$nowdateunix = date("Y-m-d",time());	

function alphanumericAndSpace( $string ) {
   return preg_replace( "/[^,;a-zA-Z0-9 _-]|[,; ]$/s", "", $string );
}

$taskUserid=strtoupper($_GET['taskUserid']);
$taskClassification=strtoupper($_GET['taskClassification']);
$taskPriority=strtoupper($_GET['taskPriority']);
$taskSubject=urldecode($_GET['taskSubject']);
$taskAssignee=strtoupper($_GET['taskAssignee2']);
$taskTargetDate=strtoupper($_GET['taskTargetDate']);
$taskStartDate=strtoupper($_GET['taskStartDate']);
$taskEndDate=strtoupper($_GET['taskEndDate']);
$description=urldecode($_GET['description']);
$taskProjectOwner=strtoupper($_GET['taskProjectOwner']);
$taskIdentification = 1;	//task is from pm
if (($description == '')||($description == null)){$description="NULL";}

$myqueryxxup1 = "SELECT statusid FROM sys_projecttb WHERE id = '$taskProjectOwner'";
$myresultxxup1 = mysqli_query($conn, $myqueryxxup1);
while($row66 = mysqli_fetch_assoc($myresultxxup1)){
	$statusidnix=$row66['statusid'];
}




if($statusidnix <> 6){

/*-------for audit trails--------*/
$auditRemarks1 = "REGISTER NEW TASK"." | ".$taskSubject;
$auditRemarks2 = "ATTEMPT TO REGISTER NEW TASK, FAILED"." | ".$taskSubject;
$auditRemarks3 = "ATTEMPT TO REGISTER NEW TASK, DUPLICATE"." | ".$taskSubject;

$myquery = "SELECT subject FROM pm_projecttasktb WHERE subject = '$taskSubject' AND projectid = '$taskProjectOwner' AND classificationid = '$taskClassification' LIMIT 1";
$myresult = mysqli_query($conn, $myquery);
$num_rows = mysqli_num_rows($myresult);

	if($num_rows == 0){
		// $sql=mysqli_query($conn,"CALL insertTaskInfo('$taskUserid','$taskClassification','$taskPriority','$taskSubject','$taskAssignee','$taskTargetDate','$taskStartDate','$taskEndDate','$description','$taskProjectOwner')");
		
		$myqueryxx = "INSERT INTO pm_projecttasktb (createdbyid,classificationid,priorityid,subject,assignee,deadline,startdate,enddate,description,projectid,istask)
					VALUES ('$taskUserid','$taskClassification','$taskPriority','$taskSubject','$taskAssignee','$taskTargetDate','$taskStartDate','$taskEndDate','$description','$taskProjectOwner','$taskIdentification')";
		$myresultxx = mysqli_query($conn, $myqueryxx);
		
		$lastid = mysqli_insert_id($conn); 
		$taskAssignee = explode(",", $taskAssignee);
		
		foreach ($taskAssignee as $key => $value) {
			// echo "assignee1:".$value."</br>";
			$myquery2 = "INSERT INTO pm_taskassigneetb (taskid,assigneeid) VALUES ('$lastid','$value')";
			$myresult2 = mysqli_query($conn, $myquery2);
		}
		
		if($myresultxx){
			
			if($statusidnix == 1){
			$myqueryxxup = "UPDATE sys_projecttb
									SET statusid = '3'
									WHERE id = '$taskProjectOwner'";
			$myresultxxup = mysqli_query($conn, $myqueryxxup);	
			}
			
			
			// $sql2=mysqli_query($conn,"CALL insertAudit('$moduleid','$auditRemarks1','$taskUserid')");
			$queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES ('$moduleid','$auditRemarks1','$taskUserid')";
			$resaud1 = mysqli_query($conn, $queryaud1);
			echo "<div class='alert alert-info fade in'>";
			echo "New Client has been saved!";   		
			echo "</div>";
		}else{
			$queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES ('$moduleid','$auditRemarks2','$taskUserid')";
			$resaud1 = mysqli_query($conn, $queryaud1);
			// $sql2=mysqli_query($conn,"CALL insertAudit('$moduleid','$auditRemarks2','$taskUserid')");
			echo "<div class='alert alert-danger fade in'>";
			echo "Failed to save information!";   		
			echo "</div>";
		}
	}else{
		$queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES ('$moduleid','$auditRemarks3','$taskUserid')";
		$resaud1 = mysqli_query($conn, $queryaud1);
		
		// $sql2=mysqli_query($conn,"CALL insertAudit('$moduleid','$auditRemarks3','$taskUserid')");
		echo "<div class='alert alert-danger fade in'>";
		echo "Duplicate Entry, Please check the details again.";   		
		echo "</div>";
	}
}else{
	echo "<div class='alert alert-danger fade in'>";
	echo "Project has been closed, to proceed adding new task for this, call your PM.";   		
	echo "</div>";
}
?>

<script type="text/javascript">
	setTimeout(function(){location.reload();}, 2000);
</script>