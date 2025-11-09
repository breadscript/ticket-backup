<?php
include "blocks/header.php";
$moduleid=5;
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
				<div id="myModal_createUser" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="false"></div>	
				<div id="myModal_updateUserInfo" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="false"></div>	
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
							<li>User Account Settings</li>
							<li><button class="btn btn-purple input-xs" onclick="showcreateuser(0);" data-toggle="modal"  data-target="#myModal_createUser" <?php if($authoritystatusidz == 2){echo "disabled";}?>><i class="ace-icon fa fa-floppy-o bigger-120"></i>Add new user</button></li>
						</ul><!-- /.breadcrumb -->

					</div>

					<div class="page-content">
						<div class="row">
							<div class="col-xs-12">
								
							</div>
							<div class="col-xs-12">
								<div class="table-responsive">
									<table class="table table-striped table-bordered table-hover" id="dataTables-examplexccz">
										<thead>
											<tr>
												<th></th>
												<th>Completename</th>
												<th>Username</th>
												<th>Userlevel</th>
												<th>Group</th>
												<th>Company</th>
												<th>Email</th>
												<th>Status</th>
											</tr>
										</thead>
										<tbody>
										<?php 
										$conn = connectionDB();
										$counter = 1;
										$myquery = "SELECT sys_usertb.id,sys_usertb.user_firstname,sys_usertb.user_lastname,sys_usertb.username,
															sys_usertb.password,sys_usertb.user_statusid,sys_usertb.user_levelid,sys_usertb.user_groupid,
															sys_usertb.companyid,sys_statustb.statusname,sys_userleveltb.levelname,sys_clienttb.clientname,
															sys_usergrouptb.groupname,sys_usertb.emailadd
													FROM sys_usertb
													LEFT JOIN sys_statustb ON sys_statustb.id=sys_usertb.user_statusid
													LEFT JOIN sys_userleveltb ON sys_usertb.user_levelid=sys_userleveltb.id
													LEFT JOIN sys_clienttb ON sys_usertb.companyid=sys_clienttb.id
													LEFT JOIN sys_usergrouptb ON sys_usergrouptb.id=sys_usertb.user_groupid
													ORDER BY sys_usertb.user_lastname ASC";
										$myresult = mysqli_query($conn, $myquery);
										while($row = mysqli_fetch_assoc($myresult)){
											$userId = $row['id'];	
										?>
											<tr onclick = "showUpdateUserInfo('<?php echo $userId;?>');" data-toggle="modal" href="#myModal_updateUserInfo" class="<?php ?>">
												<td><?php echo $counter;?></td>
												<td><?php echo $row['user_lastname']." , ".$row['user_firstname'];?></td>
												<td><?php echo $row['username'];?></td>
												<td><?php echo $row['levelname'];?></td>
												<td><?php echo $row['groupname'];?></td>
												<td><?php echo $row['clientname'];?></td>
												<td><?php echo $row['emailadd'];?></td>
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

<!-- Validator-->
<script type="text/javascript" src="../assets/customjs/bootstrapValidator.min.js"></script>  
<script type="text/javascript" src="../assets/customjs/bootstrapValidator.js"></script> 
<!-- inline scripts related to this page -->
<script type="text/javascript">
 $(document).ready(function() {
        $('#dataTables-examplexccz').DataTable({
            "aaSorting": [],
			"scrollCollapse": true,	
			"autoWidth": true,
			"responsive": true,
			"bSort" : false,
			"lengthMenu": [ [10, 25, 50, 100, 200, 300, 400, 500], [10, 25, 50, 100, 200, 300, 400, 500] ]
        });
	});

</script>