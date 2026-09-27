<p class="page_title" style="float:left;"><?php echo lang('title_user_list'); ?></p> 

<div class="" style="float:right; margin-bottom:10px; width:50%;">
	
	<form action="<?php print base_url('user/list'); ?>" method="post" name="filter_form" id="filter_form" class="form-inline" style="display:inline; float:right; margin-top:26px; width:628px;">
		<input type="text" name="term" placeholder="<?php echo lang('placeholder_filter_user'); ?>" class="form-control" value="<?php print $this->session->userdata('term'); ?>" style="width:500px; float:left;"/>
		<a href="<?php echo base_url('user/create'); ?>" title="<?php echo lang('alt_create_user'); ?>" class="btn btn-primary" style="float:right; margin-right: 0px;"><i class='glyphicon glyphicon-plus'></i> <?php echo lang('btn_new'); ?></a>
		<button type="submit" title="<?php echo lang('alt_btn_search_user'); ?>" class="btn btn-primary" style="float:right; margin-right: 5px;"><i class='glyphicon glyphicon-search'></i></button>
	</form>
	
</div> <br><br><br><br>

<?php

	$can_create_user = $this->session->userdata('can_create_user');
	$can_edit_user = $this->session->userdata('can_edit_user');

	require('application/views/includes/message.php');
	if(validation_errors() != ''){
	echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	}
?>

