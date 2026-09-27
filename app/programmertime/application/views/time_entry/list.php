<p class="page_title" style="float:left;"><?php echo lang('title_time_entry_list'); ?></p> <br><br><br>

<?php

	$can_log_time = $this->session->userdata('can_log_time');

	require('application/views/includes/message.php');
	if(validation_errors() != '')
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
?>
<?php if($time_entries_in_progress->num_rows() != 0){ ?>
	
<table width="100%" class="table table-hover table-condensed" style="padding:10px;">
	
	<tr class="info"><td colspan="10"><strong><?php echo lang('time_entries_in_progress'); ?></strong></td></tr>
	
	<tr>
		<th width=""><?php echo lang('table_project'); ?></th>
		<th width=""><?php echo lang('table_phase'); ?></th>
		<th width=""><?php echo lang('table_technical_description'); ?></th>
		<!-- <th width=""><?php echo lang('table_client_description'); ?></th> -->
		<th width=""><?php echo lang('table_user'); ?></th>
		<th width=""><?php echo lang('table_date'); ?></th>
		<th width=""><?php echo lang('table_start'); ?></th>
		<th width="" colspan="2"><?php echo lang('table_time'); ?></th>
		<th width="70px"></th>		
	</tr>
	
	<?php foreach($time_entries_in_progress->result() as $time_entry){ ?>
		
	<tr>
		<td><a href="<?php echo base_url('project/view/'. $time_entry->project_id); ?>"><?php echo $time_entry->project_name; ?></a></td>
		<td><?php echo $time_entry->phase; ?></td>
		<td><?php echo $time_entry->technical_description; ?></td>
		<!-- <td><?php echo $time_entry->client_description; ?></td> -->
		<td><a href="<?php echo base_url('user/view/'. $time_entry->user_id); ?>"><?php echo $time_entry->owner; ?></a></td>
		<td><?php echo fdate($time_entry->date, '/'); ?></td>
		<td><?php echo ftime($time_entry->start_time); ?></td>
		<td colspan="2"><?php if($time_entry->date == date('Y-m-d')){
			echo calculate_hours(date('H:i:s'), $time_entry->start_time);
		}else{
			echo "<span style='color:red'>".lang('close')."</span>";
		} ?></td>
		<td><a class="btn btn-xs btn-warning" href="<?php echo base_url('time_entry/edit/'. $time_entry->time_entry_id); ?>"><i class="glyphicon glyphicon-edit"></i></a>
			<a class="btn btn-xs btn-danger" href="<?php echo base_url('time_entry/delete/'. $time_entry->time_entry_id); ?>"><i class="glyphicon glyphicon-remove"></i></a></td>
	</tr>
	
	<?php } ?>
	
	</table>
	
<?php } ?>
	
	<table width="100%" class="table table-hover table-condensed" style="padding:10px;">
	
	<tr class="info"><td colspan="10"><strong><?php echo lang('time_entries_completed'); ?></strong></td></tr>
	
	<tr>
		<th width=""><?php echo lang('table_project'); ?></th>
		<th width=""><?php echo lang('table_phase'); ?></th>
		<th width=""><?php echo lang('table_technical_description'); ?></th>
		<!-- <th width=""><?php echo lang('table_client_description'); ?></th> -->
		<th width=""><?php echo lang('table_user'); ?></th>
		<th width=""><?php echo lang('table_date'); ?></th>
		<th width=""><?php echo lang('table_start'); ?></th>
		<th width=""><?php echo lang('table_end'); ?></th>
		<th width=""><?php echo lang('table_time'); ?></th>
		<th width="70px"></th>		
	</tr>
	
	<?php if($time_entries->num_rows() == 0){ ?>
		<tr><td colspan="10" style="text-align:center;"><?php echo lang('no_time_entries'); ?></td></tr>
	<?php } ?>
	
	<?php foreach($time_entries->result() as $time_entry){ ?>
		
	<tr>
		<td><a href="<?php echo base_url('project/view/'. $time_entry->project_id); ?>"><?php echo $time_entry->project_name; ?></a></td>
		<td><?php echo $time_entry->phase; ?></td>
		<td><?php echo $time_entry->technical_description; ?></td>
		<!-- <td><?php echo $time_entry->client_description; ?></td> -->
		<td><a href="<?php echo base_url('user/view/'. $time_entry->user_id); ?>"><?php echo $time_entry->owner; ?></a></td>
		<td><?php echo fdate($time_entry->date, '/'); ?></td>
		<td><?php echo ftime($time_entry->start_time); ?></td>
		<td><?php echo ftime($time_entry->end_time); ?></td>
		<td><?php echo calculate_hours($time_entry->end_time, $time_entry->start_time); ?></td>
		<td><a class="btn btn-xs btn-warning" href="<?php echo base_url('time_entry/edit/'. $time_entry->time_entry_id); ?>"><i class="glyphicon glyphicon-edit"></i></a>
			<a class="btn btn-xs btn-danger" href="<?php echo base_url('time_entry/delete/'. $time_entry->time_entry_id); ?>"><i class="glyphicon glyphicon-remove"></i></a></td>
	</tr>
	
	<?php } ?>

</table>
	
		<div style="text-align:center;"><?php print $pagination; ?></div>
	<br>