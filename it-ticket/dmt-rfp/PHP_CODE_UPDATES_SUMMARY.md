# PHP Code Updates Summary

## Overview

This document summarizes the PHP code updates made to support the new `work_flow_type` table and the additional workflow types (PR, PO, PAW) in the existing workflow system.

## Files Updated

### 1. **workflow_helpers.php** (NEW FILE)
**Purpose**: Centralized helper functions for workflow type management

**Key Functions**:
- `getWorkflowTypeId($conn, $typeCode)` - Get workflow type ID by type code
- `getWorkflowTypeCode($conn, $typeId)` - Get workflow type code by ID
- `getAllWorkflowTypes($conn)` - Get all active workflow types
- `createWorkflowProcess($conn, ...)` - Create workflow process with work_flow_type_id
- `createWorkflowTemplate($conn, ...)` - Create workflow template with work_flow_type_id
- `logWorkflowAction($conn, ...)` - Log workflow action with work_flow_type_id
- `getWorkflowTemplates($conn, ...)` - Get workflow templates with type information
- `isValidWorkflowType($typeCode)` - Validate workflow type code
- `getWorkflowTypeDisplayName($typeCode)` - Get display name for workflow type

**Features**:
- Backward compatibility with existing systems
- Automatic fallback to old format if work_flow_type_id is not available
- Error handling and logging
- Support for all workflow types: RFP, ERGR, ERL, PR, PO, PAW

### 2. **workflow_template_save.php**
**Updates Made**:
- Added `require_once __DIR__ . '/workflow_helpers.php';`
- Added workflow type validation using `isValidWorkflowType()`
- Updated INSERT/UPDATE queries to include `work_flow_type_id` column
- Added fallback logic for backward compatibility
- Enhanced error handling

**Key Changes**:
```php
// Get workflow type ID
$workflowTypeId = getWorkflowTypeId($mysqlconn, $workflowId);

// Use new format with work_flow_type_id if available
if ($workflowTypeId) {
    $sql = "INSERT INTO work_flow_template (work_flow_id, work_flow_type_id, ...) VALUES (?, ?, ...)";
} else {
    // Fallback to old format
    $sql = "INSERT INTO work_flow_template (work_flow_id, ...) VALUES (?, ...)";
}
```

### 3. **save_financial_request.php**
**Updates Made**:
- Added `require_once __DIR__ . '/workflow_helpers.php';`
- Updated request type validation to include new types: `['RFP', 'ERL', 'ERGR', 'PR', 'PO', 'PAW']`
- Modified workflow process creation to include `work_flow_type_id`
- Enhanced workflow process creation with proper type ID handling

**Key Changes**:
```php
// Updated validation
$request_type = validate_enum_value($_POST['request_type'] ?? null, ['RFP', 'ERL', 'ERGR', 'PR', 'PO', 'PAW']);

// Get workflow type ID for the new work_flow_type_id column
$workflowTypeId = getWorkflowTypeId($conn, $request_type);

// Use new format with work_flow_type_id if available
if ($workflowTypeId) {
    $insProc = mysqli_prepare($conn, "INSERT INTO work_flow_process (doc_type, work_flow_type_id, doc_number, ...) VALUES (?,?,?,...)");
} else {
    // Fallback to old format
    $insProc = mysqli_prepare($conn, "INSERT INTO work_flow_process (doc_type, doc_number, ...) VALUES (?,?,...)");
}
```

### 4. **workflow_action.php**
**Updates Made**:
- Added `require_once __DIR__ . '/workflow_helpers.php';`
- Replaced manual logging with `logWorkflowAction()` helper function
- Updated workflow process creation to include `work_flow_type_id`
- Enhanced sequence creation logic with proper type ID handling

**Key Changes**:
```php
// Use helper function for logging
logWorkflowAction($conn, $doc_type, $doc_number, $sequence, $actor_id, $action, 'status_change', $prevStatus, $newStatus, $remarks, $decider);

// Get workflow type ID for new sequences
$workflowTypeId = getWorkflowTypeId($conn, $doc_type);

// Use new format with work_flow_type_id if available
if ($workflowTypeId) {
    $insNext = mysqli_prepare($conn, "INSERT INTO work_flow_process (doc_type, work_flow_type_id, doc_number, ...) VALUES (?,?,?,...)");
} else {
    // Fallback to old format
    $insNext = mysqli_prepare($conn, "INSERT INTO work_flow_process (doc_type, doc_number, ...) VALUES (?,?,...)");
}
```

