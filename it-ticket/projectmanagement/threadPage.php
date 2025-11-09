<?php
// Add this at the top of your file
include('../conn/db.php');
include('../check_session.php');
$user_id = $_SESSION['userid'] ?? 0; // Use 0 as fallback if not set

// require_once '../midware_rest.php';


// Fetch recent likes for the user's comments
$likes_sql = "SELECT l.*, t.message, t.taskid, u.username, u.user_firstname, u.user_lastname, u.photo,
              CASE WHEN l.read_at IS NULL THEN 1 ELSE 0 END as is_unread
              FROM pm_thread_likes l
              JOIN pm_threadtb t ON l.thread_id = t.id
              JOIN sys_usertb u ON l.user_id = u.id
              WHERE t.createdbyid = ?
              ORDER BY l.created_at DESC
              LIMIT 5";
              
$likes_stmt = mysqli_prepare($mysqlconn, $likes_sql);
mysqli_stmt_bind_param($likes_stmt, "i", $user_id);
mysqli_stmt_execute($likes_stmt);
$likes_result = mysqli_stmt_get_result($likes_stmt);

$recent_likes = [];
$unread_count = 0;
while ($row = mysqli_fetch_assoc($likes_result)) {
    $recent_likes[] = $row;
    if ($row['is_unread']) {
        $unread_count++;
    }
}

$sql = "SELECT * FROM sys_usertb WHERE id = $user_id";
$result = mysqli_query($mysqlconn, $sql);
$user = mysqli_fetch_assoc($result);
$moduleid=8;


// Map status ID to color
$currentColor = 'green'; // Default color
switch($user['user_statusid'] ?? 1) {
    case 1:
        $currentColor = 'green';
        break;
    case 2:
        $currentColor = 'orange';
        break;
    case 3:
        $currentColor = 'grey';
        break;
    case 4:
        $currentColor = 'red';
        break;
}

// Fetch messages (comments, replies, and mentions) for the user
$messages_sql = "SELECT DISTINCT t.*, 
                 u.username, u.photo, u.user_firstname, u.user_lastname,
                 pt.subject as task_subject,
                 CASE 
                    WHEN t.parent_id IS NOT NULL THEN 'reply'
                    WHEN t.message LIKE CONCAT('%@', ?, '%') THEN 'mention'
                    ELSE 'comment'
                 END as notification_type,
                 CASE WHEN t.read_at IS NULL THEN 1 ELSE 0 END as is_unread
                 FROM pm_threadtb t
                 JOIN sys_usertb u ON t.createdbyid = u.id
                 JOIN pm_projecttasktb pt ON t.taskid = pt.id
                 WHERE (
                    -- Comments on user's tasks
                    pt.createdbyid = ? 
                    -- Replies to user's comments
                    OR t.parent_id IN (SELECT id FROM pm_threadtb WHERE createdbyid = ?)
                    -- Mentions of the user
                    OR t.message LIKE CONCAT('%@', ?, '%')
                 )
                 AND t.createdbyid != ? -- Exclude user's own comments
                 ORDER BY t.datetimecreated DESC
                 LIMIT 10";

$messages_stmt = mysqli_prepare($mysqlconn, $messages_sql);
$username = $user['username'] ?? '';
mysqli_stmt_bind_param($messages_stmt, "siiss", $username, $user_id, $user_id, $username, $user_id);
mysqli_stmt_execute($messages_stmt);
$messages_result = mysqli_stmt_get_result($messages_stmt);

$unread_messages = [];
$read_messages = [];
$unread_messages_count = 0;

while ($row = mysqli_fetch_assoc($messages_result)) {
    if ($row['is_unread']) {
        $unread_messages[] = $row;
        $unread_messages_count++;
    } else {
        $read_messages[] = $row;
    }
}

// Fetch notifications (likes) for the user
$notifications_sql = "SELECT l.*, t.message, t.taskid, u.username, u.user_firstname, u.user_lastname, u.photo,
                     CASE WHEN l.read_at IS NULL THEN 1 ELSE 0 END as is_unread
                     FROM pm_thread_likes l
                     JOIN pm_threadtb t ON l.thread_id = t.id
                     JOIN sys_usertb u ON l.user_id = u.id
                     WHERE t.createdbyid = ?
                     ORDER BY l.created_at DESC
                     LIMIT 10";
                     
$notifications_stmt = mysqli_prepare($mysqlconn, $notifications_sql);
mysqli_stmt_bind_param($notifications_stmt, "i", $user_id);
mysqli_stmt_execute($notifications_stmt);
$notifications_result = mysqli_stmt_get_result($notifications_stmt);

$unread_notifications = [];
$read_notifications = [];
$unread_notifications_count = 0;

while ($row = mysqli_fetch_assoc($notifications_result)) {
    if ($row['is_unread']) {
        $unread_notifications[] = $row;
        $unread_notifications_count++;
    } else {
        $read_notifications[] = $row;
    }
}


?>
<?php
// Define time_elapsed_string function first
function time_elapsed_string($datetime) {
    $now = time();
    $created = strtotime($datetime);
    $diff = $now - $created;

    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } else {
        return floor($diff / 86400) . ' days ago';
    }
}

// Fetch task data from database
$task_id = isset($_GET['taskId']) ? intval($_GET['taskId']) : 0;
$task_sql = "SELECT * FROM pm_projecttasktb WHERE id = $task_id";

// Execute query with error handling
$task_result = mysqli_query($mysqlconn, $task_sql);

if (!$task_result) {
    die('SQL Error: ' . mysqli_error($mysqlconn));
}

