<link href="<?php echo base_url('assets/js/alertify/themes/alertify.ptime.css'); ?>" rel="stylesheet" type="text/css" id="toggleCSS"/>

<p class="page_title" style="float:left;"><?php echo lang('title_project_list'); ?></p>

<div class="" style="float:right; margin-bottom:10px; width:50%;">
		
	<!--<div class="btn-group" style="float:right">
		<button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" style="float:right; margin-top:26px;">
			<i class='glyphicon glyphicon-search'></i> Search by Client
			<span class="caret"></span>
		</button>
		<ul class="dropdown-menu">
			<li><a href="<?php echo base_url('project/list/'); ?>">List All</a></li>
			<li class="divider"></li>
			
			<?php foreach($clients->result() as $cl){ ?>
			
				<li><a href="<?php echo base_url('project/client/'.$cl->client_id); ?>"><?php echo $cl->name; ?></a></li>
		
			<?php  } ?>
		</ul>
	</div>-->

	<form action="<?php print base_url('project/list'); ?>" method="post" name="filter_form" id="filter_form" class="form-inline" style="display:inline; float:right; margin-top:26px; width:628px;">
		
		<input type="text" name="term" placeholder="<?php echo lang('placeholder_filter_project'); ?>" class="form-control" value="<?php print $this->session->userdata('c_term'); ?>" style="width:500px; float:left;"/>
		<a href="<?php echo base_url('project/create'); ?>" title="<?php echo lang('alt_create_project'); ?>" class="btn btn-primary" style="float:right; margin-right: 0px;"><i class='glyphicon glyphicon-plus'></i> <?php echo lang('btn_new'); ?></a>
		<button type="submit" title="<?php echo lang('alt_btn_search_project'); ?>" class="btn btn-primary" style="float:right; margin-right: 5px;"><i class='glyphicon glyphicon-search'></i></button>
	
	</form>
	
</div> <br><br><br><br>

<?php
	$can_log_time = $this->session->userdata('can_log_time');
	$can_log_payment = $this->session->userdata('can_log_payment');
	$can_create_project = $this->session->userdata('can_create_project');
	$can_create_client = $this->session->userdata('can_create_client');
	$can_send_report = $this->session->userdata('can_send_report');

	require('application/views/includes/message.php');
	if(validation_errors() != '')
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
?>

