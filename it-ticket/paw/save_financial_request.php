<?php
// Save Financial Request (RFP/ERL/ERGR)
// Expects POST from disbursement_form.php

// Start session for CSRF protection
session_start();

require_once __DIR__ . '/../conn/db.php';
require_once __DIR__ . '/upload_config.php';
require_once __DIR__ . '/task_notification.php';

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo 'CSRF token validation failed';
    exit;
}

// Rate Limiting (simple implementation)
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateLimitFile = sys_get_temp_dir() . '/financial_request_rate_limit_' . md5($clientIP);
$currentTime = time();

if (file_exists($rateLimitFile)) {
    $lastSubmission = (int)file_get_contents($rateLimitFile);
    if (($currentTime - $lastSubmission) < 5) { // 5 seconds between submissions
        http_response_code(429);
        echo 'Rate limit exceeded. Please wait 5 seconds before submitting another request.';
        exit;
    }
}

// Update rate limit timestamp
file_put_contents($rateLimitFile, $currentTime);

// Form timestamp validation (prevent replay attacks)
if (!isset($_POST['form_timestamp']) || 
    abs($currentTime - (int)$_POST['form_timestamp']) > 3600) { // 1 hour window
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

// Enhanced input sanitization and validation functions
function normalize_string_or_null($value) {
    if (!isset($value)) return null;
    $trimmed = trim((string)$value);
    if ($trimmed === '') return null;
    
    // Remove potential XSS and injection attempts
    $cleaned = htmlspecialchars($trimmed, ENT_QUOTES, 'UTF-8');
    $cleaned = preg_replace('/[<>"\']/', '', $cleaned); // Remove dangerous characters
    
    // Length validation
    if (strlen($cleaned) > 500) {
        $cleaned = substr($cleaned, 0, 500);
    }
    
    return $cleaned;
}

function normalize_decimal_or_null($value) {
    if (!isset($value)) return null;
    $trimmed = trim((string)$value);
    if ($trimmed === '') return null;
    
    // Validate numeric format
    if (!preg_match('/^[0-9]+(\.[0-9]{1,2})?$/', $trimmed)) {
        return null; // Invalid format
    }
    
    $decimal = (float)$trimmed;
    
    // Range validation
    if ($decimal < 0 || $decimal > 999999999.99) {
        return null; // Out of range
    }
    
    return $decimal;
}

function normalize_array($arr) {
    return is_array($arr) ? $arr : [];
}

function normalize_boolean_int($value) {
    if (!isset($value)) return 0;
    if (is_string($value)) {
        $v = strtolower($value);
        if ($v === 'yes' || $v === 'on' || $v === '1' || $v === 'true') return 1;
        return 0;
    }
    return $value ? 1 : 0;
}

function validate_enum_value($value, $allowed_values) {
    if (!isset($value)) return null;
    $trimmed = trim((string)$value);
    if ($trimmed === '') return null;
    
    return in_array($trimmed, $allowed_values, true) ? $trimmed : null;
}

function validate_date_format($date_string) {
    if (!isset($date_string) || $date_string === '') return null;
    
    $date = DateTime::createFromFormat('Y-m-d', $date_string);
    if ($date === false) return null;
    
    // Check if date is not in the future (business logic)
    $now = new DateTime();
    if ($date > $now) return null;
    
    return $date_string;
}

// Function to get user ID from sys_usertb by username
function getUserIdByUsername($conn, $username) {
    if (!$username || $username === '') return null;
    
    try {
        $stmt = mysqli_prepare($conn, 'SELECT id FROM sys_usertb WHERE username = ? LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $userId = (int)$row['id'];
                mysqli_stmt_close($stmt);
                return $userId;
            }
            mysqli_stmt_close($stmt);
        }
    } catch (Throwable $e) {
        // Log error but don't fail
        error_log("Error looking up user ID for username '$username': " . $e->getMessage());
    }
    
    return null;
}

