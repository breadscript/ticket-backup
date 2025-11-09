<?php 
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d h:i:s",time());	
include "blocks/inc.resource.php";
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
                 <h3>Department Form</h3>
              </div>

              
            </div>
            <div class="clearfix"></div>
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                     <h2>Department <small>create or update</small></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    <form id="company-form" class="form-horizontal form-label-left" method="post" autocomplete="off" action="save_department.php" novalidate>
                      <?php
                        if (!isset($_SESSION['csrf_token'])) {
                            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        }
                        $editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
                        if ($editId > 0) echo '<input type="hidden" name="__edit_id" value="'.(int)$editId.'">';
                        $d = null;
                        if ($editId > 0 && isset($mysqlconn) && $mysqlconn) {
                          if ($st = mysqli_prepare($mysqlconn, 'SELECT company_code, department_code, department_id, department_name FROM department WHERE id=?')) {
                            mysqli_stmt_bind_param($st, 'i', $editId);
                            mysqli_stmt_execute($st);
                            $rs = mysqli_stmt_get_result($st);
                            $d = mysqli_fetch_assoc($rs);
                            mysqli_stmt_close($st);
                          }
                        }
                        // Load company list for dropdown
                        $companies = [];
                        if (isset($mysqlconn) && $mysqlconn) {
                          $q = 'SELECT company_code, company_name FROM company ORDER BY company_code';
                          if ($res = mysqli_query($mysqlconn, $q)) {
                            while ($row = mysqli_fetch_assoc($res)) { $companies[] = $row; }
                          }
                        }
                        ?>
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                      <input type="hidden" name="form_timestamp" value="<?php echo time(); ?>">

                      <div class="form-group">
                        <div class="col-md-3 col-sm-3 col-xs-12">
                          <label>Company Code</label>
                          <select name="company_code" id="company_code" class="form-control" required>
                            <option value="">Choose..</option>
                            <?php foreach ($companies as $co) { 
                              $code = htmlspecialchars($co['company_code'] ?? '', ENT_QUOTES, 'UTF-8');
                              $name = htmlspecialchars($co['company_name'] ?? '', ENT_QUOTES, 'UTF-8');
                              $sel = (isset($d['company_code']) && $d['company_code'] === $code) ? 'selected' : '';
                            ?>
                              <option value="<?php echo $code; ?>" <?php echo $sel; ?>><?php echo $code . ' - ' . $name; ?></option>
                            <?php } ?>
                          </select>
                        </div>
                        <div class="col-md-3 col-sm-3 col-xs-12">
                          <label>Department Code</label>
                          <input type="text" name="department_code" class="form-control" required maxlength="50" value="<?php echo isset($d['department_code'])?htmlspecialchars($d['department_code'],ENT_QUOTES,'UTF-8'):''; ?>">
                        </div>
                        <div class="col-md-3 col-sm-3 col-xs-12">
                          <label>Department ID</label>
                          <input type="text" name="department_id" class="form-control" required maxlength="100" value="<?php echo isset($d['department_id'])?htmlspecialchars($d['department_id'],ENT_QUOTES,'UTF-8'):''; ?>">
                        </div>
                        <div class="col-md-3 col-sm-3 col-xs-12">
                          <label>Department Name</label>
                          <input type="text" name="department_name" class="form-control" required maxlength="150" value="<?php echo isset($d['department_name'])?htmlspecialchars($d['department_name'],ENT_QUOTES,'UTF-8'):''; ?>">
                        </div>
                      </div>
                      
                      <div class="ln_solid"></div>
                      <div class="form-group">
                        <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                          <a class="btn btn-default" href="department_main.php">Cancel</a>
                          <button type="submit" class="btn btn-success" id="submitBtn">Save</button>
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
    <!-- Parsley -->
    <script src="../srv-v2/vendors/parsleyjs/dist/parsley.min.js"></script>
    <!-- Custom Theme Scripts -->
    <script src="../srv-v2/build/js/custom.min.js"></script>

  </body>
</html>
