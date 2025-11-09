<?php
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d h:i:s",time());	
require('../conn/db.php');
$conn = connectionDB();
?>
<div class="table-responsive">
	<table class="table table-striped table-bordered table-hover" id="dataTables-tasktbticketsdone">
		<thead>
			<tr>
				<th></th>
				<th>Tracker</th>
				<th>Status</th>
				<th>Priority</th>
				<th>Subject</th>
				<th>Project</th>
				<th>Deadline(expected)</th>
				<th>Duration</th>
				<th>Note</th>
			</tr>
		</thead>
		<tbody>
		<?php 
		$conn = connectionDB();
		$counter = 1;
		$myquery = "SELECT pm_projecttasktb.id,pm_projecttasktb.subject,pm_projecttasktb.description,pm_projecttasktb.deadline,
						   pm_projecttasktb.startdate,pm_projecttasktb.enddate,pm_projecttasktb.classificationid,
						   pm_projecttasktb.statusid,pm_projecttasktb.priorityid,pm_projecttasktb.createdbyid,pm_projecttasktb.datetimecreated,
						   pm_projecttasktb.assignee,pm_projecttasktb.projectid,
						   sys_taskclassificationtb.classification,sys_taskstatustb.statusname,sys_priorityleveltb.priorityname,
						   sys_projecttb.projectname,pm_projecttasktb.percentdone
					FROM pm_projecttasktb
					LEFT JOIN sys_taskclassificationtb ON sys_taskclassificationtb.id=pm_projecttasktb.classificationid
					LEFT JOIN sys_taskstatustb ON sys_taskstatustb.id=pm_projecttasktb.statusid
					LEFT JOIN sys_priorityleveltb ON sys_priorityleveltb.id=pm_projecttasktb.priorityid
					LEFT JOIN sys_projecttb ON sys_projecttb.id=pm_projecttasktb.projectid
					WHERE pm_projecttasktb.istask = '2' AND pm_projecttasktb.statusid = '6'
					ORDER BY pm_projecttasktb.datetimecreated ASC";
		$myresult = mysqli_query($conn, $myquery);
		while($row = mysqli_fetch_assoc($myresult)){
			$taskId = $row['id'];	
			$myTaskStatusid = $row['statusid'];	
			$myTaskDeadline = $row['deadline'];	
			$myTaskStartdate = $row['startdate'];		
			$myTaskEnddate = $row['enddate'];	
			$myTaskPercentage = $row['percentdone'];	
			if($myTaskPercentage == 1){
				$myEvaluation = "Exact Delivery";	//exact
			}elseif($myTaskPercentage == 2){
				$myEvaluation = "Late Delivery";	//late
			}elseif($myTaskPercentage == 3){
				$myEvaluation = "Ahead Delivery";	//aheadoftime
			}else{
				$myEvaluation = "In-progress";	//aheadoftime
			}
			/*------get hours---------*/
			$ts1 = strtotime($myTaskStartdate);
			// $ts2 = strtotime($nowdateunix);
			if(($myTaskEnddate == null)||($myTaskEnddate == '1900-01-1')){$ts2 = strtotime($nowdateunix);}else{$ts2 = strtotime($myTaskEnddate);}

			// $seconds_diff = $ts2 - $ts1;
			// $seconds_diff = date("d",$ts2 - $ts1); 
			if($ts1 > $ts2){
				$seconds_diff = date("d",$ts1 - $ts2); 
			}elseif($ts1 < $ts2){
				$seconds_diff = date("d",$ts2 - $ts1); 
			}
			 // from today
			if($myTaskStatusid == 6){$myClass = 'success';}elseif($myTaskStatusid == 2){$myClass = 'info';}elseif($myTaskStatusid == 3){$myClass = 'warning';}else{$myClass = 'danger';}
		?>
			<tr onclick = "showUpdateTask('<?php echo $taskId;?>');" data-toggle="modal" href="#myModal_updateTask" class="<?php echo $myClass; ?>">
				<td><?php echo $counter;?></td>
				<td><?php echo $row['classification'];?></td>
				<td><?php echo $row['statusname'];?></td>
				<td><?php echo $row['priorityname'];?></td>
				<td><?php echo $row['subject'];?></td>
				<td><?php echo $row['projectname'];?></td>
				<td><?php echo $row['deadline'];?></td>
				<td><?php echo $seconds_diff." days";?></td>
				<td><?php echo $myEvaluation;?></td>
			</tr>
		
		</tbody>
		<?php
			$counter ++;
			}
		?>
	</table>
</div>

<script type="text/javascript">
 $(document).ready(function() {
       $('#dataTables-tasktbticketsdone').DataTable({
            "aaSorting": [],
			"scrollCollapse": true,	
			"autoWidth": false,
			"responsive": true,
			"bSort" : true,
			"lengthMenu": [ [10, 25, 50, 100, 200, 300, 400, 500], [10, 25, 50, 100, 200, 300, 400, 500] ]
        });
	});
</script>