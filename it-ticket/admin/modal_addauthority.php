<?php 
include('../check_session.php');
$moduleid=6;
$conn = connectionDB();


$id2=trim($_GET['id2']);
$useridy = null;
$moduleidy = null;
$authoritystatusidy = null;
$statidy = null;
$modulecategoryidy = null;
$userfirstnamez = null;
$userlastnamez = null;

$queryuser = "SELECT a.*,b.user_firstname,b.user_lastname
			FROM sys_authoritymoduletb a			
			LEFT JOIN sys_usertb b ON a.userid=b.id 
			WHERE a.id='$id2'
			ORDER BY a.id ASC LIMIT 1";
$resultuser = mysqli_query($conn, $queryuser);	
while($row = mysqli_fetch_assoc($resultuser)){
	$useridy = $row['userid'];	
	$moduleidy = $row['moduleid'];	
	$authoritystatusidy = $row['authoritystatusid'];	
	$statidy = $row['statid'];	
	$modulecategoryidy = $row['modulecategoryid'];	
	$userfirstnamez = $row['user_firstname'];	
	$userlastnamez = $row['user_lastname'];	
}

	$myquery = "SELECT DISTINCT a.user_groupid,b.authoritystatusid,a.user_levelid
				from sys_usertb a
				LEFT JOIN sys_authoritymoduletb b ON a.id = b.userid
				WHERE a.id=$userid";
	$myresult = mysqli_query($conn, $myquery);
	$num_rows = mysqli_num_rows($myresult);
	while($rowxyz = mysqli_fetch_assoc($myresult)){
	  $usergroupid = $rowxyz['user_groupid'];			
	  $authoritystatusidz = $rowxyz['authoritystatusid'];			
	 $userlevelid = $rowxyz['user_levelid'];			
	}
