<?php 
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d h:i:s", time());
include "blocks/inc.resource.php";

// Pagination setup (15 per page)
$perPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) { $page = 1; }
$total = 0;
$totalPages = 1;
$offset = 0;
// Search
$search = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$whereClause = '';
if (isset($mysqlconn) && $mysqlconn && $search !== '') {
  $esc = mysqli_real_escape_string($mysqlconn, $search);
  $like = "'%" . $esc . "%'";
  $whereClause = "WHERE category_name LIKE $like OR category_code LIKE $like OR category_id LIKE $like";
}

// Fetch categories
$categories = [];
if (isset($mysqlconn) && $mysqlconn) {
  // Total count
  if ($cntRes = mysqli_query($mysqlconn, "SELECT COUNT(*) AS cnt FROM categories $whereClause")) {
    $cntRow = mysqli_fetch_assoc($cntRes);
    $total = (int)($cntRow['cnt'] ?? 0);
    $totalPages = max(1, (int)ceil($total / $perPage));
    if ($page > $totalPages) { $page = $totalPages; }
    $offset = ($page - 1) * $perPage;
  }

  $limit = (int)$perPage;
  $off = (int)$offset;
  $listSql = "SELECT id, category_code, category_id, category_name, is_active, created_at FROM categories $whereClause ORDER BY created_at DESC LIMIT $limit OFFSET $off";
  if ($listResult = mysqli_query($mysqlconn, $listSql)) {
    while ($row = mysqli_fetch_assoc($listResult)) { $categories[] = $row; }
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
                <h3>Category Management</h3>
              </div>
            </div>
            <div class="clearfix"></div>

            <!-- Toolbar -->
            <div class="row" style="margin-bottom: 10px;"></div>

            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Categories <small>view and manage</small></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li>
                        <button type="button" class="btn btn-success btn-sm" id="createBtn" name="create" onclick="window.location.href='category_form.php'">
                          <i class="fa fa-plus"></i> Create
                        </button>
                      </li>
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    <div class="table-responsive">
                      <div style="margin-bottom:8px;" class="row">
                        <div class="col-sm-6">
                          <form method="get" class="form-inline" autocomplete="off" style="margin-bottom:8px;">
                            <div class="form-group">
                          <input type="text" name="q" class="form-control input-sm" placeholder="Search categories..." value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Search</button>
                            <?php if ($search !== '') { ?>
                              <a class="btn btn-default btn-sm" href="category_main.php"><i class="fa fa-undo"></i> Reset</a>
                            <?php } ?>
                          </form>
                        </div>
                        <div class="col-sm-6 text-right">
                          <button type="button" class="btn btn-default btn-sm" id="resetFilterBtn"><i class="fa fa-undo"></i> Show All</button>
                        </div>
                      </div>
                      <table class="table table-striped table-bordered" id="requestsTable">
                        <thead>
                            <tr>
                              <th>#</th>
                              <th>Category</th>
                              <th>Category Code</th>
                              <th>Category ID</th>
                              <th>Status</th>
                              <th>Created</th>
                              <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                          <?php if (empty($categories)) { ?>
                            <tr>
                              <td colspan="8" class="text-center">No records to display.</td>
                            </tr>
                          <?php } else { ?>
                            <?php foreach ($categories as $cat) { 
                              $id = (int)($cat['id'] ?? 0);
                              $name = htmlspecialchars($cat['category_name'] ?? '', ENT_QUOTES, 'UTF-8');
                              $code = htmlspecialchars($cat['category_code'] ?? '', ENT_QUOTES, 'UTF-8');
                              $cid  = htmlspecialchars($cat['category_id'] ?? '', ENT_QUOTES, 'UTF-8');
                              $active = ((int)($cat['is_active'] ?? 1)) === 1 ? 'Active' : 'Inactive';
                              $created = htmlspecialchars($cat['created_at'] ?? '', ENT_QUOTES, 'UTF-8');
                              $createdDisp = $created ? date('Y-m-d H:i', strtotime($created)) : '';
                            ?>
                              <tr>
                                <td><?php echo $id; ?></td>
                                <td><?php echo $name; ?></td>
                                <td><?php echo $code; ?></td>
                                <td><?php echo $cid; ?></td>
                                <td><?php echo $active; ?></td>
                                <td><?php echo $createdDisp; ?></td>
                                <td>
                                  <button type="button" class="btn btn-xs btn-info" onclick="window.location.href='category_view.php?id=<?php echo $id; ?>'">
                                    <i class="fa fa-eye"></i> View
                                  </button>
                                  <button type="button" class="btn btn-xs btn-warning" onclick="window.location.href='category_form.php?id=<?php echo $id; ?>'">
                                    <i class="fa fa-pencil"></i> Edit
                                  </button>
                                </td>
                              </tr>
                            <?php } ?>
                          <?php } ?>
                        </tbody>
                      </table>
                      <?php if ($totalPages > 1) { ?>
                        <nav aria-label="Department pagination" class="text-center">
                          <ul class="pagination pagination-sm">
                            <?php 
                              $prev = max(1, $page - 1);
                              $next = min($totalPages, $page + 1);
                            ?>
                            <li class="<?php echo ($page <= 1 ? 'disabled' : ''); ?>">
                              <a href="?page=<?php echo $prev; ?>" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a>
                            </li>
                            <?php for ($p = 1; $p <= $totalPages; $p++) { ?>
                              <li class="<?php echo ($p === $page ? 'active' : ''); ?>"><a href="?page=<?php echo $p; ?>"><?php echo $p; ?></a></li>
                            <?php } ?>
                            <li class="<?php echo ($page >= $totalPages ? 'disabled' : ''); ?>">
                              <a href="?page=<?php echo $next; ?>" aria-label="Next"><span aria-hidden="true">&raquo;</span></a>
                            </li>
                          </ul>
                        </nav>
                      <?php } ?>
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
    <script>
      (function() {
        function setActiveTile(filter) {
          var tiles = document.querySelectorAll('.js-type-filter');
          for (var i = 0; i < tiles.length; i++) {
            var t = tiles[i];
            if (t.getAttribute('data-filter') === filter) {
              t.classList.add('active');
            } else {
              t.classList.remove('active');
            }
          }
        }

        function applyFilter(filter) {
          var rows = document.querySelectorAll('#requestsTable tbody tr');
          var anyVisible = false;
          for (var i = 0; i < rows.length; i++) {
            var r = rows[i];
            var type = r.getAttribute('data-type');
            var show = !filter || filter === '' || type === filter;
            r.style.display = show ? '' : 'none';
            if (show) anyVisible = true;
          }
          // If no visible rows and there was data, add a placeholder row
          var tbody = document.querySelector('#requestsTable tbody');
          if (!tbody) return;
          var placeholder = document.getElementById('noRowsPlaceholder');
          if (!anyVisible) {
            if (!placeholder) {
              var tr = document.createElement('tr');
              tr.id = 'noRowsPlaceholder';
              var td = document.createElement('td');
              td.colSpan = 7;
              td.className = 'text-center';
              td.textContent = 'No records to display.';
              tr.appendChild(td);
              tbody.appendChild(tr);
            }
          } else if (placeholder) {
            placeholder.parentNode.removeChild(placeholder);
          }
        }

        document.addEventListener('DOMContentLoaded', function() {
          var tiles = document.querySelectorAll('.js-type-filter');
          for (var i = 0; i < tiles.length; i++) {
            tiles[i].addEventListener('click', function() {
              var filter = this.getAttribute('data-filter');
              setActiveTile(filter);
              applyFilter(filter);
            });
          }
          var resetBtn = document.getElementById('resetFilterBtn');
          if (resetBtn) {
            resetBtn.addEventListener('click', function() {
              setActiveTile('');
              applyFilter('');
            });
          }
        });
      })();
    </script>
  </body>
</html>


