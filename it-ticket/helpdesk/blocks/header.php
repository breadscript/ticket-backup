<?php
include('../check_session.php'); 
$user_id = $_SESSION['userid'] ?? 0;

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

// Fetch user information
$sql = "SELECT * FROM sys_usertb WHERE id = $user_id";
$result = mysqli_query($mysqlconn, $sql);
$user = mysqli_fetch_assoc($result);
$moduleid = 8;

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
                    pt.createdbyid = ? 
                    OR t.parent_id IN (SELECT id FROM pm_threadtb WHERE createdbyid = ?)
                    OR t.message LIKE CONCAT('%@', ?, '%')
                 )
                 AND t.createdbyid != ?
                 ORDER BY is_unread DESC, t.datetimecreated DESC
                 LIMIT 10";

$messages_stmt = mysqli_prepare($mysqlconn, $messages_sql);
$username = $user['username'] ?? '';
mysqli_stmt_bind_param($messages_stmt, "siiss", $username, $user_id, $user_id, $username, $user_id);
mysqli_stmt_execute($messages_stmt);
$messages_result = mysqli_stmt_get_result($messages_stmt);

$messages = [];
$unread_messages = [];
$read_messages = [];
$unread_messages_count = 0;

while ($row = mysqli_fetch_assoc($messages_result)) {
    $messages[] = $row;
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
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
        <meta charset="utf-8" />
        <title></title>
        
        <!-- Favicon -->
        <link rel="icon" type="image/png" href="../assets/images/favicon/favicon-32x32.png" />
        
        <!--[if IE]><link rel="shortcut icon" href="images/favicon.ico"><![endif]
        <link rel="apple-touch-icon-precomposed" href="../assets/images/logo/logo.png">
        <link rel="icon" href="../assets/images/logo/logo.png">-->
        
        <meta name="description" content="overview &amp; stats" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
        
        <!-- bootstrap & fontawesome -->
        <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
        <link rel="stylesheet" href="../assets/font-awesome/4.5.0/css/font-awesome.min.css" />

        <!-- page specific plugin styles -->
        <link rel="stylesheet" href="../assets/css/jquery-ui.custom.min.css" />
        <link rel="stylesheet" href="../assets/css/jquery.gritter.min.css" />

        <!-- text fonts -->
        <link rel="stylesheet" href="../assets/css/fonts.googleapis.com.css" />

        <!-- ace styles -->
        <link rel="stylesheet" href="../assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />

        <link rel="stylesheet" href="../assets/css/ace-skins.min.css" />
        <link rel="stylesheet" href="../assets/css/ace-rtl.min.css" />
    
        <link href="../assets/css/bootstrap-modal-bs3patchx.css" rel="stylesheet" />
        <link href="../assets/css/bootstrap-modal.css" rel="stylesheet" /> 
        <link rel="stylesheet" href="../assets/css/bootstrap-datepicker3.min.css" />
        <!-- ace settings handler -->
        <script src="../assets/js/ace-extra.min.js"></script>
        <link rel="stylesheet" href="../assets/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css">
        <link rel="stylesheet" href="../assets/css/bootstrap-datepicker3.min.css" />

        <!-- Include notifications script -->
        <script src="../assets/js/notification.js"></script>
    </head>

    <div id="navbar" class="navbar navbar-default ace-save-state">
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
                    </small>
                </a>
            </div>

            <div class="navbar-buttons navbar-header pull-right" role="navigation">
                <ul class="nav ace-nav">
                    <!-- Tasks -->
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

                    <!-- Notifications -->
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
                                                <a href="../projectmanagement/threadPage.php?taskId=<?php echo $notification['taskid']; ?>&highlight=<?php echo $notification['id']; ?>" 
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
                                                <a href="../projectmanagement/threadPage.php?taskId=<?php echo $notification['taskid']; ?>&highlight=<?php echo $notification['id']; ?>" 
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

                    <!-- Messages -->
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
                                <?php echo $unread_messages_count; ?> Messages
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

                    <!-- User Menu -->
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
                                <a href="../logout.php">
                                    <i class="ace-icon fa fa-power-off"></i>
                                    Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</html>
