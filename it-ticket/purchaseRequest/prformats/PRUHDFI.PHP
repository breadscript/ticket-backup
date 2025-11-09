<?php
include('check_session.php');
$moduleid=4;
include('proxy.php');
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d h:i:s",time());	
$conn = connectionDB();
// $myusergroupid;

// Get next sequential PR number from database
function generateNextPRNumber($mysqlconn) {
    // Get the last PR number from database
    $sql = "SELECT pr_num FROM purchase_req WHERE pr_num LIKE 'PR-IT-%' ORDER BY CAST(SUBSTRING(pr_num, 7) AS UNSIGNED) DESC LIMIT 1";
    $result = mysqli_query($mysqlconn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $last_pr = $row['pr_num'];
        
        // Extract the numeric part (after PR-IT-)
        if (preg_match('/PR-IT-(\d+)/', $last_pr, $matches)) {
            $last_number = intval($matches[1]);
            $next_number = $last_number + 1;
            return 'PR-IT-' . str_pad($next_number, 6, '0', STR_PAD_LEFT);
        }
    }
    
    // If no existing PR numbers or invalid format, start with PR-IT-241024
    return 'PR-IT-241024';
}

$next_pr_number = generateNextPRNumber($conn);

?>

<div class="table-responsive">

            <div class="profile-info-row">
                <div class="profile-info-name"> PR Number </div>
                <div class="profile-info-value">
                    <input type="text" class="form-control input-sm" id="prnumber_input" placeholder="Enter PR Number" style="margin-left: 10px; width: 200px;" value="<?php echo htmlspecialchars($next_pr_number); ?>">
                </div>
            </div>
            <div class="profile-info-row">
                <div class="profile-info-name"> Date </div>
                <div class="profile-info-value">
                    <input type="date" class="form-control input-sm" id="date_input" style="margin-left: 10px; width: 200px;" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>

            <hr class="hr-8 dotted">

            <!-- Dynamic Row Controls -->
            <div class="profile-info-row">
                <div class="profile-info-name"> Add Rows </div>
                <div class="profile-info-value">
                    <div class="row-controls" style="margin-left: 10px;">
                        <button type="button" class="btn btn-sm btn-warning" id="remove_row_btn">
                            <i class="fa fa-minus"></i> 
                        </button>
                        <button type="button" class="btn btn-sm btn-info" id="add_row_btn">
                            <i class="fa fa-plus"></i> 
                        </button>
                        <span class="row-counter" style="margin-left: 10px; font-weight: bold;"> <span id="row_count">1</span></span>
            </div>
                </div>
            </div>

            <hr class="hr-8 dotted">

            <!-- Dynamic Items Container -->
            <div id="dynamic_items_container">
                <!-- Initial row will be added by JavaScript -->
            </div>

            <hr class="hr-8 dotted">

            <div class="profile-info-row">
                <div class="profile-info-name"> Requested By: </div>
                <div class="profile-info-value">
                    <input type="text" class="form-control input-sm" id="requested_by_input" placeholder="Enter Requested By" style="margin-left: 10px; width: 200px;">
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name"> Requestor Position: </div>
                <div class="profile-info-value">
                    <input type="text" class="form-control input-sm" id="requestor_position_input" placeholder="Enter Requestor Position" style="margin-left: 10px; width: 200px;">
                </div>
            </div>



            <div class="profile-info-row">
                <div class="profile-info-name"> Approved By: </div>
                <div class="profile-info-value">
                    <input type="text" class="form-control input-sm" id="approved_by_input" placeholder="Enter Approved By" style="margin-left: 10px; width: 200px;">
                </div>
            </div>

            <div class="profile-info-row">
                <div class="profile-info-name"> Approver Position: </div>
                <div class="profile-info-value">
                    <input type="text" class="form-control input-sm" id="approved_by_position_input" placeholder="Enter Approved By Position" style="margin-left: 10px; width: 200px;">
                </div>
            </div>

            <hr class="hr-8 dotted">

            <button class="btn btn-sm btn-primary" id="preview_pr_button">Preview</button>
            <button class="btn btn-sm btn-success" id="export_pr_button">Export PDF</button>
            <!-- <button class="btn btn-sm btn-warning" id="export_excel_button">Export Excel</button> -->

