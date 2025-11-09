<?php
// Returns the current CSRF token, generating a new one if missing
// Use for AJAX to refresh token without reloading the page
ob_start();
session_start();

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$token = (string)$_SESSION['csrf_token'];
setcookie('csrf_token', $token, 0, '/', '', isset($_SERVER['HTTPS']), true);

echo json_encode([
    'success' => true,
    'csrf_token' => $token
]);


