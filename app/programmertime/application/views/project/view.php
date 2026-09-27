<p class="page_title"  style="float:left;"><?php echo $project->name . ' - ' . $project->client; ?></p> <br>

<link type="text/css" href="<?php echo base_url('assets/js/pagination/paging.css'); ?>" rel="stylesheet" />
<script type="text/javascript" src="<?php echo base_url('assets/js/pagination/paging.js'); ?>"></script>
<script src="<?php echo base_url('assets/js/highcharts/js/highcharts.js'); ?>"></script>

<div style="float:right;">
	<?php if($can_edit_project){ ?>
		<a href="<?php echo base_url('project/edit/'.$project->project_id); ?>"><span class="btn btn-warning" title="Edit Project"><i class='glyphicon glyphicon-edit'></i> <?php echo lang('btn_edit'); ?></span></a>
	<?php } ?>

	<a class="btn btn-info" alt="<?php echo lang('btn_view_tasks'); ?>" title="<?php echo lang('btn_view_tasks'); ?>" href="<?php echo base_url('task/list/0/'. $project->project_id); ?>">
		<i class='glyphicon glyphicon-list-alt'></i>
	</a>
	
	<?php if($can_send_report){ ?>
		<a href="<?php echo base_url('report/generate/'.$project->project_id); ?>"><span class="btn btn-info" title="<?php echo lang('btn_generate_report'); ?>"><i class='glyphicon glyphicon-file'></i> <?php echo lang('btn_generate_report'); ?></span></a>
	<?php } ?>

	<?php if($can_log_time){ ?>
		<a class="btn btn-info <?php if($project->status == 'completed'){ echo "disabled"; } ?>" alt="<?php echo lang('btn_log_time'); ?>" title="<?php echo lang('btn_log_time'); ?>" href="<?php echo base_url('time_entry/start/'. $project->project_id); ?>" id="log_time_entry">
			<i class='glyphicon glyphicon-time'></i>
		</a>
	<?php } ?>
	
	<?php if($can_log_payment){ ?>
		<a class="btn btn-success <?php if($project->status == 'completed'){ echo "disabled"; } ?>" alt="<?php echo lang('btn_log_payment'); ?>" title="<?php echo lang('btn_log_payment'); ?>" href="<?php echo base_url('finance/create/'.$project->client_id.'/'.$project->project_id); ?>" id="log_payment">
			<i class='glyphicon glyphicon-usd'></i>
		</a>
	<?php } ?>
	
	<?php if($can_create_project){ ?>
		<span class="btn btn-danger" alt="Remove Project" title="Remove Project" id="delete_project_<?php echo $project->project_id; ?>">
			<i class='glyphicon glyphicon-remove'></i> <?php echo lang('btn_delete'); ?>
		</span>
	<?php } ?>
</div><br><br>

<?php
	require('application/views/includes/message.php');
	if(validation_errors() != '')
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	
?>

