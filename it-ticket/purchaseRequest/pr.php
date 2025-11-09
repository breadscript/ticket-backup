<?php
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d h:i:s",time());	
include "blocks/header.php";
$moduleid=4;
include('proxy.php');


date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d h:i:s",time());	
$conn = connectionDB();


// Get next sequential PR number from database - only if needed
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
    return 'PR-IT-00000';
}

// Don't generate PR number by default - let JavaScript handle it
$next_pr_number = ''; // Default placeholder


?>
<!DOCTYPE html>
<style>
.input-xs {
    height: 22px;
    padding: 0px 1px;
    font-size: 10px;
    line-height: 1;
    border-radius: 0px;
}

/* Compact table styling */
#dataTables-myticket {
    font-size: 12px;
}

#dataTables-myticket td {
    padding: 6px 4px;
    vertical-align: middle;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 120px;
}

#dataTables-myticket th {
    padding: 8px 4px;
    font-size: 11px;
    font-weight: bold;
}

.btn-group-xs .btn {
    padding: 2px 6px;
    font-size: 10px;
    line-height: 1.2;
}

.btn-group-xs .btn i {
    font-size: 10px;
}
</style>
<html lang="en">
	<head>
		<!-- SweetAlert2 CSS -->
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
		<!-- SweetAlert2 JS -->
		<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
	</head>
	<body class="no-skin">
		
		<div class="row">
            <div class="col-lg-12">
				
				<div id="myModal_updateTask" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="false"></div>	
				<div id="insert_openThread" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="false"></div>			
				<div id="myModal_createAsset" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="false"></div>	
				<div id="myModal_checkout" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="false"></div>	
			

				<input type="hidden" class="form-control" placeholder="Subject" id="user_id" name="user_id" value="<?php echo $userid;?>"/>
				<input type="hidden" class="form-control" placeholder="Subject" id="mygroup" name="mygroup" value="<?php echo $myusergroupid;?>"/>
			</div>
		</div>
		<div class="main-container ace-save-state" id="main-container">
			<?php include "blocks/navigation.php";?>

			<div class="main-content">
				<div class="main-content-inner">
					<div class="breadcrumbs ace-save-state" id="breadcrumbs">
						<ul class="breadcrumb">
							<li>
								<i class="ace-icon fa fa-home home-icon"></i>
								<a href="../index.php">Home</a>
							</li>
							<li>PR CREATION</li>
									
							<li class="active"><button class="btn btn-purple input-xs" onclick="showAddAsset(0);" data-toggle="modal"  data-target="#myModal_createAsset" <?php if($authoritystatusidz == 2){echo "disabled";}?>><i class="ace-icon fa fa-floppy-o bigger-120"></i>Add Asset</button></li>
	
						</ul><!-- /.breadcrumb -->

					</div>

					<div class="page-content">
						<div class="row">
							<div class="col-xs-8">
								<!-- This space is intentionally left empty to maintain layout -->
							</div>
							<div class="col-xs-4 text-right" style="padding-top: 5px;">
								
							</div>
							<div class="col-xs-12">
								<ul class="nav nav-tabs" id="tabUL">
									
									<!-- <li class="active"><a href="#assetList" data-toggle="tab">All List <span class="badge"></span></a></li> -->
									<li class="active"><a href="#PRUHDFI" data-toggle="tab">PR-CREATION <span class="badge"></span></a></li>
                                    
									<div class="pull-right">
								</ul>
								<div class="tab-content">

                                <!-- Client Selection -->
                                <div class="profile-info-row">
                                    <div class="profile-info-name"> Company </div>
                                    <div class="profile-info-value">
                                        <select class="form-control input-sm" id="client_select" style="margin-left: 10px; width: 200px;">
                                            <option value="">Select Company</option>
                                            <?php
                                            // Fetch clients from sys_clienttb
                                            $client_sql = "SELECT clientname FROM sys_clienttb ORDER BY clientname ASC";
                                            $client_result = mysqli_query($conn, $client_sql);
                                            
                                            if ($client_result && mysqli_num_rows($client_result) > 0) {
                                                while ($client_row = mysqli_fetch_assoc($client_result)) {
                                                    echo '<option value="' . htmlspecialchars($client_row['clientname']) . '">' . htmlspecialchars($client_row['clientname']) . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>


									<div class="tab-pane fade in active" id="PRUHDFI">            
                                        <div class="table-responsive">
                                        <div class="profile-info-row">
                                            <div class="profile-info-name"> PR Number </div>
                                            <div class="profile-info-value">
                                                <input type="text" class="form-control input-sm" id="prnumber_input" placeholder="Select a Company" style="margin-left: 10px; width: 200px;" value="" disabled>
                                            </div>
                                        </div>
                                        <div class="profile-info-row">
                                            <div class="profile-info-name"> Date </div>
                                            <div class="profile-info-value">
                                                <input type="date" class="form-control input-sm" id="date_input" style="margin-left: 10px; width: 200px;" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>

                                        <hr class="hr-8 dotted">

    

                                        <hr class="hr-8 dotted">

                                        <!-- Dynamic Items Container with Integrated Add Button -->
                                        <div id="dynamic_items_container" class="horizontal-items-container">
                                            <!-- Initial row will be added by JavaScript -->
                                        </div>

                                        <!-- Minimalist Add Button -->
                                        <div class="add-row-section">
                                            <button type="button" class="btn-add-row" id="add_row_btn">
                                                <i class="fa fa-plus"></i>
                                                <span>Add Row</span>
                                            </button>
                                            <!-- <span class="row-counter">Items: <span id="row_count">1</span></span> -->
                                        </div>

                                        <hr class="hr-8 dotted">

                                      
                                        <div class="profile-info-row horizontal-row">
                                            <div class="profile-info-name"> Requested By: </div>
                                            <div class="profile-info-value">
                                                <input type="text" class="form-control input-sm" id="requested_by_input" placeholder="Enter Requested By" style="margin-left: 10px; width: 200px;">
                                            </div>
                                            <div class="profile-info-name"> Approved By: </div>
                                            <div class="profile-info-value">
                                                <input type="text" class="form-control input-sm" id="approved_by_input" placeholder="MC AUSTINE PHILIP M. REDONDO" style="margin-left: 10px; width: 200px;">
                                            </div>
                                        </div>

                                        <div class="profile-info-row horizontal-row">
                                            <div class="profile-info-name"> Requestor Position: </div>
                                            <div class="profile-info-value">
                                                <input type="text" class="form-control input-sm" id="requestor_position_input" placeholder="Enter Requestor Position" style="margin-left: 10px; width: 200px;">
                                            </div>
                                            <div class="profile-info-name"> Approver Position: </div>
                                            <div class="profile-info-value">
                                                <input type="text" class="form-control input-sm" id="approved_by_position_input" placeholder="IT Manager" style="margin-left: 10px; width: 200px;">
                                            </div>
                                        </div>

                                        <hr class="hr-8 dotted">

                                        <button class="btn btn-sm btn-primary" id="preview_pr_button"> Print Preview</button>
                                        <button class="btn btn-sm btn-success" id="export_pr_button">Export PDF</button>
                                        <!-- <button class="btn btn-sm btn-warning" id="export_excel_button">Export Excel</button> -->

                                        </div>

										
									</div>
									
								
								</div>
								
								
								
							</div>
						</div>
					</div><!-- /.page-content -->
				</div>
			</div><!-- /.main-content -->

			<?php include "blocks/footer.php"; ?>

			
		</div><!-- /.main-container -->
	
	</body>
</html>

<script src="../assets/customjs/inventory.js"></script>
<!-- Validator-->
<script type="text/javascript" src="../assets/customjs/bootstrapValidator.min.js"></script>  
<script type="text/javascript" src="../assets/customjs/bootstrapValidator.js"></script> 

	
<!-- JavaScript for Dynamic Rows -->
<script>
$(document).ready(function() {
    let rowCounter = 1;
    const maxRows = 20; // Maximum number of rows allowed
    
    // Remove this line - don't load PR number on page load
    // loadNextPRNumber();
    
    // Function to load next PR number (keep this for reference but don't call it automatically)
    function loadNextPRNumber() {
        $.ajax({
            url: 'prformats/get_next_pr_number.php',
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
            <div class="item-row" data-row="${rowNumber}">
            <div class="item-row-header">
                <button type="button" class="btn-remove-row" data-row="${rowNumber}" title="Remove this row">
                    <i class="fa fa-times"></i>
                </button>
            </div>
                <div class="item-col item-row-header">
                    <span class="row-number">${rowNumber}</span>
                    
                </div>
                <div class="item-col">
                    <input type="text" class="form-control item-input" data-row="${rowNumber}" placeholder="Quantity">
                </div>
                <div class="item-col">
                    <input type="text" class="form-control item-input" data-row="${rowNumber}" placeholder="Unit">
                </div>
                <div class="item-col">
                    <textarea class="form-control item-input" data-row="${rowNumber}" placeholder="Description" rows="2"></textarea>
                </div>
                <div class="item-col">
                    <textarea class="form-control item-input" data-row="${rowNumber}" placeholder="Specification" rows="2"></textarea>
                </div>
                <div class="item-col">
                    <input type="text" class="form-control item-input" data-row="${rowNumber}" placeholder="Purpose">
                </div>
                <div class="item-col">
                    <input type="text" class="form-control item-input" data-row="${rowNumber}" placeholder="Budget Allocation">
                </div>
            </div>
        `;
    }
    
    // Function to update row numbers
    function updateRowNumbers() {
        $('.item-row').each(function(index) {
            const newRowNumber = index + 1;
            $(this).attr('data-row', newRowNumber);
            $(this).find('.row-number').text(`${newRowNumber}`);
            $(this).find('.btn-remove-row').attr('data-row', newRowNumber);
            $(this).find('.item-input').attr('data-row', newRowNumber);
        });
    }
    
    // Function to update row counter display
    function updateRowCounter() {
        $('#row_count').text(rowCounter);
        
        // Show/hide remove buttons based on row count
        if (rowCounter > 1) {
            $('.btn-remove-row').show();
            $('#remove_row_btn').prop('disabled', false);
        } else {
            $('.btn-remove-row').hide();
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
    $(document).on('click', '.btn-remove-row', function() {
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
                quantity: $(this).find('.item-input[placeholder="Quantity"]').val(),
                unit: $(this).find('.item-input[placeholder="Unit"]').val(),
                description: $(this).find('.item-input[placeholder="Description"]').val(),
                specification: $(this).find('.item-input[placeholder="Specification"]').val(),
                purpose: $(this).find('.item-input[placeholder="Purpose"]').val(),
                budget: $(this).find('.item-input[placeholder="Budget Allocation"]').val()
            });
        });
        return items;
    };
    
    // Function to get form data for export/preview
    window.getFormData = function() {
        return {
            prNumber: $('#prnumber_input').val(),
            date: $('#date_input').val(),
            client: $('#client_select').val(), // <-- this is the company name
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
            Swal.fire({
                icon: 'warning',
                title: 'Missing Information',
                text: 'Please enter PR Number',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        // Open preview in new window
        const previewWindow = window.open('', '_blank', 'width=800,height=600');
        const htmlContent = generatePRHTML(formData);
        previewWindow.document.write(htmlContent);
        previewWindow.document.close();
        
        // Removed the SweetAlert notification
    });

    // Export PDF button click
    $('#export_pr_button').click(function() {
        const formData = window.getFormData();
        
        // Validation with SweetAlert
        if (!formData.prNumber) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Information',
                text: 'Please enter PR Number',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        if (!formData.date) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Information',
                text: 'Please enter Date',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        if (!formData.requestedBy) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Information',
                text: 'Please enter Requested By',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        // Show loading state with SweetAlert
        Swal.fire({
            title: 'Generating PDF...',
            text: 'Please wait while we prepare your document',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // First open print window without saving to database
        const printWindow = window.open('', '_blank', 'width=800,height=600');
        const htmlContent = generatePRHTML(formData);
        printWindow.document.write(htmlContent);
        printWindow.document.close();
        
        // Wait for content to load then print
        setTimeout(function() {
            printWindow.print();
            
            // Close the loading SweetAlert
            Swal.close();
            
            // After print dialog closes, ask user if they want to save
            setTimeout(function() {
                // Check if user wants to save to database with SweetAlert
                Swal.fire({
                    title: 'Save Purchase Request?',
                    text: `Do you want to save this Purchase Request Record: ${formData.prNumber}?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Confirm',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show saving progress
                        Swal.fire({
                            title: 'Saving...',
                            text: 'Please wait while we save your data',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        
                        // Save to database
                        $.ajax({
                            url: 'prformats/save_pr.php',
                            type: 'POST',
                            data: { pr_data: JSON.stringify(formData) },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    // After successful database save, generate and save the file
                                    $.ajax({
                                        url: 'prformats/generate_and_save_pdf.php',
                                        type: 'POST',
                                        data: { pr_data: JSON.stringify(formData) },
                                        dataType: 'json',
                                        success: function(fileResponse) {
                                            if (fileResponse.success) {
                                                Swal.fire({
                                                    icon: 'success',
                                                    title: 'Success!',
                                                    text: 'Purchase Request saved successfully with file: ' + fileResponse.file_path,
                                                    confirmButtonColor: '#28a745'
                                                });
                                            } else {
                                                Swal.fire({
                                                    icon: 'warning',
                                                    title: 'Partial Success',
                                                    text: 'Data saved but file generation failed: ' + fileResponse.message,
                                                    confirmButtonColor: '#ffc107'
                                                });
                                            }
                                        },
                                        error: function() {
                                            Swal.fire({
                                                icon: 'warning',
                                                title: 'Partial Success',
                                                text: 'Data saved but file generation failed.',
                                                confirmButtonColor: '#ffc107'
                                            });
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: 'Error saving data: ' + response.message,
                                        confirmButtonColor: '#dc3545'
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Error saving data. Please try again.',
                                    confirmButtonColor: '#dc3545'
                                });
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'info',
                            title: 'Not Saved',
                            text: 'Purchase Request was not saved to records.',
                            confirmButtonColor: '#17a2b8'
                        });
                    }
                });
            }, 1000); // Wait 1 second after print dialog closes
        }, 500);
    });

    // // Export Excel button click
    // $('#export_excel_button').click(function() {
    //     const formData = window.getFormData();
    //     if (!formData.prNumber) {
    //         alert('Please enter PR Number');
    //         return;
    //     }
        
    //     // Create form and submit to Excel export
    //     const form = $('<form>', {
    //         'method': 'POST',
    //         'action': 'export_pr_excel.php',
    //         'target': '_blank'
    //     });
        
    //     // Add form data
    //     form.append($('<input>', {
    //         'type': 'hidden',
    //         'name': 'pr_data',
    //         'value': JSON.stringify(formData)
    //     }));
        
    //     $('body').append(form);
    //     form.submit();
    //     form.remove();
    // });

    // Function to generate PR HTML
    function generatePRHTML(data) {
        // Define company info mapping
        const companyInfo = {
            "UNIVERSAL HARVESTER DAIRY FARM INC.": {
                name: "Universal Harvester Dairy Farm Inc.",
                address: "11th Floor Rufino Tower <br> Legaspi Village Makati City<br>Tel. No. (02) 874-37783",
                logo: "prformats/company_logo/uhdfi_logo.png"
            },
            "METRO PACIFIC FRESH FARMS INC": {
                name: "Metro Pacific Fresh Farms Inc.",
                address: "9th Floor Tower 1 Rockwell Business Center <br> Ortigas Avenue Ugong 1604 City of Pasig <br> Tel No. (02) 874-337783",
                logo: "prformats/company_logo/mpff_logo.png"
            },
            "METRO PACIFIC AGRO VENTURES INC.": {
                name: "Metro Pacific Agro Ventures Inc.",
                address: "9th Floor Tower 1 Rockwell Business Center <br> Ortigas Avenue Ugong 1604 City of Pasig <br> Tel. No. (02) 874-337783 ",
                logo: "prformats/company_logo/mpav_logo.png"
            },
            "THE LAGUNA CREAMERY INC.": {
                name: "The Laguna Creamery Inc.",
                address: "Sample Address for TLCI",
                logo: "prformats/company_logo/tlci.jpg"
            },
            "METRO PACIFIC DAIRY FARMS, INC.": {
                name: "Metro Pacific Dairy Farms, Inc.",
                address: "9th Floor Tower 1 Rockwell Business Center <br> Ortigas Avenue Ugong 1604 City of Pasig <br> Tel. No. (02) 874-337783 ",
                logo: "prformats/company_logo/mpdf_logo.png"
            },
            "METRO PACIFIC NOVA AGRO TECH, INC.": {
                name: "Metro Pacific Nova Agro Tech, Inc.",
                address: "Sample Address for MPNAT",
                logo: "prformats/company_logo/mpnat.jpg"
            }
        };

        // Fallback to UHDFI if not found
        const company = companyInfo[data.client] || companyInfo["UNIVERSAL HARVESTER DAIRY FARM INC."];

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
            <title>${data.prNumber}</title>
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
                        <img src="${company.logo}" style="width: 100px; height: 100px; object-fit: contain;">
                    </div>
                    <div class="company-details">
                        <h2 class="company-name">${company.name}</h2>
                        <p class="company-address">${company.address}</p>
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

    // Handle client selection change
    $('#client_select').on('change', function() {
        const selectedClient = $(this).val();

        if (!selectedClient || selectedClient === '') {
            $('#prnumber_input').val('').prop('disabled', true);
            return;
        }

        // Enable the field before AJAX call
        $('#prnumber_input').prop('disabled', false);

        // If any company is selected, get the next PR number
        $.ajax({
            url: 'prformats/get_next_pr_number.php',
            type: 'POST',
            data: { company: selectedClient },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#prnumber_input').val(response.next_pr_number);
                } else {
                    $('#prnumber_input').val('');
                }
            },
            error: function(xhr, status, error) {
                $('#prnumber_input').val('');
            }
        });
    });

    // Remove this section - don't set any initial value
    if (!$('#client_select').val()) {
        $('#prnumber_input').val('').prop('disabled', true);
    }

    // Auto-resize textarea functionality
    function autoResizeTextarea(textarea) {
        // Reset height to auto to get the correct scrollHeight
        textarea.style.height = 'auto';
        // Set the height to the scrollHeight to fit all content
        const newHeight = Math.min(textarea.scrollHeight, 120); // Max height of 120px
        textarea.style.height = newHeight + 'px';
    }

    // Apply auto-resize to existing textareas
    $('textarea.item-input').each(function() {
        autoResizeTextarea(this);
    });

    // Auto-resize on input for existing and new textareas
    $(document).on('input', 'textarea.item-input', function() {
        autoResizeTextarea(this);
    });

    // Auto-resize on keyup for better responsiveness
    $(document).on('keyup', 'textarea.item-input', function() {
        autoResizeTextarea(this);
    });
});
</script>

