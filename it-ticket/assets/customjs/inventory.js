function showAddAsset(id2) {
    var id2 = id2;
    // alert(id2);  
                                                
    /*var searchstring =  '';                                                               
    if(id ==''){searchstring=searchstring;}else{searchstring=searchstring+" "+id;}
    */                                                                                                                                  
    var dataString = 'id2='+ id2;                                   
                
    // alert(dataString);
                
    $.ajax({
    type: "GET",
    url: "modal_addasset.php",
    data: dataString,
    cache: false,                                                                                           
              success: function(html)
                {
                   $("#myModal_createAsset").show();
                   $('#myModal_createAsset').empty();
                   $("#myModal_createAsset").append(html);
                                               
               },
              error: function(html)
                {
                   $("#myModal_createAsset").show();
                   $('#myModal_createAsset').empty();
                   $("#myModal_createAsset").append(html);  
                   $("#myModal_createAsset").hide();                                                                                                    
               }                                               
            });
}

// Global variable to store current company filter
var currentCompanyFilter = null;

// Asset form validation and submission
$(document).ready(function() {
    $('#assetInfo')
    .on('init.field.bv', function(e, data) {    
        var $parent = data.element.parents('.form-group'),
            $icon = $parent.find('.form-control-feedback[data-bv-icon-for="' + data.field + '"]'),
            options = data.bv.getOptions(),
            validators = data.bv.getOptions(data.field).validators;
        
        if (validators.notEmpty && options.feedbackIcons && options.feedbackIcons.required) {
            $icon.addClass(options.feedbackIcons.required).show();
        }
    })
    .bootstrapValidator({
        message: 'This value is not valid',
        live: 'enabled',
        submitButtons: 'button[name="submitAssetBtn"]',
        feedbackIcons: {
            required: 'glyphicon glyphicon-asterisk',
            valid: 'glyphicon glyphicon-ok',
            invalid: 'glyphicon glyphicon-remove',
            validating: 'glyphicon glyphicon-refresh'
        },
        fields: {
            asset_tag: {
                validators: {
                    notEmpty: {
                        message: 'Asset tag is required'
                    }
                }
            },
            current_owner: {
                validators: {
                    notEmpty: {
                        message: 'Current owner is required'
                    }
                }
            },
            previous_owner: {
                validators: {
                    notEmpty: {
                        message: 'Previous owner is required'
                    }
                }
            },
            status: {
                validators: {
                    notEmpty: {
                        message: 'Status is required'
                    }
                }
            },
            department: {
                validators: {
                    notEmpty: {
                        message: 'Department is required'
                    }
                }
            },
            company: {
                validators: {
                    notEmpty: {
                        message: 'Company is required'
                    }
                }
            },
            asset_type: {
                validators: {
                    notEmpty: {
                        message: 'Asset type is required'
                    }
                }
            },
            manufacturer: {
                validators: {
                    notEmpty: {
                        message: 'Manufacturer is required'
                    }
                }
            },
            model: {
                validators: {
                    notEmpty: {
                        message: 'Model is required'
                    }
                }
            },
            color: {
                validators: {
                    notEmpty: {
                        message: 'Color is required'
                    }
                }
            },
            serial_number: {
                validators: {
                    notEmpty: {
                        message: 'Serial number is required'
                    }
                }
            },
            sim_number: {
                validators: {
                    // Removing the notEmpty validator to make it optional
                }
            },
            // purchase_date: {
            //     validators: {
            //         notEmpty: {
            //             message: 'Purchase date is required'
            //         },
            //         date: {
            //             format: 'YYYY-MM-DD',
            //             message: 'The date is not valid. Should be in YYYY-MM-DD'
            //         }
            //     }
            // },
            purchase_price: {
                validators: {
                    notEmpty: {
                        message: 'Purchase price is required'
                    },
                    numeric: {
                        message: 'The value must be a number'
                    }
                }
            },
            order_number: {
                validators: {
                    notEmpty: {
                        message: 'Order number is required'
                    }
                }
            },
            due_date: {
                validators: {
                    // Removing the notEmpty validator to make it optional
                    date: {
                        format: 'YYYY-MM-DD',
                        message: 'The date is not valid. Should be in YYYY-MM-DD'
                    }
                }
            },
            // warranty_start: {
            //     validators: {
            //         notEmpty: {
            //             message: 'Warranty start date is required'
            //         },
            //         date: {
            //             format: 'YYYY-MM-DD',
            //             message: 'The date is not valid. Should be in YYYY-MM-DD'
            //         }
            //     }
            // },
            // warranty_end: {
            //     validators: {
            //         notEmpty: {
            //             message: 'Warranty end date is required'
            //         },
            //         date: {
            //             format: 'YYYY-MM-DD',
            //             message: 'The date is not valid. Should be in YYYY-MM-DD'
            //         }
            //     }
            // }
        }
    })
    .on('success.field.bv', function(e, data) {
        console.log(data.field, data.element, '-->success');
    });

    // Add these event handlers for input fields
    $('#assetInfo input, #assetInfo select').on('change keyup', function() {
        // Revalidate the field when its value is changed
        $('#assetInfo').bootstrapValidator('revalidateField', $(this).attr('name'));
    });

    // For date picker fields specifically
    $('.date-picker').on('changeDate', function() {
        // Revalidate the field when date is changed
        $('#assetInfo').bootstrapValidator('revalidateField', $(this).attr('name'));
    });

    $('#submitAssetBtn').click(function(e) {
        // Prevent multiple clicks
        var $btn = $(this);
        if ($btn.prop('disabled')) {
            return;
        }
        
        $('#assetInfo').bootstrapValidator('validate');
        var bootstrapValidator = $('#assetInfo').data('bootstrapValidator');
        
        if (bootstrapValidator.isValid()) {
            // Disable the button immediately
            $btn.prop('disabled', true);
            
            // Create FormData object
            $('#condition_notes').val(CKEDITOR.instances.condition_notes.getData());
            var formData = new FormData($('#assetInfo')[0]);
            
            $.ajax({
                type: "POST",
                url: "saveasset.php",
                data: formData,
                processData: false,
                contentType: false,
                cache: false,
                dataType: 'html',
                beforeSend: function() {
                    $("#flash5").show().html('<div class="alert alert-info">Saving...Please Wait.</div>');
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
                        if (response.includes('Asset Registered Successfully')) {
                            handleSuccessfulSave();
                        } else {
                            $("#insert_search5").show().html(response);
                            $btn.prop('disabled', false);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    // Check if the response contains success message despite being treated as an error
                    if (xhr.responseText && xhr.responseText.includes('Asset Registered Successfully')) {
                        $("#insert_search5").show().html(xhr.responseText);
                        handleSuccessfulSave();
                    } else {
                        console.error('Ajax error:', error);
                        $("#flash5").html('<div class="alert alert-danger">Error saving asset. Please try again.</div>');
                        $btn.prop('disabled', false);
                    }
                }
            });
        }

        function handleSuccessfulSave() {
            // Show success message
            $("#flash5").html('<div class="alert alert-success">' +
                '<i class="fa fa-check-circle"></i> ' +
                'Asset registered successfully!' +
            '</div>').show();
            
            // Clear any existing messages in insert_search5
            $("#insert_search5").empty();
            
            // Disable form inputs
            $('#assetInfo :input').prop('disabled', true);
            $('#assetInfo').off();
            
            // Close modal after delay
            setTimeout(function() {
                $('.modal').modal('hide');
                // Reload the page to update the asset list
                window.location.reload();
            }, 1500);
        }
    });
    
    // Clear button functionality
    $('#resetAssetBtn').click(function() {
        $('.modal').modal('hide');
    });
});



// Delete Asset
function deleteAsset(assetId) {
    Swal.fire({
        title: 'Are you sure you want to delete this asset?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Confirm'
    }).then((result) => {
        if (result.isConfirmed) {
            var userId = $('#user_id').val();
            $.ajax({
                type: "POST",
                url: "deleteAsset.php",
                data: { 
                    asset_id: assetId,
                    userid: userId 
                },
                dataType: "json",
                success: function(response) {
                    if(response.success) {
                        Swal.fire({
                            title: 'Deleted!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonColor: '#3085d6'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: response.message,
                            icon: 'error',
                            confirmButtonColor: '#3085d6'
                        });
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX Error:", textStatus, errorThrown);
                    console.log("Response:", jqXHR.responseText);
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred during the delete operation. Check console for details.',
                        icon: 'error',
                        confirmButtonColor: '#3085d6'
                    });
                }
            });
        }
    });
}