// Check if any rows were returned
if (mysqli_num_rows($task_result) > 0) {
    $task = mysqli_fetch_assoc($task_result);
?>


<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta charset="utf-8" />
		<title>User Profile Page - Ace Admin</title>

		<meta name="description" content="3 styles with inline editable feature" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

		<!-- bootstrap & fontawesome -->
		<link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
		<link rel="stylesheet" href="../assets/font-awesome/4.5.0/css/font-awesome.min.css" />

		<!-- page specific plugin styles -->
		<link rel="stylesheet" href="../assets/css/jquery-ui.custom.min.css" />
		<link rel="stylesheet" href="../assets/css/jquery.gritter.min.css" />
		<link rel="stylesheet" href="../assets/css/select2.min.css" />
		<link rel="stylesheet" href="../assets/css/bootstrap-datepicker3.min.css" />
		<link rel="stylesheet" href="../assets/css/bootstrap-editable.min.css" />

		<!-- text fonts -->
		<link rel="stylesheet" href="../assets/css/fonts.googleapis.com.css" />

		<!-- ace styles -->
		<link rel="stylesheet" href="../assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />

		<!--[if lte IE 9]>
			<link rel="stylesheet" href="assets/css/ace-part2.min.css" class="ace-main-stylesheet" />
		<![endif]-->
		<link rel="stylesheet" href="../assets/css/ace-skins.min.css" />
		<link rel="stylesheet" href="../assets/css/ace-rtl.min.css" />

		<!--[if lte IE 9]>
		  <link rel="stylesheet" href="assets/css/ace-ie.min.css" />
		<![endif]-->

		<!-- inline styles related to this page -->

		<!-- ace settings handler -->
		<script src="../assets/js/ace-extra.min.js"></script>

		<!-- HTML5shiv and Respond.js for IE8 to support HTML5 elements and media queries -->

		<!--[if lte IE 8]>
		<script src="assets/js/html5shiv.min.js"></script>
		<script src="assets/js/respond.min.js"></script>
		<![endif]-->

		<!-- Include notifications script -->
		<script src="../assets/js/notification.js"></script>
	</head>

        

	<body class="no-skin">
		<div id="navbar" class="navbar navbar-default          ace-save-state">
			<div class="navbar-container ace-save-state" id="navbar-container">
				<button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
					<span class="sr-only">Toggle sidebar</span>

					<span class="icon-bar"></span>

					<span class="icon-bar"></span>

					<span class="icon-bar"></span>
				</button>

				<div class="navbar-header pull-left">
					<a href="../index.php" class="navbar-brand">
					<small>
							<img src="../assets/images/favicon/favicon-32x32.png" alt="Logo" style="height: 28px; width: auto; margin-right: 5px; border-radius: 50%;">
							The Laguna Creamery Inc.
							<!--<img src="assets/images/logo/logo.png" class="msg-photo" alt="RedBel" />-->
						</small>
					</a>
				</div>

				<div class="navbar-buttons navbar-header pull-right" role="navigation">
					<ul class="nav ace-nav">
						<li class="grey dropdown-modal">
							<a data-toggle="dropdown" class="dropdown-toggle" href="#">
								<i class="ace-icon fa fa-tasks"></i>
								<span class="badge badge-grey">4</span>
							</a>

							<ul class="dropdown-menu-right dropdown-navbar dropdown-menu dropdown-caret dropdown-close">
								<li class="dropdown-header">
									<i class="ace-icon fa fa-check"></i>
									4 Tasks to complete
								</li>

								<li class="dropdown-content">
									<ul class="dropdown-menu dropdown-navbar">
										<li>
											<a href="#">
												<div class="clearfix">
													<span class="pull-left">Software Update</span>
													<span class="pull-right">65%</span>
												</div>

												<div class="progress progress-mini">
													<div style="width:65%" class="progress-bar"></div>
												</div>
											</a>
										</li>

										<li>
											<a href="#">
												<div class="clearfix">
													<span class="pull-left">Hardware Upgrade</span>
													<span class="pull-right">35%</span>
												</div>

												<div class="progress progress-mini">
													<div style="width:35%" class="progress-bar progress-bar-danger"></div>
												</div>
											</a>
										</li>

										<li>
											<a href="#">
												<div class="clearfix">
													<span class="pull-left">Unit Testing</span>
													<span class="pull-right">15%</span>
												</div>

												<div class="progress progress-mini">
													<div style="width:15%" class="progress-bar progress-bar-warning"></div>
												</div>
											</a>
										</li>

										<li>
											<a href="#">
												<div class="clearfix">
													<span class="pull-left">Bug Fixes</span>
													<span class="pull-right">90%</span>
												</div>

												<div class="progress progress-mini progress-striped active">
													<div style="width:90%" class="progress-bar progress-bar-success"></div>
												</div>
											</a>
										</li>
									</ul>
								</li>

								<li class="dropdown-footer">
									<a href="#">
										See tasks with details
										<i class="ace-icon fa fa-arrow-right"></i>
									</a>
								</li>
							</ul>
						</li>

						<li class="purple dropdown-modal">
							<a data-toggle="dropdown" class="dropdown-toggle" href="#">
								<i class="ace-icon fa fa-bell icon-animated-bell"></i>
								<?php if ($unread_notifications_count > 0): ?>
								<span class="badge badge-important"><?php echo $unread_notifications_count; ?></span>
								<?php endif; ?>
							</a>

							<ul class="dropdown-menu-right dropdown-navbar navbar-pink dropdown-menu dropdown-caret dropdown-close">
								<li class="dropdown-header">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									<?php echo $unread_notifications_count; ?> New Notifications
								</li>

								<li class="dropdown-content">
									<ul class="dropdown-menu dropdown-navbar navbar-pink">
										<?php if (count($unread_notifications) > 0): ?>
											<?php foreach ($unread_notifications as $notification): ?>
												<li>
													<a href="threadPage.php?taskId=<?php echo $notification['taskid']; ?>&highlight=<?php echo $notification['id']; ?>" 
													   class="clearfix notification-link"
													   data-notification-id="<?php echo $notification['id']; ?>"
													   onclick="markNotificationRead(event, this)">
														<img src="<?php echo !empty($notification['photo']) ? '../' . $notification['photo'] : '../assets/images/avatars/user.png'; ?>" 
															 class="msg-photo" 
															 alt="<?php echo htmlspecialchars($notification['username']); ?>'s Avatar" />
														<span class="msg-body">
															<span class="msg-title">
																<span class="blue"><?php echo htmlspecialchars($notification['user_firstname'] . ' ' . $notification['user_lastname']); ?></span>
																liked your comment
															</span>
															<span class="msg-time">
																<i class="ace-icon fa fa-clock-o"></i>
																<span><?php echo time_elapsed_string($notification['created_at']); ?></span>
															</span>
														</span>
													</a>
												</li>
											<?php endforeach; ?>
										<?php endif; ?>

										<?php if (count($read_notifications) > 0): ?>
											<li class="dropdown-header">
												<i class="ace-icon fa fa-history"></i>
												Earlier Notifications
											</li>
											<?php foreach ($read_notifications as $notification): ?>
												<li>
													<a href="threadPage.php?taskId=<?php echo $notification['taskid']; ?>&highlight=<?php echo $notification['id']; ?>" 
													   class="clearfix">
														<img src="<?php echo !empty($notification['photo']) ? '../' . $notification['photo'] : '../assets/images/avatars/user.png'; ?>" 
															 class="msg-photo" 
															 alt="<?php echo htmlspecialchars($notification['username']); ?>'s Avatar" />
														<span class="msg-body">
															<span class="msg-title">
																<span class="blue"><?php echo htmlspecialchars($notification['user_firstname'] . ' ' . $notification['user_lastname']); ?></span>
																liked your comment
															</span>
															<span class="msg-time">
																<i class="ace-icon fa fa-clock-o"></i>
																<span><?php echo time_elapsed_string($notification['created_at']); ?></span>
															</span>
														</span>
													</a>
												</li>
											<?php endforeach; ?>
										<?php endif; ?>

										<?php if (count($unread_notifications) === 0 && count($read_notifications) === 0): ?>
											<li>
												<a href="#" class="clearfix">
													<span class="msg-body">
														<span class="msg-title">
															No notifications
														</span>
													</span>
												</a>
											</li>
										<?php endif; ?>
									</ul>
								</li>

								<li class="dropdown-footer">
									<a href="#">
										See all notifications
										<i class="ace-icon fa fa-arrow-right"></i>
									</a>
								</li>
							</ul>
						</li>

						<li class="green dropdown-modal">
							<a data-toggle="dropdown" class="dropdown-toggle" href="#">
								<i class="ace-icon fa fa-envelope icon-animated-vertical"></i>
								<?php if ($unread_messages_count > 0): ?>
								<span class="badge badge-success"><?php echo $unread_messages_count; ?></span>
								<?php endif; ?>
							</a>

							<ul class="dropdown-menu-right dropdown-navbar dropdown-menu dropdown-caret dropdown-close">
								<li class="dropdown-header">
									<i class="ace-icon fa fa-envelope-o"></i>
									<?php echo $unread_messages_count; ?> New Messages
								</li>

								<li class="dropdown-content">
                                <ul class="dropdown-menu dropdown-navbar">
                                    <?php if ($unread_messages_count > 0): ?>
                                        <?php foreach ($unread_messages as $message): ?>
                                        <li>
                                            <a href="threadPage.php?taskId=<?php echo $message['taskid']; ?>&highlight=<?php echo $message['id']; ?>" 
                                               class="clearfix message-link"
                                               data-message-id="<?php echo $message['id']; ?>"
                                               onclick="markMessageRead(event, this)">
                                                <img src="<?php echo !empty($message['photo']) ? '../' . $message['photo'] : '../assets/images/avatars/user.png'; ?>" 
                                                     class="msg-photo" 
                                                     alt="<?php echo htmlspecialchars($message['username']); ?>'s Avatar" />
                                                <span class="msg-body">
                                                    <span class="msg-title">
                                                        <span class="blue"><?php echo htmlspecialchars($message['user_firstname'] . ' ' . $message['user_lastname']); ?>:</span>
                                                        <?php
                                                        switch ($message['notification_type']) {
                                                            case 'reply':
                                                                echo 'replied to your comment on "' . htmlspecialchars(substr($message['task_subject'], 0, 30)) . '..."';
                                                                break;
                                                            case 'mention':
                                                                echo 'mentioned you in "' . htmlspecialchars(substr($message['task_subject'], 0, 30)) . '..."';
                                                                break;
                                                            default:
                                                                echo 'commented on your task "' . htmlspecialchars(substr($message['task_subject'], 0, 30)) . '..."';
                                                        }
                                                        ?>
                                                    </span>
                                                    <span class="msg-time">
                                                        <i class="ace-icon fa fa-clock-o"></i>
                                                        <span><?php echo time_elapsed_string($message['datetimecreated']); ?></span>
                                                    </span>
                                                </span>
                                            </a>
                                        </li>
                                        <?php endforeach; ?>
                                        
                                        <?php if (count($read_messages) > 0): ?>
                                        <li class="divider"></li>
                                        <li class="dropdown-header">
                                            <i class="ace-icon fa fa-history"></i>
                                            Read Messages
                                        </li>
                                        <?php foreach ($read_messages as $message): ?>
                                        <li>
                                            <a href="threadPage.php?taskId=<?php echo $message['taskid']; ?>&highlight=<?php echo $message['id']; ?>" 
                                               class="clearfix">
                                                <img src="<?php echo !empty($message['photo']) ? '../' . $message['photo'] : '../assets/images/avatars/user.png'; ?>" 
                                                     class="msg-photo" 
                                                     alt="<?php echo htmlspecialchars($message['username']); ?>'s Avatar" />
                                                <span class="msg-body">
                                                    <span class="msg-title">
                                                        <span class="blue"><?php echo htmlspecialchars($message['user_firstname'] . ' ' . $message['user_lastname']); ?>:</span>
                                                        <?php
                                                        switch ($message['notification_type']) {
                                                            case 'reply':
                                                                echo 'replied to your comment on "' . htmlspecialchars(substr($message['task_subject'], 0, 30)) . '..."';
                                                                break;
                                                            case 'mention':
                                                                echo 'mentioned you in "' . htmlspecialchars(substr($message['task_subject'], 0, 30)) . '..."';
                                                                break;
                                                            default:
                                                                echo 'commented on your task "' . htmlspecialchars(substr($message['task_subject'], 0, 30)) . '..."';
                                                        }
                                                        ?>
                                                    </span>
                                                    <span class="msg-time">
                                                        <i class="ace-icon fa fa-clock-o"></i>
                                                        <span><?php echo time_elapsed_string($message['datetimecreated']); ?></span>
                                                    </span>
                                                </span>
                                            </a>
                                        </li>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <li>
                                            <a href="#" class="clearfix">
                                                <span class="msg-body">
                                                    <span class="msg-title">No new messages</span>
                                                </span>
                                            </a>
                                        </li>
                                        
                                        <?php if (count($read_messages) > 0): ?>
                                        <li class="divider"></li>
                                        <li class="dropdown-header">
                                            <i class="ace-icon fa fa-history"></i>
                                            Read Messages
                                        </li>
                                        <?php foreach ($read_messages as $message): ?>
                                        <li>
                                            <a href="threadPage.php?taskId=<?php echo $message['taskid']; ?>&highlight=<?php echo $message['id']; ?>" 
                                               class="clearfix">
                                                <img src="<?php echo !empty($message['photo']) ? '../' . $message['photo'] : '../assets/images/avatars/user.png'; ?>" 
                                                     class="msg-photo" 
                                                     alt="<?php echo htmlspecialchars($message['username']); ?>'s Avatar" />
                                                <span class="msg-body">
                                                    <span class="msg-title">
                                                        <span class="blue"><?php echo htmlspecialchars($message['user_firstname'] . ' ' . $message['user_lastname']); ?>:</span>
                                                        <?php
                                                        switch ($message['notification_type']) {
                                                            case 'reply':
                                                                echo 'replied to your comment on "' . htmlspecialchars(substr($message['task_subject'], 0, 30)) . '..."';
                                                                break;
                                                            case 'mention':
                                                                echo 'mentioned you in "' . htmlspecialchars(substr($message['task_subject'], 0, 30)) . '..."';
                                                                break;
                                                            default:
                                                                echo 'commented on your task "' . htmlspecialchars(substr($message['task_subject'], 0, 30)) . '..."';
                                                        }
                                                        ?>
                                                    </span>
                                                    <span class="msg-time">
                                                        <i class="ace-icon fa fa-clock-o"></i>
                                                        <span><?php echo time_elapsed_string($message['datetimecreated']); ?></span>
                                                    </span>
                                                </span>
                                            </a>
                                        </li>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </ul>
                            </li>
							<li class="dropdown-footer">
                                <a href="#">
                                    See all messages
                                    <i class="ace-icon fa fa-arrow-right"></i>
                                </a>
                            </li>
                        </ul>
                    </li>
						<li class="light-blue dropdown-modal">
							<a data-toggle="dropdown" href="#" class="dropdown-toggle">
								<img class="nav-user-photo" src="<?php 
									echo !empty($_SESSION['userphoto']) ? 
										htmlspecialchars(str_replace('assets/', '../assets/', $_SESSION['userphoto'])) : 
										'../assets/images/avatars/user.png'; 
								?>" alt="<?php echo htmlspecialchars($userfirstname); ?>'s Photo" />
								<span class="user-info">
                                    <small>Welcome,</small>
                                    <?php echo htmlspecialchars($user['user_firstname'] ?? 'User'); ?>
                                </span>

								<i class="ace-icon fa fa-caret-down"></i>
							</a>

							<ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
								<li>
									<a href="#">
										<i class="ace-icon fa fa-cog"></i>
										Settings
									</a>
								</li>

								<li>
									<a href="../userprofile.php">
										<i class="ace-icon fa fa-user"></i>
										Profile
									</a>
								</li>

								<li class="divider"></li>

								<li>
									<a href="#">
										<i class="ace-icon fa fa-power-off"></i>
										Logout
									</a>
								</li>
							</ul>
						</li>
					</ul>
				</div>
			</div><!-- /.navbar-container -->
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

							<li>
								<a href="Projectmanagement.php">Task management</a>
							</li>
							<li class="active">Thread Page</li>
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
						<div class="ace-settings-container" id="ace-settings-container">
							<div class="btn btn-app btn-xs btn-warning ace-settings-btn" id="ace-settings-btn">
								<i class="ace-icon fa fa-cog bigger-130"></i>
							</div>

							<div class="ace-settings-box clearfix" id="ace-settings-box">
								<div class="pull-left width-50">
									<div class="ace-settings-item">
										<div class="pull-left">
											<select id="skin-colorpicker" class="hide">
												<option data-skin="no-skin" value="#438EB9">#438EB9</option>
												<option data-skin="skin-1" value="#222A2D">#222A2D</option>
												<option data-skin="skin-2" value="#C6487E">#C6487E</option>
												<option data-skin="skin-3" value="#D0D0D0">#D0D0D0</option>
											</select>
										</div>
										<span>&nbsp; Choose Skin</span>
									</div>

									<div class="ace-settings-item">
										<input type="checkbox" class="ace ace-checkbox-2 ace-save-state" id="ace-settings-navbar" autocomplete="off" />
										<label class="lbl" for="ace-settings-navbar"> Fixed Navbar</label>
									</div>

									<div class="ace-settings-item">
										<input type="checkbox" class="ace ace-checkbox-2 ace-save-state" id="ace-settings-sidebar" autocomplete="off" />
										<label class="lbl" for="ace-settings-sidebar"> Fixed Sidebar</label>
									</div>

									<div class="ace-settings-item">
										<input type="checkbox" class="ace ace-checkbox-2 ace-save-state" id="ace-settings-breadcrumbs" autocomplete="off" />
										<label class="lbl" for="ace-settings-breadcrumbs"> Fixed Breadcrumbs</label>
									</div>

									<div class="ace-settings-item">
										<input type="checkbox" class="ace ace-checkbox-2" id="ace-settings-rtl" autocomplete="off" />
										<label class="lbl" for="ace-settings-rtl"> Right To Left (rtl)</label>
									</div>

									<div class="ace-settings-item">
										<input type="checkbox" class="ace ace-checkbox-2 ace-save-state" id="ace-settings-add-container" autocomplete="off" />
										<label class="lbl" for="ace-settings-add-container">
											Inside
											<b>.container</b>
										</label>
									</div>
								</div><!-- /.pull-left -->

								<div class="pull-left width-50">
									<div class="ace-settings-item">
										<input type="checkbox" class="ace ace-checkbox-2" id="ace-settings-hover" autocomplete="off" />
										<label class="lbl" for="ace-settings-hover"> Submenu on Hover</label>
									</div>

									<div class="ace-settings-item">
										<input type="checkbox" class="ace ace-checkbox-2" id="ace-settings-compact" autocomplete="off" />
										<label class="lbl" for="ace-settings-compact"> Compact Sidebar</label>
									</div>

									<div class="ace-settings-item">
										<input type="checkbox" class="ace ace-checkbox-2" id="ace-settings-highlight" autocomplete="off" />
										<label class="lbl" for="ace-settings-highlight"> Alt. Active Item</label>
									</div>
								</div><!-- /.pull-left -->
							</div><!-- /.ace-settings-box -->
						</div><!-- /.ace-settings-container -->

						<div class="page-header">
							<h1>
								Thread 	
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>

									<span class="editable" id="istask">
										<?php 
										$istaskValue = $task['istask'] ?? 0;
										echo htmlspecialchars($istaskValue == 1 ? 'Assignment' : ($istaskValue == 2 ? 'Ticket' : 'N/A')); 
										?>
									</span>

									<span class="editable" id="statusTask">
										<?php
										$statusName = 'N/A';
										if (!empty($task['statusid'])) {
											$status_sql = "SELECT statusname FROM sys_taskstatustb WHERE id = " . intval($task['statusid']);
											$status_result = mysqli_query($mysqlconn, $status_sql);
											
											if ($status_result && mysqli_num_rows($status_result) > 0) {
												$status = mysqli_fetch_assoc($status_result);
												$statusName = '(' . htmlspecialchars($status['statusname']) . ')'; // Add parentheses here
											}
										}
										echo $statusName;
										?>
									</span>

								</small>
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									<span class="editable" id="ticketID"><?php echo htmlspecialchars($task['srn_id'] ?? 'N/A'); ?></span>
								</small>						
							</h1>
						</div><!-- /.page-header -->
							
											<script src="../assets/js/jquery-2.1.4.min.js"></script>

										

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    var statusOptions = document.querySelectorAll('.status-option');
    statusOptions.forEach(function(option) {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            var status = this.getAttribute('data-status');
            var statusIcon = document.getElementById('status-icon');
            
            // Remove existing color classes
            statusIcon.classList.remove('green', 'orange', 'grey', 'red');
            
            // Add new color class based on selection
            switch(status) {
                case 'available':
                    statusIcon.classList.add('green');
                    break;
                case 'pending':
                    statusIcon.classList.add('orange');
                    break;
                case 'inactive':
                    statusIcon.classList.add('grey');
                    break;
                case 'deleted':
                    statusIcon.classList.add('red');
                    break;
            }

            // Save to database
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_status.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send('status=' + encodeURIComponent(status));
        });
    });
});
</script>