<table class="table table-bordered table-condensed">

	<tr class="<?php echo $class_tr; ?>">
	
		<td rowspan="7" width="200px">
			<div id="chart_time_entry" style="min-width: 200px; height: 200px; margin: 0 auto"></div>
		</td>
		
		<td colspan="4">
			<strong><?php echo '# ' . $project->project_id . ' - ' . $project->name . ' - ' . $project->type; ?></strong>
		</td>
		
		<td align="center" valign="middle" width="10%">
		<?php 
			if($project->status == 'not_started'){
				?> <input type='image' width="20px" alt='Not Started' title='Not Started' overdue='<?php echo ($danger == 'danger') ? "yes" : "no"; ?>' src='<?php echo base_url('assets/images/system/dot_blue.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'status', '<?php echo $project->project_id; ?>');" /><?php
			}
			
			elseif($project->status == 'in_progress'){
				?><input type='image' width="20px" alt='In Progress' title='In Progress' overdue='<?php echo ($danger == 'danger') ? "yes" : "no"; ?>' src='<?php echo base_url('assets/images/system/dot_green.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'status', '<?php echo $project->project_id; ?>');" /><?php
			}
			
			elseif($project->status == 'paused'){
				?><input type='image' width="20px" alt='Paused' title='Paused' overdue='<?php echo ($danger == 'danger') ? "yes" : "no"; ?>' src='<?php echo base_url('assets/images/system/paused.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'status', '<?php echo $project->project_id; ?>');" /><?php
			}
			
			elseif($project->status == 'completed'){
				?><input type='image' width="20px" alt='Completed' title='Completed' overdue='<?php echo ($danger == 'danger') ? "yes" : "no"; ?>' src='<?php echo base_url('assets/images/system/completed.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'status', '<?php echo $project->project_id; ?>');" /><?php
			}
		?>
		</td>
		
	</tr>
	
	<tr>
		<td><strong>Client:</strong></td>
		
		<td colspan="3"><a href="<?php echo base_url('client/view/'. $project->client_id) ?>"><?php echo $project->client . '</a> - <a href="mailto:'. $project->client_email .'">' . $project->client_email; ?></a></td>
		
		<td align="center" valign="middle">
		<?php 
			if($project->priority == 'low'){
				?><input type='image' width="20px" alt='Low' title='Low' src='<?php echo base_url('assets/images/system/star_grey.png'); ?>' onclick="priority(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'priority', '<?php echo $project->project_id; ?>');" /><?php
			}
			
			elseif($project->priority == 'normal'){
				?><input type='image' width="20px" alt='Normal' title='Normal' src='<?php echo base_url('assets/images/system/star_black.png'); ?>' onclick="priority(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'priority', '<?php echo $project->project_id; ?>');" /><?php
			}
			
			elseif($project->priority == 'urgent'){
				?><input type='image' width="20px" alt='Urgent' title='Urgent' src='<?php echo base_url('assets/images/system/star_red.png'); ?>' onclick="priority(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'priority', '<?php echo $project->project_id; ?>');" /><?php
			}
		?>
		</td>
	</tr>
	
	<tr>
		<td><strong>Owner:</strong></td>
		<td colspan="3"><a href="<?php echo base_url('user/view/'. $project->owner_id) ?>"><?php echo $project->owner . '</a> - <a href="mailto:'. $project->owner_email .'">' . $project->owner_email; ?></a></td>
		<td></td>
	</tr>
	
	<tr>
		<td><strong>Start:</strong></td>
		<td><?php echo fdate($project->start_date, '/'); ?></td>
		<td rowspan="4" colspan="4" width="50%"><?php echo $project->description; ?></td>
	</tr>
	
	<tr>
		<td><strong>Deadline:</strong></td>
		<td><?php echo fdatetime($project->deadline, '/'); ?></td>
	</tr>
	
	<tr>
		<td><strong>End:</strong></td>
		<td><?php if((!empty($project->end_date)) OR ($project->end_date != '0000-00-00')){ echo fdate($project->end_date, '/'); } ?></td>
	</tr>
	
	<tr>
		<td><strong>Link:</strong></td>
		<td><a href="<?php echo $project->link; ?>" target="_blank"><?php echo $project->link; ?></a></td>
	</tr>

</table>

<table width="100%" class="table table-bordered table-condensed" style="padding:0px;">

	<tr class="<?php echo $payment_class; ?>">
		<td width="150px"><strong>Project Days:</strong></td>
			<td><span rel="tooltip" title="Days since the project started"><?php echo count_days($project->start_date, $project->end_date); ?> days</span></td>
			
		<td width="150px"><strong>Project Hours:</strong></td>
			<td><span rel="tooltip" title="Hours spent on this project's time entries"><?php echo $hours_spent . ' hours spent'; ?></span></td>

		<td width="150px"><strong>Project Costs:</strong></td>
			<td><span rel="tooltip" title="How much the company spent on the project"><?php echo currency($finance->total_company); ?></span></td>
		
	</tr>

	<tr class="<?php echo $payment_class; ?>">

		<td width="150px"><strong>Company Profit:</strong></td>
			<td><span rel="tooltip" title="How much the company made from the project"><?php echo currency($finance->profit); ?></span></td>
			
		<td width="150px"><strong>Project Income:</strong></td>
			<td><span rel="tooltip" title="How much the client paid for the project"><?php echo currency($finance->total_client); ?></span></td>

		<td width="150px"><strong>Balance:</strong></td>
			<td align="middle">
			<?php if($finance->status == 'positive'){ ?>
				<?php if(empty($finance->profit)){ ?>
					<input type='image' rel="tooltip" width="18px" alt='Neutral' title='Neutral' src='<?php echo base_url('assets/images/system/dot_blue.png'); ?>' />
				<?php }else{ ?>
					<input type='image' rel="tooltip" width="18px" alt='Positive' title='Positive' src='<?php echo base_url('assets/images/system/dot_green.png'); ?>' />
				<?php } ?>
			<?php } else{  ?>
				<input type='image' rel="tooltip" width="18px" alt='Negative' title='Negative' src='<?php echo base_url('assets/images/system/dot_red.png'); ?>' />
			<?php } ?>
			</td>
	</tr>

