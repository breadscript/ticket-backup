<?php
require('../conn/db.php');
$conn = connectionDB();
$moduleid=1;

$nowdateunix = date("Y-m-d",time());	

function alphanumericAndSpace( $string ) {
   return preg_replace( "/[^,;a-zA-Z0-9 _-]|[,; ]$/s", "", $string );
}

$ticketIdUp=strtoupper($_GET['ticketIdUp2']);
$ticketUseridUp=strtoupper($_GET['ticketUseridUp2']);
$ticketStatus=strtoupper($_GET['ticketStatus2']);
$descriptionupdate=strtoupper($_GET['descriptionupdate2']);
$ticketSubjectUp=strtoupper($_GET['ticketSubjectUp2']);



$myquery = "SELECT enddate,statusid FROM pm_projecttasktb WHERE id = '$ticketIdUp'";
$myresult = mysqli_query($conn, $myquery);
while($row = mysqli_fetch_assoc($myresult)){
	$currentEndDate = $row['enddate'];	
	$currentStatusId = $row['statusid'];	
}

if (($descriptionupdate == '')||($descriptionupdate == null)){$descriptionupdate="NULL";}


/*-------for audit trails--------*/
$auditRemarks1 = "UPDATED TICKET"." | ".$ticketSubjectUp;
$auditRemarks2 = "ATTEMPT TO UPDATE TICKET, FAILED"." | ".$ticketSubjectUp;
$auditRemarks3 = "ATTEMPT TO UPDATE TICKET, DUPLICATE"." | ".$ticketSubjectUp;

	if($ticketStatus != 6){
		$myqueryxx = "UPDATE pm_projecttasktb
								SET 
								description = '$descriptionupdate',
								statusid = '$ticketStatus',
								isupdatedbyclient = '1'
						WHERE id = '$ticketIdUp' ";
		$myresultxx = mysqli_query($conn, $myqueryxx);
		
		if($myresultxx){
			$queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES ('$moduleid','$auditRemarks1','$ticketIdUp')";
			$resaud1 = mysqli_query($conn, $queryaud1);
			
			echo "<div class='alert alert-info fade in'>";
			echo "Changest has been saved!";   		
			echo "</div>";
		}else{
			$queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES ('$moduleid','$auditRemarks2','$ticketIdUp')";
			$resaud1 = mysqli_query($conn, $queryaud1);
			
			echo "<div class='alert alert-danger fade in'>";
			echo "Failed to save information!";   		
			echo "</div>";
		}
	}else{
		echo "<div class='alert alert-danger fade in'>";
		echo "You dont have permission to tag as DONE, please contact your provider.";   		
		echo "</div>";
	}
?>

<script type="text/javascript">
	setTimeout(function(){location.reload();}, 1000);
</script>