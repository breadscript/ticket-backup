<?php 
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d h:i:s", time());
include "blocks/inc.resource.php";
// Fetch counts per request type (RFP, ERL, ERGR) where current session user is an actor awaiting action
$currentUserId = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
$currentUserUsername = isset($_SESSION['username']) ? (string)$_SESSION['username'] : '';
$currentUserFirstName = (string)($_SESSION['userfirstname'] ?? '');
$currentUserLastName = (string)($_SESSION['userlastname'] ?? '');
$currentUserFullName = trim($currentUserFirstName.' '.$currentUserLastName);
$requestTypeToCount = ['RFP' => 0, 'ERL' => 0, 'ERGR' => 0];
if (isset($mysqlconn) && $mysqlconn) {
  $countSql = "
    SELECT fr.request_type, COUNT(*) AS total
    FROM financial_requests fr
    WHERE EXISTS (
      SELECT 1 FROM work_flow_process w
      WHERE w.doc_type = fr.request_type
        AND w.doc_number = fr.doc_number
        AND (
          w.actor_id = ?
          OR w.actor_id = ?
          OR w.actor_id = ?
          OR w.actor_id = ?
          OR w.actor_id = ?
          OR REPLACE(UPPER(w.actor_id),' ','') = REPLACE(UPPER(?),' ','')
        )
    )
    GROUP BY fr.request_type";
  if ($stmtCnt = mysqli_prepare($mysqlconn, $countSql)) {
    $uidStr = (string)$currentUserId;
    mysqli_stmt_bind_param($stmtCnt, 'ssssss', $currentUserFullName, $currentUserUsername, $uidStr, $currentUserFirstName, $currentUserLastName, $currentUserFullName);
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

// Fetch available requests to display in the table (where current user is an actor awaiting action)
$requests = [];
if (isset($mysqlconn) && $mysqlconn) {
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
    WHERE EXISTS (
      SELECT 1 FROM work_flow_process w
      WHERE w.doc_type = fr.request_type
        AND w.doc_number = fr.doc_number
        AND (
          w.actor_id = ?
          OR w.actor_id = ?
          OR w.actor_id = ?
          OR w.actor_id = ?
          OR w.actor_id = ?
          OR REPLACE(UPPER(w.actor_id),' ','') = REPLACE(UPPER(?),' ','')
        )
    )
    ORDER BY fr.created_at DESC";
  if ($stmtList = mysqli_prepare($mysqlconn, $listSql)) {
    $uidStr2 = (string)$currentUserId;
    mysqli_stmt_bind_param($stmtList, 'ssssss', $currentUserFullName, $currentUserUsername, $uidStr2, $currentUserFirstName, $currentUserLastName, $currentUserFullName);
    mysqli_stmt_execute($stmtList);
    $listResult = mysqli_stmt_get_result($stmtList);
    while ($row = mysqli_fetch_assoc($listResult)) {
      $requests[] = $row;
    }
    mysqli_stmt_close($stmtList);
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
                <h3>Disbursement Management</h3>
              </div>
            </div>
            <div class="clearfix"></div>

            <!-- Summary Tiles: Interactive Filters by Request Type -->
            <div class="row" style="margin-bottom: 10px;">
              <div class="col-md-4 col-sm-4 col-xs-12">
                <div class="tile-stats js-type-filter" data-filter="RFP" style="cursor:pointer;">
                  <div class="icon"><i class="fa fa-file-text-o"></i></div>
                  <div class="count"><?php echo (int)$requestTypeToCount['RFP']; ?></div>
                  <h3>RFP</h3>
                  <p>Click to show only RFP requests</p>
                </div>
              </div>
              <div class="col-md-4 col-sm-4 col-xs-12">
                <div class="tile-stats js-type-filter" data-filter="ERL" style="cursor:pointer;">
                  <div class="icon"><i class="fa fa-list-alt"></i></div>
                  <div class="count"><?php echo (int)$requestTypeToCount['ERL']; ?></div>
                  <h3>ERL</h3>
                  <p>Click to show only ERL requests</p>
                </div>
              </div>
              <div class="col-md-4 col-sm-4 col-xs-12">
                <div class="tile-stats js-type-filter" data-filter="ERGR" style="cursor:pointer;">
                  <div class="icon"><i class="fa fa-file-o"></i></div>
                  <div class="count"><?php echo (int)$requestTypeToCount['ERGR']; ?></div>
                  <h3>ERGR</h3>
                  <p>Click to show only ERGR requests</p>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Financial Requests <small>view and manage requests</small></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li>
                        <!-- <button type="button" class="btn btn-success btn-sm" id="createBtn" name="create" onclick="window.location.href='disbursement_form.php'">
                          <i class="fa fa-plus"></i> Create
                        </button> -->
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
                                <th>Document Number</th>
                                <th>Payee</th>
                                <th>Amount</th>
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
                                      <button type="button" class="btn btn-xs btn-info" onclick="window.location.href='disbursement_approver_view.php?id=<?php echo $id; ?>'">
                                        <i class="fa fa-eye"></i> View
                                      </button>
                                      <a class="btn btn-xs btn-success" href="print_financial_request.php?id=<?php echo $id; ?>" target="_blank">
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
                                <th>Document Number</th>
                                <th>Payee</th>
                                <th>Amount</th>
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
                                      <button type="button" class="btn btn-xs btn-info" onclick="window.location.href='disbursement_approver_view.php?id=<?php echo $id; ?>'">
                                        <i class="fa fa-eye"></i> View
                                      </button>
                                      <a class="btn btn-xs btn-success" href="print_financial_request.php?id=<?php echo $id; ?>" target="_blank">
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
                          <i class="fa fa-info-circle"></i> <strong>Note:</strong> This tab shows both declined and cancelled requests. Declined requests appear here when they are rejected during the approval process.
                        </div>
                        <div class="table-responsive">
                          <table class="table table-striped table-bordered" id="tableCancelled">
                            <thead>
                              <tr>
                                <th>#</th>
                                <th>Document Number</th>
                                <th>Payee</th>
                                <th>Amount</th>
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
                                      <button type="button" class="btn btn-xs btn-info" onclick="window.location.href='disbursement_approver_view.php?id=<?php echo $id; ?>'">
                                        <i class="fa fa-eye"></i> View
                                      </button>
                                      <a class="btn btn-xs btn-success" href="print_financial_request.php?id=<?php echo $id; ?>" target="_blank">
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
              var type = r.getAttribute('data-type');
              var show = !filter || filter === '' || type === filter;
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
            });
          }
        });
      })();
    </script>
  </body>
</html>


