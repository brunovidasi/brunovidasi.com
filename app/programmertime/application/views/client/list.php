<link href="<?php echo base_url('assets/js/alertify/themes/alertify.ptime.css'); ?>" rel="stylesheet" type="text/css" id="toggleCSS"/>

<p class="page_title" style="float:left;"><?php echo lang('title_client_list'); ?></p> 

<div class="" style="float:right; margin-bottom:10px; width:50%;">
	
	<form action="<?php print base_url('client/list'); ?>" method="post" name="filter_form" id="filter_form" class="form-inline" style="display:inline; float:right; margin-top:26px; width:628px;">
		
		<input type="text" name="term" placeholder="<?php echo lang('placeholder_filter_client'); ?>" class="form-control" value="<?php print $this->session->userdata('c_term'); ?>" style="width:500px; float:left;"/>
		<a href="<?php echo base_url('client/create'); ?>" title="<?php echo lang('alt_create_client'); ?>" class="btn btn-primary" style="float:right; margin-right: 0px;"><i class='glyphicon glyphicon-plus'></i> <?php echo lang('btn_new'); ?></a>
		<button type="submit" title="<?php echo lang('alt_btn_search_client'); ?>" class="btn btn-primary" style="float:right; margin-right: 5px;"><i class='glyphicon glyphicon-search'></i></button>
	
	</form>
	
</div> <br><br><br><br>

<?php
	$can_create_client = $this->session->userdata('can_create_client');
	$can_edit_client = $this->session->userdata('can_edit_client');

	require('application/views/includes/message.php');
	if(validation_errors() != '')
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
?>

<table width="100%" class="table table-hover table-condensed" style="padding:10px;">

	<tr>
		<th width=""><?php echo lang('table_id'); ?></th>
		<th width=""><?php echo lang('table_name'); ?></th>
		<th width=""><?php echo lang('table_email'); ?></th>
		<th width="">Projects</th>
		<th width="135px"><?php echo lang('table_created_at'); ?></th>
		<th width="120px"></th>		
	</tr>
	
	<?php 
	if($clients->num_rows() == 0)
		echo '<tr><td colspan="5" align="center"><strong>'.lang('table_no_results').'</strong></td></tr>';
	
	foreach($clients->result() as $client){
		$class_tr = "";
		$danger = "";
		
		if($client->status == 'inactive'){
			$class_tr = 'danger';
			$danger = 'danger';
		}
	?>
	
	<tr class="<?php echo $class_tr; ?>" id="context_<?php echo $client->client_id; ?>">

		<td><a href="<?php echo base_url("client/view/".$client->client_id); ?>"># <?php echo $client->client_id; ?></a></td>

		<td><a href="<?php echo base_url("client/view/".$client->client_id); ?>"><?php echo $client->name; ?></a></td>

		<td><a href="mailto:<?php echo $client->email; ?>"><?php echo $client->email; ?></a></td>

		<td><?php echo $client->project_count; ?></td>

		<td><?php echo fdatetime($client->created_at, "/"); ?></td>
		
		<td align="right">
			<a class="btn btn-xs btn-primary" alt="<?php echo lang('btn_view_client'); ?>" title="<?php echo lang('btn_view_client'); ?>" href="<?php echo base_url('client/view/'. $client->client_id); ?>" id="view_client">
				<i class='glyphicon glyphicon-user'></i>
			</a>

			<?php if($client->email){ ?>
				<a class="btn btn-xs btn-info" alt="<?php echo lang('btn_send_email'); ?>" title="<?php echo lang('btn_send_email'); ?>" href="mailto:<?php echo $client->email; ?>" id="email_client">
					<i class='glyphicon glyphicon-envelope'></i>
				</a>
			<?php } ?>
			
			<?php if($can_edit_client){ ?>
				<a class="btn btn-xs btn-warning" alt="<?php echo lang('btn_edit_client'); ?>" title="<?php echo lang('btn_edit_client'); ?>" href="<?php echo base_url('client/edit/'. $client->client_id); ?>" id="can_edit_client">
					<i class='glyphicon glyphicon-edit'></i>
				</a>

				<?php if($this->session->userdata('access_level') == 1 || $this->session->userdata('access_level') == 2){ ?>

					<?php if($client->status == 'active'){ ?>
						<span class="btn btn-xs btn-danger" alt="<?php echo lang('btn_deactivate_client'); ?>" title="<?php echo lang('btn_deactivate_client'); ?>" id="deactivate_client_<?php echo $client->client_id; ?>">
							<i class='glyphicon glyphicon-remove'></i>
						</span>
					<?php }else{ ?>
						<span class="btn btn-xs btn-success" alt="<?php echo lang('btn_reactivate_client'); ?>" title="<?php echo lang('btn_reactivate_client'); ?>" id="reactivate_client_<?php echo $client->client_id; ?>">
							<i class='glyphicon glyphicon-ok'></i>
						</span>
					<?php } ?>
					
				<?php } ?>
			<?php } ?>
		</td>
		
	</tr>
	
	<script>	
	$(function(){
		$.contextMenu({
			selector: '#context_<?php echo $client->client_id; ?>', 
			
			callback: function(key, options) {
				
				if(key == "view"){
					var url = '<?php echo base_url("client/view/".$client->client_id); ?>';
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "edit"){
					var url = '<?php echo base_url("client/edit/".$client->client_id); ?>';
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "delete"){
					reset();
					var text = `<strong>Request to deactivate client <?php echo $client->name; ?></strong>
					<br /><br />
					Deactivating a client changes all of its projects that are in progress to "Cancelled".
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
				}
				
			},
			
			items: {
				"view": {name: "<?php echo lang('btn_view_client'); ?>", icon: "paste"},
				<?php if($can_edit_client){ ?>
				"edit": {name: "<?php echo lang('btn_edit_client'); ?>", icon: "edit"},
				"delete": {name: "<?php echo lang('btn_deactivate_client'); ?>", icon: "delete"},
				<?php } ?>
			}
		});
	});
	
	reset = function () {
		alertify.set({
			labels : {
				ok     : "<?php echo lang('yes'); ?>",
				cancel : "<?php echo lang('no'); ?>"
			},
			delay : 5000,
			buttonReverse : false,
			buttonFocus   : "ok"
		});
	};
		
	$("#deactivate_client_<?php echo $client->client_id; ?>").click(function () {
		reset();
		var text = `<strong>Request to deactivate client <?php echo $client->name; ?></strong>
		<br /><br />
		Deactivating a client changes all of its projects that are in progress to "Cancelled".
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
				alertify.error("<?php echo lang('client_not_deactivated');?>");
			}
		});
		return false;
	});
	
	$("#reactivate_client_<?php echo $client->client_id; ?>").click(function () {
		reset();
		alertify.confirm("<?php echo lang('client_confirm_reactivate');?>", function (e) {
			if (e) {
				var url = '<?php echo base_url("client//change_status/active/".$client->client_id); ?>';
			
				if (url) {
					window.location = url;
				}
				
			} else {
				alertify.error("<?php echo lang('client_not_reactivated');?>");
			}
		});
		return false;
	});
	</script>
	
	<?php } ?>

</table>
	
	<br>
		<span style=""><?php print $pagination; ?></span>
	<br>

<script>
$(function(){
	$('.per_page').bind('change', function () {
		var url = $(this).val();
		if(url){
			window.location = url;
		}
		return false;
	});
});
</script>