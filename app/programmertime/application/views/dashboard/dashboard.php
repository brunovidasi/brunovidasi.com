<link type="text/css" href="<?php echo base_url('assets/js/pagination/paging.css'); ?>" rel="stylesheet" />
<script type="text/javascript" src="<?php echo base_url('assets/js/pagination/paging.js'); ?>"></script>

<p class="page_title"  style="float:left;">Hi! How was your day? Let's get to our projects!</p> <br><br><br><br>

<?php
	$can_log_time = $this->session->userdata('can_log_time');
	$can_log_payment = $this->session->userdata('can_log_payment');
	$can_create_project = $this->session->userdata('can_create_project');
	$can_create_client = $this->session->userdata('can_create_client');
	$can_send_report = $this->session->userdata('can_send_report');

	require('application/views/includes/message.php');
	if(validation_errors() != ''){
	echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	}
?>

<div class="pull-left" style="width:58%">

	<div class="panel panel-default">
		<div class="panel-heading">My Recent Projects</div>
		
		<div class="panel-body">
			<table width="100%" class="table table-hover" id="table_projects">
				<tr>
					<th>ID</th>
					<th>Name</th>
					<th>Type</th>
					<th title="Project owner">Owner</th>
					<th title="Time entries you logged on this project">Entries</th>
					<th title="Hours you worked on this project">Hours</th>
					<th>Deadline</th>
					<th></th>
				</tr>
				
				<?php foreach($projects_involved as $project_involved){
				$project = $project_involved->row();  ?>
				<tr>
					<td><a href="<?php echo base_url('project/view/'.$project->project_id); ?>"># <?php echo $project->project_id; ?></a></td>
					<td><?php echo $project->name; ?></td>
					<td><?php echo $project->type; ?></td>
					<td><a href="<?php echo base_url('user/view/'.$project->owner_id); ?>"><?php echo $project->owner; ?></a></td>
					<td><?php echo $project_involved->time_entry_count; ?></td>
					<td><?php echo $project_involved->hours_worked; ?></td>
					<td><?php echo fdatetime($project->deadline, '/'); ?></td>
					<td>
						<a href="<?php echo base_url('project/view/'.$project->project_id); ?>" class="btn btn-primary btn-xs"><i class="glyphicon glyphicon-th-large"></i></a>
						<?php if($can_create_project){ ?>
							<a class="btn btn-xs btn-info" alt="Create Report" title="Create Report" href="<?php echo base_url('report/generate/'. $project->project_id); ?>" id="generate_report">
								<i class='glyphicon glyphicon-file'></i>
							</a>
						<?php } ?>
						
						<?php if($can_create_project){ ?>
							<a class="btn btn-xs btn-warning" alt="Edit Project" title="Edit Project" href="<?php echo base_url('project/edit/'. $project->project_id); ?>" id="create_project">
								<i class='glyphicon glyphicon-pencil'></i>
							</a>
						<?php } ?>
			
						<?php if($can_log_time){ ?>
							<a class="btn btn-xs btn-info <?php if($project->status == 'completed'){ echo "disabled"; } ?>" alt="Log Time" title="Log Time" href="<?php echo base_url('time_entry/start/'. $project->project_id); ?>" id="log_time_entry">
								<i class='glyphicon glyphicon-time'></i>
							</a>
						<?php } ?>
						
						<?php if($can_log_payment){ ?>
							<a class="btn btn-xs btn-success <?php if($project->status == 'completed'){ echo "disabled"; } ?>" alt="Add Payment" title="Add Payment" href="<?php echo base_url('finance/create/'. $project->project_id); ?>" id="log_payment">
								<i class='glyphicon glyphicon-usd'></i>
							</a>
						<?php } ?>
					</td>
				</tr>
				<?php } ?>
				
			</table>
			
			<?php if(count($projects_involved) > 5){ ?>
				<div id="pagination_projects" style="display:inline;"></div>

				<script>
				var pager = new Pager('table_projects', 5);
				pager.init();
				pager.showPageNav('pager', 'pagination_projects');
				pager.showPage(1);
				</script>
			<?php } ?>
		</div>
		
	</div>
	
	<button id="form-field-1" class="btn btn-primary" href="#time_entries_modal" data-toggle="modal" data-target="#time_entries_modal"></button>
	
	<div class="modal fade" id="time_entries_modal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<span type="button" class="close" data-dismiss="modal" aria-hidden="true"></span>
				<div class="modal-body">
					<iframe id="frame_modal" src="<?php echo base_url("test/modal/"); ?>" width="100%"  scrolling="auto" border="0" height="530px" style="border:0"></iframe>
				</div>
				
			</div>
		</div>
	</div>
	
</div>


