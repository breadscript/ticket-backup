<?php 
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d h:i:s", time());
include "blocks/inc.resource.php";

// Search
$search = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$whereClause = '';
if (isset($mysqlconn) && $mysqlconn && $search !== '') {
  $esc = mysqli_real_escape_string($mysqlconn, $search);
  $like = "'%" . $esc . "%'";
  $whereClause = "WHERE work_flow_id LIKE $like OR department LIKE $like OR company LIKE $like OR actor_id LIKE $like OR action LIKE $like";
}

// Fetch all workflow templates
$templates = [];
if (isset($mysqlconn) && $mysqlconn) {
  $listSql = "SELECT id, work_flow_id, department, company, sequence, actor_id, action, is_parellel, global, amount_from, amount_to, Note, created_at FROM work_flow_template $whereClause ORDER BY work_flow_id, company, department, sequence";
  if ($listResult = mysqli_query($mysqlconn, $listSql)) {
    while ($row = mysqli_fetch_assoc($listResult)) { $templates[] = $row; }
  }
}

// Fetch company details for better display
$companyDetails = [];
if (isset($mysqlconn) && $mysqlconn) {
  $companySql = "SELECT company_code, company_name FROM company ORDER BY company_code";
  if ($companyResult = mysqli_query($mysqlconn, $companySql)) {
    while ($row = mysqli_fetch_assoc($companyResult)) {
      $companyDetails[$row['company_code']] = $row['company_name'];
    }
  }
}

// Fetch department details for better display
$departmentDetails = [];
if (isset($mysqlconn) && $mysqlconn) {
  $deptSql = "SELECT department_code, department_name FROM department ORDER BY department_code";
  if ($deptResult = mysqli_query($mysqlconn, $deptSql)) {
    while ($row = mysqli_fetch_assoc($deptResult)) {
      $departmentDetails[$row['department_code']] = $row['department_name'];
    }
  }
}

// Group templates by workflow type and company
$groupedTemplates = [];
foreach ($templates as $template) {
  $workflowId = $template['work_flow_id'];
  $company = $template['company'];
  
  if (!isset($groupedTemplates[$workflowId])) {
    $groupedTemplates[$workflowId] = [];
  }
  
  if (!isset($groupedTemplates[$workflowId][$company])) {
    $groupedTemplates[$workflowId][$company] = [
      'templates' => [],
      'departments' => []
    ];
  }
  
  $groupedTemplates[$workflowId][$company]['templates'][] = $template;
  
  // Track unique departments for this company
  $dept = $template['department'] ?: 'GLOBAL';
  if (!in_array($dept, $groupedTemplates[$workflowId][$company]['departments'])) {
    $groupedTemplates[$workflowId][$company]['departments'][] = $dept;
  }
}

