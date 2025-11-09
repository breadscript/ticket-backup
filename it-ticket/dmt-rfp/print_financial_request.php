<?php
// Print/export financial request as PDF per request type (ERL, ERGR, RFP)
// Generates structured PDFs based on the template layouts

date_default_timezone_set('Asia/Manila');
include __DIR__ . '/blocks/inc.resource.php';

function h($val) { return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8'); }

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

$docTypeParam = isset($_GET['doc_type']) ? (string)$_GET['doc_type'] : '';
$docNumberParam = isset($_GET['doc_number']) ? (string)$_GET['doc_number'] : '';
if ($docNumberParam === '') {
  http_response_code(400);
  echo 'Invalid request.';
  exit;
}

// Fetch financial request header
$fr = null;
if (isset($mysqlconn) && $mysqlconn) {
  $stmt = mysqli_prepare($mysqlconn, "SELECT f.*, c.company_name, d.department_name, u.user_firstname, u.user_lastname
                                       FROM financial_requests f
                                       LEFT JOIN company c ON f.company = c.company_code
                                       LEFT JOIN department d ON f.cost_center = d.department_code
                                       LEFT JOIN sys_usertb u ON u.id = f.payee
                                       WHERE f.doc_number = ?");
  if ($stmt) {
    mysqli_stmt_bind_param($stmt, 's', $docNumberParam);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $fr = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
  }
}

// Function to get currency symbol based on currency code
function getCurrencySymbol($currencyCode) {
  $currencySymbols = [
    'PHP' => '₱',
    'USD' => '$',
    'EUR' => '€',
    'JPY' => '¥',
    'GBP' => '£',
    'AUD' => 'A$',
    'CAD' => 'C$',
    'CHF' => 'CHF',
    'CNY' => '¥'
  ];
  
  // Clean the currency code
  $currencyCode = trim(strtoupper($currencyCode));
  
  // Return the symbol if found, otherwise return the currency code
  return $currencySymbols[$currencyCode] ?? $currencyCode;
}

if (!$fr) {
  http_response_code(404);
  echo 'Record not found';
  exit;
}

// Access policy: allow payee, any actor in workflow, or admin
$__SESSION_USER = [
  'id' => (string)($_SESSION['userid'] ?? ''),
  'username' => (string)($_SESSION['username'] ?? ''),
  'first' => (string)($_SESSION['userfirstname'] ?? ''),
  'last' => (string)($_SESSION['userlastname'] ?? ''),
  'role' => (string)($_SESSION['userrole'] ?? ''),
];
$__SESSION_USER['full'] = trim($__SESSION_USER['first'].' '.$__SESSION_USER['last']);

function __matches_actor_p($actor, $sess) {
  $actor = (string)$actor;
  if ($actor === '') return false;
  if (strcasecmp($actor, $sess['full']) === 0) return true;
  if (strcasecmp($actor, $sess['username']) === 0) return true;
  if ((string)$actor === (string)$sess['id']) return true;
  if (strcasecmp($actor, $sess['first']) === 0) return true;
  if (strcasecmp($actor, $sess['last']) === 0) return true;
  if (strtoupper(str_replace(' ', '', $actor)) === strtoupper(str_replace(' ', '', $sess['full']))) return true;
  return false;
}

$canPrint = false;
// payee
if ((string)($fr['payee'] ?? '') !== '' && (string)$fr['payee'] === $__SESSION_USER['id']) {
  $canPrint = true;
}
// admin
if (!$canPrint && strtolower((string)$__SESSION_USER['role']) === 'admin') {
  $canPrint = true;
}
// fallback: if user has a valid session and the document exists, allow print (temporary fix)
if (!$canPrint && !empty($__SESSION_USER['id']) && !empty($fr['id'])) {
  $canPrint = true;
}
// workflow actor (process table)
if (!$canPrint && isset($mysqlconn) && $mysqlconn) {
  $chStmt = mysqli_prepare($mysqlconn, "SELECT actor_id FROM work_flow_process WHERE doc_type = ? AND doc_number = ?");
  if ($chStmt) {
    $rt = (string)($fr['request_type'] ?? '');
    $dn = (string)($fr['doc_number'] ?? '');
    mysqli_stmt_bind_param($chStmt, 'ss', $rt, $dn);
    mysqli_stmt_execute($chStmt);
    $rs = mysqli_stmt_get_result($chStmt);
    while ($row = mysqli_fetch_assoc($rs)) {
      if (__matches_actor_p($row['actor_id'] ?? '', $__SESSION_USER)) { $canPrint = true; break; }
    }
    mysqli_stmt_close($chStmt);
  }
}
// workflow actor (template table)
if (!$canPrint && isset($mysqlconn) && $mysqlconn) {
  $templateStmt = mysqli_prepare($mysqlconn, "SELECT COUNT(*) as count FROM work_flow_template 
                                             WHERE work_flow_id = ? AND company = ? 
                                             AND (
                                               actor_id = ? OR actor_id = ? OR actor_id = ? OR actor_id = ?
                                               OR REPLACE(UPPER(actor_id),' ','') = REPLACE(UPPER(?),' ','')
                                             )");
  if ($templateStmt) {
    $requestTypeTmp = (string)($fr['request_type'] ?? '');
    $companyTmp = (string)($fr['company'] ?? '');
    $username = (string)$__SESSION_USER['username'];
    $first = (string)$__SESSION_USER['first'];
    $last = (string)$__SESSION_USER['last'];
    $full = (string)$__SESSION_USER['full'];
    mysqli_stmt_bind_param($templateStmt, 'sssssss', $requestTypeTmp, $companyTmp, $username, $first, $last, $full, $full);
    mysqli_stmt_execute($templateStmt);
    $templateRes = mysqli_stmt_get_result($templateStmt);
    $templateRow = mysqli_fetch_assoc($templateRes);
    if ($templateRow && (int)$templateRow['count'] > 0) {
      $canPrint = true;
    }
    mysqli_stmt_close($templateStmt);
  }
}
// allow if no workflow records exist yet (mirrors approver view fallback)
if (!$canPrint && isset($mysqlconn) && $mysqlconn) {
  $checkStmt = mysqli_prepare($mysqlconn, "SELECT COUNT(*) as count FROM work_flow_process WHERE doc_type = ? AND doc_number = ?");
  if ($checkStmt) {
    $docTypeTmp = (string)($fr['request_type'] ?? '');
    $docNumTmp = (string)($fr['doc_number'] ?? '');
    mysqli_stmt_bind_param($checkStmt, 'ss', $docTypeTmp, $docNumTmp);
    mysqli_stmt_execute($checkStmt);
    $checkRes = mysqli_stmt_get_result($checkStmt);
    $checkRow = mysqli_fetch_assoc($checkRes);
    $hasWorkflowRecords = ($checkRow && (int)$checkRow['count'] > 0);
    mysqli_stmt_close($checkStmt);
    if (!$hasWorkflowRecords) { $canPrint = true; }
  }
}
// allow if coming from a validated view (clean URL session gate used by view pages)
if (!$canPrint) {
  $viewKey = 'dmt_view_id';
  $viewExp = 'dmt_view_exp';
  if (isset($_SESSION[$viewKey], $_SESSION[$viewExp]) && isset($fr['id']) && (int)$_SESSION[$viewKey] === (int)$fr['id'] && time() <= (int)$_SESSION[$viewExp]) {
    $canPrint = true;
  }
}

if (!$canPrint) {
  http_response_code(403);
  echo 'Forbidden';
  exit;
}

// Fetch items and breakdowns (optional for template use)
$items = [];
$breakdowns = [];
if (isset($mysqlconn) && $mysqlconn) {
  // Check which columns exist in the table to avoid column errors
  $tableColumns = [];
  try {
    $result = mysqli_query($mysqlconn, "DESCRIBE financial_request_items");
    if ($result) {
      while ($row = mysqli_fetch_assoc($result)) {
        $tableColumns[] = $row['Field'];
      }
      mysqli_free_result($result);
    }
  } catch (Throwable $e) {
    // Fallback to basic columns if DESCRIBE fails
    $tableColumns = ['category', 'description', 'amount', 'reference_number', 'po_number', 'due_date'];
  }

  // Build dynamic SELECT statement based on available columns
  $selectColumns = [];
  $coreColumns = ['category', 'description', 'amount', 'reference_number', 'po_number', 'due_date'];
  $taxColumns = ['gross_amount', 'vatable', 'vat_amount', 'withholding_tax', 'amount_withhold', 'net_payable_amount'];
  $erColumns = ['carf_no', 'pcv_no', 'invoice_date', 'invoice_number', 'supplier_name', 'tin', 'address'];

  // Add core columns
  foreach ($coreColumns as $column) {
    if (in_array($column, $tableColumns)) {
      $selectColumns[] = $column;
    }
  }

  // Add tax columns if they exist
  foreach ($taxColumns as $column) {
    if (in_array($column, $tableColumns)) {
      $selectColumns[] = $column;
    }
  }

  // Add ER columns if they exist
  foreach ($erColumns as $column) {
    if (in_array($column, $tableColumns)) {
      $selectColumns[] = $column;
    }
  }

  // Function to get SAP account description for category
  // Note: $categoryCode contains the category_id from categories table (or sap_account_code for legacy data)
  function getSapAccountDescription($mysqlconn, $categoryCode) {
    if (!$categoryCode || !$mysqlconn) return $categoryCode;
    
    try {
      // Try category_id first (new format)
      $stmt = mysqli_prepare($mysqlconn, 'SELECT sap_account_description FROM categories WHERE category_id = ? LIMIT 1');
      if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $categoryCode);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
          $description = (string)($row['sap_account_description'] ?? '');
          mysqli_stmt_close($stmt);
          return $description ?: $categoryCode;
        }
        mysqli_stmt_close($stmt);
      }
      
      // Fallback to sap_account_code for legacy data
      $stmt = mysqli_prepare($mysqlconn, 'SELECT sap_account_description FROM categories WHERE sap_account_code = ? LIMIT 1');
      if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $categoryCode);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
          $description = (string)($row['sap_account_description'] ?? '');
          mysqli_stmt_close($stmt);
          return $description ?: $categoryCode; // Return description if found, otherwise return original code
        }
        mysqli_stmt_close($stmt);
      }
    } catch (Throwable $e) {
      // Log error but don't fail
      error_log("Error looking up SAP account description for category '$categoryCode': " . $e->getMessage());
    }
    
    return $categoryCode; // Fallback to original code
  }

  if (!empty($selectColumns)) {
    $selectSQL = "SELECT " . implode(', ', $selectColumns) . " FROM financial_request_items WHERE doc_number = ? ORDER BY id";
    if ($stmtI = mysqli_prepare($mysqlconn, $selectSQL)) {
      mysqli_stmt_bind_param($stmtI, 's', $docNumberParam);
      mysqli_stmt_execute($stmtI);
      $ri = mysqli_stmt_get_result($stmtI);
      while ($r = mysqli_fetch_assoc($ri)) { $items[] = $r; }
      mysqli_stmt_close($stmtI);
    }
  }
  if ($stmtB = mysqli_prepare($mysqlconn, "SELECT reference_number AS reference_number_2, due_date AS `date`, net_payable_amount AS amount2 FROM financial_request_items WHERE doc_number = ? ORDER BY id")) {
    mysqli_stmt_bind_param($stmtB, 's', $docNumberParam);
    mysqli_stmt_execute($stmtB);
    $rb = mysqli_stmt_get_result($stmtB);
    while ($r = mysqli_fetch_assoc($rb)) { $breakdowns[] = $r; }
    mysqli_stmt_close($stmtB);
  }
}

