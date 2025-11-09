<?php
// Save Financial Request (RFP/ERL/ERGR)
// Expects POST from disbursement_form.php

// Start session for CSRF protection
session_start();

require_once __DIR__ . '/../conn/db.php';
require_once __DIR__ . '/upload_config.php';
require_once __DIR__ . '/task_notification.php';
require_once __DIR__ . '/workflow_helpers.php';
require_once __DIR__ . '/attachment_handler.php';

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
$request_type = validate_enum_value($_POST['request_type'] ?? null, ['RFP', 'ERL', 'ERGR', 'PR', 'PO', 'PAW']);
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

// Payee: store only the vendor/payee name from input
$inputVendor = normalize_string_or_null($_POST['payee'] ?? null); // note: disabled inputs won't submit
$payee = $inputVendor; // Store only the payee name, not the user ID
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

// Initialize attachment handler
$attachmentHandler = new AttachmentHandler($conn);

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
    supporting_document_path, is_budgeted, from_company, to_company, credit_to_payroll, issue_check,
    created_by_user_id
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

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
         'i'  .      // issue_check
         'i';        // created_by_user_id

// Prepare values for binding with correct null handling
$balance_str = is_null($balance) ? null : number_format($balance, 2, '.', '');
$budget_str  = is_null($budget)  ? null : number_format($budget, 2, '.', '');
$amount_in_figures_str = is_null($amount_in_figures) ? null : number_format($amount_in_figures, 2, '.', '');
$is_budgeted_str = is_null($is_budgeted) ? null : (($is_budgeted === 'yes') ? '1' : '0');

// Get current user ID from session
$created_by_user_id = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : null;

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
    $issue_check,
    $created_by_user_id
);

try {
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Insert failed');
    }
    $newId = mysqli_insert_id($conn);

    // Insert item rows
    $items_category = normalize_array($_POST['items_category'] ?? []);
    $items_description = normalize_array($_POST['items_description'] ?? []);
    $items_reference_number = normalize_array($_POST['items_reference_number'] ?? []);
    $items_po_number = normalize_array($_POST['items_po_number'] ?? []);
    $items_due_date = normalize_array($_POST['items_due_date'] ?? []);
    $items_cash_advance = normalize_array($_POST['items_cash_advance'] ?? []);
    $items_form_of_payment = normalize_array($_POST['items_form_of_payment'] ?? []);