// Sort templates within each group by sequence
foreach ($groupedTemplates as $workflowId => &$companies) {
  foreach ($companies as $company => &$companyData) {
    usort($companyData['templates'], function($a, $b) {
      return $a['sequence'] - $b['sequence'];
    });
  }
}
?>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <!-- left navigation -->
        <?php include "blocks/navigation.php"; ?>
        <!-- /left navigation -->

        <!-- top navigation -->
        <?php include "blocks/header.php"; ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
              <div class="title_left">
                <h3>Workflow Template Management</h3>
              </div>
            </div>
            <div class="clearfix"></div>

            <!-- Toolbar -->
            <div class="row" style="margin-bottom: 10px;"></div>

            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Workflow Templates <small>grouped by type and company</small></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li>
                        <button type="button" class="btn btn-success btn-sm" id="createBtn" name="create" onclick="window.location.href='workflow_template_form.php'">
                          <i class="fa fa-plus"></i> Create Template
                        </button>
                      </li>
                      <li>
                        <button type="button" class="btn btn-info btn-sm" id="expandAllBtn">
                          <i class="fa fa-expand"></i> Expand All
                        </button>
                      </li>
                      <li>
                        <button type="button" class="btn btn-default btn-sm" id="collapseAllBtn">
                          <i class="fa fa-compress"></i> Collapse All
                        </button>
                      </li>
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    <div style="margin-bottom:8px;" class="row">
                      <div class="col-sm-6">
                        <form method="get" class="form-inline" autocomplete="off" style="margin-bottom:8px;">
                          <div class="form-group">
                            <input type="text" name="q" class="form-control input-sm" placeholder="Search templates..." value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                          </div>
                          <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Search</button>
                          <?php if ($search !== '') { ?>
                            <a class="btn btn-default btn-sm" href="workflow_template_main.php"><i class="fa fa-undo"></i> Reset</a>
                          <?php } ?>
                        </form>
                      </div>
                      <div class="col-sm-6 text-right">
                        <button type="button" class="btn btn-default btn-sm" id="resetFilterBtn"><i class="fa fa-undo"></i> Show All</button>
                      </div>
                    </div>

                    <?php if (empty($groupedTemplates)) { ?>
                      <div class="text-center" style="padding: 40px;">
                        <h4 class="text-muted">No workflow templates found</h4>
                        <p class="text-muted">Create your first workflow template to get started.</p>
                        <a href="workflow_template_form.php" class="btn btn-success">
                          <i class="fa fa-plus"></i> Create First Template
                        </a>
                      </div>
                    <?php } else { ?>
                      <?php foreach ($groupedTemplates as $workflowId => $companies) { 
                        $workflowIdSafe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $workflowId);
                      ?>
                        <div class="workflow-group" style="margin-bottom: 30px;">
                          <div class="workflow-header" 
                               style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; cursor: pointer;" 
                               onclick="toggleWorkflow('<?php echo $workflowIdSafe; ?>')">
                            <h3>
                              <i class="fa fa-chevron-down workflow-toggle" id="workflow-icon-<?php echo $workflowIdSafe; ?>"></i>
                              <span class="label label-info" style="font-size: 16px;"><?php echo htmlspecialchars($workflowId, ENT_QUOTES, 'UTF-8'); ?></span>
                              <span class="text-muted">Workflow Templates</span>
                              <small class="text-muted pull-right"><?php echo count($companies); ?> companies</small>
                            </h3>
                          </div>
                          
                          <div class="workflow-content" id="workflow-content-<?php echo $workflowIdSafe; ?>">
                            <?php foreach ($companies as $company => $companyData) { 
                              $companySafe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $company);
                              $uniqueId = $workflowIdSafe . '_' . $companySafe;
                            ?>
                              <div class="company-group" style="margin-bottom: 25px; padding: 20px; border: 1px solid #e3e3e3; border-radius: 8px; background: white;">
                                <div class="company-header" 
                                     style="margin-bottom: 15px; cursor: pointer;" 
                                     onclick="toggleCompany('<?php echo $uniqueId; ?>')">
                                  <h4>
                                    <i class="fa fa-chevron-down company-toggle" id="company-icon-<?php echo $uniqueId; ?>"></i>
                                    <span class="label label-primary"><?php echo htmlspecialchars($company, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php if (isset($companyDetails[$company])) { ?>
                                      <small class="text-muted"> - <?php echo htmlspecialchars($companyDetails[$company], ENT_QUOTES, 'UTF-8'); ?></small>
                                    <?php } ?>
                                    <small class="text-muted">Company</small>
                                    <small class="text-muted pull-right"><?php echo count($companyData['templates']); ?> templates</small>
                                  </h4>
                                  <div class="departments-info">
                                    <small class="text-muted">
                                      Departments: 
                                      <?php 
                                        $deptLabels = [];
                                        foreach ($companyData['departments'] as $dept) {
                                          if ($dept === 'GLOBAL') {
                                            $deptLabels[] = '<span class="label label-success">Global</span>';
                                          } else {
                                            $deptName = isset($departmentDetails[$dept]) ? $departmentDetails[$dept] : $dept;
                                            $deptLabels[] = '<span class="label label-default">' . htmlspecialchars($dept, ENT_QUOTES, 'UTF-8') . ' (' . htmlspecialchars($deptName, ENT_QUOTES, 'UTF-8') . ')</span>';
                                          }
                                        }
                                        echo implode(' ', $deptLabels);
                                      ?>
                                    </small>
                                  </div>
                                </div>
                                
                                <div class="company-content" id="company-content-<?php echo $uniqueId; ?>">
                                  <div class="table-responsive">
                                    <table class="table table-condensed table-bordered">
                                      <thead>
                                        <tr>
                                          <th>Seq</th>
                                          <th>Department</th>
                                          <th>Actor</th>
                                          <th>Action</th>
                                          <th>Parallel</th>
                                          <th>Global</th>
                                          <th>Amount Range</th>
                                          <th>Notes</th>
                                          <th>Created</th>
                                          <th>Actions</th>
                                        </tr>
                                      </thead>
                                      <tbody>
                                        <?php foreach ($companyData['templates'] as $template) { 
                                          $id = (int)($template['id'] ?? 0);
                                          $dept = htmlspecialchars($template['department'] ?? '', ENT_QUOTES, 'UTF-8');
                                          $seq = (int)($template['sequence'] ?? 0);
                                          $actorId = $template['actor_id'] ?? '';
                                          $action = htmlspecialchars($template['action'] ?? '', ENT_QUOTES, 'UTF-8');
                                          $parallel = (int)($template['is_parellel'] ?? 0);
                                          $global = (int)($template['global'] ?? 0);
                                          $amountFrom = $template['amount_from'] ? number_format($template['amount_from'], 2) : '';
                                          $amountTo = $template['amount_to'] ? number_format($template['amount_to'], 2) : '';
                                          $note = htmlspecialchars($template['Note'] ?? '', ENT_QUOTES, 'UTF-8');
                                          $created = htmlspecialchars($template['created_at'] ?? '', ENT_QUOTES, 'UTF-8');
                                          $createdDisp = $created ? date('Y-m-d H:i', strtotime($created)) : '';
                                          
                                          $amountRange = '';
                                          if ($amountFrom && $amountTo) {
                                            $amountRange = $amountFrom . ' - ' . $amountTo;
                                          } elseif ($amountFrom) {
                                            $amountRange = '≥ ' . $amountFrom;
                                          } elseif ($amountTo) {
                                            $amountRange = '≤ ' . $amountTo;
                                          }
                                          
                                          // Actor ID now contains the firstname directly
                                          $actorDisplay = htmlspecialchars($actorId, ENT_QUOTES, 'UTF-8');
                                        ?>
                                          <tr>
                                            <td><span class="badge badge-primary"><?php echo $seq; ?></span></td>
                                            <td>
                                              <?php if ($dept) { ?>
                                                <span class="label label-default">
                                                  <?php echo $dept; ?>
                                                  <?php if (isset($departmentDetails[$dept])) { ?>
                                                    <small>(<?php echo htmlspecialchars($departmentDetails[$dept], ENT_QUOTES, 'UTF-8'); ?>)</small>
                                                  <?php } ?>
                                                </span>
                                              <?php } else { ?>
                                                <span class="label label-success">Global</span>
                                              <?php } ?>
                                            </td>
                                            <td><?php echo $actorDisplay; ?></td>
                                            <td><code><?php echo $action; ?></code></td>
                                            <td>
                                              <?php if ($parallel) { ?>
                                                <span class="label label-success">Yes</span>
                                              <?php } else { ?>
                                                <span class="label label-default">No</span>
                                              <?php } ?>
                                            </td>
                                            <td>
                                              <?php if ($global) { ?>
                                                <span class="label label-primary">Yes</span>
                                              <?php } else { ?>
                                                <span class="label label-default">No</span>
                                              <?php } ?>
                                            </td>
                                            <td><?php echo $amountRange ?: '-'; ?></td>
                                            <td><?php echo $note ?: '-'; ?></td>
                                            <td><?php echo $createdDisp; ?></td>
                                            <td>
                                              <button type="button" class="btn btn-xs btn-info" onclick="window.location.href='workflow_template_view.php?id=<?php echo $id; ?>'">
                                                <i class="fa fa-eye"></i> View
                                              </button>
                                              <button type="button" class="btn btn-xs btn-warning" onclick="window.location.href='workflow_template_form.php?id=<?php echo $id; ?>'">
                                                <i class="fa fa-pencil"></i> Edit
                                              </button>
                                              <button type="button" class="btn btn-xs btn-danger" onclick="deleteTemplate(<?php echo $id; ?>)">
                                                <i class="fa fa-trash"></i> Delete
                                              </button>
                                            </td>
                                          </tr>
                                        <?php } ?>
                                      </tbody>
                                    </table>
                                  </div>
                                </div>
                              </div>
                            <?php } ?>
                          </div>
                        </div>
                      <?php } ?>
                    <?php } ?>
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
    <!-- Custom Theme Scripts -->
    <script src="../srv-v2/build/js/custom.min.js"></script>
    <script>
      function deleteTemplate(id) {
        if (confirm('Are you sure you want to delete this workflow template? This action cannot be undone.')) {
          window.location.href = 'workflow_template_delete.php?id=' + id;
        }
      }

      function toggleWorkflow(workflowId) {
        var content = document.getElementById('workflow-content-' + workflowId);
        var icon = document.getElementById('workflow-icon-' + workflowId);
        
        if (content.style.display === 'none') {
          content.style.display = 'block';
          icon.className = 'fa fa-chevron-down workflow-toggle';
        } else {
          content.style.display = 'none';
          icon.className = 'fa fa-chevron-right workflow-toggle';
        }
      }

      function toggleCompany(uniqueId) {
        var content = document.getElementById('company-content-' + uniqueId);
        var icon = document.getElementById('company-icon-' + uniqueId);
        
        if (content.style.display === 'none') {
          content.style.display = 'block';
          icon.className = 'fa fa-chevron-down company-toggle';
        } else {
          content.style.display = 'none';
          icon.className = 'fa fa-chevron-right company-toggle';
        }
      }

      function expandAll() {
        // Expand all workflows
        var workflowContents = document.querySelectorAll('.workflow-content');
        var workflowIcons = document.querySelectorAll('.workflow-toggle');
        
        workflowContents.forEach(function(content) {
          content.style.display = 'block';
        });
        
        workflowIcons.forEach(function(icon) {
          icon.className = 'fa fa-chevron-down workflow-toggle';
        });

        // Expand all companies
        var companyContents = document.querySelectorAll('.company-content');
        var companyIcons = document.querySelectorAll('.company-toggle');
        
        companyContents.forEach(function(content) {
          content.style.display = 'block';
        });
        
        companyIcons.forEach(function(icon) {
          icon.className = 'fa fa-chevron-down company-toggle';
        });
      }

      function collapseAll() {
        // Collapse all workflows
        var workflowContents = document.querySelectorAll('.workflow-content');
        var workflowIcons = document.querySelectorAll('.workflow-toggle');
        
        workflowContents.forEach(function(content) {
          content.style.display = 'none';
        });
        
        workflowIcons.forEach(function(icon) {
          icon.className = 'fa fa-chevron-right workflow-toggle';
        });

        // Collapse all companies
        var companyContents = document.querySelectorAll('.company-content');
        var companyIcons = document.querySelectorAll('.company-toggle');
        
        companyContents.forEach(function(content) {
          content.style.display = 'none';
        });
        
        companyIcons.forEach(function(icon) {
          icon.className = 'fa fa-chevron-right company-toggle';
        });
      }

      document.addEventListener('DOMContentLoaded', function() {
        var resetBtn = document.getElementById('resetFilterBtn');
        if (resetBtn) {
          resetBtn.addEventListener('click', function() {
            window.location.href = 'workflow_template_main.php';
          });
        }

        var expandAllBtn = document.getElementById('expandAllBtn');
        if (expandAllBtn) {
          expandAllBtn.addEventListener('click', expandAll);
        }

        var collapseAllBtn = document.getElementById('collapseAllBtn');
        if (collapseAllBtn) {
          collapseAllBtn.addEventListener('click', collapseAll);
        }

        // Initialize all sections as collapsed by default
        collapseAll();
      });
    </script>

    <style>
      .workflow-header:hover,
      .company-header:hover {
        background-color: #e9ecef !important;
        transition: background-color 0.2s ease;
      }
      
      .workflow-toggle,
      .company-toggle {
        margin-right: 10px;
        transition: transform 0.2s ease;
      }
      
      .workflow-header,
      .company-header {
        user-select: none;
      }
      
      .workflow-content,
      .company-content {
        transition: all 0.3s ease;
      }
    </style>
  </body>
</html>