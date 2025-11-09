<?php 
include('../check_session.php');
$moduleid=5;
$conn = connectionDB();
$useruseridx=$_GET['id2'];
$myquery = "SELECT sys_usertb.id,sys_usertb.user_firstname,sys_usertb.user_lastname,sys_usertb.username,
				sys_usertb.password,sys_usertb.user_statusid,sys_usertb.user_levelid,sys_usertb.user_groupid,
				sys_usertb.companyid,sys_statustb.statusname,sys_userleveltb.levelname,sys_clienttb.clientname,
				sys_usergrouptb.groupname,sys_usertb.emailadd
			FROM sys_usertb
			LEFT JOIN sys_statustb ON sys_statustb.id=sys_usertb.user_statusid
			LEFT JOIN sys_userleveltb ON sys_usertb.user_levelid=sys_userleveltb.id
			LEFT JOIN sys_clienttb ON sys_usertb.companyid=sys_clienttb.id
			LEFT JOIN sys_usergrouptb ON sys_usergrouptb.id=sys_usertb.user_groupid
			WHERE sys_usertb.id = '$useruseridx'
			ORDER BY sys_usertb.user_lastname ASC";
$myresult = mysqli_query($conn, $myquery);
	while($row = mysqli_fetch_assoc($myresult)){
		$userIdx = $row['id'];	
		$userLastName = $row['user_lastname'];	
		$userFirstName = $row['user_firstname'];	
		$userName = $row['username'];	
		$userLevelId = $row['user_levelid'];	
		$userLevel = $row['levelname'];	
		$userGroupId = $row['user_groupid'];	
		$userGroup = $row['groupname'];	
		$clientNameId = $row['companyid'];	
		$clientName = $row['clientname'];
		$emailAddress = $row['emailadd'];	
		$userStatusId = $row['user_statusid'];	
		$userStatus = $row['statusname'];	
		$userPassword = $row['password'];	
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
					New User Registration
				</h4>
				<p> Enter your details to begin: </p>
				<form id="updateUserInfo">
					<input type="hidden" class="form-control" placeholder="Email Address" id="user2" name="user2" value="<?php echo $userid;?>"/>
					<input type="hidden" class="form-control" placeholder="Email Address" id="userUserid2" name="userUserid2" value="<?php echo $userIdx;?>"/>
					<fieldset>
						<div class="row">
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">First Name
										<span class="block input-icon input-icon-left">
											<i class="ace-icon fa fa-user"></i>
											<input class="form-control" placeholder="First Name" id="userFirstName2" name="userFirstName2" value="<?php echo $userFirstName;?>" />
										</span>
									</label>
								</div>	
							</div>	
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Last Name
										<span class="block input-icon input-icon-left">
											<i class="ace-icon fa fa-user"></i>
											<input class="form-control" placeholder="Last Name" id="userLastName2" name="userLastName2" value="<?php echo $userLastName;?>" />
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Email
										<span class="block input-icon input-icon-left">
											<i class="ace-icon fa fa-user"></i>
											<input class="form-control" placeholder="Email" id="userEmail2" name="userEmail2" value="<?php echo $emailAddress;?>" />
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">User Name
										<span class="block input-icon input-icon-left">
											<i class="ace-icon fa fa-user"></i>
											<input class="form-control" placeholder="User Name" id="userName2" name="userName2" value="<?php echo $userName;?>" />
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Password
										<span class="block input-icon input-icon-left">
											<i class="ace-icon fa fa-user"></i>
											<input class="form-control" placeholder="Password" id="password2" name="password2" value="<?php echo $userPassword;?>" />
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">User Level
										<span class="block input-icon input-icon-left">
											<select class="form-control" id="userLevel2" name="userLevel2">
												<?php 
													$query66 = "SELECT id,levelname FROM sys_userleveltb ";
													$result66 = mysqli_query($conn, $query66);
													while($row66 = mysqli_fetch_assoc($result66)){
														$clientname=mb_convert_case($row66['levelname'], MB_CASE_TITLE, "UTF-8");
														$clientid=$row66['id'];
												?>
												<option value="<?php echo $clientid;?>"
												<?php 
													if ($userLevelId == $clientid){
														echo "selected";
													}
												?>><?php echo $clientname;?></option>
												<?php } ?>
											</select>
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">User Group
										<span class="block input-icon input-icon-left">
											<select class="form-control" id="userGroup2" name="userGroup2">
												<?php 
													$query66 = "SELECT id,groupname FROM sys_usergrouptb";
													$result66 = mysqli_query($conn, $query66);
													while($row66 = mysqli_fetch_assoc($result66)){
														$clientname=mb_convert_case($row66['groupname'], MB_CASE_TITLE, "UTF-8");
														$clientid=$row66['id'];
												?>
												<option value="<?php echo $clientid;?>"
												<?php 
													if ($userGroupId == $clientid){
														echo "selected";
													}
												?>><?php echo $clientname;?></option>
												<?php } ?>
											</select>
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Company/Client
										<span class="block input-icon input-icon-left">
											<select class="form-control" id="userClient2" name="userClient2">
												<?php 
													$query66x = "SELECT id,clientname FROM sys_clienttb";
													$result66x = mysqli_query($conn, $query66x);
													while($row66x = mysqli_fetch_assoc($result66x)){
														$clientnamex=mb_convert_case($row66x['clientname'], MB_CASE_TITLE, "UTF-8");
														$clientidx=$row66x['id'];
												?>
												<option value="<?php echo $clientidx;?>"
												<?php 
													if ($clientNameId == $clientidx){
														echo "selected";
													}
												?>><?php echo $clientnamex;?></option>
												<?php } ?>
											</select>
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">Company/Client
										<span class="block input-icon input-icon-left">
											<select class="form-control" id="userStatus2" name="userStatus2">
												<?php 
													$query66x = "SELECT id,statusname FROM sys_statustb";
													$result66x = mysqli_query($conn, $query66x);
													while($row66x = mysqli_fetch_assoc($result66x)){
														$clientnamex=mb_convert_case($row66x['statusname'], MB_CASE_TITLE, "UTF-8");
														$clientidx=$row66x['id'];
												?>
												<option value="<?php echo $clientidx;?>"
												<?php 
													if ($userStatusId == $clientidx){
														echo "selected";
													}
												?>><?php echo $clientnamex;?></option>
												<?php } ?>
											</select>
										</span>
									</label>
								</div>	
							</div>
						</div>	
						<div class="clearfix">
							<div id="flashxxx"></div>	
							<div id="insert_searchxxx"></div>	
							<button type="button" class="width-25 pull-right btn btn-sm" id="resetUserBtn2" name="resetUserBtn2" data-dismiss="modal">
								<i class="ace-icon fa fa-refresh"></i>
								<span class="bigger-110">Close</span>
							</button>

							<button type="button" class="width-25 pull-right btn btn-sm btn-success" id="submitUserBtn2" name="submitUserBtn2">
								<span class="bigger-110">Update</span>
								<i class="ace-icon fa fa-arrow-right icon-on-right"></i>
							</button>
						</div>
					</fieldset>
				</form>
			<!--</div>-->
		</div>
	</div>
</div>
<script src="../assets/customjs/adminsettings.js"></script>