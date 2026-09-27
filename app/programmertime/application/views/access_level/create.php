<link href="<?php echo base_url('assets/js/icheck/skins/line/blue.css'); ?>" rel="stylesheet">
<script src="<?php echo base_url('assets/js/icheck/icheck.js'); ?>"></script>


<p class="page_title" style="float:left;">New Access Level</p> <br>
<br><br>
<?php
	require('application/views/includes/message.php');
	if(validation_errors() != '')
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
?>

<form action="<?php echo base_url('access_level/insert') ?>" method="post" name="form1" class="form1">

    <table width="100%" border="0" cellpadding="3" cellspacing="3" class="table_main" style="padding:10px;">
		
		<tr>
			<td width="15%" valign="middle">
				<div class="inputs">
					<strong>Role Name:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="85%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="role" type="text" class="form-control" id="" value="<?php echo set_value("role"); ?>" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td  valign="top">
				<div class="inputs">
					<strong>Create Projects:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td  valign="middle">
				<div class="col-lg-10 col-md-10 inputs" id="can_create_project">
					<input type="radio" name="can_create_project" value="yes" <?php echo set_radio('can_create_project', 'yes', TRUE); ?> /> Yes
					<input type="radio" name="can_create_project" value="no" <?php echo set_radio('can_create_project', 'no'); ?>  /> No
				</div>
			</td>
		</tr>
		
		<tr>
			<td  valign="top">
				<div class="inputs">
					<strong>Edit Projects:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td  valign="middle">
				<div class="col-lg-10 col-md-10 inputs" id="can_edit_project">
					<input type="radio" name="can_edit_project" value="yes" <?php echo set_radio('can_edit_project', 'yes', TRUE); ?> /> Yes
					<input type="radio" name="can_edit_project" value="no" <?php echo set_radio('can_edit_project', 'no'); ?>  /> No
				</div>
			</td>
		</tr>
		
		<tr>
			<td  valign="top">
				<div class="inputs">
					<strong>Create Clients:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td  valign="middle">
				<div class="col-lg-10 col-md-10 inputs" id="can_create_client">
					<input type="radio" name="can_create_client" value="yes" <?php echo set_radio('can_create_client', 'yes', TRUE); ?> /> Yes
					<input type="radio" name="can_create_client" value="no" <?php echo set_radio('can_create_client', 'no'); ?>  /> No
				</div>
			</td>
		</tr>
		
		<tr>
			<td  valign="top">
				<div class="inputs">
					<strong>Edit Clients:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td  valign="middle">
				<div class="col-lg-10 col-md-10 inputs" id="can_edit_client">
					<input type="radio" name="can_edit_client" value="yes" <?php echo set_radio('can_edit_client', 'yes', TRUE); ?> /> Yes
					<input type="radio" name="can_edit_client" value="no" <?php echo set_radio('can_edit_client', 'no'); ?>  /> No
				</div>
			</td>
		</tr>
		
		<tr>
			<td  valign="top">
				<div class="inputs">
					<strong>Log Time:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td  valign="middle">
				<div class="col-lg-10 col-md-10 inputs" id="can_log_time">
					<input type="radio" name="can_log_time" value="yes" <?php echo set_radio('can_log_time', 'yes', TRUE); ?> /> Yes
					<input type="radio" name="can_log_time" value="no" <?php echo set_radio('can_log_time', 'no'); ?>  /> No
				</div>
			</td>
		</tr>
		
		<tr>
			<td  valign="top">
				<div class="inputs">
					<strong>Log Payments:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td  valign="middle">
				<div class="col-lg-10 col-md-10 inputs" id="can_log_payment">
					<input type="radio" name="can_log_payment" value="yes" <?php echo set_radio('can_log_payment', 'yes', TRUE); ?> /> Yes
					<input type="radio" name="can_log_payment" value="no" <?php echo set_radio('can_log_payment', 'no'); ?>  /> No
				</div>
			</td>
		</tr>
		
		<tr>
			<td  valign="top">
				<div class="inputs">
					<strong>Send Reports:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td  valign="middle">
				<div class="col-lg-10 col-md-10 inputs" id="can_send_report">
					<input type="radio" name="can_send_report" value="yes" <?php echo set_radio('can_send_report', 'yes', TRUE); ?> /> Yes
					<input type="radio" name="can_send_report" value="no" <?php echo set_radio('can_send_report', 'no'); ?>  /> No
				</div>
			</td>
		</tr>
		
		<tr>
			<td  valign="top">
				<div class="inputs">
					<strong>Create Users:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td  valign="middle">
				<div class="col-lg-10 col-md-10 inputs" id="can_create_user">
					<input type="radio" name="can_create_user" value="yes" <?php echo set_radio('can_create_user', 'yes', TRUE); ?> /> Yes
					<input type="radio" name="can_create_user" value="no" <?php echo set_radio('can_create_user', 'no'); ?>  /> No
				</div>
			</td>
		</tr>
		
		<tr>
			<td  valign="top">
				<div class="inputs">
					<strong>Edit Users:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td  valign="middle">
				<div class="col-lg-10 col-md-10 inputs" id="can_edit_user">
					<input type="radio" name="can_edit_user" value="yes" <?php echo set_radio('can_edit_user', 'yes', TRUE); ?> /> Yes
					<input type="radio" name="can_edit_user" value="no" <?php echo set_radio('can_edit_user', 'no'); ?>  /> No
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td valign="middle"></td>
			
			<td style="padding-left:14px">
				<button name="submit" type="submit" class="btn btn-primary" id="submit" />Create Access Level</button>
			</td>
		</tr>
		

	</table>
</form>
<br>

<script>
$(document).ready(function(){
  // $('input').each(function(){
  //   var self = $(this),
  //     label = self.next(),
  //     label_text = label.text();

  //   label.remove();
  //   self.iCheck({
  //     checkboxClass: 'icheckbox_line-blue',
  //     radioClass: 'iradio_line-blue',
  //     insert: '<div class="icheck_line-icon">' + label_text + '</div>'
  //   });
  // });
});

</script>