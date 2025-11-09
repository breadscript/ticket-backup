<?php 
date_default_timezone_set('Asia/Manila');
include "blocks/inc.resource.php";
require_once 'upload_config.php';
require_once '../class/DisbursementClass.php';
require_once __DIR__ . '/attachment_display_helper.php';
require_once __DIR__ . '/transaction_history_helper.php';
$moduleid = 10;
include "proxy.php";
// Check permission for Disbursement (module ID 10)
// $hasPermission = false;
// if (isset($_SESSION['userid']) && isset($mysqlconn) && $mysqlconn) {
//     $userId = (int)$_SESSION['userid'];
//     $moduleId = 10; // Disbursement module ID
    
//     $permissionQuery = "SELECT COUNT(*) as count FROM sys_authoritymoduletb sam 
//                        WHERE sam.moduleid = ? AND sam.userid = ? AND sam.statid = 1";
    
//     if ($stmt = mysqli_prepare($mysqlconn, $permissionQuery)) {
//         mysqli_stmt_bind_param($stmt, 'ii', $moduleId, $userId);
//         mysqli_stmt_execute($stmt);
//         $result = mysqli_stmt_get_result($stmt);
//         $row = mysqli_fetch_assoc($result);
//         $hasPermission = ($row && (int)$row['count'] > 0);
//         mysqli_stmt_close($stmt);
//     }
// }

// // Check if no permission and handle gracefully
// if (!$hasPermission) {
//     // Check if this is already a reload attempt to prevent infinite loop
//     if (!isset($_SESSION['no_permission_checked'])) {
//         $_SESSION['no_permission_checked'] = true;
//         echo '<script>location.reload();</script>';
//         exit;
//     } else {
//         // Clear the session flag and show access denied message
//         unset($_SESSION['no_permission_checked']);
//         echo '<div style="text-align:center; padding:50px; font-family:Arial;">
//                 <h2>Access Denied</h2>
//                 <p>You do not have permission to access this module.</p>
//                 <p><a href="javascript:history.back()">Go Back</a></p>
//               </div>';
//         exit;
//     }
// }

