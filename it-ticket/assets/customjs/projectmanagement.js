function showcreatetask(id2) {
	var id2 = id2;
	// alert(id2);	
												
	/*var searchstring =  '';																
	if(id ==''){searchstring=searchstring;}else{searchstring=searchstring+" "+id;}
	*/																																	
	var dataString = 'id2='+ id2;									
				
	// alert(dataString);
				
	$.ajax({
	type: "GET",
	url: "modal_createtask.php",
	data: dataString,
	cache: false,																							
			  success: function(html)
			    {
				   $("#myModal_createTask").show();
				   $('#myModal_createTask').empty();
				   $("#myModal_createTask").append(html);
											   
			   },
			  error: function(html)
			    {
				   $("#myModal_createTask").show();
				   $('#myModal_createTask').empty();
				   $("#myModal_createTask").append(html);	
				   $("#myModal_createTask").hide();											   												   		
			   }											   
	        });
}

/*====processing of data from form to php==============*/
$(document).ready(function() {
	$('#taskInfo')
	.on('init.field.bv', function(e, data) {	
	var $parent    = data.element.parents('.form-group'),
	    $icon      = $parent.find('.form-control-feedback[data-bv-icon-for="' + data.field + '"]'),
	    options    = data.bv.getOptions(),                      // Entire options
	    validators = data.bv.getOptions(data.field).validators; // The field validators
	
	    if (validators.notEmpty && options.feedbackIcons && options.feedbackIcons.required) {
	        $icon.addClass(options.feedbackIcons.required).show();
	    }
	})   
	.bootstrapValidator({
        message: 'This value is not valid',
        live: 'enabled',
		// submitButtons: 'button[type="button"]',			    
		submitButtons: 'button[name="submitTasktBtn"]',			    
        feedbackIcons: {
        required: 'glyphicon glyphicon-asterisk',
            valid: 'glyphicon glyphicon-ok',
            invalid: 'glyphicon glyphicon-remove',
            validating: 'glyphicon glyphicon-refresh'
        },
        fields: {
            taskProjectOwner: {
                validators: {
                    notEmpty: {
                        message: 'The owner is required'
                    }                            
                }
            },
			taskClassification: {
                validators: {
                    notEmpty: {
                        message: 'The classification is required'
                    }                            
                }
            },
			taskPriority: {
                validators: {
                    notEmpty: {
                        message: 'The priority level is required'
                    }                            
                }
            },
			taskSubject: {
                validators: {
                    notEmpty: {
                        message: 'The subject is required'
                    }                            
                }
            },
			'taskAssignee[]': {
                validators: {
                    notEmpty: {
                        message: 'Person assigned is required'
                    }                            
                }
            },
			taskTargetDate: {
                validators: {
                    notEmpty: {
                        message: 'Target Date is required'
                    },
					date: {
						format: 'YYYY-MM-DD',
						message: 'The date is not valid. Should be in YYYY-MM-DD'
					}
                }
            },
			taskStartDate: {
                validators: {
                    notEmpty: {
                        message: 'Start Date is required'
                    },
					date: {
						format: 'YYYY-MM-DD',
						message: 'The date is not valid. Should be in YYYY-MM-DD'
					}
                }
            },
			taskEndDate: {
                validators: {
                    notEmpty: {
                        message: 'Expected date to finish is required'
                    },
					date: {
						format: 'YYYY-MM-DD',
						message: 'The date is not valid. Should be in YYYY-MM-DD'
					}
                }
            }
		}
    })
	.on('success.field.bv', function(e, data) {
         console.log(data.field, data.element, '-->success');
    });
	$('#submitTasktBtn').click(function(e) {
		// Prevent multiple clicks
		var $btn = $(this);
		if ($btn.prop('disabled')) {
			return;
		}
		
		$('#taskInfo').bootstrapValidator('validate');
		var bootstrapValidator = $('#taskInfo').data('bootstrapValidator');
		
		if (bootstrapValidator.isValid()) {
			// Disable the button immediately
			$btn.prop('disabled', true);
			
			// Create FormData object
			var formData = new FormData($('#taskInfo')[0]);
			
			// Add the CKEDITOR content
			formData.append('description', CKEDITOR.instances.description.getData());
			
			// Add other form fields
			formData.append('taskProjectOwner', $("#taskProjectOwner").val());
			formData.append('taskUserid', $("#taskUserid").val());
			formData.append('taskClassification', $("#taskClassification").val());
			formData.append('taskPriority', $("#taskPriority").val());
			formData.append('taskSubject', $("#taskSubject").val());
			formData.append('taskAssignee2', $("#taskAssignee2").val());
			formData.append('taskTargetDate', $("#taskTargetDate").val());
			formData.append('taskStartDate', $("#taskStartDate").val());
			formData.append('taskEndDate', $("#taskEndDate").val());

			$.ajax({
				type: "POST",
				url: "saveTask.php",
				data: formData,
				processData: false,
				contentType: false,
				cache: false,
				dataType: 'html',
				beforeSend: function() {
					$("#flash5").show().html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Saving...Please Wait.');
				},
				success: function(response) {
					$("#flash5").hide();
					
					// First try to parse as JSON
					try {
						const result = JSON.parse(response);
						if (result.success) {
							handleSuccessfulSave();
						} else {
							$("#insert_search5").show().html(result.message);
							$btn.prop('disabled', false);
						}
					} catch (e) {
						// If not JSON, handle as HTML response
						if (response.includes('Task Saved Successfully')) {
							handleSuccessfulSave();
						} else {
							$("#insert_search5").show().html(response);
							$btn.prop('disabled', false);
						}
					}
				},
				error: function(xhr, status, error) {
					// Check if the response contains success message despite being treated as an error
					if (xhr.responseText && xhr.responseText.includes('Task Saved Successfully')) {
						$("#insert_search5").show().html(xhr.responseText);
						handleSuccessfulSave();
					} else {
						console.error('Ajax error:', error);
						$("#flash5").html('<div class="alert alert-danger">Error saving task. Please try again.</div>');
						$btn.prop('disabled', false);
					}
				}
			});
		}

		function handleSuccessfulSave() {
			// Show success message
			$("#flash5").html('<div class="alert alert-success">' +
				'<i class="fa fa-check-circle"></i> ' +
				'Task saved successfully!' +
			'</div>').show();
			
			// Clear any existing messages in insert_search5
			$("#insert_search5").empty();
			
			// Disable form inputs
			$('#taskInfo :input').prop('disabled', true);
			$('#taskInfo').off();
			
			// Close modal after delay
			setTimeout(function() {
				$('.modal').modal('hide');
			}, 1500);
		}
	});	
});



