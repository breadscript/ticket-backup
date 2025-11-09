<?php
include('../check_session.php');
$conn = connectionDB();

$usermasteridx=$_REQUEST['usermasteridx'];
//check if no value
if($usermasteridx == null)
{
	$usermasteridx=0;
}
// echo "usermasteridx:". $usermasteridx."<br>";

$querycounter= "SELECT count(a.id) as mycount FROM sys_authoritymoduletb a WHERE a.userid=$usermasteridx";
$resultcounter = mysqli_query($conn, $querycounter);	
while($rowscounter = mysqli_fetch_assoc($resultcounter))
{	
  $mycount= $rowscounter['mycount'];
}
// echo "mycount:". $mycount."<br>";;

$parameterstring=null;
if($mycount >= 1)
{
	//create parameter
	$queryparam= "SELECT DISTINCT a.moduleid as moduleidz FROM sys_authoritymoduletb a WHERE a.userid=$usermasteridx";
	$resultparam = mysqli_query($conn, $queryparam);	
	while($rowsparam = mysqli_fetch_assoc($resultparam))
	{	
	  $moduleidz= $rowsparam['moduleidz'];
	  
	  $parameterstring.="'".$moduleidz."'".",";
	}
	//remove last comma
	
	$parameterstring=substr($parameterstring, 0, -1);
	$parameterstring="(".$parameterstring.")";
	// echo "parameterstring:". $parameterstring."<br>";;
	
	//query available module
	if($usergroupid == 1)
	{
		//admin groups only
			if($userlevelid == 1)
			{
					//superuser only			
					$querycompany = "select a.id as moduleid,a.modulename from sys_moduletb a
									 WHERE a.modulepath <> '#' AND a.id NOT IN $parameterstring";
					  $resultcompany = mysqli_query($conn, $querycompany);			  		
			}elseif ($userlevelid == 2){
					//admin/manager only				    
					if($parameterstring <> null)	
					{
						 $querycompany = "SELECT a.id as moduleid,a.modulename from sys_moduletb a WHERE a.modulepath <> '#' AND a.id NOT IN $parameterstring";
						 $resultcompany = mysqli_query($conn, $querycompany);						
					}else {
						 $querycompany = "SELECT a.id as moduleid,a.modulename from sys_moduletb a WHERE a.modulepath <> '#'";
						 $resultcompany = mysqli_query($conn, $querycompany);
						 //echo $querycompany;
					}	
											
			}else{
					//standard user - dummy sql			
					 $querycompany = "SELECT a.id as moduleid,a.modulename from sys_moduletb a
									 WHERE a.modulepath <> '#' AND a.id NOT IN $parameterstring";
					  $resultcompany = mysqli_query($conn, $querycompany);						
			}				  
	  }else{
					//standard user - dummy sql			
					 $querycompany = "SELECT a.id as moduleid,a.modulename from sys_moduletb a
									 WHERE a.modulepath <> '#' AND a.id NOT IN $parameterstring";
					  $resultcompany = mysqli_query($conn, $querycompany);	
	}	
}else{
	//if no existing module assigned
	if($usergroupid == 1){
		//admin groups only
			if($userlevelid == 1){
					//superuser only			
					$querycompany = "select a.id as moduleid,a.modulename from sys_moduletb a
									 WHERE a.modulepath <> '#'";
					  $resultcompany = mysqli_query($conn, $querycompany);			  		
			}elseif ($userlevelid == 2){
					//admin/manager only	
					//9999 usergroupmasteruid for common task								
					if($parameterstring <> null){
						 $querycompany = "SELECT a.id as moduleid,a.modulename from sys_moduletb a WHERE a.modulepath <> '#' AND a.id NOT IN $parameterstring";
						 $resultcompany = mysqli_query($conn, $querycompany);						
					}else {
						 $querycompany = "SELECT a.id as moduleid,a.modulename from sys_moduletb a WHERE a.modulepath <> '#'";
						 $resultcompany = mysqli_query($conn, $querycompany);
						 //echo $querycompany;
					}	
			}else{
					//standard user - dummy sql			
					 $querycompany = "SELECT a.id as moduleid,a.modulename from sys_moduletb a
									 WHERE a.modulepath <> '#'";
					  $resultcompany = mysqli_query($conn, $querycompany);						
			}
		}else{
					//standard user - dummy sql			
					 $querycompany = "SELECT a.id as moduleid,a.modulename from sys_moduletb a
									 WHERE a.modulepath <> '#'";
					  $resultcompany = mysqli_query($conn, $querycompany);	
	}
}
//echo $querycompany;
?>
<label class="block clearfix">Module
<select class="form-control" name="moduleidx" id="moduleidx">
<option value="">Select Module</option>
<?php 
while($rowcompany = mysqli_fetch_assoc($resultcompany))
{	
  $moduleidx= $rowcompany['moduleid'];
  $modulename = $rowcompany['modulename'];
?>
<option value="<?php echo $moduleidx; ?>"><?php echo $modulename; ?></option>
<?php 
} 
?>
</select>
</label>