<?php
include "blocks/header.php";
$moduleid=3;
include('proxy.php');
?>
<!DOCTYPE html>
<link rel="../stylesheet" href="../css/bootstrapValidator.min.css"/> 
<html lang="en">
	<body class="no-skin">
		
		<div class="row">
            <div class="col-lg-12">
				<div id="myModal_createProject" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="false"></div>	
				<div id="myModal_updateProject" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="false"></div>	
			</div>
		</div>
		<div class="main-container ace-save-state" id="main-container">
			<?php include "blocks/navigation.php";?>

			<div class="main-content">
				<div class="main-content-inner">
					<div class="breadcrumbs ace-save-state" id="breadcrumbs">
						<ul class="breadcrumb">
							<li>
								<i class="ace-icon fa fa-home home-icon"></i>
								<a href="../index.php">Home</a>
							</li>
							<li>Client Settings</li>
							<li class="active">Project Registration</li>
						</ul><!-- /.breadcrumb -->

					</div>

					<div class="page-content">
						<div class="row">
							<div class="col-xs-12">
								<button class="btn btn-purple" onclick="showcreateproject(0);" data-toggle="modal"  data-target="#myModal_createProject" <?php if($authoritystatusidz == 2){echo "disabled";}?>><i class="ace-icon fa fa-floppy-o bigger-120"></i>Add new project</button>
							</div>
							<div class="col-xs-12">
								<div class="table-responsive">
									<table class="table table-striped table-bordered table-hover" id="dataTables-projecttb">
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
											<tr onclick = "showUpdateProject('<?php echo $projectId;?>');" data-toggle="modal" href="#myModal_updateProject" class="<?php echo $myClass; ?>">
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
										<?php
											$counter ++;
											}
										?>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div><!-- /.page-content -->
				</div>
			</div><!-- /.main-content -->

			<?php include "blocks/footer.php"; ?>

			
		</div><!-- /.main-container -->

		
	</body>
</html>
<script src="../assets/customjs/clientsettings.js"></script>

<!-- Validator-->
<script type="text/javascript" src="../assets/customjs/bootstrapValidator.min.js"></script>  
<script type="text/javascript" src="../assets/customjs/bootstrapValidator.js"></script> 
<!-- inline scripts related to this page -->
<script type="text/javascript">
 $(document).ready(function() {
        $('#dataTables-projecttb').DataTable({
            "aaSorting": [],
			"scrollCollapse": true,	
			"autoWidth": true,
			"responsive": true,
			"bSort" : false,
			"lengthMenu": [ [10, 25, 50, 100, 200, 300, 400, 500], [10, 25, 50, 100, 200, 300, 400, 500] ]
        });
	});

</script>