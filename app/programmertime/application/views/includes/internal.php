<!DOCTYPE html>
<html>
<head>
	<title>ProgrammerTime</title>
	
	<link href="<?php echo base_url('favicon.ico');?>" rel='shortcut icon' type="image/x-icon" />
	
	<link href="<?php echo base_url('assets/css/style.css');?>" rel="stylesheet">
	
	<meta charset="UTF-8">
	<meta name="description" content="Project management system for software development teams">
	<meta name="keywords" content="programmer time, ptime, programmer, time">
	<meta name="author" content="Bruno Vieira">
	
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

    <link href="<?php echo base_url('assets/adminLTE/js/select2/select2.css'); ?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url('assets/adminLTE/js/select2/select2-bootstrap.css'); ?>" rel="stylesheet" type="text/css" />
    <script src="<?php echo base_url('assets/adminLTE/js/select2/select2.full.min.js'); ?>" type="text/javascript"></script>
	
	<style>
		<?php if(!empty($background)){ 
				if($background == TRUE){ ?>
		body{
			background-image: url("<?php echo base_url('assets/images/system/background.png'); ?>");
			background-repeat: no-repeat;
			background-attachment: fixed;
		}
		<?php } } ?>
	</style>

	<?php if(!$this->session->flashdata("just_logged_in")){ ?>
		<script language="JavaScript">
			var word = "_";
			var speed = 1000;
			var visible = 1;
			function blink() {
			
				if (visible == 1) {
					text.innerHTML = word;
					text_mobile.innerHTML = word;
					visible=0;
				} else {
					text.innerHTML = "";
					text_mobile.innerHTML = "";
					visible=1;
				}
				
			setTimeout("blink();",speed);
			}
			
			$(document).ready(function(){
				 $('input').iCheck({
					checkboxClass: 'icheckbox_minimal-blue',
					radioClass: 'iradio_minimal-blue',
					increaseArea: '20%'
				 });
			});
		</script>
	<?php } ?>

</head>

<body <?php if(!$this->session->flashdata("just_logged_in")){ echo 'onload="blink();"'; }?>>
	<?php 
		require('application/views/includes/header.php'); 
		if(!empty($view)) echo $view;
		require('application/views/includes/footer.php'); 
	?>
</body>

</html>