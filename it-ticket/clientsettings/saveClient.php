<?php
require('../conn/db.php');
$conn = connectionDB();
$moduleid=2;

$nowdateunix = date("Y-m-d",time());	

function alphanumericAndSpace( $string ) {
   return preg_replace( "/[^,;a-zA-Z0-9 _-]|[,; ]$/s", "", $string );
}

$clientName=strtoupper($_GET['clientName']);
$clientEmail=strtoupper($_GET['clientEmail']);
$clientContact=strtoupper($_GET['clientContact']);
$clientPerson=strtoupper($_GET['clientPerson']);
$clientPosition=strtoupper($_GET['clientPosition']);
$clientStreet=strtoupper($_GET['clientStreet']);
$clientCity=strtoupper($_GET['clientCity']);
$clientState=strtoupper($_GET['clientState']);
$clientCountry=strtoupper($_GET['clientCountry']);
$clientUserid=strtoupper($_GET['clientUserid']);

if (($clientName == '')||($clientName == null)){$clientName="NULL";}
if (($clientEmail == '')||($clientEmail == null)){$clientEmail="NULL";}
if (($clientContact == '')||($clientContact == null)){$clientContact="NULL";}
if (($clientPerson == '')||($clientPerson == null)){$clientPerson="NULL";}
if (($clientPosition == '')||($clientPosition == null)){$clientPosition="NULL";}
if (($clientStreet == '')||($clientStreet == null)){$clientStreet="NULL";}
if (($clientCity == '')||($clientCity == null)){$clientCity="NULL";}
if (($clientState == '')||($clientState == null)){$clientState="NULL";}
if (($clientCountry == '')||($clientCountry == null)){$clientCountry="NULL";}

/*-------for audit trails--------*/
$auditRemarks1 = "REGISTER NEW CLIENT"." | ".$clientName;
$auditRemarks2 = "ATTEMPT TO REGISTER NEW CLIENT, FAILED"." | ".$clientName;
$auditRemarks3 = "ATTEMPT TO REGISTER NEW CLIENT, DUPLICATE"." | ".$clientName;

$myquery = "SELECT clientname FROM sys_clienttb WHERE clientname = '$clientName' LIMIT 1";
$myresult = mysqli_query($conn, $myquery);
$num_rows = mysqli_num_rows($myresult);

if($num_rows == 0){
	// $sql=mysqli_query($conn,"CALL insertClientInfo('$clientName','$clientStreet','$clientCity','$clientState','$clientCountry','$clientEmail','$clientContact','$clientPerson','$clientPosition','$clientUserid')");
	$mysqlins = "INSERT INTO `sys_clienttb` (clientname,clientaddressstreet,clientaddresscity,clientaddressstate,
											 clientaddresscountry,clientemail,clientcontactnumber,clientcontactperson,
											 clientcontactpersonposition,createdbyid)
									VALUES ('$clientName','$clientStreet','$clientCity','$clientState',
											'$clientCountry','$clientEmail','$clientContact','$clientPerson',
											'$clientPosition','$clientUserid')";
	$myresultins = mysqli_query($conn, $mysqlins);
	
	if($mysqlins){
		$queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES ('$moduleid','$auditRemarks1','$clientUserid')";
		$resaud1 = mysqli_query($conn, $queryaud1);
		
		echo "<div class='alert alert-info fade in'>";
		echo "New Client has been saved!";   		
		echo "</div>";
	}else{
		$queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES ('$moduleid','$auditRemarks2','$clientUserid')";
		$resaud1 = mysqli_query($conn, $queryaud1);
		
		echo "<div class='alert alert-danger fade in'>";
		echo "Failed to save information!";   		
		echo "</div>";
	}
}else{
	$queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES ('$moduleid','$auditRemarks3','$clientUserid')";
	$resaud1 = mysqli_query($conn, $queryaud1);
		
	echo "<div class='alert alert-danger fade in'>";
	echo "Duplicate Entry, Please check the details again.";   		
	echo "</div>";
}
?>

<script type="text/javascript">
	setTimeout(function(){location.reload();}, 5000);
</script>