<?php 
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d h:i:s",time());	
include "blocks/inc.resource.php";

// Get edit ID if provided
$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$template = null;

// Fetch existing template data if editing
if ($editId > 0 && isset($mysqlconn) && $mysqlconn) {
  if ($st = mysqli_prepare($mysqlconn, 'SELECT work_flow_id, department, company, sequence, actor_id, action, is_parellel, global, amount_from, amount_to, Note FROM work_flow_template WHERE id=?')) {
    mysqli_stmt_bind_param($st, 'i', $editId);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $template = mysqli_fetch_assoc($rs);
    mysqli_stmt_close($st);
  }
}

// Get available workflow types
$workflowTypes = ['RFP', 'ERL', 'ERGR', 'PR', 'PO', 'PAW'];

// Fetch companies from company table
$companies = [];
if (isset($mysqlconn) && $mysqlconn) {
  $companySql = "SELECT company_id, company_code, company_name FROM company ORDER BY company_code";
  if ($companyResult = mysqli_query($mysqlconn, $companySql)) {
    while ($row = mysqli_fetch_assoc($companyResult)) {
      $companies[] = $row;
    }
  }
  
  // If no companies found in company table, add some default ones
  if (empty($companies)) {
    $companies = [
      ['company_id' => '20', 'company_code' => 'TLCI', 'company_name' => 'The Laguna Creamery Inc.'],
      ['company_id' => '10', 'company_code' => 'MPAV', 'company_name' => 'Metro Pacific Agricultural Venture'],
      ['company_id' => '30', 'company_code' => 'MPFF', 'company_name' => 'Metro Pacific Fresh Farm']
    ];
  }
}

// Fetch all departments from department table for initial load
$departments = [];
if (isset($mysqlconn) && $mysqlconn) {
  $deptSql = "SELECT company_code, department_code, department_name FROM department ORDER BY company_code, department_code";
  if ($deptResult = mysqli_query($mysqlconn, $deptSql)) {
    while ($row = mysqli_fetch_assoc($deptResult)) {
      $departments[] = $row;
    }
  }
  
  // If no departments found in department table, add some default ones
  if (empty($departments)) {
    $departments = [
      ['company_code' => 'TLCI', 'department_code' => 'TLCI-IT', 'department_name' => 'IT'],
      ['company_code' => 'TLCI', 'department_code' => 'TLCI-FIN', 'department_name' => 'FIN'],
      ['company_code' => 'TLCI', 'department_code' => 'TLCI-ACC', 'department_name' => 'ACC']
    ];
  }
}

// Fetch users from sys_usertb for Actor ID dropdown
$users = [];
if (isset($mysqlconn) && $mysqlconn) {
  $userSql = "SELECT id, user_firstname, user_lastname, username, emailadd FROM sys_usertb WHERE user_statusid = 1 ORDER BY user_firstname, user_lastname";
  if ($userResult = mysqli_query($mysqlconn, $userSql)) {
    while ($row = mysqli_fetch_assoc($userResult)) {
      $users[] = $row;
    }
  }
}

// Debug: Check if users are found
if (empty($users)) {
  // If no users found, add some default test users
  $users = [
    ['id' => '1', 'user_firstname' => 'REDBEL', 'user_lastname' => 'ITSOLUTIONS', 'username' => 'SA', 'emailadd' => 'ASD@GMAIL.COM'],
    ['id' => '2', 'user_firstname' => 'JUNELYN', 'user_lastname' => 'BELISARIO', 'username' => 'A', 'emailadd' => 'ASDSA@GMAIL.COM'],
    ['id' => '3', 'user_firstname' => 'DIANA LYN', 'user_lastname' => 'BAUTISTA', 'username' => 'TS-DL.BAUSTISTA', 'emailadd' => 'MS.DIANALYNBAUTISTA@GMAIL.COM'],
    ['id' => '4', 'user_firstname' => 'DENVER', 'user_lastname' => 'BALTAZAR', 'username' => 'TS-D.BALTAZAR', 'emailadd' => 'BALTAZAR.DENVERF@GMAIL.COM'],
    ['id' => '5', 'user_firstname' => 'PHILIP', 'user_lastname' => 'REDONDO', 'username' => 'TS-P.REDONDO', 'emailadd' => 'PHILIP.REDONDO20@GMAIL.COM']
  ];
}