<style>
.horizontal-items-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
    width: 100%;
}

.item-row {
    display: flex;
    align-items: stretch;
    gap: 12px;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    background-color: #ffffff;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
}

.item-row:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    border-color: #cbd5e0;
}

.item-col {
    flex: 1;
    min-width: 120px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start; /* Align items to top */
}

.item-row-header {
    flex: 0 0 80px;
    display: flex;
    flex-direction: column;
    align-items: flex-start; /* Changed from center to flex-start */
    justify-content: center;
    gap: 8px;
    height: 38px; /* Match the height of input fields */
    padding-left: 8px; /* Add some padding from the left edge */
}

.row-number {
    font-size: 14px;
    font-weight: 600;
    color: #4a5568;
    background-color: #f7fafc;
    padding: 6px 12px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    align-self: center; /* Center the row number */
}

.btn-remove-row {
    background: none;
    border: none;
    color: #e53e3e;
    cursor: pointer;
    padding: 6px;
    border-radius: 4px;
    transition: all 0.2s ease;
    font-size: 14px;
    align-self: flex-start; /* Position the button to the left */
    margin-left: 0; /* Ensure no left margin */
}

.btn-remove-row:hover {
    background-color: #fed7d7;
    color: #c53030;
}

.btn-remove-row:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(229, 62, 62, 0.2);
}

