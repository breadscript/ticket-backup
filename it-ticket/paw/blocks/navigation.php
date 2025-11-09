<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userid = isset($_SESSION['userid']) ? intval($_SESSION['userid']) : 0;
include_once('../conn/db.php');

// Resolve display name and photo from session or DB
$conn = connectionDB();
$first = isset($_SESSION['userfirstname']) ? (string)$_SESSION['userfirstname'] : '';
$last = isset($_SESSION['userlastname']) ? (string)($_SESSION['userlastname'] ?? '') : '';
$uname = isset($_SESSION['username']) ? (string)$_SESSION['username'] : '';
$photo = '';
if ($userid > 0) {
    $rsU = mysqli_query($conn, "SELECT username, user_firstname, user_lastname, photo FROM sys_usertb WHERE id = ".$userid);
    if ($rsU && ($rowU = mysqli_fetch_assoc($rsU))) {
        if ($first === '' && !empty($rowU['user_firstname'])) $first = (string)$rowU['user_firstname'];
        if ($last === '' && !empty($rowU['user_lastname'])) $last = (string)$rowU['user_lastname'];
        if ($uname === '' && !empty($rowU['username'])) $uname = (string)$rowU['username'];
        if (!empty($rowU['photo'])) $photo = (string)$rowU['photo'];
    }
}
$displayName = trim($first.' '.$last);
if ($displayName === '') { $displayName = $uname !== '' ? $uname : 'User'; }
$displayName = htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8');
$profileImg = $photo !== '' ? $photo : 'images/img.jpg';
?>
<div class="col-md-3 left_col">
          <div class="left_col scroll-view">
            <div class="navbar nav_title" style="border: 0;">
              <a href="disbursement_main.php" class="site_title"><i class="fa fa-credit-card"></i> <span>TLCI</span></a>
            </div>

            <div class="clearfix"></div>

            <!-- menu profile quick info -->
            <div class="profile clearfix">
              <div class="profile_pic">
                <img src="<?php echo htmlspecialchars($profileImg, ENT_QUOTES, 'UTF-8'); ?>" alt="..." class="img-circle profile_img" onerror="this.onerror=null;this.src='images/img.jpg';">
              </div>
              <div class="profile_info">
                <span>Welcome,</span>
                <h2><?php echo $displayName; ?></h2>
              </div>
            </div>
            <!-- /menu profile quick info -->

            <br />

            <!-- sidebar menu -->
            <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
              <div class="menu_section">
                <h3>General</h3>
                <ul class="nav side-menu">
                  <li><a><i class="fa fa-home"></i> Disbursement <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                      <li><a href="../dmt-rfp/disbursement_main.php">Disbursement</a></li>
                      <li><a href="../dmt-rfp/company_main.php">Company</a></li>
                      <li><a href="../dmt-rfp/department_main.php">Department</a></li>
					  <li><a href="../dmt-rfp/category_main.php">Category</a></li>
					  <li><a href="../dmt-rfp/disbursement_approver_main.php">DMS Approval</a></li>
					  <li><a href="../dmt-rfp/workflow_template_main.php">Workflow Approval</a></li>
                    </ul>
                  </li>

                  <li><a><i class="fa fa-paw"></i> Paw Request <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                      <li><a href="../paw/paw_main.php">Promotional Activity Workplan</a></li>
                    </ul>
                  </li>

				  <?php
					// $userid=1;
					$conn = connectionDB();
					$myquery = "select b.id,b.modulecategory,b.modulecategorylogo
								from sys_authoritymodulecategorytb a
								left join sys_modulecategorytb b on a.modulecategoryid=b.id
								where a.userid=$userid and a.statid= '1' and b.statid= '1'
								ORDER BY b.id ASC";
					
					$myresult = mysqli_query($conn, $myquery);
					while($row = mysqli_fetch_assoc($myresult)){
						$modulecategoryid = $row['id'];				
						$modulecategory = $row['modulecategory'];	
						$modulecategorylogo = $row['modulecategorylogo'];	
						
						echo "<li>";
							//first level
							echo "<a>
									<i class='$modulecategorylogo'></i>$modulecategory
									<span class='fa fa-chevron-down'></span>
								</a>";
							//second level
							echo "<ul class='nav child_menu'>";
								$query2= "select b.modulecategoryid,c.id as parentid,c. modulename,c.modulepath,c.modulepath_index 
											,c.firstlevel,c.secondlevel
											from sys_authoritymoduletb b
										left join sys_moduletb c on b.moduleid = c.id
										where b.statid= '1' and b.modulecategoryid='$modulecategoryid' and b.userid=$userid
										and c.statid= '1' and c.firstlevel = 0
										ORDER BY c.id ASC";
								$result2 = mysqli_query($conn, $query2);  
								while($row2 = mysqli_fetch_assoc($result2)){
									$parentid = $row2['parentid'];				
									$modulecategory = $row2['modulecategoryid'];	
									$modulename = $row2['modulename'];
									$modulepath = $row2['modulepath'];	
									$modulepath_index = $row2['modulepath_index'];	
									$firstlevel = $row2['firstlevel'];	
									$secondlevel = $row2['secondlevel'];
									
									if($secondlevel == 0){
										//no child menu	<li><a href="index.html">Dashboard</a></li>
										echo "<li>";
											echo "<a href='../$modulepath_index$modulepath'>$modulename</a>";
										echo "</li>";	
									}else{
										echo "<ul class='nav child_menu'>";
											$query2a= "select b.modulecategoryid,c. modulename,c.modulepath,c.modulepath_index 
														,c.firstlevel,c.secondlevel
														from sys_authoritymoduletb b
														left join sys_moduletb c on b.moduleid = c.id
														where b.statid= '1' and b.modulecategoryid='$modulecategoryid' and b.userid=$userid
														and c.statid= '1' and c.firstlevel = $parentid
														ORDER BY c.id ASC";
											$result2a = pg_query($conn, $query2a);                        
											while (@$row2a = pg_fetch_array($result2a, NULL, PGSQL_ASSOC)){				
												$modulecategory2 = $row2a['modulecategoryid'];	
												$modulename2 = $row2a['modulename'];
												$modulepath2 = $row2a['modulepath'];	
												$modulepath_index2 = $row2a['modulepath_index'];	
												$firstlevel2 = $row2a['firstlevel'];	
												$secondlevel2 = $row2a['secondlevel'];
																												
												echo "<li>";
													echo "<a href='../$modulepath_index2$modulepath2'>$modulename2</a>";
												echo "</li>";																						
											}
											
											
											// echo "<b class='arrow'></b>";
										echo "</ul>";
									}
								}
							echo "</ul>";
						echo "</li>";					 
					}
				?>
                  
                </ul>
              </div>
              

            </div>
            <!-- /sidebar menu -->

            <!-- /menu footer buttons -->
            <div class="sidebar-footer hidden-small">
              <a data-toggle="tooltip" data-placement="top" title="Settings">
                <span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
              </a>
              <a data-toggle="tooltip" data-placement="top" title="FullScreen">
                <span class="glyphicon glyphicon-fullscreen" aria-hidden="true"></span>
              </a>
              <a data-toggle="tooltip" data-placement="top" title="Lock">
                <span class="glyphicon glyphicon-eye-close" aria-hidden="true"></span>
              </a>
              <a data-toggle="tooltip" data-placement="top" title="Logout" href="logout.php">
                <span class="glyphicon glyphicon-off" aria-hidden="true"></span>
              </a>
            </div>
            <!-- /menu footer buttons -->
          </div>
        </div>