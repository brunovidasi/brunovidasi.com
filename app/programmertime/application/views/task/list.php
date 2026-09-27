<p class="page_title" style="float:left;"><?php 

$session_id = $this->session->userdata('id');

if($session_id == $user_id && $project_id == 0)
	echo lang('title_task_list');
elseif($session_id == $user_id && $project_id > 0)
	echo 'My Tasks - '.$project;
elseif($user_id == 0 AND $project_id > 0)
	echo 'Tasks - '.$project;
elseif($user_id > 0 AND $project_id == 0)
	echo 'Tasks - '.$user;
elseif($user_id > 0 AND $project_id > 0)
	echo 'Tasks - ' . $user . ' - ' . $project;
else
	echo 'Tasks';


?></p> 

<div class="" style="float:right; margin-bottom:10px; width:50%;">

	<form action="<?php print base_url('task/list'); ?>" method="post" name="filter_form" id="filter_form" class="form-inline" style="display:inline; float:right; margin-top:26px; width:628px;">
		
		<input type="text" name="term" placeholder="<?php echo lang('placeholder_filter_task'); ?>" class="form-control" value="<?php print $this->session->userdata('c_term'); ?>" style="width:500px; float:left;"/>
		<a href="<?php echo base_url('task/create/'.$project_id.'/'.$user_id); ?>" title="<?php echo lang('alt_create_task'); ?>" class="btn btn-primary" style="float:right; margin-right: 0px;"><i class='glyphicon glyphicon-plus'></i> <?php echo lang('btn_new'); ?></a>
		<button type="submit" title="<?php echo lang('alt_btn_search_task'); ?>" class="btn btn-primary" style="float:right; margin-right: 5px;"><i class='glyphicon glyphicon-search'></i></button>
	
	</form>
</div>

<br><br><br><br>

<?php
	$can_log_time = $this->session->userdata('can_log_time');

	require('application/views/includes/message.php');
	if(validation_errors() != '')
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
?>
	
<table width="100%" class="table table-hover table-condensed" style="padding:10px;">
	
	<tr>
		<th width="80px" style="text-align:center;"><?php echo lang('table_status'); ?></th>
		<th width=""><?php echo lang('table_name'); ?></th>
		<th width=""><?php echo lang('table_project'); ?></th>
		<th width=""><?php echo lang('table_owner'); ?></th>
		<th width=""><?php echo lang('table_hours_spent'); ?></th>
		<th width=""><?php echo lang('table_estimated_hours'); ?></th>
		<!-- <th width=""><?php echo lang('table_percentage'); ?></th> -->
		<th width=""><?php echo lang('table_deadline'); ?></th>
		<th width="120px" style="text-align:right;"></th>		
	</tr>
	
	<?php if($task_count == 0){ ?>
		<tr><td colspan="10" style="text-align:center;"><?php echo lang('no_tasks'); ?></td></tr>
	<?php } ?>
	
	<?php foreach($tasks as $task){ 

		$class_tr = "";
		$danger = "";
		
		if($task->due_date <= date('Y-m-d H:i:s')){
			$class_tr = 'danger';
			$danger = 'danger';
		}
		
		if($task->status == 'in_progress')
			$class_tr = 'info';

		if($task->status == 'cancelled')
			$class_tr = 'danger';
		
		if($task->status == 'completed')
			$class_tr = '';

	?>


	<tr class="<?php echo $class_tr; ?>" id="context_<?php echo $task->project_id; ?>">

		<td align="center">
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
		

		<td>
			<a href="<?php echo base_url('task/view/'.$task->task_id); ?>"><?php echo $task->name; ?></a>
		</td>

		<td>
			<a href="<?php echo base_url('project/view/'.$task->project_id); ?>"><?php echo $task->project_name; ?></a>
		</td>

		<td>
			<a href="<?php echo base_url('user/view/'.$task->owner_id); ?>" id="a-popover-<?php echo $task->task_id; ?>">
				<?php echo short_name($task->owner, 0); ?>
			</a>

			<div id="div-popover-<?php echo $task->task_id; ?>" class="hide">
				
				<div style="width:80px;">
					<img src="<?php echo base_url('assets/images/users/'.$task->owner_image); ?>" class="img-thumbnail" style="background-color:<?php echo $task->owner_color; ?>;">
				</div>
			</div>

			<script type="text/javascript">
				
					$('#a-popover-<?php echo $task->task_id; ?>').popover({
						trigger: 'hover',
						placement: 'top',
						html: true,
						content: $('#div-popover-<?php echo $task->task_id; ?>').html()
					});
			   
			 </script>
		</td>

		<td>
			<?php echo $task->total_hours; ?>
		</td>

		<td>
			<?php echo ($task->hours < 10) ? '0'. $task->hours . ':00' : $task->hours . ':00'; ?>
		</td>

		<!--<td>
			<?php echo $task->percentage; ?>
		</td>-->

		<td>
			<?php echo fdatetime($task->due_date, '/'); ?>
		</td>

		<td style="text-align:right;">
			<a class="btn btn-xs btn-primary" href="<?php echo base_url('task/view/'. $task->task_id); ?>"><i class="glyphicon glyphicon-th-large"></i></a>
			<a class="btn btn-xs btn-info" href="<?php echo base_url('time_entry/start/'.$task->project_id.'/'. $task->task_id); ?>"><i class="glyphicon glyphicon-time"></i></a>
			<a class="btn btn-xs btn-warning" href="<?php echo base_url('task/edit/'. $task->task_id); ?>"><i class="glyphicon glyphicon-edit"></i></a>
			<?php if($this->session->userdata('access_level') == 1 || $this->session->userdata('access_level') == 2){ ?>
				<a class="btn btn-xs btn-danger" href="<?php echo base_url('task/delete/'. $task->task_id); ?>"><i class="glyphicon glyphicon-remove"></i></a>
			<?php } ?>
		</td>
	</tr>
	
	<?php } ?>

</table>
	
		<div style="text-align:center;"><?php print $pagination; ?></div>
	<br>