<p class="page_title" style="float:left;">Manage Access Levels</p> <br>

<div style="float:right;">

<div class="btn-group" style="">
    <button type="button" class="btn btn-danger dropdown-toggle" data-toggle="dropdown">
		<i class='glyphicon glyphicon-remove'></i> Delete
		<span class="caret"></span>
    </button>
    <ul class="dropdown-menu">
		<?php foreach($access_level->result() as $level){
					if($level->id != '1'){ ?>
					
		<li><a href="<?php echo base_url('access_level/delete/'.$level->id); ?>"><?php echo $level->role; ?></a></li>
		
		<?php 		} 
			   }
		?>
    </ul>
</div>
 
 <a href="<?php echo base_url('access_level/create'); ?>"><span class="btn btn-primary"><i class='glyphicon glyphicon-plus'></i> Add Access Level</span></a>
</div>
<br><br>

<?php require('application/views/includes/message.php'); ?>

    <table width="100%" border="0" cellpadding="3" cellspacing="3" class="table table-hover" style="padding:10px;">
		
		<tr>
			<th width="28%"><i class='glyphicon glyphicon-user'></i> Access Level</th>
			<th width="8%" style="text-align:center;"><i class='glyphicon glyphicon-pencil'></i> Create Projects</th>
			<th width="8%" style="text-align:center;"><i class='glyphicon glyphicon-edit'></i> Edit Projects</th>
			<th width="8%" style="text-align:center;"><i class='glyphicon glyphicon-user'></i> Create Clients</th>
			<th width="8%" style="text-align:center;"><i class='glyphicon glyphicon-edit'></i> Edit Clients</th>
			<th width="8%" style="text-align:center;"><i class='glyphicon glyphicon-time'></i> Log Time</th>
			<th width="8%" style="text-align:center;"><i class='glyphicon glyphicon-usd'></i> Log Payments</th> 
			<th width="8%" style="text-align:center;"><i class='glyphicon glyphicon-file'></i> Send Reports</th> 
			<th width="8%" style="text-align:center;"><i class='glyphicon glyphicon-user'></i> Create Users</th> 
			<th width="8%" style="text-align:center;"><i class='glyphicon glyphicon-edit'></i> Edit Users</th> 
		</tr>
		
		<?php foreach($access_level->result() as $level){
				if($level->id != '1'){
		?>
		
			<tr>
			
				<td class="td_level" valign="middle"><?php echo $level->role; ?></td>
				
				<td align="center">
				
				<?php if($level->can_create_project == 'yes'){ ?>
				
					<input type='image' width="20px" alt='yes' title='yes' src='<?php echo base_url('assets/images/system/yes.png'); ?>' onclick="level(this, '<?php echo base_url('assets/'); ?>', 'access_level', 'id', 'can_create_project', '<?php echo $level->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $level->role; ?>', 'create projects');" />
				
				<?php }else{ ?>
				
					<input type='image' width="20px" alt='no' title='no' src='<?php echo base_url('assets/images/system/no.png'); ?>' onclick="level(this, '<?php echo base_url('assets/'); ?>', 'access_level', 'id', 'can_create_project', '<?php echo $level->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $level->role; ?>', 'create projects');" />
				
				<?php } ?>
				
				</td>
				
				<td align="center">
				
				<?php if($level->can_edit_project == 'yes'){ ?>
				
					<input type='image' width="20px" alt='yes' title='yes' src='<?php echo base_url('assets/images/system/yes.png'); ?>' onclick="level(this, '<?php echo base_url('assets/'); ?>', 'access_level', 'id', 'can_edit_project', '<?php echo $level->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $level->role; ?>', 'edit projects');" />
				
				<?php }else{ ?>
				
					<input type='image' width="20px" alt='no' title='no' src='<?php echo base_url('assets/images/system/no.png'); ?>' onclick="level(this, '<?php echo base_url('assets/'); ?>', 'access_level', 'id', 'can_edit_project', '<?php echo $level->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $level->role; ?>', 'edit projects');" />
				
				<?php } ?>
				
				</td>
				
				<td align="center">
				
				<?php if($level->can_create_client == 'yes'){ ?>
				
					<input type='image' width="20px" alt='yes' title='yes' src='<?php echo base_url('assets/images/system/yes.png'); ?>' onclick="level(this, '<?php echo base_url('assets/'); ?>', 'access_level', 'id', 'can_create_client', '<?php echo $level->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $level->role; ?>', 'create clients');" />
				
				<?php }else{ ?>
				
					<input type='image' width="20px" alt='no' title='no' src='<?php echo base_url('assets/images/system/no.png'); ?>' onclick="level(this, '<?php echo base_url('assets/'); ?>', 'access_level', 'id', 'can_create_client', '<?php echo $level->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $level->role; ?>', 'create clients');" />
				
				<?php } ?>
				
				</td>
				
				<td align="center">
				
				<?php if($level->can_edit_client == 'yes'){ ?>
				
					<input type='image' width="20px" alt='yes' title='yes' src='<?php echo base_url('assets/images/system/yes.png'); ?>' onclick="level(this, '<?php echo base_url('assets/'); ?>', 'access_level', 'id', 'can_edit_client', '<?php echo $level->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $level->role; ?>', 'edit clients');" />
				
				<?php }else{ ?>
				
					<input type='image' width="20px" alt='no' title='no' src='<?php echo base_url('assets/images/system/no.png'); ?>' onclick="level(this, '<?php echo base_url('assets/'); ?>', 'access_level', 'id', 'can_edit_client', '<?php echo $level->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $level->role; ?>', 'edit clients');" />
				
				<?php } ?>
				
				</td>
				
				<td align="center">
				
				<?php if($level->can_log_time == 'yes'){ ?>
				
					<input type='image' width="20px" alt='yes' title='yes' src='<?php echo base_url('assets/images/system/yes.png'); ?>' onclick="level(this, '<?php echo base_url('assets/'); ?>', 'access_level', 'id', 'can_log_time', '<?php echo $level->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $level->role; ?>', 'log time');" />
				
				<?php }else{ ?>
				
					<input type='image' width="20px" alt='no' title='no' src='<?php echo base_url('assets/images/system/no.png'); ?>' onclick="level(this, '<?php echo base_url('assets/'); ?>', 'access_level', 'id', 'can_log_time', '<?php echo $level->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $level->role; ?>', 'log time');" />
				
				<?php } ?>
				
				</td>
				
				<td align="center">
				
				<?php if($level->can_log_payment == 'yes'){ ?>
				
					<input type='image' width="20px" alt='yes' title='yes' src='<?php echo base_url('assets/images/system/yes.png'); ?>' onclick="level(this, '<?php echo base_url('assets/'); ?>', 'access_level', 'id', 'can_log_payment', '<?php echo $level->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $level->role; ?>', 'log payments');" />
				
				<?php }else{ ?>
				
					<input type='image' width="20px" alt='no' title='no' src='<?php echo base_url('assets/images/system/no.png'); ?>' onclick="level(this, '<?php echo base_url('assets/'); ?>', 'access_level', 'id', 'can_log_payment', '<?php echo $level->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $level->role; ?>', 'log payments');" />
				
				<?php } ?>
				
				<td align="center">
				
				<?php if($level->can_send_report == 'yes'){ ?>
				
					<input type='image' width="20px" alt='yes' title='yes' src='<?php echo base_url('assets/images/system/yes.png'); ?>' onclick="level(this, '<?php echo base_url('assets/'); ?>', 'access_level', 'id', 'can_send_report', '<?php echo $level->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $level->role; ?>', 'send reports');" />
				
				<?php }else{ ?>
				
					<input type='image' width="20px" alt='no' title='no' src='<?php echo base_url('assets/images/system/no.png'); ?>' onclick="level(this, '<?php echo base_url('assets/'); ?>', 'access_level', 'id', 'can_send_report', '<?php echo $level->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $level->role; ?>', 'send reports');" />
				
				<?php } ?>
				
				<td align="center">
				
				<?php if($level->can_create_user == 'yes'){ ?>
				
					<input type='image' width="20px" alt='yes' title='yes' src='<?php echo base_url('assets/images/system/yes.png'); ?>' onclick="level(this, '<?php echo base_url('assets/'); ?>', 'access_level', 'id', 'can_create_user', '<?php echo $level->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $level->role; ?>', 'create users');" />
				
				<?php }else{ ?>
				
					<input type='image' width="20px" alt='no' title='no' src='<?php echo base_url('assets/images/system/no.png'); ?>' onclick="level(this, '<?php echo base_url('assets/'); ?>', 'access_level', 'id', 'can_create_user', '<?php echo $level->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $level->role; ?>', 'create users');" />
				
				<?php } ?>
				
				<td align="center">
				
				<?php if($level->can_edit_user == 'yes'){ ?>
				
					<input type='image' width="20px" alt='yes' title='yes' src='<?php echo base_url('assets/images/system/yes.png'); ?>' onclick="level(this, '<?php echo base_url('assets/'); ?>', 'access_level', 'id', 'can_edit_user', '<?php echo $level->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $level->role; ?>', 'edit users');" />
				
				<?php }else{ ?>
				
					<input type='image' width="20px" alt='no' title='no' src='<?php echo base_url('assets/images/system/no.png'); ?>' onclick="level(this, '<?php echo base_url('assets/'); ?>', 'access_level', 'id', 'can_edit_user', '<?php echo $level->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $level->role; ?>', 'edit users');" />
				
				<?php } ?>
				
				</td>
				
			</tr>
			
		<?php }
		} ?>
		
		

	</table>
	
<br/>