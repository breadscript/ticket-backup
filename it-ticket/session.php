<?php
require('conn/db.php');
include_once("assets/classes/rndPass.class.php");
session_start();
// error_reporting(0);
$conn = connectionDB();

// Has a cookie been initiated previously?
if (! isset($_COOKIE['cookusername']) && !isset ($_COOKIE['cookuserpassword'])) 
{
	   $username = $_GET['username'];
	   $pswd = $_GET['pswd'];
	   $remember = $_GET['remember'];

/*
	   if ($remember!="off")
	   {
		$remember="yes";
	   }
	   else
	   {
	     $remember="no";
	   }
*/	   
	  	  
	   
	   	
	   	/*echo "Username: ".$username."<br>";
	    echo "Password: ".$pswd."<br>";	  
		echo "Remember Me: ".$remember."<br>";*/	 
	
		// Look for the user in the users table.
		$query = "SELECT * FROM sys_usertb WHERE username='$username'";
		$result = mysqli_query($conn, $query);

		if (!$result) {
			error_log("Query failed: " . mysqli_error($conn));
			die("Database error");
		}

		if (mysqli_num_rows($result) == 0) {
			$loc = "User not found";
			$isok = 2;
			echo $loc . "|" . $isok;
			die();
		}

		$row3 = mysqli_fetch_assoc($result);
		$storedPassword = $row3['password'];
		$userStatusId = $row3['user_statusid'];
		$userFirstName = $row3['user_firstname'];
		$userLastName = $row3['user_lastname'];

		// Check user status
		if ($userStatusId == '3') {
			$loc = "Pending Approval";
			$isok = 4; // New status code for pending approval
			echo $loc . "|" . $isok . "|" . $userFirstName . " " . $userLastName;
			die();
		} else if ($userStatusId != '1') {
			$loc = "Account Inactive";
			$isok = 0;
			echo $loc . "|" . $isok;
			die();
		}

		// Debug information
		error_log("Login attempt for user: " . $username);
		error_log("Stored hash: " . $storedPassword);
		error_log("Provided password: " . $pswd);

		// Check if the stored password is actually hashed
		if (strlen($storedPassword) < 20 || strpos($storedPassword, '$2y$') !== 0) {
			error_log("WARNING: Password in database may not be properly hashed");
			// For testing: Hash the stored password if it's not already hashed
			$newHash = password_hash($storedPassword, PASSWORD_DEFAULT);
			// Update the database with the proper hash
			$updateQuery = "UPDATE sys_usertb SET password = '$newHash' WHERE username = '$username'";
			mysqli_query($conn, $updateQuery);
			$storedPassword = $newHash;
		}

		if (password_verify($pswd, $storedPassword)) {
			// Password is correct, continue with login
			$usergroupid = $row3['user_groupid'];
			$userid = $row3['id'];
			$username = $row3['username'];
			$userstatid = $row3['user_statusid'];
			$userfirstname = $row3['user_firstname'];
			$userlastname = $row3['user_lastname'];
			$userlevelid = $row3['user_levelid'];
			$userphoto = $row3['photo'];

			// If no previous session, has the user submitted the form?
			if (isset($_GET['username']))
			{
					// If the user was found, assign some session variables.
					if (mysqli_num_rows($result) == 1)
					{
						//create sessions					    
						$_SESSION['userid']=$userid;
						$_SESSION['usergroupid']=$usergroupid;
						$_SESSION['password'] = $storedPassword;
						$_SESSION['username'] = $username;
						$_SESSION['userstatid'] = $userstatid;
						$_SESSION['userfirstname'] = $userfirstname;
						$_SESSION['userlastname'] = $userlastname;
						$_SESSION['userlevelid'] = $userlevelid;	
						$_SESSION['userphoto'] = $userphoto;
						
						//create session token code
						$tokencodex=new rndPass(15);
						$_SESSION['sessiontokencode'] = $tokencodex->PassGen();
						
						$userid = $_SESSION['userid'];
						$usergroupid = $_SESSION['usergroupid'];
						$username = $_SESSION['username'];
						$userstatid=$_SESSION['userstatid'];	
						$userfirstname=$_SESSION['userfirstname'];	
						$userlevelid=$_SESSION['userlevelid'];
						$sessiontokencode=$_SESSION['sessiontokencode'];
				 
							 //set cookie remember? 					  
							if(isset($_GET['remember']))
							{										
								 @setcookie("cooklogid", $logid, time()+60*60*24*100, "/");
								 @setcookie("cookuserid", $userid, time()+60*60*24*100, "/");
								 @setcookie("cookusername", $userusername, time()+60*60*24*100, "/");
								 @setcookie("cookuserpassword", $userpassword, time()+60*60*24*100, "/");
								 @setcookie("cookuserstatid", $userstatid, time()+60*60*24*100, "/");
								 @setcookie("cookcompanyid", $companyid, time()+60*60*24*100, "/");
								 @setcookie("cookuserfirstname", $userfirstname, time()+60*60*24*100, "/");
								 @setcookie("cookuseremail", $useremail, time()+60*60*24*100, "/");
								 @setcookie("cookuserposition", $userposition, time()+60*60*24*100, "/");	
								 @setcookie("userlevelid", $userlevelid, time()+60*60*24*100, "/");
								 @setcookie("sessiontokencode", $sessiontokencode, time()+60*60*24*100, "/");							 
							}	
							// Look for the lastuser in the loggedusers table.
						
							$query2x = "SELECT * FROM sys_loginlogstb ORDER BY logintime DESC";
							$result2x = mysqli_query($conn, $query2x);
							if (mysqli_num_rows($result2x) < 1){
								// echo "1";
								$logid_old = 0;
								$_SESSION['logid']=$logid_old+1;
								@$logid = $_SESSION['logid'];
							}else{
								// echo "2";
								while($row4x = mysqli_fetch_assoc($result2x)){
									$logid_old = $row4x['id'];					
									$_SESSION['logid']=$logid_old+1;
									$logid = $_SESSION['logid'];
								}
							}
							// echo "logs:".$logid;
							//insert data for log users
							$queryadd="INSERT INTO sys_loginlogstb (userid,logintime,logid,isol,dateonly) VALUES ($userid,now(),$logid,0,now())";
							$add_id = mysqli_query($conn, $queryadd);
							
							$queryupdate_auditnssw = "UPDATE sys_usertb SET sessiontokencode = '".$sessiontokencode."' WHERE id = '".$userid."'";							
							$resultupdate_nssw = mysqli_query($conn,$queryupdate_auditnssw);
												
							$loc="Record found";
							$isok = 1;
							$unitidtry = $userid;
							echo $loc . "|" . $isok. "|" . $unitidtry. "|";
							
							die();
					}
				else
					{
						   //echo "Wrong username";
							$loc="No Record_found. Access Denied.";
							$isok = 2;
							echo $loc . "|" . $isok;   
					}
														
			} // If the user has not previously logged in, show the login form
			else
			{
			   //echo "Wrong username";
			   //header('Location: login.php');
				$loc="No Record found. Access_Denied.";
				$isok = 0;
				echo $loc . "|" . $isok;   					   
			}	
		}
		else
		{
			 //echo "Wrong username";
		 //header('Location: login.php');
			$loc = "Invalid username or password";
			$isok = 3; // Using 3 as a specific code for invalid credentials
			echo $loc . "|" . $isok;   
			error_log("Failed login attempt for user: " . $username);
			die();
		}	
					
} 
else // The user has returned. Offer a welcoming note.
{
		//check cookies -walang cookies
		if (! isset($_COOKIE['cookusername']) && !isset ($_COOKIE['cookuserpassword'])) 
		{	
			//set variables
			 $logid = $_SESSION['logid'];
			 $userid = $_SESSION['userid'];
			 $usergroupid = $_SESSION['usergroupid'];
			 $username = $_SESSION['username'];
			 $userstatid=$_SESSION['userstatid'];	
			 $companyid=$_SESSION['companyid'];	
			 $userfirstname=$_SESSION['userfirstname'];	
			 // $useremail=$_SESSION['useremail'];
			 $userposition=$_SESSION['userposition'];	
			 $userlevelid=$_SESSION['userlevelid'];
			 // $parentuserid=$_SESSION['parentuserid'];	
			 // $usergroupmasterid=$_SESSION['usergroupmasterid'];	
			 $sessiontokencode=$_SESSION['sessiontokencode'];			 
		}
		else
		{	
			//set session from cookies			
			$_SESSION['userid']= $_COOKIE['cooklogid'];
			$_SESSION['usergroupid']= $_COOKIE['cooklogid'];
			$_SESSION['username']= $_COOKIE['cookusername'];
			$_SESSION['password']= $_COOKIE['cookuserpassword'];			
			$_SESSION['userstatid']= $_COOKIE['cookuserstatid'];	
			$_SESSION['companyid']= $_COOKIE['cookcompanyid'];	
			$_SESSION['userfirstname']= $_COOKIE['cookuserfirstname'];	
			// $_SESSION['useremail']= $_COOKIE['cookuseremail'];
			$_SESSION['userposition']= $_COOKIE['cookuserposition'];	
			$_SESSION['userlevelid']= $_COOKIE['userlevelid'];
			// $_SESSION['parentuserid']= $_COOKIE['parentuserid'];	
			// $_SESSION['usergroupmasterid']= $_COOKIE['usergroupmasterid'];	
			$_SESSION['sessiontokencode']= $_COOKIE['sessiontokencode']; 
			 
			 //set variables
			$userid = $_SESSION['userid'];
			$usergroupid = $_SESSION['usergroupid'];
			$username = $_SESSION['username'];
			$userstatid=$_SESSION['userstatid'];	
			$companyid=$_SESSION['companyid'];	
			$userfirstname=$_SESSION['userfirstname'];	
			// $useremail=$_SESSION['useremail'];
			$userposition=$_SESSION['userposition'];
			$userlevelid=$_SESSION['userlevelid'];
			// $parentuserid=$_SESSION['parentuserid'];	
			// $usergroupmasterid=$_SESSION['usergroupmasterid'];	
			$sessiontokencode=$_SESSION['sessiontokencode'];	
		
		}
	
}

echo "test:".$sessiontokencode;
?>