<?php
include('../check_session.php');
$conn = connectionDB();
$nowdateunix = date("Y-m-d",time());	

$userAuthUserid=trim($_POST['userAuthUserid']);
$useridx=trim($_POST['useridx']);
$moduleidx=trim($_POST['moduleidx']);
$userAuthoLevel=trim($_POST['userAuthoLevel']);
$userAuthmoduleStat=trim($_POST['userAuthmoduleStat']);

// echo "userAuthUserid:".$userAuthUserid."</br>";
// echo "useridx:".$useridx."</br>";
// echo "moduleidx:".$moduleidx."</br>";
// echo "userAuthoLevel:".$userAuthoLevel."</br>";
// echo "userAuthmoduleStat:".$userAuthmoduleStat."</br>";


$mysqlins = "SELECT DISTINCT a.id,a.firstlevel,a.modulecategoryid FROM sys_moduletb a WHERE a.id='$moduleidx' LIMIT 1";
$myresultins = mysqli_query($conn, $mysqlins);
while($row = mysqli_fetch_assoc($myresultins)){
	$vfirstlevel = $row['firstlevel'];
	$vmodulecategoryid = $row['modulecategoryid'];
	// echo "vfirstlevel:".$vfirstlevel."</br>";
	echo "vmodulecategoryid:".$vmodulecategoryid."</br>";
	
	// if vfirstlevel <> 0 then
	if($vfirstlevel <> 0){
		$query = "SELECT into userid FROM sys_authoritymoduletb a
									WHERE a.moduleid = vfirstlevel and a.userid='$useridx'";
		$result = mysqli_query($conn, $query);
		$vflag = mysqli_num_rows($result);
		if($vflag == 0){
			// echo "insert1";
			$insert = "insert into sys_authoritymoduletb
							(userid,moduleid,authoritystatusid,moduleorderno,
							datecreated,createdby_userid,statid,modulecategoryid)
						values('$useridx','$vfirstlevel','1','1',
								'$nowdateunix','$userAuthUserid','$userAuthmoduleStat','$vmodulecategoryid')";
			$resinsert = mysqli_query($conn, $insert);
		}else{
			
		}
	}else{
			// echo "insert2";
			$insert = "insert into sys_authoritymoduletb
							(userid,moduleid,authoritystatusid,moduleorderno,
							datecreated,createdby_userid,statid,modulecategoryid)
						values('$useridx','$moduleidx','$userAuthoLevel','1',
								'$nowdateunix','$userAuthUserid','$userAuthmoduleStat','$vmodulecategoryid')";
			$resinsert = mysqli_query($conn, $insert);
			
			$query2 = "SELECT modulecategoryid FROM sys_authoritymodulecategorytb a
									WHERE a.modulecategoryid = '$vmodulecategoryid' and a.userid='$useridx'";
			$result2 = mysqli_query($conn, $query2);
			$vflag2 = mysqli_num_rows($result2);
			
			if($vflag2 == 0){
				// echo "insert3";
				$insert2 = "insert into sys_authoritymodulecategorytb
								 	    (userid,modulecategoryid,statid,datecreated,createdby_userid)
							values('$useridx','$vmodulecategoryid','1',
									'$nowdateunix','$userAuthUserid')";
				$resinsert2 = mysqli_query($conn, $insert2);
			}
	}
}	
?>
<script type="text/javascript">
	setTimeout(function(){location.reload();}, 5000);
</script>
