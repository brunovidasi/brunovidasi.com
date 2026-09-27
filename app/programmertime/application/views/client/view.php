<link type="text/css" href="<?php echo base_url('assets/js/pagination/paging.css'); ?>" rel="stylesheet" />
<script type="text/javascript" src="<?php echo base_url('assets/js/pagination/paging.js'); ?>"></script>

<p class="page_title"  style="float:left;">Client - <?php echo $client->name ?></p> <br>

<?php
	$can_create_user = $this->session->userdata('can_create_user');
	$can_edit_user = $this->session->userdata('can_edit_user');
	$can_log_time = $this->session->userdata('can_log_time');
	$can_log_payment = $this->session->userdata('can_log_payment');
	$can_create_project = $this->session->userdata('can_create_project');
	$can_edit_project = $this->session->userdata('can_edit_project');
	$can_create_client = $this->session->userdata('can_create_client');
	$can_edit_client = $this->session->userdata('can_edit_client');
	$can_send_report = $this->session->userdata('can_send_report');
?>

<div style="float:right;">

	<a class="btn btn-primary" title="Back to the client list" href="<?php echo base_url('client/'); ?>"><i class='glyphicon glyphicon-arrow-left'></i> View all</a>

	<?php if($can_edit_client){ ?>
		<a href="<?php echo base_url('client/edit/'.$client->client_id); ?>"><span class="btn btn-warning"><i class='glyphicon glyphicon-edit'></i> Edit</span></a>
		
		<?php if($this->session->userdata('access_level') == 1 || $this->session->userdata('access_level') == 2){ ?>

			<?php if($client->status == 'active'){ ?>
				<button class="btn btn-danger" title="Click to deactivate" id="deactivate"><i class='glyphicon glyphicon-remove'></i> Deactivate</button>
			<?php }else{ ?>
				<a class="btn btn-success" title="Click to activate" href="<?php echo base_url('client/change_status/active/'.$client->client_id); ?>"><i class='glyphicon glyphicon-ok'></i> Activate</a>
			<?php } ?>

		<?php } ?>
	<?php } ?>

	<?php if($this->session->userdata('access_level') == 1 || $this->session->userdata('access_level') == 2){ ?>
		<button id="delete_permanently" class="btn btn-danger"><i class='glyphicon glyphicon-trash'></i></button>
	<?php } ?>

	<script>
		reset = function () {
			alertify.set({
				labels : {
					ok     : "Delete the client <?php echo $client->name; ?>",
					cancel : "Never mind"
				},
				delay : 5000,
				buttonReverse : false,
				buttonFocus   : "cancel"
			});
		};

		reset_deactivate = function () {
			alertify.set({
				labels : {
					ok     : "Deactivate the client <?php echo $client->name; ?>",
					cancel : "Never mind"
				},
				delay : 5000,
				buttonReverse : false,
				buttonFocus   : "cancel"
			});
		};

		$("#deactivate").click(function(){
			reset_deactivate();

			var text = `<strong>Request to deactivate client <?php echo $client->name; ?></strong>
			<br /><br />
			Deactivating a client changes all of its projects that are in progress to "Cancelled".
			<br /><br />
			This client has:<br />
			<?php $suffix_project = ($projects->num_rows() == 1) ? ' project' : ' projects'; echo $projects->num_rows() . $suffix_project; ?><br />
			<?php $suffix_project_active = ($active_projects->num_rows() == 1) ? ' project' : ' projects'; echo $active_projects->num_rows() . $suffix_project_active . ' active'; ?>

			<br /><br />

			Are you sure you want to deactivate client <?php echo $client->name; ?>, ID <?php echo $client->client_id; ?>? <br /><br />
			`;

			alertify.confirm(text, function (e){
				if (e) {
					var url = '<?php echo base_url("client/change_status/inactive/".$client->client_id); ?>';
				
					if(url){
						window.location = url;
					}
					
				} else {
					alertify.error("Client not deactivated.");
				}
			});
			return false;
		});

		$("#delete_permanently").click(function(){
			reset();

			var text = `<strong>Request to permanently delete client <?php echo $client->name; ?></strong>
			<br /><br />
			Deleting a client can be a little dangerous. <br /><br />
			By deleting a client you accept that everything related to it will be erased from the system, 
			including the hours logged by your staff, its projects and all information about those projects.
			<br /><br />
			It may be better to just <a href="<?php echo base_url('client/change_status/inactive/'.$client->client_id); ?>">deactivate the client</a>.
			<br /><br />
			This client has:<br />
			<?php $suffix_project = ($projects->num_rows() == 1) ? ' project' : ' projects'; echo $projects->num_rows() . $suffix_project; ?><br />
			<?php $suffix_project_active = ($active_projects->num_rows() == 1) ? ' project' : ' projects'; echo $active_projects->num_rows() . $suffix_project_active . ' active'; ?>

			<br /><br />

			Are you sure you want to delete client <?php echo $client->name; ?>, ID <?php echo $client->client_id; ?> and all of its projects? <br /><br />
			<strong>This action cannot be undone.</strong>
			
			`;

			alertify.confirm(text, function (e){
				if (e) {
					var url = '<?php echo base_url("client/delete/".$client->client_id); ?>';
				
					if(url){
						window.location = url;
					}
					
				} else {
					alertify.error("Client not deleted.");
				}
			});
			return false;
		});
	</script>


