function showcreateclient(id2) {
	var id2 = id2;
	// alert(id2);	
												
	/*var searchstring =  '';																
	if(id ==''){searchstring=searchstring;}else{searchstring=searchstring+" "+id;}
	*/																																	
	var dataString = 'id2='+ id2;									
				
	// alert(dataString);
				
	$.ajax({
	type: "GET",
	url: "modal_createclient.php",
	data: dataString,
	cache: false,																							
			  success: function(html)
			    {
				   $("#myModal_createClient").show();
				   $('#myModal_createClient').empty();
				   $("#myModal_createClient").append(html);
											   
			   },
			  error: function(html)
			    {
				   $("#myModal_createClient").show();
				   $('#myModal_createClient').empty();
				   $("#myModal_createClient").append(html);	
				   $("#myModal_createClient").hide();											   												   		
			   }											   
	        });
}

/*====processing of data from form to php==============*/
$(document).ready(function() {
	$('#clientInfo')
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
		submitButtons: 'button[name="submitClientBtn"]',			    
        feedbackIcons: {
        required: 'glyphicon glyphicon-asterisk',
            valid: 'glyphicon glyphicon-ok',
            invalid: 'glyphicon glyphicon-remove',
            validating: 'glyphicon glyphicon-refresh'
        },
        fields: {
            clientName: {
                validators: {
                    notEmpty: {
                        message: 'Client/Company name is required'
                    }                            
                }
            },
			clientEmail: {
                validators: {
                    notEmpty: {
                        message: 'Email is required'
                    },
					emailAddress:{
						message: 'The input is not a valid email address'
					}
                }
            },
			clientContact: {
                validators: {
                    notEmpty: {
                        message: 'Contact Number is required'
                    }                            
                }
            },
			clientPerson: {
                validators: {
                    notEmpty: {
                        message: 'Contact Person is required'
                    }                            
                }
            },
			clientPosition: {
               
            },
			clientStreet: {
               
            },
			clientCity: {
               
            },
			clientState: {
               
            },
			clientCountry: {
               
            },
		}
    })
	.on('error.field.bv', function(e, data) {
        console.log(data.field, data.element, '-->error');
        $("#resetClientBtn").prop('disabled', true);	
    })
    .on('success.field.bv', function(e, data) {
         console.log(data.field, data.element, '-->success');
    });
	$('#submitClientBtn').click(function() {
		$('#clientInfo').bootstrapValidator('validate');
		var bootstrapValidator = $('#clientInfo').data('bootstrapValidator');
		var stat1 = bootstrapValidator.isValid();
		if(stat1=='1')
		{
			var clientName = $("#clientName").val();	
			var clientEmail = $("#clientEmail").val();	
			var clientContact = $("#clientContact").val();	
			var clientPerson = $("#clientPerson").val();	
			var clientPosition = $("#clientPosition").val();	
			var clientStreet = $("#clientStreet").val();	
			var clientCity = $("#clientCity").val();	
			var clientState = $("#clientState").val();	
			var clientCountry = $("#clientCountry").val();	
			var clientUserid = $("#clientUserid").val();	
			
			var dataString = 'clientName='+ clientName
							+'&clientEmail='+ clientEmail
							+'&clientContact='+ clientContact
							+'&clientPerson='+ clientPerson
							+'&clientPosition='+ clientPosition
							+'&clientStreet='+ clientStreet
							+'&clientCity='+ clientCity
							+'&clientState='+ clientState
							+'&clientCountry='+ clientCountry
							+'&clientUserid='+ clientUserid;
			
			// alert(dataString);
			$.ajax({
				type: "GET",
				url: "saveClient.php",
				data: dataString,
				cache: false,									
						beforeSend: function(html) 
							{			   
								$("#flash").show();
								$("#flash").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Saving...Please Wait.');				
							},															
						success: function(html)
						    {
								$("#insert_search").show();
								$('#insert_search').empty();
								$("#insert_search").append(html);
								$("#flash").hide();																		   
						   },
						error: function(html)
						    {
								$("#insert_search").show();
								$('#insert_search').empty();
								$("#insert_search").append(html);
								$("#flash").hide();													   												   		
						   }											   
			});
		}
	});	
});


