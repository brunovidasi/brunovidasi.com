<script src="<?php echo base_url('assets/js/jquery.mousewheel.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/timeentry/jquery.timeentry.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/dateentry/jquery.dateentry.js'); ?>" type="text/javascript"></script>

<p class="page_title" style="float:left">Create Project</p> <br><br><br>

<?php
	require('application/views/includes/message.php');
	if(validation_errors() != ''){
	echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	}
?>

<form action="<?php echo base_url('project/insert') ?>" method="post" name="form1" class="form1">

    
    <table width="" border="0" cellpadding="3" cellspacing="3" class="table_main col-lg-12" style="padding:10px;">
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Project Name:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-6 inputs">
					<input name="name" type="text" class="form-control" id="" value="<?php echo set_value("name"); ?>" placeholder="Project / system name" />
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
							if(set_value('client_id') == $client->client_id){
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
						<option value=""></option>
						<?php
						foreach($types->result() as $type){
							$selected = "";
							if(set_value('type_id') == $type->type_id){
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
							if($user->user_id == 1) continue;

							$selected = "";
							if(set_value('owner_id') == $user->user_id){
								$selected = 'selected="selected"';
							}
							echo '<option value="'. $user->user_id .'" '. $selected .'>'. $user->name . ' - ' . $user->role.'</option>';
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
					<textarea name="description" class="form-control" id="description"><?php echo set_value('description');  ?></textarea>
				</div>
			<td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		</table>

		<table border="0" cellpadding="3" cellspacing="3" class="table_main col-lg-12" style="padding:10px; ">

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Status:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-12 inputs">
					<input type="radio" name="status" value="not_started" <?php echo set_radio('status', 'not_started'); ?> /> Not Started
					<input type="radio" name="status" value="in_progress" <?php echo set_radio('status', 'in_progress', TRUE); ?> /> In Progress
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
					<input name="start_date" type="text" class="form-control datepicker" id="start_date" value="<?php echo set_value("start_date", date("d/m/Y")); ?>" maxlength="10" />
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
					<input name="deadline" type="text" class="form-control datepicker" id="deadline" value="<?php echo set_value("deadline", date("d/m/Y")); ?>" maxlength="10" />
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
					<input name="deadline_time" type="text" class="form-control" id="deadline_time" value="<?php echo set_value("deadline_time"); ?>" maxlength="10" />
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
					<input type="radio" name="priority" value="low" <?php echo set_radio('priority', 'low'); ?> /> Low
					<input type="radio" name="priority" value="normal" <?php echo set_radio('priority', 'normal', TRUE); ?> /> Normal
					<input type="radio" name="priority" value="urgent" <?php echo set_radio('priority', 'urgent'); ?> /> Urgent
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>External Link:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-6 inputs">
					<input name="link" type="text" class="form-control" id="" value="<?php echo set_value("link"); ?>" placeholder="Project link or address"/>
				</div>
			</td>
		</tr>

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-6 inputs">
					<input name="submit" type="submit" class="btn btn-primary" id="submit" value="Create Project" />
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
		$(".datepicker").mask("99/99/9999");
		$("#deadline_time").mask("99:99");
	});
	
	$("#description").jqte({ol: false, ul: false, format: false});	
	
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
     