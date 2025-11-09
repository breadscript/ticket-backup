<?php
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d h:i:s",time());	
include "blocks/header.php";
$moduleid=4;
include('proxy.php');
// $myusergroupid;

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
				
			<div id="myModal_updateTask" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="static" data-keyboard="false"></div>	
				<div id="insert_openThread" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="false"></div>			
				<div id="myModal_createAsset" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="static" data-keyboard="false"></div>	
				<div id="myModal_checkout" class="modal container fade" data-width="760" data-replace="true" style="display: none;" data-modal-overflow="true" data-backdrop="static" data-keyboard="false"></div>	
			

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
							<li>Inventory Management </li>
									
							<li class="active"><button class="btn btn-purple input-xs" onclick="showAddAsset(0);" data-toggle="modal"  data-target="#myModal_createAsset" <?php if($authoritystatusidz == 2){echo "disabled";}?>><i class="ace-icon fa fa-floppy-o bigger-120"></i>Add Asset</button></li>
	
		

						</ul><!-- /.breadcrumb -->

					</div>

					<div class="page-content">
						<div class="row">
							<div class="col-xs-8">
								<!-- This space is intentionally left empty to maintain layout -->
							</div>
							<div class="col-xs-4 text-right" style="padding-top: 5px;">
								<button type="button" class="btn btn-success btn-sm" onclick="exportToCSV()" style="margin-bottom: 15px;">
									<i class="ace-icon fa fa-download"></i> Export 
								</button>
							</div>
							<div class="col-xs-12">
								<ul class="nav nav-tabs" id="tabUL">
									<?php
									// Get counts for each category
									$conn = connectionDB();
									
									// Total count (All List)
									$total_query = "SELECT COUNT(*) as count FROM inv_inventorylisttb WHERE statusid = 0";
									$total_result = mysqli_query($conn, $total_query);
									$total_count = mysqli_fetch_assoc($total_result)['count'];
									
									// PC Desktop count
									$pc_query = "SELECT COUNT(*) as count FROM inv_inventorylisttb WHERE statusid = 0 AND asset_type = 'PC Desktop'";
									$pc_result = mysqli_query($conn, $pc_query);
									$pc_count = mysqli_fetch_assoc($pc_result)['count'];
									
									// Laptop count
									$laptop_query = "SELECT COUNT(*) as count FROM inv_inventorylisttb WHERE statusid = 0 AND asset_type = 'Laptop'";
									$laptop_result = mysqli_query($conn, $laptop_query);
									$laptop_count = mysqli_fetch_assoc($laptop_result)['count'];
									
									// Smartphone count
									$phone_query = "SELECT COUNT(*) as count FROM inv_inventorylisttb WHERE statusid = 0 AND asset_type = 'Smartphone'";
									$phone_result = mysqli_query($conn, $phone_query);
									$phone_count = mysqli_fetch_assoc($phone_result)['count'];
									
									// Tablet count
									$tablet_query = "SELECT COUNT(*) as count FROM inv_inventorylisttb WHERE statusid = 0 AND asset_type = 'Tablet'";
									$tablet_result = mysqli_query($conn, $tablet_query);
									$tablet_count = mysqli_fetch_assoc($tablet_result)['count'];
									
									// Printer count
									$printer_query = "SELECT COUNT(*) as count FROM inv_inventorylisttb WHERE statusid = 0 AND asset_type = 'Printers'";
									$printer_result = mysqli_query($conn, $printer_query);
									$printer_count = mysqli_fetch_assoc($printer_result)['count'];
									
									// Accessories count
									$acc_query = "SELECT COUNT(*) as count FROM inv_inventorylisttb WHERE statusid = 0 AND asset_type NOT IN ('PC Desktop', 'Laptop', 'Smartphone', 'Tablet', 'Printers')";
									$acc_result = mysqli_query($conn, $acc_query);
									$acc_count = mysqli_fetch_assoc($acc_result)['count'];
									?>
									<li class="active"><a href="#assetList" data-toggle="tab">All List <span class="badge"><?php echo $total_count; ?></span></a></li>
									<li><a href="#pcdesktop" data-toggle="tab">PC Desktop <span class="badge"><?php echo $pc_count; ?></span></a></li>
									<li><a href="#laptop" data-toggle="tab">Laptop <span class="badge"><?php echo $laptop_count; ?></span></a></li>
									<li><a href="#smartphones" data-toggle="tab">Smart Phones <span class="badge"><?php echo $phone_count; ?></span></a></li>
									<li><a href="#tablets" data-toggle="tab">Tablets <span class="badge"><?php echo $tablet_count; ?></span></a></li>
									<li><a href="#printers" data-toggle="tab">Printers <span class="badge"><?php echo $printer_count; ?></span></a></li>
									<li><a href="#accessories" data-toggle="tab">Accessories <span class="badge"><?php echo $acc_count; ?></span></a></li>
									<!-- <li><a href="#PRUHDFI" data-toggle="tab">PR-UHDFI <span class="badge"></span></a></li> -->
									
									<div class="pull-right">
															
									<div class="pull-right">
									<div class="btn-group" style="margin-left: 10px; margin-top: -5px;">
										<button type="button" class="btn btn-primary dropdown-toggle1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="background-color: #4a90e2; border: none; border-radius: 4px; padding: 6px 12px; font-size: 13px; height: 32px; line-height: 1.4;">
											<i class="ace-icon fa fa-filter"></i> Filter Company <span class="caret"></span>
										</button>
										<ul class="dropdown-menu">
												<?php 
													$query66 = "SELECT id,clientname FROM sys_clienttb WHERE clientstatusid = '1'";
													$result66 = mysqli_query($conn, $query66);
													while($row66 = mysqli_fetch_assoc($result66)){
														$clientname=mb_convert_case($row66['clientname'], MB_CASE_TITLE, "UTF-8");
														$clientid=$row66['id'];
												?>
												<li><a href="#" onclick="filterCompany('<?php echo $clientname; ?>')"><?php echo $clientname; ?></a></li>
												<?php } ?>
										</ul>
									</div>
							

									<div class="btn-group" style="margin-left: 10px; margin-top: -5px;">
										<button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="background-color: #4a90e2; border: none; border-radius: 4px; padding: 6px 12px; font-size: 13px; height: 32px; line-height: 1.4;">
											<i class="ace-icon fa fa-filter"></i> Filter Status <span class="caret"></span>
										</button>
										<ul class="dropdown-menu">
											<?php 
											// Get counts for each status
											$conn = connectionDB();

											// Available count
											$available_query = "SELECT COUNT(*) as count FROM inv_inventorylisttb WHERE statusid = 0 AND status = 'Available'";
											$available_result = mysqli_query($conn, $available_query);
											$available_count = mysqli_fetch_assoc($available_result)['count'];
											
											// In repair count
											$repair_query = "SELECT COUNT(*) as count FROM inv_inventorylisttb WHERE statusid = 0 AND status = 'In repair'";
											$repair_result = mysqli_query($conn, $repair_query);
											$repair_count = mysqli_fetch_assoc($repair_result)['count'];
											
											// In use count
											$inuse_query = "SELECT COUNT(*) as count FROM inv_inventorylisttb WHERE statusid = 0 AND status = 'In use'";
											$inuse_result = mysqli_query($conn, $inuse_query);
											$inuse_count = mysqli_fetch_assoc($inuse_result)['count'];
											
											// Lost count
											$lost_query = "SELECT COUNT(*) as count FROM inv_inventorylisttb WHERE statusid = 0 AND status = 'Lost'";
											$lost_result = mysqli_query($conn, $lost_query);
											$lost_count = mysqli_fetch_assoc($lost_result)['count'];
											
											// Reserved count
											$reserved_query = "SELECT COUNT(*) as count FROM inv_inventorylisttb WHERE statusid = 0 AND status = 'Reserved'";
											$reserved_result = mysqli_query($conn, $reserved_query);
											$reserved_count = mysqli_fetch_assoc($reserved_result)['count'];
											
											// Retired count
											$retired_query = "SELECT COUNT(*) as count FROM inv_inventorylisttb WHERE statusid = 0 AND status = 'Retired'";
											$retired_result = mysqli_query($conn, $retired_query);
											$retired_count = mysqli_fetch_assoc($retired_result)['count'];
											
											// S.U Available count
											$su_available_query = "SELECT COUNT(*) as count FROM inv_inventorylisttb WHERE statusid = 0 AND status = 'S.U Available'";
											$su_available_result = mysqli_query($conn, $su_available_query);
											$su_available_count = mysqli_fetch_assoc($su_available_result)['count'];
											
											// S.U Deployed count
											$su_deployed_query = "SELECT COUNT(*) as count FROM inv_inventorylisttb WHERE statusid = 0 AND status = 'S.U Deployed'";
											$su_deployed_result = mysqli_query($conn, $su_deployed_query);
											$su_deployed_count = mysqli_fetch_assoc($su_deployed_result)['count'];
											
											// Upcoming count
											$upcoming_query = "SELECT COUNT(*) as count FROM inv_inventorylisttb WHERE statusid = 0 AND status = 'Upcoming'";
											$upcoming_result = mysqli_query($conn, $upcoming_query);
											$upcoming_count = mysqli_fetch_assoc($upcoming_result)['count'];
											?>
											<li><a href="#" onclick="filterStatus('Available')">Available <span style="color: #4a90e2; font-weight: bold;"><?php echo $available_count; ?></span></a></li>
											<li><a href="#" onclick="filterStatus('In repair')">In-repair <span style="color: #4a90e2; font-weight: bold;"><?php echo $repair_count; ?></span></a></li>
											<li><a href="#" onclick="filterStatus('In use')">In-use <span style="color: #4a90e2; font-weight: bold;"><?php echo $inuse_count; ?></span></a></li>
											<li><a href="#" onclick="filterStatus('Lost')">Lost <span style="color: #4a90e2; font-weight: bold;"><?php echo $lost_count; ?></span></a></li>
											<li><a href="#" onclick="filterStatus('Reserved')">Reserved <span style="color: #4a90e2; font-weight: bold;"><?php echo $reserved_count; ?></span></a></li>
											<li><a href="#" onclick="filterStatus('Retired')">Retired <span style="color: #4a90e2; font-weight: bold;"><?php echo $retired_count; ?></span></a></li>
											<li><a href="#" onclick="filterStatus('S.U Available')">S.U Available <span style="color: #4a90e2; font-weight: bold;"><?php echo $su_available_count; ?></span></a></li>
											<li><a href="#" onclick="filterStatus('S.U Deployed')">S.U Deployed <span style="color: #4a90e2; font-weight: bold;"><?php echo $su_deployed_count; ?></span></a></li>
											<li><a href="#" onclick="filterStatus('Upcoming')">Upcoming <span style="color: #4a90e2; font-weight: bold;"><?php echo $upcoming_count; ?></span></a></li>
											<li class="divider"></li>
											<li><a href="#" onclick="filterStatus('all')">Show All</a></li>
										</ul>
									</div>
								</ul>


								

								<div class="tab-content">
									<div class="tab-pane fade in active" id="assetList">
										<div class="table-responsive">
										<table class="table table-striped table-bordered table-hover" id="dataTables-myticket">
												<thead>
													<tr>
													
														<th>Asset Reference Number</th>		
														<th>PO Number</th>											
														<th>Asset Tag</th>	
														<th>Serial Number</th>
														<!-- <th>Assignee</th>																								 -->
														<th>Checked Out to</th>
														<th>Location</th>                          					
														<th>Company</th>												
														<th>Asset Type</th>													                                                                                                                                                                                                            
                                                        <!-- <th>Purchase Price</th> -->                                                         
														<th>Status</th>	  
														<th>OS Version</th>
														<th>Antivirus</th>
														<th>PMS Update</th>
                                                        <th>Warranty End</th>                                                                                                            
                                                        <th>Action</th>                       
													</tr>
												</thead>
												<tbody>
												<?php 
												// Establish database connection
												$conn = connectionDB();

												// Get current user ID from session
