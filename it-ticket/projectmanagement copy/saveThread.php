<?php
require('../conn/db.php');
$conn = connectionDB();
$moduleid=4;

$nowdateunix = date("Y-m-d",time());	

function alphanumericAndSpace( $string ) {
   return preg_replace( "/[^,;a-zA-Z0-9 _-]|[,; ]$/s", "", $string );
}


$createduserid=strtoupper($_GET['createduserid']);
$taskthreadid=strtoupper($_GET['taskthreadid']);
$threadSubject=strtoupper($_GET['threadSubject']);
$descriptionupdate23xxx=addslashes(strtoupper($_GET['descriptionupdate23xxx']));


// echo "createduserid:".$createduserid."</br>";
// echo "taskthreadid:".$taskthreadid."</br>";
// echo "threadSubject:".$threadSubject."</br>";
// echo "descriptionupdate23xxx:".$descriptionupdate23xxx."</br>";

$myqueryxx = "INSERT INTO pm_threadtb (	taskid,createdbyid,subject,	message)
				VALUES ('$taskthreadid','$createduserid','$threadSubject','$descriptionupdate23xxx')";
$myresultxx = mysqli_query($conn, $myqueryxx);
?>

<script type="text/javascript">
	setTimeout(function(){location.reload();}, 1000);
</script>