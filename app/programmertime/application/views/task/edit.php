<script src="<?php echo base_url('assets/js/jquery.mousewheel.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/timeentry/jquery.timeentry.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/dateentry/jquery.dateentry.js'); ?>" type="text/javascript"></script>

<p class="page_title" style="float:left"><?php echo lang('title_edit_task'); ?></p> <br><br><br>

<?php require('application/views/includes/message.php'); ?>

<!-- <pre><?php print_r($task); ?></pre> -->

<form action="<?php echo base_url('task/update/'.$task->task_id) ?>" method="post" name="form1" class="form1" id="log_time_entry">

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
							if(set_value('client_id', $task->client_id) == $client->client_id){
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
							$cid = set_value('client_id', $task->client_id);
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
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_phase'); ?>:</strong> <span class="required">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-3 col-md-4 col-sm-4 inputs <?php if(form_error('phase_id')){ echo 'has-error has-feedback'; }?>">
					<select name="phase_id" class="form-control" id="phase_id">
						<option hidden></option>
						<?php foreach($phases->result() as $phase){
							$selected = "";
							if(set_value('phase_id', $task->phase_id) == $phase->phase_id){
								$selected = 'selected="selected"';
							}
							echo '<option value="'. $phase->phase_id .'" '. $selected .'>'. $phase->phase .'</option>';
						} ?>
					</select>
					<?php echo form_error('phase_id', '<span><label class="control-label" for="phase_id">', '</label></span>'); ?>
				</div>
			<td>
		</tr>

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_owner'); ?>:</strong> <span class="required">*</span></div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-5 col-md-4 col-sm-5 inputs <?php if(form_error('user_id')){ echo 'has-error has-feedback'; }?>">
					<select name="user_id" class="form-control select2" id="user_id">
						<option hidden></option>
						<?php foreach($users->result() as $user){
							if($user->user_id == 1)
								continue;

							$selected = "";
							if(set_value('user_id', $task->owner_id) == $user->user_id){
								$selected = 'selected="selected"';
							}
							echo '<option value="'. $user->user_id .'" '. $selected .'>'. $user->name . ' - ' . $user->role .'</option>';
						} ?>
					</select>
					<?php echo form_error('user_id', '<span><label class="control-label" for="user_id">', '</label></span>'); ?>
				</div>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_name'); ?>:</strong> <span class="required">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-4 col-md-8 col-sm-8 inputs <?php if(form_error('name')){ echo 'has-error has-feedback'; }?>">
					<input name="name" id="name" type="text" class="form-control" id="name" value="<?php echo set_value('name', $task->name); ?>" />
					<?php echo form_error('name', '<span><label class="control-label" for="name">', '</label></span>'); ?>
				</div>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_description'); ?>:</strong> <span class="required"></span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-6 col-md-10 col-sm-10 inputs <?php if(form_error('description')){ echo 'has-error has-feedback'; }?>">
					<textarea name="description" id="description" class="form-control" id="description" rows="3"><?php echo set_value('description', $task->description); ?></textarea>
					<?php echo form_error('description', '<span><label class="control-label" for="description">', '</label></span>'); ?>
				</div>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_deadline'); ?>:</strong> <span class="required">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-2 col-md-3 col-sm-4 inputs <?php if(form_error('date')){ echo 'has-error has-feedback'; }?>">
					<input name="date" type="text" class="form-control" id="datepicker" value="<?php echo set_value("date", fdate($task->due_date, "/")); ?>" maxlength="10" />
					<?php echo form_error('date', '<span><label class="control-label" for="date">', '</label></span>'); ?>
				</div>
				<span class="btn btn-primary" id="btn_date" onclick="fillDate()" /> <?php echo lang('btn_today'); ?> </span>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('msg_estimated_hours'); ?>:</strong> <span class="required">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-1 col-md-2 col-sm-3 inputs <?php if(form_error('hours')){ echo 'has-error has-feedback'; }?>">
					<input name="hours" type="number" class="form-control" id="hours" size="10" maxlength="5" value="<?php echo set_value('hours', $task->estimated_hrs); ?>"/>
					<?php echo form_error('hours', '<span><label class="control-label" for="hours">', '</label></span>'); ?>
				</div>
			</td>
		</tr>		
		
		<tr>
			<td valign="middle"></td>
			
			<td style="padding-left:14px">
				<input name="task_id" type="hidden" value="<?php echo $task->task_id; ?>" />
				
				<button type="submit" class="btn btn-primary" id="submit" /><i class="glyphicon glyphicon-import"></i> <?php echo lang('btn_edit'); ?></button>
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
		
		$.post("<?php print base_url('time_entry/get_projects/'.$task->project_id); ?>", {client_id:$(this).val()}, function(response){
			$("select[name=project_id]").html(response);
			$("#loader_projects").html("");
			$("html").css("cursor", "auto");
		});
	});
	
	$(function() {
		$( "#datepicker" ).datepicker();
		$("#datepicker").mask("99/99/9999");
		$("#start_time").mask("99:99");
	});

	$( "#copy_description" ).click(function() {
		var msg = $('#technical_description').val();
		$('#client_description').val(msg);
		$("#client_description").parent().removeClass("has-error");
		$("label[for='client_description']").parent().html("");		
	});

	$('#technical_description, #client_description, #start_time, #date, #phase_id, #project_id, #client_id').change(function(){
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
	
});
</script>