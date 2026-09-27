<p class="page_title" style="">Add Image</p> <br>

<?php
	require('application/views/includes/message.php');
	if(validation_errors() != ''){
	echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	}
?>


<form action="<?php echo base_url('image/insert_image/') ?>" method="post" name="form1" class="form1">
	<table width="100%" border="0" cellpadding="3" cellspacing="3" class="table_main" style="padding:10px;">
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Image Title:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="title" type="text" class="form-control" id="" value="<?php echo set_value("title"); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong>Description:</strong> <span class="required">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-5 com-md-8 inputs">
					<textarea name="description" class="form-control" id="description" rows="4"><?php echo set_value('description');  ?></textarea>
					<div id="description_char_count"></div>
				</div>
			<td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Image:</strong> <span class="required">*</span>
				</div>
			</td> 
			
			<td>
				
			</td>
		</tr>
		
		<tr>
			<td valign="middle"></td>
			
			<td style="padding-left:14px">
				<button type="submit" name="submit" id="submit" class="btn btn-primary"> Add Image</button> 
			</td>
		</tr>
		
	</table>
</form>

<script>
jQuery(document).ready(function($){
	var text_max = 1000;
	
	$('#description_char_count').html(text_max + ' characters left.');
	
	$('#description').keyup(function() {
		var text_length = $('#description').val().length;
		var text_remaining = text_max - text_length;
		$('#description_char_count').html(text_remaining + ' characters left.');
	});
});
</script>