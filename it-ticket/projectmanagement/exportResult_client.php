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

    <title>Client Reports</title>
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
							<th class="center">
								
							</th>
							<th class="detail-col">Company Name</th>
							<th>Address</th>
							<th>Mobile</th>
							<th class="hidden-480">Email</th>

							<th>
								<i class="ace-icon fa fa-clock-o bigger-110 hidden-480"></i>
									Person In-Charge
							</th>
							<th class="hidden-480">Status</th>
						</tr>
					</thead>
					<tbody>
					<?php 
						$conn = connectionDB();
						$counter = 1;
						$myquery = "SELECT sys_clienttb.id,sys_clienttb.clientname,sys_clienttb.clientaddressstreet,sys_clienttb.clientcontactperson,
											sys_clienttb.clientaddresscity,sys_clienttb.clientaddressstate,sys_clienttb.clientaddresscountry,
											sys_clienttb.clientemail,sys_clienttb.clientcontactnumber,sys_clientstatustb.status,sys_clienttb.clientstatusid
									FROM sys_clienttb
									LEFT JOIN sys_clientstatustb ON sys_clienttb.clientstatusid=sys_clientstatustb.id
									$rest
									ORDER BY clientname ASC";
						$myresult = mysqli_query($conn, $myquery);
							while($row = mysqli_fetch_assoc($myresult)){
								$clientId = $row['id'];	
								$myClientStatusid = $row['clientstatusid'];	
								// if($myClientStatusid == 1){$myClass = 'success';}elseif($myClientStatusid == 2){$myClass = 'info';}elseif($myClientStatusid == 3){$myClass = 'warning';}else{$myClass = 'danger';}
					?>
						<tr>
							<td class="center">
								<?php echo $counter; ?>
							</td>
							<td class="center"><?php echo $row['clientname'];?></td>
							<td><?php echo $row['clientaddressstreet']." , ".$row['clientaddresscity'];?></td>
							<td><?php echo $row['clientcontactnumber'];?></td>
							<td><?php echo $row['clientemail'];?></td>
							<td><?php echo $row['clientcontactperson'];?></td>
							<td class="hidden-480">
								<span class="label label-sm label-warning"><?php echo $row['status'];?></span>
							</td>
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