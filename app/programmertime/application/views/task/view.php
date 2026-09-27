<script src="<?php echo base_url('assets/js/jquery.mousewheel.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/timeentry/jquery.timeentry.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/dateentry/jquery.dateentry.js'); ?>" type="text/javascript"></script>

<p class="page_title" style="float:left">Task: <?php echo $task->name; ?></p> <br>


<div style="float:right;">

	<a class="btn btn-primary" title="View my tasks" href="<?php echo base_url('task/list/'.$this->session->userdata('id')); ?>"><i class="glyphicon glyphicon-arrow-left"></i> View my tasks</a>
	<a class="btn btn-primary" title="View this project's tasks" href="<?php echo base_url('task/list/0/'.$task->project_id); ?>">View this project's tasks</a>
	<a class="btn btn-info" title="New Task" href="<?php echo base_url('task/create/'.$task->project_id.'/'.$task->owner_id); ?>"><i class="glyphicon glyphicon-plus"></i> New Task</a>
	<a class="btn btn-info" title="Log Time" href="<?php echo base_url('time_entry/start/'.$task->project_id.'/'. $task->task_id); ?>"><i class="glyphicon glyphicon-time"></i></a>
	<a href="<?php echo base_url('task/edit/'.$task->task_id); ?>"><span class="btn btn-warning" title="Edit Task"><i class='glyphicon glyphicon-edit'></i> <?php echo lang('btn_edit'); ?></span></a>

</div><br><br><br>

<?php require('application/views/includes/message.php'); ?>
<!--
<pre>
<?php print_r($task); ?>
</pre>
-->
<div class="col-lg-6">

	<div class="jumbotron">
	  <p><?php echo $task->description; ?></p>
	</div>

	<?php
		$class_tr = "";
		$danger = "";
		
		if($task->due_date <= date('Y-m-d H:i:s')){
			$class_tr = 'danger';
			$danger = 'danger';
		}

		if($task->status == 'cancelled')
			$class_tr = 'danger';
		
		if($task->status == 'completed')
			$class_tr = '';
	?>

	<table class="table table-condensed">
		<tr>
			<th>Project</th>
			<td><a href="<?php echo base_url('project/view/'.$task->project_id); ?>"><?php echo $task->project_name; ?></a></td>
		</tr>

		<tr>
			<th>Client</th>
			<td><a href="<?php echo base_url('client/view/'.$task->client_id); ?>"><?php echo $task->client_name; ?></a></td>
		</tr>

		<tr>
			<th>Phase</th>
			<td><?php echo $task->phase; ?></td>
		</tr>

		<tr>
			<th>Owner</th>
			<td><a href="<?php echo base_url('user/view/'.$task->owner_id); ?>"><?php echo $task->owner; ?></a></td>
		</tr>

		<tr>
			<th>Created By</th>
			<td><a href="<?php echo base_url('user/view/'.$task->created_by); ?>"><?php echo $task->created_by_name; ?></a></td>
		</tr>

		<tr>
			<th>Created</th>
			<td><?php echo fdatetime($task->created_at, "/"); ?></td>
		</tr>

		<tr>
			<th>Deadline</th>
			<td><?php echo fdatetime($task->due_date, "/"); ?></td>
		</tr>

		<tr class="<?php echo $class_tr; ?>">
			<th>Status</th>
			<td>
				<?php 
					if($task->status == 'not_started'){
						?>
							<input type='image' width="20px" alt='<?php echo lang('status_not_started'); ?>' title='<?php echo lang('status_not_started'); ?>' overdue='<?php echo ($danger == 'danger') ? "yes" : "no"; ?>' src='<?php echo base_url('assets/images/system/dot_blue.png'); ?>' onclick="taskStatus(this, '<?php echo base_url('assets/'); ?>', 'task', 'task_id', 'status', '<?php echo $task->task_id; ?>');" />
						<?php
					}
					
					elseif($task->status == 'in_progress'){
						?>
							<input type='image' width="20px" alt='<?php echo lang('status_in_progress'); ?>' title='<?php echo lang('status_in_progress'); ?>' overdue='<?php echo ($danger == 'danger') ? "yes" : "no"; ?>' src='<?php echo base_url('assets/images/system/dot_green.png'); ?>' onclick="taskStatus(this, '<?php echo base_url('assets/'); ?>', 'task', 'task_id', 'status', '<?php echo $task->task_id; ?>');" />
						<?php
					}
					
					elseif($task->status == 'completed'){
						?>
							<input type='image' width="20px" alt='<?php echo lang('status_completed'); ?>' title='<?php echo lang('status_completed'); ?>' overdue='<?php echo ($danger == 'danger') ? "yes" : "no"; ?>' src='<?php echo base_url('assets/images/system/completed.png'); ?>' onclick="taskStatus(this, '<?php echo base_url('assets/'); ?>', 'task', 'task_id', 'status', '<?php echo $task->task_id; ?>');" />
						<?php
					}

					elseif($task->status == 'cancelled'){
						?>
							<input type='image' width="20px" alt='<?php echo lang('status_cancelled'); ?>' title='<?php echo lang('status_cancelled'); ?>' overdue='<?php echo ($danger == 'danger') ? "yes" : "no"; ?>' src='<?php echo base_url('assets/images/system/cancelled.png'); ?>' onclick="taskStatus(this, '<?php echo base_url('assets/'); ?>', 'task', 'task_id', 'status', '<?php echo $task->task_id; ?>');" />
						<?php
					}
				?>
			</td>
		</tr>

	</table>

</div>

<div class="col-lg-6">

	<table class="table table-condensed">
		<tr>
			<th>Description</th>
			<th>Date</th>
			<th>Start</th>
			<th>End</th>
			<th>User</th>
			<th>Total</th>
		</tr>

		<?php foreach($task->hours as $time){ ?>
			<tr>
				<td><?php echo $time->technical_description;  ?></td>
				<td><?php echo fdate($time->date, "/");?></td>
				<td><?php echo strip_seconds($time->start_time);  ?></td>
				<td><?php echo strip_seconds($time->end_time);  ?></td>
				<td><a href="<?php echo base_url('user/view/'.$time->user_id); ?>"><?php echo short_name($time->user, 0); ?></a></td>
				<td><?php echo strip_seconds($time->total_time); ?></td>
			</tr>
		<?php } ?>

		<tr>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td align="right">Total:</td>
			<td>
				<?php if($task->total_hours > $task->estimated_hours){ ?>
					<strong style="color:red;"><?php echo $task->total_hours; ?></strong>
				<?php }else{ ?>
					<strong><?php echo $task->total_hours; ?></strong>
				<?php } ?>
			</td>
		</tr>

		<tr>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td align="right">Estimated:</td>
			<td><strong><?php echo $task->estimated_hours; ?></strong></td>
		</tr>
	</table>

</div>