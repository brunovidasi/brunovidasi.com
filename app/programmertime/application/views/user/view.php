<link type="text/css" href="<?php echo base_url('assets/js/pagination/paging.css'); ?>" rel="stylesheet" />
<script type="text/javascript" src="<?php echo base_url('assets/js/pagination/paging.js'); ?>"></script>

<script>
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
</script>

<p class="page_title"  style="float:left;">User Profile - <?php echo $user->name; ?></p> <br>

<?php
	$can_create_user = $this->session->userdata('can_create_user');
	$can_edit_user = $this->session->userdata('can_edit_user');
	$can_log_time = $this->session->userdata('can_log_time');
	$can_log_payment = $this->session->userdata('can_log_payment');
	$can_create_project = $this->session->userdata('can_create_project');
	$can_create_client = $this->session->userdata('can_create_client');
	$can_send_report = $this->session->userdata('can_send_report');
?>

<div style="float:right;">

	<a class="btn btn-primary" title="Back to the user list" href="<?php echo base_url('user/'); ?>"><i class='glyphicon glyphicon-arrow-left'></i> <?php echo lang('btn_view_all'); ?></a>

	<a href="<?php echo base_url('task/list/'.$user->user_id); ?>"><span class="btn btn-info"><i class='glyphicon glyphicon-list-alt'></i> <?php echo lang('btn_view_tasks'); ?></span></a>
	
	<a href="<?php echo base_url('user/edit/'.$user->user_id); ?>"><span class="btn btn-warning"><i class='glyphicon glyphicon-edit'></i> <?php echo lang('btn_edit'); ?></span></a>

	<?php if($user->status == 'active'){ ?>
		<button class="btn btn-danger" id="deactivate"><i class='glyphicon glyphicon-remove'></i> <?php echo lang('btn_deactivate'); ?></button>
	<?php }else{ ?>
		<a href="<?php echo base_url("user/change_status/active/".$user->user_id); ?>"><span class="btn btn-success"><i class='glyphicon glyphicon-ok'></i> <?php echo lang('btn_activate'); ?></span></a>
	<?php } ?>

	<?php #if() ?>
	<button id="delete_permanently" class="btn btn-danger"><i class='glyphicon glyphicon-trash'></i> <?php echo lang('btn_delete'); ?></button>

	<script>
		reset = function () {
			alertify.set({
				labels : {
					ok     : "Delete the user <?php echo $user->name; ?>",
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
					ok     : "Deactivate the user <?php echo $user->name; ?>",
					cancel : "Never mind"
				},
				delay : 5000,
				buttonReverse : false,
				buttonFocus   : "cancel"
			});
		};

		$("#deactivate").click(function(){
			reset_deactivate();

			var text = `<strong>Request to deactivate user <?php echo $user->name; ?></strong>
			<br /><br />
			Once deactivated, the user will no longer have access to the system, but their details, 
			logged hours and everything else they entered will be kept.<br />
			The user can be reactivated at any time.
			<br /><br />

			Are you sure you want to deactivate user <?php echo $user->name; ?>? <br /><br />
			`;

			alertify.confirm(text, function (e){
				if (e) {
					var url = '<?php echo base_url("user/change_status/inactive/".$user->user_id); ?>';
				
					if(url){
						window.location = url;
					}
					
				} else {
					alertify.error("User not deactivated.");
				}
			});
			return false;
		});

		$("#delete_permanently").click(function(){
			reset();

			var text = `<strong>Request to permanently delete user <?php echo $user->name; ?></strong>
			<br /><br />
			Deleting a user can be a little dangerous. <br /><br />
			By deleting a user you accept that everything related to them will be erased from the system, 
			affecting the information of the projects they worked on, 
			including the hours they logged.
			<br /><br />
			It may be better to just <a href="<?php echo base_url('user/change_status/inactive/'.$user->user_id); ?>">deactivate the user</a>. 
			When deactivated, <?php echo $user->name; ?> will no longer have access to the system, but their details will be kept.
			<br /><br />
			This user has:<br />
			<?php echo ($time_entries->num_rows() == 1) ? $time_entries->num_rows() . ' logged time entry' : $time_entries->num_rows() . ' logged time entries'; ?>
			<br /><br />

			Are you sure you want to delete user <?php echo $user->name; ?>, ID <?php echo $user->user_id; ?>? <br /><br />
			<strong>This action cannot be undone.</strong>
			
			`;

			alertify.confirm(text, function (e){
				if (e) {
					var url = '<?php echo base_url("user/delete/".$user->user_id); ?>';
				
					if(url){
						window.location = url;
					}
					
				} else {
					alertify.error("User not deleted.");
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
<div class="panel-heading" style="<?php echo ($user->status == 'inactive')  ? 'background-color: #f2dede;' : ''; ?>">About <?php echo $user->name; ?></div>
<table class="table table-bordered table-condensed">

	<tr>
		<td rowspan="6" width="155px"><img title="<?php echo $user->image; ?>" src="<?php echo base_url('assets/images/users/'. $user->image); ?>" class="img-thumbnail" width="200px" height="200px" style="background-color:<?php echo $this->session->userdata('color'); ?>;"/></td>
		
		<td width="80px"><strong title="Full name">Name:</strong></td>
			<td><?php echo $user->name; ?></td>
		
		<td width="80px"><strong title="Email">Email:</strong></td>
			<td><a href="mailto:<?php echo $user->email; ?>"><?php echo $user->email; ?></a></td>
		
		<td width="80px"><strong title="Access level">Role:</strong></td>
			<td><?php echo  $user->role; ?></td>
	</tr>
	
	<tr>
		<td style="min-width: 100px;"><strong title="Username">Login:</strong></td>
			<td><?php echo $user->login; ?></td>
		
		<td style="min-width: 100px;"><strong title="Date of birth">Birth Date:</strong></td>
			<td><?php echo fdate($user->birth_date, "/"); ?></td>
		
		<td style="min-width: 100px;"><strong title="User's age">Age:</strong></td>
			<td>
			<?php 
			if(!empty($user->birth_date)){
				$date = new DateTime($user->birth_date);
				$interval = $date->diff(new DateTime(date('Y-m-d')));
				echo $interval->format('%y years');
			}
			?>
			</td>
	</tr>
	
	<tr>
		<td><strong title="Employee ID">Employee ID:</strong></td>
			<td><?php echo $user->employee_id; ?></td>
		
		<td><strong title="Identity document number">ID Number:</strong></td>
			<td><?php echo $user->id_number; ?></td>
		
		<td><strong title="Tax ID">Tax ID:</strong></td>
			<td><?php if(!empty($user->tax_id)){ echo $user->tax_id; } ?></td>
	</tr>
	
	<tr>
		<td><strong title="Date the user was added">Created:</strong></td>
			<td><?php $created_at = fdatetime_parts($user->created_at, "/"); echo $created_at['date'] . " - " . $created_at['time']; ?></td>
		
		<!--<td><strong title="Email confirmation code">Confirmation:</strong></td>
			<td><?php if($user->confirmed == 'yes'){ echo $user->email_token; } ?></td>-->

		<td></td>
			<td></td>
		
		<td><strong title="Whether the user has confirmed their email">Confirmed:</strong></td>
			<td>
			<?php if($user->confirmed == 'yes'){ ?><label class="label label-primary">Confirmed</label>
			<?php }else{ ?><label class="label label-danger">Not Confirmed</label><?php } ?>
			</td>
	</tr>
	
	<tr>
		<td><strong title="Last login to Programmer Time">Last Login:</strong></td>
			<td><?php if(!empty($user->last_access)){ $last_access = fdatetime_parts($user->last_access, "/"); echo $last_access['date'] . " - " . $last_access['time']; }else{ echo 'Never'; } ?></td>
		
		<td><strong title="How many times the user has logged in">Logins:</strong></td>
		
		<td><?php $suffix_access = ($user->login_count == 1) ? ' login' : ' logins'; echo $user->login_count . $suffix_access; ?></td>
		
		<td><strong title="User status">Status:</strong></td>
			<td>
			<?php if($user->status == 'active'){ ?><label class="label label-primary">Active</label>
			<?php }else{ ?><label class="label label-danger">Inactive</label><?php } ?>
			</td>
	</tr>

</table>
</div>


<div role="tabpanel">

	<!-- Nav tabs -->
	<ul class="nav nav-tabs" role="tablist">

		<li role="presentation" class="active">
			<a href="#settings" aria-controls="settings" role="tab" data-toggle="tab">Hours Worked</a>
		</li>

		<li role="presentation">
			<a href="#messages" aria-controls="messages" role="tab" data-toggle="tab">Projects</a>
		</li>

		<li role="presentation">
			<a href="#home" aria-controls="home" role="tab" data-toggle="tab">Time Entries</a>
		</li>

		<li role="presentation">
			<a href="#profile" aria-controls="profile" role="tab" data-toggle="tab">Working on now</a>
		</li>


	</ul>

	<div class="tab-content">

		<div role="tabpanel" class="tab-pane active" id="settings">

			<br />

			<div class="">
				
				<div class="col-lg-8">
					<table class="table table-bordered table-hover table-condensed" id="table_hours">

					<tr>
						<th width="">Date</th>
						<th style="text-align:right; width: 150px;">Hours Worked</th>
						<th style="text-align:right; width: 150px;">Regular Hours</th>
						<th style="text-align:right; width: 150px;">Overtime</th>
						<th style="text-align:right; width: 150px;">Daily Target</th>
					</tr>
					
					<?php if(count($hours_worked) == 0){ ?>
						<tr><td colspan="5" style="text-align:center;"><?php echo $user->name; ?> has no hours worked yet.</td></tr>
					<?php } ?>
					
					<?php /*<pre><?php print_r($hours_worked); ?></pre> */ ?>

					<?php 

					$h_today = array();
					$h_week = array();
					$h_month = array();
					$h_total = array();
					$first_date = "";
					$previous_date = '0000-00-00';

					$row_count = 0;

					foreach($hours_worked as $date => $time){

							if($previous_date != '0000-00-00'){

								$begin 	= new DateTime($date);
								$end 	= new DateTime($previous_date);

								$interval = DateInterval::createFromDateString('1 day');
								$period = new DatePeriod($begin, $interval, $end);

								$period = array_reverse(iterator_to_array($period));

								foreach($period as $dt){

									$date_f = odate($dt->format('Y-m-d H:i:s'));

									if($date_f->Ymd == $date OR $date_f->Ymd == $previous_date)
										continue;

									echo '<tr>
											<td>'.$date_f->D .', '. $date_f->dmY.'</td>
											<td align="right">-</td>
											<td align="right">-</td>
											<td align="right">-</td>
											<td align="right">-</td>
										  </tr>';

									$row_count++;
								}
							}

							$previous_date = $date;

							$h_date = odate($date.' 00:00:00');

							if($h_date->d == date('d') AND $h_date->m == date('m') AND $h_date->Y == date('Y'))
								$h_today[] = $time;

							if($h_date->m == date('m') AND $h_date->Y == date('Y'))
								$h_month[] = $time;


							// print_r($h_date->w);

							// for($i = $h_date->w; $i > 0; $i--){
							// 	$h_week[] = $time;
							// }

							$h_total[] = $time;

							$first_date = $date;

							$row_count++;
					?>

						<tr>
							<td><?php echo $h_date->D .', '. $h_date->dmY; ?></td>
							<td align="right"><?php echo $time; ?></td>
							<td align="right"><?php echo regular_hours($time); ?></td>
							<td align="right"><?php echo subtract_hours($time, '08:00'); ?></td>
							<td align="right">08:00</td>
						</tr>

					<?php } ?>

					</table>

					<?php if($row_count > 10){ ?>
						<div id="pagination_hours" style="display:inline"></div>

						<script>
						var pager2 = new Pager('table_hours', 7);
						pager2.init();
						pager2.showPageNav('pager2', 'pagination_hours');
						pager2.showPage(1);
						</script>
					<?php } ?>
				</div>

				<div class="col-lg-4">

					<table class="table table-bordered table-hover table-condensed">

					<tr>
						<th style="text-align:right;">Today's Total - <?php echo date('d/m/Y'); ?></th>
					</tr>

					<tr>
						<td align="right"><?php echo sum_hours($h_today); ?></td>
					</tr>

					</table>

					<!--<table class="table table-bordered table-hover table-condensed">

					<tr>
						<th style="text-align:right;">This Week's Total</th>
					</tr>

					<tr>
						<td align="right"><?php echo sum_hours($h_week); ?></td>
					</tr>

					</table>-->

					<table class="table table-bordered table-hover table-condensed">

					<tr>
						<th style="text-align:right;">This Month's Total - <?php echo date('m/Y'); ?></th>
					</tr>

					<tr>
						<td align="right"><?php echo sum_hours($h_month); ?></td>
					</tr>

					</table>

					<table class="table table-bordered table-hover table-condensed">

					<tr>
						<th style="text-align:right;">Total <?php echo ($first_date) ? 'since '. fdate($first_date, "/") : ""; ?></th>
					</tr>

					<tr>
						<td align="right"><?php echo sum_hours($h_total); ?></td>
					</tr>

					</table>

				</div>

			</div>
		</div>

		<div role="tabpanel" class="tab-pane" id="messages">

			<br />

			<div class="col-lg-12">

			<table class="table table-bordered table-hover table-condensed" id="table_projects">

				<tr>
					<th width="80px" style="text-align:center;">ID</th>
					<th width="80px" style="text-align:center;">Priority</th>
					<th width="80px" style="text-align:center;">Status</th>
					<th width="">Project Name</th>
					<th width="">Client</th>
					<th width="220px">Type</th>		
					<th width="" style="text-align:center;">Entries</th>
					<th width="" style="text-align:center;">Hours</th>
					<!--<th width="80px">Date</th>-->
					<th width="110px">Deadline</th>
					<th width="">Owner</th>
					<th width="180px"></th>
				</tr>

			<?php
	
			if(count($projects_involved) == 0){
				echo '<tr><td colspan="12" align="center">'.$user->name.' is not involved in any project yet.</td></tr>';
			}
				
				
			?>

			<?php foreach($projects_involved as $project_involved){
					$pe = $project_involved->row();

					$class_tr = "";
					$danger = "";
					
					if($pe->deadline <= date('Y-m-d H:i:s')){
						$class_tr = 'danger';
						$danger = 'danger';
					}
					
					if($pe->status == 'paused')
						$class_tr = 'warning';
					
					if($pe->status == 'completed')
						$class_tr = '';
				?>

				<tr class="<?php echo $class_tr; ?>" id="context_<?php echo $pe->project_id; ?>">

					<td align="center">
						<a href="<?php echo base_url('project/view/'. $pe->project_id); ?>"><?php echo '# ' . $pe->project_id; ?></a>
					</td>

					<td align="center">
					<?php 
						if($pe->priority == 'low'){
							?>
								<input type='image' width="20px" alt='Low' title='Low' src='<?php echo base_url('assets/images/system/star_grey.png'); ?>' onclick="priority(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'priority', '<?php echo $pe->project_id; ?>');" />
							<?php
						}
						
						elseif($pe->priority == 'normal'){
							?>
								<input type='image' width="20px" alt='Normal' title='Normal' src='<?php echo base_url('assets/images/system/star_black.png'); ?>' onclick="priority(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'priority', '<?php echo $pe->project_id; ?>');" />
							<?php
						}
						
						elseif($pe->priority == 'urgent'){
							?>
								<input type='image' width="20px" alt='Urgent' title='Urgent' src='<?php echo base_url('assets/images/system/star_red.png'); ?>' onclick="priority(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'priority', '<?php echo $pe->project_id; ?>');" />
							<?php
						}
					?>
					</td>
					
					<td align="center">
					<?php 
						if($pe->status == 'not_started'){
							?>
								<input type='image' width="20px" alt='Not Started' title='Not Started' overdue='<?php echo ($danger == 'danger') ? "yes" : "no"; ?>' src='<?php echo base_url('assets/images/system/dot_blue.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'status', '<?php echo $pe->project_id; ?>');" />
							<?php
						}
						
						elseif($pe->status == 'in_progress'){
							?>
								<input type='image' width="20px" alt='In Progress' title='In Progress' overdue='<?php echo ($danger == 'danger') ? "yes" : "no"; ?>' src='<?php echo base_url('assets/images/system/dot_green.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'status', '<?php echo $pe->project_id; ?>');" />
							<?php
						}
						
						elseif($pe->status == 'paused'){
							?>
								<input type='image' width="20px" alt='Paused' title='Paused' overdue='<?php echo ($danger == 'danger') ? "yes" : "no"; ?>' src='<?php echo base_url('assets/images/system/paused.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'status', '<?php echo $pe->project_id; ?>');" />
							<?php
						}
						
						elseif($pe->status == 'completed'){
							?>
								<input type='image' width="20px" alt='Completed' title='Completed' overdue='<?php echo ($danger == 'danger') ? "yes" : "no"; ?>' src='<?php echo base_url('assets/images/system/completed.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'status', '<?php echo $pe->project_id; ?>');" />
							<?php
						}
					?>
					</td>
					
					<td>
						<a href="<?php echo base_url('project/view/'.$pe->project_id) ?>" title="<?php echo htmlentities($pe->description); ?>">
							<?php echo $pe->name; ?>
						</a>
					</td>
					
					<td>
						<?php echo '<a href="'. base_url('client/view/'.$pe->client_id) .'" alt="'. $pe->client_status .'">' . $pe->client . '</a>'; ?>
					</td>
					
					<td><?php echo  $pe->type; ?></td>
					
					<td align="center"><?php echo  $project_involved->time_entry_count; ?></td>
					<td align="center"><?php echo  $project_involved->hours_worked; ?></td>
					
					<!--<td><?php echo '<span class="label label-primary">' . fdate($pe->start_date, "/") . '</span>'; ?></td>-->
					
					<td>
					<?php
						$label_deadline = "";
						if($pe->deadline <= date('Y-m-d H:i:s')){
							if($pe->status == 'completed'){
								$label_deadline = "label-primary";
							}else{
								$label_deadline = "label-danger";
							}
						}else{
							$label_deadline = "label-warning";
						}
						
						echo '<span class="label '. $label_deadline .'" id="deadline">' . fdatetime($pe->deadline,"/") . '</span>';
					?>
					</td>
					
					<td>
						<a href="<?php echo base_url('user/view/'.$pe->owner_id); ?>" id="a-popover-<?php echo $pe->project_id; ?>">
							<?php echo short_name($pe->owner, 0); ?>
						</a>

						<div id="div-popover-<?php echo $pe->project_id; ?>" class="hide">
							
							<div style="width:80px;">
								<img src="<?php echo base_url('assets/images/users/'.$pe->owner_image); ?>" class="img-thumbnail" style="background-color:<?php echo $pe->owner_color; ?>;">
							</div>
						</div>

						<script type="text/javascript">
							
								$('#a-popover-<?php echo $pe->project_id; ?>').popover({
									trigger: 'hover',
									placement: 'top',
									html: true,
									content: $('#div-popover-<?php echo $pe->project_id; ?>').html()
								});
						   
						 </script>
					</td>
					
					<td align="right">
						<a class="btn btn-xs btn-primary" alt="View Project" title="View Project" href="<?php echo base_url('project/view/'. $pe->project_id); ?>" id="view_project">
							<i class='glyphicon glyphicon-th-large'></i>
						</a>
						
						<?php if($can_create_project){ ?>
							<a class="btn btn-xs btn-info" alt="Create Report" title="Create Report" href="<?php echo base_url('report/generate/'. $pe->project_id); ?>" id="generate_report">
								<i class='glyphicon glyphicon-file'></i>
							</a>
						<?php } ?>
						
						<?php if($can_create_project){ ?>
							<a class="btn btn-xs btn-warning" alt="Edit Project" title="Edit Project" href="<?php echo base_url('project/edit/'. $pe->project_id); ?>" id="create_project">
								<i class='glyphicon glyphicon-pencil'></i>
							</a>
						<?php } ?>
						
						<?php if($can_log_time){ ?>
							<a class="btn btn-xs btn-info <?php if($pe->status == 'completed'){ echo "disabled"; } ?>" alt="Log Time" title="Log Time" href="<?php echo base_url('time_entry/start/'. $pe->project_id); ?>" id="log_time_entry">
								<i class='glyphicon glyphicon-time'></i>
							</a>
						<?php } ?>
						
						<?php if($can_log_payment){ ?>
							<a class="btn btn-xs btn-success <?php if($pe->status == 'completed'){ echo "disabled"; } ?>" alt="Add Payment" title="Add Payment" href="<?php echo base_url('finance/create/'. $pe->project_id); ?>" id="log_payment">
								<i class='glyphicon glyphicon-usd'></i>
							</a>
						<?php } ?>
						
						<?php if($can_create_project){ ?>
							<span class="btn btn-xs btn-danger" alt="Remove Project" title="Remove Project" id="delete_project_<?php echo $pe->project_id; ?>">
								<i class='glyphicon glyphicon-remove'></i>
							</span>
						<?php } ?>
					</td>
				</tr>

			<?php } ?>

			</table>

			</div>

			<?php if($time_entries->num_rows() > 10){ ?>
				<div id="pagination_projects" style="display:inline"></div>

				<script>
				var pager3 = new Pager('table_projects', 10);
				pager3.init();
				pager3.showPageNav('pager3', 'pagination_projects');
				pager3.showPage(1);
				</script>
			<?php } ?>
		</div>

		<div role="tabpanel" class="tab-pane" id="home">

			<br />

			<div class="col-lg-12">
			<table class="table table-bordered table-hover table-condensed" id="table_time_entries">
				
				<!--<tr class="info"><td colspan="10"><strong>Completed Time Entries</strong></td></tr>-->
				
				<tr>
					<th width="">Project</th>
					<th width="">Phase</th>
					<th width="">Technical Description</th>
					<!-- <th width="">Client Description</th> -->
					<th width="">Date</th>
					<th width="">Start</th>
					<th width="">End</th>
					<th width="">Time</th>
					<th width="70px"></th>		
				</tr>
				
				<?php if($time_entries->num_rows() == 0){ ?>
					<tr><td colspan="9" style="text-align:center;"><?php echo $user->name; ?> has not logged any time yet.</td></tr>
				<?php } ?>
				
				<?php foreach($time_entries->result() as $time_entry){ ?>
				<tr>
					<td title="<?php echo '# '.$time_entry->project_id; ?>">
						<?php echo  '<a href="'. base_url('project/view/'. $time_entry->project_id) .'" title="'. $time_entry->priority .'">' . $time_entry->project_name . '</a>'; ?>
					</td>
					<td><?php echo  $time_entry->phase; ?></td>
					<td><?php echo  $time_entry->technical_description; ?></td>
					<!-- <td><?php echo  $time_entry->client_description; ?></td> -->
					<td><?php echo fdate($time_entry->date, '/'); ?></td>
					<td><?php echo ftime($time_entry->start_time); ?></td>
					<td><?php echo ftime($time_entry->end_time); ?></td>
					<td><?php echo calculate_hours($time_entry->end_time, $time_entry->start_time); ?></td>
					<td>
						<a class="btn btn-xs btn-warning" href="<?php echo base_url('time_entry/edit/'. $time_entry->time_entry_id); ?>"><i class="glyphicon glyphicon-edit"></i></a>
						<a class="btn btn-xs btn-danger" id="delete_time_entry_<?php echo $time_entry->time_entry_id; ?>"><i class="glyphicon glyphicon-remove"></i></a>
					</td>

				</tr>

				<script>
				$("#delete_time_entry_<?php echo $time_entry->time_entry_id; ?>").click(function () {
					reset();
					alertify.confirm("Are you sure you want to permanently delete this time entry? <br /><b><?php echo $time_entry->phase; ?></b> - <?php echo $time_entry->technical_description; ?>", function (e) {
						if (e) {
							var url = "<?php echo base_url('time_entry/delete/'.$time_entry->time_entry_id); ?>";
						
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
			</div>
				
			<?php if($time_entries->num_rows() > 10){ ?>
				<div id="pagination_time_entries" style="display:inline"></div>

				<script>
				var pager = new Pager('table_time_entries', 10);
				pager.init();
				pager.showPageNav('pager', 'pagination_time_entries');
				pager.showPage(1);
				</script>
			<?php } ?>
		</div>

		<div role="tabpanel" class="tab-pane" id="profile">

			<br />

			<?php if($open_time_entry->num_rows() == 1){ $a_time_entry = $open_time_entry->row(); ?>
			<div class="col-lg-12">
				<table class="table table-bordered table-hover table-condensed" id="table_now">

					<tr>
						<td width="150px;"><strong>Project:</strong></td>
						<td>
							<a href="<?php echo base_url('project/view/'. $a_time_entry->project_id); ?>" title="<?php echo $a_time_entry->project_description; ?>"># <?php echo $a_time_entry->project_id.' - '.$a_time_entry->project_name.'</a>'; ?>
						</td>

						<td><strong>Priority:</strong></td>
						<td><?php echo ucfirst($a_time_entry->project_priority); ?></td>
					</tr>

					<tr>
						<td><strong>Start:</strong></td>
						<td><?php echo ftime($a_time_entry->start_time); ?></td>

						<td width="150px;"><strong>Created:</strong></td>
						<td><?php echo fdatetime($a_time_entry->created_at, "/"); ?></td>
					</tr>

					<tr>
						<td><strong>Phase:</strong></td>
						<td><?php echo $a_time_entry->phase; ?></td>

						<td title="Time elapsed so far (<?php echo date('H:i'); ?>)"><strong>Time:</strong></td>
						<td><?php echo calculate_hours(date('H:i:s'), $a_time_entry->start_time); ?></td>
					</tr>

					<tr>
						<td><strong>Technical Description:</strong></td>
						<td colspan="3"><?php echo $a_time_entry->technical_description; ?></td>
					</tr>

					<tr>
						<td><strong>Client Description:</strong></td>
						<td colspan="3"><?php echo $a_time_entry->client_description; ?></td>
					</tr>

				</table>
			</div>

			<?php }else{ ?>

				<div class="col-lg-12">The user is not working on anything right now.</div>

			<?php } ?>

		</div>

	</div>
</div>