<div class="pull-right" style="width:40%">
<div class="panel-group" id="accordion">
<div class="panel panel-default">

	<div class="panel-heading">
		<h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#collapse">General Notices</a> <span class="badge pull-right">3</span></h4>
	</div>
	
	<div id="collapse" class="panel-collapse collapse">
		<div class="panel-body">
			<table class="table">
			
				<tr>
					<td rowspan="2"><img src="http://placehold.it/100x100" /></td>
					<td><strong><a href="">Bruno Vieira</a>:</strong> Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS.</td>
					
				</tr>
				<tr>
					<td><span class="pull-right"><strong><i class="glyphicon glyphicon-time"></i> 20/05/2014 10:00</strong></span></td>
				</tr>
				
				<tr>
					<td rowspan="2"><img src="<?php echo base_url('assets/images/system/logo.png'); ?>" width="100px" height="100px"/></td>
					<td><strong>Automatic notice:</strong> The deadline for project Personal Site expires in 10 days.</td>
					
				</tr>
				<tr>
					<td><span class="pull-right"><strong><i class="glyphicon glyphicon-time"></i> 20/05/2014 10:00</strong></span></td>
				</tr>
				
				<tr>
					<td rowspan="2"><img src="http://placehold.it/100x100" /></td>
					<td><strong><a href="">Bruno Vieira</a>:</strong> Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS.</td>
					
				</tr>
				<tr>
					<td><span class="pull-right"><strong><i class="glyphicon glyphicon-time"></i> 20/05/2014 10:00</strong></span></td>
				</tr>
				
				
			</table>
		</div>
	</div>
	
	<div class="panel-body">
		There are 3 new notices since you last logged in to Programmer Time! <a data-toggle="collapse" data-parent="#accordion" href="#collapse">Click to view!</a>
	</div>
</div>

<div class="panel panel-default">

	<div class="panel-heading">
		<h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#collapse3">What am I working on now?</a> <?php if($open_time_entry->num_rows() == 1){ echo'<span class="badge pull-right">1</span>';} ?></h4>
	</div>
	
	<div id="collapse3" class="panel-collapse collapse in">
		<?php if($open_time_entry->num_rows() == 1){ $a_time_entry = $open_time_entry->row();?>
			
			<table class="table table-condensed">
			<tr>
				<td><strong>Project:</strong></td>
				<td><?php echo $a_time_entry->project_name; ?></td>
			</tr>
			<tr>
				<td><strong>Phase:</strong></td>
				<td><?php echo $a_time_entry->phase; ?></td>
			</tr>
			<tr>
				<td><strong>Technical Description:</strong></td>
				<td><?php echo $a_time_entry->technical_description; ?></td>
			</tr>
			<tr>
				<td><strong>Client Description:</strong></td>
				<td><?php echo $a_time_entry->client_description; ?></td>
			</tr>
		</table>
		<?php }else{
			#echo 'There is no open time entry right now.';
		} ?>
	</div>
	
	<div class="panel-body">
		<?php if($open_time_entry->num_rows() == 1){ $a_time_entry = $open_time_entry->row();?> <span class="pull-left"><strong><?php echo fdate($a_time_entry->date, "/") . ' - ' . ftime($a_time_entry->start_time); ?></strong><br> <?php echo $a_time_entry->phase . ' - ' . $a_time_entry->project_name; ?></span>
			<span class="pull-right"><a href="<?php echo base_url('time_entry'); ?>" class="btn btn-primary"><i class="glyphicon glyphicon-time"></i> Stop Timer</a>
			<a href="<?php echo base_url('time_entry'); ?>" class="btn btn-danger"><i class="glyphicon glyphicon-remove"></i> Delete Timer</a></span>
		<?php }else{ ?>
			<span class="pull-right"><a href="<?php echo base_url('time_entry'); ?>" class="btn btn-primary"><i class="glyphicon glyphicon-time"></i> Log Time</a></span>
		<?php } ?>
	</div>
</div>


<div class="panel panel-default">

	<div class="panel-heading">
		<h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#collapse2">My Notes</a></h4>
	</div>
	
	<div id="collapse2" class="panel-collapse collapse">
		<div class="panel-body">
			<label>What's on for today?</label>
			<form action="<?php echo base_url('user/notes') ?>" method="post" name="form1" class="form1">
				<textarea name="notes" class="form-control" id="notes" rows="6"><?php echo set_value('notes', 'My notes blah blah');  ?></textarea>
				<br><button type="submit" name="submit" class="btn btn-primary pull-right"><i class="glyphicon glyphicon-file"></i> Save Note</button>
			</form>
		</div>
	</div>
	
	<div class="panel-body">
		<strong>20/05/2014: </strong>My notes blah blah
	</div>
</div>

<div class="panel panel-default">

	<div class="panel-heading">
		<h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#collapse4">Another menu</a> <span class="badge pull-right">1</span></h4>
	</div>
	
	<div id="collapse4" class="panel-collapse collapse">
		<div class="panel-body">
			
		</div>
	</div>
	
	<div class="panel-body">
		<strong>20/05/2014 - 12:54 </strong> Some Project
	</div>
</div>


</div>

</div>

