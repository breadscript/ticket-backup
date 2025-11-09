<?php
// Start session
session_start();

// Include database connection
include_once('../conn/db.php');

// Check if database connection is successful
if (!$mysqlconn) {
    die("Database connection failed");
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: paw_form.php');
    exit;
}

// CSRF protection
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
    $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("CSRF token validation failed");
}

// Get form data
$paw_date = $_POST['paw_date'] ?? '';
$company = $_POST['company'] ?? '';
$internal_order_no = $_POST['internal_order_no'] ?? '';
$brand_skus = $_POST['brand_skus'] ?? '';
$lead_brand_skus = $_POST['lead_brand_skus'] ?? '';
$participating_brands = $_POST['participating_brands'] ?? '';
$sales_group = $_POST['sales_group'] ?? '';
$sharing_brand_skus = $_POST['sharing_brand_skus'] ?? '';

// New fields for enhanced requirements
$baseline_sales_source = $_POST['baseline_sales_source'] ?? '';
$channel_manager_verified = isset($_POST['channel_manager_verified']) ? 1 : 0;
$internal_approval_complete = isset($_POST['internal_approval_complete']) ? 1 : 0;
$actual_incremental_sales = $_POST['actual_incremental_sales'] ?? null;
$actual_total_sales = $_POST['actual_total_sales'] ?? null;
$variance_analysis = $_POST['variance_analysis'] ?? '';
$reconciliation_status = $_POST['reconciliation_status'] ?? 'Pending';
$reconciliation_notes = $_POST['reconciliation_notes'] ?? '';
$signed_trade_letter = isset($_POST['signed_trade_letter']) ? 1 : 0;
$signed_display_contract = isset($_POST['signed_display_contract']) ? 1 : 0;
$signed_agreement = isset($_POST['signed_agreement']) ? 1 : 0;

// Activity checkboxes
$activity_listing_fee = isset($_POST['activity_listing_fee']) ? 1 : 0;
$activity_rentables = isset($_POST['activity_rentables']) ? 1 : 0;
$activity_product_sampling = isset($_POST['activity_product_sampling']) ? 1 : 0;
$activity_merchandising = isset($_POST['activity_merchising']) ? 1 : 0;
$activity_special_events = isset($_POST['activity_special_events']) ? 1 : 0;
$activity_product_donation = isset($_POST['activity_product_donation']) ? 1 : 0;
$activity_other = isset($_POST['activity_other']) ? 1 : 0;

$specific_scheme = $_POST['specific_scheme'] ?? '';
$activity_title = $_POST['activity_title'] ?? '';
$activity_mechanics = $_POST['activity_mechanics'] ?? '';

// Objectives
$objective_product_availability = isset($_POST['objective_product_availability']) ? 1 : 0;
$objective_trial = isset($_POST['objective_trial']) ? 1 : 0;
$objective_incremental_sales = isset($_POST['objective_incremental_sales']) ? 1 : 0;
$objective_market_development = isset($_POST['objective_market_development']) ? 1 : 0;
$objective_move_out_programs = isset($_POST['objective_move_out_programs']) ? 1 : 0;
$objective_others = isset($_POST['objective_others']) ? 1 : 0;
$objective_others_specify = $_POST['objective_others_specify'] ?? '';

$target_incremental_value = $_POST['target_incremental_value'] ?? 0;
$current_value_wo_promo = $_POST['current_value_wo_promo'] ?? 0;
$total_value = $_POST['total_value'] ?? 0;

// Duration and outlets
$promo_from_date = $_POST['promo_from_date'] ?? '';
$promo_to_date = $_POST['promo_to_date'] ?? '';
$ave_selling_price = $_POST['ave_selling_price'] ?? 0;
$target_no_outlets = $_POST['target_no_outlets'] ?? 0;

// Outlet types
$outlet_supermarkets = isset($_POST['outlet_supermarkets']) ? 1 : 0;
$outlet_cstores = isset($_POST['outlet_cstores']) ? 1 : 0;
$outlet_horeca = isset($_POST['outlet_horeca']) ? 1 : 0;
$outlet_retail = isset($_POST['outlet_retail']) ? 1 : 0;
$outlet_ecom = isset($_POST['outlet_ecom']) ? 1 : 0;
$outlet_others = isset($_POST['outlet_others']) ? 1 : 0;
$outlet_details = $_POST['outlet_details'] ?? '';
$area_coverage = $_POST['area_coverage'] ?? '';

