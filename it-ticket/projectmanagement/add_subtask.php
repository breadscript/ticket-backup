<?php 
include('../check_session.php');
$moduleid=4;
$conn = connectionDB();
?>
<!-- CKEditor -->
<script src="../assets/ckeditor/ckeditor.js"></script>
<!-- jQuery Tags Input -->
<link href="../assets/customjs/jquery.tagsinput.css" rel="stylesheet">
<!-- page specific plugin styles -->
<link rel="stylesheet" href="../assets/css/chosen.min.css" />

<!-- Core DataTables -->
<script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>

<!-- DataTables Bootstrap 4 -->
<script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap4.min.js"></script>

<div class="modal-body">
    <style>
        /* Add this at the top of the modal-body */
        .modal-dialog {
            width: 90%; /* Adjust this percentage as needed */
            max-width: 800px; /* Optional: set a max-width to prevent it from getting too wide */
        }
    </style>
    <div id="signup-box" class="signup-box widget-box no-border">
        <div class="widget-body">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><small>×</small></button>
            <h4 class="header green lighter bigger">
                <i class="ace-icon fa fa-users blue"></i>
                New Subtask Registration
            </h4>
            <p> Enter your details to begin: </p>
            <form id="subtaskInfo">
                <input type="hidden" class="form-control" id="subtaskUserid" name="subtaskUserid" value="<?php echo $userid;?>"/>
                <input type="hidden" class="form-control" id="parentTaskId" name="parentTaskId" value="<?php echo $_GET['taskId'] ?? ''; ?>"/>
                
                <fieldset>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="block clearfix">Project Name
                                    <span class="block input-icon input-icon-left">
                                        <select class="form-control" id="taskProjectOwner" name="taskProjectOwner">
                                            <option value="">Select Owner</option>
                                            <?php 
                                                $query66 = "SELECT id,projectname FROM sys_projecttb WHERE statusid <> 6";
                                                $result66 = mysqli_query($conn, $query66);
                                                while($row66 = mysqli_fetch_assoc($result66)){
                                                    $projectname=mb_convert_case($row66['projectname'], MB_CASE_TITLE, "UTF-8");
                                                    $projectid=$row66['id'];
                                            ?>
                                            <option value="<?php echo $projectid;?>"><?php echo $projectname;?></option>
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
                                        <select class="form-control" id="taskClassification" name="taskClassification">
                                            <option value="">Select Classification</option>
                                            <?php 
                                                $query66 = "SELECT id,classification FROM sys_taskclassificationtb";
                                                $result66 = mysqli_query($conn, $query66);
                                                while($row66 = mysqli_fetch_assoc($result66)){
                                                    $classification=mb_convert_case($row66['classification'], MB_CASE_TITLE, "UTF-8");
                                                    $classificationid=$row66['id'];
                                            ?>
                                            <option value="<?php echo $classificationid;?>"><?php echo $classification;?></option>
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
                                        <select class="form-control" id="subtaskPriority" name="subtaskPriority">
                                            <option value="">Select Priority</option>
                                            <?php 
                                                $query66 = "SELECT id,priorityname FROM sys_priorityleveltb";
                                                $result66 = mysqli_query($conn, $query66);
                                                while($row66 = mysqli_fetch_assoc($result66)){
                                                    $priorityname=mb_convert_case($row66['priorityname'], MB_CASE_TITLE, "UTF-8");
                                                    $prioritynameid=$row66['id'];
                                            ?>
                                            <option value="<?php echo $prioritynameid;?>"><?php echo $priorityname;?></option>
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
                                        <input class="form-control" placeholder="subtaskSubject" id="subtaskSubject" name="subtaskSubject" />
                                    </span>
                                </label>
                            </div>  
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="block clearfix">Assignee
                                    <span class="block input-icon input-icon-left">
                                        <select name="subtaskAssignee[]" id="subtaskAssignee" class="form-control chosen-select" multiple>
                                            <option value="">Select Assignee</option>
                                            <?php 
                                                $query66 = "SELECT id,user_firstname,user_lastname FROM sys_usertb WHERE user_statusid = '1' ";
                                                $result66 = mysqli_query($conn, $query66);
                                                while($row66 = mysqli_fetch_assoc($result66)){
                                                    $user_firstname=mb_convert_case($row66['user_firstname'], MB_CASE_TITLE, "UTF-8");
                                                    $user_lastname=mb_convert_case($row66['user_lastname'], MB_CASE_TITLE, "UTF-8");
                                                    $engrid=$row66['id'];
                                                    $name = $user_firstname." , ".$user_lastname;
                                            ?>
                                            <option value="<?php echo $engrid;?>"><?php echo $name;?></option>
                                            <?php } ?>
                                        </select>
                                    </span>
                                </label>
                            </div>  

                            <div class="form-group">
                                <label class="block clearfix">Target Date
                                    <span class="block input-icon input-icon-left">
                                        <i class="ace-icon fa fa-calendar"></i>
                                        <input class="form-control date-picker" id="subtaskTargetDate" name="subtaskTargetDate" type="text" data-date-format="yyyy-mm-dd" placeholder="yyyy-mm-dd"/>
                                    </span>
                                </label>
                            </div>

                            <div class="form-group">
                                <label class="block clearfix">Start Date
                                    <span class="block input-icon input-icon-left">
                                        <i class="ace-icon fa fa-calendar"></i>
                                        <input class="form-control date-picker" id="subtaskStartDate" name="subtaskStartDate" type="text" data-date-format="yyyy-mm-dd" placeholder="yyyy-mm-dd"/>
                                        <input class="form-control date-picker" id="subtaskEndDate" name="subtaskEndDate" type="hidden" data-date-format="yyyy-mm-dd" value="1900-01-01"/>
                                    </span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="block clearfix">Description
                                    <textarea id="subtaskDescription" name="subtaskDescription" rows="5" cols="80"></textarea>
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
                            <span class="bigger-110">Register</span>
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
    CKEDITOR.replace('subtaskDescription');
    
    $('.date-picker').datepicker({
        autoclose: true,
        todayHighlight: true,
        format: 'yyyy-mm-dd',
        dateFormat: 'yy-mm-dd',
        language: 'en',
        orientation: 'auto'
    }).on('changeDate', function(e) {
        $(this).val(formatDate(e.date));
    });

    // Helper function to format date as YYYY-MM-DD
    function formatDate(date) {
        var d = new Date(date),
            month = '' + (d.getMonth() + 1),
            day = '' + d.getDate(),
            year = d.getFullYear();

        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;

        return [year, month, day].join('-');
    }
    
    if(!ace.vars['touch']) {
        $('.chosen-select').chosen({
            allow_single_deselect: true,
            width: '100%',
            placeholder_text_multiple: "Select Assignee(s)"
        }); 
        
        $('#chosen-multiple-style .btn').on('click', function(e){
            var target = $(this).find('input[type=radio]');
            var which = parseInt(target.val());
            if(which == 2) $('#form-field-select-4').addClass('tag-input-style');
             else $('#form-field-select-4').removeClass('tag-input-style');
        });
    }

    // Prepare input fields for validation icons - with BLACK asterisks
    $('input:not([type="hidden"]), select').each(function() {
        var id = $(this).attr('id');
        if (id) {
            // Add required asterisk to all fields (in BLACK)
            var $parent = $(this).closest('.block.clearfix');
            var $iconSpan = $parent.find('.input-icon-left');
            
            if ($iconSpan.length) {
                $iconSpan.append('<i class="validation-icon glyphicon glyphicon-asterisk" style="position: absolute; right: 10px; top: 10px; color: #000000; font-size: 12px;"></i>');
            }
            
            // Add error message container
            $(this).after('<div id="' + id + '-error" class="error-message" style="display:none; color:#d9534f; font-size:12px; margin-top:5px;"></div>');
        }
    });

    // Handle form submission with custom validation
    $('#submitSubtaskBtn').click(function() {
        var isValid = validateSubtaskForm();
        
        if(isValid) {
            // Get CKEditor content
            var description = CKEDITOR.instances.subtaskDescription.getData();
            
            // Create FormData object
            var formData = new FormData($('#subtaskInfo')[0]);
            
            // Remove any existing subtaskDescription and add the one from CKEditor
            formData.delete('subtaskDescription');
            formData.append('subtaskDescription', description);

            // Ajax submission
            $.ajax({
                url: 'save_subtask.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                beforeSend: function() {
                    $("#flash5").show();
                    $("#flash5").html('<img src="../assets/images/ajax-loader.gif" align="absmiddle">&nbsp;Saving...Please Wait.');
                },
                success: function(result) {
                    if(result.success) {
                        $('#flash5').html('<div class="alert alert-success">' + result.message + '</div>');
                        setTimeout(function() {
                            $('#modal-subtask').modal('hide');
                            if(typeof loadTasks === 'function') {
                                loadTasks();
                            }
                        }, 1500);
                    } else {
                        $('#flash5').html('<div class="alert alert-danger">' + (result.message || 'Unknown error occurred') + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Status:', status);
                    console.log('Error:', error);
                    console.log('Response:', xhr.responseText);
                    $('#flash5').html('<div class="alert alert-danger">Error submitting form. Please check console for details.</div>');
                }
            });
        }
    });
    
    // Custom form validation function - only runs when Register button is clicked
    function validateSubtaskForm() {
        $('#flash5').html(''); // Clear previous messages
        
        // Clear all error messages first
        $('.error-message').hide();
        $('input, select').removeClass('has-error');
        
        var isValid = true;
        
        // Project validation
        if (!$('#taskProjectOwner').val()) {
            $('#taskProjectOwner').addClass('has-error');
            $('#taskProjectOwner-error').text('The project name is required').show();
            $('#taskProjectOwner').closest('.block.clearfix').find('.validation-icon')
                .removeClass('glyphicon-asterisk').addClass('glyphicon-remove').css('color', '#d9534f');
            isValid = false;
        } else {
            $('#taskProjectOwner').closest('.block.clearfix').find('.validation-icon')
                .removeClass('glyphicon-asterisk').addClass('glyphicon-ok').css('color', '#5cb85c');
        }
        
        // Classification validation
        if (!$('#taskClassification').val()) {
            $('#taskClassification').addClass('has-error');
            $('#taskClassification-error').text('The classification is required').show();
            $('#taskClassification').closest('.block.clearfix').find('.validation-icon')
                .removeClass('glyphicon-asterisk').addClass('glyphicon-remove').css('color', '#d9534f');
            isValid = false;
        } else {
            $('#taskClassification').closest('.block.clearfix').find('.validation-icon')
                .removeClass('glyphicon-asterisk').addClass('glyphicon-ok').css('color', '#5cb85c');
        }
        
        // Priority validation
        if (!$('#subtaskPriority').val()) {
            $('#subtaskPriority').addClass('has-error');
            $('#subtaskPriority-error').text('The priority level is required').show();
            $('#subtaskPriority').closest('.block.clearfix').find('.validation-icon')
                .removeClass('glyphicon-asterisk').addClass('glyphicon-remove').css('color', '#d9534f');
            isValid = false;
        } else {
            $('#subtaskPriority').closest('.block.clearfix').find('.validation-icon')
                .removeClass('glyphicon-asterisk').addClass('glyphicon-ok').css('color', '#5cb85c');
        }
        
        // Subject validation
        if (!$('#subtaskSubject').val()) {
            $('#subtaskSubject').addClass('has-error');
            $('#subtaskSubject-error').text('The subject is required').show();
            $('#subtaskSubject').closest('.block.clearfix').find('.validation-icon')
                .removeClass('glyphicon-asterisk').addClass('glyphicon-remove').css('color', '#d9534f');
            isValid = false;
        } else {
            $('#subtaskSubject').closest('.block.clearfix').find('.validation-icon')
                .removeClass('glyphicon-asterisk').addClass('glyphicon-ok').css('color', '#5cb85c');
        }
        
        // Assignee validation
        if (!$('#subtaskAssignee').val() || $('#subtaskAssignee').val().length === 0) {
            $('#subtaskAssignee').next('.chosen-container').addClass('has-error');
            $('#subtaskAssignee-error').text('At least one assignee is required').show();
            $('#subtaskAssignee').closest('.block.clearfix').find('.validation-icon')
                .removeClass('glyphicon-asterisk').addClass('glyphicon-remove').css('color', '#d9534f');
            isValid = false;
        } else {
            $('#subtaskAssignee').closest('.block.clearfix').find('.validation-icon')
                .removeClass('glyphicon-asterisk').addClass('glyphicon-ok').css('color', '#5cb85c');
        }
        
        // Target date validation
        if (!$('#subtaskTargetDate').val()) {
            $('#subtaskTargetDate').addClass('has-error');
            $('#subtaskTargetDate-error').text('Target date is required').show();
            $('#subtaskTargetDate').closest('.block.clearfix').find('.validation-icon')
                .removeClass('glyphicon-asterisk').addClass('glyphicon-remove').css('color', '#d9534f');
            isValid = false;
        } else if (!isValidDate($('#subtaskTargetDate').val())) {
            $('#subtaskTargetDate').addClass('has-error');
            $('#subtaskTargetDate-error').text('Target date format should be YYYY-MM-DD').show();
            $('#subtaskTargetDate').closest('.block.clearfix').find('.validation-icon')
                .removeClass('glyphicon-asterisk').addClass('glyphicon-remove').css('color', '#d9534f');
            isValid = false;
        } else {
            $('#subtaskTargetDate').closest('.block.clearfix').find('.validation-icon')
                .removeClass('glyphicon-asterisk').addClass('glyphicon-ok').css('color', '#5cb85c');
        }
        
        // Start date validation
        if (!$('#subtaskStartDate').val()) {
            $('#subtaskStartDate').addClass('has-error');
            $('#subtaskStartDate-error').text('Start date is required').show();
            $('#subtaskStartDate').closest('.block.clearfix').find('.validation-icon')
                .removeClass('glyphicon-asterisk').addClass('glyphicon-remove').css('color', '#d9534f');
            isValid = false;
        } else if (!isValidDate($('#subtaskStartDate').val())) {
            $('#subtaskStartDate').addClass('has-error');
            $('#subtaskStartDate-error').text('Start date format should be YYYY-MM-DD').show();
            $('#subtaskStartDate').closest('.block.clearfix').find('.validation-icon')
                .removeClass('glyphicon-asterisk').addClass('glyphicon-remove').css('color', '#d9534f');
            isValid = false;
        } else {
            $('#subtaskStartDate').closest('.block.clearfix').find('.validation-icon')
                .removeClass('glyphicon-asterisk').addClass('glyphicon-ok').css('color', '#5cb85c');
        }
        
        return isValid;
    }
    
    // Date format validation helper
    function isValidDate(dateStr) {
        if (!dateStr) return false;
        var regex = /^\d{4}-\d{2}-\d{2}$/;
        if (!regex.test(dateStr)) return false;
        
        // Additional date validity check
        var date = new Date(dateStr);
        return date instanceof Date && !isNaN(date);
    }

    // Add CSS for validation styling
    $('<style>' +
      '.has-error { border-color: #d9534f !important; }' +
      '.error-message { color: #d9534f; font-size: 12px; margin-top: 5px; }' +
      '.input-icon-left { position: relative; }' +
      '.validation-icon { position: absolute; right: 10px; top: 10px; z-index: 10; }' +
      '</style>').appendTo('head');
</script>

