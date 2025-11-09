<?php
date_default_timezone_set('Asia/Manila');
include "blocks/inc.resource.php";

echo "<h2>Initialize Default Workflow Templates</h2>";

// Check if templates already exist
$checkSql = "SELECT COUNT(*) as count FROM work_flow_template";
$result = mysqli_query($mysqlconn, $checkSql);
$row = mysqli_fetch_assoc($result);
$existingCount = (int)$row['count'];

echo "<p>Existing templates: $existingCount</p>";

if ($existingCount > 0) {
    echo "<p>Templates already exist. Skipping initialization.</p>";
    echo "<p><a href='test_workflow_template.php'>Test Templates</a></p>";
    exit;
}

// Default workflow templates for RFP
$defaultTemplates = [
    // RFP - TLCI - Global
    ['work_flow_id' => 'RFP', 'department' => '', 'company' => 'TLCI', 'sequence' => 1, 'actor_id' => 'Requestor', 'action' => 'Requestor', 'is_parellel' => 0, 'global' => 1, 'amount_from' => null, 'amount_to' => null, 'Note' => ''],
    ['work_flow_id' => 'RFP', 'department' => '', 'company' => 'TLCI', 'sequence' => 2, 'actor_id' => 'Cost Center Head', 'action' => 'Cost_Center_Head', 'is_parellel' => 0, 'global' => 1, 'amount_from' => null, 'amount_to' => null, 'Note' => ''],
    ['work_flow_id' => 'RFP', 'department' => '', 'company' => 'TLCI', 'sequence' => 3, 'actor_id' => 'Accounting 1', 'action' => 'Accounting_Approver_1', 'is_parellel' => 1, 'global' => 1, 'amount_from' => null, 'amount_to' => null, 'Note' => 'One approval only'],
    ['work_flow_id' => 'RFP', 'department' => '', 'company' => 'TLCI', 'sequence' => 3, 'actor_id' => 'Accounting 2', 'action' => 'Accounting_Approver_1_Sub', 'is_parellel' => 1, 'global' => 1, 'amount_from' => null, 'amount_to' => null, 'Note' => 'One approval only'],
    ['work_flow_id' => 'RFP', 'department' => '', 'company' => 'TLCI', 'sequence' => 4, 'actor_id' => 'Accounting 3', 'action' => 'Accounting_Approver_2', 'is_parellel' => 0, 'global' => 1, 'amount_from' => null, 'amount_to' => null, 'Note' => ''],
    ['work_flow_id' => 'RFP', 'department' => '', 'company' => 'TLCI', 'sequence' => 5, 'actor_id' => 'Controller', 'action' => 'Accounting_Controller_1', 'is_parellel' => 0, 'global' => 1, 'amount_from' => null, 'amount_to' => null, 'Note' => ''],
    ['work_flow_id' => 'RFP', 'department' => '', 'company' => 'TLCI', 'sequence' => 6, 'actor_id' => 'Cashier', 'action' => 'Accounting_Cashier', 'is_parellel' => 0, 'global' => 1, 'amount_from' => null, 'amount_to' => null, 'Note' => ''],
    
    // RFP - TLCI - IT Department specific
    ['work_flow_id' => 'RFP', 'department' => 'TLCI-IT', 'company' => 'TLCI', 'sequence' => 1, 'actor_id' => 'Requestor', 'action' => 'Requestor', 'is_parellel' => 0, 'global' => 0, 'amount_from' => null, 'amount_to' => null, 'Note' => ''],
    ['work_flow_id' => 'RFP', 'department' => 'TLCI-IT', 'company' => 'TLCI', 'sequence' => 2, 'actor_id' => 'IT Manager', 'action' => 'Cost_Center_Head', 'is_parellel' => 0, 'global' => 0, 'amount_from' => null, 'amount_to' => null, 'Note' => ''],
    ['work_flow_id' => 'RFP', 'department' => 'TLCI-IT', 'company' => 'TLCI', 'sequence' => 3, 'actor_id' => 'Accounting 1', 'action' => 'Accounting_Approver_1', 'is_parellel' => 1, 'global' => 0, 'amount_from' => null, 'amount_to' => null, 'Note' => 'One approval only'],
    ['work_flow_id' => 'RFP', 'department' => 'TLCI-IT', 'company' => 'TLCI', 'sequence' => 3, 'actor_id' => 'Accounting 2', 'action' => 'Accounting_Approver_1_Sub', 'is_parellel' => 1, 'global' => 0, 'amount_from' => null, 'amount_to' => null, 'Note' => 'One approval only'],
    ['work_flow_id' => 'RFP', 'department' => 'TLCI-IT', 'company' => 'TLCI', 'sequence' => 4, 'actor_id' => 'Accounting 3', 'action' => 'Accounting_Approver_2', 'is_parellel' => 0, 'global' => 0, 'amount_from' => null, 'amount_to' => null, 'Note' => ''],
    ['work_flow_id' => 'RFP', 'department' => 'TLCI-IT', 'company' => 'TLCI', 'sequence' => 5, 'actor_id' => 'Controller', 'action' => 'Accounting_Controller_1', 'is_parellel' => 0, 'global' => 0, 'amount_from' => null, 'amount_to' => null, 'Note' => ''],
    ['work_flow_id' => 'RFP', 'department' => 'TLCI-IT', 'company' => 'TLCI', 'sequence' => 6, 'actor_id' => 'Cashier', 'action' => 'Accounting_Cashier', 'is_parellel' => 0, 'global' => 0, 'amount_from' => null, 'amount_to' => null, 'Note' => ''],
    
    // ERL - TLCI - Global
    ['work_flow_id' => 'ERL', 'department' => '', 'company' => 'TLCI', 'sequence' => 1, 'actor_id' => 'Requestor', 'action' => 'Requestor', 'is_parellel' => 0, 'global' => 1, 'amount_from' => null, 'amount_to' => null, 'Note' => ''],
    ['work_flow_id' => 'ERL', 'department' => '', 'company' => 'TLCI', 'sequence' => 2, 'actor_id' => 'Cost Center Head', 'action' => 'Cost_Center_Head', 'is_parellel' => 0, 'global' => 1, 'amount_from' => null, 'amount_to' => null, 'Note' => ''],
    ['work_flow_id' => 'ERL', 'department' => '', 'company' => 'TLCI', 'sequence' => 3, 'actor_id' => 'Accounting 1', 'action' => 'Accounting_Approver_1', 'is_parellel' => 1, 'global' => 1, 'amount_from' => null, 'amount_to' => null, 'Note' => 'One approval only'],
    ['work_flow_id' => 'ERL', 'department' => '', 'company' => 'TLCI', 'sequence' => 3, 'actor_id' => 'Accounting 2', 'action' => 'Accounting_Approver_1_Sub', 'is_parellel' => 1, 'global' => 1, 'amount_from' => null, 'amount_to' => null, 'Note' => 'One approval only'],
    ['work_flow_id' => 'ERL', 'department' => '', 'company' => 'TLCI', 'sequence' => 4, 'actor_id' => 'Accounting 3', 'action' => 'Accounting_Approver_2', 'is_parellel' => 0, 'global' => 1, 'amount_from' => null, 'amount_to' => null, 'Note' => ''],
    ['work_flow_id' => 'ERL', 'department' => '', 'company' => 'TLCI', 'sequence' => 5, 'actor_id' => 'Controller', 'action' => 'Accounting_Controller_1', 'is_parellel' => 0, 'global' => 1, 'amount_from' => null, 'amount_to' => null, 'Note' => ''],
    ['work_flow_id' => 'ERL', 'department' => '', 'company' => 'TLCI', 'sequence' => 6, 'actor_id' => 'Cashier', 'action' => 'Accounting_Cashier', 'is_parellel' => 0, 'global' => 1, 'amount_from' => null, 'amount_to' => null, 'Note' => ''],
    
    // ERGR - TLCI - Global
    ['work_flow_id' => 'ERGR', 'department' => '', 'company' => 'TLCI', 'sequence' => 1, 'actor_id' => 'Requestor', 'action' => 'Requestor', 'is_parellel' => 0, 'global' => 1, 'amount_from' => null, 'amount_to' => null, 'Note' => ''],
    ['work_flow_id' => 'ERGR', 'department' => '', 'company' => 'TLCI', 'sequence' => 2, 'actor_id' => 'Cost Center Head', 'action' => 'Cost_Center_Head', 'is_parellel' => 0, 'global' => 1, 'amount_from' => null, 'amount_to' => null, 'Note' => ''],
    ['work_flow_id' => 'ERGR', 'department' => '', 'company' => 'TLCI', 'sequence' => 3, 'actor_id' => 'Accounting 1', 'action' => 'Accounting_Approver_1', 'is_parellel' => 1, 'global' => 1, 'amount_from' => null, 'amount_to' => null, 'Note' => 'One approval only'],
    ['work_flow_id' => 'ERGR', 'department' => '', 'company' => 'TLCI', 'sequence' => 3, 'actor_id' => 'Accounting 2', 'action' => 'Accounting_Approver_1_Sub', 'is_parellel' => 1, 'global' => 1, 'amount_from' => null, 'amount_to' => null, 'Note' => 'One approval only'],
    ['work_flow_id' => 'ERGR', 'department' => '', 'company' => 'TLCI', 'sequence' => 4, 'actor_id' => 'Accounting 3', 'action' => 'Accounting_Approver_2', 'is_parellel' => 0, 'global' => 1, 'amount_from' => null, 'amount_to' => null, 'Note' => ''],
    ['work_flow_id' => 'ERGR', 'department' => '', 'company' => 'TLCI', 'sequence' => 5, 'actor_id' => 'Controller', 'action' => 'Accounting_Controller_1', 'is_parellel' => 0, 'global' => 1, 'amount_from' => null, 'amount_to' => null, 'Note' => ''],
    ['work_flow_id' => 'ERGR', 'department' => '', 'company' => 'TLCI', 'sequence' => 6, 'actor_id' => 'Cashier', 'action' => 'Accounting_Cashier', 'is_parellel' => 0, 'global' => 1, 'amount_from' => null, 'amount_to' => null, 'Note' => ''],
];

