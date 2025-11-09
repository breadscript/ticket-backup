<?php 
// b.statid,a.modulecategoryid
	include_once('../conn/db.php');
	$conn = connectionDB();

	// Ensure session and required vars exist
	if (session_status() === PHP_SESSION_NONE) { session_start(); }
	$userid = isset($_SESSION['userid']) ? intval($_SESSION['userid']) : 0;
	$moduleid = isset($moduleid) ? intval($moduleid) : 0;
	if ($userid <= 0 || $moduleid <= 0) {
		echo "<script language='JavaScript'>history.back();</script>";
		exit;
	}

	$queryxyz = "SELECT DISTINCT b.authoritystatusid,a.user_levelid,b.statid,b.modulecategoryid,a.companyid
				from sys_usertb a
				LEFT JOIN sys_authoritymoduletb b ON a.id = b.userid
				WHERE a.id=$userid AND b.moduleid=$moduleid";
    $resultxyz = mysqli_query($conn, $queryxyz);
	$totalrows_xyz = mysqli_num_rows($resultxyz);
	while ($rowxyz= mysqli_fetch_assoc($resultxyz))
	{
	  $authoritystatusidz = $rowxyz['authoritystatusid'];			
	  $ulevelid = $rowxyz['user_levelid'];
	  $statid_xyz = $rowxyz['statid'];
	  $modulecategoryid_xyz = $rowxyz['modulecategoryid'];	
	  $mycompanyid = $rowxyz['companyid'];	
			  
	}
	
	
	if ($totalrows_xyz==1)
	{
		//user has an authority 
		//echo "User has an authority "."<br>";
			//check module status
			if($statid_xyz == 1)
			{
				//active
				//echo "ACTIVE - User has authority to view the page"."<br>"; 
					//check authority status if full or read only
					if($authoritystatusidz == 1)
					{
						//AUTHORITY IS FULL
						//echo "AUTHORITY IS FULL "."<br>";							
						$authoritystatusidz=1;
						$authoritystatus=" -FULL";
						//echo "AUTHORITY IS FULL"."<br>";
						//echo "AUTHORITY IS FULL ".$authoritystatusidxyz;						
					}
					elseif ($authoritystatusidz == 2){
						//AUTHORITY IS READ-ONLY	
						//echo "AUTHORITY IS READ-ONLY "."<br>";												
						$authoritystatusidz=2;
						$authoritystatus=" -READ-ONLY";
						//echo "AUTHORITY IS READ-ONLY "."<br>";
						//echo $authoritystatusidxyz;
					}
					else {
						//no authority set- not authorized to view the page
						//echo "NO AUTHORITY SET - User has NO authority "."<br>"; 
						//echo "AUTHORITY IS READ-ONLY "."<br>";
						$authoritystatusidz=1;	
						$authoritystatus=" -READ-ONLY";					
						echo "<script language='JavaScript'>history.back();</script>"; 
					}				
			}
			elseif ($statid_xyz == 2)
			{
				//inactive - not authorized to view the page
				//echo "INACTIVE - User has NO authority "."<br>";	
				 echo "<script language='JavaScript'>history.back();</script>"; 			
			}
			else {
				//inactive - not authorized to view the page
				//echo "INACTIVE - User has NO authority "."<br>";  
				 echo "<script language='JavaScript'>history.back();</script>"; 
			}
			
	}
	else
	{
       //not authorized to view the page
       //echo "User has NO authority "."<br>"; 
	   echo "<script language='JavaScript'>history.back();</script>"; 	   
	}
?>