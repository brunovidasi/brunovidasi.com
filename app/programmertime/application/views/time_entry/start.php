<script src="<?php echo base_url('assets/js/jquery.mousewheel.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/timeentry/jquery.timeentry.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/dateentry/jquery.dateentry.js'); ?>" type="text/javascript"></script>

<p class="page_title" style="float:left"><?php echo lang('time_entry_title'); ?></p> <br><br><br>

<?php require('application/views/includes/message.php'); ?>

<form action="<?php echo base_url('time_entry/insert') ?>" method="post" name="form1" class="form1" id="log_time_entry">

    <table width="100%" border="0" cellpadding="3" cellspacing="3" class="table_main" style="padding:10px;">

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_client'); ?>:</strong> <span class="required">*</span></div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-5 col-md-4 col-sm-5 inputs <?php if(form_error('client_id')){ echo 'has-error has-feedback'; }?>">
					<select name="client_id" class="form-control select2" id="client_id">
						<option hidden></option>
						<?php foreach($clients->result() as $client){
							$selected = "";
							if(set_value('client_id', $client_id) == $client->client_id){
								$selected = 'selected="selected"';
							}
							echo '<option value="'. $client->client_id .'" '. $selected .'>'. $client->name .'</option>';
						} ?>
					</select>
					<?php echo form_error('client_id', '<span><label class="control-label" for="client_id">', '</label></span>'); ?>
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_project'); ?>:</strong> <span class="required">*</span></div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-6 col-md-8 col-sm-8 inputs <?php if(form_error('project_id')){ echo 'has-error has-feedback'; }?>" style="display:inline">
					<select name="project_id" class="form-control select2" id="project_id">
						<option hidden></option>
						<?php
							$cid = set_value('client_id', $client_id);
							if(empty($cid)){
								echo '<option value="">'.lang('msg_select_client').'</option>';
							}else{
								echo $project_options;
							}
						?>
					</select>
					<?php echo form_error('project_id', '<span><label class="control-label" for="project_id">', '</label></span>'); ?>
				</div>
				<div class="col-lg-6 col-md-4 col-sm-4 inputs" style="display:inline">
					<span id="loader_projects"></span>
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
						<option hidden></option>
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
				<div class="inputs"><strong><?php echo lang('lbl_phase'); ?>:</strong> <span class="required">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-3 col-md-4 col-sm-4 inputs <?php if(form_error('phase_id')){ echo 'has-error has-feedback'; }?>">
					<?php 
						$phase_disabled = '';
						if(set_value('task_id') != 0)
							$phase_disabled = 'disabled';
					?>
					<select name="phase_id" class="form-control" id="phase_id" <?php echo $phase_disabled; ?>>
						<option hidden></option>
						<?php foreach($phases->result() as $phase){
							$selected = "";
							if(set_value('phase_id') == $phase->phase_id){
								$selected = 'selected="selected"';
							}
							echo '<option value="'. $phase->phase_id .'" '. $selected .'>'. $phase->phase .'</option>';
						} ?>
					</select>
					<?php echo form_error('phase_id', '<span><label class="control-label" for="phase_id">', '</label></span>'); ?>
					<?php if(empty($phase_disabled)) { ?>
						<input type="hidden" id="hidden_phase" />
					<?php }else{ ?>
						<input type="hidden" id="hidden_phase" name="phase_id" value="<?php echo set_value('phase_id'); ?>" />
					<?php } ?>
				</div>
			<td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_technical_description'); ?>:</strong> <span class="required">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-4 col-md-8 col-sm-8 inputs <?php if(form_error('technical_description')){ echo 'has-error has-feedback'; }?>">
					<input name="technical_description" id="technical_description" type="text" class="form-control" id="technical_description" value="<?php echo set_value('technical_description'); ?>" placeholder="<?php echo lang('placeholder_technical_description'); ?>" />
					<?php echo form_error('technical_description', '<span><label class="control-label" for="technical_description">', '</label></span>'); ?>
				</div>
				<span class="btn btn-primary" id="copy_description" title="<?php echo lang('title_clone_description'); ?>" /><i class='glyphicon glyphicon-arrow-down'></i></span>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_client_description'); ?>:</strong> <span class="required">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-4 col-md-8 col-sm-8 inputs <?php if(form_error('client_description')){ echo 'has-error has-feedback'; }?>">
					<input name="client_description" id="client_description" type="text" class="form-control" id="client_description" value="<?php echo set_value('client_description'); ?>" placeholder="<?php echo lang('placeholder_client_description'); ?>" />
					<?php echo form_error('client_description', '<span><label class="control-label" for="client_description">', '</label></span>'); ?>
				</div>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_date'); ?>:</strong> <span class="required">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-2 col-md-3 col-sm-4 inputs <?php if(form_error('date')){ echo 'has-error has-feedback'; }?>">
					<input name="date" type="text" class="form-control" id="datepicker" value="<?php echo set_value("date", date("d/m/Y")); ?>" maxlength="10" />
					<?php echo form_error('date', '<span><label class="control-label" for="date">', '</label></span>'); ?>
				</div>
				<span class="btn btn-primary" id="btn_date" onclick="fillDate()" /> <?php echo lang('btn_today'); ?> </span>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_start_time'); ?>:</strong> <span class="required">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-1 col-md-2 col-sm-3 inputs <?php if(form_error('start_time')){ echo 'has-error has-feedback'; }?>">
					<input name="start_time" type="text" class="form-control" id="start_time" size="10" maxlength="5" value="<?php echo set_value('start_time'); ?>"/>
					<?php echo form_error('start_time', '<span><label class="control-label" for="start_time">', '</label></span>'); ?>
				</div>
				<span class="btn btn-primary" id="btn_start" onclick="fillStartTime()" /><i class='glyphicon glyphicon-time'></i></span>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs" style="margin-top: -20px;"><strong><?php echo lang('lbl_end_time'); ?>:</strong> <span class="required"></span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-1 col-md-2 col-sm-3 inputs">
					<input name="end_time" type="text" class="form-control" id="end_time" size="10" maxlength="5" disabled />&nbsp;
				</div>
				<span class="btn btn-primary disabled" onclick="#" /><i class='glyphicon glyphicon-time'></i></span>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle"></td>
			
			<td style="padding-left:14px">
				<input name="user_id" type="hidden" value="<?php echo $this->session->userdata('id'); ?>" />
				
				<button type="submit" class="btn btn-primary" id="submit" /><i class="glyphicon glyphicon-import"></i> <?php echo lang('btn_start_timer'); ?></button>
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
	
	$("select[name=client_id]").change(function(){
		$("html").css("cursor", "progress");
		$("select[name=project_id]").html('<option value="0"><?php echo lang("loading_projects"); ?></option>');
		$("#loader_projects").html("<img src='<?php echo base_url('assets/images/system/ajax_loader.gif'); ?>' width='30px'/>");
		
		$.post("<?php print base_url('time_entry/get_projects/'.$project); ?>", {client_id:$(this).val()}, function(response){
			$("select[name=project_id]").html(response);
			$("#loader_projects").html("");
			$("html").css("cursor", "auto");
		});
	});

	$("select[name=project_id]").change(function(){
		$("html").css("cursor", "progress");
		$("select[name=task_id]").html('<option value="0"><?php echo lang("loading_tasks"); ?></option>');
		$("#loader_tasks").html("<img src='<?php echo base_url('assets/images/system/ajax_loader.gif'); ?>' width='30px'/>");
		
		$.post("<?php print base_url('time_entry/get_tasks/'.$project); ?>", {project_id:$(this).val()}, function(response){
			$("select[name=task_id]").html(response);
			$("#loader_tasks").html("");
			$("html").css("cursor", "auto");
		});
	});

	$("select[name=task_id]").change(function(){
		
		var task_id = $(this).val();

		if(task_id == 0){
			$('select[name=phase_id]').removeAttr('disabled');
			$('#hidden_phase').attr('disabled', 'disabled');

		}else{
			$("html").css("cursor", "progress");
			$("select[name=phase_id]").html('<option value="0"><?php echo lang("loading_data"); ?></option>');
			$("#loader_tasks").html("<img src='<?php echo base_url('assets/images/system/ajax_loader.gif'); ?>' width='30px'/>");
			
			$.post("<?php print base_url('time_entry/get_task/'.$project); ?>", {task_id:task_id}, function(response){
				var task = $.parseJSON(response);
				$('#technical_description').val(task.name);
				$('#client_description').val(task.name);
				$('select[name=phase_id]').attr('disabled', 'disabled');
				$('select[name=phase_id]').html('<option value="'+task.phase_id+'">'+task.phase+'</option>');
				$('#hidden_phase').removeAttr('disabled');
				$('#hidden_phase').attr('name', 'phase_id');
				$('#hidden_phase').attr('value', task.phase_id);

				$("#loader_tasks").html("");
				$("html").css("cursor", "auto");
			});
		}
	});
	
	$(function() {
		$( "#datepicker" ).datepicker();
		$("#datepicker").mask("99/99/9999");
		$("#start_time").mask("99:99");
	});

	$("#copy_description").click(function() {
		var msg = $('#technical_description').val();
		$('#client_description').val(msg);
		$("#client_description").parent().removeClass("has-error");
		$("label[for='client_description']").parent().html("");		
	});

	$('#technical_description, #client_description, #start_time, #date, #phase_id, #project_id, #task_id, #client_id').change(function(){
		var name = $(this).attr('id');
		$("#"+name).parent().removeClass("has-error");
		$("#"+name).parent().removeClass("has-error");
		$("label[for='"+ name +"']").parent().html("");
	});
	
	$('#submit').click(function(){
		$("html").css("cursor", "progress");
		$("#submit").addClass("disabled");
		$("#loader_submit").html("<img src='<?php echo base_url('assets/images/system/ajax_loader.gif'); ?>' width='20px'/>");
		
		$(".form1").submit();
	});
	
	$('#btn_start').click(function(){
		$("#start_time").parent().removeClass("has-error");
		$("label[for='start_time']").parent().html("");
	});
	
	$('#btn_date').click(function(){
		$("#date").parent().removeClass("has-error");
		$("label[for='date']").parent().html("");
	});

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


	<?php if(!empty($task_id)){ ?>

		var task_id = <?php echo (int) $task_id; ?>;

		if(task_id == 0){
			$('select[name=phase_id]').removeAttr('disabled');
			$('#hidden_phase').attr('disabled', 'disabled');

		}else{
			$("html").css("cursor", "progress");
			$("select[name=phase_id]").html('<option value="0"><?php echo lang("loading_data"); ?></option>');
			$("#loader_tasks").html("<img src='<?php echo base_url('assets/images/system/ajax_loader.gif'); ?>' width='30px'/>");
			
			$.post("<?php print base_url('time_entry/get_task/'.$project); ?>", {task_id:task_id}, function(response){
				var task = $.parseJSON(response);
				$('#technical_description').val(task.name);
				$('#client_description').val(task.name);
				$('select[name=phase_id]').attr('disabled', 'disabled');
				$('select[name=phase_id]').html('<option value="'+task.phase_id+'">'+task.phase+'</option>');
				$('#hidden_phase').removeAttr('disabled');
				$('#hidden_phase').attr('name', 'phase_id');
				$('#hidden_phase').attr('value', task.phase_id);

				$("#loader_tasks").html("");
				$("html").css("cursor", "auto");
			});
		}
	<?php } ?>
	
});
</script>