<?php
date_default_timezone_set('Asia/Manila');
session_start();
include "blocks/inc.resource.php";
require_once __DIR__ . '/workflow_helpers.php';

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    header('Location: ../login.php');
    exit;
}

// Check CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = 'Invalid security token. Please try again.';
    header('Location: workflow_template_main.php');
    exit;
}

// Check form timestamp (prevent replay attacks)
$formTimestamp = isset($_POST['form_timestamp']) ? (int)$_POST['form_timestamp'] : 0;
$currentTime = time();
if (abs($currentTime - $formTimestamp) > 3600) { // 1 hour
    $_SESSION['error'] = 'Form has expired. Please try again.';
    header('Location: workflow_template_main.php');
    exit;
}

// Get form data
$editId = isset($_POST['__edit_id']) ? (int)$_POST['__edit_id'] : 0;
$workflowId = trim($_POST['work_flow_id'] ?? '');
$department = trim($_POST['department'] ?? '');
$company = trim($_POST['company'] ?? '');
$sequence = (int)($_POST['sequence'] ?? 1);
$actorId = trim($_POST['actor_id'] ?? '');
$action = trim($_POST['action'] ?? '');
$isParallel = isset($_POST['is_parellel']) ? 1 : 0;
$global = isset($_POST['global']) ? 1 : 0;
$amountFrom = !empty($_POST['amount_from']) ? (float)$_POST['amount_from'] : null;
$amountTo = !empty($_POST['amount_to']) ? (float)$_POST['amount_to'] : null;
$note = trim($_POST['Note'] ?? '');

// Debug: Log received data
error_log("=== Workflow Template Save Debug ===");
error_log("Edit ID: " . $editId);
error_log("Workflow ID: " . $workflowId);
error_log("Company: " . $company);
error_log("Department: " . $department);
error_log("Sequence: " . $sequence);
error_log("Actor ID (raw): " . $actorId);
error_log("Action: " . $action);
error_log("Is Parallel: " . $isParallel);
error_log("Global: " . $global);
error_log("Amount From: " . ($amountFrom ?? 'NULL'));
error_log("Amount To: " . ($amountTo ?? 'NULL'));
error_log("Note: " . $note);
error_log("Raw POST data: " . print_r($_POST, true));

// Get user's firstname based on actor_id
$actorFirstname = '';
if (!empty($actorId) && isset($mysqlconn) && $mysqlconn) {
    $userSql = "SELECT user_firstname FROM sys_usertb WHERE id = ?";
    if ($userStmt = mysqli_prepare($mysqlconn, $userSql)) {
        mysqli_stmt_bind_param($userStmt, 'i', $actorId);
        mysqli_stmt_execute($userStmt);
        $userResult = mysqli_stmt_get_result($userStmt);
        if ($userRow = mysqli_fetch_assoc($userResult)) {
            $actorFirstname = trim($userRow['user_firstname'] ?? '');
        }
        mysqli_stmt_close($userStmt);
    }
}

// If action is Requestor, set actor to 'Requestor' regardless of selected user
if (strcasecmp($action, 'Requestor') === 0) {
    $actorFirstname = 'Requestor';
}

error_log("Actor Firstname found: " . $actorFirstname);

// Validation
$errors = [];

if (empty($workflowId)) {
    $errors[] = 'Workflow Type is required.';
}

// Validate workflow type code
if (!isValidWorkflowType($workflowId)) {
    $errors[] = 'Invalid workflow type. Please select a valid workflow type.';
}

if (empty($company)) {
    $errors[] = 'Company is required.';
}

if ($sequence < 1 || $sequence > 99) {
    $errors[] = 'Sequence must be between 1 and 99.';
}

if (strcasecmp($action, 'Requestor') !== 0) {
    if (empty($actorId)) {
        $errors[] = 'Actor ID is required.';
    }
    if (empty($actorFirstname)) {
        $errors[] = 'Selected user not found or has no firstname.';
    }
}

if (empty($action)) {
    $errors[] = 'Action is required.';
}

// Validate amount range
if ($amountFrom !== null && $amountTo !== null && $amountFrom > $amountTo) {
    $errors[] = 'Amount From cannot be greater than Amount To.';
}

if ($amountFrom !== null && $amountFrom < 0) {
    $errors[] = 'Amount From cannot be negative.';
}

if ($amountTo !== null && $amountTo < 0) {
    $errors[] = 'Amount To cannot be negative.';
}

// If global is checked, clear department
if ($global) {
    $department = '';
}

// Check for duplicate sequence within same workflow/company/department combination
if (isset($mysqlconn) && $mysqlconn) {
    $checkSql = "SELECT id FROM work_flow_template WHERE work_flow_id = ? AND company = ? AND sequence = ?";
    $params = [$workflowId, $company, $sequence];
    $types = 'ssi';
    
    if ($department) {
        $checkSql .= " AND department = ?";
        $params[] = $department;
        $types .= 's';
    } else {
        $checkSql .= " AND (department IS NULL OR department = '')";
    }
    
    if ($editId > 0) {
        $checkSql .= " AND id != ?";
        $params[] = $editId;
        $types .= 'i';
    }
    
    if ($st = mysqli_prepare($mysqlconn, $checkSql)) {
        mysqli_stmt_bind_param($st, $types, ...$params);
        mysqli_stmt_execute($st);
        $rs = mysqli_stmt_get_result($st);
        
        if (mysqli_num_rows($rs) > 0) {
            $errors[] = "A template with sequence $sequence already exists for this workflow type, company, and department combination.";
        }
        mysqli_stmt_close($st);
    }
}

