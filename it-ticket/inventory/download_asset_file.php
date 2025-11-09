<?php
include('../check_session.php');

// Expect base64-encoded relative path in f, optional inline=1
$fParam = isset($_GET['f']) ? $_GET['f'] : '';
if ($fParam === '') {
    http_response_code(400);
    echo 'Missing file parameter';
    exit;
}

$relative = base64_decode($fParam, true);
if ($relative === false) {
    http_response_code(400);
    echo 'Invalid file parameter';
    exit;
}

// Normalize separators
$relative = str_replace('\\', '/', $relative);

// Prevent path traversal
if (strpos($relative, '..') !== false) {
    http_response_code(400);
    echo 'Invalid path';
    exit;
}

$projectRoot = realpath(__DIR__ . '/..');
if ($projectRoot === false) {
    http_response_code(500);
    echo 'Server path error';
    exit;
}

$absolutePath = $projectRoot . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $relative), DIRECTORY_SEPARATOR);

if (!file_exists($absolutePath) || !is_file($absolutePath)) {
    http_response_code(404);
    echo 'File not found';
    exit;
}

$filename = basename($absolutePath);
$mime = function_exists('mime_content_type') ? mime_content_type($absolutePath) : 'application/octet-stream';
$filesize = filesize($absolutePath);

$inline = isset($_GET['inline']) && $_GET['inline'] == '1';

// Send headers
header('Content-Type: ' . $mime);
header('Content-Length: ' . $filesize);
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . rawurlencode($filename) . '"; filename*=UTF-8\'' . rawurlencode($filename));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600');

// Stream file
$fp = fopen($absolutePath, 'rb');
if ($fp) {
    while (!feof($fp)) {
        echo fread($fp, 8192);
        flush();
    }
    fclose($fp);
}
exit;
?>


