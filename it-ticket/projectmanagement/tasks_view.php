<?php
require_once('../conn/db.php');
require_once('../check_session.php');

// Get task ID from AJAX request
$taskId = $_GET['taskId'] ?? 0;
$userid = $_SESSION['userid'] ?? 0;
$myusergroupid = $_SESSION['usergroupid'] ?? 0;

if (!$mysqlconn) {
    echo '<div class="alert alert-danger">Database connection failed</div>';
    exit;
}

// Modify query to filter by parent task ID
$myquery = "SELECT DISTINCT pm_projecttasktb.id,pm_projecttasktb.subject,pm_projecttasktb.description,pm_projecttasktb.deadline,
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
            WHERE pm_projecttasktb.parent_id = '$taskId' 
            AND pm_projecttasktb.type = 'subtask'
            ORDER BY pm_projecttasktb.datetimecreated ASC";
?>

<!-- Replace your current DataTables CSS/JS with these lines -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap4.min.css">
<script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap4.min.js"></script>

<!-- Simple table with vertical scroll -->
<div>
    <?php
    $myresult = mysqli_query($mysqlconn, $myquery);
    
    if (!$myresult) {
        echo '<div class="alert alert-danger">Error executing query: ' . mysqli_error($mysqlconn) . '</div>';
    } else if (mysqli_num_rows($myresult) == 0) {
        echo '<div class="alert alert-info">No subtasks found for this task.</div>';
    } else {
    ?>
        <div style="overflow-y: auto; max-height: 400px;">
            <table id="subtasksTable" class="table table-striped table-bordered table-hover" style="width: 100%;">
                <thead>
                    <tr>
                        <th width="3%">#</th>
                        <th width="8%">Tracker</th>
                        <th width="8%">Status</th>
                        <th width="8%">Priority</th>
                        <th width="25%">Subject</th>
                        <th width="15%">Assignee</th>
                        <th width="15%">Project</th>
                        <th width="8%">Deadline</th>
                       
                        <th width="5%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                $counter = 1;
                
                while($row = mysqli_fetch_assoc($myresult)){
                    // Skip rows where statusid is 5
                    if($row['statusid'] == 5) continue;
                    
                    $taskId = $row['id'];    
                    $myTaskStatusid = $row['statusid'];    
                    $myTaskDeadline = $row['deadline'];    
                    $myTaskStartdate = $row['startdate'];        
                    $myTaskEnddate = $row['enddate'];    
                    $myTaskPercentage = $row['percentdone'];    
                    
                    if($myTaskPercentage == 1){
                        $myEvaluation = "Exact Delivery";
                    }elseif($myTaskPercentage == 2){
                        $myEvaluation = "Late Delivery";
                    }elseif($myTaskPercentage == 3){
                        $myEvaluation = "Ahead Delivery";
                    }else{
                        $myEvaluation = "In-progress";
                    }
                    
                    if($myTaskStatusid == 6){
                        $myClass = 'success';
                    }elseif($myTaskStatusid == 2){
                        $myClass = 'info';
                    }elseif($myTaskStatusid == 3){
                        $myClass = 'warning';
                    }else{
                        $myClass = 'danger';
                    }
                    ?>
                    <tr id="task-row-<?php echo $taskId; ?>" class="<?php echo $myClass; ?>">
                        <td><?php echo $counter;?></td>
                        <td><?php echo $row['classification'];?></td>
                        <td><?php echo $row['statusname'];?></td>
                        <td><?php echo $row['priorityname'];?></td>
                        <td><?php echo $row['subject'];?></td>
                        <td>
                            <?php
                            $assigneeNames = [];
                            $assignees = explode(',', $row['assignee']);
                            
                            foreach ($assignees as $assigneeId) {
                                if (empty($assigneeId)) continue;
                                
                                $myqueryxx2 = "SELECT CONCAT(user_firstname, ' ', user_lastname) AS full_name
                                            FROM sys_usertb 
                                            WHERE id = '" . mysqli_real_escape_string($mysqlconn, $assigneeId) . "'";
                                $myresultxx2 = mysqli_query($mysqlconn, $myqueryxx2);
                                
                                if ($myresultxx2 && mysqli_num_rows($myresultxx2) > 0) {
                                    $rowxx2 = mysqli_fetch_assoc($myresultxx2);
                                    $assigneeNames[] = $rowxx2['full_name'];
                                }
                            }
                            
                            echo !empty($assigneeNames) ? implode(', ', $assigneeNames) : "Unassigned";
                            ?>
                        </td>
                        <td><?php echo $row['projectname'];?></td>
                        <td><?php echo date('m/d/Y', strtotime($row['deadline']));?></td>
                        
                        <td>
                            <div class="btn-group" style="display: flex; gap: 4px;">
                                <button type="button" class="btn btn-primary btn-xs" data-toggle="modal" data-target="#updateSubtaskModal" onclick="loadSubtaskModal('<?php echo $taskId; ?>')">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-xs" onclick="deleteTask('<?php echo $taskId;?>');">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php
                    $counter++;
                }
                ?>
                </tbody>
            </table>
        </div>
    <?php
    }
    ?>