$current_user_id = $_SESSION['userid'] ?? 0;

// Query to fetch inventory items with conditional filtering
if ($current_user_id == 271) {
    // For user ID 271, filter by specific company
    $query = "SELECT *
    FROM inv_inventorylisttb
    WHERE statusid = 0 
    AND company NOT IN ('The Laguna Creamery Inc.', 'BMC', 'Metro Pacific Agro Ventures Inc.', 'Metro Pacific Fresh Farms Inc', 'Metro Pacific Dairy Farms, Inc.', 'Metro Pacific Nova Agro Tech, Inc.')
    ORDER BY datetimecreated DESC, asset_id DESC";
} elseif ($current_user_id == 383) {
    // For user ID 383, only show Metro Pacific Fresh Farms Inc
    $query = "SELECT *
    FROM inv_inventorylisttb
    WHERE statusid = 0
    AND company = 'Metro Pacific Fresh Farms Inc'
    ORDER BY datetimecreated DESC, asset_id DESC";
} else {
    // For all other users, show all inventory items
    $query = "SELECT *
    FROM inv_inventorylisttb
    WHERE statusid = 0
    ORDER BY datetimecreated DESC, asset_id DESC";
}

												$result = mysqli_query($conn, $query);

												// Check if we have results
												if($result && mysqli_num_rows($result) > 0) {
													while($row = mysqli_fetch_assoc($result)) {
														?>
														<tr>
															<td><?php echo $row['asset_id'] ?: 'N/A'; ?></td>	
															<td><?php echo $row['order_number'] ?: 'N/A'; ?></td>													
															<td><?php echo $row['asset_tag'] ?: 'N/A'; ?></td>
															<td><?php echo $row['serial_number'] ?: 'N/A'; ?></td>
															<!-- <td>
																<?php
																	if (empty($row['assignee'])) {
																		echo '<span style="color: #fd7e14">Not yet assigned</span>';
																	} else {
																		$assigneeIds = explode(',', $row['assignee']);
																		$assigneeNames = [];

																		foreach ($assigneeIds as $assigneeId) {
																			$assigneeQuery = "SELECT user_firstname, user_lastname FROM sys_usertb WHERE id = '$assigneeId'";
																			$assigneeResult = mysqli_query($conn, $assigneeQuery);
																			if ($assigneeRow = mysqli_fetch_assoc($assigneeResult)) {
																				$assigneeNames[] = '* ' . implode(' ', [$assigneeRow['user_firstname'], $assigneeRow['user_lastname']]);
																			} else {
																				$assigneeNames[] = "* Unknown Assignee";
																			}
																		}

																		echo implode('<br>', $assigneeNames);
																	}
																?>
															</td> -->
															
															<td><?php 
																if (empty($row['current_owner'])) {
																	echo 'N/A';
																} else {
																	$user_query = "SELECT user_firstname, user_lastname FROM sys_usertb WHERE id = '" . mysqli_real_escape_string($conn, $row['current_owner']) . "'";
																	$user_result = mysqli_query($conn, $user_query);
																	if ($user_result && $user_data = mysqli_fetch_assoc($user_result)) {
																		echo htmlspecialchars($user_data['user_firstname'] . ' ' . $user_data['user_lastname']);
																	} else {
																		echo 'N/A';
																	}
																}
															?></td> 														
															<td><?php echo $row['emp_location'] ?: 'N/A'; ?></td> 
															<td><?php echo $row['company'] ?: 'N/A'; ?></td>
															<td><?php echo $row['asset_type'] ?: 'N/A'; ?></td>																																																										
															<!-- <td>₱<?php echo $row['purchase_price'] ? number_format($row['purchase_price'], 2) : '0.00'; ?></td> -->
								
															<td><?php echo $row['status'] ?: 'Unknown'; ?></td>																									
															<td><?php echo $row['osver'] ?: 'N/A'; ?></td>
															<td><?php echo $row['antivirus'] ?: 'N/A'; ?></td>
															<td><?php echo $row['pms_date'] ?: 'N/A'; ?></td>
															<td><?php 
															if (empty($row['warranty_end']) || $row['warranty_end'] == '0000-00-00' || $row['warranty_end'] == '0000-00-00 00:00:00') {
																echo '<div style="color: #000000">N/A</div>';
																echo '<div style="color: #dc3545"><i><strong>No Warranty Info</strong></i></div>';
															} else {
																$warranty_end = strtotime($row['warranty_end']);
																if ($warranty_end === false || $warranty_end < 0) {
																	echo '<div style="color: #000000">N/A</div>';
																	echo '<div style="color: #dc3545"><i><strong>Invalid Warranty Date</strong></i></div>';
																} else {
																	$current_date = time();
																	if ($warranty_end > $current_date) {
																		echo '<div style="color: #000000">' . date('F d, Y', $warranty_end) . '</div>';
																		echo '<div style="color: #28a745"><i><strong>Under Warranty</strong></i></div>';
																	} else {
																		echo '<div style="color: #000000">' . date('F d, Y', $warranty_end) . '</div>';
																		echo '<div style="color: #dc3545"><i><strong>Warranty Expired</strong></i></div>';
																	}
																}
															}
														?></td>
															<td>
															<div class="btn-group btn-group-xs" role="group">
															<button type="button" class="btn btn-success btn-xs" onclick="showCheckout('<?php echo $row['asset_tag']; ?>');" data-toggle="modal" data-target="#myModal_checkout">
																<i class="ace-icon fa fa-pencil"></i> Check out
															</button>
															<button type="button" class="btn btn-info btn-xs" onclick="showUpdateAsset('<?php echo $row['asset_tag']; ?>');" data-toggle="modal" data-target="#myModal_updateTask">
																<i class="ace-icon fa fa-pencil"></i> Edit
															</button>														
																	<a href="assetInfoPage.php?asset_id=<?php echo $row['asset_id']; ?>" class="btn btn-xs btn-danger">
																		<i class="ace-icon fa fa-eye"></i> View
																	</a> 
																	<!-- <button type="button" class="btn btn-danger" onclick="deleteAsset('<?php echo $row['asset_id']; ?>');">
																		<i class="ace-icon fa fa-trash bigger-120"></i> Delete
																	</button>       -->
																	                                                            																
																</div>
															</td>
														</tr>
														<?php
													}
												} else {
													// No data found
													echo '<tr><td colspan="14" class="text-center">No inventory items found</td></tr>';
												}
												?>
												</tbody>
											</table>
										</div>
									</div>
									<div class="tab-pane fade in" id="pcdesktop">
										<div id="insert_pcdesktop"></div>					                    	
										<div id="insertbody_pcdesktop"></div>
									</div>
									<div class="tab-pane fade in" id="laptop">
										<div id="insert_laptop"></div>					                    	
										<div id="insertbody_laptop"></div>
									</div>
									<div class="tab-pane fade in" id="smartphones">
										<div id="insert_smartphones"></div>					                    	
										<div id="insertbody_smartphones"></div>
									</div>
									<div class="tab-pane fade in" id="tablets">
										<div id="insert_tablets"></div>					                    	
										<div id="insertbody_tablets"></div>
									</div>
									<div class="tab-pane fade in" id="printers">
										<div id="insert_printers"></div>					                    	
										<div id="insertbody_printers"></div>
									</div>	
									<div class="tab-pane fade in" id="accessories">
										<div id="insert_accessories"></div>					                    	
										<div id="insertbody_accessories"></div>
									</div>	
									<div class="tab-pane fade in" id="PRUHDFI">
										<div id="insert_PRUHDFI"></div>					                    	
										<div id="insertbody_PRUHDFI"></div>
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

	
		<!-- Your inline scripts -->
		<script type="text/javascript">
			$(document).ready(function() {
				// Destroy existing DataTable if it exists
				if ($.fn.DataTable.isDataTable('#dataTables-myticket')) {
					$('#dataTables-myticket').DataTable().destroy();
				}
				
				// Initialize DataTable with search and sort functionality
				$('#dataTables-myticket').DataTable({
					responsive: true,
					"order": [],
					"pageLength": 10,
					"lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
					"dom": '<"top"lf>rt<"bottom"ip><"clear">'
				});
    
				// For empty tables, apply simple styling
				$('#dataTables-myticket').addClass('simple-table');
    
				// navigation click actions	
				$('.scroll-link').on('click', function(event){
					event.preventDefault();
					var sectionID = $(this).attr("data-id");
					scrollToID('#' + sectionID, 750);
				});	
				
				//-----------store tab values to hash-----------------
				$('#tabUL a').click(function (e) {
					e.preventDefault();
					$(this).tab('show');
					
					// Get the current filter value
					var currentFilter = $('.btn-group .dropdown-toggle').text().trim();
					if (currentFilter !== 'Filter Status') {
						// Reapply the filter after tab switch
						var status = currentFilter.replace('Filter Status: ', '');
						filterStatus(status);
					}
				});

				//-----------store tab values to hash-----------------
				$('#tabUL2 a').click(function (e) {
					e.preventDefault();
					$(this).tab('show');
					
					// Get the current filter value
					var currentFilter = $('.btn-group .dropdown-toggle1').text().trim();
					if (currentFilter !== 'Filter Company') {
						// Reapply the filter after tab switch
						var company = currentFilter.replace('Filter Company: ', '');
						filterCompany(company);
					}
				});

				// store the currently selected tab in the hash value
				$("ul.nav-tabs > li > a").on("shown.bs.tab", function (e) {
					var id = $(e.target).attr("href").substr(1);
					window.location.hash = id;
				});
			
				// on load of the page: switch to the currently selected tab
				var hash = window.location.hash;
				$('#tabUL a[href="' + hash + '"]').tab('show');
				if(hash == "#pcdesktop") {
					showPCDesktop();
				}
				if(hash == "#laptop") {
					showLaptop();
				}
				if(hash == "#smartphones") {
					showSmartPhones();
				}
				if(hash == "#tablets") {
					showTablets();
				}
				if(hash == "#printers") {
					showPrinters();
				}
				if(hash == "#accessories") {
					showAccessories();
				}
				if(hash == "#PRUHDFI") {
					showPRUHDFI();
				}
				

			});

			function filterStatus(status) {
				// Get all tables that might be present
				var tables = [
					'#dataTables-myticket',  // All List
					'#dataTables-pcdesktop', // PC Desktop
					'#dataTables-laptop',    // Laptop
					'#dataTables-smartphones', // Smart Phones
					'#dataTables-tablets',   // Tablets
					'#dataTables-printers',  // Printers
					'#dataTables-accessories' // Accessories
					
				];

				// Apply filter to each table if it exists
				tables.forEach(function(tableId) {
					if ($.fn.DataTable.isDataTable(tableId)) {
						var table = $(tableId).DataTable();
						if (status === 'all') {
							table.column(8).search('').draw();
						} else {
							table.column(8).search(status).draw();
						}
					}
				});
			}

			function updateFilter(status) {
				// Update the button text
				var buttonText = status === 'all' ? 'Filter Status' : 'Filter Status: ' + status;
				$('.btn-group .dropdown-toggle').text(buttonText + ' ');
				$('.btn-group .dropdown-toggle').append('<span class="caret"></span>');
				
				// Apply the filter
				filterStatus(status);
			}


			function filterStatus(status) {
	// Get all tables that might be present
	var tables = [
		'#dataTables-myticket',  // All List
		'#dataTables-pcdesktop', // PC Desktop
		'#dataTables-laptop',    // Laptop
		'#dataTables-smartphones', // Smart Phones
		'#dataTables-tablets',   // Tablets
		'#dataTables-printers',  // Printers
		'#dataTables-accessories' // Accessories
	];

	// Apply filter to each table if it exists
	tables.forEach(function(tableId) {
		if ($.fn.DataTable.isDataTable(tableId)) {
			var table = $(tableId).DataTable();
			if (status === 'all') {
				table.column(8).search('').draw();
			} else {
				table.column(8).search(status).draw();
			}
		}
	});
}

