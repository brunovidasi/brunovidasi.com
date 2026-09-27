<form action="<?php echo base_url('report/generate_pdf/') ?>" method="post" name="form1" class="form1" target="_blank">
	
	<p class="page_title" style="float:left;"><?php echo $report->title; ?></p>
	
	<?php if($report->title != "ERROR"){ ?>
	<div style="float:right; margin-top: 20px; margin-right: 12px;">
		<button name="submit" type="submit" class="btn btn-lg btn-danger" id="submit" value="Send Report" ><i class="glyphicon glyphicon-download-alt"></i> Export to PDF</button>
	</div>
	<?php } ?>
	
	<?php
		require('application/views/includes/message.php');
		if(validation_errors() != ''){
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
		}
	?>
	
    <table width="100%" border="0" cellpadding="3" cellspacing="3" class="table_main" style="">
		
		<tr>
			<td style="width:100%;" valign="middle">
				<div class="col-lg-4 col-md-8 col-sm-8 inputs" style="width:100%;">
					<div class="generating">
						Generating report... 
						<br /><br /><img src="<?php echo base_url('assets/images/system/ajax_loader.gif'); ?>" />
					</div>
					<textarea name="report" id="report" class="form-control report" style="display:none;">
						<?php  echo set_value('report', $report->html); ?>
					</textarea>


				</div>
			</td>
		</tr>
		
		<tr>
			<td style="padding-left:14px">
				<input name="user_id" type="hidden" value="<?php echo $this->session->userdata('id'); ?>" />
				<input name="title" type="hidden" value="<?php echo $report->title; ?>" />
				
			</td>
		</tr>
		
	</table>
	
</form>
<br>

<script src="<?php echo base_url('assets/js/jquery.maskedinput.js');?>"></script>
<script src="<?php echo base_url('assets/js/ckeditor/ckeditor.js'); ?>"></script>

<script>
	CKEDITOR.replace("report", {
		customConfig: '<?php echo base_url('assets/js/ckeditor/custom/editor_basic_config.js'); ?>',
		height: '550px',
		width: '100%',

	});

	$(document).ready(function(){
		$(".generating").slideUp(1000);
	});
</script>
     