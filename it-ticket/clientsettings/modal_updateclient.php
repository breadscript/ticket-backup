<?php 
include('../check_session.php');
$moduleid=2;
include('proxy.php');
$conn = connectionDB();

$clientId=$_GET['id2'];

$myquery = "SELECT sys_clienttb.id,sys_clienttb.clientname,sys_clienttb.clientaddressstreet,
				   sys_clienttb.clientaddresscity,sys_clienttb.clientaddressstate,sys_clienttb.clientaddresscountry,sys_clienttb.clientstatusid,
				   sys_clienttb.clientemail,sys_clienttb.clientcontactnumber,sys_clienttb.clientcontactperson,sys_clienttb.clientcontactpersonposition,sys_clientstatustb.status
			FROM sys_clienttb
			LEFT JOIN sys_clientstatustb
			ON sys_clienttb.clientstatusid=sys_clientstatustb.id
			WHERE sys_clienttb.id = '$clientId'";
$myresult = mysqli_query($conn, $myquery);
while($row = mysqli_fetch_assoc($myresult)){
	$myClientname = $row['clientname'];	
	$myClientStreet = $row['clientaddressstreet'];	
	$myClientCity = $row['clientaddresscity'];	
	$myClientState = $row['clientaddressstate'];	
	$myClientCountry = $row['clientaddresscountry'];	
	$myClientEmail = $row['clientemail'];	
	$myClientContact = $row['clientcontactnumber'];	
	$myClientPerson = $row['clientcontactperson'];	
	$myClientPosition = $row['clientcontactpersonposition'];	
	$myClientStatusId = $row['clientstatusid'];	
	$myClientStatus = $row['status'];	
	$myClientId = $row['id'];	
	
	if (($myClientname == '')||($myClientname == null)||($myClientname == "NULL")){$myClientname=NULL;}
	if (($myClientStreet == '')||($myClientStreet == null)||($myClientStreet == "NULL")){$myClientStreet=NULL;}
	if (($myClientCity == '')||($myClientCity == null)||($myClientCity == "NULL")){$myClientCity=NULL;}
	if (($myClientState == '')||($myClientState == null)||($myClientState == "NULL")){$myClientState=NULL;}
	if (($myClientCountry == '')||($myClientCountry == null)||($myClientCountry == "NULL")){$myClientCountry=NULL;}
	if (($myClientEmail == '')||($myClientEmail == null)||($myClientEmail == "NULL")){$myClientEmail=NULL;}
	if (($myClientContact == '')||($myClientContact == null)||($myClientContact == "NULL")){$myClientContact=NULL;}
	if (($myClientPerson == '')||($myClientPerson == null)||($myClientPerson == "NULL")){$myClientPerson=NULL;}
	if (($myClientPosition == '')||($myClientPosition == null)||($myClientPosition == "NULL")){$myClientPosition=NULL;}
	if (($myClientStatus == '')||($myClientStatus == null)||($myClientStatus == "NULL")){$myClientStatus=NULL;}
}

	
?>
<div class="modal-body">
	<!--<div id="signup-box" class="signup-box widget-box no-border">-->
	<div id="signup-box" class="signup-box widget-box no-border">
		<div class="widget-body">
			<!--<div class="widget-main">-->
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true"><small>×</small></button>
				<h4 class="header green lighter bigger">
					<i class="ace-icon fa fa-users blue"></i>
					Update&nbsp;&nbsp;<?php echo $myClientname;?>
				</h4>
				<p> Enter your details to begin: </p>
				<form id="updateClientInfo">
					<input type="hidden" class="form-control" placeholder="Email Address" id="clientId2" name="clientId2" value="<?php echo $myClientId;?>"/>
					<input type="hidden" class="form-control" placeholder="Email Address" id="clientUserid2" name="clientUserid2" value="<?php echo $userid;?>"/>
					<fieldset>
						<div class="row">
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">
										<span class="block input-icon input-icon-left">
											<i class="ace-icon fa fa-user"></i>
											<input class="form-control" placeholder="Client/Company Name" id="clientName2" name="clientName2" value="<?php echo $myClientname;?>" />
										</span>
									</label>
								</div>	
							</div>	
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">
										<span class="block input-icon input-icon-left">
											<i class="ace-icon fa fa-envelope"></i>
											<input class="form-control" placeholder="Email Address" id="clientEmail2" name="clientEmail2" value="<?php echo $myClientEmail;?>" />
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">
										<span class="block input-icon input-icon-left">
											<i class="ace-icon fa fa-phone"></i>
											<input class="form-control" placeholder="Contact Number" id="clientContact2" name="clientContact2" value="<?php echo $myClientContact;?>" />
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">
										<span class="block input-icon input-icon-left">
											<i class="ace-icon fa fa-user"></i>
											<input class="form-control" placeholder="Contact Person" id="clientPerson2" name="clientPerson2" value="<?php echo $myClientPerson;?>" />
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">
										<span class="block input-icon input-icon-left">
											<i class="ace-icon fa-user-plus"></i>
											<input class="form-control" placeholder="Position" id="clientPosition2" name="clientPosition2" value="<?php echo $myClientPosition;?>" />
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">
										<select class="form-control" id="clientStatus2" name="clientStatus2">
											<?php 
												$query66 = "SELECT * FROM sys_clientstatustb";
												$result66 = mysqli_query($conn, $query66);
												while($row66 = mysqli_fetch_assoc($result66)){
													$statusname=mb_convert_case($row66['status'], MB_CASE_TITLE, "UTF-8");
													$statusid=$row66['id'];
											?>
											<option value="<?php echo $statusid;?>"
											<?php 
												if ($myClientStatusId == $statusid){
													echo "selected";
												}
											?>><?php echo $statusname;?></option>
											<?php } ?>
										</select>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">
										<span class="block input-icon input-icon-left">
											<input class="form-control" placeholder="Street" id="clientStreet2" name="clientStreet2" value="<?php echo $myClientStreet;?>" />
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">
										<span class="block input-icon input-icon-left">
											<input class="form-control" placeholder="City" id="clientCity2" name="clientCity2" value="<?php echo $myClientCity;?>" />
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">
										<span class="block input-icon input-icon-left">
											<input class="form-control" placeholder="State" id="clientState2" name="clientState2" value="<?php echo $myClientState;?>" />
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">
										<span class="block input-icon input-icon-left">
											<input class="form-control" placeholder="Country" id="clientCountry2" name="clientCountry2" value="<?php echo $myClientCountry;?>" />
										</span>
									</label>
								</div>	
							</div>
						</div>	
						<?php if($authoritystatusidz == 1){?>
						<div class="clearfix">
							<div id="flashup"></div>	
							<div id="insert_searchup"></div>	
							<button type="button" class="width-25 pull-right btn btn-sm" id="resetClientBtn" name="resetClientBtn" data-dismiss="modal">
								<i class="ace-icon fa fa-refresh"></i>
								<span class="bigger-110">Close</span>
							</button>

							<button type="button" class="width-25 pull-right btn btn-sm btn-success" id="submitUpdateClientBtn" name="submitUpdateClientBtn">
								<span class="bigger-110">Update</span>
								<i class="ace-icon fa fa-arrow-right icon-on-right"></i>
							</button>
						</div>
						<?php }else{
							echo "<div class='alert alert-alert fade in'>";
							echo "Access : Read Only";   		
							echo "</div>";
						} ?>
					</fieldset>
				</form>
			<!--</div>-->
		</div>
	</div>
</div>
<script src="../assets/customjs/clientsettings.js"></script>