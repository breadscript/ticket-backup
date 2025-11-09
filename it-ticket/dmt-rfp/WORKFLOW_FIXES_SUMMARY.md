# DMT-RFP Workflow Fixes Summary

## Issues Fixed

### 1. Payee Field Issue
**Problem**: The `payee` field in `financial_requests` table was saving user IDs as strings instead of actual integer user IDs from `sys_usertb`.

**Solution**: 
- Updated `save_financial_request.php` to store the actual user ID instead of converting it to string
- Changed database binding from string (`'s'`) to integer (`'i'`) for the payee field
- Updated database schema to change `payee` field from `VARCHAR(255)` to `BIGINT`
- Added foreign key constraint to ensure data integrity with `sys_usertb` table

### 2. Workflow Actor ID Issue
**Problem**: The `work_flow_process` table was saving actor names (like "BIMSY", "Cost Center Head") instead of user IDs from `sys_usertb`.

**Solution**:
- Added user lookup functions in both `save_financial_request.php` and `workflow_action.php`:
  - `getUserIdByUsername()`: Looks up user ID by username
  - `getUserIdByName()`: Looks up user ID by firstname + lastname
- Updated workflow process creation to:
  - For Requestor (sequence 1): Use actual user ID from session
  - For other actors (sequence 2+): Look up user ID from `sys_usertb` based on actor name
  - Fallback to actor name if user ID lookup fails (for backward compatibility)

## Files Modified

### 1. `save_financial_request.php`
- Fixed payee field to store actual user ID instead of string conversion
- Added user lookup functions
- Updated workflow process creation to use user IDs
- Changed database binding for payee field from string to integer

### 2. `workflow_action.php`
- Added user lookup functions
- Updated workflow process creation to use user IDs for new sequences
- Maintains backward compatibility for existing workflows

### 3. `financial_requests.sql`
- Changed `payee` field from `VARCHAR(255)` to `BIGINT`
- Added foreign key constraint to `sys_usertb(id)`

## Database Schema Changes

### Before:
```sql
`payee` VARCHAR(255) NULL,
```

### After:
```sql
`payee` BIGINT NULL,  -- Store user ID from sys_usertb
CONSTRAINT `fk_financial_requests_payee` FOREIGN KEY (`payee`) REFERENCES `sys_usertb`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
```

## Benefits

1. **Data Integrity**: Payee field now properly references actual users from `sys_usertb`
2. **Workflow Accuracy**: Workflow processes now store actual user IDs instead of names
3. **Referential Integrity**: Foreign key constraints ensure data consistency
4. **Backward Compatibility**: Existing workflows continue to work while new ones use proper user IDs
5. **Performance**: Integer lookups are faster than string comparisons

## Implementation Notes

- The system gracefully falls back to actor names if user ID lookup fails
- All existing functionality is preserved
- New financial requests will use the corrected user ID storage
- Workflow templates continue to work as before, but now resolve to actual user IDs

## Testing Recommendations

1. Test creating new financial requests to ensure payee is stored as integer
2. Test workflow approval process to ensure actor_id fields contain user IDs
3. Verify foreign key constraints work correctly
4. Test backward compatibility with existing workflows
5. Verify user lookup functions work for various username/name formats
