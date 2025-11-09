<?php
/**
 * Upload Configuration Checker
 * This script checks PHP configuration for large file uploads
 * Access this file to verify your server can handle 200MB+ uploads
 */

// Security check - only allow access from localhost or specific IPs
$allowed_ips = ['127.0.0.1', '::1', 'localhost'];
$client_ip = $_SERVER['REMOTE_ADDR'] ?? '';

if (!in_array($client_ip, $allowed_ips) && !in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'])) {
    die('Access denied. This script can only be accessed from localhost.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Configuration Checker</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .ok { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .recommendation { background-color: #e2e3e5; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>Upload Configuration Checker</h1>
    <p>This tool checks if your PHP configuration can handle large file uploads (200MB+).</p>

    <?php
    // Function to format bytes
    function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    // Function to check if value is sufficient
    function isSufficient($current, $required, $unit = 'MB') {
        if ($unit === 'MB') {
            $currentMB = $current / (1024 * 1024);
            return $currentMB >= $required;
        }
        return $current >= $required;
    }

    // Get PHP configuration values
    $config = [
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'max_execution_time' => ini_get('max_execution_time'),
        'max_input_time' => ini_get('max_input_time'),
        'memory_limit' => ini_get('memory_limit'),
        'max_file_uploads' => ini_get('max_file_uploads'),
        'file_uploads' => ini_get('file_uploads'),
        'max_input_vars' => ini_get('max_input_vars'),
    ];

    // Convert to bytes for comparison
    $upload_max_bytes = return_bytes($config['upload_max_filesize']);
    $post_max_bytes = return_bytes($config['post_max_size']);
    $memory_limit_bytes = return_bytes($config['memory_limit']);

    // Required values (in bytes)
    $required_upload = 250 * 1024 * 1024; // 250MB
    $required_post = 250 * 1024 * 1024;   // 250MB
    $required_memory = 512 * 1024 * 1024; // 512MB

    function return_bytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int)$val;
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;
        }
        return $val;
    }
    ?>

    <h2>Current PHP Configuration</h2>
    <table>
        <tr>
            <th>Setting</th>
            <th>Current Value</th>
            <th>Required</th>
            <th>Status</th>
        </tr>
        <tr>
            <td>upload_max_filesize</td>
            <td><?php echo $config['upload_max_filesize']; ?> (<?php echo formatBytes($upload_max_bytes); ?>)</td>
            <td>250MB</td>
            <td><?php echo isSufficient($upload_max_bytes, 250) ? '<span class="ok">✓ OK</span>' : '<span class="error">✗ Too Small</span>'; ?></td>
        </tr>
        <tr>
            <td>post_max_size</td>
            <td><?php echo $config['post_max_size']; ?> (<?php echo formatBytes($post_max_bytes); ?>)</td>
            <td>250MB</td>
            <td><?php echo isSufficient($post_max_bytes, 250) ? '<span class="ok">✓ OK</span>' : '<span class="error">✗ Too Small</span>'; ?></td>
        </tr>
        <tr>
            <td>max_execution_time</td>
            <td><?php echo $config['max_execution_time']; ?> seconds</td>
            <td>300+ seconds</td>
            <td><?php echo $config['max_execution_time'] >= 300 ? '<span class="ok">✓ OK</span>' : '<span class="warning">⚠ Low</span>'; ?></td>
        </tr>
        <tr>
            <td>max_input_time</td>
            <td><?php echo $config['max_input_time']; ?> seconds</td>
            <td>300+ seconds</td>
            <td><?php echo $config['max_input_time'] >= 300 ? '<span class="ok">✓ OK</span>' : '<span class="warning">⚠ Low</span>'; ?></td>
        </tr>
        <tr>
            <td>memory_limit</td>
            <td><?php echo $config['memory_limit']; ?> (<?php echo formatBytes($memory_limit_bytes); ?>)</td>
            <td>512MB</td>
            <td><?php echo isSufficient($memory_limit_bytes, 512) ? '<span class="ok">✓ OK</span>' : '<span class="warning">⚠ Low</span>'; ?></td>
        </tr>
        <tr>
            <td>max_file_uploads</td>
            <td><?php echo $config['max_file_uploads']; ?></td>
            <td>20+</td>
            <td><?php echo $config['max_file_uploads'] >= 20 ? '<span class="ok">✓ OK</span>' : '<span class="warning">⚠ Low</span>'; ?></td>
        </tr>
        <tr>
            <td>file_uploads</td>
            <td><?php echo $config['file_uploads'] ? 'On' : 'Off'; ?></td>
            <td>On</td>
            <td><?php echo $config['file_uploads'] ? '<span class="ok">✓ OK</span>' : '<span class="error">✗ Disabled</span>'; ?></td>
        </tr>
        <tr>
            <td>max_input_vars</td>
            <td><?php echo $config['max_input_vars']; ?></td>
            <td>3000+</td>
            <td><?php echo $config['max_input_vars'] >= 3000 ? '<span class="ok">✓ OK</span>' : '<span class="warning">⚠ Low</span>'; ?></td>
        </tr>
    </table>

    <?php
    // Overall status
    $has_errors = false;
    $has_warnings = false;

    if (!isSufficient($upload_max_bytes, 250) || !isSufficient($post_max_bytes, 250) || !$config['file_uploads']) {
        $has_errors = true;
    }

    if ($config['max_execution_time'] < 300 || $config['max_input_time'] < 300 || 
        !isSufficient($memory_limit_bytes, 512) || $config['max_file_uploads'] < 20 || 
        $config['max_input_vars'] < 3000) {
        $has_warnings = true;
    }

    if ($has_errors) {
        echo '<div class="status error">';
        echo '<h3>❌ Configuration Issues Found</h3>';
        echo '<p>Your PHP configuration has critical issues that will prevent large file uploads from working.</p>';
        echo '</div>';
    } elseif ($has_warnings) {
        echo '<div class="status warning">';
        echo '<h3>⚠️ Configuration Warnings</h3>';
        echo '<p>Your PHP configuration may work but could cause issues with very large uploads.</p>';
        echo '</div>';
    } else {
        echo '<div class="status ok">';
        echo '<h3>✅ Configuration Looks Good</h3>';
        echo '<p>Your PHP configuration should handle large file uploads without issues.</p>';
        echo '</div>';
    }
    ?>

    <div class="recommendation">
        <h3>Recommended PHP Configuration</h3>
        <p>Add these settings to your <code>php.ini</code> file or create a <code>.htaccess</code> file in your project root:</p>
        <pre style="background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;">
