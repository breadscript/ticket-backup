<?php
require('conn/db.php');
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
require 'phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

session_start();
date_default_timezone_set('Asia/Manila'); // Set Philippine timezone

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $conn = connectionDB();
    
    // Check if email exists and user status in database
    $query = "SELECT id, username, user_firstname, user_statusid FROM sys_usertb WHERE emailadd = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Check user status
        if ($row['user_statusid'] == 3) {
            $success_message = "Your account is pending approval. Please wait for admin verification before requesting a password reset.";
        } 
        else if ($row['user_statusid'] == 2) {
            $success_message = "Your account is inactive. Please contact the administrator.";
        }
        else if ($row['user_statusid'] == 1) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            
            // Fix the expiry time calculation
            $current_time = new DateTime();
            $expiry = $current_time->modify('+1 hour')->format('Y-m-d H:i:s');
            
            // Store token in database
            $update_query = "UPDATE sys_usertb SET reset_token = ?, reset_token_expiry = ? WHERE emailadd = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "sss", $token, $expiry, $email);
            mysqli_stmt_execute($stmt);
            
            // Create a new PHPMailer instance
            $mail = new PHPMailer(true);

            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'tlcreameryinc@gmail.com';
                $mail->Password = 'kdvg bueb seul rfnw';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                
                // Set timeout
                $mail->Timeout = 60;
                $mail->SMTPKeepAlive = true;
                
                // Set UTF-8 encoding
                $mail->CharSet = 'UTF-8';
                $mail->Encoding = 'base64';

                // Recipients
                $mail->setFrom('tlcreameryinc@gmail.com', 'The Laguna Creamery Inc. (No-Reply)');
                $mail->addReplyTo('no-reply@tlcreameryinc.com', 'TLCI IT Support (Do Not Reply)');
                $mail->addAddress($email);

                // Add auto-response headers
                $mail->addCustomHeader('Auto-Submitted', 'auto-generated');
                $mail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');

                // Content
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/it-ticket/reset-password.php?token=" . $token;
                
                $mail->isHTML(true);
                $mail->Subject = "Password Reset Request";
                $mail->Body = "Hello " . htmlspecialchars($row['user_firstname']) . ",<br><br>"
                    . "You have requested to reset your password. Click the link below to reset your password:<br><br>"
                    . "<a href='" . $reset_link . "'>" . $reset_link . "</a><br><br>"
                    . "This link will expire in 1 hour.<br><br>"
                    . "If you did not request this password reset, please ignore this email.<br><br>"
                    . "<div style='background-color: #f8f9fa; padding: 15px; margin-top: 20px; border-left: 4px solid #dc3545;'>"
                    . "<p style='color: #dc3545; font-weight: bold; margin: 0;'>IMPORTANT: This email was sent from a notification-only address that cannot accept incoming email. Please do not reply to this message.</p>"
                    . "</div>";
                
                $mail->AltBody = strip_tags(str_replace("<br>", "\n", $mail->Body));

                // Try to send
                if (!$mail->send()) {
                    throw new Exception("Mailer Error: " . $mail->ErrorInfo);
                }
                
                $success_message = "If an account exists with this email, you will receive password reset instructions.";
                
            } catch (Exception $e) {
                $success_message = "If an account exists with this email, you will receive password reset instructions.";
            }
        } else {
            // For other status users
            $success_message = "If an account exists with this email, you will receive password reset instructions.";
        }
    } else {
        // Show same message even if email doesn't exist (security best practice)
        $success_message = "If an account exists with this email, you will receive password reset instructions.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title>Forgot Password</title>
    <meta name="description" content="Forgot Password page" />
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
                                        Forgot Password
                                    </h3>
                                </div>
                                <div class="panel-body">
                                    <?php if (isset($success_message)): ?>
                                        <div class="alert alert-info">
                                            <?php echo $success_message; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <form role="form" method="POST">
                                        <fieldset>
                                            <div class="form-group">
                                                <input class="form-control" placeholder="Email Address" name="email" type="email" required autofocus>
                                            </div>
                                            <button type="submit" class="btn btn-primary btn-success btn-lg btn-block">
                                                Reset Password
                                            </button>
                                            <br>
                                            <div class="text-center">
                                                <a href="login.php">Back to Login</a>
                                            </div>
                                        </fieldset>
                                    </form>
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