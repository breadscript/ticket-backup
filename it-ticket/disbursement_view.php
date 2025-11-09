<?php 
date_default_timezone_set('Asia/Manila');
include "blocks/inc.resource.php";
require_once 'upload_config.php';
require_once '../class/DisbursementClass.php';

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

// Check if sequence 1 (requestor) has "Submitted" status
$sequence1Status = '';
$sequence1Key = '1|' . trim((string)$payee) . '|Requestor';
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
  } elseif ($seq === 2 && $sequence1Status === 'SUBMITTED' && !in_array(2, $existingSequences)) {
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
  if ($s === 'SUBMITTED') return '<span class="label label-primary">'.h($status).'</span>';
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
    } elseif ($status === 'SUBMITTED') {
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
// Fetch child rows: items and breakdowns
$items = [];
$breakdowns = [];
if (isset($mysqlconn) && $mysqlconn) {
  $stmtItems = mysqli_prepare($mysqlconn, "SELECT i.category, i.attachment_path, i.description, i.amount, i.reference_number, i.po_number, i.due_date, i.cash_advance, i.form_of_payment, i.budget_consumption, c.category_name 
                                          FROM financial_request_items i 
                                          LEFT JOIN categories c ON i.category = c.category_code 
                                          WHERE i.doc_number = ? 
                                          ORDER BY i.id");
  if ($stmtItems) {
    mysqli_stmt_bind_param($stmtItems, 's', $docNumber);
    mysqli_stmt_execute($stmtItems);
    $resItems = mysqli_stmt_get_result($stmtItems);
    while ($row = mysqli_fetch_assoc($resItems)) { $items[] = $row; }
    mysqli_stmt_close($stmtItems);
  }
  $stmtBd = mysqli_prepare($mysqlconn, "SELECT reference_number_2, `date`, amount2 FROM financial_request_breakdowns WHERE doc_number = ? ORDER BY id");
  if ($stmtBd) {
    mysqli_stmt_bind_param($stmtBd, 's', $docNumber);
    mysqli_stmt_execute($stmtBd);
    $resBd = mysqli_stmt_get_result($stmtBd);
    while ($row = mysqli_fetch_assoc($resBd)) { $breakdowns[] = $row; }
    mysqli_stmt_close($stmtBd);
  }
}

// Derive display amount from first available item or breakdown if present
if (!$amountFigures) {
  if (!empty($items) && isset($items[0]['amount'])) {
    $amountFigures = $items[0]['amount'];
  } elseif (!empty($breakdowns) && isset($breakdowns[0]['amount2'])) {
    $amountFigures = $breakdowns[0]['amount2'];
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
      // Create a unique key to identify duplicates
      $key = $row['actor_id'] . '|' . $row['sequence'] . '|' . $row['new_status'] . '|' . $row['created_at'];
      
      // Only add if we haven't seen this exact combination before
      if (!isset($seen[$key])) {
        $seen[$key] = true;
        
        // For Request Submission (sequence 1), use payee name instead of actor_display_name
        if ((int)$row['sequence'] === 1) {
          // Use payee name if available, otherwise fall back to actor_display_name or actor_id
          $row['actor_display_name'] = !empty($payee) ? $payee : ($row['actor_display_name'] ?: $row['actor_id']);
          
          // Debug: Log what we're setting for sequence 1
          error_log("Sequence 1 - Original actor_id: " . $row['actor_id'] . ", actor_display_name: " . $row['actor_display_name'] . ", payee: " . $payee);
        }
        
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
                      <li>
                        <a class="btn btn-sm btn-success" href="print_financial_request.php?id=<?php echo (int)$id; ?>" target="_blank" title="Print / Export">
                          <i class="fa fa-print"></i> Print
                        </a>
                      </li>
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
                            <div class="col-md-3 col-sm-3 col-xs-12">
                              <label>Doc Type</label>
                              <input type="text" id="doc_type" name="doc_type" class="form-control" value="<?php echo h($docType); ?>" readonly>
                            </div>
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
                                  <div class="col-md-4 col-sm-4 col-xs-12" id="field_category_col">
                                    <label>Category</label>
                                    <input type="text" class="form-control" value="<?php echo h($it['category_name'] ?: $it['category']); ?>" readonly>
                                  </div>
                                  <div class="col-md-4 col-sm-4 col-xs-12" id="field_attachment_col">
                                    <label>Attachment</label>
                                    <?php 
                                      $attachments = [];
                                      if (!empty($it['attachment_path'])) {
                                        // Handle both single file path and JSON array of multiple files
                                        if (strpos($it['attachment_path'], '[') === 0) {
                                          // JSON array of multiple files
                                          $attachments = json_decode($it['attachment_path'], true) ?: [];
                                        } else {
                                          // Single file path
                                          $attachments = [$it['attachment_path']];
                                        }
                                      }
                                      

                                    ?>
                                    <?php if (empty($attachments)) { ?>
                                      <div class="text-muted">No attachments</div>
                                    <?php } else { ?>
                                      <div class="attachment-list">
                                        <?php foreach ($attachments as $idx => $attachment) { ?>
                                          <div class="attachment-item" style="margin-bottom: 8px;">
                                            <?php 
                                              $fileName = basename($attachment);
                                              $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                              $iconClass = 'fa-file';
                                              if (in_array($fileExt, ['pdf'])) $iconClass = 'fa-file-pdf-o';
                                              elseif (in_array($fileExt, ['doc', 'docx'])) $iconClass = 'fa-file-word-o';
                                              elseif (in_array($fileExt, ['xls', 'xlsx'])) $iconClass = 'fa-file-excel-o';
                                              elseif (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif'])) $iconClass = 'fa-file-image-o';
                                            ?>
                                            <a href="<?php echo h(getAttachmentFilePath($attachment)); ?>" target="_blank" class="btn btn-xs btn-default">
                                              <i class="fa <?php echo $iconClass; ?>"></i>
                                              <?php echo h($fileName); ?>
                                            </a>
                                          </div>
                                        <?php } ?>
                                      </div>
                                    <?php } ?>
                                  </div>
                                  <div class="col-md-4 col-sm-4 col-xs-12" id="field_description_col">
                                    <label>Description</label>
                                    <textarea class="form-control" rows="2" readonly><?php echo h($it['description']); ?></textarea>
                                  </div>
                                </div>

                                <div class="form-group">
                                  <div class="col-md-4 col-sm-4 col-xs-12" id="field_amount_col">
                                    <label>Amount 1</label>
                                    <input type="text" class="form-control currency-view" value="<?php echo h($it['amount']); ?>" readonly>
                                  </div>
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
                                  <div class="col-md-4 col-sm-4 col-xs-12" id="field_due_date_col">
                                    <label>Due Date</label>
                                    <input type="date" class="form-control" value="<?php echo h($it['due_date']); ?>" readonly>
                                  </div>
                                  <div class="col-md-4 col-sm-4 col-xs-12" id="field_cash_advance_col">
                                    <label>Cash Advance?</label>
                                    <div>
                                      <span class="label label-default"><?php echo ((string)$it['cash_advance']==='1') ? 'Yes' : 'No'; ?></span>
                                    </div>
                                  </div>
                                  <div class="col-md-4 col-sm-4 col-xs-12" id="field_form_of_payment_col">
                                    <label>Form of Payment:</label>
                                    <input type="text" class="form-control" value="<?php echo h($it['form_of_payment']); ?>" readonly>
                                  </div>
                                </div>

                                <div class="form-group">
                                  <div class="col-md-6 col-sm-6 col-xs-12" id="field_budget_consumption_col">
                                    <label>Budget Consumption</label>
                                    <input type="text" class="form-control" value="<?php echo h($it['budget_consumption']); ?>" readonly>
                                  </div>
                                </div>
                              </div>
                            <?php } ?>
                          <?php } ?>
                        </div>
                      </div>

                      <!-- ADVANCED (BREAKDOWN) SECTION (list of rows) -->
                      <div class="x_panel">
                        <div class="x_title">
                          <h3><i class="fa fa-cogs"></i> Advanced (Breakdown)</h3>
                          <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                          <?php if (empty($breakdowns)) { ?>
                            <div class="alert alert-info">No breakdown entries recorded.</div>
                          <?php } else { ?>
                            <?php foreach ($breakdowns as $bd) { ?>
                              <div class="well" style="padding:10px">
                                <div class="form-group">
                                  <div class="col-md-4 col-sm-4 col-xs-12" id="field_reference_number2_col">
                                    <label>Reference Number 2</label>
                                    <input type="text" class="form-control" value="<?php echo h($bd['reference_number_2']); ?>" readonly>
                                  </div>
                                  <div class="col-md-4 col-sm-4 col-xs-12" id="field_date_col">
                                    <label>Date</label>
                                    <input type="date" class="form-control" value="<?php echo h($bd['date']); ?>" readonly>
                                  </div>
                                  <div class="col-md-4 col-sm-4 col-xs-12" id="field_amount2_col">
                                    <label>Amount 2</label>
                                    <input type="text" class="form-control currency-view" value="<?php echo h($bd['amount2']); ?>" readonly>
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
                              <?php 
                                $supportingDocs = [];
                                if (!empty($supportingPath)) {
                                  // Handle both single file path and JSON array of multiple files
                                  if (strpos($supportingPath, '[') === 0) {
                                    // JSON array of multiple files
                                    $supportingDocs = json_decode($supportingPath, true) ?: [];
                                  } else {
                                    // Single file path
                                    $supportingDocs = [$supportingPath];
                                  }
                                }
                                

                              ?>
                              <?php if (empty($supportingDocs)) { ?>
                                <div class="text-muted">No supporting documents</div>
                              <?php } else { ?>
                                <div class="supporting-docs-list">
                                  <?php foreach ($supportingDocs as $idx => $doc) { ?>
                                    <div class="doc-item" style="margin-bottom: 8px;">
                                      <?php 
                                        $fileName = basename($doc);
                                        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                        $iconClass = 'fa-file';
                                        if (in_array($fileExt, ['pdf'])) $iconClass = 'fa-file-pdf-o';
                                        elseif (in_array($fileExt, ['doc', 'docx'])) $iconClass = 'fa-file-word-o';
                                        elseif (in_array($fileExt, ['xls', 'xlsx'])) $iconClass = 'fa-file-excel-o';
                                        elseif (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif'])) $iconClass = 'fa-file-image-o';
                                      ?>
                                      <a href="<?php echo h(getFullFilePath($doc, 'supporting')); ?>" target="_blank" class="btn btn-xs btn-default">
                                        <i class="fa <?php echo $iconClass; ?>"></i>
                                        <?php echo h($fileName); ?>
                                      </a>
                                    </div>
                                  <?php } ?>
                                </div>
                              <?php } ?>
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
                              <div class="checkbox" style="margin-top: 25px;">
                                <label>
                                  <input type="checkbox" id="credit_to_payroll" name="credit_to_payroll" class="flat" <?php echo ($creditToPayroll==='1')?'checked':''; ?> disabled> Credit To Payroll
                                </label>
                              </div>
                            </div>
                          </div>

                          <div class="form-group">
                            <div class="col-md-4 col-sm-4 col-xs-12" id="field_issue_check_col">
                              <div class="checkbox" style="margin-top: 25px;">
                                <label>
                                  <input type="checkbox" id="issue_check" name="issue_check" class="flat" <?php echo ($issueCheck==='1')?'checked':''; ?> disabled> Issue Check
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
                                  <?php 
                                    $currentSequence = null;
                                    $sequenceNames = [
                                      1 => 'Request Submission',
                                      2 => 'Cost Center Approval',
                                      3 => 'Accounting Review',
                                      4 => 'Accounting Approval',
                                      5 => 'Controller Review',
                                      6 => 'Cashier Processing'
                                    ];

                                    // Compact Accounting Review (sequence 3) to only one line per key status
                                    // Key statuses: Waiting for Approval (incl. Submitted), Approved, Return to Requestor, Return to Approver, Declined
                                    $lastIdxPerCat = [];
                                    for ($i = 0; $i < count($transactionHistory); $i++) {
                                      $row = $transactionHistory[$i];
                                      $seqTmp = (int)($row['sequence'] ?? 0);
                                      if ($seqTmp === 3) {
                                        $stText = strtoupper(trim((string)($row['new_status'] ?? '')));
                                        $cat = null;
                                        if ($stText === 'DONE' || strpos($stText, 'APPROVED') !== false) {
                                          $cat = 'APPROVED';
                                        } elseif (strpos($stText, 'WAITING FOR APPROVAL') !== false || strpos($stText, 'SUBMITTED') !== false) {
                                          $cat = 'WAITING_APPROVAL';
                                        } elseif (strpos($stText, 'RETURN TO REQUESTOR') !== false) {
                                          $cat = 'RETURN_REQUESTOR';
                                        } elseif (strpos($stText, 'RETURN TO APPROVER') !== false) {
                                          $cat = 'RETURN_APPROVER';
                                        } elseif (strpos($stText, 'DECLINED') !== false || strpos($stText, 'REJECTED') !== false) {
                                          $cat = 'DECLINED';
                                        }
                                        if ($cat !== null) {
                                          $lastIdxPerCat[$cat] = $i; // keep latest index per category
                                        }
                                      }
                                    }

                                    $displayHistory = [];
                                    for ($i = 0; $i < count($transactionHistory); $i++) {
                                      $row = $transactionHistory[$i];
                                      $seqTmp = (int)($row['sequence'] ?? 0);
                                      if ($seqTmp === 3) {
                                        $stText = strtoupper(trim((string)($row['new_status'] ?? '')));
                                        $cat = null;
                                        if ($stText === 'DONE' || strpos($stText, 'APPROVED') !== false) {
                                          $cat = 'APPROVED';
                                        } elseif (strpos($stText, 'WAITING FOR APPROVAL') !== false || strpos($stText, 'SUBMITTED') !== false) {
                                          $cat = 'WAITING_APPROVAL';
                                        } elseif (strpos($stText, 'RETURN TO REQUESTOR') !== false) {
                                          $cat = 'RETURN_REQUESTOR';
                                        } elseif (strpos($stText, 'RETURN TO APPROVER') !== false) {
                                          $cat = 'RETURN_APPROVER';
                                        } elseif (strpos($stText, 'DECLINED') !== false || strpos($stText, 'REJECTED') !== false) {
                                          $cat = 'DECLINED';
                                        }
                                        if ($cat !== null) {
                                          if (isset($lastIdxPerCat[$cat]) && $lastIdxPerCat[$cat] === $i) {
                                            $displayHistory[] = $row; // include only the latest occurrence for this category
                                          }
                                        } else {
                                          $displayHistory[] = $row; // include other statuses (e.g., SKIPPED) as-is
                                        }
                                      } else {
                                        $displayHistory[] = $row; // non-seq3 rows unchanged
                                      }
                                    }

                                    foreach ($displayHistory as $txn) { 
                                      $sequence = (int)($txn['sequence'] ?? 0);
                                      $status = $txn['new_status'] ?? '';
                                      // For sequence 1, prioritize actor_display_name (which should be payee name)
                                      // For other sequences, use the normal fallback chain
                                      if ((int)$sequence === 1) {
                                        $actor = $txn['actor_display_name'] ?: $txn['actor_id'] ?: 'Unknown';
                                        // Debug: Log what we're displaying for sequence 1
                                        error_log("Displaying Sequence 1 - actor_display_name: " . $txn['actor_display_name'] . ", actor_id: " . $txn['actor_id'] . ", final actor: " . $actor);
                                      } else {
                                        $actor = $txn['actor_display_name'] ?? $txn['actor_id'] ?? 'Unknown';
                                      }
                                      $remarks = $txn['remarks'] ?? '';
                                      $createdAt = $txn['created_at'] ?? '';
                                      
                                      // Show step name only when sequence changes
                                      $showStepName = ($currentSequence !== $sequence);
                                      $currentSequence = $sequence;
                                      
                                      // Determine status class, but keep the real status text from the log
                                      $statusClass = 'label-default';
                                      $statusText = trim((string)$status);
                                      $statusUpperTx = strtoupper($statusText);
                                      if (strpos($statusUpperTx, 'APPROVED') !== false || $statusUpperTx === 'DONE') {
                                        $statusClass = 'label-success';
                                      } elseif (strpos($statusUpperTx, 'SUBMITTED') !== false) {
                                        $statusClass = 'label-primary';
                                      } elseif (strpos($statusUpperTx, 'DECLINED') !== false || strpos($statusUpperTx, 'REJECTED') !== false) {
                                        $statusClass = 'label-danger';
                                      } elseif (strpos($statusUpperTx, 'RETURN') !== false) {
                                        $statusClass = 'label-warning';
                                      } elseif (strpos($statusUpperTx, 'WAITING FOR APPROVAL') !== false) {
                                        $statusClass = 'label-info';
                                      } elseif (strpos($statusUpperTx, 'SKIPPED') !== false) {
                                        $statusClass = 'label-default';
                                      }
                                  ?>
                                    <tr>
                                      <td>
                                        <?php if ($showStepName) { ?>
                                          <strong><?php echo h($sequenceNames[$sequence] ?? "Step $sequence"); ?></strong>
                                        <?php } ?>
                                      </td>
                                      <td>
                                        <strong><?php echo h($actor); ?></strong>
                                      </td>
                                      <td>
                                        <span class="label <?php echo $statusClass; ?>"><?php echo h($statusText); ?></span>
                                      </td>
                                      <td>
                                        <?php 
                                          if ($createdAt) {
                                            $date = new DateTime($createdAt);
                                            echo $date->format('m/d/Y h:i A');
                                          } else {
                                            echo 'N/A';
                                          }
                                        ?>
                                      </td>
                                      <td>
                                        <?php 
                                          $remarksDisplay = trim((string)$remarks);
                                          if ($remarksDisplay === '') {
                                            // Provide a friendly default description when remarks are empty
                                            $statusUpperTx = strtoupper(trim((string)$status));
                                            $eventName = isset($txn['event']) ? strtoupper(trim((string)$txn['event'])) : '';
                                            if (strpos($statusUpperTx, 'WAITING FOR APPROVAL') !== false) {
                                              $remarksDisplay = 'Waiting for approval';
                                            } elseif (strpos($statusUpperTx, 'SUBMITTED') !== false) {
                                              $remarksDisplay = 'Submitted';
                                            } elseif (strpos($statusUpperTx, 'APPROVED') !== false || $statusUpperTx === 'DONE') {
                                              $remarksDisplay = 'Approved';
                                            } elseif (strpos($statusUpperTx, 'RETURN TO APPROVER') !== false) {
                                              $remarksDisplay = 'Returned to previous approver';
                                            } elseif (strpos($statusUpperTx, 'RETURN TO REQUESTOR') !== false) {
                                              $remarksDisplay = 'Returned to requestor';
                                            } elseif (strpos($statusUpperTx, 'DECLINED') !== false || strpos($statusUpperTx, 'REJECTED') !== false) {
                                              $remarksDisplay = 'Declined';
                                            } elseif (strpos($statusUpperTx, 'CANCELLED') !== false) {
                                              $remarksDisplay = 'Cancelled';
                                            } elseif (strpos($statusUpperTx, 'SKIPPED') !== false) {
                                              $remarksDisplay = 'Skipped due to parallel approval';
                                            } elseif (strpos($eventName, 'SEQUENCE_CREATION') !== false || strpos($eventName, 'SEQUENCE') !== false) {
                                              $remarksDisplay = 'Sequence created automatically';
                                            } elseif ($eventName === 'INSERT') {
                                              $remarksDisplay = 'Process created';
                                            } elseif ($eventName === 'UPDATE') {
                                              $remarksDisplay = 'Process updated';
                                            } else {
                                              $remarksDisplay = 'No remarks';
                                            }
                                          }
                                          echo h($remarksDisplay);
                                        ?>
                                      </td>
                                    </tr>
                                  <?php } ?>
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
                                <?php if ($activeSequence === 1 && $sequence1Status !== 'SUBMITTED') { ?>
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
                                                if ($st0 && $st0 !== 'WAITING FOR APPROVAL' && $st0 !== 'SUBMITTED') {
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
                                        echo '<div style="margin-bottom:12px;">';
                                        echo '<div style="height:16px; font-size:12px; color:#777; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">'.h($a['actor_id']).'</div>';
                                        echo '<div style="border-bottom:1px solid #ddd; width:70%; margin:6px auto 6px;"></div>';
                                        echo '<div style="font-size:11px; color:#888;">'.h(str_replace('_',' ',(string)$a['action'])).'</div>';
                                        echo '<div style="margin-top:6px;">'. wf_status_badge($status) .'</div>';
                                        
                                        // Action buttons logic
                                        $isActiveSeq = ($seq === $activeSequence);
                                        $statusUpper = strtoupper(trim((string)$status));
                                        $isPending = empty($status) || $statusUpper === 'WAITING FOR APPROVAL' || $statusUpper === 'SUBMITTED';
                                        
                                        if ($seq === 1) {
                                            // Sequence 1 (Requestor) logic
                                            if ($statusUpper === 'SUBMITTED' && !$sequence2Approved) {
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            'field_budget_consumption_col'
          ],

          hideWhenErgr: [
            'field_reference_number_2_col',
            'field_po_number_col',
            'field_due_date_col',
            'field_cash_advance_col', 
            'field_form_of_payment_col',
            'field_budget_consumption_col',
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
 

