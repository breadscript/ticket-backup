# Disbursement System Test Scenarios
## Financial Request Management System (RFP, ERL, ERGR)

### System Overview
The Disbursement System manages three types of financial requests:
- **RFP** (Request for Payment) - For vendor payments, invoices, etc.
- **ERL** (Expense Reimbursement Letter) - For employee expense reimbursements
- **ERGR** (Expense Reimbursement General Request) - For general expense reimbursements

### Test Environment
- **URL**: http://172.16.4.44/dmt/dmt-rfp/login.php
- **Test Database**: Separate test database with sample data
- **Browser**: Chrome, Firefox, Edge (latest versions)

---

## 1. USER AUTHENTICATION & ACCESS CONTROL

### 1.1 Login Functionality
**Objective**: Verify user authentication and role-based access

**Test Cases**:
- [ ] **TC-001**: Valid user login with correct credentials
- [ ] **TC-002**: Invalid username/password combination
- [ ] **TC-003**: Empty username/password fields
- [ ] **TC-004**: Session timeout after inactivity
- [ ] **TC-005**: Logout functionality
- [ ] **TC-006**: Remember me functionality (if applicable)

**Test Data**:
```
Requestor: MRAMIREZ / TLCI
Cost Center Head: AFLORES / TLCI
Accounting: ACCOUNTONE / TLCI
Controller: CONTROLLER / TLCI
```

### 1.2 Role-Based Access
**Objective**: Verify users can only access appropriate modules

**Test Cases**:
- [ ] **TC-007**: Requestor can access disbursement form but not approver panel
- [ ] **TC-008**: Cost Center Head can access both request creation and approval
- [ ] **TC-009**: Accounting users can only access approval panel
- [ ] **TC-010**: Controller has full access to all modules

---

## 2. REQUEST CREATION (RFP, ERL, ERGR)

### 2.1 RFP (Request for Payment) Creation
**Objective**: Test complete RFP request creation workflow

**Test Cases**:
- [ ] **TC-011**: Create RFP with all required fields
- [ ] **TC-012**: Create RFP with optional fields
- [ ] **TC-013**: Validate required field validation
- [ ] **TC-014**: Test file upload functionality
- [ ] **TC-015**: Test amount calculations (items + breakdown)
- [ ] **TC-016**: Test currency selection
- [ ] **TC-017**: Test company/department selection
- [ ] **TC-018**: Test expenditure type (capex/opex)
- [ ] **TC-019**: Test budget validation
- [ ] **TC-020**: Test form submission with validation errors

**Sample Test Data - RFP**:
```
Request Type: RFP
Company: TLCI
Document Type: Invoice Payment
Document Number: RFP-2024-001
Document Date: 2024-01-15
Cost Center: FIN
Expenditure Type: opex
Currency: PHP
Payee: ABC Suppliers Inc.
Payment For: Office supplies and equipment
Amount: 50,000.00
```

### 2.2 ERL (Expense Reimbursement Letter) Creation
**Objective**: Test ERL request creation workflow

**Test Cases**:
- [ ] **TC-021**: Create ERL with travel expenses
- [ ] **TC-022**: Create ERL with meal allowances
- [ ] **TC-023**: Create ERL with miscellaneous expenses
- [ ] **TC-024**: Test cash advance field
- [ ] **TC-025**: Test due date validation
- [ ] **TC-026**: Test form of payment selection
- [ ] **TC-027**: Test reference number validation

**Sample Test Data - ERL**:
```
Request Type: ERL
Company: TLCI
Document Type: Travel Reimbursement
Document Number: ERL-2024-001
Document Date: 2024-01-15
Cost Center: SALES
Expenditure Type: opex
Currency: PHP
Payee: Patrick Gonzales
Payment For: Business travel expenses
Amount: 25,000.00
Cash Advance: Yes
Due Date: 2024-01-20
```

### 2.3 ERGR (Expense Reimbursement General Request) Creation
**Objective**: Test ERGR request creation workflow

**Test Cases**:
- [ ] **TC-028**: Create ERGR with general expenses
- [ ] **TC-029**: Create ERGR with project-related expenses
- [ ] **TC-030**: Test special instructions field
- [ ] **TC-031**: Test credit to payroll option
- [ ] **TC-032**: Test issue check option
- [ ] **TC-033**: Test from/to company fields

