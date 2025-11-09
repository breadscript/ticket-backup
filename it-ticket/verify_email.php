<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
        <meta charset="utf-8" />
        <title>Email Verification</title>

        <meta name="description" content="Email verification page" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
        <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
        <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400&display=swap" />
        <link rel="stylesheet" href="assets/css/ace.min.css" />
        <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />

        <style>
            .panel-success {
                border-color: #d6e9c6;
            }
            .panel-success > .panel-heading {
                color: #3c763d;
                background-color: #dff0d8;
                border-color: #d6e9c6;
            }
            .alert-success {
                color: #3c763d;
                background-color: #dff0d8;
                border-color: #d6e9c6;
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 4px;
            }
            .center-content {
                text-align: center;
                margin-top: 50px;
            }
            .verification-message {
                margin: 20px 0;
                font-size: 16px;
            }
            .login-link {
                color: #3498db;
                text-decoration: none;
                font-weight: 500;
            }
            .login-link:hover {
                text-decoration: underline;
            }
        </style>
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

                            <div class="position-relative">
                                <div id="verification-box" class="verification-box visible widget-box no-border">
                                    <?php
                                    require_once(__DIR__ . '/conn/db.php');

                                    if (!isset($_GET['token'])) {
                                        die('No verification token provided');
                                    }

                                    $token = $_GET['token'];
                                    $conn = connectionDB();

                                    if (!$conn) {
                                        die("Connection failed");
                                    }

                                    $stmt = $conn->prepare("SELECT id FROM sys_usertb WHERE verification_token = ? AND user_statusid = 0");
                                    $stmt->bind_param("s", $token);
                                    $stmt->execute();
                                    $result = $stmt->get_result();

                                    if ($result->num_rows === 1) {
                                        $user = $result->fetch_assoc();
                                        $user_id = $user['id'];
                                        
                                        // Update user status
                                        $update_stmt = $conn->prepare("UPDATE sys_usertb SET user_statusid = 1, status = 'active', verification_token = NULL WHERE id = ?");
                                        $update_stmt->bind_param("i", $user_id);
                                        
                                        if ($update_stmt->execute()) {
                                            // Insert authority module access
                                            $auth_stmt = $conn->prepare("INSERT INTO sys_authoritymoduletb (
                                                userid, 
                                                moduleid,
                                                authoritystatusid,
                                                moduleorderno,
                                                createdby_userid,
                                                statid,
                                                modulecategoryid
                                            ) VALUES (?, 1, 1, 1, 1, 1, 1)");
                                            
                                            $auth_stmt->bind_param("i", $user_id);
                                            $auth_stmt->execute();
                                            
                                            $auth_cat_stmt = $conn->prepare("INSERT INTO sys_authoritymodulecategorytb (userid, modulecategoryid, statid, createdby_userid) VALUES (?, 1, 1, 1)");
                                            $auth_cat_stmt->bind_param("i", $user_id);
                                            $auth_cat_stmt->execute();

                                             // Add audit log entry
                                             $audit_stmt = $conn->prepare("INSERT INTO sys_audit (
                                                module,
                                                remarks,
                                                userid
                                            ) VALUES (1, 'system generated verification', ?)");
                                            
                                            $audit_stmt->bind_param("i", $user_id);
                                            $audit_stmt->execute();
                                            
                                            echo '<div class="panel panel-success">
                                                    <div class="panel-heading">
                                                        <h3 class="panel-title">
                                                            <i class="fa fa-check-circle"></i> 
                                                            Email Verified Successfully!
                                                        </h3>
                                                    </div>
                                                    <div class="panel-body center-content">
                                                        <div class="verification-message">
                                                            Your account has been activated. You can now login to your account.
                                                        </div>
                                                        <a href="login.php" class="btn btn-primary btn-lg">
                                                            <i class="fa fa-sign-in"></i> Login Now
                                                        </a>
                                                    </div>
                                                </div>';
                                        } else {
                                            echo '<div class="alert alert-danger">Error activating account</div>';
                                        }
                                    } else {
                                        echo '<div class="alert alert-danger">Invalid or expired verification token</div>';
                                    }

                                    $stmt->close();
                                    $conn->close();
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- basic scripts -->
        <script src="assets/js/jquery-2.1.4.min.js"></script>
    </body>
</html> 