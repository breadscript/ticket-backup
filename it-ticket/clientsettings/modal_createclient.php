<?php 
include('../check_session.php');
$moduleid=2;
?>
<div class="modal-body">
	<!--<div id="signup-box" class="signup-box widget-box no-border">-->
	<div id="signup-box" class="signup-box widget-box no-border">
		<div class="widget-body">
			<!--<div class="widget-main">-->
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true"><small>×</small></button>
				<h4 class="header green lighter bigger">
					<i class="ace-icon fa fa-users blue"></i>
					New Client Registration
				</h4>
				<p> Enter your details to begin: </p>
				<form id="clientInfo">
					<input type="hidden" class="form-control" placeholder="Email Address" id="clientUserid" name="clientUserid" value="<?php echo $userid;?>"/>
					<fieldset>
						<div class="row">
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">
										<span class="block input-icon input-icon-left">
											<i class="ace-icon fa fa-user"></i>
											<input class="form-control" placeholder="Client/Company Name" id="clientName" name="clientName" />
										</span>
									</label>
								</div>	
							</div>	
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">
										<span class="block input-icon input-icon-left">
											<i class="ace-icon fa fa-envelope"></i>
											<input class="form-control" placeholder="Email Address" id="clientEmail" name="clientEmail" />
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-4">
								<div class="form-group">
									<label class="block clearfix">
										<span class="block input-icon input-icon-left">
											<i class="ace-icon fa fa-phone"></i>
											<input class="form-control" placeholder="Contact Number" id="clientContact" name="clientContact" />
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-4">
								<div class="form-group">
									<label class="block clearfix">
										<span class="block input-icon input-icon-left">
											<i class="ace-icon fa fa-user"></i>
											<input class="form-control" placeholder="Contact Person" id="clientPerson" name="clientPerson" />
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-4">
								<div class="form-group">
									<label class="block clearfix">
										<span class="block input-icon input-icon-left">
											<i class="ace-icon fa-user-plus"></i>
											<input class="form-control" placeholder="Position" id="clientPosition" name="clientPosition" />
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">
										<span class="block input-icon input-icon-left">
											<input class="form-control" placeholder="Street" id="clientStreet" name="clientStreet" />
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">
										<span class="block input-icon input-icon-left">
											<input class="form-control" placeholder="City" id="clientCity" name="clientCity" />
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">
										<span class="block input-icon input-icon-left">
											<input class="form-control" placeholder="State" id="clientState" name="clientState" />
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label class="block clearfix">
										<span class="block input-icon input-icon-left">
											<input class="form-control" placeholder="Country" id="clientCountry" name="clientCountry" />
										</span>
									</label>
								</div>	
							</div>
						</div>	
						<div class="clearfix">
							<div id="flash"></div>	
							<div id="insert_search"></div>	
							<button type="button" class="width-25 pull-right btn btn-sm" id="resetClientBtn" name="resetClientBtn" data-dismiss="modal">
								<i class="ace-icon fa fa-refresh"></i>
								<span class="bigger-110">Close</span>
							</button>

							<button type="button" class="width-25 pull-right btn btn-sm btn-success" id="submitClientBtn" name="submitClientBtn">
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
<script src="../assets/customjs/clientsettings.js"></script>