**Sample Test Data - ERGR**:
```
Request Type: ERGR
Company: TLCI
Document Type: General Reimbursement
Document Number: ERGR-2024-001
Document Date: 2024-01-15
Cost Center: ENGR
Expenditure Type: capex
Currency: PHP
Payee: Samuel Torres
Payment For: Project materials and tools
Amount: 75,000.00
Special Instructions: Urgent delivery required
```

### 2.4 Form Validation & Error Handling
**Objective**: Test comprehensive form validation

**Test Cases**:
- [ ] **TC-034**: Required field validation (all request types)
- [ ] **TC-035**: Amount format validation
- [ ] **TC-036**: Date format validation
- [ ] **TC-037**: File size and type validation
- [ ] **TC-038**: Duplicate document number validation
- [ ] **TC-039**: Budget limit validation
- [ ] **TC-040**: Character limit validation
- [ ] **TC-041**: SQL injection prevention
- [ ] **TC-042**: XSS prevention
- [ ] **TC-043**: CSRF protection

---

## 3. WORKFLOW APPROVAL PROCESS

### 3.1 Workflow Template Management
**Objective**: Test workflow template creation and management

**Test Cases**:
- [ ] **TC-044**: Create workflow template for RFP
- [ ] **TC-045**: Create workflow template for ERL
- [ ] **TC-046**: Create workflow template for ERGR
- [ ] **TC-047**: Test parallel approval configuration
- [ ] **TC-048**: Test amount-based workflow routing
- [ ] **TC-049**: Test department-specific workflows
- [ ] **TC-050**: Test global workflow templates

### 3.2 Approval Process - RFP
**Objective**: Test complete RFP approval workflow

**Test Cases**:
- [ ] **TC-051**: Submit RFP for approval
- [ ] **TC-052**: Cost Center Head receives notification
- [ ] **TC-053**: Cost Center Head approves RFP
- [ ] **TC-054**: Cost Center Head returns RFP for revision
- [ ] **TC-055**: Cost Center Head declines RFP
- [ ] **TC-056**: Accounting receives approved RFP
- [ ] **TC-057**: Accounting approves RFP
- [ ] **TC-058**: Controller receives for final approval
- [ ] **TC-059**: Controller approves RFP
- [ ] **TC-060**: RFP marked as completed

### 3.3 Approval Process - ERL
**Objective**: Test complete ERL approval workflow

**Test Cases**:
- [ ] **TC-061**: Submit ERL for approval
- [ ] **TC-062**: Cost Center Head reviews ERL
- [ ] **TC-063**: Cost Center Head approves ERL
- [ ] **TC-064**: Accounting processes ERL
- [ ] **TC-065**: Controller final approval for ERL
- [ ] **TC-066**: ERL completion and payment processing

### 3.4 Approval Process - ERGR
**Objective**: Test complete ERGR approval workflow

**Test Cases**:
- [ ] **TC-067**: Submit ERGR for approval
- [ ] **TC-068**: Cost Center Head reviews ERGR
- [ ] **TC-069**: Cost Center Head approves ERGR
- [ ] **TC-070**: Accounting processes ERGR
- [ ] **TC-071**: Controller final approval for ERGR
- [ ] **TC-072**: ERGR completion

### 3.5 Approval Actions & Comments
**Objective**: Test approval actions and communication

**Test Cases**:
- [ ] **TC-073**: Add approval comments
- [ ] **TC-074**: Add rejection comments
- [ ] **TC-075**: Add return for revision comments
- [ ] **TC-076**: Test comment character limits
- [ ] **TC-077**: Test comment history tracking
- [ ] **TC-078**: Test email notifications
- [ ] **TC-079**: Test approval timestamps
- [ ] **TC-080**: Test approval audit trail

---

## 4. REQUEST MANAGEMENT & TRACKING

### 4.1 Request Dashboard
**Objective**: Test request dashboard functionality

**Test Cases**:
- [ ] **TC-081**: View pending requests
- [ ] **TC-082**: View approved requests
- [ ] **TC-083**: View declined requests
- [ ] **TC-084**: View returned requests
- [ ] **TC-085**: Filter by request type
- [ ] **TC-086**: Filter by date range
- [ ] **TC-087**: Filter by status
- [ ] **TC-088**: Search by document number
- [ ] **TC-089**: Search by payee
- [ ] **TC-090**: Export request data

### 4.2 Request Details & History
**Objective**: Test request detail viewing and history

