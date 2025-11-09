<?php 
include('../check_session.php');
$moduleid=4;
include('proxy.php');
$conn = connectionDB();

// Get the ID parameter - prioritize 'id', but if not found, use 'taskId'
$id = isset($_GET['id']) ? $_GET['id'] : (isset($_REQUEST['taskId']) ? $_REQUEST['taskId'] : 0);

// Simple debugging
echo "<div style='color:blue;'>Using ID: $id</div>";

if (!$id) {
    echo "<div class='alert alert-danger'>Error: No ID provided. Please ensure you're passing an ID parameter.</div>";
    exit;
}

$myquery = "SELECT pm_projecttasktb.subject, pm_projecttasktb.description,
                   pm_projecttasktb.deadline, pm_projecttasktb.startdate, 
                   pm_projecttasktb.enddate, sys_priorityleveltb.priorityname,
                   pm_projecttasktb.percentdone, sys_taskclassificationtb.classification, 
                   sys_taskstatustb.statusname, sys_projecttb.projectname,
                   pm_projecttasktb.classificationid, pm_projecttasktb.statusid,
                   pm_projecttasktb.priorityid, pm_projecttasktb.projectid,
                   pm_projecttasktb.parent_id
            FROM pm_projecttasktb
            LEFT JOIN sys_taskclassificationtb ON sys_taskclassificationtb.id=pm_projecttasktb.classificationid
            LEFT JOIN sys_taskstatustb ON sys_taskstatustb.id=pm_projecttasktb.statusid
            LEFT JOIN sys_priorityleveltb ON sys_priorityleveltb.id=pm_projecttasktb.priorityid
            LEFT JOIN sys_projecttb ON sys_projecttb.id=pm_projecttasktb.projectid
            WHERE pm_projecttasktb.id = '$id'";
            
$myresult = mysqli_query($conn, $myquery);

// Check if query returned data
if (!$myresult || mysqli_num_rows($myresult) == 0) {
    echo "<div class='alert alert-danger'>Error: No record found with ID $id.</div>";
    exit;
}

// Initialize variables with default values
$mySubtaskProjectId = '';
$mySubtaskPriorityId = '';
$mySubtaskStatusId = '';
$mySubtaskClassId = '';
$mySubtaskSubject = '';
$mySubtaskDescription = '';
$mySubtaskDeadline = '';
$mySubtaskStartdate = '';
$mySubtaskEnddate = '';
$mySubtaskPriorityname = '';
$mySubtaskPercentage = '';
$mySubtaskClass = '';
$mySubtaskStatus = '';
$mySubtaskProjectname = '';
$parentId = '';

while($row = mysqli_fetch_assoc($myresult)){
    $mySubtaskProjectId = $row['projectid'];    
    $mySubtaskPriorityId = $row['priorityid'];    
    $mySubtaskStatusId = $row['statusid'];    
    $mySubtaskClassId = $row['classificationid'];    
    $mySubtaskSubject = $row['subject'];    
    $mySubtaskDescription = $row['description'];    
    $mySubtaskDeadline = $row['deadline'];    
    $mySubtaskStartdate = $row['startdate'];    
    $mySubtaskEnddate = $row['enddate'];    
    $mySubtaskPriorityname = $row['priorityname'];    
    $mySubtaskPercentage = $row['percentdone'];    
    $mySubtaskClass = $row['classification'];    
    $mySubtaskStatus = $row['statusname'];    
    $mySubtaskProjectname = $row['projectname'];
    $parentId = $row['parent_id'];

    if (($mySubtaskSubject == '')||($mySubtaskSubject == null)||($mySubtaskSubject == "NULL")){$mySubtaskSubject=NULL;}
    if (($mySubtaskDescription == '')||($mySubtaskDescription == null)||($mySubtaskDescription == "NULL")){$mySubtaskDescription=NULL;}
    if (($mySubtaskDeadline == '')||($mySubtaskDeadline == null)||($mySubtaskDeadline == "NULL")){$mySubtaskDeadline="1900-01-01";}
    if (($mySubtaskStartdate == '')||($mySubtaskStartdate == null)||($mySubtaskStartdate == "NULL")){$mySubtaskStartdate="1900-01-01";}
    if (($mySubtaskEnddate == '')||($mySubtaskEnddate == null)||($mySubtaskEnddate == "NULL")){$mySubtaskEnddate="1900-01-01";}
    if($mySubtaskEnddate == "1900-01-01"){$mySubtaskEnddate = null;}
    if($mySubtaskDeadline == "1900-01-01"){$mySubtaskDeadline = null;}
    if($mySubtaskStartdate == "1900-01-01"){$mySubtaskStartdate = null;}
}
?>
<!-- jQuery Tags Input -->
<link href="../assets/customjs/jquery.tagsinput.css" rel="stylesheet">
<!-- page specific plugin styles -->
<link rel="stylesheet" href="../assets/css/chosen.min.css" />

