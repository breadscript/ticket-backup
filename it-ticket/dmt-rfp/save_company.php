<?php
// Save or update Company master

session_start();
require_once __DIR__ . '/../conn/db.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo 'CSRF token validation failed';
    exit;
}

$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateLimitFile = sys_get_temp_dir() . '/company_rate_limit_' . md5($clientIP);
$currentTime = time();
if (file_exists($rateLimitFile)) {
    $lastSubmission = (int)file_get_contents($rateLimitFile);
	if (($currentTime - $lastSubmission) < 2) {
        http_response_code(429);
		echo 'Rate limit exceeded. Please wait a moment before submitting again.';
        exit;
    }
}
file_put_contents($rateLimitFile, $currentTime);

if (!isset($_POST['form_timestamp']) || abs($currentTime - (int)$_POST['form_timestamp']) > 3600) {
    http_response_code(400);
    echo 'Form timestamp validation failed';
    exit;
}

$conn = connectionDB();
if (!$conn) {
    http_response_code(500);
    echo 'Database connection failed';
    exit;
}

function h_clean($v) {
	if (!isset($v)) return null;
	$val = trim((string)$v);
	if ($val === '') return null;
	$val = htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
	$val = preg_replace('/[<>"\']/', '', $val);
	return $val;
}

$editId = isset($_POST['__edit_id']) ? (int)$_POST['__edit_id'] : 0;

$company_name = h_clean($_POST['company_name'] ?? null);
$company_code = h_clean($_POST['company_code'] ?? null);
$company_ident = h_clean($_POST['company_ident'] ?? null); // Company id
$company_address = h_clean($_POST['company_address'] ?? null);
$company_tin = h_clean($_POST['company_tin'] ?? null);

if (!$company_name || !$company_code || !$company_ident) {
    http_response_code(400);
	echo 'Company Name, Company Code, and Company ID are required';
    exit;
}

try {
	if ($editId > 0) {
		$sql = "UPDATE company
					SET company_name = ?, company_code = ?, company_id = ?, company_address = ?, company_tin = ?
					WHERE id = ?";
		$stmt = mysqli_prepare($conn, $sql);
		if (!$stmt) { throw new Exception('Failed to prepare update'); }
		mysqli_stmt_bind_param($stmt, 'sssssi', $company_name, $company_code, $company_ident, $company_address, $company_tin, $editId);
		if (!mysqli_stmt_execute($stmt)) { throw new Exception('Update failed'); }
		$newId = $editId;
		mysqli_stmt_close($stmt);
	} else {
		$sql = "INSERT INTO company (company_name, company_code, company_id, company_address, company_tin)
					VALUES (?,?,?,?,?)";
$stmt = mysqli_prepare($conn, $sql);
		if (!$stmt) { throw new Exception('Failed to prepare insert'); }
		mysqli_stmt_bind_param($stmt, 'sssss', $company_name, $company_code, $company_ident, $company_address, $company_tin);
		if (!mysqli_stmt_execute($stmt)) { throw new Exception('Insert failed'); }
    $newId = mysqli_insert_id($conn);
		mysqli_stmt_close($stmt);
    }
} catch (Throwable $e) {
	// Handle uniqueness errors gracefully
	$err = mysqli_error($conn);
	$msg = $e->getMessage();
	if (stripos($err, 'uniq_company_code') !== false) { $msg = 'Company Code already exists'; }
	if (stripos($err, 'uniq_company_id') !== false) { $msg = 'Company ID already exists'; }
	http_response_code(400);
	echo $msg;
    exit;
} finally {
    mysqli_close($conn);
}

if (!empty($_POST['__expect_json'])) {
    header('Content-Type: application/json');
	echo json_encode(['success' => true, 'id' => (int)$newId]);
    exit;
}

header('Location: company_view.php?id=' . urlencode((string)$newId));
exit;