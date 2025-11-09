<?php 
// Move error reporting before any output
error_reporting(E_ALL);
ini_set('display_errors', 1);

require('conn/db.php');
session_start(); // Remove @ suppression

$conn = connectionDB();
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

		//delete cookies
		setcookie("cooklogid", "", time()-60*60*24*100, "/");
		setcookie("cookuserid", "", time()-60*60*24*100, "/");
		setcookie("cookusername", "", time()-60*60*24*100, "/");
		setcookie("cookuserpassword", "", time()-60*60*24*100, "/");
		setcookie("cookuserstatid", "", time()-60*60*24*100, "/");
		setcookie("cookuserfirstname", "", time()-60*60*24*100, "/"); 
		setcookie("userlevelid", "", time()-60*60*24*100, "/"); 
		setcookie("sessiontokencode", "", time()-60*60*24*100, "/");  
		
		//initialize variable
		$logid = isset($_SESSION['logid']) ? $_SESSION['logid'] : null;
		$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : null;
		$username = isset($_SESSION['username']) ? $_SESSION['username'] : null;
		$userfirstname = isset($_SESSION['userfirstname']) ? $_SESSION['userfirstname'] : null;	
		$userid2 = isset($_SESSION['userid']) ? $_SESSION['userid'] : null;
		// echo "</br>";
		// Look for the user in the users table.
		if ($userid && $username) {
			$query = "SELECT sys_loginlogstb.*, sys_usertb.* 
			FROM sys_loginlogstb 
			LEFT JOIN sys_usertb ON sys_loginlogstb.userid=sys_usertb.id 
			WHERE sys_loginlogstb.userid=? AND sys_usertb.username=? 
			ORDER BY sys_loginlogstb.logintime DESC";
			
			$stmt = mysqli_prepare($conn, $query);
			mysqli_stmt_bind_param($stmt, "is", $userid, $username);
			mysqli_stmt_execute($stmt);
			$result = mysqli_stmt_get_result($stmt);

			if ($result && $row3 = mysqli_fetch_assoc($result)) {
				@$id = $row3['userid'];
				@$oras2 = $row3['logintime'];
			}
		}
		// @$id = mysqli_fetch_assoc($result,0,'userid');				
		// @$oras2 = mysqli_fetch_assoc($result,0,'logintime');
		// echo "</br>";
		//update loggesuser last access
		// echo $query2 = "UPDATE sys_loginlogstb SET lastaccess=now(),isol = '1' WHERE userid = $id AND id=$logid";
		if ($logid) {
			$query2 = "UPDATE sys_loginlogstb SET lastaccess=NOW(), isol='1' WHERE id=?";
			$stmt2 = mysqli_prepare($conn, $query2);
			mysqli_stmt_bind_param($stmt2, "i", $logid);
			mysqli_stmt_execute($stmt2);
		}
		
		//delete sessions
		unset($_SESSION['userid']);
		unset($_SESSION['usergroupid']);
		unset($_SESSION['username']);
		unset($_SESSION['userstatid']);
		unset($_SESSION['userfirstname']);
		unset($_SESSION['userlastname']);
		unset($_SESSION['userlevelid']);
		unset($_SESSION['sessiontokencode']);	
		@session_destroy();
		$_SESSION = array();

		// Ensure all output is cleared
		ob_clean();

