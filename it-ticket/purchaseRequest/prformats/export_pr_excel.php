<?php
include('check_session.php');
$moduleid = 4;
include('proxy.php');

// Set headers for Excel download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=PURCHASE_REQUEST.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Get the POST data
$pr_data = isset($_POST['pr_data']) ? json_decode($_POST['pr_data'], true) : null;

if (!$pr_data) {
    echo "No data received";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Purchase Request - <?php echo htmlspecialchars($pr_data['prNumber']); ?></title>
    <style>
        body {
            font-family: "Times New Roman", serif;
            font-size: 12pt;
            color: #000;
            margin: 20px;
            line-height: 1.2;
        }
        .header {
            margin-bottom: 30px;
        }
        .company-section {
            display: flex;
            align-items: flex-start;
            margin-bottom: 25px;
        }
        .logo-section {
            width: 120px;
            height: 120px;
            border: 2px solid #000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 30px;
            flex-shrink: 0;
        }
        .logo-content {
            text-align: center;
            font-size: 8pt;
            line-height: 1.1;
        }
        .logo-outer {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .logo-inner {
            font-size: 14pt;
            font-weight: bold;
            margin: 5px 0;
        }
        .company-details {
            flex-grow: 1;
            padding-top: 10px;
        }
        .company-name {
            font-size: 16pt;
            font-weight: bold;
            margin: 0 0 5px 0;
            text-align: center;
        }
        .company-address {
            font-size: 12pt;
            margin: 0;
            text-align: center;
            line-height: 1.3;
        }
        .title {
            font-size: 18pt;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
            text-transform: uppercase;
        }
        .pr-info {
            margin-bottom: 20px;
        }
        .pr-info table {
            width: 100%;
        }
        .pr-info td {
            padding: 5px;
            font-size: 12pt;
        }
        .pr-number {
            font-weight: bold;
        }
        .pr-date {
            text-align: right;
            font-weight: bold;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 11pt;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: top;
        }
        .items-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .quantity-col { width: 8%; text-align: center; }
        .unit-col { width: 12%; text-align: center; }
        .description-col { width: 25%; }
        .specs-col { width: 20%; }
        .purpose-col { width: 25%; }
        .budget-col { width: 10%; text-align: right; }
        .signatures {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 45%;
        }
        .signature-label {
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 40px;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 50px;
            width: 100%;
        }
        .signature-name {
            font-weight: bold;
            font-size: 12pt;
            margin-top: 10px;
        }
        .signature-title {
            font-style: italic;
            font-size: 11pt;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-section">
            <div class="logo-section">
                <div class="logo-content">
                    <div class="logo-outer">UNIVERSAL HARVESTER<br>DAIRY FARMS, INC.</div>
                    <div class="logo-inner">UHDFI</div>
                    <div style="font-size: 6pt;">🐄🐄🐄</div>
                </div>
            </div>
            <div class="company-details">
                <h2 class="company-name">Universal Harvester Dairy Farms, Inc.</h2>
                <p class="company-address">11th Floor Rufino Tower<br>Legaspi Village Makati City<br>Tel. No. (02) 874-37783</p>
            </div>
        </div>
        <div class="title">PURCHASE REQUEST</div>
    </div>

    <div class="pr-info">
        <table>
            <tr>
                <td class="pr-number"><strong>PR No.:</strong> <?php echo htmlspecialchars($pr_data['prNumber']); ?></td>
                <td class="pr-date"><strong>Date:</strong> <?php echo htmlspecialchars($pr_data['date']); ?></td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th class="quantity-col">Quantity</th>
                <th class="unit-col">Unit</th>
                <th class="description-col">Description</th>
                <th class="specs-col">Specs</th>
                <th class="purpose-col">Purpose</th>
                <th class="budget-col">Budget Allocation</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pr_data['items'] as $item): ?>
            <tr>
                <td class="quantity-col"><?php echo htmlspecialchars($item['quantity']); ?></td>
                <td class="unit-col"><?php echo htmlspecialchars($item['unit']); ?></td>
                <td class="description-col"><?php echo htmlspecialchars($item['description']); ?></td>
                <td class="specs-col"><?php echo htmlspecialchars($item['specification']); ?></td>
                <td class="purpose-col"><?php echo htmlspecialchars($item['purpose']); ?></td>
                <td class="budget-col"><?php echo htmlspecialchars($item['budget']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="signatures">
        <div class="signature-box">
            <div class="signature-label">Requested By:</div>
            <div class="signature-line"></div>
            <div class="signature-name"><?php echo htmlspecialchars($pr_data['requestedBy'] ?: ''); ?></div>
            <div class="signature-title"><?php echo htmlspecialchars($pr_data['requestorPosition'] ?: ''); ?></div>
        </div>
        <div class="signature-box">
            <div class="signature-label">Approved By:</div>
            <div class="signature-line"></div>
            <div class="signature-name"><?php echo htmlspecialchars($pr_data['approvedBy'] ?: 'MC AUSTINE PHILIP M. REDONDO'); ?></div>
            <div class="signature-title"><?php echo htmlspecialchars($pr_data['approverPosition'] ?: 'IT Manager'); ?></div>
        </div>
    </div>
</body>
</html>
