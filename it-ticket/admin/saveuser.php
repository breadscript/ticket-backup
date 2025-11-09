<?php
require('../conn/db.php');
$conn = connectionDB();
$moduleid=5;

$nowdateunix = date("Y-m-d",time());	

function alphanumericAndSpace( $string ) {
   return preg_replace( "/[^,;a-zA-Z0-9 _-]|[,; ]$/s", "", $string );
}

$userUserid=strtoupper($_GET['userUserid']);
$userFirstName=strtoupper($_GET['userFirstName']);
$userLastName=strtoupper($_GET['userLastName']);
$userEmail=strtoupper($_GET['userEmail']);
$userName=strtoupper($_GET['userName']);
$userLevel=strtoupper($_GET['userLevel']);
$userGroup=strtoupper($_GET['userGroup']);
$userClient=strtoupper($_GET['userClient']);
$userPassword = '1234';
$hashedPassword = password_hash($userPassword, PASSWORD_DEFAULT);


if (strlen($hashedPassword) < 60) {
    die("Error: Password hash is incomplete");
}

$userStatusid = '1';
if (($userClient == '')||($userClient == null)){$userClient=0;}
/*-------for audit trails--------*/
$auditRemarks1 = "REGISTER NEW USER"." | ".$userName;
$auditRemarks2 = "ATTEMPT TO REGISTER NEW USER, FAILED"." | ".$userName;
$auditRemarks3 = "ATTEMPT TO REGISTER NEW USER, DUPLICATE"." | ".$userName;

$myquery = "SELECT username FROM sys_usertb WHERE username = '$userName' LIMIT 1";
$myresult = mysqli_query($conn, $myquery);
$num_rows = mysqli_num_rows($myresult);

if($num_rows == 0){
	$mysqlins = "INSERT INTO `sys_usertb` (user_firstname,user_lastname,username,password,
											 user_statusid,user_levelid,user_groupid,
											 createdbyid,companyid,emailadd)
								   VALUES ('$userFirstName','$userLastName','$userName','$hashedPassword',
											'$userStatusid','$userLevel','$userGroup',
											'$userUserid','$userClient','$userEmail')";
	$myresultins = mysqli_query($conn, $mysqlins);
	if($mysqlins){
		$queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES ('$moduleid','$auditRemarks1','$userUserid')";
		$resaud1 = mysqli_query($conn, $queryaud1);
		
		echo "<div class='alert alert-info fade in'>";
		echo "New Client has been saved!";   		
		echo "</div>";
	}else{
		$queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES ('$moduleid','$auditRemarks2','$userUserid')";
		$resaud1 = mysqli_query($conn, $queryaud1);
		
		echo "<div class='alert alert-danger fade in'>";
		echo "Failed to save information!";   		
		echo "</div>";
	}
}else{
	echo "<div class='alert alert-danger fade in'>";
	echo "Duplicate Entry, Please check the details again.";   		
	echo "</div>";
}

?>
<script type="text/javascript">
	setTimeout(function(){location.reload();}, 5000);
</script>