// For editing: Find the user ID based on the stored firstname
$selectedUserId = '';
if ($editId > 0 && isset($template['actor_id']) && !empty($template['actor_id'])) {
  $actorFirstname = trim($template['actor_id']);
  foreach ($users as $user) {
    if (trim($user['user_firstname']) === $actorFirstname) {
      $selectedUserId = $user['id'];
      break;
    }
  }
}

// If editing and action is Requestor, pre-select special Requestor option
if ($editId > 0 && isset($template['action']) && strcasecmp((string)$template['action'], 'Requestor') === 0) {
  $selectedUserId = '__REQUESTOR__';
}

// Static list of actions as shown in the image
$actions = [
  'Requestor',
  'Cost_Center_Head',
  'Accounting_Approver_1',
  'Accounting_Approver_1_Sub',
  'Accounting_Approver_2',
  'Accounting_Controller_1',
  'Accounting_Cashier',
  'Department_Manager',
  'General_Manager',
  'CEO'
];
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
                 <h3><?php echo $editId > 0 ? 'Edit Workflow Template' : 'Create Workflow Template'; ?></h3>
              </div>
            </div>
            <div class="clearfix"></div>
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                     <h2>Workflow Template <small><?php echo $editId > 0 ? 'update existing' : 'create new'; ?></small></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    <form id="workflow-template-form" class="form-horizontal form-label-left" method="post" autocomplete="off" action="workflow_template_save.php" novalidate>
                      <?php
                        if (!isset($_SESSION['csrf_token'])) {
                            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        }
                        if ($editId > 0) echo '<input type="hidden" name="__edit_id" value="'.(int)$editId.'">';
                        ?>
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                      <input type="hidden" name="form_timestamp" value="<?php echo time(); ?>">

                      <div class="form-group">
                        <div class="col-md-4 col-sm-4 col-xs-12">
                          <label>Workflow Type <span class="required">*</span></label>
                          <select name="work_flow_id" class="form-control" required>
                            <option value="">Select Workflow Type</option>
                            <?php 
                            $workflowTypeNames = [
                              'RFP' => 'Request for Proposal',
                              'ERL' => 'Expense Reimbursement Liquidation',
                              'ERGR' => 'Expense Reimbursement General Request',
                              'PR' => 'Purchase Request',
                              'PO' => 'Purchase Order',
                              'PAW' => 'Promotional Activity Workplan'
                            ];
                            foreach ($workflowTypes as $type): 
                              $displayName = $workflowTypeNames[$type] ?? $type;
                            ?>
                              <option value="<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (isset($template['work_flow_id']) && $template['work_flow_id'] === $type) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div class="col-md-4 col-sm-4 col-xs-12">
                          <label>Company <span class="required">*</span></label>
                          <select id="company" name="company" class="form-control" required
                                      data-parsley-required="true"
                                      data-parsley-required-message="Please select a company">
                                <option value="">Choose..</option>
                                <?php foreach ($companies as $co) { 
                                  $code = htmlspecialchars($co['company_code'] ?? '', ENT_QUOTES, 'UTF-8');
                                  $cid  = htmlspecialchars($co['company_id'] ?? '', ENT_QUOTES, 'UTF-8');
                                  $name = htmlspecialchars($co['company_name'] ?? '', ENT_QUOTES, 'UTF-8');
                                  $selected = (isset($template['company']) && $template['company'] === $code) ? 'selected' : '';
                                ?>
                                  <option value="<?php echo $code; ?>" data-company-id="<?php echo $cid; ?>" <?php echo $selected; ?>>
                                    <?php echo $code; ?> - <?php echo $name; ?>
                                  </option>
                                <?php } ?>
                              </select>
                        </div>
                        <div class="col-md-4 col-sm-4 col-xs-12">
                          <label>Department</label>
                          <select name="department" id="department" class="form-control">
                            <option value="">Departments</option>
                            <?php foreach ($departments as $dept) { 
                              $deptCode = htmlspecialchars($dept['department_code'] ?? '', ENT_QUOTES, 'UTF-8');
                              $deptName = htmlspecialchars($dept['department_name'] ?? '', ENT_QUOTES, 'UTF-8');
                              $companyCode = htmlspecialchars($dept['company_code'] ?? '', ENT_QUOTES, 'UTF-8');
                              $selected = (isset($template['department']) && $template['department'] === $deptCode) ? 'selected' : '';
                            ?>
                              <option value="<?php echo $deptCode; ?>" data-company="<?php echo $companyCode; ?>" <?php echo $selected; ?>>
                                <?php echo $deptCode; ?> - <?php echo $deptName; ?>
                              </option>
                            <?php } ?>
                          </select>
                        </div>
                      </div>

                      <div class="form-group">
                        <div class="col-md-3 col-sm-3 col-xs-12">
                          <label>Sequence <span class="required">*</span></label>
                          <input type="number" name="sequence" class="form-control" required min="1" max="99" value="<?php echo isset($template['sequence'])?(int)$template['sequence']:1; ?>">
                          <small class="text-muted">Order of approval (1, 2, 3...)</small>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                          <label>Actor ID <span class="required">*</span></label>
                          <select name="actor_id" id="actor_id" class="form-control select2" required>
                            <option value="">Search for a user...</option>
                            <option value="__REQUESTOR__" <?php echo ($selectedUserId === '__REQUESTOR__') ? 'selected' : ''; ?>>Requestor (self)</option>
                            <?php foreach ($users as $user) { 
                              $userId = htmlspecialchars($user['id'] ?? '', ENT_QUOTES, 'UTF-8');
                              $firstName = htmlspecialchars($user['user_firstname'] ?? '', ENT_QUOTES, 'UTF-8');
                              $lastName = htmlspecialchars($user['user_lastname'] ?? '', ENT_QUOTES, 'UTF-8');
                              $username = htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8');
                              $email = htmlspecialchars($user['emailadd'] ?? '', ENT_QUOTES, 'UTF-8');
                              $displayName = trim($firstName . ' ' . $lastName);
                              $selected = ($selectedUserId == $userId) ? 'selected' : '';
                            ?>
                              <option value="<?php echo $userId; ?>" <?php echo $selected; ?>>
                                <?php echo $displayName; ?> (<?php echo $username; ?>) - <?php echo $email; ?>
                              </option>
                            <?php } ?>
                          </select>
                          <small class="text-muted">Search by name, username, or email. If Action is Requestor, choose "Requestor (self)".</small>
                          <?php if (empty($users)): ?>
                            <div class="alert alert-warning">
                              <strong>Warning:</strong> No users found in the system. Please ensure users are properly configured.
                            </div>
                          <?php endif; ?>
                        </div>
                        <div class="col-md-3 col-sm-3 col-xs-12">
                          <label>Action <span class="required">*</span></label>
                          <select name="action" class="form-control" required>
                            <option value="">Select Action</option>
                            <?php foreach ($actions as $action): ?>
                              <option value="<?php echo htmlspecialchars($action, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (isset($template['action']) && $template['action'] === $action) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($action, ENT_QUOTES, 'UTF-8'); ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                      </div>

                      <div class="form-group">
                        <div class="col-md-3 col-sm-3 col-xs-12">
                          <label>Parallel Approval</label>
                          <div class="checkbox">
                            <label>
                              <input type="checkbox" name="is_parellel" value="1" <?php echo (isset($template['is_parellel']) && $template['is_parellel']) ? 'checked' : ''; ?>>
                              Allow parallel approvals at this step
                            </label>
                          </div>
                        </div>
                        <div class="col-md-3 col-sm-3 col-xs-12">
                          <label>Global Template</label>
                          <div class="checkbox">
                            <label>
                              <input type="checkbox" name="global" value="1" <?php echo (isset($template['global']) && $template['global']) ? 'checked' : ''; ?>>
                              Apply globally across all departments
                            </label>
                          </div>
                        </div>
                        <div class="col-md-3 col-sm-3 col-xs-12">
                          <label>Amount From</label>
                          <input type="number" name="amount_from" class="form-control" step="0.01" min="0" placeholder="0.00" value="<?php echo isset($template['amount_from'])?number_format($template['amount_from'], 2):''; ?>">
                          <small class="text-muted">Lower bound (optional)</small>
                        </div>
                        <div class="col-md-3 col-sm-3 col-xs-12">
                          <label>Amount To</label>
                          <input type="number" name="amount_to" class="form-control" step="0.01" min="0" placeholder="0.00" value="<?php echo isset($template['amount_to'])?number_format($template['amount_to'], 2):''; ?>">
                          <small class="text-muted">Upper bound (optional)</small>
                        </div>
                      </div>

                      <div class="form-group">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                          <label>Notes</label>
                          <textarea name="Note" class="form-control" rows="3" maxlength="500" placeholder="Additional notes or instructions for this workflow step"><?php echo isset($template['Note'])?htmlspecialchars($template['Note'],ENT_QUOTES,'UTF-8'):''; ?></textarea>
                        </div>
                      </div>
                      
                      <div class="ln_solid"></div>
                      <div class="form-group">
                        <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                          <a class="btn btn-default" href="workflow_template_main.php">Cancel</a>
                          <button type="submit" class="btn btn-success" id="submitBtn">
                            <?php echo $editId > 0 ? 'Update Template' : 'Create Template'; ?>
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
    <script src="../srv-v2/vendors/icheck/icheck.min.js"></script>
    <!-- Parsley -->
    <script src="../srv-v2/vendors/parsleyjs/dist/parsley.min.js"></script>
    <!-- Select2 -->
    <script src="../srv-v2/vendors/select2/dist/js/select2.full.min.js"></script>
    <!-- Custom Theme Scripts -->
    <script src="../srv-v2/build/js/custom.min.js"></script>
    
    <!-- Select2 CSS -->
    <link href="../srv-v2/vendors/select2/dist/css/select2.min.css" rel="stylesheet">
    
    <script>
      $(document).ready(function() {
        // Form validation
        $('#workflow-template-form').parsley();
        
        // Initialize Select2 for Actor ID dropdown
        $('#actor_id').select2({
          placeholder: 'Search for a user...',
          allowClear: true,
          width: '100%',
          minimumInputLength: 1,
          language: {
            noResults: function() {
              return "No users found";
            },
            searching: function() {
              return "Searching...";
            }
          }
        });
        
        // Auto-clear department if global is checked
        $('input[name="global"]').change(function() {
          if ($(this).is(':checked')) {
            $('select[name="department"]').val('').prop('disabled', true);
          } else {
            $('select[name="department"]').prop('disabled', false);
          }
        });

        // Auto-select Requestor (self) when Action is Requestor
        $('select[name="action"]').on('change', function() {
          var actionVal = ($(this).val() || '').toString().toLowerCase();
          if (actionVal === 'requestor') {
            var $actor = $('#actor_id');
            if ($actor.find('option[value="__REQUESTOR__"]').length === 0) {
              $actor.prepend('<option value="__REQUESTOR__">Requestor (self)</option>');
            }
            $actor.val('__REQUESTOR__').trigger('change');
          }
        });

        // On load, if Action already Requestor, ensure actor is set accordingly
        (function initRequestorSelection(){
          var actSel = $('select[name="action"]').val();
          if (typeof actSel === 'string' && actSel.toLowerCase() === 'requestor') {
            var $actor = $('#actor_id');
            if ($actor.find('option[value="__REQUESTOR__"]').length === 0) {
              $actor.prepend('<option value="__REQUESTOR__">Requestor (self)</option>');
            }
            if ($actor.val() !== '__REQUESTOR__') {
              $actor.val('__REQUESTOR__').trigger('change');
            }
          }
        })();
        
        // Filter departments based on selected company
        $('#company').change(function() {
          var selectedCompany = $(this).val();
          var departmentSelect = $('#department');
          
          if (selectedCompany) {
            // Enable department selection
            departmentSelect.prop('disabled', false);
            
            // Hide all department options first
            departmentSelect.find('option').hide();
            
            // Show only the "Departments" option
            departmentSelect.find('option[value=""]').show();
            
            // Show departments that match the selected company
            departmentSelect.find('option[data-company="' + selectedCompany + '"]').show();
            
            // Reset department selection
            departmentSelect.val('');
          } else {
            // Disable department selection if no company selected
            departmentSelect.prop('disabled', true).val('');
            departmentSelect.find('option').hide();
            departmentSelect.find('option[value=""]').show();
          }
        });
        
        // Trigger change events on load
        $('input[name="global"]').trigger('change');
        $('#company').trigger('change');
      });
    </script>
  </body>
</html>
