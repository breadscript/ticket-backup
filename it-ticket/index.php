<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta charset="utf-8" />
		<title>Dashboard</title>
		<!-- Favicon -->
		<link rel="icon" type="image/png" href="assets/images/favicon/favicon-32x32.png" />
		
		<meta name="description" content="overview &amp; stats" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

		<!-- bootstrap & fontawesome -->
		<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
		<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

		<!-- page specific plugin styles -->

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

		<!-- Add these responsive CSS rules before closing </head> -->
		<style>
		  /* Responsive styles */
		  @media (max-width: 768px) {
		    .infobox-container {
		      width: 100%;
		      margin-bottom: 20px;
		    }
		    
		    .infobox {
		      width: 100% !important;
		      margin-bottom: 10px;
		    }

		    .widget-box {
		      margin-top: 20px;
		    }

		    .table-responsive {
		      overflow-x: auto;
		    }

		    .nav-search {
		      width: 100%;
		      margin-top: 10px;
		    }

		    .breadcrumbs {
		      padding: 0 10px;
		    }
		  }

		  @media (max-width: 480px) {
		    .page-header h1 {
		      font-size: 24px;
		    }

		    .page-header h1 small {
		      display: block;
		      margin-top: 5px;
		    }

		    .infobox-data-number {
		      font-size: 20px;
		    }
		  }
		</style>
	</head>

	<body class="no-skin">
		<?php include "blocks/header.php"; ?>
		<?php $conn = connectionDB(); ?>

		<?php
		// Check if a session is not already started
		if (session_status() === PHP_SESSION_NONE) {
			session_start(); // Start the session to access session variables
		}

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
							<li class="active">Dashboard</li>
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
								Dashboard
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									overview &amp; stats
								</small>
							</h1>
						</div>
						
						<div class="row">
							<div class="col-sm-12 col-md-8 col-md-offset-2">
								<div class="row">
									<div class="space-6"></div>
									<div class="col-sm-12 col-md-6 infobox-container">
										
										<div class="infobox infobox-green" style="width: 70.5%;">
											<div class="infobox-icon">
												<i class="ace-icon fa fa-ticket"></i>
											</div>
											<div class="infobox-data">
												<div style="display: flex; justify-content: space-between; width: 100%;">
													<div>
														<?php
														// Get total tickets based on user group
														// If user_groupid is 1 (ADMIN), show all tickets, otherwise only show tickets created by the user
														$user_groupid = $user['user_groupid'] ?? 0;

														if ($user_groupid == 1) {
															// For admin users, show all tickets
															$tickets_sql = "SELECT COUNT(*) as ticket_count FROM pm_projecttasktb WHERE istask = 2";
														} else {
															// For non-admin users, show only their tickets
															$tickets_sql = "SELECT COUNT(*) as ticket_count FROM pm_projecttasktb WHERE createdbyid = " . intval($user_id) . " AND istask = 2";
														}

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
											</div>
										</div>

										<div class="infobox infobox-pink">
											<div class="infobox-progress">
												<div class="easy-pie-chart percentage" data-percent="100" data-size="55">
													<?php 
													// Get count of tickets with statusid = 1 (New)
													if ($user_groupid == 1) {
														// For admin users, show all tickets
														$new_sql = "SELECT COUNT(*) as new_count 
																	FROM pm_projecttasktb 
																	WHERE statusid = 1
																	AND istask = 2";
													} else {
														// For non-admin users, show only their tickets
														$new_sql = "SELECT COUNT(*) as new_count 
																	FROM pm_projecttasktb 
																	WHERE createdbyid = " . intval($user_id) . "
																	AND statusid = 1
																	AND istask = 2";
													}
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
												<span class="infobox-text">&nbsp;&nbsp;&nbsp;&nbsp;New</span>
												<div class="infobox-content">
													<span class="bigger-110">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tickets</span>
												</div>
											</div>
										</div>

										<div class="infobox infobox-pink">
											<div class="infobox-progress">
												<div class="easy-pie-chart percentage" data-percent="50" data-size="55">
													<?php 
													// Get count of tickets with statusid = 3 (In Progress)
													if ($user_groupid == 1) {
														// For admin users, show all tickets
														$in_progress_sql = "SELECT COUNT(*) as in_progress_count 
																		FROM pm_projecttasktb 
																		WHERE statusid = 3
																		AND istask = 2";
													} else {
														// For non-admin users, show only their tickets
														$in_progress_sql = "SELECT COUNT(*) as in_progress_count 
																		FROM pm_projecttasktb 
																		WHERE createdbyid = " . intval($user_id) . "
																		AND statusid = 3
																		AND istask = 2";
													}
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

										<div class="infobox infobox-orange2">
											<div class="infobox-progress">
												<div class="easy-pie-chart percentage" data-percent="100" data-size="55">
													<?php 
													// Get count of tickets with statusid = 4 (Pending)
													if ($user_groupid == 1) {
														// For admin users, show all tickets
														$pending_sql = "SELECT COUNT(*) as pending_count 
																		FROM pm_projecttasktb 
																		WHERE statusid = 4
																		AND istask = 2";
													} else {
														// For non-admin users, show only their tickets
														$pending_sql = "SELECT COUNT(*) as pending_count 
																		FROM pm_projecttasktb 
																		WHERE createdbyid = " . intval($user_id) . "
																		AND statusid = 4
																		AND istask = 2";
													}
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
												<span class="infobox-text">&nbsp;&nbsp;&nbsp;&nbsp;Pending</span>
												<div class="infobox-content">
													<span class="bigger-110">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tickets</span>
												</div>
											</div>
										</div>

										<div class="infobox infobox-green">
											<div class="infobox-progress">
												<div class="easy-pie-chart percentage" data-percent="100" data-size="55">
											<?php 
													// Get count of tickets with statusid = 6 (Done)
													if ($user_groupid == 1) {
														// For admin users, show all tickets
														$done_sql = "SELECT COUNT(*) as done_count 
																		FROM pm_projecttasktb 
																		WHERE statusid = 6
																		AND istask = 2";
													} else {
														// For non-admin users, show only their tickets
														$done_sql = "SELECT COUNT(*) as done_count 
																		FROM pm_projecttasktb 
																		WHERE createdbyid = " . intval($user_id) . "
																		AND statusid = 6
																		AND istask = 2";
													}
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
												<span class="infobox-text">&nbsp;&nbsp;&nbsp;&nbsp;Done</span>
												<div class="infobox-content">
													<span class="bigger-110">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tickets</span>
												</div>
											</div>
										</div>

										<div class="infobox infobox-orange2">
											<div class="infobox-progress">
												<div class="easy-pie-chart percentage" data-percent="100" data-size="55">
													<?php
													// Get count of tickets with statusid = 2 (Rejected)
													if ($user_groupid == 1) {
														// For admin users, show all tickets
														$reject_sql = "SELECT COUNT(*) as reject_count 
																		FROM pm_projecttasktb 
																		WHERE statusid = 2
																		AND istask = 2";
													} else {
														// For non-admin users, show only their tickets
														$reject_sql = "SELECT COUNT(*) as reject_count 
																		FROM pm_projecttasktb 
																		WHERE createdbyid = " . intval($user_id) . "
																		AND statusid = 2
																		AND istask = 2";
													}
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
												<span class="infobox-text">&nbsp;&nbsp;&nbsp;&nbsp;Reject</span>
												<div class="infobox-content">
													<span class="bigger-110">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tickets</span>
												</div>
											</div>
										</div>

										<div class="infobox infobox-orange2">
											<div class="infobox-progress">
												<div class="easy-pie-chart percentage" data-percent="100" data-size="55">
													<?php
													// Get count of tickets with statusid = 5 (Cancelled)
													if ($user_groupid == 1) {
														// For admin users, show all tickets
														$cancelled_sql = "SELECT COUNT(*) as cancelled_count 
																		FROM pm_projecttasktb 
																		WHERE statusid = 5
																		AND istask = 2";
													} else {
														// For non-admin users, show only their tickets
														$cancelled_sql = "SELECT COUNT(*) as cancelled_count 
																		FROM pm_projecttasktb 
																		WHERE createdbyid = " . intval($user_id) . "
																		AND statusid = 5
																		AND istask = 2";
													}
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
												<span class="infobox-text">&nbsp;&nbsp;&nbsp;&nbsp;Cancelled</span>
												<div class="infobox-content">
													<span class="bigger-110">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tickets</span>
												</div>
											</div>
										</div>

									</div>

									<div class="col-sm-12 col-md-6 infobox-container">
										
										<div class="infobox infobox-blue" style="width: 70.5%;">
											<div class="infobox-icon">
												<i class="ace-icon fa fa-tasks"></i>
											</div>
											<div class="infobox-data">
												<div style="display: flex; justify-content: space-between; width: 100%;">
													<div style="margin-left: 15px;">
														<?php
														// Get total assignments based on user group
														// If user_groupid is 1 (ADMIN), show all assignments, otherwise only show assignments created by the user
														$user_groupid = $user['user_groupid'] ?? 0;

														if ($user_groupid == 1) {
															// For admin users, show all assignments
															$assignments_sql = "SELECT COUNT(*) as assignment_count FROM pm_projecttasktb WHERE istask = 1";
														} else {
															// For non-admin users, show only their assignments
															$assignments_sql = "SELECT COUNT(*) as assignment_count FROM pm_projecttasktb WHERE createdbyid = " . intval($user_id) . " AND istask = 1";
														}

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
											</div>
										</div>

										<div class="infobox infobox-pink">
											<div class="infobox-progress">
												<div class="easy-pie-chart percentage" data-percent="100" data-size="55">
													<?php 
													// New assignments
													if ($user_groupid == 1) {
														// For admin users, show all assignments
														$assign_new_sql = "SELECT COUNT(*) as new_count 
																		FROM pm_projecttasktb 
																		WHERE statusid = 1
																		AND istask = 1";
													} else {
														// For non-admin users, show only their assignments
														$assign_new_sql = "SELECT COUNT(*) as new_count 
																		FROM pm_projecttasktb 
																		WHERE createdbyid = " . intval($user_id) . "
																		AND statusid = 1
																		AND istask = 1";
													}
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
												<span class="infobox-text">&nbsp;&nbsp;&nbsp;&nbsp;New</span>
												<div class="infobox-content">
													<span class="bigger-110">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Assignments</span>
												</div>
											</div>
										</div>

										<div class="infobox infobox-pink">
											<div class="infobox-progress">
												<div class="easy-pie-chart percentage" data-percent="50" data-size="55">
													<?php 
													// In Progress assignments
													if ($user_groupid == 1) {
														// For admin users, show all assignments
														$assign_in_progress_sql = "SELECT COUNT(*) as in_progress_count 
																				FROM pm_projecttasktb 
																				WHERE statusid = 3
																				AND istask = 1";
													} else {
														// For non-admin users, show only their assignments
														$assign_in_progress_sql = "SELECT COUNT(*) as in_progress_count 
																				FROM pm_projecttasktb 
																				WHERE createdbyid = " . intval($user_id) . "
																				AND statusid = 3
																				AND istask = 1";
													}
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

										<div class="infobox infobox-pink">
											<div class="infobox-progress">
												<div class="easy-pie-chart percentage" data-percent="100" data-size="55">
													<?php 
													// Pending assignments
													if ($user_groupid == 1) {
														// For admin users, show all assignments
														$assign_pending_sql = "SELECT COUNT(*) as pending_count 
																				FROM pm_projecttasktb 
																				WHERE statusid = 4
																				AND istask = 1";
													} else {
														// For non-admin users, show only their assignments
														$assign_pending_sql = "SELECT COUNT(*) as pending_count 
																				FROM pm_projecttasktb 
																				WHERE createdbyid = " . intval($user_id) . "
																				AND statusid = 4
																				AND istask = 1";
													}
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
												<span class="infobox-text">&nbsp;&nbsp;&nbsp;&nbsp;Pending</span>
												<div class="infobox-content">
													<span class="bigger-110">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Assignments</span>
												</div>
											</div>
										</div>

										<div class="infobox infobox-green">
											<div class="infobox-progress">
												<div class="easy-pie-chart percentage" data-percent="100" data-size="55">
											<?php 
													// Get count of assignments with statusid = 6 (Done)
													if ($user_groupid == 1) {
														// For admin users, show all tickets
														$assign_done_sql = "SELECT COUNT(*) as done_count 
																		FROM pm_projecttasktb 
																		WHERE statusid = 6
																		AND istask = 1";
													} else {
														// For non-admin users, show only their tickets
														$assign_done_sql = "SELECT COUNT(*) as done_count 
																		FROM pm_projecttasktb 
																		WHERE createdbyid = " . intval($user_id) . "
																		AND statusid = 6
																		AND istask = 1";
													}
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
												<span class="infobox-text">&nbsp;&nbsp;&nbsp;&nbsp;Done</span>
												<div class="infobox-content">
													<span class="bigger-110">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Assignments</span>
												</div>
											</div>
										</div>

										<div class="infobox infobox-orange2">
											<div class="infobox-progress">
												<div class="easy-pie-chart percentage" data-percent="100" data-size="55">
													<?php
													// Get count of assignments with statusid = 2 (Rejected)
													if ($user_groupid == 1) {
														// For admin users, show all tickets
														$assign_reject_sql = "SELECT COUNT(*) as reject_count 
																		FROM pm_projecttasktb 
																		WHERE statusid = 2
																		AND istask = 1";
													} else {
														// For non-admin users, show only their tickets
														$assign_reject_sql = "SELECT COUNT(*) as reject_count 
																		FROM pm_projecttasktb 
																		WHERE createdbyid = " . intval($user_id) . "
																		AND statusid = 2
																		AND istask = 1";
													}
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
												<span class="infobox-text">&nbsp;&nbsp;&nbsp;&nbsp;Reject</span>
												<div class="infobox-content">
													<span class="bigger-110">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Assignments</span>
												</div>
											</div>
										</div>

										<div class="infobox infobox-orange2">
											<div class="infobox-progress">
												<div class="easy-pie-chart percentage" data-percent="100" data-size="55">
													<?php
													// Get count of assignments with statusid = 5 (Cancelled)
													if ($user_groupid == 1) {
														// For admin users, show all tickets
														$assign_cancelled_sql = "SELECT COUNT(*) as cancelled_count 
																		FROM pm_projecttasktb 
																		WHERE statusid = 5
																		AND istask = 1";
													} else {
														// For non-admin users, show only their tickets
														$assign_cancelled_sql = "SELECT COUNT(*) as cancelled_count 
																		FROM pm_projecttasktb 
																		WHERE createdbyid = " . intval($user_id) . "
																		AND statusid = 5
																		AND istask = 1";
													}
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
												<span class="infobox-text">&nbsp;&nbsp;&nbsp;&nbsp;Cancelled</span>
												<div class="infobox-content">
													<span class="bigger-110">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Assignments</span>
												</div>
											</div>
										</div>

									</div>
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col-sm-12 col-md-8 col-md-offset-2">
								<div class="row">
									<div class="space-6"></div>
									<div class="col-sm-12 col-md-6">
										<div class="widget-box transparent">
											<div class="widget-header widget-header-flat">
												<h4 class="widget-title lighter">
													<i class="ace-icon fa fa-star orange"></i>
													Ticket Classification Status
												</h4>
											</div>

											<div class="widget-body">
												<div class="widget-main no-padding table-responsive">
													<table class="table table-bordered table-striped">
														<thead class="thin-border-bottom">
															<tr>
																<th>
																	<i class="ace-icon fa fa-caret-right blue"></i>Classification
																</th>

																<th>
																	<i class="ace-icon fa fa-caret-right blue"></i>Total Count
																</th>

																<th class="hidden-480">
																	<i class="ace-icon fa fa-caret-right blue"></i>Ongoing
																</th>
															
																	<th class="hidden-480">
																		<i class="ace-icon fa fa-caret-right blue"></i>Closed
																	</th>
															</tr>
														</thead>

														<tbody>
															<?php
																// Define classifications
																$classifications = [
																	1 => 'Feature',
																	2 => 'Enhancement',
																	3 => 'Bug/Error',
																	4 => 'Support/Maintenance',
																	5 => 'Others'
																];

																foreach ($classifications as $classificationId => $classificationName) {
															?>
															<tr>
																<td><?php echo $classificationName; ?></td>
																<?php 
																	// Total tickets in this classification
																	$user_groupid = $user['user_groupid'] ?? 0;

																	if ($user_groupid == 1) {
																		// For admin users, show all tickets
																		$myquery = "SELECT COUNT(id) AS countfeature FROM pm_projecttasktb WHERE classificationid = '$classificationId' AND istask = 2 AND statusid IN ('1','2','3','4','5','6')";
																	} else {
																		// For non-admin users, show only their tickets
																		$myquery = "SELECT COUNT(id) AS countfeature FROM pm_projecttasktb WHERE classificationid = '$classificationId' AND istask = 2 AND createdbyid = " . intval($user_id) . " AND statusid IN ('1','2','3','4','5','6')";
																	}
																	$myresult = mysqli_query($conn, $myquery);
																	$totalfeature = 0;
																	if ($myresult && mysqli_num_rows($myresult) > 0) {
																		$row = mysqli_fetch_assoc($myresult);
																		$totalfeature = $row['countfeature'];
																	}
																	
																	// Ongoing tickets in this classification
																	if ($user_groupid == 1) {
																		// For admin users, show all tickets
																		$myquery2 = "SELECT COUNT(id) AS countfeaturenotdone FROM pm_projecttasktb WHERE classificationid = '$classificationId' AND istask = 2 AND statusid IN ('1','2','3','4','5')";
																	} else {
																		// For non-admin users, show only their tickets
																		$myquery2 = "SELECT COUNT(id) AS countfeaturenotdone FROM pm_projecttasktb WHERE classificationid = '$classificationId' AND istask = 2 AND createdbyid = " . intval($user_id) . " AND statusid IN ('1','2','3','4','5')";
																	}
																	$myresult2 = mysqli_query($conn, $myquery2);
																	$totalnotdone = 0;
																	if ($myresult2 && mysqli_num_rows($myresult2) > 0) {
																		$row2 = mysqli_fetch_assoc($myresult2);
																		$totalnotdone = $row2['countfeaturenotdone'];
																	}
																	
																	// Closed tickets in this classification
																	if ($user_groupid == 1) {
																		// For admin users, show all tickets
																		$myquery3 = "SELECT COUNT(id) AS countfeaturedone FROM pm_projecttasktb WHERE classificationid = '$classificationId' AND istask = 2 AND statusid = '6'";
																	} else {
																		// For non-admin users, show only their tickets
																		$myquery3 = "SELECT COUNT(id) AS countfeaturedone FROM pm_projecttasktb WHERE classificationid = '$classificationId' AND istask = 2 AND createdbyid = " . intval($user_id) . " AND statusid = '6'";
																	}
																	$myresult3 = mysqli_query($conn, $myquery3);
																	$totaldone = 0;
																	if ($myresult3 && mysqli_num_rows($myresult3) > 0) {
																		$row3 = mysqli_fetch_assoc($myresult3);
																		$totaldone = $row3['countfeaturedone'];
																	}
																?>

																<td>
																	<center><b class="green"><?php echo $totalfeature; ?></b></center>
																</td>

																<td class="hidden-480">
																	<center><b class="red"><?php echo $totalnotdone; ?></b></center>
																</td>
															
																	<td class="hidden-480">
																		<span class="label label-info arrowed-right arrowed-in"><?php echo $totaldone; ?></span>
																	</td>
															</tr>
															<?php 
																}
															?>
														</tbody>
													</table>
												</div>
											</div>
										</div>
									</div>

									<div class="col-sm-12 col-md-6">
										<div class="widget-box transparent">
											<div class="widget-header widget-header-flat">
												<h4 class="widget-title lighter">
													<i class="ace-icon fa fa-star orange"></i>
													Assignment Classification Status
												</h4>
											</div>

											<div class="widget-body">
												<div class="widget-main no-padding table-responsive">
													<table class="table table-bordered table-striped">
														<thead class="thin-border-bottom">
															<tr>
																<th>
																	<i class="ace-icon fa fa-caret-right blue"></i>Classification
																</th>

																<th>
																	<i class="ace-icon fa fa-caret-right blue"></i>Total Count
																</th>

																<th class="hidden-480">
																	<i class="ace-icon fa fa-caret-right blue"></i>Ongoing
																</th>
															
																	<th class="hidden-480">
																		<i class="ace-icon fa fa-caret-right blue"></i>Closed
																	</th>
															</tr>
														</thead>

														<tbody>
															<?php
																$myquerya = "SELECT classification,id FROM sys_taskclassificationtb";
																$myresulta = mysqli_query($conn, $myquerya);
																while($rowa = mysqli_fetch_assoc($myresulta)){
																	$classificationName = $rowa['classification'];
																	$classificationNameId = $rowa['id'];
																?>
																<tr>
																	<td><?php echo $classificationName; ?></td>
																	<?php 
																		// Total assignments in this classification
																		$user_groupid = $user['user_groupid'] ?? 0;

																		if ($user_groupid == 1) {
																			// For admin users, show all assignments
																			$myquery = "SELECT COUNT(id) AS countfeature FROM pm_projecttasktb WHERE classificationid = '$classificationNameId' AND istask = 1 AND statusid IN ('1','2','3','4','5','6')";
																		} else {
																			// For non-admin users, show only their assignments
																			$myquery = "SELECT COUNT(id) AS countfeature FROM pm_projecttasktb WHERE classificationid = '$classificationNameId' AND istask = 1 AND createdbyid = " . intval($user_id) . " AND statusid IN ('1','2','3','4','5','6')";
																		}
																		$myresult = mysqli_query($conn, $myquery);
																		$totalfeature = 0;
																		if ($myresult && mysqli_num_rows($myresult) > 0) {
																			$row = mysqli_fetch_assoc($myresult);
																			$totalfeature = $row['countfeature'];
																		}
																	
																		// Ongoing assignments in this classification
																		if ($user_groupid == 1) {
																			// For admin users, show all assignments
																			$myquery2 = "SELECT COUNT(id) AS countfeaturenotdone FROM pm_projecttasktb WHERE classificationid = '$classificationNameId' AND istask = 1 AND statusid IN ('1','2','3','4','5')";
																		} else {
																			// For non-admin users, show only their assignments
																			$myquery2 = "SELECT COUNT(id) AS countfeaturenotdone FROM pm_projecttasktb WHERE classificationid = '$classificationNameId' AND istask = 1 AND createdbyid = " . intval($user_id) . " AND statusid IN ('1','2','3','4','5')";
																		}
																		$myresult2 = mysqli_query($conn, $myquery2);
																		$totalnotdone = 0;
																		if ($myresult2 && mysqli_num_rows($myresult2) > 0) {
																			$row2 = mysqli_fetch_assoc($myresult2);
																			$totalnotdone = $row2['countfeaturenotdone'];
																		}
																				
																		// Closed assignments in this classification
																		if ($user_groupid == 1) {
																			// For admin users, show all assignments
																			$myquery3 = "SELECT COUNT(id) AS countfeaturedone FROM pm_projecttasktb WHERE classificationid = '$classificationNameId' AND istask = 1 AND statusid = '6'";
																		} else {
																			// For non-admin users, show only their assignments
																			$myquery3 = "SELECT COUNT(id) AS countfeaturedone FROM pm_projecttasktb WHERE classificationid = '$classificationNameId' AND istask = 1 AND createdbyid = " . intval($user_id) . " AND statusid = '6'";
																		}
																		$myresult3 = mysqli_query($conn, $myquery3);
																		$totaldone = 0;
																		if ($myresult3 && mysqli_num_rows($myresult3) > 0) {
																			$row3 = mysqli_fetch_assoc($myresult3);
																			$totaldone = $row3['countfeaturedone'];
																		}
																	?>

																	<td>
																		<center><b class="green"><?php echo $totalfeature; ?></b></center>
																	</td>

																	<td class="hidden-480">
																		<center><b class="red"><?php echo $totalnotdone; ?></b></center>
																	</td>
																
																	<td class="hidden-480">
																		<span class="label label-info arrowed-right arrowed-in"><?php echo $totaldone; ?></span>
																	</td>
																</tr>
																<?php 
																	}
																?>
														</tbody>
													</table>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<?php include "blocks/footer.php"; ?>

		
	</div><!-- /.main-container -->

	
</body>
</html>

