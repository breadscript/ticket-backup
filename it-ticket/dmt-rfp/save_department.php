<?php
// Save or update Department master

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
$rateLimitFile = sys_get_temp_dir() . '/department_rate_limit_' . md5($clientIP);
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

$company_code = h_clean($_POST['company_code'] ?? null);
$department_code = h_clean($_POST['department_code'] ?? null);
$department_id = h_clean($_POST['department_id'] ?? null);
$department_name = h_clean($_POST['department_name'] ?? null);

if (!$company_code || !$department_code || !$department_id || !$department_name) {
    http_response_code(400);
    echo 'Company Code, Department Code, Department ID, and Department Name are required';
    exit;
}

try {
    if ($editId > 0) {
        $sql = "UPDATE department
                    SET company_code = ?, department_code = ?, department_id = ?, department_name = ?
                    WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) { throw new Exception('Failed to prepare update'); }
        mysqli_stmt_bind_param($stmt, 'ssssi', $company_code, $department_code, $department_id, $department_name, $editId);
        if (!mysqli_stmt_execute($stmt)) { throw new Exception('Update failed'); }
		$newId = $editId;
		mysqli_stmt_close($stmt);
	} else {
        $sql = "INSERT INTO department (company_code, department_code, department_id, department_name)
                    VALUES (?,?,?,?)";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) { throw new Exception('Failed to prepare insert'); }
        mysqli_stmt_bind_param($stmt, 'ssss', $company_code, $department_code, $department_id, $department_name);
        if (!mysqli_stmt_execute($stmt)) { throw new Exception('Insert failed'); }
    $newId = mysqli_insert_id($conn);
		mysqli_stmt_close($stmt);
    }
} catch (Throwable $e) {
	// Handle uniqueness errors gracefully
	$err = mysqli_error($conn);
	$msg = $e->getMessage();
    if (stripos($err, 'uniq_department_code') !== false) { $msg = 'Department Code already exists'; }
    if (stripos($err, 'uniq_department_id') !== false) { $msg = 'Department ID already exists'; }
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

header('Location: department_view.php?id=' . urlencode((string)$newId));
exit;