</table>

<ul class="nav nav-pills" style="cursor:pointer;">
	<li class="active" id="chart_menu"><a>Charts</a></li>
	<li id="time_entry_menu"><a>Hours <?php if($time_entry_count > 0){ echo '<span class="badge">'. $time_entry_count .'</span>'; } ?></a></li>
	<li id="finance_menu"><a>Payments <?php if($payment_count > 0){ echo '<span class="badge">'. $payment_count .'</span>'; } ?></a></li>
	<li id="users_menu"><a>Team Members <?php if(!empty($involved)){ echo '<span class="badge">'. count($involved) .'</span>';} ?></a></li>
	<!-- <li id="image_menu"><a>Images <?php if($images->num_rows() > 0){ echo '<span class="badge">'. $images->num_rows() .'</span>';} ?></a></li> -->
	<li id="notes_menu"><a>Notes <?php if(!empty($project->notes)){ echo '<span class="badge">1</span>';} ?></a></li>
</ul>

<br>

<div id="control_chart" style="display:block;">
	<div id="chart_time_entry_details" style="min-width: 500px; height: 350px; margin: 0 auto"></div>
</div>

<div id="control_time_entry" style="display:none;">

<?php if($time_entries_in_progress->num_rows() != 0){ ?>
	
<table width="100%" class="table table-hover table-condensed" style="padding:10px;">
	
	<tr class="info"><td colspan="10"><strong>Running Time Entries</strong></td></tr>
	
	<tr>
		<th width="">Phase</th>
		<th width="">Technical Description</th>
		<th width="">Client Description</th>
		<th width="">User</th>
		<th width="">Date</th>
		<th width="">Start</th>
		<th width="" colspan="2">Time</th>
		<?php if($can_edit_project){ ?>
		<th width="40px"></th>
		<?php } ?>
	</tr>
	
	<?php foreach($time_entries_in_progress->result() as $time_entry){ ?>
		
	<tr>
		<td><?php echo $time_entry->phase; ?></td>
		<td><?php echo $time_entry->technical_description; ?></td>
		<td><?php echo $time_entry->client_description; ?></td>
		<td><a href="<?php echo base_url('user/view/'. $time_entry->user_id); ?>"><?php echo $time_entry->owner; ?></a></td>
		<td><?php echo fdate($time_entry->date, '/'); ?></td>
		<td><?php echo ftime($time_entry->start_time); ?></td>
		<td colspan="2"><?php 
		if($time_entry->date == date('Y-m-d')){
			echo calculate_hours(date('H:i:s'), $time_entry->start_time);
		}else{
			echo "<span style='color:red'>Stop</span>";
		}
		?></td>
		<?php if($can_edit_project){ ?>
		<td><span class="btn btn-xs btn-danger" id="delete_time_entry_<?php echo $time_entry->time_entry_id; ?>"><i class="glyphicon glyphicon-remove"></i></span></td>
		<?php } ?>
	</tr>
	
	<script>
		$("#delete_time_entry_<?php echo $time_entry->time_entry_id; ?>").click(function () {
			reset();
			alertify.confirm("Are you sure you want to delete this time entry?", function (e) {
				if (e) {
					var url = '<?php echo base_url('time_entry/delete/'.$time_entry->time_entry_id); ?>';
				
					if (url) {
						window.location = url;
					}
					
				} else {
					alertify.error("Time entry not removed.");
				}
			});
			return false;
		});
		</script>
	
	<?php } ?>
	
	</table>
	
<?php } ?>
	
	<table class="table table-hover" id="table_time_entry">
		<?php if($time_entries_in_progress->num_rows() != 0){ ?>
			<tr class="info"><td colspan="10"><strong>Completed Time Entries</strong></td></tr>
		<?php } ?>
		
		<tr>
			<th>Phase</th>
			<th>Technical Description</th>
			<th>Client Description</th>
			<th>User</th>
			<th>Date</th>
			<th>Start</th>
			<th>End</th>
			<th>Time</th>
			<?php if($can_edit_project){ ?>
			<th width="70px"></th>
			<?php } ?>
		</tr>
		
		<?php if($time_entries->num_rows() == 0){ ?>
			<tr><td colspan="9" style="text-align:center;">No time has been logged on this project.</td></tr>
		<?php }else{ ?>
		
		<?php foreach($time_entries->result() as $time_entry){ ?>
			
		<tr>
			<td><?php echo $time_entry->phase; ?></td>
			<td><?php echo $time_entry->technical_description; ?></td>
			<td><?php echo $time_entry->client_description; ?></td>
			<td><a href="<?php echo base_url('user/view/'. $time_entry->user_id); ?>"><?php echo $time_entry->owner; ?></a></td>
			<td><?php echo fdate($time_entry->date, '/'); ?></td>
			<td><?php echo ftime($time_entry->start_time); ?></td>
			<td><?php echo ftime($time_entry->end_time); ?></td>
			<td><?php echo calculate_hours($time_entry->end_time, $time_entry->start_time); ?></td>
			<?php if($can_edit_project){ ?>
			<td><a class="btn btn-xs btn-warning" href="<?php echo base_url('time_entry/edit/'. $time_entry->time_entry_id); ?>"><i class="glyphicon glyphicon-edit"></i></a>
				<span class="btn btn-xs btn-danger" id="delete_time_entry_<?php echo $time_entry->time_entry_id; ?>"><i class="glyphicon glyphicon-remove"></i></span></td>
			<?php } ?>
		</tr>
		
		<?php if($can_edit_project){ ?>
		<script>
		$("#delete_time_entry_<?php echo $time_entry->time_entry_id; ?>").click(function () {
			reset();
			alertify.confirm("Are you sure you want to delete this time entry?", function (e) {
				if (e) {
					var url = '<?php echo base_url('time_entry/delete/'.$time_entry->time_entry_id); ?>';
				
					if (url) {
						window.location = url;
					}
					
				} else {
					alertify.error("Time entry not removed.");
				}
			});
			return false;
		});
		</script>
		<?php } ?>
		
		<?php }
		} ?>
	
	</table>
	
	<?php if($time_entries->num_rows() > 10){ ?>
		<div id="pagination_time_entry"></div>
	<?php } ?>
	