$requestType = (string)($fr['request_type'] ?? '');

function generatePDF($requestType, $fr, $items, $breakdowns) {
  // Set HTML headers for print-friendly output
  header('Content-Type: text/html; charset=utf-8');
  header('Cache-Control: no-cache, must-revalidate');
  
  // Start output buffering
  ob_start();
  
  // Get the database connection from global scope
  global $mysqlconn;
  
  if ($requestType === 'ERL') {
    generateERL_PDF($fr, $items, $breakdowns, $mysqlconn);
  } elseif ($requestType === 'ERGR') {
    generateERGR_PDF($fr, $items, $breakdowns, $mysqlconn);
  } elseif ($requestType === 'RFP') {
    generateRFP_PDF($fr, $items, $breakdowns, $mysqlconn);
  } else {
    generateGeneric_PDF($fr, $items, $breakdowns, $mysqlconn);
  }
  
  $html = ob_get_clean();
  
  // Add print functionality to the HTML
  $html = addPrintFunctionality($html);
  
  // Output HTML that can be printed to PDF
  echo $html;
  return true;
}

function addPrintFunctionality($html) {
  // Check if download parameter is present
  $isDownload = isset($_GET['download']) && $_GET['download'] === '1';
  
  // Insert print button and JavaScript after the opening body tag
  $printButton = '
    <div style="position: fixed; top: 20px; right: 20px; z-index: 1000; background: #007bff; color: white; padding: 10px 20px; border-radius: 5px; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.2);" onclick="printDocument()">
      <i style="margin-right: 8px;">🖨️</i> Print to PDF
    </div>
    <div style="position: fixed; top: 70px; right: 20px; z-index: 1000; background: #28a745; color: white; padding: 10px 20px; border-radius: 5px; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.2);" onclick="downloadPDF()">
      <i style="margin-right: 8px;">📥</i> Download PDF
    </div>
    <script>
      function printDocument() {
        // Hide print buttons before printing
        const buttons = document.querySelectorAll("div[onclick]");
        buttons.forEach(btn => btn.style.display = "none");
        
        // Print the document
        window.print();
        
        // Show buttons again after printing
        setTimeout(() => {
          buttons.forEach(btn => btn.style.display = "block");
        }, 1000);
      }
      
      function downloadPDF() {
        // Hide print buttons
        const buttons = document.querySelectorAll("div[onclick]");
        buttons.forEach(btn => btn.style.display = "none");
        
        // Create a blob from the current page content
        const htmlContent = document.documentElement.outerHTML;
        const blob = new Blob([htmlContent], { type: \'text/html\' });
        
        // Create download link
        const downloadLink = document.createElement(\'a\');
        downloadLink.href = URL.createObjectURL(blob);
        downloadLink.download = \'financial_request_\' + new Date().getTime() + \'.html\';
        
        // Trigger download
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
        
        // Clean up blob URL
        setTimeout(() => {
          URL.revokeObjectURL(downloadLink.href);
        }, 1000);
        
        // Show buttons again
        setTimeout(() => {
          buttons.forEach(btn => btn.style.display = "block");
        }, 2000);
      }
      
      // Auto-print after page loads (disabled - handled by parent page)
      // window.onload = function() {
      //   setTimeout(() => {
      //     printDocument();
      //   }, 1000);
      // };
      
      // Auto-download if download parameter is present
      ' . ($isDownload ? '
      window.onload = function() {
        setTimeout(function() {
          downloadPDF();
        }, 1000);
      };
      ' : '') . '
    </script>
  ';
  
  // Insert the print button after the opening body tag
  $html = str_replace('<body>', '<body>' . $printButton, $html);
  
  return $html;
}

// Function to get approver sequence for a document
function getApproverSequence($mysqlconn, $requestType, $company, $department = '') {
  $approvers = [];
  
  if (!$mysqlconn) return $approvers;
  
  try {
    // Get workflow type ID
    $typeStmt = mysqli_prepare($mysqlconn, 'SELECT id FROM work_flow_type WHERE type_code = ? LIMIT 1');
    if ($typeStmt) {
      mysqli_stmt_bind_param($typeStmt, 's', $requestType);
      mysqli_stmt_execute($typeStmt);
      $typeResult = mysqli_stmt_get_result($typeStmt);
      $typeRow = mysqli_fetch_assoc($typeResult);
      $workflowTypeId = $typeRow ? $typeRow['id'] : null;
      mysqli_stmt_close($typeStmt);
      
      if ($workflowTypeId) {
        // Get approvers from workflow template
        $approverStmt = mysqli_prepare($mysqlconn, 
          'SELECT wft.sequence, wft.actor_id, wft.action, wft.department, u.user_firstname, u.user_lastname
           FROM work_flow_template wft
           LEFT JOIN sys_usertb u ON (u.username = wft.actor_id OR u.user_firstname = wft.actor_id OR u.user_lastname = wft.actor_id)
           WHERE wft.work_flow_type_id = ? AND wft.company = ? 
           AND (wft.department = ? OR wft.department = "" OR wft.global = 1)
           ORDER BY wft.sequence, wft.id');
        
        if ($approverStmt) {
          mysqli_stmt_bind_param($approverStmt, 'iss', $workflowTypeId, $company, $department);
          mysqli_stmt_execute($approverStmt);
          $approverResult = mysqli_stmt_get_result($approverStmt);
          
          while ($row = mysqli_fetch_assoc($approverResult)) {
            $sequence = (int)$row['sequence'];
            $actorId = $row['actor_id'];
            $action = $row['action'];
            $firstName = $row['user_firstname'] ?? '';
            $lastName = $row['user_lastname'] ?? '';
            $fullName = trim($firstName . ' ' . $lastName);
            
            // Skip if it's the requestor
            if ($action === 'Requestor') continue;
            
            // Get display name
            $displayName = $fullName ?: $actorId;
            
            // Map action to display title
            $title = '';
            switch ($action) {
              case 'Cost_Center_Head':
                $title = 'Cost Center Head';
                break;
              case 'Accounting_1':
                $title = 'Accounting';
                break;
              case 'Accounting_2':
                $title = 'Accounting';
                break;
              case 'Accounting_Manager':
                $title = 'Controller';
                break;
              case 'Cashier_Assistant':
                $title = 'Cashier';
                break;
              default:
                $title = $action;
            }
            
            $approvers[] = [
              'sequence' => $sequence,
              'name' => $displayName,
              'title' => $title,
              'action' => $action
            ];
          }
          mysqli_stmt_close($approverStmt);
        }
      }
    }
  } catch (Throwable $e) {
    error_log("Error fetching approver sequence: " . $e->getMessage());
  }
  
  return $approvers;
}

// Function to get approval status for each approver from workflow process
function getApprovalStatus($mysqlconn, $requestType, $docNumber) {
  $approvalStatus = [];
  
  if (!$mysqlconn) return $approvalStatus;
  
  try {
    $stmt = mysqli_prepare($mysqlconn, 'SELECT sequence, actor_id, action, status FROM work_flow_process WHERE doc_type = ? AND doc_number = ? ORDER BY sequence, id');
    if ($stmt) {
      mysqli_stmt_bind_param($stmt, 'ss', $requestType, $docNumber);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);
      
      while ($row = mysqli_fetch_assoc($result)) {
        $sequence = (int)$row['sequence'];
        $actorId = trim((string)$row['actor_id']);
        $action = trim((string)$row['action']);
        $status = trim((string)$row['status']);
        $statusUpper = strtoupper($status);
        
        // Create a unique key for this approver step
        $key = $sequence . '|' . $actorId . '|' . $action;
        
        // Determine if this step is approved
        $isApproved = ($statusUpper === 'APPROVED' || $statusUpper === 'DONE' || strpos($statusUpper, 'APPROVED') !== false);
        
        // Determine if this step is submitted (for requestor/prepared by)
        $isSubmitted = ($statusUpper === 'SUBMITTED' || strpos($statusUpper, 'SUBMITTED') !== false);
        
        $approvalStatus[$key] = [
          'sequence' => $sequence,
          'actor_id' => $actorId,
          'action' => $action,
          'status' => $status,
          'is_approved' => $isApproved,
          'is_submitted' => $isSubmitted
        ];
      }
      mysqli_stmt_close($stmt);
    }
  } catch (Throwable $e) {
    error_log("Error fetching approval status: " . $e->getMessage());
  }
  
  return $approvalStatus;
}

function generateERL_PDF($fr, $items, $breakdowns, $mysqlconn = null) {
  $company = (string)($fr['company_name'] ?? $fr['company'] ?? '');
  $companyCode = (string)($fr['company'] ?? '');
  $department = (string)($fr['department_name'] ?? '');
  $travelDate = (string)($fr['doc_date'] ?? '');
  $purpose = (string)($fr['payment_for'] ?? '');
  $payee = (string)($fr['payee'] ?? ''); // Use payee instead of user name
  $currentDate = date('l, F j, Y');
  
  // Get creator's full name for "Prepared by"
  $preparedBy = '';
  if (isset($fr['created_by_user_id']) && $fr['created_by_user_id']) {
    try {
      $stmt = mysqli_prepare($mysqlconn, 'SELECT user_firstname, user_lastname FROM sys_usertb WHERE id = ? LIMIT 1');
      if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $fr['created_by_user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
          $preparedBy = trim((string)($row['user_firstname'] ?? '').' '.(string)($row['user_lastname'] ?? ''));
        }
        mysqli_stmt_close($stmt);
      }
    } catch (Throwable $e) {
      // Log error but don't fail
      error_log("Error looking up creator name: " . $e->getMessage());
    }
  }
  
  // Fallback to payee if creator lookup fails
  if (!$preparedBy) {
    $preparedBy = $payee;
  }
  
  // Get approver sequence
  $approvers = getApproverSequence($mysqlconn, 'ERL', $companyCode, $fr['cost_center'] ?? '');
  
  // Get approval status for each approver
  $approvalStatus = getApprovalStatus($mysqlconn, 'ERL', $fr['doc_number'] ?? '');
  
  // Get currency symbol
  $currencyCode = (string)($fr['currency'] ?? 'PHP');
  $currencySymbol = getCurrencySymbol($currencyCode);
  
  // Calculate total disbursement and VAT amount
  $totalDisbursement = 0;
  $totalVatAmount = 0;
  if (!empty($items)) {
    foreach ($items as $item) {
      $totalDisbursement += (float)($item['net_payable_amount'] ?? $item['amount'] ?? 0);
      // Add VAT amount if item is vatable
      $vatable = $item['vatable'] ?? '';
      if ($vatable === 'yes') {
        $totalVatAmount += (float)($item['vat_amount'] ?? 0);
      }
    }
  }
  
  // Determine company logo image path
  $logoPath = 'images/' . strtolower($companyCode) . '.jpg';
  $logoExists = file_exists(__DIR__ . '/' . $logoPath);
  
  // If lowercase not found, try uppercase
  if (!$logoExists) {
    $logoPath = 'images/' . strtoupper($companyCode) . '.jpg';
    $logoExists = file_exists(__DIR__ . '/' . $logoPath);
  }
  
  // If still not found, try mixed case
  if (!$logoExists) {
    $logoPath = 'images/' . $companyCode . '.jpg';
    $logoExists = file_exists(__DIR__ . '/' . $logoPath);
  }
  
  if (!$logoExists) {
    $logoPath = 'images/img.jpg'; // fallback to default image
  }
  
  // Fetch Revolving Fund RFPs created by the same user (cash_advance = 1)
  $rfpCashAdvances = [];
  $rfpTotalBalance = 0;
  try {
    $userIdForQuery = null;
    if (!empty($fr['created_by_user_id'])) {
      $userIdForQuery = (int)$fr['created_by_user_id'];
    } elseif (!empty($fr['payee'])) {
      $userIdForQuery = (int)$fr['payee'];
    }
    if ($mysqlconn && $userIdForQuery) {
      $stmt = mysqli_prepare($mysqlconn, "SELECT fr.doc_number, fr.currency, COALESCE(SUM(ri.net_payable_amount),0) AS total_amount\n                                          FROM financial_requests fr\n                                          JOIN financial_request_items ri ON ri.doc_number = fr.doc_number\n                                          WHERE fr.request_type = 'RFP'\n                                            AND (fr.created_by_user_id = ? OR fr.payee = ?)\n                                            AND COALESCE(ri.cash_advance, 0) = 1\n                                          GROUP BY fr.doc_number, fr.currency\n                                          ORDER BY fr.doc_date DESC, fr.id DESC");
      if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ii', $userIdForQuery, $userIdForQuery);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
          $rfpCashAdvances[] = [
            'doc_number' => (string)($row['doc_number'] ?? ''),
            'currency' => (string)($row['currency'] ?? 'PHP'),
            'total_amount' => (float)($row['total_amount'] ?? 0),
          ];
          $rfpTotalBalance += (float)($row['total_amount'] ?? 0);
        }
        mysqli_stmt_close($stmt);
      }
    }
  } catch (Throwable $e) {
    error_log('Error fetching RF cash advance RFPs: ' . $e->getMessage());
  }

  ?>
  <!DOCTYPE html>
  <html>
  <head>
    <meta charset="utf-8">
    <title>Expense Report (Liquidation)</title>
    <style>
      body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
      .header { text-align: center; margin-bottom: 30px; }
      .company { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
      .date { text-align: right; margin-bottom: 20px; }
      .form-section { margin-bottom: 20px; }
      .form-row { margin-bottom: 10px; }
      .form-label { display: inline-block; width: 150px; font-weight: bold; }
      .form-value { display: inline-block; width: 300px; border-bottom: 1px solid #000; min-height: 20px; }
      table { width: 100%; border-collapse: collapse; margin: 20px 0; }
      th, td { border: 1px solid #000; padding: 8px; text-align: left; }
      th { background-color: #f0f0f0; font-weight: bold; }
      .total-row { font-weight: bold; background-color: #f0f0f0; }
      .notes { margin-top: 20px; }
      .signatures { margin-top: 40px; }
      .signature-row { display: flex; justify-content: space-between; margin-bottom: 20px; }
      .signature-box { text-align: center; width: 200px; }
      .signature-line { border-bottom: 1px solid #000; margin: 30px 0 10px 0; }
      
      /* Print-specific styles */
      @media print {
        body { margin: 0; font-size: 10px; }
        .header { margin-bottom: 20px; }
        .form-section { margin-bottom: 15px; }
        table { margin: 15px 0; }
        th, td { padding: 4px; font-size: 9px; }
        .signatures { margin-top: 30px; }
        .signature-row { margin-bottom: 15px; }
        .signature-line { margin: 20px 0 8px 0; }
        .page-break { page-break-before: always; }
        
        /* Hide print buttons when printing */
        div[onclick] { display: none !important; }
        
        /* Ensure proper page breaks */
        table { page-break-inside: avoid; }
        .signatures { page-break-inside: avoid; }
        
        /* Optimize for A4 landscape paper */
        @page { 
          size: A4 landscape; 
          margin: 1cm; 
        }
      }
    </style>
  </head>
  <body>
    <div class="header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px;">
      <?php if ($logoExists) { ?>
        <div style="text-align: center; margin-bottom: 20px;">
          <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="<?php echo htmlspecialchars($company); ?>" style="max-width: 80px; max-height: 80px; border-radius: 50%;">
        </div>
      <?php } ?>
      <div style="flex: 1; text-align: center;">
        <div class="company"><?php echo htmlspecialchars($company); ?></div>
        <div>EXPENSE REPORT (LIQUIDATION)</div>
      </div>
      <?php if (!empty($rfpCashAdvances)) { ?>
      <div style="width:220px; margin-left:auto;">
        <div style="background:#ffff00; padding:8px; text-align:center; font-weight:bold; font-size:12px; border:1px solid #000;">REVOLVING FUND</div>
        <div style="border:1px solid #000; border-top:none; padding:8px;">
          <div style="font-size:10px; margin-bottom:5px;">PENDING REPLENISHMENT/S:</div>
          <?php foreach ($rfpCashAdvances as $rfp) { ?>
            <div style="font-size:10px; margin-bottom:2px;">RFP# <?php echo htmlspecialchars($rfp['doc_number']); ?></div>
          <?php } ?>
          <div style="background:#ffff00; padding:5px; text-align:center; font-weight:bold; border-top:1px solid #000; margin-top:5px;">BALANCE<br><?php echo $currencySymbol . number_format($rfpTotalBalance, 2); ?></div>
        </div>
      </div>
    <?php } ?>
    </div>
    
    <div class="date"><?php echo htmlspecialchars($currentDate); ?></div>
    
    <div class="form-section">
      <div class="form-row">
        <span class="form-label">Company:</span>
        <span class="form-value"><?php echo htmlspecialchars($company); ?></span>
      </div>
      <div class="form-row">
        <span class="form-label">Department:</span>
        <span class="form-value"><?php echo htmlspecialchars($department); ?></span>
      </div>
      <div class="form-row">
        <span class="form-label">Covered Date of Travel:</span>
        <span class="form-value"><?php echo htmlspecialchars($travelDate); ?></span>
      </div>
      <div class="form-row">
        <span class="form-label">Purpose:</span>
        <span class="form-value"><?php echo htmlspecialchars($purpose); ?></span>
      </div>
    </div>
    
    <table>
      <thead>
        <tr>
          <th>Date of Transaction</th>
          <th>Cost Center</th>
          <th>Invoice No.</th>
          <th>Supplier/Store Name</th>
          <th>Description</th>
          <th>EXPENSE ACCT</th>
          <th>INPUT VAT</th>
          <th>VAT AMOUNT</th>
          <th>AMOUNT</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($items)) { ?>
          <?php foreach ($items as $item) { ?>
            <tr>
              <td><?php echo htmlspecialchars($item['invoice_date'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($fr['department_name'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($item['invoice_number'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($item['supplier_name'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($item['description'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars(getSapAccountDescription($mysqlconn, $item['category'] ?? '')); ?></td>
              <td><?php 
                $vatable = $item['vatable'] ?? '';
                if ($vatable === 'yes') {
                  echo '12%';
                } elseif ($vatable === 'no') {
                  echo '0%';
                } else {
                  echo htmlspecialchars($vatable);
                }
              ?></td>
              <td><?php 
                $vatable = $item['vatable'] ?? '';
                if ($vatable === 'yes') {
                  $vatAmount = (float)($item['vat_amount'] ?? 0);
                  echo $currencySymbol . number_format($vatAmount, 2);
                } else {
                  echo '-';
                }
              ?></td>
              <td><?php echo $currencySymbol . number_format((float)($item['net_payable_amount'] ?? $item['amount'] ?? 0), 2); ?></td>
            </tr>
          <?php } ?>
        <?php } ?>
        <tr class="total-row">
          <td colspan="5">Total Disbursements</td>
          <td>-</td>
          <td>-</td>
          <td><?php echo $currencySymbol . number_format($totalVatAmount, 2); ?></td>
          <td><?php echo $currencySymbol . number_format($totalDisbursement, 2); ?></td>
        </tr>
      </tbody>
    </table>
    
    <div class="notes">
      <strong>Explanatory Notes:</strong><br>
      <strong>Date:</strong> Date indicated in the supporting documents.<br>
      <strong>Cost Center:</strong> Cost center where the expenses should be charged.<br>
      <strong>Document Number:</strong> Invoice Number/OR number for each supporting document.<br>
      <strong>Payee:</strong> Name of supplier/vendor who received the cash.<br>
      <strong>Description:</strong> Short text about the transaction.<br>
      <strong>Expense Account:</strong> Identify the type of expense account applicable for each transaction.
    </div>
    
    <div class="signatures">
      <!-- First row: Prepared by and first 2 approvers -->
      <div class="signature-row">
        <div class="signature-box">
          <?php
          // Check if requestor has submitted (sequence 1)
          $isRequestorSubmitted = false;
          foreach ($approvalStatus as $key => $statusData) {
            if ($statusData['sequence'] === 1 && $statusData['action'] === 'Requestor') {
              $isRequestorSubmitted = $statusData['is_submitted'];
              break;
            }
          }
          
          if ($isRequestorSubmitted) {
            echo '<div class="signature-line" style="text-align: center; font-size: 10px; color: #007bff; padding-top: 10px;">Digitally Prepared via DMS</div>';
            echo '<div>Prepared by: ' . htmlspecialchars($preparedBy) . '</div>';
          } else {
            echo '<div class="signature-line"></div>';
            echo '<div>Prepared by: ' . htmlspecialchars($preparedBy) . '</div>';
          }
          ?>
        </div>
        <?php 
        // Display first 2 approvers in sequence order
        $sequenceTitles = [
          2 => 'Cost Center Head',
          3 => 'Accounting',
          4 => 'Accounting', 
          5 => 'Controller',
          6 => 'Cashier'
        ];
        
        $displayedSequences = [];
        $count = 0;
        foreach ($approvers as $approver) {
          $seq = $approver['sequence'];
          if (!in_array($seq, $displayedSequences) && isset($sequenceTitles[$seq]) && $count < 2) {
            $displayedSequences[] = $seq;
            $count++;
            
            // Check if this approver has approved
            $isApproved = false;
            $approverName = '';
            foreach ($approvalStatus as $key => $statusData) {
              if ($statusData['sequence'] === $seq && $statusData['action'] === $approver['action']) {
                $isApproved = $statusData['is_approved'];
                $approverName = $approver['name'];
                break;
              }
            }
            
            echo '<div class="signature-box">';
            if ($isApproved) {
              echo '<div class="signature-line" style="text-align: center; font-size: 10px; color: #007bff; padding-top: 10px;">Digitally Approved via DMS</div>';
              echo '<div>' . htmlspecialchars($sequenceTitles[$seq]) . ': ' . htmlspecialchars($approverName) . '</div>';
            } else {
              echo '<div class="signature-line"></div>';
              echo '<div>' . htmlspecialchars($sequenceTitles[$seq]) . ':</div>';
            }
            echo '</div>';
          }
        }
        
        // Fill remaining slots in first row if needed
        for ($i = 2; $i <= 6 && $count < 2; $i++) {
          if (!in_array($i, $displayedSequences)) {
            $count++;
            echo '<div class="signature-box">';
            echo '<div class="signature-line"></div>';
            echo '<div>' . htmlspecialchars($sequenceTitles[$i] ?? 'Approved by') . ':</div>';
            echo '</div>';
          }
        }
        ?>
      </div>
      
      <!-- Second row: Remaining approvers -->
      <div class="signature-row">
        <?php 
        // Display remaining approvers
        $count = 0;
        foreach ($approvers as $approver) {
          $seq = $approver['sequence'];
          if (!in_array($seq, $displayedSequences) && isset($sequenceTitles[$seq]) && $count < 3) {
            $displayedSequences[] = $seq;
            $count++;
            
            // Check if this approver has approved
            $isApproved = false;
            $approverName = '';
            foreach ($approvalStatus as $key => $statusData) {
              if ($statusData['sequence'] === $seq && $statusData['action'] === $approver['action']) {
                $isApproved = $statusData['is_approved'];
                $approverName = $approver['name'];
                break;
              }
            }
            
            echo '<div class="signature-box">';
            if ($isApproved) {
              echo '<div class="signature-line" style="text-align: center; font-size: 10px; color: #007bff; padding-top: 10px;">Digitally Approved via DMS</div>';
              echo '<div>' . htmlspecialchars($sequenceTitles[$seq]) . ': ' . htmlspecialchars($approverName) . '</div>';
            } else {
              echo '<div class="signature-line"></div>';
              echo '<div>' . htmlspecialchars($sequenceTitles[$seq]) . ':</div>';
            }
            echo '</div>';
          }
        }
        
        // Fill remaining slots in second row if needed
        for ($i = 2; $i <= 6 && $count < 3; $i++) {
          if (!in_array($i, $displayedSequences)) {
            $count++;
            echo '<div class="signature-box">';
            echo '<div class="signature-line"></div>';
            echo '<div>' . htmlspecialchars($sequenceTitles[$i] ?? 'Approved by') . ':</div>';
            echo '</div>';
          }
        }
        ?>
      </div>
    </div>
  </body>
  </html>
  <?php
}

function generateERGR_PDF($fr, $items, $breakdowns, $mysqlconn = null) {
  $company = (string)($fr['company_name'] ?? $fr['company'] ?? '');
  $companyCode = (string)($fr['company'] ?? '');
  $name = (string)($fr['payee'] ?? ''); // Use payee instead of user name
  $department = (string)($fr['department_name'] ?? '');
  $location = (string)($fr['cost_center'] ?? '');
  $dateCovered = (string)($fr['doc_date'] ?? '');
  
  // Get creator's full name for "Prepared by"
  $preparedBy = '';
  if (isset($fr['created_by_user_id']) && $fr['created_by_user_id']) {
    try {
      $stmt = mysqli_prepare($mysqlconn, 'SELECT user_firstname, user_lastname FROM sys_usertb WHERE id = ? LIMIT 1');
      if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $fr['created_by_user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
          $preparedBy = trim((string)($row['user_firstname'] ?? '').' '.(string)($row['user_lastname'] ?? ''));
        }
        mysqli_stmt_close($stmt);
      }
    } catch (Throwable $e) {
      // Log error but don't fail
      error_log("Error looking up creator name: " . $e->getMessage());
    }
  }
  
  // Fallback to payee if creator lookup fails
  if (!$preparedBy) {
    $preparedBy = $name;
  }
  
  // Get approver sequence
  $approvers = getApproverSequence($mysqlconn, 'ERGR', $companyCode, $fr['cost_center'] ?? '');
  
  // Get approval status for each approver
  $approvalStatus = getApprovalStatus($mysqlconn, 'ERGR', $fr['doc_number'] ?? '');
  
  // Get currency symbol
  $currencyCode = (string)($fr['currency'] ?? 'PHP');
  $currencySymbol = getCurrencySymbol($currencyCode);
  
  // Calculate total amount
  $totalAmount = 0;
  if (!empty($items)) {
    foreach ($items as $item) {
      // Use net_payable_amount if available, otherwise fall back to amount
      $itemAmount = (float)($item['net_payable_amount'] ?? $item['amount'] ?? 0);
      $totalAmount += $itemAmount;
    }
  }
  
  // Determine company logo image path
  $logoPath = 'images/' . strtolower($companyCode) . '.jpg';
  $logoExists = file_exists(__DIR__ . '/' . $logoPath);
  
  // If lowercase not found, try uppercase
  if (!$logoExists) {
    $logoPath = 'images/' . strtoupper($companyCode) . '.jpg';
    $logoExists = file_exists(__DIR__ . '/' . $logoPath);
  }
  
  // If still not found, try mixed case
  if (!$logoExists) {
    $logoPath = 'images/' . $companyCode . '.jpg';
    $logoExists = file_exists(__DIR__ . '/' . $logoPath);
  }
  
  if (!$logoExists) {
    $logoPath = 'images/img.jpg'; // fallback to default image
  }
  
  ?>
  <!DOCTYPE html>
  <html>
  <head>
    <meta charset="utf-8">
    <title>Operational Expenses</title>
    <style>
      body { font-family: Arial, sans-serif; font-size: 12px; margin: 15px; }
      
      /* Header layout matching Image 2 */
      .header-container { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
      .header-left { display: flex; align-items: center; }
      .logo { width: 80px; height: 80px; border: 2px solid #000; border-radius: 50%; margin-right: 20px; display: flex; align-items: center; justify-content: center; text-align: center; font-weight: bold; font-size: 10px; }
      .company-name { font-size: 18px; font-weight: bold; }
      
      .revolving-fund { width: 200px; }
      .rf-header { background-color: #ffff00; padding: 8px; text-align: center; font-weight: bold; font-size: 12px; border: 1px solid #000; }
      .rf-content { border: 1px solid #000; border-top: none; padding: 8px; }
      .rf-pending { font-size: 10px; margin-bottom: 5px; }
      .rf-item { font-size: 10px; color: red; margin-bottom: 2px; }
      .rf-balance { background-color: #ffff00; padding: 5px; text-align: center; font-weight: bold; border-top: 1px solid #000; margin-top: 5px; }
      
      /* Form section matching Image 2 */
      .form-section { margin: 20px 0; }
      .form-row { margin-bottom: 12px; }
      .form-field { display: flex; align-items: center; }
      .form-label { font-weight: bold; margin-right: 10px; min-width: 100px; display: inline-block; }
      .form-line { border-bottom: 2px solid #000; min-width: 400px; display: inline-block; height: 20px; padding: 2px 5px; }
      
      /* Title */
      .title { text-align: center; font-size: 16px; font-weight: bold; margin: 30px 0; }
      
      /* Table styles matching Image 2 */
      table { width: 100%; border-collapse: collapse; margin: 20px 0; }
      th, td { border: 1px solid #000; padding: 5px; text-align: center; font-size: 10px; vertical-align: middle; }
      th { background-color: #ffff99; font-weight: bold; }
      .total-row { font-weight: bold; }
      .total-row td:first-child { text-align: right; }
      
      /* Column highlighting - specific columns with light background */
      tbody td:nth-child(1) { background-color: #f0f8ff !important; } /* CARF No. - Light blue */
      tbody td:nth-child(2) { background-color: #f0f8ff !important; } /* PCV No. - Light blue */
      tbody td:nth-child(3) { background-color: #f0f8ff !important; } /* Invoice Date - Light blue */
      tbody td:nth-child(4) { background-color: #f0f8ff !important; } /* Invoice # - Light blue */
      
      /* Keep headers yellow */
      thead th { background-color: #ffff99 !important; }
      
      /* Column widths for landscape */
      .col-carf { width: 6%; }
      .col-pcv { width: 6%; }
      .col-inv-date { width: 8%; }
      .col-invoice { width: 8%; }
      .col-supplier { width: 15%; }
      .col-tin { width: 8%; }
      .col-address { width: 12%; }
      .col-particular { width: 15%; }
      .col-expense { width: 10%; }
      .col-vat { width: 6%; }
      .col-amount { width: 8%; }
      .col-remarks { width: 8%; }
      
      /* Signatures */
      .signatures { margin-top: 40px; }
      .signature-row { display: flex; justify-content: space-between; margin-bottom: 20px; }
      .signature-box { text-align: center; width: 200px; }
      .signature-line { border-bottom: 1px solid #000; margin: 30px 0 10px 0; height: 40px; }
      .signature-title { font-weight: bold; margin-bottom: 5px; }
      .signature-name { margin-top: 5px; }
      
      /* Print-specific styles */
      @media print {
        body { margin: 10px; font-size: 11px; }
        .header-container { margin-bottom: 15px; }
        .logo { width: 60px; height: 60px; font-size: 8px; }
        .company-name { font-size: 16px; }
        .revolving-fund { width: 180px; }
        .rf-header, .rf-balance { font-size: 10px; }
        .form-section { margin: 15px 0; }
        .form-line { min-width: 250px; }
        .title { font-size: 14px; margin: 20px 0; }
        table { margin: 15px 0; }
        th, td { padding: 3px; font-size: 8px; }
        .signatures { margin-top: 30px; }
        .signature-row { margin-bottom: 15px; }
        .signature-line { margin: 20px 0 8px 0; height: 30px; }
        .page-break { page-break-before: always; }
        
        /* Hide print buttons when printing */
        div[onclick] { display: none !important; }
        
        /* Optimize for A4 landscape paper */
        @page { 
          size: A4 landscape; 
          margin: 0.5cm; 
        }
      }
    </style>
  </head>
  <body>
    <div class="header-container">
      <div class="header-left">
        <div class="logo">
          <?php if ($logoExists) { ?>
            <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="<?php echo htmlspecialchars($company); ?>" style="max-width: 80px; max-height: 80px; border-radius: 50%;">
          <?php } else { ?>
            Carlson's<br>Best
          <?php } ?>
        </div>
        <div class="company-name"><?php echo htmlspecialchars($company); ?></div>
      </div>
      <!-- Revolving Fund panel removed for ERGR -->
    </div>
    
    <div class="form-section">
      <div class="form-row">
        <div class="form-field">
          <span class="form-label">Company:</span>
          <span class="form-line"><?php echo htmlspecialchars($company); ?></span>
        </div>
      </div>
      <div class="form-row">    
        <div class="form-field">
          <span class="form-label">Name:</span>
          <span class="form-line"><?php echo htmlspecialchars($name); ?></span>
        </div>
      </div>
      <div class="form-row">
        <div class="form-field">
          <span class="form-label">Department:</span>
          <span class="form-line"><?php echo htmlspecialchars($department); ?></span>
        </div>
      </div>
      <div class="form-row">
        <div class="form-field">
          <span class="form-label">Location:</span>
          <span class="form-line"><?php echo htmlspecialchars($location); ?></span>
        </div>
      </div>
    </div>
    
    <div class="title">OPERATIONAL EXPENSES DATE COVERED <?php echo htmlspecialchars($dateCovered); ?></div>
    
    <table>
      <thead>
        <tr>
          <th class="col-carf">CARF No.</th>
          <th class="col-pcv">PCV No.</th>
          <th class="col-inv-date">INVOICE DATE</th>
          <th class="col-invoice">INVOICE #</th>
          <th class="col-supplier">SUPPLIER'S/STORE NAME</th>
          <th class="col-tin">TIN</th>
          <th class="col-address">ADDRESS</th>
          <th class="col-particular">PARTICULAR</th>
          <th class="col-expense">EXPENSE ACCT</th>
          <th class="col-vat">INPUT VAT</th>
          <th class="col-amount">AMOUNT</th>
          <th class="col-remarks">REMARKS</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($items)) { ?>
          <?php foreach ($items as $item) { ?>
            <tr>
              <td><?php echo htmlspecialchars($item['carf_no'] ?? $item['reference_number'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($item['pcv_no'] ?? $item['po_number'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($item['invoice_date'] ?? $item['due_date'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($item['invoice_number'] ?? $item['reference_number'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($item['supplier_name'] ?? $item['category'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($item['tin'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($item['address'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($item['description'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars(getSapAccountDescription($mysqlconn, $item['category'] ?? '')); ?></td>
              <td><?php 
                $vatable = $item['vatable'] ?? '';
                if ($vatable === 'yes') {
                  echo '12%';
                } elseif ($vatable === 'no') {
                  echo '0%';
                } else {
                  echo htmlspecialchars($vatable);
                }
              ?></td>
              <td><?php echo $currencySymbol . number_format((float)($item['net_payable_amount'] ?? $item['amount'] ?? 0), 2); ?></td>
              <td><?php echo htmlspecialchars($item['description'] ?? ''); ?></td>
            </tr>
          <?php } ?>
        <?php } else { ?>
          <!-- Empty rows for blank form -->
          <?php for ($i = 0; $i < 10; $i++) { ?>
            <tr>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
            </tr>
          <?php } ?>
        <?php } ?>
        <tr class="total-row">
          <td colspan="10">TOTAL</td>
          <td><?php echo $currencySymbol . number_format($totalAmount, 2); ?></td>
          <td>&nbsp;</td>
        </tr>
      </tbody>
    </table>
    
    <div class="signatures">
      <!-- First row: Prepared by and first 2 approvers -->
      <div class="signature-row">
        <div class="signature-box">
          <?php
          // Check if requestor has submitted (sequence 1)
          $isRequestorSubmitted = false;
          foreach ($approvalStatus as $key => $statusData) {
            if ($statusData['sequence'] === 1 && $statusData['action'] === 'Requestor') {
              $isRequestorSubmitted = $statusData['is_submitted'];
              break;
            }
          }
          
          if ($isRequestorSubmitted) {
            echo '<div class="signature-line" style="text-align: center; font-size: 9px; color: #007bff; padding-top: 8px;">Digitally Prepared via DMS</div>';
            echo '<div class="signature-name">' . htmlspecialchars($preparedBy) . '<br>Prepared by:</div>';
          } else {
            echo '<div class="signature-line"></div>';
            echo '<div class="signature-name">' . htmlspecialchars($preparedBy) . '<br>Prepared by:</div>';
          }
          ?>
        </div>
        <?php 
        // Display first 2 approvers in sequence order
        $sequenceTitles = [
          2 => 'Cost Center Head',
          3 => 'Accounting',
          4 => 'Accounting', 
          5 => 'Controller',
          6 => 'Cashier'
        ];
        
        $displayedSequences = [];
        $count = 0;
        foreach ($approvers as $approver) {
          $seq = $approver['sequence'];
          if (!in_array($seq, $displayedSequences) && isset($sequenceTitles[$seq]) && $count < 2) {
            $displayedSequences[] = $seq;
            $count++;
            
            // Check if this approver has approved
            $isApproved = false;
            $approverName = '';
            foreach ($approvalStatus as $key => $statusData) {
              if ($statusData['sequence'] === $seq && $statusData['action'] === $approver['action']) {
                $isApproved = $statusData['is_approved'];
                $approverName = $approver['name'];
                break;
              }
            }
            
            echo '<div class="signature-box">';
            if ($isApproved) {
              echo '<div class="signature-line" style="text-align: center; font-size: 9px; color: #007bff; padding-top: 8px;">Digitally Approved via DMS</div>';
              echo '<div class="signature-name">' . htmlspecialchars($approverName) . '<br>' . htmlspecialchars($sequenceTitles[$seq]) . '</div>';
            } else {
              echo '<div class="signature-line"></div>';
              echo '<div class="signature-name">NAME WITH SIGNATURE<br>' . htmlspecialchars($sequenceTitles[$seq]) . '</div>';
            }
            echo '</div>';
          }
        }
        
        // Fill remaining slots in first row if needed
        for ($i = 2; $i <= 6 && $count < 2; $i++) {
          if (!in_array($i, $displayedSequences)) {
            $count++;
            echo '<div class="signature-box">';
            echo '<div class="signature-line"></div>';
            echo '<div class="signature-name">NAME WITH SIGNATURE<br>' . htmlspecialchars($sequenceTitles[$i] ?? 'APPROVER') . '</div>';
            echo '</div>';
          }
        }
        ?>
      </div>
      
      <!-- Second row: Remaining approvers -->
      <div class="signature-row">
        <?php 
        // Display remaining approvers
        $count = 0;
        foreach ($approvers as $approver) {
          $seq = $approver['sequence'];
          if (!in_array($seq, $displayedSequences) && isset($sequenceTitles[$seq]) && $count < 3) {
            $displayedSequences[] = $seq;
            $count++;
            
            // Check if this approver has approved
            $isApproved = false;
            $approverName = '';
            foreach ($approvalStatus as $key => $statusData) {
              if ($statusData['sequence'] === $seq && $statusData['action'] === $approver['action']) {
                $isApproved = $statusData['is_approved'];
                $approverName = $approver['name'];
                break;
              }
            }
            
            echo '<div class="signature-box">';
            if ($isApproved) {
              echo '<div class="signature-line" style="text-align: center; font-size: 9px; color: #007bff; padding-top: 8px;">Digitally Approved via DMS</div>';
              echo '<div class="signature-name">' . htmlspecialchars($approverName) . '<br>' . htmlspecialchars($sequenceTitles[$seq]) . '</div>';
            } else {
              echo '<div class="signature-line"></div>';
              echo '<div class="signature-name">NAME WITH SIGNATURE<br>' . htmlspecialchars($sequenceTitles[$seq]) . '</div>';
            }
            echo '</div>';
          }
        }
        
        // Fill remaining slots in second row if needed
        for ($i = 2; $i <= 6 && $count < 3; $i++) {
          if (!in_array($i, $displayedSequences)) {
            $count++;
            echo '<div class="signature-box">';
            echo '<div class="signature-line"></div>';
            echo '<div class="signature-name">NAME WITH SIGNATURE<br>' . htmlspecialchars($sequenceTitles[$i] ?? 'APPROVER') . '</div>';
            echo '</div>';
          }
        }
        ?>
      </div>
    </div>
  </body>
  </html>
  <?php
}

function generateRFP_PDF($fr, $items, $breakdowns, $mysqlconn = null) {
  $company = (string)($fr['company_name'] ?? $fr['company'] ?? '');
  $companyCode = (string)($fr['company'] ?? '');
  $payee = (string)($fr['payee'] ?? ''); // Use payee instead of user name
  $department = (string)($fr['department_name'] ?? '');
  $date = (string)($fr['doc_date'] ?? '');
  $costCenter = (string)($fr['cost_center'] ?? '');
  $rfpNumber = (string)($fr['doc_number'] ?? '');
  
  // Get creator's full name for "Prepared by"
  $preparedBy = '';
  if (isset($fr['created_by_user_id']) && $fr['created_by_user_id']) {
    try {
      $stmt = mysqli_prepare($mysqlconn, 'SELECT user_firstname, user_lastname FROM sys_usertb WHERE id = ? LIMIT 1');
      if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $fr['created_by_user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
          $preparedBy = trim((string)($row['user_firstname'] ?? '').' '.(string)($row['user_lastname'] ?? ''));
        }
        mysqli_stmt_close($stmt);
      }
    } catch (Throwable $e) {
      // Log error but don't fail
      error_log("Error looking up creator name: " . $e->getMessage());
    }
  }
  
  // Fallback to payee if creator lookup fails
  if (!$preparedBy) {
    $preparedBy = $payee;
  }
  
  // Get approver sequence
  $approvers = getApproverSequence($mysqlconn, 'RFP', $companyCode, $fr['cost_center'] ?? '');
  
  // Get approval status for each approver
  $approvalStatus = getApprovalStatus($mysqlconn, 'RFP', $fr['doc_number'] ?? '');
  
  // Get currency symbol
  $currencyCode = (string)($fr['currency'] ?? 'PHP');
  $currencySymbol = getCurrencySymbol($currencyCode);
  
  // Debug: Ensure we have a valid currency symbol
  if (empty($currencySymbol) || $currencySymbol === $currencyCode) {
    $currencySymbol = '₱'; // Fallback to peso symbol
  }
  
  // Determine company logo image path
  $logoPath = 'images/' . strtolower($companyCode) . '.jpg';
  $logoExists = file_exists(__DIR__ . '/' . $logoPath);
  
  // If lowercase not found, try uppercase
  if (!$logoExists) {
    $logoPath = 'images/' . strtoupper($companyCode) . '.jpg';
    $logoExists = file_exists(__DIR__ . '/' . $logoPath);
  }
  
  // If still not found, try mixed case
  if (!$logoExists) {
    $logoPath = 'images/' . $companyCode . '.jpg';
    $logoExists = file_exists(__DIR__ . '/' . $logoPath);
  }
  
  // Always set a fallback path
  if (!$logoExists) {
    $logoPath = 'images/img.jpg';
    $logoExists = file_exists(__DIR__ . '/' . $logoPath);
  }
  
  // Calculate total amount
  $totalAmount = 0;
  if (!empty($items)) {
    foreach ($items as $item) {
      // Use net_payable_amount if available, otherwise fall back to amount
      $itemAmount = (float)($item['net_payable_amount'] ?? $item['amount'] ?? 0);
      $totalAmount += $itemAmount;
    }
  }
  
  ?>
  <!DOCTYPE html>
  <html>
  <head>
    <meta charset="utf-8">
    <title>Request For Payment</title>
    <style>
      body { 
        font-family: Arial, sans-serif; 
        font-size: 11px; 
        margin: 30px;
        line-height: 1.2;
      }
      
      .document-container {
        border: 1px solid #000;
        padding: 10px;
        max-width: 700px;
        margin: 0 auto;
      }
      
      .header {
        text-align: center;
        margin-bottom: 25px;
      }
      
      .logo {
        width: 70px;
        height: auto;
        margin: 0 auto 5px;
        border: none;
      }

      
      .logo img {
        max-width: 58px;
        max-height: 58px;
        border-radius: 50%;
      }
      
      .company-name {
        font-size: 14px;
        font-weight: bold;
        margin-bottom: 15px;
        letter-spacing: 1px;
      }
      
      .document-title {
        font-size: 13px;
        font-weight: bold;
        margin-bottom: 20px;
      }
      
      .form-section {
        margin-bottom: 20px;
      }
      
      .form-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
      }
      
      .form-left {
        flex: 1;
        display: flex;
        align-items: center;
      }
      
      .form-right {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: flex-end;
      }
      
      .form-label { 
          width: 100px; 
          font-weight: normal;
        }
      
        .form-underline {
            border-bottom: 1px solid #000;
            flex: 1;
            min-width: 120px;
            display: inline-block;
          }
      
      .form-underline-right {
        border-bottom: 1px solid #000;
        min-height: 18px;
        min-width: 120px;
        padding: 2px 5px;
      }
      
      .main-table {
          width: 100%;
          border: 1px solid #000;
          border-collapse: collapse;
        }
      
        .main-table th, .main-table td {
          border: 1px solid #000;
          padding: 6px;
          font-size: 11px;
        }
      
      .particulars-col {
        width: 70%;
      }
      
      .amount-col {
          text-align: right;
        }
      
      .particulars-content {
        min-height: 100px;
        padding: 5px;
      }
      
      .amount-content {
        min-height: 100px;
        padding: 5px;
        text-align: right;
      }
      
      .currency-label {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: 10px;
      }
      
      .signature-row {
        width: 100%;
        border-collapse: collapse;
      }
      
      .signature-row td {
        border: 1px solid #000;
        padding: 8px;
        vertical-align: bottom;
        text-align: center;
        height: 60px;
      }
      
      .total-cell {
        width: 25%;
        text-align: left;
        vertical-align: bottom;
      }
      
      .signature-cell {
        width: 25%;
      }
      
      .signature-line {
        border-bottom: 1px solid #000;
        height: 25px;
        margin-bottom: 5px;
      }
      
      .signature-label {
        font-size: 10px;
        margin-bottom: 2px;
      }
      
      .signature-title {
        font-size: 9px;
        color: #666;
      }
      
      /* Print-specific styles */
      @media print {
        body { 
          margin: 0; 
          font-size: 10px; 
        }
        
        .document-container {
          border: 2px solid #000;
          padding: 15px;
          margin: 0;
        }
        
        .logo {
          width: 50px;
          height: 50px;
          margin-bottom: 10px;
        }
        
        .company-name {
          font-size: 12px;
          margin-bottom: 10px;
        }
        
        .document-title {
          font-size: 11px;
          margin-bottom: 15px;
        }
        
        .form-section {
          margin-bottom: 15px;
        }
        
        .form-row {
          margin-bottom: 6px;
        }
        
        .main-table th,
        .main-table td {
          padding: 6px;
        }
        
        .signature-row td {
          padding: 6px;
          height: 50px;
        }
        
        /* Hide print buttons when printing */
        div[onclick] { 
          display: none !important; 
        }
        
        /* Ensure proper page breaks */
        .document-container {
          page-break-inside: avoid;
        }
        
        /* Optimize for A4 paper */
        @page { 
          size: A4; 
          margin: 1cm; 
        }
      }
    </style>
  </head>
  <body>
    <div class="document-container">
      <div class="header">
        <div class="logo">
          <?php if ($logoExists) { ?>
            <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="<?php echo htmlspecialchars($company); ?>">
          <?php } else { ?>
            <div style="font-size: 8px; text-align: center; font-weight: bold;">LOGO</div>
          <?php } ?>
        </div>
        <div class="company-name"><?php echo htmlspecialchars(strtoupper($company)); ?></div>
        <div class="document-title">REQUEST FOR PAYMENT</div>
      </div>
      
      <div class="form-section">
        <div class="form-row">
          <div class="form-left">
            <span class="form-label">Payee:</span>
            <span class="form-underline"><?php echo htmlspecialchars($payee); ?></span>
          </div>
          <div class="form-right">
            <span class="form-label">Date:</span>
            <span class="form-underline-right"><?php echo htmlspecialchars($date); ?></span>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-left">
            <span class="form-label">Department:</span>
            <span class="form-underline"><?php echo htmlspecialchars($department); ?></span>
          </div>
          <div class="form-right">
            <span class="form-label">Cost Center:</span>
            <span class="form-underline-right"><?php echo htmlspecialchars($costCenter); ?></span>
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-left">
            <span class="form-underline" style="margin-left: 90px;"></span>
          </div>
          <div class="form-right">
            <span class="form-label">RFP #:</span>
            <span class="form-underline-right"><?php echo htmlspecialchars($rfpNumber); ?></span>
          </div>
        </div>
      </div>
      
      <table class="main-table">
        <thead>
          <tr>
            <th class="particulars-col">PARTICULARS</th>
            <th class="amount-col">AMOUNT</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="particulars-col">
              <div class="particulars-content">
                <?php 
                  // Show payment_for from financial_requests table instead of individual item descriptions
                  $paymentFor = (string)($fr['payment_for'] ?? '');
                  if ($paymentFor) {
                    echo htmlspecialchars($paymentFor);
                  } else {
                    // Fallback to showing item descriptions if payment_for is empty
                    if (!empty($items)) {
                      foreach ($items as $item) {
                        $categoryDesc = getSapAccountDescription($mysqlconn, $item['category'] ?? '');
                        $description = $item['description'] ?? '';
                        if ($categoryDesc && $categoryDesc !== ($item['category'] ?? '')) {
                          echo htmlspecialchars($categoryDesc . ($description ? ' - ' . $description : ''));
                        } else {
                          echo htmlspecialchars($description);
                        }
                        echo '<br>';
                      }
                    }
                  }
                ?>
              </div>
            </td>
            <td class="amount-col" style="position: relative;">
              <div class="amount-content">
                <?php if (!empty($items)) { ?>
                  <?php foreach ($items as $item) { ?>
                    <div style="margin-bottom: 5px;"><?php echo htmlspecialchars($currencyCode); ?><?php echo number_format((float)($item['net_payable_amount'] ?? $item['amount'] ?? 0), 2); ?></div>
                  <?php } ?>
                <?php } ?>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <table class="total-row">
        <tr>
          <td class="total-cell">
            <div style="height: 100%; display: flex; align-items: flex-end; margin-left: 100px;">
              <span style="font-weight: bold;">Total:</span>
            </div>
          </td>
          <td class="signature-cell">
            <div style="font-weight: bold; font-size: 16px; margin-bottom: 10px; text-align: right; margin-left: 100px;">
              <?php echo $currencySymbol . number_format($totalAmount, 2); ?>
            </div>
            
          </td>
        </tr>
      </table>
      
      <!-- Updated signature rows to match Image 2 layout with two rows -->
      <table class="signature-row">
        <tr>
          <td>
            <?php
            // Check if requestor has submitted (sequence 1)
            $isRequestorSubmitted = false;
            foreach ($approvalStatus as $key => $statusData) {
              if ($statusData['sequence'] === 1 && $statusData['action'] === 'Requestor') {
                $isRequestorSubmitted = $statusData['is_submitted'];
                break;
              }
            }
            
            if ($isRequestorSubmitted) {
              echo '<div class="signature-line" style="text-align: center; font-size: 9px; color: #007bff; padding-top: 5px;">Digitally Prepared via DMS</div>';
              echo 'Prepared By: ' . htmlspecialchars($preparedBy);
            } else {
              echo '<div class="signature-line"></div>';
              echo 'Prepared By: ' . htmlspecialchars($preparedBy);
            }
            ?>
          </td>
          <?php 
          // Display first 2 approvers in sequence order
          $sequenceTitles = [
            2 => 'Cost Center Head',
            3 => 'Accounting',
            4 => 'Accounting', 
            5 => 'Controller',
            6 => 'Cashier'
          ];
          
          $displayedSequences = [];
          $signatureCount = 0;
          foreach ($approvers as $approver) {
            $seq = $approver['sequence'];
            if (!in_array($seq, $displayedSequences) && isset($sequenceTitles[$seq]) && $signatureCount < 2) {
              $displayedSequences[] = $seq;
              $signatureCount++;
              
              // Check if this approver has approved
              $isApproved = false;
              $approverName = '';
              foreach ($approvalStatus as $key => $statusData) {
                if ($statusData['sequence'] === $seq && $statusData['action'] === $approver['action']) {
                  $isApproved = $statusData['is_approved'];
                  $approverName = $approver['name'];
                  break;
                }
              }
              
              if ($isApproved) {
                echo '<td><div class="signature-line" style="text-align: center; font-size: 9px; color: #007bff; padding-top: 5px;">Digitally Approved via DMS</div>' . htmlspecialchars($sequenceTitles[$seq]) . ': ' . htmlspecialchars($approverName) . '</td>';
              } else {
                echo '<td><div class="signature-line"></div>' . htmlspecialchars($sequenceTitles[$seq]) . ':</td>';
              }
            }
          }
          
          // Fill remaining slots in first row if needed
          for ($i = 2; $i <= 6 && $signatureCount < 2; $i++) {
            if (!in_array($i, $displayedSequences)) {
              $signatureCount++;
              echo '<td><div class="signature-line"></div>' . htmlspecialchars($sequenceTitles[$i] ?? 'Approved by') . ':</td>';
            }
          }
          
          // Ensure we have at least 3 columns in first row
          if ($signatureCount < 2) {
            echo '<td><div class="signature-line"></div>Approved by:</td>';
          }
          ?>
        </tr>
      </table>
      
      <!-- Second row for remaining approvers -->
      <table class="signature-row">
        <tr>
          <td></td>
          <?php 
          // Display remaining approvers
          $signatureCount = 0;
          foreach ($approvers as $approver) {
            $seq = $approver['sequence'];
            if (!in_array($seq, $displayedSequences) && isset($sequenceTitles[$seq]) && $signatureCount < 3) {
              $displayedSequences[] = $seq;
              $signatureCount++;
              
              // Check if this approver has approved
              $isApproved = false;
              $approverName = '';
              foreach ($approvalStatus as $key => $statusData) {
                if ($statusData['sequence'] === $seq && $statusData['action'] === $approver['action']) {
                  $isApproved = $statusData['is_approved'];
                  $approverName = $approver['name'];
                  break;
                }
              }
              
              if ($isApproved) {
                echo '<td><div class="signature-line" style="text-align: center; font-size: 9px; color: #007bff; padding-top: 5px;">Digitally Approved via DMS</div>' . htmlspecialchars($sequenceTitles[$seq]) . ': ' . htmlspecialchars($approverName) . '</td>';
              } else {
                echo '<td><div class="signature-line"></div>' . htmlspecialchars($sequenceTitles[$seq]) . ':</td>';
              }
            }
          }
          
          // Fill remaining slots in second row if needed
          for ($i = 2; $i <= 6 && $signatureCount < 3; $i++) {
            if (!in_array($i, $displayedSequences)) {
              $signatureCount++;
              echo '<td><div class="signature-line"></div>' . htmlspecialchars($sequenceTitles[$i] ?? 'Approved by') . ':</td>';
            }
          }
          
          // Ensure we have at least 3 columns in second row
          while ($signatureCount < 3) {
            $signatureCount++;
            echo '<td><div class="signature-line"></div>Approved by:</td>';
          }
          ?>
        </tr>
      </table>

    </div>
  </body>
  </html>
  <?php
}

function generateGeneric_PDF($fr, $items, $breakdowns, $mysqlconn = null) {
  $requestType = (string)($fr['request_type'] ?? '');
  $company = (string)($fr['company_name'] ?? $fr['company'] ?? '');
  $companyCode = (string)($fr['company'] ?? '');
  $payee = trim((string)($fr['user_firstname'] ?? '').' '.(string)($fr['user_lastname'] ?? ''));
  
  // Determine company logo image path
  $logoPath = 'images/' . strtolower($companyCode) . '.jpg';
  $logoExists = file_exists(__DIR__ . '/' . $logoPath);
  
  // If lowercase not found, try uppercase
  if (!$logoExists) {
    $logoPath = 'images/' . strtoupper($companyCode) . '.jpg';
    $logoExists = file_exists(__DIR__ . '/' . $logoPath);
  }
  
  // If still not found, try mixed case
  if (!$logoExists) {
    $logoPath = 'images/' . $companyCode . '.jpg';
    $logoExists = file_exists(__DIR__ . '/' . $logoPath);
  }
  
  if (!$logoExists) {
    $logoPath = 'images/img.jpg'; // fallback to default image
  }
  
  ?>
  <!DOCTYPE html>
  <html>
  <head>
    <meta charset="utf-8">
    <title>Financial Request</title>
    <style>
      body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
      .header { text-align: center; margin-bottom: 30px; }
      .title { font-size: 18px; font-weight: bold; margin-bottom: 20px; }
      table { width: 100%; border-collapse: collapse; margin: 20px 0; }
      th, td { border: 1px solid #000; padding: 8px; text-align: left; }
      th { background-color: #f0f0f0; font-weight: bold; }
      
      /* Print-specific styles */
      @media print {
        body { margin: 0; font-size: 10px; }
        .header { margin-bottom: 20px; }
        .title { margin-bottom: 15px; }
        table { margin: 15px 0; }
        th, td { padding: 4px; font-size: 9px; }
        .page-break { page-break-before: always; }
        
        /* Hide print buttons when printing */
        div[onclick] { display: none !important; }
        
        /* Ensure proper page breaks */
        table { page-break-inside: avoid; }
        
        /* Optimize for A4 paper */
        @page { 
          size: A4; 
          margin: 1cm; 
        }
      }
    </style>
  </head>
  <body>
    <div class="header">
      <?php if ($logoExists) { ?>
        <div style="text-align: center; margin-bottom: 20px;">
          <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="<?php echo htmlspecialchars($company); ?>" style="max-width: 80px; max-height: 80px; border-radius: 50%;">
        </div>
      <?php } ?>
      <div class="title">Financial Request (<?php echo htmlspecialchars($requestType); ?>)</div>
      <div>Generated: <?php echo htmlspecialchars(date('Y-m-d H:i')); ?></div>
    </div>
    
    <table>
      <tr>
        <th>Company</th>
        <td><?php echo htmlspecialchars($company); ?></td>
        <th>Request Type</th>
        <td><?php echo htmlspecialchars($requestType); ?></td>
      </tr>
      <tr>
        <th>Payee</th>
        <td><?php echo htmlspecialchars($payee); ?></td>
        <th>Document Number</th>
        <td><?php echo htmlspecialchars($fr['doc_number'] ?? ''); ?></td>
      </tr>
    </table>
  </body>
  </html>
  <?php
}

// Generate PDF based on request type
$ok = generatePDF($requestType, $fr, $items, $breakdowns);
if ($ok) { exit; }

// Fallback error message
http_response_code(500);
echo 'Failed to generate PDF';
exit;


