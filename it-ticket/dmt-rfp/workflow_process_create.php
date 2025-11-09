<?php
date_default_timezone_set('Asia/Manila');
session_start();
include "blocks/inc.resource.php";

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    header('Location: ../login.php');
    exit;
}

// Check CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = 'Invalid security token. Please try again.';
    header('Location: workflow_template_init.php');
    exit;
}

// Check form timestamp (prevent replay attacks)
$formTimestamp = isset($_POST['form_timestamp']) ? (int)$_POST['form_timestamp'] : 0;
$currentTime = time();
if (abs($currentTime - $formTimestamp) > 3600) { // 1 hour
    $_SESSION['error'] = 'Form has expired. Please try again.';
    header('Location: workflow_template_init.php');
    exit;
}

// Get form data
$workflowId = trim($_POST['work_flow_id'] ?? '');
$company = trim($_POST['company'] ?? '');
$department = trim($_POST['department'] ?? '');
$docNumber = trim($_POST['doc_number'] ?? '');
$amount = !empty($_POST['amount']) ? (float)$_POST['amount'] : null;
$remarks = trim($_POST['remarks'] ?? '');

// Validation
$errors = [];

if (empty($workflowId)) {
    $errors[] = 'Workflow Type is required.';
}

if (empty($company)) {
    $errors[] = 'Company is required.';
}

if (empty($docNumber)) {
    $errors[] = 'Document Number is required.';
}

if ($amount !== null && $amount < 0) {
    $errors[] = 'Amount cannot be negative.';
}

// If there are validation errors, redirect back with errors
if (!empty($errors)) {
    $_SESSION['error'] = 'Please correct the following errors:<br>' . implode('<br>', $errors);
    $_SESSION['form_data'] = $_POST;
    header('Location: workflow_template_init.php');
    exit;
}

// Check if document already has a workflow process
if (isset($mysqlconn) && $mysqlconn) {
    $checkSql = "SELECT COUNT(*) as cnt FROM work_flow_process WHERE doc_type = ? AND doc_number = ?";
    if ($st = mysqli_prepare($mysqlconn, $checkSql)) {
        mysqli_stmt_bind_param($st, 'ss', $workflowId, $docNumber);
        mysqli_stmt_execute($st);
        $rs = mysqli_stmt_get_result($st);
        $row = mysqli_fetch_assoc($rs);
        
        if ((int)$row['cnt'] > 0) {
            $_SESSION['error'] = 'A workflow process already exists for document ' . htmlspecialchars($docNumber, ENT_QUOTES, 'UTF-8');
            $_SESSION['form_data'] = $_POST;
            header('Location: workflow_template_init.php');
            exit;
        }
        mysqli_stmt_close($st);
    }
}

// Get applicable templates
$templates = [];
if (isset($mysqlconn) && $mysqlconn) {
    $templateSql = "SELECT id, sequence, actor_id, action, is_parellel, global, amount_from, amount_to, Note 
                    FROM work_flow_template 
                    WHERE work_flow_id = ? AND company = ?";
    $params = [$workflowId, $company];
    $types = 'ss';
    
    if ($department) {
        $templateSql .= " AND (department = ? OR global = 1)";
        $params[] = $department;
        $types .= 's';
    } else {
        $templateSql .= " AND global = 1";
    }
    
    $templateSql .= " ORDER BY sequence, id";
    
    if ($st = mysqli_prepare($mysqlconn, $templateSql)) {
        mysqli_stmt_bind_param($st, $types, ...$params);
        mysqli_stmt_execute($st);
        $rs = mysqli_stmt_get_result($st);
        
        while ($row = mysqli_fetch_assoc($rs)) {
            // Check if amount range matches
            $amountFrom = $row['amount_from'];
            $amountTo = $row['amount_to'];
            
            if ($amount !== null) {
                // If amount is specified, check if it falls within the range
                if (($amountFrom !== null && $amount < $amountFrom) || 
                    ($amountTo !== null && $amount > $amountTo)) {
                    continue; // Skip this template if amount doesn't match
                }
            }
            
            $templates[] = $row;
        }
        mysqli_stmt_close($st);
    }
}

if (empty($templates)) {
    $_SESSION['error'] = 'No applicable workflow templates found for the selected criteria.';
    $_SESSION['form_data'] = $_POST;
    header('Location: workflow_template_init.php');
    exit;
}

// Create workflow process
try {
    if (isset($mysqlconn) && $mysqlconn) {
        // Insert main workflow process
        $processSql = "INSERT INTO work_flow_process 
                       (doc_type, doc_number, company, department, amount, remarks, 
                        status, created_by, created_at) 
                       VALUES (?, ?, ?, ?, ?, ?, 'PENDING', ?, NOW())";
        
        if ($st = mysqli_prepare($mysqlconn, $processSql)) {
            $createdBy = $_SESSION['userid'] ?? 1;
            mysqli_stmt_bind_param($st, 'ssssdsi', 
                $workflowId, $docNumber, $company, $department, 
                $amount, $remarks, $createdBy);
            
            if (mysqli_stmt_execute($st)) {
                $processId = mysqli_insert_id($mysqlconn);
                
                // Create workflow steps
                foreach ($templates as $template) {
                    $stepSql = "INSERT INTO work_flow_step 
                                (process_id, template_id, sequence, actor_id, action, 
                                 is_parellel, status, created_at) 
                                VALUES (?, ?, ?, ?, ?, ?, 'PENDING', NOW())";
                    
                    if ($stepSt = mysqli_prepare($mysqlconn, $stepSql)) {
                        mysqli_stmt_bind_param($stepSt, 'iiissi', 
                            $processId, $template['id'], $template['sequence'], 
                            $template['actor_id'], $template['action'], $template['is_parellel']);
                        
                        mysqli_stmt_execute($stepSt);
                        mysqli_stmt_close($stepSt);
                    }
                }
                
                $_SESSION['success'] = 'Workflow process created successfully for document ' . htmlspecialchars($docNumber, ENT_QUOTES, 'UTF-8');
                header('Location: workflow_template_main.php');
            } else {
                throw new Exception('Failed to create workflow process: ' . mysqli_stmt_error($st));
            }
            mysqli_stmt_close($st);
        } else {
            throw new Exception('Failed to prepare workflow process statement: ' . mysqli_error($mysqlconn));
        }
    } else {
        throw new Exception('Database connection not available');
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    $_SESSION['form_data'] = $_POST;
    header('Location: workflow_template_init.php');
}

exit;
?>
