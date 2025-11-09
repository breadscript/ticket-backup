<?php
require_once '../conn/db.php';
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

if (!isset($_COOKIE['cookusername']) && !isset($_COOKIE['cookuserpassword'])) {
	if (!isset($_SESSION['username'])) {
		header('Location: login.php');
		exit;
	}
} else {
	$_SESSION['userid'] = $_COOKIE['cooklogid'] ?? null;
	$_SESSION['usergroupid'] = $_COOKIE['cooklogid'] ?? null;
	$_SESSION['username'] = $_COOKIE['cookusername'] ?? null;
	$_SESSION['password'] = $_COOKIE['cookuserpassword'] ?? null;
	$_SESSION['userstatid'] = $_COOKIE['cookuserstatid'] ?? null;
	$_SESSION['userfirstname'] = $_COOKIE['cookuserfirstname'] ?? null;
	$_SESSION['userlevelid'] = $_COOKIE['userlevelid'] ?? null;
	$_SESSION['sessiontokencode'] = $_COOKIE['sessiontokencode'] ?? null;
}
?>


