<?php
require('../conn/db.php');
$conn = connectionDB();
$moduleid=3;

$nowdateunix = date("Y-m-d",time());	

function alphanumericAndSpace( $string ) {
   return preg_replace( "/[^,;a-zA-Z0-9 _-]|[,; ]$/s", "", $string );
}

$projectUserid=strtoupper($_GET['projectUserid']);
$projectName=strtoupper($_GET['projectName']);
$projectOwner=strtoupper($_GET['projectOwner']);
$projectDateStart=strtoupper($_GET['projectDateStart']);
$projectDateEnd=strtoupper($_GET['projectDateEnd']);
$projectManager=strtoupper($_GET['projectManager']);
$remarks=strtoupper($_GET['remarks']);

/*-------for audit trails--------*/
$auditRemarks1 = "REGISTER NEW PROJECT"." | ".$projectName;
$auditRemarks2 = "ATTEMPT TO REGISTER NEW PROJECT, FAILED"." | ".$projectName;
$auditRemarks3 = "ATTEMPT TO REGISTER NEW PROJECT, DUPLICATE"." | ".$projectName;


if (($projectName == '')||($projectName == null)){$projectName="NULL";}
if (($remarks == '')||($remarks == null)){$remarks="NULL";}

$myquery = "SELECT projectname FROM sys_projecttb WHERE projectname = '$projectName' LIMIT 1";
$myresult = mysqli_query($conn, $myquery);
$num_rows = mysqli_num_rows($myresult);
if($num_rows == 0){
	$mysqlins = "INSERT INTO `sys_projecttb` (projectname,projectstartdate,projectenddate,clientid,
											  createdbyid,description,projectmanagerid)
									  VALUES ('$projectName','$projectDateStart','$projectDateEnd',
											  '$projectOwner','$projectUserid','$remarks','$projectManager')";
	$myresultins = mysqli_query($conn, $mysqlins);
	if($mysqlins){
		$queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES ('$moduleid','$auditRemarks1','$projectUserid')";
		$resaud1 = mysqli_query($conn, $queryaud1);
		echo "<div class='alert alert-info fade in'>";
		echo "New Client has been saved!";   		
		echo "</div>";
	}else{
		$queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES ('$moduleid','$auditRemarks2','$projectUserid')";
		$resaud1 = mysqli_query($conn, $queryaud1);
		echo "<div class='alert alert-danger fade in'>";
		echo "Failed to save information!";   		
		echo "</div>";
	}
}else{
	$queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES ('$moduleid','$auditRemarks3','$projectUserid')";
	$resaud1 = mysqli_query($conn, $queryaud1);
	echo "<div class='alert alert-danger fade in'>";
	echo "Duplicate Entry, Please check the details again.";   		
	echo "</div>";
}
	

?>

<script type="text/javascript">
	setTimeout(function(){location.reload();}, 5000);
</script>