.item-input {
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 8px 12px;
    font-size: 14px;
    transition: all 0.2s ease;
    background-color: #ffffff;
    height: 38px; /* Set consistent height for all inputs */
    box-sizing: border-box;
}

.item-input:focus {
    outline: none;
    border-color: #4299e1;
    box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
}

.item-input::placeholder {
    color: #a0aec0;
}

/* Default textarea styling - same as inputs */
textarea.item-input {
    resize: none; /* Disable resize to maintain consistent size */
    min-height: 38px; /* Match the height of input fields */
    max-height: 120px; /* Allow expansion for text wrapping */
    overflow-y: auto; /* Add scroll if content exceeds max height */
    word-wrap: break-word; /* Enable word wrapping */
    white-space: pre-wrap; /* Preserve line breaks and spaces */
    line-height: 1.4; /* Improve readability */
}

/* Allow description and specification textareas to be adjustable */
textarea.item-description,
textarea.item-specification {
    resize: vertical;
    min-height: 38px; /* Start with same height as inputs */
    max-height: 120px; /* Allow expansion up to 120px */
    overflow-y: auto;
    word-wrap: break-word; /* Enable word wrapping */
    white-space: pre-wrap; /* Preserve line breaks and spaces */
    line-height: 1.4; /* Improve readability */
}