function showUpdateClient(id2) {
	var id2 = id2;
																																	
	var dataString = 'id2='+ id2;									
				
	// alert(dataString);
				
	$.ajax({
	type: "GET",
	url: "modal_updateclient.php",
	data: dataString,
	cache: false,																							
			  success: function(html)
			    {
				   $("#myModal_updateClient").show();
				   $('#myModal_updateClient').empty();
				   $("#myModal_updateClient").append(html);
											   
			   },
			  error: function(html)
			    {
				   $("#myModal_updateClient").show();
				   $('#myModal_updateClient').empty();
				   $("#myModal_updateClient").append(html);	
				   $("#myModal_updateClient").hide();											   												   		
			   }											   
	        });
}


/*====update client information==============*/
$(document).ready(function() {
	$('#updateClientInfo')
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
		submitButtons: 'button[name="submitUpdateClientBtn"]',			    
        feedbackIcons: {
        required: 'glyphicon glyphicon-asterisk',
            valid: 'glyphicon glyphicon-ok',
            invalid: 'glyphicon glyphicon-remove',
            validating: 'glyphicon glyphicon-refresh'
        },
        fields: {
            clientName2: {
                validators: {
                    notEmpty: {
                        message: 'Client/Company name is required'
                    }                            
                }
            },
			clientEmail2: {
                validators: {
                    notEmpty: {
                        message: 'Email is required'
                    },
					emailAddress:{
						message: 'The input is not a valid email address'
					}
                }
            },
			clientStatus2: {
                validators: {
                    notEmpty: {
                        message: 'Status is required'
                    }                            
                }
            },
			clientContact2: {
                validators: {
                    notEmpty: {
                        message: 'Contact Number is required'
                    }                            
                }
            },
			clientPerson2: {
                validators: {
                    notEmpty: {
                        message: 'Contact Person is required'
                    }                            
                }
            },
			clientPosition2: {
               
            },
			clientStreet2: {
               
            },
			clientCity2: {
               
            },
			clientState2: {
               
            },
			clientCountry2: {
               
            },
		}
    })
	.on('error.field.bv', function(e, data) {
        console.log(data.field, data.element, '-->error');
        $("#resetClientBtn").prop('disabled', true);	
    })
    .on('success.field.bv', function(e, data) {
         console.log(data.field, data.element, '-->success');
    });
	$('#submitUpdateClientBtn').click(function() {
		$('#updateClientInfo').bootstrapValidator('validate');
		var bootstrapValidator = $('#updateClientInfo').data('bootstrapValidator');
		var stat2 = bootstrapValidator.isValid();
		if(stat2=='1')
		{
			var clientName2 = $("#clientName2").val();	
			var clientEmail2 = $("#clientEmail2").val();	
			var clientContact2 = $("#clientContact2").val();	
			var clientPerson2 = $("#clientPerson2").val();	
			var clientPosition2 = $("#clientPosition2").val();	
			var clientStreet2 = $("#clientStreet2").val();	
			var clientCity2 = $("#clientCity2").val();	
			var clientState2 = $("#clientState2").val();	
			var clientCountry2 = $("#clientCountry2").val();	
			var clientId2 = $("#clientId2").val();	
			var clientStatus2 = $("#clientStatus2").val();	
			var clientUserid2 = $("#clientUserid2").val();	
			
			var dataString = 'clientName2='+ clientName2
							+'&clientEmail2='+ clientEmail2
							+'&clientContact2='+ clientContact2
							+'&clientPerson2='+ clientPerson2
							+'&clientPosition2='+ clientPosition2
							+'&clientStreet2='+ clientStreet2
							+'&clientCity2='+ clientCity2
							+'&clientState2='+ clientState2
							+'&clientCountry2='+ clientCountry2
							+'&clientId2='+ clientId2
							+'&clientUserid2='+ clientUserid2
							+'&clientStatus2='+ clientStatus2;
			
			// alert(dataString);
			$.ajax({
				type: "GET",
				url: "updateClient.php",
				data: dataString,
				cache: false,									
						beforeSend: function(html) 
							{			   
								$("#flashup").show();
								$("#flashup").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Updating...Please Wait.');				
							},															
						success: function(html)
						    {
								$("#insert_searchup").show();
								$('#insert_searchup').empty();
								$("#insert_searchup").append(html);
								$("#flashup").hide();																		   
						   },
						error: function(html)
						    {
								$("#insert_searchup").show();
								$('#insert_searchup').empty();
								$("#insert_searchup").append(html);
								$("#flashup").hide();													   												   		
						   }											   
			});
		}
	});	
});

