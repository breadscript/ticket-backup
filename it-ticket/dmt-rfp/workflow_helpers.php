<?php
/**
 * Workflow Helper Functions
 * 
 * This file contains helper functions for working with the new work_flow_type table
 * and maintaining backward compatibility with existing workflow systems.
 */

/**
 * Get workflow type ID by type code
 * 
 * @param mysqli $conn Database connection
 * @param string $typeCode Workflow type code (e.g., 'RFP', 'ERGR', 'ERL', 'PR', 'PO', 'PAW')
 * @return int|null Workflow type ID or null if not found
 */
function getWorkflowTypeId($conn, $typeCode) {
    if (!$typeCode || !$conn) {
        return null;
    }
    
    try {
        $stmt = mysqli_prepare($conn, 'SELECT id FROM work_flow_type WHERE type_code = ? AND is_active = 1 LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $typeCode);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $typeId = (int)$row['id'];
                mysqli_stmt_close($stmt);
                return $typeId;
            }
            mysqli_stmt_close($stmt);
        }
    } catch (Throwable $e) {
        error_log("Error looking up workflow type ID for code '$typeCode': " . $e->getMessage());
    }
    
    return null;
}

/**
 * Get workflow type code by ID
 * 
 * @param mysqli $conn Database connection
 * @param int $typeId Workflow type ID
 * @return string|null Workflow type code or null if not found
 */
function getWorkflowTypeCode($conn, $typeId) {
    if (!$typeId || !$conn) {
        return null;
    }
    
    try {
        $stmt = mysqli_prepare($conn, 'SELECT type_code FROM work_flow_type WHERE id = ? AND is_active = 1 LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $typeId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $typeCode = (string)$row['type_code'];
                mysqli_stmt_close($stmt);
                return $typeCode;
            }
            mysqli_stmt_close($stmt);
        }
    } catch (Throwable $e) {
        error_log("Error looking up workflow type code for ID '$typeId': " . $e->getMessage());
    }
    
    return null;
}

/**
 * Get all active workflow types
 * 
 * @param mysqli $conn Database connection
 * @return array Array of workflow types with id, type_code, type_name, description
 */
function getAllWorkflowTypes($conn) {
    if (!$conn) {
        return [];
    }
    
    try {
        $stmt = mysqli_prepare($conn, 'SELECT id, type_code, type_name, description FROM work_flow_type WHERE is_active = 1 ORDER BY id');
        if ($stmt) {
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $types = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $types[] = [
                    'id' => (int)$row['id'],
                    'type_code' => (string)$row['type_code'],
                    'type_name' => (string)$row['type_name'],
                    'description' => (string)$row['description']
                ];
            }
            mysqli_stmt_close($stmt);
            return $types;
        }
    } catch (Throwable $e) {
        error_log("Error getting all workflow types: " . $e->getMessage());
    }
    
    return [];
}

/**
 * Create workflow process with work_flow_type_id
 * 
 * @param mysqli $conn Database connection
 * @param string $docType Document type (e.g., 'RFP', 'ERGR', 'ERL', 'PR', 'PO', 'PAW')
 * @param string $docNumber Document number
 * @param int $sequence Sequence number
 * @param string $actorId Actor ID
 * @param string $action Action
 * @param string $status Status
 * @return bool Success status
 */
function createWorkflowProcess($conn, $docType, $docNumber, $sequence, $actorId, $action, $status) {
    if (!$conn || !$docType || !$docNumber || !$sequence || !$actorId || !$action || !$status) {
        return false;
    }
    
    try {
        // Get workflow type ID
        $workflowTypeId = getWorkflowTypeId($conn, $docType);
        
        // Prepare SQL with work_flow_type_id if available
        if ($workflowTypeId) {
            $sql = "INSERT INTO work_flow_process (doc_type, work_flow_type_id, doc_number, sequence, actor_id, action, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'sisisss', $docType, $workflowTypeId, $docNumber, $sequence, $actorId, $action, $status);
                $result = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                return $result;
            }
        } else {
            // Fallback to old format without work_flow_type_id
            $sql = "INSERT INTO work_flow_process (doc_type, doc_number, sequence, actor_id, action, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ssisss', $docType, $docNumber, $sequence, $actorId, $action, $status);
                $result = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                return $result;
            }
        }
    } catch (Throwable $e) {
        error_log("Error creating workflow process: " . $e->getMessage());
    }
    
    return false;
}

/**
 * Create workflow template with work_flow_type_id
 * 
 * @param mysqli $conn Database connection
 * @param string $workflowId Workflow ID (e.g., 'RFP', 'ERGR', 'ERL', 'PR', 'PO', 'PAW')
 * @param string $department Department
 * @param string $company Company
 * @param int $sequence Sequence
 * @param string $actorId Actor ID
 * @param string $action Action
 * @param int $isParallel Is parallel
 * @param int $global Is global
 * @param float|null $amountFrom Amount from
 * @param float|null $amountTo Amount to
 * @param string|null $note Note
 * @return bool Success status
 */