// Function to get user ID from sys_usertb by firstname + lastname
function getUserIdByName($conn, $firstName, $lastName) {
    if (!$firstName && !$lastName) return null;
    
    try {
        $stmt = mysqli_prepare($conn, 'SELECT id FROM sys_usertb WHERE user_firstname = ? AND user_lastname = ? LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ss', $firstName, $lastName);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $userId = (int)$row['id'];
                mysqli_stmt_close($stmt);
                return $userId;
            }
            mysqli_stmt_close($stmt);
        }
    } catch (Throwable $e) {
        // Log error but don't fail
        error_log("Error looking up user ID for name '$firstName $lastName': " . $e->getMessage());
    }
    
    return null;
}

// Gather and validate POST values with enhanced security
$request_type = validate_enum_value($_POST['request_type'] ?? null, ['RFP', 'ERL', 'ERGR']);
if (!$request_type) {
    http_response_code(400);
    echo 'Invalid request type';
    exit;
}

$company = normalize_string_or_null($_POST['company'] ?? null);
$doc_type = normalize_string_or_null($_POST['doc_type'] ?? null);
$doc_number = normalize_string_or_null($_POST['doc_number'] ?? null);
$doc_date = validate_date_format($_POST['doc_date'] ?? null);

$cost_center = validate_enum_value(
    $_POST['cost_center'] ?? null,
    [
        // TLCI
        'TLCI-IT','TLCI-FIN','TLCI-ACC','TLCI-LOG','TLCI-PR','TLCI-SALES','TLCI-ENGR',
        // MPAV
        'MPAV-IT','MPAV-FIN','MPAV-ACC','MPAV-LOG','MPAV-PR','MPAV-SALES','MPAV-ENGR','MPAV IT','MPAV IT',
        // MPFF
        'MPFF-IT','MPFF-FIN','MPFF-ACC','MPFF-LOG','MPFF-PR','MPFF-SALES','MPFF-ENGR','MPFF IT'
    ]
);
$expenditure_type = validate_enum_value($_POST['expenditure_type'] ?? null, ['capex', 'opex']);
$balance = normalize_decimal_or_null($_POST['balance'] ?? null);
$budget = normalize_decimal_or_null($_POST['budget'] ?? null);
$currency = validate_enum_value($_POST['currency'] ?? null, ['USD', 'EUR', 'JPY', 'GBP', 'AUD', 'CAD', 'CHF', 'CNY', 'PHP']);
// Items and breakdowns arrive as arrays; header/footer remain on base table
$reference_number = null; // now in items
$reference_number_2 = null; // now in breakdowns
$po_number = null; // now in items
$due_date = null; // now in items
$date = null; // now in breakdowns
$amount = null; // now in items
$amount2 = null; // now in breakdowns
$cash_advance = 0; // now in items
$form_of_payment = null; // now in items

// Payee: store both user id and optional vendor text from input
$payeeUserId = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
$inputVendor = normalize_string_or_null($_POST['payee'] ?? null); // note: disabled inputs won't submit
// Combine as "<userId>|<vendor>"; if no vendor, just store userId
$payee = (string)$payeeUserId;
if (!is_null($inputVendor) && $inputVendor !== '') { $payee .= '|' . $inputVendor; }
$amount_in_words = normalize_string_or_null($_POST['amount_words'] ?? null);
// Amount in Figures header value (store as decimal)
$amount_in_figures = normalize_decimal_or_null($_POST['amount_figures'] ?? null);
$payment_for = normalize_string_or_null($_POST['payment_for'] ?? null);
$special_instructions = normalize_string_or_null($_POST['special_instructions'] ?? null);

$is_budgeted = validate_enum_value($_POST['is_budgeted'] ?? null, ['yes', 'no']);
$from_company = normalize_string_or_null($_POST['from_company'] ?? null);
$to_company = normalize_string_or_null($_POST['to_company'] ?? null);
$credit_to_payroll = normalize_boolean_int($_POST['credit_to_payroll'] ?? null);
$issue_check = normalize_boolean_int($_POST['issue_check'] ?? null);

