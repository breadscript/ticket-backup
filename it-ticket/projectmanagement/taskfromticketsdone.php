<?php
date_default_timezone_set('Asia/Manila');
$nowdateunix = date("Y-m-d h:i:s",time());  
require('../conn/db.php');
$conn = connectionDB();

// Function to truncate text with ellipsis
function truncateText($text, $maxLength = 30) {
    if (strlen($text) <= $maxLength) {
        return $text;
    }
    return substr($text, 0, $maxLength) . '...';
}

?>
<div class="table-responsive">
    <table class="table table-striped table-bordered table-hover" id="dataTables-tasktbticketsdone">
        <thead>
            <tr>
                <th>Task ID</th>
                <th>Created By</th>
                <th>Date Created</th>
                <th>Tracker</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Subject</th>
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
                    WHERE pm_projecttasktb.istask = '2' AND pm_projecttasktb.statusid IN ('6','5')
                    ORDER BY pm_projecttasktb.datetimecreated ASC";
        $myresult = mysqli_query($conn, $myquery);

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
                    $myEvaluation = "Exact Delivery";   //exact
                }elseif($myTaskPercentage == 2){
                    $myEvaluation = "Late Delivery";    //late
                }elseif($myTaskPercentage == 3){
                    $myEvaluation = "Ahead Delivery";   //aheadoftime
                }else{
                    $myEvaluation = "In-progress";  //aheadoftime
                }
                /*------get hours---------*/
                $ts1 = strtotime($myTaskStartdate);
                // $ts2 = strtotime($nowdateunix);
                if(($myTaskEnddate == null)||($myTaskEnddate == '1900-01-1')){$ts2 = strtotime($nowdateunix);}else{$ts2 = strtotime($myTaskEnddate);}

                $seconds_diff = 0; // Initialize with a default value

                if ($ts1 > $ts2) {
                    $seconds_diff = date("d", $ts1 - $ts2); 
                } elseif ($ts1 < $ts2) {
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
                $counter++;
            }
        } else {
            echo '<tr><td colspan="11" class="text-center">No completed ticket tasks found</td></tr>';
        }
        ?>
        </tbody>
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