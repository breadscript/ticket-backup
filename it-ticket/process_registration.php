<?php
// Include PHPMailer
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
require 'phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Database connection
$db_host = "localhost";
$db_user = "root";         
$db_pass = "";            
$db_name = "techsource";  

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get POST data
$username = $_POST['username'];
$email = $_POST['email'];
$password = $_POST['password'];
$firstname = $_POST['firstname'];
$lastname = $_POST['lastname'];

// Validate input
if (empty($username) || empty($email) || empty($password) || empty($firstname) || empty($lastname)) {
    echo "All fields are required";
    exit();
}

// Validate email domain
$allowed_domains = array(
    '@mpav.com.ph',
    '@carmensbest.com.ph',
    '@uhdfi.com',
    '@metropacificfreshfarms.com',
    '@metropacificdairyfarms.com'
);

$valid_domain = false;
foreach ($allowed_domains as $domain) {
    if (str_ends_with(strtolower($email), strtolower($domain))) {
        $valid_domain = true;
        break;
    }
}

if (!$valid_domain) {
    echo "Please use your company email address";
    exit();
}

// Check if email already exists
$stmt = $conn->prepare("SELECT id FROM sys_usertb WHERE emailadd = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "Email already exists";
    exit();
}

// Check if username already exists
$stmt = $conn->prepare("SELECT id FROM sys_usertb WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "Username already exists";
    exit();
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Set default values - updated
$user_statusid = '0';  // Changed to 0 (pending verification)
$user_levelid = 4;     
$user_groupid = 4;     
$status = 'pending';   // Changed to pending until verified
$current_date = date('Y-m-d H:i:s');

// Generate verification token
$verification_token = bin2hex(random_bytes(32));

// Insert new user with verification token
$stmt = $conn->prepare("INSERT INTO sys_usertb (
    user_firstname, 
    user_lastname, 
    username, 
    password, 
    user_statusid,
    user_levelid,
    user_groupid,
    datecreated,
    emailadd,
    status,
    verification_token
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("ssssiiissss", 
    $firstname,
    $lastname,
    $username,
    $hashed_password,
    $user_statusid,
    $user_levelid,
    $user_groupid,
    $current_date,
    $email,
    $status,
    $verification_token
);

if ($stmt->execute()) {
    // Get the newly inserted user ID
    $new_user_id = $conn->insert_id;
    
    // Create verification link
    $verification_link = "http://" . $_SERVER['HTTP_HOST'] . "/it-ticket/verify_email.php?token=" . $verification_token;
    
    // Send verification email
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tlcreameryinc@gmail.com';
        $mail->Password = 'kdvg bueb seul rfnw';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Set timeout and encoding
        $mail->Timeout = 60;
        $mail->SMTPKeepAlive = true;
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        // Recipient
        $mail->setFrom('tlcreameryinc@gmail.com', 'The Laguna Creamery Inc. (No-Reply)');
        $mail->addAddress($email, $firstname . ' ' . $lastname);

        // Email content for verification
        $mail->isHTML(true);
        $mail->Subject = "Verify Your Email Address";
        $mail->Body = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='text-align: center; margin-bottom: 20px;'>
                    <div style='background-color: #3498db; color: white; padding: 20px; text-align: center; font-weight: bold; font-size: 24px; border-radius: 5px;'>Welcome to TLCI</div>
                </div>
                
                <div style='background-color: #f8f9fa; padding: 15px; margin-bottom: 20px;'>
                    <h2 style='color: #2C3E50; margin-top: 0;'>Verify Your Email Address</h2>
                    <p>Please click the button below to verify your email address and activate your account.</p>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$verification_link' style='background-color: #4CAF50; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px;'>Verify Email Address</a>
                </div>
                
                <div style='background-color: #ffffff; border: 1px solid #e9ecef; padding: 20px; margin-bottom: 20px;'>
                    <p>Or copy and paste this link in your browser:</p>
                    <p style='word-break: break-all;'>$verification_link</p>
                </div>
                
                <p><small>If you didn't create an account, you can safely ignore this email.</small></p>
            </div>
        </body>
        </html>";
        
        $mail->AltBody = strip_tags(str_replace("<br>", "\n", $mail->Body));
        
        // Send verification email
        $mail->send();
        
        // Send notification to admin
        $adminMail = new PHPMailer(true);
        
        // Server settings for admin email
        $adminMail->isSMTP();
        $adminMail->Host = 'smtp.gmail.com';
        $adminMail->SMTPAuth = true;
        $adminMail->Username = 'tlcreameryinc@gmail.com';
        $adminMail->Password = 'kdvg bueb seul rfnw';
        $adminMail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $adminMail->Port = 587;
        
        // Set timeout and encoding for admin email
        $adminMail->Timeout = 60;
        $adminMail->SMTPKeepAlive = true;
        $adminMail->CharSet = 'UTF-8';
        $adminMail->Encoding = 'base64';

        // Admin recipient
        $adminMail->setFrom('tlcreameryinc@gmail.com', 'The Laguna Creamery Inc. (No-Reply)');
        $adminMail->addAddress('MMREDONDO@CARMENSBEST.COM.PH', 'Admin');

        // Email content for admin notification
        $adminMail->isHTML(true);
        $adminMail->Subject = "New User Registration Notification";
        $adminMail->Body = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='text-align: center; margin-bottom: 20px;'>
                    <div style='background-color: #3498db; color: white; padding: 20px; text-align: center; font-weight: bold; font-size: 24px; border-radius: 5px;'>New User Registration</div>
                </div>
                
                <div style='background-color: #f8f9fa; padding: 15px; margin-bottom: 20px;'>
                    <h2 style='color: #2C3E50; margin-top: 0;'>Registration Details</h2>
                    <p><strong>Name:</strong> $firstname $lastname</p>
                    <p><strong>Username:</strong> $username</p>
                    <p><strong>Email:</strong> $email</p>
                    <p><strong>Registration Date:</strong> $current_date</p>
                </div>
                
                <p><small>This is an automated notification. Please do not reply to this email.</small></p>
            </div>
        </body>
        </html>";
        
        $adminMail->AltBody = strip_tags(str_replace("<br>", "\n", $adminMail->Body));
        
        // Send admin notification email
        try {
            $adminMail->send();
        } catch (Exception $e) {
            error_log("Admin notification email failed: " . $adminMail->ErrorInfo);
            // Don't show this error to the user since their registration was successful
        }
        
        // Show success message
        echo '
        <div class="panel panel-success">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <span class="glyphicon glyphicon-ok"></span> 
                    Registration Successful
                </h3>
            </div>
            <div class="panel-body">
                <div class="alert alert-success">
                    <h4>Thank you for registering!</h4>
                    <p>Please check your email to verify your account before logging in.</p>
                </div>
            </div>
        </div>';
        
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        echo "Error sending verification email";
    }
} else {
    echo "Error registering user";
}

$stmt->close();
$conn->close();
?> 