/* Ensure all columns have equal width and alignment */
.item-col {
    flex: 1;
    min-width: 120px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start; /* Align items to top */
}

/* Ensure row header maintains consistent height */
.item-row-header {
    flex: 0 0 80px;
    display: flex;
    flex-direction: column;
    align-items: flex-start; /* Changed from center to flex-start */
    justify-content: center;
    gap: 8px;
    height: 38px; /* Match the height of input fields */
    padding-left: 8px; /* Add some padding from the left edge */
}

/* Minimalist Add Button Section */
.add-row-section {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-top: 16px;
    padding: 12px 16px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 2px dashed #cbd5e0;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.add-row-section:hover {
    border-color: #4299e1;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(66, 153, 225, 0.12);
}

.btn-add-row {
    display: flex;
    align-items: center;
    gap: 6px;
    background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 1px 3px rgba(66, 153, 225, 0.2);
    height: 32px;
}

.btn-add-row:hover {
    background: linear-gradient(135deg, #3182ce 0%, #2c5aa0 100%);
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(66, 153, 225, 0.25);
}

.btn-add-row:active {
    transform: translateY(0);
    box-shadow: 0 1px 3px rgba(66, 153, 225, 0.2);
}

.btn-add-row i {
    font-size: 11px;
    transition: transform 0.2s ease;
}

.btn-add-row:hover i {
    transform: scale(1.1);
}

.btn-add-row span {
    font-size: 13px;
    line-height: 1;
}

.row-counter {
    font-size: 13px;
    color: #64748b;
    font-weight: 500;
    background: white;
    padding: 8px 16px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.row-counter span {
    color: #4299e1;
    font-weight: 600;
}

/* Custom SweetAlert styling for larger size */
.swal2-popup {
    font-size: 1.1rem !important;
    width: 500px !important;
    max-width: 90vw !important;
}

.swal2-title {
    font-size: 1.5rem !important;
    margin-bottom: 1rem !important;
}

.swal2-content {
    font-size: 1.1rem !important;
    line-height: 1.5 !important;
}

.swal2-confirm,
.swal2-cancel {
    font-size: 1rem !important;
    padding: 10px 20px !important;
    min-width: 100px !important;
}

.swal2-actions {
    margin-top: 1.5rem !important;
}
</style>
