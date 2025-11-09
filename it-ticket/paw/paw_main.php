<?php 
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d h:i:s", time());
include "blocks/inc.resource.php";

// Initialize fallback data
$currentUserId = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 1;
$requestTypeToCount = ['RFP' => 0, 'ERL' => 0, 'ERGR' => 0];

// Check if database connection exists and tables are available
$dbAvailable = false;
if (isset($mysqlconn) && $mysqlconn) {
  // Check if financial_requests table exists
  $tableCheck = mysqli_query($mysqlconn, "SHOW TABLES LIKE 'financial_requests'");
  if ($tableCheck && mysqli_num_rows($tableCheck) > 0) {
    $dbAvailable = true;
    
    // Fetch counts per request type (RFP, ERL, ERGR) for current session user only
    $countSql = "SELECT request_type, COUNT(*) AS total FROM financial_requests WHERE payee = ? GROUP BY request_type";
    if ($stmtCnt = mysqli_prepare($mysqlconn, $countSql)) {
      mysqli_stmt_bind_param($stmtCnt, 'i', $currentUserId);
      mysqli_stmt_execute($stmtCnt);
      $countResult = mysqli_stmt_get_result($stmtCnt);
      while ($countRow = mysqli_fetch_assoc($countResult)) {
        $requestType = $countRow['request_type'] ?? '';
        $totalForType = (int)($countRow['total'] ?? 0);
        if (array_key_exists($requestType, $requestTypeToCount)) {
          $requestTypeToCount[$requestType] = $totalForType;
        }
      }
      mysqli_stmt_close($stmtCnt);
    }
  }
}

// Fetch available requests to display in the table (current user only)
$requests = [];
if ($dbAvailable) {
  // Check if all required tables exist
  $tablesCheck = [
    'financial_requests',
    'work_flow_process', 
    'financial_request_items',
    'financial_request_breakdowns',
    'sys_usertb'
  ];
  
  $allTablesExist = true;
  foreach ($tablesCheck as $table) {
    $tableCheck = mysqli_query($mysqlconn, "SHOW TABLES LIKE '$table'");
    if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
      $allTablesExist = false;
      break;
    }
  }
  
  if ($allTablesExist) {
    // Include computed flag whether edit is allowed: either any step has 'Return to Requestor'
    // or sequence 2 is still 'Waiting for Approval'
    $listSql = "
      SELECT fr.doc_number,
             fr.request_type,
             fr.company,
             fr.doc_number,
             fr.payee,
             fr.status,
             (
               CASE
                 WHEN EXISTS (
                   SELECT 1 FROM work_flow_process wx
                   WHERE wx.doc_type = fr.request_type AND wx.doc_number = fr.doc_number AND (UPPER(wx.status) = 'DECLINED' OR wx.status LIKE 'Declined%' OR wx.status LIKE 'declined%')
                 ) THEN 'Declined'
                 WHEN EXISTS (
                   SELECT 1 FROM work_flow_process wx
                   WHERE wx.doc_type = fr.request_type AND wx.doc_number = fr.doc_number AND wx.status LIKE 'Return to Requestor%'
                 ) THEN 'Return to Requestor'
                 WHEN EXISTS (
                   SELECT 1 FROM work_flow_process wx
                   WHERE wx.doc_type = fr.request_type AND wx.doc_number = fr.doc_number AND wx.status LIKE 'Return to Approver%'
                 ) THEN 'Return to Approver'
                 WHEN EXISTS (
                   SELECT 1 FROM work_flow_process wx
                   WHERE wx.doc_type = fr.request_type AND wx.doc_number = fr.doc_number AND wx.status LIKE 'Waiting%'
                 ) THEN 'Waiting for Approval'
                 ELSE fr.status
               END
             ) AS status_to_show,
             fr.created_at,
             COALESCE(items.total_amount, 0) AS items_total,
             COALESCE(breaks.total_amount2, 0) AS breakdown_total,
             (
               EXISTS (
                 SELECT 1 FROM work_flow_process wfp
                 WHERE wfp.doc_type = fr.request_type AND wfp.doc_number = fr.doc_number
                   AND wfp.status LIKE 'Return to Requestor%'
               )
               OR EXISTS (
                 SELECT 1 FROM work_flow_process wfp2
                 WHERE wfp2.doc_type = fr.request_type AND wfp2.doc_number = fr.doc_number
                   AND wfp2.sequence = 2 AND wfp2.status LIKE 'Waiting%'
               )
             ) AS can_edit,
             (
               SELECT GROUP_CONCAT(w2.actor_id SEPARATOR ', ')
               FROM work_flow_process w2
               WHERE w2.doc_type = fr.request_type
                 AND w2.doc_number = fr.doc_number
                 AND w2.status LIKE 'Waiting%'
                 AND w2.sequence = (
                   SELECT MIN(w3.sequence)
                   FROM work_flow_process w3
                   WHERE w3.doc_type = fr.request_type
                     AND w3.doc_number = fr.doc_number
                     AND w3.status LIKE 'Waiting%'
                 )
             ) AS current_approvers,
             (
               SELECT COUNT(DISTINCT w1.sequence)
               FROM work_flow_process w1
               WHERE w1.doc_type = fr.request_type
                 AND w1.doc_number = fr.doc_number
                 AND (
                   UPPER(w1.status) = 'APPROVED'
                   OR UPPER(w1.status) = 'DONE'
                   OR w1.status LIKE 'Approved%'
                 )
             ) AS approved_sequences,
             (
               SELECT COUNT(DISTINCT w2.sequence)
               FROM work_flow_process w2
               WHERE w2.doc_type = fr.request_type
                 AND w2.doc_number = fr.doc_number
             ) AS total_sequences,
             (SELECT CONCAT(COALESCE(u.user_firstname,''),' ',COALESCE(u.user_lastname,'')) FROM sys_usertb u WHERE u.id = fr.payee LIMIT 1) AS payee_name
      FROM financial_requests fr
      LEFT JOIN (
        SELECT doc_number, SUM(amount) AS total_amount
        FROM financial_request_items
        GROUP BY doc_number
      ) AS items ON items.doc_number = fr.doc_number
      LEFT JOIN (
        SELECT doc_number, SUM(amount2) AS total_amount2
        FROM financial_request_breakdowns
        GROUP BY doc_number
      ) AS breaks ON breaks.doc_number = fr.doc_number
      WHERE fr.payee = ?
      ORDER BY fr.created_at DESC";
    if ($stmtList = mysqli_prepare($mysqlconn, $listSql)) {
      mysqli_stmt_bind_param($stmtList, 'i', $currentUserId);
      mysqli_stmt_execute($stmtList);
      $listResult = mysqli_stmt_get_result($stmtList);
      while ($row = mysqli_fetch_assoc($listResult)) {
        $requests[] = $row;
      }
      mysqli_stmt_close($stmtList);
    }
  }
}

