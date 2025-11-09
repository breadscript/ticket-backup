<?php 
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d h:i:s",time());	
include "blocks/inc.resource.php";

// Initialize fallback data
$companies = [];
$departments = [];
$categories = [];

// Check if database connection exists and tables are available
$dbAvailable = false;
if (isset($mysqlconn) && $mysqlconn) {
  // Check if required tables exist
  $tablesCheck = ['company', 'department', 'categories'];
  $allTablesExist = true;
  
  foreach ($tablesCheck as $table) {
    $tableCheck = mysqli_query($mysqlconn, "SHOW TABLES LIKE '$table'");
    if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
      $allTablesExist = false;
      break;
    }
  }
  
  if ($allTablesExist) {
    $dbAvailable = true;
    
    // Load companies and departments for dropdowns
    if ($res = mysqli_query($mysqlconn, "SELECT company_code, company_name, company_id FROM company ORDER BY company_code")) {
      while ($r = mysqli_fetch_assoc($res)) { $companies[] = $r; }
    }
    if ($res2 = mysqli_query($mysqlconn, "SELECT company_code, department_code, department_name, department_id FROM department ORDER BY company_code, department_code")) {
      while ($r2 = mysqli_fetch_assoc($res2)) { $departments[] = $r2; }
    }
    if ($res3 = mysqli_query($mysqlconn, "SELECT category_code, category_name FROM categories WHERE is_active = 1 ORDER BY category_name")) {
      while ($r3 = mysqli_fetch_assoc($res3)) { $categories[] = $r3; }
    }
  }
}

// Add fallback data if database is not available
if (!$dbAvailable) {
  $companies = [
    ['company_code' => 'MPAV', 'company_name' => 'Metro Pacific Agro Ventures', 'company_id' => 1],
    ['company_code' => 'SAMPLE', 'company_name' => 'Sample Company', 'company_id' => 2]
  ];
  $departments = [
    ['company_code' => 'MPAV', 'department_code' => 'SALES', 'department_name' => 'Sales Department', 'department_id' => 1],
    ['company_code' => 'MPAV', 'department_code' => 'MARKETING', 'department_name' => 'Marketing Department', 'department_id' => 2]
  ];
  $categories = [
    ['category_code' => 'PROMO', 'category_name' => 'Promotional Activities'],
    ['category_code' => 'EVENT', 'category_name' => 'Special Events']
  ];
}
?>