// Cost summary data
$cost_items = $_POST['cost_items'] ?? [];
$cost_qty = $_POST['cost_qty'] ?? [];
$cost_unit = $_POST['cost_unit'] ?? [];
$cost_total = $_POST['cost_total'] ?? [];
$cost_summary_total = $_POST['cost_summary_total'] ?? 0;

// Financial implications
$expense_wo_promo = $_POST['expense_wo_promo'] ?? 0;
$expense_w_promo = $_POST['expense_w_promo'] ?? 0;
$sales_wo_promo = $_POST['sales_wo_promo'] ?? 0;
$sales_w_promo = $_POST['sales_w_promo'] ?? 0;
$ratio_wo_promo = $_POST['ratio_wo_promo'] ?? '';
$ratio_w_promo = $_POST['ratio_w_promo'] ?? '';
$justification = $_POST['justification'] ?? '';

// Billing details
$billing_brand_sales_group = $_POST['billing_brand_sales_group'] ?? '';
$charge_account_number = $_POST['charge_account_number'] ?? '';
$total_amount = $_POST['total_amount'] ?? 0;

// Submission details
$submitted_signature = $_POST['submitted_signature'] ?? '';
$submitted_name = $_POST['submitted_name'] ?? '';

// Concurrence dates and approvers
$channel_manager_approver = $_POST['channel_manager_approver'] ?? '';
$channel_manager_received = $_POST['channel_manager_received'] ?? '';
$channel_manager_released = $_POST['channel_manager_released'] ?? '';
$trade_marketing_approver = $_POST['trade_marketing_approver'] ?? '';
$trade_marketing_received = $_POST['trade_marketing_received'] ?? '';
$trade_marketing_released = $_POST['trade_marketing_released'] ?? '';
$finance_approver = $_POST['finance_approver'] ?? '';
$finance_manager_received = $_POST['finance_manager_received'] ?? '';
$finance_manager_released = $_POST['finance_manager_released'] ?? '';
$sales_head_approver = $_POST['sales_head_approver'] ?? '';
$sales_head_received = $_POST['sales_head_received'] ?? '';
$sales_head_released = $_POST['sales_head_released'] ?? '';
$cco_approver = $_POST['cco_approver'] ?? '';
$cco_received = $_POST['cco_received'] ?? '';
$cco_released = $_POST['cco_released'] ?? '';

// Attachments
$attachments_specify = $_POST['attachments_specify'] ?? '';

// Generate PAW number
$paw_no = 'PAW-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

