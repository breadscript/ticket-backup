<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
        <meta charset="utf-8" />
        <title>Registration Page</title>

        <meta name="description" content="User registration page" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
        <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
        <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400&display=swap" />
        <link rel="stylesheet" href="assets/css/ace.min.css" />
        <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />

        <script type="text/javascript">
            function validateForm() {
                var password = document.getElementById("password").value;
                var confirm_password = document.getElementById("confirm_password").value;
                
                if (password != confirm_password) {
                    document.getElementById("password_error").style.display = "block";
                    return false;
                }
                return true;
            }

            function validateEmail(email) {
                const allowedDomains = [
                    '@mpav.com.ph',
                    '@carmensbest.com.ph',
                    '@uhdfi.com',
                    '@metropacificfreshfarms.com',
                    '@metropacificdairyfarms.com'
                ];
                
                email = email.toLowerCase();
                return allowedDomains.some(domain => email.endsWith(domain.toLowerCase()));
            }

           function processRegistration() {
                // Clear previous error messages
                document.getElementById("error_message").style.display = "none";
                document.getElementById("password_error").style.display = "none";

                // Get all form values
                var firstname = document.getElementById("firstname").value.trim();
                var lastname = document.getElementById("lastname").value.trim();
                var username = document.getElementById("username").value.trim();
                var email = document.getElementById("email").value.trim();
                var password = document.getElementById("password").value;
                var confirm_password = document.getElementById("confirm_password").value;

                // Check if any field is empty
                if (!firstname || !lastname || !username || !email || !password || !confirm_password) {
                    document.getElementById("error_message").innerHTML = "All fields are required";
                    document.getElementById("error_message").style.display = "block";
                    return;
                }

                // Password match validation
                if (password !== confirm_password) {
                    document.getElementById("password_error").style.display = "block";
                    return;
                }

                // Email domain validation
                if (!validateEmail(email)) {
                    document.getElementById("error_message").innerHTML = "Please use your company email address";
                    document.getElementById("error_message").style.display = "block";
                    return;
                }

                // Hide the registration form
                document.getElementById("registrationForm").style.display = "none";
                
                // Show loading overlay
                document.getElementById("loadingOverlay").style.display = "flex";

                // If all validations pass, proceed with AJAX request
                var xhr = new XMLHttpRequest();

                xhr.onreadystatechange = function() {
                    if (xhr.readyState == 4) {
                        // Hide loading overlay
                        document.getElementById("loadingOverlay").style.display = "none";
                        
                        if (xhr.status == 200) {
                            var response = xhr.responseText;
                            if (response === "Email already exists") {
                                // Show the form again if there's an error
                                document.getElementById("registrationForm").style.display = "block";
                                document.getElementById("error_message").innerHTML = "This email address is already registered";
                                document.getElementById("error_message").style.display = "block";
                            } else {
                                document.getElementById("box").innerHTML = response;
                            }
                        }
                    }
                };

                xhr.open("POST", "process_registration.php", true);
                xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xhr.send(
                    "firstname=" + encodeURIComponent(firstname) +
                    "&lastname=" + encodeURIComponent(lastname) +
                    "&username=" + encodeURIComponent(username) +
                    "&email=" + encodeURIComponent(email) +
                    "&password=" + encodeURIComponent(password)
                );
            }
        </script>

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
                margin-bottom: 20px;
            }
            .alert-danger {
                color: #721c24;
                background-color: #f8d7da;
                border-color: #f5c6cb;
                padding: 12px;
                margin-bottom: 15px;
                border: 1px solid transparent;
                border-radius: 4px;
            }

            .alert .close {
                float: right;
                font-size: 20px;
                font-weight: bold;
                line-height: 1;
                color: #000;
                opacity: .2;
                background: none;
                border: none;
                padding: 0;
                cursor: pointer;
            }

            .alert .close:hover {
                opacity: .5;
            }

            .loading-overlay {
                display: none;
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                z-index: 9999;
                text-align: center;
            }

            .spinner {
                width: 40px;
                height: 40px;
                border: 4px solid rgba(0, 0, 0, 0.1);
                border-left: 4px solid #3498db;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin: 0 auto 20px;
            }

            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            .loading-text {
                color: #333;
                font-family: Arial, sans-serif;
                font-size: 16px;
                margin-top: 10px;
                font-weight: 500;
            }
        </style>
    </head>

    <body class="login-layout">
        <!-- Loading overlay -->
        <div class="loading-overlay" id="loadingOverlay">
            <div class="loading-spinner">
                <div class="spinner"></div>
                <div class="loading-text">Processing registration...</div>
            </div>
        </div>

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
                                            <span class="glyphicon glyphicon-user"></span> 
                                            Register
                                        </h3>
                                    </div>
                                    <div class="panel-body">
                                        <form role="form" name="form_register" id="registrationForm" onsubmit="return false;">
                                            <fieldset>
                                                <div class="form-group">
                                                    <input class="form-control" placeholder="First Name" name="firstname" id="firstname" type="text" required autofocus>
                                                </div>
                                                <div class="form-group">
                                                    <input class="form-control" placeholder="Last Name" name="lastname" id="lastname" type="text" required>
                                                </div>
                                                <div class="form-group">
                                                    <input class="form-control" placeholder="Username" name="username" id="username" type="text" required>
                                                </div>
                                                <div class="form-group">
                                                    <input class="form-control" placeholder="Email" name="email" id="email" type="email" required>
                                                    <small class="form-text text-muted">Allowed company email address only</small>
                                                </div>
                                                <div class="form-group">
                                                    <input class="form-control" placeholder="Password" id="password" name="password" type="password" required>
                                                </div>
                                                <div class="form-group">
                                                    <input class="form-control" placeholder="Confirm Password" id="confirm_password" name="confirm_password" type="password" required>
                                                </div>

                                                <div class="alert alert-danger" style="display: none" id="error_message">
                                                    <button type="button" class="close" onclick="this.parentElement.style.display='none'">&times;</button>
                                                    <span class="message"></span>
                                                </div>

                                                <div class="alert alert-danger" style="display: none" id="password_error">
                                                    <button type="button" class="close" onclick="this.parentElement.style.display='none'">&times;</button>
                                                    Passwords do not match!
                                                </div>

                                                <button type="submit" class="btn btn-primary btn-success btn-lg btn-block" onclick="processRegistration()">Register</button>
                                                
                                                <div class="text-center" style="margin-top: 15px;">
                                                    Already have an account? <a href="login.php">Login here</a>
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

        <!-- basic scripts -->
        <script src="assets/js/jquery-2.1.4.min.js"></script>
        <script src="assets/js/jquery.shake.js"></script>
    </body>
</html> 