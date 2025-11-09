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
                 <h3>Company Form</h3>
              </div>

              
            </div>
            <div class="clearfix"></div>
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                     <h2>Company <small>create or update</small></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    <form id="company-form" class="form-horizontal form-label-left" method="post" autocomplete="off" action="save_company.php" novalidate>
                      <?php
                        if (!isset($_SESSION['csrf_token'])) {
                            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        }
                        $editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
                        if ($editId > 0) echo '<input type="hidden" name="__edit_id" value="'.(int)$editId.'">';
                        $c = null;
                        if ($editId > 0 && isset($mysqlconn) && $mysqlconn) {
                          if ($st = mysqli_prepare($mysqlconn, 'SELECT company_name, company_code, company_id, company_address, company_tin FROM company WHERE id=?')) {
                            mysqli_stmt_bind_param($st, 'i', $editId);
                            mysqli_stmt_execute($st);
                            $rs = mysqli_stmt_get_result($st);
                            $c = mysqli_fetch_assoc($rs);
                            mysqli_stmt_close($st);
                          }
                        }
                        ?>
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                      <input type="hidden" name="form_timestamp" value="<?php echo time(); ?>">

                      <div class="form-group">
                        <div class="col-md-6 col-sm-6 col-xs-12">
                          <label>Company Name</label>
                          <input type="text" name="company_name" class="form-control" required maxlength="150" value="<?php echo isset($c['company_name'])?htmlspecialchars($c['company_name'],ENT_QUOTES,'UTF-8'):''; ?>" readonly>
                        </div>
                            <div class="col-md-3 col-sm-3 col-xs-12">
                          <label>Company Code</label>
                          <input type="text" name="company_code" class="form-control" required maxlength="50" value="<?php echo isset($c['company_code'])?htmlspecialchars($c['company_code'],ENT_QUOTES,'UTF-8'):''; ?>" readonly>
                        </div>
                        <div class="col-md-3 col-sm-3 col-xs-12">
                          <label>Company ID</label>
                          <input type="text" name="company_ident" class="form-control" required maxlength="100" value="<?php echo isset($c['company_id'])?htmlspecialchars($c['company_id'],ENT_QUOTES,'UTF-8'):''; ?>" readonly>
                        </div>
                      </div>

                              <div class="form-group">
                        <div class="col-md-8 col-sm-8 col-xs-12">
                          <label>Company Address</label>
                          <input type="text" name="company_address" class="form-control" maxlength="255" value="<?php echo isset($c['company_address'])?htmlspecialchars($c['company_address'],ENT_QUOTES,'UTF-8'):''; ?>" readonly>
                        </div>
                        <div class="col-md-4 col-sm-4 col-xs-12">
                          <label>Company TIN</label>
                          <input type="text" name="company_tin" class="form-control" maxlength="50" value="<?php echo isset($c['company_tin'])?htmlspecialchars($c['company_tin'],ENT_QUOTES,'UTF-8'):''; ?>" readonly>
                        </div>
                      </div>
                      
                      <div class="ln_solid"></div>
                      <div class="form-group">
                        <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                          <a class="btn btn-default" href="company_main.php">Back</a>
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