$items_gross_amount = normalize_array($_POST['items_gross_amount'] ?? []);
$items_vatable = normalize_array($_POST['items_vatable'] ?? []);
$items_vat_amount = normalize_array($_POST['items_vat_amount'] ?? []);
$items_withholding_tax = normalize_array($_POST['items_withholding_tax'] ?? []);
$items_amount_withhold = normalize_array($_POST['items_amount_withhold'] ?? []);
$items_net_payable = normalize_array($_POST['items_net_payable'] ?? []);
$items_carf_no = normalize_array($_POST['items_carf_no'] ?? []);
$items_pcv_no = normalize_array($_POST['items_pcv_no'] ?? []);
$items_invoice_date = normalize_array($_POST['items_invoice_date'] ?? []);
$items_invoice_number = normalize_array($_POST['items_invoice_number'] ?? []);
$items_supplier_name = normalize_array($_POST['items_supplier_name'] ?? []);
$items_tin = normalize_array($_POST['items_tin'] ?? []);
$items_address = normalize_array($_POST['items_address'] ?? []);

    // Note: Item attachments will be handled after items are inserted into database
    // We'll store the item_id and upload attachments separately

    // Check which columns exist in the table to avoid column count mismatch
    $tableColumns = [];
    try {
        $result = mysqli_query($conn, "DESCRIBE financial_request_items");
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $tableColumns[] = $row['Field'];
            }
            mysqli_free_result($result);
        }
    } catch (Throwable $e) {
        // Fallback to basic columns if DESCRIBE fails
        $tableColumns = ['doc_number', 'category', 'attachment_path', 'description', 'amount', 'reference_number', 'po_number', 'due_date', 'cash_advance', 'form_of_payment', 'budget_consumption', 'created_at', 'updated_at'];
    }

    // Build dynamic INSERT statement based on available columns
    $insertColumns = [];
    $insertValues = [];
    $insertTypes = '';
    $insertParams = [];

    // Core columns that should always exist
    $coreColumns = [
        'doc_number' => 's',
        'category' => 's', 
        'attachment_path' => 's',
        'description' => 's',
        'amount' => 's',
        'reference_number' => 's',
        'po_number' => 's',
        'due_date' => 's',
        'cash_advance' => 'i',
        'form_of_payment' => 's',
        'budget_consumption' => 's'
    ];

    // Tax-related columns (may not exist in older database)
    $taxColumns = [
        'gross_amount' => 's',
        'vatable' => 's',
        'vat_amount' => 's',
        'withholding_tax' => 's',
        'amount_withhold' => 's',
        'net_payable_amount' => 's'
    ];

    // ERGR/ERL specific columns (may not exist in older database)
    $erColumns = [
        'carf_no' => 's',
        'pcv_no' => 's',
        'invoice_date' => 's',
        'invoice_number' => 's',
        'supplier_name' => 's',
        'tin' => 's',
        'address' => 's'
    ];

    // Add core columns
    foreach ($coreColumns as $column => $type) {
        if (in_array($column, $tableColumns)) {
            $insertColumns[] = $column;
            $insertValues[] = '?';
            $insertTypes .= $type;
        }
    }

    // Add tax columns if they exist
    foreach ($taxColumns as $column => $type) {
        if (in_array($column, $tableColumns)) {
            $insertColumns[] = $column;
            $insertValues[] = '?';
            $insertTypes .= $type;
        }
    }

    // Add ER columns if they exist
    foreach ($erColumns as $column => $type) {
        if (in_array($column, $tableColumns)) {
            $insertColumns[] = $column;
            $insertValues[] = '?';
            $insertTypes .= $type;
        }
    }

    if (!empty($insertColumns)) {
        $sql = "INSERT INTO financial_request_items (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $insertValues) . ")";
        $insItem = mysqli_prepare($conn, $sql);
        
    if ($insItem) {
        // Since we're collecting all files into one item, we only need to check if we have any data
        $cat = normalize_string_or_null($items_category[0] ?? null);
        // Map posted category_code to sap_account_code for storage
        if ($cat) {
            try {
                if ($stCat = mysqli_prepare($conn, 'SELECT sap_account_code FROM categories WHERE category_code = ? LIMIT 1')) {
                    mysqli_stmt_bind_param($stCat, 's', $cat);
                    mysqli_stmt_execute($stCat);
                    $rsCat = mysqli_stmt_get_result($stCat);
                    if ($rowCat = mysqli_fetch_assoc($rsCat)) {
                        $sapCode = (string)($rowCat['sap_account_code'] ?? '');
                        if ($sapCode !== '') { $cat = $sapCode; }
                    }
                    mysqli_stmt_close($stCat);
                }
            } catch (Throwable $e) { /* ignore mapping errors */ }
        }
        $desc = normalize_string_or_null($items_description[0] ?? null);
        $ref = normalize_string_or_null($items_reference_number[0] ?? null);
        $po = normalize_string_or_null($items_po_number[0] ?? null);
        $due = validate_date_format($items_due_date[0] ?? null);
        $cash = validate_enum_value($items_cash_advance[0] ?? null, ['yes','no']);
        $cashInt = $cash === 'yes' ? 1 : 0;
        $fop = validate_enum_value($items_form_of_payment[0] ?? null, ['corporate_check','wire_transfer','cash','manager_check','credit_to_account','others']);
        $gross = normalize_decimal_or_null($items_gross_amount[0] ?? null);
        $vatable = validate_enum_value($items_vatable[0] ?? null, ['yes','no']);
        $vat = normalize_decimal_or_null($items_vat_amount[0] ?? null);
        $withholding = normalize_string_or_null($items_withholding_tax[0] ?? null);
        $withhold = normalize_decimal_or_null($items_amount_withhold[0] ?? null);
        $net = normalize_decimal_or_null($items_net_payable[0] ?? null);
        $carf = normalize_string_or_null($items_carf_no[0] ?? null);
        $pcv = normalize_string_or_null($items_pcv_no[0] ?? null);
        $inv_date = normalize_string_or_null($items_invoice_date[0] ?? null);
        $inv_num = normalize_string_or_null($items_invoice_number[0] ?? null);
        $supplier = normalize_string_or_null($items_supplier_name[0] ?? null);
        $tin = normalize_string_or_null($items_tin[0] ?? null);
        $address = normalize_string_or_null($items_address[0] ?? null);

        // Insert when any meaningful data exists. For RFP, category/description/amount may be hidden,
        // so rely on reference/po/due/cash/form_of_payment/budget as well.
        $hasData = ($cat || $desc || $ref || $po || $due || $attachment_path || ($cash !== null) || ($fop !== null) || ($gross !== null) || ($vatable !== null) || ($vat !== null) || ($withholding !== null) || ($withhold !== null) || ($net !== null) || $carf || $pcv || $inv_date || $inv_num || $supplier || $tin || $address);
        if ($hasData) {
            $gross_str = is_null($gross) ? null : number_format($gross, 2, '.', '');
            $vat_str = is_null($vat) ? null : number_format($vat, 2, '.', '');
            $withhold_str = is_null($withhold) ? null : number_format($withhold, 2, '.', '');
            $net_str = is_null($net) ? null : number_format($net, 2, '.', '');

                // Build parameter array dynamically based on available columns
                $params = [];
                $paramTypes = '';
                
                // Add core parameters
                if (in_array('doc_number', $tableColumns)) { $params[] = $doc_number; $paramTypes .= 's'; }
                if (in_array('category', $tableColumns)) { $params[] = $cat; $paramTypes .= 's'; }
                if (in_array('attachment_path', $tableColumns)) { $params[] = $attachment_path; $paramTypes .= 's'; }
                if (in_array('description', $tableColumns)) { $params[] = $desc; $paramTypes .= 's'; }
                if (in_array('reference_number', $tableColumns)) { $params[] = $ref; $paramTypes .= 's'; }
                if (in_array('po_number', $tableColumns)) { $params[] = $po; $paramTypes .= 's'; }
                if (in_array('due_date', $tableColumns)) { $params[] = $due; $paramTypes .= 's'; }
                if (in_array('cash_advance', $tableColumns)) { $params[] = $cashInt; $paramTypes .= 'i'; }
                if (in_array('form_of_payment', $tableColumns)) { $params[] = $fop; $paramTypes .= 's'; }
                
                // Add tax parameters if columns exist
                if (in_array('gross_amount', $tableColumns)) { $params[] = $gross_str; $paramTypes .= 's'; }
                if (in_array('vatable', $tableColumns)) { $params[] = $vatable; $paramTypes .= 's'; }
                if (in_array('vat_amount', $tableColumns)) { $params[] = $vat_str; $paramTypes .= 's'; }
                if (in_array('withholding_tax', $tableColumns)) { $params[] = $withholding; $paramTypes .= 's'; }
                if (in_array('amount_withhold', $tableColumns)) { $params[] = $withhold_str; $paramTypes .= 's'; }
                if (in_array('net_payable_amount', $tableColumns)) { $params[] = $net_str; $paramTypes .= 's'; }
                
                // Add ER parameters if columns exist
                if (in_array('carf_no', $tableColumns)) { $params[] = $carf; $paramTypes .= 's'; }
                if (in_array('pcv_no', $tableColumns)) { $params[] = $pcv; $paramTypes .= 's'; }
                if (in_array('invoice_date', $tableColumns)) { $params[] = $inv_date; $paramTypes .= 's'; }
                if (in_array('invoice_number', $tableColumns)) { $params[] = $inv_num; $paramTypes .= 's'; }
                if (in_array('supplier_name', $tableColumns)) { $params[] = $supplier; $paramTypes .= 's'; }
                if (in_array('tin', $tableColumns)) { $params[] = $tin; $paramTypes .= 's'; }
                if (in_array('address', $tableColumns)) { $params[] = $address; $paramTypes .= 's'; }

                // Bind parameters dynamically
                if (!empty($params)) {
                    $paramCount = count($params);
                    
                    // Create references for all parameters
                    $refs = [];
                    foreach ($params as $key => $value) {
                        $refs[$key] = &$params[$key];
                    }
                    
                    // Use switch statement to handle different parameter counts
                    // This avoids eval() and call_user_func_array() issues with references
                    switch ($paramCount) {
                        case 1:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0]);
                            break;
                        case 2:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0], $refs[1]);
                            break;
                        case 3:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0], $refs[1], $refs[2]);
                            break;
                        case 4:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0], $refs[1], $refs[2], $refs[3]);
                            break;
                        case 5:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0], $refs[1], $refs[2], $refs[3], $refs[4]);
                            break;
                        case 6:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0], $refs[1], $refs[2], $refs[3], $refs[4], $refs[5]);
                            break;
                        case 7:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0], $refs[1], $refs[2], $refs[3], $refs[4], $refs[5], $refs[6]);
                            break;
                        case 8:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0], $refs[1], $refs[2], $refs[3], $refs[4], $refs[5], $refs[6], $refs[7]);
                            break;
                        case 9:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0], $refs[1], $refs[2], $refs[3], $refs[4], $refs[5], $refs[6], $refs[7], $refs[8]);
                            break;
                        case 10:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0], $refs[1], $refs[2], $refs[3], $refs[4], $refs[5], $refs[6], $refs[7], $refs[8], $refs[9]);
                            break;
                        case 11:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0], $refs[1], $refs[2], $refs[3], $refs[4], $refs[5], $refs[6], $refs[7], $refs[8], $refs[9], $refs[10]);
                            break;
                        case 12:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0], $refs[1], $refs[2], $refs[3], $refs[4], $refs[5], $refs[6], $refs[7], $refs[8], $refs[9], $refs[10], $refs[11]);
                            break;
                        case 13:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0], $refs[1], $refs[2], $refs[3], $refs[4], $refs[5], $refs[6], $refs[7], $refs[8], $refs[9], $refs[10], $refs[11], $refs[12]);
                            break;
                        case 14:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0], $refs[1], $refs[2], $refs[3], $refs[4], $refs[5], $refs[6], $refs[7], $refs[8], $refs[9], $refs[10], $refs[11], $refs[12], $refs[13]);
                            break;
                        case 15:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0], $refs[1], $refs[2], $refs[3], $refs[4], $refs[5], $refs[6], $refs[7], $refs[8], $refs[9], $refs[10], $refs[11], $refs[12], $refs[13], $refs[14]);
                            break;
                        case 16:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0], $refs[1], $refs[2], $refs[3], $refs[4], $refs[5], $refs[6], $refs[7], $refs[8], $refs[9], $refs[10], $refs[11], $refs[12], $refs[13], $refs[14], $refs[15]);
                            break;
                        case 17:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0], $refs[1], $refs[2], $refs[3], $refs[4], $refs[5], $refs[6], $refs[7], $refs[8], $refs[9], $refs[10], $refs[11], $refs[12], $refs[13], $refs[14], $refs[15], $refs[16]);
                            break;
                        case 18:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0], $refs[1], $refs[2], $refs[3], $refs[4], $refs[5], $refs[6], $refs[7], $refs[8], $refs[9], $refs[10], $refs[11], $refs[12], $refs[13], $refs[14], $refs[15], $refs[16], $refs[17]);
                            break;
                        case 19:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0], $refs[1], $refs[2], $refs[3], $refs[4], $refs[5], $refs[6], $refs[7], $refs[8], $refs[9], $refs[10], $refs[11], $refs[12], $refs[13], $refs[14], $refs[15], $refs[16], $refs[17], $refs[18]);
                            break;
                        case 20:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0], $refs[1], $refs[2], $refs[3], $refs[4], $refs[5], $refs[6], $refs[7], $refs[8], $refs[9], $refs[10], $refs[11], $refs[12], $refs[13], $refs[14], $refs[15], $refs[16], $refs[17], $refs[18], $refs[19]);
                            break;
                        case 21:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0], $refs[1], $refs[2], $refs[3], $refs[4], $refs[5], $refs[6], $refs[7], $refs[8], $refs[9], $refs[10], $refs[11], $refs[12], $refs[13], $refs[14], $refs[15], $refs[16], $refs[17], $refs[18], $refs[19], $refs[20]);
                            break;
                        case 22:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0], $refs[1], $refs[2], $refs[3], $refs[4], $refs[5], $refs[6], $refs[7], $refs[8], $refs[9], $refs[10], $refs[11], $refs[12], $refs[13], $refs[14], $refs[15], $refs[16], $refs[17], $refs[18], $refs[19], $refs[20], $refs[21]);
                            break;
                        case 23:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0], $refs[1], $refs[2], $refs[3], $refs[4], $refs[5], $refs[6], $refs[7], $refs[8], $refs[9], $refs[10], $refs[11], $refs[12], $refs[13], $refs[14], $refs[15], $refs[16], $refs[17], $refs[18], $refs[19], $refs[20], $refs[21], $refs[22]);
                            break;
                        case 24:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0], $refs[1], $refs[2], $refs[3], $refs[4], $refs[5], $refs[6], $refs[7], $refs[8], $refs[9], $refs[10], $refs[11], $refs[12], $refs[13], $refs[14], $refs[15], $refs[16], $refs[17], $refs[18], $refs[19], $refs[20], $refs[21], $refs[22], $refs[23]);
                            break;
                        case 25:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0], $refs[1], $refs[2], $refs[3], $refs[4], $refs[5], $refs[6], $refs[7], $refs[8], $refs[9], $refs[10], $refs[11], $refs[12], $refs[13], $refs[14], $refs[15], $refs[16], $refs[17], $refs[18], $refs[19], $refs[20], $refs[21], $refs[22], $refs[23], $refs[24]);
                            break;
                        case 26:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0], $refs[1], $refs[2], $refs[3], $refs[4], $refs[5], $refs[6], $refs[7], $refs[8], $refs[9], $refs[10], $refs[11], $refs[12], $refs[13], $refs[14], $refs[15], $refs[16], $refs[17], $refs[18], $refs[19], $refs[20], $refs[21], $refs[22], $refs[23], $refs[24], $refs[25]);
                            break;
                        case 27:
                            mysqli_stmt_bind_param($insItem, $paramTypes, $refs[0], $refs[1], $refs[2], $refs[3], $refs[4], $refs[5], $refs[6], $refs[7], $refs[8], $refs[9], $refs[10], $refs[11], $refs[12], $refs[13], $refs[14], $refs[15], $refs[16], $refs[17], $refs[18], $refs[19], $refs[20], $refs[21], $refs[22], $refs[23], $refs[24], $refs[25], $refs[26]);
                            break;
                        default:
                            // Fallback for more than 27 parameters (shouldn't happen with current schema)
                            error_log("Too many parameters for financial_request_items insert: $paramCount");
                            break;
                    }
                    
            mysqli_stmt_execute($insItem);
                }
        }
        mysqli_stmt_close($insItem);
        }
    }

    // Breakdown functionality has been removed - data is now handled through items table

    // Initialize workflow process rows based on template (or fallback)
    // Compute requestor display from session; do not use numeric payee id
    $creatorName = isset($_SESSION['userfirstname']) || isset($_SESSION['userlastname'])
        ? trim((string)($_SESSION['userfirstname'] ?? '').' '.(string)($_SESSION['userlastname'] ?? ''))
        : ((isset($_SESSION['username']) ? (string)$_SESSION['username'] : 'Requestor'));

    // Get requestor user ID for workflow
    $requestorUserId = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;

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
    // Get workflow type ID for the new work_flow_type_id column
    $workflowTypeId = getWorkflowTypeId($conn, $request_type);
    
    if ($workflowTypeId) {
        // Use new format with work_flow_type_id
        $insProc = mysqli_prepare($conn, "INSERT INTO work_flow_process (doc_type, work_flow_type_id, doc_number, sequence, actor_id, action, status) VALUES (?,?,?,?,?,?,?)");
    } else {
        // Fallback to old format without work_flow_type_id
        $insProc = mysqli_prepare($conn, "INSERT INTO work_flow_process (doc_type, doc_number, sequence, actor_id, action, status) VALUES (?,?,?,?,?,?)");
    }
    
    if ($insProc) {
        // Create sequence 1 (Requestor) with 'Submitted' status
        $seq1Rows = array_filter($tplRows, function($row) {
            return (int)$row['sequence'] === 1 || strcasecmp((string)$row['action'], 'Requestor') === 0;
        });
        
        if (!empty($seq1Rows)) {
            foreach ($seq1Rows as $row) {
                $seq = (int)$row['sequence'];
                $action = (string)$row['action'];
                // For Requestor, use the payee name from the form
                $actor = (strcasecmp($action, 'Requestor') === 0) ? $payee : (string)$row['actor_id'];
                
                // Ensure actor is not null or empty for Requestor
                if (strcasecmp($action, 'Requestor') === 0 && (!$actor || $actor === '')) {
                    $actor = 'Requestor'; // Default fallback for Requestor
                }
                $status = 'Submitted'; // Sequence 1 starts as 'Submitted'
                
                if ($workflowTypeId) {
                    mysqli_stmt_bind_param($insProc, 'sisisss', $request_type, $workflowTypeId, $doc_number, $seq, $actor, $action, $status);
                } else {
                    mysqli_stmt_bind_param($insProc, 'ssisss', $request_type, $doc_number, $seq, $actor, $action, $status);
                }
                mysqli_stmt_execute($insProc);
            }
        } else {
            // Fallback - create Requestor entry with payee name
            $actor = $payee;
            if (!$actor || $actor === '') {
                $actor = 'Requestor'; // Default fallback
            }
            $action = 'Requestor';
            $status = 'Submitted';
            
            if ($workflowTypeId) {
                mysqli_stmt_bind_param($insProc, 'sisisss', $request_type, $workflowTypeId, $doc_number, 1, $actor, $action, $status);
            } else {
                mysqli_stmt_bind_param($insProc, 'ssisss', $request_type, $doc_number, 1, $actor, $action, $status);
            }
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
                // Store actor_id as username/string directly (no user ID lookup)
                $actor = (string)$row['actor_id'];
                if ($actor === '') { $actor = 'System'; }
                $status = 'Waiting for Approval'; // Sequence 2 starts as 'Waiting for Approval'
                
                if ($workflowTypeId) {
                    mysqli_stmt_bind_param($insProc, 'sisisss', $request_type, $workflowTypeId, $doc_number, $seq, $actor, $action, $status);
                } else {
                    mysqli_stmt_bind_param($insProc, 'ssisss', $request_type, $doc_number, $seq, $actor, $action, $status);
                }
                mysqli_stmt_execute($insProc);
            }
        } else {
            // Fallback - create Cost Center Head entry
            $actor = 'Cost Center Head';
            $action = 'Cost_Center_Head';
            $status = 'Waiting for Approval';
            
            if ($workflowTypeId) {
                mysqli_stmt_bind_param($insProc, 'sisisss', $request_type, $workflowTypeId, $doc_number, 2, $actor, $action, $status);
            } else {
                mysqli_stmt_bind_param($insProc, 'ssisss', $request_type, $doc_number, 2, $actor, $action, $status);
            }
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