# Workflow Type Implementation Guide

## Overview

This implementation adds a new `work_flow_type` table to the existing workflow system, allowing for better organization and management of different document types. The system now supports both existing workflow types (RFP, ERGR, ERL) and new types (PR, PO, PAW).

## New Workflow Types

| ID | Code | Name | Description |
|----|------|------|-------------|
| 1 | RFP | Request for Proposal | Request for Proposal workflow |
| 2 | ERGR | Expense Reimbursement General Request | Expense Reimbursement General Request workflow |
| 3 | ERL | Expense Reimbursement Liquidation | Expense Reimbursement Liquidation workflow |
| 4 | PR | Purchase Request | Purchase Request workflow |
| 5 | PO | Purchase Order | Purchase Order workflow |
| 6 | PAW | Promotional Activity Workplan | Promotional Activity Workplan workflow |

## Database Schema Changes

### 1. New Table: `work_flow_type`

```sql
CREATE TABLE `work_flow_type` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type_code` varchar(10) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_type_code` (`type_code`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### 2. Updated Tables

All existing workflow tables now include a `work_flow_type_id` column that references the `work_flow_type` table:

- `work_flow_template` - Added `work_flow_type_id` column
- `work_flow_process` - Added `work_flow_type_id` column  
- `work_flow_action_log` - Added `work_flow_type_id` column

## Migration Process

### Step 1: Backup Database
**IMPORTANT**: Always backup your database before running the migration script.

```bash
mysqldump -u username -p database_name > backup_before_migration.sql
```

### Step 2: Run Migration Script
Execute the migration script:

```bash
mysql -u username -p database_name < migrate_to_work_flow_type.sql
```

### Step 3: Verify Migration
The migration script includes verification queries that will show:
- All workflow types created
- Template counts by type
- Process counts by type
- Action log counts by type

## File Structure

```
dmt-rfp/db/
├── work_flow_type.sql                    # New work_flow_type table
├── work_flow_template_updated.sql        # Updated template table structure
├── work_flow_process_updated.sql         # Updated process table structure
├── work_flow_action_log_updated.sql      # Updated action log table structure
└── migrate_to_work_flow_type.sql         # Complete migration script
```

## Backward Compatibility

The implementation maintains full backward compatibility:

1. **Existing Columns Preserved**: All original columns (`work_flow_id`, `doc_type`) are kept for backward compatibility
2. **Existing Data Intact**: All existing workflow data remains unchanged
3. **Existing Functionality**: Current workflows continue to work without modification

## Usage Examples

### Creating a New Workflow Template

```sql
-- For a new PR (Purchase Request) workflow
INSERT INTO `work_flow_template` (
    `work_flow_id`, 
    `work_flow_type_id`, 
    `department`, 
    `company`, 
    `sequence`, 
    `actor_id`, 
    `action`, 
    `is_parellel`, 
    `global`
) VALUES (
    'PR', 
    4,  -- PR workflow type ID
    'TLCI-IT', 
    'TLCI', 
    2, 
    'RAYMOND', 
    'Cost_Center_Head', 
    0, 
    0
);
```

### Querying Workflows by Type

```sql
-- Get all templates for Purchase Request workflow
SELECT wftm.*, wft.type_name 
FROM `work_flow_template` wftm
JOIN `work_flow_type` wft ON wftm.work_flow_type_id = wft.id
WHERE wft.type_code = 'PR';

-- Get all active processes for a specific workflow type
SELECT wfp.*, wft.type_name 
FROM `work_flow_process` wfp
JOIN `work_flow_type` wft ON wfp.work_flow_type_id = wft.id
WHERE wft.type_code = 'PAW' 
AND wfp.status = 'Waiting for Approval';
```

## Benefits

1. **Better Organization**: Workflow types are now centrally managed
2. **Easier Maintenance**: Adding new workflow types is straightforward
3. **Improved Queries**: Better performance with proper indexing
4. **Data Integrity**: Foreign key constraints ensure data consistency
5. **Scalability**: Easy to add new workflow types without schema changes

## Adding New Workflow Types

To add a new workflow type:

1. Insert into `work_flow_type` table:
```sql
INSERT INTO `work_flow_type` (`type_code`, `type_name`, `description`) 
VALUES ('NEW_TYPE', 'New Workflow Type', 'Description of new workflow');
```

2. Create templates for the new type:
```sql
INSERT INTO `work_flow_template` (`work_flow_id`, `work_flow_type_id`, ...) 
VALUES ('NEW_TYPE', LAST_INSERT_ID(), ...);
```

## Troubleshooting

### Common Issues

1. **Foreign Key Constraint Errors**: Ensure all referenced workflow types exist
2. **Duplicate Type Codes**: Use `INSERT IGNORE` or check for existing types
3. **Migration Failures**: Check database permissions and table locks

### Verification Queries

```sql
-- Check if migration was successful
SELECT COUNT(*) as total_types FROM `work_flow_type`;
SELECT COUNT(*) as templates_with_type_id FROM `work_flow_template` WHERE `work_flow_type_id` IS NOT NULL;
SELECT COUNT(*) as processes_with_type_id FROM `work_flow_process` WHERE `work_flow_type_id` IS NOT NULL;
```

## Support

For technical support or questions about this implementation, please refer to the development team or check the existing workflow documentation.

---

**Version**: 1.0  
**Last Updated**: December 2024  
**Compatibility**: MySQL 5.7+, MariaDB 10.2+