//project updateCommands
function showUpdateTask(id2) {
	var id2 = id2;
																																	
	var dataString = 'id2='+ id2;									
				
	// alert(dataString);
				
	$.ajax({
	type: "GET",
	url: "modal_updatetask.php",
	data: dataString,
	cache: false,																							
			  success: function(html)
			    {
				   $("#myModal_updateTask").show();
				   $('#myModal_updateTask').empty();
				   $("#myModal_updateTask").append(html);
											   
			   },
			  error: function(html)
			    {
				   $("#myModal_updateTask").show();
				   $('#myModal_updateTask').empty();
				   $("#myModal_updateTask").append(html);	
				   $("#myModal_updateTask").hide();											   												   		
			   }											   
	        });
}


/*====processing of data from form to php==============*/
$(document).ready(function() {
	$('#taskInfoUpdate')
	.on('init.field.bv', function(e, data) {	
	var $parent    = data.element.parents('.form-group'),
	    $icon      = $parent.find('.form-control-feedback[data-bv-icon-for="' + data.field + '"]'),
	    options    = data.bv.getOptions(),                      // Entire options
	    validators = data.bv.getOptions(data.field).validators; // The field validators
	
	    if (validators.notEmpty && options.feedbackIcons && options.feedbackIcons.required) {
	        $icon.addClass(options.feedbackIcons.required).show();
	    }
	})   
	.bootstrapValidator({
        message: 'This value is not valid',
        live: 'enabled',
		// submitButtons: 'button[type="button"]',			    
		submitButtons: 'button[name="submitTaskUptBtn"]',			    
        feedbackIcons: {
        required: 'glyphicon glyphicon-asterisk',
            valid: 'glyphicon glyphicon-ok',
            invalid: 'glyphicon glyphicon-remove',
            validating: 'glyphicon glyphicon-refresh'
        },
        fields: {
            projectOwnerUp2: {
                validators: {
                    notEmpty: {
                        message: 'The owner is required'
                    }                            
                }
            },
			taskClassificationUp2: {
                validators: {
                    notEmpty: {
                        message: 'The classification is required'
                    }                            
                }
            },
			taskPriorityUp2: {
                validators: {
                    notEmpty: {
                        message: 'The priority level is required'
                    }                            
                }
            },
			taskSubjectUp2: {
                validators: {
                    notEmpty: {
                        message: 'The subject is required'
                    }                            
                }
            },
			taskAssigneeUp2: {
                validators: {
                    notEmpty: {
                        message: 'Person assigned is required'
                    }                            
                }
            },
			taskStatus2: {
                validators: {
                    notEmpty: {
                        message: 'Status is required'
                    }                            
                }
            },
			taskTargetDateUp2: {
                validators: {
                    notEmpty: {
                        message: 'Target Date is required'
                    },
					date: {
						format: 'YYYY-MM-DD',
						message: 'The date is not valid. Should be in YYYY-MM-DD'
					}
                }
            },
			taskStartDateUp2: {
                validators: {
                    notEmpty: {
                        message: 'Start Date is required'
                    },
					date: {
						format: 'YYYY-MM-DD',
						message: 'The date is not valid. Should be in YYYY-MM-DD'
					}
                }
            },
			taskEndDateUp2: {
                validators: {
                    notEmpty: {
                        message: 'Expected date to finish is required'
                    },
					date: {
						format: 'YYYY-MM-DD',
						message: 'The date is not valid. Should be in YYYY-MM-DD'
					}
                }
            }
		}
    })
	.on('success.field.bv', function(e, data) {
         console.log(data.field, data.element, '-->success');
    });

    // Add these event handlers for input fields
    $('#taskInfoUpdate input, #taskInfoUpdate select').on('change keyup', function() {
        // Revalidate the field when its value is changed
        $('#taskInfoUpdate').bootstrapValidator('revalidateField', $(this).attr('name'));
    });

    // For date picker fields specifically
    $('.date-picker').on('changeDate', function() {
        // Revalidate the field when date is changed
        $('#taskInfoUpdate').bootstrapValidator('revalidateField', $(this).attr('name'));
    });

    // For chosen select fields (if you're using them)
    $('.chosen-select').on('change', function() {
        // Revalidate the field when selection is changed
        $('#taskInfoUpdate').bootstrapValidator('revalidateField', $(this).attr('name'));
    });

	$('#submitTaskUptBtn').click(function() {
		$('#taskInfoUpdate').bootstrapValidator('validate');
		var bootstrapValidator = $('#taskInfoUpdate').data('bootstrapValidator');
		var stat1 = bootstrapValidator.isValid();
		if(stat1=='1')
		{
			var taskIdUp2 = $("#taskIdUp2").val();	
			var taskUseridUp2 = $("#taskUseridUp2").val();	
			var projectOwnerUp2 = $("#projectOwnerUp2").val();	
			var taskClassificationUp2 = $("#taskClassificationUp2").val();	
			var taskPriorityUp2 = $("#taskPriorityUp2").val();	
			var taskSubjectUp2 = encodeURIComponent($("#taskSubjectUp2").val());	
			var taskAssigneeUp2 = $("#taskAssigneeUp2").val();	
			var taskTargetDateUp2 = $("#taskTargetDateUp2").val();	
			var taskStartDateUp2 = $("#taskStartDateUp2").val();	
			var taskEndDateUp2 = $("#taskEndDateUp2").val();		
			var taskStatus2 = $("#taskStatus2").val();		
			var descriptionupdate2 = CKEDITOR.instances.descriptionupdate2.getData();
			var descriptionupdate2 = encodeURIComponent(descriptionupdate2);
			// Add resolution
			var resolution = CKEDITOR.instances.resolution.getData();
			var resolution = encodeURIComponent(resolution);
			
			var dataString = 'taskIdUp2='+ taskIdUp2
							+'&taskUseridUp2='+ taskUseridUp2
							+'&projectOwnerUp2='+ projectOwnerUp2
							+'&taskClassificationUp2='+ taskClassificationUp2
							+'&taskPriorityUp2='+ taskPriorityUp2
							+'&taskSubjectUp2='+ taskSubjectUp2
							+'&taskAssigneeUp2='+ taskAssigneeUp2
							+'&taskTargetDateUp2='+ taskTargetDateUp2
							+'&taskStartDateUp2='+ taskStartDateUp2
							+'&taskStatus2='+ taskStatus2
							+'&taskEndDateUp2='+ taskEndDateUp2
							+'&descriptionupdate2='+ descriptionupdate2
							+'&resolution='+ resolution;
			
			// alert(taskAssignee2);
			$.ajax({
				type: "GET",
				url: "updateTask.php",
				data: dataString,
				cache: false,									
						beforeSend: function(html) 
							{			   
								$("#flashtaskup").show();
								$("#flashtaskup").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Saving...Please Wait.');				
							},															
						success: function(html)
						    {
								$("#insert_taskup").show();
								$('#insert_taskup').empty();
								$("#insert_taskup").append(html);
								$("#flashtaskup").hide();																		   
						   },
						error: function(html)
						    {
								$("#insert_taskup").show();
								$('#insert_taskup').empty();
								$("#insert_taskup").append(html);
								$("#flashtaskup").hide();													   												   		
						   }											   
			});
		}
	});	
	//revalidate
	$('#taskInfoUpdate').bootstrapValidator('revalidateField', 'taskStartDateUp2');
	$('#taskInfoUpdate').bootstrapValidator('revalidateField', 'taskTargetDateUp2');
	
});

