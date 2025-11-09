<?php 
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d h:i:s",time());	
include "blocks/inc.resource.php";

// Load companies and departments for dropdowns
$companies = [];
$departments = [];
$categories = [];
if (isset($mysqlconn) && $mysqlconn) {
  if ($res = mysqli_query($mysqlconn, "SELECT company_code, company_name FROM company ORDER BY company_code")) {
    while ($r = mysqli_fetch_assoc($res)) { $companies[] = $r; }
  }
  if ($res2 = mysqli_query($mysqlconn, "SELECT company_code, department_code, department_name FROM department ORDER BY company_code, department_code")) {
    while ($r2 = mysqli_fetch_assoc($res2)) { $departments[] = $r2; }
  }
  if ($res3 = mysqli_query($mysqlconn, "SELECT category_code, category_name FROM categories WHERE is_active = 1 ORDER BY category_name")) {
    while ($r3 = mysqli_fetch_assoc($res3)) { $categories[] = $r3; }
  }
}

// Prefill values for edit
$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$er = null; $items = []; $breakdowns = [];
if ($editId > 0 && isset($mysqlconn) && $mysqlconn) {
  if ($st = mysqli_prepare($mysqlconn, "SELECT * FROM financial_requests WHERE id = ?")) {
    mysqli_stmt_bind_param($st, 'i', $editId);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    $er = mysqli_fetch_assoc($res);
    mysqli_stmt_close($st);
  }
  if ($stI = mysqli_prepare($mysqlconn, "SELECT id, category, attachment_path, description, amount, reference_number, po_number, due_date, cash_advance, form_of_payment, budget_consumption FROM financial_request_items WHERE doc_number = ? ORDER BY id")) {
    mysqli_stmt_bind_param($stI, 'i', $editId);
    mysqli_stmt_execute($stI);
    $resI = mysqli_stmt_get_result($stI);
    while ($row = mysqli_fetch_assoc($resI)) { $items[] = $row; }
    mysqli_stmt_close($stI);
  }
  if ($stB = mysqli_prepare($mysqlconn, "SELECT id, reference_number_2, `date`, amount2 FROM financial_request_breakdowns WHERE doc_number = ? ORDER BY id")) {
    mysqli_stmt_bind_param($stB, 'i', $editId);
    mysqli_stmt_execute($stB);
    $resB = mysqli_stmt_get_result($stB);
    while ($row = mysqli_fetch_assoc($resB)) { $breakdowns[] = $row; }
    mysqli_stmt_close($stB);
  }
}
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Ownership enforcement
if ($er && isset($_SESSION['userid']) && (string)$er['payee'] !== (string)$_SESSION['userid']) {
  http_response_code(403);
  echo 'Forbidden: you are not allowed to edit this record.';
  exit;
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
                    - File size limits (10MB max)
                    - Malicious content detection in uploads
                    -->
                    <form id="demo-form2" class="form-horizontal form-label-left" method="post" enctype="multipart/form-data" autocomplete="off" action="<?php echo ($editId > 0 ? 'update_financial_request.php' : 'save_financial_request.php'); ?>" novalidate>
                      <?php
                        // If accessed with ?id=, load existing and switch to update endpoint
                        $editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
                        if ($editId > 0) {
                          echo '<input type="hidden" name="__edit_id" value="'.(int)$editId.'">';
                          echo '<script>document.addEventListener("DOMContentLoaded",function(){ var f=document.getElementById("demo-form2"); if(f){ f.setAttribute("action","update_financial_request.php"); }});</script>';
                        }
                      ?>
                      
                      <!-- CSRF Protection -->
                      <?php
                        if (!isset($_SESSION['csrf_token'])) {
                            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        }
                        ?>
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                      
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
                            </div>
                            <div class="col-md-3 col-sm-3 col-xs-12">
                              <label>Doc Type</label>
                               <input type="text" id="doc_type" name="doc_type" class="form-control" placeholder="Enter document type"
                                     maxlength="150"
                                      data-parsley-pattern="^[0-9A-Za-z .,()&-]+$"
                                     data-parsley-pattern-message="Document type contains invalid characters"
                                     data-parsley-maxlength="150"
                                     data-parsley-maxlength-message="Document type cannot exceed 150 characters" value="<?php echo h($er['doc_type'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3 col-sm-3 col-xs-12">
                              <label id="doc_number_label">Doc Number (ERL)</label>
                               <input type="text" id="doc_number" name="doc_number" class="form-control" placeholder="Enter ERL document number"
                                     maxlength="150"
                                      data-parsley-pattern="^[0-9A-Za-z .,()&-]+$"
                                     data-parsley-pattern-message="Document number contains invalid characters"
                                     data-parsley-maxlength="150"
                                     data-parsley-maxlength-message="Document number cannot exceed 150 characters" value="<?php echo h($er['doc_number'] ?? ''); ?>" readonly>
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
                                  <option value="<?php echo $dcode; ?>" data-company="<?php echo $ccode; ?>" <?php echo $sel; ?>><?php echo $dcode; ?></option> 
                                <?php } ?>
                              </select>
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
                            </div>
                          </div>

                          <div class="form-group">
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
                                  <select name="items_category[]" class="form-control">
                                    <option value="">Choose..</option>
                                    <?php foreach ($categories as $cat) { 
                                      $code = htmlspecialchars($cat['category_code'] ?? '', ENT_QUOTES, 'UTF-8');
                                      $name = htmlspecialchars($cat['category_name'] ?? '', ENT_QUOTES, 'UTF-8');
                                    ?>
                                      <option value="<?php echo $code; ?>"><?php echo $name; ?></option>
                                    <?php } ?>
                                  </select>
                                </div>
                                <div class="col-md-3 col-sm-3 col-xs-12 field-attachment field_attachment_col" id="field_attachment_col">
                                  <label>Attachment</label>
                                  <input type="file" name="items_attachment[]" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
                                </div>
                                <div class="col-md-3 col-sm-3 col-xs-12 field-description field_description_col" id="field_description_col">
                                  <label>Description</label>
                                  <textarea name="items_description[]" class="form-control" rows="2" maxlength="1000"></textarea>
                                </div>
                                <div class="col-md-3 col-sm-3 col-xs-12 field-amount field_amount_col" id="field_amount_col">
                                  <label>Amount 1</label>
                                  <input type="number" step="0.01" name="items_amount[]" class="form-control" min="0.01" max="999999999.99">
                                </div>
                              </div>
                              <div class="form-group">
                                <div class="col-md-6 col-sm-6 col-xs-12 field-reference-number field_reference_number_col" id="field_reference_number_col">
                                  <label>Reference Number</label>
                                  <input type="text" name="items_reference_number[]" class="form-control" maxlength="150">
                                </div>
                                <div class="col-md-6 col-sm-6 col-xs-12 field-po-number field_po_number_col" id="field_po_number_col">
                                  <label>PO Number</label>
                                  <input type="text" name="items_po_number[]" class="form-control" maxlength="150">
                                </div>
                              </div>
                              <div class="form-group">
                                <div class="col-md-4 col-sm-4 col-xs-12 field-due-date field_due_date_col" id="field_due_date_col">
                                  <label>Due Date</label>
                                  <input type="date" name="items_due_date[]" class="form-control">
                                </div>
                                <div class="col-md-4 col-sm-4 col-xs-12 field-cash-advance field_cash_advance_col" id="field_cash_advance_col">
                                  <label>Cash Advance?</label>
                                  <select name="items_cash_advance[]" class="form-control">
                                    <option value="">Choose..</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                  </select>
                                </div>
                                <div class="col-md-4 col-sm-4 col-xs-12 field-form-of-payment field_form_of_payment_col" id="field_form_of_payment_col">
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
                                </div>
                              </div>
                              <div class="form-group">
                                <div class="col-md-6 col-sm-6 col-xs-12 field-budget-consumption field_budget_consumption_col" id="field_budget_consumption_col">
                                  <label>Budget Consumption</label>
                                  <input type="number" step="0.01" name="items_budget_consumption[]" class="form-control currency-field" min="0" max="999999999.99">
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
                                data.forEach(function(it){
                                  var node = document.importNode(tpl.content, true);
                                  var root = node.querySelector('.item-row');
                                  var categorySelect = root.querySelector('[name="items_category[]"]');
                                  if (categorySelect) {
                                    categorySelect.value = it.category || '';
                                  }
                                  root.querySelector('[name="items_description[]"]').value = it.description || '';
                                  root.querySelector('[name="items_amount[]"]').value = it.amount || '';
                                  root.querySelector('[name="items_reference_number[]"]').value = it.reference_number || '';
                                  root.querySelector('[name="items_po_number[]"]').value = it.po_number || '';
                                  root.querySelector('[name="items_due_date[]"]').value = it.due_date || '';
                                  root.querySelector('[name="items_cash_advance[]"]').value = (it.cash_advance==='1'?'yes':(it.cash_advance==='0'?'no':''));
                                  root.querySelector('[name="items_form_of_payment[]"]').value = it.form_of_payment || '';
                                  root.querySelector('[name="items_budget_consumption[]"]').value = it.budget_consumption || '';
                                  var attachmentCol = root.querySelector('#field_attachment_col');
                                  var hidden = document.createElement('input');
                                  hidden.type = 'hidden';
                                  hidden.name = 'items_attachment_existing[]';
                                  hidden.value = it.attachment_path || '';
                                  attachmentCol.appendChild(hidden);
                                  if (it.attachment_path) {
                                    // Handle both single file path and JSON array of multiple files
                                    var attachments = [];
                                    try {
                                      if (it.attachment_path.startsWith('[')) {
                                        // JSON array of multiple files
                                        attachments = JSON.parse(it.attachment_path);
                                      } else {
                                        // Single file path
                                        attachments = [it.attachment_path];
                                      }
                                    } catch (e) {
                                      attachments = [it.attachment_path];
                                    }
                                    
                                    // Display each attachment with proper icon
                                    attachments.forEach(function(attachment) {
                                      var fileName = attachment.split('/').pop();
                                      var fileExt = fileName.split('.').pop().toLowerCase();
                                      var iconClass = 'fa-file';
                                      if (['pdf'].includes(fileExt)) iconClass = 'fa-file-pdf-o';
                                      else if (['doc', 'docx'].includes(fileExt)) iconClass = 'fa-file-word-o';
                                      else if (['xls', 'xlsx'].includes(fileExt)) iconClass = 'fa-file-excel-o';
                                      else if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExt)) iconClass = 'fa-file-image-o';
                                      
                                      var small = document.createElement('div');
                                      small.style.marginTop = '5px';
                                      small.innerHTML = '<a href="'+ attachment +'" target="_blank" class="btn btn-xs btn-default"><i class="fa '+ iconClass +'"></i> '+ fileName +'</a>';
                                      attachmentCol.appendChild(small);
                                    });
                                  }
                                  container.appendChild(node);
                                });
                              } catch(e) {}
                            });
                          </script>
                          <?php } ?>
                        </div>
                      </div>

                      <!-- ======================================== -->
                      <!-- ADVANCED (BREAKDOWN) SECTION -->
                      <!-- ======================================== -->
                      <div class="x_panel" id="advanced_section">
                        <div class="x_title">
                          <h3><i class="fa fa-cogs"></i> Advanced (Breakdown)</h3>
                          <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                          <div class="clearfix" style="margin-bottom:10px;">
                            <button type="button" class="btn btn-sm btn-primary" id="addBreakdownBtn"><i class="fa fa-plus"></i> Add Breakdown</button>
                          </div>
                          <div id="breakdownsContainer"></div>
                          <template id="breakdownRowTemplate">
                            <div class="well breakdown-row" style="padding:10px; position:relative;">
                              <button type="button" class="btn btn-danger btn-sm remove-breakdown" title="Remove breakdown" style="position:absolute; top:10px; right:10px; z-index:5000; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:4px;"><i class="fa fa-trash fa-lg"></i></button>
                              <div class="form-group">
                                <div class="col-md-4 col-sm-4 col-xs-12 field-reference-number-2 field_reference_number_2_col" id="field_reference_number_2_col">
                                  <label>Reference Number 2</label>
                                  <input type="text" name="break_reference_number_2[]" class="form-control" maxlength="150">
                                </div>
                                <div class="col-md-4 col-sm-4 col-xs-12 field-date field_date_col" id="field_date_col">
                                  <label>Date</label>
                                  <input type="date" name="break_date[]" class="form-control">
                                </div>
                                <div class="col-md-4 col-sm-4 col-xs-12 field-amount2 field_amount2_col" id="field_amount2_col">
                                  <label>Amount 2</label>
                                  <input type="number" step="0.01" name="break_amount2[]" class="form-control" min="0.01" max="999999999.99">
                                </div>
                              </div>
                            </div>
                          </template>
                          <?php if (!empty($breakdowns)) { ?>
                          <script>
                            document.addEventListener('DOMContentLoaded', function(){
                              try {
                                var container = document.getElementById('breakdownsContainer');
                                var tpl = document.getElementById('breakdownRowTemplate');
                                var data = <?php echo json_encode($breakdowns); ?>;
                                data.forEach(function(bd){
                                  var node = document.importNode(tpl.content, true);
                                  var root = node.querySelector('.breakdown-row');
                                  root.querySelector('[name="break_reference_number_2[]"]').value = bd.reference_number_2 || '';
                                  root.querySelector('[name="break_date[]"]').value = bd.date || '';
                                  root.querySelector('[name="break_amount2[]"]').value = bd.amount2 || '';
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
                              <?php $sessName = trim((string)($_SESSION['userfirstname'] ?? '').' '.(string)($_SESSION['userlastname'] ?? '')); if($sessName===''){ $sessName = (string)($_SESSION['username'] ?? ''); } ?>
                              <div class="checkbox" style="margin: 5px 0 10px;">
                                <label>
                                  <input type="checkbox" id="toggle_payee" class="js-switch"> Enable Payee
                                </label>
                              </div>
                              <input type="text" id="payee" name="payee" class="form-control" placeholder="Enter payee" disabled
                                     maxlength="255"
                                      data-parsley-pattern="^[0-9A-Za-z .,()&-]+$"
                                     data-parsley-pattern-message="Payee name contains invalid characters"
                                      data-parsley-maxlength="255"
                                      data-parsley-maxlength-message="Payee name cannot exceed 255 characters" value="<?php echo h($sessName); ?>">
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
                            </div>
                            <div class="col-md-4 col-sm-4 col-xs-12" id="field_amount_words_col">
                              <label>Amount in Words</label>
                               <input type="text" id="amount_words" name="amount_words" class="form-control" placeholder="e.g., One Thousand Pesos" readonly
                                     maxlength="500"
                                     data-parsley-maxlength="500"
                                     data-parsley-maxlength-message="Amount in words cannot exceed 500 characters" value="<?php echo h($er['amount_in_words'] ?? ''); ?>">
                            </div>
                          </div>
                          
                          <div class="form-group">
                            <div class="col-md-12 col-sm-12 col-xs-12" id="field_payment_for_col">
                              <label for="payment_for">Payment For</label>
                               <textarea id="payment_for" required="required" class="form-control" name="payment_for" data-parsley-trigger="keyup" data-parsley-minlength="5" data-parsley-maxlength="500" data-parsley-minlength-message="Please enter at least 5 characters."
                                   data-parsley-validation-threshold="10"><?php echo h($er['payment_for'] ?? ''); ?></textarea>
                            </div>
                          </div>

                          <div class="form-group">
                            <div class="col-md-12 col-sm-12 col-xs-12" id="field_special_instruction_col">
                              <label for="special_instructions">Special Instructions</label>
                               <textarea id="special_instructions" class="form-control" name="special_instructions" data-parsley-trigger="keyup" data-parsley-maxlength="1000" data-parsley-validation-threshold="10"><?php echo h($er['special_instructions'] ?? ''); ?></textarea>
                            </div>
                          </div>
                          
                          <div class="form-group">
                                                        <div class="col-md-6 col-sm-6 col-xs-12" id="field_supporting_document_col">
                              <label>Supporting Document</label>
                              <input type="file" id="supporting_document" name="supporting_document" class="form-control"
                                     accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif" multiple
                                     data-parsley-fileextension="pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif"
                                     data-parsley-fileextension-message="Please upload a valid file type (PDF, DOC, XLS, or image)"
                                     data-parsley-filesize="10"
                                     data-parsley-filesize-message="File size must be less than 10MB">
                              <small class="text-muted">Multiple files allowed (PDF, Excel, Word, Images)</small>
                              <?php if ($er && !empty($er['supporting_document_path'])) { ?>
                                <?php 
                                  $supportingDocs = [];
                                  if (strpos($er['supporting_document_path'], '[') === 0) {
                                    // JSON array of multiple files
                                    $supportingDocs = json_decode($er['supporting_document_path'], true) ?: [];
                                  } else {
                                    // Single file path
                                    $supportingDocs = [$er['supporting_document_path']];
                                  }
                                ?>
                                <div class="current-supporting-docs" style="margin-top: 10px;">
                                  <strong>Current supporting documents:</strong>
                                  <?php foreach ($supportingDocs as $doc) { ?>
                                    <div style="margin-top: 5px;">
                                      <?php 
                                        $fileName = basename($doc);
                                        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                        $iconClass = 'fa-file';
                                        if (in_array($fileExt, ['pdf'])) $iconClass = 'fa-file-pdf-o';
                                        elseif (in_array($fileExt, ['doc', 'docx'])) $iconClass = 'fa-file-word-o';
                                        elseif (in_array($fileExt, ['xls', 'xlsx'])) $iconClass = 'fa-file-excel-o';
                                        elseif (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif'])) $iconClass = 'fa-file-image-o';
                                      ?>
                                      <a href="<?php echo h($doc); ?>" target="_blank" class="btn btn-xs btn-default">
                                        <i class="fa <?php echo $iconClass; ?>"></i>
                                        <?php echo h($fileName); ?>
                                      </a>
                                    </div>
                                  <?php } ?>
                                </div>
                              <?php } ?>
                            </div>
                            <div class="col-md-6 col-sm-6 col-xs-12" id="field_is_budgeted_col">
                              <label>Tag (Is it budgeted)</label>
                              <select id="is_budgeted" name="is_budgeted" class="form-control"
                                      data-parsley-in="yes,no"
                                      data-parsley-in-message="Please select a valid option">
                                <option value="">Choose..</option>
                                <option value="yes">Yes</option>
                                <option value="no">No</option>
                              </select>
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
                              <div class="checkbox" style="margin-top: 25px;">
                                <label>
                                   <input type="checkbox" id="credit_to_payroll" name="credit_to_payroll" class="flat" <?php echo ($er && (string)$er['credit_to_payroll']==='1'?'checked':''); ?>> Credit To Payroll
                                </label>
                              </div>
                            </div>
                          </div>

                          <div class="form-group">
                            <div class="col-md-4 col-sm-4 col-xs-12" id="field_issue_check_col">
                              <div class="checkbox" style="margin-top: 25px;">
                                <label>
                                   <input type="checkbox" id="issue_check" name="issue_check" class="flat" <?php echo ($er && (string)$er['issue_check']==='1'?'checked':''); ?>> Issue Check
                                </label>
                              </div>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="financial-form.js"></script>
    <script>
      (function(){
        document.addEventListener('DOMContentLoaded', function(){
          var togglePayee = document.getElementById('toggle_payee');
          var payeeInput = document.getElementById('payee');
          if (togglePayee && payeeInput) {
            var syncPayeeState = function(){
              payeeInput.disabled = !togglePayee.checked;
            };
            // initialize as disabled (unchecked by default)
            try { togglePayee.checked = false; } catch (e) {}
            syncPayeeState();
            togglePayee.addEventListener('change', syncPayeeState);
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

  </body>
</html>