?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta charset="utf-8" />
		<title>Login Page </title>

		<meta name="description" content="User login page" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
		<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
		<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
		<link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
		<link rel="stylesheet" href="assets/css/ace.min.css" />
		<link rel="stylesheet" href="assets/css/ace-rtl.min.css" />
	<script type="text/javascript">
			function getXMLHTTP() { //fuction to return the xml http object
			var xmlhttp=false;	
			try{
				xmlhttp=new XMLHttpRequest();
			}
			catch(e)	{		
				try{			
					xmlhttp= new ActiveXObject("Microsoft.XMLHTTP");
				}
				catch(e){
					try{
					req = new ActiveXObject("Msxml2.XMLHTTP");
					}
					catch(e1){
						xmlhttp=false;
					}
				}
			}
				
			return xmlhttp;
		}
		
		function processvalidate(strURL) 
		{					
			var req = getXMLHTTP();

			if (req)
				{			
					req.onreadystatechange = function() 
					{
						if (req.readyState == 4) 
						{
							// only if "OK"
							if (req.status == 200) 
							{											
								var data;
								data="";
								data = req.responseText.split("|");
								//alert(data);
								
								//css display
								var ele = document.getElementById("toggleText");

								
								if(data[1] == 0)
								{
								
									//alert('Access Denied.');								
									ele.style.display = "block";
									clearfields(); 
									$('#box').shake();
									
								}
								else if(data[1] == 2)
								{
									//alert('Access Denied. No Record found.');
									ele.style.display = "block";
									clearfields(); 
									$('#box').shake();
								}
								else
								{	
									//data[1] == 1						
								   //correct username
									var data_status;
									data_status=data[2];
									
									//usergroupid								
									var theusergroupid=data[3];
									
									if(theusergroupid == 1)
									{
										//redirect - superadmins
										window.location='index.php';
									}
									else if(theusergroupid == 2)
									{
										//redirect - recruiter
										window.location='index.php';
									}
									else if(theusergroupid == 3)
									{
										//redirect - hr
										window.location='index.php';
									}
									else if(theusergroupid == 4)
									{
										//redirect - payroll
										window.location='index.php';
									}
									else if(theusergroupid == 5)
									{
										//redirect - account coor
										window.location='index.php';
									}
									else
									{
										window.location='index.php';
									}
								}
								
							} 
							else 
							{
								alert("There was a problem while using XMLHTTP:\n" + req.statusText);
							}
						}				
					}			
				
				req.open('GET', strURL, true);
				req.send(null);
			}
					
		}
					
			function clearfields() 
			{
				document.form_login.username.value = "";
				document.form_login.pswd.value = "";
			}
			
			function hidediv()
			{			
			  var ele = document.getElementById("toggleText");
			  ele.style.display = "none";
			} 

	</script>
	</head>

	<body class="login-layout">
		<div class="main-container">
			<div class="main-content">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<div class="login-container" style="margin-top: 100px;">
							<div class="center">
								<h1>
									<i class="fa fa-bell red" aria-hidden="true"></i>
									<span class="red">TLCI</span>
									<span class="white" id="id-text2">IT Department</span>
								</h1>
							</div>
							<!-- Form --> 
							<div id="box">
								<div class="panel panel-default">
									<div class="panel-heading">
										<h3 class="panel-title">
											<span class="glyphicon glyphicon-log-in"></span> 
											 Log-in
										</h3>
									</div>
									<div class="panel-body">
										<form role="form" name="form_login" onsubmit="return false;">
											<fieldset>
												<div class="form-group">
													<input class="form-control" placeholder="Username" name="username" id="username" type="username" value="" autofocus onchange="hidediv()" onkeypress="handleKeyPress(event)">
												</div>
												<div class="form-group">
													<input class="form-control" placeholder="Password" id="pswd" name="pswd" type="password" value="" onchange="hidediv()" onkeypress="handleKeyPress(event)">
												</div>
												<div class="checkbox" style="display: none">
													<label>
													<input name="remember" type="hidden" value="1" id="remember">
													</label>
												</div>
												<div class="alert alert-danger alert-dismissable" style="display: none" id="toggleText">
													<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
													Invalid Username or password. Please try again using the correct log-in information.
												</div>  
												<!-- Change this to a button or input when using this as a form -->
												<button id="loginbtn" type="button" class="btn btn-primary btn-success btn-lg btn-block input-xs" onclick="processvalidate('session.php?username='+document.getElementById('username').value+'&pswd='+document.getElementById('pswd').value+'&remember='+document.getElementById('remember').value)" value="Login">Login</button>
												<br>
												<div class="text-center" style="margin-top: 15px;">
													Don't have an account? <a href="register.php">Register here</a>
													<br>
													<a href="forgot-password.php">Forgot Password?</a>
												</div>
												<p class="help-block">
													<!--<a href="../hrportal/index.php" target="_self">
														<i class="glyphicon glyphicon-hand-right"></i>&nbsp;Back to HR Portal.
													</a>
													
														<div class='row'><div class='panel-body'><div class='alert alert-danger'>
														<strong><i class='glyphicon glyphicon-lock'></i>&nbsp;NOTICE!</strong> 
															</br>System is temporary unavailable due to server update. 
														</div></div>
														</div>
													-->
												</p>
											</fieldset>
										</form>
									</div>
								</div>
								<!-- <button id="submitConcernBtn" type="button" class="btn btn-primary btn-primary btn-lg btn-block input-xs" onclick="window.location.href='https://force.carmensbest.com.ph/it-ticket/instantticket/modal_createticket.php'" value="Submit Concerns">Submit Concerns <i class="fa fa-arrow-right"></i></button> -->

							</div>
						</div>
					</div><!-- /.col -->
				</div><!-- /.row -->
			</div><!-- /.main-content -->
		</div><!-- /.main-container -->

		<!-- basic scripts -->

		<!--[if !IE]> -->
		<script src="assets/js/jquery-2.1.4.min.js"></script>
		<!-- SHAKE ANIMATION -->
		<script src="assets/js/jquery.shake.js"></script>  
		
		<script>
			function handleKeyPress(event) {
				if (event.keyCode === 13) { // 13 is the Enter key
					event.preventDefault();
					processvalidate('session.php?username='+document.getElementById('username').value+'&pswd='+document.getElementById('pswd').value+'&remember='+document.getElementById('remember').value);
				}
			}
		</script>

	</body>
</html>
