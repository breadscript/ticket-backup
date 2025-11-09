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

// Get template ID
$templateId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($templateId <= 0) {
    $_SESSION['error'] = 'Invalid template ID.';
    header('Location: workflow_template_main.php');
    exit;
}

// Check if template exists and get its details
$template = null;
if (isset($mysqlconn) && $mysqlconn) {
    if ($st = mysqli_prepare($mysqlconn, 'SELECT work_flow_id, department, company, sequence, actor_id, action FROM work_flow_template WHERE id=?')) {
        mysqli_stmt_bind_param($st, 'i', $templateId);
        mysqli_stmt_execute($st);
        $rs = mysqli_stmt_get_result($st);
        $template = mysqli_fetch_assoc($rs);
        mysqli_stmt_close($st);
    }
}

if (!$template) {
    $_SESSION['error'] = 'Template not found.';
    header('Location: workflow_template_main.php');
    exit;
}

// Check if template is being used in any active workflow processes
$isInUse = false;
if (isset($mysqlconn) && $mysqlconn) {
    $checkSql = "SELECT COUNT(*) as cnt FROM work_flow_process 
                  WHERE doc_type = ? AND status IN ('Waiting for Approval', 'In Progress')";
    
    if ($st = mysqli_prepare($mysqlconn, $checkSql)) {
        mysqli_stmt_bind_param($st, 's', $template['work_flow_id']);
        mysqli_stmt_execute($st);
        $rs = mysqli_stmt_get_result($st);
        $row = mysqli_fetch_assoc($rs);
        $isInUse = (int)$row['cnt'] > 0;
        mysqli_stmt_close($st);
    }
}

// If template is in use, show warning
if ($isInUse) {
    $_SESSION['error'] = 'Cannot delete template. It is currently being used in active workflow processes.';
    header('Location: workflow_template_main.php');
    exit;
}

// Proceed with deletion
try {
    if (isset($mysqlconn) && $mysqlconn) {
        // Delete the template
        $deleteSql = "DELETE FROM work_flow_template WHERE id = ?";
        
        if ($st = mysqli_prepare($mysqlconn, $deleteSql)) {
            mysqli_stmt_bind_param($st, 'i', $templateId);
            
            if (mysqli_stmt_execute($st)) {
                $affectedRows = mysqli_stmt_affected_rows($st);
                mysqli_stmt_close($st);
                
                if ($affectedRows > 0) {
                    $_SESSION['success'] = 'Workflow template deleted successfully.';
                    
                    // Log the deletion using helper (includes work_flow_type_id when available)
                    $remarks = "Template deleted: " . $template['work_flow_id'] . " - " . $template['company'];
                    if (!empty($template['department'])) {
                        $remarks .= " (" . $template['department'] . ")";
                    }
                    $remarks .= " - Sequence " . $template['sequence'] . " - " . $template['actor_id'];
                    $createdBy = isset($_SESSION['userid']) ? (string)$_SESSION['userid'] : 'System';
                    logWorkflowAction($mysqlconn, (string)$template['work_flow_id'], 'TEMPLATE-' . (string)$templateId, (int)$template['sequence'], (string)$template['actor_id'], (string)$template['action'], 'DELETE', null, null, $remarks, $createdBy);
                } else {
                    $_SESSION['error'] = 'Template was not deleted. It may have already been removed.';
                }
            } else {
                throw new Exception('Failed to delete template: ' . mysqli_stmt_error($st));
            }
        } else {
            throw new Exception('Failed to prepare delete statement: ' . mysqli_error($mysqlconn));
        }
    } else {
        throw new Exception('Database connection not available');
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
}

// Redirect back to main page
header('Location: workflow_template_main.php');
exit;
?>