// Helper for HTML escaping
function h($val) { return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8'); }

// Helper function to get full file path for attachments
function getAttachmentFilePath($filename) {
  if (empty($filename)) return '';
  // If the path already includes the upload directory, use it as is
  if (strpos($filename, 'uploads/') === 0) {
    return $filename;
  }
  // Otherwise, construct the full path
  return ATTACHMENTS_DIR . $filename;
}

// Helper function to get full file path
function getFullFilePath($filename, $type = 'attachment') {
  if (empty($filename)) return '';
  // If the path already includes the upload directory, use it as is
  if (strpos($filename, 'uploads/') === 0) {
    return $filename;
  }
  // Otherwise, construct the full path
  switch ($type) {
    case 'supporting':
      return SUPPORTING_DOCS_DIR . $filename;
    case 'attachment':
    default:
      return ATTACHMENTS_DIR . $filename;
  }
}

// Resolve an actor identifier (numeric id or username or name text) to a display name "First Last"
function wf_resolve_actor_fullname($conn, $actorId) {
  static $cache = [];
  $key = (string)$actorId;
  if ($key === '') return '';
  if (isset($cache[$key])) return $cache[$key];
  $display = '';
  try {
    if ($conn) {
      if (ctype_digit($key)) {
        if ($st = mysqli_prepare($conn, "SELECT CONCAT(COALESCE(user_firstname,''),' ',COALESCE(user_lastname,'')) AS full FROM sys_usertb WHERE id = ? LIMIT 1")) {
          $idInt = (int)$key;
          mysqli_stmt_bind_param($st, 'i', $idInt);
          mysqli_stmt_execute($st);
          $rs = mysqli_stmt_get_result($st);
          if ($row = mysqli_fetch_assoc($rs)) { $display = trim((string)($row['full'] ?? '')); }
          mysqli_stmt_close($st);
        }
      }
      if ($display === '' && !ctype_digit($key)) {
        if ($st2 = mysqli_prepare($conn, "SELECT CONCAT(COALESCE(user_firstname,''),' ',COALESCE(user_lastname,'')) AS full FROM sys_usertb WHERE username = ? LIMIT 1")) {
          mysqli_stmt_bind_param($st2, 's', $key);
          mysqli_stmt_execute($st2);
          $rs2 = mysqli_stmt_get_result($st2);
          if ($row2 = mysqli_fetch_assoc($rs2)) { $display = trim((string)($row2['full'] ?? '')); }
          mysqli_stmt_close($st2);
        }
      }
    }
  } catch (Throwable $e) { /* ignore */ }
  if ($display === '') { $display = $key; }
  return $cache[$key] = $display;
}

// Clean URL handling: accept id once, store in session, then redirect to clean URL
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
$__VIEW_KEY = 'dmt_view_id';
$__VIEW_EXP = 'dmt_view_exp';
$__VIEW_TTL = 900; // 15 minutes
$__CLEAN_URL = 'disbursement_view.php';

// If id is provided, store and redirect to clean URL (no query string)
if (isset($_GET['id']) && $_GET['id'] !== '') {
	$_SESSION[$__VIEW_KEY] = (int)$_GET['id'];
	$_SESSION[$__VIEW_EXP] = time() + $__VIEW_TTL;
	header('Location: ' . $__CLEAN_URL);
	exit;
}

// Resolve id from session, enforce expiry
$id = 0;
if (isset($_SESSION[$__VIEW_KEY], $_SESSION[$__VIEW_EXP]) && time() <= (int)$_SESSION[$__VIEW_EXP]) {
	$id = (int)$_SESSION[$__VIEW_KEY];
} else {
	// Gracefully redirect instead of showing 403
	$fallback = 'disbursement_main.php';
	if (!empty($_SERVER['HTTP_REFERER'])) {
		header('Location: ' . $_SERVER['HTTP_REFERER']);
	} else {
		header('Location: ' . $fallback);
	}
	exit;
}
$fr = null;
if ($id > 0 && isset($mysqlconn) && $mysqlconn) {
  $stmt = mysqli_prepare($mysqlconn, "SELECT f.*, c.company_name, d.department_name,
                                      u.user_firstname, u.user_lastname,
                                      creator.user_firstname as creator_firstname, creator.user_lastname as creator_lastname
                                      FROM financial_requests f 
                                      LEFT JOIN company c ON f.company = c.company_code 
                                      LEFT JOIN department d ON f.cost_center = d.department_code 
                                      LEFT JOIN sys_usertb u ON u.id = f.payee
                                      LEFT JOIN sys_usertb creator ON creator.id = f.created_by_user_id
                                      WHERE f.id = ?");
  if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $fr = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
  }
}

// If not found, show a simple message page
if (!$fr) {
  echo '<body class="nav-md"><div class="container body"><div class="main_container">';
  include "blocks/navigation.php";
  include "blocks/header.php";
  echo '<div class="right_col" role="main"><div class="page-title"><div class="title_left"><h3>Record not found</h3></div></div><div class="clearfix"></div>';
  echo '<div class="x_panel"><div class="x_content">The requested record does not exist.</div></div></div>';
  echo '</div></div></body></html>';
  exit;
}

// Enforce ownership: only the user who created the record can view it
if ($fr && isset($_SESSION['userid']) && (string)$fr['created_by_user_id'] !== (string)$_SESSION['userid']) {
  http_response_code(403);
  echo 'Forbidden: you are not allowed to view this record.';
  exit;
}

// Map DB fields to form defaults
$requestType = $fr['request_type'] ?? '';
$company = $fr['company'] ?? '';
$docType = $fr['doc_type'] ?? '';
$docNumber = $fr['doc_number'] ?? '';
$docDate = $fr['doc_date'] ?? '';
$costCenter = $fr['cost_center'] ?? '';
$expenditureType = $fr['expenditure_type'] ?? '';
$currency = $fr['currency'] ?? '';
$balance = $fr['balance'] ?? '';
$budget = $fr['budget'] ?? '';
$supportingPath = $fr['supporting_document_path'] ?? '';
$payee = '';
if (!empty($fr['user_firstname']) || !empty($fr['user_lastname'])) {
  $payee = trim(((string)($fr['user_firstname'] ?? '')).' '.((string)($fr['user_lastname'] ?? '')));
} elseif (!empty($fr['payee'])) {
  // Fallback to raw payee value from DB when no user match
  $payee = (string)$fr['payee'];
}
$amountFigures = $fr['amount_figures'] ?? '';
$amountWords = $fr['amount_in_words'] ?? '';
$paymentFor = $fr['payment_for'] ?? '';
$specialInstructions = $fr['special_instructions'] ?? '';
$isBudgeted = (string)($fr['is_budgeted'] ?? '');
$fromCompany = $fr['from_company'] ?? '';
$toCompany = $fr['to_company'] ?? '';
$creditToPayroll = (string)($fr['credit_to_payroll'] ?? '');
$issueCheck = (string)($fr['issue_check'] ?? '');

// ===== Workflow data (approvers) =====
// Centralized via DisbursementClass
$workflowData = DisbursementClass::getWorkflowData($mysqlconn, $requestType, $company, $costCenter, $docNumber, $payee);
$approverSteps = $workflowData['approverSteps'];
$processStatus = $workflowData['processStatus'];
$existingSequences = $workflowData['existingSequences'];

// Check if sequence 1 (requestor) has "Submit" status
$sequence1Status = '';
$sequence1Key = '1|Requestor|Requestor';
if (isset($processStatus[$sequence1Key])) {
    $sequence1Status = strtoupper(trim($processStatus[$sequence1Key]));
}

// Check if any sequence 2 approver has approved
$sequence2Approved = false;
foreach ($processStatus as $key => $status) {
    $parts = explode('|', $key);
    if (count($parts) >= 1 && (int)$parts[0] === 2) {
        if (strtoupper(trim($status)) === 'APPROVED') {
            $sequence2Approved = true;
            break;
        }
    }
}

// Check if sequence 2 has been returned to requestor
$sequence2ReturnedToRequestor = false;
foreach ($processStatus as $key => $status) {
    $parts = explode('|', $key);
    if (count($parts) >= 1 && (int)$parts[0] === 2) {
        $statusUpper = strtoupper(trim($status));
        if (strpos($statusUpper, 'RETURN') !== false && strpos($statusUpper, 'REQUESTOR') !== false) {
            $sequence2ReturnedToRequestor = true;
            break;
        }
    }
}

// Filter approver steps to show existing sequences plus the next expected sequence
$filteredApproverSteps = [];
foreach ($approverSteps as $seq => $actors) {
  if (in_array($seq, $existingSequences)) {
    // Show existing sequences
    $filteredApproverSteps[$seq] = $actors;
  } elseif ($seq === 2 && ($sequence1Status === 'SUBMITTED' || $sequence1Status === 'SUBMIT') && !in_array(2, $existingSequences)) {
    // Show sequence 2 if sequence 1 is submitted but sequence 2 doesn't exist yet
    // This handles the case where sequence 2 was deleted due to return to requestor
    $filteredApproverSteps[$seq] = $actors;
  }
}
$approverSteps = $filteredApproverSteps;

// Helper to get status text/color
function wf_status_badge($status) {
  $s = strtoupper(trim((string)$status));
  if ($s === 'DONE' || $s === 'APPROVED') return '<span class="label label-success">'.h($status).'</span>';
  if ($s === 'SUBMIT' || $s === 'SUBMITTED') return '<span class="label label-primary">'.h($status).'</span>';
  if ($s === 'DECLINED' || $s === 'REJECTED') return '<span class="label label-danger">'.h($status).'</span>';
  if ($s === 'RETURN TO APPROVER' || $s === 'RETURN TO REQUESTOR') return '<span class="label label-warning">'.h($status).'</span>';
  if ($s === 'WAITING FOR APPROVAL') return '<span class="label label-info">'.h($status).'</span>';
  if ($s) return '<span class="label label-info">'.h($status).'</span>';
  return '<span class="label label-default">Pending</span>';
}

// Compute active sequence
// Prefer the earliest sequence that is explicitly Waiting for Approval.
// If none are Waiting, fall back to the earliest Submitted sequence.
$activeSequence = null;
if (!empty($processStatus)) {
  $waitingSeqs = [];
  $submittedSeqs = [];
  foreach ($processStatus as $key => $st) {
    // key format: seq|actor|action
    $parts = explode('|', $key, 2);
    $seq = (int)$parts[0];
    $status = strtoupper(trim((string)$st));
    if ($status === 'WAITING FOR APPROVAL') {
      $waitingSeqs[$seq] = true;
    } elseif ($status === 'SUBMIT' || $status === 'SUBMITTED') {
      $submittedSeqs[$seq] = true;
    }
  }
  if (!empty($waitingSeqs)) {
    $activeSequence = min(array_keys($waitingSeqs));
  } elseif (!empty($submittedSeqs)) {
    $activeSequence = min(array_keys($submittedSeqs));
  }
}
if ($activeSequence === null) {
  // If no process yet, start at 1
  $activeSequence = 1;
}

// Note: Cost Center Head and other sequences will be created progressively when previous sequences are approved
// Fetch child rows: items
$items = [];
if (isset($mysqlconn) && $mysqlconn) {
       $stmtItems = mysqli_prepare($mysqlconn, "SELECT i.id, i.category, i.description,  i.reference_number, i.po_number, i.due_date, i.cash_advance, i.form_of_payment,  i.gross_amount, i.vatable, i.vat_amount, i.withholding_tax, i.amount_withhold, i.net_payable_amount, i.carf_no, i.pcv_no, i.invoice_date, i.invoice_number, i.supplier_name, i.tin, i.address, c.category_name, c.sap_account_description 
                                               FROM financial_request_items i 
                                               LEFT JOIN categories c ON i.category = c.category_id 
                                               WHERE i.doc_number = ? 
                                               ORDER BY i.id");
  if ($stmtItems) {
    mysqli_stmt_bind_param($stmtItems, 's', $docNumber);
    mysqli_stmt_execute($stmtItems);
    $resItems = mysqli_stmt_get_result($stmtItems);
    while ($row = mysqli_fetch_assoc($resItems)) { $items[] = $row; }
    mysqli_stmt_close($stmtItems);
  }
  // Show all items exactly as stored; do not de-duplicate here
}

// Derive display amount from first available item if present
if (!$amountFigures) {
  if (!empty($items) && isset($items[0]['amount'])) {
    $amountFigures = $items[0]['amount'];
  }
}

// Fetch transaction history from work_flow_action_log with deduplication and user name resolution
$transactionHistory = [];
if (isset($mysqlconn) && $mysqlconn) {
  $stmtHistory = mysqli_prepare($mysqlconn, "SELECT 
    w.actor_id, 
    w.new_status, 
    w.created_at, 
    w.remarks,
    w.sequence,
    w.event,
    CASE 
      WHEN w.actor_id REGEXP '^[0-9]+$' THEN CONCAT(u.user_firstname, ' ', u.user_lastname)
      ELSE w.actor_id
    END as actor_display_name
    FROM work_flow_action_log w
    LEFT JOIN sys_usertb u ON w.actor_id REGEXP '^[0-9]+$' AND u.id = CAST(w.actor_id AS UNSIGNED)
    WHERE w.doc_type = ? AND w.doc_number = ? 
    ORDER BY w.created_at ASC, w.sequence ASC");
  if ($stmtHistory) {
    mysqli_stmt_bind_param($stmtHistory, 'ss', $requestType, $docNumber);
    mysqli_stmt_execute($stmtHistory);
    $resHistory = mysqli_stmt_get_result($stmtHistory);
    
    // Deduplicate and process history
    $seen = [];
    while ($row = mysqli_fetch_assoc($resHistory)) { 
      // Skip entries with "Skipped" status (Peer Approved)
      $statusUpper = strtoupper(trim((string)($row['new_status'] ?? '')));
      if (strpos($statusUpper, 'SKIPPED') !== false) {
        continue;
      }
      
      // Skip system-generated entries
      $actorId = strtolower(trim((string)($row['actor_id'] ?? '')));
      if ($actorId === 'system') {
        continue;
      }
      
      // Create a unique key to identify duplicates
      $key = $row['actor_id'] . '|' . $row['sequence'] . '|' . $row['new_status'] . '|' . $row['created_at'];
      
      // Only add if we haven't seen this exact combination before
      if (!isset($seen[$key])) {
        $seen[$key] = true;
        
        // Use the actor_display_name from the database query (which resolves user IDs to full names)
        // Don't override with payee name - use the actual actor from the workflow log
        
        $transactionHistory[] = $row;
      }
    }
    mysqli_stmt_close($stmtHistory);
  }
}
?>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <!-- left navigation -->
        <?php include "blocks/navigation.php"; ?>
        <!-- /left navigation -->

        <!-- top navigation -->
        <?php include "blocks/header.php"; ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
              <div class="title_left">
                <h3>Disbursement View</h3>
              </div>
            </div>
            <div class="clearfix"></div>
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Header Details <small>readonly</small></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    <form id="view-form" class="form-horizontal form-label-left" autocomplete="off">

                      <!-- HEADER SECTION -->
                      <div class="x_panel">
                        <div class="x_title">
                          <h3><i class="fa fa-header"></i> Header</h3>
                          <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                          <div class="form-group">
                            <div class="col-md-3 col-sm-3 col-xs-12">
                              <label>Request Type</label>
                              <select id="requestType" name="request_type" class="form-control" disabled>
                                <option value="">Choose..</option>
                                <option value="ERGR" <?php echo ($requestType==='ERGR'?'selected':''); ?>>Expense Report (General Reimbursement)</option>
                                <option value="ERL" <?php echo ($requestType==='ERL'?'selected':''); ?>>Expense Report (Liquidation)</option>
                                <option value="RFP" <?php echo ($requestType==='RFP'?'selected':''); ?>>Request For Payment</option>
                              </select>
                            </div>
                            <div class="col-md-3 col-sm-3 col-xs-12">
                              <label>Company</label>
                              <input type="text" id="company" name="company" class="form-control" value="<?php echo h($company); ?>" readonly>
                            </div>
                            <!-- <div class="col-md-3 col-sm-3 col-xs-12" style="display:none;">
                              <label>Doc Type</label>
                              <input type="text" id="doc_type" name="doc_type" class="form-control" value="<?php echo h($docType); ?>" readonly>
                            </div> -->
                            <div class="col-md-3 col-sm-3 col-xs-12">
                              <label id="doc_number_label">Doc Number</label>
                              <input type="text" id="doc_number" name="doc_number" class="form-control" value="<?php echo h($docNumber); ?>" readonly>
                            </div>
                          </div>

                          <div class="form-group">
                            <div class="col-md-3 col-sm-3 col-xs-12">
                              <label>Doc Date</label>
                              <input type="date" id="doc_date" name="doc_date" class="form-control" value="<?php echo h($docDate); ?>" readonly>
                            </div>
                            <div class="col-md-3 col-sm-3 col-xs-12">
                              <label>Cost Center/Department</label>
                              <input type="text" id="cost_center" name="cost_center" class="form-control" value="<?php echo h($fr['department_name'] ?? $costCenter); ?>" readonly>
                            </div>
                            <div class="col-md-3 col-sm-3 col-xs-12">
                              <label>Expenditure Type</label>
                              <select id="expenditure_type" name="expenditure_type" class="form-control" disabled>
                                <option value="">Choose..</option>
                                <option value="capex" <?php echo ($expenditureType==='capex'?'selected':''); ?>>Capex</option>
                                <option value="opex" <?php echo ($expenditureType==='opex'?'selected':''); ?>>Opex</option>
                              </select>
                            </div>
                            <div class="col-md-3 col-sm-3 col-xs-12">
                              <label>Currency</label>
                              <select id="currency" name="currency" class="form-control" disabled>
                                <option value="">Choose..</option>
                                <?php foreach (["USD","EUR","JPY","GBP","AUD","CAD","CHF","CNY","PHP"] as $cur) { ?>
                                  <option value="<?php echo $cur; ?>" <?php echo ($currency===$cur?'selected':''); ?>><?php echo $cur; ?></option>
                                <?php } ?>
                              </select>
                            </div>
                          </div>

                          <div class="form-group">
                          <div class="col-md-4 col-sm-4 col-xs-12" id="field_is_budgeted_col">
                              <label>Tag (Is it budgeted)</label>
                              <select id="is_budgeted" name="is_budgeted" class="form-control" disabled>
                                <option value="">Choose..</option>
                                <option value="yes" <?php echo ($isBudgeted==='1' || strtolower($isBudgeted)==='yes')?'selected':''; ?>>Yes</option>
                                <option value="no" <?php echo ($isBudgeted==='0' || strtolower($isBudgeted)==='no')?'selected':''; ?>>No</option>
                              </select>
                            </div>
                            <div class="col-md-4 col-sm-4 col-xs-12">
                              <label>Balance</label>
                              <input type="text" id="balance" name="balance" class="form-control currency-view" value="<?php echo h($balance); ?>" readonly>
                            </div>
                            <div class="col-md-4 col-sm-4 col-xs-12">
                              <label>Budget</label>
                              <input type="text" id="budget" name="budget" class="form-control currency-view" value="<?php echo h($budget); ?>" readonly>
                            </div>

                          </div>
                        </div>
                      </div>

                      <!-- ITEM DETAILS SECTION (list of items) -->
                      <div class="x_panel">
                        <div class="x_title">
                          <h3><i class="fa fa-list"></i> Item Details</h3>
                          <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                          <?php if (empty($items)) { ?>
                            <div class="alert alert-info">No item details recorded.</div>
                          <?php } else { ?>
                            <?php foreach ($items as $idx => $it) { ?>
                              <div class="well" style="padding:10px">
                                <div class="form-group">
                                  <div class="col-md-3 col-sm-3 col-xs-12" id="field_category_col">
                                    <label>Category</label>
                                    <input type="text" class="form-control" value="<?php echo h(trim(((string)($it['category_name'] ?? '')) . (isset($it['sap_account_description']) && $it['sap_account_description'] !== '' ? (' - ' . (string)$it['sap_account_description']) : '')) ?: $it['category']); ?>" readonly>
                                  </div>
                                  <div class="col-md-2 col-sm-2 col-xs-12" id="field_attachment_col">
                                    <label>Attachment</label>
                                    <?php 
                                      // Get item ID for this item to fetch attachments
                                      $itemId = $it['id'] ?? null;
                                      if ($itemId) {
                                        displayAttachments($docNumber, $itemId, 'item_attachment', false);
                                      } else {
                                        echo '<div class="text-muted">No attachments</div>';
                                      }
                                    ?>
                                  </div>
                                  <div class="col-md-4 col-sm-4 col-xs-12" id="field_description_col">
                                    <label>Description</label>
                                    <textarea class="form-control" rows="2" readonly><?php echo h($it['description']); ?></textarea>
                                  </div>
                                </div>

                                <div class="form-group">
                                  <div class="col-md-4 col-sm-4 col-xs-12" id="field_reference_number_col">
                                    <label>Reference Number</label>
                                    <input type="text" class="form-control" value="<?php echo h($it['reference_number']); ?>" readonly>
                                  </div>
                                  <div class="col-md-4 col-sm-4 col-xs-12" id="field_po_number_col">
                                    <label>PO Number</label>
                                    <input type="text" class="form-control" value="<?php echo h($it['po_number']); ?>" readonly>
                                  </div>
                                </div>

                                <div class="form-group">
                                  <div class="col-md-3 col-sm-3 col-xs-12" id="field_due_date_col">
                                    <label>Due Date</label>
                                    <input type="date" class="form-control" value="<?php echo h($it['due_date']); ?>" readonly>
                                  </div>
                                  <div class="col-md-3 col-sm-3 col-xs-12" id="field_cash_advance_col">
                                    <label>Cash Advance?</label>
                                    <div>
                                      <span class="label label-default"><?php echo ((string)$it['cash_advance']==='1') ? 'Yes' : 'No'; ?></span>
                                    </div>
                                  </div>
                                  <div class="col-md-6 col-sm-6 col-xs-12" id="field_form_of_payment_col">
                                    <label>Form of Payment:</label>
                                    <input type="text" class="form-control" value="<?php echo h($it['form_of_payment']); ?>" readonly>
                                  </div>
                                </div>


                                <!-- ERGR/ERL specific fields display -->
                                <div class="form-group">
                                  <div class="col-md-2 col-sm-6 col-xs-12" id="field_carf_no_col">
                                    <label>CARF No.</label>
                                    <input type="text" class="form-control" value="<?php echo h($it['carf_no']); ?>" readonly>
                                  </div>
                                  <div class="col-md-2 col-sm-6 col-xs-12" id="field_pcv_no_col">
                                    <label>PCV No.</label>
                                    <input type="text" class="form-control" value="<?php echo h($it['pcv_no']); ?>" readonly>
                                  </div>
                                  <div class="col-md-2 col-sm-6 col-xs-12" id="field_invoice_date_col">
                                    <label>Invoice Date</label>
                                    <input type="text" class="form-control" value="<?php echo h($it['invoice_date']); ?>" readonly>
                                  </div>
                                  <div class="col-md-2 col-sm-6 col-xs-12" id="field_invoice_number_col">
                                    <label>Invoice #</label>
                                    <input type="text" class="form-control" value="<?php echo h($it['invoice_number']); ?>" readonly>
                                  </div>
                                  <div class="col-md-4 col-sm-6 col-xs-12" id="field_supplier_name_col">
                                    <label>Supplier's/Store Name</label>
                                    <input type="text" class="form-control" value="<?php echo h($it['supplier_name']); ?>" readonly>
                                  </div>
                                </div>
                                
                                <div class="form-group">
                                  <div class="col-md-2 col-sm-6 col-xs-12" id="field_tin_col">
                                    <label>TIN</label>
                                    <input type="text" class="form-control" value="<?php echo h($it['tin']); ?>" readonly>
                                  </div>
                                  <div class="col-md-10 col-sm-6 col-xs-12" id="field_address_col">
                                    <label>Address</label>
                                    <textarea class="form-control" rows="2" readonly><?php echo h($it['address']); ?></textarea>
                                  </div>
                                </div>
                                
                                <!-- Tax-related fields display -->
                                <div class="form-group">
                                  <div class="col-md-2 col-sm-3 col-xs-12" id="field_gross_amount_col">
                                    <label>Gross Amount</label>
                                    <input type="text" class="form-control currency-view" value="<?php echo h($it['gross_amount']); ?>" readonly>
                                  </div>
                                  <div class="col-md-2 col-sm-3 col-xs-12" id="field_vatable_col">
                                    <label>Vatable?</label>
                                    <div>
                                      <span class="label label-default"><?php echo ((string)$it['vatable']==='yes') ? 'Yes' : 'No'; ?></span>
                                    </div>
                                  </div>
                                  <div class="col-md-2 col-sm-3 col-xs-12" id="field_vat_amount_col">
                                    <label>VAT Amount</label>
                                    <input type="text" class="form-control currency-view" value="<?php echo h($it['vat_amount']); ?>" readonly>
                                  </div>
                                  <div class="col-md-3 col-sm-3 col-xs-12" id="field_withholding_tax_col">
                                    <label>Withholding Tax/Final Tax</label>
                                    <input type="text" class="form-control" value="<?php echo h($it['withholding_tax']); ?>" readonly>
                                  </div>
                                  <div class="col-md-3 col-sm-3 col-xs-12" id="field_amount_withhold_col">
                                    <label>Amount Withhold</label>
                                    <input type="text" class="form-control currency-view" value="<?php echo h($it['amount_withhold']); ?>" readonly>
                                  </div>
                                </div>
                                
                                <div class="form-group">
                                  <div class="col-md-4 col-sm-6 col-xs-12" id="field_net_payable_col">
                                    <label>Net Payable Amount</label>
                                    <input type="text" class="form-control currency-view" value="<?php echo h($it['net_payable_amount']); ?>" readonly>
                                  </div>
                                </div>
                                
                              </div>
                            <?php } ?>
                          <?php } ?>
                        </div>
                      </div>


                      <!-- FOOTER SECTION -->
                      <div class="x_panel">
                        <div class="x_title">
                          <h3><i class="fa fa-credit-card"></i> Footer</h3>
                          <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                          <div class="form-group">
                            <div class="col-md-4 col-sm-4 col-xs-12" id="field_payee_col">
                              <label>Payee</label>
                              <input type="text" id="payee" name="payee" class="form-control" value="<?php echo h($payee); ?>" readonly>
                            </div>
                            <div class="col-md-4 col-sm-4 col-xs-12" id="field_amount_figures_col">
                              <label>Amount in Figures</label>
                              <input type="text" id="amount_figures" name="amount_figures" class="form-control currency-view" value="<?php echo h($amountFigures); ?>" readonly>
                            </div>
                            <div class="col-md-4 col-sm-4 col-xs-12" id="field_amount_words_col">
                              <label>Amount in Words</label>
                              <input type="text" id="amount_words" name="amount_words" class="form-control" value="<?php echo h($amountWords); ?>" readonly>
                            </div>
                          </div>

                          <div class="form-group">
                            <div class="col-md-12 col-sm-12 col-xs-12" id="field_payment_for_col">
                              <label for="payment_for">Payment For</label>
                              <textarea id="payment_for" class="form-control" name="payment_for" readonly><?php echo h($paymentFor); ?></textarea>
                            </div>
                          </div>

                          <div class="form-group">
                            <div class="col-md-12 col-sm-12 col-xs-12" id="field_special_instruction_col">
                              <label for="special_instructions">Special Instructions</label>
                              <textarea id="special_instructions" class="form-control" name="special_instructions" readonly><?php echo h($specialInstructions); ?></textarea>
                            </div>
                          </div>

                          <div class="form-group">
                            <div class="col-md-6 col-sm-6 col-xs-12" id="field_supporting_document_col">
                              <label>Supporting Document</label>
                              <?php displayAttachments($docNumber, null, 'supporting_document', false); ?>
                            </div>
                          </div>

                          <div class="form-group">
                            <div class="col-md-4 col-sm-4 col-xs-12" id="field_from_company_col">
                              <label>From Company</label>
                              <input type="text" id="from_company" name="from_company" class="form-control" value="<?php echo h($fromCompany); ?>" readonly>
                            </div>
                            <div class="col-md-4 col-sm-4 col-xs-12" id="field_to_company_col">
                              <label>To Company</label>
                              <input type="text" id="to_company" name="to_company" class="form-control" value="<?php echo h($toCompany); ?>" readonly>
                            </div>
                            <div class="col-md-4 col-sm-4 col-xs-12" id="field_credit_to_payroll_col">
                              <div class="radio" style="margin-top: 25px;">
                                <label>
                                  <input type="radio" id="credit_to_payroll" name="payment_option" value="credit_to_payroll" <?php echo ($creditToPayroll==='1')?'checked':''; ?> disabled> Credit To Payroll
                                </label>
                              </div>
                            </div>
                            <div class="col-md-4 col-sm-4 col-xs-12" id="field_issue_check_col">
                              <div class="radio" style="margin-top: 25px;">
                                <label>
                                  <input type="radio" id="issue_check" name="payment_option" value="issue_check" <?php echo ($issueCheck==='1')?'checked':''; ?> disabled> Issue Check
                                </label>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>


                                            <!-- TRANSACTION HISTORY SECTION -->
                                            <div class="x_panel">
                        <div class="x_title">
                          <h3><i class="fa fa-history"></i> Transaction History</h3>
                          <ul class="nav navbar-right panel_toolbox">
                            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                          </ul>
                          <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                          <?php if (empty($transactionHistory)) { ?>
                            <div class="alert alert-info">No transaction history recorded.</div>
                          <?php } else { ?>
                            <div class="table-responsive" style="max-height:300px; overflow-y:auto;">
                              <table class="table table-striped table-bordered" style="margin-bottom:0;">
                                <thead>
                                  <tr>
                                    <th style="background-color: #555; color: white;">Step</th>
                                    <th style="background-color: #555; color: white;">Approver</th>
                                    <th style="background-color: #555; color: white;">Status</th>
                                    <th style="background-color: #555; color: white;">Date & Time</th>
                                    <th style="background-color: #555; color: white;">Remarks</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <?php echo renderTransactionHistory($transactionHistory, null, 'table'); ?>
                                </tbody>
                              </table>
                            </div>
                          <?php } ?>
                        </div>
                      </div>
                      
                    <!-- APPROVALS (WORKFLOW) SECTION - Updated -->
                    <div class="x_panel">
                        <div class="x_title">
                            <h3><i class="fa fa-users"></i> Approvers</h3>
                            <div class="pull-right" style="margin-top:6px;">
                                <?php if ($activeSequence === 1 && $sequence1Status !== 'SUBMITTED' && $sequence1Status !== 'SUBMIT') { ?>
                                    <a href="disbursement_edit_form.php?id=<?php echo (int)$id; ?>" class="btn btn-sm btn-info btn-edit"><i class="fa fa-edit"></i> Edit / Add Evidence</a>
                                <?php } ?>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <div class="row">
                                <?php
                                ksort($approverSteps);
                                $colClass = 'col-md-2 col-sm-4 col-xs-6';
                                foreach ($approverSteps as $seq => $actors) {
                                    echo '<div class="'. $colClass .'" style="text-align:center; margin-bottom:15px;">';
                                    $isParallelGroup = (count($actors) > 1 && !empty($actors[0]['is_parallel']));
                                    
                                    // For parallel-one-approval steps, when any approver already acted,
                                    // only show the approver who acted (hide peers that are skipped/pending).
                                    $actorsToRender = $actors;
                                    if ($isParallelGroup) {
                                        $note = isset($actors[0]['note']) ? (string)$actors[0]['note'] : '';
                                        $oneApprovalOnly = (stripos($note, 'one approval only') !== false || stripos($note, 'one approval') !== false);
                                        if ($oneApprovalOnly) {
                                            $strongActed = false;
                                            $actedKeys = [];
                                            foreach ($actors as $a0) {
                                                $k0 = $seq.'|'.trim((string)$a0['actor_id']).'|'.trim((string)$a0['action']);
                                                $st0 = isset($processStatus[$k0]) ? strtoupper(trim((string)$processStatus[$k0])) : '';
                                                if ($st0 && $st0 !== 'WAITING FOR APPROVAL' && $st0 !== 'SUBMITTED' && $st0 !== 'SUBMIT') {
                                                    // Ignore SKIPPED peers entirely
                                                    if (strpos($st0, 'SKIPPED') !== false) { continue; }
                                                    if (strpos($st0, 'APPROVED') !== false || strpos($st0, 'DECLINED') !== false || strpos($st0, 'RETURN') !== false || strpos($st0, 'CANCEL') !== false) {
                                                        $strongActed = true;
                                                        $actedKeys[$k0] = true;
                                                    }
                                                }
                                            }
                                            if ($strongActed) {
                                                $filtered = [];
                                                foreach ($actors as $a1) {
                                                    $k1 = $seq.'|'.trim((string)$a1['actor_id']).'|'.trim((string)$a1['action']);
                                                    if (isset($actedKeys[$k1])) { $filtered[] = $a1; }
                                                }
                                                if (!empty($filtered)) { $actorsToRender = $filtered; }
                                            }
                                        }
                                    }

                                    foreach ($actorsToRender as $a) {
                                        $key = $seq.'|'.trim((string)$a['actor_id']).'|'.trim((string)$a['action']);
                                        $status = isset($processStatus[$key]) ? $processStatus[$key] : '';
                                        
                                        // Debug: Log status for requestor (sequence 1)
                                        if ($seq === 1) {
                                            echo '<!-- DEBUG: Requestor status key: ' . $key . ' -->';
                                            echo '<!-- DEBUG: Requestor status value: ' . $status . ' -->';
                                            echo '<!-- DEBUG: All processStatus keys: ' . implode(', ', array_keys($processStatus)) . ' -->';
                                        }
                                        
                                        echo '<div style="margin-bottom:12px;">';
                                        $actorLabel = ($seq >= 2) ? wf_resolve_actor_fullname($mysqlconn, (string)$a['actor_id']) : (string)$a['actor_id'];
                                        if ($seq >= 2) { $actorLabel = strtoupper($actorLabel); }
                                        echo '<div style="height:16px; font-size:12px; color:#777; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">'.h($actorLabel).'</div>';
                                        echo '<div style="border-bottom:1px solid #ddd; width:70%; margin:6px auto 6px;"></div>';
                                        echo '<div style="font-size:11px; color:#888;">'.h(str_replace('_',' ',(string)$a['action'])).'</div>';
                                        echo '<div style="margin-top:6px;">'. wf_status_badge($status) .'</div>';
                                        
                                        // Action buttons logic
                                        $isActiveSeq = ($seq === $activeSequence);
                                        $statusUpper = strtoupper(trim((string)$status));
                                        $isPending = empty($status) || $statusUpper === 'WAITING FOR APPROVAL' || $statusUpper === 'SUBMITTED' || $statusUpper === 'SUBMIT';
                                        
                                        if ($seq === 1) {
                                            // Sequence 1 (Requestor) logic
                                            if (($statusUpper === 'SUBMITTED' || $statusUpper === 'SUBMIT') && !$sequence2Approved) {
                                                // Show cancel button when submitted and sequence 2 hasn't approved yet
                                                echo '<div style="margin-top:8px;">'
                                                    .'<button type="button" class="btn btn-xs btn-danger wf-act" data-act="CANCEL" data-seq="'.h($seq).'" data-actor="'.h($a['actor_id']).'" data-role="'.h($a['action']).'">Cancel</button>'
                                                    .'</div>';
                                            } elseif ($statusUpper === 'WAITING FOR APPROVAL') {
                                                // Show submit and cancel buttons when status is "Waiting for Approval" (returned from sequence 2 or initial state)
                                                echo '<div style="margin-top:8px;">'
                                                    .'<button type="button" class="btn btn-xs btn-primary wf-act" data-act="SUBMIT" data-seq="'.h($seq).'" data-actor="'.h($a['actor_id']).'" data-role="'.h($a['action']).'">Submit</button> '
                                                    .'<button type="button" class="btn btn-xs btn-danger wf-act" data-act="CANCEL" data-seq="'.h($seq).'" data-actor="'.h($a['actor_id']).'" data-role="'.h($a['action']).'">Cancel</button>'
                                                    .'</div>';
                                            }
                                        } elseif ($seq === 2 && $isPending) {
                                            // Requestor view: do not show approver buttons
                                        } elseif ($seq > 2 && $isActiveSeq && $isPending) {
                                            // Requestor view: do not show approver buttons
                                        }
                                        
                                        echo '</div>';
                                    }
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>



                      <div class="ln_solid"></div>
                      <div class="form-group">
                        <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                          <a class="btn btn-default" href="disbursement_main.php">Back</a>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
        <footer>
          <div class="pull-right">
            Gentelella - Bootstrap Admin Template by <a href="https://colorlib.com">Colorlib</a>
          </div>
          <div class="clearfix"></div>
        </footer>
        <!-- /footer content -->
      </div>
    </div>

    <!-- jQuery -->
    <script src="../srv-v2/vendors/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap -->
    <script src="../srv-v2/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
    <!-- FastClick -->
    <script src="../srv-v2/vendors/fastclick/lib/fastclick.js"></script>
    <!-- NProgress -->
    <script src="../srv-v2/vendors/nprogress/nprogress.js"></script>
    <!-- bootstrap-progressbar -->
    <script src="../srv-v2/vendors/bootstrap-progressbar/bootstrap-progressbar.min.js"></script>
    <!-- iCheck -->
    <script src="../srv-v2/vendors/iCheck/icheck.min.js"></script>
    <!-- Custom Theme Scripts -->
    <script src="../srv-v2/build/js/custom.min.js"></script>
    <!-- Form behavior script (for showing/hiding sections based on request type) -->
    <script src="financial_form_view.js"></script>
<script src="sweetalert2@11.js"></script>
    <script>
      (function(){
        function numberWithCommas(x){
          if(x===null||x===undefined||x==='') return '';
          var num = parseFloat(String(x).replace(/[^\d.\-]/g,''));
          if (isNaN(num)) return '';
          var s = num.toFixed(2);
          var parts = s.split('.');
          parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
          return parts.join('.');
        }
        function applyCurrency(){
          var curSel = document.getElementById('currency');
          var cur = curSel ? curSel.value : '';
          var symbol = cur ? (cur + ' ') : '';
          var fields = document.querySelectorAll('.currency-view');
          for (var i=0;i<fields.length;i++){
            var el = fields[i];
            var raw = el.value || el.textContent;
            var formatted = numberWithCommas(raw);
            if (el.tagName==='INPUT') el.value = (symbol + formatted);
            else el.textContent = (symbol + formatted);
          }
        }
        document.addEventListener('DOMContentLoaded', applyCurrency);
      })();
    </script>
    <!-- Force read-only and basic dev-tools blocking -->
    <script>
      (function() {
        // Force all inputs to be non-editable
        function lockForm() {
          var inputs = document.querySelectorAll('#view-form input, #view-form select, #view-form textarea, #view-form button');
          for (var i = 0; i < inputs.length; i++) {
            var el = inputs[i];
            // Skip workflow action buttons so they remain clickable
            if (el.tagName === 'BUTTON' && el.classList && el.classList.contains('wf-act')) {
              continue;
            }
            if (el.tagName === 'TEXTAREA' || el.type === 'text' || el.type === 'number' || el.type === 'date') {
              el.setAttribute('readonly', 'readonly');
            }
            el.setAttribute('disabled', 'disabled');
          }
          // Re-enable workflow action buttons explicitly (safety)
          var wfBtns = document.querySelectorAll('button.wf-act');
          for (var j = 0; j < wfBtns.length; j++) {
            wfBtns[j].removeAttribute('disabled');
          }
          // But enable the Back and Edit anchor buttons when request is returned to requestor (active seq 1)
          var backBtn = document.querySelector('a.btn.btn-default');
          if (backBtn) backBtn.removeAttribute('disabled');
          var editBtn = document.querySelector('a.btn-edit');
          if (editBtn) editBtn.removeAttribute('disabled');
        }

        // Prevent context menu and common devtools shortcuts
        function hardenUI() {
          document.addEventListener('contextmenu', function(e) { e.preventDefault(); return false; });
          document.addEventListener('keydown', function(e) {
            if (e.key === 'F12') { e.preventDefault(); return false; }
            if (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'i')) { e.preventDefault(); return false; }
            if (e.ctrlKey && e.shiftKey && (e.key === 'J' || e.key === 'j')) { e.preventDefault(); return false; }
            if (e.ctrlKey && (e.key === 'U' || e.key === 'u')) { e.preventDefault(); return false; }
          });
        }

        // Field visibility configuration based on request type
        var fieldVisibility = {
          hideWhenRfp: [
            'field_category_col',
            'field_attachment_col', 
            'field_description_col',
            'field_amount_col',
            'field_reference_number_2_col',
            'field_date_col',
            'field_amount2_col',
            'field_from_company_col',
            'field_to_company_col',
            'field_credit_to_payroll_col',
            'field_issue_check_col'
          ],

          hideWhenErl: [
            'field_reference_number_col',
            'field_po_number_col',
            'field_due_date_col', 
            'field_cash_advance_col',
            'field_form_of_payment_col',
          ],

          hideWhenErgr: [
            'field_reference_number_2_col',
            'field_po_number_col',
            'field_due_date_col',
            'field_cash_advance_col', 
            'field_form_of_payment_col',
            'field_reference_number_col',
            'field_date_col',
            'field_amount2_col',
            'field_from_company_col',
            'field_to_company_col',
            'field_credit_to_payroll_col',
            'field_issue_check_col'
          ]
        };

        // Apply field visibility based on request type
        function applyFieldVisibility() {
          var requestType = '<?php echo h($requestType); ?>';
          var allFields = new Set();
          
          // Collect all field IDs from the configuration
          Object.keys(fieldVisibility).forEach(function(key) {
            fieldVisibility[key].forEach(function(id) {
              allFields.add(id);
            });
          });
          
          // Apply visibility rules
          allFields.forEach(function(fieldId) {
            var shouldHide = false;
            
            if (requestType === 'RFP') {
              shouldHide = fieldVisibility.hideWhenRfp.indexOf(fieldId) !== -1;
            } else if (requestType === 'ERL') {
              shouldHide = fieldVisibility.hideWhenErl.indexOf(fieldId) !== -1;
            } else if (requestType === 'ERGR') {
              shouldHide = fieldVisibility.hideWhenErgr.indexOf(fieldId) !== -1;
            }
            
            if (shouldHide) {
              var elements = document.querySelectorAll('#' + fieldId);
              elements.forEach(function(element) {
                element.style.display = 'none';
              });
            }
          });
        }

        document.addEventListener('DOMContentLoaded', function() {
          // Trigger existing logic to show/hide sections based on requestType
          var sel = document.getElementById('requestType');
          if (sel) {
            try { sel.dispatchEvent(new Event('change')); } catch (e) {}
          }
          
          // Apply field visibility based on request type
          applyFieldVisibility();
          
          lockForm();
          hardenUI();

          // WF buttons
          document.body.addEventListener('click', function(e){
            var t = e.target;
            if (t && t.classList && t.classList.contains('wf-act')) {
              e.preventDefault();
              var act = t.getAttribute('data-act');
              var seq = t.getAttribute('data-seq');
              var actor = t.getAttribute('data-actor');
              var role = t.getAttribute('data-role');
              
              // Customize confirmation message based on action
              var title = 'Confirm action';
              var confirmText = 'Proceed';
              
              switch(act) {
                case 'SUBMIT':
                  title = 'Submit Request';
                  confirmText = 'Submit';
                  break;
                case 'CANCEL':
                  title = 'Cancel Request';
                  confirmText = 'Cancel Request';
                  break;
                case 'APPROVE':
                  title = 'Approve Request';
                  confirmText = 'Approve';
                  break;
                case 'DECLINE':
                  title = 'Decline Request';
                  confirmText = 'Decline';
                  break;
                case 'RETURN_REQUESTOR':
                  title = 'Return to Requestor';
                  confirmText = 'Return';
                  break;
                case 'RETURN_APPROVER':
                  title = 'Return to Previous Approver';
                  confirmText = 'Return';
                  break;
              }
              
              Swal.fire({
                title: title,
                input: 'textarea',
                inputLabel: 'Remarks ' + (act === 'CANCEL' || act === 'DECLINE' || act.includes('RETURN') ? '(required)' : '(optional)'),
                inputPlaceholder: 'Enter remarks...',
                showCancelButton: true,
                confirmButtonText: confirmText,
                inputValidator: function(value) {
                  // Require remarks for certain actions
                  if ((act === 'CANCEL' || act === 'DECLINE' || act.includes('RETURN')) && !value.trim()) {
                    return 'Remarks are required for this action';
                  }
                }
              }).then(function(result){
                if (!result.isConfirmed) return;
                
                var fd = new FormData();
                fd.append('doc_type', '<?php echo h($requestType); ?>');
                fd.append('doc_number', '<?php echo h($docNumber); ?>');
                fd.append('sequence', seq);
                fd.append('actor_id', actor);
                fd.append('action', role);
                fd.append('decision', act);
                fd.append('remarks', result.value || '');
                fd.append('__expect_json', '1');
                
                // Show loading
                Swal.fire({
                  title: 'Processing...',
                  allowOutsideClick: false,
                  didOpen: function() {
                    Swal.showLoading();
                  }
                });
                
                fetch('workflow_action.php', { 
                  method: 'POST', 
                  body: fd, 
                  credentials: 'same-origin' 
                })
                .then(function(r){ 
                  return r.json().catch(function(){ 
                    return { success: false, message: 'Invalid server response' }; 
                  }); 
                })
                .then(function(res){
                  if (res && res.success) {
                    Swal.fire({ 
                      icon: 'success', 
                      title: 'Success', 
                      text: res.message || 'Workflow updated successfully.' 
                    }).then(function(){ 
                      location.reload(); 
                    });
                  } else {
                    Swal.fire({ 
                      icon: 'error', 
                      title: 'Error', 
                      text: (res && res.message) || 'Failed to update workflow.' 
                    });
                  }
                })
                .catch(function(err){ 
                  console.error('Workflow action error:', err);
                  Swal.fire({ 
                    icon: 'error', 
                    title: 'Network Error', 
                    text: 'Unable to connect to server. Please try again.' 
                  }); 
                });
              });
            }
          });
        });
      })();
    </script>
  </body>
</html>
 

