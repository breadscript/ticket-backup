<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Clear session
$_SESSION = [];
if (ini_get('session.use_cookies')) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000,
		$params['path'], $params['domain'],
		$params['secure'], $params['httponly']
	);
}
session_destroy();

// Clear app cookies used by legacy login
@setcookie('cooklogid','', time()-3600, '/');
@setcookie('cookuserid','', time()-3600, '/');
@setcookie('cookusername','', time()-3600, '/');
@setcookie('cookuserpassword','', time()-3600, '/');
@setcookie('cookuserstatid','', time()-3600, '/');
@setcookie('cookcompanyid','', time()-3600, '/');
@setcookie('cookuserfirstname','', time()-3600, '/');
@setcookie('cookuseremail','', time()-3600, '/');
@setcookie('cookuserposition','', time()-3600, '/');
@setcookie('userlevelid','', time()-3600, '/');
@setcookie('sessiontokencode','', time()-3600, '/');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title>RFP Logout</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="../assets/css/fonts.googleapis.com.css" />
    <link rel="stylesheet" href="../assets/css/ace.min.css" />
    <link rel="stylesheet" href="../assets/css/ace-rtl.min.css" />
  </head>
  <body class="login-layout">
    <div class="main-container">
      <div class="main-content">
        <div class="row">
          <div class="col-sm-10 col-sm-offset-1">
            <div class="login-container">
              <div class="center">
                <h1>
                  <i class="fa fa-credit-card red" aria-hidden="true"></i>
                  <span class="red">RFP</span>
                  <span class="white" id="id-text2">Disbursement</span>
                </h1>
              </div>
              <div id="box">
                <div class="panel panel-default">
                  <div class="panel-heading">
                    <h3 class="panel-title"><span class="glyphicon glyphicon-log-out"></span> Logged out</h3>
                  </div>
                  <div class="panel-body">
                    <p>You have been successfully logged out.</p>
                    <a class="btn btn-success btn-block" href="login.php"><i class="fa fa-sign-in"></i> Back to Login</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>
  </html>


