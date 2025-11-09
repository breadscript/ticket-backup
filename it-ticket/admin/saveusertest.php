<?php
require('../classes/classMZUY.php');
$dxc = new DXCheck;
$dxc->check_is_ajax(__FILE__); // prevent direct access
$moduleid=20;
require('conn/db.php');
include('../components/check_session.php'); 
$nowdateunix = date("Y-m-d",time());	

function alphanumericAndSpace( $string ) {
    return preg_replace( "/[^a-z0-9 ]/i", "", $string );
}

$myuserid=trim($_POST['myuserid']);
$myusername=strtoupper(trim($_POST['username']));
$myfirstname=alphanumericAndSpace(strtoupper(trim($_POST['firstname'])));
$mylastname=alphanumericAndSpace(strtoupper(trim($_POST['lastname'])));
$myemail=trim($_POST['email']);
$myposition=strtoupper(trim($_POST['uposition']));
$usergroupid=strtoupper(trim($_POST['usergroupid']));
$department_id=trim($_POST['department_id']);
$company_id=trim($_POST['company_id']);
$mypassword=trim($_POST['password']);
$myuserstatid=1;

if (($myuserid == '')||($myuserid == null)){$myuserid='0';}
if (($mypassword == '')||($mypassword == null)){$mypassword='NULL';}
if (($myposition == '')||($myposition == null)){$myposition='NULL';}
if (($myusername == '')||($myusername == null)){$myusername='NULL';}
if (($myfirstname == '')||($myfirstname == null)){$myfirstname='NULL';}
if (($mylastname == '')||($mylastname == null)){$mylastname='NULL';}
if (($myemail == '')||($myemail == null)){$myemail='0';}
if (($usergroupid == '')||($usergroupid == null)){$usergroupid='0';}
if (($company_id == '')||($company_id == null)){$company_id='0';}
if (($department_id == '')||($department_id == null)){$department_id='0';}

$password = password_hash($mypassword, PASSWORD_DEFAULT);
// echo "";
/*Warning: Undefined array key "department_id" in C:\xampp\htdocs\cbzonex\users\saveuser.php on line 22

echo "myuserid: ".$myuserid."<br>";
echo "myusername: ".$myusername."<br>";
echo "myfirstname: ".$myfirstname."<br>";
echo "mylastname: ".$mylastname."<br>";
echo "myemail: ".$myemail."<br>";
echo "myposition: ".$myposition."<br>";
echo "company_id: ".$company_id."<br>";
echo "department_id: ".$department_id."<br>";

*/
//die();
//query password


$note = 'Created user '.$myusername. ' successfully.';


							$data="";
							$data .= $myusername.'|'.$myfirstname.'|'.$mylastname
							.'|'.$myemail.'|'.$myposition
							.'|'.$password
							.'|'.$myuserstatid
							.'|'.$user_id
							.'|'.$note
							.'|'.$company_id
							.'|'.$department_id
							.'|'.$usergroupid.'|';
							 $data;
									
									$slev2 = "select save_user('$data') as x";
									$result2 = pg_query($conn, $slev2);	
									$notice = alphanumericAndSpace(trim(pg_last_notice($conn))); 		
									$notice = preg_replace('/\s+/', '', $notice);			
								
														 
															if($notice=="NOTICEEXISTINGUSER"){
															 	echo "<div class='alert alert-danger'>";
																	echo "An error occured! Username is existing. Transaction not saved. <button type='button' class='close' data-dismiss='alert'>&times;</button>";  			
																echo "</div>";									
															}
															else {
																echo "<div class='alert alert-info fade in'>";												
																	echo "User Account successfuly saved!";  													
																	echo "<button type='button' class='close' data-dismiss='alert'>&times;</button>";  		
																echo "</div>";										
															}
																	 																		
														?>
														<script type="text/javascript">
															$("#addbtn").prop('disabled', true);
															refreshdiv();																																		
														</script>									
														<?php
																
												  
					  		 
?>