<div class="modal-body">
<style>
    /* Make selectors specific to this modal to prevent conflicts */
    #updateSubtaskModal .modal-body {
        max-width: 800px;
        margin: 0 auto;
    }
    
    #updateSubtaskModal .widget-body {
        padding: 15px;
    }
    
    #updateSubtaskModal .form-group {
        margin-bottom: 10px;
    }
    
    #updateSubtaskModal textarea {
        min-height: 100px;
    }
</style>
    <div id="signup-box" class="signup-box widget-box no-border">
        <div class="widget-body">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><small>×</small></button>
            <h4 class="header green lighter bigger">
                <i class="ace-icon fa fa-users blue"></i>
                Update Task: <?php echo $mySubtaskSubject; ?>
            </h4>
            <p> Update task details: </p>
            <form id="subtaskInfo">
                <input type="hidden" class="form-control" id="id" name="id" value="<?php echo $id; ?>"/>
                <input type="hidden" class="form-control" id="subtaskUserid" name="subtaskUserid" value="<?php echo $userid; ?>"/>
                <input type="hidden" class="form-control" id="parent_id" name="parent_id" value="<?php echo $parentId; ?>"/>
                
                <fieldset>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="block clearfix">Project Name
                                    <span class="block input-icon input-icon-left">
                                        <select class="form-control" id="projectid" name="projectid">
                                            <option value="">Select Owner</option>
                                            <?php 
                                                $query66 = "SELECT id,projectname FROM sys_projecttb WHERE statusid <> 6";
                                                $result66 = mysqli_query($conn, $query66);
                                                while($row66 = mysqli_fetch_assoc($result66)){
                                                    $projectname=mb_convert_case($row66['projectname'], MB_CASE_TITLE, "UTF-8");
                                                    $projectid=$row66['id'];
                                            ?>
                                            <option value="<?php echo $projectid; ?>" <?php if($mySubtaskProjectId == $projectid) echo "selected"; ?>>
                                                <?php echo $projectname; ?>
                                            </option>
                                            <?php } ?>
                                        </select>
                                    </span>
                                </label>
                            </div>  
                        </div>  
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="block clearfix">Classification
                                    <span class="block input-icon input-icon-left">
                                        <select class="form-control" id="classificationid" name="classificationid">
                                            <option value="">Select Classification</option>
                                            <?php 
                                                $query66 = "SELECT id,classification FROM sys_taskclassificationtb";
                                                $result66 = mysqli_query($conn, $query66);
                                                while($row66 = mysqli_fetch_assoc($result66)){
                                                    $classification=mb_convert_case($row66['classification'], MB_CASE_TITLE, "UTF-8");
                                                    $classificationid=$row66['id'];
                                            ?>
                                            <option value="<?php echo $classificationid; ?>" <?php if($mySubtaskClassId == $classificationid) echo "selected"; ?>>
                                                <?php echo $classification; ?>
                                            </option>
                                            <?php } ?>
                                        </select>
                                    </span>
                                </label>
                            </div>  
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="block clearfix">Priority Level
                                    <span class="block input-icon input-icon-left">
                                        <select class="form-control" id="priorityid" name="priorityid">
                                            <option value="">Select Priority</option>
                                            <?php 
                                                $query66 = "SELECT id,priorityname FROM sys_priorityleveltb";
                                                $result66 = mysqli_query($conn, $query66);
                                                while($row66 = mysqli_fetch_assoc($result66)){
                                                    $priorityname=mb_convert_case($row66['priorityname'], MB_CASE_TITLE, "UTF-8");
                                                    $prioritynameid=$row66['id'];
                                            ?>
                                            <option value="<?php echo $prioritynameid; ?>" <?php if($mySubtaskPriorityId == $prioritynameid) echo "selected"; ?>>
                                                <?php echo $priorityname; ?>
                                            </option>
                                            <?php } ?>
                                        </select>
                                    </span>
                                </label>
                            </div>  
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="block clearfix">Subject
                                    <span class="block input-icon input-icon-left">
                                        <i class="ace-icon fa fa-user"></i>
                                        <input class="form-control" placeholder="Subject" id="subject" name="subject" value="<?php echo $mySubtaskSubject; ?>" />
                                    </span>
                                </label>
                            </div>  
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="block clearfix">Assignee
                                    <select multiple name="taskAssigneeUp2[]" id="taskAssigneeUp2" class="chosen-select form-control" data-placeholder="Select Assignee(s)">
                                        <?php 
                                        // Get all active users
                                        $query66 = "SELECT id, user_firstname, user_lastname FROM sys_usertb WHERE user_statusid = '1'";
                                        $result66 = mysqli_query($conn, $query66);
                                        
                                        // Get current assignees from the task table
                                        $query_current = "SELECT assignee FROM pm_projecttasktb WHERE id = '$id'";
                                        $result_current = mysqli_query($conn, $query_current);
                                        $row_current = mysqli_fetch_assoc($result_current);
                                        
                                        // Convert comma-separated assignees to array
                                        $current_assignees = !empty($row_current['assignee']) ? 
                                            explode(',', $row_current['assignee']) : array();

                                        while($row66 = mysqli_fetch_assoc($result66)) {
                                            $user_firstname = mb_convert_case($row66['user_firstname'], MB_CASE_TITLE, "UTF-8");
                                            $user_lastname = mb_convert_case($row66['user_lastname'], MB_CASE_TITLE, "UTF-8");
                                            $engrid = $row66['id'];
                                            $name = $user_firstname . " " . $user_lastname;
                                            
                                            // Check if this user is in the current assignees array
                                            $isSelected = in_array($engrid, $current_assignees) ? true : false;
                                        ?>
                                            <option value="<?php echo $engrid; ?>" <?php echo $isSelected ? 'selected' : ''; ?>>
                                                <?php echo $name; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="block clearfix">Target Date
                                    <span class="block input-icon input-icon-left">
                                        <i class="ace-icon fa fa-calendar"></i>
                                        <input class="form-control date-picker" id="deadline" name="deadline" type="text" data-date-format="yyyy-mm-dd" placeholder="yyyy-mm-dd" value="<?php echo $mySubtaskDeadline; ?>"/>
                                    </span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="block clearfix">Start Date
                                    <span class="block input-icon input-icon-left">
                                        <i class="ace-icon fa fa-calendar"></i>
                                        <input class="form-control date-picker" id="startdate" name="startdate" type="text" data-date-format="yyyy-mm-dd" placeholder="yyyy-mm-dd" value="<?php echo $mySubtaskStartdate; ?>"/>
                                    </span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="block clearfix">End Date
                                    <span class="block input-icon input-icon-left">
                                        <i class="ace-icon fa fa-calendar"></i>
                                        <input class="form-control date-picker" id="enddate" name="enddate" type="text" data-date-format="yyyy-mm-dd" placeholder="yyyy-mm-dd" value="<?php echo $mySubtaskEnddate; ?>"/>
                                    </span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="block clearfix">Task Status
                                    <span class="block input-icon input-icon-left">
                                        <select class="form-control" id="statusid" name="statusid">
                                            <option value="">Select Status</option>
                                            <?php 
                                                $query66 = "SELECT * FROM sys_taskstatustb";
                                                $result66 = mysqli_query($conn, $query66);
                                                while($row66 = mysqli_fetch_assoc($result66)){
                                                    $statusname=mb_convert_case($row66['statusname'], MB_CASE_TITLE, "UTF-8");
                                                    $statusid=$row66['id'];
                                            ?>
                                            <option value="<?php echo $statusid; ?>" <?php if($mySubtaskStatusId == $statusid) echo "selected"; ?>>
                                                <?php echo $statusname; ?>
                                            </option>
                                            <?php } ?>
                                        </select>
                                    </span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="block clearfix">Description
                                    <textarea id="description" name="description" rows="8" cols="80" class="form-control"><?php echo trim(strip_tags($mySubtaskDescription)); ?></textarea>
                                </label>
                            </div>  
                        </div>
                    </div>  

                    <div class="clearfix">
                        <div id="flash5"></div>  
                        <div id="insert_search5"></div>  
                        <button type="button" class="width-25 pull-right btn btn-sm" id="resetSubtaskBtn" name="resetSubtaskBtn" data-dismiss="modal">
                            <i class="ace-icon fa fa-refresh"></i>
                            <span class="bigger-110">Close</span>
                        </button>

                        <button type="button" class="width-25 pull-right btn btn-sm btn-success" id="submitSubtaskBtn" name="submitSubtaskBtn">
                            <span class="bigger-110">Update</span>
                            <i class="ace-icon fa fa-arrow-right icon-on-right"></i>
                        </button>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>
</div>


<script src="../assets/customjs/jquery.tagsinput.js"></script>
<script src="../assets/js/jquery-ui.custom.min.js"></script>
<script src="../assets/js/chosen.jquery.min.js"></script>

<script type="text/javascript">
    // Save page position and prevent scrolling
    var scrollPosition;
    
    $(document).ready(function() {
        // Prevent modal from causing page scroll
        $('#updateSubtaskModal').on('show.bs.modal', function() {
            scrollPosition = $(window).scrollTop();
        });
        
        $('#updateSubtaskModal').on('shown.bs.modal', function() {
            $(window).scrollTop(scrollPosition);
        });
        
        // Also ensure position is maintained when modal closes
        $('#updateSubtaskModal').on('hide.bs.modal', function() {
            // Store current position again in case it changed
            scrollPosition = $(window).scrollTop();
        });
        
        $('#updateSubtaskModal').on('hidden.bs.modal', function() {
            // Restore scroll position after modal is fully hidden
            $(window).scrollTop(scrollPosition);
            
            // Add a slight delay to ensure browser has fully processed the modal closing
            setTimeout(function() {
                $(window).scrollTop(scrollPosition);
            }, 50);
        });
    });

    $('.date-picker').datepicker({
        autoclose: true,
        todayHighlight: true
    });
    
    // Find the parent modal-dialog and set its size
    $(document).ready(function() {
        $(function() {
            $('#updateSubtaskModal .modal-dialog').removeClass('modal-lg modal-xl').addClass('modal-sm');
        });
    });
    
    if(!ace.vars['touch']) {
        $('.chosen-select').chosen({
            allow_single_deselect: true,
            width: '100%',
            placeholder_text_multiple: "Select Assignee(s)"
        }); 
        
        $('#chosen-multiple-style .btn').on('click', function(e){
            var target = $(this).find('input[type=radio]');
            var which = parseInt(target.val());
            if(which == 2) $('#taskAssigneeUp2').addClass('tag-input-style');
             else $('#taskAssigneeUp2').removeClass('tag-input-style');
        });
    }

    // Handle focus management for accessibility - UPDATED MODAL ID
    $('#updateSubtaskModal').on('hidden.bs.modal', function () {
        // Ensure focus moves to a visible element outside the modal
        setTimeout(function() {
            // Try to focus on the element that opened the modal, or a sensible fallback
            $('[data-target="#updateSubtaskModal"]').focus();
        }, 0);
    });

    // Reset focus when modal opens to prevent focus being on hidden elements - UPDATED MODAL ID
    $('#updateSubtaskModal').on('shown.bs.modal', function () {
        // Focus on first visible input
        $('#subject').focus();
    });

    // Handle form submission
    $('#submitSubtaskBtn').click(function(e) {
        e.preventDefault();
        
        var formData = new FormData($('#subtaskInfo')[0]);
        
        // Handle multiple assignees properly
        var selectedAssignees = $('#taskAssigneeUp2').val() || [];
        
        // Keep the original field name taskAssigneeUp2[]
        formData.delete('taskAssigneeUp2[]');
        selectedAssignees.forEach(function(assignee) {
            formData.append('taskAssigneeUp2[]', assignee);
        });
        
        // Debug logging
        console.log('Selected assignees:', selectedAssignees);
        console.log('Form data:');
        for (var pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        
        // Ajax submission
        $.ajax({
            url: 'update_subtask.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Raw response:', response); // Add this line for debugging
                try {
                    if (typeof response === 'string') {
                        response = JSON.parse(response);
                    }
                    
                    if(response.success) {
                        $('#flash5').html('<div class="alert alert-success">' + response.message + '</div>');
                        setTimeout(function() {
                            $('#updateSubtaskModal').modal('hide');
                            if(typeof loadTasks === 'function') {
                                loadTasks();
                            }
                        }, 1500);
                    } else {
                        $('#flash5').html('<div class="alert alert-danger">' + (response.message || 'Unknown error occurred') + '</div>');
                    }
                } catch(e) {
                    console.error('Error parsing response:', e, 'Raw response:', response);
                    $('#flash5').html('<div class="alert alert-danger">Error processing server response.</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                console.error('Status:', status);
                console.error('Response Text:', xhr.responseText);
                $('#flash5').html('<div class="alert alert-danger">Error updating task. Please check console for details.</div>');
            }
        });
    });
    
    // Also handle the close button to ensure it properly closes the modal - UPDATED MODAL ID
    $('#resetSubtaskBtn').click(function() {
        $('#updateSubtaskModal').modal('hide');
    });
</script>

<script>
    $(document).ready(function() {
        // Initialize Chosen with specific options
        $('.chosen-select').chosen({
            allow_single_deselect: true,
            width: '100%',
            placeholder_text_multiple: "Select Assignee(s)",
            search_contains: true
        });
    });
</script>

<script>
    // Add after your existing document.ready function
    $('#taskAssigneeUp2').on('change', function(e, params) {
        var selected = $(this).val();
        console.log('Currently selected assignees:');
        selected.forEach(function(userId) {
            var text = $('#taskAssigneeUp2 option[value="' + userId + '"]').text();
            console.log('ID:', userId, 'Name:', text);
        });
    });
</script>