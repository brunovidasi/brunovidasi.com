<script src="<?php echo base_url('assets/js/jquery.mousewheel.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/timeentry/jquery.timeentry.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/dateentry/jquery.dateentry.js'); ?>" type="text/javascript"></script>

<script src="<?php echo base_url('assets/js/jquery.maskedinput.js');?>"></script>
<link rel="stylesheet" href="<?php echo base_url('assets/css/datepicker.css');?>" type="text/css">
<script src="<?php echo base_url('assets/js/bootstrap-datepicker.js');?>" type="text/javascript"></script>

<p class="page_title" style="float:left;">Add Project Payment</p> <br>

<?php
	require('application/views/includes/message.php');
	if(validation_errors() != '')
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
?>

<form action="<?php echo base_url('finance/insert') ?>" method="post" name="form1" class="form1">

    <table width="100%" border="0" cellpadding="3" cellspacing="3" class="table_main" style="padding:10px;">

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Client:</strong> <!--<span class="required">*</span>-->
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-5 col-md-4 inputs">
					<select name="client_id" class="form-control select2" id="client_id">
						<option hidden></option>
						<?php
						foreach($clients->result() as $client){
							$selected = "";
							if(set_value('client_id', $client_id) == $client->client_id){
								$selected = 'selected="selected"';
							}
							echo '<option value="'. $client->client_id .'" '. $selected .'>'. $client->name .'</option>';
						}						
						?>
					</select>
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Project:</strong> <!--<span class="required">*</span>-->
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-6 col-md-8 inputs">
					<select name="project_id" class="form-control select2" id="project_id">
						<option hidden></option>
						<?php
							$cid = set_value('client_id', $client_id);
							if(empty($cid)){
								echo '<option value="">Select the client</option>';
							}else{
								echo $project_options;
							}
						?>
					</select>
				</div>
				<div class="col-lg-6 col-md-4 col-sm-4 inputs" style="display:inline">
					<span id="loader_projects"></span>
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Type:</strong> <!--<span class="required">*</span>-->
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-8 inputs">
					<input type="radio" name="type" value="project_cost"  <?php echo set_radio('type', 'project_cost'); ?>/> Project Fee
					<input type="radio" name="type" value="external_cost" <?php echo set_radio('type', 'external_cost'); ?> /> Operating / External Cost
					<input type="radio" name="type" value="other_cost"  <?php echo set_radio('type', 'other_cost'); ?> /> Other
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Paid By:</strong> <!--<span class="required">*</span>-->
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-8 inputs">
					<input type="radio" name="paid_by" value="company"  <?php echo set_radio('paid_by', 'company'); ?>/> Company
					<input type="radio" name="paid_by" value="client" <?php echo set_radio('paid_by', 'client'); ?> /> Client
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong>Description:</strong> <!--<span class="required">*</span>-->
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-5 inputs">
					<input name="description" type="text" class="form-control" id="" value="<?php echo set_value("description"); ?>" />
				</div>
			<td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong>Amount:</strong> <!--<span class="required">*</span>-->
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-2 col-md-3 inputs">
					<input name="amount" type="text" class="form-control currency" id="" value="<?php echo set_value("amount"); ?>" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Status:</strong> <!--<span class="required">*</span>-->
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-8 inputs">
					<input type="radio" name="status" value="unpaid" onClick="toggle_fields('unpaid')" <?php echo set_radio('status', 'unpaid'); ?>/> Unpaid
					<input type="radio" name="status" value="invoiced" onClick="toggle_fields('invoiced')" <?php echo set_radio('status', 'invoiced'); ?> /> Invoiced
					<input type="radio" name="status" value="partially_paid" onClick="toggle_fields('partially_paid')" <?php echo set_radio('status', 'partially_paid'); ?> /> Partially Paid
					<input type="radio" name="status" value="paid" onClick="toggle_fields('paid')" <?php echo set_radio('status', 'paid'); ?> /> Paid
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td valign="middle">
				<div class="inputs" id="amount_paid" style="display:block;">
					<strong>Amount Paid:</strong> <!--<span class="required">*</span>-->
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-2 col-md-3 inputs" id="amount_paid2" style="display:block;">
					<input name="amount_paid" type="text" class="form-control currency" id="" value="<?php echo set_value("amount_paid"); ?>" />
				</div>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs" id="invoiced_date_row" style="display:block;">
					<strong>Invoiced On:</strong> <!--<span class="required">*</span>-->
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-2 col-md-3 inputs" id="invoiced_date2" style="display:block;">
					<input name="invoiced_date" type="text" class="form-control datepicker" id="invoiced_date" value="<?php echo set_value("invoiced_date", date("d/m/Y")); ?>" maxlength="10" />
				</div>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs" id="paid_date_row" style="display:block;">
					<strong>Paid On:</strong> <!--<span class="required">*</span>-->
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-2 col-md-3 inputs" id="paid_date2" style="display:block;">
					<input name="paid_date" type="text" class="form-control datepicker" id="paid_date" value="<?php echo set_value("paid_date"); ?>" maxlength="10" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>External Link:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-6 col-md-8 inputs">
					<input name="link" type="text" class="form-control" id="" value="<?php echo set_value("link"); ?>" />
				</div>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong>Notes:</strong> <span class="required"></span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-5 com-md-8 inputs">
					<textarea name="notes" class="form-control" id="notes" rows="4"><?php echo set_value('notes');  ?></textarea>
					<div id="notes_char_count"></div>
				</div>
			<td>
		</tr>
		
		
		<tr>
			<td valign="middle"></td>
			
			<td style="padding-left:14px">
				<input name="submit" type="submit" class="btn btn-primary" id="submit" value="Add Payment" />
			</td>
		</tr>

	</table>