<table width="100%" class="table table-hover table-condensed" style="padding:10px;">

	<tr>
		<th width=""><?php echo lang('table_id'); ?></th>
		<th width="180px"><?php echo lang('table_login'); ?></th>
		<th width=""><?php echo lang('table_name'); ?></th>
		<th width=""><?php echo lang('table_email'); ?></th>		
		<th width="200px"><?php echo lang('table_access_level'); ?></th>		
		<th width="125px"><?php echo lang('table_created_at'); ?></th>		
		<th width="125px"><?php echo lang('table_last_access'); ?></th>	
		<th width="80px" style="text-align:center;"><?php echo lang('table_confirmed'); ?></th>	
		<th width="100px"></th>		
	</tr>
	
	<?php 

	if($users->num_rows() <= 1)
		echo '<tr><td colspan="9" align="center"><strong>'.lang('table_no_results').'</strong></td></tr>';
	
	foreach($users->result() as $user){

		if($user->user_id == '1'){
			if(empty($term)){
				echo '<tr class="" id="context_1">
						<td># 1</td>
						<td>admin</td>
						<td>'.lang('administrator').'</td>
						<td></td>
						<td>'.lang('administrator').'</td>
						<td></td>
						<td></td>
						<td align="center"></td>
						<td></td>
					</tr>';
			}
			continue;
		}
		
	?>
	
	<tr class="<?php echo ($user->status == 'inactive') ? 'danger' : ''; ?>" id="context_<?php echo $user->user_id; ?>">

		
		<td><a href="<?php echo base_url('user/view/'. $user->user_id); ?>"># <?php echo $user->user_id; ?></a></td>
		<td><?php echo $user->login; ?></td>
		<td><?php echo $user->name; ?></td>
		<td><a href="mailto:<?php echo $user->email; ?>"><?php echo $user->email; ?></a></td>
		<td><?php echo $user->role; ?></td>
		<td><?php echo fdatetime($user->created_at,"/"); ?></td>
		
		<td><?php
			if(!empty($user->last_access))
				echo '<span title="'. $user->login_count .' logins">'. fdatetime($user->last_access ,"/") . '</span>';
		?></td>
		
		<td align="center">
		<?php
			if($user->confirmed == 'yes')
				echo '<label class="label label-primary">'.lang('yes').'</label>';
			else
				echo '<label class="label label-danger">'.lang('no').'</label>';			
		?>
		</td>
		
		<td align="right">
			<a class="btn btn-xs btn-primary" alt="<?php echo lang('btn_view_user'); ?>" title="<?php echo lang('btn_view_user'); ?>" href="<?php echo base_url('user/view/'. $user->user_id); ?>" id="view_user">
				<i class='glyphicon glyphicon-user'></i>
			</a>
			
			<?php if($can_edit_user){ ?>
				<a class="btn btn-xs btn-warning" alt="<?php echo lang('btn_edit_user'); ?>" title="<?php echo lang('btn_edit_user'); ?>" href="<?php echo base_url('user/edit/'. $user->user_id); ?>" id="can_edit_user">
					<i class='glyphicon glyphicon-edit'></i>
				</a>

				<?php if($user->status == 'active'){ ?>
					<span class="btn btn-xs btn-danger" alt="<?php echo lang('btn_deactivate_user'); ?>" title="<?php echo lang('btn_deactivate_user'); ?>" id="delete_user_<?php echo $user->user_id; ?>">
						<i class='glyphicon glyphicon-remove'></i>
					</span>
				<?php }else{ ?>
					<span class="btn btn-xs btn-success" alt="<?php echo lang('btn_reactivate_user'); ?>" title="<?php echo lang('btn_reactivate_user'); ?>" id="reactivate_user_<?php echo $user->user_id; ?>">
						<i class='glyphicon glyphicon-ok'></i>
					</span>
				<?php } ?>
			<?php } ?>
		</td>
		
		
	</tr>
	
	<script>	
	$(function(){
		$.contextMenu({
			selector: '#context_<?php echo $user->user_id; ?>', 
			
			callback: function(key, options) {
				
				if(key == "view"){
					var url = '<?php echo base_url("user/view/".$user->user_id); ?>';
					if (url) {
						window.location = url;
					}
				}

				<?php if($can_edit_user){ ?>
				
				if(key == "edit"){
					var url = '<?php echo base_url("user/edit/".$user->user_id); ?>';
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "delete"){
					if(validateForm()){
						var url = '<?php echo base_url("user/change_status/inactive/".$user->user_id); ?>';
						if (url) {
							window.location = url;
						}
					}
				}

				<?php } ?>
				
			},
			
			items: {
				"view": {name: "<?php echo lang('btn_view_user'); ?>", icon: "paste"},
				<?php if($can_edit_user){ ?>
				"edit": {name: "<?php echo lang('btn_edit_user'); ?>", icon: "edit"},
				"delete": {name: "<?php echo lang('btn_deactivate_user'); ?>", icon: "delete"},
				<?php } ?>
			}
		});
	});
	
	reset = function () {
		//$("toggleCSS").href = "<?php echo base_url('assets/js/alertify/themes/alertify.ptime.css'); ?>";
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
		
	$("#delete_user_<?php echo $user->user_id; ?>").click(function () {
		reset();
		alertify.confirm("<?php echo lang('user_confirm_deactivation', '', $user->name); ?>", function (e) {
			if (e) {
				var url = '<?php echo base_url("user/change_status/inactive/".$user->user_id); ?>';
			
				if (url) {
					window.location = url;
				}
				
			} else {
				alertify.error("<?php echo lang('user_not_deactivated'); ?>");
			}
		});
		return false;
	});
	
	$("#reactivate_user_<?php echo $user->user_id; ?>").click(function () {
		reset();
		alertify.confirm("<?php echo lang('user_confirm_reactivate'); ?>", function (e) {
			if (e) {
				var url = '<?php echo base_url("user/change_status/active/".$user->user_id); ?>';
			
				if (url) {
					window.location = url;
				}
				
			} else {
				alertify.error("<?php echo lang('user_not_reactivated'); ?>");
			}
		});
		return false;
	});
	</script>
	
	<?php } ?>

</table>
	
	<br><span style=""><?php print $pagination; ?></span><br>

<script>
$(function(){
  $('.per_page').bind('change', function () {
	  var url = $(this).val();
	  if (url) {
		  window.location = url;
	  }
	  return false;
  });
});
</script>