$('#tabUL a[href="#taskfromticket"]').on('click', function(event){				
	showtasksfromticket();
});

function showtasksfromticket() {
	// alert('test');
	var user_id = $("#user_id").val();	
	var mygroup = $("#mygroup").val();	
	var dataString = 'user_id='+ user_id
					+'&mygroup='+ mygroup;
	$.ajax({
	type: "GET",
	url: "taskfromtickets.php",
	data: dataString,
	cache: false,	
			beforeSend: function(html) 
				{			   
					$("#insert_tickets").show();
					$("#insert_tickets").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Loading...Please Wait.');				
				},	
			  success: function(html)
				{
				   $("#insertbody_tickets").show();
				   $('#insertbody_tickets').empty();
				   $("#insertbody_tickets").append(html);
				   $("#insert_tickets").hide();				   			   		  
			   },
			  error: function(html)
				{
				   $("#insertbody_tickets").show();
				   $('#insertbody_tickets').empty();
				   $("#insertbody_tickets").append(html);	
				   $("#insertbody_tickets").hide();	
				   $("#insert_tickets").hide();			   					   		
			   }						   
	});
}

$('#tabUL a[href="#taskfrompmdone"]').on('click', function(event){				
	showtasksfrompmdone();
});

function showtasksfrompmdone() {
	// alert('test');
	$.ajax({
	type: "GET",
	url: "assignmentdone.php",
	cache: false,	
			beforeSend: function(html) 
				{			   
					$("#insert_assignmentdone").show();
					$("#insert_assignmentdone").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Loading...Please Wait.');				
				},	
			  success: function(html)
				{
				   $("#insertbody_assignmentdone").show();
				   $('#insertbody_assignmentdone').empty();
				   $("#insertbody_assignmentdone").append(html);
				   $("#insert_assignmentdone").hide();				   			   		  
			   },
			  error: function(html)
				{
				   $("#insertbody_assignmentdone").show();
				   $('#insertbody_assignmentdone').empty();
				   $("#insertbody_assignmentdone").append(html);	
				   $("#insertbody_assignmentdone").hide();	
				   $("#insert_assignmentdone").hide();			   					   		
			   }						   
	});
}


