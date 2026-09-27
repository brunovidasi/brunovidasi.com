<script src="<?php echo base_url('assets/js/jquery.mousewheel.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/timeentry/jquery.timeentry.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/dateentry/jquery.dateentry.js'); ?>" type="text/javascript"></script>

<p class="page_title" style="float:left;">Edit Project - <?php echo $project->name; ?></p> <br><br><br>

<?php
	$deadline		 	= fdatetime_parts($project->deadline, "/");
	$deadline_date		= $deadline['date'];
	$time			= explode(":", $deadline['time']);
	$deadline_time 	= $time[0] . ':' . $time[1];
	
	if($project->end_date == "00/00/0000" OR $project->end_date == "0000-00-00")
		$project->end_date = "";

	require('application/views/includes/message.php');
	if(validation_errors() != '')
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";

?>

<form action="<?php echo base_url('project/update/'.$project->project_id) ?>" method="post" name="form1" class="form1">

    <table border="0" cellpadding="3" cellspacing="3" class="table_main col-lg-12" style="padding:10px;">
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Project Name:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-6 inputs">
					<input name="name" type="text" class="form-control" id="" value="<?php echo set_value("name", $project->name); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Client:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-5 inputs">
					<select name="client_id" class="form-control select2" id="client_id">
						<option hidden></option>
						<?php
						foreach($clients->result() as $client){
							$selected = "";
							if(set_value('client_id', $project->client_id) == $client->client_id){
								$selected = 'selected="selected"';
							}
							echo '<option value="'. $client->client_id .'" '. $selected .'>'. $client->name .'</option>';
						}						
						?>
					</select>
				</div>
			</td>
		</tr>
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong>Type:</strong> <span class="required">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-5 inputs">
					<select name="type_id" class="form-control" id="type_id">
						<option hidden></option>
						<?php
						foreach($types->result() as $type){
							$selected = "";
							if(set_value('type_id', $project->type_id) == $type->type_id){
								$selected = 'selected="selected"';
							}
							echo '<option value="'. $type->type_id .'" '. $selected .'>'. $type->type .'</option>';
						}
						?>
					</select>
				</div>
			<td>
		</tr>
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong>Owner:</strong> <span class="required">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-5 inputs">
					<select name="owner_id" class="form-control select2" id="owner_id">
						<option hidden></option>
						<?php
						foreach($users->result() as $user){
							$selected = "";
							if(set_value('owner_id', $project->owner_id) == $user->user_id){
								$selected = 'selected="selected"';
							}
							echo '<option value="'. $user->user_id .'" '. $selected .'>'. $user->name .'</option>';
						}
						?>
					</select>
				</div>
			<td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong>Description:</strong> <span class="required">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-6 inputs">
					<textarea name="description" class="form-control" id="description"><?php echo set_value('description', $project->description);  ?></textarea>
				</div>
			<td>
		</tr>

		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong>Notes:</strong> <span class="required"></span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-6 inputs">
					<textarea name="notes" class="form-control" id="notes"><?php echo set_value('notes', $project->notes);  ?></textarea>
				</div>
			<td>
		</tr>
		
	</table>

	<table border="0" cellpadding="3" cellspacing="3" class="table_main col-lg-12" style="padding:10px;">

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Status:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-12 inputs">
					<input type="radio" name="status" value="not_started" <?php echo set_radio('status', 'not_started', (($project->status=='not_started')?TRUE:FALSE)); ?> /> Not Started
					<input type="radio" name="status" value="in_progress" <?php echo set_radio('status', 'in_progress', (($project->status=='in_progress')?TRUE:FALSE)); ?> /> In Progress
					<input type="radio" name="status" value="paused" <?php echo set_radio('status', 'paused', (($project->status=='paused')?TRUE:FALSE)); ?> /> Paused
					<input type="radio" name="status" value="completed" <?php echo set_radio('status', 'completed', (($project->status=='completed')?TRUE:FALSE)); ?> /> Completed
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong>Start Date:</strong> <span class="required">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-3 inputs">
					<input name="start_date" type="text" class="form-control datepicker" id="start_date" value="<?php echo set_value("start_date", fdate($project->start_date, "/")); ?>" maxlength="10" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>

		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong>Deadline:</strong> <span class="required">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-3 inputs">
					<input name="deadline" type="text" class="form-control datepicker" id="deadline" value="<?php echo set_value("deadline", $deadline_date); ?>" maxlength="10" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong>Deadline Time:</strong> <span class="required">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-2 inputs">
					<input name="deadline_time" type="text" class="form-control" id="deadline_time" value="<?php echo set_value("deadline_time", $deadline_time); ?>" maxlength="10" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>

		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong>End Date:</strong> <span class="required">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-3 inputs">
					<input name="end_date" type="text" class="form-control datepicker" id="end_date" value="<?php if((!empty($project->end_date)) OR ($project->end_date != "0000-00-00")){ echo set_value("end_date", fdate($project->end_date, "/")); } ?>" maxlength="10" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Priority:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-8 inputs">
					<input type="radio" name="priority" value="low" <?php echo set_radio('priority', 'low', (($project->priority=='low')?TRUE:FALSE)); ?> /> Low
					<input type="radio" name="priority" value="normal" <?php echo set_radio('priority', 'normal', (($project->priority=='normal')?TRUE:FALSE)); ?> /> Normal
					<input type="radio" name="priority" value="urgent" <?php echo set_radio('priority', 'urgent', (($project->priority=='urgent')?TRUE:FALSE)); ?> /> Urgent
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>External Link:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-6 inputs">
					<input name="link" type="text" class="form-control" id="" value="<?php echo set_value("link", $project->link); ?>" />
				</div>
			</td>
		</tr>

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-12 inputs">
					<input name="submit" type="submit" class="btn btn-primary" id="submit" value="Save Project" />
				</div>
			</td>
		</tr>

	</table>

</form>
<br>

<script src="<?php echo base_url('assets/js/jquery.maskedinput.js');?>"></script>
<link rel="stylesheet" href="<?php echo base_url('assets/css/datepicker.css');?>" type="text/css">
<script src="<?php echo base_url('assets/js/bootstrap-datepicker.js');?>" type="text/javascript"></script>

<script src="<?php echo base_url('assets/js/texteditor/jquery-te-1.4.0.min.js');?>" type="text/javascript"></script>
<link rel="stylesheet" href="<?php echo base_url('assets/js/texteditor/jquery-te-1.4.0.css');?>" type="text/css">

<script>
	$(function() {
		$( "#deadline" ).datepicker();
		$( "#start_date" ).datepicker();
		$( "#end_date" ).datepicker();
		$(".datepicker").mask("99/99/9999");
		$("#deadline_time").mask("99:99");
	});
	
	$("#description").jqte({ol: false, ul: false, format: false});	
	$("#notes").jqte({ol: false, ul: false, format: false});	
	
	$('.datepicker').dateEntry({
		dateFormat: 'dmy/',
		spinnerImage: '',
		monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
		monthNamesShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
		dayNames: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
		dayNamesShort: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
		useMouseWheel: false,
		minDate: null, 
		maxDate: null,
	});
	
	$('#deadline_time').timeEntry({
		show24Hours: true, 
		showSeconds: false,
		useMouseWheel: false,
		spinnerImage: '',
		separator: ':',
		timeSteps: [1, 1, 0],
		defaultTime: null,
		minTime: null,
		maxTime: null,
	});
	
</script> 
     