</div>

<div id="control_finance" style="display:none;">
	<?php if($can_log_payment){ ?>
	<table class="table table-hover" id="table_payment">
	
		<tr>
			<th style="text-align:center;">Status</th>
			<th>Type</th>
			<th>Description</th>
			<th>Amount</th>
			<th>Amount Paid</th>
			<th>Paid By</th>
			<th>Invoiced On</th>
			<th>Paid On</th>
			<th width="100px"></th>
		</tr>
		
		<?php if($payments->num_rows() == 0){ ?>
			<tr><td colspan="9" style="text-align:center;">No payments have been added to this project.</td></tr>
		<?php }else{ ?>
		
		<?php foreach($payments->result() as $payment){ ?>
			
		<tr>
			<td style="text-align:center; width: 60px;"><?php 
			if($payment->status == 'paid'){ $status_finance = "completed.png"; }
			elseif($payment->status == 'unpaid'){ $status_finance = "dot_red.png"; }
			elseif($payment->status == 'invoiced'){ $status_finance = "dot_green.png"; }
			elseif($payment->status == 'partially_paid'){ $status_finance = "dot_blue.png"; }
			
			echo '<img src="'. base_url('assets/images/system/'. $status_finance) .'" width="18px" alt="'. ucfirst(str_replace("_"," ",$payment->status)) .'" title="'. ucfirst(str_replace("_"," ",$payment->status)) .'"/>'; 
			?></td>
			<td><?php
			if($payment->type == 'project_cost'){ echo 'Project Fee'; }
			if($payment->type == 'external_cost'){ echo 'External Cost'; }
			if($payment->type == 'other_cost'){ echo 'Other'; }
			?></td>
			<td><span title="<?php echo $payment->notes; ?>"><?php echo $payment->description; ?></span></td>
			<td><?php echo currency($payment->amount); ?></td>
			<td><?php echo currency($payment->amount_paid); ?></td>
			<td><?php echo ucfirst($payment->paid_by); ?></td>
			<td><?php echo fdatetime($payment->invoiced_date, '/'); ?></td>
			<td><?php echo fdatetime($payment->paid_date, '/'); ?></td>
			<td><a class="btn btn-xs btn-warning" href="<?php echo base_url('finance/edit/'. $payment->payment_id); ?>"><i class="glyphicon glyphicon-edit"></i></a>
				<a class="btn btn-xs btn-danger" id="delete_payment_<?php echo $payment->payment_id; ?>"><i class="glyphicon glyphicon-remove"></i></a></td>
		</tr>
		
		<?php if($can_edit_project){ ?>
		<script>
		$("#delete_payment_<?php echo $payment->payment_id; ?>").click(function () {
			reset();
			alertify.confirm("Are you sure you want to delete this payment?", function (e) {
				if (e) {
					var url = '<?php echo base_url('finance/delete/'.$payment->payment_id); ?>';
				
					if (url) {
						window.location = url;
					}
					
				} else {
					alertify.error("Payment not removed.");
				}
			});
			return false;
		});
		</script>
		
		<?php } ?>
		
		<?php } 
		} ?>
	
	</table>
	
	<div id="pagination_payment"></div>
	
	<?php } ?>