function updateFilter(status) {
	// Update the button text
	var buttonText = status === 'all' ? 'Filter Status' : 'Filter Status: ' + status;
	$('.btn-group .dropdown-toggle').text(buttonText + ' ');
	$('.btn-group .dropdown-toggle').append('<span class="caret"></span>');
	
	// Apply the filter
	filterStatus(status);
}

function exportToCSV() {
	// Get the current DataTable
	var table = $('#dataTables-myticket').DataTable();
	
	// Get all data (including filtered data)
	var data = table.rows({search: 'applied'}).data();
	
	// Define CSV headers - Updated to include all requested columns
	var headers = [
		'Asset Reference Number',
		'PO Number', 
		'Asset Tag',
		'Serial Number',
		'Checked Out to',
		'Location',
		'Company',
		'Asset Type',
		'Status',
		'OS Version',
		'Antivirus',
		'PMS Date',
		'Warranty End'
	];
	
	// Start building CSV content
	var csvContent = headers.join(',') + '\n';
	
	// Add data rows
	for (var i = 0; i < data.length; i++) {
		var row = data[i];
		var csvRow = [];
		
		// Asset Reference Number (column 0)
		csvRow.push('"' + (row[0] || '').replace(/"/g, '""') + '"');
		
		// PO Number (column 1)
		csvRow.push('"' + (row[1] || '').replace(/"/g, '""') + '"');
		
		// Asset Tag (column 2)
		csvRow.push('"' + (row[2] || '').replace(/"/g, '""') + '"');
		
		// Serial Number (column 3)
		csvRow.push('"' + (row[3] || '').replace(/"/g, '""') + '"');
		
		// Checked Out to (column 4)
		csvRow.push('"' + (row[4] || '').replace(/"/g, '""') + '"');
		
		// Location (column 5)
		csvRow.push('"' + (row[5] || '').replace(/"/g, '""') + '"');
		
		// Company (column 6)
		csvRow.push('"' + (row[6] || '').replace(/"/g, '""') + '"');
		
		// Asset Type (column 7)
		csvRow.push('"' + (row[7] || '').replace(/"/g, '""') + '"');
		
		// Status (column 8)
		csvRow.push('"' + (row[8] || '').replace(/"/g, '""') + '"');
		
		// OS Version (column 9)
		csvRow.push('"' + (row[9] || '').replace(/"/g, '""') + '"');
		
		// Antivirus (column 10)
		csvRow.push('"' + (row[10] || '').replace(/"/g, '""') + '"');
		
		// PMS Date (column 11)
		csvRow.push('"' + (row[11] || '').replace(/"/g, '""') + '"');
		
		// Warranty End (column 12) - Extract text content from HTML
		var warrantyText = '';
		if (row[12]) {
			// Create a temporary div to extract text content
			var tempDiv = document.createElement('div');
			tempDiv.innerHTML = row[12];
			warrantyText = tempDiv.textContent || tempDiv.innerText || '';
		}
		csvRow.push('"' + warrantyText.replace(/"/g, '""') + '"');
		
		csvContent += csvRow.join(',') + '\n';
	}
	
	// Create and download the CSV file
	var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
	var link = document.createElement('a');
	
	if (link.download !== undefined) {
		var url = URL.createObjectURL(blob);
		link.setAttribute('href', url);
		link.setAttribute('download', 'inventory_export_' + new Date().toISOString().slice(0,10) + '.csv');
		link.style.visibility = 'hidden';
		document.body.appendChild(link);
		link.click();
		document.body.removeChild(link);
	} else {
		// Fallback for older browsers
		alert('Your browser does not support automatic download. Please copy the data manually.');
		console.log(csvContent);
	}


}

function filterCompany(company) {
	// Target the same set of possible tables
	var tables = [
		'#dataTables-myticket',  // All List
		'#dataTables-pcdesktop', // PC Desktop
		'#dataTables-laptop',    // Laptop
		'#dataTables-smartphones', // Smart Phones
		'#dataTables-tablets',   // Tablets
		'#dataTables-printers',  // Printers
		'#dataTables-accessories' // Accessories
	];

	// Apply filter to each table if it exists; Company is column index 6 (0-based)
	tables.forEach(function(tableId) {
		if ($.fn.DataTable.isDataTable(tableId)) {
			var table = $(tableId).DataTable();
			if (!company || company === 'all') {
				table.column(6).search('').draw();
			} else {
				table.column(6).search(company).draw();
			}
		}
	});

	// Update the button text
	var buttonText = (!company || company === 'all') ? 'Filter Company' : 'Filter Company: ' + company;
	$('.btn-group .dropdown-toggle1').text(buttonText + ' ');
	$('.btn-group .dropdown-toggle1').append('<span class="caret"></span>');

}
	
		</script>