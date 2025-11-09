function showcreateuser(id2) {
	var id2 = id2;
	// alert(id2);	
												
	/*var searchstring =  '';																
	if(id ==''){searchstring=searchstring;}else{searchstring=searchstring+" "+id;}
	*/																																	
	var dataString = 'id2='+ id2;									
				
	// alert(dataString);
				
	$.ajax({
	type: "GET",
	url: "modal_createuser.php",
	data: dataString,
	cache: false,																							
			  success: function(html)
			    {
				   $("#myModal_createUser").show();
				   $('#myModal_createUser').empty();
				   $("#myModal_createUser").append(html);
											   
			   },
			  error: function(html)
			    {
				   $("#myModal_createUser").show();
				   $('#myModal_createUser').empty();
				   $("#myModal_createUser").append(html);	
				   $("#myModal_createUser").hide();											   												   		
			   }											   
	        });
}


/*====processing of data from form to php==============*/
$(document).ready(function() {
	$('#userInfo')
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
		submitButtons: 'button[type="button"]',			    
        feedbackIcons: {
        required: 'glyphicon glyphicon-asterisk',
            valid: 'glyphicon glyphicon-ok',
            invalid: 'glyphicon glyphicon-remove',
            validating: 'glyphicon glyphicon-refresh'
        },
        fields: {
            userFirstName: {
                validators: {
                    notEmpty: {
                        message: 'First name is required'
                    }                            
                }
            },
			userEmail: {
                validators: {
                    notEmpty: {
                        message: 'Email is required'
                    },
					emailAddress:{
						message: 'The input is not a valid email address'
					}
                }
            },
			userLastName: {
                validators: {
                    notEmpty: {
                        message: 'last is required'
                    }                            
                }
            },
			userName: {
                validators: {
                    notEmpty: {
                        message: 'Username is required'
                    }                            
                }
            },
			userLevel: {
                validators: {
                    notEmpty: {
                        message: 'Level is required'
                    }                            
                }
            },
			userGroup: {
                validators: {
                    notEmpty: {
                        message: 'Group is required'
                    }                            
                }
            }
		}
    })
	.on('error.field.bv', function(e, data) {
        console.log(data.field, data.element, '-->error');
        $("#resetUserBtn").prop('disabled', true);	
    })
    .on('success.field.bv', function(e, data) {
         console.log(data.field, data.element, '-->success');
    });
	$('#submitUserBtn').click(function() {
		$('#userInfo').bootstrapValidator('validate');
		var bootstrapValidator = $('#userInfo').data('bootstrapValidator');
		var stat1 = bootstrapValidator.isValid();
		if(stat1=='1')
		{
			var userUserid = $("#userUserid").val();	
			var userFirstName = $("#userFirstName").val();	
			var userLastName = $("#userLastName").val();	
			var userEmail = $("#userEmail").val();	
			var userName = $("#userName").val();	
			var userLevel = $("#userLevel").val();	
			var userGroup = $("#userGroup").val();	
			var userClient = $("#userClient").val();	
			
			var dataString = 'userUserid='+ userUserid
							+'&userFirstName='+ userFirstName
							+'&userLastName='+ userLastName
							+'&userEmail='+ userEmail
							+'&userName='+ userName
							+'&userLevel='+ userLevel
							+'&userGroup='+ userGroup
							+'&userClient='+ userClient;
			
			// alert(dataString);
			$.ajax({
				type: "GET",
				url: "saveuser.php",
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


function showaddauthority(id2) {
	var id2 = id2;
	// alert(id2);	
												
	/*var searchstring =  '';																
	if(id ==''){searchstring=searchstring;}else{searchstring=searchstring+" "+id;}
	*/																																	
	var dataString = 'id2='+ id2;									
				
	// alert(dataString);
				
	$.ajax({
	type: "GET",
	url: "modal_addauthority.php",
	data: dataString,
	cache: false,																							
			  success: function(html)
			    {
				   $("#myModal_addAuthority").show();
				   $('#myModal_addAuthority').empty();
				   $("#myModal_addAuthority").append(html);
											   
			   },
			  error: function(html)
			    {
				   $("#myModal_addAuthority").show();
				   $('#myModal_addAuthority').empty();
				   $("#myModal_addAuthority").append(html);	
				   $("#myModal_addAuthority").hide();											   												   		
			   }											   
	        });
}

/*====processing of data from form to php==============*/
$(document).ready(function() {
	$('#userAuth')
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
		submitButtons: 'button[type="button"]',			    
        feedbackIcons: {
        required: 'glyphicon glyphicon-asterisk',
            valid: 'glyphicon glyphicon-ok',
            invalid: 'glyphicon glyphicon-remove',
            validating: 'glyphicon glyphicon-refresh'
        },
        fields: {
            useridx: {
                validators: {
                    notEmpty: {
                        message: 'First name is required'
                    }                            
                }
            },
			userAuthoLevel: {
                validators: {
                    notEmpty: {
                        message: 'last is required'
                    }                            
                }
            },
			userAuthmoduleStat: {
                validators: {
                    notEmpty: {
                        message: 'Username is required'
                    }                            
                }
            }
		}
    })
	.on('error.field.bv', function(e, data) {
        console.log(data.field, data.element, '-->error');
        $("#resetUserAuthBtn").prop('disabled', true);	
    })
    .on('success.field.bv', function(e, data) {
         console.log(data.field, data.element, '-->success');
    });
	$('#submitUserAuthBtn').click(function() {
		$('#userAuth').bootstrapValidator('validate');
		var bootstrapValidator = $('#userAuth').data('bootstrapValidator');
		var stat2 = bootstrapValidator.isValid();
		if(stat2=='1')
		{
			var userAuthUserid = $("#userAuthUserid").val();	
			var useridx = $("#useridx").val();	
			var moduleidx = $("#moduleidx").val();	
			var userAuthoLevel = $("#userAuthoLevel").val();	
			var userAuthmoduleStat = $("#userAuthmoduleStat").val();	
			
			var dataString = 'userAuthUserid='+ userAuthUserid
							+'&useridx='+ useridx
							+'&moduleidx='+ moduleidx
							+'&userAuthoLevel='+ userAuthoLevel
							+'&userAuthmoduleStat='+ userAuthmoduleStat;
			
			// alert(dataString);
			$.ajax({
				type: "POST",
				url: "saveauthority.php",
				data: dataString,
				cache: false,									
						beforeSend: function(html) 
							{			   
								$("#flash2").show();
								$("#flash2").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Saving...Please Wait.');				
							},															
						success: function(html)
						    {
								$("#insert_search2").show();
								$('#insert_search2').empty();
								$("#insert_search2").append(html);
								$("#flash2").hide();																		   
						   },
						error: function(html)
						    {
								$("#insert_search2").show();
								$('#insert_search2').empty();
								$("#insert_search2").append(html);
								$("#flash2").hide();													   												   		
						   }											   
			});
		}
	});	
});



function showUpdateUserInfo(id2) {
	var id2 = id2;
	// alert(id2);	
												
	/*var searchstring =  '';																
	if(id ==''){searchstring=searchstring;}else{searchstring=searchstring+" "+id;}
	*/																																	
	var dataString = 'id2='+ id2;									
				
	// alert(dataString);
				
	$.ajax({
	type: "GET",
	url: "modal_updateuser.php",
	data: dataString,
	cache: false,																							
			  success: function(html)
			    {
				   $("#myModal_updateUserInfo").show();
				   $('#myModal_updateUserInfo').empty();
				   $("#myModal_updateUserInfo").append(html);
											   
			   },
			  error: function(html)
			    {
				   $("#myModal_updateUserInfo").show();
				   $('#myModal_updateUserInfo').empty();
				   $("#myModal_updateUserInfo").append(html);	
				   $("#myModal_updateUserInfo").hide();											   												   		
			   }											   
	        });
}



/*====processing of data from form to php==============*/
$(document).ready(function() {
	$('#updateUserInfo')
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
		submitButtons: 'button[name="submitUserBtn2"]',			    
        feedbackIcons: {
        required: 'glyphicon glyphicon-asterisk',
            valid: 'glyphicon glyphicon-ok',
            invalid: 'glyphicon glyphicon-remove',
            validating: 'glyphicon glyphicon-refresh'
        },
        fields: {
            userFirstName2: {
                validators: {
                    notEmpty: {
                        message: 'First name is required'
                    }                            
                }
            },
			userEmail2: {
                validators: {
                    notEmpty: {
                        message: 'Email is required'
                    },
					emailAddress:{
						message: 'The input is not a valid email address'
					}
                }
            },
			userLastName2: {
                validators: {
                    notEmpty: {
                        message: 'last is required'
                    }                            
                }
            },
			userName2: {
                validators: {
                    notEmpty: {
                        message: 'Username is required'
                    }
				}
            },
			userLevel2: {
                validators: {
                    notEmpty: {
                        message: 'Level is required'
                    }                            
                }
            },
			userStatus2: {
                validators: {
                    notEmpty: {
                        message: 'Status is required'
                    }                            
                }
            },
			password2: {
                validators: {
                    notEmpty: {
                        message: 'Password is required'
                    }                            
                }
            },
			userGroup2: {
                validators: {
                    notEmpty: {
                        message: 'Group is required'
                    }                            
                }
            }
		}
    })
	.on('error.field.bv', function(e, data) {
        console.log(data.field, data.element, '-->error');
        $("#resetUserBtn2").prop('disabled', true);	
    })
    .on('success.field.bv', function(e, data) {
         console.log(data.field, data.element, '-->success');
    });
	$('#submitUserBtn2').click(function() {
		$('#updateUserInfo').bootstrapValidator('validate');
		var bootstrapValidator = $('#updateUserInfo').data('bootstrapValidator');
		var stat4 = bootstrapValidator.isValid();
		if(stat4=='1')
		{
			var user2 = $("#user2").val();	
			var userUserid2 = $("#userUserid2").val();	
			var userFirstName2 = $("#userFirstName2").val();	
			var userLastName2 = $("#userLastName2").val();	
			var userEmail2 = $("#userEmail2").val();	
			var userName2 = $("#userName2").val();	
			var userLevel2 = $("#userLevel2").val();	
			var userGroup2 = $("#userGroup2").val();	
			var userClient2 = $("#userClient2").val();	
			var password2 = $("#password2").val();	
			var userStatus2 = $("#userStatus2").val();	
			
			var dataString = 'userUserid2='+ userUserid2
							+'&user2='+ user2
							+'&userFirstName2='+ userFirstName2
							+'&userLastName2='+ userLastName2
							+'&userEmail2='+ userEmail2
							+'&userName2='+ userName2
							+'&userLevel2='+ userLevel2
							+'&userGroup2='+ userGroup2
							+'&password2='+ password2
							+'&userStatus2='+ userStatus2
							+'&userClient2='+ userClient2;
			
			// alert(dataString);
			$.ajax({
				type: "GET",
				url: "saveUpdateUserInfo.php",
				data: dataString,
				cache: false,									
						beforeSend: function(html) 
							{			
								$("#flashxxx").show();
								$("#flashxxx").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Saving...Please Wait.');				
							},															
						success: function(html)
						    {
								$("#insert_searchxxx").show();
								$('#insert_searchxxx').empty();
								$("#insert_searchxxx").append(html);
								$("#flashxxx").hide();																		   
						   },
						error: function(html)
						    {
								
								$("#insert_searchxxx").show();
								$('#insert_searchxxx').empty();
								$("#insert_searchxxx").append(html);
								$("#flashxxx").hide();													   												   		
						   }											   
			});
		}
	});	
	$('#updateUserInfo').bootstrapValidator('revalidateField', 'userEmail2');
								$('#updateUserInfo').bootstrapValidator('revalidateField', 'userLastName2');
								$('#updateUserInfo').bootstrapValidator('revalidateField', 'userFirstName2');
								$('#updateUserInfo').bootstrapValidator('revalidateField', 'userName2');
								$('#updateUserInfo').bootstrapValidator('revalidateField', 'userLevel2');
								$('#updateUserInfo').bootstrapValidator('revalidateField', 'userGroup2');
								$('#updateUserInfo').bootstrapValidator('revalidateField', 'password2');
								$('#updateUserInfo').bootstrapValidator('revalidateField', 'userStatus2');		

});