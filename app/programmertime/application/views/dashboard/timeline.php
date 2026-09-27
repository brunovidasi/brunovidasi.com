<link type="text/css" href="<?php echo base_url('assets/js/pagination/paging.css'); ?>" rel="stylesheet" />
<script type="text/javascript" src="<?php echo base_url('assets/js/pagination/paging.js'); ?>"></script>
<script type="text/javascript" src="<?php echo base_url('assets/js/moment.js'); ?>"></script>
<script src="<?php echo base_url('assets/js/highcharts/js/highcharts.js'); ?>"></script>

<!--<p class="page_title"  style="float:left;">Hi! How was your day? Let's get to our projects!</p> <br><br><br><br>--><br />

<?php 
if($just_logged_in == TRUE){ ?>
	<script>
	$(document).ready(function(){
		$(".timeline").slideDown(1000);

		setTimeout(function(){
			$(".recent-projects").slideDown(1000);
		}, 1000);
	});
	</script>
<?php } ?>

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


	$item = array();

	# TASKS ############################################################################

	if($task_count > 0){
		$task['datetime'] = date('Y-m-d H:i:s');

		if($task_count == 1)
			$number_tasks = 'You have <strong>1</strong> pending task.';
		else
			$number_tasks = 'You have <strong>'. $task_count .'</strong> pending tasks.';

		$bg = 'bg-aqua';

		$task['content'] = '<li>
		    <i class="glyphicon glyphicon-list-alt '.$bg.'"></i>
		    	<div class="timeline-item">
			       	<span class="time"><i class="glyphicon glyphicon-time"></i> <span title="'. fdatetime($task['datetime'], "/") .'">'. fdatetime($task['datetime'], "/") .'</span></span>

			        <h3 class="timeline-header">
			        	<a href="'. base_url('task/list/'. $this->session->userdata('id')) .'">My Tasks</a>
			        </h3>
		            
		            <div class="timeline-body">
		            	'. $number_tasks .'
		            </div>

		            <div class="timeline-footer">
		                <a href="'. base_url('task/list/'. $this->session->userdata('id')) .'" class="btn btn-primary btn-xs"><i class="glyphicon glyphicon-arrow-right"></i> View Tasks</a>
		            </div>
		    	</div>
		    </li>
		';

		$item[] = $task;
	}

	# OPEN TIME ENTRY #################################################################

	if($open_time_entry->num_rows() == 1){

		$a_time_entry = $open_time_entry->row();

		$time_entry['datetime'] = $a_time_entry->date . ' ' . $a_time_entry->start_time;

		if($a_time_entry->date == date('Y-m-d')){
			$bg = 'bg-aqua';
		}else{
			$bg = 'bg-red';
		}

		$time_entry['content'] = '<li>
		    <i class="glyphicon glyphicon-time '.$bg.'"></i>
		    	<div class="timeline-item">
			       	<span class="time"><i class="glyphicon glyphicon-time"></i> <span id="time_entry_'.$a_time_entry->time_entry_id.'" title="'. fdatetime($time_entry['datetime'], "/") .'">'. ftime($a_time_entry->start_time) .'</span></span>

			        <h3 class="timeline-header">
			        	<a href="'. base_url('project/view/'. $a_time_entry->project_id) .'">'. $a_time_entry->project_name .'</a>
			        </h3>
		            
		            <div class="timeline-body">
		            	<strong>'. $a_time_entry->phase .'</strong><br />
		                '. $a_time_entry->technical_description .'
		            </div>

		            <div class="timeline-footer">
		                <a href="'. base_url('time_entry') .'" class="btn btn-primary btn-xs"><i class="glyphicon glyphicon-arrow-right"></i> Stop Timer</a>
		                <a class="btn btn-danger btn-xs" id="delete_time_entry_lt_'.$a_time_entry->time_entry_id.'"><i class="glyphicon glyphicon-remove"></i> Delete</a>
		            </div>
		    	</div>
		    </li>

			<script>
		    	var when = moment("'.$a_time_entry->date.' '.$a_time_entry->start_time.'", "YYYY/MM/DD HH:mm:ss").startOf("second").fromNow();
				$("#time_entry_'.$a_time_entry->time_entry_id.'").html(when);

				$("#delete_time_entry_lt_'.$a_time_entry->time_entry_id.'").click(function () {
					reset();
					alertify.confirm("Are you sure you want to permanently delete this time entry? <b>'.$a_time_entry->phase.'</b> - '.$a_time_entry->technical_description.'", function (e) {
						if (e) {
							var url = "'. base_url('time_entry/delete/'.$a_time_entry->time_entry_id). '";
						
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

		    ';

		    $item[] = $time_entry;
	}

	# MESSAGES #########################################################################

	foreach($messages as $msg){
		
		$message['datetime'] = $msg->sent_at;

		$message['content'] = '<li>
		    <i class="glyphicon glyphicon-envelope bg-blue"></i>
		    	<div class="timeline-item">
			       	<span class="time"><i class="glyphicon glyphicon-time"></i> <span id="message_'.$msg->message_id.'" title="'. fdatetime($msg->sent_at, "/") .'">'. fdatetime($msg->sent_at, "/") .'</span></span>

			        <h3 class="timeline-header">
			        	<a href="'.base_url('message/view/'.$msg->message_id).'">'.$msg->subject.'</a>
			        </h3>
		            
		            <div class="timeline-body">

		            <a href="'. base_url('user/view/'. $msg->from_user_id) .'" class="thumbnail" style="width:50px; margin-right:8px; margin-bottom: 5px; float: left; background: '.$msg->color.';">
		            	<img src="'. base_url('assets/images/users/'. $msg->image) .'"  />
		            </a>

		            <a href="'. base_url('user/view/'. $msg->from_user_id) .'"><strong>'.short_name($msg->name).'</a>:</strong> '.$msg->message.'

		            </div>

		            <div class="timeline-footer">
		            	<a href="'.base_url('message/view/'.$msg->message_id).'" class="btn btn-primary btn-xs">
		            	 	<i class="glyphicon glyphicon-th-large"></i> View
		            	</a>

		            	<a href="'.base_url('message/view/'.$msg->message_id).'" class="btn btn-warning btn-xs">
		            	 	<i class="glyphicon glyphicon-share-alt"></i> Reply
		            	</a>
		            </div>
		    	</div>
		    </li>

		    <script>
		    	var when = moment("'.$msg->sent_at.'", "YYYY/MM/DD HH:mm:ss").startOf("second").fromNow();
 				$("#message_'.$msg->message_id.'").html(when);
		    </script>

		';

		$item[] = $message;
	}


	# SORT ITEMS BY DATE #############################################################

	// function cmp($item,$b){
	//     return strtotime($item['datetime'])<strtotime($b['datetime'])?1:-1;
	// }

	// uasort($item,'cmp');

	$ord = array();
	foreach ($item as $key => $value){
	    $ord[] = strtotime($value['datetime']);
	}

	array_multisort($ord, SORT_DESC, $item);

	####################################################################################

	#print_r($item);
?>

<div class="row">
    <div class="col-md-4">
        <ul class="timeline" <?php if($just_logged_in) echo 'style="display:none;"';?>>

            <?php 

			if(count($item) == 0){
				echo '<li class="time-label">
						<span class="bg-blue">
							'.weekday().', '. tdate(date('Y-m-d')) .'
						</span>
					</li>
					<li>
					<i class="glyphicon glyphicon-ok bg-aqua"></i>
						<div class="timeline-item">
							<div class="timeline-body">No notifications today.</div>
						</div>
					</li>
					';
			}

            for($i = 0; $i < count($item); $i++){

            	list($item[$i]['date'], $item[$i]['time']) = explode(" ", $item[$i]['datetime']);

            	$bg = ($item[$i]['date'] == date('Y-m-d')) ? 'bg-blue' : 'bg-yellow';
            	
            	if($i == 0){
            		if($item[$i]['date'] != date('Y-m-d')){
            			echo '<li class="time-label">
			               <span class="bg-blue">
			                    '.weekday().', '. tdate(date('Y-m-d')) .'
			                </span>
			            </li>
			            <li>
			            <i class="glyphicon glyphicon-ok bg-aqua"></i>
		    				<div class="timeline-item">
		    					<div class="timeline-body">No notifications today.</div>
		    				</div>
			            </li>
			            ';
            		}

            		echo '<li class="time-label">
		               <span class="'.$bg.'">
		                    '. tdate($item[$i]['date']) .'
		                </span>
		            </li>';
            	}else{
            		if($item[$i-1]['date'] != $item[$i]['date']){
            			echo '<li class="time-label">
			               <span class="'.$bg.'">
			                    '. tdate($item[$i]['date']) .'
			                </span>
			            </li>';
            		}
            	}

            	echo $item[$i]['content'];

            }

            ?>

            <li>
                <i class="glyphicon glyphicon-clock-o"></i>
            </li>
        </ul>
    </div><!-- /.col --> 


    <div class="recent-projects col-md-8" <?php //if($just_logged_in) echo 'style="display:none;"';?>>


    	<div role="tabpanel">

		  <!-- Nav tabs -->
			<ul class="nav nav-tabs" role="tablist">

				<li role="presentation" class="active">
					<a href="#settings" aria-controls="settings" role="tab" data-toggle="tab"><i class="glyphicon glyphicon-signal"></i> Hours Worked</a>
				</li>

				<li role="presentation">
					<a href="#home" aria-controls="home" role="tab" data-toggle="tab"><i class="glyphicon glyphicon-book"></i> My Projects</a>
				</li>

				<li role="presentation">
					<a href="#messages" aria-controls="messages" role="tab" data-toggle="tab"><i class="glyphicon glyphicon-time"></i> My Time Entries</a>
				</li>

				<li role="presentation">
					<a href="#profile" aria-controls="profile" role="tab" data-toggle="tab"><i class="glyphicon glyphicon-flash"></i> Now</a>
				</li>


			</ul>

			<!-- Tab panes -->
			<div class="tab-content">
				<div role="tabpanel" class="tab-pane" id="home">

					<br />

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
									<td><a href="<?php echo base_url('project/view/'.$project->project_id); ?>"><?php echo $project->name; ?></a></td>
									<td><?php echo $project->type; ?></td>
									
									<td>
										<a href="<?php echo base_url('user/view/'.$project->owner_id); ?>" id="a-popover-<?php echo $project->project_id; ?>">
											<?php echo $project->owner; ?>
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
								<?php } 

								if(count($projects_involved) == 0){
									echo '<td colspan="8" style="text-align:center;">You have no recent projects.</td>';
								}

								?>
								
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

				</div>

				<div role="tabpanel" class="tab-pane" id="messages">

					<br />

					<div class="panel panel-default">
						<div class="panel-heading">Latest completed time entries</div>
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
							
							<?php if($my_time_entries->num_rows() == 0){ ?>
								<tr><td colspan="9" style="text-align:center;">You haven't logged any time yet.</td></tr>
							<?php } ?>
							
							<?php foreach($my_time_entries->result() as $time_entry){ ?>
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

					<div class="panel panel-default">

						<div class="panel-heading">
							<h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#collapse3">What am I working on now?</a> <?php if($open_time_entry->num_rows() == 1){ echo'<span class="badge pull-right">1</span>';} ?></h4>
						</div>
						
						<div class="panel-body">

							<?php if($open_time_entry->num_rows() == 1){ $a_time_entry = $open_time_entry->row();?>

								<br />
								
								<table class="table table-hover">

									<tr>
										<td width="150px;"><strong>Project:</strong></td>
										<td>
											<a href="<?php echo base_url('project/view/'. $a_time_entry->project_id); ?>" title="<?php echo $a_time_entry->project_description; ?>"><?php echo $a_time_entry->project_name.'</a> - <strong>Priority: </strong>'.ucfirst($a_time_entry->project_priority); ?>
										</td>
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

							<?php if($open_time_entry->num_rows() == 1){ $a_time_entry = $open_time_entry->row();?> <span class="pull-left"><strong><?php echo fdate($a_time_entry->date, "/") . ' - ' . ftime($a_time_entry->start_time); ?></strong><br> <?php echo $a_time_entry->phase . ' - ' . $a_time_entry->project_name; ?></span>
								<span class="pull-right"><a href="<?php echo base_url('time_entry'); ?>" class="btn btn-primary"><i class="glyphicon glyphicon-time"></i> Stop Timer</a>
								<a href="<?php echo base_url('time_entry'); ?>" class="btn btn-danger"><i class="glyphicon glyphicon-remove"></i> Delete Timer</a></span>
							<?php }else{ ?>
								<span class="pull-right"><a href="<?php echo base_url('time_entry'); ?>" class="btn btn-primary"><i class="glyphicon glyphicon-time"></i> Log Time</a></span>
							<?php } ?>
						</div>
					</div>

				</div>

				

				<div role="tabpanel" class="tab-pane active" id="settings">
					<br />
					<div class="panel panel-default">

						<div class="panel-heading">Hours worked per day</div>

						<div id="chart_hours_worked" style="min-width: 500px; width: 100%; height: 350px; margin: 0 auto"></div>

						<?php /*
						<table class="table table-bordered table-hover table-condensed" id="table_hours_worked">

							<!--<tr class="info"><td colspan="10"><strong>Completed Time Entries</strong></td></tr>-->
							
							<tr>
								<th width="150px;" title="Date">Date</th>
								<th width="" title="Hours worked that day">Hours</th>	
							</tr>
							
							<?php if(count($hours_worked) == 0){ ?>
								<tr><td colspan="2" style="text-align:center;">You have no hours worked yet.</td></tr>
							<?php } ?>
							
							<?php foreach($hours_worked as $hr_date => $time){ ?>
							<tr>
								<td><?php echo fdate($hr_date, '/'); ?></td>
								<td><?php echo $time; ?></td>
							</tr>
							<?php } ?>
							
						</table>

						*/ ?>
					</div>
							
						<?php /*if($time_entries->num_rows() > 10){ ?>
							<div id="pagination_hours_worked" style="display:inline"></div>

							<script>
							var pager2 = new Pager('table_hours_worked', 10);
							pager2.init();
							pager2.showPageNav('pager2', 'pagination_hours_worked');
							pager2.showPage(1);
							</script>
						<?php } */ ?>


				</div>
			</div>

		</div>



    </div>

</div><!-- /.row -->

<script>
$(function () {
        $('#chart_hours_worked').highcharts({
			credits: false,
            chart: {
            },
            title: {
                text: 'Last 10 days'
            },
            xAxis: {
				categories: [
				<?php 
				$counter = 0;
				foreach($hours_worked as $hr_date => $time){ 
					echo "'". fdate($hr_date, '/') . "',";
					$counter++;
					if($counter == 10) break;
				} ?>
				]
            },
			yAxis: {
				title: {
                    text: 'Hours'
                },
            },
			
            series: [	
			{
                type: 'column',
                name: 'Hours',
                data: [
                <?php 
				$counter = 0;
				foreach($hours_worked as $hr_date => $time){ 
					$hr = explode(':', $time);

					echo (float) $hr[0].'.'.$hr[1];
					echo ",";

					$counter++;
					if($counter == 10) break;
				} ?>		
                ],
                marker: {
                	lineWidth: 2,
                	lineColor: Highcharts.getOptions().colors[3],
                	fillColor: 'white'
                }
            }]
        });
    });
</script>


