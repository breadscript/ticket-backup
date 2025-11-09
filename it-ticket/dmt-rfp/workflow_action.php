<?php
// Simple endpoint to update workflow process and log the decision
// Requires workflow_schema.sql tables

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../conn/db.php';
require_once __DIR__ . '/task_notification.php';
require_once __DIR__ . '/workflow_helpers.php';

$conn = connectionDB();
if (!$conn) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'DB connection failed']);
  exit;
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

function j($k,$d='') { return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $d; }

$doc_type   = j('doc_type');
$doc_number = j('doc_number');
$sequence   = (int)j('sequence','0');
$actor_id   = j('actor_id');
$action     = j('action');
$decision   = strtoupper(j('decision'));
$remarks    = j('remarks');

if ($doc_type === '' || $doc_number === '' || $sequence <= 0 || $actor_id === '' || $action === '' || $decision === '') {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
  exit;
}

// Map decision -> status text
switch ($decision) {
  case 'APPROVE':
    $newStatus = 'Approved';
    break;
  case 'SUBMIT':
    $newStatus = 'Submitted';
    break;
  case 'RETURN_REQUESTOR':
    $newStatus = 'Return to Requestor';
    break;
  case 'RETURN_APPROVER':
    $newStatus = 'Return to Approver';
    break;
  case 'DECLINE':
    $newStatus = 'Declined';
    break;
  case 'CANCEL':
    $newStatus = 'Cancelled';
    break;
  default:
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid decision']);
    exit;
}

// Start transaction for data consistency
mysqli_autocommit($conn, false);
$transaction_success = false;