</div>
<br><br><br>

<?php
	require('application/views/includes/message.php');
	if(validation_errors() != ''){
	echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	}
?>

<div class="panel panel-default">
<div class="panel-heading" style="<?php echo ($client->status == 'inactive') ? 'background-color: #f2dede;' : ''; ?>">About <?php echo $client->name; ?></div>
<table class="table table-bordered table-condensed">

	<tr>		
		<td width="150px"><strong title="Full name">Name:</strong></td>
			<td><?php echo $client->name; ?></td>
		
		<td width="130px"><strong title="Email">Email:</strong></td>
			<td><a href="mailto:<?php echo $client->email; ?>"><?php echo $client->email; ?></a></td>
		
		<td width="90px"><strong title="Website">Website:</strong></td>
			<td><a href="<?php echo $client->website; ?>" target="_blank"><?php echo $client->website; ?></a></td>
	</tr>
	
	<tr>
		<td><strong title="Legal name">Legal Name:</strong></td>
			<td><?php echo $client->legal_name; ?></td>
		
		<td><strong title="Company registration number">Company No.:</strong></td>
			<td><?php if(!empty($client->company_tax_id)){ echo $client->company_tax_id; } ?></td>
		
		<td><strong title="Tax ID">Tax ID:</strong></td>
			<td><?php if(!empty($client->tax_id)){ echo $client->tax_id; } ?></td>
	</tr>
	
	<tr>
		<td><strong title="Phone">Phone:</strong></td>
			<td><?php if(!empty($client->phone)){ echo $client->phone; } ?></td>
		
		<td><strong title="Mobile">Mobile:</strong></td>
			<td><?php if(!empty($client->mobile)){ echo $client->mobile; } ?></td>
		
		<td><strong title="Date the client was added">Created:</strong></td>
			<td><?php echo fdatetime($client->created_at, "/"); ?></td>
	</tr>
	
	<tr>
		<td><strong title="Number of projects for this client">Total Projects:</strong></td>
			<td><?php $suffix_project = ($projects->num_rows() == 1) ? ' project' : ' projects'; echo $projects->num_rows() . $suffix_project; ?></td>
		
		<td><strong title="Number of this client's projects in progress">Active Projects:</strong></td>
			<td><?php $suffix_project_active = ($active_projects->num_rows() == 1) ? ' project' : ' projects'; echo $active_projects->num_rows() . $suffix_project_active; ?></td>
		
		

		<td><strong title="Hours spent on this client's projects">Hours:</strong></td>
			<td><?php echo $project_hours; ?></td>
	</tr>

	<tr>
		<td><strong title="Address">Address:</strong></td>
			<td colspan="3"><?php echo ($client->address) ? $client->address.', '.$client->address_number.' '.$client->address_line2.' - '.$client->address_district.' - '.$client->address_city.' - '.$client->address_state : ""; ?></td>
		
		<td><strong title="Postcode">Postcode:</strong></td>
			<td><?php echo $client->address_postcode; ?></td>
		
	</tr>

	<tr>
		<td><strong title="Contact">Contact:</strong></td>
			<td colspan="3"><?php echo ($client->contact_name) ? $client->contact_name.' - <a href="mailto:'.$client->contact_email.'">'.$client->contact_email.'</a> - '.$client->contact_phone : ""; ?></td>
		
		<td><strong title="Client status">Status:</strong></td>
			<td>
				<?php if($client->status == 'active'){ ?>
					<label class="label label-success">Active</label>
				<?php }else{ ?>
					<label class="label label-danger">Inactive</label>
				<?php } ?>
			</td>
	</tr>

</table>
</div>