<table width="100%" class="table table-hover table-condensed" style="padding:10px;">

	<tr>
		<th width="80px" style="text-align:center;"><?php echo lang('table_id'); ?></th>
		<th width="80px" style="text-align:center;"><?php echo lang('table_priority'); ?></th>
		<th width="80px" style="text-align:center;"><?php echo lang('table_status'); ?></th>
		<th width=""><?php echo lang('table_project_name'); ?></th>
		<th width=""><?php echo lang('table_client'); ?></th>
		<th width=""><?php echo lang('table_type'); ?></th>		
		<th width="" style="text-align:center;"><?php echo lang('table_time_entries'); ?></th>
		<th width="" style="text-align:center;"><?php echo lang('table_hours'); ?></th>
		<th width="80px"><?php echo lang('table_date'); ?></th>
		<th width="110px"><?php echo lang('table_deadline'); ?></th>
		<th width=""><?php echo lang('table_owner'); ?></th>
		<th width="210px"></th>
	</tr>
	
	<?php
	
	if($projects->num_rows() == 0){
		echo '<tr><td colspan="12" align="center">'.lang('table_no_results').'</td></tr>';
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

		if($project->status == 'cancelled')
			$class_tr = 'danger';
		
		if($project->status == 'completed')
			$class_tr = '';
		
	?>
	
	<tr class="<?php echo $class_tr; ?>" id="context_<?php echo $project->project_id; ?>">
		
		<td align="center">
			<a href="<?php echo base_url('project/view/'. $project->project_id); ?>"><?php echo '# ' . $project->project_id; ?></a>
		</td>
		
		<td align="center">
		<?php 
			if($project->priority == 'low'){
				?>
					<input type='image' width="20px" alt='<?php echo lang('priority_low'); ?>' title='<?php echo lang('priority_low'); ?>' src='<?php echo base_url('assets/images/system/star_grey.png'); ?>' onclick="priority(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'priority', '<?php echo $project->project_id; ?>');" />
				<?php
			}
			
			elseif($project->priority == 'normal'){
				?>
					<input type='image' width="20px" alt='<?php echo lang('priority_normal'); ?>' title='<?php echo lang('priority_normal'); ?>' src='<?php echo base_url('assets/images/system/star_black.png'); ?>' onclick="priority(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'priority', '<?php echo $project->project_id; ?>');" />
				<?php
			}
			
			elseif($project->priority == 'urgent'){
				?>
					<input type='image' width="20px" alt='<?php echo lang('priority_urgent'); ?>' title='<?php echo lang('priority_urgent'); ?>' src='<?php echo base_url('assets/images/system/star_red.png'); ?>' onclick="priority(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'priority', '<?php echo $project->project_id; ?>');" />
				<?php
			}
		?>
		</td>
		
		<td align="center">
		<?php 
			if($project->status == 'not_started'){
				?>
					<input type='image' width="20px" alt='<?php echo lang('status_not_started'); ?>' title='<?php echo lang('status_not_started'); ?>' overdue='<?php echo ($danger == 'danger') ? "yes" : "no"; ?>' src='<?php echo base_url('assets/images/system/dot_blue.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'status', '<?php echo $project->project_id; ?>');" />
				<?php
			}
			
			elseif($project->status == 'in_progress'){
				?>
					<input type='image' width="20px" alt='<?php echo lang('status_in_progress'); ?>' title='<?php echo lang('status_in_progress'); ?>' overdue='<?php echo ($danger == 'danger') ? "yes" : "no"; ?>' src='<?php echo base_url('assets/images/system/dot_green.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'status', '<?php echo $project->project_id; ?>');" />
				<?php
			}
			
			elseif($project->status == 'paused'){
				?>
					<input type='image' width="20px" alt='<?php echo lang('status_paused'); ?>' title='<?php echo lang('status_paused'); ?>' overdue='<?php echo ($danger == 'danger') ? "yes" : "no"; ?>' src='<?php echo base_url('assets/images/system/paused.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'status', '<?php echo $project->project_id; ?>');" />
				<?php
			}
			
			elseif($project->status == 'completed'){
				?>
					<input type='image' width="20px" alt='<?php echo lang('status_completed'); ?>' title='<?php echo lang('status_completed'); ?>' overdue='<?php echo ($danger == 'danger') ? "yes" : "no"; ?>' src='<?php echo base_url('assets/images/system/completed.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'status', '<?php echo $project->project_id; ?>');" />
				<?php
			}

			elseif($project->status == 'cancelled'){
				?>
					<input type='image' width="20px" alt='<?php echo lang('status_cancelled'); ?>' title='<?php echo lang('status_cancelled'); ?>' overdue='<?php echo ($danger == 'danger') ? "yes" : "no"; ?>' src='<?php echo base_url('assets/images/system/cancelled.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'project', 'project_id', 'status', '<?php echo $project->project_id; ?>');" />
				<?php
			}
		?>
		</td>
		
		<td>
			<a href="<?php echo base_url('project/view/'.$project->project_id) ?>" title="<?php echo htmlentities($project->description); ?>">
				<?php echo $project->name; ?>
			</a>
		</td>
		
		<td>
			<?php echo '<a href="'. base_url('client/view/'.$project->client_id) .'" alt="'. $project->client_status .'">' . $project->client_name . '</a>'; ?>
		</td>
		
		<td><?php echo  $project->project_type; ?></td>
		
		<td align="center"><?php echo  $project->time_entry_count; ?></td>
		<td align="center"><?php echo  $project->total_hours; ?></td>
		
		<td><?php echo '<span class="label label-primary">' . fdate($project->start_date, "/") . '</span>'; ?></td>
		
		<td>
		<?php
			$label_deadline = "";
			if($project->deadline <= date('Y-m-d H:i:s')){
				if($project->status == 'completed'){
					$label_deadline = "label-primary";
				}else{
					$label_deadline = "label-danger";
				}
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
			<a class="btn btn-xs btn-primary" alt="<?php echo lang('btn_view_project'); ?>" title="<?php echo lang('btn_view_project'); ?>" href="<?php echo base_url('project/view/'. $project->project_id); ?>" id="view_project">
				<i class='glyphicon glyphicon-th-large'></i>
			</a>

			<a class="btn btn-xs btn-info" alt="<?php echo lang('btn_view_tasks'); ?>" title="<?php echo lang('btn_view_tasks'); ?>" href="<?php echo base_url('task/list/0/'. $project->project_id); ?>">
				<i class='glyphicon glyphicon-list-alt'></i>
			</a>
			
			<?php if($can_create_project){ ?>
				<a class="btn btn-xs btn-info" alt="<?php echo lang('btn_generate_report'); ?>" title="<?php echo lang('btn_generate_report'); ?>" href="<?php echo base_url('report/generate/'. $project->project_id); ?>" id="generate_report">
					<i class='glyphicon glyphicon-file'></i>
				</a>

				<a class="btn btn-xs btn-warning" alt="<?php echo lang('btn_edit_project'); ?>" title="<?php echo lang('btn_edit_project'); ?>" href="<?php echo base_url('project/edit/'. $project->project_id); ?>" id="create_project">
					<i class='glyphicon glyphicon-pencil'></i>
				</a>
			<?php } ?>
			
			<?php if($can_log_time){ ?>
				<a class="btn btn-xs btn-info <?php if($project->status == 'completed'){ echo "disabled"; } ?>" alt="<?php echo lang('btn_log_time'); ?>" title="<?php echo lang('btn_log_time'); ?>" href="<?php echo base_url('time_entry/start/'. $project->project_id); ?>" id="log_time_entry">
					<i class='glyphicon glyphicon-time'></i>
				</a>
			<?php } ?>
			
			<?php if($can_log_payment){ ?>
				<a class="btn btn-xs btn-success <?php if($project->status == 'completed'){ echo "disabled"; } ?>" alt="<?php echo lang('btn_log_payment'); ?>" title="<?php echo lang('btn_log_payment'); ?>" href="<?php echo base_url('finance/create/'.$project->client_id.'/'.$project->project_id); ?>" id="log_payment">
					<i class='glyphicon glyphicon-usd'></i>
				</a>
			<?php } ?>
			
			<?php if($this->session->userdata('access_level') == 1 || $this->session->userdata('access_level') == 2){ ?>
				<span class="btn btn-xs btn-danger" alt="<?php echo lang('btn_remove_project'); ?>" title="<?php echo lang('btn_remove_project'); ?>" id="delete_project_<?php echo $project->project_id; ?>">
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
					alertify.confirm("<?php echo lang('project_confirm_deletion'); ?>", function (e) {
						if (e) {
							var url = '<?php echo base_url('project/delete/'.$project->project_id); ?>';
						
							if (url) {
								window.location = url;
							}
							
						} else {
							alertify.error("<?php echo lang('project_not_removed'); ?>");
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
					var url = '<?php echo base_url("finance/create/".$project->client_id."/".$project->project_id); ?>';
					if (url) {
						window.location = url;
					}
				}
				
			},
			
			items: {
				"view": {name: "<?php echo lang('btn_view_project'); ?>", icon: "paste"},
				<?php if($can_create_project){ ?>
				"edit": {name: "<?php echo lang('btn_edit_project'); ?>", icon: "edit"},
				"delete": {name: "<?php echo lang('btn_remove_project'); ?>", icon: "delete"},
				<?php } ?>
				"sep1": "---------",
				<?php if($can_log_time && $project->status != 'completed'){ ?>
				"time_entry": {name: "<?php echo lang('btn_log_time'); ?>", icon: "time"},
				<?php } ?>
				<?php if($can_log_payment && $project->status != 'completed'){ ?>
				"finance": {name: "<?php echo lang('btn_log_payment'); ?>", icon: "usd"},
				<?php } ?>
			}
		});
	});
	

		reset = function () {
			$("toggleCSS").href = "<?php echo base_url('assets/js/alertify/themes/alertify.ptime.css'); ?>";
			alertify.set({
				labels : {
					ok     : "<?php echo lang('btn_delete'); ?>",
					cancel : "<?php echo lang('btn_keep'); ?>"
				},
				delay : 5000,
				buttonReverse : false,
				buttonFocus   : "ok"
			});
		};
		
	$("#delete_project_<?php echo $project->project_id; ?>").click(function () {
		reset();
		alertify.confirm("<?php echo lang('project_confirm_deletion'); ?>", function (e) {
			if (e) {
				var url = '<?php echo base_url("project/delete/".$project->project_id); ?>';
			
				if (url) {
					window.location = url;
				}
				
			} else {
				alertify.error("<?php echo lang('project_not_removed'); ?>");
			}
		});
		return false;
	});
	</script>
	
	<?php } ?>

</table>
	
<div style="text-align: center;"><?php print $pagination; ?></div>