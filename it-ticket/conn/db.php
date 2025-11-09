<?php
/*==================================
 		connect to database
 ===================================*/
function connectionDB() {
	$servername = "localhost";
	$username = "root";
	$password = "";
	$db = "techsource";
	$mysqlconn = mysqli_connect($servername, $username, $password, $db);	
	
	if (!$mysqlconn) {
		return false;
	}
	// echo $db;
	return $mysqlconn;
}

	$mysqlconn = connectionDB();

?>