### 5. **workflow_template_form.php**
**Updates Made**:
- Updated workflow types array to include new types: `['RFP', 'ERL', 'ERGR', 'PR', 'PO', 'PAW']`
- Enhanced workflow type dropdown with display names
- Added user-friendly labels for each workflow type

**Key Changes**:
```php
// Updated workflow types
$workflowTypes = ['RFP', 'ERL', 'ERGR', 'PR', 'PO', 'PAW'];

// Enhanced dropdown with display names
$workflowTypeNames = [
    'RFP' => 'Request for Proposal',
    'ERL' => 'Expense Reimbursement Liquidation',
    'ERGR' => 'Expense Reimbursement General Request',
    'PR' => 'Purchase Request',
    'PO' => 'Purchase Order',
    'PAW' => 'Promotional Activity Workplan'
];
```

## Backward Compatibility

All updates maintain full backward compatibility:

1. **Existing Data**: All existing workflow data continues to work without modification
2. **Fallback Logic**: If `work_flow_type_id` is not available, the system falls back to the old format
3. **Existing Functionality**: Current workflows continue to work without any changes
4. **Database Schema**: Original columns are preserved alongside new ones

## New Workflow Types Supported

| Code | Name | Description |
|------|------|-------------|
| RFP | Request for Proposal | Request for Proposal workflow |
| ERGR | Expense Reimbursement General Request | Expense Reimbursement General Request workflow |
| ERL | Expense Reimbursement Liquidation | Expense Reimbursement Liquidation workflow |
| PR | Purchase Request | Purchase Request workflow |
| PO | Purchase Order | Purchase Order workflow |
| PAW | Promotional Activity Workplan | Promotional Activity Workplan workflow |

## Benefits

1. **Centralized Management**: Workflow types are now centrally managed in the `work_flow_type` table
2. **Better Organization**: Clear separation between workflow types and their configurations
3. **Easier Maintenance**: Adding new workflow types is straightforward
4. **Improved Performance**: Better indexing and query performance with proper foreign keys
5. **Data Integrity**: Foreign key constraints ensure data consistency
6. **Scalability**: Easy to add new workflow types without schema changes

## Error Handling

All functions include comprehensive error handling:
- Database connection validation
- SQL statement preparation checks
- Graceful fallbacks for missing data
- Error logging for debugging
- Exception handling for critical operations

## Testing Recommendations

1. **Test Existing Workflows**: Verify that existing RFP, ERGR, and ERL workflows continue to work
2. **Test New Workflow Types**: Create and test PR, PO, and PAW workflows
3. **Test Backward Compatibility**: Ensure old data works with new code
4. **Test Error Scenarios**: Verify error handling and fallback mechanisms
5. **Test Database Migration**: Run the migration script and verify data integrity

## Usage Examples

### Creating a New Workflow Template
```php
$success = createWorkflowTemplate(
    $conn, 
    'PR',           // workflow type code
    'TLCI-IT',      // department
    'TLCI',         // company
    2,              // sequence
    'RAYMOND',      // actor ID
    'Cost_Center_Head', // action
    0,              // is parallel
    0,              // is global
    null,           // amount from
    null,           // amount to
    null            // note
);
```

### Creating a Workflow Process
```php
$success = createWorkflowProcess(
    $conn,
    'PR',                    // document type
    'PR-20241228001',        // document number
    1,                       // sequence
    '123',                   // actor ID
    'Requestor',             // action
    'Submitted'              // status
);
```

### Logging Workflow Actions
```php
$success = logWorkflowAction(
    $conn,
    'PR',                    // document type
    'PR-20241228001',        // document number
    1,                       // sequence
    '123',                   // actor ID
    'Requestor',             // action
    'status_change',         // event
    'Submitted',             // previous status
    'Approved',              // new status
    'Approved by manager',   // remarks
    'manager_user'           // created by
);
```

## Conclusion

The PHP code updates successfully integrate the new `work_flow_type` table while maintaining full backward compatibility. The system now supports all six workflow types (RFP, ERGR, ERL, PR, PO, PAW) with proper data integrity and improved maintainability.

All existing functionality continues to work as before, while new features are available for enhanced workflow management.