</div>

<div id="control_image" style="display:none;">
	
	<div id="content_images" class="col-lg-12">
	<?php foreach($images->result() as $image){ ?>
		<div class="image image-project col-lg-2 col-sm-3" id="image_id_<?php echo $image->image_id; ?>">
			<a href="<?php echo base_url('image/view/'. $image->image_id); ?>">
				<img src="<?php echo base_url('assets/images/projects/'. $image->image); ?>" alt="<?php echo $image->title; ?>" title="<?php echo $image->title; ?>" class="img-thumbnail" />
			</a>
		</div>
	<?php } ?>
	</div>

</div>

<div id="control_users" style="display:none;">
	<table class="table table-hover" id="table_involved">
	
	<tr>
		<th style="width:25px;"></th>
		<th>Name</th>
		<th>Role</th>
		<th>Login</th>
		<th>Email</th>
		<th title="Time entries on this project">Entries</th>
		<th title="Hours worked on this project">Hours</th>
		<th title="Share of the hours worked on this project">%</th>
		<th title="When the user last logged in">Last Login</th>
		<th style="text-align:center; width:40px;" title="User status">Status</th>
	</tr>
	
	<?php 
	if(count($involved) == 0){
		?><tr><td colspan="10" style="text-align:center;">No one has worked on this project yet.</td></tr><?php
	}
	
	foreach($involved as $involved_array){
		$member = $involved_array->row(); ?>
	
			<tr>
				<td style="text-align:center;"><a href="<?php echo base_url('user/view/'. $member->user_id); ?>"><img src="<?php echo base_url('assets/images/users/'. $member->image); ?>" class="img img-circle" style="width:25px;" /></a></td>
				<td><a href="<?php echo base_url('user/view/'. $member->user_id); ?>"><?php echo short_name($member->name); ?></a></td>
				<td><?php echo $member->role; ?></td>
				<td><?php echo $member->login; ?></td>
				<td><?php echo $member->email; ?></td>
				<td><?php echo $involved_array->time_entry_count; ?></td>
				<td><?php echo $involved_array->hours_worked; ?></td>
				<td><?php echo number_format($involved_array->percentage, 2, '.', ','); ?>%</td>
				<td><?php echo fdatetime($member->last_access, "/"); ?></td>
				<td style="text-align:center;"><?php echo ($member->status == 'active') ? '<img src="'. base_url('assets/images/system/dot_green.png') .'" alt="Active" title="Active" style="width:20px;">' : '<img src="'. base_url('assets/images/system/dot_red.png') .'" alt="Inactive" title="Inactive" style="width:20px;">'; ?></td>
			</tr>
		
	<?php } ?>
	</table>
	
	<div id="pagination_involved"></div>
