<?php
date_default_timezone_set('Asia/Manila');
require('../conn/db.php');
$conn = connectionDB();
$moduleid=3;

$nowdateunix = date("Y-m-d h:i:s",time());	

function alphanumericAndSpace( $string ) {
   return preg_replace( "/[^,;a-zA-Z0-9 _-]|[,; ]$/s", "", $string );
}
		
$projectId=strtoupper($_GET['projectId2']);
$projectUserid=strtoupper($_GET['projectUserid2']);
$projectName=strtoupper($_GET['projectName2']);
$projectOwner=strtoupper($_GET['projectOwner2']);
$projectDateStart=strtoupper($_GET['projectDateStart2']);
$projectDateEnd=strtoupper($_GET['projectDateEnd2']);
$projectManager=strtoupper($_GET['projectManager2']);
$projectStatus=strtoupper($_GET['projectStatus2']);
$remarks2=strtoupper($_GET['remarks22']);

if (($projectId == '')||($projectId == null)){$projectId="NULL";}
if (($projectUserid == '')||($projectUserid == null)){$projectUserid="NULL";}
if (($projectName == '')||($projectName == null)){$projectName="NULL";}
if (($projectOwner == '')||($projectOwner == null)){$projectOwner="NULL";}
if (($projectDateStart == '')||($projectDateStart == null)){$projectDateStart="NULL";}
if (($projectDateEnd == '')||($projectDateEnd == null)){$projectDateEnd="NULL";}
if (($projectManager == '')||($projectManager == null)){$projectManager="NULL";}
if (($projectStatus == '')||($projectStatus == null)){$projectStatus="NULL";}
if (($remarks2 == '')||($remarks2 == null)){$remarks2="NULL";}


$myquery = "SELECT sys_projecttb.id,sys_projecttb.projectname,sys_projecttb.projectstartdate,sys_projecttb.projectenddate,
				   sys_projecttb.clientid,sys_projecttb.datecreated,sys_projecttb.createdbyid,sys_projecttb.statusid,
				   sys_projecttb.description,sys_projecttb.projectmanagerid
			FROM sys_projecttb
			LEFT JOIN sys_clienttb ON sys_clienttb.id=sys_projecttb.clientid
			LEFT JOIN sys_clientstatustb ON sys_projecttb.statusid=sys_clientstatustb.id
			LEFT JOIN sys_usertb ON sys_usertb.id=sys_projecttb.projectmanagerid
			WHERE sys_projecttb.id = '$projectId'";
$myresult = mysqli_query($conn, $myquery);
while($row = mysqli_fetch_assoc($myresult)){
	$myProjectId = $row['id'];	
	$myProjectName = $row['projectname'];	
	$myProjectStart = $row['projectstartdate'];	
	$myProjectEnd = $row['projectenddate'];	
	$myProjectClientId = $row['clientid'];	
	$myProjectStatusId = $row['statusid'];	
	$myProjectDescription = $row['description'];	
	$myProjectManagerId = $row['projectmanagerid'];	
}

/*-------for audit trails--------*/
$auditRemarks1 = "UPDATED PROJECT INFO"." | ".$myProjectName;
$auditRemarks2 = "ATTEMPT TO UPDATE PROJECT INFO"." | ".$myProjectName;

if($myProjectName != $projectName){$sql2=mysqli_query($conn,"CALL insertAudit('$moduleid','CHANGE PROJECT NAME | $myProjectName','$projectUserid')");}
if($myProjectStart != $projectDateStart){$sql2=mysqli_query($conn,"CALL insertAudit('$moduleid','CHANGE PROJECT STARTDATE | $myProjectName','$projectUserid')");}
if($myProjectEnd != $projectDateEnd){$sql2=mysqli_query($conn,"CALL insertAudit('$moduleid','CHANGE PROJECT ENDDATE | $myProjectName','$projectUserid')");}
if($myProjectClientId != $projectOwner){$sql2=mysqli_query($conn,"CALL insertAudit('$moduleid','CHANGE PROJECT OWNER | $myProjectName','$projectUserid')");}
if($myProjectStatusId != $projectStatus){$sql2=mysqli_query($conn,"CALL insertAudit('$moduleid','CHANGE PROJECT STATUS | $myProjectName','$projectUserid')");}
if($myProjectDescription != $remarks2){$sql2=mysqli_query($conn,"CALL insertAudit('$moduleid','CHANGE PROJECT DESCRIPTION | $myProjectName','$projectUserid')");}
if($myProjectManagerId != $projectManager){$sql2=mysqli_query($conn,"CALL insertAudit('$moduleid','CHANGE PROJECT MANAGER | $myProjectName','$projectUserid')");}




$mysqlins = "UPDATE `sys_projecttb`
				SET
				projectname = '$projectName',
				projectstartdate = '$projectDateStart',
				projectenddate = '$projectDateEnd',
				clientid = '$projectOwner',
				updatedbyid = '$projectUserid',
				datetimeupdated = '$nowdateunix',
				statusid = '$projectStatus',
				description = '$remarks2',
				projectmanagerid = '$projectManager'
			WHERE id = '$projectId'";
$myresultins = mysqli_query($conn, $mysqlins);
if($mysqlins){
	$queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES ('$moduleid','$auditRemarks1','$projectUserid')";
	$resaud1 = mysqli_query($conn, $queryaud1);
	echo "<div class='alert alert-info fade in'>";
	echo "Data has been updated!";   		
	echo "</div>";
	?>
	<script type="text/javascript">
		setTimeout(function(){location.reload();}, 1000);
	</script>
<?php
}else{
	$queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES ('$moduleid','$auditRemarks2','$projectUserid')";
	$resaud1 = mysqli_query($conn, $queryaud1);
	echo "<div class='alert alert-danger fade in'>";
	echo "Failed to save new information!";   		
	echo "</div>";
	?>
	<script type="text/javascript">
		setTimeout(function(){location.reload();}, 1000);
	</script>
	<?php
	}


?>




