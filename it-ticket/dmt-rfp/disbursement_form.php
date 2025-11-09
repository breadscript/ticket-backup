<?php 
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d h:i:s",time());	
include "blocks/inc.resource.php";
$moduleid = 10;
include "proxy.php";
require_once __DIR__ . '/attachment_display_helper.php';
// Check permission for Disbursement (module ID 10)
// $hasPermission = false;
// if (isset($_SESSION['userid']) && isset($mysqlconn) && $mysqlconn) {
//     $userId = (int)$_SESSION['userid'];
//     $moduleId = 10; // Disbursement module ID
    
//     $permissionQuery = "SELECT COUNT(*) as count FROM sys_authoritymoduletb sam 
//                        WHERE sam.moduleid = ? AND sam.userid = ? AND sam.statid = 1";
    
//     if ($stmt = mysqli_prepare($mysqlconn, $permissionQuery)) {
//         mysqli_stmt_bind_param($stmt, 'ii', $moduleId, $userId);
//         mysqli_stmt_execute($stmt);
//         $result = mysqli_stmt_get_result($stmt);
//         $row = mysqli_fetch_assoc($result);
//         $hasPermission = ($row && (int)$row['count'] > 0);
//         mysqli_stmt_close($stmt);
//     }
// }

// // Check if no permission and handle gracefully
// if (!$hasPermission) {
//     // Check if this is already a reload attempt to prevent infinite loop
//     if (!isset($_SESSION['no_permission_checked'])) {
//         $_SESSION['no_permission_checked'] = true;
//         echo '<script>location.reload();</script>';
//         exit;
//     } else {
//         // Clear the session flag and show access denied message
//         unset($_SESSION['no_permission_checked']);
//         echo '<div style="text-align:center; padding:50px; font-family:Arial;">
//                 <h2>Access Denied</h2>
//                 <p>You do not have permission to access this module.</p>
//                 <p><a href="javascript:history.back()">Go Back</a></p>
//               </div>';
//         exit;
//     }
// }
// Load companies and departments for dropdowns
$companies = [];
$departments = [];
$categories = [];
$withholdingTaxes = [];
if (isset($mysqlconn) && $mysqlconn) {
  if ($res = mysqli_query($mysqlconn, "SELECT company_code, company_name, company_id FROM company ORDER BY company_code")) {
    while ($r = mysqli_fetch_assoc($res)) { $companies[] = $r; }
  }
  if ($res2 = mysqli_query($mysqlconn, "SELECT company_code, department_code, department_name, department_id FROM department ORDER BY company_code, department_code")) {
    while ($r2 = mysqli_fetch_assoc($res2)) { $departments[] = $r2; }
  }
  if ($res3 = mysqli_query($mysqlconn, "SELECT category_code, category_name, sap_account_code, sap_account_description FROM categories WHERE is_active = 1 ORDER BY category_name")) {
    while ($r3 = mysqli_fetch_assoc($res3)) { $categories[] = $r3; }
  }
  if ($res4 = mysqli_query($mysqlconn, "SELECT tax_code, tax_name, tax_rate, tax_type, description FROM withholding_taxes WHERE is_active = 1 ORDER BY tax_code")) {
    while ($r4 = mysqli_fetch_assoc($res4)) { $withholdingTaxes[] = $r4; }
  }
}
?>


  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <!-- left navigation -->
        <?php  include "blocks/navigation.php";?>
        <!-- /left navigation -->

        <!-- top navigation -->
        <?php  include "blocks/header.php";?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
              <div class="title_left">
                <h3>Disbursement Form</h3>
              </div>

              
            </div>
            <div class="clearfix"></div>
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Header Details <small>input the header details of your financial request.</small></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                      <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                        <ul class="dropdown-menu" role="menu">
                          <li><a href="#">Settings 1</a>
                          </li>
                          <li><a href="#">Settings 2</a>
                          </li>
                        </ul>
                      </li>
                      <li><a class="close-link"><i class="fa fa-close"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    <br />
                    <!-- <div class="col-md-8 center-margin">
                        <form class="form-horizontal form-label-left">
                            <div class="form-group">
                            <label>Email address</label>
                            <input type="email" class="form-control" placeholder="Enter email">
                            </div>
                            <div class="form-group">
                            <label>Password</label>
                            <input type="password" class="form-control" placeholder="Password">
                            </div>

                        </form>
                        </div> -->
                    <!-- 
                    SECURITY FEATURES IMPLEMENTED:
                    - CSRF Protection with secure token generation
                    - Form timestamp validation (prevents replay attacks)
                    - Client-side validation with Parsley.js
                    - Server-side input sanitization and validation
                    - File upload security (MIME type, extension, content validation)
                    - Rate limiting (5 seconds between submissions)
                    - XSS protection with htmlspecialchars
                    - SQL injection prevention with prepared statements
                    - Input length and format validation
                    - Enum value validation for dropdowns
                    - Date format and range validation
                    - File size limits (200MB max)
                    - Malicious content detection in uploads
                    -->
                    <form id="demo-form2" class="form-horizontal form-label-left" method="post" enctype="multipart/form-data" autocomplete="off" action="save_financial_request.php" novalidate>
                      
                      <!-- CSRF Protection -->
                      <?php
                        if (!isset($_SESSION['csrf_token'])) {
                            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        }
                        ?>
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                      
                      <!-- Form Submission Timestamp -->
                      <input type="hidden" name="form_timestamp" value="<?php echo time(); ?>">
                      
                      <!-- Item File Counts (populated by JavaScript) -->
                      <input type="hidden" name="item_file_counts" id="item_file_counts" value="">

                      <!-- ======================================== -->
                      <!-- HEADER SECTION -->
                      <!-- ======================================== -->
                      <div class="x_panel">
                        <div class="x_title">
                          <h3><i class="fa fa-header"></i> Header</h3>
                          <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                          
                          <div class="form-group">
                            <div class="col-md-3 col-sm-3 col-xs-12">
                              <label>Request Type</label>
                              <select id="requestType" name="request_type" class="form-control" required 
                                      data-parsley-required="true" 
                                      data-parsley-required-message="Please select a request type"
                                      data-parsley-in="ERGR,ERL,RFP"
                                      data-parsley-in-message="Invalid request type selected">
                                <option value="">Choose..</option>
                                <option value="ERGR">Expense Report (General Reimbursement)</option>
                                <option value="ERL">Expense Report (Liquidation)</option>
                                <option value="RFP">Request For Payment</option>
                              </select>
                              <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="requestType" style=""></i>
                              
                            </div>
                            <div class="col-md-3 col-sm-3 col-xs-12">
                              <label>Company</label>
                              <select id="company" name="company" class="form-control" required
                                      data-parsley-required="true"
                                      data-parsley-required-message="Please select a company">
                                <option value="">Choose..</option>
                                <?php foreach ($companies as $co) { 
                                  $code = htmlspecialchars($co['company_code'] ?? '', ENT_QUOTES, 'UTF-8');
                                  $cid  = htmlspecialchars($co['company_id'] ?? '', ENT_QUOTES, 'UTF-8');
                                ?>
                                  <option value="<?php echo $code; ?>" data-company-id="<?php echo $cid; ?>"><?php echo $code; ?></option>
                                <?php } ?>
                              </select>
                              <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="company" style=""></i>
                              
                            </div>
                            <!-- <div class="col-md-3 col-sm-3 col-xs-12">
                              <label>Doc Type</label>
                              <input type="text" id="doc_type" name="doc_type" class="form-control" placeholder="Enter document type"
                                     maxlength="150"
                                      data-parsley-pattern="^[0-9A-Za-z .,()&-]+$"
                                     data-parsley-pattern-message="Document type contains invalid characters"
                                     data-parsley-maxlength="150"
                                     data-parsley-maxlength-message="Document type cannot exceed 150 characters">
                              <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="doc_type" style=""></i>
                            </div> -->
                            <div class="col-md-3 col-sm-3 col-xs-12" style="display:none;">
                              <label id="doc_number_label">Doc Number (ERL)</label>
                              <input type="text" id="doc_number" name="doc_number" class="form-control" placeholder=""
                                     maxlength="150"
                                      data-parsley-pattern="^[0-9A-Za-z .,()&-]+$"
                                     data-parsley-pattern-message="Document number contains invalid characters"
                                     data-parsley-maxlength="150"
                                     data-parsley-maxlength-message="Document number cannot exceed 150 characters">
                            </div>
                          </div>

                          <div class="form-group">
                            <div class="col-md-3 col-sm-3 col-xs-12">
                              <label>Doc Date</label>
                              <input type="date" id="doc_date" name="doc_date" class="form-control"
                                     value="<?php echo date('Y-m-d'); ?>"
                                     data-parsley-required="true"
                                     data-parsley-required-message="Document date is required"
                                     data-parsley-date="true"
                                     data-parsley-date-message="Please enter a valid date">
                              <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="doc_date" style=""></i>
                              
                            </div>
                            <div class="col-md-3 col-sm-3 col-xs-12">
                              <label>Cost Center/Department</label>
                              <select id="cost_center" name="cost_center" class="form-control" required
                                      data-parsley-required="true"
                                      data-parsley-required-message="Please select a cost center">
                                <option value="">Choose..</option>
                                <?php foreach ($departments as $dep) { 
                                  $ccode = htmlspecialchars($dep['company_code'] ?? '', ENT_QUOTES, 'UTF-8');
                                  $dcode = htmlspecialchars($dep['department_code'] ?? '', ENT_QUOTES, 'UTF-8');
                                  $dname = htmlspecialchars($dep['department_name'] ?? '', ENT_QUOTES, 'UTF-8');
                                  $did   = htmlspecialchars($dep['department_id'] ?? '', ENT_QUOTES, 'UTF-8');
                                ?>
                                  <option value="<?php echo $dcode; ?>" data-company="<?php echo $ccode; ?>" data-department-id="<?php echo $did; ?>"><?php echo $dname; ?></option>
                                <?php } ?>
                              </select>
                              <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="cost_center" style=""></i>
                              
                            </div>
                            <div class="col-md-3 col-sm-3 col-xs-12">
                              <label>Expenditure Type</label>
                              <select id="expenditure_type" name="expenditure_type" class="form-control" required
                                      data-parsley-required="true"
                                      data-parsley-required-message="Please select an expenditure type"
                                      data-parsley-in="capex,opex,other"
                                      data-parsley-in-message="Invalid expenditure type selected">
                                <option value="">Choose..</option>
                                <option value="capex">Capex</option>
                                <option value="opex">Opex</option>
                                <option value="inventory">Inventory</option>
                                <option value="other">Others</option>
                              </select>
                              <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="expenditure_type" style=""></i>
                              
                            </div>
                            <div class="col-md-3 col-sm-3 col-xs-12">
                              <label>Currency</label>
                              <select id="currency" name="currency" class="form-control" required
                                      data-parsley-required="true"
                                      data-parsley-required-message="Please select a currency"
                                      data-parsley-in="USD,EUR,JPY,GBP,AUD,CAD,CHF,CNY,PHP"
                                      data-parsley-in-message="Invalid currency selected">
                                <option value="PHP">PHP</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="JPY">JPY</option>
                                <option value="GBP">GBP</option>
                                <option value="AUD">AUD</option>
                                <option value="CAD">CAD</option>
                                <option value="CHF">CHF</option>
                                <option value="CNY">CNY</option>
                              </select>
                              <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="currency" style=""></i>
                              
                            </div>
                          </div>

                          <div class="form-group">
                          <div class="col-md-4 col-sm-4 col-xs-12" id="field_is_budgeted_col">
                              <label>Tag (Is it budgeted)</label>
                              <select id="is_budgeted" name="is_budgeted" class="form-control"
                                      data-parsley-in="yes,no"
                                      data-parsley-in-message="Please select a valid option">
                                <option value="">Choose..</option>
                                <option value="yes">Yes</option>
                                <option value="no">No</option>
                              </select>
                              <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="is_budgeted" style=""></i>
                            </div>
                            <div class="col-md-4 col-sm-4 col-xs-12">
                              <label>Balance</label>
                              <input type="number" step="0.01" id="balance" name="balance" class="form-control" placeholder="0.00"
                                     min="0" max="999999999.99"
                                     data-parsley-type="number"
                                     data-parsley-min="0"
                                     data-parsley-min-message="Balance cannot be negative"
                                     data-parsley-max="999999999.99"
                                     data-parsley-max-message="Balance cannot exceed 999,999,999.99">
                            </div>
                            <div class="col-md-4 col-sm-4 col-xs-12">
                              <label>Budget</label>
                              <input type="number" step="0.01" id="budget" name="budget" class="form-control" placeholder="0.00"
                                     min="0" max="999999999.99"
                                     data-parsley-type="number"
                                     data-parsley-min="0"
                                     data-parsley-min-message="Budget cannot be negative"
                                     data-parsley-max="999999999.99"
                                     data-parsley-max-message="Budget cannot exceed 999,999,999.99">
                            </div>

                          </div>
                        </div>
                      </div>

                      <!-- ======================================== -->
                      <!-- ITEM DETAILS SECTION -->
                      <!-- ======================================== -->
                      <div class="x_panel" id="item_section" style="display: none;">
                        <div class="x_title">
                          <h3><i class="fa fa-list"></i> Item Details</h3>
                          <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                          <div class="clearfix" style="margin-bottom:10px;">
                            <button type="button" class="btn btn-sm btn-primary" id="addItemBtn"><i class="fa fa-plus"></i> Add Item</button>
                          </div>
                          <div id="itemsContainer"></div>
                          <!-- Template for an item row -->
                          <template id="itemRowTemplate">
                            <div class="well item-row" style="padding:10px; position:relative;">
                              <button type="button" class="btn btn-danger btn-sm remove-item" title="Remove item" style="position:absolute; top:10px; right:10px; z-index:5000; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:4px;"><i class="fa fa-trash fa-lg"></i></button>
                              <div class="form-group">
                                <div class="col-md-3 col-sm-3 col-xs-12 field-category field_category_col" id="field_category_col">
                                  <label>Category</label>
                                  <select name="items_category[]" class="form-control select2-category">
                                    <option value="">Choose..</option>
                                    <?php foreach ($categories as $cat) { 
                                      $code = htmlspecialchars($cat['category_code'] ?? '', ENT_QUOTES, 'UTF-8');
                                      $name = htmlspecialchars($cat['category_name'] ?? '', ENT_QUOTES, 'UTF-8');
                                      $sapCode = htmlspecialchars($cat['sap_account_code'] ?? '', ENT_QUOTES, 'UTF-8');
                                      $sapDesc = htmlspecialchars($cat['sap_account_description'] ?? '', ENT_QUOTES, 'UTF-8');
                                      $displayText = $name;
                                      if ($sapDesc) {
                                        $displayText .= ' - ' . $sapDesc;
                                      }
                                    ?>
                                      <option value="<?php echo $code; ?>" data-sap-code="<?php echo $sapCode; ?>" data-sap-desc="<?php echo $sapDesc; ?>"><?php echo $displayText; ?></option>
                                    <?php } ?>
                                  </select>
                                  <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="items_category" style=""></i>
                                </div>
                                <div class="col-md-4 col-sm-4 col-xs-12 field-attachment field_attachment_col" id="field_attachment_col">
                                  <label>Attachments <small>(Multiple files allowed)</small></label>
                                  <div class="upload-area item-upload-area" style="border: 2px dashed #ccc; border-radius: 8px; padding: 20px; text-align: center; background-color: #fafafa; transition: all 0.3s ease; cursor: pointer; position: relative;" onmouseover="this.style.borderColor='#007bff'; this.style.backgroundColor='#f0f8ff';" onmouseout="this.style.borderColor='#ccc'; this.style.backgroundColor='#fafafa';">
                                    <i class="fa fa-cloud-upload" style="font-size: 24px; color: #666; margin-bottom: 10px;"></i>
                                    <div style="font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 500;">Click to upload or drag files here</div>
                                    <div style="font-size: 10px; color: #999; margin-bottom: 5px;">PDF, DOC, XLS, Images</div>
                                    <div style="font-size: 9px; color: #aaa;">Multiple files supported • Max 200MB each</div>
                                    <input type="file" name="items_attachment[]" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif" multiple style="position: absolute; opacity: 0; width: 100%; height: 100%; cursor: pointer; top: 0; left: 0;" onchange="handleItemFileSelection(this)">
                                  </div>
                                  <div class="file-list" style="margin-top: 10px; font-size: 12px;"></div>
                                  <div class="existing-item-attachments" style="margin-top: 10px; font-size: 11px;"></div>
                                  <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="items_attachment" style=""></i>
                                </div>
                                <div class="col-md-4 col-sm-4 col-xs-12 field-description field_description_col" id="field_description_col">
                                  <label>Description</label>
                                  <textarea name="items_description[]" class="form-control" rows="2" maxlength="1000"></textarea>
                                  <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="items_description" style=""></i>
                                </div>
                              </div>
                              <div class="form-group">
                                <div class="col-md-4 col-sm-4 col-xs-12 field-reference-number field_reference_number_col" id="field_reference_number_col">
                                  <label>Reference Number</label>
                                  <input type="text" name="items_reference_number[]" class="form-control" maxlength="150">
                                  <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="items_reference_number" style=""></i>
                                </div>
                                <div class="col-md-4 col-sm-4 col-xs-12 field-po-number field_po_number_col" id="field_po_number_col">
                                  <label>PO Number</label>
                                  <input type="text" name="items_po_number[]" class="form-control" maxlength="150">
                                  <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="items_po_number" style=""></i>
                                </div>
                              </div>
                              <div class="form-group">
                                <div class="col-md-3 col-sm-3 col-xs-12 field-due-date field_due_date_col" id="field_due_date_col">
                                  <label>Due Date</label>
                                  <input type="date" name="items_due_date[]" class="form-control">
                                  <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="items_due_date" style=""></i>
                                </div>
                                <div class="col-md-3 col-sm-3 col-xs-12 field-cash-advance field_cash_advance_col" id="field_cash_advance_col">
                                  <label>Cash Advance?</label>
                                  <select name="items_cash_advance[]" class="form-control">
                                    <option value="">Choose..</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                  </select>
                                  <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="items_cash_advance" style=""></i>
                                </div>
                                <div class="col-md-3 col-sm-3 col-xs-12 field-form-of-payment field_form_of_payment_col" id="field_form_of_payment_col">
                                  <label>Form of Payment</label>
                                  <select name="items_form_of_payment[]" class="form-control">
                                    <option value="">Choose..</option>
                                    <option value="corporate_check">Corporate Check</option>
                                    <option value="wire_transfer">Telegraphic/Wire Transfer</option>
                                    <option value="cash">Cash</option>
                                    <option value="manager_check">Manager Check</option>
                                    <option value="credit_to_account">Credit to Account</option>
                                    <option value="others">Others</option>
                                  </select>
                                  <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="items_form_of_payment" style=""></i>
                                </div>
                              </div>
  
                              <!-- ERGR/ERL specific fields -->
                              <div class="form-group">
                                <div class="col-md-2 col-sm-6 col-xs-12 field-carf-no field_carf_no_col" id="field_carf_no_col">
                                  <label>CARF No.</label>
                                  <input type="text" name="items_carf_no[]" class="form-control" maxlength="150" placeholder="Enter CARF Number">
                                  <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="items_carf_no" style=""></i>
                                </div>
                                <div class="col-md-2 col-sm-6 col-xs-12 field-pcv-no field_pcv_no_col" id="field_pcv_no_col">
                                  <label>PCV No.</label>
                                  <input type="text" name="items_pcv_no[]" class="form-control" maxlength="150" placeholder="Enter PCV Number">
                                  <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="items_pcv_no" style=""></i>
                                </div>
                                <div class="col-md-2 col-sm-6 col-xs-12 field-invoice-date field_invoice_date_col" id="field_invoice_date_col">
                                  <label>Invoice Date</label>
                                  <input type="date" name="items_invoice_date[]" class="form-control">
                                  <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="items_invoice_date" style=""></i>
                                </div>
                                <div class="col-md-2 col-sm-6 col-xs-12 field-invoice-number field_invoice_number_col" id="field_invoice_number_col">
                                  <label>Invoice #</label>
                                  <input type="text" name="items_invoice_number[]" class="form-control" maxlength="150" placeholder="Enter Invoice Number">
                                  <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="items_invoice_number" style=""></i>
                                </div>
                                <div class="col-md-4 col-sm-6 col-xs-12 field-supplier-name field_supplier_name_col" id="field_supplier_name_col">
                                  <label>Supplier's/Store Name</label>
                                  <input type="text" name="items_supplier_name[]" class="form-control" maxlength="255" placeholder="Enter Supplier/Store Name">
                                  <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="items_supplier_name" style=""></i>
                                </div>
                              </div>
                              
                              <div class="form-group">
                                <div class="col-md-2 col-sm-6 col-xs-12 field-tin field_tin_col" id="field_tin_col">
                                  <label>TIN</label>
                                  <input type="text" name="items_tin[]" class="form-control" maxlength="50" placeholder="Enter TIN">
                                  <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="items_tin" style=""></i>
                                </div>
                                <div class="col-md-10 col-sm-6 col-xs-12 field-address field_address_col" id="field_address_col">
                                  <label>Address</label>
                                  <textarea name="items_address[]" class="form-control" rows="2" maxlength="500" placeholder="Enter Address"></textarea>
                                  <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="items_address" style=""></i>
                                </div>
                              </div>
                              
                              <!-- Tax-related fields -->
                              <div class="form-group">
                                <div class="col-md-2 col-sm-3 col-xs-12 field-gross-amount field_gross_amount_col" id="field_gross_amount_col">
                                  <label>Gross Amount</label>
                                  <input type="number" step="0.01" name="items_gross_amount[]" class="form-control tax-calc-field" min="0" max="999999999.99" placeholder="0.00">
                                  <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="items_gross_amount" style=""></i>
                                </div>
                                <div class="col-md-2 col-sm-3 col-xs-12 field-vatable field_vatable_col" id="field_vatable_col">
                                  <label>Vatable?</label>
                                  <select name="items_vatable[]" class="form-control tax-calc-field">
                                    <option value="">Choose..</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                  </select>
                                  <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="items_vatable" style=""></i>
                                </div>
                                <div class="col-md-2 col-sm-3 col-xs-12 field-vat-amount field_vat_amount_col" id="field_vat_amount_col">
                                  <label>VAT Amount</label>
                                  <input type="number" step="0.01" name="items_vat_amount[]" class="form-control" min="0" max="999999999.99" placeholder="0.00" readonly>
                                  <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="items_vat_amount" style=""></i>
                                </div>
                                <div class="col-md-3 col-sm-3 col-xs-12 field-withholding-tax field_withholding_tax_col" id="field_withholding_tax_col">
                                  <label>Withholding Tax/Final Tax</label>
                                  <div style="display: flex; gap: 5px;">
                                    <select name="items_withholding_tax[]" class="form-control tax-calc-field" style="flex: 1;">
                                      <option value="">Choose..</option>
                                      <?php foreach ($withholdingTaxes as $tax) { 
                                        $code = htmlspecialchars($tax['tax_code'] ?? '', ENT_QUOTES, 'UTF-8');
                                        $name = htmlspecialchars($tax['tax_name'] ?? '', ENT_QUOTES, 'UTF-8');
                                        $rate = htmlspecialchars($tax['tax_rate'] ?? '', ENT_QUOTES, 'UTF-8');
                                        $displayText = $code;
                                        if ($rate > 0) {
                                          $displayText .= ' - ' . $rate . '% ' . $name;
                                        } else {
                                          $displayText .= ' - ' . $name;
                                        }
                                      ?>
                                        <option value="<?php echo $code; ?>" data-rate="<?php echo $rate; ?>"><?php echo $displayText; ?></option>
                                      <?php } ?>
                                      <option value="other">Other</option>
                                    </select>
                                  </div>
                                  <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="items_withholding_tax" style=""></i>
                                </div>
                                <div class="col-md-3 col-sm-3 col-xs-12 field-amount-withhold field_amount_withhold_col" id="field_amount_withhold_col">
                                  <label>Amount Withhold</label>
                                  <input type="number" step="0.01" name="items_amount_withhold[]" class="form-control" min="0" max="999999999.99" placeholder="0.00" readonly>
                                  <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="items_amount_withhold" style=""></i>
                                </div>
                              </div>
                              
                              <div class="form-group">
                                <div class="col-md-4 col-sm-6 col-xs-12 field-net-payable field_net_payable_col" id="field_net_payable_col">
                                  <label>Net Payable Amount</label>
                                  <input type="number" step="0.01" name="items_net_payable[]" class="form-control" min="0" max="999999999.99" placeholder="0.00" readonly>
                                  <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="items_net_payable" style=""></i>
                                </div>
                              </div>
                            
                            </div>
                          </template>
                        </div>
                      </div>


                      <!-- ======================================== -->
                      <!-- FOOTER SECTION -->
                      <!-- ======================================== -->
                      <div class="x_panel" id="footer_section">
                        <div class="x_title">
                          <h3><i class="fa fa-credit-card"></i> Footer</h3>
                          <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                          
                          <div class="form-group">
                            <div class="col-md-4 col-sm-4 col-xs-12" id="field_payee_col">
                              <label>Payee</label>
                              <?php $sessName = trim((string)($_SESSION['userfirstname'] ?? '').' '.(string)($_SESSION['userlastname'] ?? '')); if($sessName===''){ $sessName = (string)($_SESSION['username'] ?? ''); } ?>
                     
                              <input type="text" id="payee" name="payee" class="form-control" placeholder="Enter payee" readonly
                                     maxlength="255"
                                      data-parsley-pattern="^[0-9A-Za-z .,()&-]+$"
                                     data-parsley-pattern-message="Payee name contains invalid characters"
                                     data-parsley-maxlength="255"
                                     data-parsley-maxlength-message="Payee name cannot exceed 255 characters" value="<?php echo htmlspecialchars($sessName, ENT_QUOTES, 'UTF-8'); ?>">
                              <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="payee" style=""></i>
                                     <div class="checkbox" style="margin: 5px 0 10px;">
                                <label>
                                  <input type="checkbox" id="toggle_payee" class="js-switch"> Enable Payee
                                </label>
                              </div>
                            </div>
                            <div class="col-md-4 col-sm-4 col-xs-12" id="field_amount_figures_col">
                              <label>Amount in Figures</label>
                              <input type="number" step="0.01" id="amount_figures" name="amount_figures" class="form-control" placeholder="0.00" readonly
                                     min="0.01" max="999999999.99"
                                     data-parsley-type="number"
                                     data-parsley-min="0.01"
                                     data-parsley-min-message="Amount must be greater than 0"
                                     data-parsley-max="999999999.99"
                                     data-parsley-max-message="Amount cannot exceed 999,999,999.99">
                              <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="amount_figures" style=""></i>
                            </div>
                            <div class="col-md-4 col-sm-4 col-xs-12" id="field_amount_words_col">
                              <label>Amount in Words</label>
                              <input type="text" id="amount_words" name="amount_words" class="form-control" placeholder="e.g., One Thousand Pesos" readonly
                                     maxlength="500"
                                     data-parsley-maxlength="500"
                                     data-parsley-maxlength-message="Amount in words cannot exceed 500 characters">
                              <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="amount_words" style=""></i>
                            </div>

                          </div>
                          
                          <div class="form-group">
                            <div class="col-md-12 col-sm-12 col-xs-12" id="field_payment_for_col">
                              <label for="payment_for">Payment For</label>
                              <textarea id="payment_for" required="required" class="form-control" name="payment_for" data-parsley-trigger="keyup" data-parsley-minlength="5" data-parsley-maxlength="500" data-parsley-minlength-message="Please enter at least 5 characters."
                                  data-parsley-validation-threshold="10"></textarea>
                              <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="payment_for" style=""></i>
                              
                            </div>
                          </div>

                          <div class="form-group">
                            <div class="col-md-12 col-sm-12 col-xs-12" id="field_special_instruction_col">
                              <label for="special_instructions">Special Instructions</label>
                              <textarea id="special_instructions" class="form-control" name="special_instructions" data-parsley-trigger="keyup" data-parsley-maxlength="1000" data-parsley-validation-threshold="10"></textarea>
                              <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="special_instructions" style=""></i>
                            </div>
                          </div>
                          
                          <div class="form-group">
                            <div class="col-md-6 col-sm-6 col-xs-12" id="field_supporting_document_col">
                              <label>Supporting Document</label>
                              <div class="upload-area" style="border: 2px dashed #ccc; border-radius: 8px; padding: 30px; text-align: center; background-color: #fafafa; transition: all 0.3s ease; cursor: pointer; position: relative;" onmouseover="this.style.borderColor='#007bff'; this.style.backgroundColor='#f0f8ff';" onmouseout="this.style.borderColor='#ccc'; this.style.backgroundColor='#fafafa';">
                                <i class="fa fa-cloud-upload" style="font-size: 32px; color: #666; margin-bottom: 15px;"></i>
                                <div style="font-size: 14px; color: #666; margin-bottom: 8px; font-weight: 500;">Click to upload or drag files here</div>
                                <div style="font-size: 12px; color: #999; margin-bottom: 10px;">Multiple files allowed (PDF, Excel, Word, Images)</div>
                                <div style="font-size: 11px; color: #aaa;">Max file size: 200MB per file</div>
                                <input type="file" id="supporting_document" name="supporting_document[]" class="form-control"
                                       accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif" multiple
                                       data-parsley-fileextension="pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif"
                                       data-parsley-fileextension-message="Please upload a valid file type (PDF, DOC, XLS, or image)"
                                       data-parsley-filesize="200"
                                       data-parsley-filesize-message="File size must be less than 200MB"
                                       style="position: absolute; opacity: 0; width: 100%; height: 100%; cursor: pointer; top: 0; left: 0;" onchange="handleSupportingFileSelection(this)">
                              </div>
                              <div class="supporting-file-list" style="margin-top: 15px; font-size: 12px;"></div>
                              <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="supporting_document" style=""></i>
                            </div>
                            <div class="col-md-6 col-sm-6 col-xs-12">
                              <label>Existing Supporting Documents</label>
                              <div id="existing-supporting-docs" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px; background-color: #f9f9f9;">
                                <div style="text-align: center; color: #666; font-style: italic; padding: 20px;">
                                  No existing documents to display
                                </div>
                              </div>
                            </div>
                          </div>

                          <div class="form-group">
                            <div class="col-md-4 col-sm-4 col-xs-12" id="field_from_company_col">
                              <label>From Company</label>
                              <input type="text" id="from_company" name="from_company" class="form-control" placeholder="Enter source company"
                                     maxlength="150"
                                      data-parsley-pattern="^[0-9A-Za-z .,()&-]+$"
                                     data-parsley-pattern-message="Company name contains invalid characters"
                                     data-parsley-maxlength="150"
                                     data-parsley-maxlength-message="Company name cannot exceed 150 characters">
                            </div>
                            <div class="col-md-4 col-sm-4 col-xs-12" id="field_to_company_col">
                              <label>To Company</label>
                              <input type="text" id="to_company" name="to_company" class="form-control" placeholder="Enter destination company"
                                     maxlength="150"
                                      data-parsley-pattern="^[0-9A-Za-z .,()&-]+$"
                                     data-parsley-pattern-message="Company name contains invalid characters"
                                     data-parsley-maxlength="150"
                                     data-parsley-maxlength-message="Company name cannot exceed 150 characters">
                            </div>
                            <div class="col-md-4 col-sm-4 col-xs-12" id="field_credit_to_payroll_col">
                              <div class="radio" style="margin-top: 25px;">
                                <label>
                                   <input type="radio" id="credit_to_payroll" name="payment_option" value="credit_to_payroll"> Credit To Payroll
                                </label>
                              </div>
                              <input type="hidden" name="credit_to_payroll" id="credit_to_payroll_hidden" value="0">
                            </div>
                            <div class="col-md-4 col-sm-4 col-xs-12" id="field_issue_check_col">
                              <div class="radio" style="margin-top: 25px;">
                                <label>
                                   <input type="radio" id="issue_check" name="payment_option" value="issue_check"> Issue Check
                                </label>
                              </div>
                              <input type="hidden" name="issue_check" id="issue_check_hidden" value="0">
                            </div>
                          </div>
                        </div>
                      </div>
                      
                      <div class="ln_solid"></div>
                      
                      <!-- DEBUG PANEL -->
                      <!-- <div class="x_panel" style="background-color: #f8f9fa; border: 2px solid #007bff;">
                        <div class="x_title">
                          <h3><i class="fa fa-bug"></i> Debug Information</h3>
                          <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                          <div id="debug-panel" style="font-family: monospace; font-size: 12px; background: white; padding: 10px; border-radius: 4px; max-height: 200px; overflow-y: auto;">
                            <div style="color: #666; font-style: italic;">Click "Show Debug Info" to see current file status</div>
                          </div>
                          <button type="button" class="btn btn-info btn-sm" onclick="updateDebugPanel();" style="margin-top: 10px;">
                            <i class="fa fa-refresh"></i> Refresh Debug Info
                          </button>
                        </div>
                      </div> -->
                      
                      <div class="form-group">
                        <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                          <button class="btn btn-primary" type="button">Cancel</button>
						              <button class="btn btn-primary" type="reset">Reset</button>
                          <!-- <button type="button" class="btn btn-info" onclick="showDebugInfo();">Show Debug Info</button> -->
                          <button type="submit" class="btn btn-success" id="submitBtn" 
                                  data-loading-text="Submitting..."
                                  onclick="return validateFileUploads();">
                            Submit
                          </button>
                        </div>
                      </div>

                    </form>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
        <footer>
          <div class="pull-right">
            Gentelella - Bootstrap Admin Template by <a href="https://colorlib.com">Colorlib</a>
          </div>
          <div class="clearfix"></div>
        </footer>
        <!-- /footer content -->
      </div>
    </div>

    <!-- jQuery -->
    <script src="../srv-v2/vendors/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap -->
    <script src="../srv-v2/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
    <!-- FastClick -->
    <script src="../srv-v2/vendors/fastclick/lib/fastclick.js"></script>
    <!-- NProgress -->
    <script src="../srv-v2/vendors/nprogress/nprogress.js"></script>
    <!-- bootstrap-progressbar -->
    <script src="../srv-v2/vendors/bootstrap-progressbar/bootstrap-progressbar.min.js"></script>
    <!-- iCheck -->
    <script src="../srv-v2/vendors/iCheck/icheck.min.js"></script>
    <!-- bootstrap-daterangepicker -->
    <script src="../srv-v2/vendors/moment/min/moment.min.js"></script>
    <script src="../srv-v2/vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
    <!-- bootstrap-wysiwyg -->
    <script src="../srv-v2/vendors/bootstrap-wysiwyg/js/bootstrap-wysiwyg.min.js"></script>
    <script src="../srv-v2/vendors/jquery.hotkeys/jquery.hotkeys.js"></script>
    <script src="../srv-v2/vendors/google-code-prettify/src/prettify.js"></script>
    <!-- jQuery Tags Input -->
    <script src="../srv-v2/vendors/jquery.tagsinput/src/jquery.tagsinput.js"></script>
    <!-- Switchery -->
    <script src="../srv-v2/vendors/switchery/dist/switchery.min.js"></script>
    <!-- Select2 -->
    <script src="../srv-v2/vendors/select2/dist/js/select2.full.min.js"></script>
    <!-- Parsley -->
    <script src="../srv-v2/vendors/parsleyjs/dist/parsley.min.js"></script>
    <!-- Autosize -->
    <script src="../srv-v2/vendors/autosize/dist/autosize.min.js"></script>
    <!-- jQuery autocomplete -->
    <script src="../srv-v2/vendors/devbridge-autocomplete/dist/jquery.autocomplete.min.js"></script>
    <!-- starrr -->
    <script src="../srv-v2/vendors/starrr/dist/starrr.js"></script>
    <!-- Custom Theme Scripts -->
    <script src="../srv-v2/build/js/custom.min.js"></script>
    <!-- Link to external JavaScript file -->
    <!-- SweetAlert2 -->
