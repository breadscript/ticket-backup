<?php
header('Content-Type: application/json');

$logoPath = 'company_logo/uhdfi.jpg';

if (file_exists($logoPath)) {
    $imageData = base64_encode(file_get_contents($logoPath));
    $mimeType = mime_content_type($logoPath);
    echo json_encode([
        'success' => true,
        'data' => 'data:' . $mimeType . ';base64,' . $imageData
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Logo file not found'
    ]);
}
?> 