<?php
// Helper script to check company images
// This script shows which company images are available and which ones are missing

date_default_timezone_set('Asia/Manila');
include __DIR__ . '/blocks/inc.resource.php';

echo "<h2>Company Image Status</h2>\n";
echo "<p>This script checks which company images are available in the dmt-rfp/images/ directory.</p>\n";

// Get all companies from database
$companies = [];
if (isset($mysqlconn) && $mysqlconn) {
  $sql = "SELECT company_code, company_name FROM company ORDER BY company_code";
  if ($result = mysqli_query($mysqlconn, $sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
      $companies[] = $row;
    }
  }
}

if (empty($companies)) {
  echo "<p>No companies found in database.</p>\n";
  exit;
}

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr style='background-color: #f0f0f0;'>\n";
echo "<th>Company Code</th>\n";
echo "<th>Company Name</th>\n";
echo "<th>Image File</th>\n";
echo "<th>Status</th>\n";
echo "<th>Action</th>\n";
echo "</tr>\n";

foreach ($companies as $company) {
  $companyCode = $company['company_code'];
  $companyName = $company['company_name'];
  
  // Check for image with different case variations
  $imageFileLower = strtolower($companyCode) . '.jpg';
  $imageFileUpper = strtoupper($companyCode) . '.jpg';
  $imageFileMixed = $companyCode . '.jpg';
  
  $imagePathLower = __DIR__ . '/images/' . $imageFileLower;
  $imagePathUpper = __DIR__ . '/images/' . $imageFileUpper;
  $imagePathMixed = __DIR__ . '/images/' . $imageFileMixed;
  
  $imageExists = file_exists($imagePathLower) || file_exists($imagePathUpper) || file_exists($imagePathMixed);
  $foundImage = '';
  
  if (file_exists($imagePathLower)) {
    $foundImage = $imageFileLower;
  } elseif (file_exists($imagePathUpper)) {
    $foundImage = $imageFileUpper;
  } elseif (file_exists($imagePathMixed)) {
    $foundImage = $imageFileMixed;
  }
  
  echo "<tr>\n";
  echo "<td>" . htmlspecialchars($companyCode) . "</td>\n";
  echo "<td>" . htmlspecialchars($companyName) . "</td>\n";
  echo "<td>" . htmlspecialchars($foundImage ?: $imageFileLower) . "</td>\n";
  
  if ($imageExists) {
    echo "<td style='color: green;'>✓ Available</td>\n";
    echo "<td>-</td>\n";
  } else {
    echo "<td style='color: red;'>✗ Missing</td>\n";
    echo "<td>Add image file: <code>dmt-rfp/images/" . htmlspecialchars($imageFileLower) . "</code> (or any case variation)</td>\n";
  }
  
  echo "</tr>\n";
}

echo "</table>\n";

echo "<h3>Instructions:</h3>\n";
echo "<ol>\n";
echo "<li>For each missing company, add a JPG image file to the <code>dmt-rfp/images/</code> directory</li>\n";
echo "<li>Name the file using the company code in lowercase (e.g., <code>tlci.jpg</code> for TLCI)</li>\n";
echo "<li>Recommended image size: 200x200 pixels or larger (will be resized to 80x80 in PDFs)</li>\n";
echo "<li>Use square images for best results (they will be displayed as circles)</li>\n";
echo "</ol>\n";

echo "<h3>Current Images in Directory:</h3>\n";
$imageDir = __DIR__ . '/images/';
if (is_dir($imageDir)) {
  $files = scandir($imageDir);
  $imageFiles = array_filter($files, function($file) {
    return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']);
  });
  
  if (empty($imageFiles)) {
    echo "<p>No image files found in the images directory.</p>\n";
  } else {
    echo "<ul>\n";
    foreach ($imageFiles as $file) {
      echo "<li>" . htmlspecialchars($file) . "</li>\n";
    }
    echo "</ul>\n";
  }
} else {
  echo "<p>Images directory not found.</p>\n";
}
?>