function createWorkflowTemplate($conn, $workflowId, $department, $company, $sequence, $actorId, $action, $isParallel = 0, $global = 0, $amountFrom = null, $amountTo = null, $note = null) {
    if (!$conn || !$workflowId || !$company || !$sequence || !$actorId || !$action) {
        return false;
    }
    
    try {
        // Get workflow type ID
        $workflowTypeId = getWorkflowTypeId($conn, $workflowId);
        
        // Prepare SQL with work_flow_type_id if available
        if ($workflowTypeId) {
            $sql = "INSERT INTO work_flow_template (work_flow_id, work_flow_type_id, department, company, sequence, actor_id, action, is_parellel, global, amount_from, amount_to, Note, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'sisssisidds', $workflowId, $workflowTypeId, $department, $company, $sequence, $actorId, $action, $isParallel, $global, $amountFrom, $amountTo, $note);
                $result = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                return $result;
            }
        } else {
            // Fallback to old format without work_flow_type_id
            $sql = "INSERT INTO work_flow_template (work_flow_id, department, company, sequence, actor_id, action, is_parellel, global, amount_from, amount_to, Note, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ssssisidds', $workflowId, $department, $company, $sequence, $actorId, $action, $isParallel, $global, $amountFrom, $amountTo, $note);
                $result = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                return $result;
            }
        }
    } catch (Throwable $e) {
        error_log("Error creating workflow template: " . $e->getMessage());
    }
    
    return false;
}

/**
 * Log workflow action with work_flow_type_id
 * 
 * @param mysqli $conn Database connection
 * @param string $docType Document type
 * @param string $docNumber Document number
 * @param int $sequence Sequence
 * @param string $actorId Actor ID
 * @param string $action Action
 * @param string $event Event
 * @param string|null $prevStatus Previous status
 * @param string|null $newStatus New status
 * @param string|null $remarks Remarks
 * @param string|null $createdBy Created by
 * @return bool Success status
 */
function logWorkflowAction($conn, $docType, $docNumber, $sequence, $actorId, $action, $event, $prevStatus = null, $newStatus = null, $remarks = null, $createdBy = null) {
    if (!$conn || !$docType || !$docNumber || !$sequence || !$actorId || !$action || !$event) {
        return false;
    }
    
    try {
        // Get workflow type ID
        $workflowTypeId = getWorkflowTypeId($conn, $docType);
        
        // Prepare SQL with work_flow_type_id if available
        if ($workflowTypeId) {
            $sql = "INSERT INTO work_flow_action_log (doc_type, work_flow_type_id, doc_number, sequence, actor_id, action, event, prev_status, new_status, remarks, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'sisisssssss', $docType, $workflowTypeId, $docNumber, $sequence, $actorId, $action, $event, $prevStatus, $newStatus, $remarks, $createdBy);
                $result = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                return $result;
            }
        } else {
            // Fallback to old format without work_flow_type_id
            $sql = "INSERT INTO work_flow_action_log (doc_type, doc_number, sequence, actor_id, action, event, prev_status, new_status, remarks, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ssisssssss', $docType, $docNumber, $sequence, $actorId, $action, $event, $prevStatus, $newStatus, $remarks, $createdBy);
                $result = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                return $result;
            }
        }
    } catch (Throwable $e) {
        error_log("Error logging workflow action: " . $e->getMessage());
    }
    
    return false;
}

/**
 * Get workflow templates with type information
 * 
 * @param mysqli $conn Database connection
 * @param string $workflowId Workflow ID
 * @param string $company Company
 * @param string|null $department Department (optional)
 * @return array Array of template rows
 */
function getWorkflowTemplates($conn, $workflowId, $company, $department = null) {
    if (!$conn || !$workflowId || !$company) {
        return [];
    }
    
    try {
        $sql = "SELECT t.*, wt.type_name, wt.description 
                FROM work_flow_template t 
                LEFT JOIN work_flow_type wt ON t.work_flow_type_id = wt.id 
                WHERE t.work_flow_id = ? AND t.company = ?";
        $params = [$workflowId, $company];
        $types = 'ss';
        
        if ($department) {
            $sql .= " AND (t.department = ? OR t.department = '' OR t.department IS NULL)";
            $params[] = $department;
            $types .= 's';
        }
        
        $sql .= " ORDER BY t.sequence, t.id";
        
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $templates = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $templates[] = $row;
            }
            mysqli_stmt_close($stmt);
            return $templates;
        }
    } catch (Throwable $e) {
        error_log("Error getting workflow templates: " . $e->getMessage());
    }
    
    return [];
}

/**
 * Validate workflow type code
 * 
 * @param string $typeCode Type code to validate
 * @return bool True if valid, false otherwise
 */
function isValidWorkflowType($typeCode) {
    $validTypes = ['RFP', 'ERGR', 'ERL', 'PR', 'PO', 'PAW'];
    return in_array($typeCode, $validTypes, true);
}

/**
 * Get workflow type display name
 * 
 * @param string $typeCode Type code
 * @return string Display name
 */
function getWorkflowTypeDisplayName($typeCode) {
    $displayNames = [
        'RFP' => 'Request for Proposal',
        'ERGR' => 'Expense Reimbursement General Request',
        'ERL' => 'Expense Reimbursement Liquidation',
        'PR' => 'Purchase Request',
        'PO' => 'Purchase Order',
        'PAW' => 'Promotional Activity Workplan'
    ];
    
    return $displayNames[$typeCode] ?? $typeCode;
}
?>
