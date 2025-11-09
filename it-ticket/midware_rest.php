<?php
/**
 * Ultra-Sensitive URL Change Detection
 * Detects ANY manual URL changes, even single character modifications
 * Include this file at the top of any page you want to restrict
 * Usage: require_once 'simple_protection.php';
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include DB connection
require_once __DIR__ . '/conn/db.php';

// Basic session check
if (!isset($_SESSION['username']) || !isset($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}

// Helper: log restriction access to DB
function logRestrictionAccess($reason) {
    global $mysqlconn;

    if (!$mysqlconn) {
        return;
    }

    $user_id = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : null;
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : null;
    $user_firstname = null;
    $user_lastname = null;
    $emailadd = null;

    if ($user_id) {
        if ($stmt = mysqli_prepare($mysqlconn, "SELECT id, user_firstname, user_lastname, username, emailadd FROM sys_usertb WHERE id = ?")) {
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_bind_result($stmt, $rid, $rfn, $rln, $run, $rem);
                if (mysqli_stmt_fetch($stmt)) {
                    $username = $run;
                    $user_firstname = $rfn;
                    $user_lastname = $rln;
                    $emailadd = $rem;
                }
            }
            mysqli_stmt_close($stmt);
        }
    }

    $previous_url = isset($_SESSION['last_url']) ? $_SESSION['last_url'] : null;
    $current_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $page = isset($_SERVER['PHP_SELF']) ? basename($_SERVER['PHP_SELF']) : '';
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    $ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

    if ($stmt = mysqli_prepare(
        $mysqlconn,
        "INSERT INTO access_restriction_log
        (user_id, username, user_firstname, user_lastname, emailadd, previous_url, current_url, page, ip_address, reason, referrer, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    )) {
        mysqli_stmt_bind_param(
            $stmt,
            "isssssssssss",
            $user_id,
            $username,
            $user_firstname,
            $user_lastname,
            $emailadd,
            $previous_url,
            $current_url,
            $page,
            $ip,
            $reason,
            $ref,
            $ua
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Function to detect ANY URL change
function detectAnyUrlChange() {
    $current_url = $_SERVER['REQUEST_URI'];
    $current_page = basename($_SERVER['PHP_SELF']);
    
    // Check if this is a direct URL access (no referrer)
    $has_referrer = !empty($_SERVER['HTTP_REFERER']);
    
    // Initialize session variables if not set
    if (!isset($_SESSION['last_url'])) {
        $_SESSION['last_url'] = $current_url;
        $_SESSION['last_page'] = $current_page;
        return false; // First visit, allow
    }
    
    // If there's no referrer, it's likely manual URL change
    if (!$has_referrer) {
        // Check if URL has changed at all (even 1 character)
        if ($_SESSION['last_url'] !== $current_url) {
            // URL has changed - this is manual manipulation
            return true;
        }
        
        // Check if page has changed
        if ($_SESSION['last_page'] !== $current_page) {
            // Page has changed - this is manual manipulation
            return true;
        }
    } else {
        // There is a referrer, so this is legitimate navigation
        // Update tracking variables
        $_SESSION['last_url'] = $current_url;
        $_SESSION['last_page'] = $current_page;
    }
    
    return false;
}

// Function to detect suspicious URL patterns (basic security check)
function detectSuspiciousPatterns() {
    $current_url = $_SERVER['REQUEST_URI'];
    
    // Only check for the most dangerous patterns
    $dangerous_patterns = [
        '..',           // Directory traversal
        '%2e%2e',       // URL encoded directory traversal
        'script',       // Script injection
        'javascript',   // JavaScript injection
        'union',        // SQL injection
        'select',       // SQL injection
        'insert',       // SQL injection
        'delete',       // SQL injection
        'drop',         // SQL injection
        'exec',         // Command injection
        'eval',         // Code injection
    ];
    
    foreach ($dangerous_patterns as $pattern) {
        if (stripos($current_url, $pattern) !== false) {
            return true;
        }
    }
    
    return false;
}

// Check for ANY URL changes (even 1 character)
if (detectAnyUrlChange()) {
    // Log the attempt
    error_log("Manual URL change detected from IP: " . $_SERVER['REMOTE_ADDR'] . 
              " - Previous URL: " . ($_SESSION['last_url'] ?? 'none') . 
              " - Current URL: " . $_SERVER['REQUEST_URI']);
    
    // DB log
    logRestrictionAccess('manual_url_change');
    
    // Redirect to restricted access page
    header('Location: ../error_pages/restricted_access.php');
    exit();
}

// Check for dangerous patterns
if (detectSuspiciousPatterns()) {
    error_log("Dangerous URL pattern detected from IP: " . $_SERVER['REMOTE_ADDR'] . 
              " - URL: " . $_SERVER['REQUEST_URI']);
    
    // DB log
    logRestrictionAccess('suspicious_pattern');

    header('Location: ../error_pages/restricted_access.php');
    exit();
}

// Update tracking (only if no referrer - legitimate navigation)
if (empty($_SERVER['HTTP_REFERER'])) {
    $_SESSION['last_url'] = $_SERVER['REQUEST_URI'];
    $_SESSION['last_page'] = basename($_SERVER['PHP_SELF']);
}

// Set access time
$_SESSION['last_access'] = time();
?>