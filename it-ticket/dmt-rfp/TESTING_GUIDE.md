# Disbursement System Testing Guide
## Comprehensive Testing Manual for RFP, ERL, and ERGR

### Table of Contents
1. [Getting Started](#getting-started)
2. [Test Environment Setup](#test-environment-setup)
3. [User Roles and Permissions](#user-roles-and-permissions)
4. [Test Data Management](#test-data-management)
5. [Testing Workflows](#testing-workflows)
6. [Common Test Scenarios](#common-test-scenarios)
7. [Bug Reporting](#bug-reporting)
8. [Test Execution Checklist](#test-execution-checklist)
9. [Troubleshooting](#troubleshooting)
10. [Best Practices](#best-practices)

---

## Getting Started

### Prerequisites
- Access to test environment: http://172.16.4.44/dmt/dmt-rfp/login.php
- Test user accounts (see test-account.txt)
- Modern web browser (Chrome, Firefox, Edge)
- Test data files (sample invoices, receipts, etc.)
- Screen recording software (optional but recommended)

### Test Environment Access
1. **URL**: http://172.16.4.44/dmt/dmt-rfp/login.php
2. **Database**: Separate test database with sample data
3. **Email**: Test email configuration for notifications
4. **File Storage**: Test upload directory with proper permissions

---

## Test Environment Setup

### 1. Database Setup
```sql
-- Create test database
CREATE DATABASE dmt_rfp_test;
USE dmt_rfp_test;

-- Import schema files
SOURCE financial_requests.sql;
SOURCE workflow_schema.sql;
SOURCE category_schema.sql;
SOURCE company_schema.sql;
SOURCE department_schema.sql;

-- Load sample data
INSERT INTO company (company_code, company_name) VALUES ('TLCI', 'Test Company Inc.');
INSERT INTO department (company_code, department_code, department_name) VALUES 
('TLCI', 'FIN', 'Finance'),
('TLCI', 'ACC', 'Accounting'),
('TLCI', 'SALES', 'Sales'),
('TLCI', 'ENGR', 'Engineering');
```

### 2. User Account Setup
```sql
-- Create test users (if not using existing accounts)
INSERT INTO users (username, password, firstname, lastname, role) VALUES
('TESTUSER1', 'TLCI', 'Test', 'User1', 'requestor'),
('TESTUSER2', 'TLCI', 'Test', 'User2', 'cost_center_head'),
('TESTUSER3', 'TLCI', 'Test', 'User3', 'accounting'),
('TESTUSER4', 'TLCI', 'Test', 'User4', 'controller');
```

### 3. Workflow Template Setup
```sql
-- Create test workflow templates
INSERT INTO work_flow_template (work_flow_id, company, sequence, actor_id, action) VALUES
('RFP', 'TLCI', 1, 'Cost Center Head', 'Cost_Center_Head'),
('RFP', 'TLCI', 2, 'Accounting', 'Accounting'),
('RFP', 'TLCI', 3, 'Controller', 'Controller'),
('ERL', 'TLCI', 1, 'Cost Center Head', 'Cost_Center_Head'),
('ERL', 'TLCI', 2, 'Accounting', 'Accounting'),
('ERGR', 'TLCI', 1, 'Cost Center Head', 'Cost_Center_Head'),
('ERGR', 'TLCI', 2, 'Accounting', 'Accounting');
```

---

## User Roles and Permissions

### 1. Requestor Role
**Purpose**: Create and manage financial requests
**Permissions**:
- Create new requests (RFP, ERL, ERGR)
- Edit pending/returned requests
- View own request history
- Upload supporting documents
- Submit requests for approval

**Test Accounts**:
- Username: `MRAMIREZ`, Password: `TLCI`
- Username: `ACRUZ`, Password: `TLCI`
- Username: `JREYES`, Password: `TLCI`

### 2. Cost Center Head Role
**Purpose**: First-level approval and budget oversight
**Permissions**:
- Approve/decline/return requests
- View requests in their cost center
- Add approval comments
- Access both request creation and approval panels

**Test Accounts**:
- Username: `AFLORES`, Password: `TLCI` (Finance)
- Username: `CDOMINGUEZ`, Password: `TLCI` (Accounting)
- Username: `JCASTILLO`, Password: `TLCI` (Logistics)

### 3. Accounting Role
**Purpose**: Financial processing and compliance
**Permissions**:
- Process approved requests
- Verify financial details
- Generate payment instructions
- Access approval panel only

**Test Accounts**:
- Username: `ACCOUNTONE`, Password: `TLCI`
- Username: `ACCOUNTTWO`, Password: `TLCI`
- Username: `ACCOUNTTHREE`, Password: `TLCI`

### 4. Controller Role
**Purpose**: Final approval and oversight
**Permissions**:
- Final approval for high-value requests
- System-wide oversight
- Access to all modules and reports

**Test Account**:
- Username: `CONTROLLER`, Password: `TLCI`

---

## Test Data Management

### 1. Sample Request Data

#### RFP Test Data
```json
{
  "request_type": "RFP",
  "company": "TLCI",
  "doc_type": "Invoice Payment",
  "doc_number": "RFP-TEST-001",
  "doc_date": "2024-01-15",
  "cost_center": "FIN",
  "expenditure_type": "opex",
  "currency": "PHP",
  "payee": "ABC Suppliers Inc.",
  "payment_for": "Office supplies and equipment",
  "amount": 50000.00,
  "items": [
    {
      "category": "Office Supplies",
      "description": "Printer paper, pens, notebooks",
      "amount": 30000.00,
      "reference_number": "INV-001"
    },
    {
      "category": "Equipment",
      "description": "Computer accessories",
      "amount": 20000.00,
      "reference_number": "INV-002"
    }
  ]
}
```

#### ERL Test Data
```json
{
  "request_type": "ERL",
  "company": "TLCI",
  "doc_type": "Travel Reimbursement",
  "doc_number": "ERL-TEST-001",
  "doc_date": "2024-01-15",
  "cost_center": "SALES",
  "expenditure_type": "opex",
  "currency": "PHP",
  "payee": "Patrick Gonzales",
  "payment_for": "Business travel expenses",
  "amount": 25000.00,
  "cash_advance": true,
  "due_date": "2024-01-20",
  "form_of_payment": "wire_transfer"
}
```

#### ERGR Test Data
```json
{
  "request_type": "ERGR",
  "company": "TLCI",
  "doc_type": "General Reimbursement",
  "doc_number": "ERGR-TEST-001",
  "doc_date": "2024-01-15",
  "cost_center": "ENGR",
  "expenditure_type": "capex",
  "currency": "PHP",
  "payee": "Samuel Torres",
  "payment_for": "Project materials and tools",
  "amount": 75000.00,
  "special_instructions": "Urgent delivery required",
  "credit_to_payroll": false,
  "issue_check": true
}
```

### 2. Test Files
Prepare the following test files:
- Sample invoice (PDF, 1-2 MB)
- Receipt images (JPG, PNG, 500KB-1MB)
- Supporting documents (PDF, DOC, XLS)
- Large files for upload limit testing (10MB+)

### 3. Data Cleanup
```sql
-- Clean up test data after testing
DELETE FROM financial_request_breakdowns WHERE doc_number IN (
  SELECT id FROM financial_requests WHERE doc_number LIKE '%-TEST-%'
);
DELETE FROM financial_request_items WHERE doc_number IN (
  SELECT id FROM financial_requests WHERE doc_number LIKE '%-TEST-%'
);
DELETE FROM financial_requests WHERE doc_number LIKE '%-TEST-%';
DELETE FROM work_flow_process WHERE doc_number LIKE '%-TEST-%';
```

---

## Testing Workflows

### 1. Complete RFP Workflow Test

#### Step 1: Create RFP Request
1. Login as `MRAMIREZ` (Requestor)
2. Navigate to "Disbursement Form"
3. Select "RFP" as request type
4. Fill in all required fields:
   - Company: TLCI
   - Document Type: Invoice Payment
   - Document Number: RFP-TEST-001
   - Document Date: Today's date
   - Cost Center: FIN
   - Expenditure Type: opex
   - Currency: PHP
   - Payee: ABC Suppliers Inc.
   - Payment For: Office supplies and equipment
   - Amount: 50,000.00
5. Add item details:
   - Category: Office Supplies
   - Description: Printer paper, pens, notebooks
   - Amount: 30,000.00
   - Reference Number: INV-001
6. Upload supporting document
7. Submit request

#### Step 2: Cost Center Head Approval
1. Login as `AFLORES` (Finance Cost Center Head)
2. Navigate to "Disbursement Approver"
3. Find the RFP-TEST-001 request
4. Review request details
5. Add approval comment: "Approved - within budget"
6. Click "Approve"

#### Step 3: Accounting Processing
1. Login as `ACCOUNTONE` (Accounting)
2. Navigate to "Disbursement Approver"
3. Find the RFP-TEST-001 request
4. Review financial details
5. Add processing comment: "Payment processed"
6. Click "Approve"

#### Step 4: Controller Final Approval
1. Login as `CONTROLLER`
2. Navigate to "Disbursement Approver"
3. Find the RFP-TEST-001 request
4. Review complete request
5. Add final approval comment: "Final approval granted"
6. Click "Approve"

#### Step 5: Verification
1. Login as `MRAMIREZ` (original requestor)
2. Check request status - should be "Completed"
3. View approval history and comments
4. Verify email notifications received

### 2. ERL Workflow Test

#### Step 1: Create ERL Request
1. Login as `PGONZALES` (Sales - can create requests)
2. Navigate to "Disbursement Form"
3. Select "ERL" as request type
4. Fill in travel expense details:
   - Document Type: Travel Reimbursement
   - Document Number: ERL-TEST-001
   - Payee: Patrick Gonzales
   - Payment For: Business travel expenses
   - Amount: 25,000.00
   - Cash Advance: Yes
   - Due Date: 5 days from today
   - Form of Payment: wire_transfer
5. Upload travel receipts
6. Submit request

#### Step 2: Approval Process
1. Cost Center Head (`PGONZALES`) approves
2. Accounting (`ACCOUNTONE`) processes
3. Verify completion

### 3. ERGR Workflow Test

#### Step 1: Create ERGR Request
1. Login as `STORRES` (Engineering)
2. Navigate to "Disbursement Form"
3. Select "ERGR" as request type
4. Fill in project expense details:
   - Document Type: General Reimbursement
   - Document Number: ERGR-TEST-001
   - Payee: Samuel Torres
   - Payment For: Project materials and tools
   - Amount: 75,000.00
   - Special Instructions: Urgent delivery required
   - Credit to Payroll: No
   - Issue Check: Yes
5. Upload project documentation
6. Submit request

#### Step 2: Approval Process
1. Cost Center Head (`STORRES`) approves
2. Accounting (`ACCOUNTONE`) processes
3. Verify completion

---

## Common Test Scenarios

### 1. Form Validation Testing

#### Required Field Validation
1. Try to submit form with empty required fields
2. Verify error messages appear
3. Verify form doesn't submit
4. Test each required field individually

#### Amount Validation
1. Enter negative amounts
2. Enter zero amounts
3. Enter very large amounts (999,999,999.99)
4. Enter decimal amounts
5. Enter text in amount fields
6. Verify appropriate error messages

#### Date Validation
1. Enter future dates
2. Enter invalid date formats
3. Enter dates before company establishment
4. Verify date picker functionality
5. Test date range validations

#### File Upload Validation
1. Upload files larger than 10MB
2. Upload unsupported file types
3. Upload files with special characters in names
4. Upload multiple files
5. Try to upload without selecting file
6. Verify file size and type restrictions

### 2. Workflow Testing

#### Parallel Approval Testing
1. Configure workflow with parallel approvals
2. Submit request requiring parallel approval
3. Verify multiple approvers can approve simultaneously
4. Test that request proceeds when all parallel approvals are complete

#### Amount-Based Routing
1. Create requests with different amounts
2. Verify routing to appropriate approvers based on amount
3. Test boundary conditions (exact amounts)
4. Verify amount range configurations

#### Return for Revision Testing
1. Submit request for approval
2. Have approver return request for revision
3. Verify requestor can edit returned request
4. Verify approver comments are visible
5. Resubmit revised request
6. Verify approval process continues

### 3. Security Testing

#### Authentication Testing
1. Try to access pages without login
2. Test session timeout
3. Test concurrent login attempts
4. Test password complexity requirements
5. Test account lockout after failed attempts

#### Authorization Testing
1. Try to access other users' requests
2. Try to approve requests without permission
3. Test role-based access controls
4. Verify users can only see appropriate data

#### Input Validation Testing
1. Test SQL injection attempts
2. Test XSS attempts
3. Test CSRF attacks
4. Test file upload security
5. Test input sanitization

### 4. Performance Testing

#### Load Testing
1. Create multiple requests simultaneously
2. Test with multiple users logged in
3. Test database performance under load
4. Test file upload performance
5. Test report generation performance

#### Stress Testing
1. Submit requests rapidly
2. Upload large files
3. Generate multiple reports
4. Test system limits
5. Monitor resource usage

---

## Bug Reporting

### Bug Report Template
```
Bug ID: BUG-001
Title: [Brief description of the issue]
Severity: [Critical/High/Medium/Low]
Priority: [High/Medium/Low]
Environment: [Test/Staging/Production]
Browser: [Chrome/Firefox/Edge/Safari]
Version: [Browser version]

Description:
[Detailed description of the issue]

Steps to Reproduce:
1. [Step 1]
2. [Step 2]
3. [Step 3]
...

Expected Result:
[What should happen]

Actual Result:
[What actually happened]

Screenshots:
[Attach relevant screenshots]

Additional Information:
[Any other relevant details]
```

### Bug Severity Levels
- **Critical**: System crash, data loss, security breach
- **High**: Major functionality broken, cannot complete core tasks
- **Medium**: Minor functionality issues, workarounds available
- **Low**: UI/UX improvements, minor bugs, cosmetic issues

### Bug Tracking Process
1. Document bug using template
2. Assign severity and priority
3. Report to development team
4. Track bug status
5. Verify bug fix
6. Close bug report

---

## Test Execution Checklist

### Pre-Testing Checklist
- [ ] Test environment is accessible
- [ ] Test database is properly configured
- [ ] Test user accounts are created
- [ ] Sample data is loaded
- [ ] Workflow templates are configured
- [ ] Test files are prepared
- [ ] Email configuration is working
- [ ] Browser is up to date
- [ ] Screen recording software is ready
- [ ] Bug reporting system is accessible

### Daily Testing Checklist
- [ ] Review test cases for the day
- [ ] Set up test data if needed
- [ ] Execute planned test cases
- [ ] Document any issues found
- [ ] Report bugs using template
- [ ] Update test execution status
- [ ] Clean up test data
- [ ] Prepare test report

### Post-Testing Checklist
- [ ] Complete all planned test cases
- [ ] Document all findings
- [ ] Report all bugs found
- [ ] Update test documentation
- [ ] Clean up test environment
- [ ] Prepare test summary report
- [ ] Archive test artifacts
- [ ] Schedule retest for fixed bugs

---

## Troubleshooting

### Common Issues and Solutions

#### Login Issues
**Problem**: Cannot login with valid credentials
**Solution**: 
1. Check if account is active
2. Verify username/password case sensitivity
3. Clear browser cache and cookies
4. Try different browser
5. Check database connectivity

#### Form Submission Issues
**Problem**: Form doesn't submit or shows errors
**Solution**:
1. Check required fields are filled
2. Verify file upload size limits
3. Check browser console for JavaScript errors
4. Verify server is responding
5. Check database connection

#### Workflow Issues
**Problem**: Requests not routing correctly
**Solution**:
1. Verify workflow templates are configured
2. Check user roles and permissions
3. Verify cost center assignments
4. Check amount-based routing rules
5. Review workflow process table

#### Email Notification Issues
**Problem**: Email notifications not received
**Solution**:
1. Check email configuration
2. Verify email queue processing
3. Check spam/junk folders
4. Verify email addresses are correct
5. Test email server connectivity

#### Performance Issues
**Problem**: System is slow or unresponsive
**Solution**:
1. Check server resources
2. Monitor database performance
3. Check for long-running queries
4. Verify file upload limits
5. Check network connectivity

### Debug Information
When reporting issues, include:
- Browser console errors
- Network tab information
- Database error logs
- Server error logs
- Screenshots of issues
- Steps to reproduce
- Environment details

---

## Best Practices

### Testing Best Practices
1. **Plan Ahead**: Review test cases before execution
2. **Document Everything**: Record all findings and observations
3. **Use Test Data**: Always use test data, never production data
4. **Clean Up**: Remove test data after testing
5. **Verify Fixes**: Always retest after bug fixes
6. **Think Like User**: Test from user perspective
7. **Test Edge Cases**: Don't just test happy path
8. **Use Multiple Browsers**: Test across different browsers
9. **Record Issues**: Use screen recording for complex issues
10. **Communicate**: Keep team informed of progress

### Data Management Best Practices
1. **Use Unique Identifiers**: Use test prefixes for all test data
2. **Backup Before Testing**: Backup database before major testing
3. **Clean Up Regularly**: Remove test data after each test session
4. **Use Realistic Data**: Use realistic but fake data for testing
5. **Document Test Data**: Keep track of test data used
6. **Isolate Test Data**: Use separate test environment
7. **Version Control**: Track changes to test data
8. **Backup Test Data**: Keep backup of test data sets

### Communication Best Practices
1. **Daily Updates**: Provide daily testing progress updates
2. **Clear Bug Reports**: Write clear, detailed bug reports
3. **Include Context**: Provide context for all issues
4. **Use Templates**: Use consistent templates for reporting
5. **Follow Up**: Follow up on reported issues
6. **Document Decisions**: Document testing decisions and rationale
7. **Share Knowledge**: Share testing insights with team
8. **Ask Questions**: Don't hesitate to ask for clarification

### Security Testing Best Practices
1. **Test Authentication**: Always test authentication mechanisms
2. **Test Authorization**: Verify role-based access controls
3. **Test Input Validation**: Test all input fields for vulnerabilities
4. **Test File Uploads**: Verify file upload security
5. **Test Session Management**: Test session handling
6. **Test Data Protection**: Verify sensitive data protection
7. **Test Audit Trails**: Verify audit logging
8. **Test Error Handling**: Test error message security

---

## Conclusion

This testing guide provides comprehensive coverage for testing the Disbursement System. Follow the structured approach outlined in this guide to ensure thorough testing of all system components.

### Key Success Factors
1. **Preparation**: Proper test environment setup
2. **Execution**: Systematic test case execution
3. **Documentation**: Thorough documentation of findings
4. **Communication**: Clear communication with development team
5. **Follow-up**: Proper follow-up on issues and fixes

### Continuous Improvement
- Update test cases based on findings
- Refine test data based on requirements
- Improve test processes based on experience
- Share lessons learned with team
- Update guide based on system changes

---

**Document Version**: 1.0  
**Last Updated**: January 2024  
**Prepared By**: QA Team  
**Reviewed By**: Development Team  
**Approved By**: Project Manager
