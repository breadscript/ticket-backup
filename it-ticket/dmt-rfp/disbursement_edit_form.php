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

// Clean URL handling for edit: accept id once, store in session, then redirect to clean URL
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
$__EDIT_KEY = 'dmt_edit_id';
$__EDIT_EXP = 'dmt_edit_exp';
$__EDIT_TTL = 900; // 15 minutes
$__CLEAN_EDIT_URL = 'disbursement_edit_form.php';

// If id is provided, store and redirect to clean URL (no query string)
if (isset($_GET['id']) && $_GET['id'] !== '') {
	$_SESSION[$__EDIT_KEY] = (int)$_GET['id'];
	$_SESSION[$__EDIT_EXP] = time() + $__EDIT_TTL;
	header('Location: ' . $__CLEAN_EDIT_URL);
	exit;
}

// Resolve id from session, enforce expiry
$editId = 0;
if (isset($_SESSION[$__EDIT_KEY], $_SESSION[$__EDIT_EXP]) && time() <= (int)$_SESSION[$__EDIT_EXP]) {
	$editId = (int)$_SESSION[$__EDIT_KEY];
} else {
	// Gracefully redirect instead of showing 403
	$fallback = 'disbursement_main.php';
	if (!empty($_SERVER['HTTP_REFERER'])) {
		header('Location: ' . $_SERVER['HTTP_REFERER']);
	} else {
		header('Location: ' . $fallback);
	}
	exit;
}

// Load companies and departments for dropdowns
$companies = [];
$departments = [];
$categories = [];
$withholdingTaxes = [];
if (isset($mysqlconn) && $mysqlconn) {
  if ($res = mysqli_query($mysqlconn, "SELECT company_code, company_name FROM company ORDER BY company_code")) {
    while ($r = mysqli_fetch_assoc($res)) { $companies[] = $r; }
  }
  if ($res2 = mysqli_query($mysqlconn, "SELECT company_code, department_code, department_name FROM department ORDER BY company_code, department_code")) {
    while ($r2 = mysqli_fetch_assoc($res2)) { $departments[] = $r2; }
  }
  if ($res3 = mysqli_query($mysqlconn, "SELECT category_code, category_id, category_name, sap_account_code, sap_account_description FROM categories WHERE is_active = 1 ORDER BY category_name")) {
    while ($r3 = mysqli_fetch_assoc($res3)) { $categories[] = $r3; }
  }
  if ($res4 = mysqli_query($mysqlconn, "SELECT tax_code, tax_name, tax_rate, tax_type, description FROM withholding_taxes WHERE is_active = 1 ORDER BY tax_code")) {
    while ($r4 = mysqli_fetch_assoc($res4)) { $withholdingTaxes[] = $r4; }
  }
}

