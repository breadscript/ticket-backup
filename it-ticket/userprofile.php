<?php
session_start();
include "check_session.php";
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta charset="utf-8" />
		<title>User Profile</title>
		<!-- Favicon -->
		<link rel="icon" type="image/png" href="assets/images/favicon/favicon-32x32.png" />
		
		<meta name="description" content="overview &amp; stats" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

		<!-- bootstrap & fontawesome -->
		<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
		<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

		<!-- page specific plugin styles -->
		<!-- Cropper.js CSS -->
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">

		<!-- text fonts -->
		<link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />

		<!-- ace styles -->
		<link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />

		<!--[if lte IE 9]>
			<link rel="stylesheet" href="assets/css/ace-part2.min.css" class="ace-main-stylesheet" />
		<![endif]-->
		<link rel="stylesheet" href="assets/css/ace-skins.min.css" />
		<link rel="stylesheet" href="assets/css/ace-rtl.min.css" />

		<!--[if lte IE 9]>
		  <link rel="stylesheet" href="assets/css/ace-ie.min.css" />
		<![endif]-->

		<!-- inline styles related to this page -->

		<!-- ace settings handler -->
		<script src="assets/js/ace-extra.min.js"></script>

		<!-- HTML5shiv and Respond.js for IE8 to support HTML5 elements and media queries -->

		<!--[if lte IE 8]>
		<script src="assets/js/html5shiv.min.js"></script>
		<script src="assets/js/respond.min.js"></script>
		<![endif]-->
		<style>
			.infobox-container {
				display: flex;
				flex-direction: column;
				gap: 20px;
			}

			.infobox-row {
				display: flex;
				flex-wrap: wrap;
				gap: 10px;
				justify-content: flex-start;
			}

			.infobox {
				flex: 0 0 calc(33.33% - 10px); /* Makes 3 boxes per row with gap */
				margin: 0 !important;
				min-width: 200px;
			}

			/* Ensure the total counter takes full width */
			.infobox.total-counter {
				flex: 0 0 100%;
				margin-bottom: 10px !important;
			}

			/* Add these new styles */
			.infobox-link {
				text-decoration: none !important;
				color: inherit !important;
				cursor: pointer;
				display: block;
			}

			.infobox-link:hover .infobox {
				transform: translateY(-2px);
				box-shadow: 0 4px 8px rgba(0,0,0,0.1);
				transition: all 0.3s ease;
			}

			.infobox {
				transition: all 0.3s ease;
				/* ... your existing infobox styles ... */
			}

			.has-success .form-control {
				border-color: #87b87f;
			}

			.has-error .form-control {
				border-color: #d68273;
			}

			.help-block {
				color: #737373;
				margin-top: 5px;
				margin-bottom: 0;
			}

			.has-error .help-block.password-feedback {
				color: #d68273;
			}

			.password-feedback {
				font-size: 12px;
				margin-top: 5px;
				display: block;
			}

			.text-success {
				color: #87b87f !important;
			}

			.text-danger {
				color: #d68273 !important;
			}

			.security-feedback {
				font-size: 12px;
				margin-top: 5px;
				display: block;
				position: absolute;
			}

			.input-group + .security-feedback {
				margin-top: 10px;
			}

			.cropper-container {
				margin: 10px 0;
				max-width: 100%;
			}
			
			.image-preview-wrapper {
				position: relative;
				overflow: hidden;
				width: 300px;
				height: 300px;
				border: 1px solid #ddd;
				border-radius: 5px;
			}
			
			.cropper-view-box,
			.cropper-face {
				border-radius: 50%;
			}
			
			.preview-container {
				display: flex;
				flex-direction: column;
				align-items: center;
				margin-top: 15px;
			}
			
			.preview {
				overflow: hidden;
				width: 150px;
				height: 150px;
				border: 1px solid #ddd;
				border-radius: 50%;
			}
			
			.preview img {
				max-width: 100%;
			}
			
			.cropper-controls {
				margin-top: 10px;
				display: flex;
				gap: 5px;
				flex-wrap: wrap;
			}
		</style>
	</head>

	<body class="no-skin">
		<?php include "blocks/header.php"; ?>
		<?php $conn = connectionDB(); ?>
		<?php
		// Use a default value of 0 if 'userid' is not set in the session
		$user_id = $_SESSION['userid'] ?? 0;

		if ($user_id) {
			// Fetch user details from the database
			$userDetailsSql = "SELECT * FROM sys_usertb WHERE id = " . intval($user_id);
			$userDetailsResult = mysqli_query($conn, $userDetailsSql);

			if ($userDetailsResult && mysqli_num_rows($userDetailsResult) > 0) {
				$user = mysqli_fetch_assoc($userDetailsResult);
			} else {
				$user = [];
			}
		} else {
			// Handle the case where the user is not logged in
			$user = [];
		}
		?>

		<div class="main-container ace-save-state" id="main-container">
			<?php include "blocks/navigation.php";?>

			<div class="main-content">
				<div class="main-content-inner">
					<div class="breadcrumbs ace-save-state" id="breadcrumbs">
						<ul class="breadcrumb">
							<li>
								<i class="ace-icon fa fa-home home-icon"></i>
								<a href="index.php">Home</a>
							</li>
							<li class="active">User Profile</li>
						</ul><!-- /.breadcrumb -->

						
						<div class="nav-search" id="nav-search">
							<form class="form-search">
								<span class="input-icon">
									<input type="text" placeholder="Search ..." class="nav-search-input" id="nav-search-input" autocomplete="off" />
									<i class="ace-icon fa fa-search nav-search-icon"></i>
								</span>
							</form>
						</div><!-- /.nav-search -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								User Profile
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									my profile
								</small>
							</h1>
						</div>

						<!-- Move entire tab system to top level -->
						<div class="row">
							<div class="col-xs-12">
								<div class="tabbable">
									<ul class="nav nav-tabs" id="myTab">
										<li class="active">
											<a data-toggle="tab" href="#profile">
												<i class="ace-icon fa fa-user bigger-110"></i>
												Profile
											</a>
										</li>
										<li>
											<a data-toggle="tab" href="#password">
												<i class="ace-icon fa fa-key bigger-110"></i>
												Change Password
											</a>
										</li>
									</ul>

									<div class="tab-content">
										<div id="profile" class="tab-pane in active">
											<div class="row">
												<!-- Profile Details - Left Column -->
												<div class="col-sm-5">
													<div class="widget-box transparent">
														<div class="row">
															<div class="col-md-12">
																<div class="page-header">
																	<div class="user-info-row" style="display: flex; align-items: center; gap: 8px;">
																		<div class="user-info-container" style="display: flex; align-items: center; gap: 8px; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
																			<img id="profile-image" src="<?php echo !empty($_SESSION['userphoto']) ? htmlspecialchars($_SESSION['userphoto']) : 'assets/images/avatars/user.png'; ?>" alt="User Photo" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
																		</div>
																		<h4 class="widget-title lighter">
																			<i class="ace-icon fa fa-star orange"></i>
																			<span class="editable" id="username"><?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?></span>
																		</h4>
																	</div>
																</div>

																<!-- Add Profile Picture Upload Form -->
																<div class="profile-picture-upload" style="margin-bottom: 20px;">
																	<form id="profile-picture-form" enctype="multipart/form-data">
																		<div class="form-group">
																			<label for="profile_picture">Change Profile Picture</label>
																			<input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="form-control">
																			<p class="help-block">Supported formats: JPG, PNG, GIF. Max size: 2MB</p>
																		</div>
																		<div class="image-preview-container" style="display: none; margin: 10px 0;">
																			<div class="image-preview-wrapper">
																				<img id="image-preview" src="" alt="Preview">
																			</div>
																			<div class="preview-container">
																				<div class="preview"></div>
																				<p class="help-block">Preview</p>
																			</div>
																			<div class="cropper-controls">
																				<button type="button" id="rotate-left" class="btn btn-xs btn-info">
																					<i class="ace-icon fa fa-rotate-left"></i> Rotate Left
																				</button>
																				<button type="button" id="rotate-right" class="btn btn-xs btn-info">
																					<i class="ace-icon fa fa-rotate-right"></i> Rotate Right
																				</button>
																				<button type="button" id="zoom-in" class="btn btn-xs btn-info">
																					<i class="ace-icon fa fa-search-plus"></i> Zoom In
																				</button>
																				<button type="button" id="zoom-out" class="btn btn-xs btn-info">
																					<i class="ace-icon fa fa-search-minus"></i> Zoom Out
																				</button>
																				<button type="button" id="reset-crop" class="btn btn-xs btn-warning">
																					<i class="ace-icon fa fa-refresh"></i> Reset
																				</button>
																			</div>
																		</div>
																		<button type="submit" class="btn btn-sm btn-primary">
																			<i class="ace-icon fa fa-upload"></i> Upload
																		</button>
																	</form>
																	<div class="alert alert-danger" id="upload-error" style="display: none; margin-top: 10px;">
																		<button type="button" class="close" data-dismiss="alert">
																			<i class="ace-icon fa fa-times"></i>
																		</button>
																		<span id="upload-error-message"></span>
																	</div>
																	<div class="alert alert-success" id="upload-success" style="display: none; margin-top: 10px;">
																		<button type="button" class="close" data-dismiss="alert">
																			<i class="ace-icon fa fa-times"></i>
																		</button>
																		Profile picture successfully updated!
																	</div>
																</div>
																
																<div class="profile-user-info profile-user-info-striped">
																	<div class="profile-info-row">
																		<div class="profile-info-name"> User ID </div>
																		<div class="profile-info-value">
																			<span class="editable" id="user_id"><?php echo htmlspecialchars($user['id'] ?? 'N/A'); ?></span>
																		</div>
																	</div>

																	<div class="profile-info-row">
																		<div class="profile-info-name"> Fullname </div>
																		<div class="profile-info-value">
																			<span class="editable" id="fullname">
																				<?php 
																					$fullname = trim(($user['user_firstname'] ?? '') . ' , ' . ($user['user_lastname'] ?? ''));
																					echo htmlspecialchars($fullname ?: 'N/A'); 
																				?>
																			</span>
																		</div>
																	</div>
																	<div class="profile-info-row">
																		<div class="profile-info-name"> Email </div>
																		<div class="profile-info-value">
																			<span class="editable" id="email"><?php echo htmlspecialchars($user['emailadd'] ?? 'N/A'); ?></span>
																		</div>
																	</div>
																	<hr class="hr-8 dotted">
																	<div class="profile-info-row">
																		<div class="profile-info-name"> Company </div>
																		<div class="profile-info-value">
																			<?php 
																			$companyName = 'N/A';
																			if (!empty($user['id'])) {
																				$company_sql = "
																					SELECT c.clientname 
																					FROM sys_usertb u
																					LEFT JOIN sys_clienttb c ON u.companyid = c.id
																					WHERE u.id = " . intval($user['id']);
																				$company_result = mysqli_query($conn, $company_sql);
																				
																				if ($company_result && mysqli_num_rows($company_result) > 0) {
																					$company = mysqli_fetch_assoc($company_result);
																					$companyName = htmlspecialchars($company['clientname']);  
																				}
																			}
																			echo '<span class="editable" id="company">' . $companyName . '</span>';
																			?>
																		</div>
																	</div>

																	<div class="profile-info-row">
																		<div class="profile-info-name"> User Level</div>
																		<div class="profile-info-value">
																			<?php 
																			$userLevelName = 'N/A';
																			if (!empty($user['id'])) {
																				$user_level_sql = "
																					SELECT l.levelname 
																					FROM sys_usertb u
																					LEFT JOIN sys_userleveltb l ON u.user_levelid = l.id
																					WHERE u.id = " . intval($user['id']);
																				$user_level_result = mysqli_query($conn, $user_level_sql);
																				
																				if ($user_level_result && mysqli_num_rows($user_level_result) > 0) {
																					$user_level = mysqli_fetch_assoc($user_level_result);
																					$userLevelName = htmlspecialchars($user_level['levelname']);  
																				}
																			}
																			echo '<span class="editable" id="user_level">' . $userLevelName . '</span>';
																			?>
																		</div>
																	</div>

																	<div class="profile-info-row">
																		<div class="profile-info-name"> User Group</div>
																		<div class="profile-info-value">
																			<?php 
																			$userGroupName = 'N/A';
																			if (!empty($user['id'])) {
																				$user_group_sql = "
																					SELECT g.groupname 
																					FROM sys_usertb u
																					LEFT JOIN sys_usergrouptb g ON u.user_groupid = g.id
																					WHERE u.id = " . intval($user['id']);
																				$user_group_result = mysqli_query($conn, $user_group_sql);
																				
																				if ($user_group_result && mysqli_num_rows($user_group_result) > 0) {
																					$user_group = mysqli_fetch_assoc($user_group_result);
																					$userGroupName = htmlspecialchars($user_group['groupname']);  
																				}
																			}
																			echo '<span class="editable" id="user_group">' . $userGroupName . '</span>';
																			?>
																		</div>
																	</div>
																</div>
															</div>
														</div>
													</div>
												</div>

												<!-- Statistics - Right Column -->
												<div class="col-sm-7">
													<div class="infobox-container">
														<!-- Total Tickets Counter -->
														<div class="row">
															<div class="col-xs-12">
																<a href="#" onclick="return handleInfoboxClick('tickets', 'all')" class="infobox-link">
																	<div class="infobox infobox-green total-counter">
																		<div class="infobox-icon">
																			<i class="ace-icon fa fa-ticket"></i>
																		</div>
																		<div class="infobox-data">
																			<?php
																			// Get total tickets created by the user where istask = 2
																			$tickets_sql = "SELECT COUNT(*) as ticket_count 
																						FROM pm_projecttasktb 
																						WHERE createdbyid = " . intval($user_id) . "
																						AND istask = 2";
																			$tickets_result = mysqli_query($conn, $tickets_sql);
																			$ticket_count = 0;
																			
																			if ($tickets_result && mysqli_num_rows($tickets_result) > 0) {
																				$tickets = mysqli_fetch_assoc($tickets_result);
																				$ticket_count = $tickets['ticket_count'];
																			}
																			?>
																			<span class="infobox-data-number"><?php echo $ticket_count; ?></span>
																			<div class="infobox-content">Total Tickets</div>
																		</div>
																	</div>
																</a>
															</div>
														</div>

														<!-- Tickets Status Boxes -->
														<div class="row">
															<div class="col-xs-12">
																<div class="infobox-row">
																	<!-- New -->
																	<a href="#" onclick="return handleInfoboxClick('tickets', '1')" class="infobox-link">
																		<div class="infobox infobox-blue">
																			<div class="infobox-progress">
																				<div class="easy-pie-chart percentage" data-percent="100" data-size="55">
																					<?php
																					// Get count of tickets with statusid = 1 (New)
																					$new_sql = "SELECT COUNT(*) as new_count 
																								FROM pm_projecttasktb 
																								WHERE createdbyid = " . intval($user_id) . "
																								AND statusid = 1
																								AND istask = 2";
																					$new_result = mysqli_query($conn, $new_sql);
																					$new_count = 0;
																					
																					if ($new_result && mysqli_num_rows($new_result) > 0) {
																						$new = mysqli_fetch_assoc($new_result);
																						$new_count = $new['new_count'];
																					}
																					?>
																					<span class="percent"><?php echo $new_count; ?></span>
																				</div>
																			</div>
																			<div class="infobox-data">
																				<span class="infobox-text">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;New</span>
																				<div class="infobox-content">
																					<span class="bigger-110">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tickets</span>
																				</div>
																			</div>
																		</div>
																	</a>

																	<!-- In Progress -->
																	<a href="#" onclick="return handleInfoboxClick('tickets', '3')" class="infobox-link">
																		<div class="infobox infobox-pink">
																			<div class="infobox-progress">
																				<div class="easy-pie-chart percentage" data-percent="50" data-size="55">
																					<?php
																					// Get count of tickets with statusid = 3 (InProg)
																					$in_progress_sql = "SELECT COUNT(*) as in_progress_count 
																							  FROM pm_projecttasktb 
																							  WHERE createdbyid = " . intval($user_id) . "
																							  AND statusid = 3
																							  AND istask = 2";
																					$in_progress_result = mysqli_query($conn, $in_progress_sql);
																					$in_progress_count = 0;
																					
																					if ($in_progress_result && mysqli_num_rows($in_progress_result) > 0) {
																						$in_progress = mysqli_fetch_assoc($in_progress_result);
																						$in_progress_count = $in_progress['in_progress_count'];
																					}
																					?>
																					<span class="percent"><?php echo $in_progress_count; ?></span>
																				</div>
																			</div>
																			<div class="infobox-data">
																				<span class="infobox-text">&nbsp;&nbsp;&nbsp;&nbsp;In Progress</span>
																				<div class="infobox-content">
																					<span class="bigger-110">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tickets</span>
																				</div>
																			</div>
																		</div>
																	</a>

																	<!-- Pending -->
																	<a href="#" onclick="return handleInfoboxClick('tickets', '4')" class="infobox-link">
																		<div class="infobox infobox-orange2">
																			<div class="infobox-progress">
																				<div class="easy-pie-chart percentage" data-percent="100" data-size="55">
																					<?php
																					// Get count of tickets with statusid = 4 (Pending)
																					$pending_sql = "SELECT COUNT(*) as pending_count 
																							  FROM pm_projecttasktb 
																							  WHERE createdbyid = " . intval($user_id) . "
																							  AND statusid = 4
																							  AND istask = 2";
																					$pending_result = mysqli_query($conn, $pending_sql);
																					$pending_count = 0;
																					
																					if ($pending_result && mysqli_num_rows($pending_result) > 0) {
																						$pending = mysqli_fetch_assoc($pending_result);
																						$pending_count = $pending['pending_count'];
																					}
																					?>
																					<span class="percent"><?php echo $pending_count; ?></span>
																				</div>
																			</div>
																			<div class="infobox-data">
																				<span class="infobox-text">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Pending</span>
																				<div class="infobox-content">
																					<span class="bigger-110">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tickets</span>
																				</div>
																			</div>
																		</div>
																	</a>

																	<!-- Done -->
																	<a href="#" onclick="return handleInfoboxClick('tickets', '6')" class="infobox-link">
																		<div class="infobox infobox-green">
																			<div class="infobox-progress">
																				<div class="easy-pie-chart percentage" data-percent="100" data-size="55">
																					<?php
																					// Get count of tickets with statusid = 6 (Done)
																					$done_sql = "SELECT COUNT(*) as done_count 
																								FROM pm_projecttasktb 
																								WHERE createdbyid = " . intval($user_id) . "
																								AND statusid = 6
																								AND istask = 2";
																					$done_result = mysqli_query($conn, $done_sql);
																					$done_count = 0;
																					
																					if ($done_result && mysqli_num_rows($done_result) > 0) {
																						$done = mysqli_fetch_assoc($done_result);
																						$done_count = $done['done_count'];
																					}
																					?>
																					<span class="percent"><?php echo $done_count; ?></span>
																				</div>
																			</div>
																			<div class="infobox-data">
																				<span class="infobox-text">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Done</span>
																				<div class="infobox-content">
																					<span class="bigger-110">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tickets</span>
																				</div>
																			</div>
																		</div>
																	</a>

																	<!-- Rejected -->
																	<a href="#" onclick="return handleInfoboxClick('tickets', '2')" class="infobox-link">
																		<div class="infobox infobox-orange2">
																			<div class="infobox-progress">
																				<div class="easy-pie-chart percentage" data-percent="100" data-size="55">
																					<?php
																					// Get count of tickets with statusid = 2 (Rejected)
																					$reject_sql = "SELECT COUNT(*) as reject_count 
																							 FROM pm_projecttasktb 
																							 WHERE createdbyid = " . intval($user_id) . "
																							 AND statusid = 2
																							 AND istask = 2";
																					$reject_result = mysqli_query($conn, $reject_sql);
																					$reject_count = 0;
																					
																					if ($reject_result && mysqli_num_rows($reject_result) > 0) {
																						$reject = mysqli_fetch_assoc($reject_result);
																						$reject_count = $reject['reject_count'];
																					}
																					?>
																					<span class="percent"><?php echo $reject_count; ?></span>
																				</div>
																			</div>
																			<div class="infobox-data">
																				<span class="infobox-text">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Reject</span>
																				<div class="infobox-content">
																					<span class="bigger-110">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tickets</span>
																				</div>
																			</div>
																		</div>
																	</a>

																	<!-- Cancelled -->
																	<a href="#" onclick="return handleInfoboxClick('tickets', '5')" class="infobox-link">
																		<div class="infobox infobox-orange2">
																			<div class="infobox-progress">
																				<div class="easy-pie-chart percentage" data-percent="100" data-size="55">
																					<?php
																					// Get count of tickets with statusid = 5 (Cancelled)
																					$cancelled_sql = "SELECT COUNT(*) as cancelled_count 
																								FROM pm_projecttasktb 
																								WHERE createdbyid = " . intval($user_id) . "
																								AND statusid = 5
																								AND istask = 2";
																					$cancelled_result = mysqli_query($conn, $cancelled_sql);
																					$cancelled_count = 0;
																					
																					if ($cancelled_result && mysqli_num_rows($cancelled_result) > 0) {
																						$cancelled = mysqli_fetch_assoc($cancelled_result);
																						$cancelled_count = $cancelled['cancelled_count'];
																					}
																					?>
																					<span class="percent"><?php echo $cancelled_count; ?></span>
																				</div>
																			</div>
																			<div class="infobox-data">
																				<span class="infobox-text">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Cancelled</span>
																				<div class="infobox-content">
																					<span class="bigger-110">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tickets</span>
																				</div>
																			</div>
																		</div>
																	</a>
																</div>
															</div>
														</div>

														<!-- Total Assignments Counter -->
														<div class="row">
															<div class="col-xs-12">
																<a href="#" onclick="return handleInfoboxClick('assignments', 'all')" class="infobox-link">
																	<div class="infobox infobox-blue total-counter">
																		<div class="infobox-icon">
																			<i class="ace-icon fa fa-tasks"></i>
																		</div>
																		<div class="infobox-data">
																			<?php
																			// Get total assignments created by the user where istask = 1
																			$assignments_sql = "SELECT COUNT(*) as assignment_count 
																						FROM pm_projecttasktb 
																						WHERE createdbyid = " . intval($user_id) . "
																						AND istask = 1";
																			$assignments_result = mysqli_query($conn, $assignments_sql);
																			$assignment_count = 0;
																			
																			if ($assignments_result && mysqli_num_rows($assignments_result) > 0) {
																				$assignments = mysqli_fetch_assoc($assignments_result);
																				$assignment_count = $assignments['assignment_count'];
																			}
																			?>
																			<span class="infobox-data-number"><?php echo $assignment_count; ?></span>
																			<div class="infobox-content">Total Assignment</div>
																		</div>
																	</div>
																</a>
															</div>
														</div>

														<!-- Assignment Status Boxes -->
														<div class="row">
															<div class="col-xs-12">
																<div class="infobox-row">
																	<!-- New -->
																	<a href="#" onclick="return handleInfoboxClick('assignments', '1')" class="infobox-link">
																		<div class="infobox infobox-blue">
																			<div class="infobox-progress">
																				<div class="easy-pie-chart percentage" data-percent="100" data-size="55">
																					<?php
																					// Get count of assignments with statusid = 1 (New)
																					$assign_new_sql = "SELECT COUNT(*) as new_count 
																							 FROM pm_projecttasktb 
																							 WHERE createdbyid = " . intval($user_id) . "
																							 AND statusid = 1
																							 AND istask = 1";
																					$assign_new_result = mysqli_query($conn, $assign_new_sql);
																					$assign_new_count = 0;
																					
																					if ($assign_new_result && mysqli_num_rows($assign_new_result) > 0) {
																						$assign_new = mysqli_fetch_assoc($assign_new_result);
																						$assign_new_count = $assign_new['new_count'];
																					}
																					?>
																					<span class="percent"><?php echo $assign_new_count; ?></span>
																				</div>
																			</div>
																			<div class="infobox-data">
																				<span class="infobox-text">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;New</span>
																				<div class="infobox-content">
																					<span class="bigger-110">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Assignments</span>
																				</div>
																			</div>
																		</div>
																	</a>

																	<!-- In Progress -->
																	<a href="#" onclick="return handleInfoboxClick('assignments', '3')" class="infobox-link">
																		<div class="infobox infobox-pink">
																			<div class="infobox-progress">
																				<div class="easy-pie-chart percentage" data-percent="50" data-size="55">
																					<?php
																					// Get count of assignments with statusid = 3 (InProg)
																					$assign_in_progress_sql = "SELECT COUNT(*) as in_progress_count 
																							 FROM pm_projecttasktb 
																							 WHERE createdbyid = " . intval($user_id) . "
																							 AND statusid = 3
																							 AND istask = 1";
																					$assign_in_progress_result = mysqli_query($conn, $assign_in_progress_sql);
																					$assign_in_progress_count = 0;
																					
																					if ($assign_in_progress_result && mysqli_num_rows($assign_in_progress_result) > 0) {
																						$assign_in_progress = mysqli_fetch_assoc($assign_in_progress_result);
																						$assign_in_progress_count = $assign_in_progress['in_progress_count'];
																					}
																					?>
																					<span class="percent"><?php echo $assign_in_progress_count; ?></span>
																				</div>
																			</div>
																			<div class="infobox-data">
																				<span class="infobox-text">&nbsp;&nbsp;&nbsp;&nbsp;In Progress</span>
																				<div class="infobox-content">
																					<span class="bigger-110">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Assignments</span>
																				</div>
																			</div>
																		</div>
																	</a>

																	<!-- Pending -->
																	<a href="#" onclick="return handleInfoboxClick('assignments', '4')" class="infobox-link">
																		<div class="infobox infobox-orange2">
																			<div class="infobox-progress">
																				<div class="easy-pie-chart percentage" data-percent="100" data-size="55">
																					<?php
																					// Get count of assignments with statusid = 4 (Pending)
																					$assign_pending_sql = "SELECT COUNT(*) as pending_count 
																							 FROM pm_projecttasktb 
																							 WHERE createdbyid = " . intval($user_id) . "
																							 AND statusid = 4
																							 AND istask = 1";
																					$assign_pending_result = mysqli_query($conn, $assign_pending_sql);
																					$assign_pending_count = 0;
																					
																					if ($assign_pending_result && mysqli_num_rows($assign_pending_result) > 0) {
																						$assign_pending = mysqli_fetch_assoc($assign_pending_result);
																						$assign_pending_count = $assign_pending['pending_count'];
																					}
																					?>
																					<span class="percent"><?php echo $assign_pending_count; ?></span>
																				</div>
																			</div>
																			<div class="infobox-data">
																				<span class="infobox-text">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Pending</span>
																				<div class="infobox-content">
																					<span class="bigger-110">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Assignments</span>
																				</div>
																			</div>
																		</div>
																	</a>

																	<!-- Done -->
																	<a href="#" onclick="return handleInfoboxClick('assignments', '6')" class="infobox-link">
																		<div class="infobox infobox-green">
																			<div class="infobox-progress">
																				<div class="easy-pie-chart percentage" data-percent="100" data-size="55">
																					<?php
																					// Get count of assignments with statusid = 6 (Done)
																					$assign_done_sql = "SELECT COUNT(*) as done_count 
																							 FROM pm_projecttasktb 
																							 WHERE createdbyid = " . intval($user_id) . "
																							 AND statusid = 6
																							 AND istask = 1";
																					$assign_done_result = mysqli_query($conn, $assign_done_sql);
																					$assign_done_count = 0;
																					
																					if ($assign_done_result && mysqli_num_rows($assign_done_result) > 0) {
																						$assign_done = mysqli_fetch_assoc($assign_done_result);
																						$assign_done_count = $assign_done['done_count'];
																					}
																					?>
																					<span class="percent"><?php echo $assign_done_count; ?></span>
																				</div>
																			</div>
																			<div class="infobox-data">
																				<span class="infobox-text">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Done</span>
																				<div class="infobox-content">
																					<span class="bigger-110">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Assignments</span>
																				</div>
																			</div>
																		</div>
																	</a>

																	<!-- Rejected -->
																	<a href="#" onclick="return handleInfoboxClick('assignments', '2')" class="infobox-link">
																		<div class="infobox infobox-orange2">
																			<div class="infobox-progress">
																				<div class="easy-pie-chart percentage" data-percent="100" data-size="55">
																					<?php
																					// Get count of assignments with statusid = 2 (Rejected)
																					$assign_reject_sql = "SELECT COUNT(*) as reject_count 
																							 FROM pm_projecttasktb 
																							 WHERE createdbyid = " . intval($user_id) . "
																							 AND statusid = 2
																							 AND istask = 1";
																					$assign_reject_result = mysqli_query($conn, $assign_reject_sql);
																					$assign_reject_count = 0;
																					
																					if ($assign_reject_result && mysqli_num_rows($assign_reject_result) > 0) {
																						$assign_reject = mysqli_fetch_assoc($assign_reject_result);
																						$assign_reject_count = $assign_reject['reject_count'];
																					}
																					?>
																					<span class="percent"><?php echo $assign_reject_count; ?></span>
																				</div>
																			</div>
																			<div class="infobox-data">
																				<span class="infobox-text">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Reject</span>
																				<div class="infobox-content">
																					<span class="bigger-110">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Assignments</span>
																				</div>
																			</div>
																		</div>
																	</a>

																	<!-- Cancelled -->
																	<a href="#" onclick="return handleInfoboxClick('assignments', '5')" class="infobox-link">
																		<div class="infobox infobox-orange2">
																			<div class="infobox-progress">
																				<div class="easy-pie-chart percentage" data-percent="100" data-size="55">
																					<?php
																					// Get count of assignments with statusid = 5 (Cancelled)
																					$assign_cancelled_sql = "SELECT COUNT(*) as cancelled_count 
																							 FROM pm_projecttasktb 
																							 WHERE createdbyid = " . intval($user_id) . "
																							 AND statusid = 5
																							 AND istask = 1";
																					$assign_cancelled_result = mysqli_query($conn, $assign_cancelled_sql);
																					$assign_cancelled_count = 0;
																					
																					if ($assign_cancelled_result && mysqli_num_rows($assign_cancelled_result) > 0) {
																						$assign_cancelled = mysqli_fetch_assoc($assign_cancelled_result);
																						$assign_cancelled_count = $assign_cancelled['cancelled_count'];
																					}
																					?>
																					<span class="percent"><?php echo $assign_cancelled_count; ?></span>
																				</div>
																			</div>
																			<div class="infobox-data">
																				<span class="infobox-text">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Cancelled</span>
																				<div class="infobox-content">
																					<span class="bigger-110">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Assignments</span>
																				</div>
																			</div>
																		</div>
																	</a>
																</div>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>

										<div id="password" class="tab-pane">
											<?php
											// Generate random arithmetic problem
											$num1 = rand(10, 99); // Two digit number
											$num2 = rand(2, 12);  // For multiplication/division
											$num3 = rand(5, 20);  // Third number for addition/subtraction

											// Array of operators
											$operators = ['+', '-', '*', '/'];
											// Randomly select two operators
											$operator1 = $operators[rand(0, 3)];
											$operator2 = $operators[rand(0, 1)]; // Only + or - for second operator

											// Calculate answer based on operator precedence
											switch($operator1) {
												case '*':
													$intermediate = $num1 * $num2;
													break;
												case '/':
													$num1 = $num2 * rand(1, 10); // Ensure clean division
													$intermediate = $num1 / $num2;
													break;
												case '+':
													$intermediate = $num1 + $num2;
													break;
												case '-':
													$intermediate = $num1 - $num2;
													break;
											}

											$answer = ($operator2 == '+') ? $intermediate + $num3 : $intermediate - $num3;

											// Store answer in session
											$_SESSION['captcha_answer'] = $answer;

											// Format the expression
											$expression = '';
											if ($operator1 == '/') {
												$expression = "($num1 $operator1 $num2) $operator2 $num3";
											} else {
												$expression = "$num1 $operator1 $num2 $operator2 $num3";
											}
											?>
											<div class="widget-box">
												<div class="widget-header">
													<h4 class="widget-title">
														<i class="ace-icon fa fa-key"></i>
														Change Password
													</h4>
												</div>

												<div class="widget-body">
													<div class="widget-main">
														<form id="change-password-form" class="form-horizontal" method="POST" action="change_password.php" onsubmit="return false;">
															<div class="form-group">
																<label class="col-sm-3 control-label"> Current Password </label>
																<div class="col-sm-9">
																	<input type="password" id="current_password" name="current_password" class="form-control" required>
																	<span class="help-block password-feedback"></span>
																</div>
															</div>

															<div class="form-group">
																<label class="col-sm-3 control-label"> New Password </label>
																<div class="col-sm-9">
																	<input type="password" id="new_password" name="new_password" class="form-control" required>
																	<span class="help-block password-feedback"></span>
																</div>
															</div>

															<div class="form-group">
																<label class="col-sm-3 control-label"> Confirm New Password </label>
																<div class="col-sm-9">
																	<input type="password" id="confirm_new_password" name="confirm_new_password" class="form-control" required>
																	<span class="help-block password-feedback"></span>
																</div>
															</div>

															<!-- Add arithmetic captcha -->
															<div class="form-group">
																<label class="col-sm-3 control-label"> Security Check </label>
																<div class="col-sm-9">
																	<div class="input-group">
																		<span class="input-group-addon">
																			<?php echo $expression . ' = '; ?>
																		</span>
																		<input type="number" step="any" id="captcha_answer" name="captcha_answer" class="form-control" required>
																		<span class="input-group-addon">
																			<i class="ace-icon fa fa-info-circle" data-toggle="tooltip" title="Solve the arithmetic problem. Use a calculator if needed. Round to 2 decimal places for division."></i>
																		</span>
																	</div>
																	<span class="help-block security-feedback"></span>
																</div>
															</div>

															<div class="alert alert-danger" id="password-error" style="display: none;">
																<button type="button" class="close" data-dismiss="alert">
																	<i class="ace-icon fa fa-times"></i>
																</button>
																<span id="error-message"></span>
															</div>

															<div class="alert alert-success" id="password-success" style="display: none;">
																<button type="button" class="close" data-dismiss="alert">
																	<i class="ace-icon fa fa-times"></i>
																</button>
																Password successfully updated!
															</div>

															<div class="form-group">
																<div class="col-sm-offset-3 col-sm-9">
																	<button type="submit" class="btn btn-primary">
																		<i class="ace-icon fa fa-check"></i>
																		Update Password
																	</button>
																</div>
															</div>
														</form>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div><!-- /.page-content -->
				</div>
			</div><!-- /.main-content -->

			<?php include "blocks/footer.php"; ?>

			
		</div><!-- /.main-container -->

		<!-- basic scripts -->
		<script src="assets/js/jquery-2.1.4.min.js"></script>
		<script src="assets/js/bootstrap.min.js"></script>
		<script src="assets/js/jquery-ui.custom.min.js"></script>
		<script src="assets/js/jquery.ui.touch-punch.min.js"></script>
		<script src="assets/js/ace-elements.min.js"></script>
		<script src="assets/js/ace.min.js"></script>

		<!-- Add these scripts before ace.min.js -->
		<script src="assets/js/jquery.easypiechart.min.js"></script>
		<script src="assets/js/jquery.sparkline.index.min.js"></script>
		<!-- Cropper.js -->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>

		<script>
		$(document).ready(function() {
			// Remove fade class from tab panes
			$('.tab-pane').removeClass('fade');
			
			// Initialize tabs
			$('#myTab a').click(function (e) {
				e.preventDefault();
				$(this).tab('show');
			});

			// Show first tab on load
			$('#myTab li:first-child a').tab('show');

			// Clear any alerts when switching tabs
			$('a[data-toggle="tab"]').on('shown.bs.tab', function() {
				$('.alert').hide();
			});

			// Initialize tooltips
			$('[data-toggle="tooltip"]').tooltip();

			// Initialize easy pie charts with options
			$('.easy-pie-chart.percentage').each(function(){
				var $box = $(this);
				var barColor = $(this).data('color') || (!$box.hasClass('infobox-dark') ? $box.css('color') : 'rgba(255,255,255,0.95)');
				var trackColor = barColor == 'rgba(255,255,255,0.95)' ? 'rgba(255,255,255,0.25)' : '#E2E2E2';
				
				$(this).easyPieChart({
					barColor: barColor,
					trackColor: trackColor,
					scaleColor: false,
					lineCap: 'butt',
					lineWidth: 8,
					animate: true,
					size: 55
				});
			});

			// Handle password change form submission
			$('#change-password-form').on('submit', function(e) {
				e.preventDefault(); // Prevent form from submitting normally
				
				// Hide any existing alerts
				$('#password-error, #password-success').hide();
				
				// Get form values
				var currentPassword = $('#current_password').val().trim();
				var newPassword = $('#new_password').val().trim();
				var confirmPassword = $('#confirm_new_password').val().trim();
				var captchaAnswer = $('#captcha_answer').val().trim();
				
				// Validate current password
				if (!currentPassword) {
					$('#error-message').text('Please enter your current password');
					$('#password-error').show();
					$('#current_password').focus();
					return false;
				}
				
				// Validate new password
				if (!newPassword) {
					$('#error-message').text('Please enter a new password');
					$('#password-error').show();
					$('#new_password').focus();
					return false;
				}
				
				// Validate confirm password
				if (!confirmPassword) {
					$('#error-message').text('Please confirm your new password');
					$('#password-error').show();
					$('#confirm_new_password').focus();
					return false;
				}
				
				// Validate passwords match
				if (newPassword !== confirmPassword) {
					$('#error-message').text('New passwords do not match!');
					$('#password-error').show();
					$('#confirm_new_password').focus();
					return false;
				}
				
				// Validate current and new passwords are different
				if (currentPassword === newPassword) {
					$('#error-message').text('New password must be different from current password');
					$('#password-error').show();
					$('#new_password').focus();
					return false;
				}
				
				// Validate security check answer
				if (!captchaAnswer) {
					$('#error-message').text('Please solve the security check');
					$('#password-error').show();
					$('#captcha_answer').focus();
					return false;
				}
				
				// Submit form via AJAX with captcha answer
				$.ajax({
					url: 'change_password.php',
					type: 'POST',
					data: {
						current_password: currentPassword,
						new_password: newPassword,
						captcha_answer: captchaAnswer
					},
					success: function(response) {
						if (response === 'success') {
							$('#password-success').show();
							$('#change-password-form')[0].reset();
							// Reload the page to get a new arithmetic problem
							setTimeout(function() {
								window.location.href = 'userprofile.php';
							}, 2000);
						} else {
							$('#error-message').text(response);
							$('#password-error').show();
						}
					},
					error: function() {
						$('#error-message').text('An error occurred. Please try again.');
						$('#password-error').show();
					}
				});
				
				return false; // Prevent form from submitting normally
			});

			// Function to validate password fields
			function validatePasswords() {
				var currentPassword = $('#current_password').val().trim();
				var newPassword = $('#new_password').val().trim();
				var confirmPassword = $('#confirm_new_password').val().trim();
				
				// Reset all validation states
				$('.password-feedback').remove();
				$('.form-group').removeClass('has-error has-success');
				
				// Validate current password
				if (currentPassword) {
					$('#current_password').closest('.form-group').addClass('has-success');
				}
				
				// Validate new password
				if (newPassword) {
					if (currentPassword && newPassword === currentPassword) {
						$('#new_password').closest('.form-group').addClass('has-error');
						$('#new_password').after('<span class="help-block password-feedback">New password must be different from current password</span>');
					} else {
						$('#new_password').closest('.form-group').addClass('has-success');
					}
				}
				
				// Validate confirm password
				if (confirmPassword) {
					if (newPassword && confirmPassword !== newPassword) {
						$('#confirm_new_password').closest('.form-group').addClass('has-error');
						$('#confirm_new_password').after('<span class="help-block password-feedback">Passwords do not match</span>');
					} else if (newPassword && confirmPassword === newPassword) {
						$('#confirm_new_password').closest('.form-group').addClass('has-success');
					}
				}
			}
			
			// Add real-time validation for password fields
			$('#current_password, #new_password, #confirm_new_password').on('input', validatePasswords);
			
			// Add real-time validation for security check
			$('#captcha_answer').on('input', function() {
				var answer = $(this).val().trim();
				var $formGroup = $(this).closest('.form-group');
				var correctAnswer = <?php echo $_SESSION['captcha_answer']; ?>; // Get the correct answer
				
				$formGroup.removeClass('has-error has-success');
				$('.security-feedback').remove();
				
				if (answer) {
					// For division results, round to 2 decimal places
					var userAnswer = Math.round(parseFloat(answer) * 100) / 100;
					var expectedAnswer = Math.round(correctAnswer * 100) / 100;
					
					if (userAnswer === expectedAnswer) {
						$formGroup.addClass('has-success');
						$(this).after('<span class="help-block security-feedback text-success">Correct answer!</span>');
					} else {
						$formGroup.addClass('has-error');
						$(this).after('<span class="help-block security-feedback text-danger">Incorrect answer, try again</span>');
					}
				}
			});

			// Global variable for the cropper instance
			var cropper;

			// Preview image before upload with cropping
			$('#profile_picture').on('change', function() {
				var file = this.files[0];
				if (file) {
					var reader = new FileReader();
					reader.onload = function(e) {
						// Show the image preview container
						$('.image-preview-container').show();
						
						// Set the image source
						$('#image-preview').attr('src', e.target.result);
						
						// Destroy existing cropper if it exists
						if (cropper) {
							cropper.destroy();
						}
						
						// Initialize cropper with a circular crop box
						cropper = new Cropper(document.getElementById('image-preview'), {
							aspectRatio: 1,
							viewMode: 1,
							guides: true,
							autoCropArea: 0.8,
							responsive: true,
							preview: '.preview',
							ready: function() {
								// Cropper is ready
							}
						});
					}
					reader.readAsDataURL(file);
				} else {
					// Hide the image preview container if no file is selected
					$('.image-preview-container').hide();
				}
			});

			// Rotate left
			$('#rotate-left').on('click', function() {
				if (cropper) {
					cropper.rotate(-90);
				}
			});

			// Rotate right
			$('#rotate-right').on('click', function() {
				if (cropper) {
					cropper.rotate(90);
				}
			});

			// Zoom in
			$('#zoom-in').on('click', function() {
				if (cropper) {
					cropper.zoom(0.1);
				}
			});

			// Zoom out
			$('#zoom-out').on('click', function() {
				if (cropper) {
					cropper.zoom(-0.1);
				}
			});

			// Reset cropper
			$('#reset-crop').on('click', function() {
				if (cropper) {
					cropper.reset();
				}
			});

			// Handle profile picture upload with cropping
			$('#profile-picture-form').on('submit', function(e) {
				e.preventDefault();
				
				// Hide any existing alerts
				$('#upload-error, #upload-success').hide();
				
				// Get the file input
				var fileInput = $('#profile_picture')[0];
				
				// Check if a file was selected
				if (fileInput.files.length === 0) {
					$('#upload-error-message').text('Please select an image file');
					$('#upload-error').show();
					return false;
				}
				
				// Check if cropper is initialized
				if (!cropper) {
					$('#upload-error-message').text('Please wait for the image to load');
					$('#upload-error').show();
					return false;
				}
				
				// Get the cropped canvas
				var canvas = cropper.getCroppedCanvas({
					width: 300,
					height: 300,
					fillColor: '#fff',
					imageSmoothingEnabled: true,
					imageSmoothingQuality: 'high',
				});
				
				if (!canvas) {
					$('#upload-error-message').text('Failed to crop the image');
					$('#upload-error').show();
					return false;
				}
				
				// Convert canvas to blob
				canvas.toBlob(function(blob) {
					// Create FormData object
					var formData = new FormData();
					formData.append('profile_picture', blob, 'cropped-image.png');
					
					// Show loading indicator
					var btn = $('#profile-picture-form').find('button[type="submit"]');
					var originalText = btn.html();
					btn.html('<i class="ace-icon fa fa-spinner fa-spin"></i> Uploading...');
					btn.prop('disabled', true);
					
					// Submit via AJAX
					$.ajax({
						url: 'update_profile_picture.php',
						type: 'POST',
						data: formData,
						processData: false,
						contentType: false,
						success: function(response) {
							try {
								var result = JSON.parse(response);
								if (result.status === 'success') {
									// Show success message
									$('#upload-success').show();
									
									// Update the profile image
									$('#profile-image').attr('src', result.photo_url + '?' + new Date().getTime());
									
									// Reset the form and hide the preview
									$('#profile-picture-form')[0].reset();
									$('.image-preview-container').hide();
									
									// Destroy cropper
									if (cropper) {
										cropper.destroy();
										cropper = null;
									}
								} else {
									// Show error message
									$('#upload-error-message').text(result.message);
									$('#upload-error').show();
								}
							} catch (e) {
								// Show generic error
								$('#upload-error-message').text('An error occurred. Please try again.');
								$('#upload-error').show();
							}
						},
						error: function() {
							// Show error message
							$('#upload-error-message').text('An error occurred. Please try again.');
							$('#upload-error').show();
						},
						complete: function() {
							// Restore button state
							btn.html(originalText);
							btn.prop('disabled', false);
						}
					});
				}, 'image/png');
				
				return false;
			});
		});
		</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
		<script>
		function handleInfoboxClick(type, status) {
			// Get the count value from the clicked infobox
			let count = parseInt(event.currentTarget.querySelector('.percent').textContent);
			
			// If count is 0, show sweet alert and prevent navigation
			if (count === 0) {
				Swal.fire({
					icon: 'info',
					title: 'No Items',
					text: 'There are no items to display in this category.',
				});
				return false;
			}

			// Define the redirect URLs based on type and status
			const redirectUrls = {
				tickets: {
					'1': 'http://localhost/new-it-ticket/helpdesk/helpdesk.php#ticketlist',
					'3': 'http://localhost/new-it-ticket/helpdesk/helpdesk.php#ticketprogress',
					'4': 'http://localhost/new-it-ticket/helpdesk/helpdesk.php#ticketpending',
					'6': 'http://localhost/new-it-ticket/helpdesk/helpdesk.php#ticketdone',
					'2': 'http://localhost/new-it-ticket/helpdesk/helpdesk.php#ticketreject',
					'5': 'http://localhost/new-it-ticket/helpdesk/helpdesk.php#ticketcancelled',
					'all': 'http://localhost/new-it-ticket/helpdesk/helpdesk.php#ticketlist'
				},
				assignments: {
					'1': 'http://localhost/new-it-ticket/projectmanagement/projectmanagement.php#taskfrompm',
					'3': 'http://localhost/new-it-ticket/projectmanagement/projectmanagement.php#taskfrompm',
					'4': 'http://localhost/new-it-ticket/projectmanagement/projectmanagement.php#taskfrompm',
					'6': 'http://localhost/new-it-ticket/projectmanagement/projectmanagement.php#taskfrompmdone',
					'2': 'http://localhost/new-it-ticket/projectmanagement/projectmanagement.php#taskfrompmreject',
					'5': 'http://localhost/new-it-ticket/projectmanagement/projectmanagement.php#taskfrompm',
					'all': 'http://localhost/new-it-ticket/projectmanagement/projectmanagement.php#taskfrompm'
				}
			};

			// Get the redirect URL
			const redirectUrl = redirectUrls[type][status];
			
			// Redirect to the appropriate URL
			if (redirectUrl) {
				window.location.href = redirectUrl;
			}
			
			return false;
		}
		</script>
	</body>
</html>

