<link href="<?php echo base_url('assets/js/alertify/themes/alertify.ptime.css'); ?>" rel="stylesheet" type="text/css" id="toggleCSS"/>

<p class="page_title" style="float:left;">Project List</p>

<div class="" style="float:right; margin-bottom:10px; width:50%;">
	
	<form action="<?php print base_url('project/grid'); ?>" method="post" name="filter_form" id="filter_form" class="form-inline" style="display:inline; float:right; margin-top:26px; width:550px;">
	
		<input type="text" name="term" class="form-control" value="<?php print $this->session->userdata('term'); ?>" style="width:500px; float:left;"/>
		<button type="submit" title="Filter by project name" class="btn btn-primary" style="float:right; margin-right: 5px;"><i class='glyphicon glyphicon-search'></i></button>
		
	</form>
	
</div> <br><br><br><br>

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

	<div class="panel-group" id="accordion">
	
	<?php
	if($projects->num_rows() == 0){
		echo 'No results for this search.';
	}
	
	foreach($projects->result() as $project){
		$class_tr = "";
		$danger = "";
		
		if($project->deadline <= date('Y-m-d H:i:s')){
			$class_tr = 'danger';
			$danger = 'danger';
		}
		
		if($project->status == 'paused'){
			$class_tr = 'warning';
		}
		
		if($project->status == 'completed'){
			$class_tr = '';
		}
		
		// $class_th = "info";
		
		// if(!empty($class_tr)){
			// $class_th = $class_tr;
		// }
	?>
		<div class="panel panel-default">
			<div class="panel-heading">
				<h4 class="panel-title" style="font-size: 13px;"><a data-toggle="collapse" data-parent="#accordion" href="#collapse<?php echo $project->project_id ?>"><?php echo '# ' . $project->project_id . ' - ' . $project->name . ' ('. $project->project_type .') - ' . $project->client_name; ?></a></h4>
			</div>
			<div id="collapse<?php echo $project->project_id ?>" class="panel-collapse collapse">
				<div class="panel-body">
					
					<table class="table table-condesed" style="margin-bottom: 0px;">
						<tr><td rowspan="3" width="220px"><img src="http://placehold.it/250x100" /></td>
							<td width="140px"><strong>Project: </strong></td>
							<td><?php echo $project->name .' - '. $project->project_type; ?></td>
							
							<td width="140px"><strong>Start Date: </strong></td>
							<td width="150px"><?php echo fdate($project->start_date, "/"); ?></td></tr>
						
						<tr><td><strong>Client: </strong></td>
							<td><?php echo '<a href="'. base_url('client/view/'.$project->client_id) .'" alt="'. $project->client_status .'" target="_blank">' . $project->client_name . '</a>'; ?></td>
							
							<td><strong>Deadline: </strong></td>
							<td><?php echo fdatetime($project->deadline,"/"); ?></td></tr>
						
						<tr><td><strong>Owner: </strong></td>
							<td><?php echo '<a href="'. base_url('user/view/'.$project->owner_id) .'" title="'. $project->owner_status .'" target="_blank">' . $project->owner_name . '</a>'; ?></td>
							
							<td width="140px"><strong>End Date: </strong></td>
							<td><?php echo fdate($project->end_date, "/"); ?></td></tr></tr>
					</table>
					
					<table class="table table-condesed" style="margin-bottom: 0px;">
						<tr><th class="<?php #echo $class_th; ?>"><strong>Description: </strong></th></tr><tr><td><?php echo $project->description; ?></td></tr>
					</table>
					
					<?php if(!empty($project->notes)){ ?><table class="table table-condesed" style="margin-bottom: 0px;">
						<tr><th class="<?php #echo $class_th; ?>"><strong>Notes:</strong></th></tr><tr><td><?php echo $project->notes; ?></td></tr>
					</table><?php } ?>
						
				</div>
			</div>
			<div class="col-lg-12">
				<div class="row">
					<table class="table table-condesed col-lg-12" style="margin-bottom: 0px;">
						<tr class=" <?php echo $class_tr; ?>">
						
						<td align="center" width="80px" style="text-align:center;">
						<?php 
							if($project->priority == 'low'){
								?>
									<input type='image' width="20px" alt='Low' title='Low' src='<?php echo base_url('assets/images/system/star_grey.png'); ?>' onclick="priority(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'priority', '<?php echo $project->project_id; ?>');" />
								<?php
							}
							
							elseif($project->priority == 'normal'){
								?>
									<input type='image' width="20px" alt='Normal' title='Normal' src='<?php echo base_url('assets/images/system/star_black.png'); ?>' onclick="priority(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'priority', '<?php echo $project->project_id; ?>');" />
								<?php
							}
							
							elseif($project->priority == 'urgent'){
								?>
									<input type='image' width="20px" alt='Urgent' title='Urgent' src='<?php echo base_url('assets/images/system/star_red.png'); ?>' onclick="priority(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'priority', '<?php echo $project->project_id; ?>');" />
								<?php
							}
						?>
						</td>
						
						<td align="center" width="80px" style="text-align:center;">
						<?php 
							if($project->status == 'not_started'){
								?>
									<input type='image' width="20px" alt='Not Started' title='Not Started' overdue='<?php echo ($danger == 'danger') ? "yes" : "no"; ?>' src='<?php echo base_url('assets/images/system/dot_blue.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'status', '<?php echo $project->project_id; ?>');" />
								<?php
							}
							
							elseif($project->status == 'in_progress'){
								?>
									<input type='image' width="20px" alt='In Progress' title='In Progress' overdue='<?php echo ($danger == 'danger') ? "yes" : "no"; ?>' src='<?php echo base_url('assets/images/system/dot_green.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'status', '<?php echo $project->project_id; ?>');" />
								<?php
							}
							
							elseif($project->status == 'paused'){
								?>
									<input type='image' width="20px" alt='Paused' title='Paused' overdue='<?php echo ($danger == 'danger') ? "yes" : "no"; ?>' src='<?php echo base_url('assets/images/system/paused.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'status', '<?php echo $project->project_id; ?>');" />
								<?php
							}
							
							elseif($project->status == 'completed'){
								?>
									<input type='image' width="20px" alt='Completed' title='Completed' overdue='<?php echo ($danger == 'danger') ? "yes" : "no"; ?>' src='<?php echo base_url('assets/images/system/completed.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'status', '<?php echo $project->project_id; ?>');" />
								<?php
							}
						?>
						</td>
						<td align="right">
							<?php if($can_create_project){ ?>
								<span class="btn btn-xs btn-danger" alt="Remove Project" title="Remove Project" id="delete_project_<?php echo $project->project_id; ?>">
									<i class='glyphicon glyphicon-remove'></i>
								</span>
							<?php } ?>
							
							<?php if($can_create_project){ ?>
								<a class="btn btn-xs btn-warning" alt="Edit Project" title="Edit Project" href="<?php echo base_url('project/edit/'. $project->project_id); ?>" id="create_project">
									<i class='glyphicon glyphicon-edit'></i>
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
							
							<?php if($can_create_project){ ?>
								<a class="btn btn-xs btn-info" alt="Create Report" title="Create Report" href="<?php echo base_url('report/generate/'. $project->project_id); ?>" id="generate_report">
									<i class='glyphicon glyphicon-file'></i>
								</a>
							<?php } ?>
							
							<a class="btn btn-xs btn-primary" alt="View Project" title="View Project" href="<?php echo base_url('project/view/'. $project->project_id); ?>" id="view_project">
								<i class='glyphicon glyphicon-th-large'></i> View Project
							</a>
						</td>
						</tr>
					</table>
				</div>
			</div>
		</div><br>
	
	<script>	
	$(function(){
		$.contextMenu({
			selector: '#context_<?php echo $project->project_id; ?>', 
			
			callback: function(key, options) {
				
				if(key == "view"){
					var url = '<?php echo base_url('project/view/'.$project->project_id); ?>';
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "edit"){
					var url = '<?php echo base_url('project/edit/'.$project->project_id); ?>';
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "delete"){
					reset();
					alertify.confirm("Are you sure you want to delete this project?", function (e) {
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
				}
				
				if(key == "time_entry"){
					var url = '<?php echo base_url('time_entry/start/'.$project->project_id); ?>';
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "finance"){
					var url = '<?php echo base_url('finance/create/'.$project->project_id); ?>';
					if (url) {
						window.location = url;
					}
				}
				
			},
			
			items: {
				"view": {name: "View Project", icon: "paste"},
				<?php if($can_create_project){ ?>
				"edit": {name: "Edit Project", icon: "edit"},
				"delete": {name: "Delete Project", icon: "delete"},
				<?php } ?>
				"sep1": "---------",
				<?php if($can_log_time){ ?>
				"time_entry": {name: "Log Time", icon: "time"},
				<?php } ?>
				<?php if($can_log_payment){ ?>
				"finance": {name: "Add Payment", icon: "usd"},
				<?php } ?>
			}
		});
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
		alertify.confirm("Are you sure you want to delete this project?", function (e) {
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
	</script>
	
	<?php } ?>
	
	</div>
	
	<div style="text-align: center;"><?php print $pagination; ?></div>
