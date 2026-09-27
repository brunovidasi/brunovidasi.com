<p class="page_title" style="">Create Help Article</p> <br>

<?php
	require('application/views/includes/message.php');
	if(validation_errors() != ''){
	echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	}
?>

<form action="<?php echo base_url('help/insert') ?>" method="post" name="form1" class="form1">

    <table width="100%" border="0" cellpadding="3" cellspacing="3" class="table_main" style="padding:10px;">
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Title:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="title" type="text" class="form-control" id="" placeholder="E.g. How do I log hours?" value="<?php echo set_value("title"); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Text:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-6 col-md-9 inputs">
					<textarea name="text" class="form-control" id="text_help"><?php echo set_value("text"); ?></textarea>
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Type:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="type" type="text" class="form-control" id="" placeholder="E.g. Logging time" value="<?php echo set_value("type"); ?>" />
				</div>
			</td>
		</tr>
		
		<input type="hidden" name="status" value="active" />
		
		<tr>
			<td valign="middle"></td>
			
			<td style="padding-left:14px">
				<input name="submit" type="submit" class="btn btn-primary" id="submit" value="Create Help Article" />
			</td>
		</tr>

	</table>
</form>
<br>

<script src="<?php echo base_url('assets/js/texteditor/jquery-te-1.4.0.min.js');?>" type="text/javascript"></script>
<link rel="stylesheet" href="<?php echo base_url('assets/js/texteditor/jquery-te-1.4.0.css');?>" type="text/css">

<script>$("#text_help").jqte({ol: false, ul: false, format: false});</script>