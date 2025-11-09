<?php
// Save Financial Request (RFP/ERL/ERGR) - New Version with Attachment System
// Expects POST from disbursement_form.php

// Start output buffering to prevent HTML from being sent before JSON response
ob_start();

// Debug system - create debug array to store debug information
// $debug_info = [];
// function addDebug($message, $data = null) {
//     global $debug_info;
//     $debug_entry = [
//         'timestamp' => date('Y-m-d H:i:s'),
//         'message' => $message,
//         'data' => $data
//     ];
//     $debug_info[] = $debug_entry;
// }

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

// CSRF Protection
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    ob_clean();
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

// Payee: store vendor/payee name from input
$inputVendor = normalize_string_or_null($_POST['payee'] ?? null);
$payee = $inputVendor ?: 'Requestor'; // Store actual vendor/payee name, fallback to 'Requestor'
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
    // Insert main financial request
$sql = "INSERT INTO financial_requests (
    request_type, company, doc_type, doc_number, doc_date,
    cost_center, expenditure_type, balance, budget, currency,
    amount_figures, payee, amount_in_words, payment_for, special_instructions,
        is_budgeted, from_company, to_company, credit_to_payroll, issue_check,
    created_by_user_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($mysqlconn, $sql);
if (!$stmt) {
        throw new Exception('Failed to prepare financial_requests insert: ' . mysqli_error($mysqlconn));
    }

    $is_budgeted_str = $is_budgeted === 'yes' ? '1' : ($is_budgeted === 'no' ? '0' : null);
    $amount_in_figures_str = $amount_in_figures ? number_format($amount_in_figures, 2, '.', '') : null;
    $created_by_user_id = (int)($_SESSION['userid'] ?? 0);

    mysqli_stmt_bind_param($stmt, 'sssssssssssssssssssss',
    $request_type,
    $company,
    $doc_type,
    $doc_number,
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
    $created_by_user_id
);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to execute financial_requests insert: ' . mysqli_error($mysqlconn));
    }

    $financial_request_id = mysqli_insert_id($mysqlconn);
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

    // Insert items
    $item_ids = [];
    // addDebug('About to process ' . $n . ' items');
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
        
        // Also check if this item has file attachments
        $hasAttachments = false;
        if (isset($_FILES['items_attachment']['name'][$i])) {
            $itemFiles = $_FILES['items_attachment']['name'][$i];
            if (is_array($itemFiles)) {
                foreach ($itemFiles as $singleFile) {
                    if (!empty($singleFile)) {
                        $hasAttachments = true;
                        break;
                    }
                }
            } else {
                $hasAttachments = !empty($itemFiles);
            }
        }
        
        // Create item if it has data OR attachments
        $shouldCreateItem = $hasData || $hasAttachments;
        

        if ($shouldCreateItem) {
            // If item has attachments but no description, provide a default description
            if ($hasAttachments && !$desc) {
                $desc = 'Item with attachments';
            }
            
            // Map category_code to sap_account_code for storage
        if ($cat) {
            try {
                    if ($stCat = mysqli_prepare($mysqlconn, 'SELECT sap_account_code FROM categories WHERE category_code = ? LIMIT 1')) {
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

            $gross_str = is_null($gross) ? null : number_format($gross, 2, '.', '');
            $vat_str = is_null($vat) ? null : number_format($vat, 2, '.', '');
            $withhold_str = is_null($withhold) ? null : number_format($withhold, 2, '.', '');
            $net_str = is_null($net) ? null : number_format($net, 2, '.', '');

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
                $doc_number, $cat, $desc, $ref, $po, $due, $cashInt, $fop,
                $gross_str, $vatable, $vat_str, $withholding, $withhold_str, $net_str,
                $carf, $pcv, $inv_date, $inv_num, $supplier, $tin, $address
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to execute financial_request_items insert: ' . mysqli_error($mysqlconn));
            }

            $item_id = mysqli_insert_id($mysqlconn);
            $item_ids[] = $item_id;
            // addDebug('Created item ' . $i . ' with ID: ' . $item_id);
            mysqli_stmt_close($stmt);
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
            $result = $attachmentHandler->uploadAttachments($doc_number, $_FILES['supporting_document'], 'supporting_document');
            if (!$result['success']) {
                throw new Exception('Failed to upload supporting documents: ' . implode(', ', $result['errors']));
            }
        }
    }

    // Handle item attachments - properly indexed by item with enhanced multiple file support
    if (isset($_FILES['items_attachment']) && !empty($_FILES['items_attachment']['name'])) {
        
        // Debug: Log the entire $_FILES structure for items_attachment
        // addDebug('$_FILES[items_attachment] structure', $_FILES['items_attachment']);
        
        // The issue: When multiple files are selected in a single input, they get flattened
        // into a single array instead of being grouped by input field.
        // We need to reconstruct the proper grouping based on the number of items created.
        
        $totalFiles = count($_FILES['items_attachment']['name']);
        $totalItems = count($item_ids);
        // addDebug('Total files received', $totalFiles);
        // addDebug('Total items created', $totalItems);
        
        // Check if any files were actually selected
        $hasFiles = false;
        foreach ($_FILES['items_attachment']['name'] as $fileName) {
            if (!empty($fileName)) {
                $hasFiles = true;
                break;
            }
        }
        
        if ($hasFiles && $totalItems > 0) {
            // The issue: Files are sent in a flat array but we need to group them by item
            // We need to reconstruct the proper grouping based on the frontend data
            
            // Let's try to get the file count per item from the frontend
            // We'll look for hidden inputs or use a more intelligent approach
            
            $filesPerItem = [];
            
            // Method 1: Try to get file counts from POST data
            $itemFileCounts = [];
            if (isset($_POST['item_file_counts'])) {
                $itemFileCounts = json_decode($_POST['item_file_counts'], true);
                // addDebug('Item file counts from POST', $itemFileCounts);
            }
            
            // Method 2: If no POST data, try to infer from the file names
            // This is a fallback method
            if (empty($itemFileCounts)) {
                // For now, let's use a simple distribution
                // This is not ideal but will work for testing
                $filesPerItem = [];
                $currentItemIndex = 0;
                $filesForCurrentItem = [];
                
                // Simple approach: distribute files evenly
                $filesPerItemCount = ceil($totalFiles / $totalItems);
                
                for ($i = 0; $i < $totalFiles; $i++) {
                    $fileName = $_FILES['items_attachment']['name'][$i];
                    
                    if (!empty($fileName)) {
                        $filesForCurrentItem[] = $i;
                        
                        // Move to next item when we have enough files
                        if (count($filesForCurrentItem) >= $filesPerItemCount && $currentItemIndex < $totalItems - 1) {
                            $filesPerItem[$currentItemIndex] = $filesForCurrentItem;
                            $currentItemIndex++;
                            $filesForCurrentItem = [];
                        }
                    }
                }
                
                // Add remaining files to the last item
                if (!empty($filesForCurrentItem)) {
                    $filesPerItem[$currentItemIndex] = $filesForCurrentItem;
                }
            } else {
                // Use the file counts from POST data
                $currentFileIndex = 0;
                foreach ($itemFileCounts as $itemIndex => $fileCount) {
                    $filesForItem = [];
                    for ($j = 0; $j < $fileCount && $currentFileIndex < $totalFiles; $j++) {
                        if (!empty($_FILES['items_attachment']['name'][$currentFileIndex])) {
                            $filesForItem[] = $currentFileIndex;
                        }
                        $currentFileIndex++;
                    }
                    if (!empty($filesForItem)) {
                        $filesPerItem[$itemIndex] = $filesForItem;
                    }
                }
            }
            
            // addDebug('Files grouped by item', $filesPerItem);
            
            // Process each item's files
            foreach ($filesPerItem as $itemIndex => $fileIndices) {
                if (!isset($item_ids[$itemIndex])) {
                    // addDebug('Skipping item ' . $itemIndex . ' - no item_id');
                    continue;
                }
                
                $itemId = $item_ids[$itemIndex];
                // addDebug('Processing item ' . $itemIndex . ' with ' . count($fileIndices) . ' files (item_id: ' . $itemId . ')');
                
                // Create attachment data for this item
                $itemAttachmentData = [
                    'name' => [],
                    'type' => [],
                    'tmp_name' => [],
                    'error' => [],
                    'size' => []
                ];
                
                foreach ($fileIndices as $fileIndex) {
                    $itemAttachmentData['name'][] = $_FILES['items_attachment']['name'][$fileIndex];
                    $itemAttachmentData['type'][] = $_FILES['items_attachment']['type'][$fileIndex];
                    $itemAttachmentData['tmp_name'][] = $_FILES['items_attachment']['tmp_name'][$fileIndex];
                    $itemAttachmentData['error'][] = $_FILES['items_attachment']['error'][$fileIndex];
                    $itemAttachmentData['size'][] = $_FILES['items_attachment']['size'][$fileIndex];
                }
                
                // addDebug('Item ' . ($itemIndex + 1) . ' attachment data', $itemAttachmentData);
                
                // Upload all files for this item
                $result = $attachmentHandler->uploadAttachments($doc_number, $itemAttachmentData, 'item_attachment', $itemId);
                
                // addDebug('uploadAttachments result for item ' . ($itemIndex + 1), $result);
                
                if (!$result['success']) {
                    // addDebug('Upload failed for item ' . ($itemIndex + 1), $result['errors']);
                    throw new Exception('Failed to upload item attachments for item ' . ($itemIndex + 1) . ': ' . implode(', ', $result['errors']));
                }
                
                // Log successful upload
                if (isset($result['uploaded_files'])) {
                    // addDebug('Successfully uploaded ' . count($result['uploaded_files']) . ' files for item ' . ($itemIndex + 1));
                    foreach ($result['uploaded_files'] as $uploadedFile) {
                        // addDebug('Uploaded file: ' . $uploadedFile['original_filename']);
                    }
                } else {
                    // addDebug('No uploaded_files in result for item ' . ($itemIndex + 1));
                }
            }
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
    if ($stmtTpl = mysqli_prepare($mysqlconn, $sqlTpl)) {
        mysqli_stmt_bind_param($stmtTpl, 'sss', $request_type, $company, $cost_center);
        mysqli_stmt_execute($stmtTpl);
        $rsTpl = mysqli_stmt_get_result($stmtTpl);
        while ($row = mysqli_fetch_assoc($rsTpl)) {
            $tplRows[] = $row;
        }
        mysqli_stmt_close($stmtTpl);
    }

    // Ensure we have sequence 2 (Cost Center Head) - try to fetch if missing
    $hasSeq2 = false;
    foreach ($tplRows as $r) { if ((int)$r['sequence'] === 2) { $hasSeq2 = true; break; } }
    if (!$hasSeq2) {
        // Try to fetch a sequence 2 row specifically (best match on department, else global)
        $sqlCH = "SELECT sequence, actor_id, action, is_parellel
                  FROM work_flow_template
                  WHERE work_flow_id = ? AND company = ? AND sequence = 2
                    AND (department = ? OR department = '' OR department IS NULL)
                  ORDER BY CASE WHEN department = ? THEN 0 ELSE 1 END, id LIMIT 1";
        if ($stCH = mysqli_prepare($mysqlconn, $sqlCH)) {
            mysqli_stmt_bind_param($stCH, 'ssss', $request_type, $company, $cost_center, $cost_center);
            mysqli_stmt_execute($stCH);
            $rsCH = mysqli_stmt_get_result($stCH);
            if ($rowCH = mysqli_fetch_assoc($rsCH)) {
                $tplRows[] = $rowCH;
                $hasSeq2 = true;
            }
            mysqli_stmt_close($stCH);
        }
    }

    // Sort template rows by sequence
        usort($tplRows, function($a, $b) {
        $sa = (int)($a['sequence'] ?? 0);
        $sb = (int)($b['sequence'] ?? 0);
            return $sa <=> $sb;
        });

    // Prepare insert for process - Create sequence 1 (Requestor) and sequence 2 (Cost Center Head)
    // Get workflow type ID for the new work_flow_type_id column
    $workflowTypeId = getWorkflowTypeId($mysqlconn, $request_type);
    
    if ($workflowTypeId) {
        // Use new format with work_flow_type_id
        $insProc = mysqli_prepare($mysqlconn, "INSERT INTO work_flow_process (doc_type, work_flow_type_id, doc_number, sequence, actor_id, action, status) VALUES (?,?,?,?,?,?,?)");
    } else {
        // Fallback to old format without work_flow_type_id
        $insProc = mysqli_prepare($mysqlconn, "INSERT INTO work_flow_process (doc_type, doc_number, sequence, actor_id, action, status) VALUES (?,?,?,?,?,?)");
    }
    
    if ($insProc) {
        // Create sequence 1 (Requestor) with 'Submitted' status
        $seq1Rows = array_filter($tplRows, function($row) {
            return (int)($row['sequence'] ?? 0) === 1;
        });
        
        if (!empty($seq1Rows)) {
            foreach ($seq1Rows as $row) {
                $seq = (int)($row['sequence'] ?? 1);
                $action = (string)($row['action'] ?? '');
                
                // For Requestor, use the full name from session
                if (strcasecmp($action, 'Requestor') === 0) {
                    // Prefer full name, fallback to username, then to user ID, then to creator name
                    $fullName = '';
                    if (isset($_SESSION['userfirstname']) || isset($_SESSION['userlastname'])) {
                        $fullName = trim((string)($_SESSION['userfirstname'] ?? '').' '.(string)($_SESSION['userlastname'] ?? ''));
                    }
                    $actor = $fullName ?: (isset($_SESSION['username']) ? (string)$_SESSION['username'] : 
                             (isset($_SESSION['userid']) ? (string)$_SESSION['userid'] : $creatorName));
                } else {
                    $actor = (string)($row['actor_id'] ?? '');
                }
                
                // Ensure actor is not null or empty
                if (!$actor || $actor === '') {
                    $actor = 'Requestor'; // Default fallback
                }
                $status = 'Submit'; // Sequence 1 starts as 'Submit'
                
                if ($workflowTypeId) {
                    mysqli_stmt_bind_param($insProc, 'sisisss', $request_type, $workflowTypeId, $doc_number, $seq, $actor, $action, $status);
                } else {
                    mysqli_stmt_bind_param($insProc, 'ssisss', $request_type, $doc_number, $seq, $actor, $action, $status);
                }
                mysqli_stmt_execute($insProc);
            }
        } else {
            // Fallback - create Requestor entry with actual user info
            // Prefer full name, fallback to username, then to user ID, then to creator name
            $fullName = '';
            if (isset($_SESSION['userfirstname']) || isset($_SESSION['userlastname'])) {
                $fullName = trim((string)($_SESSION['userfirstname'] ?? '').' '.(string)($_SESSION['userlastname'] ?? ''));
            }
            $actor = $fullName ?: (isset($_SESSION['username']) ? (string)$_SESSION['username'] : 
                     (isset($_SESSION['userid']) ? (string)$_SESSION['userid'] : $creatorName));
            
            // Ensure actor is not null or empty
            if (!$actor || $actor === '') {
                $actor = 'Requestor'; // Default fallback
            }
            $action = 'Requestor';
            $status = 'Submit';
            
            if ($workflowTypeId) {
                mysqli_stmt_bind_param($insProc, 'sisisss', $request_type, $workflowTypeId, $doc_number, 1, $actor, $action, $status);
            } else {
                mysqli_stmt_bind_param($insProc, 'ssisss', $request_type, $doc_number, 1, $actor, $action, $status);
            }
            mysqli_stmt_execute($insProc);
        }
        
        // Create sequence 2 (Cost Center Head) with 'Waiting for Approval' status
        $seq2Rows = array_filter($tplRows, function($row) {
            return (int)($row['sequence'] ?? 0) === 2;
        });
        
        if (!empty($seq2Rows)) {
            foreach ($seq2Rows as $row) {
                $seq = (int)($row['sequence'] ?? 2);
                $actor = (string)($row['actor_id'] ?? '');
                $action = (string)($row['action'] ?? '');
                
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
			$seq = 2;
            if ($workflowTypeId) {
				mysqli_stmt_bind_param($insProc, 'sisisss', $request_type, $workflowTypeId, $doc_number, $seq, $actor, $action, $status);
            } else {
				mysqli_stmt_bind_param($insProc, 'ssisss', $request_type, $doc_number, $seq, $actor, $action, $status);
            }
            mysqli_stmt_execute($insProc);
        }
        
        mysqli_stmt_close($insProc);
    }

    // Store notification data for background processing (outside the if block)
    $notificationData = [
        'request_type' => $request_type,
        'doc_number' => $doc_number,
        'company' => $company,
        'cost_center' => $cost_center,
        'decider_ident' => $creatorName,
        'sequence' => 2,
        'action' => 'SUBMIT'
    ];
    
    // Get decider identifier for notification (prefer full name, fallback to username, then user ID)
    $fullName = '';
    if (isset($_SESSION['userfirstname']) || isset($_SESSION['userlastname'])) {
        $fullName = trim((string)($_SESSION['userfirstname'] ?? '').' '.(string)($_SESSION['userlastname'] ?? ''));
    }
    $notificationData['decider_ident'] = $fullName ?: (isset($_SESSION['username']) ? (string)$_SESSION['username'] : (isset($_SESSION['userid']) ? (string)$_SESSION['userid'] : $creatorName));

    // Commit transaction
    mysqli_commit($mysqlconn);

    // Log final summary
    // addDebug('FINAL SUMMARY - Financial request saved successfully');
    // addDebug('Doc number', $doc_number);
    // addDebug('Financial request ID', $financial_request_id);
    // addDebug('Total items created', count($item_ids));
    // addDebug('Item IDs', $item_ids);

    // Save debug information to file
    // $debug_file = 'debug_' . date('Y-m-d_H-i-s') . '.json';
    // file_put_contents($debug_file, json_encode($debug_info, JSON_PRETTY_PRINT));
    
    // Send success response
    ob_clean(); // Clear any output that might have been generated
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Financial request saved successfully',
        'doc_number' => $doc_number,
        'financial_request_id' => $financial_request_id,
        'notification_data' => $notificationData ?? null
    ]);

} catch (Exception $e) {
    // Rollback transaction
    mysqli_rollback($mysqlconn);
    
    // Add error to debug info
    // addDebug('ERROR: ' . $e->getMessage());
    
    // Save debug information to file
    // $debug_file = 'debug_error_' . date('Y-m-d_H-i-s') . '.json';
    // file_put_contents($debug_file, json_encode($debug_info, JSON_PRETTY_PRINT));
    
    // Send error response
    ob_clean(); // Clear any output that might have been generated
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save financial request: ' . $e->getMessage()
    ]);
} finally {
    // Close database connection
    if (isset($mysqlconn)) {
        mysqli_close($mysqlconn);
    }
}
?>
