<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
//check cookies -walang cookies
// echo "username:".$_COOKIE['cookusername'];
// echo "cookuserpassword:".$_COOKIE['cookuserpassword'];
if (! isset($_COOKIE['cookusername']) && !isset ($_COOKIE['cookuserpassword'])) 
{					
		// Has a session been initiated previously?
		//nope?
		if (! isset($_SESSION['username'])) 
			{
				   header('Location: login.php');
			}	
		
		else // The user has returned. Offer a welcoming note.
			{
				//set variables
				$userid = $_SESSION['userid'];
				$usergroupid = $_SESSION['usergroupid'];
				$username = $_SESSION['username'];
				$userstatid=$_SESSION['userstatid'];		
				$userfirstname=$_SESSION['userfirstname'];	
				$userlevelid=$_SESSION['userlevelid'];
				$sessiontokencode=$_SESSION['sessiontokencode'];
				
			}
}
else
{
			    //set session from cookies
				$_SESSION['userid']= $_COOKIE['cooklogid'];
				$_SESSION['usergroupid']= $_COOKIE['cooklogid'];
				$_SESSION['username']= $_COOKIE['cookusername'];
				$_SESSION['password']= $_COOKIE['cookuserpassword'];			
				$_SESSION['userstatid']= $_COOKIE['cookuserstatid'];	
				$_SESSION['userfirstname']= $_COOKIE['cookuserfirstname'];	
				$_SESSION['userlevelid']= $_COOKIE['userlevelid'];	
				$_SESSION['sessiontokencode']= $_COOKIE['sessiontokencode'];	
			
				//set variables
				$logid = $_SESSION['logid'];
				$userid = $_SESSION['userid'];
				$usergroupid = $_SESSION['usergroupid'];
				$username = $_SESSION['username'];
				$userstatid=$_SESSION['userstatid'];	
				$userfirstname=$_SESSION['userfirstname'];	
				$userlevelid=$_SESSION['userlevelid'];
				$sessiontokencode=$_SESSION['sessiontokencode'];
}			
?>