<?php
require('../conn/db.php');
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d");	

// ERROR_REPORTING(0);

$projectName2=strtoupper(trim($_GET['projectName2']));
$projectOwnerId=$_GET['projectOwnerId'];
$projectManager=$_GET['projectManager'];
$projectStatus2=$_GET['projectStatus2'];
$datestartedfrom=$_GET['datestartedfrom'];
$datestartedto=$_GET['datestartedto'];
$datewillendfrom=$_GET['datewillendfrom'];
$datewillendto=$_GET['datewillendto'];


if (($projectName2 == '')||($projectName2 == null)||($projectName2== 'NULL')||($projectName2== 'null')){$projectName2=null;}
if (($projectOwnerId == '')||($projectOwnerId == null)||($projectOwnerId== 'NULL')||($projectOwnerId== 'null')){$projectOwnerId=null;}
if (($projectManager == '')||($projectManager == null)||($projectManager== 'NULL')||($projectManager== 'null')){$projectManager=null;}
if (($projectStatus2 == '')||($projectStatus2 == null)||($projectStatus2== 'NULL')||($projectStatus2== 'null')){$projectStatus2=null;}
if (($datestartedfrom == '')||($datestartedfrom == null)||($datestartedfrom== 'NULL')||($datestartedfrom== 'null')){$datestartedfrom=null;}
if (($datestartedto == '')||($datestartedto == null)||($datestartedto== 'NULL')||($datestartedto== 'null')){$datestartedto=$nowdateunix;}
if (($datewillendfrom == '')||($datewillendfrom == null)||($datewillendfrom== 'NULL')||($datewillendfrom== 'null')){$datewillendfrom=null;}
if (($datewillendto == '')||($datewillendto == null)||($datewillendto== 'NULL')||($datewillendto== 'null')){$datewillendto=$nowdateunix;}




$rest="";
	
	
	if($projectName2!="")
	 {
		if($rest==""){$rest.=" WHERE sys_projecttb.projectname LIKE '%$projectName2%' ";}
		else
		{$rest.=" AND sys_projecttb.projectname  LIKE '%$projectName2%'  ";}
	}
	if($projectOwnerId!="")
	 {
		if($rest==""){$rest.=" WHERE sys_projecttb.clientid = '$projectOwnerId' ";}
		else
		{$rest.=" AND sys_projecttb.clientid  = '$projectOwnerId'  ";}
	}
	if($projectManager!="")
	 {
		if($rest==""){$rest.=" WHERE sys_projecttb.projectmanagerid = '$projectManager' ";}
		else
		{$rest.=" AND sys_projecttb.projectmanagerid  = '$projectManager'  ";}
	}
	if($projectStatus2!="")
	 {
		if($rest==""){$rest.=" WHERE sys_projecttb.statusid = '$projectStatus2' ";}
		else
		{$rest.=" AND sys_projecttb.statusid  = '$projectStatus2'  ";}
	}
	 if($datestartedfrom!="")
	 {
		if($rest==""){$rest.=" WHERE sys_projecttb.projectstartdate >= '$datestartedfrom' AND sys_projecttb.projectstartdate <= '$datestartedto' ";}
		else
		{$rest.=" AND sys_projecttb.projectstartdate >= '$datestartedfrom'  AND sys_projecttb.projectstartdate <= '$datestartedto' ";}
	}
	if($datewillendfrom!="")
	 {
		if($rest==""){$rest.=" WHERE sys_projecttb.projectenddate >= '$datewillendfrom' AND sys_projecttb.projectenddate <= '$datewillendto' ";}
		else
		{$rest.=" AND sys_projecttb.projectenddate >= '$datewillendfrom'  AND sys_projecttb.projectenddate <= '$datewillendto' ";}
	}
	echo $rest;
?>
<div class="row">
	<div class="col-xs-12">
		<!-- PAGE CONTENT BEGINS -->
		<div class="row">
			<div class="col-xs-12">
				<a href="exportResult_project.php?searchstring=<?php echo $rest; ?>" target="_blank" class="btn btn-sm bg-danger">Export to Excel</a> 
				<table id="simple-table" class="table  table-bordered table-hover">
					<thead>
						<tr>
							<th></th>
							<th>Project Name</th>
							<th>Category</th>
							<!-- <th>Project Manager</th> -->
							<!-- <th>DateStart</th>
							<th>Expected </br>End Date</th> -->
							<th>Total Tasks</th>
							<th>Closed</th>
							<th>Open</th>
							<th>Percent</th>
							<!-- <th>Status</th> -->
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
											FROM pm_projecttasktb WHERE projectid = '$projectId' ";
								$myresult3 = mysqli_query($conn, $myquery3);
									while($row3 = mysqli_fetch_assoc($myresult3)){
										$projectCounterAll = $row3['countall'];
									}	
								if(($projectCounter <= 0)||($projectCounterAll <= 0)){
									$percentdone = 0;
								}else{
									$percentdone = number_format((($projectCounter/$projectCounterAll)*100),2);
								}

								$myquery4 = "SELECT COUNT(statusid) AS countall
											FROM pm_projecttasktb WHERE projectid = '$projectId' AND statusid IN ('1','3','4')  ";
								$myresult4 = mysqli_query($conn, $myquery4);
									while($row4 = mysqli_fetch_assoc($myresult4)){
									$totalOngoing = $row4['countall'];
								}


					?>
						<tr>
							<td><?php echo $counter;?></td>
							<td><?php echo $row['projectname'];?></td>
							<td><?php echo $row['clientname'];?></td>
							<!-- <td><?php echo $row['user_firstname']." ".$row['user_lastname'];?></td> -->
							<!-- <td><?php echo $row['projectstartdate'];?></td>
							<td><?php echo $row['projectenddate'];?></td> -->
							<td><?php echo $projectCounterAll;?></td>
							<td><?php echo $projectCounterAll-$totalOngoing;?></td>
							<td><?php echo $totalOngoing;?></td>
							<td><?php echo $percentdone."%";?></td>
							<!-- <td><?php echo $row['statusname'];?></td> -->
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