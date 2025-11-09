# Promotional Activity Workplan (PAW) System

## Overview
The PAW system is a comprehensive form-based application for planning and approving promotional activities at Metro Pacific Agro Ventures. It provides a structured workflow for creating, reviewing, and approving promotional campaigns.

## Features
- **Complete Form Structure**: Matches the original PAW form layout exactly
- **Dynamic Calculations**: Automatic computation of costs, ratios, and totals
- **File Uploads**: Support for multiple document types
- **Approval Workflow**: Multi-level approval system with date tracking
- **Responsive Design**: Works on desktop and mobile devices
- **Database Storage**: Secure storage of all PAW data

## Installation

### 1. Database Setup
1. Import the database schema from `paw_database.sql`
2. Ensure your MySQL server is running
3. Update database connection details in `../conn/db.php` if needed

### 2. File Structure
```
paw/
├── paw_form.php          # Main form interface
├── save_paw.php          # Form submission handler
├── paw_database.sql      # Database schema
├── blocks/
│   ├── inc.resource.php  # Resource includes
│   ├── navigation.php    # Navigation menu
│   ├── header.php        # Header section
│   └── footer.php        # Footer section
└── README.md             # This file
```

### 3. Dependencies
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- jQuery and Bootstrap (included via CDN)

## Usage

### 1. Access the Form
Navigate to `paw_form.php` in your web browser.

### 2. Fill Out the Form
The form is divided into several sections:

#### Header Information
- PAW Number (auto-generated)
- Internal Order Number
- Date
- Company selection

#### Brand Specifics
- Brand/SKUs information
- Sales group details
- Sharing arrangements

#### Type of Activity
- Checkboxes for various promotion types
- Specific scheme description
- Activity title and mechanics

#### Promo Objectives
- Multiple objective checkboxes
- Volume evaluation with automatic calculations
- Target and current value inputs

#### Duration & Target Outlets
- Promotional period dates
- Average selling price
- Target outlet types and numbers
- Area coverage details

#### Cost Summary & Financial Implications
- Dynamic cost item table
- Automatic total calculations
- Financial ratio computations
- Justification text

#### Billing & Charging Details
- Brand/sales group information
- Charge account numbers
- Total amount (auto-calculated)

#### Submission & Concurrence
- Submitter details
- Multi-level approval workflow
- Date tracking for each approval level

#### Attachments
- File upload support
- Specification requirements

### 3. Form Submission
1. Fill in all required fields
2. Review calculations and totals
3. Click "Submit" button
4. Form will be saved to database
5. PAW number will be generated and displayed

## Database Tables

### Main Tables
- `paw_main` - Core PAW information
- `paw_activities` - Activity type selections
- `paw_objectives` - Objective selections
- `paw_outlets` - Outlet type selections
- `paw_cost_items` - Cost breakdown items
- `paw_concurrence` - Approval workflow dates
- `paw_attachments` - File attachment records

### Sample Data
The system includes sample company, department, and category data for immediate use.

## Customization

### Adding New Fields
1. Add form inputs in `paw_form.php`
2. Update `save_paw.php` to process new fields
3. Modify database schema if needed

### Changing Calculations
Update the JavaScript functions in `paw_form.php`:
- `calculateCostSummaryTotal()` - Cost calculations
- Volume evaluation calculations
- Financial ratio computations

### Styling
The form uses Bootstrap classes and can be customized by modifying CSS in `inc.resource.php`.

## Security Features

- CSRF token protection
- Input validation and sanitization
- Prepared SQL statements
- File upload restrictions
- Session management

## Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check database credentials in `../conn/db.php`
   - Ensure MySQL service is running

2. **Form Not Submitting**
   - Check browser console for JavaScript errors
   - Verify all required fields are filled
   - Check file permissions on `save_paw.php`

3. **Calculations Not Working**
   - Ensure jQuery is loaded
   - Check for JavaScript errors in console
   - Verify input field names match JavaScript selectors

### Error Logs
Check your web server error logs for detailed error information.

## Support

For technical support or feature requests, please contact your system administrator or development team.

## Version History

- **v1.0** - Initial release with complete PAW form functionality
- Includes all form sections and database storage
- Dynamic calculations and responsive design