try {
  // Handle CANCEL action specially
  if ($decision === 'CANCEL') {
    // Update all workflow processes for this document to cancelled
    $cancelAll = mysqli_prepare($conn, "UPDATE work_flow_process SET status = 'Cancelled', decided_by = ?, decided_at = NOW(), remarks = ? WHERE doc_type = ? AND doc_number = ?");
    if (!$cancelAll) {
      throw new Exception('Failed to prepare cancel statement');
    }
    
    $decider = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';
    mysqli_stmt_bind_param($cancelAll, 'ssss', $decider, $remarks, $doc_type, $doc_number);
    
    if (!mysqli_stmt_execute($cancelAll)) {
      throw new Exception('Failed to cancel workflow');
    }
    mysqli_stmt_close($cancelAll);
    
    // Update the financial request status to cancelled
    $updateHeader = mysqli_prepare($conn, "UPDATE financial_requests SET status='cancelled', updated_at=NOW() WHERE doc_number=? AND request_type=? LIMIT 1");
    if ($updateHeader) {
      mysqli_stmt_bind_param($updateHeader, 'ss', $doc_number, $doc_type);
      mysqli_stmt_execute($updateHeader);
      mysqli_stmt_close($updateHeader);
    }
    
    // Log the cancellation
    $decider = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';
    logWorkflowAction($conn, $doc_type, $doc_number, $sequence, $actor_id, $action, 'cancelled', 'Submitted', 'Cancelled', $remarks, $decider);
    
    // Commit and exit
    mysqli_commit($conn);
    mysqli_autocommit($conn, true);
    echo json_encode(['success' => true, 'message' => 'Request cancelled successfully']);
    exit;
  }

  // Update current step for non-cancel actions
  // Allow actor match by stored username string OR sys_usertb numeric id OR first/last/full name
  $upd = mysqli_prepare($conn, "UPDATE work_flow_process 
                               SET status = ?, decided_by = ?, decided_at = NOW(), remarks = ? 
                               WHERE doc_type = ? AND doc_number = ? AND sequence = ? 
                                 AND (
                                   actor_id = ? 
                                   OR actor_id = ? 
                                   OR actor_id = ? 
                                   OR actor_id = ? 
                                   OR actor_id = ? 
                                   OR REPLACE(UPPER(actor_id),' ','') = REPLACE(UPPER(?),' ','')
                                 )
                                 AND action = ?");
  if (!$upd) {
    throw new Exception('Failed to prepare update statement');
  }

  $decider = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';
  $sessIdStr = isset($_SESSION['userid']) ? (string)$_SESSION['userid'] : '';
  $sessUser = isset($_SESSION['username']) ? (string)$_SESSION['username'] : '';
  $sessFirst = isset($_SESSION['userfirstname']) ? (string)$_SESSION['userfirstname'] : '';
  $sessLast = isset($_SESSION['userlastname']) ? (string)$_SESSION['userlastname'] : '';
  $sessFull = trim($sessFirst . ' ' . $sessLast);
  mysqli_stmt_bind_param($upd, 'sssssisssssss', $newStatus, $decider, $remarks, $doc_type, $doc_number, $sequence, $actor_id, $sessUser, $sessIdStr, $sessFirst, $sessLast, $sessFull, $action);
  
  if (!mysqli_stmt_execute($upd)) {
    throw new Exception('Failed to update workflow process');
  }
  
  $affected_rows = mysqli_stmt_affected_rows($upd);
  mysqli_stmt_close($upd);
  
  if ($affected_rows === 0) {
    throw new Exception('No workflow process record found to update');
  }

  // Log the action for audit trail
  $prevStatus = 'Waiting for Approval';
  if ($decision === 'RETURN_REQUESTOR' || $decision === 'RETURN_APPROVER') {
    $prevStatus = 'Submitted';
  }
  
  // Use helper function to log workflow action with work_flow_type_id
  logWorkflowAction($conn, $doc_type, $doc_number, $sequence, $actor_id, $action, 'status_change', $prevStatus, $newStatus, $remarks, $decider);

  // Handle parallel approvals at the same sequence
  $isParallel = false;
  $oneApprovalOnly = false;
  $tpl = mysqli_prepare($conn, "SELECT is_parellel, Note FROM work_flow_template WHERE work_flow_id = ? AND sequence = ? AND company = (SELECT company FROM financial_requests WHERE doc_number = ? LIMIT 1) LIMIT 1");
  if ($tpl) {
    mysqli_stmt_bind_param($tpl, 'sis', $doc_type, $sequence, $doc_number);
    mysqli_stmt_execute($tpl);
    $rs = mysqli_stmt_get_result($tpl);
    if ($row = mysqli_fetch_assoc($rs)) {
      $val = isset($row['is_parellel']) ? $row['is_parellel'] : '';
      $valStr = is_string($val) ? $val : (string)$val;
      $isParallel = (
        stripos($valStr, 'YES') !== false ||
        stripos($valStr, 'Y') !== false ||
        stripos($valStr, 'TRUE') !== false ||
        $val === 1 || $val === '1'
      );
      $note = isset($row['Note']) ? (string)$row['Note'] : '';
      $oneApprovalOnly = (stripos($note, 'one approval only') !== false || stripos($note, 'one approval') !== false);
    }
    mysqli_stmt_close($tpl);
  }

  if ($decision === 'APPROVE' && $isParallel && $oneApprovalOnly) {
    // Mark other peers at same sequence as skipped
    $skip = mysqli_prepare($conn, "UPDATE work_flow_process SET status='Skipped (Peer Approved)' WHERE doc_type=? AND doc_number=? AND sequence=? AND NOT (actor_id=? AND action=?) AND status LIKE 'Waiting%'");
    if ($skip) {
      mysqli_stmt_bind_param($skip, 'ssiss', $doc_type, $doc_number, $sequence, $actor_id, $action);
      mysqli_stmt_execute($skip);
      mysqli_stmt_close($skip);
    }
  } elseif (($decision === 'RETURN_APPROVER' || $decision === 'RETURN_REQUESTOR' || $decision === 'DECLINE') && $isParallel && $oneApprovalOnly) {
    // Any decision at a One-Approval-Only parallel step resolves the whole sequence
    $skip2 = mysqli_prepare($conn, "UPDATE work_flow_process SET status='Skipped (Peer Decided)' WHERE doc_type=? AND doc_number=? AND sequence=? AND NOT (actor_id=? AND action=?) AND status LIKE 'Waiting%'");
    if ($skip2) {
      mysqli_stmt_bind_param($skip2, 'ssiss', $doc_type, $doc_number, $sequence, $actor_id, $action);
      mysqli_stmt_execute($skip2);
      mysqli_stmt_close($skip2);
    }
  }

  // Handle workflow progression based on decision
  if ($decision === 'RETURN_APPROVER') {
    // Return to previous sequence
    $prevSeq = max(1, $sequence - 1);
    $stmt = mysqli_prepare($conn, "UPDATE work_flow_process SET status='Waiting for Approval', decided_by=NULL, decided_at=NULL, remarks=NULL WHERE doc_type=? AND doc_number=? AND sequence=?");
    if ($stmt) {
      mysqli_stmt_bind_param($stmt, 'ssi', $doc_type, $doc_number, $prevSeq);
      mysqli_stmt_execute($stmt);
      mysqli_stmt_close($stmt);
    }
  } elseif ($decision === 'RETURN_REQUESTOR') {
    // Return to Requestor at sequence 1
    $stmt = mysqli_prepare($conn, "UPDATE work_flow_process SET status='Waiting for Approval', decided_by=NULL, decided_at=NULL, remarks=NULL WHERE doc_type=? AND doc_number=? AND sequence=1");
    if ($stmt) {
      mysqli_stmt_bind_param($stmt, 'ss', $doc_type, $doc_number);
      $result = mysqli_stmt_execute($stmt);
      $affected = mysqli_stmt_affected_rows($stmt);
      mysqli_stmt_close($stmt);
      
      // Debug: Check if the update worked
      if ($affected === 0) {
        throw new Exception('Failed to update sequence 1 status to Waiting for Approval. No rows affected. Check if sequence 1 exists.');
      }
    }
    
    // Delete any higher sequences that haven't been processed yet
    // This ensures a clean slate for when the requestor resubmits
    $deleteHigherSeq = mysqli_prepare($conn, "DELETE FROM work_flow_process WHERE doc_type=? AND doc_number=? AND sequence > 1");
    if ($deleteHigherSeq) {
      mysqli_stmt_bind_param($deleteHigherSeq, 'ss', $doc_type, $doc_number);
      $deleteResult = mysqli_stmt_execute($deleteHigherSeq);
      $deletedRows = mysqli_stmt_affected_rows($deleteHigherSeq);
      mysqli_stmt_close($deleteHigherSeq);
      
      // Log the deletion of higher sequences (as a system event with empty remarks)
      if ($deletedRows > 0) {
        $systemUser = 'system';
        logWorkflowAction($conn, $doc_type, $doc_number, 0, 'system', 'sequence_deletion', 'sequence_deletion', 'Active', 'Deleted', '', $systemUser);
      }

      // Re-log the return to requestor decision to ensure user's remarks are the latest entry
      $decider2 = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';
      logWorkflowAction($conn, $doc_type, $doc_number, $sequence, $actor_id, $action, 'status_change', 'Submitted', 'Return to Requestor', $remarks, $decider2);
    }
  } elseif ($decision === 'DECLINE') {
    // Return to Requestor at sequence 1 (same as RETURN_REQUESTOR behavior)
    $stmt = mysqli_prepare($conn, "UPDATE work_flow_process SET status='Waiting for Approval', decided_by=NULL, decided_at=NULL, remarks=NULL WHERE doc_type=? AND doc_number=? AND sequence=1");
    if ($stmt) {
      mysqli_stmt_bind_param($stmt, 'ss', $doc_type, $doc_number);
      $result = mysqli_stmt_execute($stmt);
      $affected = mysqli_stmt_affected_rows($stmt);
      mysqli_stmt_close($stmt);
      
      // Debug: Check if the update worked
      if ($affected === 0) {
        throw new Exception('Failed to update sequence 1 status to Waiting for Approval. No rows affected. Check if sequence 1 exists.');
      }
    }
    
    // Delete any higher sequences that haven't been processed yet
    // This ensures a clean slate for when the requestor resubmits
    $deleteHigherSeq = mysqli_prepare($conn, "DELETE FROM work_flow_process WHERE doc_type=? AND doc_number=? AND sequence > 1");
    if ($deleteHigherSeq) {
      mysqli_stmt_bind_param($deleteHigherSeq, 'ss', $doc_type, $doc_number);
      $deleteResult = mysqli_stmt_execute($deleteHigherSeq);
      $deletedRows = mysqli_stmt_affected_rows($deleteHigherSeq);
      mysqli_stmt_close($deleteHigherSeq);
      
      // Log the deletion of higher sequences (as a system event with empty remarks)
      if ($deletedRows > 0) {
        $systemUser = 'system';
        logWorkflowAction($conn, $doc_type, $doc_number, 0, 'system', 'sequence_deletion', 'sequence_deletion', 'Active', 'Deleted', '', $systemUser);
      }

      // Re-log the decline decision to ensure user's remarks are the latest entry
      $decider2 = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';
      logWorkflowAction($conn, $doc_type, $doc_number, $sequence, $actor_id, $action, 'status_change', 'Submitted', 'Declined', $remarks, $decider2);
    }
    
    // Update header status to declined
    $stmt2 = mysqli_prepare($conn, "UPDATE financial_requests SET status='declined', updated_at=NOW() WHERE doc_number=? AND request_type=? LIMIT 1");
    if ($stmt2) {
      mysqli_stmt_bind_param($stmt2, 'ss', $doc_number, $doc_type);
      mysqli_stmt_execute($stmt2);
      mysqli_stmt_close($stmt2);
    }
  } elseif ($decision === 'SUBMIT') {
    // Handle submission - create sequence 2 if it doesn't exist
    if ($sequence === 1) {
      $nextSeq = 2;
      
      // Get company and department for next sequence lookup
      $getCompany = mysqli_prepare($conn, "SELECT company, cost_center FROM financial_requests WHERE doc_number=? AND request_type=? LIMIT 1");
      if ($getCompany) {
        mysqli_stmt_bind_param($getCompany, 'ss', $doc_number, $doc_type);
        mysqli_stmt_execute($getCompany);
        $companyResult = mysqli_stmt_get_result($getCompany);
        $companyRow = mysqli_fetch_assoc($companyResult);
        mysqli_stmt_close($getCompany);
        
        if ($companyRow) {
          $company = $companyRow['company'];
          $department = $companyRow['cost_center'];
          
          // Check if sequence 2 already exists
          $checkExisting = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM work_flow_process WHERE doc_type = ? AND doc_number = ? AND sequence = ?");
          $nextSeqExists = false;
          if ($checkExisting) {
            mysqli_stmt_bind_param($checkExisting, 'ssi', $doc_type, $doc_number, $nextSeq);
            mysqli_stmt_execute($checkExisting);
            $existingResult = mysqli_stmt_get_result($checkExisting);
            $existingRow = mysqli_fetch_assoc($existingResult);
            $nextSeqExists = ($existingRow && (int)$existingRow['count'] > 0);
            mysqli_stmt_close($checkExisting);
          }
          
          // Always create sequence 2 from template (this handles both new submissions and resubmissions after return)
          $getNextTpl = mysqli_prepare($conn, "SELECT sequence, actor_id, action, is_parellel, Note FROM work_flow_template 
                                           WHERE work_flow_id = ? AND company = ? AND sequence = ? 
                                           AND (department = ? OR department = '' OR department IS NULL)
                                           ORDER BY CASE WHEN department = ? THEN 0 ELSE 1 END, sequence, id");
          if ($getNextTpl) {
            mysqli_stmt_bind_param($getNextTpl, 'sssss', $doc_type, $company, $nextSeq, $department, $department);
            mysqli_stmt_execute($getNextTpl);
            $nextResult = mysqli_stmt_get_result($getNextTpl);
            

            
            $templateRows = mysqli_num_rows($nextResult);
            
            if ($templateRows > 0) {
              // If sequence 2 exists, delete it first to ensure clean recreation
              if ($nextSeqExists) {
                $deleteSeq2 = mysqli_prepare($conn, "DELETE FROM work_flow_process WHERE doc_type = ? AND doc_number = ? AND sequence = ?");
                if ($deleteSeq2) {
                  mysqli_stmt_bind_param($deleteSeq2, 'ssi', $doc_type, $doc_number, $nextSeq);
                  mysqli_stmt_execute($deleteSeq2);
                  mysqli_stmt_close($deleteSeq2);
                }
              }
              
              // Create fresh sequence 2 entries
              // Get workflow type ID for the new work_flow_type_id column
              $workflowTypeId = getWorkflowTypeId($conn, $doc_type);
              
              if ($workflowTypeId) {
                // Use new format with work_flow_type_id
                $insNext = mysqli_prepare($conn, "INSERT INTO work_flow_process (doc_type, work_flow_type_id, doc_number, sequence, actor_id, action, status, created_at) VALUES (?,?,?,?,?,?,?, NOW())");
              } else {
                // Fallback to old format without work_flow_type_id
                $insNext = mysqli_prepare($conn, "INSERT INTO work_flow_process (doc_type, doc_number, sequence, actor_id, action, status, created_at) VALUES (?,?,?,?,?,?, NOW())");
              }
              
              if ($insNext) {
                $createdCount = 0;
                while ($nextRow = mysqli_fetch_assoc($nextResult)) {
                  // Store actor_id as username/string directly (no user ID lookup)
                  $actor = (string)$nextRow['actor_id'];
                  if ($actor === '') { $actor = 'System'; }
                  
                  $actionName = (string)$nextRow['action'];
                  $status = 'Waiting for Approval';
                  
                  if ($workflowTypeId) {
                    mysqli_stmt_bind_param($insNext, 'sisisss', $doc_type, $workflowTypeId, $doc_number, $nextSeq, $actor, $actionName, $status);
                  } else {
                    mysqli_stmt_bind_param($insNext, 'ssisss', $doc_type, $doc_number, $nextSeq, $actor, $actionName, $status);
                  }
                  
                  if (mysqli_stmt_execute($insNext)) {
                    $createdCount++;
                  }
                }
                mysqli_stmt_close($insNext);
                
                // Log the creation of sequence 2
                $systemUser = 'system';
                $remarks = "Sequence 2 created after submission (Template rows: $templateRows, Created: $createdCount)";
                logWorkflowAction($conn, $doc_type, $doc_number, $nextSeq, 'system', 'sequence_creation', 'sequence_creation', '', 'Waiting for Approval', $remarks, $systemUser);
              }
            } else {
              // Log if no template found for sequence 2
              $systemUser = 'system';
              $remarks = 'No template found for sequence 2';
              logWorkflowAction($conn, $doc_type, $doc_number, $nextSeq, 'system', 'error', 'error', '', 'Error', $remarks, $systemUser);
            }
            mysqli_stmt_close($getNextTpl);
          }
        }
      }
    }
  } elseif ($decision === 'APPROVE') {
    // Check if all approvers at current sequence are approved
    $checkCurrent = mysqli_prepare($conn, "SELECT COUNT(*) as total, SUM(CASE WHEN status IN ('Approved', 'Skipped (Peer Approved)') THEN 1 ELSE 0 END) as approved FROM work_flow_process WHERE doc_type=? AND doc_number=? AND sequence=?");
    if ($checkCurrent) {
      mysqli_stmt_bind_param($checkCurrent, 'ssi', $doc_type, $doc_number, $sequence);
      mysqli_stmt_execute($checkCurrent);
      $result = mysqli_stmt_get_result($checkCurrent);
      $row = mysqli_fetch_assoc($result);
      mysqli_stmt_close($checkCurrent);
      
      // If all approvers at current sequence are approved, create next sequence
      if ($row && (int)$row['total'] > 0 && (int)$row['total'] === (int)$row['approved']) {
        $nextSeq = $sequence + 1;
        
        // Get company and department for next sequence lookup
        $getCompany = mysqli_prepare($conn, "SELECT company, cost_center FROM financial_requests WHERE doc_number=? AND request_type=? LIMIT 1");
        if ($getCompany) {
          mysqli_stmt_bind_param($getCompany, 'ss', $doc_number, $doc_type);
          mysqli_stmt_execute($getCompany);
          $companyResult = mysqli_stmt_get_result($getCompany);
          $companyRow = mysqli_fetch_assoc($companyResult);
          mysqli_stmt_close($getCompany);
          
          if ($companyRow) {
            $company = $companyRow['company'];
            $department = $companyRow['cost_center'];
            
            // Check if next sequence already exists in process table
            $checkExisting = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM work_flow_process WHERE doc_type = ? AND doc_number = ? AND sequence = ?");
            $nextSeqExists = false;
            if ($checkExisting) {
              mysqli_stmt_bind_param($checkExisting, 'ssi', $doc_type, $doc_number, $nextSeq);
              mysqli_stmt_execute($checkExisting);
              $existingResult = mysqli_stmt_get_result($checkExisting);
              $existingRow = mysqli_fetch_assoc($existingResult);
              $nextSeqExists = ($existingRow && (int)$existingRow['count'] > 0);
              mysqli_stmt_close($checkExisting);
            }
            
            // Only create next sequence if it doesn't exist; if it exists (e.g., returned), reactivate it
            if (!$nextSeqExists) {
              // Get next sequence template entries with better ordering
              $getNextTpl = mysqli_prepare($conn, "SELECT sequence, actor_id, action, is_parellel, Note FROM work_flow_template 
                                               WHERE work_flow_id = ? AND company = ? AND sequence = ? 
                                               AND (department = ? OR department = '' OR department IS NULL)
                                               ORDER BY CASE WHEN department = ? THEN 0 ELSE 1 END, sequence, id");
              if ($getNextTpl) {
                mysqli_stmt_bind_param($getNextTpl, 'sssss', $doc_type, $company, $nextSeq, $department, $department);
                mysqli_stmt_execute($getNextTpl);
                $nextResult = mysqli_stmt_get_result($getNextTpl);
                
                // Check if next sequence exists in template
                if (mysqli_num_rows($nextResult) > 0) {
                  // Create process entries for next sequence
                  // Get workflow type ID for the new work_flow_type_id column
                  $workflowTypeId = getWorkflowTypeId($conn, $doc_type);
                  
                  if ($workflowTypeId) {
                    // Use new format with work_flow_type_id
                    $insNext = mysqli_prepare($conn, "INSERT INTO work_flow_process (doc_type, work_flow_type_id, doc_number, sequence, actor_id, action, status, created_at) VALUES (?,?,?,?,?,?,?, NOW())");
                  } else {
                    // Fallback to old format without work_flow_type_id
                    $insNext = mysqli_prepare($conn, "INSERT INTO work_flow_process (doc_type, doc_number, sequence, actor_id, action, status, created_at) VALUES (?,?,?,?,?,?, NOW())");
                  }
                  
                  if ($insNext) {
                    $nextSeqCreated = false;
                    while ($nextRow = mysqli_fetch_assoc($nextResult)) {
                      // Store actor_id as username/string directly (no user ID lookup)
                      $actor = (string)$nextRow['actor_id'];
                      if ($actor === '') { $actor = 'System'; }
                      
                      $actionName = (string)$nextRow['action'];
                      $status = 'Waiting for Approval';
                      
                      if ($workflowTypeId) {
                        mysqli_stmt_bind_param($insNext, 'sisisss', $doc_type, $workflowTypeId, $doc_number, $nextSeq, $actor, $actionName, $status);
                      } else {
                        mysqli_stmt_bind_param($insNext, 'ssisss', $doc_type, $doc_number, $nextSeq, $actor, $actionName, $status);
                      }
                      
                      if (mysqli_stmt_execute($insNext)) {
                        $nextSeqCreated = true;
                      }
                    }
                    mysqli_stmt_close($insNext);
                    
                    // Log the creation of next sequence
                    // if ($nextSeqCreated) {
                    //   $systemUser = 'system';
                    //   $remarks = 'Next sequence created automatically';
                    //   logWorkflowAction($conn, $doc_type, $doc_number, $nextSeq, 'system', 'sequence_creation', 'sequence_creation', '', 'Waiting for Approval', $remarks, $systemUser);
                    // }
                  }
                } else {
                  // No more sequences in template - workflow is complete
                  $updateHeader = mysqli_prepare($conn, "UPDATE financial_requests SET status='approved', updated_at=NOW() WHERE doc_number=? AND request_type=? LIMIT 1");
                  if ($updateHeader) {
                    mysqli_stmt_bind_param($updateHeader, 'ss', $doc_number, $doc_type);
                    mysqli_stmt_execute($updateHeader);
                    mysqli_stmt_close($updateHeader);
                  }
                }
                mysqli_stmt_close($getNextTpl);
              }
            } else {
              // Reactivate existing next sequence (e.g., previously 'Return to Approver')
              $reactivateNext = mysqli_prepare($conn, "UPDATE work_flow_process SET status='Waiting for Approval', decided_by=NULL, decided_at=NULL, remarks=NULL WHERE doc_type=? AND doc_number=? AND sequence=?");
              if ($reactivateNext) {
                mysqli_stmt_bind_param($reactivateNext, 'ssi', $doc_type, $doc_number, $nextSeq);
                mysqli_stmt_execute($reactivateNext);
                mysqli_stmt_close($reactivateNext);
              }
            }
          }
        }
      }
    }
  }

  // Commit the transaction
  mysqli_commit($conn);
  $transaction_success = true;

} catch (Exception $e) {
  // Rollback on any error
  mysqli_rollback($conn);
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
  exit;
}

// Restore autocommit
mysqli_autocommit($conn, true);

if ($transaction_success) {
  // Send notifications based on decision
  $deciderIdent = isset($_SESSION['userid']) ? (string)$_SESSION['userid'] : (isset($_SESSION['username']) ? (string)$_SESSION['username'] : null);
  try {
    if ($decision === 'SUBMIT') {
      @frn_trigger_async_notification($doc_type, $doc_number, 'SUBMIT', $sequence, $deciderIdent, $remarks);
    } elseif ($decision === 'APPROVE') {
      @frn_trigger_async_notification($doc_type, $doc_number, 'APPROVE', $sequence, $deciderIdent, $remarks);
    } elseif ($decision === 'RETURN_REQUESTOR') {
      @frn_trigger_async_notification($doc_type, $doc_number, 'RETURN_REQUESTOR', $sequence, $deciderIdent, $remarks);
    } elseif ($decision === 'RETURN_APPROVER') {
      @frn_trigger_async_notification($doc_type, $doc_number, 'RETURN_APPROVER', $sequence, $deciderIdent, $remarks);
    } elseif ($decision === 'DECLINE') {
      @frn_trigger_async_notification($doc_type, $doc_number, 'DECLINE', $sequence, $deciderIdent, $remarks);
    }
  } catch (Throwable $e) {
    // ignore email errors
  }
  echo json_encode(['success' => true, 'message' => 'Workflow updated successfully']);
} else {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Unknown error occurred']);
}
exit;
?>