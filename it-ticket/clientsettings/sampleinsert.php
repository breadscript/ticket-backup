<html>
<body>
<h1>Passing a parameter to a stored procedure</h1>
<pre><?php
include_once('../conn/db.php');
$conn = connectionDB();

// $qr = $conn->query("insertClientInfo('CLIENT 4','STREET 4','CITY 4','STATE 4','COUNTRY 4', 'EMAIL4@GMAIL.COM', 'NUMBER 4', 'PERSON 4', 'POSITION 4')");
// $qr->query("SELECT @OutPut"); 
// echo $qr;


?></pre><br>
This demonstration shows a stored procedure to which a parameter has
been passed which is passed in turn into the select query.
</body>
</html>

<?php 
$clientName = "CLIENT 11";
$streetName = "STREET 11";
$cityName = "CITY 11";
$stateName = "STATE 11";
$countryName = "COUNTRY 11";
$email = "EMAIL11@GMAIL.COM";
$contactNumber = "NUMBER 11";
$personName = "PERSON 11";
$position = "POSITION 11";

$sql=mysqli_query($conn,"CALL insertClientInfo('$clientName','$streetName','$cityName','$stateName','$countryName','$email','$contactNumber','$personName','$position')");

// $sql=mysqli_query($conn,"CALL viewClient()");


?>