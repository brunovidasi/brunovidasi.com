<br>

<?php
	require('application/views/includes/message.php');
	if(validation_errors() != ''){
	echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	}
?>

<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">×</button>Request to close your Programmer Time account.</div>


<div id="deactivate">
	
	<hr>
	
	<h1>WARNING!</h1>

	<p>Once you deactivate your account, you will no longer have access to Programmer Time.</p>
	
	<p>Your activity, projects and hours will be kept.</p>
	
	<p>You can only be reactivated with the system administrator's permission.</p>

	<p>Are you sure you want to deactivate your account?</p>
	
	<br><br>
	
	<hr>
	
	<a href="<?php echo base_url('dashboard') ?>" class="btn btn-primary"><i class='glyphicon glyphicon-arrow-left'></i> Don't deactivate</a>
	
	<a href="<?php echo base_url('user/change_status/inactive/'.$user_id); ?>" class="btn btn-danger"><i class='glyphicon glyphicon-remove'></i> Deactivate my account</a>
	
	<hr>
	
</div>