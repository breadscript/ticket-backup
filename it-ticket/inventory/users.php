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
							<li>Users List </li>
									
							<!-- <li class="active"><button class="btn btn-purple input-xs" onclick="showAddAsset(0);" data-toggle="modal"  data-target="#myModal_createAsset" <?php if($authoritystatusidz == 2){echo "disabled";}?>><i class="ace-icon fa fa-floppy-o bigger-120"></i>Add Asset</button></li>
	 -->
		

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
									<?php
									// Get total count of users
									$conn = connectionDB();
									$total_query = "SELECT COUNT(DISTINCT current_owner) as count 
												   FROM inv_inventorylisttb 
												   WHERE statusid = 0";
									$total_result = mysqli_query($conn, $total_query);
									$total_count = mysqli_fetch_assoc($total_result)['count'];
									?>
									<li class="active"><a href="#assetList" data-toggle="tab">All List <span class="badge"><?php echo $total_count; ?></span></a></li>
									<!-- <li><a href="#pcdesktop" data-toggle="tab">PC Desktop</a></li>
									<li><a href="#laptop" data-toggle="tab">Laptop</a></li>
									<li><a href="#smartphones" data-toggle="tab">Smart Phones</a></li>
									<li><a href="#tablets" data-toggle="tab">Tablets</a></li>
									<li><a href="#printers" data-toggle="tab">Printers</a></li>
									<li><a href="#accessories" data-toggle="tab">Accessories</a></li> -->
									<div class="pull-right">
                                    <div class="btn-group" style="margin-left: 10px; margin-top: -5px;">
										<button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="background-color: #4a90e2; border: none; border-radius: 4px; padding: 6px 12px; font-size: 13px; height: 32px; line-height: 1.4;">
											<i class="ace-icon fa fa-filter"></i> Filter Department <span class="caret"></span>
										</button>
											<ul class="dropdown-menu">
												<?php 
												// Get counts for each department
												$conn = connectionDB();

												// Commercial count
												$commercial_query = "SELECT COUNT(DISTINCT current_owner) as count FROM inv_inventorylisttb WHERE statusid = 0 AND department = 'Commercial'";
												$commercial_result = mysqli_query($conn, $commercial_query);
												$commercial_count = mysqli_fetch_assoc($commercial_result)['count'];
												
												// Eng count
												$eng_query = "SELECT COUNT(DISTINCT current_owner) as count FROM inv_inventorylisttb WHERE statusid = 0 AND department = 'Eng'";
												$eng_result = mysqli_query($conn, $eng_query);
												$eng_count = mysqli_fetch_assoc($eng_result)['count'];
												
												// Farm count
												$farm_query = "SELECT COUNT(DISTINCT current_owner) as count FROM inv_inventorylisttb WHERE statusid = 0 AND department = 'Farm'";
												$farm_result = mysqli_query($conn, $farm_query);
												$farm_count = mysqli_fetch_assoc($farm_result)['count'];
												
												// Finance and Accounting count
												$finance_query = "SELECT COUNT(DISTINCT current_owner) as count FROM inv_inventorylisttb WHERE statusid = 0 AND department = 'Finance and Accounting'";
												$finance_result = mysqli_query($conn, $finance_query);
												$finance_count = mysqli_fetch_assoc($finance_result)['count'];
												
												// HR Admin count
												$hr_query = "SELECT COUNT(DISTINCT current_owner) as count FROM inv_inventorylisttb WHERE statusid = 0 AND department = 'HR Admin'";
												$hr_result = mysqli_query($conn, $hr_query);
												$hr_count = mysqli_fetch_assoc($hr_result)['count'];
												
												// IT count
												$it_query = "SELECT COUNT(DISTINCT current_owner) as count FROM inv_inventorylisttb WHERE statusid = 0 AND department = 'I.T'";
												$it_result = mysqli_query($conn, $it_query);
												$it_count = mysqli_fetch_assoc($it_result)['count'];
												
												// Ice cream count
												$ice_query = "SELECT COUNT(DISTINCT current_owner) as count FROM inv_inventorylisttb WHERE statusid = 0 AND department = 'Ice cream'";
												$ice_result = mysqli_query($conn, $ice_query);
												$ice_count = mysqli_fetch_assoc($ice_result)['count'];
												
												// Planning and Logistic count
												$planning_query = "SELECT COUNT(DISTINCT current_owner) as count FROM inv_inventorylisttb WHERE statusid = 0 AND department = 'Planning and Logistic'";
												$planning_result = mysqli_query($conn, $planning_query);
												$planning_count = mysqli_fetch_assoc($planning_result)['count'];
												
												// Procurement count
												$procurement_query = "SELECT COUNT(DISTINCT current_owner) as count FROM inv_inventorylisttb WHERE statusid = 0 AND department = 'Procurement'";
												$procurement_result = mysqli_query($conn, $procurement_query);
												$procurement_count = mysqli_fetch_assoc($procurement_result)['count'];
												
												// QA/R&D count
												$qa_query = "SELECT COUNT(DISTINCT current_owner) as count FROM inv_inventorylisttb WHERE statusid = 0 AND department = 'QA/R&D'";
												$qa_result = mysqli_query($conn, $qa_query);
												$qa_count = mysqli_fetch_assoc($qa_result)['count'];
												?>
												<li><a href="#" onclick="filterDepartment('Commercial')">Commercial <span style="color: #4a90e2; font-weight: bold;"><?php echo $commercial_count; ?></span></a></li>
												<li><a href="#" onclick="filterDepartment('Eng')">Eng <span style="color: #4a90e2; font-weight: bold;"><?php echo $eng_count; ?></span></a></li>
												<li><a href="#" onclick="filterDepartment('Farm')">Farm <span style="color: #4a90e2; font-weight: bold;"><?php echo $farm_count; ?></span></a></li>
												<li><a href="#" onclick="filterDepartment('Finance and Accounting')">Finance and Accounting <span style="color: #4a90e2; font-weight: bold;"><?php echo $finance_count; ?></span></a></li>
												<li><a href="#" onclick="filterDepartment('HR Admin')">HR Admin <span style="color: #4a90e2; font-weight: bold;"><?php echo $hr_count; ?></span></a></li>
												<li><a href="#" onclick="filterDepartment('I.T')">I.T <span style="color: #4a90e2; font-weight: bold;"><?php echo $it_count; ?></span></a></li>
												<li><a href="#" onclick="filterDepartment('Ice cream')">Ice cream <span style="color: #4a90e2; font-weight: bold;"><?php echo $ice_count; ?></span></a></li>
												<li><a href="#" onclick="filterDepartment('Planning and Logistic')">Planning and Logistic <span style="color: #4a90e2; font-weight: bold;"><?php echo $planning_count; ?></span></a></li>
												<li><a href="#" onclick="filterDepartment('Procurement')">Procurement <span style="color: #4a90e2; font-weight: bold;"><?php echo $procurement_count; ?></span></a></li>
												<li><a href="#" onclick="filterDepartment('QA/R&D')">QA/R&D <span style="color: #4a90e2; font-weight: bold;"><?php echo $qa_count; ?></span></a></li>
												<li class="divider"></li>
												<li><a href="#" onclick="filterDepartment('all')">Show All</a></li>
											</ul>
										</div>
									</div>
								</ul>
								<div class="tab-content">
									<div class="tab-pane fade in active" id="assetList">
										<div class="table-responsive">
										<table class="table table-striped table-bordered table-hover" id="dataTables-myticket">
												<thead>
													<tr>				
														<th>ID</th>		
														<th>Employee Reference</th>											
														<th>Name</th>	
														<th>Last Name</th> 
														<th>Department</th>                                                   
														<th>Email</th>
														<th>License</th>
														<th>PC Desktop</th> 
														<th>Laptop</th> 
														<th>Smartphone</th>  
														<th>Tablet</th>
														<th>Printer</th>  
														<th>Accessories</th> 
														<th>Action</th>                       
													</tr>
												</thead>
												<tbody>
												<?php 
												// Establish database connection
												$conn = connectionDB();

												// Query to fetch inventory items with PC Desktop count
												$query = "SELECT 
													u.id,
													u.emp_refnum,
													u.current_owner,
													u.inv_lastname,
													u.department,
													u.email,
													i.license,
													COUNT(CASE WHEN i.asset_type = 'PC Desktop' THEN 1 END) as pc_desktop_count,
													GROUP_CONCAT(CASE WHEN i.asset_type = 'PC Desktop' THEN CONCAT(i.asset_tag, ':', i.asset_id) END SEPARATOR '|') as pc_desktop_assets,
													COUNT(CASE WHEN i.asset_type = 'Laptop' THEN 1 END) as laptop_count,
													GROUP_CONCAT(CASE WHEN i.asset_type = 'Laptop' THEN CONCAT(i.asset_tag, ':', i.asset_id) END SEPARATOR '|') as laptop_assets,
													COUNT(CASE WHEN i.asset_type = 'Smartphone' THEN 1 END) as smartphone_count,
													GROUP_CONCAT(CASE WHEN i.asset_type = 'Smartphone' THEN CONCAT(i.asset_tag, ':', i.asset_id) END SEPARATOR '|') as smartphone_assets,
													COUNT(CASE WHEN i.asset_type = 'Tablet' THEN 1 END) as tablet_count,
													GROUP_CONCAT(CASE WHEN i.asset_type = 'Tablet' THEN CONCAT(i.asset_tag, ':', i.asset_id) END SEPARATOR '|') as tablet_assets,
													COUNT(CASE WHEN i.asset_type = 'Printer' THEN 1 END) as printer_count,
													GROUP_CONCAT(CASE WHEN i.asset_type = 'Printer' THEN CONCAT(i.asset_tag, ':', i.asset_id) END SEPARATOR '|') as printer_assets,
													COUNT(CASE WHEN i.asset_type NOT IN ('PC Desktop', 'Laptop', 'Smartphone', 'Tablet', 'Printer') THEN 1 END) as accessories_count,
													GROUP_CONCAT(CASE WHEN i.asset_type NOT IN ('PC Desktop', 'Laptop', 'Smartphone', 'Tablet', 'Printer') THEN CONCAT(i.asset_tag, ':', i.asset_id) END SEPARATOR '|') as accessories_assets
												FROM inv_userlisttb u
												LEFT JOIN inv_inventorylisttb i ON u.current_owner = i.current_owner
												WHERE i.statusid = 0 
												AND u.current_owner IS NOT NULL 
												AND u.current_owner != ''
												GROUP BY u.id, u.emp_refnum, u.current_owner, u.inv_lastname, u.department, u.email, i.license";

												$result = mysqli_query($conn, $query);

												// Check if we have results
												if($result && mysqli_num_rows($result) > 0) {
													while($row = mysqli_fetch_assoc($result)) {
														?>
														<tr>                                                      
															<td><?php echo $row['id'] ? $row['id'] : '<span style="color: #fd7e14">N/A</span>'; ?></td>    
															<td><?php echo $row['emp_refnum'] ? $row['emp_refnum'] : '<span style="color: #fd7e14">N/A</span>'; ?></td>    
															<td><?php echo $row['current_owner'] ? $row['current_owner'] : '<span style="color: #fd7e14">N/A</span>'; ?></td>                                                    
															<td><?php echo $row['inv_lastname'] ? $row['inv_lastname'] : '<span style="color: #fd7e14">N/A</span>'; ?></td>
															<td><?php echo $row['department'] ? $row['department'] : '<span style="color: #fd7e14">N/A</span>'; ?></td>
															<td><?php echo $row['email'] ? $row['email'] : '<span style="color: #fd7e14">N/A</span>'; ?></td>
															<td><?php echo $row['license'] ? $row['license'] : '<span style="color: #fd7e14">N/A</span>'; ?></td>
															<td>
																<?php 
																echo $row['pc_desktop_count']; 
																if ($row['pc_desktop_assets']) {
																	$assets = explode('|', $row['pc_desktop_assets']);
																	foreach ($assets as $asset_pair) {
																		list($tag, $id) = explode(':', $asset_pair);
																		echo '<br><small style="color: #666;"><a href="assetInfoPage.php?asset_id=' . $id . '" style="color: #666;">(' . $tag . ')</a></small>';
																	}
																}
																?>
															</td>
															<td>
																<?php 
																echo $row['laptop_count']; 
																if ($row['laptop_assets']) {
																	$assets = explode('|', $row['laptop_assets']);
																	foreach ($assets as $asset_pair) {
																		list($tag, $id) = explode(':', $asset_pair);
																		echo '<br><small style="color: #666;"><a href="assetInfoPage.php?asset_id=' . $id . '" style="color: #666;">(' . $tag . ')</a></small>';
																	}
																}
																?>
															</td>
															<td>
																<?php 
																echo $row['smartphone_count']; 
																if ($row['smartphone_assets']) {
																	$assets = explode('|', $row['smartphone_assets']);
																	foreach ($assets as $asset_pair) {
																		list($tag, $id) = explode(':', $asset_pair);
																		echo '<br><small style="color: #666;"><a href="assetInfoPage.php?asset_id=' . $id . '" style="color: #666;">(' . $tag . ')</a></small>';
																	}
																}
																?>
															</td>
															<td>
																<?php 
																echo $row['tablet_count']; 
																if ($row['tablet_assets']) {
																	$assets = explode('|', $row['tablet_assets']);
																	foreach ($assets as $asset_pair) {
																		list($tag, $id) = explode(':', $asset_pair);
																		echo '<br><small style="color: #666;"><a href="assetInfoPage.php?asset_id=' . $id . '" style="color: #666;">(' . $tag . ')</a></small>';
																	}
																}
																?>
															</td>
															<td>
																<?php 
																echo $row['printer_count']; 
																if ($row['printer_assets']) {
																	$assets = explode('|', $row['printer_assets']);
																	foreach ($assets as $asset_pair) {
																		list($tag, $id) = explode(':', $asset_pair);
																		echo '<br><small style="color: #666;"><a href="assetInfoPage.php?asset_id=' . $id . '" style="color: #666;">(' . $tag . ')</a></small>';
																	}
																}
																?>
															</td>
															<td>
																<?php 
																echo $row['accessories_count']; 
																if ($row['accessories_assets']) {
																	$assets = explode('|', $row['accessories_assets']);
																	foreach ($assets as $asset_pair) {
																		list($tag, $id) = explode(':', $asset_pair);
																		echo '<br><small style="color: #666;"><a href="assetInfoPage.php?asset_id=' . $id . '" style="color: #666;">(' . $tag . ')</a></small>';
																	}
																}
																?>
															</td>
															
															<td>
															<div class="btn-group btn-group-xs" role="group">
																	<button type="button" class="btn btn-info" onclick="showUpdateAsset('<?php echo $row['id']; ?>');" data-toggle="modal" data-target="#myModal_updateTask">
																		<i class="ace-icon fa fa-pencil bigger-120"></i> Edit
																	</button>
																	<a href="assetInfoPage.php?asset_id=<?php echo $row['id']; ?>" class="btn btn-xs btn-danger">
																		<i class="ace-icon fa fa-eye icon-on-right"></i> View
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
													echo '<tr><td colspan="12" class="text-center">No inventory items found</td></tr>';
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
				// Initialize DataTable with search and sort functionality
				var table = $('#dataTables-myticket').DataTable({
					responsive: true,
					"order": [[ 0, "desc" ]],
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
				

			});

			function filterDepartment(department) {
				var table = $('#dataTables-myticket').DataTable();
				
				if (department === 'all') {
					table.column(3).search('').draw();
				} else {
					table.column(3).search(department).draw();
				}
				
				// Update the button text
				var buttonText = department === 'all' ? 'Filter Department' : 'Filter Department: ' + department;
				$('.btn-group .dropdown-toggle').text(buttonText + ' ');
				$('.btn-group .dropdown-toggle').append('<span class="caret"></span>');
			}

	
		</script>