<?php
require('../conn/db.php');
$conn = connectionDB();
$moduleid=2;

$nowdateunix = date("Y-m-d",time());	

function alphanumericAndSpace( $string ) {
   return preg_replace( "/[^,;a-zA-Z0-9 _-]|[,; ]$/s", "", $string );
}

$clientName=strtoupper($_GET['clientName2']);
$clientEmail=strtoupper($_GET['clientEmail2']);
$clientContact=strtoupper($_GET['clientContact2']);
$clientPerson=strtoupper($_GET['clientPerson2']);
$clientPosition=strtoupper($_GET['clientPosition2']);
$clientStreet=strtoupper($_GET['clientStreet2']);
$clientCity=strtoupper($_GET['clientCity2']);
$clientState=strtoupper($_GET['clientState2']);
$clientCountry=strtoupper($_GET['clientCountry2']);
$clientId=strtoupper($_GET['clientId2']);
$clientStatus=strtoupper($_GET['clientStatus2']);
$clientUserid=strtoupper($_GET['clientUserid2']);

if (($clientName == '')||($clientName == null)){$clientName="NULL";}
if (($clientEmail == '')||($clientEmail == null)){$clientEmail="NULL";}
if (($clientContact == '')||($clientContact == null)){$clientContact="NULL";}
if (($clientPerson == '')||($clientPerson == null)){$clientPerson="NULL";}
if (($clientPosition == '')||($clientPosition == null)){$clientPosition="NULL";}
if (($clientStreet == '')||($clientStreet == null)){$clientStreet="NULL";}
if (($clientCity == '')||($clientCity == null)){$clientCity="NULL";}
if (($clientState == '')||($clientState == null)){$clientState="NULL";}
if (($clientCountry == '')||($clientCountry == null)){$clientCountry="NULL";}



$myquery = "SELECT sys_clienttb.id,sys_clienttb.clientname,sys_clienttb.clientaddressstreet,
				   sys_clienttb.clientaddresscity,sys_clienttb.clientaddressstate,sys_clienttb.clientaddresscountry,sys_clienttb.clientstatusid,
				   sys_clienttb.clientemail,sys_clienttb.clientcontactnumber,sys_clienttb.clientcontactperson,sys_clienttb.clientcontactpersonposition,sys_clientstatustb.status
			FROM sys_clienttb
			LEFT JOIN sys_clientstatustb
			ON sys_clienttb.clientstatusid=sys_clientstatustb.id
			WHERE sys_clienttb.id = '$clientId'";
$myresult = mysqli_query($conn, $myquery);
while($row = mysqli_fetch_assoc($myresult)){
	$myClientname = $row['clientname'];	
	$myClientStreet = $row['clientaddressstreet'];	
	$myClientCity = $row['clientaddresscity'];	
	$myClientState = $row['clientaddressstate'];	
	$myClientCountry = $row['clientaddresscountry'];	
	$myClientEmail = $row['clientemail'];	
	$myClientContact = $row['clientcontactnumber'];	
	$myClientPerson = $row['clientcontactperson'];	
	$myClientPosition = $row['clientcontactpersonposition'];	
	$myClientStatusId = $row['clientstatusid'];	
	$myClientStatus = $row['status'];	
	$myClientId = $row['id'];	
}

/*-------for audit trails--------*/
$auditRemarks1 = "UPDATED CLIENT INFO"." | ".$myClientname;
$auditRemarks2 = "ATTEMPT TO UPDATE CLIENT INFO"." | ".$myClientname;
// echo "oldClientName:".$myClientname."</br>";
if($myClientname != $clientName){$sql2=mysqli_query($conn,"CALL insertAudit('$moduleid','CHANGE CLIENT NAME | $myClientname','$clientUserid')");}
if($myClientStreet != $clientStreet){$sql2=mysqli_query($conn,"CALL insertAudit('$moduleid','CHANGE CLIENT STREET | $myClientname','$clientUserid')");}
if($myClientCity != $clientCity){$sql2=mysqli_query($conn,"CALL insertAudit('$moduleid','CHANGE CLIENT CITY | $myClientname','$clientUserid')");}
if($myClientState != $clientState){$sql2=mysqli_query($conn,"CALL insertAudit('$moduleid','CHANGE CLIENT STATE | $myClientname','$clientUserid')");}
if($myClientCountry != $clientCountry){$sql2=mysqli_query($conn,"CALL insertAudit('$moduleid','CHANGE CLIENT COUNTRY | $myClientname','$clientUserid')");}
if($myClientEmail != $clientEmail){$sql2=mysqli_query($conn,"CALL insertAudit('$moduleid','CHANGE CLIENT EMAIL | $myClientname','$clientUserid')");}
if($myClientContact != $clientContact){$sql2=mysqli_query($conn,"CALL insertAudit('$moduleid','CHANGE CLIENT CONTACT | $myClientname','$clientUserid')");}
if($myClientPerson != $clientPerson){$sql2=mysqli_query($conn,"CALL insertAudit('$moduleid','CHANGE CLIENT PERSON IN CHARGE | $myClientname','$clientUserid')");}
if($myClientPosition != $clientPosition){$sql2=mysqli_query($conn,"CALL insertAudit('$moduleid','CHANGE PERSON IN CHARGE POSITION | $myClientname','$clientUserid')");}
if($myClientStatusId != $clientStatus){$sql2=mysqli_query($conn,"CALL insertAudit('$moduleid','CHANGE CLIENT STATUS | $myClientname','$clientUserid')");}


 $mysqlins = "UPDATE `sys_clienttb` SET
  		  clientname = '$clientName',
          clientaddressstreet = '$clientStreet',
          clientaddresscity = '$clientCity',
          clientaddressstate = '$clientState',
          clientaddresscountry = '$clientCountry',
          clientemail = '$clientEmail',
          clientcontactnumber = '$clientContact',
          clientcontactperson = '$clientPerson',
          clientcontactpersonposition = '$clientPosition',
          clientstatusid = '$clientStatus',
          updatedbyid = '$clientUserid'
			WHERE id = '$clientId'";
$myresultins = mysqli_query($conn, $mysqlins);

if($mysqlins){
	$queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES ('$moduleid','$auditRemarks1','$clientUserid')";
	$resaud1 = mysqli_query($conn, $queryaud1);
	echo "<div class='alert alert-info fade in'>";
	echo "Data has been updated!";   		
	echo "</div>";
	?>
	<script type="text/javascript">
		setTimeout(function(){location.reload();}, 1000);
	</script>
<?php
}else{
	$queryaud1 = "INSERT INTO `sys_audit` (module,remarks,userid) VALUES ('$moduleid','$auditRemarks2','$clientUserid')";
	$resaud1 = mysqli_query($conn, $queryaud1);
	echo "<div class='alert alert-danger fade in'>";
	echo "Failed to save new information!";   		
	echo "</div>";
	?>
	<script type="text/javascript">
		setTimeout(function(){location.reload();}, 1000);
	</script>
	<?php
	}
?>