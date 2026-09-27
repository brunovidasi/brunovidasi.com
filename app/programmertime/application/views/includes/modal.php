<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">

	<title>Programmer Time</title>

	<link href="<?php echo base_url('favicon.ico');?>" rel='shortcut icon' type="image/x-icon" />

	<link href="<?php echo base_url('assets/css/style.css');?>" rel="stylesheet">

	<script src="<?php echo base_url('assets/js/jquery.js');?>"></script>
	<script src="<?php echo base_url('assets/js/jquery.maskedinput.js');?>"></script>
	<script src="<?php echo base_url('assets/js/time_capture.js');?>"></script>
	
	<link href="<?php echo base_url('assets/js/contextmenu/src/jquery.contextMenu.css'); ?>" rel="stylesheet" type="text/css" />
	<script src="<?php echo base_url('assets/js/contextmenu/src/jquery.contextMenu.js'); ?>"></script>
	<script src="<?php echo base_url('assets/js/contextmenu/src/jquery.ui.position.js'); ?>"></script>
	
	<script src="<?php echo base_url('assets/js/alertify/lib/alertify.min.js'); ?>"></script>
	<link href="<?php echo base_url('assets/js/alertify/themes/alertify.core.css'); ?>" rel="stylesheet" type="text/css" />
	<link href="<?php echo base_url('assets/js/alertify/themes/alertify.ptime.css'); ?>" rel="stylesheet" type="text/css" />
	
	<link href="<?php echo base_url('assets/js/icheck/skins/minimal/blue.css'); ?>" rel="stylesheet">
	<script src="<?php echo base_url('assets/js/icheck/icheck.js'); ?>"></script>
	
	<link href="<?php echo base_url('assets/css/bootstrap.css'); ?>" rel="stylesheet" type="text/css" />

	<script src="<?php echo base_url('assets/js/bootstrap.js'); ?>"></script>
	<script src="<?php echo base_url('assets/js/currency_format.js'); ?>"></script>
	<script type="text/javascript" src="<?php echo base_url('assets/js/inlineUpdate.js'); ?>"></script> 

	<script language="JavaScript">
		$(document).ready(function(){
			 $('input').iCheck({
				checkboxClass: 'icheckbox_minimal-blue',
				radioClass: 'iradio_minimal-blue',
				increaseArea: '20%'
			 });
		});
	</script>

</head>

<body>
	<?php
	if (!empty($view)) {
		echo $view;
	}
	?>
</body>

</html>