<script src="sweetalert2@11.js"></script>
    <script src="upload_size_validator.js"></script>
    <script src="financial-form.js"></script>
    
    <!-- Debug and testing functions -->
    <script>
      // Manual trigger function for testing amount calculation
      window.testAmountCalculation = function() {
        console.log('Testing amount calculation...');
        if (typeof updateTotalAmountInFigures === 'function') {
          updateTotalAmountInFigures();
        } else {
          console.log('updateTotalAmountInFigures function not found');
        }
      };
      
      // Manual trigger function for testing tax calculations
      window.testTaxCalculations = function() {
        console.log('Testing tax calculations...');
        if (typeof triggerTaxCalculations === 'function') {
          triggerTaxCalculations();
        } else {
          console.log('triggerTaxCalculations function not found');
        }
      };
    </script>

    <style>
      /* Select2 customization for better alignment and appearance */
      .select2-container--default .select2-selection--single {
        height: 34px;
        border: 1px solid #ccc;
        border-radius: 4px;
      }
      
      .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 32px;
        padding-left: 12px;
        padding-right: 20px;
      }
      
      .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 32px;
      }
      
      .select2-dropdown {
        border: 1px solid #ccc;
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      }
      
      .select2-search--dropdown .select2-search__field {
        border: 1px solid #ccc;
        border-radius: 4px;
        padding: 8px;
      }
      
      .select2-results__option {
        padding: 8px 12px;
      }
      
      .select2-results__option--highlighted[aria-selected] {
        background-color: #337ab7;
      }
      
      /* Asterisk positioning inside form controls */
      .form-control-feedback.glyphicon-asterisk {
        position: absolute;
        top: 50%;
        right: 8px;
        transform: translateY(-50%);
        color:rgb(177, 174, 174);
        font-size: 12px;
        z-index: 10;
        pointer-events: none;
      }
      
      /* Ensure form groups have relative positioning for absolute asterisks */
      .form-group {
        position: relative;
      }
      
      /* Adjust padding for inputs with asterisks */
      .form-control {
        padding-right: 25px;
      }
      
      /* Special handling for Select2 dropdowns */
      .select2-container .form-control-feedback.glyphicon-asterisk {
        right: 30px; /* Account for Select2's dropdown arrow */
      }
    </style>

    <script>
      // Initialize Select2 for category dropdowns
      $(document).ready(function() {
        // Initialize Select2 for existing category dropdowns
        $('.select2-category').select2({
          placeholder: 'Search and select category...',
          allowClear: true,
          width: '100%',
          dropdownParent: $('body')
        });

        // Initialize Select2 for dynamically added category dropdowns
        $(document).on('select2:open', function() {
          // Ensure Select2 dropdown is properly positioned
          setTimeout(function() {
            $('.select2-dropdown').css('z-index', 9999);
          }, 0);
        });
      });
    </script>

    <script>
      (function(){
        function enforceAllRequired(formEl){
          if (!formEl) return;
          var fields = formEl.querySelectorAll('input, select, textarea');
          for (var i=0;i<fields.length;i++){
            var el = fields[i];
            if (el.type === 'hidden') continue;
            if (el.disabled) continue;
            if (el.readOnly) continue;
            if (el.tagName === 'BUTTON') continue;
            if (el.id === 'toggle_payee') continue; // Do not require the Enable Payee switch
            if (el.closest && el.closest('#field_attachment_col')) continue; // Do not require item attachments
            if (el.closest && el.closest('#field_supporting_document_col')) continue; // Do not require item attachments
            if (el.id === 'balance' || el.id === 'budget' || el.id === 'from_company' || el.id === 'to_company') continue; // Do not require these optional fields
            if (el.name === 'items_withholding_tax[]') continue; // Do not require withholding tax field
            if (el.type === 'checkbox') continue; // Do not require checkboxes individually
            el.setAttribute('data-parsley-required','true');
            if (!el.getAttribute('data-parsley-required-message')) {
              el.setAttribute('data-parsley-required-message','This field is required');
            }
          }
        }
        function applyRequiredByType(){
          var formEl = document.getElementById('demo-form2');
          var reqSel = document.getElementById('requestType');
          if (!formEl || !reqSel) return;
          var val = (reqSel.value || '').toUpperCase();
          var itemSection = document.getElementById('item_section');
          
          if (val === 'ERGR' || val === 'ERL' || val === 'RFP') {
            // Show item details section when request type is selected
            if (itemSection) {
              itemSection.style.display = 'block';
            }
            
            enforceAllRequired(formEl);
            // Explicitly remove required attribute from withholding tax field (make it optional)
            var withholdingTaxFields = formEl.querySelectorAll('[name="items_withholding_tax[]"]');
            for (var w = 0; w < withholdingTaxFields.length; w++) {
              var wtf = withholdingTaxFields[w];
              try { wtf.removeAttribute('required'); } catch (e) {}
              try { wtf.removeAttribute('data-parsley-required'); } catch (e) {}
              try { wtf.classList && wtf.classList.remove('parsley-error'); } catch (e) {}
            }
            // re-evaluate payee conditional required
            var togglePayee = document.getElementById('toggle_payee');
            var payeeInput = document.getElementById('payee');
            if (togglePayee && payeeInput) {
              if (togglePayee.checked) {
                payeeInput.setAttribute('data-parsley-required','true');
              } else {
                payeeInput.removeAttribute('data-parsley-required');
              }
            }
            // For RFP, make specific item fields optional (not required)
            if (val === 'RFP') {
              var optionalSelectors = [
                '[name="items_carf_no[]"]',
                '[name="items_pcv_no[]"]',
                '[name="items_invoice_date[]"]',
                '[name="items_invoice_number[]"]',
                '[name="items_supplier_name[]"]',
                '[name="items_tin[]"]',
                '[name="items_address[]"]'
              ];
              for (var s = 0; s < optionalSelectors.length; s++) {
                var nodes = formEl.querySelectorAll(optionalSelectors[s]);
                for (var n = 0; n < nodes.length; n++) {
                  var f = nodes[n];
                  try { f.removeAttribute('required'); } catch (e) {}
                  try { f.removeAttribute('data-parsley-required'); } catch (e) {}
                  try { f.classList && f.classList.remove('parsley-error'); } catch (e) {}
                }
              }
            }
            
            // For ERL, no special validation needed for radio buttons
          } else {
            // Hide item details section when no request type is selected
            if (itemSection) {
              itemSection.style.display = 'none';
            }
          }
        }
        
        document.addEventListener('DOMContentLoaded', function(){
          var reqSel = document.getElementById('requestType');
          if (reqSel) {
            reqSel.addEventListener('change', applyRequiredByType);
            applyRequiredByType();
          }
          
          // Handle radio button changes for payment options
          var creditToPayrollRadio = document.getElementById('credit_to_payroll');
          var issueCheckRadio = document.getElementById('issue_check');
          var creditToPayrollHidden = document.getElementById('credit_to_payroll_hidden');
          var issueCheckHidden = document.getElementById('issue_check_hidden');
          
          if (creditToPayrollRadio && issueCheckRadio && creditToPayrollHidden && issueCheckHidden) {
            function updateHiddenFields() {
              if (creditToPayrollRadio.checked) {
                creditToPayrollHidden.value = '1';
                issueCheckHidden.value = '0';
              } else if (issueCheckRadio.checked) {
                creditToPayrollHidden.value = '0';
                issueCheckHidden.value = '1';
              } else {
                creditToPayrollHidden.value = '0';
                issueCheckHidden.value = '0';
              }
            }
            
            creditToPayrollRadio.addEventListener('change', updateHiddenFields);
            issueCheckRadio.addEventListener('change', updateHiddenFields);
            
            // Initialize hidden fields on page load
            updateHiddenFields();
          }
        });
      })();
    </script>

    <script>
      (function(){
        // Department filtering function - make it globally accessible
        window.filterDepartments = function() {
          var companySel = document.getElementById('company');
          var deptSel = document.getElementById('cost_center');
          if (!companySel || !deptSel) return;
          
          var selectedCompany = companySel.value;
          var anyShown = false;
          var currentSelection = deptSel.value;
          
          // First, show all options
          for (var i = 0; i < deptSel.options.length; i++) {
            deptSel.options[i].style.display = '';
          }
          
          // Then filter based on company selection
          for (var i = 0; i < deptSel.options.length; i++) {
            var opt = deptSel.options[i];
            if (!opt.value) continue; // Skip empty option
            
            var show = !selectedCompany || opt.getAttribute('data-company') === selectedCompany;
            
            if (!show) {
              opt.style.display = 'none';
              // If this option was selected, deselect it
              if (opt.selected) {
                opt.selected = false;
                currentSelection = '';
              }
            } else {
              anyShown = true;
            }
          }
          
          // If no departments are shown for the selected company, clear the selection
          if (!anyShown) {
            deptSel.value = '';
          } else if (currentSelection && deptSel.value !== currentSelection) {
            // Restore the previous selection if it's still valid
            deptSel.value = currentSelection;
          }
        }

        // Fallback item handlers
        function bindFallbackItemHandlers() {
          var addItemBtn = document.getElementById('addItemBtn');
          if (addItemBtn && !addItemBtn.dataset.ffBound && !addItemBtn.dataset.inlineBound) {
            addItemBtn.addEventListener('click', function(){
              var container = document.getElementById('itemsContainer');
              var tpl = document.getElementById('itemRowTemplate');
              if (container && tpl) {
                var node = document.importNode(tpl.content, true);
                
                // Generate unique ID for the upload area
                var uploadArea = node.querySelector('.upload-area');
                if (uploadArea) {
                  var uniqueId = 'item-upload-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
                  uploadArea.id = uniqueId;
                  
                  // Initialize file storage for this new item
                  storedItemFiles[uniqueId] = [];
                }
                
                container.appendChild(node);
                
                // Initialize Select2 for the new category dropdown
                var newCategorySelect = container.querySelector('.item-row:last-child .select2-category');
                if (newCategorySelect) {
                  $(newCategorySelect).select2({
                    placeholder: 'Search and select category...',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('body')
                  });
                }
              }
            });
            addItemBtn.dataset.inlineBound = '1';
          }


          var itemsContainer = document.getElementById('itemsContainer');
          if (itemsContainer && !itemsContainer.dataset.ffBound && !itemsContainer.dataset.inlineBound) {
            itemsContainer.addEventListener('click', function(e){
              var target = e.target;
              if (!target) return;
              var btn = (target.classList && target.classList.contains('remove-item')) ? target : target.closest && target.closest('.remove-item');
              if (btn) {
                var row = btn.closest('.item-row');
                if (row && row.parentNode) row.parentNode.removeChild(row);
              }
            });
            itemsContainer.dataset.inlineBound = '1';
          }

        }

        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', function(){
          // Set up company-department filtering
          var companySel = document.getElementById('company');
          var deptSel = document.getElementById('cost_center');
          
          if (companySel) {
            companySel.addEventListener('change', function() {
              filterDepartments();
            });
            
            // Initial filter on page load
            filterDepartments();
          }
          
          if (deptSel) {
            deptSel.addEventListener('change', function() {
            });
          }
          
          // Fallback bindings in case external script fails to bind
          bindFallbackItemHandlers();

          // Payee enable/disable toggle
          var togglePayee = document.getElementById('toggle_payee');
          var payeeInput = document.getElementById('payee');
          if (togglePayee && payeeInput) {
            // Store the current user name for reset functionality
            var currentUserName = payeeInput.value || '';
            
            var syncPayeeState = function(){
              var enabled = !!togglePayee.checked;
              payeeInput.readOnly = !enabled; // use readOnly so value still submits
              
              // Reset to current user when toggle is unchecked
              if (!enabled) {
                payeeInput.value = currentUserName;
              }
            };
            // initialize
            syncPayeeState();
            // bind change
            togglePayee.addEventListener('change', syncPayeeState);
            // also re-sync when request type changes (other scripts may toggle disabled)
            var reqTypeSel = document.getElementById('requestType');
            if (reqTypeSel) {
              reqTypeSel.addEventListener('change', function(){
                // slight delay to let other handlers run first
                setTimeout(syncPayeeState, 0);
              });
            }
            // ensure after form reset it stays disabled when toggle is unchecked
            var formEl = document.getElementById('demo-form2');
            if (formEl) {
              formEl.addEventListener('reset', function(){
                setTimeout(function(){
                  // default: keep toggle unchecked and payee disabled
                  try { togglePayee.checked = false; } catch (e) {}
                  syncPayeeState();
                }, 0);
              });
            }
          }
        });
      })();
    </script>
    
    <script>
      // Global variables to store files
      var storedItemFiles = {};
      var storedSupportingFiles = {};
      
      // Function to handle item file selection (simplified approach)
      function handleItemFileSelection(input) {
        // Use a more reliable way to identify the item - find the item row index
        var itemRow = input.closest('.item-row');
        var container = document.getElementById('itemsContainer');
        var itemIndex = Array.from(container.children).indexOf(itemRow);
        var inputId = input.closest('.upload-area').id || 'item-' + itemIndex;
        
        var newFiles = input.files;
        
        // VISIBLE DEBUG - Show alert with file information
        // var debugMessage = 'DEBUG: File Selection\n';
        // debugMessage += 'Input ID: ' + inputId + '\n';
        // debugMessage += 'Files Selected: ' + newFiles.length + '\n';
        // for (var i = 0; i < newFiles.length; i++) {
        //   debugMessage += 'File ' + (i+1) + ': ' + newFiles[i].name + ' (' + (newFiles[i].size/1024).toFixed(1) + ' KB)\n';
        // }
        // alert(debugMessage);
        
        // Debug logging
        // console.log('DEBUG: handleItemFileSelection called for inputId: ' + inputId);
        // console.log('DEBUG: Selected ' + newFiles.length + ' files');
        // for (var i = 0; i < newFiles.length; i++) {
        //   console.log('DEBUG: File ' + i + ': ' + newFiles[i].name + ' (' + newFiles[i].size + ' bytes)');
        // }
        
        // Store the files for this input
        if (newFiles.length > 0) {
          storedItemFiles[inputId] = Array.from(newFiles);
        } else {
          // If no files selected, clear the stored files
          storedItemFiles[inputId] = [];
        }
        
        updateUploadText(input);
        
        // Auto-update debug panel
        // updateDebugPanel();
      }
      
      // Function to handle supporting file selection (preserves existing files)
      function handleSupportingFileSelection(input) {
        var inputId = input.id || 'supporting_default';
        var newFiles = input.files;
        
        if (newFiles.length === 0) {
          // No new files selected, restore stored files if they exist
          if (storedSupportingFiles[inputId] && storedSupportingFiles[inputId].length > 0) {
            var dt = new DataTransfer();
            storedSupportingFiles[inputId].forEach(function(file) {
              dt.items.add(file);
            });
            input.files = dt.files;
          }
        } else {
          // New files selected, store them
          storedSupportingFiles[inputId] = Array.from(newFiles);
        }
        
        updateSupportingDocText(input);
      }
      
      // Function to update upload text for item attachments
      function updateUploadText(input) {
        var fileList = input.parentNode.querySelector('.file-list');
        var files = input.files;
        
        // Check if fileList element exists, if not, create it
        if (!fileList) {
          fileList = document.createElement('div');
          fileList.className = 'file-list';
          fileList.style.cssText = 'margin-top: 10px; font-size: 12px;';
          input.parentNode.appendChild(fileList);
        }
        
        if (files.length > 0) {
          var totalSize = 0;
          for (var i = 0; i < files.length; i++) {
            totalSize += files[i].size;
          }
          var totalSizeMB = (totalSize / 1024 / 1024).toFixed(2);
          
          var html = '<div style="background: #e8f5e8; padding: 10px; border-radius: 6px; border-left: 4px solid #28a745;">';
          html += '<i class="fa fa-check-circle" style="color: #28a745; margin-right: 8px; font-size: 16px;"></i>';
          html += '<strong>Selected ' + files.length + ' file' + (files.length > 1 ? 's' : '') + ':</strong>';
          html += '<span style="color: #666; margin-left: 10px;">(' + totalSizeMB + ' MB total)</span><br>';
          
          for (var i = 0; i < files.length; i++) {
            var file = files[i];
            var size = (file.size / 1024 / 1024).toFixed(2);
            var icon = 'fa-file';
            var ext = file.name.split('.').pop().toLowerCase();
            
            if (['pdf'].includes(ext)) icon = 'fa-file-pdf-o';
            else if (['doc', 'docx'].includes(ext)) icon = 'fa-file-word-o';
            else if (['xls', 'xlsx'].includes(ext)) icon = 'fa-file-excel-o';
            else if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) icon = 'fa-file-image-o';
            
            html += '<div style="margin: 5px 0; font-size: 11px; padding: 6px; background: white; border-radius: 3px; display: flex; align-items: center; justify-content: space-between; border: 1px solid #e0e0e0;">';
            html += '<div style="display: flex; align-items: center; flex: 1;">';
            html += '<i class="fa ' + icon + '" style="margin-right: 8px; color: #666; width: 16px;"></i>';
            html += '<span style="font-weight: 500; flex: 1; word-break: break-all;">' + file.name + '</span>';
            html += '<span style="color: #666; margin-left: 10px; font-size: 10px;">(' + size + ' MB)</span>';
            html += '</div>';
            html += '<button type="button" class="btn btn-xs btn-danger" onclick="removeFile(this, ' + i + '); event.stopPropagation(); event.preventDefault();" style="padding: 3px 8px; font-size: 10px; margin-left: 10px; z-index: 10; position: relative; border-radius: 3px;">';
            html += '<i class="fa fa-times"></i>';
            html += '</button>';
            html += '</div>';
          }
          html += '</div>';
          fileList.innerHTML = html;
        } else {
          fileList.innerHTML = '';
        }
      }
      
      // Function to update upload text for supporting documents
      function updateSupportingDocText(input) {
        var fileList = input.parentNode.querySelector('.supporting-file-list');
        var files = input.files;
        
        // Check if fileList element exists, if not, create it
        if (!fileList) {
          fileList = document.createElement('div');
          fileList.className = 'supporting-file-list';
          fileList.style.cssText = 'margin-top: 15px; font-size: 12px;';
          input.parentNode.appendChild(fileList);
        }
        
        if (files.length > 0) {
          var html = '<div style="background: #e8f5e8; padding: 12px; border-radius: 6px; border-left: 4px solid #28a745;">';
          html += '<i class="fa fa-check-circle" style="color: #28a745; margin-right: 8px; font-size: 16px;"></i>';
          html += '<strong>Selected supporting documents:</strong><br>';
          
          for (var i = 0; i < files.length; i++) {
            var file = files[i];
            var size = (file.size / 1024 / 1024).toFixed(2);
            var icon = 'fa-file';
            var ext = file.name.split('.').pop().toLowerCase();
            
            if (['pdf'].includes(ext)) icon = 'fa-file-pdf-o';
            else if (['doc', 'docx'].includes(ext)) icon = 'fa-file-word-o';
            else if (['xls', 'xlsx'].includes(ext)) icon = 'fa-file-excel-o';
            else if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) icon = 'fa-file-image-o';
            
            html += '<div style="margin: 5px 0; font-size: 12px; padding: 8px; background: white; border-radius: 3px; display: flex; align-items: center; justify-content: space-between;">';
            html += '<div style="display: flex; align-items: center;">';
            html += '<i class="fa ' + icon + '" style="margin-right: 8px; color: #666;"></i>';
            html += '<span style="font-weight: 500;">' + file.name + '</span>';
            html += '<span style="color: #666; margin-left: 10px;">(' + size + ' MB)</span>';
            html += '</div>';
            html += '<button type="button" class="btn btn-xs btn-danger" onclick="removeSupportingFile(this, ' + i + '); event.stopPropagation(); event.preventDefault();" style="padding: 3px 8px; font-size: 11px; margin-left: 10px; z-index: 10; position: relative;">';
            html += '<i class="fa fa-times"></i>';
            html += '</button>';
            html += '</div>';
          }
          html += '</div>';
          fileList.innerHTML = html;
        } else {
          fileList.innerHTML = '';
        }
      }
      
      // Add drag and drop functionality
      document.addEventListener('DOMContentLoaded', function() {
        var uploadAreas = document.querySelectorAll('.upload-area');
        
        uploadAreas.forEach(function(area) {
          var input = area.querySelector('input[type="file"]');
          
          // Prevent default drag behaviors
          ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function(eventName) {
            area.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
          });
          
          // Highlight drop area when item is dragged over it
          ['dragenter', 'dragover'].forEach(function(eventName) {
            area.addEventListener(eventName, highlight, false);
          });
          
          ['dragleave', 'drop'].forEach(function(eventName) {
            area.addEventListener(eventName, unhighlight, false);
          });
          
          // Handle dropped files
          area.addEventListener('drop', handleDrop, false);
          
          function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
          }
          
          function highlight(e) {
            area.style.borderColor = '#007bff';
            area.style.backgroundColor = '#e3f2fd';
          }
          
          function unhighlight(e) {
            area.style.borderColor = '#ccc';
            area.style.backgroundColor = '#fafafa';
          }
          
          function handleDrop(e) {
            var dt = e.dataTransfer;
            var files = dt.files;
            
            input.files = files;
            
            // Trigger the change event
            var event = new Event('change', { bubbles: true });
            input.dispatchEvent(event);
          }
        });
      });
      
      // Function to remove individual files from item attachments
      function removeFile(button, fileIndex) {
        // Prevent event bubbling
        if (event) {
          event.stopPropagation();
          event.preventDefault();
        }
        
        var input = button.closest('.upload-area').querySelector('input[type="file"]');
        var itemRow = input.closest('.item-row');
        var container = document.getElementById('itemsContainer');
        var itemIndex = Array.from(container.children).indexOf(itemRow);
        var inputId = input.closest('.upload-area').id || 'item-' + itemIndex;
        
        // Remove from stored files
        if (storedItemFiles[inputId] && fileIndex >= 0 && fileIndex < storedItemFiles[inputId].length) {
          storedItemFiles[inputId].splice(fileIndex, 1);
          
          // Update the input with remaining files
          var dt = new DataTransfer();
          storedItemFiles[inputId].forEach(function(file) {
            dt.items.add(file);
          });
          input.files = dt.files;
          
          // Refresh the display
          updateUploadText(input);
        }
      }
      
      // Function to remove individual files from supporting documents
      function removeSupportingFile(button, fileIndex) {
        // Prevent event bubbling
        if (event) {
          event.stopPropagation();
          event.preventDefault();
        }
        
        var input = button.closest('.upload-area').querySelector('input[type="file"]');
        var inputId = input.id || 'supporting_default';
        var files = Array.from(input.files);
        
        // Remove the file at the specified index
        files.splice(fileIndex, 1);
        
        // Create a new FileList-like object
        var dt = new DataTransfer();
        files.forEach(function(file) {
          dt.items.add(file);
        });
        
        // Update the input's files
        input.files = dt.files;
        
        // Update stored files
        storedSupportingFiles[inputId] = files;
        
        // Refresh the display
        updateSupportingDocText(input);
      }
      
      // Function to load existing attachments
      function loadExistingAttachments(docNumber) {
        if (!docNumber) return;
        
        // Load supporting documents
        fetch('./get_attachments.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'doc_number=' + encodeURIComponent(docNumber) + '&attachment_type=supporting_document'
        })
        .then(response => response.json())
        .then(data => {
          if (data.success && data.attachments && data.attachments.length > 0) {
            var container = document.getElementById('existing-supporting-docs');
            var html = '';
            data.attachments.forEach(function(attachment) {
              var icon = getFileIcon(attachment.file_extension);
              html += '<div style="margin: 5px 0; padding: 8px; background: white; border-radius: 4px; border: 1px solid #ddd; display: flex; align-items: center; justify-content: space-between;">';
              html += '<div style="display: flex; align-items: center;">';
              html += '<i class="fa ' + icon + '" style="margin-right: 8px; color: #666;"></i>';
              html += '<span style="font-size: 12px; font-weight: 500;">' + attachment.original_filename + '</span>';
              html += '<span style="color: #666; margin-left: 10px; font-size: 10px;">(' + formatFileSize(attachment.file_size) + ')</span>';
              html += '</div>';
              html += '<button type="button" class="btn btn-xs btn-danger" onclick="deleteAttachment(' + attachment.id + ')" style="padding: 2px 6px; font-size: 10px; border-radius: 3px;">×</button>';
              html += '</div>';
            });
            container.innerHTML = html;
          }
        })
        .catch(error => {
          console.error('Error loading supporting documents:', error);
        });
        
        // Load item attachments grouped by item_id
        fetch('./get_attachments.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'doc_number=' + encodeURIComponent(docNumber) + '&attachment_type=item_attachment'
        })
        .then(response => response.json())
        .then(data => {
          if (data.success && data.attachments && data.attachments.length > 0) {
            // Group attachments by item_id
            var attachmentsByItem = {};
            data.attachments.forEach(function(attachment) {
              var itemId = attachment.item_id || 'unknown';
              if (!attachmentsByItem[itemId]) {
                attachmentsByItem[itemId] = [];
              }
              attachmentsByItem[itemId].push(attachment);
            });
            
            // Display attachments for each item
            var containers = document.querySelectorAll('.existing-item-attachments');
            containers.forEach(function(container, index) {
              var itemRow = container.closest('.item-row');
              if (itemRow) {
                // Try to match by index or find the corresponding item
                var itemId = Object.keys(attachmentsByItem)[index];
                if (itemId && attachmentsByItem[itemId]) {
                  var html = '<div style="background: #f8f9fa; padding: 8px; border-radius: 4px; border-left: 3px solid #007bff; margin-bottom: 10px;">';
                  html += '<div style="font-size: 11px; font-weight: 500; color: #007bff; margin-bottom: 5px;">Existing Attachments:</div>';
                  
                  attachmentsByItem[itemId].forEach(function(attachment) {
                    var icon = getFileIcon(attachment.file_extension);
                    html += '<div style="margin: 3px 0; padding: 4px; background: white; border-radius: 3px; border: 1px solid #e0e0e0; display: flex; align-items: center; justify-content: space-between;">';
                    html += '<div style="display: flex; align-items: center;">';
                    html += '<i class="fa ' + icon + '" style="margin-right: 6px; color: #666; font-size: 10px;"></i>';
                    html += '<span style="font-size: 10px; font-weight: 500;">' + attachment.original_filename + '</span>';
                    html += '<span style="color: #666; margin-left: 8px; font-size: 9px;">(' + formatFileSize(attachment.file_size) + ')</span>';
                    html += '</div>';
                    html += '<button type="button" class="btn btn-xs btn-danger" onclick="deleteAttachment(' + attachment.id + ')" style="padding: 1px 4px; font-size: 9px; border-radius: 2px;">×</button>';
                    html += '</div>';
                  });
                  html += '</div>';
                  container.innerHTML = html;
                }
              }
            });
          }
        })
        .catch(error => {
          console.error('Error loading item attachments:', error);
        });
      }
      
      // Helper function to get file icon
      function getFileIcon(extension) {
        if (!extension) return 'fa-file';
        var ext = extension.toLowerCase();
        if (['pdf'].includes(ext)) return 'fa-file-pdf-o';
        else if (['doc', 'docx'].includes(ext)) return 'fa-file-word-o';
        else if (['xls', 'xlsx'].includes(ext)) return 'fa-file-excel-o';
        else if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) return 'fa-file-image-o';
        else return 'fa-file';
      }
      
      // Helper function to format file size
      function formatFileSize(bytes) {
        if (bytes >= 1024 * 1024) {
          return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
        } else if (bytes >= 1024) {
          return (bytes / 1024).toFixed(2) + ' KB';
        } else {
          return bytes + ' bytes';
        }
      }
      
      // Function to update the debug panel on the page
      // function updateDebugPanel() {
      //   var debugPanel = document.getElementById('debug-panel');
      //   if (!debugPanel) return;
      //   
      //   var debugInfo = '<div style="color: #007bff; font-weight: bold; margin-bottom: 10px;">=== DEBUG INFORMATION ===</div>';
      //   
      //   // Show stored files
      //   debugInfo += '<div style="color: #28a745; font-weight: bold; margin: 10px 0 5px 0;">STORED FILES:</div>';
      //   var hasStoredFiles = false;
      //   for (var inputId in storedItemFiles) {
      //     hasStoredFiles = true;
      //     debugInfo += '<div style="margin-left: 10px; margin-bottom: 5px;">';
      //     debugInfo += '<strong>Input ID:</strong> ' + inputId + '<br>';
      //     debugInfo += '<strong>Files:</strong> ' + storedItemFiles[inputId].length + '<br>';
      //     for (var i = 0; i < storedItemFiles[inputId].length; i++) {
      //       var file = storedItemFiles[inputId][i];
      //       debugInfo += '&nbsp;&nbsp;- ' + file.name + ' (' + (file.size/1024).toFixed(1) + ' KB)<br>';
      //     }
      //     debugInfo += '</div>';
      //   }
      //   if (!hasStoredFiles) {
      //     debugInfo += '<div style="color: #666; font-style: italic; margin-left: 10px;">No stored files</div>';
      //   }
      //   
      //   // Show actual file inputs
      //   debugInfo += '<div style="color: #dc3545; font-weight: bold; margin: 10px 0 5px 0;">ACTUAL FILE INPUTS:</div>';
      //   var fileInputs = document.querySelectorAll('input[type="file"]');
      //   for (var i = 0; i < fileInputs.length; i++) {
      //     var input = fileInputs[i];
      //     debugInfo += '<div style="margin-left: 10px; margin-bottom: 5px;">';
      //     debugInfo += '<strong>Input ' + i + ':</strong> ' + input.files.length + ' files<br>';
      //     for (var j = 0; j < input.files.length; j++) {
      //       var file = input.files[j];
      //       debugInfo += '&nbsp;&nbsp;- ' + file.name + ' (' + (file.size/1024).toFixed(1) + ' KB)<br>';
      //     }
      //     debugInfo += '</div>';
      //   }
      //   
      //   debugPanel.innerHTML = debugInfo;
      // }
      
      // Function to show debug information in a visible panel
      // function showDebugInfo() {
      //   var debugInfo = '=== DEBUG INFORMATION ===\n\n';
      //   
      //   // Show stored files
      //   debugInfo += 'STORED FILES:\n';
      //   for (var inputId in storedItemFiles) {
      //     debugInfo += 'Input ID: ' + inputId + '\n';
      //     debugInfo += 'Files: ' + storedItemFiles[inputId].length + '\n';
      //     for (var i = 0; i < storedItemFiles[inputId].length; i++) {
      //       var file = storedItemFiles[inputId][i];
      //       debugInfo += '  - ' + file.name + ' (' + (file.size/1024).toFixed(1) + ' KB)\n';
      //     }
      //     debugInfo += '\n';
      //   }
      //   
      //   // Show actual file inputs
      //   debugInfo += 'ACTUAL FILE INPUTS:\n';
      //   var fileInputs = document.querySelectorAll('input[type="file"]');
      //   for (var i = 0; i < fileInputs.length; i++) {
      //     var input = fileInputs[i];
      //     debugInfo += 'Input ' + i + ': ' + input.files.length + ' files\n';
      //     for (var j = 0; j < input.files.length; j++) {
      //       var file = input.files[j];
      //       debugInfo += '  - ' + file.name + ' (' + (file.size/1024).toFixed(1) + ' KB)\n';
      //     }
      //   }
      //   
      //   // Update the debug panel
      //   updateDebugPanel();
      //   
      //   // Show in alert
      //   alert(debugInfo);
      //   
      //   // Also log to console
      //   console.log('=== DEBUG INFORMATION ===');
      //   console.log('Stored files:', storedItemFiles);
      //   console.log('File inputs:', fileInputs);
      // }
      
      // Function to validate file uploads before form submission
      function validateFileUploads() {
        var hasErrors = false;
        var errorMessages = [];
        
        // VISIBLE DEBUG - Show what files are being submitted
        // var submitDebug = 'SUBMITTING FILES:\n\n';
        var fileInputs = document.querySelectorAll('input[type="file"]');
        // submitDebug += 'Total file inputs: ' + fileInputs.length + '\n\n';
        
        var totalFiles = 0;
        fileInputs.forEach(function(input, index) {
          // submitDebug += 'Input ' + index + ': ' + input.files.length + ' files\n';
          if (input.files && input.files.length > 0) {
            for (var i = 0; i < input.files.length; i++) {
              var file = input.files[i];
              // submitDebug += '  - ' + file.name + ' (' + (file.size/1024).toFixed(1) + ' KB)\n';
              totalFiles++;
              
              // Check file size (200MB limit)
              if (file.size > 200 * 1024 * 1024) {
                hasErrors = true;
                errorMessages.push('File "' + file.name + '" exceeds 200MB size limit');
              }
              
              // Check file type
              var allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif'];
              var extension = file.name.split('.').pop().toLowerCase();
              if (!allowedTypes.includes(extension)) {
                hasErrors = true;
                errorMessages.push('File "' + file.name + '" has an unsupported file type');
              }
            }
          }
        });
        
        // submitDebug += '\nTOTAL FILES TO SUBMIT: ' + totalFiles;
        // alert(submitDebug);
        
        // Populate the hidden field with item file counts
        var itemFileCounts = [];
        fileInputs.forEach(function(input, index) {
          if (input.id && input.id.startsWith('item-')) {
            var itemIndex = parseInt(input.id.split('-')[1]);
            itemFileCounts[itemIndex] = input.files.length;
          }
        });
        
        // Set the hidden field value
        document.getElementById('item_file_counts').value = JSON.stringify(itemFileCounts);
        // console.log('DEBUG: Item file counts:', itemFileCounts);
        
        // Debug: Log all file inputs and their files
        // console.log('DEBUG: Validating file uploads...');
        // console.log('DEBUG: Found ' + fileInputs.length + ' file inputs');
        
        // Check all file inputs for size and type validation
        fileInputs.forEach(function(input, index) {
          // console.log('DEBUG: File input ' + index + ' has ' + input.files.length + ' files');
          if (input.files && input.files.length > 0) {
            for (var i = 0; i < input.files.length; i++) {
              var file = input.files[i];
              // console.log('DEBUG: File ' + i + ': ' + file.name + ' (' + file.size + ' bytes)');
            }
          }
        });
        
        if (hasErrors) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              title: 'File Upload Errors',
              html: errorMessages.join('<br>'),
              icon: 'error',
              confirmButtonText: 'OK'
            });
          } else {
            alert('File Upload Errors:\n' + errorMessages.join('\n'));
          }
          return false;
        }
        
        return true;
      }
      
      // Function to delete attachment
      function deleteAttachment(attachmentId) {
        if (!confirm('Are you sure you want to delete this attachment?')) return;
        
        fetch('./delete_attachment.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'attachment_id=' + attachmentId + '&csrf_token=' + encodeURIComponent(document.querySelector('input[name="csrf_token"]').value)
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Reload attachments
            var docNumber = document.querySelector('input[name="doc_number"]')?.value;
            if (docNumber) {
              loadExistingAttachments(docNumber);
            }
            // Show success message
            if (typeof Swal !== 'undefined') {
              Swal.fire('Success', 'Attachment deleted successfully', 'success');
            } else {
              alert('Attachment deleted successfully');
            }
          } else {
            if (typeof Swal !== 'undefined') {
              Swal.fire('Error', data.message || 'Failed to delete attachment', 'error');
            } else {
              alert('Error: ' + (data.message || 'Failed to delete attachment'));
            }
          }
        })
        .catch(error => {
          console.error('Error deleting attachment:', error);
          if (typeof Swal !== 'undefined') {
            Swal.fire('Error', 'Failed to delete attachment', 'error');
          } else {
            alert('Error: Failed to delete attachment');
          }
        });
      }
    </script>

  </body>
</html>