</div>

<!-- JavaScript for Dynamic Rows -->
<script>
$(document).ready(function() {
    let rowCounter = 1;
    const maxRows = 20; // Maximum number of rows allowed
    
    // Load next sequential PR number on page load
    loadNextPRNumber();
    
    // Function to load next PR number
    function loadNextPRNumber() {
        $.ajax({
            url: 'get_next_pr_number.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#prnumber_input').val(response.next_pr_number);
                }
            },
            error: function() {
                // If error, keep the default value
                console.log('Could not load next PR number, using default');
            }
        });
    }
    
    // Function to create a new item row
    function createItemRow(rowNumber) {
        return `
            <div class="item-row" data-row="${rowNumber}" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px; background-color: #f9f9f9;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h5 style="margin: 0; color: #333;">Row ${rowNumber}</h5>
                    <button type="button" class="btn btn-sm btn-danger remove-specific-row" data-row="${rowNumber}" style="display: none;">
                        <i class="fa fa-trash"></i> 
                    </button>
                </div>
                <div class="profile-info-row">
                    <div class="profile-info-name"> Quantity </div>
                    <div class="profile-info-value">
                        <input type="text" class="form-control input-sm item-quantity" data-row="${rowNumber}" placeholder="Enter Quantity" style="margin-left: 10px; width: 200px;">
                    </div>
                </div>
                <div class="profile-info-row">
                    <div class="profile-info-name"> Unit </div>
                    <div class="profile-info-value">
                        <input type="text" class="form-control input-sm item-unit" data-row="${rowNumber}" placeholder="Enter Unit" style="margin-left: 10px; width: 200px;">
                    </div>
                </div>
                <div class="profile-info-row">
                    <div class="profile-info-name"> Description </div>
                    <div class="profile-info-value">
                        <textarea class="form-control input-sm item-description" data-row="${rowNumber}" placeholder="Enter Description" style="margin-left: 10px; width: 200px; height: 80px; resize: vertical;"></textarea>
                    </div>
                </div>
                <div class="profile-info-row">
                    <div class="profile-info-name"> Specification </div>
                    <div class="profile-info-value">
                        <textarea class="form-control input-sm item-specification" data-row="${rowNumber}" placeholder="Enter Specification" style="margin-left: 10px; width: 200px; height: 80px; resize: vertical;"></textarea>
                    </div>
                </div>
                <div class="profile-info-row">
                    <div class="profile-info-name"> Purpose </div>
                    <div class="profile-info-value">
                        <input type="text" class="form-control input-sm item-purpose" data-row="${rowNumber}" placeholder="Enter Purpose" style="margin-left: 10px; width: 200px;">
                    </div>
                </div>
                <div class="profile-info-row">
                    <div class="profile-info-name"> Budget Allocation </div>
                    <div class="profile-info-value">
                        <input type="text" class="form-control input-sm item-budget" data-row="${rowNumber}" placeholder="Enter Budget Allocation" style="margin-left: 10px; width: 200px;">
                    </div>
                </div>
            </div>
        `;
    }
    
    // Function to update row numbers
    function updateRowNumbers() {
        $('.item-row').each(function(index) {
            const newRowNumber = index + 1;
            $(this).attr('data-row', newRowNumber);
            $(this).find('h5').text(`Row ${newRowNumber}`);
            $(this).find('.remove-specific-row').attr('data-row', newRowNumber);
            $(this).find('input').attr('data-row', newRowNumber);
        });
    }
    
    // Function to update row counter display
    function updateRowCounter() {
        $('#row_count').text(rowCounter);
        
        // Show/hide remove buttons based on row count
        if (rowCounter > 1) {
            $('.remove-specific-row').show();
            $('#remove_row_btn').prop('disabled', false);
        } else {
            $('.remove-specific-row').hide();
            $('#remove_row_btn').prop('disabled', true);
        }
        
        // Disable add button if max rows reached
        if (rowCounter >= maxRows) {
            $('#add_row_btn').prop('disabled', true);
        } else {
            $('#add_row_btn').prop('disabled', false);
        }
    }
    
    // Add row button click
    $('#add_row_btn').click(function() {
        if (rowCounter < maxRows) {
            rowCounter++;
            $('#dynamic_items_container').append(createItemRow(rowCounter));
            updateRowCounter();
        }
    });
    
    // Remove row button click (removes last row)
    $('#remove_row_btn').click(function() {
        if (rowCounter > 1) {
            $('.item-row').last().remove();
            rowCounter--;
            updateRowNumbers();
            updateRowCounter();
        }
    });
    
    // Remove specific row button click
    $(document).on('click', '.remove-specific-row', function() {
        if (rowCounter > 1) {
            $(this).closest('.item-row').remove();
            rowCounter--;
            updateRowNumbers();
            updateRowCounter();
        }
    });
    
    // Initialize with first row
    $('#dynamic_items_container').append(createItemRow(1));
    updateRowCounter();
    
    // Function to get all item data for export/preview
    window.getAllItemData = function() {
        const items = [];
        $('.item-row').each(function() {
            const rowNumber = $(this).attr('data-row');
            items.push({
                row: rowNumber,
                quantity: $(this).find('.item-quantity').val(),
                unit: $(this).find('.item-unit').val(),
                description: $(this).find('.item-description').val(),
                specification: $(this).find('.item-specification').val(),
                purpose: $(this).find('.item-purpose').val(),
                budget: $(this).find('.item-budget').val()
            });
        });
        return items;
    };
    
    // Function to get form data for export/preview
    window.getFormData = function() {
        return {
            prNumber: $('#prnumber_input').val(),
            date: $('#date_input').val(),
            requestedBy: $('#requested_by_input').val(),
            requestorPosition: $('#requestor_position_input').val(),
            approvedBy: $('#approved_by_input').val(),
            approverPosition: $('#approved_by_position_input').val(),
            items: window.getAllItemData()
        };
    };

    // Preview button click
    $('#preview_pr_button').click(function() {
        const formData = window.getFormData();
        if (!formData.prNumber) {
            alert('Please enter PR Number');
            return;
        }
        
        // Open preview in new window
        const previewWindow = window.open('', '_blank', 'width=800,height=600');
        const htmlContent = generatePRHTML(formData);
        previewWindow.document.write(htmlContent);
        previewWindow.document.close();
    });

    // Export PDF button click
    $('#export_pr_button').click(function() {
        const formData = window.getFormData();
        if (!formData.prNumber) {
            alert('Please enter PR Number');
            return;
        }
        
        if (!formData.date) {
            alert('Please enter Date');
            return;
        }
        
        if (!formData.requestedBy) {
            alert('Please enter Requested By');
            return;
        }
        
        // Show loading state
        $(this).prop('disabled', true).text('Generating PDF...');
        
        // First save to database
        $.ajax({
            url: 'prformats/save_pr.php',
            type: 'POST',
            data: { pr_data: JSON.stringify(formData) },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Save successful, now generate PDF
                    const printWindow = window.open('', '_blank', 'width=800,height=600');
                    const htmlContent = generatePRHTML(formData);
                    printWindow.document.write(htmlContent);
                    printWindow.document.close();
                    
                    // Wait for content to load then print
                    setTimeout(function() {
                        printWindow.print();
                        alert('Purchase Request saved successfully and PDF generated!');
                    }, 500);
                } else {
                    alert('Error saving data: ' + response.message);
                }
            },
            error: function() {
                alert('Error saving data. Please try again.');
            },
            complete: function() {
                $('#export_pr_button').prop('disabled', false).text('Export PDF');
            }
        });
    });

    // Export Excel button click
    $('#export_excel_button').click(function() {
        const formData = window.getFormData();
        if (!formData.prNumber) {
            alert('Please enter PR Number');
            return;
        }
        
        // Create form and submit to Excel export
        const form = $('<form>', {
            'method': 'POST',
            'action': 'export_pr_excel.php',
            'target': '_blank'
        });
        
        // Add form data
        form.append($('<input>', {
            'type': 'hidden',
            'name': 'pr_data',
            'value': JSON.stringify(formData)
        }));
        
        $('body').append(form);
        form.submit();
        form.remove();
    });

    // Function to generate PR HTML
    function generatePRHTML(data) {
        const itemsHTML = data.items.map(item => `
            <tr>
                <td style="border: 1px solid #000; padding: 8px; text-align: center; vertical-align: top;">${item.quantity || ''}</td>
                <td style="border: 1px solid #000; padding: 8px; text-align: center; vertical-align: top;">${item.unit || ''}</td>
                <td style="border: 1px solid #000; padding: 8px; vertical-align: top;">${item.description || ''}</td>
                <td style="border: 1px solid #000; padding: 8px; vertical-align: top;">${item.specification || ''}</td>
                <td style="border: 1px solid #000; padding: 8px; vertical-align: top;">${item.purpose || ''}</td>
                <td style="border: 1px solid #000; padding: 8px; text-align: right; vertical-align: top;">${item.budget || ''}</td>
            </tr>
        `).join('');

        return `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Purchase Request - ${data.prNumber}</title>
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
                    margin-right: var(--company-details-left, 15%);
                    text-align: left;
                }
                .company-name,
                .company-address {
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
                    margin: 10px 0 5px 0; /* top, right, bottom, left */
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
                @media print {
                    body { margin: 15px; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="company-section">
                     <div class="logo-section">
                        <img src="<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']); ?>/company_logo/uhdfi.jpg" style="width: 100px; height: 100px; object-fit: contain;">
                    </div>     
                    <div class="company-details">
                        <h2 class="company-name">Universal Harvester Dairy Farms, Inc.123</h2>
                        <p class="company-address">11th Floor Rufino Tower<br>Legaspi Village Makati City<br>Tel. No. (02) 874-37783</p>
                    </div>
                </div>
                <div class="title">PURCHASE REQUEST</div>
            </div>

            <div class="pr-info">
                <table>
                    <tr> 
                        <td class="pr-number"><strong>PR No.:</strong> ${data.prNumber || ''}</td>
                        <td class="pr-date"><strong>Date:</strong> ${data.date || ''}</td>
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
                    ${itemsHTML}
                </tbody>
            </table>

            <div class="signatures">
                <div class="signature-box">
                    <div class="signature-label">Requested By:</div>
                    <div class="signature-name">${data.requestedBy || 'no data'}</div>
                    <div class="signature-line"></div>
                    <div class="signature-title">${data.requestorPosition || 'no data'}</div>
                </div>
                <div class="signature-box">
                    <div class="signature-label">Approved By:</div>
                    <div class="signature-name">${data.approvedBy || 'MC AUSTINE PHILIP M. REDONDO'}</div>
                    <div class="signature-line"></div>
                    <div class="signature-title">${data.approverPosition || 'IT Manager'}</div>
                </div>
</div>

            <div class="no-print" style="margin-top: 30px; text-align: center;">
                <button onclick="window.print()">Print/Save as PDF</button>
                <button onclick="window.close()">Close</button>
            </div>
        </body>
        </html>
        `;
    }
});
</script>

<style>
.item-row {
    transition: all 0.3s ease;
}

.item-row:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.row-controls {
    display: flex;
    align-items: center;
    gap: 10px;
}

.row-counter {
    background-color: #e8f4fd;
    padding: 5px 10px;
    border-radius: 3px;
    border: 1px solid #b3d9ff;
}
</style>


