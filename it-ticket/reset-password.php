<?php
require('conn/db.php');
session_start();
date_default_timezone_set('Asia/Manila'); // Set Philippine timezone

$conn = connectionDB();
$error_message = '';
$success_message = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Verify token, expiry and user status
    $query = "SELECT id, user_statusid FROM sys_usertb WHERE reset_token = ? AND reset_token_expiry > NOW() AND reset_token_expiry IS NOT NULL";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        if ($row['user_statusid'] != 1) {
            $error_message = "Your account is not active. Please contact administrator.";
        }
    } else {
        $error_message = "Invalid or expired reset token.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    $token = $_POST['token'];
    
    // Check user status before allowing password reset
    $status_query = "SELECT user_statusid FROM sys_usertb WHERE reset_token = ?";
    $stmt = mysqli_prepare($conn, $status_query);
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        if ($row['user_statusid'] != 1) {
            $error_message = "Your account is not active. Please contact administrator.";
        } else {
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            if ($password !== $confirm_password) {
                $error_message = "Passwords do not match.";
            } else {
                // Update password and clear reset token
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_query = "UPDATE sys_usertb SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = ?";
                $stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($stmt, "ss", $hashed_password, $token);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success_message = "Password has been reset successfully. You can now login with your new password.";
                } else {
                    $error_message = "Error resetting password. Please try again.";
                }
            }
        }
    } else {
        $error_message = "Invalid reset token.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title>Reset Password</title>
    <meta name="description" content="Reset Password page" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />
</head>

<body class="login-layout">
    <div class="main-container">
        <div class="main-content">
            <div class="row">
                <div class="col-sm-10 col-sm-offset-1">
                    <div class="login-container">
                        <div class="center">
                            <h1>
                                <i class="fa fa-bell red" aria-hidden="true"></i>
                                    <span class="red">TLCI</span>
                                    <span class="white" id="id-text2">IT Department</span>
                            </h1>
                        </div>
                        <div id="box">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h3 class="panel-title">
                                        <span class="glyphicon glyphicon-lock"></span> 
                                        Reset Password
                                    </h3>
                                </div>
                                <div class="panel-body">
                                    <?php if ($error_message): ?>
                                        <div class="alert alert-danger">
                                            <?php echo $error_message; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($success_message): ?>
                                        <div class="alert alert-success">
                                            <?php echo $success_message; ?>
                                            <br>
                                            <a href="login.php">Click here to login</a>
                                        </div>
                                    <?php elseif (!$error_message): ?>
                                        <form role="form" method="POST">
                                            <fieldset>
                                                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                                                <div class="form-group">
                                                    <input class="form-control" placeholder="New Password" name="password" type="password" required>
                                                </div>
                                                <div class="form-group">
                                                    <input class="form-control" placeholder="Confirm New Password" name="confirm_password" type="password" required>
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-success btn-lg btn-block">
                                                    Reset Password
                                                </button>
                                            </fieldset>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery-2.1.4.min.js"></script>
</body>
</html> 