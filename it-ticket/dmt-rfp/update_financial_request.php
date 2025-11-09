<?php
// Update Financial Request (RFP/ERL/ERGR) - New Version with Attachment System
// Expects POST from disbursement_edit_form.php

// Start output buffering to prevent HTML from being sent before JSON response
ob_start();

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
    ob_clean();
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

// CSRF Protection (accept token from POST, header, or cookie)
$csrfSession = $_SESSION['csrf_token'] ?? null;
$csrfPost = $_POST['csrf_token'] ?? null;
$csrfCookie = $_COOKIE['csrf_token'] ?? null;
$csrfHeader = null;
if (!function_exists('getallheaders')) {
    function getallheaders() { $headers = []; foreach ($_SERVER as $name => $value) { if (substr($name, 0, 5) == 'HTTP_') { $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5))))); $headers[$key] = $value; } } return $headers; }
}
$headers = getallheaders();
if (isset($headers['X-CSRF-Token'])) { $csrfHeader = $headers['X-CSRF-Token']; }
if (!$csrfHeader && isset($headers['X-Csrf-Token'])) { $csrfHeader = $headers['X-Csrf-Token']; }

// Temporary bypass toggle (only when explicitly requested)
$bypass = (isset($_POST['csrf_disable']) && $_POST['csrf_disable'] === '1') ||
          (isset($headers['X-CSRF-Bypass']) && $headers['X-CSRF-Bypass'] === '1');

if (!$bypass) {
    $providedCsrf = $csrfPost ?: ($csrfHeader ?: $csrfCookie);
    if (!$csrfSession || !$providedCsrf || !hash_equals((string)$csrfSession, (string)$providedCsrf)) {
        // Prepare a fresh token so the client can retry without a full reload
        $newToken = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $newToken;
        setcookie('csrf_token', $newToken, 0, '/', '', isset($_SERVER['HTTPS']), true);
        ob_clean();
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'code' => 'CSRF_INVALID',
            'message' => 'CSRF token validation failed',
            'csrf_token' => $newToken
        ]);
        exit;
    }
}

// Rate Limiting (simple implementation)
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateLimitFile = sys_get_temp_dir() . '/financial_request_rate_limit_' . md5($clientIP);
$currentTime = time();

if (file_exists($rateLimitFile)) {
    $lastSubmission = (int)file_get_contents($rateLimitFile);
    if (($currentTime - $lastSubmission) < 5) { // 5 seconds between submissions
        ob_clean();
        http_response_code(429);
        echo 'Rate limit exceeded. Please wait 5 seconds before submitting another request.';
        exit;
    }
}

// Update rate limit timestamp
file_put_contents($rateLimitFile, $currentTime);

// Form timestamp validation (prevent replay attacks)
$formTimestamp = (int)($_POST['form_timestamp'] ?? 0);
$currentTimestamp = time();
if (($currentTimestamp - $formTimestamp) > 3600) { // 1 hour expiry
    ob_clean();
    http_response_code(400);
    echo 'Form has expired. Please refresh and try again.';
    exit;
}

// Get edit ID to determine if this is an update operation
$editId = isset($_POST['__edit_id']) ? (int)$_POST['__edit_id'] : 0;

// Initialize database connection
$mysqlconn = connectionDB();
if (!$mysqlconn) {
    ob_clean();
    http_response_code(500);
    echo 'Database connection failed';
    exit;
}

// Initialize attachment handler
$attachmentHandler = new AttachmentHandler($mysqlconn);

// Input validation and sanitization functions
function normalize_string_or_null($value) {
    if ($value === null || $value === '') return null;
    return trim((string)$value);
}

function normalize_decimal_or_null($value) {
    if ($value === null || $value === '') return null;
    $cleaned = preg_replace('/[^0-9.-]/', '', (string)$value);
    return $cleaned === '' ? null : (float)$cleaned;
}

function normalize_array($value) {
    if (!is_array($value)) return [];
    return array_filter($value, function($v) { return $v !== null && $v !== ''; });
}

function validate_enum_value($value, $allowedValues) {
    if ($value === null || $value === '') return null;
    return in_array($value, $allowedValues, true) ? $value : null;
}

function validate_date_format($value) {
    if ($value === null || $value === '') return null;
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : null;
}