<body class="nav-md">
  <div class="container body">
    <div class="main_container">
      <?php include "blocks/navigation.php";?>
      <?php include "blocks/header.php";?>

      <div class="right_col" role="main">
        <div class="">
          <div class="page-title">
            <div class="title_left">
              <h3>Promotional Activity Workplan</h3>
            </div>
          </div>
          <div class="clearfix"></div>

          <?php if (!$dbAvailable) { ?>
          <div class="alert alert-info" style="margin-bottom: 20px;">
            <i class="fa fa-info-circle"></i> 
            <strong>Demo Mode:</strong> The database tables are not yet set up. You can still create and preview PAW forms, but data will not be saved until the database is configured.
          </div>
          <?php } ?>
          
          <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
              <div class="x_panel">
                <div class="x_title">
                  <h2>METRO PACIFIC AGRO VENTURES - PROMOTIONAL ACTIVITY WORKPLAN</h2>
                  <div class="clearfix"></div>
                </div>
                <div class="x_content">
                  
                  <form id="paw-form" class="form-horizontal form-label-left" method="post" enctype="multipart/form-data" action="save_paw.php">
                    
                    <!-- CSRF Protection -->
                    <?php
                      if (!isset($_SESSION['csrf_token'])) {
                          $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                      }
                    ?>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    
                    <!-- Header Section -->
                    <div class="x_panel">
                      <div class="x_title">
                        <h3><i class="fa fa-header"></i> Header Information</h3>
                        <div class="clearfix"></div>
                      </div>
                      <div class="x_content">
                        <div class="form-group">
                          <div class="col-md-3 col-sm-3 col-xs-12">
                            <label>PAW NO.</label>
                            <input type="text" name="paw_no" class="form-control" placeholder="Auto-generated" readonly>
                          </div>
                          <div class="col-md-3 col-sm-3 col-xs-12">
                            <label>Internal Order No.</label>
                            <input type="text" name="internal_order_no" class="form-control" placeholder="Enter internal order number">
                          </div>
                          <div class="col-md-3 col-sm-3 col-xs-12">
                            <label>Date</label>
                            <input type="date" name="paw_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                          </div>
                          <div class="col-md-3 col-sm-3 col-xs-12">
                            <label>Company</label>
                            <select name="company" class="form-control" required>
                              <option value="">Choose..</option>
                              <?php foreach ($companies as $co) { ?>
                                <option value="<?php echo htmlspecialchars($co['company_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($co['company_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?></option>
                              <?php } ?>
                            </select>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Brand Specifics Section -->
                    <div class="x_panel">
                      <div class="x_title">
                        <h3><i class="fa fa-tags"></i> Brand Specifics</h3>
                        <div class="clearfix"></div>
                      </div>
                      <div class="x_content">
                        <div class="form-group">
                          <div class="col-md-4 col-sm-4 col-xs-12">
                            <label>Brand/SKUs (Product Highlighted)</label>
                            <input type="text" name="brand_skus" class="form-control" placeholder="Enter brand/SKUs">
                          </div>
                          <div class="col-md-4 col-sm-4 col-xs-12">
                            <label>Lead Brand/SKUs (Omnibus)</label>
                            <input type="text" name="lead_brand_skus" class="form-control" placeholder="Enter lead brand/SKUs">
                          </div>
                          <div class="col-md-4 col-sm-4 col-xs-12">
                            <label>Participating Brands/SKUs</label>
                            <input type="text" name="participating_brands" class="form-control" placeholder="Enter participating brands">
                          </div>
                        </div>
                        <div class="form-group">
                          <div class="col-md-6 col-sm-6 col-xs-12">
                            <label>Sales Group</label>
                            <input type="text" name="sales_group" class="form-control" placeholder="Enter sales group">
                          </div>
                          <div class="col-md-6 col-sm-6 col-xs-12">
                            <label>Brand/SKUs (Sharing)</label>
                            <input type="text" name="sharing_brand_skus" class="form-control" placeholder="Enter sharing brand/SKUs">
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Type of Activity Section -->
                    <div class="x_panel">
                      <div class="x_title">
                        <h3><i class="fa fa-list"></i> Type of Activity</h3>
                        <div class="clearfix"></div>
                      </div>
                      <div class="x_content">
                        <div class="form-group">
                          <div class="col-md-3 col-sm-3 col-xs-12">
                            <label>Trade and Consumer Promotion</label>
                            <div class="checkbox">
                              <label><input type="checkbox" name="activity_listing_fee" value="1"> Listing Fee</label>
                            </div>
                            <div class="checkbox">
                              <label><input type="checkbox" name="activity_rentables" value="1"> Rentables</label>
                            </div>
                            <div class="checkbox">
                              <label><input type="checkbox" name="activity_product_sampling" value="1"> Product Sampling</label>
                            </div>
                            <div class="checkbox">
                              <label><input type="checkbox" name="activity_merchandising" value="1"> Merchandising Materials</label>
                            </div>
                            <div class="checkbox">
                              <label><input type="checkbox" name="activity_special_events" value="1"> Special Events</label>
                            </div>
                            <div class="checkbox">
                              <label><input type="checkbox" name="activity_product_donation" value="1"> Product Donation</label>
                            </div>
                            <div class="checkbox">
                              <label><input type="checkbox" name="activity_other" value="1"> Other Initiatives</label>
                            </div>
                          </div>
                          <div class="col-md-3 col-sm-3 col-xs-12">
                            <label>Specific Scheme</label>
                            <textarea name="specific_scheme" class="form-control" rows="6" placeholder="Describe the specific scheme"></textarea>
                          </div>
                          <div class="col-md-3 col-sm-3 col-xs-12">
                            <label>Title</label>
                            <textarea name="activity_title" class="form-control" rows="6" placeholder="Enter activity title"></textarea>
                          </div>
                          <div class="col-md-3 col-sm-3 col-xs-12">
                            <label>Mechanics</label>
                            <textarea name="activity_mechanics" class="form-control" rows="6" placeholder="Describe the mechanics"></textarea>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Promo Objectives Section -->
                    <div class="x_panel">
                      <div class="x_title">
                        <h3><i class="fa fa-bullseye"></i> Promo Objectives</h3>
                        <div class="clearfix"></div>
                      </div>
                      <div class="x_content">
                        <div class="form-group">
                          <div class="col-md-6 col-sm-6 col-xs-12">
                            <label>Promo Objectives</label>
                            <div class="checkbox">
                              <label><input type="checkbox" name="objective_product_availability" value="1"> Product Availability</label>
                            </div>
                            <div class="checkbox">
                              <label><input type="checkbox" name="objective_trial" value="1"> Trial</label>
                            </div>
                            <div class="checkbox">
                              <label><input type="checkbox" name="objective_incremental_sales" value="1"> Incremental Sales</label>
                            </div>
                            <div class="checkbox">
                              <label><input type="checkbox" name="objective_market_development" value="1"> Market Development</label>
                            </div>
                            <div class="checkbox">
                              <label><input type="checkbox" name="objective_move_out_programs" value="1"> Move out Programs</label>
                            </div>
                            <div class="checkbox">
                              <label><input type="checkbox" name="objective_others" value="1"> Others (pls Specify)</label>
                            </div>
                            <input type="text" name="objective_others_specify" class="form-control" placeholder="Specify other objectives" style="margin-top: 10px;">
                          </div>
                          <div class="col-md-6 col-sm-6 col-xs-12">
                            <label>Volume Evaluation (use official data from Finance)</label>
                            
                            <!-- Finance Data Verification -->
                            <div class="form-group">
                              <label>Baseline Sales Source</label>
                              <select name="baseline_sales_source" class="form-control" required>
                                <option value="">Select Source</option>
                                <option value="Official Finance Data">Official Finance Data</option>
                                <option value="Channel Manager Verified">Channel Manager Verified</option>
                                <option value="Sales Team Estimate">Sales Team Estimate</option>
                              </select>
                              <small class="text-danger">Required: Must use official finance data for accuracy</small>
                            </div>
                            
                            <div class="form-group">
                              <label>1. Target incremental value =</label>
                              <div class="input-group">
                                <input type="number" step="0.01" name="target_incremental_value" class="form-control" placeholder="0.00">
                                <span class="input-group-addon">per month</span>
                              </div>
                            </div>
                            <div class="form-group">
                              <label>2. Current value w/o promo =</label>
                              <div class="input-group">
                                <input type="number" step="0.01" name="current_value_wo_promo" class="form-control" placeholder="0.00">
                                <span class="input-group-addon">per month</span>
                              </div>
                            </div>
                            <div class="form-group">
                              <label>TOTAL</label>
                              <input type="number" step="0.01" name="total_value" class="form-control" placeholder="0.00" readonly>
                            </div>
                            
                            <!-- Channel Manager Verification -->
                            <div class="form-group">
                              <div class="checkbox">
                                <label><input type="checkbox" name="channel_manager_verified" value="1"> Channel Manager has verified baseline sales data</label>
                              </div>
                              <small class="text-danger">Required: Channel Manager must verify baseline sales before approval</small>
                            </div>
                            
                            <small class="text-danger">Requirement: Term sheet and outlet conforme on the agreed targets.</small>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Duration & Target Outlets Section -->
                    <div class="x_panel">
                      <div class="x_title">
                        <h3><i class="fa fa-calendar"></i> Duration of Promo & Target Outlets</h3>
                        <div class="clearfix"></div>
                      </div>
                      <div class="x_content">
                        <!-- Timing Controls -->
                        <div class="alert alert-info">
                          <strong>Timing Requirements:</strong>
                          <ul style="margin-bottom: 0;">
                            <li>PAW must be submitted at least 1 month before promo start date</li>
                            <li>No retroactive PAWs allowed</li>
                            <li>Internal approval must be completed before external commitments</li>
                          </ul>
                        </div>
                        
                        <div class="form-group">
                          <div class="col-md-3 col-sm-3 col-xs-12">
                            <label>FROM</label>
                            <input type="date" name="promo_from_date" class="form-control" required id="promo_from_date">
                            <small class="text-danger" id="timing_error" style="display: none;">Must be at least 1 month from today</small>
                          </div>
                          <div class="col-md-3 col-sm-3 col-xs-12">
                            <label>TO</label>
                            <input type="date" name="promo_to_date" class="form-control" required id="promo_to_date">
                          </div>
                          <div class="col-md-3 col-sm-3 col-xs-12">
                            <label>Ave. Selling Price (ASP) =</label>
                            <input type="number" step="0.01" name="ave_selling_price" class="form-control" placeholder="0.00">
                          </div>
                          <div class="col-md-3 col-sm-3 col-xs-12">
                            <label>Target No. of Outlets</label>
                            <input type="number" name="target_no_outlets" class="form-control" placeholder="0">
                          </div>
                        </div>
                        <div class="form-group">
                          <div class="col-md-6 col-sm-6 col-xs-12">
                            <label>Target Outlet/s (Type/Name)</label>
                            <div class="checkbox">
                              <label><input type="checkbox" name="outlet_supermarkets" value="1"> Supermarkets</label>
                            </div>
                            <div class="checkbox">
                              <label><input type="checkbox" name="outlet_cstores" value="1"> C-stores</label>
                            </div>
                            <div class="checkbox">
                              <label><input type="checkbox" name="outlet_horeca" value="1"> HORECA</label>
                            </div>
                            <div class="checkbox">
                              <label><input type="checkbox" name="outlet_retail" value="1"> Retail</label>
                            </div>
                            <div class="checkbox">
                              <label><input type="checkbox" name="outlet_ecom" value="1"> Ecom</label>
                            </div>
                            <div class="checkbox">
                              <label><input type="checkbox" name="outlet_others" value="1"> Others</label>
                            </div>
                            <textarea name="outlet_details" class="form-control" rows="3" placeholder="Specify outlet details"></textarea>
                          </div>
                          <div class="col-md-6 col-sm-6 col-xs-12">
                            <label>Area Coverage</label>
                            <textarea name="area_coverage" class="form-control" rows="6" placeholder="Describe area coverage"></textarea>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Promo Cost Summary Section -->
                    <div class="x_panel">
                      <div class="x_title">
                        <h3><i class="fa fa-calculator"></i> Promo Cost Summary & Financial Implications</h3>
                        <div class="clearfix"></div>
                      </div>
                      <div class="x_content">
                        <div class="form-group">
                          <div class="col-md-6 col-sm-6 col-xs-12">
                            <label>Promo Cost Summary</label>
                            <div class="table-responsive">
                              <table class="table table-bordered" id="cost-summary-table">
                                <thead>
                                  <tr>
                                    <th>ITEM/S</th>
                                    <th>QTY</th>
                                    <th>UNIT</th>
                                    <th>TOTAL</th>
                                  </tr>
                                </thead>
                                <tbody id="cost-summary-tbody">
                                  <tr>
                                    <td><input type="text" name="cost_items[]" class="form-control" placeholder="Item description"></td>
                                    <td><input type="number" name="cost_qty[]" class="form-control cost-qty" placeholder="0"></td>
                                    <td><input type="number" step="0.01" name="cost_unit[]" class="form-control cost-unit" placeholder="0.00"></td>
                                    <td><input type="number" step="0.01" name="cost_total[]" class="form-control cost-total" placeholder="0.00" readonly></td>
                                  </tr>
                                </tbody>
                                <tfoot>
                                  <tr>
                                    <td colspan="3" class="text-right"><strong>TOTAL</strong></td>
                                    <td><input type="number" step="0.01" name="cost_summary_total" id="cost-summary-total" class="form-control" value="0.00" readonly></td>
                                  </tr>
                                </tfoot>
                              </table>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary" id="add-cost-row"><i class="fa fa-plus"></i> Add Row</button>
                          </div>
                          <div class="col-md-6 col-sm-6 col-xs-12">
                            <label>Financial Implications</label>
                            <div class="table-responsive">
                              <table class="table table-bordered">
                                <thead>
                                  <tr>
                                    <th>ITEM/S</th>
                                    <th>W/O PROMO</th>
                                    <th>W/ PROMO</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <tr>
                                    <td>A. Expense or Promo Cost</td>
                                    <td><input type="number" step="0.01" name="expense_wo_promo" class="form-control" value="0.00" readonly></td>
                                    <td><input type="number" step="0.01" name="expense_w_promo" class="form-control" value="0.00" readonly></td>
                                  </tr>
                                  <tr>
                                    <td>B. Sales to be Generated</td>
                                    <td><input type="number" step="0.01" name="sales_wo_promo" class="form-control" value="0.00" readonly></td>
                                    <td><input type="number" step="0.01" name="sales_w_promo" class="form-control" value="0.00" readonly></td>
                                  </tr>
                                  <tr>
                                    <td>C. Expense/Sales Ratio (A/B)</td>
                                    <td><input type="text" name="ratio_wo_promo" class="form-control" value="#DIV/0!" readonly></td>
                                    <td><input type="text" name="ratio_w_promo" class="form-control" value="#DIV/0!" readonly></td>
                                  </tr>
                                </tbody>
                              </table>
                            </div>
                            <div class="form-group">
                              <label>Justification as follows (Rationale of the program):</label>
                              <textarea name="justification" class="form-control" rows="4" placeholder="Provide justification for the program"></textarea>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Promo Billing/Charging Details -->
                    <div class="x_panel">
                      <div class="x_title">
                        <h3><i class="fa fa-credit-card"></i> Promo Billing/Charging Details</h3>
                        <div class="clearfix"></div>
                      </div>
                      <div class="x_content">
                        <div class="form-group">
                          <div class="col-md-4 col-sm-4 col-xs-12">
                            <label>Brand(s)/Sales Group</label>
                            <input type="text" name="billing_brand_sales_group" class="form-control" placeholder="Enter brand/sales group">
                          </div>
                          <div class="col-md-4 col-sm-4 col-xs-12">
                            <label>Charge Account Number (Specify pls)</label>
                            <input type="text" name="charge_account_number" class="form-control" placeholder="Enter account number">
                          </div>
                          <div class="col-md-4 col-sm-4 col-xs-12">
                            <label>Total Amount</label>
                            <input type="number" step="0.01" name="total_amount" class="form-control" placeholder="0.00" readonly>
                            <small class="text-muted">Php</small>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Submission and Concurrence -->
                    <div class="x_panel">
                      <div class="x_title">
                        <h3><i class="fa fa-users"></i> Submission and Concurrence</h3>
                        <div class="clearfix"></div>
                      </div>
                      <div class="x_content">
                        <div class="form-group">
                          <div class="col-md-6 col-sm-6 col-xs-12">
                            <label>Submitted By (Signed over Printed Name)</label>
                            <div class="form-group">
                              <label>Key Account Specialist</label>
                              <div class="row">
                                <div class="col-md-6">
                                  <input type="text" name="submitted_signature" class="form-control" placeholder="Signature" readonly>
                                </div>
                                <div class="col-md-6">
                                  <input type="text" name="submitted_name" class="form-control" placeholder="Printed Name" readonly>
                                </div>
                              </div>
                            </div>
                            
                            <!-- PWP Number Assignment -->
                            <div class="form-group">
                              <label>PWP Number</label>
                              <input type="text" name="pwp_number" class="form-control" placeholder="Assigned by Trade Marketing after approval" readonly>
                              <small class="text-muted">Will be assigned after full approval by Trade Marketing</small>
                            </div>
                            
                            <!-- Internal Approval Status -->
                            <div class="form-group">
                              <label>Internal Approval Status</label>
                              <div class="checkbox">
                                <label><input type="checkbox" name="internal_approval_complete" value="1"> Internal approval completed before external commitment</label>
                              </div>
                              <small class="text-danger">Required: Internal approval must be completed before any external commitments</small>
                            </div>
                          </div>
                          
                          <div class="col-md-6 col-sm-6 col-xs-12">
                            <label>Approval Workflow (Indicate Date Received & Released)</label>
                            <div class="table-responsive">
                              <table class="table table-bordered">
                                <thead>
                                  <tr>
                                    <th>Role</th>
                                    <th>Approver</th>
                                    <th>DATERCVD</th>
                                    <th>DATERLSD</th>
                                    <th>Status</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <tr>
                                    <td>Channel Manager</td>
                                    <td>
                                      <select name="channel_manager_approver" class="form-control">
                                        <option value="">Select Approver</option>
                                        <option value="Angel">Angel</option>
                                        <option value="Cheng">Cheng</option>
                                        <option value="Dante">Dante</option>
                                        <option value="Stewart">Stewart</option>
                                        <option value="Idan">Idan</option>
                                      </select>
                                    </td>
                                    <td><input type="date" name="channel_manager_received" class="form-control"></td>
                                    <td><input type="date" name="channel_manager_released" class="form-control"></td>
                                    <td><span class="badge badge-warning">Pending</span></td>
                                  </tr>
                                  <tr>
                                    <td>Trade Marketing</td>
                                    <td>
                                      <select name="trade_marketing_approver" class="form-control">
                                        <option value="">Select Approver</option>
                                        <option value="Deb">Deb</option>
                                      </select>
                                    </td>
                                    <td><input type="date" name="trade_marketing_received" class="form-control"></td>
                                    <td><input type="date" name="trade_marketing_released" class="form-control"></td>
                                    <td><span class="badge badge-warning">Pending</span></td>
                                  </tr>
                                  <tr>
                                    <td>Finance</td>
                                    <td>
                                      <select name="finance_approver" class="form-control">
                                        <option value="">Select Approver</option>
                                        <option value="Veejay">Veejay</option>
                                        <option value="Levie">Levie</option>
                                      </select>
                                    </td>
                                    <td><input type="date" name="finance_manager_received" class="form-control"></td>
                                    <td><input type="date" name="finance_manager_released" class="form-control"></td>
                                    <td><span class="badge badge-warning">Pending</span></td>
                                  </tr>
                                  <tr>
                                    <td>Sales Head</td>
                                    <td>
                                      <select name="sales_head_approver" class="form-control">
                                        <option value="">Select Approver</option>
                                        <option value="Sales Head">Sales Head</option>
                                      </select>
                                    </td>
                                    <td><input type="date" name="sales_head_received" class="form-control"></td>
                                    <td><input type="date" name="sales_head_released" class="form-control"></td>
                                    <td><span class="badge badge-warning">Pending</span></td>
                                  </tr>
                                  <tr>
                                    <td>Chief Commercial Officer</td>
                                    <td>
                                      <select name="cco_approver" class="form-control">
                                        <option value="">Select Approver</option>
                                        <option value="CCO">CCO</option>
                                      </select>
                                    </td>
                                    <td><input type="date" name="cco_received" class="form-control"></td>
                                    <td><input type="date" name="cco_released" class="form-control"></td>
                                    <td><span class="badge badge-warning">Pending</span></td>
                                  </tr>
                                </tbody>
                              </table>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Post-Promo Actuals Tracking Section -->
                    <div class="x_panel">
                      <div class="x_title">
                        <h3><i class="fa fa-chart-line"></i> Post-Promo Actuals Tracking</h3>
                        <div class="clearfix"></div>
                      </div>
                      <div class="x_content">
                        <div class="alert alert-warning">
                          <strong>Note:</strong> This section will be filled after promo completion to track actual results vs. estimates.
                        </div>
                        <div class="form-group">
                          <div class="col-md-6 col-sm-6 col-xs-12">
                            <label>Actual Incremental Sales</label>
                            <input type="number" step="0.01" name="actual_incremental_sales" class="form-control" placeholder="0.00" readonly>
                            <small class="text-muted">To be filled after promo completion</small>
                          </div>
                          <div class="col-md-6 col-sm-6 col-xs-12">
                            <label>Actual Total Sales</label>
                            <input type="number" step="0.01" name="actual_total_sales" class="form-control" placeholder="0.00" readonly>
                            <small class="text-muted">To be filled after promo completion</small>
                          </div>
                        </div>
                        <div class="form-group">
                          <div class="col-md-12 col-sm-12 col-xs-12">
                            <label>Variance Analysis & Lessons Learned</label>
                            <textarea name="variance_analysis" class="form-control" rows="3" placeholder="Analysis of actual vs. estimated results and key learnings" readonly></textarea>
                            <small class="text-muted">To be filled after promo completion for future reference</small>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Reconciliation & Documentation Section -->
                    <div class="x_panel">
                      <div class="x_title">
                        <h3><i class="fa fa-file-contract"></i> Reconciliation & Documentation</h3>
                        <div class="clearfix"></div>
                      </div>
                      <div class="x_content">
                        <div class="form-group">
                          <div class="col-md-6 col-sm-6 col-xs-12">
                            <label>Signed Documents</label>
                            <div class="checkbox">
                              <label><input type="checkbox" name="signed_trade_letter" value="1"> Signed Trade Letter</label>
                            </div>
                            <div class="checkbox">
                              <label><input type="checkbox" name="signed_display_contract" value="1"> Signed Display Contract</label>
                            </div>
                            <div class="checkbox">
                              <label><input type="checkbox" name="signed_agreement" value="1"> Signed Agreement</label>
                            </div>
                            <input type="file" name="signed_documents[]" class="form-control" multiple accept=".pdf,.doc,.docx">
                            <small class="text-muted">Upload signed documents for easy matching with PAW</small>
                          </div>
                          <div class="col-md-6 col-sm-6 col-xs-12">
                            <label>Reconciliation Status</label>
                            <select name="reconciliation_status" class="form-control">
                              <option value="Pending">Pending</option>
                              <option value="In Progress">In Progress</option>
                              <option value="Completed">Completed</option>
                              <option value="Discrepancy">Discrepancy Found</option>
                              <option value="NTE Issued">NTE Issued</option>
                            </select>
                            <div class="form-group" style="margin-top: 10px;">
                              <label>Reconciliation Notes</label>
                              <textarea name="reconciliation_notes" class="form-control" rows="3" placeholder="Notes on reconciliation process"></textarea>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Attachments Section -->
                    <div class="x_panel">
                      <div class="x_title">
                        <h3><i class="fa fa-paperclip"></i> Attachments</h3>
                        <div class="clearfix"></div>
                      </div>
                      <div class="x_content">
                        <div class="form-group">
                          <div class="col-md-12 col-sm-12 col-xs-12">
                            <label>Attachments (Pls specify)</label>
                            <textarea name="attachments_specify" class="form-control" rows="3" placeholder="Specify required attachments"></textarea>
                            <input type="file" name="attachments[]" class="form-control" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
                            <small class="text-muted">Multiple files allowed (PDF, Excel, Word, Images)</small>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Notes Section -->
                    <div class="x_panel">
                      <div class="x_content">
                        <div class="alert alert-info">
                          <strong>Notes:</strong>
                          <ul style="margin-bottom: 0;">
                            <li>PAT (PAW Approval Tracking).</li>
                            <li>Do not fill up fields shaded with blue. Contains formula, automatically filled up.</li>
                          </ul>
                        </div>
                      </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="ln_solid"></div>
                    <div class="form-group">
                      <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                        <button class="btn btn-primary" type="button">Cancel</button>
                        <button class="btn btn-primary" type="reset">Reset</button>
                        <button type="submit" class="btn btn-success">Submit</button>
                      </div>
                    </div>

                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <footer>
        <div class="pull-right">
          Gentelella - Bootstrap Admin Template by <a href="https://colorlib.com">Colorlib</a>
        </div>
        <div class="clearfix"></div>
      </footer>
    </div>
  </div>

  <!-- Scripts -->
  <script src="../srv-v2/vendors/jquery/dist/jquery.min.js"></script>
  <script src="../srv-v2/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
  <script src="../srv-v2/build/js/custom.min.js"></script>

  <script>
    $(document).ready(function() {
      // Timing validation - 1 month minimum lead time
      $('#promo_from_date').on('change', function() {
        validateTiming();
      });
      
      function validateTiming() {
        var promoStart = new Date($('#promo_from_date').val());
        var today = new Date();
        var oneMonthFromNow = new Date(today.getTime() + (30 * 24 * 60 * 60 * 1000));
        
        if (promoStart < oneMonthFromNow) {
          $('#timing_error').show();
          $('#promo_from_date').addClass('is-invalid');
          return false;
        } else {
          $('#timing_error').hide();
          $('#promo_from_date').removeClass('is-invalid');
          return true;
        }
      }
      
      // Auto-calculate cost summary totals
      $(document).on('input', '.cost-qty, .cost-unit', function() {
        var row = $(this).closest('tr');
        var qty = parseFloat(row.find('.cost-qty').val()) || 0;
        var unit = parseFloat(row.find('.cost-unit').val()) || 0;
        var total = qty * unit;
        row.find('.cost-total').val(total.toFixed(2));
        calculateCostSummaryTotal();
      });

      // Add new cost summary row
      $('#add-cost-row').click(function() {
        var newRow = `
          <tr>
            <td><input type="text" name="cost_items[]" class="form-control" placeholder="Item description"></td>
            <td><input type="number" name="cost_qty[]" class="form-control cost-qty" placeholder="0"></td>
            <td><input type="number" step="0.01" name="cost_unit[]" class="form-control cost-unit" placeholder="0.00"></td>
            <td><input type="number" step="0.01" name="cost_total[]" class="form-control cost-total" placeholder="0.00" readonly></td>
          </tr>
        `;
        $('#cost-summary-tbody').append(newRow);
      });

      // Calculate total cost summary
      function calculateCostSummaryTotal() {
        var total = 0;
        $('.cost-total').each(function() {
          total += parseFloat($(this).val()) || 0;
        });
        $('#cost-summary-total').val(total.toFixed(2));
        
        // Update financial implications
        $('input[name="expense_w_promo"]').val(total.toFixed(2));
        $('input[name="expense_wo_promo"]').val('0.00');
        
        // Calculate ratios
        var salesWPromo = parseFloat($('input[name="sales_w_promo"]').val()) || 0;
        var salesWoPromo = parseFloat($('input[name="sales_wo_promo"]').val()) || 0;
        
        if (salesWPromo > 0) {
          var ratioWPromo = (total / salesWPromo * 100).toFixed(2);
          $('input[name="ratio_w_promo"]').val(ratioWPromo + '%');
        }
        
        if (salesWoPromo > 0) {
          var ratioWoPromo = (total / salesWoPromo * 100).toFixed(2);
          $('input[name="ratio_wo_promo"]').val(ratioWoPromo + '%');
        }
      }

      // Auto-calculate total value
      $('input[name="target_incremental_value"], input[name="current_value_wo_promo"]').on('input', function() {
        var target = parseFloat($('input[name="target_incremental_value"]').val()) || 0;
        var current = parseFloat($('input[name="current_value_wo_promo"]').val()) || 0;
        var total = target + current;
        $('input[name="total_value"]').val(total.toFixed(2));
        
        // Update sales in financial implications
        $('input[name="sales_w_promo"]').val(total.toFixed(2));
        $('input[name="sales_wo_promo"]').val(current.toFixed(2));
        
        // Recalculate ratios
        calculateCostSummaryTotal();
      });

      // Auto-calculate total amount
      $('input[name="cost_summary_total"]').on('input', function() {
        var costTotal = parseFloat($(this).val()) || 0;
        $('input[name="total_amount"]').val(costTotal.toFixed(2));
      });

      // Form submission
      $('#paw-form').on('submit', function(e) {
        e.preventDefault();
        
        // Basic validation
        var isValid = true;
        var requiredFields = ['paw_date', 'company', 'promo_from_date', 'promo_to_date'];
        
        requiredFields.forEach(function(fieldName) {
          var field = $('input[name="' + fieldName + '"], select[name="' + fieldName + '"]');
          if (!field.val()) {
            field.addClass('is-invalid');
            isValid = false;
          } else {
            field.removeClass('is-invalid');
          }
        });
        
        if (isValid) {
          // Show loading state
          var submitBtn = $(this).find('button[type="submit"]');
          var originalText = submitBtn.text();
          submitBtn.text('Submitting...').prop('disabled', true);
          
          // Check if database is available
          <?php if (!$dbAvailable) { ?>
          // Demo mode - show preview instead of submitting
          setTimeout(function() {
            submitBtn.text(originalText).prop('disabled', false);
            alert('Demo Mode: Form validation passed! In a real environment, this would be submitted to the database.\n\nForm data has been validated and is ready for submission.');
            
            // Show form data preview
            var formData = new FormData($('#paw-form')[0]);
            var preview = 'Form Preview:\n';
            for (var pair of formData.entries()) {
              if (pair[1] && pair[1] !== '') {
                preview += pair[0] + ': ' + pair[1] + '\n';
              }
            }
            console.log('PAW Form Data:', preview);
          }, 1000);
          <?php } else { ?>
          // Submit form via AJAX when database is available
          $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            success: function(response) {
              submitBtn.text(originalText).prop('disabled', false);
              
              if (response.success) {
                alert('PAW submitted successfully! PAW Number: ' + response.paw_no);
                // Optionally redirect or reset form
                $('#paw-form')[0].reset();
              } else {
                alert('Error: ' + response.message);
              }
            },
            error: function(xhr, status, error) {
              submitBtn.text(originalText).prop('disabled', false);
              alert('Error submitting form. Please try again.');
              console.error('Error:', error);
            }
          });
          <?php } ?>
        } else {
          alert('Please fill in all required fields.');
        }
      });
    });
  </script>

</body>
</html>