// Show Checkout Modal
function showCheckout(asset_tag) {
    $.ajax({
        type: "GET",
        url: "modal_checkout.php",
        data: {asset_tag: asset_tag},
        cache: false,
        success: function(html) {
            $("#myModal_checkout").show();
            $('#myModal_checkout').empty();
            $("#myModal_checkout").append(html);
        },
        error: function(xhr, status, error) {
            console.error("Error loading checkout modal:", error);
            $("#myModal_checkout").show();
            $('#myModal_checkout').empty();
            $("#myModal_checkout").append("Error loading checkout modal. Please try again.");
            setTimeout(function() {
                $("#myModal_checkout").hide();
            }, 3000);
        }
    });
}

// Show Update Asset Modal
function showUpdateAsset(asset_tag) {
    $.ajax({
        type: "GET",
        url: "modal_updateasset.php",
        data: {asset_tag: asset_tag},
        cache: false,
        success: function(html) {
            $("#myModal_updateTask").show();
            $('#myModal_updateTask').empty();
            $("#myModal_updateTask").append(html);
        },
        error: function(xhr, status, error) {
            console.error("Error loading asset update modal:", error);
            $("#myModal_updateTask").show();
            $('#myModal_updateTask').empty();
            $("#myModal_updateTask").append("Error loading update modal. Please try again.");
            setTimeout(function() {
                $("#myModal_updateTask").hide();
            }, 3000);
        }
    });
}




