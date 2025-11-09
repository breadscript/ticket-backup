<?php
include "blocks/header.php";
$moduleid=6;
include('proxy.php');
?>
<!DOCTYPE html>
<link rel="../stylesheet" href="../css/bootstrapValidator.min.css"/> 
<style>
.input-xs {
    height: 22px;
    padding: 0px 1px;
    font-size: 10px;
    line-height: 1; //If Placeholder of the input is moved up, rem/modify this.
    border-radius: 0px;
    }
</style>
<html lang="en">
	<body class="no-skin">
		
		<div class="row">
            <div class="col-lg-12">
				<div id="myModal_addAuthority" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="false"></div>	
				<div id="myModal_updateClient" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="false"></div>	
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
							<li>Access Level Settings</li>
							<li><button class="btn btn-purple input-xs" onclick="showaddauthority(0);" data-toggle="modal"  data-target="#myModal_addAuthority" <?php if($authoritystatusidz == 2){echo "disabled";}?>><i class="ace-icon fa fa-floppy-o bigger-120"></i>Add Authority</button></li>
						</ul><!-- /.breadcrumb -->

					</div>

					<div class="page-content">
						<div class="row">
							<div class="col-xs-12">
								
							</div>
							<div class="col-xs-12">
								<div class="table-responsive">
									<table class="table table-striped table-bordered table-hover" id="dataTables-examplexccz11">
										<thead>
											<tr>
												<th></th>
												<th>User</th>
												<th>Category</th>
												<th>Module</th>
												<th>Authority Status</th>	
												<th>Module Status</th>	
											</tr>
										</thead>
										<tbody>
										<?php 
										$conn = connectionDB();
										$counter = 1;
										// echo "groupid:".$usergroupid."</br>";
										// echo "userlevelid:".$userlevelid."</br>";
										if($usergroupid == 1){
												if($userlevelid == 1){
													$queryuser = "SELECT a.id as authoritymodule,b.user_firstname,b.user_lastname,c.modulename,d.authoritystatus
																		  ,e.modulecategory,f.statusname
																  FROM sys_authoritymoduletb a
																  LEFT JOIN sys_usertb b ON a.userid=b.id
																  LEFT JOIN sys_moduletb c ON a.moduleid=c.id
																  LEFT JOIN sys_authoritystatustb d ON a.authoritystatusid=d.id
																  LEFT JOIN sys_modulecategorytb e ON a.modulecategoryid=e.id
																  LEFT JOIN sys_statustb f ON a.statid=f.id
																  WHERE c.modulepath <> '#'
																  ORDER BY a.userid ASC";
													$resultuser = mysqli_query($conn, $queryuser);	
												}elseif($userlevelid == 2){
													$queryuser = "SELECT a.id as authoritymodule,b.user_firstname,b.user_lastname,c.modulename,d.authoritystatus
																		  ,e.modulecategory,f.statusname
																  FROM sys_authoritymoduletb a
																  LEFT JOIN sys_usertb b ON a.userid=b.id
																  LEFT JOIN sys_moduletb c ON a.moduleid=c.id
																  LEFT JOIN sys_authoritystatustb d ON a.authoritystatusid=d.id
																  LEFT JOIN sys_modulecategorytb e ON a.modulecategoryid=e.id
																  LEFT JOIN sys_statustb f ON a.statid=f.id
																  WHERE c.modulepath <> '#'
																  ORDER BY a.userid ASC";
													$resultuser = mysqli_query($conn, $queryuser);	
												}else{
													$queryuser = "SELECT a.id as authoritymodule,b.user_firstname,b.user_lastname,c.modulename,d.authoritystatus
																		  ,e.modulecategory,f.statusname
																  FROM sys_authoritymoduletb a
																  LEFT JOIN sys_usertb b ON a.userid=b.id
																  LEFT JOIN sys_moduletb c ON a.moduleid=c.id
																  LEFT JOIN sys_authoritystatustb d ON a.authoritystatusid=d.id
																  LEFT JOIN sys_modulecategorytb e ON a.modulecategoryid=e.id
																  LEFT JOIN sys_statustb f ON a.statid=f.id
																  WHERE c.modulepath <> '#' AND a.userid=$userid
																  ORDER BY a.userid ASC";
													$resultuser = mysqli_query($conn, $queryuser);	
												}
										}else{
											$queryuser = "SELECT a.id as authoritymodule,b.user_firstname,b.user_lastname,c.modulename,d.authoritystatus
																		  ,e.modulecategory,f.statusname
																  FROM sys_authoritymoduletb a
																  LEFT JOIN sys_usertb b ON a.userid=b.id
																  LEFT JOIN sys_moduletb c ON a.moduleid=c.id
																  LEFT JOIN sys_authoritystatustb d ON a.authoritystatusid=d.id
																  LEFT JOIN sys_modulecategorytb e ON a.modulecategoryid=e.id
																  LEFT JOIN sys_statustb f ON a.statid=f.id
																  WHERE c.modulepath <> '#' AND a.userid=$userid
																  ORDER BY a.userid ASC";
											$resultuser = mysqli_query($conn, $queryuser);	
										}
										while($row = mysqli_fetch_assoc($resultuser)){
											$clientId = $row['authoritymodule'];	
										?>
											<tr onclick = "showUpdateClient('<?php echo $clientId;?>');" data-toggle="modal" href="#myModal_updateClient" class="<?php ?>">
												<td><?php echo $counter;?></td>
												<td><?php echo $row['user_lastname']." , ".$row['user_firstname'];?></td>
												<td><?php echo $row['modulecategory'];?></td>
												<td><?php echo $row['modulename'];?></td>
												<td><?php echo $row['authoritystatus'];?></td>
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
<script src="../assets/customjs/adminsettings.js"></script>
<script src="../assets/customjs/userdropdown.js"></script>

<!-- Validator-->
<script type="text/javascript" src="../assets/customjs/bootstrapValidator.min.js"></script>  
<script type="text/javascript" src="../assets/customjs/bootstrapValidator.js"></script> 
<!-- inline scripts related to this page -->
<script type="text/javascript">
 $(document).ready(function() {
        $('#dataTables-examplexccz11').DataTable({
            "aaSorting": [],
			"scrollCollapse": true,	
			"autoWidth": true,
			"responsive": true,
			"bSort" : false,
			"lengthMenu": [ [10, 25, 50, 100, 200, 300, 400, 500], [10, 25, 50, 100, 200, 300, 400, 500] ]
        });
	});

</script>