<div class="panel panel-default">
	<div class="panel-heading">Projects for <?php echo $client->name; ?></div>
	<table class="table table-hover table-condensed" id="table_projects">
		<tr>
			<th width="80px" style="text-align:center;">ID</th>
			<th width="80px" style="text-align:center;">Priority</th>
			<th width="80px" style="text-align:center;">Status</th>
			<th width="">Project Name</th>
			<th width="220px">Type</th>
			<th width="" style="text-align:center;">Entries</th>
			<th width="" style="text-align:center;">Hours</th>
			<th width="80px">Date</th>
			<th width="110px">Deadline</th>
			<th width="">Owner</th>
			<th width="180px"></th>
		</tr>
		
		<?php
	
	if($projects->num_rows() == 0){
		echo '<tr><td colspan="11" align="center"><strong>'. $client->name .' has no projects.</strong></td></tr>';
	}
	
	foreach($projects->result() as $project){
		$class_tr = "";
		$danger = "";
		
		if($project->deadline <= date('Y-m-d H:i:s')){
			$class_tr = 'danger';
			$danger = 'danger';
		}
		
		if($project->status == 'paused')
			$class_tr = 'warning';
		elseif($project->status == 'cancelled')
			$class_tr = 'danger';
		
	?>
	
	<tr class="<?php echo $class_tr; ?>" id="context_<?php echo $project->project_id; ?>">
		
		<td align="center">
			<a href="<?php echo base_url('project/view/'. $project->project_id); ?>"><?php echo '# ' . $project->project_id; ?></a>
		</td>
		
		<td align="center">
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
		
		<td align="center">
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

			elseif($project->status == 'cancelled'){
				?>
					<input type='image' width="20px" alt='Cancelled' title='Cancelled' overdue='<?php echo ($danger == 'danger') ? "yes" : "no"; ?>' src='<?php echo base_url('assets/images/system/cancelled.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'status', '<?php echo $project->project_id; ?>');" />
				<?php
			}
			
			elseif($project->status == 'completed'){
				?>
					<input type='image' width="20px" alt='Completed' title='Completed' overdue='<?php echo ($danger == 'danger') ? "yes" : "no"; ?>' src='<?php echo base_url('assets/images/system/completed.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'status', '<?php echo $project->project_id; ?>');" />
				<?php
			}
		?>
		</td>
		
		<td><?php echo $project->name; ?></td>
		<td><?php echo  $project->project_type; ?></td>
		<td align="center"><?php echo  $project->time_entry_count; ?></td>
		<td align="center"><?php echo  $project->total_hours; ?></td>
		
		<td><?php echo '<span class="label label-primary">' . fdate($project->start_date, "/") . '</span>';?></td>
		
		<td>
		<?php
			$label_deadline = "";
			if($project->deadline <= date('Y-m-d H:i:s')){
				if($project->status == 'completed')
					$label_deadline = "label-primary";
				else
					$label_deadline = "label-danger";
			}else{
				$label_deadline = "label-warning";
			}
			
			echo '<span class="label '. $label_deadline .'" id="deadline">' . fdatetime($project->deadline,"/") . '</span>';
		?>
		</td>
		
		<td>
			<a href="<?php echo base_url('user/view/'.$project->owner_id); ?>" id="a-popover-<?php echo $project->project_id; ?>">
				<?php echo short_name($project->owner_name, 0); ?>
			</a>

			<div id="div-popover-<?php echo $project->project_id; ?>" class="hide">
				<div style="width:80px;">
					<img src="<?php echo base_url('assets/images/users/'.$project->owner_image); ?>" class="img-thumbnail" style="background-color:<?php echo $project->owner_color; ?>;">
				</div>
			</div>

			<script type="text/javascript">
				$('#a-popover-<?php echo $project->project_id; ?>').popover({
					trigger: 'hover',
					placement: 'top',
					html: true,
					content: $('#div-popover-<?php echo $project->project_id; ?>').html()
				});
			 </script>
		</td>
		
		<td align="right">
			<a class="btn btn-xs btn-primary" alt="View Project" title="View Project" href="<?php echo base_url('project/view/'. $project->project_id); ?>" id="view_project">
				<i class='glyphicon glyphicon-th-large'></i>
			</a>

			<?php if($can_create_project){ ?>
				<a class="btn btn-xs btn-info" alt="Create Report" title="Create Report" href="<?php echo base_url('report/generate/'. $project->project_id); ?>" id="generate_report">
					<i class='glyphicon glyphicon-file'></i>
				</a>
			<?php } ?>
			
			<?php if($can_edit_project){ ?>
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
			
			<?php if($can_edit_project){ ?>
				<span class="btn btn-xs btn-danger" alt="Remove Project" title="Remove Project" id="delete_project_<?php echo $project->project_id; ?>">
					<i class='glyphicon glyphicon-remove'></i>
				</span>
			<?php } ?>
		</td>
		
	</tr>
	
	<script>	
	$(function(){
		$.contextMenu({
			selector: '#context_<?php echo $project->project_id; ?>', 
			
			callback: function(key, options) {
				
				if(key == "view"){
					var url = '<?php echo base_url("project/view/".$project->project_id); ?>';
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "edit"){
					var url = '<?php echo base_url("project/edit/".$project->project_id); ?>';
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "delete"){
					reset();
					alertify.confirm("Deleting this project will also delete all of its time entries, payments and everything else in it. Are you sure you want to delete this project?", function (e) {
						if (e) {
							var url = '<?php echo base_url("project/delete/".$project->project_id); ?>';
						
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
					var url = '<?php echo base_url("time_entry/start/".$project->project_id); ?>';
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "finance"){
					var url = '<?php echo base_url("finance/create/".$project->project_id); ?>';
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
				<?php if($can_log_time && $project->status != 'completed'){ ?>
				"time_entry": {name: "Log Time", icon: "time"},
				<?php } ?>
				<?php if($can_log_payment && $project->status != 'completed'){ ?>
				"finance": {name: "Add Payment", icon: "usd"},
				<?php } ?>
			}
		});
	});

	</script>
	
	<?php } ?>

  </table>
  
</div>

<?php if($projects->num_rows() > 10){ ?>
	<div id="pagination_projects" style="display:inline"></div>

	<script>
	var pager = new Pager('table_projects', 10);
	pager.init();
	pager.showPageNav('pager', 'pagination_projects');
	pager.showPage(1);
	</script>
<?php } ?>