function normalize_boolean_int($value) {
    if ($value === null || $value === '') return 0;
    return in_array(strtolower((string)$value), ['1', 'true', 'yes', 'on'], true) ? 1 : 0;
}

// Validate and sanitize input data
$request_type = validate_enum_value($_POST['request_type'] ?? null, ['ERGR', 'ERL', 'RFP']);
$company = validate_enum_value($_POST['company'] ?? null, ['TLCI', 'MPAV', 'MPFF']);
$doc_type = normalize_string_or_null($_POST['doc_type'] ?? null);
$doc_number = normalize_string_or_null($_POST['doc_number'] ?? null);
$doc_date = validate_date_format($_POST['doc_date'] ?? null);

$cost_center_input = normalize_string_or_null($_POST['cost_center'] ?? null);
$cost_center = null;
if ($cost_center_input) {
	try {
		// Prefer validating department by company_code + department_code when company is known
		if ($company) {
			if ($stDept = mysqli_prepare($mysqlconn, 'SELECT department_code FROM department WHERE company_code = ? AND department_code = ? LIMIT 1')) {
				mysqli_stmt_bind_param($stDept, 'ss', $company, $cost_center_input);
				mysqli_stmt_execute($stDept);
				$rsDept = mysqli_stmt_get_result($stDept);
				if ($rowDept = mysqli_fetch_assoc($rsDept)) {
					$cost_center = (string)$rowDept['department_code'];
				}
				mysqli_stmt_close($stDept);
			}
		}
		// Fallback: validate by department_code only
		if ($cost_center === null) {
			if ($stDept2 = mysqli_prepare($mysqlconn, 'SELECT department_code FROM department WHERE department_code = ? LIMIT 1')) {
				mysqli_stmt_bind_param($stDept2, 's', $cost_center_input);
				mysqli_stmt_execute($stDept2);
				$rsDept2 = mysqli_stmt_get_result($stDept2);
				if ($rowDept2 = mysqli_fetch_assoc($rsDept2)) {
					$cost_center = (string)$rowDept2['department_code'];
				}
				mysqli_stmt_close($stDept2);
			}
		}
	} catch (Throwable $e) {
		// Leave $cost_center as null on error
	}
}

$expenditure_type = validate_enum_value($_POST['expenditure_type'] ?? null, ['capex', 'opex']);
$balance = normalize_decimal_or_null($_POST['balance'] ?? null);
$budget = normalize_decimal_or_null($_POST['budget'] ?? null);
$currency = validate_enum_value($_POST['currency'] ?? null, ['USD', 'EUR', 'JPY', 'GBP', 'AUD', 'CAD', 'CHF', 'CNY', 'PHP']);

