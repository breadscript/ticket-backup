<?php
include('../check_session.php');
$conn = connectionDB();

header('Content-Type: application/json');

function respond($success, $message, $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

$assetId = isset($_POST['asset_id']) ? trim($_POST['asset_id']) : '';
$createdById = isset($_POST['createdbyid']) ? trim($_POST['createdbyid']) : ($_SESSION['userid'] ?? '');

if ($assetId === '') {
    respond(false, 'Missing asset_id');
}

if (!isset($_FILES['files'])) {
    respond(false, 'No files received');
}

// Prepare upload directory per asset
$baseUploadDir = realpath(__DIR__ . '/../uploads');
if ($baseUploadDir === false) {
    // Try to create the base directory
    $baseUploadDir = __DIR__ . '/../uploads';
    if (!is_dir($baseUploadDir) && !mkdir($baseUploadDir, 0775, true)) {
        respond(false, 'Unable to create base upload directory');
    }
} else {
    $baseUploadDir = $baseUploadDir; // absolute
}

$assetDir = $baseUploadDir . '/assets/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $assetId);
if (!is_dir($assetDir) && !mkdir($assetDir, 0775, true)) {
    respond(false, 'Unable to create asset upload directory');
}

$uploadedCount = 0;
$errors = [];

for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
    $name = $_FILES['files']['name'][$i];
    $tmpName = $_FILES['files']['tmp_name'][$i];
    $error = $_FILES['files']['error'][$i];

    if ($error !== UPLOAD_ERR_OK) {
        $errors[] = "$name: upload error code $error";
        continue;
    }

    // Sanitize filename
    $safeName = preg_replace('/[^a-zA-Z0-9_\.\-]/', '_', $name);

    // Ensure unique filename
    $targetPath = $assetDir . '/' . $safeName;
    $pathInfo = pathinfo($targetPath);
    $counter = 1;
    while (file_exists($targetPath)) {
        $targetPath = $assetDir . '/' . $pathInfo['filename'] . '_' . $counter . (isset($pathInfo['extension']) ? ('.' . $pathInfo['extension']) : '');
        $counter++;
    }

    if (!move_uploaded_file($tmpName, $targetPath)) {
        $errors[] = "$name: failed to move uploaded file";
        continue;
    }

    // Build relative path to store in DB
    $relativePath = str_replace(realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR, '', realpath($targetPath));
    if ($relativePath === false) {
        // fallback to path relative from uploads dir
        $relativePath = 'uploads/assets/' . basename($assetDir) . '/' . basename($targetPath);
    }

    // Insert record into inv_asset_files
    $stmt = mysqli_prepare($conn, "INSERT INTO inv_asset_files (asset_id, file_data, datetimecreated, createdbyid) VALUES (?, ?, NOW(), ?)");
    if ($stmt === false) {
        $errors[] = "$name: DB prepare failed";
        continue;
    }
    mysqli_stmt_bind_param($stmt, 'sss', $assetId, $relativePath, $createdById);
    if (!mysqli_stmt_execute($stmt)) {
        $errors[] = "$name: DB insert failed";
        // best effort: keep file even if DB insert fails
    } else {
        $uploadedCount++;
    }
    mysqli_stmt_close($stmt);
}

if ($uploadedCount > 0 && empty($errors)) {
    respond(true, 'Uploaded ' . $uploadedCount . ' file(s).');
}

if ($uploadedCount > 0 && !empty($errors)) {
    respond(true, 'Uploaded ' . $uploadedCount . ' file(s) with some errors.', ['errors' => $errors]);
}

respond(false, 'No files were uploaded.', ['errors' => $errors]);

?>