// If there are validation errors, redirect back with errors
if (!empty($errors)) {
    $_SESSION['error'] = 'Please correct the following errors:<br>' . implode('<br>', $errors);
    $_SESSION['form_data'] = $_POST;
    
    if ($editId > 0) {
        header('Location: workflow_template_form.php?id=' . $editId);
    } else {
        header('Location: workflow_template_form.php');
    }
    exit;
}

// Proceed with save/update
try {
    // Get workflow type ID
    $workflowTypeId = getWorkflowTypeId($mysqlconn, $workflowId);
    
    if ($editId > 0) {
        // Update existing template
        if ($workflowTypeId) {
            $sql = "UPDATE work_flow_template SET 
                    work_flow_id = ?, work_flow_type_id = ?, department = ?, company = ?, sequence = ?, 
                    actor_id = ?, action = ?, is_parellel = ?, global = ?, 
                    amount_from = ?, amount_to = ?, Note = ?, updated_at = NOW()
                    WHERE id = ?";
            
            if ($st = mysqli_prepare($mysqlconn, $sql)) {
                mysqli_stmt_bind_param($st, 'sississiiddsi', 
                    $workflowId, $workflowTypeId, $department, $company, $sequence, 
                    $actorFirstname, $action, $isParallel, $global, 
                    $amountFrom, $amountTo, $note, $editId);
                
                if (mysqli_stmt_execute($st)) {
                    $_SESSION['success'] = 'Workflow template updated successfully.';
                    header('Location: workflow_template_view.php?id=' . $editId);
                } else {
                    throw new Exception('Failed to update template: ' . mysqli_stmt_error($st));
                }
                mysqli_stmt_close($st);
            } else {
                throw new Exception('Failed to prepare update statement: ' . mysqli_error($mysqlconn));
            }
        } else {
            // Fallback to old format without work_flow_type_id
            $sql = "UPDATE work_flow_template SET 
                    work_flow_id = ?, department = ?, company = ?, sequence = ?, 
                    actor_id = ?, action = ?, is_parellel = ?, global = ?, 
                    amount_from = ?, amount_to = ?, Note = ?, updated_at = NOW()
                    WHERE id = ?";
            
            if ($st = mysqli_prepare($mysqlconn, $sql)) {
                mysqli_stmt_bind_param($st, 'sssissiiddsi', 
                    $workflowId, $department, $company, $sequence, 
                    $actorFirstname, $action, $isParallel, $global, 
                    $amountFrom, $amountTo, $note, $editId);
                
                if (mysqli_stmt_execute($st)) {
                    $_SESSION['success'] = 'Workflow template updated successfully.';
                    header('Location: workflow_template_view.php?id=' . $editId);
                } else {
                    throw new Exception('Failed to update template: ' . mysqli_stmt_error($st));
                }
                mysqli_stmt_close($st);
            } else {
                throw new Exception('Failed to prepare update statement: ' . mysqli_error($mysqlconn));
            }
        }
    } else {
        // Create new template
        if ($workflowTypeId) {
            $sql = "INSERT INTO work_flow_template 
                    (work_flow_id, work_flow_type_id, department, company, sequence, actor_id, action, 
                     is_parellel, global, amount_from, amount_to, Note, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            if ($st = mysqli_prepare($mysqlconn, $sql)) {
                // Types: s i s s i s s i i d d s (12)
                mysqli_stmt_bind_param($st, 'sississiidds', 
                    $workflowId, $workflowTypeId, $department, $company, $sequence, 
                    $actorFirstname, $action, $isParallel, $global, 
                    $amountFrom, $amountTo, $note);
                
                if (mysqli_stmt_execute($st)) {
                    $newId = mysqli_insert_id($mysqlconn);
                    $_SESSION['success'] = 'Workflow template created successfully.';
                    header('Location: workflow_template_view.php?id=' . $newId);
                } else {
                    throw new Exception('Failed to create template: ' . mysqli_stmt_error($st));
                }
                mysqli_stmt_close($st);
            } else {
                throw new Exception('Failed to prepare insert statement: ' . mysqli_error($mysqlconn));
            }
        } else {
            // Fallback to old format without work_flow_type_id
            $sql = "INSERT INTO work_flow_template 
                    (work_flow_id, department, company, sequence, actor_id, action, 
                     is_parellel, global, amount_from, amount_to, Note, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            if ($st = mysqli_prepare($mysqlconn, $sql)) {
                mysqli_stmt_bind_param($st, 'ssssissidds', 
                    $workflowId, $department, $company, $sequence, 
                    $actorFirstname, $action, $isParallel, $global, 
                    $amountFrom, $amountTo, $note);
                
                if (mysqli_stmt_execute($st)) {
                    $newId = mysqli_insert_id($mysqlconn);
                    $_SESSION['success'] = 'Workflow template created successfully.';
                    header('Location: workflow_template_view.php?id=' . $newId);
                } else {
                    throw new Exception('Failed to create template: ' . mysqli_stmt_error($st));
                }
                mysqli_stmt_close($st);
            } else {
                throw new Exception('Failed to prepare insert statement: ' . mysqli_error($mysqlconn));
            }
        }
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    $_SESSION['form_data'] = $_POST;
    
    if ($editId > 0) {
        header('Location: workflow_template_form.php?id=' . $editId);
    } else {
        header('Location: workflow_template_form.php');
    }
}

exit;
?>