// Payee: store only the vendor/payee name from input
$inputVendor = normalize_string_or_null($_POST['payee'] ?? null);
$payee = $inputVendor;
$amount_in_words = normalize_string_or_null($_POST['amount_words'] ?? null);
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
            if ($stCo = mysqli_prepare($mysqlconn, 'SELECT company_id FROM company WHERE company_code = ? LIMIT 1')) {
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
        if ($cost_center) {
            if ($stDp = mysqli_prepare($mysqlconn, 'SELECT department_id FROM department WHERE department_code = ? LIMIT 1')) {
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
        $ymd = date('ymd');
        $hh = date('H');
        $mi = date('i');
        $doc_number = $request_type . '-' . $prefix . $ymd . $hh . $mi;
    }

// Ensure doc_number is unique
try {
    if ($doc_number) {
        $chk = mysqli_prepare($mysqlconn, 'SELECT 1 FROM financial_requests WHERE doc_number = ? LIMIT 1');
        if ($chk) {
            $base = $doc_number;
            $next = 1;
            $final = $base;
            while (true) {
                mysqli_stmt_bind_param($chk, 's', $final);
                mysqli_stmt_execute($chk);
                $rs = mysqli_stmt_get_result($chk);
                if (!mysqli_fetch_assoc($rs)) {
                    $doc_number = $final;
                    break;
                }
                $final = $base . '-' . str_pad($next, 3, '0', STR_PAD_LEFT);
                $next++;
            }
            mysqli_stmt_close($chk);
        }
    }
} catch (Throwable $e) {
    // On any error, keep the generated doc number as-is
}

// Basic required check
if (!$request_type) {
    ob_clean();
    http_response_code(400);
    echo 'request_type is required';
    exit;
}

// Start transaction
mysqli_begin_transaction($mysqlconn);

try {
    // Validate edit ID for update operation
    if ($editId <= 0) {
        throw new Exception('Invalid edit ID provided for update operation');
    }

    // Verify the financial request exists and get current data
    $checkSql = "SELECT id, doc_number FROM financial_requests WHERE id = ? LIMIT 1";
    $checkStmt = mysqli_prepare($mysqlconn, $checkSql);
    if (!$checkStmt) {
        throw new Exception('Failed to prepare financial_requests check: ' . mysqli_error($mysqlconn));
    }
    
    mysqli_stmt_bind_param($checkStmt, 'i', $editId);
    mysqli_stmt_execute($checkStmt);
    $result = mysqli_stmt_get_result($checkStmt);
    $existingRecord = mysqli_fetch_assoc($result);
    mysqli_stmt_close($checkStmt);
    
    if (!$existingRecord) {
        throw new Exception('Financial request not found for update');
    }
    
    $financial_request_id = $existingRecord['id'];
    $existing_doc_number = $existingRecord['doc_number'];

    // Update main financial request (don't update doc_number as it's a unique identifier)
    $sql = "UPDATE financial_requests SET 
        request_type = ?, company = ?, doc_type = ?, doc_date = ?,
        cost_center = ?, expenditure_type = ?, balance = ?, budget = ?, currency = ?,
        amount_figures = ?, payee = ?, amount_in_words = ?, payment_for = ?, special_instructions = ?,
        is_budgeted = ?, from_company = ?, to_company = ?, credit_to_payroll = ?, issue_check = ?,
        updated_at = NOW()
        WHERE id = ?";

    $stmt = mysqli_prepare($mysqlconn, $sql);
if (!$stmt) {
        throw new Exception('Failed to prepare financial_requests update: ' . mysqli_error($mysqlconn));
    }

    $is_budgeted_str = $is_budgeted === 'yes' ? '1' : ($is_budgeted === 'no' ? '0' : null);
    $amount_in_figures_str = $amount_in_figures ? number_format($amount_in_figures, 2, '.', '') : null;

    mysqli_stmt_bind_param($stmt, 'sssssssssssssssssssi',
    $request_type,
    $company,
    $doc_type,
    $doc_date,
    $cost_center,
    $expenditure_type,
        $balance,
        $budget,
    $currency,
    $amount_in_figures_str,
    $payee,
    $amount_in_words,
    $payment_for,
    $special_instructions,
    $is_budgeted_str,
    $from_company,
    $to_company,
    $credit_to_payroll,
    $issue_check,
    $financial_request_id
);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to execute financial_requests update: ' . mysqli_error($mysqlconn));
    }

    mysqli_stmt_close($stmt);

    // Handle item data
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

    // Get existing items to preserve them and their attachments
    $existingItemsSql = "SELECT id FROM financial_request_items WHERE doc_number = ? ORDER BY id";
    $existingItemsStmt = mysqli_prepare($mysqlconn, $existingItemsSql);
    $existingItemIds = [];
    if ($existingItemsStmt) {
        mysqli_stmt_bind_param($existingItemsStmt, 's', $existing_doc_number);
        mysqli_stmt_execute($existingItemsStmt);
        $result = mysqli_stmt_get_result($existingItemsStmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $existingItemIds[] = $row['id'];
        }
        mysqli_stmt_close($existingItemsStmt);
    }

    // Breakdown functionality has been removed - data is now handled through items table

    // Get the maximum count of item arrays
    $n = max(
        count($items_category),
        count($items_description),
        count($items_reference_number),
        count($items_po_number),
        count($items_due_date),
        count($items_cash_advance),
        count($items_form_of_payment),
        count($items_gross_amount),
        count($items_vatable),
        count($items_vat_amount),
        count($items_withholding_tax),
        count($items_amount_withhold),
        count($items_net_payable),
        count($items_carf_no),
        count($items_pcv_no),
        count($items_invoice_date),
        count($items_invoice_number),
        count($items_supplier_name),
        count($items_tin),
        count($items_address)
    );

    // Handle items - update existing ones and add new ones to preserve attachments
    $item_ids = [];
    for ($i = 0; $i < $n; $i++) {
        $cat = normalize_string_or_null($items_category[$i] ?? null);
        $desc = normalize_string_or_null($items_description[$i] ?? null);
        $ref = normalize_string_or_null($items_reference_number[$i] ?? null);
        $po = normalize_string_or_null($items_po_number[$i] ?? null);
        $due = validate_date_format($items_due_date[$i] ?? null);
        $cash = validate_enum_value($items_cash_advance[$i] ?? null, ['yes','no']);
        $cashInt = $cash === 'yes' ? 1 : 0;
        $fop = validate_enum_value($items_form_of_payment[$i] ?? null, ['corporate_check','wire_transfer','cash','manager_check','credit_to_account','others']);
        $gross = normalize_decimal_or_null($items_gross_amount[$i] ?? null);
        $vatable = validate_enum_value($items_vatable[$i] ?? null, ['yes','no']);
        $vat = normalize_decimal_or_null($items_vat_amount[$i] ?? null);
        $withholding = normalize_string_or_null($items_withholding_tax[$i] ?? null);
        $withhold = normalize_decimal_or_null($items_amount_withhold[$i] ?? null);
        $net = normalize_decimal_or_null($items_net_payable[$i] ?? null);
        $carf = normalize_string_or_null($items_carf_no[$i] ?? null);
        $pcv = normalize_string_or_null($items_pcv_no[$i] ?? null);
        $inv_date = normalize_string_or_null($items_invoice_date[$i] ?? null);
        $inv_num = normalize_string_or_null($items_invoice_number[$i] ?? null);
        $supplier = normalize_string_or_null($items_supplier_name[$i] ?? null);
        $tin = normalize_string_or_null($items_tin[$i] ?? null);
        $address = normalize_string_or_null($items_address[$i] ?? null);

        // Check if we have meaningful data for this item
        $hasData = ($cat || $desc || $ref || $po || $due || ($cash !== null) || ($fop !== null) || 
                   ($gross !== null) || ($vatable !== null) || ($vat !== null) || ($withholding !== null) || 
                   ($withhold !== null) || ($net !== null) || $carf || $pcv || $inv_date || $inv_num || 
                   $supplier || $tin || $address);

        if ($hasData) {
            // Map category_code to category_id for storage
            if ($cat) {
                try {
                    if ($stCat = mysqli_prepare($mysqlconn, 'SELECT category_id FROM categories WHERE category_code = ? LIMIT 1')) {
                        mysqli_stmt_bind_param($stCat, 's', $cat);
                        mysqli_stmt_execute($stCat);
                        $rsCat = mysqli_stmt_get_result($stCat);
                        if ($rowCat = mysqli_fetch_assoc($rsCat)) {
                            $categoryId = (string)($rowCat['category_id'] ?? '');
                            if ($categoryId !== '') { $cat = $categoryId; }
                        }
                        mysqli_stmt_close($stCat);
                    }
                } catch (Throwable $e) { /* ignore mapping errors */ }
            }

            $gross_str = is_null($gross) ? null : number_format($gross, 2, '.', '');
            $vat_str = is_null($vat) ? null : number_format($vat, 2, '.', '');
            $withhold_str = is_null($withhold) ? null : number_format($withhold, 2, '.', '');
            $net_str = is_null($net) ? null : number_format($net, 2, '.', '');

            // Check if we have an existing item ID to update
            $existingItemId = isset($existingItemIds[$i]) ? $existingItemIds[$i] : null;
            
            if ($existingItemId) {
                // Update existing item to preserve attachments
                $sql = "UPDATE financial_request_items SET 
                    category = ?, description = ?, reference_number = ?, po_number = ?, due_date = ?, 
                    cash_advance = ?, form_of_payment = ?, gross_amount = ?, vatable = ?, vat_amount = ?, 
                    withholding_tax = ?, amount_withhold = ?, net_payable_amount = ?, carf_no = ?, pcv_no = ?, 
                    invoice_date = ?, invoice_number = ?, supplier_name = ?, tin = ?, address = ?,
                    updated_at = NOW()
                    WHERE id = ?";

                $stmt = mysqli_prepare($mysqlconn, $sql);
                if (!$stmt) {
                    throw new Exception('Failed to prepare financial_request_items update: ' . mysqli_error($mysqlconn));
                }

                mysqli_stmt_bind_param($stmt, 'sssssissssssssssssssi',
                    $cat, $desc, $ref, $po, $due, $cashInt, $fop,
                    $gross_str, $vatable, $vat_str, $withholding, $withhold_str, $net_str,
                    $carf, $pcv, $inv_date, $inv_num, $supplier, $tin, $address, $existingItemId
                );

                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception('Failed to execute financial_request_items update: ' . mysqli_error($mysqlconn));
                }

                $item_ids[] = $existingItemId;
                mysqli_stmt_close($stmt);
            } else {
                // Insert new item
                $sql = "INSERT INTO financial_request_items (
                    doc_number, category, description, reference_number, po_number, due_date, 
                    cash_advance, form_of_payment, gross_amount, vatable, vat_amount, 
                    withholding_tax, amount_withhold, net_payable_amount, carf_no, pcv_no, 
                    invoice_date, invoice_number, supplier_name, tin, address
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = mysqli_prepare($mysqlconn, $sql);
                if (!$stmt) {
                    throw new Exception('Failed to prepare financial_request_items insert: ' . mysqli_error($mysqlconn));
                }

                mysqli_stmt_bind_param($stmt, 'ssssssissssssssssssss',
                    $existing_doc_number, $cat, $desc, $ref, $po, $due, $cashInt, $fop,
                    $gross_str, $vatable, $vat_str, $withholding, $withhold_str, $net_str,
                    $carf, $pcv, $inv_date, $inv_num, $supplier, $tin, $address
                );

                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception('Failed to execute financial_request_items insert: ' . mysqli_error($mysqlconn));
                }

                $item_id = mysqli_insert_id($mysqlconn);
                $item_ids[] = $item_id;
                mysqli_stmt_close($stmt);
            }
        }
    }

    // Validate that at least one item detail exists
    if (empty($item_ids)) {
        // Rollback transaction before returning
        mysqli_rollback($mysqlconn);
        ob_clean();
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Please add at least one item to continue. Item details are required before updating the financial request.',
            'type' => 'warning'
        ]);
        mysqli_close($mysqlconn);
        exit;
    }

    // Delete any remaining items that were not updated (if we have fewer items now)
    if (count($item_ids) < count($existingItemIds)) {
        $remainingIds = array_slice($existingItemIds, count($item_ids));
        if (!empty($remainingIds)) {
            $placeholders = str_repeat('?,', count($remainingIds) - 1) . '?';
            $deleteRemainingSql = "DELETE FROM financial_request_items WHERE id IN ($placeholders)";
            $deleteRemainingStmt = mysqli_prepare($mysqlconn, $deleteRemainingSql);
            if ($deleteRemainingStmt) {
                mysqli_stmt_bind_param($deleteRemainingStmt, str_repeat('i', count($remainingIds)), ...$remainingIds);
                mysqli_stmt_execute($deleteRemainingStmt);
                mysqli_stmt_close($deleteRemainingStmt);
            }
        }
    }

    // Handle supporting documents
    if (isset($_FILES['supporting_document']) && !empty($_FILES['supporting_document']['name'])) {
        // Check if any files were actually selected (not just empty file inputs)
        $hasFiles = false;
        foreach ($_FILES['supporting_document']['name'] as $fileName) {
            if (!empty($fileName)) {
                $hasFiles = true;
                            break;
            }
        }
        
        if ($hasFiles) {
            // For updates, always insert new attachments (don't update existing ones)
            $result = $attachmentHandler->uploadAttachments($existing_doc_number, $_FILES['supporting_document'], 'supporting_document');
            if (!$result['success']) {
                throw new Exception('Failed to upload supporting documents: ' . implode(', ', $result['errors']));
            }
        }
    }

    // Handle item attachments - now properly associated with specific items
    $itemAttachmentProcessed = false;
    $debugInfo = [];
    
    // Debug: Log the complete $_FILES structure
    $debugInfo[] = "Complete _FILES structure: " . json_encode($_FILES);
    
    // First, collect all files for each item
    $itemFiles = [];
    
    // Look for file inputs with names like items_attachment_item_0, items_ap_attachment_item_0, items_cv_attachment_item_0, etc.
    // Also look for additional files like items_attachment_item_1_additional_0, etc.
    foreach ($_FILES as $inputName => $fileData) {
        if (preg_match('/^items_(attachment|ap_attachment|cv_attachment)_item_(\d+)(_additional_\d+)?$/', $inputName, $matches)) {
            $attachmentType = $matches[1]; // attachment, ap_attachment, or cv_attachment
            $itemIndex = (int)$matches[2];
            $isAdditional = !empty($matches[3]); // Check if this is an additional file
            
            // Debug: Collect debug information
            $debugInfo[] = "Found file input: $inputName for item index $itemIndex" . ($isAdditional ? " (additional)" : "");
            $debugInfo[] = "File data type: " . gettype($fileData['name']);
            $debugInfo[] = "File data value: " . json_encode($fileData['name']);
            
            // Check if any files were actually selected (not just empty file inputs)
            $hasFiles = false;
            if (isset($fileData['name'])) {
                if (is_array($fileData['name'])) {
                    foreach ($fileData['name'] as $fileName) {
                        if (!empty($fileName)) {
                            $hasFiles = true;
                            break;
                        }
                    }
                } else {
                    $hasFiles = !empty($fileData['name']);
                }
            }
            
            if ($hasFiles && isset($item_ids[$itemIndex])) {
                // Initialize item files array if not exists
                if (!isset($itemFiles[$itemIndex])) {
                    $itemFiles[$itemIndex] = [];
                }
                
                // Initialize attachment type array if not exists
                if (!isset($itemFiles[$itemIndex][$attachmentType])) {
                    $itemFiles[$itemIndex][$attachmentType] = [
                        'name' => [],
                        'type' => [],
                        'tmp_name' => [],
                        'error' => [],
                        'size' => []
                    ];
                }
                
                // Add files to the item's collection for this attachment type
                if (is_array($fileData['name'])) {
                    $itemFiles[$itemIndex][$attachmentType]['name'] = array_merge($itemFiles[$itemIndex][$attachmentType]['name'], $fileData['name']);
                    $itemFiles[$itemIndex][$attachmentType]['type'] = array_merge($itemFiles[$itemIndex][$attachmentType]['type'], $fileData['type']);
                    $itemFiles[$itemIndex][$attachmentType]['tmp_name'] = array_merge($itemFiles[$itemIndex][$attachmentType]['tmp_name'], $fileData['tmp_name']);
                    $itemFiles[$itemIndex][$attachmentType]['error'] = array_merge($itemFiles[$itemIndex][$attachmentType]['error'], $fileData['error']);
                    $itemFiles[$itemIndex][$attachmentType]['size'] = array_merge($itemFiles[$itemIndex][$attachmentType]['size'], $fileData['size']);
                } else {
                    $itemFiles[$itemIndex][$attachmentType]['name'][] = $fileData['name'];
                    $itemFiles[$itemIndex][$attachmentType]['type'][] = $fileData['type'];
                    $itemFiles[$itemIndex][$attachmentType]['tmp_name'][] = $fileData['tmp_name'];
                    $itemFiles[$itemIndex][$attachmentType]['error'][] = $fileData['error'];
                    $itemFiles[$itemIndex][$attachmentType]['size'][] = $fileData['size'];
                }
                
                $debugInfo[] = "Added files to item $itemIndex collection";
            }
        }
    }
    
    // Now process each item's collected files
    foreach ($itemFiles as $itemIndex => $attachmentTypes) {
        $itemId = $item_ids[$itemIndex];
        
        $debugInfo[] = "Processing item index $itemIndex with itemId $itemId";
        
        // Process each attachment type for this item
        foreach ($attachmentTypes as $attachmentType => $fileData) {
            $debugInfo[] = "Processing attachment type: $attachmentType for item $itemIndex";
            $debugInfo[] = "Total files for this attachment type: " . count($fileData['name']);
            $debugInfo[] = "File names: " . implode(', ', $fileData['name']);
            
            // Map attachment type to database value
            $dbAttachmentType = 'item_attachment'; // default
            if ($attachmentType === 'ap_attachment') {
                $dbAttachmentType = 'item_ap_attachment';
            } elseif ($attachmentType === 'cv_attachment') {
                $dbAttachmentType = 'item_cv_attachment';
            }
            
            // For updates, always insert new attachments (don't update existing ones)
            $result = $attachmentHandler->uploadAttachments($existing_doc_number, $fileData, $dbAttachmentType, $itemId);
            if (!$result['success']) {
                throw new Exception('Failed to upload ' . $attachmentType . ' for item ' . $itemIndex . ': ' . implode(', ', $result['errors']));
            }
            
            $itemAttachmentProcessed = true;
        }
    }
    
    // Legacy support: Handle old items_attachment[] format if no new format was found
    if (!$itemAttachmentProcessed && isset($_FILES['items_attachment']) && !empty($_FILES['items_attachment']['name'])) {
        echo '<script>console.log("DEBUG: Using legacy items_attachment[] format");</script>';
        echo '<script>console.log("DEBUG: $_FILES[items_attachment] structure:", ' . json_encode($_FILES['items_attachment']) . ');</script>';
        
        // Check if any files were actually selected (not just empty file inputs)
        $hasFiles = false;
        foreach ($_FILES['items_attachment']['name'] as $fileName) {
            if (!empty($fileName)) {
                $hasFiles = true;
                break;
            }
        }
        
        if ($hasFiles) {
            // Fallback to old distribution logic
            $totalFiles = count($_FILES['items_attachment']['name']);
            $totalItems = count($item_ids);
            
            if ($totalItems > 0) {
                $filesPerItem = floor($totalFiles / $totalItems);
                $remainingFiles = $totalFiles % $totalItems;
                
                echo '<script>console.log("DEBUG: Distributing ' . $totalFiles . ' files among ' . $totalItems . ' items (legacy mode)");</script>';
                
                $fileIndex = 0;
                
                for ($itemIndex = 0; $itemIndex < $totalItems; $itemIndex++) {
                    $itemId = $item_ids[$itemIndex];
                    
                    // Calculate how many files this item should get
                    $filesForThisItem = $filesPerItem + ($itemIndex < $remainingFiles ? 1 : 0);
                    
                    if ($filesForThisItem > 0) {
                        // Extract files for this item
                        $itemFileNames = array_slice($_FILES['items_attachment']['name'], $fileIndex, $filesForThisItem);
                        $itemTypes = array_slice($_FILES['items_attachment']['type'], $fileIndex, $filesForThisItem);
                        $itemTmpNames = array_slice($_FILES['items_attachment']['tmp_name'], $fileIndex, $filesForThisItem);
                        $itemErrors = array_slice($_FILES['items_attachment']['error'], $fileIndex, $filesForThisItem);
                        $itemSizes = array_slice($_FILES['items_attachment']['size'], $fileIndex, $filesForThisItem);
                        
                        // Create attachment data for this item
                        $itemAttachmentData = [
                            'name' => $itemFileNames,
                            'type' => $itemTypes,
                            'tmp_name' => $itemTmpNames,
                            'error' => $itemErrors,
                            'size' => $itemSizes
                        ];
                        
                        // Debug: Log what we're sending for this item
                        echo '<script>console.log("DEBUG: Processing item ' . $itemIndex . ' with itemId ' . $itemId . ', files:", ' . json_encode($itemFileNames) . ');</script>';
                        
                        // For updates, always insert new attachments (don't update existing ones)
                        $result = $attachmentHandler->uploadAttachments($existing_doc_number, $itemAttachmentData, 'item_attachment', $itemId);
                        if (!$result['success']) {
                            throw new Exception('Failed to upload attachments for item ' . $itemIndex . ': ' . implode(', ', $result['errors']));
                        }
                        
                        $fileIndex += $filesForThisItem;
                    }
                }
            }
        }
    }

    // Breakdown functionality has been removed - data is now handled through items table

    // For updates, skip workflow process creation as it should already exist
    // The workflow process is not modified during updates to preserve approval history


    // Commit transaction
    mysqli_commit($mysqlconn);

    // Send success response
    ob_clean(); // Clear any output that might have been generated
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Financial request updated successfully',
        'doc_number' => $existing_doc_number,
        'financial_request_id' => $financial_request_id,
        'debug_info' => $debugInfo
    ]);

} catch (Exception $e) {
    // Rollback transaction
    mysqli_rollback($mysqlconn);
    
    // Log error
    error_log('Financial request update error: ' . $e->getMessage());
    
    // Send error response
    ob_clean(); // Clear any output that might have been generated
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update financial request: ' . $e->getMessage()
    ]);
}
?>
