<p class="page_title" style="float:left">Edit Client</p> <br>
<br><br>
<?php
	require('application/views/includes/message.php');
	if(validation_errors() != '')
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
?>

<form action="<?php echo base_url('client/update/'.$client->client_id) ?>" method="post" name="form1" class="form1">

    <table width="100%" border="0" cellpadding="3" cellspacing="3" class="table_main" style="padding:10px;">
		
		<tr><td><br></td><td><strong style="margin-left: 15px;">General Details</strong></td></tr>

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Name:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="name" type="text" class="form-control" id="" value="<?php echo set_value("name", $client->name); ?>" />
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
					<input name="website" type="url" class="form-control" id="" value="<?php echo set_value("website", $client->website); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Email:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-7 inputs">
					<input name="email" type="text" class="form-control" id="" value="<?php echo set_value("email", $client->email); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Phone:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-3 col-md-6 inputs">
					<input name="phone" type="text" class="form-control phone" id="" value="<?php echo set_value("phone", $client->phone); ?>" style="width:140px;" />
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
				<div class="col-lg-3 col-md-6 inputs">
					<input name="mobile" type="text" class="form-control phone" id="" value="<?php echo set_value("mobile", $client->mobile); ?>" style="width:140px;" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Legal Name:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="legal_name" type="text" class="form-control" id="" value="<?php echo set_value("legal_name", $client->legal_name); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Company No.:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="company_tax_id" type="text" class="form-control" id="company_tax_id" value="<?php echo set_value("company_tax_id", $client->company_tax_id); ?>" style="width:160px;" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Tax ID:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="tax_id" type="text" class="form-control" id="tax_id" value="<?php echo set_value("tax_id", $client->tax_id); ?>" style="width:130px;" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		<tr><td><br></td><td><strong style="margin-left: 15px;">Additional Contact</strong></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Name:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="contact_name" type="text" class="form-control" id="" value="<?php echo set_value("contact_name", $client->contact_name); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Email:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="contact_email" type="text" class="form-control" id="" value="<?php echo set_value("contact_email", $client->contact_email); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Phone:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="contact_phone" type="text" class="form-control phone" id="" value="<?php echo set_value("contact_phone", $client->contact_phone); ?>" style="width:140px;" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		<tr><td><br></td><td><strong style="margin-left: 15px;">Address</strong></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Postcode:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-2 col-md-3 inputs">
					<input name="address_postcode" type="text" class="form-control" id="postcode" value="<?php echo set_value("address_postcode", $client->address_postcode); ?>" />
				</div>
			</td>
		</tr>

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Street:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="address" type="text" class="form-control" id="street" value="<?php echo set_value("address", $client->address); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Number:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-1 col-md-2 inputs">
					<input name="address_number" type="text" class="form-control" id="address_number" value="<?php echo set_value("address_number", $client->address_number); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Address Line 2:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="address_line2" type="text" class="form-control" id="" value="<?php echo set_value("address_line2", $client->address_line2); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>District:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-2 col-md-3 inputs">
					<input name="address_district" type="text" class="form-control" id="district" value="<?php echo set_value("address_district", $client->address_district); ?>" />
				</div>
			</td>
		</tr>

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>City:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-2 col-md-3 inputs">
					<input name="address_city" type="text" class="form-control" id="city" value="<?php echo set_value("address_city", $client->address_city); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>State:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-2 col-md-3 inputs">
					<input name="address_state" type="text" class="form-control" id="state" value="<?php echo set_value("address_state", $client->address_state); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td valign="middle"></td>
			
			<td style="padding-left:14px">
				<button name="submit" type="submit" class="btn btn-primary" id="submit" />Save Changes</button>
			</td>
		</tr>
		
		

	</table>
</form>
<br>
