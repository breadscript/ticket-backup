<?php 
include('../check_session.php');
$moduleid=5;
$conn = connectionDB();
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
				<form id="userInfo">
					<input type="hidden" class="form-control" placeholder="Email Address" id="userUserid" name="userUserid" value="<?php echo $userid;?>"/>
					<fieldset>
						<div class="row">
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">
										<span class="block input-icon input-icon-left">
											<i class="ace-icon fa fa-user"></i>
											<input class="form-control" placeholder="First Name" id="userFirstName" name="userFirstName" />
										</span>
									</label>
								</div>	
							</div>	
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">
										<span class="block input-icon input-icon-left">
											<i class="ace-icon fa fa-user"></i>
											<input class="form-control" placeholder="Last Name" id="userLastName" name="userLastName" />
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">
										<span class="block input-icon input-icon-left">
											<i class="ace-icon fa fa-user"></i>
											<input class="form-control" placeholder="Email" id="userEmail" name="userEmail" />
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">
										<span class="block input-icon input-icon-left">
											<i class="ace-icon fa fa-user"></i>
											<input class="form-control" placeholder="User Name" id="userName" name="userName" />
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-4">
								<div class="form-group">
									<label class="block clearfix">User Level
										<span class="block input-icon input-icon-left">
											<select class="form-control" id="userLevel" name="userLevel">
											<option value="">Select Level</option>
												<?php 
													$query66 = "SELECT id,levelname FROM sys_userleveltb WHERE id != '1'";
													$result66 = mysqli_query($conn, $query66);
													while($row66 = mysqli_fetch_assoc($result66)){
														$clientname=mb_convert_case($row66['levelname'], MB_CASE_TITLE, "UTF-8");
														$clientid=$row66['id'];
												?>
												<option value="<?php echo $clientid;?>"><?php echo $clientname;?></option>
												<?php } ?>
											</select>
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-4">
								<div class="form-group">
									<label class="block clearfix">User Group
										<span class="block input-icon input-icon-left">
											<select class="form-control" id="userGroup" name="userGroup">
											<option value="">Select Group</option>
												<?php 
													$query66 = "SELECT id,groupname FROM sys_usergrouptb";
													$result66 = mysqli_query($conn, $query66);
													while($row66 = mysqli_fetch_assoc($result66)){
														$clientname=mb_convert_case($row66['groupname'], MB_CASE_TITLE, "UTF-8");
														$clientid=$row66['id'];
												?>
												<option value="<?php echo $clientid;?>"><?php echo $clientname;?></option>
												<?php } ?>
											</select>
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-4">
								<div class="form-group">
									<label class="block clearfix">Company/Client
										<span class="block input-icon input-icon-left">
											<select class="form-control" id="userClient" name="userClient">
											<option value="">Select</option>
												<?php 
													$query66 = "SELECT id,clientname FROM sys_clienttb WHERE clientstatusid = '1'";
													$result66 = mysqli_query($conn, $query66);
													while($row66 = mysqli_fetch_assoc($result66)){
														$clientname=mb_convert_case($row66['clientname'], MB_CASE_TITLE, "UTF-8");
														$clientid=$row66['id'];
												?>
												<option value="<?php echo $clientid;?>"><?php echo $clientname;?></option>
												<?php } ?>
											</select>
										</span>
									</label>
								</div>	
							</div>
						</div>	
						<div class="clearfix">
							<div id="flash"></div>	
							<div id="insert_search"></div>	
							<button type="button" class="width-25 pull-right btn btn-sm" id="resetUserBtn" name="resetUserBtn" data-dismiss="modal">
								<i class="ace-icon fa fa-refresh"></i>
								<span class="bigger-110">Close</span>
							</button>

							<button type="button" class="width-25 pull-right btn btn-sm btn-success" id="submitUserBtn" name="submitUserBtn">
								<span class="bigger-110">Register</span>
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