// Server-side auto-generation for doc_type and doc_number
if (!$doc_type) {
    $doc_type = $request_type;
}
    if (!$doc_number) {
        // Dynamically fetch company_id and department_id from master tables
        $cCode = '';
        $dCode = '';

        try {
            if ($company) {
                if ($stCo = mysqli_prepare($conn, 'SELECT company_id FROM company WHERE company_code = ? LIMIT 1')) {
                    mysqli_stmt_bind_param($stCo, 's', $company);
                    mysqli_stmt_execute($stCo);
                    $rsCo = mysqli_stmt_get_result($stCo);
                    if ($rowCo = mysqli_fetch_assoc($rsCo)) {
                        $cCode = (string)($rowCo['company_id'] ?? '');
                    }
                    mysqli_stmt_close($stCo);
                }
            }
        } catch (Throwable $e) {
            // ignore and leave $cCode empty
        }

        try {
            if ($cost_center) { // cost_center stores department_code
                if ($stDp = mysqli_prepare($conn, 'SELECT department_id FROM department WHERE department_code = ? LIMIT 1')) {
                    mysqli_stmt_bind_param($stDp, 's', $cost_center);
                    mysqli_stmt_execute($stDp);
                    $rsDp = mysqli_stmt_get_result($stDp);
                    if ($rowDp = mysqli_fetch_assoc($rsDp)) {
                        $dCode = (string)($rowDp['department_id'] ?? '');
                    }
                    mysqli_stmt_close($stDp);
                }
            }
        } catch (Throwable $e) {
            // ignore and leave $dCode empty
        }

        $prefix = $cCode . $dCode;
        // Build yyMMdd + HH:mm with no spaces or dashes (only colon before minutes)
        $ymd = date('ymd');
        $hh = date('H');
        $mi = date('i');
        $doc_number = $request_type . '-' . $prefix . $ymd . $hh . $mi;
    }

// Ensure doc_number is unique; if duplicate, append -001, -002, ...
try {
    if ($doc_number) {
        $chk = mysqli_prepare($conn, 'SELECT 1 FROM financial_requests WHERE doc_number = ? LIMIT 1');
        if ($chk) {
            $base = $doc_number;
            $next = 1;
            $final = $base;
            while (true) {
                mysqli_stmt_bind_param($chk, 's', $final);
                mysqli_stmt_execute($chk);
                $res = mysqli_stmt_get_result($chk);
                if (!$res || mysqli_num_rows($res) === 0) {
                    // no duplicate
                    $doc_number = $final;
                    break;
                }
                // duplicate found → try with incremented suffix
                $suffix = str_pad((string)$next, 3, '0', STR_PAD_LEFT);
                $final = $base . $suffix;
                $next++;
                if ($next > 999) { // safety cap
                    $doc_number = $base . time();
                    break;
                }
            }
            mysqli_stmt_close($chk);
        }
    }
} catch (Throwable $e) {
    // On any error, keep the generated doc number as-is
}

// Handle file uploads - use new upload configuration
ensureUploadDirectories();
$attachmentsDir = ATTACHMENTS_DIR;
$supportingDocsDir = SUPPORTING_DOCS_DIR;

function save_uploaded_file($fieldName, $targetDir) {
    if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) return null;
    $file = $_FILES[$fieldName];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
    if (!$targetDir) return null;
    
    // Use configuration constants
    $allowedTypes = array_values(ALLOWED_ATTACHMENT_TYPES);
    $allowedExtensions = array_keys(ALLOWED_ATTACHMENT_TYPES);
    $maxFileSize = MAX_FILE_SIZE;
    
    // Check file size
    if ($file['size'] > $maxFileSize) {
        error_log("File size exceeded for field: $fieldName");
        return null;
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes, true)) {
        error_log("Invalid MIME type for field: $fieldName: $mimeType");
        return null;
    }
    
    // Check file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        error_log("Invalid file extension for field: $fieldName: $extension");
        return null;
    }
    
    // Additional security: check for executable content
    $fileContent = file_get_contents($file['tmp_name']);
    if (preg_match('/<\?php|<script|javascript|vbscript|onload|onerror/i', $fileContent)) {
        error_log("Potentially malicious content detected in field: $fieldName");
        return null;
    }

    $originalName = basename($file['name']);
    $unique = generateUniqueFilename($originalName, $targetDir);

    $dest = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $unique;
    if (!@move_uploaded_file($file['tmp_name'], $dest)) {
        return null;
    }
    // Return full path for database storage
    return $targetDir . $unique;
}