function deleteAsset(assetId) {
    Swal.fire({
        title: 'Are you sure you want to delete this asset?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Confirm'
    }).then((result) => {
        if (result.isConfirmed) {
            var userId = $('#user_id').val();
            $.ajax({
                type: "POST",
                url: "deleteAsset.php",
                data: { 
                    asset_id: assetId,
                    userid: userId 
                },
                dataType: "json",
                success: function(response) {
                    if(response.success) {
                        Swal.fire({
                            title: 'Deleted!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonColor: '#3085d6'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: response.message,
                            icon: 'error',
                            confirmButtonColor: '#3085d6'
                        });
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX Error:", textStatus, errorThrown);
                    console.log("Response:", jqXHR.responseText);
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred during the delete operation. Check console for details.',
                        icon: 'error',
                        confirmButtonColor: '#3085d6'
                    });
                }
            });
        }
    });
}




$('#tabUL a[href="#pcdesktop"]').on('click', function(event){               
    showPCDesktop();
});

function showPCDesktop() {
    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#dataTables-pcdesktop')) {
        $('#dataTables-pcdesktop').DataTable().destroy();
    }
    
    $.ajax({
        type: "GET",
        url: "pcdesktoptb.php",
        cache: false,    
        beforeSend: function(html) {               
            $("#insert_pcdesktop").show();
            $("#insert_pcdesktop").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Loading...Please Wait.');             
        },    
        success: function(html) {
            $("#insertbody_pcdesktop").show();
            $('#insertbody_pcdesktop').empty();
            $("#insertbody_pcdesktop").append(html);
            $("#insert_pcdesktop").hide();
        },
        error: function(html) {
            $("#insertbody_pcdesktop").show();
            $('#insertbody_pcdesktop').empty();
            $("#insertbody_pcdesktop").append(html);    
            $("#insert_pcdesktop").hide();                                     
        }                           
    });
}

// Add these functions after the showPCDesktop() function

$('#tabUL a[href="#laptop"]').on('click', function(event){              
    showLaptop();
});

function showLaptop() {
    $.ajax({
        type: "GET",
        url: "laptoptb.php",
        cache: false,   
        beforeSend: function(html) {               
            $("#insert_laptop").show();
            $("#insert_laptop").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Loading...Please Wait.');              
        },  
        success: function(html) {
            $("#insertbody_laptop").show();
            $('#insertbody_laptop').empty();
            $("#insertbody_laptop").append(html);
            $("#insert_laptop").hide();
        },
        error: function(html) {
            $("#insertbody_laptop").show();
            $('#insertbody_laptop').empty();
            $("#insertbody_laptop").append(html);   
            $("#insert_laptop").hide();                                     
        }                          
    });
}

$('#tabUL a[href="#smartphones"]').on('click', function(event){             
    showSmartPhones();
});

function showSmartPhones() {
    $.ajax({
        type: "GET",
        url: "smartphonestb.php",
        cache: false,   
        beforeSend: function(html) {               
            $("#insert_smartphones").show();
            $("#insert_smartphones").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Loading...Please Wait.');             
        },  
        success: function(html) {
            $("#insertbody_smartphones").show();
            $('#insertbody_smartphones').empty();
            $("#insertbody_smartphones").append(html);
            $("#insert_smartphones").hide();
        },
        error: function(html) {
            $("#insertbody_smartphones").show();
            $('#insertbody_smartphones').empty();
            $("#insertbody_smartphones").append(html);  
            $("#insert_smartphones").hide();                                        
        }                          
    });
}

$('#tabUL a[href="#tablets"]').on('click', function(event){             
    showTablets();
});

function showTablets() {
    $.ajax({
        type: "GET",
        url: "tablets.php",
        cache: false,   
        beforeSend: function(html) {               
            $("#insert_tablets").show();
            $("#insert_tablets").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Loading...Please Wait.');             
        },  
        success: function(html) {
            $("#insertbody_tablets").show();
            $('#insertbody_tablets').empty();
            $("#insertbody_tablets").append(html);
            $("#insert_tablets").hide();
        },
        error: function(html) {
            $("#insertbody_tablets").show();
            $('#insertbody_tablets').empty();
            $("#insertbody_tablets").append(html);  
            $("#insert_tablets").hide();                                        
        }                          
    });
}

