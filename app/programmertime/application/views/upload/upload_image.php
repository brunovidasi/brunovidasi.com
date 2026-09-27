
<div id="modal" style="padding:20px;">
    <div class="header">
        <h4>Choose an Image</h4>
        <div class="clear"></div>
    </div>
    <div id="form_validation_errors" style="<?php echo (validation_errors()) ? "display:block;" : "display:none;"; ?>">
        <ul id="validation_errors">
            <?php echo validation_errors('<li>', '</li>'); ?>
        </ul>
    </div>

    <?php
	require('application/views/includes/message.php');
	?>
	<div class="form-style">
		<form action="<?php echo base_url('upload/save_upload'); ?>" method="post" id="form_upload" onsubmit="return check_form(this)" enctype="multipart/form-data">
			<input type="hidden" id="source" name="source" value="<?php echo set_value('source', $parms->source); ?>"  />
			<input type="hidden" id="destination" name="destination" value="<?php echo set_value('destination', $parms->destination); ?>"  />
			<input type="hidden" id="height" name="height" value="<?php echo set_value('height', $parms->height); ?>"  />
			<input type="hidden" id="width" name="width" value="<?php echo set_value('width', $parms->width); ?>"  />
			<ul>
				<li>
					<label>Image: </label>
					<input id="photo" type="file" validate="true"  validate_label="Image" name="photo" size="50" />
				</li>  
				<li>    
					<label></label>     
					<div style="text-align:right;"><button type="submit" class="btn btn-primary">Upload</button></div>
				</li>   
			</ul>
		</form>
	</div>
</div>

<!-- CSS -->
<link href="<?php echo base_url('assets/css/bootstrap.css'); ?>" rel="stylesheet">
<link href="<?php echo base_url('assets/css/modal.css'); ?>" rel="stylesheet">

<!-- JS -->
<script src="<?php echo base_url('assets/js/jquery.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/bootstrap.js'); ?>"></script> 
<script src="<?php echo base_url('assets/js/jcrop/js/jquery.Jcrop.js'); ?>" type="text/javascript"></script>
<script charset="UTF-8" src="<?php echo base_url('assets/js/validate_form.js'); ?>" type="text/javascript"></script>