</div>

<!-- Add this modal container -->
<div class="modal fade" id="updateSubtaskModal" tabindex="-1" role="dialog" aria-labelledby="updateSubtaskModalLabel" aria-hidden="true">
    <!-- Modal content will be loaded here -->
</div>

<!-- Add this near the bottom of your page -->
<div class="modal fade" id="deleteSubtaskModal" tabindex="-1" role="dialog" aria-labelledby="deleteSubtaskModalLabel">
</div>

<script>
(function() {
    function initDataTable() {
        if (typeof $.fn.DataTable !== 'undefined') {
            try {
                var table = $('#subtasksTable').DataTable({
                    responsive: true,
                    paging: true,
                    searching: true,
                    ordering: true,
                    info: true,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                    language: {
                        emptyTable: "No subtasks available"
                    },
                    deferRender: false,
                    processing: false,
                    autoWidth: false,
                    stateSave: false,
                    columnDefs: [
                        { width: "3%", targets: 0 },
                        { width: "8%", targets: [1, 2, 3, 7] },
                        { width: "25%", targets: 4 },
                        { width: "15%", targets: [5, 6] },
                        { width: "5%", targets: 8, orderable: false, searchable: false }
                    ],
                    rowCallback: function(row, data, index) {
                        var rowId = data[0];
                        $('tr[id^="task-row-"]').each(function() {
                            var $this = $(this);
                            if ($this.find('td:first').text() == rowId) {
                                $(row).addClass($this.attr('class'));
                            }
                        });
                    },
                    drawCallback: function() {
                        $('.btn-group').css('display', 'flex').css('gap', '4px');
                    }
                });
                
                $('#subtasksTable_wrapper').css('opacity', 1);
            } catch (e) {
                // Silently handle any errors
            }
        } else {
            var script = document.createElement('script');
            script.src = 'https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js';
            script.onload = function() {
                if (typeof $.fn.DataTable !== 'undefined') {
                    $('#subtasksTable').DataTable({
                        responsive: false,
                        deferRender: false,
                        processing: false,
                        autoWidth: false,
                        paging: true,
                        ordering: true,
                        info: true
                    });
                    
                    [
                        'https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js',
                        'https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js',
                        'https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap4.min.js'
                    ].forEach(function(src) {
                        var script = document.createElement('script');
                        script.src = src;
                        document.head.appendChild(script);
                    });
                }
            };
            document.head.appendChild(script);
        }
    }
    
    // Remove the setTimeout to eliminate the artificial delay
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        // Execute immediately
        initDataTable();
    } else {
        // Execute as soon as DOM is ready
        document.addEventListener('DOMContentLoaded', initDataTable);
    }
})();

function deleteTask(taskId) {
    $('#deleteSubtaskModal').load('modal_deletesubtask.php?taskId=' + taskId, function() {
        $('#deleteSubtaskModal').modal('show');
    });
}

function loadSubtaskModal(taskId) {
    $('#updateSubtaskModal').load('modal_updatesubtask.php?taskId=' + taskId, function() {
        $('#updateSubtaskModal').modal('show');
    });
}
</script>

<!-- Add this CSS to remove animation delays -->
<style>
/* Override DataTables animations that cause delay */
.dataTable {
    transition: none !important;
    animation: none !important;
}
.dataTables_wrapper .row {
    transition: none !important;
    animation: none !important;
}
/* Make DataTables controls appear immediately */
.dataTables_length, .dataTables_filter, .dataTables_info, .dataTables_paginate {
    opacity: 1 !important;
    transition: none !important;
}
</style>