**Test Cases**:
- [ ] **TC-091**: View complete request details
- [ ] **TC-092**: View approval history
- [ ] **TC-093**: View attached documents
- [ ] **TC-094**: View workflow progression
- [ ] **TC-095**: View audit trail
- [ ] **TC-096**: Print request details
- [ ] **TC-097**: Download attachments
- [ ] **TC-098**: View comments history

### 4.3 Request Editing & Updates
**Objective**: Test request modification capabilities

**Test Cases**:
- [ ] **TC-099**: Edit pending request
- [ ] **TC-100**: Edit returned request
- [ ] **TC-101**: Cannot edit approved request
- [ ] **TC-102**: Cannot edit declined request
- [ ] **TC-103**: Update request details
- [ ] **TC-104**: Add new attachments
- [ ] **TC-105**: Remove attachments
- [ ] **TC-106**: Update item details
- [ ] **TC-107**: Update breakdown details
- [ ] **TC-108**: Resubmit updated request

---

## 5. NOTIFICATION & COMMUNICATION

### 5.1 Email Notifications
**Objective**: Test email notification system

**Test Cases**:
- [ ] **TC-109**: Request submitted notification
- [ ] **TC-110**: Request approved notification
- [ ] **TC-111**: Request declined notification
- [ ] **TC-112**: Request returned notification
- [ ] **TC-113**: Request completed notification
- [ ] **TC-114**: Email content validation
- [ ] **TC-115**: Email recipient validation
- [ ] **TC-116**: Email template formatting
- [ ] **TC-117**: Email delivery confirmation
- [ ] **TC-118**: Email queue processing

### 5.2 In-System Notifications
**Objective**: Test in-system notification features

**Test Cases**:
- [ ] **TC-119**: Dashboard notification count
- [ ] **TC-120**: Notification badge updates
- [ ] **TC-121**: Mark notification as read
- [ ] **TC-122**: Clear all notifications
- [ ] **TC-123**: Notification history
- [ ] **TC-124**: Notification preferences
- [ ] **TC-125**: Real-time notification updates

---

## 6. REPORTING & ANALYTICS

### 6.1 Request Reports
**Objective**: Test reporting functionality

**Test Cases**:
- [ ] **TC-126**: Generate request summary report
- [ ] **TC-127**: Generate approval time report
- [ ] **TC-128**: Generate cost center report
- [ ] **TC-129**: Generate user activity report
- [ ] **TC-130**: Export reports to Excel
- [ ] **TC-131**: Export reports to PDF
- [ ] **TC-132**: Filter reports by date range
- [ ] **TC-133**: Filter reports by request type
- [ ] **TC-134**: Filter reports by status
- [ ] **TC-135**: Filter reports by cost center

### 6.2 Dashboard Analytics
**Objective**: Test dashboard analytics and metrics

**Test Cases**:
- [ ] **TC-136**: View request counts by type
- [ ] **TC-137**: View approval time metrics
- [ ] **TC-138**: View cost center distribution
- [ ] **TC-139**: View status distribution
- [ ] **TC-140**: View monthly trends
- [ ] **TC-141**: View user performance metrics
- [ ] **TC-142**: View budget utilization
- [ ] **TC-143**: View pending approval queue
- [ ] **TC-144**: View overdue requests
- [ ] **TC-145**: View system performance metrics

---

## 7. SYSTEM ADMINISTRATION

### 7.1 User Management
**Objective**: Test user administration features

**Test Cases**:
- [ ] **TC-146**: Create new user account
- [ ] **TC-147**: Update user information
- [ ] **TC-148**: Deactivate user account
- [ ] **TC-149**: Reset user password
- [ ] **TC-150**: Assign user roles
- [ ] **TC-151**: Manage user permissions
- [ ] **TC-152**: View user activity logs
- [ ] **TC-153**: Bulk user operations
- [ ] **TC-154**: User import/export
- [ ] **TC-155**: User audit trail

### 7.2 System Configuration
**Objective**: Test system configuration management

**Test Cases**:
- [ ] **TC-156**: Configure email settings
- [ ] **TC-157**: Configure workflow templates
- [ ] **TC-158**: Configure approval limits
- [ ] **TC-159**: Configure notification settings
- [ ] **TC-160**: Configure file upload limits
- [ ] **TC-161**: Configure session timeout
- [ ] **TC-162**: Configure backup settings
- [ ] **TC-163**: Configure security settings
- [ ] **TC-164**: Configure audit logging
- [ ] **TC-165**: Configure system maintenance

---

## 8. PERFORMANCE & SECURITY

