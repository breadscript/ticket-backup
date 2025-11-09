function showcreateticket(id2) {
	var id2 = id2;
	// alert(id2);	
												
	/*var searchstring =  '';																
	if(id ==''){searchstring=searchstring;}else{searchstring=searchstring+" "+id;}
	*/																																	
	var dataString = 'id2='+ id2;									
				
	// alert(dataString);
				
	$.ajax({
	type: "GET",
	url: "modal_createticket.php",
	data: dataString,
	cache: false,																							
			  success: function(html)
			    {
				   $("#myModal_createTicket").show();
				   $('#myModal_createTicket').empty();
				   $("#myModal_createTicket").append(html);
											   
			   },
			  error: function(html)
			    {
				   $("#myModal_createTicket").show();
				   $('#myModal_createTicket').empty();
				   $("#myModal_createTicket").append(html);	
				   $("#myModal_createTicket").hide();											   												   		
			   }											   
	        });
}

/*====processing of data from form to php==============*/
$(document).ready(function() {
	$('#ticketInfo')
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
		submitButtons: 'button[name="submitTicketBtn"]',			    
        feedbackIcons: {
        required: 'glyphicon glyphicon-asterisk',
            valid: 'glyphicon glyphicon-ok',
            invalid: 'glyphicon glyphicon-remove',
            validating: 'glyphicon glyphicon-refresh'
        },
        fields: {
            ticketProjectOwner: {
                validators: {
                    notEmpty: {
                        message: 'The project owner is required'
                    }
                }
            },
			ticketClassification: {
                validators: {
                    notEmpty: {
                        message: 'The classification is required'
                    }
                }
            },
			ticketPriority: {
                validators: {
                    notEmpty: {
                        message: 'The priority level is required'
                    }
                }
            },
			ticketSubject: {
                validators: {
                    notEmpty: {
                        message: 'The subject is required'
                    }
                }
            },
			ticketTargetDate: {
                validators: {
                    notEmpty: {
                        message: 'Target date is required'
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
	$('#submitTicketBtn').click(function(e) {
		e.preventDefault();
		e.stopPropagation();
		
		// Prevent multiple clicks
		var $btn = $(this);
		if ($btn.prop('disabled')) {
			return false;
		}

		// Disable button immediately to prevent multiple clicks
		$btn.prop('disabled', true);

		// Custom validation for CKEditor description field
		var ckContent = CKEDITOR.instances.description.getData();
		var textContent = ckContent.replace(/<[^>]*>/g, '').trim();
		var hasDescriptionError = false;
		
		// Clear any existing description error
		$('#description-error').remove();
		
		if (textContent.length === 0) {
			hasDescriptionError = true;
			// Add error message below the CKEditor
			$('#description').after('<div id="description-error" class="help-block" style="color: #a94442; margin-top: 5px;"><i class="fa fa-times-circle"></i> Description is required.</div>');
			// Add error styling to the CKEditor container
			$('#description').closest('.form-group').addClass('has-error');
		} else {
			// Remove error styling if content is valid
			$('#description').closest('.form-group').removeClass('has-error');
		}

		$('#ticketInfo').bootstrapValidator('validate');
		var bootstrapValidator = $('#ticketInfo').data('bootstrapValidator');

		// Check if form is valid AND description is not empty
		if (bootstrapValidator.isValid() && !hasDescriptionError) {
			// Form is valid, proceed with submission
			// Create FormData object
			var formData = new FormData();

			// Add form fields manually to avoid duplicates
			formData.append('ticketUserid', $('#ticketUserid').val());
			formData.append('ticketProjectOwner', $('#ticketProjectOwner').val());
			formData.append('ticketClassification', $('#ticketClassification').val());
			formData.append('ticketPriority', $('#ticketPriority').val());
			formData.append('ticketSubject', $('#ticketSubject').val());
			formData.append('ticketTargetDate', $('#ticketTargetDate').val());
			formData.append('description', CKEDITOR.instances.description.getData());

			// Add files if present
			var fileInput = document.getElementById('attachFile');
			if (fileInput && fileInput.files.length > 0) {
				for (var i = 0; i < fileInput.files.length; i++) {
					formData.append('attachFile[]', fileInput.files[i]);
				}
			}

			// Debug: Log form data
			for (var pair of formData.entries()) {
				console.log(pair[0] + ': ' + pair[1]);
			}

			$.ajax({
				type: "POST",
				url: "saveticket.php",
				data: formData,
				processData: false,
				contentType: false,
				cache: false,
				beforeSend: function() {
					$("#flash5").show().html('<i class="fa fa-spinner fa-spin"></i>&nbsp;Saving...Please Wait.');
				},
				success: function(response) {
					console.log('Raw response:', response); // Debug log

					$("#flash5").hide();
					
					try {
						// If response is already an object, use it directly
						if (typeof response === 'object') {
							handleResponse(response);
						} else {
							// Try to parse JSON string
							const result = JSON.parse(response);
							handleResponse(result);
						}
					} catch (e) {
						console.error('Parse error:', e); // Debug log
						console.log('Response that failed to parse:', response); // Debug log
						$("#insert_search5").show().html(
							'<div class="alert alert-danger">' +
							'Server response error. Check console for details.<br>' +
							'Error: ' + e.message +
							'</div>'
						);
						$btn.prop('disabled', false);
					}
				},
				error: function(xhr, status, error) {
					console.error('Ajax error:', {
						status: status,
						error: error,
						response: xhr.responseText
					});
					$("#flash5").html(
						'<div class="alert alert-danger">' +
						'Error saving ticket. Status: ' + status + '<br>' +
						'Error: ' + error +
						'</div>'
					);
					$btn.prop('disabled', false);
				}
			});
		} else {
			// If there are validation errors, re-enable the button after a short delay
			setTimeout(function() {
				$btn.prop('disabled', false);
			}, 100);
		}

		function handleResponse(result) {
			if (result.status === 'success') {
				handleSuccessfulSave(result.message);
			} else {
				$("#insert_search5").show().html(
					'<div class="alert alert-danger">' + 
					(result.message || 'Unknown error occurred') + 
					'</div>'
				);
				$btn.prop('disabled', false);
			}
		}
		
		return false; // Prevent any default form submission
	});

	function handleSuccessfulSave(message) {
		// Show success message
		$("#flash5").html('<div class="alert alert-success">' +
			'<i class="fa fa-check-circle"></i> ' +
			(message || 'Ticket saved successfully!') +
		'</div>').show();
		
		// Clear any existing messages
		$("#insert_search5").empty();
		
		// Disable form inputs
		$('#ticketInfo :input').prop('disabled', true);
		
		// Close modal after delay
		setTimeout(function() {
			$('.modal').modal('hide');
			// Reload the page or update the ticket list
			if (typeof loadTickets === 'function') {
				loadTickets();
			} else {
				location.reload();
			}
		}, 1500);
	}
});


//project updateCommands
function showUpdateTicket(id2) {
	var id2 = id2;
																																	
	var dataString = 'id2='+ id2;									
				
	// alert(dataString);
				
	$.ajax({
	type: "GET",
	url: "modal_updateticket.php",
	data: dataString,
	cache: false,																							
			  success: function(html)
			    {
				   $("#myModal_updateTicket").show();
				   $('#myModal_updateTicket').empty();
				   $("#myModal_updateTicket").append(html);
											   
			   },
			  error: function(html)
			    {
				   $("#myModal_updateTicket").show();
				   $('#myModal_updateTicket').empty();
				   $("#myModal_updateTicket").append(html);	
				   $("#myModal_updateTicket").hide();											   												   		
			   }											   
	        });
}


/*====processing of data from form to php==============*/
$(document).ready(function() {
	$('#ticketInfoUpdate')
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
		submitButtons: 'button[name="submitTicketUptBtn"]',			    
        feedbackIcons: {
        required: 'glyphicon glyphicon-asterisk',
            valid: 'glyphicon glyphicon-ok',
            invalid: 'glyphicon glyphicon-remove',
            validating: 'glyphicon glyphicon-refresh'
        },
        fields: {
            ticketStatus2: {
                validators: {
                    notEmpty: {
                        message: 'The status is required'
                    }                            
                }
            },
			descriptionupdate2: {
                validators: {
                    notEmpty: {
                        message: 'The description is required'
                    }                            
                }
            }
		}
    })
	.on('success.field.bv', function(e, data) {
         console.log(data.field, data.element, '-->success');
    });
	$('#submitTicketUptBtn').click(function() {
		$('#ticketInfoUpdate').bootstrapValidator('validate');
		var bootstrapValidator = $('#ticketInfoUpdate').data('bootstrapValidator');
		var stat2 = bootstrapValidator.isValid();
		if(stat2=='1')
		{
			var ticketIdUp2 = $("#ticketIdUp2").val();	
			var ticketUseridUp2 = $("#ticketUseridUp2").val();	
			var ticketSubjectUp2 = encodeURIComponent($("#ticketSubjectUp2").val());	
			var ticketStatus2 = $("#ticketStatus2").val();	
			var descriptionupdate2= CKEDITOR.instances.descriptionupdate2.getData();
			var descriptionupdate2 = encodeURIComponent(descriptionupdate2);	
			
			var dataString = 'ticketIdUp2='+ ticketIdUp2
							+'&ticketUseridUp2='+ ticketUseridUp2
							+'&ticketSubjectUp2='+ ticketSubjectUp2
							+'&ticketStatus2='+ ticketStatus2
							+'&descriptionupdate2='+ descriptionupdate2;
			
			// alert(taskAssignee2);
			$.ajax({
				type: "GET",
				url: "updateticket.php",
				data: dataString,
				cache: false,									
						beforeSend: function(html) 
							{			   
								$("#flashticketup").show();
								$("#flashticketup").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Saving...Please Wait.');				
							},															
						success: function(html)
						    {
								$("#insert_ticketup").show();
								$('#insert_ticketup').empty();
								$("#insert_ticketup").append(html);
								$("#flashticketup").hide();																		   
						   },
						error: function(html)
						    {
								$("#insert_ticketup").show();
								$('#insert_ticketup').empty();
								$("#insert_ticketup").append(html);
								$("#flashticketup").hide();													   												   		
						   }											   
			});
		}
	});	
});


$('#tabUL a[href="#ticketprogress"]').on('click', function(event){				
	showticketprogress();
});

function showticketprogress() {
	// alert('test');
	$.ajax({
	type: "GET",
	url: "ticketinprogress.php",
	cache: false,	
			beforeSend: function(html) 
				{			   
					$("#insert_ticketprocess").show();
					$("#insert_ticketprocess").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Loading...Please Wait.');				
				},	
			  success: function(html)
				{
				   $("#insertbody_ticketprocess").show();
				   $('#insertbody_ticketprocess').empty();
				   $("#insertbody_ticketprocess").append(html);
				   $("#insert_ticketprocess").hide();				   			   		  
			   },
			  error: function(html)
				{
				   $("#insertbody_ticketprocess").show();
				   $('#insertbody_ticketprocess').empty();
				   $("#insertbody_ticketprocess").append(html);	
				   $("#insertbody_ticketprocess").hide();	
				   $("#insert_ticketprocess").hide();			   					   		
			   }						   
	});
}

$('#tabUL a[href="#ticketpending"]').on('click', function(event){				
	showticketpending();
});

function showticketpending() {
	// alert('test');
	$.ajax({
	type: "GET",
	url: "ticketpending.php",
	cache: false,	
			beforeSend: function(html) 
				{			   
					$("#insert_ticketpending").show();
					$("#insert_ticketpending").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Loading...Please Wait.');				
				},	
			  success: function(html)
				{
				   $("#insertbody_ticketpending").show();
				   $('#insertbody_ticketpending').empty();
				   $("#insertbody_ticketpending").append(html);
				   $("#insert_ticketpending").hide();				   			   		  
			   },
			  error: function(html)
				{
				   $("#insertbody_ticketpending").show();
				   $('#insertbody_ticketpending').empty();
				   $("#insertbody_ticketpending").append(html);	
				   $("#insertbody_ticketpending").hide();	
				   $("#insert_ticketpending").hide();			   					   		
			   }						   
	});
}

$('#tabUL a[href="#ticketreject"]').on('click', function(event){				
	showticketrejected();
});

function showticketrejected() {
	// alert('test');
	$.ajax({
	type: "GET",
	url: "ticketrejected.php",
	cache: false,	
			beforeSend: function(html) 
				{			   
					$("#insert_ticketrejected").show();
					$("#insert_ticketrejected").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Loading...Please Wait.');				
				},	
			  success: function(html)
				{
				   $("#insertbody_ticketrejected").show();
				   $('#insertbody_ticketrejected').empty();
				   $("#insertbody_ticketrejected").append(html);
				   $("#insert_ticketrejected").hide();				   			   		  
			   },
			  error: function(html)
				{
				   $("#insertbody_ticketrejected").show();
				   $('#insertbody_ticketrejected').empty();
				   $("#insertbody_ticketrejected").append(html);	
				   $("#insertbody_ticketrejected").hide();	
				   $("#insert_ticketrejected").hide();			   					   		
			   }						   
	});
}


$('#tabUL a[href="#ticketcancelled"]').on('click', function(event){				
	showticketcancelled();
});

function showticketcancelled() {
	// alert('test');
	$.ajax({
	type: "GET",
	url: "ticketcancelled.php",
	cache: false,	
			beforeSend: function(html) 
				{			   
					$("#insert_ticketcancelled").show();
					$("#insert_ticketcancelled").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Loading...Please Wait.');				
				},	
			  success: function(html)
				{
				   $("#insertbody_ticketcancelled").show();
				   $('#insertbody_ticketcancelled').empty();
				   $("#insertbody_ticketcancelled").append(html);
				   $("#insert_ticketcancelled").hide();				   			   		  
			   },
			  error: function(html)
				{
				   $("#insertbody_ticketcancelled").show();
				   $('#insertbody_ticketcancelled').empty();
				   $("#insertbody_ticketcancelled").append(html);	
				   $("#insertbody_ticketcancelled").hide();	
				   $("#insert_ticketcancelled").hide();			   					   		
			   }						   
	});
}

$('#tabUL a[href="#ticketdone"]').on('click', function(event){				
	showticketdone();
});

function showticketdone() {
	// alert('test');
	$.ajax({
	type: "GET",
	url: "ticketdone.php",
	cache: false,	
			beforeSend: function(html) 
				{			   
					$("#insert_ticketdone").show();
					$("#insert_ticketdone").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Loading...Please Wait.');				
				},	
			  success: function(html)
				{
				   $("#insertbody_ticketdone").show();
				   $('#insertbody_ticketdone').empty();
				   $("#insertbody_ticketdone").append(html);
				   $("#insert_ticketdone").hide();				   			   		  
			   },
			  error: function(html)
				{
				   $("#insertbody_ticketdone").show();
				   $('#insertbody_ticketdone').empty();
				   $("#insertbody_ticketdone").append(html);	
				   $("#insertbody_ticketdone").hide();	
				   $("#insert_ticketdone").hide();			   					   		
			   }						   
	});
}