$('#tabUL a[href="#taskfromticketdone"]').on('click', function(event){				
	showtasksticketdone();
});

function showtasksticketdone() {
	// alert('test');
	$.ajax({
	type: "GET",
	url: "taskfromticketsdone.php",
	cache: false,	
			beforeSend: function(html) 
				{			   
					$("#insert_taskdone").show();
					$("#insert_taskdone").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Loading...Please Wait.');				
				},	
			  success: function(html)
				{
				   $("#insertbody_taskdone").show();
				   $('#insertbody_taskdone').empty();
				   $("#insertbody_taskdone").append(html);
				   $("#insert_taskdone").hide();				   			   		  
			   },
			  error: function(html)
				{
				   $("#insertbody_taskdone").show();
				   $('#insertbody_taskdone').empty();
				   $("#insertbody_taskdone").append(html);	
				   $("#insertbody_taskdone").hide();	
				   $("#insert_taskdone").hide();			   					   		
			   }						   
	});
}

$('#tabUL a[href="#taskfrompmreject"]').on('click', function(event){				
	showtasksfrompmreject();
});

function showtasksfrompmreject() {
	// alert('test');
	$.ajax({
	type: "GET",
	url: "assignmentreject.php",
	cache: false,	
			beforeSend: function(html) 
				{			   
					$("#insert_taskreject").show();
					$("#insert_taskreject").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Loading...Please Wait.');				
				},	
			  success: function(html)
				{
				   $("#insertbody_taskreject").show();
				   $('#insertbody_taskreject').empty();
				   $("#insertbody_taskreject").append(html);
				   $("#insert_taskreject").hide();				   			   		  
			   },
			  error: function(html)
				{
				   $("#insertbody_taskreject").show();
				   $('#insertbody_taskreject').empty();
				   $("#insertbody_taskreject").append(html);	
				   $("#insertbody_taskreject").hide();	
				   $("#insert_taskreject").hide();			   					   		
			   }						   
	});
}