// Handle multiple supporting documents
$supporting_document_paths = [];
if (isset($_FILES['supporting_document']) && is_array($_FILES['supporting_document']['name'])) {
    $count = count($_FILES['supporting_document']['name']);
    for ($i = 0; $i < $count; $i++) {
        // Build per-file structure to reuse save_uploaded_file
        $tmp = [
            'name' => $_FILES['supporting_document']['name'][$i] ?? null,
            'type' => $_FILES['supporting_document']['type'][$i] ?? null,
            'tmp_name' => $_FILES['supporting_document']['tmp_name'][$i] ?? null,
            'error' => $_FILES['supporting_document']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $_FILES['supporting_document']['size'][$i] ?? 0,
        ];
        
        // Skip if no file was uploaded
        if (($tmp['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        
        // Temporarily override $_FILES index for save_uploaded_file
        $_FILES['__single_supporting_document'] = $tmp;
        $path = save_uploaded_file('__single_supporting_document', $supportingDocsDir);
        unset($_FILES['__single_supporting_document']);
        if ($path) {
            $supporting_document_paths[] = $path;
        }
    }
}

// Store supporting documents as JSON array or single path
$supporting_document_path = empty($supporting_document_paths) ? null : 
    (count($supporting_document_paths) === 1 ? $supporting_document_paths[0] : json_encode($supporting_document_paths));

// Basic required check
if (!$request_type) {
    http_response_code(400);
    echo 'request_type is required';
    exit;
}

// Server-side enforcement: ignore hidden/disabled groups based on request_type
// to prevent tampering via DevTools
// Request-type specific server-side enforcement still handled via UI logic

// Build insert
$sql = "INSERT INTO financial_requests (
    request_type, company, doc_type, doc_number, doc_date,
    cost_center, expenditure_type, balance, budget, currency,
    amount_figures, payee, amount_in_words, payment_for, special_instructions,
    supporting_document_path, is_budgeted, from_company, to_company, credit_to_payroll, issue_check
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    http_response_code(500);
    echo 'Failed to prepare statement';
    exit;
}

// Use string bindings for decimals and nullable ints to allow NULL to pass through
$types = 'sssssss' . // request_type, company, doc_type, doc_number, doc_date, cost_center, expenditure_type
         'ss' .      // balance, budget (as strings to allow NULL)
         's'  .      // currency
         's'  .      // amount_in_figures (string for decimal)
         's'  .      // payee (store as combined string: "userId|vendor")
         's'  .      // amount_in_words
         's'  .      // payment_for
         's'  .      // special_instructions
         's'  .      // supporting_document_path
         's'  .      // is_budgeted (store '1'/'0' or NULL)
         's'  .      // from_company
         's'  .      // to_company
         'i'  .      // credit_to_payroll
         'i';        // issue_check

// Prepare values for binding with correct null handling
$balance_str = is_null($balance) ? null : number_format($balance, 2, '.', '');
$budget_str  = is_null($budget)  ? null : number_format($budget, 2, '.', '');
$amount_in_figures_str = is_null($amount_in_figures) ? null : number_format($amount_in_figures, 2, '.', '');
$is_budgeted_str = is_null($is_budgeted) ? null : (($is_budgeted === 'yes') ? '1' : '0');

mysqli_stmt_bind_param(
    $stmt,
    $types,
    $request_type,
    $company,
    $doc_type,
    $doc_number,
    $doc_date,
    $cost_center,
    $expenditure_type,
    $balance_str,
    $budget_str,
    $currency,
    $amount_in_figures_str,
    $payee,
    $amount_in_words,
    $payment_for,
    $special_instructions,
    $supporting_document_path,
    $is_budgeted_str,
    $from_company,
    $to_company,
    $credit_to_payroll,
    $issue_check
);

try {
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Insert failed');
    }
    $newId = mysqli_insert_id($conn);

    // Insert item rows
    $items_category = normalize_array($_POST['items_category'] ?? []);
    $items_description = normalize_array($_POST['items_description'] ?? []);
    $items_amount = normalize_array($_POST['items_amount'] ?? []);
    $items_reference_number = normalize_array($_POST['items_reference_number'] ?? []);
    $items_po_number = normalize_array($_POST['items_po_number'] ?? []);
    $items_due_date = normalize_array($_POST['items_due_date'] ?? []);
    $items_cash_advance = normalize_array($_POST['items_cash_advance'] ?? []);
    $items_form_of_payment = normalize_array($_POST['items_form_of_payment'] ?? []);
    $items_budget_consumption = normalize_array($_POST['items_budget_consumption'] ?? []);

    // Handle multiple item attachments: input name is items_attachment[]
    // Collect ALL files into a single array for ONE item
    $all_attachments = [];
    if (isset($_FILES['items_attachment']) && is_array($_FILES['items_attachment']['name'])) {
        $count = count($_FILES['items_attachment']['name']);
        for ($i = 0; $i < $count; $i++) {
            // Build per-file structure to reuse save_uploaded_file
            $tmp = [
                'name' => $_FILES['items_attachment']['name'][$i] ?? null,
                'type' => $_FILES['items_attachment']['type'][$i] ?? null,
                'tmp_name' => $_FILES['items_attachment']['tmp_name'][$i] ?? null,
                'error' => $_FILES['items_attachment']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $_FILES['items_attachment']['size'][$i] ?? 0,
            ];
            
            // Skip if no file was uploaded for this item
            if (($tmp['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            
            // Temporarily override $_FILES index for save_uploaded_file
            $_FILES['__single_items_attachment'] = $tmp;
            $path = save_uploaded_file('__single_items_attachment', $attachmentsDir);
            unset($_FILES['__single_items_attachment']);
            if ($path) {
                $all_attachments[] = $path;
            }
        }
    }
    
    // Convert attachments to appropriate format for database
    $attachment_path = null;
    if (!empty($all_attachments)) {
        if (count($all_attachments) === 1) {
            $attachment_path = $all_attachments[0]; // Single file as string
        } else {
            $attachment_path = json_encode($all_attachments); // Multiple files as JSON array
        }
    }

    $insItem = mysqli_prepare($conn, "INSERT INTO financial_request_items (doc_number, category, attachment_path, description, amount, reference_number, po_number, due_date, cash_advance, form_of_payment, budget_consumption) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    if ($insItem) {
        // Since we're collecting all files into one item, we only need to check if we have any data
        $cat = normalize_string_or_null($items_category[0] ?? null);
        $desc = normalize_string_or_null($items_description[0] ?? null);
        $amt = normalize_decimal_or_null($items_amount[0] ?? null);
        $ref = normalize_string_or_null($items_reference_number[0] ?? null);
        $po = normalize_string_or_null($items_po_number[0] ?? null);
        $due = validate_date_format($items_due_date[0] ?? null);
        $cash = validate_enum_value($items_cash_advance[0] ?? null, ['yes','no']);
        $cashInt = $cash === 'yes' ? 1 : 0;
        $fop = validate_enum_value($items_form_of_payment[0] ?? null, ['corporate_check','wire_transfer','cash','manager_check','credit_to_account','others']);
        $bud = normalize_decimal_or_null($items_budget_consumption[0] ?? null);

        // Insert when any meaningful data exists. For RFP, category/description/amount may be hidden,
        // so rely on reference/po/due/cash/form_of_payment/budget as well.
        $hasData = ($cat || $desc || $amt || $ref || $po || $due || $attachment_path || ($bud !== null) || ($cash !== null) || ($fop !== null));
        if ($hasData) {
            $amt_str = is_null($amt) ? null : number_format($amt, 2, '.', '');
            $bud_str = is_null($bud) ? null : number_format($bud, 2, '.', '');
            // s (doc_number), s (cat), s (att), s (desc), s (amount), s (ref), s (po), s (due), i (cash), s (fop), s (budget_consumption)
            mysqli_stmt_bind_param($insItem, 'ssssssssiss', $doc_number, $cat, $attachment_path, $desc, $amt_str, $ref, $po, $due, $cashInt, $fop, $bud_str);
            mysqli_stmt_execute($insItem);
        }
        mysqli_stmt_close($insItem);
    }

    // Insert breakdown rows
    $break_ref2 = normalize_array($_POST['break_reference_number_2'] ?? []);
    $break_date = normalize_array($_POST['break_date'] ?? []);
    $break_amount2 = normalize_array($_POST['break_amount2'] ?? []);
    $insBd = mysqli_prepare($conn, "INSERT INTO financial_request_breakdowns (doc_number, reference_number_2, `date`, amount2) VALUES (?,?,?,?)");
    if ($insBd) {
        $m = max(count($break_ref2), count($break_date), count($break_amount2));
        for ($i = 0; $i < $m; $i++) {
            $r2 = normalize_string_or_null($break_ref2[$i] ?? null);
            $dt = validate_date_format($break_date[$i] ?? null);
            $a2 = normalize_decimal_or_null($break_amount2[$i] ?? null);
            if ($r2 || $dt || $a2) {
                $a2_str = is_null($a2) ? null : number_format($a2, 2, '.', '');
                mysqli_stmt_bind_param($insBd, 'ssss', $doc_number, $r2, $dt, $a2_str);
                mysqli_stmt_execute($insBd);
            }
        }
        mysqli_stmt_close($insBd);
    }

    // Initialize workflow process rows based on template (or fallback)
    // Compute requestor display from session; do not use numeric payee id
    $creatorName = isset($_SESSION['userfirstname']) || isset($_SESSION['userlastname'])
        ? trim((string)($_SESSION['userfirstname'] ?? '').' '.(string)($_SESSION['userlastname'] ?? ''))
        : ((isset($_SESSION['username']) ? (string)$_SESSION['username'] : 'Requestor'));

    // Get requestor user ID for workflow
    $requestorUserId = $payeeUserId; // Use the same user ID as payee

    // Try to load template rows
    $tplRows = [];
    $sqlTpl = "SELECT sequence, actor_id, action, is_parellel
               FROM work_flow_template
               WHERE work_flow_id = ? AND company = ? AND (department = ? OR department = '' OR department IS NULL)
               ORDER BY sequence, id";
    if ($stmtTpl = mysqli_prepare($conn, $sqlTpl)) {
        mysqli_stmt_bind_param($stmtTpl, 'sss', $request_type, $company, $cost_center);
        mysqli_stmt_execute($stmtTpl);
        $rsTpl = mysqli_stmt_get_result($stmtTpl);
        while ($row = mysqli_fetch_assoc($rsTpl)) {
            $tplRows[] = $row;
        }
        mysqli_stmt_close($stmtTpl);
    }

    // Ensure sequence 2 exists in process even if template lacks a row for the chosen department
    $hasSeq2 = false;
    foreach ($tplRows as $r) { if ((int)$r['sequence'] === 2) { $hasSeq2 = true; break; } }
    if (!$hasSeq2) {
        // Try to fetch a sequence 2 row specifically (best match on department, else global)
        $sqlCH = "SELECT sequence, actor_id, action, is_parellel
                  FROM work_flow_template
                  WHERE work_flow_id = ? AND company = ? AND sequence = 2
                    AND (department = ? OR department = '' OR department IS NULL)
                  ORDER BY CASE WHEN department = ? THEN 0 ELSE 1 END, id LIMIT 1";
        if ($stCH = mysqli_prepare($conn, $sqlCH)) {
            mysqli_stmt_bind_param($stCH, 'ssss', $request_type, $company, $cost_center, $cost_center);
            mysqli_stmt_execute($stCH);
            $rsCH = mysqli_stmt_get_result($stCH);
            if ($rowCH = mysqli_fetch_assoc($rsCH)) {
                $tplRows[] = $rowCH;
            } else {
                // Fallback default CC Head
                $tplRows[] = [ 'sequence' => 2, 'actor_id' => 'Cost Center Head', 'action' => 'Cost_Center_Head', 'is_parellel' => 0 ];
            }
            mysqli_stmt_close($stCH);
        } else {
            // Fallback if prepare failed
            $tplRows[] = [ 'sequence' => 2, 'actor_id' => 'Cost Center Head', 'action' => 'Cost_Center_Head', 'is_parellel' => 0 ];
        }
    }

    // Ensure sequence 1 Requestor exists
    $hasSeq1 = false;
    foreach ($tplRows as $r) {
        if ((int)$r['sequence'] === 1 || strcasecmp((string)$r['action'], 'Requestor') === 0) { $hasSeq1 = true; break; }
    }
    if (!$hasSeq1) {
        $tplRows[] = [ 'sequence' => 1, 'actor_id' => 'Requestor', 'action' => 'Requestor', 'is_parellel' => 0 ];
    }

    // Sort steps by sequence to keep process rows tidy
    if (count($tplRows) > 1) {
        usort($tplRows, function($a, $b) {
            $sa = isset($a['sequence']) ? (int)$a['sequence'] : 9999;
            $sb = isset($b['sequence']) ? (int)$b['sequence'] : 9999;
            return $sa <=> $sb;
        });
    }

    // Prepare insert for process - Create sequence 1 (Requestor) and sequence 2 (Cost Center Head)
    $insProc = mysqli_prepare($conn, "INSERT INTO work_flow_process (doc_type, doc_number, sequence, actor_id, action, status) VALUES (?,?,?,?,?,?)");
    if ($insProc) {
        // Create sequence 1 (Requestor) with 'Submitted' status
        $seq1Rows = array_filter($tplRows, function($row) {
            return (int)$row['sequence'] === 1 || strcasecmp((string)$row['action'], 'Requestor') === 0;
        });
        
        if (!empty($seq1Rows)) {
            foreach ($seq1Rows as $row) {
                $seq = (int)$row['sequence'];
                $action = (string)$row['action'];
                // For Requestor, use the actual user ID from session
                $actor = (strcasecmp($action, 'Requestor') === 0) ? $requestorUserId : (string)$row['actor_id'];
                $status = 'Submitted'; // Sequence 1 starts as 'Submitted'
                mysqli_stmt_bind_param($insProc, 'ssisss', $request_type, $doc_number, $seq, $actor, $action, $status);
                mysqli_stmt_execute($insProc);
            }
        } else {
            // Fallback - create Requestor entry with actual user ID
            $actor = $requestorUserId;
            $action = 'Requestor';
            $status = 'Submitted';
            mysqli_stmt_bind_param($insProc, 'ssisss', $request_type, $doc_number, 1, $actor, $action, $status);
            mysqli_stmt_execute($insProc);
        }
        
        // Create sequence 2 (Cost Center Head) with 'Waiting for Approval' status
        $seq2Rows = array_filter($tplRows, function($row) {
            return (int)$row['sequence'] === 2;
        });
        
        if (!empty($seq2Rows)) {
            foreach ($seq2Rows as $row) {
                $seq = (int)$row['sequence'];
                $action = (string)$row['action'];
                // Look up user ID for the actor from sys_usertb
                $actorName = (string)$row['actor_id'];
                $actor = getUserIdByUsername($conn, $actorName);
                
                // If username lookup fails, try to find by name (for cases like "Cost Center Head")
                if (!$actor && $actorName !== 'Cost Center Head') {
                    // Try to extract first and last name if actor_id contains a space
                    if (strpos($actorName, ' ') !== false) {
                        $nameParts = explode(' ', $actorName, 2);
                        $actor = getUserIdByName($conn, $nameParts[0], $nameParts[1]);
                    }
                }
                
                // If still no user ID found, use the actor name as fallback (for backward compatibility)
                if (!$actor) {
                    $actor = $actorName;
                }
                
                $status = 'Waiting for Approval'; // Sequence 2 starts as 'Waiting for Approval'
                mysqli_stmt_bind_param($insProc, 'ssisss', $request_type, $doc_number, $seq, $actor, $action, $status);
                mysqli_stmt_execute($insProc);
            }
        } else {
            // Fallback - create Cost Center Head entry
            $actor = 'Cost Center Head';
            $action = 'Cost_Center_Head';
            $status = 'Waiting for Approval';
            mysqli_stmt_bind_param($insProc, 'ssisss', $request_type, $doc_number, 2, $actor, $action, $status);
            mysqli_stmt_execute($insProc);
        }
        
        mysqli_stmt_close($insProc);

        // Trigger async notification for SUBMIT (Sequence 2 approvers)
        try { @frn_trigger_async_notification($request_type, $doc_number, 'SUBMIT', 2, $creatorName, null); } catch (Throwable $e) {}
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Save failed: ' . htmlspecialchars($e->getMessage());
    exit;
} finally {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}

// If the form expects a redirect, do that; otherwise return JSON
if (!empty($_POST['__expect_json'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'id' => $newId]);
    exit;
}

header('Location: disbursement_view.php?id=' . urlencode((string)$newId));
exit;