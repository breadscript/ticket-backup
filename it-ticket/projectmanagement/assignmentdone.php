<?php
include('../check_session.php');
$moduleid=4;
include('proxy.php');
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d h:i:s",time());	
$conn = connectionDB();
// $myusergroupid;

// Function to truncate text with ellipsis
function truncateText($text, $maxLength = 30) {
    if (strlen($text) <= $maxLength) {
        return $text;
    }
    return substr($text, 0, $maxLength) . '...';
}

?>
<div class="table-responsive">
	<table class="table table-striped table-bordered table-hover" id="dataTables-taskpmdone">
		<thead>
			<tr>
				<th>Task ID</th>
				<th>Created By</th>
				<th>Date Created</th>
				<th>Tracker</th>
				<th>Status</th>
				<th>Priority</th>
				<th>Subject</th>
				<th>Assignee</th>
				<th>Project</th>
				<th>Deadline(expected)</th>
				<th>Duration</th>
				<th>Note</th>
				<th>Actions</th>
			</tr>
		</thead>
		<tbody>
		<?php 
		$conn = connectionDB();
		$counter = 1;
		if($myusergroupid == 1){
			// Get all task IDs assigned to anyone
			$myquery2 = "SELECT taskid FROM pm_taskassigneetb";
			$myresult2 = mysqli_query($conn, $myquery2);
			
			// Build an array of task IDs
			$taskIds = array();
			while($row2 = mysqli_fetch_assoc($myresult2)){
				$taskIds[] = $row2['taskid'];
			}
			
			// If we have task IDs, create a query with all of them
			if(!empty($taskIds)) {
				$taskIdList = implode(',', $taskIds);
				$myquery = "SELECT pm_projecttasktb.id,pm_projecttasktb.subject,pm_projecttasktb.description,pm_projecttasktb.deadline,
							   pm_projecttasktb.startdate,pm_projecttasktb.enddate,pm_projecttasktb.classificationid,
							   pm_projecttasktb.statusid,pm_projecttasktb.priorityid,pm_projecttasktb.createdbyid,pm_projecttasktb.datetimecreated,
							   pm_projecttasktb.assignee,pm_projecttasktb.projectid,
							   sys_taskclassificationtb.classification,sys_taskstatustb.statusname,sys_priorityleveltb.priorityname,
							   sys_projecttb.projectname,pm_projecttasktb.percentdone,pm_projecttasktb.srn_id
						FROM pm_projecttasktb
						LEFT JOIN sys_taskclassificationtb ON sys_taskclassificationtb.id=pm_projecttasktb.classificationid
						LEFT JOIN sys_taskstatustb ON sys_taskstatustb.id=pm_projecttasktb.statusid
						LEFT JOIN sys_priorityleveltb ON sys_priorityleveltb.id=pm_projecttasktb.priorityid
						LEFT JOIN sys_projecttb ON sys_projecttb.id=pm_projecttasktb.projectid
						WHERE pm_projecttasktb.istask = '1' AND pm_projecttasktb.id IN ($taskIdList) AND pm_projecttasktb.statusid = '6'
						ORDER BY pm_projecttasktb.datetimecreated ASC";
				$myresult = mysqli_query($conn, $myquery);
			} else {
				// No tasks found
				$myresult = false;
			}
		}else if($myusergroupid == 2 || $myusergroupid == 3){
			// Get all task IDs assigned to this user
			$myquery2 = "SELECT taskid FROM pm_taskassigneetb WHERE assigneeid = '$userid' ";
			$myresult2 = mysqli_query($conn, $myquery2);
			
			// Build an array of task IDs
			$taskIds = array();
			while($row2 = mysqli_fetch_assoc($myresult2)){
				$taskIds[] = $row2['taskid'];
			}
			
			// If we have task IDs, create a query with all of them
			if(!empty($taskIds)) {
				$taskIdList = implode(',', $taskIds);
				$myquery = "SELECT pm_projecttasktb.id,pm_projecttasktb.subject,pm_projecttasktb.description,pm_projecttasktb.deadline,
							   pm_projecttasktb.startdate,pm_projecttasktb.enddate,pm_projecttasktb.classificationid,
							   pm_projecttasktb.statusid,pm_projecttasktb.priorityid,pm_projecttasktb.createdbyid,pm_projecttasktb.datetimecreated,
							   pm_projecttasktb.assignee,pm_projecttasktb.projectid,
							   sys_taskclassificationtb.classification,sys_taskstatustb.statusname,sys_priorityleveltb.priorityname,
							   sys_projecttb.projectname,pm_projecttasktb.percentdone,pm_projecttasktb.srn_id
						FROM pm_projecttasktb
						LEFT JOIN sys_taskclassificationtb ON sys_taskclassificationtb.id=pm_projecttasktb.classificationid
						LEFT JOIN sys_taskstatustb ON sys_taskstatustb.id=pm_projecttasktb.statusid
						LEFT JOIN sys_priorityleveltb ON sys_priorityleveltb.id=pm_projecttasktb.priorityid
						LEFT JOIN sys_projecttb ON sys_projecttb.id=pm_projecttasktb.projectid
						WHERE pm_projecttasktb.istask = '1' AND pm_projecttasktb.id IN ($taskIdList) AND pm_projecttasktb.statusid = '6'
						ORDER BY pm_projecttasktb.datetimecreated ASC";
				$myresult = mysqli_query($conn, $myquery);
			} else {
				// No tasks found
				$myresult = false;
			}
		}else{
			$myquery = "SELECT pm_projecttasktb.id,pm_projecttasktb.srn_id,pm_projecttasktb.subject,pm_projecttasktb.description,pm_projecttasktb.deadline,
							   pm_projecttasktb.startdate,pm_projecttasktb.enddate,pm_projecttasktb.classificationid,
							   pm_projecttasktb.statusid,pm_projecttasktb.priorityid,pm_projecttasktb.createdbyid,pm_projecttasktb.datetimecreated,
							   pm_projecttasktb.assignee,pm_projecttasktb.projectid,
							   sys_taskclassificationtb.classification,sys_taskstatustb.statusname,sys_priorityleveltb.priorityname,
							   sys_projecttb.projectname,pm_projecttasktb.percentdone,pm_projecttasktb.srn_id
						FROM pm_projecttasktb
						LEFT JOIN sys_taskclassificationtb ON sys_taskclassificationtb.id=pm_projecttasktb.classificationid
						LEFT JOIN sys_taskstatustb ON sys_taskstatustb.id=pm_projecttasktb.statusid
						LEFT JOIN sys_priorityleveltb ON sys_priorityleveltb.id=pm_projecttasktb.priorityid
						LEFT JOIN sys_projecttb ON sys_projecttb.id=pm_projecttasktb.projectid
						WHERE pm_projecttasktb.istask = '1' AND pm_projecttasktb.statusid = '6'
						ORDER BY pm_projecttasktb.datetimecreated ASC";
			$myresult = mysqli_query($conn, $myquery);
		}
		
		// Check if we have results before trying to fetch
		if($myresult && mysqli_num_rows($myresult) > 0) {
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
				$ts2 = ($myTaskEnddate == null || $myTaskEnddate == '1900-01-1') ? strtotime($nowdateunix) : strtotime($myTaskEnddate);
				
				// Initialize $seconds_diff with a default value
				$seconds_diff = 0;

				if($ts1 > $ts2){
					$seconds_diff = date("d", $ts1 - $ts2); 
				} elseif($ts1 < $ts2){
					$seconds_diff = date("d", $ts2 - $ts1); 
				}
				 // from today
				if($myTaskStatusid == 6){$myClass = 'success';}elseif($myTaskStatusid == 2){$myClass = 'info';}elseif($myTaskStatusid == 3){$myClass = 'warning';}else{$myClass = 'danger';}
			?>
				<tr class="<?php echo $myClass; ?>">
					<td><?php echo $row['srn_id'];?></td>
					<td>
						<?php 
							$createdbyid = mysqli_real_escape_string($conn, $row['createdbyid']);
							$user_query = mysqli_query($conn, "SELECT user_firstname, user_lastname FROM sys_usertb WHERE id = '$createdbyid'");
							if($user_query) {
								$user_data = mysqli_fetch_assoc($user_query);
								$creatorName = $user_data ? $user_data['user_firstname'].' '.$user_data['user_lastname'] : 'Unknown User';
								echo '<div class="truncated-text" title="' . htmlspecialchars($creatorName) . '">' . truncateText($creatorName, 20) . '</div>';
							} else {
								echo '<div class="truncated-text" title="Error: ' . mysqli_error($conn) . '">' . truncateText('Error: ' . mysqli_error($conn), 20) . '</div>';
							}
						?>
					</td>
					<td>
						<div class="truncated-text" title="<?php echo date('M d, Y h:i A', strtotime($row['datetimecreated'])); ?>">
							<?php echo truncateText(date('M d, Y h:i A', strtotime($row['datetimecreated'])), 18); ?>
						</div>
					</td>
					<td><?php echo $row['classification'];?></td>
					<td><?php echo $row['statusname'];?></td>
					<td><?php echo $row['priorityname'];?></td>
					<td>
						<div class="truncated-text" title="<?php echo htmlspecialchars($row['subject']); ?>">
							<?php echo truncateText($row['subject'], 30); ?>
						</div>
					</td>
					<td>
						<?php
							$assigneeIds = explode(',', $row['assignee']); // Assuming $row['assignee'] is a comma-separated string of IDs
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

							$assigneeText = implode('<br>', $assigneeNames);
							echo '<div class="truncated-text" title="' . htmlspecialchars(strip_tags($assigneeText)) . '">' . truncateText(strip_tags($assigneeText), 25) . '</div>';
						?>
					</td>
					<td>
						<div class="truncated-text" title="<?php echo htmlspecialchars($row['projectname']); ?>">
							<?php echo truncateText($row['projectname'], 20); ?>
						</div>
					</td>
					<td><?php echo $row['deadline'];?></td>
					<td><?php echo $seconds_diff." days";?></td>
					<td>
						<div class="truncated-text" title="<?php echo htmlspecialchars($myEvaluation); ?>">
							<?php echo truncateText($myEvaluation, 15); ?>
						</div>
					</td>
					<td>
						<div class="btn-group">
							<button type="button" class="btn btn-xs btn-info" onclick="showUpdateTask('<?php echo $taskId;?>');" data-toggle="modal" data-target="#myModal_updateTask">
								<i class="ace-icon fa fa-pencil bigger-120"></i> Edit
							</button>
							<a href="threadPage.php?taskId=<?php echo $taskId; ?>" class="btn btn-xs btn-danger">
								<i class="ace-icon fa fa-arrow-right icon-on-right"></i> >>
							</a>
						</div>
					</td>
				</tr>
			<?php
				$counter ++;
			}
		} else {
			echo '<tr><td colspan="12" class="text-center">No completed tasks found</td></tr>';
		}
		?>
		</tbody>
	</table>
</div>

<script type="text/javascript">
 $(document).ready(function() {
       $('#dataTables-taskpmdone').DataTable({
            "aaSorting": [],
			"scrollCollapse": true,	
			"autoWidth": false,
			"responsive": true,
			"bSort" : true,
			"lengthMenu": [ [10, 25, 50, 100, 200, 300, 400, 500], [10, 25, 50, 100, 200, 300, 400, 500] ]
        });
	});
</script>