$('#tabUL a[href="#taskfromticketreject"]').on('click', function(event){				
	showtasksticketreject();
});

function showtasksticketreject() {
	// alert('test');
	$.ajax({
	type: "GET",
	url: "taskfromticketsreject.php",
	cache: false,	
			beforeSend: function(html) 
				{			   
					$("#insert_ticketreject").show();
					$("#insert_ticketreject").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Loading...Please Wait.');				
				},	
			  success: function(html)
				{
				   $("#insertbody_ticketreject").show();
				   $('#insertbody_ticketreject').empty();
				   $("#insertbody_ticketreject").append(html);
				   $("#insert_ticketreject").hide();				   			   		  
			   },
			  error: function(html)
				{
				   $("#insertbody_ticketreject").show();
				   $('#insertbody_ticketreject').empty();
				   $("#insertbody_ticketreject").append(html);	
				   $("#insertbody_ticketreject").hide();	
				   $("#insert_ticketreject").hide();			   					   		
			   }						   
	});
}


/*
#open thread
*/
function showopenThread(id2) {
	var id2 = id2;
																																	
	var dataString = 'id2='+ id2;									
				
	// alert(dataString);
				
	$.ajax({
	type: "GET",
	url: "modal_threads.php",
	data: dataString,
	cache: false,																							
			  success: function(html)
			    {
				   $("#insert_openThread").show();
				   $('#insert_openThread').empty();
				   $("#insert_openThread").append(html);
											   
			   },
			  error: function(html)
			    {
				   $("#insert_openThread").show();
				   $('#insert_openThread').empty();
				   $("#insert_openThread").append(html);	
				   $("#insert_openThread").hide();											   												   		
			   }											   
	        });
}

$(document).ready(function() {
    $('#subtaskInfo').bootstrapValidator({
        message: 'This value is not valid',
        live: 'enabled',
        submitButtons: 'button[name="submitSubtaskBtn"]',
        feedbackIcons: {
            required: 'glyphicon glyphicon-asterisk',
            valid: 'glyphicon glyphicon-ok',
            invalid: 'glyphicon glyphicon-remove',
            validating: 'glyphicon glyphicon-refresh'
        },
        fields: {
            taskProjectOwner: {
                validators: {
                    notEmpty: {
                        message: 'The project owner is required'
                    }
                }
            },
            taskClassification: {
                validators: {
                    notEmpty: {
                        message: 'The classification is required'
                    }
                }
            },
            subtaskPriority: {
                validators: {
                    notEmpty: {
                        message: 'The priority level is required'
                    }
                }
            },
            subtaskSubject: {
                validators: {
                    notEmpty: {
                        message: 'The subject is required'
                    }
                }
            },
            'subtaskAssignee[]': {
                validators: {
                    notEmpty: {
                        message: 'At least one assignee is required'
                    }
                }
            },
            subtaskTargetDate: {
                validators: {
                    notEmpty: {
                        message: 'Target date is required'
                    },
                    date: {
                        format: 'YYYY-MM-DD',
                        message: 'The date is not valid. Should be in YYYY-MM-DD'
                    }
                }
            },
            subtaskStartDate: {
                validators: {
                    notEmpty: {
                        message: 'Start date is required'
                    },
                    date: {
                        format: 'YYYY-MM-DD',
                        message: 'The date is not valid. Should be in YYYY-MM-DD'
                    }
                }
            }
        }
    });

    // Handle form submission
    $('#submitSubtaskBtn').click(function() {
        $('#subtaskInfo').bootstrapValidator('validate');
        var bootstrapValidator = $('#subtaskInfo').data('bootstrapValidator');
        var stat1 = bootstrapValidator.isValid();
        if (stat1) {
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
});

function showAddSubTaskModal(taskId) {
    // Load the modal content via AJAX
    $.get('add_subtask.php', { taskId: taskId }, function(data) {
        // Create modal if it doesn't exist
        if (!$('#modal-subtask').length) {
            $('body').append('<div class="modal fade" id="modal-subtask" tabindex="-1" role="dialog">' +
                '<div class="modal-dialog modal-lg" role="document">' +
                '<div class="modal-content"></div>' +
                '</div>' +
                '</div>');
        }
        
        // Set the modal content and show it
        $('#modal-subtask .modal-content').html(data);
        $('#modal-subtask').modal('show');
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error('Error loading subtask modal:', errorThrown);
        alert('Error loading the subtask form. Please try again.');
    });
}