$insertSql = "INSERT INTO work_flow_template 
              (work_flow_id, department, company, sequence, actor_id, action, is_parellel, global, amount_from, amount_to, Note, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$successCount = 0;
$errorCount = 0;

foreach ($defaultTemplates as $template) {
    if ($stmt = mysqli_prepare($mysqlconn, $insertSql)) {
        mysqli_stmt_bind_param($stmt, 'ssssssiidds', 
            $template['work_flow_id'],
            $template['department'],
            $template['company'],
            $template['sequence'],
            $template['actor_id'],
            $template['action'],
            $template['is_parellel'],
            $template['global'],
            $template['amount_from'],
            $template['amount_to'],
            $template['Note']
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $successCount++;
        } else {
            $errorCount++;
            echo "<p>Error inserting template: " . mysqli_stmt_error($stmt) . "</p>";
        }
        mysqli_stmt_close($stmt);
    } else {
        $errorCount++;
        echo "<p>Error preparing statement: " . mysqli_error($mysqlconn) . "</p>";
    }
}

echo "<h3>Initialization Complete</h3>";
echo "<p>Successfully inserted: $successCount templates</p>";
echo "<p>Errors: $errorCount</p>";

if ($successCount > 0) {
    echo "<p><a href='test_workflow_template.php'>Test Templates</a></p>";
    echo "<p><a href='workflow_template_init.php'>View All Templates</a></p>";
}
?>