</form>
<br>

<script>

	jQuery(document).ready(function($){


		$("select[name=client_id]").change(function(){
			$("html").css("cursor", "progress");
			$("select[name=project_id]").html('<option value="0">Loading projects ...</option>');
			$("#loader_projects").html("<img src='<?php echo base_url('assets/images/system/ajax_loader.gif'); ?>' width='30px'/>");
			
			$.post("<?php echo base_url('time_entry/get_projects/'.$project); ?>", {client_id:$(this).val()}, function(response){
				$("select[name=project_id]").html(response);
				$("#loader_projects").html("");
				$("html").css("cursor", "auto");
			});
		});
		
		$(function() {
			$("#datepicker").datepicker();
			$("#invoiced_date").datepicker();
			$("#paid_date").datepicker();
			$("#datepicker").mask("99/99/9999");
			$(".datepicker").mask("99/99/9999");
		});

		$('.currency').priceFormat({
			prefix: '$ ',
			centsSeparator: '.',
			thousandsSeparator: ','
		});
		
		// Limit the number of characters in the field
		var text_max = 500;
		$('#notes_char_count').html(text_max + ' characters left.');
		$('#notes').keyup(function() {
			var text_length = $('#notes').val().length;
			var text_remaining = text_max - text_length;
			$('#notes_char_count').html(text_remaining + ' characters left.');
		});
		
		$('.datepicker').dateEntry({
			dateFormat: 'dmy/',
			spinnerImage: '',
			monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'], 
			monthNamesShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'], 
			dayNames: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
			dayNamesShort: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
			useMouseWheel: false,
			minDate: null, 
			maxDate: null,
		});
		
	});

	function toggle_fields(option) {

		if(option == 'paid') {
			document.getElementById('amount_paid').style.display = 'block';
			document.getElementById('paid_date_row').style.display = 'block';
			document.getElementById('invoiced_date_row').style.display = 'none';
			document.getElementById('amount_paid2').style.display = 'block';
			document.getElementById('paid_date2').style.display = 'block';
			document.getElementById('invoiced_date2').style.display = 'none';
		}

		if(option == 'unpaid') {
			document.getElementById('amount_paid').style.display = 'none';
			document.getElementById('paid_date_row').style.display = 'none';
			document.getElementById('invoiced_date_row').style.display = 'none';
			document.getElementById('amount_paid2').style.display = 'none';
			document.getElementById('paid_date2').style.display = 'none';
			document.getElementById('invoiced_date2').style.display = 'none';
		}

		if(option == 'invoiced') {
			document.getElementById('amount_paid').style.display = 'none';
			document.getElementById('paid_date_row').style.display = 'none';
			document.getElementById('invoiced_date_row').style.display = 'block';
			document.getElementById('amount_paid2').style.display = 'none';
			document.getElementById('paid_date2').style.display = 'none';
			document.getElementById('invoiced_date2').style.display = 'block';
		}
		
		if(option == 'partially_paid') {
			document.getElementById('amount_paid').style.display = 'block';
			document.getElementById('paid_date_row').style.display = 'block';
			document.getElementById('invoiced_date_row').style.display = 'none';
			document.getElementById('amount_paid2').style.display = 'block';
			document.getElementById('paid_date2').style.display = 'block';
			document.getElementById('invoiced_date2').style.display = 'none';
		}
	}
	
	<?php
	if(set_value('status') == 'paid'){
	?>
		document.getElementById('amount_paid').style.display = 'block';
		document.getElementById('paid_date_row').style.display = 'block';
		document.getElementById('invoiced_date_row').style.display = 'none';
		document.getElementById('amount_paid2').style.display = 'block';
		document.getElementById('paid_date2').style.display = 'block';
		document.getElementById('invoiced_date2').style.display = 'none';
	<?php } ?>
	
	<?php
	if(set_value('status') == 'unpaid'){
	?>
		document.getElementById('amount_paid').style.display = 'none';
		document.getElementById('paid_date_row').style.display = 'none';
		document.getElementById('invoiced_date_row').style.display = 'none';
		document.getElementById('amount_paid2').style.display = 'none';
		document.getElementById('paid_date2').style.display = 'none';
		document.getElementById('invoiced_date2').style.display = 'none';
	<?php } ?>
	
	<?php
	if(set_value('status') == 'invoiced'){
	?>
		document.getElementById('amount_paid').style.display = 'none';
		document.getElementById('paid_date_row').style.display = 'none';
		document.getElementById('invoiced_date_row').style.display = 'block';
		document.getElementById('amount_paid2').style.display = 'none';
		document.getElementById('paid_date2').style.display = 'none';
		document.getElementById('invoiced_date2').style.display = 'block';
	<?php } ?>
	
	<?php
	if(set_value('status') == 'partially_paid'){
	?>
		document.getElementById('amount_paid').style.display = 'block';
		document.getElementById('paid_date_row').style.display = 'block';
		document.getElementById('invoiced_date_row').style.display = 'none';
		document.getElementById('amount_paid2').style.display = 'block';
		document.getElementById('paid_date2').style.display = 'block';
		document.getElementById('invoiced_date2').style.display = 'none';
	<?php } ?>
</script> 
     