// Categorize requests by status for tab views
$approvedRequests = [];
$pendingRequests = [];
$cancelledRequests = [];
foreach ($requests as $req) {
  $statusRaw = strtolower(trim((string)($req['status_to_show'] ?? $req['status'] ?? '')));
  if ($statusRaw === 'approved' || $statusRaw === 'done') {
    $approvedRequests[] = $req;
  } elseif ($statusRaw === 'declined' || $statusRaw === 'rejected' || $statusRaw === 'cancelled' || $statusRaw === 'canceled') {
    $cancelledRequests[] = $req;
  } else {
    // pending buckets include waiting and returns as well as generic pending
    if ($statusRaw === 'pending' || strpos($statusRaw, 'waiting') !== false || strpos($statusRaw, 'return') !== false) {
      $pendingRequests[] = $req;
    } else {
      // Fallback: treat unknown as pending to ensure visibility
      $pendingRequests[] = $req;
    }
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
                <h3>Promotional Activity Workplan (PAW) Management</h3>
              </div>
            </div>
            <div class="clearfix"></div>

            <?php if (!$dbAvailable) { ?>
            <div class="alert alert-warning" style="margin-bottom: 20px;">
              <i class="fa fa-exclamation-triangle"></i> 
              <strong>Database Setup Required:</strong> The database tables for the PAW (Promotional Activity Workplan) system are not yet set up. 
              Please run the database setup scripts to enable full functionality. 
              <a href="#" class="btn btn-sm btn-info" style="margin-left: 10px;">
                <i class="fa fa-database"></i> Setup Database
              </a>
            </div>
            <?php } ?>

            <!-- Summary Tiles: PAW Status Overview -->
            <div class="row" style="margin-bottom: 10px;">
              <div class="col-md-3 col-sm-3 col-xs-12">
                <div class="tile-stats js-type-filter" data-filter="pending" style="cursor:pointer;">
                  <div class="icon"><i class="fa fa-clock-o"></i></div>
                  <div class="count"><?php echo count($pendingRequests); ?></div>
                  <h3>Pending</h3>
                  <p>PAWs awaiting approval</p>
                </div>
              </div>
              <div class="col-md-3 col-sm-3 col-xs-12">
                <div class="tile-stats js-type-filter" data-filter="approved" style="cursor:pointer;">
                  <div class="icon"><i class="fa fa-check-circle"></i></div>
                  <div class="count"><?php echo count($approvedRequests); ?></div>
                  <h3>Approved</h3>
                  <p>Approved PAWs</p>
                </div>
              </div>
              <div class="col-md-3 col-sm-3 col-xs-12">
                <div class="tile-stats js-type-filter" data-filter="cancelled" style="cursor:pointer;">
                  <div class="icon"><i class="fa fa-times-circle"></i></div>
                  <div class="count"><?php echo count($cancelledRequests); ?></div>
                  <h3>Cancelled</h3>
                  <p>Declined/Cancelled PAWs</p>
                </div>
              </div>
              <div class="col-md-3 col-sm-3 col-xs-12">
                <div class="tile-stats" style="cursor:pointer;" onclick="window.location.href='paw_form.php'">
                  <div class="icon"><i class="fa fa-plus-circle"></i></div>
                  <div class="count">+</div>
                  <h3>Create PAW</h3>
                  <p>Create new PAW</p>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>PAW Requests <small>view and manage promotional activity workplans</small></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li>
                        <button type="button" class="btn btn-success btn-sm" id="createBtn" name="create" onclick="window.location.href='paw_form.php'">
                          <i class="fa fa-plus"></i> Create PAW
                        </button>
                      </li>
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    <div style="margin-bottom:8px;">
                      <button type="button" class="btn btn-default btn-sm" id="resetFilterBtn"><i class="fa fa-undo"></i> Show All</button>
                      <?php if (isset($_GET['debug']) && $_GET['debug'] === '1') { ?>
                        <div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc; font-size: 12px;">
                          <strong>Debug Info:</strong><br>
                          Total Requests: <?php echo count($requests); ?><br>
                          Pending: <?php echo count($pendingRequests); ?><br>
                          Approved: <?php echo count($approvedRequests); ?><br>
                          Cancelled/Declined: <?php echo count($cancelledRequests); ?><br>
                          <?php if (!empty($requests)) { ?>
                            <strong>Sample Status Values:</strong><br>
                            <?php 
                              $sampleCount = 0;
                              foreach ($requests as $req) {
                                if ($sampleCount >= 5) break;
                                echo "ID: " . $req['id'] . " - Status: '" . ($req['status_to_show'] ?? 'NULL') . "' (Original: '" . ($req['status'] ?? 'NULL') . "')<br>";
                                $sampleCount++;
                              }
                            ?>
                          <?php } ?>
                        </div>
                      <?php } ?>
                    </div>

                    <!-- Tabs -->
                    <ul class="nav nav-tabs" role="tablist">
                      <li role="presentation" class="active"><a href="#tab-pending" aria-controls="tab-pending" role="tab" data-toggle="tab">Pending <span class="badge"><?php echo count($pendingRequests); ?></span></a></li>
                      <li role="presentation"><a href="#tab-approved" aria-controls="tab-approved" role="tab" data-toggle="tab">Approved <span class="badge"><?php echo count($approvedRequests); ?></span></a></li>
                      <li role="presentation"><a href="#tab-cancelled" aria-controls="tab-cancelled" role="tab" data-toggle="tab">Cancelled <span class="badge"><?php echo count($cancelledRequests); ?></span></a></li>
                    </ul>

                    <div class="tab-content" style="margin-top:10px;">
                      <!-- Pending Tab -->
                      <div role="tabpanel" class="tab-pane fade in active" id="tab-pending">
                        <div class="table-responsive">
                          <table class="table table-striped table-bordered" id="tablePending">
                            <thead>
                              <tr>
                                <th>#</th>
                                <th>PAW Number</th>
                                <th>Requestor</th>
                                <th>Total Cost</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php if (empty($pendingRequests)) { ?>
                                <tr class="js-placeholder"><td colspan="7" class="text-center">No records to display.</td></tr>
                              <?php } else { ?>
                                <?php foreach ($pendingRequests as $req) { 
                                  $id = (int)($req['id'] ?? 0);
                                  $doc_number = htmlspecialchars($req['doc_number'] ?? '', ENT_QUOTES, 'UTF-8');
                                  $payee = htmlspecialchars($req['payee_name'] ?? '', ENT_QUOTES, 'UTF-8');
                                  $itemsTotal = isset($req['items_total']) ? (float)$req['items_total'] : 0.0;
                                  $breaksTotal = isset($req['breakdown_total']) ? (float)$req['breakdown_total'] : 0.0;
                                  $amountVal = ($itemsTotal > 0) ? $itemsTotal : $breaksTotal;
                                  $amount = is_numeric($amountVal) ? number_format((float)$amountVal, 2) : '0.00';
                                  $status = htmlspecialchars(($req['status_to_show'] ?? $req['status'] ?? 'pending'), ENT_QUOTES, 'UTF-8');
                                  $canEdit = !empty($req['can_edit']);
                                  $currApprovers = htmlspecialchars((string)($req['current_approvers'] ?? ''), ENT_QUOTES, 'UTF-8');
                                  $created = htmlspecialchars($req['created_at'] ?? '', ENT_QUOTES, 'UTF-8');
                                  $createdDisp = $created ? date('Y-m-d H:i', strtotime($created)) : '';
                                  $labelClass = 'label-warning';
                                  $type = htmlspecialchars($req['request_type'] ?? '', ENT_QUOTES, 'UTF-8');
                                  $approvedSeq = isset($req['approved_sequences']) ? (int)$req['approved_sequences'] : 0;
                                  $totalSeq = 6;
                                  if ($approvedSeq < 0) { $approvedSeq = 0; }
                                  if ($approvedSeq > $totalSeq) { $approvedSeq = $totalSeq; }
                                  $pct = $totalSeq > 0 ? (int)round(($approvedSeq / $totalSeq) * 100) : 0;
                                ?>
                                  <tr data-type="<?php echo $type; ?>">
                                    <td><?php echo $id; ?></td>
                                    <td><span class="label label-primary"><?php echo $doc_number; ?></span></td>
                                    <td><?php echo $payee; ?></td>
                                    <td class="text-right"><?php echo $amount; ?></td>
                                    <td>
                                      <div style="margin-bottom:4px;">
                                        <span class="label <?php echo $labelClass; ?>"><?php echo ucfirst($status); ?></span>
                                        <?php if ($currApprovers) { ?>
                                          <div style="font-size:11px; color:#666; margin-top:4px;">Current Approver: <?php echo $currApprovers; ?></div>
                                        <?php } ?>
                                      </div>
                                      <div class="progress" style="height:10px; margin-bottom:0;">
                                        <div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="<?php echo $pct; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $pct; ?>%;">
                                        </div>
                                      </div>
                                      <div style="font-size:11px; color:#666; margin-top:2px;">
                                        <?php echo $approvedSeq; ?>/<?php echo $totalSeq; ?> steps (<?php echo $pct; ?>%)
                                      </div>
                                    </td>
                                    <td><?php echo $createdDisp; ?></td>
                                    <td>
                                      <button type="button" class="btn btn-xs btn-info" onclick="window.location.href='paw_view.php?id=<?php echo $id; ?>'">
                                        <i class="fa fa-eye"></i> View
                                      </button>
                                      <?php if ($canEdit) { ?>
                                      <button type="button" class="btn btn-xs btn-warning" onclick="window.location.href='paw_edit_form.php?id=<?php echo $id; ?>'">
                                        <i class="fa fa-pencil"></i> Edit
                                      </button>
                                      <?php } ?>
                                      <a class="btn btn-xs btn-success" href="print_paw.php?id=<?php echo $id; ?>" target="_blank">
                                        <i class="fa fa-print"></i> Print
                                      </a>
                                    </td>
                                  </tr>
                                <?php } ?>
                              <?php } ?>
                            </tbody>
                          </table>
                        </div>
                      </div>

                      <!-- Approved Tab -->
                      <div role="tabpanel" class="tab-pane fade" id="tab-approved">
                        <div class="table-responsive">
                          <table class="table table-striped table-bordered" id="tableApproved">
                            <thead>
                              <tr>
                                <th>#</th>
                                <th>PAW Number</th>
                                <th>Requestor</th>
                                <th>Total Cost</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php if (empty($approvedRequests)) { ?>
                                <tr class="js-placeholder"><td colspan="7" class="text-center">No records to display.</td></tr>
                              <?php } else { ?>
                                <?php foreach ($approvedRequests as $req) { 
                                  $id = (int)($req['id'] ?? 0);
                                  $doc_number = htmlspecialchars($req['doc_number'] ?? '', ENT_QUOTES, 'UTF-8');
                                  $payee = htmlspecialchars($req['payee_name'] ?? '', ENT_QUOTES, 'UTF-8');
                                  $itemsTotal = isset($req['items_total']) ? (float)$req['items_total'] : 0.0;
                                  $breaksTotal = isset($req['breakdown_total']) ? (float)$req['breakdown_total'] : 0.0;
                                  $amountVal = ($itemsTotal > 0) ? $itemsTotal : $breaksTotal;
                                  $amount = is_numeric($amountVal) ? number_format((float)$amountVal, 2) : '0.00';
                                  $status = htmlspecialchars(($req['status_to_show'] ?? $req['status'] ?? 'approved'), ENT_QUOTES, 'UTF-8');
                                  $canEdit = !empty($req['can_edit']);
                                  $currApprovers = htmlspecialchars((string)($req['current_approvers'] ?? ''), ENT_QUOTES, 'UTF-8');
                                  $created = htmlspecialchars($req['created_at'] ?? '', ENT_QUOTES, 'UTF-8');
                                  $createdDisp = $created ? date('Y-m-d H:i', strtotime($created)) : '';
                                  $labelClass = 'label-success';
                                  $type = htmlspecialchars($req['request_type'] ?? '', ENT_QUOTES, 'UTF-8');
                                ?>
                                  <tr data-type="<?php echo $type; ?>">
                                    <td><?php echo $id; ?></td>
                                    <td><span class="label label-primary"><?php echo $doc_number; ?></span></td>
                                    <td><?php echo $payee; ?></td>
                                    <td class="text-right"><?php echo $amount; ?></td>
                                    <td>
                                      <span class="label <?php echo $labelClass; ?>"><?php echo ucfirst($status); ?></span>
                                    </td>
                                    <td><?php echo $createdDisp; ?></td>
                                    <td>
                                      <button type="button" class="btn btn-xs btn-info" onclick="window.location.href='paw_view.php?id=<?php echo $id; ?>'">
                                        <i class="fa fa-eye"></i> View
                                      </button>
                                      <a class="btn btn-xs btn-success" href="print_paw.php?id=<?php echo $id; ?>" target="_blank">
                                        <i class="fa fa-print"></i> Print
                                      </a>
                                    </td>
                                  </tr>
                                <?php } ?>
                              <?php } ?>
                            </tbody>
                          </table>
                        </div>
                      </div>

                      <!-- Cancelled Tab (Declined + Cancelled) -->
                      <div role="tabpanel" class="tab-pane fade" id="tab-cancelled">
                        <div class="alert alert-info" style="margin-bottom: 15px;">
                          <i class="fa fa-info-circle"></i> <strong>Note:</strong> This tab shows both declined and cancelled PAWs. Declined PAWs appear here when they are rejected during the approval process.
                        </div>
                        <div class="table-responsive">
                          <table class="table table-striped table-bordered" id="tableCancelled">
                            <thead>
                              <tr>
                                <th>#</th>
                                <th>PAW Number</th>
                                <th>Requestor</th>
                                <th>Total Cost</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php if (empty($cancelledRequests)) { ?>
                                <tr class="js-placeholder"><td colspan="7" class="text-center">No records to display.</td></tr>
                              <?php } else { ?>
                                <?php foreach ($cancelledRequests as $req) { 
                                  $id = (int)($req['id'] ?? 0);
                                  $doc_number = htmlspecialchars($req['doc_number'] ?? '', ENT_QUOTES, 'UTF-8');
                                  $payee = htmlspecialchars($req['payee_name'] ?? '', ENT_QUOTES, 'UTF-8');
                                  $itemsTotal = isset($req['items_total']) ? (float)$req['items_total'] : 0.0;
                                  $breaksTotal = isset($req['breakdown_total']) ? (float)$req['breakdown_total'] : 0.0;
                                  $amountVal = ($itemsTotal > 0) ? $itemsTotal : $breaksTotal;
                                  $amount = is_numeric($amountVal) ? number_format((float)$amountVal, 2) : '0.00';
                                  $status = htmlspecialchars(($req['status_to_show'] ?? $req['status'] ?? 'cancelled'), ENT_QUOTES, 'UTF-8');
                                  $created = htmlspecialchars($req['created_at'] ?? '', ENT_QUOTES, 'UTF-8');
                                  $createdDisp = $created ? date('Y-m-d H:i', strtotime($created)) : '';
                                  $labelClass = 'label-danger';
                                  $type = htmlspecialchars($req['request_type'] ?? '', ENT_QUOTES, 'UTF-8');
                                ?>
                                  <tr data-type="<?php echo $type; ?>">
                                    <td><?php echo $id; ?></td>
                                    <td><span class="label label-primary"><?php echo $doc_number; ?></span></td>
                                    <td><?php echo $payee; ?></td>
                                    <td class="text-right"><?php echo $amount; ?></td>
                                    <td>
                                      <span class="label <?php echo $labelClass; ?>"><?php echo ucfirst($status); ?></span>
                                    </td>
                                    <td><?php echo $createdDisp; ?></td>
                                    <td>
                                      <button type="button" class="btn btn-xs btn-info" onclick="window.location.href='paw_view.php?id=<?php echo $id; ?>'">
                                        <i class="fa fa-eye"></i> View
                                      </button>
                                      <a class="btn btn-xs btn-success" href="print_paw.php?id=<?php echo $id; ?>" target="_blank">
                                        <i class="fa fa-print"></i> Print
                                      </a>
                                    </td>
                                  </tr>
                                <?php } ?>
                              <?php } ?>
                            </tbody>
                          </table>
                        </div>
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
    <script>
      (function() {
        // Tamper prevention - disable developer tools and inspection
        function initializeTamperPrevention() {
          // Disable right-click on the page
          document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
          });

          // Disable certain keyboard shortcuts
          document.addEventListener('keydown', function(e) {
            // F12, Ctrl+Shift+I, Ctrl+U
            if (e.key === 'F12' || 
                (e.ctrlKey && e.shiftKey && e.key === 'I') || 
                (e.ctrlKey && e.key === 'u')) {
              e.preventDefault();
              return false;
            }
          });

          // Disable F12 key specifically
          document.addEventListener('keydown', function(e) {
            if (e.key === 'F12') {
              e.preventDefault();
              return false;
            }
          });

          // Disable Ctrl+Shift+C (Chrome DevTools)
          document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && e.key === 'C') {
              e.preventDefault();
              return false;
            }
          });

          // Disable Ctrl+Shift+J (Chrome Console)
          document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && e.key === 'J') {
              e.preventDefault();
              return false;
            }
          });

          // Disable Ctrl+Shift+K (Firefox Console)
          document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && e.key === 'K') {
              e.preventDefault();
              return false;
            }
          });
        }

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
          
          // Show filter notification
          if (filter && filter !== '') {
            showFilterNotification(filter);
          } else {
            hideFilterNotification();
          }
        }
        
        function showFilterNotification(filterType) {
          var existingNotification = document.getElementById('filter-notification');
          if (existingNotification) {
            existingNotification.remove();
          }
          
          var notification = document.createElement('div');
          notification.id = 'filter-notification';
          notification.style.cssText = 'background: #e7f3ff; border: 1px solid #b3d9ff; color: #0066cc; padding: 10px; margin: 10px 0; border-radius: 4px; font-size: 14px;';
          notification.innerHTML = '<strong>Filter Active:</strong> Showing only ' + filterType + ' PAWs. <strong>Note:</strong> Declined PAWs will appear in the "Cancelled" tab. <button type="button" class="btn btn-xs btn-default" onclick="hideFilterNotification()" style="float: right; margin-top: -5px;">×</button>';
          
          var contentArea = document.querySelector('.x_content');
          if (contentArea) {
            contentArea.insertBefore(notification, contentArea.firstChild);
          }
        }
        
        function hideFilterNotification() {
          var notification = document.getElementById('filter-notification');
          if (notification) {
            notification.remove();
          }
        }

        function applyFilter(filter) {
          var tabIds = ['tablePending','tableApproved','tableCancelled'];
          var tabCounts = {pending: 0, approved: 0, cancelled: 0};
          
          for (var t = 0; t < tabIds.length; t++) {
            var table = document.getElementById(tabIds[t]);
            if (!table) continue;
            var tbody = table.querySelector('tbody');
            if (!tbody) continue;
            var rows = tbody.querySelectorAll('tr');
            var anyVisible = false;
            var visibleCount = 0;
            
            for (var i = 0; i < rows.length; i++) {
              var r = rows[i];
              if (r.classList.contains('js-placeholder')) continue;
              
              // For PAW filtering, show all rows in the appropriate tab
              var show = true;
              if (filter && filter !== '') {
                // If filtering by status, only show rows in the matching tab
                var tabName = tabIds[t].replace('table', '').toLowerCase();
                show = (tabName === filter);
              }
              
              r.style.display = show ? '' : 'none';
              if (show) {
                anyVisible = true;
                visibleCount++;
              }
            }
            
            // Update tab counts based on visible rows
            if (tabIds[t] === 'tablePending') {
              tabCounts.pending = visibleCount;
            } else if (tabIds[t] === 'tableApproved') {
              tabCounts.approved = visibleCount;
            } else if (tabIds[t] === 'tableCancelled') {
              tabCounts.cancelled = visibleCount;
            }
            
            // handle placeholder per table
            var placeholder = tbody.querySelector('.js-placeholder');
            if (placeholder) {
              placeholder.style.display = anyVisible ? 'none' : '';
            } else if (!anyVisible) {
              var tr = document.createElement('tr');
              tr.className = 'js-placeholder';
              var td = document.createElement('td');
              td.colSpan = 7;
              td.className = 'text-center';
              td.textContent = 'No records to display.';
              tr.appendChild(td);
              tbody.appendChild(tr);
            }
          }
          
          // Update the tab badges with filtered counts
          updateTabCounts(tabCounts);
        }
        
        function updateTabCounts(counts) {
          // Update pending tab count
          var pendingTab = document.querySelector('a[href="#tab-pending"]');
          if (pendingTab) {
            var pendingBadge = pendingTab.querySelector('.badge');
            if (pendingBadge) {
              pendingBadge.textContent = counts.pending;
            }
          }
          
          // Update approved tab count
          var approvedTab = document.querySelector('a[href="#tab-approved"]');
          if (approvedTab) {
            var approvedBadge = approvedTab.querySelector('.badge');
            if (approvedBadge) {
              approvedBadge.textContent = counts.approved;
            }
          }
          
          // Update cancelled tab count
          var cancelledTab = document.querySelector('a[href="#tab-cancelled"]');
          if (cancelledTab) {
            var cancelledBadge = cancelledTab.querySelector('.badge');
            if (cancelledBadge) {
              cancelledBadge.textContent = counts.cancelled;
            }
            
            // Highlight cancelled tab if it contains declined requests
            if (counts.cancelled > 0) {
              cancelledTab.style.borderBottom = '3px solid #d9534f';
              cancelledTab.style.fontWeight = 'bold';
            } else {
              cancelledTab.style.borderBottom = '';
              cancelledTab.style.fontWeight = '';
            }
          }
        }

        document.addEventListener('DOMContentLoaded', function() {
          // Initialize tamper prevention
          initializeTamperPrevention();
          
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
              // Ensure all tabs show correct counts after reset
              setTimeout(function() {
                updateTabCounts({
                  pending: document.querySelectorAll('#tablePending tbody tr:not(.js-placeholder)').length,
                  approved: document.querySelectorAll('#tableApproved tbody tr:not(.js-placeholder)').length,
                  cancelled: document.querySelectorAll('#tableCancelled tbody tr:not(.js-placeholder)').length
                });
              }, 100);
            });
          }
        });
      })();
    </script>
  </body>
</html>