//------------------for project creation----------------------
function showcreateproject(id2) {
	var id2 = id2;
	// alert(id2);	
												
	/*var searchstring =  '';																
	if(id ==''){searchstring=searchstring;}else{searchstring=searchstring+" "+id;}
	*/																																	
	var dataString = 'id2='+ id2;									
				
	// alert(dataString);
				
	$.ajax({
	type: "GET",
	url: "modal_createproject.php",
	data: dataString,
	cache: false,																							
			  success: function(html)
			    {
				   $("#myModal_createProject").show();
				   $('#myModal_createProject').empty();
				   $("#myModal_createProject").append(html);
											   
			   },
			  error: function(html)
			    {
				   $("#myModal_createProject").show();
				   $('#myModal_createProject').empty();
				   $("#myModal_createProject").append(html);	
				   $("#myModal_createProject").hide();											   												   		
			   }											   
	        });
}

/*====processing of data from form to php==============*/
$(document).ready(function() {
	$('#projectInfo')
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
		submitButtons: 'button[name="submitProjectBtn"]',			    
        feedbackIcons: {
        required: 'glyphicon glyphicon-asterisk',
            valid: 'glyphicon glyphicon-ok',
            invalid: 'glyphicon glyphicon-remove',
            validating: 'glyphicon glyphicon-refresh'
        },
        fields: {
            projectName: {
                validators: {
                    notEmpty: {
                        message: 'Project name is required'
                    }                            
                }
            },
			projectManager: {
                validators: {
                    notEmpty: {
                        message: 'Project manager is required'
                    }                            
                }
            },
			projectOwner: {
                validators: {
                    notEmpty: {
                        message: 'Project Owner is required'
                    }                            
                }
            },
			projectDateStart: {
                validators: {
                    notEmpty: {
                        message: 'Date Start is required'
                    },
					date: {
						format: 'YYYY-MM-DD',
						message: 'The date is not valid. Should be in YYYY-MM-DD'
					}
                }
            },
			projectDateEnd: {
                validators: {
					notEmpty: {
                        message: 'Date Start is required'
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
	$('#submitProjectBtn').click(function() {
		$('#projectInfo').bootstrapValidator('validate');
		var bootstrapValidator = $('#projectInfo').data('bootstrapValidator');
		var stat1 = bootstrapValidator.isValid();
		if(stat1=='1')
		{
			var remarks= CKEDITOR.instances.remarks.getData();
			var remarks = escape(remarks);
			var projectName = $("#projectName").val();	
			var projectOwner = $("#projectOwner").val();	
			var projectDateStart = $("#projectDateStart").val();	
			var projectDateEnd = $("#projectDateEnd").val();	
			var projectUserid = $("#projectUserid").val();	
			var projectManager = $("#projectManager").val();	
			
			var dataString = 'projectUserid='+ projectUserid
							+'&projectName='+ projectName
							+'&projectOwner='+ projectOwner
							+'&projectDateStart='+ projectDateStart
							+'&projectDateEnd='+ projectDateEnd
							+'&projectManager='+ projectManager
							+'&remarks='+ remarks;
			
			// alert(dataString);
			$.ajax({
				type: "GET",
				url: "saveProject.php",
				data: dataString,
				cache: false,									
						beforeSend: function(html) 
							{			   
								$("#flash3").show();
								$("#flash3").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Saving Project Information...Please Wait.');				
							},															
						success: function(html)
						    {
								$("#insert_search3").show();
								$('#insert_search3').empty();
								$("#insert_search3").append(html);
								$("#flash3").hide();																		   
						   },
						error: function(html)
						    {
								$("#insert_search3").show();
								$('#insert_search3').empty();
								$("#insert_search3").append(html);
								$("#flash3").hide();													   												   		
						   }											   
			});
		}
	});	
});


function showUpdateProject(id2) {
	var id2 = id2;
																																	
	var dataString = 'id2='+ id2;									
				
	// alert(dataString);
				
	$.ajax({
	type: "GET",
	url: "modal_updateproject.php",
	data: dataString,
	cache: false,																							
			  success: function(html)
			    {
				   $("#myModal_updateProject").show();
				   $('#myModal_updateProject').empty();
				   $("#myModal_updateProject").append(html);
											   
			   },
			  error: function(html)
			    {
				   $("#myModal_updateProject").show();
				   $('#myModal_updateProject').empty();
				   $("#myModal_updateProject").append(html);	
				   $("#myModal_updateProject").hide();											   												   		
			   }											   
	        });
}


/*====update project information==============*/
$(document).ready(function() {
	$('#updateProjectInfo')
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
		submitButtons: 'button[name="submitUpdateProjectBtn"]',			    
        feedbackIcons: {
        required: 'glyphicon glyphicon-asterisk',
            valid: 'glyphicon glyphicon-ok',
            invalid: 'glyphicon glyphicon-remove',
            validating: 'glyphicon glyphicon-refresh'
        },
        fields: {
            projectName2: {
                validators: {
                    notEmpty: {
                        message: 'Project name is required'
                    }                            
                }
            },
			projectOwner2: {
                validators: {
                    notEmpty: {
                        message: 'Project name is required'
                    }                            
                }
            },
			projectDateStart2: {
                validators: {
					notEmpty: {
                        message: 'Date Start is required'
                    },
					date: {
						format: 'YYYY-MM-DD',
						message: 'The date is not valid. Should be in YYYY-MM-DD'
					}
                }
            },
			projectDateEnd2: {
                validators: {
					notEmpty: {
                        message: 'End Date is required'
                    },
					date: {
						format: 'YYYY-MM-DD',
						message: 'The date is not valid. Should be in YYYY-MM-DD'
					}
                }
            },
			projectManager2: {
                validators: {
                    notEmpty: {
                        message: 'Project Manager is required'
                    }                            
                }
            },
			projectStatus2: {
                validators: {
                    notEmpty: {
                        message: 'Project status is required'
                    }                            
                }
            }
		}
    })
	.on('error.field.bv', function(e, data) {
        console.log(data.field, data.element, '-->error');
        $("#resetProjectBtn").prop('disabled', true);	
    })
    .on('success.field.bv', function(e, data) {
         console.log(data.field, data.element, '-->success');
    });
	$('#submitUpdateProjectBtn').click(function() {
		$('#updateProjectInfo').bootstrapValidator('validate');
		var bootstrapValidator = $('#updateProjectInfo').data('bootstrapValidator');
		var stat2 = bootstrapValidator.isValid();
		if(stat2=='1')
		{
			var projectId2 = $("#projectId2").val();	
			var projectUserid2 = $("#projectUserid2").val();	
			var projectName2 = $("#projectName2").val();	
			var projectOwner2 = $("#projectOwner2").val();	
			var projectDateStart2 = $("#projectDateStart2").val();	
			var projectDateEnd2 = $("#projectDateEnd2").val();	
			var projectManager2 = $("#projectManager2").val();	
			var projectStatus2 = $("#projectStatus2").val();	
			var remarks22= CKEDITOR.instances.remarks22.getData();
			var remarks22 = escape(remarks22);
			
			var dataString = 'projectId2='+ projectId2
							+'&projectUserid2='+ projectUserid2
							+'&projectName2='+ projectName2
							+'&projectOwner2='+ projectOwner2
							+'&projectDateStart2='+ projectDateStart2
							+'&projectDateEnd2='+ projectDateEnd2
							+'&projectManager2='+ projectManager2
							+'&projectStatus2='+ projectStatus2
							+'&remarks22='+ remarks22;
			
			// alert(dataString);
			
			$.ajax({
				type: "GET",
				url: "updateProject.php",
				data: dataString,
				cache: false,									
						beforeSend: function(html) 
							{			   
								$("#flashup2").show();
								$("#flashup2").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Updating...Please Wait.');				
							},															
						success: function(html)
						    {
								$("#insert_searchup2").show();
								$('#insert_searchup2').empty();
								$("#insert_searchup2").append(html);
								$("#flashup2").hide();																		   
						   },
						error: function(html)
						    {
								$("#insert_searchup2").show();
								$('#insert_searchup2').empty();
								$("#insert_searchup2").append(html);
								$("#flashup2").hide();													   												   		
						   }											   
			});
		}
	});	
});