</div>

<div id="control_notes" style="display:none;">
	<form action="<?php echo base_url('project/notes/'. $project->project_id) ?>" class="" method="post" name="form1" class="form1">
		<textarea name="notes" id="notes" class="form-control"><?php echo set_value('notes', $project->notes); ?></textarea>
		<br><button type="submit" name="submit" class="btn btn-primary">Save</button>
	</form>
</div>

<script src="<?php echo base_url('assets/js/texteditor/jquery-te-1.4.0.min.js');?>" type="text/javascript"></script>
<link rel="stylesheet" href="<?php echo base_url('assets/js/texteditor/jquery-te-1.4.0.css');?>" type="text/css">

<script>
$("#notes").jqte({ol: false, ul: false, format: false});	

$("#chart_menu").click(function() {
	$("#control_chart").show();
	$("#control_time_entry").hide();
	$("#control_finance").hide();
	$("#control_image").hide();
	$("#control_notes").hide();
	$("#control_users").hide();
	
	$("#chart_menu").attr('class', 'active');
	$("#time_entry_menu").attr('class', '');
	$("#finance_menu").attr('class', '');
	$("#image_menu").attr('class', '');
	$("#notes_menu").attr('class', '');
	$("#users_menu").attr('class', '');
});

$("#time_entry_menu").click(function() {
    $("#control_chart").hide();
	$("#control_time_entry").show();
	$("#control_finance").hide();
	$("#control_image").hide();
	$("#control_notes").hide();
	$("#control_users").hide();
	
	$("#chart_menu").attr('class', '');
	$("#time_entry_menu").attr('class', 'active');
	$("#finance_menu").attr('class', '');
	$("#image_menu").attr('class', '');
	$("#notes_menu").attr('class', '');
	$("#users_menu").attr('class', '');
});

$("#finance_menu").click(function() {
    $("#control_chart").hide();
	$("#control_time_entry").hide();
	$("#control_finance").show();
	$("#control_image").hide();
	$("#control_notes").hide();
	$("#control_users").hide();
	
	$("#chart_menu").attr('class', '');
	$("#time_entry_menu").attr('class', '');
	$("#finance_menu").attr('class', 'active');
	$("#image_menu").attr('class', '');
	$("#notes_menu").attr('class', '');
	$("#users_menu").attr('class', '');
});

$("#image_menu").click(function() { 
	$("#control_chart").hide();
	$("#control_time_entry").hide();
	$("#control_finance").hide();
	$("#control_image").show();
	$("#control_notes").hide();
	$("#control_users").hide();
	
	$("#chart_menu").attr('class', '');
	$("#time_entry_menu").attr('class', '');
	$("#finance_menu").attr('class', '');
	$("#image_menu").attr('class', 'active');
	$("#notes_menu").attr('class', '');
	$("#users_menu").attr('class', '');
});

$("#notes_menu").click(function() { 
    $("#control_chart").hide();
	$("#control_time_entry").hide();
	$("#control_finance").hide();
	$("#control_image").hide();
	$("#control_notes").show();
	$("#control_users").hide();
	
	$("#chart_menu").attr('class', '');
	$("#time_entry_menu").attr('class', '');
	$("#finance_menu").attr('class', '');
	$("#image_menu").attr('class', '');
	$("#notes_menu").attr('class', 'active');
	$("#users_menu").attr('class', '');
});