try {
    // Start transaction
    mysqli_begin_transaction($mysqlconn);
    
    // Insert main PAW record with enhanced fields
    $sql = "INSERT INTO paw_main (
        paw_no, paw_date, company, internal_order_no, brand_skus, lead_brand_skus, 
        participating_brands, sales_group, sharing_brand_skus, specific_scheme, 
        activity_title, activity_mechanics, target_incremental_value, current_value_wo_promo, 
        total_value, baseline_sales_source, channel_manager_verified, internal_approval_complete,
        promo_from_date, promo_to_date, ave_selling_price, target_no_outlets,
        outlet_details, area_coverage, cost_summary_total, expense_wo_promo, expense_w_promo,
        sales_wo_promo, sales_w_promo, ratio_wo_promo, ratio_w_promo, justification,
        billing_brand_sales_group, charge_account_number, total_amount, actual_incremental_sales,
        actual_total_sales, variance_analysis, reconciliation_status, reconciliation_notes,
        signed_trade_letter, signed_display_contract, signed_agreement, submitted_signature,
        submitted_name, attachments_specify, created_at, created_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
    
    $stmt = mysqli_prepare($mysqlconn, $sql);
    $user_id = $_SESSION['userid'] ?? 1; // Default to 1 if no session
    
    mysqli_stmt_bind_param($stmt, "sssssssssssdddiisssssddddssssssssssssssssssi", 
        $paw_no, $paw_date, $company, $internal_order_no, $brand_skus, $lead_brand_skus,
        $participating_brands, $sales_group, $sharing_brand_skus, $specific_scheme,
        $activity_title, $activity_mechanics, $target_incremental_value, $current_value_wo_promo,
        $total_value, $baseline_sales_source, $channel_manager_verified, $internal_approval_complete,
        $promo_from_date, $promo_to_date, $ave_selling_price, $target_no_outlets,
        $outlet_details, $area_coverage, $cost_summary_total, $expense_wo_promo, $expense_w_promo,
        $sales_wo_promo, $sales_w_promo, $ratio_wo_promo, $ratio_w_promo, $justification,
        $billing_brand_sales_group, $charge_account_number, $total_amount, $actual_incremental_sales,
        $actual_total_sales, $variance_analysis, $reconciliation_status, $reconciliation_notes,
        $signed_trade_letter, $signed_display_contract, $signed_agreement, $submitted_signature,
        $submitted_name, $attachments_specify, $user_id
    );
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error inserting main PAW record: " . mysqli_error($mysqlconn));
    }
    
    $paw_id = mysqli_insert_id($mysqlconn);
    
    // Insert activity types
    $activity_sql = "INSERT INTO paw_activities (paw_id, listing_fee, rentables, product_sampling, 
                     merchandising, special_events, product_donation, other) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $activity_stmt = mysqli_prepare($mysqlconn, $activity_sql);
    mysqli_stmt_bind_param($activity_stmt, "iiiiiiii", $paw_id, $activity_listing_fee, 
                          $activity_rentables, $activity_product_sampling, $activity_merchandising,
                          $activity_special_events, $activity_product_donation, $activity_other);
    mysqli_stmt_execute($activity_stmt);
    
    // Insert objectives
    $objective_sql = "INSERT INTO paw_objectives (paw_id, product_availability, trial, 
                      incremental_sales, market_development, move_out_programs, others, others_specify) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $objective_stmt = mysqli_prepare($mysqlconn, $objective_sql);
    mysqli_stmt_bind_param($objective_stmt, "iiiiiiis", $paw_id, $objective_product_availability,
                          $objective_trial, $objective_incremental_sales, $objective_market_development,
                          $objective_move_out_programs, $objective_others, $objective_others_specify);
    mysqli_stmt_execute($objective_stmt);
    
    // Insert outlet types
    $outlet_sql = "INSERT INTO paw_outlets (paw_id, supermarkets, cstores, horeca, retail, ecom, others) 
                   VALUES (?, ?, ?, ?, ?, ?, ?)";
    $outlet_stmt = mysqli_prepare($mysqlconn, $outlet_sql);
    mysqli_stmt_bind_param($outlet_stmt, "iiiiiii", $paw_id, $outlet_supermarkets, $outlet_cstores,
                          $outlet_horeca, $outlet_retail, $outlet_ecom, $outlet_others);
    mysqli_stmt_execute($outlet_stmt);
    
    // Insert cost items
    if (!empty($cost_items)) {
        for ($i = 0; $i < count($cost_items); $i++) {
            if (!empty($cost_items[$i])) {
                $cost_sql = "INSERT INTO paw_cost_items (paw_id, item_description, quantity, unit_price, total_price) 
                             VALUES (?, ?, ?, ?, ?)";
                $cost_stmt = mysqli_prepare($mysqlconn, $cost_sql);
                mysqli_stmt_bind_param($cost_stmt, "isddd", $paw_id, $cost_items[$i], 
                                    $cost_qty[$i], $cost_unit[$i], $cost_total[$i]);
                mysqli_stmt_execute($cost_stmt);
            }
        }
    }
    
    // Insert concurrence dates with approvers
    $concurrence_sql = "INSERT INTO paw_concurrence (paw_id, channel_manager_approver, channel_manager_received, channel_manager_released,
                        trade_marketing_approver, trade_marketing_received, trade_marketing_released, 
                        finance_approver, finance_manager_received, finance_manager_released,
                        sales_head_approver, sales_head_received, sales_head_released,
                        cco_approver, cco_received, cco_released) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $concurrence_stmt = mysqli_prepare($mysqlconn, $concurrence_sql);
    mysqli_stmt_bind_param($concurrence_stmt, "isssssssssssssss", $paw_id, $channel_manager_approver, $channel_manager_received,
                          $channel_manager_released, $trade_marketing_approver, $trade_marketing_received, $trade_marketing_released,
                          $finance_approver, $finance_manager_received, $finance_manager_released,
                          $sales_head_approver, $sales_head_received, $sales_head_released,
                          $cco_approver, $cco_received, $cco_released);
    mysqli_stmt_execute($concurrence_stmt);
    
    // Commit transaction
    mysqli_commit($mysqlconn);
    
    // Success response
    $response = [
        'success' => true,
        'message' => 'PAW submitted successfully!',
        'paw_no' => $paw_no,
        'paw_id' => $paw_id
    ];
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($mysqlconn);
    
    $response = [
        'success' => false,
        'message' => 'Error submitting PAW: ' . $e->getMessage()
    ];
}

// Close database connection
mysqli_close($mysqlconn);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
