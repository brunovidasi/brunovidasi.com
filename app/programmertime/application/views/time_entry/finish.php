<script src="<?php echo base_url('assets/js/jquery.mousewheel.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/timeentry/jquery.timeentry.js'); ?>" type="text/javascript"></script>

<p class="page_title" style="float:left"><?php echo lang('time_entry_title'); ?></p> <br><br><br>

<?php
	$date				= fdate($ret->date, "/");
	$start_time 			= $ret->start_time;
	$starttime = explode(":", $start_time);
	$start_time = $starttime[0] . ':' . $starttime[1];
		
	require('application/views/includes/message.php');
?>

<form action="<?php echo base_url('time_entry/update') ?>" method="post" name="form1" class="form1">

    <table width="100%" border="0" cellpadding="3" cellspacing="3" class="table_main" style="padding:10px;">

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_client'); ?>:</strong> <span class="required">*</span></div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-5 col-md-4 col-sm-5 inputs">
					<select name="client_id" class="form-control" id="client_id" disabled>
						<?php foreach($clients->result() as $client){
							$selected = "";
							if($client_id == $client->client_id)
								$selected = 'selected="selected"';

							echo '<option value="'. $client->client_id .'" '. $selected .'>'. $client->name .'</option>';
						} ?>
					</select>
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_project'); ?>:</strong> <span class="required">*</span></div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-6 col-md-8 col-sm-8 inputs">
					<select name="project_id" class="form-control" id="project_id" disabled>
						<?php foreach($project->result() as $proj){
							$deadline = fdatetime_parts($proj->deadline, "/");
					
							$date_project = $deadline['date'];
							$time = $deadline['time'];
							
							echo '<option value="'.$proj->project_id .'">'.$proj->name.' - '.lang('msg_deadline').': '. $date_project .' '. $time .' - '.lang('msg_priority').': '. $proj->priority .'</option>';
						} ?>
					</select>
				</div>
			</td>
		</tr>

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_task'); ?>:</strong> <span class="required"></span></div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-6 col-md-8 col-sm-8 inputs">
					<select name="task_id" class="form-control" id="task_id" disabled>
						<?php echo $tasks; ?>
					</select>
				</div>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_phase'); ?>:</strong> <span class="required">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-3 col-md-4 col-sm-4 inputs <?php if(form_error('phase_id')){ echo 'has-error has-feedback'; }?>">
					<select name="phase_id" class="form-control" id="phase_id" disabled>
						<option hidden></option>
						<?php foreach($phases->result() as $phase){
							$selected = "";
							if(set_value('phase_id', $ret->phase_id) == $phase->phase_id)
								$selected = 'selected="selected"';
							
							echo '<option value="'. $phase->phase_id .'" '. $selected .'>'. $phase->phase .'</option>';
						} ?>
					</select>
					<?php echo form_error('phase_id', '<span><label class="control-label" for="phase_id">', '</label></span>'); ?>
				</div>
			<td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_technical_description'); ?>:</strong> <span class="required">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-4 col-md-8 col-sm-8 inputs <?php if(form_error('technical_description')){ echo 'has-error has-feedback'; }?>">
					<input name="technical_description" id="technical_description" type="text" class="form-control" id="description" value="<?php echo set_value('technical_description', $ret->technical_description); ?>" />
					<?php echo form_error('technical_description', '<span><label class="control-label" for="technical_description">', '</label></span>'); ?>
				</div>
				<span class="btn btn-primary" id="copy_description"  title="<?php echo lang('title_clone_description'); ?>" /><i class='glyphicon glyphicon-arrow-down'></i></span>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_client_description'); ?>:</strong> <span class="required">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-4 col-md-8 col-sm-8 inputs <?php if(form_error('client_description')){ echo 'has-error has-feedback'; }?>">
					<input name="client_description" id="client_description" type="text" class="form-control" id="description" value="<?php echo set_value('client_description', $ret->client_description); ?>" />
					<?php echo form_error('client_description', '<span><label class="control-label" for="client_description">', '</label></span>'); ?>
				</div>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_date'); ?>:</strong> <span class="required">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-2 col-md-3 col-sm-4 inputs">
					<input name="date" type="text" class="form-control" id="" value="<?php echo $date; ?>" maxlength="10" disabled />
				</div>
				<span class="btn btn-primary disabled" onclick="fillDate()" /> <?php echo lang('btn_today'); ?> </span>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_start_time'); ?>:</strong> <span class="required">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-1 col-md-2 col-sm-3 inputs">
					<input name="start_disabled" type="text" class="form-control" id="start_disabled" size="10" maxlength="5" value="<?php echo $start_time; ?>" disabled />
				</div>
				<span class="btn btn-primary disabled" onclick="#" /><i class='glyphicon glyphicon-time'></i></span>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs" style="margin-top: -20px;"><strong><?php echo lang('lbl_end_time'); ?>:</strong> <span class="required">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-1 col-md-2 col-sm-3 inputs <?php if(form_error('end_time')){ echo 'has-error has-feedback'; }?>">
					<input name="end_time" type="text" class="form-control" id="end_time" size="10" maxlength="5" value="<?php echo set_value('end_time'); ?>" />&nbsp;
					<?php echo form_error('end_time', '<span><label class="control-label" for="end_time">', '</label></span>'); ?>
				</div>
				<span class="btn btn-primary" id="btn_end" onclick="fillEndTime()" /><i class='glyphicon glyphicon-time'></i></span>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle"></td>
			
			<td style="padding-left:14px">
				<input name="user_id" type="hidden" value="<?php echo $this->session->userdata('id'); ?>" />
				<input name="start_time" type="hidden" class="form-control" id="start_time" size="10" maxlength="5" value="<?php echo $start_time; ?>" />
				<input name="time_entry_id" type="hidden" value="<?php echo $ret->time_entry_id; ?>" />
				
				<button type="submit" class="btn btn-primary" id="submit" /><i class="glyphicon glyphicon-import"></i> <?php echo lang('btn_stop_timer'); ?></button>
				<span id="loader_submit"></span>
			</td>
		</tr>
		
	</table>
</form>
<br>

<link rel="stylesheet" href="<?php echo base_url('assets/css/datepicker.css');?>" type="text/css">
<script src="<?php echo base_url('assets/js/bootstrap-datepicker.js');?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/jquery.maskedinput.js');?>"></script>

<script>
jQuery(document).ready(function($){
	$( "#copy_description" ).click(function() {
		var msg = $('#technical_description').val();
		$('#client_description').val(msg);
		$("#client_description").parent().removeClass("has-error");
		$("label[for='client_description']").parent().html("");	
	});
	
	$('#technical_description, #client_description, #end_time, #phase_id').change(function(){
		var name = $(this).attr('id');
		$("#"+name).parent().removeClass("has-error");
		$("#"+name).parent().removeClass("has-error");
		$("label[for='"+ name +"']").parent().html("");
	});
	
	$('#btn_end').click(function(){
		$("#end_time").parent().removeClass("has-error");
		$("label[for='end_time']").parent().html("");
	});
	
	$("#end_time").mask("99:99");
	
	$('#end_time').timeEntry({
		show24Hours: true, 
		showSeconds: false,
		useMouseWheel: false,
		spinnerImage: '',
		separator: ':',
		timeSteps: [1, 1, 0]
	});
	
	$('#submit').click(function(){
		$("html").css("cursor", "progress");
		$("#submit").addClass("disabled");
		$("#loader_submit").html("<img src='<?php echo base_url('assets/images/system/ajax_loader.gif'); ?>' width='20px'/>");
		
		$(".form1").submit();
	});
});
</script>