<?php include_once('../conn/db.php'); ?>

<div id="sidebar" class="sidebar responsive ace-save-state">
	<script type="text/javascript">
		try{ace.settings.loadState('sidebar')}catch(e){}
	</script>

	<div class="sidebar-shortcuts" id="sidebar-shortcuts">
		<div class="sidebar-shortcuts-large" id="sidebar-shortcuts-large">
			<button class="btn btn-success">
				<i class="ace-icon fa fa-signal"></i>
			</button>
			<button class="btn btn-info">
				<i class="ace-icon fa fa-pencil"></i>
			</button>
			<button class="btn btn-warning">
				<i class="ace-icon fa fa-users"></i>
			</button>
			<button class="btn btn-danger">
				<i class="ace-icon fa fa-cogs"></i>
			</button>
		</div>
		<div class="sidebar-shortcuts-mini" id="sidebar-shortcuts-mini">
			<span class="btn btn-success"></span>
			<span class="btn btn-info"></span>
			<span class="btn btn-warning"></span>
			<span class="btn btn-danger"></span>
		</div>
	</div><!-- /.sidebar-shortcuts -->

	<ul class="nav nav-list">
		<li class="active">
			<a href="../index.php">
				<i class="menu-icon fa fa-tachometer"></i>
				<span class="menu-text"> Dashboard </span>
			</a>
			<b class="arrow"></b>
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
				
				echo "<li class=''>";
					//first level
					echo "<a href='#' class='dropdown-toggle'>
							<i class='$modulecategorylogo'></i>
							<span class='menu-text'>
								$modulecategory
							</span>
							<b class='arrow fa fa-angle-down'></b>
						</a>";
						echo "<b class='arrow'></b>";
					//second level
					echo "<ul class='submenu'>";
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
								//no child menu
								echo "<li class=''>";
									echo "<a href='../$modulepath_index$modulepath'><i class='menu-icon fa fa-caret-right'></i>$modulename</a>";
									echo "<b class='arrow'></b>";
								echo "</li>";	
							}else{
								echo "<ul class='submenu'>";
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
																										
										echo "<li class=''>";
											echo "<a href='../$modulepath_index2$modulepath2'><i class='menu-icon fa fa-caret-right'></i>$modulename2</a>";
										echo "</li>";																						
									}
									
									
									echo "<b class='arrow'></b>";
								echo "</ul>";
							}
						}
					echo "</ul>";
				echo "</li>";					 
			}
		?>
					
	</ul><!-- /.nav-list -->

		<div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
			<i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state" data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i>
		</div>
</div>