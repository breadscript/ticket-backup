<?php
require('../conn/db.php');
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d h:i:s",time());	

// ERROR_REPORTING(0);

$clientName=strtoupper(trim($_GET['clientName']));
$statusType=$_GET['statusType'];
// $mydatestart=$_GET['mydatestart'];
// $mydatestart = date("Y-m-d", strtotime($mydatestart));




if (($clientName == '')||($clientName == null)||($clientName== 'NULL')||($clientName== 'null')){$clientName=null;}
if (($statusType == '')||($statusType == null)||($statusType== 'NULL')||($statusType== 'null')){$statusType=null;}


$rest="";
	
	
	if($clientName!="")
	 {
		if($rest==""){$rest.=" WHERE sys_clienttb.clientname = '$clientName' ";}
		else
		{$rest.=" AND sys_clienttb.clientname  = '$clientName'  ";}
	}
	if($statusType!="")
	 {
		if($rest==""){$rest.=" WHERE sys_clienttb.clientstatusid = '$statusType' ";}
		else
		{$rest.=" AND sys_clienttb.clientstatusid  = '$statusType'  ";}
	}
// echo "Client Name:".$clientName."</br>";
// echo "rest:".$rest."</br>";
?>
<div class="row">
	<div class="col-xs-12">
		<!-- PAGE CONTENT BEGINS -->
		<div class="row">
			<div class="col-xs-12">
				<a href="exportResult_client.php?searchstring=<?php echo $rest; ?>" target="_blank" class="btn btn-sm bg-danger">Export to Excel</a> 
				<table id="simple-table" class="table  table-bordered table-hover">
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