<div class="row">
    <!-- Left Column - Task Details -->
    <div class="col-md-4">
        
		
		<div class="page-header">
			<h1 style="display: flex; align-items: center; gap: 8px; ">
                <span>Subject</span>
                <i class="ace-icon fa fa-angle-double-right"></i>
                <span style="display: inline-block;"><?php echo htmlspecialchars($task['subject'] ?? 'N/A'); ?></span>
            </h1>
		</div>


        <div class="profile-user-info profile-user-info-striped">

		<div class="profile-info-row">

        <div class="profile-info-row">
                <div class="profile-info-name"> 
                <span class="editable" id="istask">
                    <?php 
                        $istaskValue = $task['istask'] ?? 0;
                        echo htmlspecialchars($istaskValue == 1 ? 'Assignment ID' : ($istaskValue == 2 ? 'Ticket ID' : 'N/A')); 
                    ?>
                </span>				
                </div>
                <div class="profile-info-value" style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="editable" id="ticketID"><?php echo htmlspecialchars($task['srn_id'] ?? 'N/A'); ?></span>
                    <div style="margin-left: auto;">
                        <!-- <button class="btn btn-xs btn-copy" onclick="copyTicketID(this)" style="background: transparent; border: none; color: #666; padding: 2px 5px;">
                            <i class="ace-icon fa fa-copy"></i> 
                        </button> -->
                        <!-- <button class="btn btn-xs btn-copy" onclick="copyTicketLink(this)" style="background: transparent; border: none; color: #666; padding: 2px 5px;">
                            <i class="ace-icon fa fa-link"></i>
                        </button> -->
                    </div>
                </div>
            </div>

        <!-- task status and priority in one row -->
        <div class="profile-info-row">
            <div class="profile-info-name">Status &amp; Priority Level</div>
            <div class="profile-info-value">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <!-- Status Section -->
                    <div>
                    <?php
                        // Get the current status from the database
                        $current_status = 'N/A';
                        if (!empty($task['statusid'])) {
                            $status_sql = "SELECT statusname FROM sys_taskstatustb WHERE id = " . intval($task['statusid']);
                            $status_result = mysqli_query($mysqlconn, $status_sql);
                            
                            if ($status_result && mysqli_num_rows($status_result) > 0) {
                                $status = mysqli_fetch_assoc($status_result);
                                $current_status = trim($status['statusname']);
                            }
                        }
                        
                        // Check if the task is past due
                        $is_past_due = false;
                        $target_date = !empty($task['deadline']) ? strtotime($task['deadline']) : null;
                        $actual_end_date = !empty($task['enddate']) ? strtotime($task['enddate']) : null;
                        
                        if ($target_date) {
                            if (!$actual_end_date && time() > $target_date) {
                                $is_past_due = true;
                            } elseif ($actual_end_date && $actual_end_date > $target_date) {
                                $is_past_due = true;
                            }
                        }
                        
                        $statuses = [
                            'NEW' => 'status-new',
                            'REJECTED' => 'status-rejected',
                            'INPROG' => 'status-inprog',
                            'PENDING' => 'status-pending',
                            'CANCELLED' => 'status-cancelled',
                            'DONE' => 'status-done',
                            'PAST DUE' => 'status-pastdue'
                        ];
                        
                        foreach ($statuses as $status => $cssClass) {
                            $isActive = false;
                            if (strcasecmp($current_status, $status) === 0) {
                                $isActive = true;
                            }
                            
                            if ($status === 'PAST DUE' && $is_past_due) {
                                $isActive = true;
                            }
                            
                            if ($isActive) {
                                $buttonClass = "status-badge $cssClass";
                                echo '<span class="' . $buttonClass . '">' . $status . '</span>';
                            }
                        }
                    ?>
                    </div>
                    
                    <!-- Separator -->
                    <div class="separator" style="width: 1px; height: 24px; background-color: #ddd;"></div>
                    
                    <!-- Priority Section -->
                    <div>
                    <?php
                        // Get the current priority from the database
                        $current_priority = 'N/A';
                        if (!empty($task['priorityid'])) {
                            $priority_sql = "SELECT priorityname FROM sys_priorityleveltb WHERE id = " . intval($task['priorityid']);
                            $priority_result = mysqli_query($mysqlconn, $priority_sql);
                            
                            if ($priority_result && mysqli_num_rows($priority_result) > 0) {
                                $priority = mysqli_fetch_assoc($priority_result);
                                $current_priority = trim($priority['priorityname']);
                            }
                        }
                        
                        // Priority levels with their corresponding colors
                        $priorities = [
                            'LOW PRIORITY' => '#28a745',      // Green
                            'MEDIUM PRIORITY' => '#ffc107',    // Yellow
                            'HIGH PRIORITY' => '#fd7e14',      // Orange
                            'CRITICAL PRIORITY' => '#dc3545'   // Red
                        ];
                        
                        foreach ($priorities as $priority => $color) {
                            if (strcasecmp($current_priority, str_replace(' PRIORITY', '', $priority)) === 0) {
                                echo '<span class="priority-badge" style="background-color: ' . $color . ';">' . $priority . '</span>';
                            }
                        }
                    ?>
                    </div>
                </div>
            </div>
        </div>
        
        <hr class="hr-8 dotted">
        
       
        
            <div class="profile-info-name"> Created By/ Company </div>
            <div class="profile-info-value">
    <?php 
    $createdByName = 'Unknown User';
    if (!empty($task['createdbyid'])) {
        $createdbyid = mysqli_real_escape_string($mysqlconn, $task['createdbyid']);
        $user_query = mysqli_query($mysqlconn, "SELECT user_firstname, user_lastname FROM sys_usertb WHERE id = '$createdbyid'");
        if ($user_query) {
            $user_data = mysqli_fetch_assoc($user_query);
            $createdByName = $user_data ? htmlspecialchars($user_data['user_firstname'].' '.$user_data['user_lastname']) : 'Unknown User';
        }
    }
    echo '<span class="editable" id="createdBy">' . $createdByName . '</span>';
    ?>
</div>