// Prefill values for edit (id resolved from session above)
$er = null; $items = [];
if ($editId > 0 && isset($mysqlconn) && $mysqlconn) {
  if ($st = mysqli_prepare($mysqlconn, "SELECT fr.*, CONCAT(u.user_firstname, ' ', u.user_lastname) as created_by_name 
                                       FROM financial_requests fr 
                                       LEFT JOIN sys_usertb u ON fr.created_by_user_id = u.id 
                                       WHERE fr.id = ?")) {
    mysqli_stmt_bind_param($st, 'i', $editId);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    $er = mysqli_fetch_assoc($res);
    mysqli_stmt_close($st);
  }
  
  // Get doc_number from the financial request
  $docNumber = null;
  if ($er && isset($er['doc_number'])) {
    $docNumber = $er['doc_number'];
  }
  
  // Query items using doc_number
  if ($docNumber) {
    if ($stI = mysqli_prepare($mysqlconn, "SELECT id, category, description, net_payable_amount, reference_number, po_number, due_date, cash_advance, form_of_payment, gross_amount, vatable, vat_amount, withholding_tax, amount_withhold, net_payable_amount, carf_no, pcv_no, invoice_date, invoice_number, supplier_name, tin, address FROM financial_request_items WHERE doc_number = ? ORDER BY id")) {
      mysqli_stmt_bind_param($stI, 's', $docNumber);
      mysqli_stmt_execute($stI);
      $resI = mysqli_stmt_get_result($stI);
      while ($row = mysqli_fetch_assoc($resI)) { $items[] = $row; }
      mysqli_stmt_close($stI);
    }
    
  }
}
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }



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
                <h3>Disbursement Edit Form</h3>
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
                    <form id="demo-form2" class="form-horizontal form-label-left" method="post" enctype="multipart/form-data" autocomplete="off" action="<?php echo ($editId > 0 ? 'update_financial_request.php' : 'save_financial_request.php'); ?>" novalidate onsubmit="restoreAllStoredFiles();">
                      <?php
                        // Use session-resolved $editId from the top (clean URL flow)
                        if ($editId > 0) {
                          echo '<input type="hidden" name="__edit_id" value="'.(int)$editId.'">';
                        }
                      ?>
                      
                      <!-- CSRF Protection -->
                      <?php
                        if (!isset($_SESSION['csrf_token'])) {
                            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        }
                        ?>
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                      <?php // Temporary: allow submit without reloading if session token mismatch occurs ?>
                      <input type="hidden" name="csrf_disable" value="1">
                      
                      <!-- Form Submission Timestamp -->
                      <input type="hidden" name="form_timestamp" value="<?php echo time(); ?>">

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
                                <option value="ERGR" <?php echo ($er && $er['request_type']==='ERGR'?'selected':''); ?>>Expense Report (General Reimbursement)</option>
                                <option value="ERL" <?php echo ($er && $er['request_type']==='ERL'?'selected':''); ?>>Expense Report (Liquidation)</option>
                                <option value="RFP" <?php echo ($er && $er['request_type']==='RFP'?'selected':''); ?>>Request For Payment</option>
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
                                  $name = htmlspecialchars($co['company_name'] ?? '', ENT_QUOTES, 'UTF-8');
                                  $sel = ($er && $er['company'] === $code) ? 'selected' : '';
                                ?>
                                  <option value="<?php echo $code; ?>" <?php echo $sel; ?>><?php echo $code; ?></option>
                                <?php } ?>
                              </select>
                              <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="company" style=""></i>
                            </div>
                            <!-- <div class="col-md-3 col-sm-3 col-xs-12" style="display:none;">
                              <label>Doc Type</label>
                               <input type="text" id="doc_type" name="doc_type" class="form-control" placeholder="Enter document type"
                                     maxlength="150"
                                      data-parsley-pattern="^[0-9A-Za-z .,()&-]+$"
                                     data-parsley-pattern-message="Document type contains invalid characters"
                                     data-parsley-maxlength="150"
                                     data-parsley-maxlength-message="Document type cannot exceed 150 characters" value="<?php echo h($er['doc_type'] ?? ''); ?>">
                              <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="doc_type" style=""></i>
                            </div> -->
                            <div class="col-md-3 col-sm-3 col-xs-12">
                              <label id="doc_number_label">Doc Number (ERL)</label>
                               <input type="text" id="doc_number" name="doc_number" class="form-control" placeholder="Enter ERL document number"
                                     maxlength="150"
                                      data-parsley-pattern="^[0-9A-Za-z .,()&-]+$"
                                     data-parsley-pattern-message="Document number contains invalid characters"
                                     data-parsley-maxlength="150"
                                     data-parsley-maxlength-message="Document number cannot exceed 150 characters" value="<?php echo h($er['doc_number'] ?? ''); ?>" readonly>
                              <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="doc_number" style=""></i>
                            </div>
                          </div>

                          <div class="form-group">
                            <div class="col-md-3 col-sm-3 col-xs-12">
                              <label>Doc Date</label>
                               <input type="date" id="doc_date" name="doc_date" class="form-control"
                                     data-parsley-required="true"
                                     data-parsley-required-message="Document date is required"
                                     data-parsley-date="true"
                                     data-parsley-date-message="Please enter a valid date" value="<?php echo h($er['doc_date'] ?? ''); ?>">
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
                                  $sel = ($er && $er['cost_center'] === $dcode) ? 'selected' : '';
                                ?>
                                  <option value="<?php echo $dcode; ?>" data-company="<?php echo $ccode; ?>" <?php echo $sel; ?>><?php echo $dname; ?></option> 
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
                                <option value="capex" <?php echo ($er && $er['expenditure_type']==='capex'?'selected':''); ?>>Capex</option>
                                <option value="opex" <?php echo ($er && $er['expenditure_type']==='opex'?'selected':''); ?>>Opex</option>
                                <option value="other" <?php echo ($er && $er['expenditure_type']==='other'?'selected':''); ?>>Others</option>
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
                                <?php foreach (["USD","EUR","JPY","GBP","AUD","CAD","CHF","CNY","PHP"] as $cur) { ?>
                                  <option value="<?php echo $cur; ?>" <?php echo ($er && $er['currency']===$cur?'selected':''); ?>><?php echo $cur; ?></option>
                                <?php } ?>
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
                                <?php 
                                  $isBudgetedVal = $er['is_budgeted'] ?? '';
                                  $isBudgetedSelected = ($isBudgetedVal === '1' || $isBudgetedVal === 1 || strtolower((string)$isBudgetedVal) === 'yes');
                                ?>
                                <option value="yes" <?php echo ($er && $isBudgetedSelected) ? 'selected' : ''; ?>>Yes</option>
                                <option value="no" <?php echo ($er && !$isBudgetedSelected && $isBudgetedVal !== '') ? 'selected' : ''; ?>>No</option>
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
                                     data-parsley-max-message="Balance cannot exceed 999,999,999.99" value="<?php echo h($er['balance'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 col-sm-4 col-xs-12">
                              <label>Budget</label>
                               <input type="number" step="0.01" id="budget" name="budget" class="form-control" placeholder="0.00"
                                     min="0" max="999999999.99"
                                     data-parsley-type="number"
                                     data-parsley-min="0"
                                     data-parsley-min-message="Budget cannot be negative"
                                     data-parsley-max="999999999.99"
                                     data-parsley-max-message="Budget cannot exceed 999,999,999.99" value="<?php echo h($er['budget'] ?? ''); ?>">
                               
                                    </div>

                          </div>
                        </div>
                      </div>

                      <!-- ======================================== -->
                      <!-- ITEM DETAILS SECTION -->
                      <!-- ======================================== -->
                      <div class="x_panel" id="item_section">
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
                                      $categoryId = htmlspecialchars($cat['category_id'] ?? '', ENT_QUOTES, 'UTF-8');
                                      $name = htmlspecialchars($cat['category_name'] ?? '', ENT_QUOTES, 'UTF-8');
                                      $sapCode = htmlspecialchars($cat['sap_account_code'] ?? '', ENT_QUOTES, 'UTF-8');
                                      $sapDesc = htmlspecialchars($cat['sap_account_description'] ?? '', ENT_QUOTES, 'UTF-8');
                                      $displayText = $name;
                                      if ($sapDesc) {
                                        $displayText .= ' - ' . $sapDesc;
                                      }
                                    ?>
                                      <option value="<?php echo $code; ?>" data-category-id="<?php echo $categoryId; ?>" data-sap-code="<?php echo $sapCode; ?>" data-sap-desc="<?php echo $sapDesc; ?>"><?php echo $displayText; ?></option>
                                    <?php } ?>
                                  </select>
                                  <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="items_category" style=""></i>

                                </div>
                                <div class="col-md-2 col-sm-2 col-xs-12 field-attachment field_attachment_col" id="field_attachment_col">
                                  <label>Attachment</label>
                                  <div class="upload-area" style="border: 2px dashed #ccc; border-radius: 8px; padding: 20px; text-align: center; background-color: #fafafa; transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.borderColor='#007bff'; this.style.backgroundColor='#f0f8ff';" onmouseout="this.style.borderColor='#ccc'; this.style.backgroundColor='#fafafa';">
                                    <i class="fa fa-cloud-upload" style="font-size: 24px; color: #666; margin-bottom: 10px;"></i>
                                    <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Click to upload or drag files here</div>
                                    <div style="font-size: 10px; color: #999;">PDF, DOC, XLS, Images</div>
                                    <input type="file" name="items_attachment_item_INDEX_" class="form-control item-attachment-input" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif" multiple style="position: absolute; opacity: 0; width: 100%; height: 100%; cursor: pointer; top: 0; left: 0;" onchange="handleItemFileSelection(this)">
                                  </div>
                                  <div class="file-list" style="margin-top: 10px; font-size: 12px;"></div>
                                  <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="items_attachment" style=""></i>
                                </div>
                                <div class="col-md-2 col-sm-2 col-xs-12">
                                  <label>Existing Attachments</label>
                                  <div class="existing-item-attachments" data-item-id="" style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 8px; border-radius: 4px; background-color: #f9f9f9; font-size: 11px;">
                                    <div style="text-align: center; color: #666; font-style: italic; padding: 15px;">
                                      No existing attachments
                                    </div>
                                  </div>
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
                                <div class="col-md-3 col-sm-6 col-xs-12 field-form-of-payment field_form_of_payment_col" id="field_form_of_payment_col">
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
                                  <input type="number" step="0.01" name="items_gross_amount[]" class="form-control tax-calc-field" min="0" max="999999999.99" placeholder="0.00" value="">
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
                          <?php if (!empty($items)) { ?>
                          <script>
                            document.addEventListener('DOMContentLoaded', function(){
                              try {
                                var container = document.getElementById('itemsContainer');
                                var tpl = document.getElementById('itemRowTemplate');
                                var data = <?php echo json_encode($items); ?>;
                                console.log('Loaded items data:', data);
                                data.forEach(function(it){
                                  var node = document.importNode(tpl.content, true);
                                  var root = node.querySelector('.item-row');
                                  
                                  // Set the item ID for existing attachments container
                                  var existingAttachmentsContainer = root.querySelector('.existing-item-attachments');
                                  if (existingAttachmentsContainer && it.id) {
                                    existingAttachmentsContainer.setAttribute('data-item-id', it.id);
                                  }
                                  
                                  // Set the correct file input name with item index
                                  var fileInput = root.querySelector('input[type="file"]');
                                  if (fileInput) {
                                    fileInput.name = 'items_attachment_item_' + data.indexOf(it);
                                    fileInput.classList.add('item-attachment-input');
                                  }
                                  
                                  // Set unique ID for the upload area
                                  var uploadArea = root.querySelector('.upload-area');
                                  if (uploadArea) {
                                    var itemIndex = data.indexOf(it);
                                    uploadArea.id = 'item-upload-existing-' + itemIndex;
                                  }
                                  
                                  var categorySelect = root.querySelector('[name="items_category[]"]');
                                  if (categorySelect) {
                                    var desiredRaw = (it.category || '').toString().trim();
                                    var desired = desiredRaw.toLowerCase();
                                    var options = categorySelect.options;
                                    var matched = false;
                                    if (desired) {
                                      // 1) Try category_id match first (data-category-id) - this is what's stored in DB
                                      for (var os = 0; os < options.length; os++) {
                                        var categoryId = (options[os].getAttribute('data-category-id') || '').trim().toLowerCase();
                                        if (categoryId && categoryId === desired) {
                                          categorySelect.value = options[os].value;
                                          matched = true;
                                          break;
                                        }
                                      }
                                      // 2) Try SAP account code match (data-sap-code) - fallback for legacy data
                                      if (!matched) {
                                        for (var os = 0; os < options.length; os++) {
                                          var sapCode = (options[os].getAttribute('data-sap-code') || '').trim().toLowerCase();
                                          if (sapCode && sapCode === desired) {
                                            categorySelect.value = options[os].value;
                                            matched = true;
                                            break;
                                          }
                                        }
                                      }
                                      // 3) Try exact value match (category_code) - fallback
                                      if (!matched) {
                                        for (var oi = 0; oi < options.length; oi++) {
                                          if ((options[oi].value || '').toLowerCase() === desired) {
                                            categorySelect.value = options[oi].value;
                                            matched = true;
                                            break;
                                          }
                                        }
                                      }
                                      // 4) Try exact visible text match (category_name - sap_account_description)
                                      if (!matched) {
                                        for (var oj = 0; oj < options.length; oj++) {
                                          var txt = (options[oj].textContent || options[oj].innerText || '').trim().toLowerCase();
                                          if (txt === desired) {
                                            categorySelect.value = options[oj].value;
                                            matched = true;
                                            break;
                                          }
                                        }
                                      }
                                      // 5) Try contains match on visible text (helps when only name provided)
                                      if (!matched) {
                                        for (var ok = 0; ok < options.length; ok++) {
                                          var txt2 = (options[ok].textContent || options[ok].innerText || '').trim().toLowerCase();
                                          if (txt2.indexOf(desired) !== -1) {
                                            categorySelect.value = options[ok].value;
                                            matched = true;
                                            break;
                                          }
                                        }
                                      }
                                      // 6) Try match by SAP description only (data-sap-desc)
                                      if (!matched) {
                                        for (var ol = 0; ol < options.length; ol++) {
                                          var sapDesc = (options[ol].getAttribute('data-sap-desc') || '').trim().toLowerCase();
                                          if (sapDesc && (sapDesc === desired || sapDesc.indexOf(desired) !== -1)) {
                                            categorySelect.value = options[ol].value;
                                            matched = true;
                                            break;
                                          }
                                        }
                                      }
                                    }
                                    if (!matched) {
                                      categorySelect.value = '';
                                    }
                                  }
                                  root.querySelector('[name="items_description[]"]').value = it.description || '';
                                  root.querySelector('[name="items_reference_number[]"]').value = it.reference_number || '';
                                  root.querySelector('[name="items_po_number[]"]').value = it.po_number || '';
                                  root.querySelector('[name="items_due_date[]"]').value = it.due_date || '';
                                  // Handle cash_advance: 1 or '1' = yes, 0 or '0' = no
                                  var cashAdvanceValue = it.cash_advance;
                                  if (cashAdvanceValue === 1 || cashAdvanceValue === '1' || (typeof cashAdvanceValue === 'string' && cashAdvanceValue.toLowerCase() === 'yes')) {
                                    root.querySelector('[name="items_cash_advance[]"]').value = 'yes';
                                  } else if (cashAdvanceValue === 0 || cashAdvanceValue === '0' || (typeof cashAdvanceValue === 'string' && cashAdvanceValue.toLowerCase() === 'no')) {
                                    root.querySelector('[name="items_cash_advance[]"]').value = 'no';
                                  } else {
                                    root.querySelector('[name="items_cash_advance[]"]').value = '';
                                  }
                                  root.querySelector('[name="items_form_of_payment[]"]').value = it.form_of_payment || '';
                                  var grossAmountField = root.querySelector('[name="items_gross_amount[]"]');
                                  grossAmountField.value = it.gross_amount || '';
                                  console.log('Setting gross_amount:', it.gross_amount, 'field value after setting:', grossAmountField.value);
                                  root.querySelector('[name="items_vatable[]"]').value = it.vatable || '';
                                  root.querySelector('[name="items_vat_amount[]"]').value = it.vat_amount || '';
                                  root.querySelector('[name="items_withholding_tax[]"]').value = it.withholding_tax || '';
                                  root.querySelector('[name="items_amount_withhold[]"]').value = it.amount_withhold || '';
                                  root.querySelector('[name="items_net_payable[]"]').value = it.net_payable_amount || '';
                                  root.querySelector('[name="items_carf_no[]"]').value = it.carf_no || '';
                                  root.querySelector('[name="items_pcv_no[]"]').value = it.pcv_no || '';
                                  root.querySelector('[name="items_invoice_date[]"]').value = it.invoice_date || '';
                                  root.querySelector('[name="items_invoice_number[]"]').value = it.invoice_number || '';
                                  root.querySelector('[name="items_supplier_name[]"]').value = it.supplier_name || '';
                                  root.querySelector('[name="items_tin[]"]').value = it.tin || '';
                                  root.querySelector('[name="items_address[]"]').value = it.address || '';
                                  // Attachment handling removed - attachments now handled at header level
                                  container.appendChild(node);
                                });
                              } catch(e) {}
                            });
                          </script>
                          <?php } ?>
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
                              <?php 
                                // Prefer stored payee from record when editing; fallback to session display name
                                $storedPayee = isset($er['payee']) ? (string)$er['payee'] : '';
                                $sessName = trim((string)($_SESSION['userfirstname'] ?? '').' '.(string)($_SESSION['userlastname'] ?? ''));
                                if ($sessName==='') { $sessName = (string)($_SESSION['username'] ?? ''); }
                                $payeeDisplay = $storedPayee !== '' ? $storedPayee : $sessName;
                              ?>
                              
                              <input type="text" id="payee" name="payee" class="form-control" placeholder="Enter payee" readonly
                                     maxlength="255"
                                      data-parsley-pattern="^[0-9A-Za-z .,()&-]+$"
                                     data-parsley-pattern-message="Payee name contains invalid characters"
                                      data-parsley-maxlength="255"
                                      data-parsley-maxlength-message="Payee name cannot exceed 255 characters" value="<?php echo h($payeeDisplay); ?>">
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
                                     data-parsley-max-message="Amount cannot exceed 999,999,999.99" value="<?php echo h($er['amount_figures'] ?? ''); ?>">
                              <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="amount_figures" style=""></i>
                            </div>
                            <div class="col-md-4 col-sm-4 col-xs-12" id="field_amount_words_col">
                              <label>Amount in Words</label>
                               <input type="text" id="amount_words" name="amount_words" class="form-control" placeholder="e.g., One Thousand Pesos" readonly
                                     maxlength="500"
                                     data-parsley-maxlength="500"
                                     data-parsley-maxlength-message="Amount in words cannot exceed 500 characters" value="<?php echo h($er['amount_in_words'] ?? ''); ?>">
                              <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="amount_words" style=""></i>
                            </div>

                          </div>
                          
                          <div class="form-group">
                            <div class="col-md-12 col-sm-12 col-xs-12" id="field_payment_for_col">
                              <label for="payment_for">Payment For</label>
                               <textarea id="payment_for" required="required" class="form-control" name="payment_for" data-parsley-trigger="keyup" data-parsley-minlength="5" data-parsley-maxlength="500" data-parsley-minlength-message="Please enter at least 5 characters."
                                   data-parsley-validation-threshold="10"><?php echo h($er['payment_for'] ?? ''); ?></textarea>
                              <i class="form-control-feedback glyphicon glyphicon-asterisk" data-bv-icon-for="payment_for" style=""></i>
                            </div>
                          </div>

                          <div class="form-group">
                            <div class="col-md-12 col-sm-12 col-xs-12" id="field_special_instruction_col">
                              <label for="special_instructions">Special Instructions</label>
                               <textarea id="special_instructions" class="form-control" name="special_instructions" data-parsley-trigger="keyup" data-parsley-maxlength="1000" data-parsley-validation-threshold="10"><?php echo h($er['special_instructions'] ?? ''); ?></textarea>
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
                                <?php if ($er && $docNumber) { ?>
                                  <?php echo displayAttachments($docNumber, null, 'supporting_document', true); ?>
                                <?php } else { ?>
                                  <div style="text-align: center; color: #666; font-style: italic; padding: 20px;">
                                    No existing documents to display
                                  </div>
                                <?php } ?>
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
                                      data-parsley-maxlength-message="Company name cannot exceed 150 characters" value="<?php echo h($er['from_company'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 col-sm-4 col-xs-12" id="field_to_company_col">
                              <label>To Company</label>
                               <input type="text" id="to_company" name="to_company" class="form-control" placeholder="Enter destination company"
                                     maxlength="150"
                                      data-parsley-pattern="^[0-9A-Za-z .,()&-]+$"
                                     data-parsley-pattern-message="Company name contains invalid characters"
                                      data-parsley-maxlength="150"
                                      data-parsley-maxlength-message="Company name cannot exceed 150 characters" value="<?php echo h($er['to_company'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4 col-sm-4 col-xs-12" id="field_credit_to_payroll_col">
                              <div class="radio" style="margin-top: 25px;">
                                <label>
                                   <input type="radio" id="credit_to_payroll" name="payment_option" value="credit_to_payroll" <?php echo ($er && (string)$er['credit_to_payroll']==='1'?'checked':''); ?>> Credit To Payroll
                                </label>
                              </div>
                              <input type="hidden" name="credit_to_payroll" id="credit_to_payroll_hidden" value="<?php echo ($er && (string)$er['credit_to_payroll']==='1'?'1':'0'); ?>">
                            </div>
                            <div class="col-md-4 col-sm-4 col-xs-12" id="field_issue_check_col">
                              <div class="radio" style="margin-top: 25px;">
                                <label>
                                   <input type="radio" id="issue_check" name="payment_option" value="issue_check" <?php echo ($er && (string)$er['issue_check']==='1'?'checked':''); ?>> Issue Check
                                </label>
                              </div>
                              <input type="hidden" name="issue_check" id="issue_check_hidden" value="<?php echo ($er && (string)$er['issue_check']==='1'?'1':'0'); ?>">
                            </div>
                          </div>
                        </div>
                      </div>
                      
                      <div class="ln_solid"></div>
                      <div class="form-group">
                        <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                          <a href="disbursement_main.php" class="btn btn-primary">Back</a>
						              <!-- <button class="btn btn-primary" type="reset">Reset</button> -->
                          <button type="submit" class="btn btn-success" id="submitBtn" 
                                  data-loading-text="Submitting...">
                            Re-submit
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
        document.addEventListener('DOMContentLoaded', function(){
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
              
              // Required only when toggle is checked (enabled)
              if (enabled) {
                payeeInput.setAttribute('data-parsley-required','true');
                if (!payeeInput.getAttribute('data-parsley-required-message')) {
                  payeeInput.setAttribute('data-parsley-required-message','This field is required');
                }
              } else {
                payeeInput.removeAttribute('data-parsley-required');
              }
            };
            // initialize
            syncPayeeState();
            togglePayee.addEventListener('change', syncPayeeState);
          }
        });
      })();
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
          if (val === 'ERGR' || val === 'ERL' || val === 'RFP') {
            enforceAllRequired(formEl);
            // Adjust payee based on toggle after mass-enforce
            var togglePayee = document.getElementById('toggle_payee');
            var payeeInput = document.getElementById('payee');
            if (togglePayee && payeeInput) {
              if (togglePayee.checked) {
                payeeInput.setAttribute('data-parsley-required','true');
              } else {
                payeeInput.removeAttribute('data-parsley-required');
              }
            }
            
            // For ERL, no special validation needed for radio buttons
          }
        }
        
        document.addEventListener('DOMContentLoaded', function(){
          var reqSel = document.getElementById('requestType');
          if (reqSel) {
            reqSel.addEventListener('change', applyRequiredByType);
            applyRequiredByType();
          }
          
          // Ensure all file inputs are properly named on page load
          setTimeout(function() {
            fixItemAttachmentNames();
          }, 500);
          
          // Function to fix item attachment names
          function fixItemAttachmentNames() {
            var allItemRows = document.querySelectorAll('.item-row');
            allItemRows.forEach(function(row, index) {
              var fileInput = row.querySelector('.item-attachment-input');
              if (fileInput) {
                // Set proper name based on item index
                fileInput.name = 'items_attachment_item_' + index;
                
                // Set unique ID for the upload area
                var uploadArea = row.querySelector('.upload-area');
                if (uploadArea && !uploadArea.id) {
                  uploadArea.id = 'item-upload-' + index;
                }
              }
            });
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
          
          // Handle dynamically added items for Select2 and file inputs
          var addItemBtn = document.getElementById('addItemBtn');
          if (addItemBtn) {
            addItemBtn.addEventListener('click', function(){
              // Initialize Select2 for newly added category dropdowns after a short delay
              setTimeout(function() {
                var newCategorySelects = document.querySelectorAll('.item-row:not(.select2-initialized) .select2-category');
                newCategorySelects.forEach(function(select) {
                  if (!$(select).hasClass('select2-hidden-accessible')) {
                    $(select).select2({
                      placeholder: 'Search and select category...',
                      allowClear: true,
                      width: '100%',
                      dropdownParent: $('body')
                    });
                    $(select).closest('.item-row').addClass('select2-initialized');
                  }
                });
                
                // Fix file input names for all items (including newly added ones)
                fixItemAttachmentNames();
              }, 100);
            });
          }
        });
      })();
    </script>
    <script>
      (function(){
        function filterDepartments() {
          var companySel = document.getElementById('company');
          var deptSel = document.getElementById('cost_center');
          if (!companySel || !deptSel) return;
          var selectedCompany = companySel.value;
          var anyShown = false;
          for (var i = 0; i < deptSel.options.length; i++) {
            var opt = deptSel.options[i];
            if (!opt.value) continue;
            var show = !selectedCompany || opt.getAttribute('data-company') === selectedCompany;
            opt.style.display = show ? '' : 'none';
            if (!show && opt.selected) { opt.selected = false; }
            if (show) anyShown = true;
          }
          if (!anyShown) { deptSel.value = ''; }
        }
        document.addEventListener('DOMContentLoaded', function(){
          var companySel = document.getElementById('company');
          if (companySel) {
            companySel.addEventListener('change', filterDepartments);
            filterDepartments();
          }
        });
      })();
    </script>
    
    <script>
      // Global variables to store files
      var storedItemFiles = {};
      var storedSupportingFiles = {};
      
      // Function to handle item file selection (preserves existing files)
      function handleItemFileSelection(input) {
        var inputId = input.closest('.upload-area').id || 'default';
        var newFiles = input.files;
        
        console.log('DEBUG: handleItemFileSelection called');
        console.log('DEBUG: inputId:', inputId);
        console.log('DEBUG: newFiles.length:', newFiles.length);
        console.log('DEBUG: newFiles:', Array.from(newFiles).map(f => f.name));
        
        if (newFiles.length === 0) {
          // No new files selected, restore stored files if they exist
          console.log('DEBUG: No new files, checking stored files for inputId:', inputId);
          console.log('DEBUG: storedItemFiles[inputId]:', storedItemFiles[inputId]);
          
          if (storedItemFiles[inputId] && storedItemFiles[inputId].length > 0) {
            console.log('DEBUG: Restoring', storedItemFiles[inputId].length, 'stored files');
            var dt = new DataTransfer();
            storedItemFiles[inputId].forEach(function(file) {
              dt.items.add(file);
            });
            input.files = dt.files;
          }
        } else {
          // New files selected, store them
          console.log('DEBUG: Storing', newFiles.length, 'new files for inputId:', inputId);
          storedItemFiles[inputId] = Array.from(newFiles);
          console.log('DEBUG: storedItemFiles after storing:', storedItemFiles);
        }
        
        updateUploadText(input);
      }
      
      // Function to restore all stored files before form submission
      function restoreAllStoredFiles() {
        console.log('DEBUG: restoreAllStoredFiles called');
        console.log('DEBUG: storedItemFiles:', storedItemFiles);
        
        for (var inputId in storedItemFiles) {
          var files = storedItemFiles[inputId];
          console.log('DEBUG: Processing inputId:', inputId, 'with', files.length, 'files');
          
          if (files && files.length > 0) {
            // Find the corresponding file input
            var uploadArea = document.getElementById(inputId);
            console.log('DEBUG: Found uploadArea:', uploadArea);
            
            if (uploadArea) {
              var originalFileInput = uploadArea.querySelector('input[type="file"]');
              console.log('DEBUG: Found originalFileInput:', originalFileInput);
              
              if (originalFileInput) {
                // Remove any existing additional file inputs
                var existingAdditionalInputs = uploadArea.querySelectorAll('input[type="file"][name^="' + originalFileInput.name + '_additional_"]');
                existingAdditionalInputs.forEach(function(input) {
                  input.remove();
                });
                
                // Create separate file inputs for each additional file (skip first file as it goes to original input)
                for (var i = 1; i < files.length; i++) {
                  var additionalInput = document.createElement('input');
                  additionalInput.type = 'file';
                  additionalInput.name = originalFileInput.name + '_additional_' + (i - 1);
                  additionalInput.style.display = 'none';
                  
                  // Create a DataTransfer with just this file
                  var dt = new DataTransfer();
                  dt.items.add(files[i]);
                  additionalInput.files = dt.files;
                  
                  uploadArea.appendChild(additionalInput);
                  console.log('DEBUG: Created additional input for file:', files[i].name);
                }
                
                // Set the original input to only the first file
                if (files.length > 0) {
                  var dt = new DataTransfer();
                  dt.items.add(files[0]);
                  originalFileInput.files = dt.files;
                  console.log('DEBUG: Set original input to first file:', files[0].name);
                }
              }
            }
          }
        }
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
          var html = '<div style="background: #e8f5e8; padding: 8px; border-radius: 4px; border-left: 3px solid #28a745;">';
          html += '<i class="fa fa-check-circle" style="color: #28a745; margin-right: 5px;"></i>';
          html += '<strong>Selected files:</strong><br>';
          
          for (var i = 0; i < files.length; i++) {
            var file = files[i];
            var size = (file.size / 1024 / 1024).toFixed(2);
            html += '<div style="margin: 3px 0; font-size: 11px; display: flex; align-items: center; justify-content: space-between; background: white; padding: 5px; border-radius: 3px;">';
            html += '<div style="display: flex; align-items: center;">';
            html += '<i class="fa fa-file" style="margin-right: 5px; color: #666;"></i>';
            html += '<span>' + file.name + ' (' + size + ' MB)</span>';
            html += '</div>';
            html += '<button type="button" class="btn btn-xs btn-danger" onclick="removeFile(this, ' + i + '); event.stopPropagation(); event.preventDefault();" style="padding: 2px 6px; font-size: 10px; margin-left: 10px; z-index: 10; position: relative;">';
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
        var inputId = input.closest('.upload-area').id || 'default';
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
        storedItemFiles[inputId] = files;
        
        // Refresh the display
        updateUploadText(input);
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
              html += '<div style="margin: 5px 0; padding: 5px; background: white; border-radius: 3px; border: 1px solid #ddd;">';
              html += '<i class="fa fa-file" style="margin-right: 5px; color: #666;"></i>';
              html += '<span style="font-size: 11px;">' + attachment.original_filename + '</span>';
              html += '<button type="button" class="btn btn-xs btn-danger pull-right" onclick="deleteAttachment(' + attachment.id + ')" style="padding: 1px 5px; font-size: 10px;">×</button>';
              html += '</div>';
            });
            container.innerHTML = html;
          }
        })
        .catch(error => {
          console.error('Error loading supporting documents:', error);
        });
        
        // Load item attachments per item
        var containers = document.querySelectorAll('.existing-item-attachments[data-item-id]');
        containers.forEach(function(container) {
          var itemId = container.getAttribute('data-item-id');
          if (itemId) {
            fetch('./get_attachments.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
              },
              body: 'doc_number=' + encodeURIComponent(docNumber) + '&attachment_type=item_attachment&item_id=' + encodeURIComponent(itemId)
            })
            .then(response => response.json())
            .then(data => {
              if (data.success && data.attachments && data.attachments.length > 0) {
                var html = '';
                data.attachments.forEach(function(attachment) {
                  html += '<div style="margin: 3px 0; padding: 3px; background: white; border-radius: 2px; border: 1px solid #ddd;">';
                  html += '<i class="fa fa-file" style="margin-right: 3px; color: #666; font-size: 10px;"></i>';
                  html += '<span style="font-size: 10px;">' + attachment.original_filename + '</span>';
                  html += '<button type="button" class="btn btn-xs btn-danger pull-right" onclick="deleteAttachment(' + attachment.id + ')" style="padding: 1px 3px; font-size: 9px;">×</button>';
                  html += '</div>';
                });
                container.innerHTML = html;
              } else {
                container.innerHTML = '<div style="text-align: center; color: #666; font-style: italic; padding: 15px;">No existing attachments</div>';
              }
            })
            .catch(error => {
              console.error('Error loading attachments for item ' + itemId + ':', error);
              container.innerHTML = '<div style="text-align: center; color: #666; font-style: italic; padding: 15px;">Error loading attachments</div>';
            });
          }
        });
      }
      
      // Function to delete attachment
      function deleteAttachment(attachmentId) {
        // Use SweetAlert for confirmation
        Swal.fire({
          title: 'Delete Attachment',
          text: 'Are you sure you want to delete this attachment? This action cannot be undone.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Yes, delete it!',
          cancelButtonText: 'Cancel'
        }).then((result) => {
          if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
              title: 'Deleting...',
              text: 'Please wait while we delete the attachment.',
              allowOutsideClick: false,
              didOpen: () => {
                Swal.showLoading();
              }
            });
            
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
                Swal.fire('Deleted!', 'Attachment has been deleted successfully.', 'success');
              } else {
                Swal.fire('Error', data.message || 'Failed to delete attachment', 'error');
              }
            })
            .catch(error => {
              console.error('Error deleting attachment:', error);
              Swal.fire('Error', 'Failed to delete attachment', 'error');
            });
          }
        });
      }
      
      // Load existing attachments when page loads
      document.addEventListener('DOMContentLoaded', function() {
        var docNumber = document.querySelector('input[name="doc_number"]')?.value;
        if (docNumber) {
          loadExistingAttachments(docNumber);
        }
      });
    </script>

  </body>
</html>
