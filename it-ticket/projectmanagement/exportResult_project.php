<?php
header('content-type: application/octet-stream'); 
header('content-disposition: attachment; filename=dailyattendance.xls'); 
header('content-transfer-encoding: binary'); 

require('../conn/db.php');
$nowdateunix = date("Y-m-d",time());	

$rest=trim($_GET['searchstring']);
if($rest == null)
{
	$rest="";
}




?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Project Reports</title>
</head>
<body>
<div class="row">
	<div class="col-xs-12">
		<!-- PAGE CONTENT BEGINS -->
		<div class="row">
			<div class="col-xs-12">
				<table border="1">
					<thead>
						<tr>
							<th></th>
							<th>Project Name</th>
							<th>Client Owner</th>
							<th>Project Manager</th>
							<th>DateStart</th>
							<th>Expected </br>End Date</th>
							<th>Tasks</th>
							<th>Percent</th>
							<th>Status</th>
						</tr>
					</thead>
					<tbody>
					<?php 
						$conn = connectionDB();
						$counter = 1;
						$myquery = "SELECT sys_projecttb.id,sys_projecttb.projectname,sys_projecttb.projectstartdate,sys_projecttb.projectenddate,
											sys_projecttb.clientid,sys_projecttb.datecreated,sys_projecttb.createdbyid,sys_projecttb.statusid,
											sys_taskstatustb.statusname,sys_clienttb.clientname,sys_usertb.user_firstname,sys_usertb.user_lastname
									FROM sys_projecttb
									LEFT JOIN sys_clienttb ON sys_clienttb.id=sys_projecttb.clientid
									LEFT JOIN sys_taskstatustb ON sys_projecttb.statusid=sys_taskstatustb.id
									LEFT JOIN sys_usertb ON sys_usertb.id=sys_projecttb.projectmanagerid
									$rest
									ORDER BY sys_projecttb.projectname ASC";
						$myresult = mysqli_query($conn, $myquery);
							while($row = mysqli_fetch_assoc($myresult)){
								$projectId = $row['id'];	
								$myProjectStatusid = $row['statusid'];	
								if($myProjectStatusid == 6){$myClass = 'success';}elseif($myProjectStatusid == 2){$myClass = 'info';}elseif($myProjectStatusid == 3){$myClass = 'warning';}else{$myClass = 'danger';}
											
								$myquery2 = "SELECT COUNT(statusid) AS doneitem
											FROM pm_projecttasktb WHERE projectid = '$projectId' AND statusid = '6'";
								$myresult2 = mysqli_query($conn, $myquery2);
									while($row2 = mysqli_fetch_assoc($myresult2)){
										$projectCounter = $row2['doneitem'];
									}	
											
								$myquery3 = "SELECT COUNT(statusid) AS countall
											FROM pm_projecttasktb WHERE projectid = '$projectId' AND statusid != '5'";
								$myresult3 = mysqli_query($conn, $myquery3);
									while($row3 = mysqli_fetch_assoc($myresult3)){
										$projectCounterAll = $row3['countall'];
									}	
								if(($projectCounter <= 0)||($projectCounterAll <= 0)){
									$percentdone = 0;
								}else{
									$percentdone = number_format((($projectCounter/$projectCounterAll)*100),2);
								}
					?>
						<tr>
							<td><?php echo $counter;?></td>
							<td><?php echo $row['projectname'];?></td>
							<td><?php echo $row['clientname'];?></td>
							<td><?php echo $row['user_firstname']." ".$row['user_lastname'];?></td>
							<td><?php echo $row['projectstartdate'];?></td>
							<td><?php echo $row['projectenddate'];?></td>
							<td><?php echo $projectCounterAll;?></td>
							<td><?php echo $percentdone."%";?></td>
							<td><?php echo $row['statusname'];?></td>
						</tr>
					</tbody>
					<?php
						$counter ++;
							}
					?>
				</table>
			</div><!-- /.span -->
		</div><!-- /.row -->

								
	</div>
</div>
</body>
    
</html>    