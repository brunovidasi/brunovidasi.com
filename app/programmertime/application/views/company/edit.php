<p class="page_title" style="">Edit Company Profile</p> <br>

<?php
	require('application/views/includes/message.php');
	if(validation_errors() != ''){
	echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	}
?>

<form action="<?php echo base_url('company/update/') ?>" method="post" name="form1" class="form1">

    <table width="100%" border="0" cellpadding="3" cellspacing="3" class="table_main" style="padding:10px;">
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Company Name:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="name" type="text" class="form-control" id="" value="<?php echo set_value("name", $company->name); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Legal Name:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="legal_name" type="text" class="form-control" id="" value="<?php echo set_value("legal_name", $company->legal_name); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Company No.:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="company_tax_id" type="text" class="form-control" id="" value="<?php echo set_value("company_tax_id", $company->company_tax_id); ?>" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Email:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="email" type="text" class="form-control" id="" value="<?php echo set_value("email", $company->email); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Phone:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="phone" type="text" class="form-control" id="" value="<?php echo set_value("phone", $company->phone); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Mobile:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="mobile" type="text" class="form-control" id="" value="<?php echo set_value("mobile", $company->mobile); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Website:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="website" type="text" class="form-control" id="" value="<?php echo set_value("website", $company->website); ?>" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Founded On:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="founding_date" type="text" class="form-control" id="founding_date" value="<?php echo set_value("founding_date", fdate($company->founding_date, "/")); ?>" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Address:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="address" type="text" class="form-control" id="" value="<?php echo set_value("address", $company->address); ?>" />
				</div>
			</td>
		</tr>
		
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td valign="middle"></td>
			
			<td style="padding-left:14px">
				<button name="submit" type="submit" class="btn btn-primary" id="submit" />Save Company Profile</button>
			</td>
		</tr>
		

	</table>
</form>
<br>

<script src="<?php echo base_url('assets/js/jquery.maskedinput.js');?>"></script>
<link rel="stylesheet" href="<?php echo base_url('assets/js/datepiker/themes/base/jquery.ui.all.css');?>" type="text/css">
<script src="<?php echo base_url('assets/js/datepiker/ui/jquery.ui.core.js');?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/datepiker/ui/jquery.ui.datepicker.js');?>" type="text/javascript"></script>

<script>
function empty(v){
	if ((v == null) || (v == 0) || (v == '') || (v == "") || (v == undefined)){
		return true
	}else {
		return false
	}	
}

$(document).ready(function () {
	
	
	$("#founding_date").mask("99/99/9999");
	
	reset = function () {
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
	
	
});
</script>