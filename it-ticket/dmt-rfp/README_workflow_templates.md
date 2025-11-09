# Workflow Template Management System

This system provides a comprehensive workflow template management solution for creating, editing, and managing approval workflows for various document types (RFP, ERL, ERGR, PO, PR).

## Features

- **Template Management**: Create, edit, view, and delete workflow templates
- **Flexible Routing**: Support for department-specific and global templates
- **Amount-Based Routing**: Conditional approval steps based on document amounts
- **Parallel Approvals**: Support for simultaneous approvals at specific steps
- **Process Initialization**: Create workflow processes from templates
- **Audit Logging**: Complete audit trail of all template changes

## Database Schema

The system uses three main tables:

### 1. `work_flow_template`
Stores workflow template definitions with the following fields:
- `id`: Unique identifier
- `work_flow_id`: Document type (RFP, ERL, ERGR, etc.)
- `department`: Department-specific template (optional)
- `company`: Company identifier
- `sequence`: Approval order (1, 2, 3...)
- `actor_id`: Who is responsible for this step
- `action`: Role/action key
- `is_parellel`: Allow parallel approvals
- `global`: Apply globally across departments
- `amount_from`/`amount_to`: Amount range restrictions
- `Note`: Additional instructions

### 2. `work_flow_process`
Stores active workflow instances for specific documents.

### 3. `work_flow_action_log`
Audit log of all template and process changes.

## Files Overview

### Core Management Files
- `workflow_template_main.php` - Main listing page with search and pagination
- `workflow_template_form.php` - Create/edit template form
- `workflow_template_view.php` - View template details
- `workflow_template_save.php` - Save/update template handler
- `workflow_template_delete.php` - Delete template handler

### Process Initialization Files
- `workflow_template_init.php` - Initialize workflow processes from templates
- `workflow_process_create.php` - Create workflow processes handler

## Setup Instructions

### 1. Database Setup
Run the SQL schema file `workflow_schema.sql` to create the required tables:

```sql
-- This will create the tables and insert sample data
source workflow_schema.sql;
```

### 2. File Permissions
Ensure the web server has read/write access to the upload directories.

### 3. Configuration
Update the database connection settings in `blocks/inc.resource.php` if needed.

## Usage Guide

### Creating a Workflow Template

1. Navigate to **Workflow Template Management**
2. Click **Create Template**
3. Fill in the required fields:
   - **Workflow Type**: Select document type (RFP, ERL, etc.)
   - **Company**: Select company
   - **Department**: Optional - leave empty for global templates
   - **Sequence**: Approval order number
   - **Actor ID**: Who handles this step
   - **Action**: Role/action identifier
   - **Parallel Approval**: Check if multiple approvals can happen simultaneously
   - **Global Template**: Check if applies to all departments
   - **Amount Range**: Optional restrictions based on document amount
   - **Notes**: Additional instructions

### Managing Templates

- **View**: Click the "View" button to see template details
- **Edit**: Click the "Edit" button to modify existing templates
- **Delete**: Click the "Delete" button to remove templates (only if not in use)

### Initializing Workflow Processes

1. Navigate to **Initialize Workflow Process**
2. Select workflow type, company, and department
3. Enter document number and optional amount
4. Click **Initialize Workflow**
5. The system will create workflow processes based on applicable templates

## Template Examples

### RFP Approval Flow
```
Sequence 1: Requestor (Done)
Sequence 2: Cost Center Head (Department-specific)
Sequence 3: Accounting Approver 1 (Parallel - one approval needed)
Sequence 4: Accounting Approver 2
Sequence 5: Controller
Sequence 6: Cashier
```

### Amount-Based Routing
```
Amount < 10,000: Skip Controller approval
Amount >= 10,000: Include Controller approval
Amount >= 50,000: Include CEO approval
```

## Security Features

- **CSRF Protection**: All forms include CSRF tokens
- **Session Validation**: User authentication required
- **Input Validation**: Comprehensive input sanitization
- **SQL Injection Prevention**: Prepared statements used throughout
- **Audit Logging**: Complete trail of all changes

## Customization

### Adding New Workflow Types
1. Add new type to `$workflowTypes` array in form files
2. Update database schema if needed
3. Create corresponding templates

### Adding New Actions
1. Add new action to `$actions` array in form files
2. Update any role-based logic
3. Consider impact on existing workflows

### Modifying Approval Logic
1. Edit `workflow_process_create.php` for process creation logic
2. Update status determination rules
3. Modify parallel approval handling

## Troubleshooting

### Common Issues

1. **Template Not Found**: Check if templates exist for the selected criteria
2. **Duplicate Sequence**: Ensure unique sequences within same workflow/company/department
3. **Database Errors**: Verify database connection and table structure
4. **Permission Issues**: Check file and directory permissions

### Debug Mode
Enable error reporting in PHP for development:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## API Integration

The system can be extended with API endpoints for:
- Template management via REST API
- Workflow status updates
- Approval action processing
- Integration with external systems

## Performance Considerations

- **Pagination**: Large template lists are paginated
- **Indexing**: Database indexes on frequently queried fields
- **Caching**: Consider implementing template caching for high-traffic systems
- **Batch Operations**: Support for bulk template operations

## Future Enhancements

- **Workflow Designer**: Visual workflow builder interface
- **Conditional Logic**: Advanced routing rules
- **Notification System**: Email/SMS alerts for approvals
- **Mobile Support**: Responsive design for mobile devices
- **API Integration**: RESTful API for external systems
- **Reporting**: Advanced analytics and reporting tools

## Support

For technical support or feature requests, please contact the development team.

---

**Version**: 1.0  
**Last Updated**: December 2024  
**Compatibility**: PHP 7.4+, MySQL 5.7+
