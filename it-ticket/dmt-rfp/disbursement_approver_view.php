<?php 
date_default_timezone_set('Asia/Manila');
include "blocks/inc.resource.php";
require_once 'upload_config.php';
require_once '../class/DisbursementClass.php';
require_once __DIR__ . '/attachment_display_helper.php';
require_once __DIR__ . '/approvals_workflow_helper.php';
require_once __DIR__ . '/transaction_history_helper.php';
$moduleid = 11;
include "proxy.php";
// // Check permission for Disbursement Approval (module ID 11)
// $hasPermission = false;
// if (isset($_SESSION['userid']) && isset($mysqlconn) && $mysqlconn) {
//     $userId = (int)$_SESSION['userid'];
//     $moduleId = 11; // Disbursement Approval module ID
    
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

// Static allowlist: users who can view any record (read-only), regardless of workflow rows
function __is_view_only_allowlisted($conn, $sess) {
  // Configure by username and/or email (case-insensitive for email)
  $allowUsernames = [
    'rmmagdamit','LESPILAC','acbarit'
  ];
  $allowEmails = [
    'rmmagdamit@carmensbest.com.ph',
    'lespilac@carmensbest.com.ph',
    'acbarit@carmensbest.com.ph'
  ];

  $u = (string)($sess['username'] ?? '');
  if ($u !== '' && in_array($u, $allowUsernames, true)) return true;

  // Try to resolve email of current session user
  try {
    if ($conn && $u !== '') {
      if ($st = mysqli_prepare($conn, "SELECT user_email FROM sys_usertb WHERE username = ? LIMIT 1")) {
        mysqli_stmt_bind_param($st, 's', $u);
        mysqli_stmt_execute($st);
        $rs = mysqli_stmt_get_result($st);
        if ($row = mysqli_fetch_assoc($rs)) {
          $email = strtolower(trim((string)($row['user_email'] ?? '')));
          if ($email !== '' && in_array($email, array_map('strtolower', $allowEmails), true)) {
            mysqli_stmt_close($st);
            return true;
          }
        }
        mysqli_stmt_close($st);
      }
    }
  } catch (Throwable $e) { /* ignore */ }
  return false;
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
$__CLEAN_URL = 'disbursement_approver_view.php';

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
	$fallback = 'disbursement_approver_main.php';
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

// Access policy: approver may view if they appear as any actor in the workflow.
// Additionally, cashier Specialist/Supervisor/Manager can view any record (view-only).
// Action buttons will only show if it is their turn (earliest Waiting sequence).
$__SESSION_USER = [
  'id' => (string)($_SESSION['userid'] ?? ''),
  'username' => (string)($_SESSION['username'] ?? ''),
  'first' => (string)($_SESSION['userfirstname'] ?? ''),
  'last' => (string)($_SESSION['userlastname'] ?? ''),
];
$__SESSION_USER['full'] = trim($__SESSION_USER['first'].' '.$__SESSION_USER['last']);

function __matches_actor($actor, $sess) {
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

$canView = false;
$earliestWaitingSeq = null;
$userHasActed = false;
if ($fr && isset($mysqlconn) && $mysqlconn) {
  if ($stmtChk = mysqli_prepare($mysqlconn, "SELECT sequence, actor_id, status FROM work_flow_process WHERE doc_type = ? AND doc_number = ? ORDER BY sequence ASC")) {
    $docTypeTmp = (string)($fr['request_type'] ?? '');
    $docNumTmp = (string)($fr['doc_number'] ?? '');
    mysqli_stmt_bind_param($stmtChk, 'ss', $docTypeTmp, $docNumTmp);
    mysqli_stmt_execute($stmtChk);
    $resChk = mysqli_stmt_get_result($stmtChk);
    while ($rowA = mysqli_fetch_assoc($resChk)) {
      $actor = (string)($rowA['actor_id'] ?? '');
      $st = strtoupper(trim((string)($rowA['status'] ?? '')));
      
      // Check if current user matches this actor
      if (__matches_actor($actor, $__SESSION_USER)) {
        $canView = true;
        
        // Check if user has already acted (not just waiting)
        if ($st !== 'WAITING FOR APPROVAL' && $st !== 'SUBMITTED' && $st !== 'SUBMIT') {
          $userHasActed = true;
        }
      }
      
      // Track earliest waiting sequence for action buttons
      if (strpos($st, 'WAITING') !== false) {
        $seqVal = (int)($rowA['sequence'] ?? 0);
        if ($earliestWaitingSeq === null || $seqVal < $earliestWaitingSeq) {
          $earliestWaitingSeq = $seqVal;
        }
      }
    }
    mysqli_stmt_close($stmtChk);
  }
}

// Define payee name early to avoid undefined variable
$payee = '';
if (!empty($fr['user_firstname']) || !empty($fr['user_lastname'])) {
  $payee = trim(((string)($fr['user_firstname'] ?? '')).' '.((string)($fr['user_lastname'] ?? '')));
} elseif (!empty($fr['payee'])) {
  // Fallback to raw payee value from DB when no user match
  $payee = (string)$fr['payee'];
}

// Also check if user is the creator of the record (they should always be able to view)
if (!$canView && $fr) {
  $creatorName = trim((($fr['creator_firstname'] ?? '').' '.($fr['creator_lastname'] ?? '')));
  if (__matches_actor($creatorName, $__SESSION_USER) || (string)$fr['created_by_user_id'] === (string)$__SESSION_USER['id']) {
    $canView = true;
  }
}

// Check if user is in the workflow template (even if not yet in process)
if (!$canView && $fr && isset($mysqlconn) && $mysqlconn) {
  $templateStmt = mysqli_prepare($mysqlconn, "SELECT COUNT(*) as count FROM work_flow_template 
                                             WHERE work_flow_id = ? AND company = ? 
                                             AND (actor_id = ? OR actor_id = ? OR actor_id = ? OR actor_id = ?)");
  if ($templateStmt) {
    $requestTypeTmp = (string)($fr['request_type'] ?? '');
    $companyTmp = (string)($fr['company'] ?? '');
    $username = (string)$__SESSION_USER['username'];
    $first = (string)$__SESSION_USER['first'];
    $last = (string)$__SESSION_USER['last'];
    $full = (string)$__SESSION_USER['full'];
    
    mysqli_stmt_bind_param($templateStmt, 'ssssss', $requestTypeTmp, $companyTmp, $username, $first, $last, $full);
    mysqli_stmt_execute($templateStmt);
    $templateRes = mysqli_stmt_get_result($templateStmt);
    $templateRow = mysqli_fetch_assoc($templateRes);
    
    if ($templateRow && (int)$templateRow['count'] > 0) {
      $canView = true;
    }
    mysqli_stmt_close($templateStmt);
  }
}

// Allow viewing if in static allowlist (e.g., Cashier Specialist/Supervisor/Manager) even if not in template
if (!$canView && __is_view_only_allowlisted($mysqlconn, $__SESSION_USER)) { $canView = true; }

// Check if user can edit
$canEdit = false;
if ($fr && isset($mysqlconn) && $mysqlconn) {
  // Check if user is the creator of the record (they should always be able to edit)
  $creatorName = trim((($fr['creator_firstname'] ?? '').' '.($fr['creator_lastname'] ?? '')));
  if (strcasecmp($creatorName, $__SESSION_USER['full']) === 0 || 
      strcasecmp($creatorName, $__SESSION_USER['username']) === 0 ||
      (string)$fr['created_by_user_id'] === (string)$__SESSION_USER['id']) {
    $canEdit = true;
  }
  
  // If not creator, check if user is in workflow template (any sequence)
  if (!$canEdit) {
    $editCheckStmt = mysqli_prepare($mysqlconn, "SELECT COUNT(*) as count FROM work_flow_template wft
                                                 WHERE wft.work_flow_id = ? 
                                                   AND wft.company = ? 
                                                   AND (
                                                     wft.actor_id = ?
                                                     OR wft.actor_id = ?
                                                     OR wft.actor_id = ?
                                                     OR wft.actor_id = ?
                                                     OR wft.actor_id = ?
                                                     OR REPLACE(UPPER(wft.actor_id),' ','') = REPLACE(UPPER(?),' ','')
                                                   )");
    if ($editCheckStmt) {
      $requestTypeTmp = (string)($fr['request_type'] ?? '');
      $companyTmp = (string)($fr['company'] ?? '');
      $username = (string)$__SESSION_USER['username'];
      $first = (string)$__SESSION_USER['first'];
      $last = (string)$__SESSION_USER['last'];
      $full = (string)$__SESSION_USER['full'];
      $uid = (string)$__SESSION_USER['id'];
      
      mysqli_stmt_bind_param($editCheckStmt, 'ssssssss', $requestTypeTmp, $companyTmp, $username, $first, $last, $full, $uid, $full);
      mysqli_stmt_execute($editCheckStmt);
      $editCheckRes = mysqli_stmt_get_result($editCheckStmt);
      $editCheckRow = mysqli_fetch_assoc($editCheckRes);
      
      if ($editCheckRow && (int)$editCheckRow['count'] > 0) {
        $canEdit = true;
      }
      mysqli_stmt_close($editCheckStmt);
    }
  }
  
  // Allow admin users to edit
  if (!$canEdit && isset($_SESSION['userrole']) && strtolower($_SESSION['userrole']) === 'admin') {
    $canEdit = true;
  }
}

// Debug information (remove this in production)
if (!$canView) {
  // For debugging, let's see what we have
  echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px; border: 1px solid #ccc;">';
  echo '<h4>Debug Information:</h4>';
  echo '<p><strong>Session User:</strong></p>';
  echo '<ul>';
  echo '<li>ID: ' . h($__SESSION_USER['id']) . '</li>';
  echo '<li>Username: ' . h($__SESSION_USER['username']) . '</li>';
  echo '<li>First: ' . h($__SESSION_USER['first']) . '</li>';
  echo '<li>Last: ' . h($__SESSION_USER['last']) . '</li>';
  echo '<li>Full: ' . h($__SESSION_USER['full']) . '</li>';
  echo '</ul>';
  
  if ($fr) {
    echo '<p><strong>Document Info:</strong></p>';
    echo '<ul>';
    echo '<li>Request Type: ' . h($fr['request_type'] ?? 'N/A') . '</li>';
    echo '<li>Doc Number: ' . h($fr['doc_number'] ?? 'N/A') . '</li>';
    echo '<li>Payee: ' . h($payee) . '</li>';
    echo '</ul>';
    
         // Show workflow actors
     echo '<p><strong>Workflow Actors:</strong></p>';
     if (isset($mysqlconn) && $mysqlconn) {
       $debugStmt = mysqli_prepare($mysqlconn, "SELECT sequence, actor_id, status FROM work_flow_process WHERE doc_type = ? AND doc_number = ? ORDER BY sequence ASC");
       if ($debugStmt) {
         $docTypeTmp = (string)($fr['request_type'] ?? '');
         $docNumTmp = (string)($fr['doc_number'] ?? '');
         mysqli_stmt_bind_param($debugStmt, 'ss', $docTypeTmp, $docNumTmp);
         mysqli_stmt_execute($debugStmt);
         $debugRes = mysqli_stmt_get_result($debugStmt);
         
         if (mysqli_num_rows($debugRes) > 0) {
           echo '<ul>';
           while ($debugRow = mysqli_fetch_assoc($debugRes)) {
             $actor = $debugRow['actor_id'];
             $status = $debugRow['status'];
             $seq = $debugRow['sequence'];
             $matches = __matches_actor($actor, $__SESSION_USER) ? 'YES' : 'NO';
             echo '<li>Sequence ' . $seq . ': ' . h($actor) . ' (' . h($status) . ') - Matches: ' . $matches . '</li>';
           }
           echo '</ul>';
         } else {
           echo '<p>No workflow records found</p>';
         }
         mysqli_stmt_close($debugStmt);
       }
     }
     
     // Show workflow template actors
     echo '<p><strong>Workflow Template Actors:</strong></p>';
     if (isset($mysqlconn) && $mysqlconn) {
       $templateDebugStmt = mysqli_prepare($mysqlconn, "SELECT sequence, actor_id, department FROM work_flow_template WHERE work_flow_id = ? AND company = ? ORDER BY sequence ASC");
       if ($templateDebugStmt) {
         $docTypeTmp = (string)($fr['request_type'] ?? '');
         $companyTmp = (string)($fr['company'] ?? '');
         mysqli_stmt_bind_param($templateDebugStmt, 'ss', $docTypeTmp, $companyTmp);
         mysqli_stmt_execute($templateDebugStmt);
         $templateDebugRes = mysqli_stmt_get_result($templateDebugStmt);
         
         if (mysqli_num_rows($templateDebugRes) > 0) {
           echo '<ul>';
           while ($templateDebugRow = mysqli_fetch_assoc($templateDebugRes)) {
             $actor = $templateDebugRow['actor_id'];
             $seq = $templateDebugRow['sequence'];
             $dept = $templateDebugRow['department'];
             $matches = __matches_actor($actor, $__SESSION_USER) ? 'YES' : 'NO';
             echo '<li>Sequence ' . $seq . ': ' . h($actor) . ' (Dept: ' . h($dept) . ') - Matches: ' . $matches . '</li>';
           }
           echo '</ul>';
         } else {
           echo '<p>No workflow template records found</p>';
         }
         mysqli_stmt_close($templateDebugStmt);
       }
     }
  }
  echo '</div>';
  
  // For now, let's allow access if user is admin or if no workflow records exist
  $isAdmin = false;
  if (isset($_SESSION['userrole']) && strtolower($_SESSION['userrole']) === 'admin') {
    $isAdmin = true;
  }
  
  $hasWorkflowRecords = false;
  if (isset($mysqlconn) && $mysqlconn && $fr) {
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
    }
  }
  
  // Allow access if admin or if no workflow records exist yet
  if ($isAdmin || !$hasWorkflowRecords) {
    echo '<p><strong>Access granted:</strong> ' . ($isAdmin ? 'Admin user' : 'No workflow records yet') . '</p>';
    $canView = true;
  } else {
    http_response_code(403);
    echo 'Forbidden: you are not allowed to view this record.';
    exit;
  }
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

// Only show sequences that exist in the process table
$filteredApproverSteps = [];
foreach ($approverSteps as $seq => $actors) {
  if (in_array($seq, $existingSequences)) {
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
       $stmtItems = mysqli_prepare($mysqlconn, "SELECT i.id, i.category, i.description, i.reference_number, i.po_number, i.due_date, i.cash_advance, i.form_of_payment,  i.gross_amount, i.vatable, i.vat_amount, i.withholding_tax, i.amount_withhold, i.net_payable_amount, i.carf_no, i.pcv_no, i.invoice_date, i.invoice_number, i.supplier_name, i.tin, i.address, c.category_name, c.sap_account_description 
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
                      <li>
                        <?php if ($canEdit) { ?>
                          <a class="btn btn-sm btn-warning" href="disbursement_approver_edit_form.php?id=<?php echo (int)$id; ?>" title="Edit Request">
                            <i class="fa fa-edit"></i> Edit
                          </a>
                        <?php } ?>
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
                                  <div class="col-md-2 col-sm-2 col-xs-12" id="field_ap_attachment_col">
                                    <label>AP Attachment</label>
                                    <?php 
                                      // Get item ID for this item to fetch AP attachments
                                      $itemId = $it['id'] ?? null;
                                      if ($itemId) {
                                        displayAttachments($docNumber, $itemId, 'item_ap_attachment', false);
                                      } else {
                                        echo '<div class="text-muted">No AP attachments</div>';
                                      }
                                    ?>
                                  </div>
                                  <div class="col-md-2 col-sm-2 col-xs-12" id="field_cv_attachment_col">
                                    <label>CV Attachment</label>
                                    <?php 
                                      // Get item ID for this item to fetch CV attachments
                                      $itemId = $it['id'] ?? null;
                                      if ($itemId) {
                                        displayAttachments($docNumber, $itemId, 'item_cv_attachment', false);
                                      } else {
                                        echo '<div class="text-muted">No CV attachments</div>';
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

                                 <div class="form-group">
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
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <div class="row">
                                <?php echo renderApprovalsWorkflow($approverSteps, $processStatus, $activeSequence, $earliestWaitingSeq, $__SESSION_USER, $sequence2Approved, $mysqlconn); ?>
                            </div>
                        </div>
                    </div>

                      <div class="ln_solid"></div>
                      <div class="form-group">
                        <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                          <a class="btn btn-default" href="disbursement_approver_main.php">Back</a>
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

    
    <!-- Custom CSS for scrollable attachments -->
    <style>
    .modern-attachment-container::-webkit-scrollbar {
        width: 8px;
    }
    
    .modern-attachment-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    
    .modern-attachment-container::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }
    
    .modern-attachment-container::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    
    /* Firefox scrollbar styling */
    .modern-attachment-container {
        scrollbar-width: thin;
        scrollbar-color: #888 #f1f1f1;
    }
    </style>
    <?php include __DIR__ . '/approver_workflow_script.php'; ?>
  </body>
</html>
 

