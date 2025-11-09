<?php 
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d h:i:s", time());
include "blocks/inc.resource.php";

// Check if user is logged in
if (!isset($_SESSION['userid']) || $_SESSION['userid'] <= 0) {
    header('Location: login.php');
    exit;
}

$userid = intval($_SESSION['userid']);

// Get user's available modules
$modules_query = "SELECT DISTINCT b.moduleid, b.authoritystatusid, b.statid, b.modulecategoryid
                  FROM sys_usertb a
                  LEFT JOIN sys_authoritymoduletb b ON a.id = b.userid
                  WHERE a.id = $userid AND b.statid = 1
                  ORDER BY b.moduleid";
$modules_result = mysqli_query($mysqlconn, $modules_query);

$available_modules = [];
while ($row = mysqli_fetch_assoc($modules_result)) {
    $available_modules[] = $row;
}

// Determine redirect based on available modules
$redirect_url = 'login.php'; // Default fallback

if (!empty($available_modules)) {
    // Check for specific modules in order of preference
    foreach ($available_modules as $module) {
        switch ($module['moduleid']) {
            case 10: // Disbursement
                $redirect_url = 'disbursement_main.php';
                break 2; // Break out of both loops
            case 11: // Disbursement Approval
                $redirect_url = 'disbursement_approver_main.php';
                break 2; // Break out of both loops
            default:
                // Continue checking other modules
                break;
        }
    }
}

// Redirect to the appropriate module
header('Location: ' . $redirect_url);
exit;
?>
