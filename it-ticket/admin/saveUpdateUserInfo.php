<?php
require('../conn/db.php');
$conn = connectionDB();
$moduleid=5;

$nowdateunix = date("Y-m-d",time());	

function alphanumericAndSpace( $string ) {
   return preg_replace( "/[^,;a-zA-Z0-9 _-]|[,; ]$/s", "", $string );
}

$user2=strtoupper($_GET['user2']);
$userUserid2=strtoupper($_GET['userUserid2']);
$userFirstName2=strtoupper($_GET['userFirstName2']);
$userLastName2=strtoupper($_GET['userLastName2']);
$userEmail2=strtoupper($_GET['userEmail2']);
$userName2=strtoupper(trim($_GET['userName2']));
$userLevel2=strtoupper($_GET['userLevel2']);
$userGroup2=strtoupper($_GET['userGroup2']);
$userClient2=strtoupper($_GET['userClient2']);
$password2=$_GET['password2'];
$userStatus2=strtoupper($_GET['userStatus2']);

$queryaud1xxxx = "SELECT * FROM `sys_usertb` WHERE username = '$userName2' LIMIT 2";
$resaudxxxx1 = mysqli_query($conn, $queryaud1xxxx);
$num_rowsxx = mysqli_num_rows($resaudxxxx1);
if($num_rowsxx <= 1){
	 $mysqlins = "UPDATE `sys_usertb` 
						SET 
						user_firstname = '$userFirstName2',
						user_lastname = '$userLastName2',
						username = '$userName2',
						password = '$password2',
						user_statusid = '$userStatus2',
						user_levelid = '$userLevel2',
						user_groupid = '$userGroup2',
						companyid = '$userClient2',
						emailadd = '$userEmail2'
						WHERE id = '$userUserid2'";
	$myresultins = mysqli_query($conn, $mysqlins);
}else{
 echo "<div class='alert alert-danger fade in'>";
 echo "Username already taken.";   		
 echo "</div>";
 }

?>
<script type="text/javascript">
	setTimeout(function(){location.reload();}, 1000);
</script>