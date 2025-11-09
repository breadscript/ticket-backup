<?php
include "blocks/header.php";
$moduleid=2;
include('proxy.php');
?>
<!DOCTYPE html>
<link rel="../stylesheet" href="../css/bootstrapValidator.min.css"/> 
<html lang="en">
	<body class="no-skin">
		
		<div class="row">
            <div class="col-lg-12">
				<div id="myModal_createClient" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="false"></div>	
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
							<li>Client Settings</li>
							<li class="active">Client Registration</li>
						</ul><!-- /.breadcrumb -->

					</div>

					<div class="page-content">
						<div class="row">
							<div class="col-xs-12">
								<button class="btn btn-purple" onclick="showcreateclient(0);" data-toggle="modal"  data-target="#myModal_createClient" <?php if($authoritystatusidz == 2){echo "disabled";}?>><i class="ace-icon fa fa-floppy-o bigger-120"></i>Add new client</button>
							</div>
							<div class="col-xs-12">
								<div class="table-responsive">
									<table class="table table-striped table-bordered table-hover" id="dataTables-examplexccz">
										<thead>
											<tr>
												<th></th>
												<th>Company Name</th>
												<th>Address</th>
												<th>Mobile</th>
												<th>Email</th>
												<th>Status</th>
											</tr>
										</thead>
										<tbody>
										<?php 
										$conn = connectionDB();
										$counter = 1;
										$myquery = "SELECT sys_clienttb.id,sys_clienttb.clientname,sys_clienttb.clientaddressstreet,
															sys_clienttb.clientaddresscity,sys_clienttb.clientaddressstate,sys_clienttb.clientaddresscountry,
															sys_clienttb.clientemail,sys_clienttb.clientcontactnumber,sys_clientstatustb.status,sys_clienttb.clientstatusid
													FROM sys_clienttb
													LEFT JOIN sys_clientstatustb
													ON sys_clienttb.clientstatusid=sys_clientstatustb.id
													WHERE sys_clienttb.id != '0'
													ORDER BY clientname ASC";
										$myresult = mysqli_query($conn, $myquery);
										while($row = mysqli_fetch_assoc($myresult)){
											$clientId = $row['id'];	
											$myClientStatusid = $row['clientstatusid'];	
											if($myClientStatusid == 1){$myClass = 'success';}elseif($myClientStatusid == 2){$myClass = 'info';}elseif($myClientStatusid == 3){$myClass = 'warning';}else{$myClass = 'danger';}
										?>
											<tr onclick = "showUpdateClient('<?php echo $clientId;?>');" data-toggle="modal" href="#myModal_updateClient" class="<?php echo $myClass; ?>">
												<td><?php echo $counter;?></td>
												<td><?php echo $row['clientname'];?></td>
												<td><?php echo $row['clientaddressstreet']." , ".$row['clientaddresscity'];?></td>
												<td><?php echo $row['clientcontactnumber'];?></td>
												<td><?php echo $row['clientemail'];?></td>
												<td><?php echo $row['status'];?></td>
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