# Maximum allowed size for uploaded files
upload_max_filesize = 250M

# Maximum allowed size for POST data
post_max_size = 250M

# Maximum execution time for scripts (in seconds)
max_execution_time = 300

# Maximum input time for scripts (in seconds)
max_input_time = 300

# Maximum amount of memory a script may consume
memory_limit = 512M

# Maximum number of files that can be uploaded via a single request
max_file_uploads = 20

# Enable file uploads
file_uploads = On

# Maximum number of input variables
max_input_vars = 3000
        </pre>
    </div>

    <div class="info">
        <h3>How to Apply Changes</h3>
        <ol>
            <li><strong>For XAMPP:</strong> Edit <code>C:\xampp\php\php.ini</code> and restart Apache</li>
            <li><strong>For .htaccess:</strong> The <code>.htaccess</code> file has been created in your project root</li>
            <li><strong>For shared hosting:</strong> Contact your hosting provider to update PHP settings</li>
        </ol>
        <p><strong>Note:</strong> After making changes, restart your web server and refresh this page to verify the new settings.</p>
    </div>

    <div class="info">
        <h3>Testing Large Uploads</h3>
        <p>To test if large uploads work:</p>
        <ol>
            <li>Create a test file larger than 100MB (you can use a video file or create a dummy file)</li>
            <li>Try uploading it through your disbursement form</li>
            <li>Monitor the browser's network tab for any errors</li>
            <li>Check the server error logs if uploads fail</li>
        </ol>
    </div>

    <p><small>Last checked: <?php echo date('Y-m-d H:i:s'); ?></small></p>
</body>
</html>
