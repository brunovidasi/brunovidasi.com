<script src="<?php echo base_url('assets/js/jquery.mousewheel.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/timeentry/jquery.timeentry.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/dateentry/jquery.dateentry.js'); ?>" type="text/javascript"></script>

<p class="page_title" style="float:left"><?php echo lang('time_entry_edit_title'); ?></p> <br><br><br>

<?php
	$date				= fdate($ret->date, "/");
	$start_time 			= $ret->start_time;
	$end_time	 			= $ret->end_time;
	
	$starttime = explode(":", $start_time);
	$start_time = $starttime[0] . ':' . $starttime[1];
	
	if(!empty($ret->end_time)){
		$endtime = explode(":", $end_time);
		$end_time = $endtime[0] . ':' . $endtime[1];
	}else{
		$end_time = "";
	}

	require('application/views/includes/message.php');
	if(validation_errors() != '')
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	
?>

<form action="<?php echo base_url('time_entry/save') ?>" method="post" name="form1" class="form1">

    <table width="100%" border="0" cellpadding="3" cellspacing="3" class="table_main" style="padding:10px;">

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong><?php echo lang('lbl_client'); ?>:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-5 col-md-4 col-sm-5 inputs">
					<select name="client_id" class="form-control select2" id="client_id">
						<?php
						foreach($clients->result() as $client){
							$selected = "";
							if($client_id == $client->client_id){
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
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong><?php echo lang('lbl_project'); ?>:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-6 col-md-8 col-sm-8 inputs">
					<select name="project_id" class="form-control select2" id="project_id">
						<?php
						foreach($project->result() as $proj){
							$deadline = fdatetime_parts($proj->deadline, "/");
					
							$date_project = $deadline['date'];
							$time = $deadline['time'];
							
							echo '<option value="'.$proj->project_id .'">'.$proj->name.' - '.lang('msg_deadline').': '. $date_project .' '. $time .' - '.lang('msg_priority').': '. $proj->priority .'</option>';
						}
						?>
					</select>
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_task'); ?>:</strong> <span class="required"></span></div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-6 col-md-8 col-sm-8 inputs <?php if(form_error('task_id')){ echo 'has-error has-feedback'; }?>" style="display:inline">
					
					<select name="task_id" class="form-control select2" id="task_id">
						<?php
							$pid = set_value('project_id', $project_id);
							if(empty($pid)){
								echo '<option value="0">'.lang('msg_no_task_selected').'</option>';
							}else{
								echo $task_options;
							}
						?>
					</select>
					<?php echo form_error('task_id', '<span><label class="control-label" for="task_id">', '</label></span>'); ?>
				</div>
				<div class="col-lg-6 col-md-4 col-sm-4 inputs" style="display:inline">
					<span id="loader_tasks"></span>
				</div>
			</td>
		</tr>
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong><?php echo lang('lbl_phase'); ?>:</strong> <span class="required">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-3 col-md-4 col-sm-4 inputs">
					<select name="phase_id" class="form-control" id="phase_id">
						<option hidden></option>
						<?php
						foreach($phases->result() as $phase){
							$selected = "";
							if(set_value('phase_id', $ret->phase_id) == $phase->phase_id){
								$selected = 'selected="selected"';
							}
							echo '<option value="'. $phase->phase_id .'" '. $selected .'>'. $phase->phase .'</option>';
						}
						?>
					</select>
				</div>
			<td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong><?php echo lang('lbl_technical_description'); ?>:</strong> <span class="required">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-4 col-md-8 col-sm-8 inputs">
					<input name="technical_description" id="technical_description" type="text" class="form-control" id="description" value="<?php echo set_value('technical_description', $ret->technical_description); ?>" />
				</div>
				
				<span class="btn btn-primary" id="copy_description"  title="<?php echo lang('title_clone_description'); ?>" />
					<i class='glyphicon glyphicon-arrow-down'></i>
				</span>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong><?php echo lang('lbl_client_description'); ?>:</strong> <span class="required">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-4 col-md-8 col-sm-8 inputs">
					<input name="client_description" id="client_description" type="text" class="form-control" id="description" value="<?php echo set_value('client_description', $ret->client_description); ?>" />
				</div>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong><?php echo lang('lbl_date'); ?>:</strong> <span class="required">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-2 col-md-3 col-sm-4 inputs">
					<input name="date" type="text" class="form-control" id="datepicker" value="<?php echo set_value("date", $date); ?>" maxlength="10" />
				</div>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong><?php echo lang('lbl_start_time'); ?>:</strong> <span class="required">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-1 col-md-2 col-sm-3 inputs">
					<input name="start_time" type="text" class="form-control" id="start_time" size="10" maxlength="5" value="<?php echo set_value('start_time', $start_time); ?>" />
				</div>
        
				<span class="btn btn-primary" onclick="fillStartTime()" />
					<i class='glyphicon glyphicon-time'></i>
				</span>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs" style="margin-top: -20px;">
					<strong><?php echo lang('lbl_end_time'); ?>:</strong> <span class="required">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-1 col-md-2 col-sm-3 inputs">
					<input name="end_time" type="text" class="form-control" id="end_time" size="10" maxlength="5" value="<?php echo set_value('end_time', $end_time); ?>" />&nbsp;
				</div>
				
				<span class="btn btn-primary" onclick="fillEndTime()" />
					<i class='glyphicon glyphicon-time'></i>
				</span>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle"></td>
			
			<td style="padding-left:14px">
				<input name="user_id" type="hidden" value="<?php echo set_value('user_id', $ret->user_id); ?>" />
				<input name="time_entry_id" type="hidden" value="<?php echo $ret->time_entry_id; ?>" />
				
				<input name="submit" type="submit" class="btn btn-primary" id="submit" value="<?php echo lang('btn_edit_timer'); ?>" />
			</td>
		</tr>
		
		

	</table>
</form>
<br>

<script src="<?php echo base_url('assets/js/jquery.maskedinput.js');?>"></script>
<link rel="stylesheet" href="<?php echo base_url('assets/css/datepicker.css');?>" type="text/css">
<script src="<?php echo base_url('assets/js/bootstrap-datepicker.js');?>" type="text/javascript"></script>


<script>
	$(function() {
		$( "#datepicker" ).datepicker();
		$("#datepicker").mask("99/99/9999");
		$("#start_time").mask("99:99");
		$("#end_time").mask("99:99");
	});
	
	$( "#copy_description" ).click(function() {
		var msg = $('#technical_description').val();
		$('#client_description').val(msg);
	});
	
	jQuery(document).ready(function($){
		
		$('#start_time').timeEntry({
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
		
		$('#end_time').timeEntry({
			show24Hours: true, 
			showSeconds: false,
			useMouseWheel: false,
			spinnerImage: '',
			separator: ':',
			timeSteps: [1, 1, 0]
		});
		
		$('#datepicker').dateEntry({
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
		
		$("select[name=client_id]").change(function(){
			$("select[name=project_id]").html('<option value="0"><?php echo lang("loading"); ?></option>');
			 
			$.post("<?php echo base_url('time_entry/get_projects/'.$project_id.'/'. $client_id); ?>", {client_id:$(this).val()}, function(response){
				$("select[name=project_id]").html(response);
			});
		});
		
		
	});

</script>
     