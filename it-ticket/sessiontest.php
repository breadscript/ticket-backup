<?php

require('conn/db.php');
include_once("classes/rndPass.class.php");
session_start();

// Has a cookie been initiated previously?
if (! isset($_COOKIE['cookusername']) && !isset ($_COOKIE['cookuserpassword'])) 
{
	   $username = $_GET['username'];
	   $pswd = $_GET['pswd'];
	   $remember = $_GET['remember'] = null;

	   //echo "TT:".$password = password_hash($username, PASSWORD_DEFAULT);



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
	  	  
	   //convert to upper
	   	$username = strtoupper($username);
		//$pswd = strtoupper($pswd);
		$remember= strtoupper($remember);
	   	
	   	/*
		echo "Username: ".$username."<br>";
	    echo "Password: ".$pswd."<br>";	  
		echo "Remember Me: ".$remember."<br>";	
		*/
		// Look for the user in the users table.
		// $query = "SELECT * FROM pr_sys_usertb WHERE username='$username' AND userpassword='$pswd' AND userstatid=1";
		$query = "SELECT * FROM sys_user WHERE username='$username' AND status='1'";
		$result = pg_query($conn, $query);
          $usergroupid = 8;					
		  
			 //DISBALED USERGROUP
			 if ($usergroupid != 4)
			     {				 
					// If no previous session, has the user submitted the form?
					if (isset($_GET['username']))
					{
							// If the user was found, assign some session variables.
							if (pg_num_rows($result) == 1)
								{
									$_password = pg_fetch_result($result,0,'password');

									$passwordMatch = password_verify($pswd, $_password);
									
									// echo "pswd: ".$pswd."<br>";
									// echo "_password: ".$_password."<br>";	


									if($passwordMatch)
									{
										//create sessions					    
										$user_id = pg_fetch_result($result,0,'user_id');
																			
										$_SESSION['user_id']=$user_id;									
										$_SESSION['username'] = pg_fetch_result($result,0,'username');
										$_SESSION['status'] = pg_fetch_result($result,0,'status');
										$_SESSION['firstname'] = pg_fetch_result($result,0,'firstname');
										$_SESSION['lastname'] = pg_fetch_result($result,0,'lastname');
										$_SESSION['position'] = pg_fetch_result($result,0,'position');


										// Get header Information
										$queryZ = "SELECT f.usergroup as parent_role,c.role_id, a.status, a.position, c.company_id, 
															c.department_id , a.user_id, a.username, a.password, a.firstname, a.middlename, a.lastname, a.email, a.status
															,b.statname, d.name, e.division
														FROM sys_user a
														LEFT JOIN sys_stattb b ON a.status = b.id
														LEFT JOIN sys_user_roles c ON a.user_id =  c.user_id
														LEFT JOIN sys_company d ON c.company_id = d.company_code
														LEFT JOIN pr_sys_employee_division e ON e.division_code = c.department_id
														LEFT JOIN sys_rolemaster f ON f.id = c.role_id
														WHERE a.user_id = '$user_id'";
										$resultZ = pg_query($conn, $queryZ);
										$parent_role = pg_fetch_result($resultZ,0,'parent_role'); 
										$role_id = pg_fetch_result($resultZ,0,'role_id'); 
										$company_id = pg_fetch_result($resultZ,0,'company_id'); 
										$department_id = pg_fetch_result($resultZ,0,'department_id'); 

										$_SESSION['sessionparentrole']=$parent_role;
										$_SESSION['sessionroleid']=$role_id;
										$_SESSION['sessiondepartmentid']=$department_id;
										$_SESSION['sessioncompanyid']=$company_id;
										// Look for the lastuser in the loggedusers table.
										$query2 = "SELECT * FROM sys_loginlogstb ORDER BY logintime DESC";
										$result2 = pg_query($conn, $query2);
										$logid_old = pg_fetch_result($result2,0,'logid');
										$_SESSION['logid']=$logid_old+1;
										
										//create session token code
										$tokencodex=new rndPass(15);
										$_SESSION['sessiontokencode'] = $tokencodex->PassGen();
										
										//store sessions to variables
										$logid = $_SESSION['logid'];
										
										$user_id = $_SESSION['user_id'];
										$username = $_SESSION['username'];
										$status=$_SESSION['status'];	
										$firstname=$_SESSION['firstname'];	
										$lastname=$_SESSION['lastname'];
										$position=$_SESSION['position'];
										$sessiontokencode=$_SESSION['sessiontokencode'];
					
											//set cookie remember? 					  
											if(isset($_GET['remember']))
											{										
												@setcookie("cooklogid", $logid, time()+60*60*24*100, "/");
												@setcookie("cookuserid", $userid, time()+60*60*24*100, "/");
												@setcookie("cookusername", $username, time()+60*60*24*100, "/");
												@setcookie("cookuserpassword", $password, time()+60*60*24*100, "/");
												@setcookie("cookuserstatid", $status, time()+60*60*24*100, "/");
												//  @setcookie("cookcompanyid", $companyid, time()+60*60*24*100, "/");
												@setcookie("cookuserfirstname", $firstname, time()+60*60*24*100, "/");
												//  @setcookie("cookuseremail", $useremail, time()+60*60*24*100, "/");
												@setcookie("cookuserposition", $position, time()+60*60*24*100, "/");	
												//  @setcookie("userlevelid", $userlevelid, time()+60*60*24*100, "/");
												//  @setcookie("parentuserid", $parentuserid, time()+60*60*24*100, "/");		
												//  @setcookie("usergroupmasterid", $usergroupmasterid, time()+60*60*24*100, "/");		
												@setcookie("sessiontokencode", $sessiontokencode, time()+60*60*24*100, "/");	

												@setcookie("cookparentrole", $parent_role, time()+60*60*24*100, "/");	
												@setcookie("cookroleid", $role_id, time()+60*60*24*100, "/");	
												@setcookie("cookdepartmentid", $department_id, time()+60*60*24*100, "/");	
												@setcookie("cookcompanyid", $company_id, time()+60*60*24*100, "/");	
												
											}	
											
											//insert data for log users
											$queryadd="INSERT INTO sys_loginlogstb(userid,logintime,logid,isol,dateonly) VALUES ($user_id,now(),$logid,0,now())";
											$add_id = pg_query($conn, $queryadd);
											
											$queryupdate_auditnssw = "UPDATE sys_user SET sessiontokencode = '".$sessiontokencode."' WHERE user_id = '".$user_id."'";							
											$resultupdate_nssw = pg_query($conn,$queryupdate_auditnssw);
																					
											$loc="Record found";
											$isok = 1;
											$unitidtry = $user_id;
											// echo $loc . "|" . $isok. "|" . $unitidtry. "|" . $usergroupmasterid;
											echo $loc . "|" . $isok. "|" . $unitidtry;
											
											die();
										}else{
											   //echo "Wrong username";
										$loc="No Record_found. Access Denied1.";
										$isok = 2;
										echo $loc . "|" . $isok;
										}
								}
							else
								{
									   //echo "Wrong username";
										$loc="No Record_found. Access Denied2.";
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
					$loc="No Record found. Access Denied.";
					$isok = 0;
					echo $loc . "|" . $isok;   
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
			 $useremail=$_SESSION['useremail'];
			 $userposition=$_SESSION['userposition'];	
			 $userlevelid=$_SESSION['userlevelid'];
			 $parentuserid=$_SESSION['parentuserid'];	
			 $usergroupmasterid=$_SESSION['usergroupmasterid'];	
			 $sessiontokencode=$_SESSION['sessiontokencode'];			
			 
			 $sessionparentrole = $_SESSION['sessionparentrole'];
			 $sessionroleid = $_SESSION['sessionroleid'];
			 $sessiondepartmentid = $_SESSION['sessiondepartmentid'];
			 $sessioncompanyid = $_SESSION['sessioncompanyid'];

			 
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
			$_SESSION['useremail']= $_COOKIE['cookuseremail'];
			$_SESSION['userposition']= $_COOKIE['cookuserposition'];	
			$_SESSION['userlevelid']= $_COOKIE['userlevelid'];
			$_SESSION['parentuserid']= $_COOKIE['parentuserid'];	
			$_SESSION['usergroupmasterid']= $_COOKIE['usergroupmasterid'];	
			$_SESSION['sessiontokencode']= $_COOKIE['sessiontokencode']; 

			$_SESSION['sessionparentrole']= $_COOKIE['cookparentrole'];
			$_SESSION['sessionroleid']= $_COOKIE['cookroleid'];
			$_SESSION['sessiondepartmentid']= $_COOKIE['cookdepartmentid'];
			$_SESSION['sessioncompanyid']= $_COOKIE['cookcompanyid'];


			 //set variables
			$userid = $_SESSION['userid'];
			$usergroupid = $_SESSION['usergroupid'];
			$username = $_SESSION['username'];
			$userstatid=$_SESSION['userstatid'];	
			$companyid=$_SESSION['companyid'];	
			$userfirstname=$_SESSION['userfirstname'];	
			$useremail=$_SESSION['useremail'];
			$userposition=$_SESSION['userposition'];
			$userlevelid=$_SESSION['userlevelid'];
			$parentuserid=$_SESSION['parentuserid'];	
			$usergroupmasterid=$_SESSION['usergroupmasterid'];	
			$sessiontokencode=$_SESSION['sessiontokencode'];	

		
			$sessionparentrole = $_SESSION['sessionparentrole'];
			$sessionroleid = $_SESSION['sessionroleid'];
			$sessiondepartmentid = $_SESSION['sessiondepartmentid'];
			$sessioncompanyid = $_SESSION['sessioncompanyid'];
		}
	
}
?>