$('#tabUL a[href="#printers"]').on('click', function(event){                
    showPrinters();
});

function showPrinters() {
    $.ajax({
        type: "GET",
        url: "printers.php",
        cache: false,   
        beforeSend: function(html) {               
            $("#insert_printers").show();
            $("#insert_printers").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Loading...Please Wait.');                
        },  
        success: function(html) {
            $("#insertbody_printers").show();
            $('#insertbody_printers').empty();
            $("#insertbody_printers").append(html);
            $("#insert_printers").hide();
        },
        error: function(html) {
            $("#insertbody_printers").show();
            $('#insertbody_printers').empty();
            $("#insertbody_printers").append(html); 
            $("#insert_printers").hide();                                       
        }                          
    });
}

$('#tabUL a[href="#accessories"]').on('click', function(event){             
    showAccessories();
});

function showAccessories() {
    $.ajax({
        type: "GET",
        url: "accessories.php",
        cache: false,   
        beforeSend: function(html) {               
            $("#insert_accessories").show();
            $("#insert_accessories").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Loading...Please Wait.');             
        },  
        success: function(html) {
            $("#insertbody_accessories").show();
            $('#insertbody_accessories').empty();
            $("#insertbody_accessories").append(html);
            $("#insert_accessories").hide();
        },
        error: function(html) {
            $("#insertbody_accessories").show();
            $('#insertbody_accessories').empty();
            $("#insertbody_accessories").append(html);  
            $("#insert_accessories").hide();                                        
        }                          
    });
}




$('#tabUL a[href="#PRUHDFI"]').on('click', function(event){             
    showPRUHDFI();
});

function showPRUHDFI() {
    $.ajax({
        type: "GET",
        url: "prformats/PRUHDFI.php",
        cache: false,   
        beforeSend: function(html) {               
            $("#insert_PRUHDFI").show();
            $("#insert_PRUHDFI").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Loading...Please Wait.');             
        },  
        success: function(html) {
            $("#insertbody_PRUHDFI").show();
            $('#insertbody_PRUHDFI').empty();
            $("#insertbody_PRUHDFI").append(html);
            $("#insert_PRUHDFI").hide();                                      
        },
        error: function(html) {
            $("#insertbody_PRUHDFI").show();
            $('#insertbody_PRUHDFI').empty();
            $("#insertbody_PRUHDFI").append(html);  
            $("#insert_PRUHDFI").hide();    
            $("#insert_PRUHDFI").hide();                                        
        }                          
    });
}

// Company Filter Functions
function filterCompany(company) {
    // Store the current filter globally
    currentCompanyFilter = company;
    
    // Target all possible tables across all tabs
    var tables = [
        '#dataTables-myticket',      // All List
        '#dataTables-pcdesktop',     // PC Desktop
        '#dataTables-laptop',        // Laptop
        '#dataTables-smartphones',   // Smart Phones
        '#dataTables-tablets',       // Tablets
        '#dataTables-printers',      // Printers
        '#dataTables-accessories'    // Accessories
    ];

    // Apply filter to each table if it exists; Company is column index 5 (0-based) for most tabs
    tables.forEach(function(tableId) {
        if ($.fn.DataTable.isDataTable(tableId)) {
            var table = $(tableId).DataTable();
            if (!company || company === 'all') {
-               table.column(5).search('').draw();
+               table.column(6).search('').draw();
            } else {
-               table.column(5).search(company).draw();
+               table.column(6).search(company).draw();
            }
        }
    });

    // Update the button text
    var buttonText = (!company || company === 'all') ? 'Filter Company' : 'Filter Company: ' + company;
    $('.btn-group .dropdown-toggle1').text(buttonText + ' ');
    $('.btn-group .dropdown-toggle1').append('<span class="caret"></span>');
}

// Function to apply company filter after DataTable is initialized
function applyCompanyFilterAfterDataTableInit(tableId) {
    if (currentCompanyFilter && $.fn.DataTable.isDataTable(tableId)) {
        var table = $(tableId).DataTable();
        if (!currentCompanyFilter || currentCompanyFilter === 'all') {
            table.column(5).search('').draw();
        } else {
            table.column(5).search(currentCompanyFilter).draw();
        }
    }
}

function showDocsModal(asset_id) {
    $.ajax({
        type: "GET",
        url: "modal_docs.php",
        data: { asset_id: asset_id },
        cache: false,
        success: function(html) {
            $("#myModal_docs").show();
            $('#myModal_docs').empty();
            $("#myModal_docs").append(html);
        },
        error: function(xhr, status, error) {
            console.error("Error loading docs modal:", error);
            $("#myModal_docs").show();
            $('#myModal_docs').empty();
            $("#myModal_docs").append("Error loading modal. Please try again.");
            setTimeout(function() { $("#myModal_docs").hide(); }, 3000);
        }
    });
}