### 8.1 Performance Testing
**Objective**: Test system performance under load

**Test Cases**:
- [ ] **TC-166**: Load testing with multiple users
- [ ] **TC-167**: Stress testing with high volume
- [ ] **TC-168**: Response time testing
- [ ] **TC-169**: Database performance testing
- [ ] **TC-170**: File upload performance
- [ ] **TC-171**: Report generation performance
- [ ] **TC-172**: Email queue performance
- [ ] **TC-173**: Memory usage testing
- [ ] **TC-174**: CPU usage testing
- [ ] **TC-175**: Network bandwidth testing

### 8.2 Security Testing
**Objective**: Test system security measures

**Test Cases**:
- [ ] **TC-176**: SQL injection prevention
- [ ] **TC-177**: XSS prevention
- [ ] **TC-178**: CSRF protection
- [ ] **TC-179**: File upload security
- [ ] **TC-180**: Session security
- [ ] **TC-181**: Authentication bypass testing
- [ ] **TC-182**: Authorization testing
- [ ] **TC-183**: Data encryption testing
- [ ] **TC-184**: Audit trail integrity
- [ ] **TC-185**: Input validation testing

---

## 9. INTEGRATION & COMPATIBILITY

### 9.1 Browser Compatibility
**Objective**: Test system compatibility across browsers

**Test Cases**:
- [ ] **TC-186**: Chrome compatibility
- [ ] **TC-187**: Firefox compatibility
- [ ] **TC-188**: Edge compatibility
- [ ] **TC-189**: Safari compatibility
- [ ] **TC-190**: Mobile browser compatibility
- [ ] **TC-191**: Responsive design testing
- [ ] **TC-192**: JavaScript functionality
- [ ] **TC-193**: CSS rendering
- [ ] **TC-194**: Form submission
- [ ] **TC-195**: File upload functionality

### 9.2 Data Integration
**Objective**: Test data integration capabilities

**Test Cases**:
- [ ] **TC-196**: Database connectivity
- [ ] **TC-197**: Data import functionality
- [ ] **TC-198**: Data export functionality
- [ ] **TC-199**: API integration testing
- [ ] **TC-200**: Third-party system integration

---

## 10. DISASTER RECOVERY & BACKUP

### 10.1 Backup & Recovery
**Objective**: Test backup and recovery procedures

**Test Cases**:
- [ ] **TC-201**: Database backup functionality
- [ ] **TC-202**: File backup functionality
- [ ] **TC-203**: Backup restoration testing
- [ ] **TC-204**: Data integrity verification
- [ ] **TC-205**: Backup scheduling
- [ ] **TC-206**: Backup retention policies
- [ ] **TC-207**: Disaster recovery procedures
- [ ] **TC-208**: System restore procedures
- [ ] **TC-209**: Data migration testing
- [ ] **TC-210**: Backup monitoring

---

## TEST EXECUTION TRACKING

### Test Execution Status
- [ ] **Planned**: Test cases identified and documented
- [ ] **In Progress**: Test execution started
- [ ] **Completed**: Test execution finished
- [ ] **Passed**: Test case passed successfully
- [ ] **Failed**: Test case failed, bug identified
- [ ] **Blocked**: Test case blocked by other issues
- [ ] **Skipped**: Test case skipped due to dependencies

### Bug Tracking
- [ ] **Critical**: System crash, data loss, security breach
- [ ] **High**: Major functionality broken
- [ ] **Medium**: Minor functionality issues
- [ ] **Low**: UI/UX improvements, minor bugs

### Test Environment Setup
- [ ] **Database**: Test database configured
- [ ] **Users**: Test user accounts created
- [ ] **Data**: Sample data loaded
- [ ] **Workflows**: Test workflows configured
- [ ] **Permissions**: User permissions set
- [ ] **Email**: Test email configuration
- [ ] **Backup**: Backup procedures tested

---

## NOTES & OBSERVATIONS

### Test Execution Notes
- Document any deviations from expected behavior
- Note any workarounds or special procedures
- Record environment-specific issues
- Document performance observations
- Note any security concerns

### Recommendations
- Suggest improvements based on test results
- Identify potential enhancements
- Recommend process improvements
- Suggest performance optimizations
- Recommend security enhancements

---

**Document Version**: 1.0  
**Last Updated**: January 2024  
**Prepared By**: QA Team  
**Reviewed By**: Development Team  
**Approved By**: Project Manager
