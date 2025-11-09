<?php 
include('../check_session.php');
$moduleid=4;
include('proxy.php');
$conn = connectionDB();

$taskId=$_GET['id2'];

	
?>
<div class="modal-body">
	<!--<div id="signup-box" class="signup-box widget-box no-border">-->
	<div id="signup-box" class="signup-box widget-box no-border">
		<div class="widget-body">
			<!--<div class="widget-main">-->
			
			
			
				<div class="main-content-inner">
					
						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
								<div id="hiddenform" hidden="true">
									<form id="formThreads">
										<fieldset>
										<div class="row">
											<div class="col-lg-12">
												<div class="form-group">
													<label class="block clearfix">Subject
														<span class="block input-icon input-icon-left">
															<i class="ace-icon fa fa-user"></i>
															<input class="form-control" placeholder="Subject" id="threadSubject" name="threadSubject" />
															<input type="hidden" class="form-control" placeholder="Subject" id="createduserid" name="createduserid" value="<?php echo $userid;?>"/>
															<input type="hidden" class="form-control" placeholder="Subject" id="taskthreadid" name="taskthreadid" value="<?php echo $taskId;?>"/>
														</span>
													</label>
												</div>	
											</div>
											<div class="col-lg-12">
												<div class="form-group">
													<label class="block clearfix">Detail
														<input class="form-control" placeholder="Subject" id="descriptionupdate23xxx" name="descriptionupdate23xxx" />
														<!--<textarea id="descriptionupdate23xxx" name="descriptionupdate23xxx" rows="5" cols="80"></textarea>-->
													</label>
												</div>	
											</div>
										</div>
											<div class="clearfix">
												<div id="flashtaskupxxxxxxx"></div>	
												<div id="insert_taskupxxxxxxx"></div>	
												<button type="button" class="width-25 pull-right btn btn-sm btn-success" id="submitThreadtBtnxxx" name="submitThreadtBtnxxx">
													<span class="bigger-110">Save</span>
													<i class="ace-icon fa fa-arrow-right icon-on-right"></i>
												</button>	
												
											</div>
										</fieldset>
									</form>
								</div>
								
								<div id="faq-list-1" class="panel-group accordion-style1 accordion-style2">
									<div class="panel panel-default">
										<div class="panel-heading">
											<a id="btn" name="btn" href="#" data-parent="#" data-toggle="collapse" class="accordion-toggle collapsed" style="text-decoration:none" >
												<i class="ace-icon fa fa-bullhorn" data-icon-hide="ace-icon fa fa-bullhorn" data-icon-show="ace-icon fa fa-bullhorn"></i>
												<span class="glyphicon glyphicon-comment pull-right"></span>
												&nbsp; Create Thread
												<?php 
													$myquery2x = "SELECT id
																	FROM pm_threadtb
																	WHERE taskid = '$taskId'";
													$myresult2x = mysqli_query($conn, $myquery2x);
													$counterrow = mysqli_num_rows($myresult2x);
													
													
												?>
												<span class="light-grey">(<?php echo $counterrow; ?>)</span>
												
											</a>
										</div>
									</div>
									<?php 
										$counter = 0;
										$myquery2 = "SELECT pm_threadtb.id,pm_threadtb.createdbyid,pm_threadtb.datetimecreated,pm_threadtb.subject,pm_threadtb.message,
															sys_usertb.user_firstname,sys_usertb.user_lastname
														FROM pm_threadtb
														LEFT JOIN sys_usertb ON sys_usertb.id=pm_threadtb.createdbyid 
														WHERE pm_threadtb.taskid = '$taskId'
														ORDER by pm_threadtb.datetimecreated DESC LIMIT 10";
										$myresult2 = mysqli_query($conn, $myquery2);
										while($row2 = mysqli_fetch_assoc($myresult2)){
											$myThreadSubject = $row2['subject'];	
											$myThreadMessage = $row2['message'];	
											$myThreadTime = $row2['datetimecreated'];	
											$myThreadId = $row2['id'];	
											$myThreadFirstName = $row2['user_firstname'];	
											$myThreadLastName = $row2['user_lastname'];	
											$myCompleteName = $myThreadFirstName." ".$myThreadLastName;
									?>
									
									<div class="panel panel-default">
										<div class="message-item message-unread">
											<a href="#faq-1-<?php echo $counter;?>" data-parent="#faq-list-1" data-toggle="collapse" class="accordion-toggle collapsed" style="text-decoration:none">
												<i class="ace-icon fa fa-user bigger-130"></i>
												&nbsp; 
												<span class="sender" title="Alex John Red Smith">
													<?php echo $myCompleteName; ?>
												</span>
												<span class="text pull-right"><?php echo $myThreadTime; ?></span>
												
												<span class="summary">
													<span class="text">
														<?php echo $myThreadSubject; ?>
													</span>
												</span>
											</a>
										</div>

										<div class="panel-collapse collapse" id="faq-1-<?php echo $counter;?>">
											<div class="message-content" id="id-message-content">
												<div class="message-header clearfix">
													<div class="pull-left">
														&nbsp;
														<img class="middle" alt="John's Avatar" src="../assets/images/avatars/avatar.png" width="32" />
														&nbsp;
														<a href="#" class="sender"><?php echo $myCompleteName; ?></a>

														&nbsp;
														<i class="ace-icon fa fa-clock-o bigger-110 orange middle"></i>
														<span class="time grey"><?php echo $myThreadTime; ?></span>
													</div>
												</div>
												<div class="hr hr-double"></div>
												
												<div class="message-body">
													<p>
														<?php echo $myThreadMessage; ?>
													</p>
												</div>
												<div class="hr hr-double"></div>
												
												<!--<div class="message-attachment clearfix">
													<div class="attachment-title">
														<span class="blue bolder bigger-110">Attachments</span>
													</div>
													<div class="attachment-images pull-left">
														<div class="vspace-4-sm"></div>

														<div>
															<a href='../assets/images/gallery/thumb-4.jpg' target='_blank'>
																<img width="36" alt="image 4" src="../assets/images/gallery/thumb-4.jpg" />
															</a>
														</div>
													</div>
												</div>-->
											</div>
										</div>
									</div>
									<?php
										$counter++;
										}
											?>
								</div>


								<!-- PAGE CONTENT ENDS -->
							</div><!-- /.col -->
						</div><!-- /.row -->
					</div><!-- /.page-content -->
				
			
		</div>
	</div>
