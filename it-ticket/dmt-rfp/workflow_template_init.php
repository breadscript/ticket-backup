<?php 
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d h:i:s",time());	
include "blocks/inc.resource.php";

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

// Get available templates for preview
$templates = [];
if (isset($mysqlconn) && $mysqlconn) {
    $sql = "SELECT work_flow_id, department, company, sequence, actor_id, action, is_parellel, global, amount_from, amount_to, Note 
            FROM work_flow_template 
            ORDER BY work_flow_id, company, department, sequence";
    if ($result = mysqli_query($mysqlconn, $sql)) {
        while ($row = mysqli_fetch_assoc($result)) {
            $templates[] = $row;
        }
    }
}

// Group templates by workflow type for display
$groupedTemplates = [];
foreach ($templates as $template) {
    $key = $template['work_flow_id'] . '|' . $template['company'] . '|' . ($template['department'] ?: 'GLOBAL');
    if (!isset($groupedTemplates[$key])) {
        $groupedTemplates[$key] = [
            'work_flow_id' => $template['work_flow_id'],
            'company' => $template['company'],
            'department' => $template['department'] ?: 'GLOBAL',
            'steps' => []
        ];
    }
    $groupedTemplates[$key]['steps'][] = $template;
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
                 <h3>Initialize Workflow Process</h3>
              </div>
            </div>
            <div class="clearfix"></div>
            <div class="row">
              <div class="col-md-6 col-sm-6 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                     <h2>Create Workflow Process <small>from template</small></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    <form id="workflow-init-form" class="form-horizontal form-label-left" method="post" autocomplete="off" action="workflow_process_create.php" novalidate>
                      <?php
                        if (!isset($_SESSION['csrf_token'])) {
                            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        }
                        ?>
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                      <input type="hidden" name="form_timestamp" value="<?php echo time(); ?>">

                      <div class="form-group">
                        <div class="col-md-6 col-sm-6 col-xs-12">
                          <label>Workflow Type <span class="required">*</span></label>
                          <select name="work_flow_id" id="workflow_type" class="form-control" required>
                            <option value="">Select Workflow Type</option>
                            <?php foreach ($workflowTypes as $type): ?>
                              <option value="<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                          <label>Company <span class="required">*</span></label>
                          <select name="company" id="company" class="form-control" required>
                            <option value="">Select Company</option>
                            <?php foreach ($companies as $co): ?>
                              <option value="<?php echo htmlspecialchars($co['company_code'], ENT_QUOTES, 'UTF-8'); ?>" data-company-id="<?php echo htmlspecialchars($co['company_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($co['company_code'], ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars($co['company_name'], ENT_QUOTES, 'UTF-8'); ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                      </div>

                      <div class="form-group">
                        <div class="col-md-6 col-sm-6 col-xs-12">
                          <label>Department</label>
                          <select name="department" id="department" class="form-control">
                            <option value="">Departments</option>
                            <?php foreach ($departments as $dept) { 
                              $deptCode = htmlspecialchars($dept['department_code'] ?? '', ENT_QUOTES, 'UTF-8');
                              $deptName = htmlspecialchars($dept['department_name'] ?? '', ENT_QUOTES, 'UTF-8');
                              $companyCode = htmlspecialchars($dept['company_code'] ?? '', ENT_QUOTES, 'UTF-8');
                            ?>
                              <option value="<?php echo $deptCode; ?>" data-company="<?php echo $companyCode; ?>">
                                <?php echo $deptCode; ?> - <?php echo $deptName; ?>
                              </option>
                            <?php } ?>
                          </select>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                          <label>Document Number <span class="required">*</span></label>
                          <input type="text" name="doc_number" class="form-control" required maxlength="150" placeholder="e.g., RFP-0991, ERL-2024-001">
                        </div>
                      </div>

                      <div class="form-group">
                        <div class="col-md-6 col-sm-6 col-xs-12">
                          <label>Amount (Optional)</label>
                          <input type="number" name="amount" class="form-control" step="0.01" min="0" placeholder="0.00">
                          <small class="text-muted">For amount-based routing</small>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                          <label>Initial Remarks</label>
                          <input type="text" name="remarks" class="form-control" maxlength="500" placeholder="Initial comments or notes">
                        </div>
                      </div>
                      
                      <div class="ln_solid"></div>
                      <div class="form-group">
                        <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                          <button type="submit" class="btn btn-success" id="submitBtn">Initialize Workflow</button>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>
              </div>

              <div class="col-md-6 col-sm-6 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                     <h2>Available Templates <small>preview</small></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    <div id="template-preview">
                      <p class="text-muted">Select workflow type, company, and department to preview available templates.</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                     <h2>Template Overview <small>all available templates</small></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    <?php if (empty($groupedTemplates)): ?>
                      <p class="text-center text-muted">No workflow templates found. Please create templates first.</p>
                    <?php else: ?>
                      <?php foreach ($groupedTemplates as $group): ?>
                        <div class="template-group" style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
                          <h4>
                            <span class="label label-info"><?php echo htmlspecialchars($group['work_flow_id'], ENT_QUOTES, 'UTF-8'); ?></span>
                            - <?php echo htmlspecialchars($group['company'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php if ($group['department'] !== 'GLOBAL'): ?>
                              <span class="label label-default"><?php echo htmlspecialchars($group['department'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php else: ?>
                              <span class="label label-primary">Global</span>
                            <?php endif; ?>
                          </h4>
                          <div class="table-responsive">
                            <table class="table table-condensed">
                              <thead>
                                <tr>
                                  <th>Seq</th>
                                  <th>Actor</th>
                                  <th>Action</th>
                                  <th>Parallel</th>
                                  <th>Amount Range</th>
                                  <th>Notes</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php foreach ($group['steps'] as $step): ?>
                                  <tr>
                                    <td><span class="badge"><?php echo (int)$step['sequence']; ?></span></td>
                                    <td><?php echo htmlspecialchars($step['actor_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><code><?php echo htmlspecialchars($step['action'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                    <td>
                                      <?php if ($step['is_parellel']): ?>
                                        <span class="label label-success">Yes</span>
                                      <?php else: ?>
                                        <span class="label label-default">No</span>
                                      <?php endif; ?>
                                    </td>
                                    <td>
                                      <?php 
                                        $amountFrom = $step['amount_from'] ? number_format($step['amount_from'], 2) : '';
                                        $amountTo = $step['amount_to'] ? number_format($step['amount_to'], 2) : '';
                                        
                                        if ($amountFrom && $amountTo) {
                                          echo $amountFrom . ' - ' . $amountTo;
                                        } elseif ($amountFrom) {
                                          echo '≥ ' . $amountFrom;
                                        } elseif ($amountTo) {
                                          echo '≤ ' . $amountTo;
                                        } else {
                                          echo '-';
                                        }
                                      ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($step['Note'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                  </tr>
                                <?php endforeach; ?>
                              </tbody>
                            </table>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    <?php endif; ?>
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
        $('#workflow-init-form').parsley();
        
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
        
        // Auto-clear department if global is selected
        $('#workflow_type, #company, #department').change(function() {
          updateTemplatePreview();
        });
        
        function updateTemplatePreview() {
          var workflowType = $('#workflow_type').val();
          var company = $('#company').val();
          var department = $('#department').val();
          
          if (workflowType && company) {
            // You can add AJAX call here to get filtered templates
            // For now, just show a message
            $('#template-preview').html('<p class="text-info">Selected: ' + workflowType + ' - ' + company + 
              (department ? ' - ' + department : ' (Global)') + '</p>');
          } else {
            $('#template-preview').html('<p class="text-muted">Select workflow type, company, and department to preview available templates.</p>');
          }
        }
        
        // Trigger change events on load
        $('#company').trigger('change');
      });
    </script>
  </body>
</html>
