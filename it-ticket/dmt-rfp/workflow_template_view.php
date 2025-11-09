<?php 
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d h:i:s",time());	
include "blocks/inc.resource.php";

// Get template ID
$templateId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$template = null;

// Fetch template data
if ($templateId > 0 && isset($mysqlconn) && $mysqlconn) {
  if ($st = mysqli_prepare($mysqlconn, 'SELECT work_flow_id, department, company, sequence, actor_id, action, is_parellel, global, amount_from, amount_to, Note, created_at, updated_at FROM work_flow_template WHERE id=?')) {
    mysqli_stmt_bind_param($st, 'i', $templateId);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $template = mysqli_fetch_assoc($rs);
    mysqli_stmt_close($st);
  }
}

// Redirect if template not found
if (!$template) {
  header('Location: workflow_template_main.php');
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
                 <h3>Workflow Template Details</h3>
              </div>
            </div>
            <div class="clearfix"></div>
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                     <h2>Workflow Template <small>view details</small></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    <div class="row">
                      <div class="col-md-8 col-sm-8 col-xs-12">
                        <div class="form-group">
                          <label class="control-label col-md-3 col-sm-3 col-xs-12">Workflow Type:</label>
                          <div class="col-md-9 col-sm-9 col-xs-12">
                            <p class="form-control-static">
                              <span class="label label-info"><?php echo htmlspecialchars($template['work_flow_id'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </p>
                          </div>
                        </div>

                        <div class="form-group">
                          <label class="control-label col-md-3 col-sm-3 col-xs-12">Company:</label>
                          <div class="col-md-9 col-sm-9 col-xs-12">
                            <p class="form-control-static"><?php echo htmlspecialchars($template['company'], ENT_QUOTES, 'UTF-8'); ?></p>
                          </div>
                        </div>

                        <div class="form-group">
                          <label class="control-label col-md-3 col-sm-3 col-xs-12">Department:</label>
                          <div class="col-md-9 col-sm-9 col-xs-12">
                            <p class="form-control-static">
                              <?php if ($template['department']) { ?>
                                <?php echo htmlspecialchars($template['department'], ENT_QUOTES, 'UTF-8'); ?>
                              <?php } else { ?>
                                <em>Global (All Departments)</em>
                              <?php } ?>
                            </p>
                          </div>
                        </div>

                        <div class="form-group">
                          <label class="control-label col-md-3 col-sm-3 col-xs-12">Sequence:</label>
                          <div class="col-md-9 col-sm-9 col-xs-12">
                            <p class="form-control-static">
                              <span class="badge badge-primary"><?php echo (int)$template['sequence']; ?></span>
                            </p>
                          </div>
                        </div>

                        <div class="form-group">
                          <label class="control-label col-md-3 col-sm-3 col-xs-12">Actor ID:</label>
                          <div class="col-md-9 col-sm-9 col-xs-12">
                            <p class="form-control-static"><?php echo htmlspecialchars($template['actor_id'], ENT_QUOTES, 'UTF-8'); ?></p>
                          </div>
                        </div>

                        <div class="form-group">
                          <label class="control-label col-md-3 col-sm-3 col-xs-12">Action:</label>
                          <div class="col-md-9 col-sm-9 col-xs-12">
                            <p class="form-control-static">
                              <code><?php echo htmlspecialchars($template['action'], ENT_QUOTES, 'UTF-8'); ?></code>
                            </p>
                          </div>
                        </div>

                        <div class="form-group">
                          <label class="control-label col-md-3 col-sm-3 col-xs-12">Parallel Approval:</label>
                          <div class="col-md-9 col-sm-9 col-xs-12">
                            <p class="form-control-static">
                              <?php if ($template['is_parellel']) { ?>
                                <span class="label label-success">Yes</span>
                                <small class="text-muted">Multiple approvals can happen simultaneously</small>
                              <?php } else { ?>
                                <span class="label label-default">No</span>
                                <small class="text-muted">Sequential approval required</small>
                              <?php } ?>
                            </p>
                          </div>
                        </div>

                        <div class="form-group">
                          <label class="control-label col-md-3 col-sm-3 col-xs-12">Global Template:</label>
                          <div class="col-md-9 col-sm-9 col-xs-12">
                            <p class="form-control-static">
                              <?php if ($template['global']) { ?>
                                <span class="label label-primary">Yes</span>
                                <small class="text-muted">Applies to all departments</small>
                              <?php } else { ?>
                                <span class="label label-default">No</span>
                                <small class="text-muted">Department-specific template</small>
                              <?php } ?>
                            </p>
                          </div>
                        </div>

                        <div class="form-group">
                          <label class="control-label col-md-3 col-sm-3 col-xs-12">Amount Range:</label>
                          <div class="col-md-9 col-sm-9 col-xs-12">
                            <p class="form-control-static">
                              <?php 
                                $amountFrom = $template['amount_from'] ? number_format($template['amount_from'], 2) : '';
                                $amountTo = $template['amount_to'] ? number_format($template['amount_to'], 2) : '';
                                
                                if ($amountFrom && $amountTo) {
                                  echo $amountFrom . ' - ' . $amountTo;
                                } elseif ($amountFrom) {
                                  echo '≥ ' . $amountFrom;
                                } elseif ($amountTo) {
                                  echo '≤ ' . $amountTo;
                                } else {
                                  echo '<em>No amount restrictions</em>';
                                }
                              ?>
                            </p>
                          </div>
                        </div>

                        <div class="form-group">
                          <label class="control-label col-md-3 col-sm-3 col-xs-12">Notes:</label>
                          <div class="col-md-9 col-sm-9 col-xs-12">
                            <p class="form-control-static">
                              <?php if ($template['Note']) { ?>
                                <?php echo nl2br(htmlspecialchars($template['Note'], ENT_QUOTES, 'UTF-8')); ?>
                              <?php } else { ?>
                                <em>No additional notes</em>
                              <?php } ?>
                            </p>
                          </div>
                        </div>
                      </div>

                      <div class="col-md-4 col-sm-4 col-xs-12">
                        <div class="x_panel">
                          <div class="x_title">
                            <h4>Template Information</h4>
                            <div class="clearfix"></div>
                          </div>
                          <div class="x_content">
                            <div class="form-group">
                              <label>Created:</label>
                              <p><?php echo $template['created_at'] ? date('Y-m-d H:i:s', strtotime($template['created_at'])) : 'N/A'; ?></p>
                            </div>
                            <div class="form-group">
                              <label>Last Updated:</label>
                              <p><?php echo $template['updated_at'] ? date('Y-m-d H:i:s', strtotime($template['updated_at'])) : 'N/A'; ?></p>
                            </div>
                            <div class="form-group">
                              <label>Template ID:</label>
                              <p><code>#<?php echo $templateId; ?></code></p>
                            </div>
                          </div>
                        </div>

                        <div class="x_panel">
                          <div class="x_title">
                            <h4>Quick Actions</h4>
                            <div class="clearfix"></div>
                          </div>
                          <div class="x_content">
                            <div class="btn-group-vertical btn-block">
                              <a href="workflow_template_form.php?id=<?php echo $templateId; ?>" class="btn btn-warning btn-block">
                                <i class="fa fa-pencil"></i> Edit Template
                              </a>
                              <a href="workflow_template_main.php" class="btn btn-default btn-block">
                                <i class="fa fa-list"></i> Back to List
                              </a>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <div class="ln_solid"></div>
                    <div class="form-group">
                      <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                        <a class="btn btn-default" href="workflow_template_main.php">Back to List</a>
                        <a class="btn btn-warning" href="workflow_template_form.php?id=<?php echo $templateId; ?>">Edit Template</a>
                      </div>
                    </div>
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
    <!-- Custom Theme Scripts -->
    <script src="../srv-v2/build/js/custom.min.js"></script>
  </body>
</html>
