$('#clientReports').on('init.field.bv', function(e, data) {	
	var $parent    = data.element.parents('.form-group'),
		$icon      = $parent.find('.form-control-feedback[data-bv-icon-for="' + data.field + '"]'),
		options    = data.bv.getOptions(),                      // Entire options
		validators = data.bv.getOptions(data.field).validators; // The field validators
									
		if (validators.notEmpty && options.feedbackIcons && options.feedbackIcons.required) {
			$icon.addClass(options.feedbackIcons.required).show();
		}
})

.on('error.field.bv', function(e, data) {
	 console.log(data.field, data.element, '-->error');
})
.on('success.field.bv', function(e, data) {
	console.log(data.field, data.element, '-->success'); 
});
$('#submitSearchBtn1').click(function() {
			$('#clientReports').bootstrapValidator('validate');
				var bootstrapValidator = $('#clientReports').data('bootstrapValidator');
				var stat1 = bootstrapValidator.isValid();
				if(stat1=='1')
				{
				
					
					var clientName = $("#clientName").val();	
					var statusType = $("#statusType").val();	
																																																			
									
					dataString = 'clientName='+ clientName+
								'&statusType='+ statusType;
							
					// alert(dataString);
					
					$.ajax({
					type: "GET",
					url: "result_client.php",
					data: dataString,
					cache: false,									
								beforeSend: function(html) 
								{			   
									$("#flashdtrx").show();
									$("#flashdtrx").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Searching ...Please Wait.');				
								},															
								success: function(html)
								{
									$("#insert_searchdtrx").show();
									$('#insert_searchdtrx').empty();
									$("#insert_searchdtrx").append(html);
									$("#flashdtrx").hide();																			   																   
								},
								error: function(html)
								{
									$("#insert_searchdtrx").show();
									$('#insert_searchdtrx').empty();
									$("#insert_searchdtrx").append(html);
									$("#flashdtrx").hide();													   												   		
								}												   
							});
				}
		});
		

$('#projectReportsxxx').on('init.field.bv', function(e, data) {	
	var $parent    = data.element.parents('.form-group'),
		$icon      = $parent.find('.form-control-feedback[data-bv-icon-for="' + data.field + '"]'),
		options    = data.bv.getOptions(),                      // Entire options
		validators = data.bv.getOptions(data.field).validators; // The field validators
									
		if (validators.notEmpty && options.feedbackIcons && options.feedbackIcons.required) {
			$icon.addClass(options.feedbackIcons.required).show();
		}
})

.on('error.field.bv', function(e, data) {
	 console.log(data.field, data.element, '-->error');
})
.on('success.field.bv', function(e, data) {
	console.log(data.field, data.element, '-->success'); 
});
$('#searchBtnProject').click(function() {
			$('#projectReportsxxx').bootstrapValidator('validate');
				var bootstrapValidator = $('#projectReportsxxx').data('bootstrapValidator');
				var stat2 = bootstrapValidator.isValid();
				if(stat2=='1')
				{
				
					
					var projectName2 = $("#projectName2").val();	
					var projectOwnerId = $("#projectOwnerId").val();	
					var projectManager = $("#projectManager").val();	
					var projectStatus2 = $("#projectStatus2").val();	
					var datestartedfrom = $("#datestartedfrom").val();	
					var datestartedto = $("#datestartedto").val();	
					var datewillendfrom = $("#datewillendfrom").val();	
					var datewillendto = $("#datewillendto").val();	
																																																			
									
					dataString = 'projectName2='+ projectName2+
								'&projectOwnerId='+ projectOwnerId+
								'&projectManager='+ projectManager+
								'&projectStatus2='+ projectStatus2+
								'&datestartedfrom='+ datestartedfrom+
								'&datestartedto='+ datestartedto+
								'&datewillendfrom='+ datewillendfrom+
								'&datewillendto='+ datewillendto;
							
					// alert(dataString);
					
					$.ajax({
					type: "GET",
					url: "result_project.php",
					data: dataString,
					cache: false,									
								beforeSend: function(html) 
								{			   
									$("#flash1").show();
									$("#flash1").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Searching ...Please Wait.');				
								},															
								success: function(html)
								{
									$("#insert_search1").show();
									$('#insert_search1').empty();
									$("#insert_search1").append(html);
									$("#flash1").hide();																			   																   
								},
								error: function(html)
								{
									$("#insert_search1").show();
									$('#insert_search1').empty();
									$("#insert_search1").append(html);
									$("#flash1").hide();													   												   		
								}												   
							});
				}
		});
		
		

$('#taskreports').on('init.field.bv', function(e, data) {	
	var $parent    = data.element.parents('.form-group'),
		$icon      = $parent.find('.form-control-feedback[data-bv-icon-for="' + data.field + '"]'),
		options    = data.bv.getOptions(),                      // Entire options
		validators = data.bv.getOptions(data.field).validators; // The field validators
									
		if (validators.notEmpty && options.feedbackIcons && options.feedbackIcons.required) {
			$icon.addClass(options.feedbackIcons.required).show();
		}
})

.on('error.field.bv', function(e, data) {
	 console.log(data.field, data.element, '-->error');
})
.on('success.field.bv', function(e, data) {
	console.log(data.field, data.element, '-->success'); 
});
$('#searchBtnTask').click(function() {
			$('#taskreports').bootstrapValidator('validate');
				var bootstrapValidator = $('#taskreports').data('bootstrapValidator');
				var stat3 = bootstrapValidator.isValid();
				if(stat3=='1')
				{
				
					
					var subjectName = $("#subjectName").val();	
					var projectOwnerId2 = $("#projectOwnerId2").val();	
					var projectClassification = $("#projectClassification").val();	
					var taskPriority = $("#taskPriority").val();	
					var targetdatefrom = $("#targetdatefrom").val();	
					var targetdateto = $("#targetdateto").val();	
					var startdatefrom = $("#startdatefrom").val();	
					var startdateto = $("#startdateto").val();	
					var actualdatefrom = $("#actualdatefrom").val();	
					var actualdateto = $("#actualdateto").val();	
					var taskStatus = $("#taskStatus").val();	
					var taskType = $("#taskType").val();	
					var taskAssignee = $("#taskAssignee").val();	
																																																			
									
					dataString = 'subjectName='+ subjectName+
								'&projectOwnerId2='+ projectOwnerId2+
								'&projectClassification='+ projectClassification+
								'&taskPriority='+ taskPriority+
								'&targetdatefrom='+ targetdatefrom+
								'&targetdateto='+ targetdateto+
								'&startdatefrom='+ startdatefrom+
								'&startdateto='+ startdateto+
								'&actualdatefrom='+ actualdatefrom+
								'&actualdateto='+ actualdateto+
								'&taskStatus='+ taskStatus+
								'&taskType='+ taskType+
								'&taskAssignee='+ taskAssignee;
							
					// alert(dataString);
					
					$.ajax({
					type: "GET",
					url: "result_task.php",
					data: dataString,
					cache: false,									
								beforeSend: function(html) 
								{			   
									$("#flash2").show();
									$("#flash2").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Searching ...Please Wait.');				
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