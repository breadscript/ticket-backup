<?php
require_once 'db.php';
session_start();

// Set header for JSON response
header('Content-Type: application/json');

// Function to generate PDF content (same as in the main file)
function generatePRHTML($data) {
    // Define company info mapping
    $companyInfo = [
        "UNIVERSAL HARVESTER DAIRY FARM INC." => [
            "name" => "Universal Harvester Dairy Farm Inc.",
            "address" => "11th Floor Rufino Tower<br>Legaspi Village Makati City<br>Tel. No. (02) 874-37783",
            "logo" => "prformats/company_logo/uhdfi_logo.png"
        ],
        "METRO PACIFIC FRESH FARMS INC" => [
            "name" => "Metro Pacific Fresh Farms Inc.",
            "address" => "9th Floor Tower 1 Rockwell Business Center Ortigas Avenue Ugong 1604 City of Pasig <br> Tel No. (02) 874-337783",
            "logo" => "prformats/company_logo/mpff_logo.png"
        ],
        "METRO PACIFIC AGRO VENTURES INC." => [
            "name" => "Metro Pacific Agro Ventures Inc.",
            "address" => "9th Floor Tower 1 Rockwell Business Center Ortigas Avenue Ugong 1604 City of Pasig <br> Tel. No. (02) 874-337783 ",
            "logo" => "prformats/company_logo/mpav_logo.png"
        ],
        "THE LAGUNA CREAMERY INC." => [
            "name" => "The Laguna Creamery Inc.",
            "address" => "Sample Address for TLCI",
            "logo" => "prformats/company_logo/tlci.jpg"
        ],
        "METRO PACIFIC DAIRY FARMS, INC." => [
            "name" => "Metro Pacific Dairy Farms, Inc.",
            "address" => "Sample Address for MPDF",
            "logo" => "prformats/company_logo/mpdf.jpg"
        ],
        "METRO PACIFIC NOVA AGRO TECH, INC." => [
            "name" => "Metro Pacific Nova Agro Tech, Inc.",
            "address" => "Sample Address for MPNAT",
            "logo" => "prformats/company_logo/mpnat.jpg"
        ]
    ];

    // Fallback to UHDFI if not found
    $company = $companyInfo[$data['client']] ?? $companyInfo["UNIVERSAL HARVESTER DAIRY FARM INC."];

    $itemsHTML = '';
    foreach ($data['items'] as $item) {
        $itemsHTML .= '<tr>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; vertical-align: top;">' . htmlspecialchars($item['quantity'] ?? '') . '</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: center; vertical-align: top;">' . htmlspecialchars($item['unit'] ?? '') . '</td>
            <td style="border: 1px solid #000; padding: 8px; vertical-align: top;">' . htmlspecialchars($item['description'] ?? '') . '</td>
            <td style="border: 1px solid #000; padding: 8px; vertical-align: top;">' . htmlspecialchars($item['specification'] ?? '') . '</td>
            <td style="border: 1px solid #000; padding: 8px; vertical-align: top;">' . htmlspecialchars($item['purpose'] ?? '') . '</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: right; vertical-align: top;">' . htmlspecialchars($item['budget'] ?? '') . '</td>
        </tr>';
    }

    return '<!DOCTYPE html>
    <html>
    <head>
        <title>Purchase Request - ' . htmlspecialchars($data['prNumber']) . '</title>
        <style>
            body { 
                font-family: "Times New Roman", serif; 
                margin: 20px; 
                font-size: 12pt;
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
                margin-right: 30px;
            }
            .company-details { 
                flex-grow: 1;
                padding-top: 10px;
                margin-right: 15%;
                text-align: left;
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
            .items-table th { 
                background-color: #f0f0f0; 
                font-weight: bold; 
                border: 1px solid #000;
                padding: 8px;
                text-align: center;
                vertical-align: middle;
            }
            .items-table td {
                border: 1px solid #000;
                padding: 8px;
                vertical-align: top;
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
                text-align: center;
            }
            .signature-label {
                font-weight: bold;
                font-size: 12pt;
                margin-bottom: 40px;
            }
            .signature-line {
                border-top: 1px solid #000;
                margin: 10px 0 5px 0;
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
                    <img src="' . $company['logo'] . '" style="width: 100px; height: 100px; object-fit: contain;">
                </div>
                <div class="company-details">
                    <h2 class="company-name">' . $company['name'] . '</h2>
                    <p class="company-address">' . $company['address'] . '</p>
                </div>
            </div>
            <div class="title">PURCHASE REQUEST</div>
        </div>

        <div class="pr-info">
            <table>
                <tr> 
                    <td class="pr-number"><strong>PR No.:</strong> ' . htmlspecialchars($data['prNumber'] ?? '') . '</td>
                    <td class="pr-date"><strong>Date:</strong> ' . htmlspecialchars($data['date'] ?? '') . '</td>
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
                ' . $itemsHTML . '
            </tbody>
        </table>

        <div class="signatures">
            <div class="signature-box">
                <div class="signature-label">Requested By:</div>
                <div class="signature-name">' . htmlspecialchars($data['requestedBy'] ?? 'no data') . '</div>
                <div class="signature-line"></div>
                <div class="signature-title">' . htmlspecialchars($data['requestorPosition'] ?? 'no data') . '</div>
            </div>
            <div class="signature-box">
                <div class="signature-label">Approved By:</div>
                <div class="signature-name">' . htmlspecialchars($data['approvedBy'] ?? 'MC AUSTINE PHILIP M. REDONDO') . '</div>
                <div class="signature-line"></div>
                <div class="signature-title">' . htmlspecialchars($data['approverPosition'] ?? 'IT Manager') . '</div>
            </div>
        </div>
    </body>
    </html>';
}

try {
    // Check if data was sent
    if (!isset($_POST['pr_data'])) {
        throw new Exception('No data received');
    }

    // Decode the JSON data
    $pr_data = json_decode($_POST['pr_data'], true);
    
    if (!$pr_data) {
        throw new Exception('Invalid data format');
    }

    // Validate required fields
    if (empty($pr_data['prNumber'])) {
        throw new Exception('PR Number is required');
    }

    if (empty($pr_data['date'])) {
        throw new Exception('Date is required');
    }

    if (empty($pr_data['requestedBy'])) {
        throw new Exception('Requested By is required');
    }

    // Determine company code and folder based on selected client
    $company_mapping = [
        'METRO PACIFIC FRESH FARMS INC' => 'mpff',
        'METRO PACIFIC AGRO VENTURES INC.' => 'mpav',
        'THE LAGUNA CREAMERY INC.' => 'tlci',
        'UNIVERSAL HARVESTER DAIRY FARM INC.' => 'uhdfi',
        'METRO PACIFIC DAIRY FARMS, INC.' => 'mpdf',
        'METRO PACIFIC NOVA AGRO TECH, INC.' => 'mpnat'
    ];
    
    $selected_client = $pr_data['client'] ?? '';
    $company_folder = $company_mapping[$selected_client] ?? 'uhdfi'; // Default to uhdfi if not found

    // Create base directory path
    $base_dir = 'C:/xampp2/htdocs/new-it-ticket-inventory/purchaseRequest/pr_files';
    $company_dir = $base_dir . '/' . strtolower($company_folder);
    
    // Create directory if it doesn't exist
    if (!is_dir($company_dir)) {
        if (!mkdir($company_dir, 0755, true)) {
            throw new Exception('Failed to create directory: ' . $company_dir);
        }
    }

    // Generate filename WITHOUT timestamp
    $filename = $pr_data['prNumber'] . '.pdf';
    $filepath = $company_dir . '/' . $filename;
    
    // Generate HTML content
    $html_content = generatePRHTML($pr_data);
    
    // For now, we'll save as HTML file since PDF generation requires additional libraries
    // You can later integrate with libraries like mPDF, TCPDF, or wkhtmltopdf
    $html_filepath = $company_dir . '/' . $pr_data['prNumber'] . '.html';
    
    if (file_put_contents($html_filepath, $html_content) === false) {
        throw new Exception('Failed to save file');
    }

    // Return success response with file path
    echo json_encode([
        'success' => true,
        'message' => 'File saved successfully',
        'file_path' => 'pr_files/' . strtolower($company_folder) . '/' . $pr_data['prNumber'] . '.html',
        'full_path' => $html_filepath
    ]);

} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 