if($id2==0)			
{
	$titlestat="Add";
	$useridy = 0;
}		
else {
	$titlestat="Update";	
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
					<?php echo $titlestat; ?> User Authority
				</h4>
				<p> Enter your details to begin: </p>
				<form id="userAuth">
					<input type="hidden" class="form-control" placeholder="Email Address" id="userAuthUserid" name="userAuthUserid" value="<?php echo $userid;?>"/>
					<fieldset>
						<div class="row">
							<div class="col-lg-3">
								<div class="form-group">
									<label class="block clearfix">User
										<select class="form-control" name="useridx" id="useridx" <?php if($id2<>0){ echo "disabled";} ?>>	
											<option value=''>Please select User</option>																													                                    
											<?php
												
												if($usergroupid == 1){
													//admin groups only
													if($userlevelid == 1)
													{
														//superuser only
														$queryuser = "select distinct a.id,a.user_firstname,a.user_lastname,a.username																																																					 
														from sys_usertb a where a.user_statusid = '1'";
														$resultuser = mysqli_query($conn, $queryuser);					
													}
													elseif ($userlevelid == 2){
														//admin/manager only																																																		
														$queryuser= "select distinct a.id,a.user_firstname,a.user_lastname,a.username																																																					 
													from sys_usertb a where a.id = '$userid' and a.user_statusid = '1'";
														$resultuser = mysqli_query($conn, $queryuser);																
													}
													else {
														$queryuser= "select distinct a.id,a.user_firstname,a.user_lastname,a.username																																																					 
													from sys_usertb a where a.id = '$userid' and a.user_statusid = '1'";
														$resultuser = mysqli_query($conn, $queryuser);																
													}																															  
												}else {
													$queryuser= "select distinct a.id,a.user_firstname,a.user_lastname,a.username																																																					 
													from sys_usertb a where  a.user_statusid = '1'";
													$resultuser = mysqli_query($conn, $queryuser);														
												}
												while($rowsuser = mysqli_fetch_assoc($resultuser))
												{	
													$userprofileid= $rowsuser['id'];
													$userfirstnamez = $rowsuser['user_firstname'];		
													$userlastnamez = $rowsuser['user_lastname'];
													$username = $rowsuser['username'];																																												  				  
													?>
													<option value="<?php echo $userprofileid; ?>"
													<?php 
														if ($useridy == $userprofileid)
														{
														echo "selected";
														}
													?>
													><?php echo $userfirstnamez." ".$userlastnamez; ?></option>
												<?php
												}
												?>	
												</select>
									</label>
								</div>	
							</div>	
							<div class="col-lg-3">
								<div class="form-group" id="userdiv">
									<label class="block clearfix">Module Status
									<select class="form-control" name="moduleidx" id="moduleidx" disabled >	
										<option value=''>Select Module</option>																													                                    
										<?php
										if($usergroupid == 1){
											//admin groups only
											if($userlevelid == 1)
											{
												//superuser only																																																
												$querycompany = "select a.id as moduleid,a.modulename from sys_moduletb a
												WHERE a.modulepath <> '#'";
												$resultcompany = mysqli_query($conn, $querycompany);																																							  											
											}
											elseif ($userlevelid == 2)
											{
												//admin/manager only	
												//9999 usergroupmasteruid for common task																																																																																																																
												$querycompany = "SELECT a.id as moduleid,a.modulename from sys_moduletb a
												WHERE a.modulepath <> '#'";
												$resultcompany = mysqli_query($conn, $querycompany);																
											}
											else 
											{
											//standard user - dummy sql																																																
												$querycompany = "SELECT a.id as moduleid,a.modulename from sys_moduletb a
												WHERE a.modulepath <> '#'";
												$resultcompany = mysqli_query($conn, $querycompany);															
											}																															  
										}else{
											//standard user - dummy sql																																																
											$querycompany = "SELECT a.id as moduleid,a.modulename from sys_moduletb a
											WHERE a.modulepath <> '#'";
											$resultcompany = mysqli_query($conn, $querycompany);	
										}
											while($rowcompany = mysqli_fetch_assoc($resultcompany))
											{	
												$moduleidx= $rowcompany['moduleid'];
												$modulename = $rowcompany['modulename'];					  
												?>
												<option value="<?php echo "$moduleidx"; ?>"
												<?php 
												if ($moduleidx == $moduleidy)
												{
												echo "selected";
												}
												?>
												> <?php echo $modulename; ?> </option>
												<?php
											}
										?>	
									</select>
									</label>
								</div>	
							</div>
							
							<div class="col-lg-3">
								<div class="form-group">
									<label class="block clearfix">Authority Level
										<span class="block input-icon input-icon-left">
											<select class="form-control" id="userAuthoLevel" name="userAuthoLevel">
											<option value="">Select Level</option>
												<?php
													$query5 = "select a.* from sys_authoritystatustb a";
													$result5 = mysqli_query($conn, $query5);
													while($row5 = mysqli_fetch_assoc($result5))
													{	
													$authoritystatusidx= $row5['id'];
													$authoritystatus= $row5['authoritystatus'];																														  																											 					 
													?>
													<option value="<?php echo $authoritystatusidx; ?>"
													<?php 
													if ($authoritystatusidx == $authoritystatusidy)
													{
													echo "selected";
													}
													?>	
													> <?php echo $authoritystatus; ?> </option>
													<?php
													}
												?>	
											</select>
										</span>
									</label>
								</div>	
							</div>
							<div class="col-lg-3">
								<div class="form-group">
									<label class="block clearfix">Module Status
										<span class="block input-icon input-icon-left">
											<select class="form-control" id="userAuthmoduleStat" name="userAuthmoduleStat">
											
												<?php
													$query5 = "select a.* from sys_statustb a";
													$result5 = mysqli_query($conn, $query5);
													while($row5 = mysqli_fetch_assoc($result5))
													{	
													$userstatidq= $row5['id'];
													$statname= $row5['statusname'];																														  																											 					 
													?>
													<option value="<?php echo $userstatidq; ?>"
													<?php 
													if ($userstatidq == $statidy)
													{
													echo "selected";
													}
													?>
													> <?php echo $statname; ?> </option>
													<?php
													}
												?>	
											</select>
										</span>
									</label>
								</div>	
							</div>
						</div>	
						<div class="clearfix">
							<div id="flash2"></div>	
							<div id="insert_search2"></div>	
							<button type="button" class="width-25 pull-right btn btn-sm" id="resetUserAuthBtn" name="resetUserAuthBtn" data-dismiss="modal">
								<i class="ace-icon fa fa-refresh"></i>
								<span class="bigger-110">Close</span>
							</button>

							<button type="button" class="width-25 pull-right btn btn-sm btn-success" id="submitUserAuthBtn" name="submitUserAuthBtn">
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
<link rel="stylesheet" href="../assets/css/chosen.min.css" />
<style>
#myModal_addAuthority .modal-body,
#myModal_addAuthority .modal-dialog,
#myModal_addAuthority .modal-content {
	max-height: 80vh;
	height: auto;
}
#myModal_addAuthority .modal-body {
	max-height: 75vh;
	overflow-y: auto;
	overflow-x: visible;
	position: relative;
}
#signup-box {
	min-height: 300px;
}
/* Fix Chosen dropdown z-index and positioning */
#myModal_addAuthority .chosen-container {
	position: relative;
	z-index: 1;
}
#myModal_addAuthority .chosen-container .chosen-drop {
	z-index: 1060 !important;
}
#myModal_addAuthority .chosen-container-active .chosen-drop {
	z-index: 1060 !important;
}
/* Ensure modal doesn't clip the dropdown */
#myModal_addAuthority .modal-dialog {
	overflow: visible;
}
#myModal_addAuthority .modal-content {
	overflow: visible;
	position: relative;
}
/* Allow form-group and row to overflow properly */
#myModal_addAuthority .form-group {
	overflow: visible;
	position: relative;
}
#myModal_addAuthority .row {
	overflow: visible;
}
</style>
<script src="../assets/js/chosen.jquery.min.js"></script>
<script src="../assets/customjs/adminsettings.js"></script>
<script>
(function() {
	// Wait for jQuery and Chosen to be available
	function waitForDependencies(callback) {
		if (typeof jQuery !== 'undefined' && typeof jQuery.fn.chosen !== 'undefined') {
			callback();
		} else {
			setTimeout(function() {
				waitForDependencies(callback);
			}, 50);
		}
	}
	
	// Function to initialize Chosen
	function initChosen() {
		var $userSelect = jQuery('#useridx');
		
		// Check if element exists
		if ($userSelect.length === 0) {
			return false;
		}
		
		// Destroy existing Chosen instance if it exists
		if ($userSelect.next('.chosen-container').length > 0) {
			try {
				$userSelect.chosen('destroy');
			} catch(e) {
				// Ignore errors if destroy fails
			}
		}
		
		// Get disabled state before initialization
		var isDisabled = $userSelect.prop('disabled');
		
		try {
			// Initialize Chosen
			$userSelect.chosen({
				search_contains: true,
				placeholder_text_single: "Please select User",
				no_results_text: "No user found matching:",
				width: "100%",
				allow_single_deselect: true,
				disable_search_threshold: 10
			});
			
			// If disabled, disable Chosen as well
			if (isDisabled) {
				$userSelect.prop('disabled', true);
				$userSelect.trigger('chosen:updated');
			}
			
			// Handle the onChange event with Chosen
			$userSelect.off('change.chosen').on('change.chosen', function() {
				if (typeof getUser === 'function') {
					getUser('finduser.php?usermasteridx=' + jQuery(this).val());
				}
			});
			
			return true;
		} catch(e) {
			console.error('Error initializing Chosen:', e);
			return false;
		}
	}
	
	// Initialize when dependencies are ready
	waitForDependencies(function() {
		// Try immediate initialization
		if (!initChosen()) {
			// If element doesn't exist yet, wait for it
			var attempts = 0;
			var maxAttempts = 50; // 5 seconds max
			
			var checkInterval = setInterval(function() {
				attempts++;
				if (initChosen() || attempts >= maxAttempts) {
					clearInterval(checkInterval);
				}
			}, 100);
		}
	});
	
	// Also listen for modal show events (Bootstrap modal)
	jQuery(document).on('shown.bs.modal shown', '#myModal_addAuthority', function() {
		waitForDependencies(function() {
			setTimeout(initChosen, 200);
		});
	});
})();
</script>