$("#users_menu").click(function() { 
    $("#control_chart").hide();
	$("#control_time_entry").hide();
	$("#control_finance").hide();
	$("#control_image").hide();
	$("#control_notes").hide();
	$("#control_users").show();
	
	$("#chart_menu").attr('class', '');
	$("#time_entry_menu").attr('class', '');
	$("#finance_menu").attr('class', '');
	$("#image_menu").attr('class', '');
	$("#notes_menu").attr('class', '');
	$("#users_menu").attr('class', 'active');
});

	reset = function () {
			$("toggleCSS").href = "<?php echo base_url('assets/js/alertify/themes/alertify.ptime.css'); ?>";
			alertify.set({
				labels : {
					ok     : "Delete",
					cancel : "Don't Delete"
				},
				delay : 5000,
				buttonReverse : false,
				buttonFocus   : "ok"
			});
		};
		
$("#delete_project_<?php echo $project->project_id; ?>").click(function () {
	reset();
	alertify.confirm("Deleting this project will also delete all of its time entries, payments and everything else in it. Are you sure you want to delete this project?", function (e) {
		if (e) {
			var url = '<?php echo base_url('project/delete/'.$project->project_id); ?>';
		
			if (url) {
				window.location = url;
			}
			
		} else {
			alertify.error("Project not removed.");
		}
	});
	return false;
});

<?php if($time_entries->num_rows() > 10){ ?>
var pager = new Pager('table_time_entry', 10);
pager.init();
pager.showPageNav('pager', 'pagination_time_entry');
pager.showPage(1);
<?php } ?>

<?php if($payments->num_rows() > 10){ ?>
var pager2 = new Pager('table_payment', 10);
pager2.init();
pager2.showPageNav('pager2', 'pagination_payment');
pager2.showPage(1);
<?php } ?>

<?php if(count($involved) > 10){ ?>
var pager3 = new Pager('table_involved', 10);
pager3.init();
pager3.showPageNav('pager3', 'pagination_involved');
pager3.showPage(1);
<?php } ?>

    $(function () {
        $("[rel='tooltip']").tooltip();
    });

$(function () {
    var chart;
    
    $(document).ready(function () {
        $('#chart_time_entry').highcharts({
            credits: false,
			chart: {
                plotBackgroundColor: null,
                plotBorderWidth: null,
                plotShadow: true
            },
            title: {
                text: ''
            },
            tooltip: {
        	    pointFormat: '<b>{point.percentage:.1f}%</b>'
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: false
                    },
                    showInLegend: false
                }
            },
            series: [{
                type: 'pie',
                name: 'Time entries',
                data: [
					<?php 
					if(!empty($percentage)){
						foreach($percentage as $key => $amount){
							echo '["' . $percentage[$key]['name'] . ' - ' . $percentage[$key]['time_entry_count'] . ' ' . '", ' . $percentage[$key]['percentage'] . "],";
						}
					}
					?>
                ]
            }]
        });
    });
});

$(function () {
        $('#chart_time_entry_details').highcharts({
			credits: false,
            chart: {
            },
            title: {
                text: 'Time Entries by Phase'
            },
            xAxis: {
				categories: [
				<?php 
					if(!empty($percentage)){
						foreach($percentage as $key => $amount){
							echo "'" . $percentage[$key]['name'] . "',";
						}
					}
				?>
				]
            },
			yAxis: {
				title: {
                    text: 'Time Entries'
                },
            },
			
            series: [
			<?php 
				if(!empty($user_time_entries)){
					foreach($user_time_entries as $key2 => $value2){
						echo "{
								type: 'column',
								name: '". $user_time_entries[$key2]['name'] ."',
								data: [";
						foreach($user_time_entries[$key2] as $key => $amount){
							if($key != 'name'){
								echo $user_time_entries[$key2][$key] . ",";
							}
						
						}
					
						echo "]},";
					}
				}
			?>
			
			{
                type: 'spline',
                name: 'Count',
                data: [
				<?php 
					if(!empty($percentage)){
						foreach($percentage as $key => $amount){
							echo $percentage[$key]['count'] . ",";
						}
					}					
				?>],
                marker: {
                	lineWidth: 2,
                	lineColor: Highcharts.getOptions().colors[3],
                	fillColor: 'white'
                }
            }]
        });
    });
    
</script>