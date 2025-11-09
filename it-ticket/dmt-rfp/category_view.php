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
                 <h3>Category</h3>
              </div>

              
            </div>
            <div class="clearfix"></div>
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                     <h2>Category <small>details</small></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    <form id="category-form" class="form-horizontal form-label-left" method="post" autocomplete="off" novalidate>
                      <?php
                        if (!isset($_SESSION['csrf_token'])) {
                            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        }
                        $editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
                        if ($editId > 0) echo '<input type="hidden" name="__edit_id" value="'.(int)$editId.'">';
                        $c = null;
                        if ($editId > 0 && isset($mysqlconn) && $mysqlconn) {
                          if ($st = mysqli_prepare($mysqlconn, 'SELECT category_code, category_id, category_name, description, is_active FROM categories WHERE id=?')) {
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
                        <div class="col-md-3 col-sm-3 col-xs-12">
                          <label>Category Code</label>
                          <input type="text" class="form-control" value="<?php echo isset($c['category_code'])?htmlspecialchars($c['category_code'],ENT_QUOTES,'UTF-8'):''; ?>" readonly>
                        </div>
                        <div class="col-md-3 col-sm-3 col-xs-12">
                          <label>Category ID</label>
                          <input type="text" class="form-control" value="<?php echo isset($c['category_id'])?htmlspecialchars($c['category_id'],ENT_QUOTES,'UTF-8'):''; ?>" readonly>
                        </div>
                        <div class="col-md-3 col-sm-3 col-xs-12">
                          <label>Category Name</label>
                          <input type="text" class="form-control" value="<?php echo isset($c['category_name'])?htmlspecialchars($c['category_name'],ENT_QUOTES,'UTF-8'):''; ?>" readonly>
                        </div>
                        <div class="col-md-3 col-sm-3 col-xs-12">
                          <label>Status</label>
                          <input type="text" class="form-control" value="<?php echo ((int)($c['is_active'] ?? 1))===1?'Active':'Inactive'; ?>" readonly>
                        </div>
                      </div>
                      <div class="form-group">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                          <label>Description</label>
                          <textarea class="form-control" rows="3" readonly><?php echo isset($c['description'])?htmlspecialchars($c['description'],ENT_QUOTES,'UTF-8'):''; ?></textarea>
                        </div>
                      </div>
                      
                      <div class="ln_solid"></div>
                      <div class="form-group">
                        <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                         <a class="btn btn-default" href="category_main.php">Back</a>
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