<div class="profile-info-value">
    <?php 
    $companyName = 'Unknown Company';
    if (!empty($task['createdbyid'])) {
        $createdbyid = mysqli_real_escape_string($mysqlconn, $task['createdbyid']);
        
        // Get company of the creator through user table
        $company_query = mysqli_query($mysqlconn, "
            SELECT c.clientname 
            FROM sys_usertb u
            JOIN sys_clienttb c ON u.companyid = c.id
            WHERE u.id = '$createdbyid'
        ");
        
        if ($company_query) {
            $company_data = mysqli_fetch_assoc($company_query);
            $companyName = $company_data ? htmlspecialchars($company_data['clientname']) : 'Unknown Company';
        }
    }
    echo '<span class="editable" id="companyName">' . $companyName . '</span>';
    ?>
</div>
            </div>
            <div class="profile-info-row">
                <div class="profile-info-name"> Assignee </div>
                <div class="profile-info-value">
                    <?php
                    $assigneeNames = [];
                    if (!empty($task['assignee'])) {
                        $assigneeIds = explode(',', $task['assignee']);
                        $assigneeIds = array_map('intval', $assigneeIds);
                        $assigneeIds = array_filter($assigneeIds);
                        
                        if (!empty($assigneeIds)) {
                            $assignee_sql = "SELECT user_firstname, user_lastname 
                                            FROM sys_usertb 
                                            WHERE id IN (" . implode(',', $assigneeIds) . ")";
                            $assignee_result = mysqli_query($mysqlconn, $assignee_sql);
                            
                            if ($assignee_result && mysqli_num_rows($assignee_result) > 0) {
                                while ($assignee = mysqli_fetch_assoc($assignee_result)) {
                                    $assigneeNames[] = htmlspecialchars($assignee['user_firstname'] . ' ' . $assignee['user_lastname']);
                                }
                            }
                        }
                    }
                    echo '<span class="editable" id="assignee">' . 
                         (!empty($assigneeNames) ? implode(', ', $assigneeNames) : 'Not yet Assigned.') . 
                         '</span>';
                    ?>
                </div>
            </div>
            <!-- <div class="profile-info-row">
                <div class="profile-info-name"> Ticket Date Created </div>
                <div class="profile-info-value">
                    <?php
                    echo '<span class="editable" id="ticketDateCreated">' .
                         (!empty($task['datetimecreated'])
                            ? htmlspecialchars(date('M d, Y h:i A', strtotime($task['datetimecreated'])))
                            : 'N/A') .
                         '</span>';
                    ?>
                </div>
            </div> -->
			<hr class="hr-8 dotted">
			

            <div class="profile-info-row">
                <div class="profile-info-name"> Description </div>
                <div class="profile-info-value">
                    <div class="description-container">
                        <!-- Add copy button -->
                        <!-- <button onclick="copyDescription()" class="btn btn-xs btn-light" style="float: right;">
                            <i class="ace-icon fa fa-copy"></i> 
                        </button> -->
                        <span class="editable description-text" id="description"><?php 
                            if ($task['description'] === NULL || $task['description'] === '') {
                                echo 'No description available for this task.';
                            } else {
                                $description = $task['description'];
                                $description = strip_tags($description, '<p><br><a>');
                                
                                // Handle literal \n and normalize line endings
                                $description = str_replace('\\n', "\n", $description);
                                $description = str_replace(["\r\n", "\r"], "\n", $description);
                                
                                // Convert plain newlines to <br> only if they're not between </p> and <p>
                                $description = preg_replace('/(?<!>)\n(?!<)/', "<br>", $description);
                                
                                // Convert URLs to clickable links (only if they're not already in <a> tags)
                                $description = preg_replace(
                                    '/(?<!href=")[^<>"\']*?(https?:\/\/[^\s<>"\']+)/i',
                                    '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
                                    $description
                                );
                                
                                echo $description;
                            }
                        ?></span>
                    </div>
                </div>
            </div>
			
			<hr class="hr-8 dotted">
		    <div class="profile-info-row">
                <div class="profile-info-name"> Project Owner </div>
                <div class="profile-info-value">
                    <?php 
                    $projectName = 'N/A';
                    if (!empty($task['projectid'])) {
                        $project_sql = "SELECT projectname FROM sys_projecttb WHERE id = " . intval($task['projectid']);
                        $project_result = mysqli_query($mysqlconn, $project_sql);
                        
                        if ($project_result && mysqli_num_rows($project_result) > 0) {
                            $project = mysqli_fetch_assoc($project_result);
                            $projectName = htmlspecialchars($project['projectname']);
                        }
                    }
                    echo '<span class="editable" id="project">' . $projectName . '</span>';
                    ?>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name"> Classification </div>
                <div class="profile-info-value">
                    <?php 
                    $trackerName = 'N/A';
                    if (!empty($task['classificationid'])) {
                        $tracker_sql = "SELECT classification FROM sys_taskclassificationtb WHERE id = " . intval($task['classificationid']);
                        $tracker_result = mysqli_query($mysqlconn, $tracker_sql);
                        
                        if ($tracker_result && mysqli_num_rows($tracker_result) > 0) {
                            $tracker = mysqli_fetch_assoc($tracker_result);
                            $trackerName = htmlspecialchars($tracker['classification']);  
                        }
                    }
                    echo '<span class="editable" id="tracker">' . $trackerName . '</span>';
                    ?>
                </div>
            </div>

            <hr class="hr-8 dotted">

			<!-- <div class="profile-info-row">
                <div class="profile-info-name"> Priority Level</div>
                <div class="profile-info-value">
                    <?php 
                    $priorityName = 'N/A';
                    if (!empty($task['priorityid'])) {
                        $priority_sql = "SELECT priorityname FROM sys_priorityleveltb WHERE id = " . intval($task['priorityid']);
                        $priority_result = mysqli_query($mysqlconn, $priority_sql);
                        
                        if ($priority_result && mysqli_num_rows($priority_result) > 0) {  // Changed from $status_result to $priority_result
                            $priority = mysqli_fetch_assoc($priority_result);
                            $priorityName = htmlspecialchars($priority['priorityname']);  
                        }
                    }
                    echo '<span class="editable" id="priority">' . $priorityName . '</span>';
                    ?>
                </div>
            </div> -->

            

            
            <div class="profile-info-row">
                <div class="profile-info-name"> Date Ticket Filed</div>
                <div class="profile-info-value">
                    <?php 
                    if (!empty($task['datetimecreated'])) {
                        $createdTs = strtotime($task['datetimecreated']);
                        $friendlyDate = date('M j, Y • g:i A', $createdTs);
                        $exactIso = date('Y-m-d H:i:s', $createdTs);

                        echo '<span class="editable" id="datetimecreated" title="' . htmlspecialchars($exactIso) . '">' . htmlspecialchars($friendlyDate) . '</span>';
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name"> Start Date </div>
                <div class="profile-info-value">
                    <span class="editable" id="startdate"><?php echo (!empty($task['startdate']) && $task['startdate'] !== '1900-01-01') ? htmlspecialchars(date('Y-m-d', strtotime($task['startdate']))) : 'N/A'; ?></span>
                </div>
            </div>

			<div class="profile-info-row">
                <div class="profile-info-name"> Target Date </div>
                <div class="profile-info-value">
                    <span class="editable" id="deadline"><?php echo (!empty($task['deadline']) && $task['deadline'] !== '1900-01-01') ? htmlspecialchars(date('Y-m-d', strtotime($task['deadline']))) : 'N/A'; ?></span>
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name"> Actual End Date </div>
                <div class="profile-info-value">
                    <span class="editable" id="enddate"><?php echo (!empty($task['enddate']) && $task['enddate'] !== '1900-01-01') ? htmlspecialchars(date('Y-m-d', strtotime($task['enddate']))) : 'N/A'; ?></span>
                </div>
            </div>

			

			

			<!-- <div class="profile-info-row">
                <div class="profile-info-name"> Task Status </div>
                <div class="profile-info-value">
                    <?php 
                    $statusName = 'N/A';
                    if (!empty($task['statusid'])) {
                        $status_sql = "SELECT statusname FROM sys_taskstatustb WHERE id = " . intval($task['statusid']);
                        $status_result = mysqli_query($mysqlconn, $status_sql);
                        
                        if ($status_result && mysqli_num_rows($status_result) > 0) {
                            $status = mysqli_fetch_assoc($status_result);
                            $statusName = htmlspecialchars($status['statusname']);  
                        }
                    }
                    echo '<span class="editable" id="status">' . $statusName . '</span>';
                    ?>
                </div>
            </div> -->


           

			<hr class="hr-8 dotted">
			
			<div class="profile-info-row">
			<div class="profile-info-name" style="color: green;"> Resolution </div>
                <div class="profile-info-value">
                    <?php 
                    $resolution = 'No resolution yet';
                    if (!empty($task['resolution']) && $task['resolution'] !== 'NULL' && $task['resolution'] !== '') {
                        // First decode any HTML entities, then strip all HTML tags, then escape for display
                        $resolution = htmlspecialchars(strip_tags(html_entity_decode($task['resolution'], ENT_QUOTES, 'UTF-8')));
                        // Clean up any remaining HTML entities or special characters
                        $resolution = preg_replace('/&[a-zA-Z0-9#]+;/', '', $resolution);
                        // Trim whitespace
                        $resolution = trim($resolution);
                        
                        // Check if after cleaning, we still have meaningful content
                        if (empty($resolution) || strlen($resolution) < 3) {
                            $resolution = 'No resolution yet';
                        }
                    }
                    
                    // Add styling for empty state
                    $emptyClass = ($resolution === 'No resolution yet') ? ' style="color: #999; font-style: italic;"' : '';
                    echo '<span class="editable" id="resolution"' . $emptyClass . '>' . $resolution . '</span>';
                    ?>
                </div>
            </div>
            

           

           

        </div>

        <!-- <div class="alert alert-warning" style="display: flex; align-items: center; gap: 8px; font-size: 1.1em; margin-top: 10px;">
            <i class="ace-icon fa fa-exclamation-triangle" style="color: #fd7e14;"></i>
            <span style="color: #dc3545;">Your response within 3 working days is highly appreciated. Your ticket might be closed if we did not hear from you.</span>
        </div> -->
		<!-- <div style="text-align: right; margin-top: 20px;">
            <button onclick="showUpdateTask('<?php echo $taskId;?>');" 
                class="btn btn-primary <?php echo $myClass; ?>">
                Update
            </button>
			<button           
                class="btn btn-success">
                Save
            </button>
        </div> -->

		
</div>

	

	<div class="col-md-8">
    <div class="widget-box transparent">
    		
        <div class="widget-body">
            <div class="widget-main padding-8">
                <!-- Tab navigation -->
                <ul class="nav nav-tabs padding-12" role="tablist">
                    <li class="active">
                        <a href="#threads-tab" role="tab" data-toggle="tab">
                            <i class="ace-icon fa fa-comments"></i>
                            Threads
                        </a>
                    </li>
                    <li>
                        <a href="#files-tab" role="tab" data-toggle="tab">
                            <i class="ace-icon fa fa-file"></i>
                            Media
                        </a>
                    </li>
					<li>
                        <a href="#tasks-tab" role="tab" data-toggle="tab">
                            <i class="ace-icon fa fa-tasks"></i>
                            Tasks
                        </a>
                    </li>
                </ul>

                <!-- Tab content -->
                <div class="tab-content">
                    <!-- Threads tab -->
                    <div class="tab-pane active" id="threads-tab">
                             

                        <div class="comment-form">
                            <form id="commentForm" method="POST" action="add_comments.php" onkeydown="if(event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); this.submit(); }">
                            <div class="form-group">
                                <input type="hidden" name="taskId" value="<?php echo $task_id; ?>">
                                <input type="hidden" name="subject" value="<?php echo htmlspecialchars($task['subject'] ?? ''); ?>">
                                <textarea class="form-control" name="message" rows="3" placeholder="Write your message..." ></textarea>
                                <input type="file" name="attachment[]" id="attachment" class="form-control" style="display: none;" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt" title="Maximum 10 files allowed">
                            </div>
                            <div style="display: flex; gap: 8px; align-items: center; justify-content: space-between;">
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="ace-icon fa fa-paper-plane"></i>
                                        Send
                                    </button>
                                    <button type="button" id="cancelReply" class="btn btn-sm btn-danger" style="display: none;">
                                        <i class="ace-icon fa fa-times"></i>
                                        Cancel Reply
                                    </button>
                                </div>
                                <div style="display: flex; gap: 8px; align-items: center;">
                                <div class="widget-toolbar action-buttons">
                                        <span id="fileNameDisplay" style="font-size: 12px; color: #666; margin-left: 4px;">
                                            No files selected
                                        </span>
                                    </div>
                                    <div class="widget-toolbar action-buttons">
                                        <label for="attachment" style="cursor: pointer; margin: 0;">
                                            <i class="ace-icon fa fa-upload"></i>                
                                        </label>
                                    </div>
                                </div>
                            </div>
                            </form>
                        </div>

                        <div class="space-12"></div>

                        <div id="profile-feed-1" class="profile-feed" style="max-height: 257px; overflow-y: auto;">
                            <!-- Comments will be loaded here -->
                        </div>
                    </div>

                    <!-- Files tab -->
                    <div class="tab-pane" id="files-tab">
                        <div id="attached-files">
                            <div class="text-center">
                                <i class="ace-icon fa fa-spinner fa-spin bigger-150 orange2"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Tasks tab -->
                    <div class="tab-pane" id="tasks-tab">
                        <div class="clearfix" style="margin-bottom: 10px;">		
							<div class="text-right" style="margin-bottom: 15px;">
								<button onclick="showAddSubTaskModal(<?php echo $task_id; ?>)" class="btn btn-primary btn-sm">
									<i class="ace-icon fa fa-plus"></i>
									Add Subtask
								</button>
							</div>
                        </div>
                        <div id="task-list">
                            <div class="text-center">
                                <i class="ace-icon fa fa-spinner fa-spin bigger-150 orange2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.getElementById('attachment').addEventListener('change', function() {
                const fileInput = this;
                const fileNameDisplay = document.getElementById('fileNameDisplay');
                
                if (fileInput.files.length > 10) {
                    alert('Maximum 10 files can be uploaded at once');
                    fileInput.value = ''; // Clear the selection
                    fileNameDisplay.textContent = 'No files selected';
                    fileNameDisplay.style.color = '#666';
                    return;
                }
                
                if (fileInput.files.length > 0) {
                    if (fileInput.files.length >= 4) {
                        fileNameDisplay.textContent = `${fileInput.files.length} files selected`;
                    } else {
                        const fileNames = Array.from(fileInput.files).map(file => file.name);
                        fileNameDisplay.textContent = fileNames.join(', ');
                    }
                    fileNameDisplay.style.color = '#333';
                } else {
                    fileNameDisplay.textContent = 'No files selected';
                    fileNameDisplay.style.color = '#666';
                }
            });
        </script>

        <script>
        $(document).ready(function() {
            const taskId = <?php echo json_encode($task_id); ?>;
            let isPolling = true;
            let loadedCommentsCount = 5; // Track number of loaded comments

            function loadComments() {
                const scrollPosition = $('#profile-feed-1').scrollTop();
                
                $.ajax({
                    url: 'get_comments.php',
                    data: { taskId: taskId },
                    method: 'GET',
                    success: function(comments) {
                        let html = '';
                        if (comments.length > 0) {
                            html = buildCommentHtml(comments, 0, loadedCommentsCount);
                        } else {
                            html = '<div class="alert alert-info">No messages yet for this task.</div>';
                        }
                        
                        // Compare new content with existing content
                        const currentContent = $('#profile-feed-1').html();
                        if (currentContent !== html) {
                            $('#profile-feed-1').html(html);
                            $('#profile-feed-1').scrollTop(scrollPosition);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching comments:', error);
                    }
                });
            }

            function buildCommentHtml(comments, level = 0, showCount = 5) {
                let html = '';
                comments.forEach(function(comment, index) {
                    // Skip comments with type 'file'
                    if (comment.type === 'file') {
                        return;
                    }

                    // Add lazy loading class for comments beyond current show count
                    const lazyClass = index >= showCount ? 'lazy-comment hidden' : '';

                    let fileHtml = '';
                    if (comment.files && comment.files.length > 0) {
                        fileHtml = '<div class="attached-files" style="margin-top: 8px;">';
                        
                        comment.files.forEach(filePath => {
                            const fileName = filePath.split('/').pop();
                            const fileExtension = fileName.split('.').pop().toLowerCase();
                            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
                            
                            if (imageExtensions.includes(fileExtension)) {
                                fileHtml += `
                                    <div style="display: inline-block; margin-right: 10px; margin-bottom: 10px;">
                                        <img src="${index < showCount ? filePath : '#'}" 
                                             data-src="${filePath}"
                                             alt="Attached Image" 
                                             class="${lazyClass}"
                                             style="max-width: 150px; max-height: 150px; border-radius: 4px; cursor: pointer;"
                                             onclick="previewImage(this, null, null, null, null)">
                                    </div>
                                `;
                            } else {
                                fileHtml += `
                                    <a href="${filePath}" 
                                       target="_blank" 
                                       class="btn btn-xs btn-info" 
                                       style="margin-right: 8px; margin-bottom: 8px;">
                                        <i class="ace-icon fa fa-paperclip"></i>
                                        ${fileName}
                                    </a>
                                `;
                            }
                        });
                        
                        fileHtml += '</div>';
                    }

                    // Store user data for mentions
                    const userData = {
                        username: comment.username,
                        firstname: comment.firstname || '',
                        lastname: comment.lastname || '',
                        email: comment.email || '',
                        photo: comment.photo
                    };

                    // Process message to highlight mentions and make them clickable
                    // First, replace newlines with <br> and clean up the message
                    let processedMessage = comment.message
                        .replace(/\\n/g, '<br>') // Replace literal \n with line breaks
                        .replace(/\n/g, '<br>')  // Replace actual newlines with line breaks
                        .trim(); // Remove extra whitespace

                    // Then process mentions
                    processedMessage = processedMessage.replace(/@(\w+)/g, function(match, username) {
                        return `<a href="javascript:void(0);" 
                                  class="mention-highlight" 
                                  onclick="showUserProfile('${username}')">${match}</a>`;
                    });

                    html += `
                        <div class="profile-activity clearfix" data-comment-id="${comment.id}" style="margin-left: ${level * 40}px; position: relative;">
                            <div style="position: relative;">
                                <img class="pull-left" alt="${comment.username}'s avatar" 
                                     src="${index < showCount ? comment.photo : '#'}" 
                                     data-src="${comment.photo}"
                                     data-firstname="${comment.firstname || ''}"
                                     data-lastname="${comment.lastname || ''}"
                                     data-username="${comment.username}"
                                     data-email="${comment.email || ''}"
                                     style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; cursor: pointer;"
                                     onclick="previewImage(this, '${comment.firstname || ''}', '${comment.lastname || ''}', '${comment.username}', '${comment.email || ''}')" />
                                <div style="margin-left: 50px;">
                                    <div style="display: flex; align-items: flex-start; gap: 8px; margin-bottom: 4px;">
                                        <a class="user" href="#" style="font-weight: 600; color: #2a6496; text-decoration: none;">${comment.username}</a>
                                        <div class="comment-text" style="font-size: 14px; line-height: 1.5; color: #555; word-break: break-word;">
                                            ${processedMessage}
                                        </div>
                                    </div>
                                    ${fileHtml}
                                    <div class="time" style="font-size: 10px; color: #999; font-weight: 1000;">
                                        <i class="ace-icon fa fa-clock-o"></i>
                                        ${comment.time}
                                        ${comment.parent_id ? '<span class="reply-indicator" style="margin-left: 8px; color: #666; font-size: 10px;"> Replied</span>' : ''}
                                        <a href="#" class="reply-btn" data-comment-id="${comment.id}" style="margin-left: 8px; padding: 0 4px; background: transparent; border: none; color: #999;">
                                            <i class="ace-icon fa fa-reply" style="font-size: 10px;"></i>
                                        </a>
                                        <button class="like-btn" data-comment-id="${comment.id}" style="background: none; border: none; color: ${comment.user_liked ? '#e74c3c' : '#999'}; cursor: pointer; padding: 0 4px;">
                                            <i class="ace-icon fa fa-heart"></i>
                                            <span class="like-count">${comment.likes || 0}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    if (comment.replies && comment.replies.length > 0) {
                        comment.replies.sort((a, b) => new Date(b.datetimecreated) - new Date(a.datetimecreated));
                        html += buildCommentHtml(comment.replies, level + 1);
                    }
                });

                // Add "Load More" button if there are more comments to show
                if (level === 0 && comments.length > showCount) {
                    html += `
                        <div class="text-center load-more-container">
                            <button class="btn btn-sm btn-primary load-more-btn">
                                <i class="ace-icon fa fa-spinner"></i>
                                Load More Comments
                            </button>
                        </div>
                    `;
                }

                return html;
            }

            // Function to show user profile when mention is clicked
            function showUserProfile(username) {
                // Find the user data in the comments
                $.ajax({
                    url: 'get_user_info.php',
                    method: 'GET',
                    data: { username: username },
                    success: function(userData) {
                        if (userData) {
                            previewImage(
                                { src: userData.photo },
                                userData.firstname,
                                userData.lastname,
                                userData.username,
                                userData.email
                            );
                        }
                    }
                });
            }

            // Modify the load more button handler
            $(document).on('click', '.load-more-btn', function() {
                const $button = $(this);
                const $container = $('#profile-feed-1');
                const $hiddenComments = $container.find('.lazy-comment.hidden');
                
                // Show next batch of comments
                $hiddenComments.slice(0, 5).each(function(index) {
                    const $comment = $(this);
                    setTimeout(() => {
                        $comment.removeClass('hidden').addClass('fade-in');
                        
                        // Load lazy images
                        $comment.find('img[data-src]').each(function() {
                            const $img = $(this);
                            $img.attr('src', $img.data('src'));
                        });
                    }, index * 100);
                });

                // Hide button if no more comments to load
                if ($hiddenComments.length <= 5) {
                    $button.parent().fadeOut();
                }
            });

            // Initial load
            loadComments();

            // Set up polling with a longer interval (15 seconds instead of 5)
            const pollInterval = setInterval(function() {
                if (isPolling && !document.hidden) { // Only poll when tab is visible
                    loadComments();
                }
            }, 15000); // Changed to 15 seconds

            // Add visibility change listener
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden && isPolling) {
                    loadComments(); // Refresh immediately when tab becomes visible
                }
            });

            // Handle form submission
            $('#commentForm').on('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('textarea[name="message"]').val('');
                        $('#attachment').val('');
                        $('#fileNameDisplay').text('No files selected').css('color', '#666'); // Clear file display
                        $('input[name="parent_comment_id"]').remove();
                        $('#cancelReply').hide(); // Hide cancel button after successful post
                        loadComments();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error posting comment:', error);
                        alert('Error posting comment. Please try again.');
                    }
                });
            });

            // Reply button handler
            $('#profile-feed-1').on('click', '.reply-btn', function(e) {
                e.preventDefault();
                const commentId = $(this).data('comment-id');
                const username = $(this).closest('.profile-activity').find('.user').text();
                $('textarea[name="message"]').val(`@${username} `).focus();
                $('input[name="parent_comment_id"]').remove();
                $('#commentForm').append(`<input type="hidden" name="parent_comment_id" value="${commentId}">`);
                
                // Show cancel reply button
                $('#cancelReply').show();
                
                // Clear any previously selected files
                $('#attachment').val('');
                $('#fileNameDisplay').text('No files selected').css('color', '#666');
            });

            // File selection handler
            $('#attachment').on('change', function() {
                const fileInput = this;
                const fileNameDisplay = $('#fileNameDisplay');
                
                if (fileInput.files.length > 10) {
                    alert('Maximum 10 files can be uploaded at once');
                    fileInput.value = ''; // Clear the selection
                    fileNameDisplay.text('No files selected').css('color', '#666');
                    return;
                }
                
                if (fileInput.files.length > 0) {
                    if (fileInput.files.length >= 4) {
                        fileNameDisplay.textContent = `${fileInput.files.length} files selected`;
                    } else {
                        const fileNames = Array.from(fileInput.files).map(file => file.name);
                        fileNameDisplay.textContent = fileNames.join(', ');
                    }
                    fileNameDisplay.style.color = '#333';
                } else {
                    fileNameDisplay.textContent = 'No files selected';
                    fileNameDisplay.style.color = '#666';
                }
            });

            // Refresh button handler
            $('#refreshButton').click(function(e) {
                e.preventDefault();
                loadComments();
            });

            // Clean up when leaving the page
            $(window).on('beforeunload', function() {
                isPolling = false;
                clearInterval(pollInterval);
            });

            // Function to handle hash changes
            function handleHash() {
                var hash = window.location.hash || '#threads-tab';
                $('a[href="' + hash + '"]').tab('show');
                
                // Load files if files tab is selected
                if (hash === '#files-tab') {
                    loadFiles();
                }
            }

            // Listen for hash changes
            $(window).on('hashchange', handleHash);

            // Handle initial hash
            handleHash();

            // Update hash when tab is changed
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                window.location.hash = $(e.target).attr('href');
            });

            function loadFiles() {
                $.ajax({
                    url: 'files_view.php',
                    data: { taskId: taskId },
                    method: 'GET',
                    beforeSend: function() {
                        $('#attached-files').html('<div class="text-center"><i class="ace-icon fa fa-spinner fa-spin bigger-150 orange2"></i></div>');
                    },
                    success: function(response) {
                        $('#attached-files').html(response);
                    },
                    error: function(xhr, status, error) {
                        $('#attached-files').html('<div class="alert alert-danger">Error loading files. Please try again.</div>');
                        console.error('Error loading files:', error);
                    }
                });
            }

            // Event handlers for file actions
            $(document).on('click', '.preview-btn', function(e) {
                e.preventDefault();
                const imageUrl = $(this).data('url');
                // Add your image preview logic here (modal, lightbox, etc.)
            });

            $(document).on('click', '.delete-file-btn', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to delete this file?')) {
                    const fileId = $(this).data('file-id');
                    $.ajax({
                        url: 'delete_file.php',
                        method: 'POST',
                        data: { fileId: fileId },
                        success: function(response) {
                            if (response.success) {
                                loadFiles();
                            } else {
                                alert('Error deleting file: ' + response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error deleting file:', error);
                            alert('Error deleting file. Please try again.');
                        }
                    });
                }
            });

            // Lazy loading handler
            $(document).on('click', '.load-more-btn', function() {
                const $button = $(this);
                const $container = $('#profile-feed-1');
                const $hiddenComments = $container.find('.lazy-comment.hidden');
                
                // Show next batch of comments
                $hiddenComments.slice(0, 5).each(function(index) {
                    const $comment = $(this);
                    setTimeout(() => {
                        $comment.removeClass('hidden').addClass('fade-in');
                        
                        // Load lazy images
                        $comment.find('img[data-src]').each(function() {
                            const $img = $(this);
                            $img.attr('src', $img.data('src'));
                        });
                    }, index * 100);
                });

                // Hide button if no more comments to load
                if ($hiddenComments.length <= 5) {
                    $button.parent().fadeOut();
                }
            });

            // Intersection Observer for automatic lazy loading
            if ('IntersectionObserver' in window) {
                const commentObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const $target = $(entry.target);
                            if ($target.hasClass('load-more-btn')) {
                                $target.click();
                            }
                        }
                    });
                }, {
                    root: null,
                    rootMargin: '50px',
                    threshold: 0.1
                });

                // Observe the load more button
                $('.load-more-btn').each(function() {
                    commentObserver.observe(this);
                });
            }

            // Add cancel reply button handler
            $('#cancelReply').on('click', function() {
                // Clear the textarea
                $('textarea[name="message"]').val('');
                
                // Remove the parent comment ID
                $('input[name="parent_comment_id"]').remove();
                
                // Hide the cancel button
                $(this).hide();
                
                // Clear any selected files
                $('#attachment').val('');
                $('#fileNameDisplay').text('No files selected').css('color', '#666');
            });

            // Load tasks when switching to Tasks tab
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                if ($(e.target).attr('href') === '#tasks-tab') {
                    loadTasks();
                }
            });

            function loadTasks() {
                $.ajax({
                    url: 'tasks_view.php',
                    data: { taskId: taskId },
                    method: 'GET',
                    dataType: 'html',
                    beforeSend: function() {
                        $('#task-list').html('<div class="text-center"><i class="ace-icon fa fa-spinner fa-spin bigger-150 orange2"></i></div>');
                    },
                    success: function(response) {
                        $('#task-list').html(response);
                        // Initialize any necessary plugins or events after content is loaded
                        if (typeof $.fn.DataTable !== 'undefined') {
                            if ($.fn.DataTable.isDataTable('#dataTables-subtasks')) {
                                $('#dataTables-subtasks').DataTable().destroy();
                            }
                            if ($('#dataTables-subtasks').length) {
                                $('#dataTables-subtasks').DataTable({
                                    "pageLength": 10,
                                    "order": [[0, "asc"]],
                                    "responsive": true
                                });
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#task-list').html('<div class="alert alert-danger">Error loading tasks: ' + error + '</div>');
                        console.error('Error loading tasks:', error);
                    }
                });
            }

            // Load tasks immediately when the page loads if we're on the tasks tab
            $(document).ready(function() {
                if ($('#tasks-tab').hasClass('active')) {
                    loadTasks();
                }
            });

            // Handle like button clicks
            $('#profile-feed-1').on('click', '.like-btn', function(e) {
                e.preventDefault();
                const btn = $(this);
                const commentId = btn.data('comment-id');
                const userId = <?php echo isset($_SESSION['userid']) ? $_SESSION['userid'] : 0; ?>;

                if (!userId) {
                    alert('Please log in to like comments');
                    return;
                }

                $.ajax({
                    url: 'toggle_like.php',
                    method: 'POST',
                    data: {
                        comment_id: commentId,
                        user_id: userId
                    },
                    success: function(response) {
                        const data = JSON.parse(response);
                        if (data.success) {
                            // Update the like count
                            btn.find('.like-count').text(data.likes);
                            
                            // Toggle the heart color
                            if (data.action === 'liked') {
                                btn.css('color', '#e74c3c');
                            } else {
                                btn.css('color', '#999');
                            }
                        }
                    },
                    error: function() {
                        alert('Error toggling like');
                    }
                });
            });

            // Add this to your existing JavaScript section
            $(document).ready(function() {
                // Get the highlight parameter from URL
                const urlParams = new URLSearchParams(window.location.search);
                const highlightCommentId = urlParams.get('highlight');

                if (highlightCommentId) {
                    // Function to highlight the comment
                    function highlightComment() {
                        const commentElement = $(`.profile-activity[data-comment-id="${highlightCommentId}"]`);
                        if (commentElement.length) {
                            // Scroll to the comment
                            $('html, body').animate({
                                scrollTop: commentElement.offset().top - 100
                            }, 1000);

                            // Add highlight effect
                            commentElement.css('background-color', '#fff3cd');
                            setTimeout(() => {
                                commentElement.css('transition', 'background-color 0.5s ease');
                                commentElement.css('background-color', '');
                            }, 2000);
                        }
                    }

                    // Try to highlight immediately if comments are already loaded
                    highlightComment();

                    // If comments are loaded dynamically, wait for them and try again
                    const checkExist = setInterval(function() {
                        const commentElement = $(`.profile-activity[data-comment-id="${highlightCommentId}"]`);
                        if (commentElement.length) {
                            clearInterval(checkExist);
                            highlightComment();
                        }
                    }, 100);
                }
            });
        });
        </script>
        <?php
        } else {
            echo '<div class="alert alert-warning">No task found with ID: ' . $task_id . '</div>';
        }
        ?>



                                             </div>   
											</div>

											<div class="hr hr2 hr-double"></div>
											

											<div class="space-6"></div>
											


										</div>
									</div>
								</div>

									 
							
								

								<!-- PAGE CONTENT ENDS -->
							</div><!-- /.col -->
						</div><!-- /.row -->
					</div><!-- /.page-content -->
				</div>
			</div><!-- /.main-content -->

			<div class="footer">
				<div class="footer-inner">
					<div class="footer-content">
						<span class="bigger-120">
							<span class="blue bolder">&copy; The Laguna Creamery Inc.</span>
							All rights reserved.
							Powered by IT Department 2024
						</span>
						&nbsp; &nbsp;
						<span class="action-buttons">						
							<a href="https://www.facebook.com/watch/?v=1434541407929503">
								<i class="ace-icon fa fa-facebook-square text-primary bigger-150"></i>
							</a>			
						</span>
					</div>
				</div>
			</div>
            
			<a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
				<i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i>
			</a>
		</div><!-- /.main-container -->

		<div id="myModal_updateTask" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="false"></div>
		
		<!-- basic scripts -->

		<!--[if !IE]> -->
		<script src="../assets/js/jquery-2.1.4.min.js"></script>

		<!-- <![endif]-->

		<!--[if IE]>
<script src="assets/js/jquery-1.11.3.min.js"></script>
<![endif]-->
		<script type="text/javascript">
			if('ontouchstart' in document.documentElement) document.write("<script src='../assets/js/jquery.mobile.custom.min.js'>"+"<"+"/script>");
		</script>
		<script src="../assets/js/bootstrap.min.js"></script>

		<!-- page specific plugin scripts -->

		<!--[if lte IE 8]>
		  <script src="assets/js/excanvas.min.js"></script>
		<![endif]-->
		<script src="../assets/js/jquery-ui.custom.min.js"></script>
		<script src="../assets/js/jquery.ui.touch-punch.min.js"></script>
		<script src="../assets/js/jquery.gritter.min.js"></script>
		<script src="../assets/js/bootbox.js"></script>
		<script src="../assets/js/jquery.easypiechart.min.js"></script>
		<script src="../assets/js/bootstrap-datepicker.min.js"></script>
		<script src="../assets/js/jquery.hotkeys.index.min.js"></script>
		<script src="../assets/js/bootstrap-wysiwyg.min.js"></script>
		<script src="../assets/js/select2.min.js"></script>
		<script src="../assets/js/spinbox.min.js"></script>
		<script src="../assets/js/bootstrap-editable.min.js"></script>
		<script src="../assets/js/ace-editable.min.js"></script>
		<script src="../assets/js/jquery.maskedinput.min.js"></script>

		<!-- ace scripts -->
		<script src="../assets/js/ace-elements.min.js"></script>
		<script src="../assets/js/ace.min.js"></script>

		<!-- inline scripts related to this page -->
		<script type="text/javascript">
			jQuery(function($) {
			
				//editables on first profile page
				$.fn.editable.defaults.mode = 'inline';
				$.fn.editableform.loading = "<div class='editableform-loading'><i class='ace-icon fa fa-spinner fa-spin fa-2x light-blue'></i></div>";
			    $.fn.editableform.buttons = '<button type="submit" class="btn btn-info editable-submit"><i class="ace-icon fa fa-check"></i></button>'+
			                                '<button type="button" class="btn editable-cancel"><i class="ace-icon fa fa-times"></i></button>';    
				
				//editables 
				
				//text editable
			    $('#username')
				.editable({
					type: 'text',
					name: 'username'		
			    });
			
			
				
				//select2 editable
				var countries = [];
			    $.each({ "CA": "Canada", "IN": "India", "NL": "Netherlands", "TR": "Turkey", "US": "United States"}, function(k, v) {
			        countries.push({id: k, text: v});
			    });
			
				var cities = [];
				cities["CA"] = [];
				$.each(["Toronto", "Ottawa", "Calgary", "Vancouver"] , function(k, v){
					cities["CA"].push({id: v, text: v});
				});
				cities["IN"] = [];
				$.each(["Delhi", "Mumbai", "Bangalore"] , function(k, v){
					cities["IN"].push({id: v, text: v});
				});
				cities["NL"] = [];
				$.each(["Amsterdam", "Rotterdam", "The Hague"] , function(k, v){
					cities["NL"].push({id: v, text: v});
				});
				cities["TR"] = [];
				$.each(["Ankara", "Istanbul", "Izmir"] , function(k, v){
					cities["TR"].push({id: v, text: v});
				});
				cities["US"] = [];
				$.each(["New York", "Miami", "Los Angeles", "Chicago", "Wysconsin"] , function(k, v){
					cities["US"].push({id: v, text: v});
				});
				
				var currentValue = "NL";
			    $('#country').editable({
					type: 'select2',
					value : 'NL',
					//onblur:'ignore',
			        source: countries,
					select2: {
						'width': 140
					},		
					success: function(response, newValue) {
						if(currentValue == newValue) return;
						currentValue = newValue;
						
						var new_source = (!newValue || newValue == "") ? [] : cities[newValue];
						
						//the destroy method is causing errors in x-editable v1.4.6+
						//it worked fine in v1.4.5
						/**			
						$('#city').editable('destroy').editable({
							type: 'select2',
							source: new_source
						}).editable('setValue', null);
						*/
						
						//so we remove it altogether and create a new element
						var city = $('#city').removeAttr('id').get(0);
						$(city).clone().attr('id', 'city').text('Select City').editable({
							type: 'select2',
							value : null,
							//onblur:'ignore',
							source: new_source,
							select2: {
								'width': 140
							}
						}).insertAfter(city);//insert it after previous instance
						$(city).remove();//remove previous instance
						
					}
			    });
			
				$('#city').editable({
					type: 'select2',
					value : 'Amsterdam',
					//onblur:'ignore',
			        source: cities[currentValue],
					select2: {
						'width': 140
					}
			    });
			
			
				
				//custom date editable
				$('#signup').editable({
					type: 'adate',
					date: {
						//datepicker plugin options
						    format: 'yyyy/mm/dd',
						viewformat: 'yyyy/mm/dd',
						 weekStart: 1
						 
						//,nativeUI: true//if true and browser support input[type=date], native browser control will be used
						//,format: 'yyyy-mm-dd',
						//viewformat: 'yyyy-mm-dd'
					}
				})
			
			    $('#age').editable({
			        type: 'spinner',
					name : 'age',
					spinner : {
						min : 16,
						max : 99,
						step: 1,
						on_sides: true
						//,nativeUI: true//if true and browser support input[type=number], native browser control will be used
					}
				});
				
			
			    $('#login').editable({
			        type: 'slider',
					name : 'login',
					
					slider : {
						 min : 1,
						  max: 50,
						width: 100
						//,nativeUI: true//if true and browser support input[type=range], native browser control will be used
					},
					success: function(response, newValue) {
						if(parseInt(newValue) == 1)
							$(this).html(newValue + " hour ago");
						else $(this).html(newValue + " hours ago");
					}
				});
			
				$('#about').editable({
					mode: 'inline',
			        type: 'wysiwyg',
					name : 'about',
			
					wysiwyg : {
						//css : {'max-width':'300px'}
					},
					success: function(response, newValue) {
					}
				});
				
				
				
				// *** editable avatar *** //
				try {//ie8 throws some harmless exceptions, so let's catch'em
			
					//first let's add a fake appendChild method for Image element for browsers that have a problem with this
					//because editable plugin calls appendChild, and it causes errors on IE at unpredicted points
					try {
						document.createElement('IMG').appendChild(document.createElement('B'));
					} catch(e) {
						Image.prototype.appendChild = function(el){}
					}
			
					var last_gritter
					$('#avatar').editable({
						type: 'image',
						name: 'avatar',
						value: null,
						//onblur: 'ignore',  //don't reset or hide editable onblur?!
						image: {
							//specify ace file input plugin's options here
							btn_choose: 'Change Avatar',
							droppable: true,
							maxSize: 110000,//~100Kb
			
							//and a few extra ones here
							name: 'avatar',//put the field name here as well, will be used inside the custom plugin
							on_error : function(error_type) {//on_error function will be called when the selected file has a problem
								if(last_gritter) $.gritter.remove(last_gritter);
								if(error_type == 1) {//file format error
									last_gritter = $.gritter.add({
										title: 'File is not an image!',
										text: 'Please choose a jpg|gif|png image!',
										class_name: 'gritter-error gritter-center'
									});
								} else if(error_type == 2) {//file size rror
									last_gritter = $.gritter.add({
										title: 'File too big!',
										text: 'Image size should not exceed 100Kb!',
										class_name: 'gritter-error gritter-center'
									});
								}
								else {//other error
								}
							},
							on_success : function() {
								$.gritter.removeAll();
							}
						},
					    url: function(params) {
							// ***UPDATE AVATAR HERE*** //
							//for a working upload example you can replace the contents of this function with 
							//examples/profile-avatar-update.js
			
							var deferred = new $.Deferred
			
							var value = $('#avatar').next().find('input[type=hidden]:eq(0)').val();
							if(!value || value.length == 0) {
								deferred.resolve();
								return deferred.promise();
							}
			
			
							//dummy upload
							setTimeout(function(){
								if("FileReader" in window) {
									//for browsers that have a thumbnail of selected image
									var thumb = $('#avatar').next().find('img').data('thumb');
									if(thumb) $('#avatar').get(0).src = thumb;
								}
								
								deferred.resolve({'status':'OK'});
			
								if(last_gritter) $.gritter.remove(last_gritter);
								last_gritter = $.gritter.add({
									title: 'Avatar Updated!',
									text: 'Uploading to server can be easily implemented. A working example is included with the template.',
									class_name: 'gritter-info gritter-center'
								});
								
							 } , parseInt(Math.random() * 800 + 800))
			
							return deferred.promise();
							
							// ***END OF UPDATE AVATAR HERE*** //
						},
						
						success: function(response, newValue) {
						}
					})
				}catch(e) {}
				
				/**
				//let's display edit mode by default?
				var blank_image = true;//somehow you determine if image is initially blank or not, or you just want to display file input at first
				if(blank_image) {
					$('#avatar').editable('show').on('hidden', function(e, reason) {
						if(reason == 'onblur') {
							$('#avatar').editable('show');
							return;
						}
						$('#avatar').off('hidden');
					})
				}
				*/
			
				//another option is using modals
				$('#avatar2').on('click', function(){
					var modal = 
					'<div class="modal fade">\
					  <div class="modal-dialog">\
					   <div class="modal-content">\
						<div class="modal-header">\
							<button type="button" class="close" data-dismiss="modal">&times;</button>\
							<h4 class="blue">Change Avatar</h4>\
						</div>\
						\
						<form class="no-margin">\
						 <div class="modal-body">\
							<div class="space-4"></div>\
							<div style="width:75%;margin-left:12%;"><input type="file" name="file-input" /></div>\
						 </div>\
						\
						 <div class="modal-footer center">\
							<button type="submit" class="btn btn-sm btn-success"><i class="ace-icon fa fa-check"></i> Submit</button>\
							<button type="button" class="btn btn-sm" data-dismiss="modal"><i class="ace-icon fa fa-times"></i> Cancel</button>\
						 </div>\
						</form>\
					  </div>\
					 </div>\
					</div>';
					
					
					var modal = $(modal);
					modal.modal("show").on("hidden", function(){
						modal.remove();
					});
			
					var working = false;
			
					var form = modal.find('form:eq(0)');
					var file = form.find('input[type=file]').eq(0);
					file.ace_file_input({
						style:'well',
						btn_choose:'Click to choose new avatar',
						btn_change:null,
						no_icon:'ace-icon fa fa-picture-o',
						thumbnail:'small',
						before_remove: function() {
							//don't remove/reset files while being uploaded
							return !working;
						},
						allowExt: ['jpg', 'jpeg', 'png', 'gif'],
						allowMime: ['image/jpg', 'image/jpeg', 'image/png', 'image/gif']
					});
			
					form.on('submit', function(){
						if(!file.data('ace_input_files')) return false;
						
						file.ace_file_input('disable');
						form.find('button').attr('disabled', 'disabled');
						form.find('.modal-body').append("<div class='center'><i class='ace-icon fa fa-spinner fa-spin bigger-150 orange'></i></div>");
						
						var deferred = new $.Deferred;
						working = true;
						deferred.done(function() {
							form.find('button').removeAttr('disabled');
							form.find('input[type=file]').ace_file_input('enable');
							form.find('.modal-body > :last-child').remove();
							
							modal.modal("hide");
			
							var thumb = file.next().find('img').data('thumb');
							if(thumb) $('#avatar2').get(0).src = thumb;
			
							working = false;
						});
						
						
						setTimeout(function(){
							deferred.resolve();
						} , parseInt(Math.random() * 800 + 800));
			
						return false;
					});
							
				});
			
				
				
				//////////////////////////////
				$('#profile-feed-1').ace_scroll({
					height: '250px',
					mouseWheelLock: true,
					alwaysVisible : true
				});
			
				$('a[ data-original-title]').tooltip();
			
				$('.easy-pie-chart.percentage').each(function(){
				var barColor = $(this).data('color') || '#555';
				var trackColor = '#E2E2E2';
				var size = parseInt($(this).data('size')) || 72;
				$(this).easyPieChart({
					barColor: barColor,
					trackColor: trackColor,
					scaleColor: false,
					lineCap: 'butt',
					lineWidth: parseInt(size/10),
					animate:false,
					size: size
				}).css('color', barColor);
				});
			  
				///////////////////////////////////////////
			
				//right & left position
				//show the user info on right or left depending on its position
				$('#user-profile-2 .memberdiv').on('mouseenter touchstart', function(){
					var $this = $(this);
					var $parent = $this.closest('.tab-pane');
			
					var off1 = $parent.offset();
					var w1 = $parent.width();
			
					var off2 = $this.offset();
					var w2 = $this.width();
			
					var place = 'left';
					if( parseInt(off2.left) < parseInt(off1.left) + parseInt(w1 / 2) ) place = 'right';
					
					$this.find('.popover').removeClass('right left').addClass(place);
				}).on('click', function(e) {
					e.preventDefault();
				});
			
			
				///////////////////////////////////////////
				$('#user-profile-3')
				.find('input[type=file]').ace_file_input({
					style:'well',
					btn_choose:'Change avatar',
					btn_change:null,
					no_icon:'ace-icon fa fa-picture-o',
					thumbnail:'large',
					droppable:true,
					
					allowExt: ['jpg', 'jpeg', 'png', 'gif'],
					allowMime: ['image/jpg', 'image/jpeg', 'image/png', 'image/gif']
				})
				.end().find('button[type=reset]').on(ace.click_event, function(){
					$('#user-profile-3 input[type=file]').ace_file_input('reset_input');
				})
				.end().find('.date-picker').datepicker().next().on(ace.click_event, function(){
					$(this).prev().focus();
				})
				$('.input-mask-phone').mask('(999) 999-9999');
			
				$('#user-profile-3').find('input[type=file]').ace_file_input('show_file_list', [{
    type: 'image', 
    name: '../assets/images/avatars/avatar5.png' // Use a valid path to an existing image
}]);
			
			
				////////////////////
				//change profile
				$('[data-toggle="buttons"] .btn').on('click', function(e){
					var target = $(this).find('input[type=radio]');
					var which = parseInt(target.val());
					$('.user-profile').parent().addClass('hide');
					$('#user-profile-'+which).parent().removeClass('hide');
				});
				
				
				
				/////////////////////////////////////
				$(document).one('ajaxloadstart.page', function(e) {
					//in ajax mode, remove remaining elements before leaving page
					try {
						$('.editable').editable('destroy');
					} catch(e) {}
					$('[class*=select2]').remove();
				});
			});
		</script>

		<script type="text/javascript">
		function showAddSubTaskModal() {
			// Get the current task ID from the URL or a hidden field
			var taskId = <?php echo json_encode($task_id ?? ''); ?>;
			
			// Load the modal content via AJAX
			$.get('add_subtask.php', { taskId: taskId }, function(data) {
				// Create modal if it doesn't exist
				if (!$('#modal-subtask').length) {
					$('body').append('<div class="modal fade" id="modal-subtask" tabindex="-1" role="dialog">' +
						'<div class="modal-dialog modal-lg" role="document">' +
						'<div class="modal-content"></div>' +
						'</div>' +
						'</div>');
				}
				
				// Set the modal content and show it
				$('#modal-subtask .modal-content').html(data);
				$('#modal-subtask').modal('show');
			}).fail(function(jqXHR, textStatus, errorThrown) {
				console.error('Error loading subtask modal:', errorThrown);
				alert('Error loading the subtask form. Please try again.');
			});
		}
		</script>
	</body>
</html>

<!-- Add this right before the closing </body> tag -->
<script src="../assets/js/jquery-ui.min.js"></script>
<link rel="stylesheet" href="../assets/css/jquery-ui.min.css" />

<style>
/* Custom styles for the mention autocomplete */
.ui-autocomplete {
    max-height: 200px;
    overflow-y: auto;
    overflow-x: hidden;
    z-index: 9999 !important;
    border: 1px solid #ccc;
    border-radius: 3px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.ui-menu-item {
    padding: 5px 10px;
    cursor: pointer;
}

.ui-menu-item:hover {
    background-color: #f5f5f5;
}

.ui-state-active, 
.ui-widget-content .ui-state-active {
    border: none;
    background: #6fb3e0;
    color: #fff;
    margin: 0;
}

.mention-item {
    display: flex;
    align-items: center;
    padding: 5px 0;
}

.mention-avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    margin-right: 10px;
}

.mention-name {
    font-weight: bold;
}

.mention-username {
    color: #777;
    margin-left: 5px;
    font-size: 0.9em;
}
.modern-modal .modal-content {
    border: none;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    background: #fff;
}

.modern-modal .modal-header {
    border-bottom: none;
    padding: 20px 25px;
    position: relative;
}

.modern-modal .modal-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #2c3e50;
}

.modern-modal .close {
    position: absolute;
    right: 20px;
    top: 20px;
    width: 32px;
    height: 32px;
    background: #f8f9fa;
    border-radius: 50%;
    opacity: 1;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
}

.modern-modal .close:hover {
    background: #e9ecef;
    transform: rotate(90deg);
}

.modern-modal .modal-body {
    padding: 0;
}

.modern-modal .image-container {
    position: relative;
    background: #f8f9fa;
    border-radius: 8px;
    overflow: hidden;
    margin: 15px;
}

.modern-modal #zoomedImage {
    transition: transform 0.3s ease;
    object-fit: contain;
}

.modern-modal .user-info-panel {
    padding: 25px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    margin: 15px;
}

.modern-modal .user-info-panel h4 {
    font-size: 1.8rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 20px;
    line-height: 1.3;
}

.modern-modal .user-info-item {
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.modern-modal .user-info-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.modern-modal .user-info-label {
    font-size: 0.9rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 5px;
}

.modern-modal .user-info-value {
    font-size: 1.1rem;
    color: #2c3e50;
    font-weight: 500;
}

/* Animation for modal */
.modal.fade .modal-dialog {
    transform: scale(0.8);
    transition: transform 0.3s ease-out;
}

.modal.show .modal-dialog {
    transform: scale(1);
}

.modern-modal .user-info-panel h1 {
    font-size: 2.2rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 20px;
    line-height: 1.3;
}

.modern-modal .user-info-item {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.modern-modal .user-info-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.modern-modal .user-info-label {
    font-size: 1rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
    font-weight: 500;
}

.modern-modal .user-info-value {
    font-size: 1.8rem;  /* H2-like size */
    color: #2c3e50;
    font-weight: 600;
    line-height: 1.3;
    margin: 0;
}

/* Animation for modal */
.modal.fade .modal-dialog {
    transform: scale(0.8);
    transition: transform 0.3s ease-out;
}

.modal.show .modal-dialog {
    transform: scale(1);
}

/* Add these styles */
.mention-highlight {
    background-color: #e8f4f8;
    color: #0366d6;
    padding: 2px 4px;
    border-radius: 4px;
    font-weight: 500;
    display: inline-block;
    margin: 0 2px;
}

.mention-highlight:hover {
    background-color: #cce5ff;
    text-decoration: none;
    color: #0056b3;
}

.profile-info-row {
    display: flex;
    flex-wrap: wrap;
    width: 100%;
}

.profile-info-name {
    flex: 0 0 150px; /* Fixed width for labels */
    padding: 8px;
}

.profile-info-value {
    flex: 1;
    min-width: 200px; /* Minimum width before wrapping */
    padding: 8px;
}

.description-text {
    display: block;
    word-wrap: break-word;
    overflow-wrap: break-word;
    max-width: 100%;
}

/* Handle links within description */
.description-text a {
    word-break: break-all;
    max-width: 100%;
    display: inline-block;
}



/* Status-specific styles */
.status-new.active {
    background-color: #d1ecf1 !important;
    color: #0c5460 !important;
}

.status-rejected.active {
    background-color: #f8d7da !important;
    color: #721c24 !important;
}

.status-inprog.active {
    background-color: #fff3cd !important;
    color: #856404 !important;
}

.status-pending.active {
    background-color: #e2e3e5 !important;
    color: #383d41 !important;
}

.status-cancelled.active {
    background-color: #f5c6cb !important;
    color: #721c24 !important;
}

.status-done.active {
    background-color: #d4edda !important;
    color: #155724 !important;
}

.status-pastdue.active {
    background-color: #dc3545 !important;
    color: #fff !important;
}

.status-badge {
    font-size: 14px;
}

.priority-badge {
    font-size: 14px;
}

</style>



<!-- /* Modern Modal Styles */ -->


<div id="imageZoomModal" class="modal fade modern-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Profile Details</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-5">
                        <div class="image-container">
                            <img id="zoomedImage" src="" alt="Zoomed Image" style="max-width: 100%; max-height: 60vh;">
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="user-info-panel">
                            <h1 id="userFullName"></h1>
                            <div class="user-info-item">
                                <div class="user-info-label">Username</div>
                                <div class="user-info-value" id="userName"></div>
                            </div>
                            <div class="user-info-item">
                                <div class="user-info-label">Email Address</div>
                                <div class="user-info-value" id="userEmail"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
	$(document).ready(function() {
		// Initialize mention autocomplete
		initMentionAutocomplete();
		
		function initMentionAutocomplete() {
			var textarea = $('textarea[name="message"]');
			
			textarea.on('keyup', function(e) {
				var text = $(this).val();
				var lastAtPos = text.lastIndexOf('@');
				
				// Only trigger if we have an @ symbol
				if (lastAtPos >= 0) {
					var query = text.substring(lastAtPos + 1);
					
					// Check if we're in the middle of a word
					var endOfQuery = query.search(/\s/);
					if (endOfQuery >= 0) {
						query = query.substring(0, endOfQuery);
					}
					
					// Only search if we have at least 1 character after @
					if (query.length >= 0) { // Changed from > 0 to >= 0 to show all users when @ is typed
						fetchUserSuggestions(query);
					}
				}
			});
		}
		
		function fetchUserSuggestions(query) {
			$.ajax({
				url: 'get_users.php',
				method: 'GET',
				data: { query: query },
				dataType: 'json',
				success: function(data) {
					if (data && Array.isArray(data) && data.length > 0) {
						showUserSuggestions(data);
					} else if (data && data.error) {
						console.error('Server returned error:', data.error);
					} else {
						console.log("No users found matching the query");
					}
				},
				error: function(xhr, status, error) {
					console.error('Error fetching user suggestions:', error);
					// Try to parse response text if possible
					try {
						var response = JSON.parse(xhr.responseText);
						if (response && response.error) {
							console.error('Server error details:', response.error);
						}
					} catch (e) {
						console.error('Invalid JSON response:', xhr.responseText.substring(0, 100) + '...');
					}
				}
			});
		}
		
		function showUserSuggestions(users) {
			var textarea = $('textarea[name="message"]');
			
			// Destroy previous autocomplete if exists
			if (textarea.autocomplete("instance")) {
				textarea.autocomplete("destroy");
			}
			
			textarea.autocomplete({
				source: users,
				minLength: 0, // Show all options even with empty search
				position: { my: "left bottom", at: "left top" },
				focus: function(event, ui) {
					return false; // Prevent value inserted on focus
				},
				select: function(event, ui) {
					var text = textarea.val();
					var lastAtPos = text.lastIndexOf('@');
					
					// Replace the @query with the selected username - preserve original case
					var newText = text.substring(0, lastAtPos) + '@' + ui.item.username + ' ' + text.substring(lastAtPos + ui.item.value.length + 1);
					textarea.val(newText);
					
					// Close the autocomplete dropdown
					textarea.autocomplete("close");
					
					return false;
				}
			}).data("ui-autocomplete")._renderItem = function(ul, item) {
				// Custom rendering of the suggestion items
				return $("<li>")
					.append("<div class='mention-item'>" +
						"<img src='" + (item.avatar || "../assets/images/avatars/user.png") + "' class='mention-avatar' />" +
						"<div>" +
						"<span class='mention-name'>" + item.name + "</span>" +
						"<span class='mention-username'>@" + item.username + "</span>" +
						"</div>" +
						"</div>")
					.appendTo(ul);
			};
			
			// Trigger the autocomplete
			textarea.autocomplete("search", "");
		}
	});
</script>

<?php
// Modern PHP modal template with Bootstrap compatibility
?>

<script>
	function previewImage(imgElement, firstname, lastname, username, email) {
    const modal = $('#imageZoomModal');
    const modalImg = $('#zoomedImage');
    
    // Set the source of the modal image to the clicked image's source
    modalImg.attr('src', imgElement.src);
    
    // Update user information with fade effect
    if (firstname && lastname) {
        $('#userFullName').fadeOut(200, function() {
            $(this).text(firstname + ' ' + lastname).fadeIn(200);
        });
    } else {
        $('#userFullName').fadeOut(200, function() {
            $(this).text('').fadeIn(200);
        });
    }
    
    if (username) {
        $('#userName').fadeOut(200, function() {
            $(this).text(username).fadeIn(200);
        });
    } else {
        $('#userName').fadeOut(200, function() {
            $(this).text('N/A').fadeIn(200);
        });
    }
    
    if (email) {
        $('#userEmail').fadeOut(200, function() {
            $(this).text(email).fadeIn(200);
        });
    } else {
        $('#userEmail').fadeOut(200, function() {
            $(this).text('N/A').fadeIn(200);
        });
    }
    
    // Show the modal with animation
    modal.modal('show');
}

// Add zoom effect on hover for the modal image
	$(document).ready(function() {
		$('#zoomedImage').hover(
			function() { $(this).css('transform', 'scale(1.05)'); },
			function() { $(this).css('transform', 'scale(1)'); }
		);
	});
</script>
<!-- Image Preview Modal -->
 <div class="modal fade" id="imageZoomModal" tabindex="-1" aria-labelledby="imageZoomModalLabel" aria-hidden="true">
   <div class="modal-dialog modal-lg">
     <div class="modal-content">
       <div class="modal-header">
         <!-- Support both Bootstrap 4 and 5 close buttons -->
         <h5 class="modal-title" id="imageZoomModalLabel">User Information</h5>
         <button type="button" class="close" data-dismiss="modal" aria-label="Close">
           <span aria-hidden="true">&times;</span>
         </button>
       </div>
       <div class="modal-body">
         <div class="row">
           <div class="col-md-8 text-center">
             <img id="zoomedImage" src="" alt="Zoomed Image" class="img-fluid">
           </div>
           <div class="col-md-4">
             <div class="user-info-panel p-3 bg-light rounded">
               <h4 id="userFullName" class="text-primary mb-3"></h4>
               <p><strong>Username:</strong> <span id="userName"></span></p>
               <p class="mb-0"><strong>Email:</strong> <span id="userEmail"></span></p>
             </div>
           </div>
         </div>
       </div>
     </div>
   </div>
 </div>

 <!-- JavaScript for handling comments and image preview -->
 <script>
// document.addEventListener('DOMContentLoaded', () => {
//   /**
//    * Builds HTML for comments with modern ES6+ syntax
//    * @param {Array} comments - Array of comment objects
//    * @param {Number} level - Indentation level for nested comments
//    * @param {Number} showCount - Number of comments to show initially
//    * @return {String} HTML string for comments
//    */
//   const buildCommentHtml = (comments, level = 0, showCount = 5) => {
//     const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    
//     return comments
//       .filter(comment => comment.type !== 'file')
//       .map((comment, index) => {
//         const lazyClass = index >= showCount ? 'lazy-comment hidden' : '';
        
//         // Build HTML for attached files
//         let fileHtml = '';
//         if (comment.files?.length) {
//           fileHtml = `<div class="attached-files mt-2">`;
          
//           comment.files.forEach(filePath => {
//             const fileName = filePath.split('/').pop();
//             const fileExtension = fileName.split('.').pop().toLowerCase();
            
//             if (imageExtensions.includes(fileExtension)) {
//               fileHtml += `
//                 <div class="d-inline-block me-2 mb-2">
//                   <img 
//                     src="${index < showCount ? filePath : '#'}" 
//                     data-src="${filePath}"
//                     alt="Attached Image" 
//                     class="img-thumbnail ${lazyClass}"
//                     style="max-width: 150px; max-height: 150px; cursor: pointer;"
//                     onclick="previewImage(this)">
//                 </div>
//               `;
//             } else {
//               fileHtml += `
//                 <a href="${filePath}" target="_blank" class="btn btn-sm btn-info ms-2">
//                   <i class="bi bi-paperclip"></i>
//                   ${fileName}
//                 </a>`;
//             }
//           });
//           fileHtml += '</div>';
//         }

//         // Build comment HTML with modern Bootstrap classes
//         let html = `
//           <div class="profile-activity clearfix position-relative" style="margin-left: ${level * 40}px;">
//             <div class="position-relative">
//               <img 
//                 class="float-start rounded-circle"
//                 alt="${comment.username}'s avatar" 
//                 src="${index < showCount ? comment.photo : '#'}" 
//                 data-src="${comment.photo}"
//                 data-firstname="${comment.firstname || ''}"
//                 data-lastname="${comment.lastname || ''}"
//                 data-username="${comment.username}"
//                 data-email="${comment.email || ''}"
//                 style="width: 36px; height: 36px; object-fit: cover; cursor: pointer;"
//                 onclick="previewImage(this, '${comment.firstname || ''}', '${comment.lastname || ''}', '${comment.username}', '${comment.email || ''}')" />
              
//               <div class="ms-5">
//                 <div class="d-flex align-items-baseline gap-2 mb-1">
//                   <a class="user fw-bold text-decoration-none" href="#">${comment.username}</a>
//                   <div class="comment-text fs-6 text-secondary">
//                     ${comment.message}
//                   </div>
//                 </div>
//                 ${fileHtml}
//                 <div class="time small text-muted mt-1">
//                   <i class="bi bi-clock"></i>
//                   ${comment.time}
//                   ${comment.parent_id ? '<span class="reply-indicator ms-2 text-secondary small">Replied</span>' : ''}
//                   <button type="button" class="reply-btn btn btn-link btn-sm p-0 ms-2 text-muted" 
//                           data-comment-id="${comment.id}">
//                     <i class="bi bi-reply small"></i>
//                   </button>
//                 </div>
//               </div>
//             </div>
//           </div>
//         `;
        
//         // Add replies recursively
//         if (comment.replies?.length) {
//           const sortedReplies = [...comment.replies].sort((a, b) => 
//             new Date(b.datetimecreated) - new Date(a.datetimecreated)
//           );
//           html += buildCommentHtml(sortedReplies, level + 1);
//         }
        
//         return html;
//       }).join('');
//   };

//   /**
//    * Detect which version of Bootstrap is available (if any)
//    * @returns {string} 'bootstrap5', 'bootstrap4', or 'none'
//    */
//   const detectBootstrapVersion = () => {
//     if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal === 'function') {
//       return 'bootstrap5';
//     } else if (typeof $ !== 'undefined' && typeof $.fn.modal === 'function') {
//       return 'bootstrap4';
//     }
//     return 'none';
//   };

//   /**
//    * Shows the modal using the appropriate method based on available libraries
//    * @param {HTMLElement} modalElement - The modal DOM element
//    */
//   const showModal = (modalElement) => {
//     const bootstrapVersion = detectBootstrapVersion();
    
//     if (bootstrapVersion === 'bootstrap5') {
//       // Bootstrap 5
//       const bsModal = new bootstrap.Modal(modalElement);
//       bsModal.show();
//     } else if (bootstrapVersion === 'bootstrap4') {
//       // Bootstrap 4
//       $(modalElement).modal('show');
//     } else {
//       // Fallback for no Bootstrap - simple display toggle
//       modalElement.style.display = 'block';
//       modalElement.classList.add('show');
//       document.body.classList.add('modal-open');
      
//       // Create backdrop if it doesn't exist
//       let backdrop = document.querySelector('.modal-backdrop');
//       if (!backdrop) {
//         backdrop = document.createElement('div');
//         backdrop.classList.add('modal-backdrop', 'fade', 'show');
//         document.body.appendChild(backdrop);
//       }
      
//       // Add close handlers
//       const closeButtons = modalElement.querySelectorAll('[data-dismiss="modal"]');
//       closeButtons.forEach(button => {
//         button.addEventListener('click', () => {
//           modalElement.style.display = 'none';
//           modalElement.classList.remove('show');
//           document.body.classList.remove('modal-open');
          
//           if (backdrop) {
//             backdrop.remove();
//           }
//         });
//       });
//     }
//   };

//   /**
//    * Previews an image in the modal
//    * @param {HTMLElement} imgElement - The image element that was clicked
//    * @param {String} firstname - User's first name
//    * @param {String} lastname - User's last name
//    * @param {String} username - Username
//    * @param {String} email - User's email
//    */
//   window.previewImage = (imgElement, firstname = null, lastname = null, username = null, email = null) => {
//     const modal = document.getElementById('imageZoomModal');
//     const modalImg = document.getElementById('zoomedImage');
//     const userFullName = document.getElementById('userFullName');
//     const userNameElement = document.getElementById('userName');
//     const userEmailElement = document.getElementById('userEmail');
//     const userInfoPanel = document.querySelector('.user-info-panel');
    
//     // Set modal image
//     modalImg.src = imgElement.src;
//     modalImg.alt = `${firstname || ''} ${lastname || ''}'s avatar`;
    
//     // Update user information
//     userFullName.textContent = firstname && lastname ? `${firstname} ${lastname}` : '';
//     userNameElement.textContent = username || 'N/A';
//     userEmailElement.textContent = email || 'N/A';
    
//     // Show modal using the appropriate method
//     showModal(modal);
//   };

//   // Setup event delegation for dynamically loaded elements
//   document.body.addEventListener('click', event => {
//     const target = event.target;
    
//     // Handle replies
//     if (target.closest('.reply-btn')) {
//       const commentId = target.closest('.reply-btn').dataset.commentId;
//       // Implement reply functionality
//       console.log('Reply to comment:', commentId);
//       event.preventDefault();
//     }
//   });

//   // Implement lazy loading for images
//   if ('IntersectionObserver' in window) {
//     const lazyImageObserver = new IntersectionObserver((entries, observer) => {
//       entries.forEach(entry => {
//         if (entry.isIntersecting) {
//           const lazyImage = entry.target;
//           lazyImage.src = lazyImage.dataset.src;
//           lazyImage.classList.remove('hidden');
//           observer.unobserve(lazyImage);
//         }
//       });
//     });

//     document.querySelectorAll('img[data-src]').forEach(img => {
//       if (img.classList.contains('lazy-comment')) {
//         lazyImageObserver.observe(img);
//       }
//     });
//   } else {
//     // Fallback for browsers without IntersectionObserver
//     const lazyImages = document.querySelectorAll('img[data-src]');
//     lazyImages.forEach(img => {
//       if (img.classList.contains('lazy-comment')) {
//         img.src = img.dataset.src;
//         img.classList.remove('hidden');
//       }
//     });
//   }
// });
// </script>

<script>
// Global function for handling user profile clicks
window.showUserProfile = function(username) {
    $.ajax({
        url: 'get_user_info.php',
        method: 'GET',
        data: { username: username },
        dataType: 'json',
        success: function(userData) {
            if (userData && userData.username) {
                const imgElement = {
                    src: userData.photo || '../assets/images/avatars/user.png'
                };
                previewImage(
                    imgElement,
                    userData.firstname,
                    userData.lastname,
                    userData.username,
                    userData.email
                );
            } else {
                console.error('User data not found');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching user data:', error);
        }
    });
};

</script>

<script>
function copyTicketID(button) {
    const ticketID = document.getElementById('ticketID').innerText;
    
    navigator.clipboard.writeText(ticketID)
        .then(() => {
            const notification = document.createElement('div');
            notification.textContent = 'SRN Copied!';
            notification.style.position = 'absolute';
            notification.style.padding = '5px 10px';
            notification.style.backgroundColor = '#4CAF50';
            notification.style.color = 'white';
            notification.style.borderRadius = '3px';
            notification.style.zIndex = '9999';
            notification.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
            notification.style.fontSize = '12px';
            
            // Position next to the button
            const buttonRect = button.getBoundingClientRect();
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
            
            notification.style.top = (buttonRect.top + scrollTop - 5) + 'px';
            notification.style.left = (buttonRect.right + scrollLeft + 5) + 'px';
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 2000);
        })
        .catch(err => {
            console.error('Could not copy text: ', err);
        });
}

function copyTicketLink(button) {
    const urlParams = new URLSearchParams(window.location.search);
    const taskId = urlParams.get('taskId');
    
    const ticketLink = 'https://force.carmensbest.com.ph/it-ticket/projectmanagement/threadPage.php?taskId=' + encodeURIComponent(taskId);
    
    navigator.clipboard.writeText(ticketLink)
        .then(() => {
            const notification = document.createElement('div');
            notification.textContent = 'Thread Link Copied!';
            notification.style.position = 'absolute';
            notification.style.padding = '5px 10px';
            notification.style.backgroundColor = '#4CAF50';
            notification.style.color = 'white';
            notification.style.borderRadius = '3px';
            notification.style.zIndex = '9999';
            notification.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
            notification.style.fontSize = '12px';
            
            // Position next to the button
            const buttonRect = button.getBoundingClientRect();
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
            
            notification.style.top = (buttonRect.top + scrollTop - 5) + 'px';
            notification.style.left = (buttonRect.right + scrollLeft + 5) + 'px';
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 2000);
        })
        .catch(err => {
            console.error('Failed to copy link: ', err);
        });
}
</script>



<style>
.profile-info-value .description-text {
    display: block;
    font-size: 14px;
    line-height: 1.6;
    color: #555;
}

.profile-info-value .description-text p {
    margin: 0;
    padding: 0;
}

.profile-info-value .description-text p + p {
    margin-top: 16px; /* Space between paragraphs */
}

.profile-info-value .description-text br {
    line-height: 1.8; /* Spacing for single line breaks */
}

/* Style for links in description */
.profile-info-value .description-text a {
    color: #2a6496;
    text-decoration: none;
}

.profile-info-value .description-text a:hover {
    text-decoration: underline;
}
</style>

<style>
.profile-info-value .description-container {
    max-height: 250px;
    overflow-y: auto;
    padding-right: 10px;
    border: 1px solid #e1e1e1;
    border-radius: 4px;
    background: #fff;
}

/* Custom scrollbar styling */
.profile-info-value .description-container::-webkit-scrollbar {
    width: 8px;
}

.profile-info-value .description-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.profile-info-value .description-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.profile-info-value .description-container::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}

.profile-info-value .description-text {
    display: block;
    font-size: 14px;
    line-height: 1.6;
    color: #555;
    padding: 12px;
}

.profile-info-value .description-text p {
    margin: 0;
    padding: 0;
}

.profile-info-value .description-text p + p {
    margin-top: 16px;
}

.profile-info-value .description-text br {
    line-height: 1.8;
}

.profile-info-value .description-text a {
    color: #2a6496;
    text-decoration: none;
}

.profile-info-value .description-text a:hover {
    text-decoration: underline;
}
</style>

<!-- Add this JavaScript at the bottom of your file -->
<script>
function copyDescription() {
    const descriptionText = document.getElementById('description').innerText;
    navigator.clipboard.writeText(descriptionText).then(() => {
        // Optional: Show a brief success message
        alert('Description copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy text: ', err);
        alert('Failed to copy description');
    });
}
</script>

<style>
.status-section,
.priority-section {
    margin-bottom: 1rem;
}

.info-label {
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.status-badge,
.priority-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.875rem;
}

.priority-badge {
    color: white;
}

/* Status colors */
.status-new { background-color: #e9ecef; }
.status-rejected { background-color: #dc3545; color: white; }
.status-inprog { background-color: #007bff; color: white; }
.status-pending { background-color: #ffc107; }
.status-cancelled { background-color: #6c757d; color: white; }
.status-done { background-color: #28a745; color: white; }
.status-pastdue { background-color: #dc3545; color: white; }
</style>