</div>

<!-- CK Editor-->
<script type="text/javascript">
	// CKEDITOR.replace('descriptionupdate23xxx');
	$('#btn').click(function() {
		$('#hiddenform').toggle();
	});
	
	/*====processing of data from form to php==============*/
	$(document).ready(function() {
		$('#formThreads')
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
			submitButtons: 'button[name="submitThreadtBtnxxx"]',			    
			feedbackIcons: {
			required: 'glyphicon glyphicon-asterisk',
				valid: 'glyphicon glyphicon-ok',
				invalid: 'glyphicon glyphicon-remove',
				validating: 'glyphicon glyphicon-refresh'
			},
			fields: {
				descriptionupdate23xxx: {
					validators: {
						notEmpty: {
							message: 'The Description is required'
						}                            
					}
				},
				threadSubject: {
					validators: {
						notEmpty: {
							message: 'The Subject is required'
						}                            
					}
				}
			}
		})
		.on('success.field.bv', function(e, data) {
			 console.log(data.field, data.element, '-->success');
		});
		$('#submitThreadtBtnxxx').click(function() {
			$('#formThreads').bootstrapValidator('validate');
			var bootstrapValidator = $('#formThreads').data('bootstrapValidator');
			var stat1c = bootstrapValidator.isValid();
			if(stat1c=='1')
			{
				var createduserid = $("#createduserid").val();	
				var taskthreadid = $("#taskthreadid").val();	
				var threadSubject = $("#threadSubject").val();	
				var descriptionupdate23xxx = $("#descriptionupdate23xxx").val();	
				var descriptionupdate23xxx = escape(descriptionupdate23xxx);
				
				var dataString = 'createduserid='+ createduserid
								+'&taskthreadid='+ taskthreadid
								+'&threadSubject='+ threadSubject
								+'&descriptionupdate23xxx='+ descriptionupdate23xxx;
				
				// alert(taskAssignee2);
				$.ajax({
					type: "GET",
					url: "saveThread.php",
					data: dataString,
					cache: false,									
							beforeSend: function(html) 
								{			   
									$("#flashtaskupxxxxxxx").show();
									$("#flashtaskupxxxxxxx").html('<img src="ajax-loader.gif" align="absmiddle">&nbsp;Saving...Please Wait.');				
								},															
							success: function(html)
								{
									$("#insert_taskupxxxxxxx").show();
									$('#insert_taskupxxxxxxx').empty();
									$("#insert_taskupxxxxxxx").append(html);
									$("#flashtaskupxxxxxxx").hide();																		   
							   },
							error: function(html)
								{
									$("#insert_taskupxxxxxxx").show();
									$('#insert_taskupxxxxxxx').empty();
									$("#insert_taskupxxxxxxx").append(html);
									$("#flashtaskupxxxxxxx").hide();													   												   		
							   }											   
				});
			}
		});	
	});
</script>