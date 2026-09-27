<!DOCTYPE HTML>
<html>

<head>

	<meta charset="utf-8">
	<title><?php echo 'ProgrammerTime'; ?></title>
	
	<link href="<?php echo base_url('favicon.ico'); ?>" rel='shortcut icon' type="image/x-icon" /> 
	
	<link rel="stylesheet" href="<?php echo base_url('assets/css/first_access.css'); ?>">
	
	<script type="text/javascript">
	function loading() {
		document.getElementById('message').style.display = 'none';
		document.getElementById('loading').style.display = 'block';
		document.getElementById('btn').setAttribute("style","background-color: #0077bb; cursor: default;");
		document.getElementById('btn').setAttribute("disabled","disabled");
		document.getElementById('form_login').submit();
	}
	</script> 
	
</head>

<body ondragstart="return false" oncontextmenu="return false" onselectstart="return false" class="touch" data-twttr-rendered="true">
	
	<div id='wrap'>
	
		<form action="<?php print base_url('auth/create') ?>" id="form_login" method="post">
		
			<div id="box_login">
				<div id="message">
					<?php
						if(validation_errors() != ''){
							echo validation_errors('', '<br/>');
						}else{
							echo lang('msg_welcome');
						}
					?>
				</div>
				
				<br>
				
				<div class='input'>
					<section>
						<div>
							<input type='text' name="name" placeholder='<?php echo lang('lbl_your_full_name'); ?>'>
						</div>
					</section>			
				</div>
				
				<div class='input'>
					<section>
						<div>
							<input type='text' name="email" placeholder='<?php echo lang('lbl_your_email'); ?>'>
						</div>
					</section>			
				</div>
				
				<div class='input'>
					<section>
						<div>
							<input type='text' name="login" placeholder='<?php echo lang('lbl_your_username'); ?>'>
						</div>
					</section>			
				</div>
				
				<div class='input'>
					<section>
						<div>
							<input type='password' name="password" placeholder='<?php echo lang('lbl_your_password'); ?>'>
						</div>
					</section>
				</div>
				
				<div class='input'>
					<section>
						<div>
							<input type='password' name="confirmation_password" placeholder='<?php echo lang('lbl_repeat_password'); ?>'>
						</div>
					</section>
				</div>
				
				<button type="submit" class="btn btn-signin" onClick="loading()" id="btn"><?php echo lang('btn_continue'); ?> ></button>
				
				<div id="message">
					<?php echo $this->session->flashdata('message'); ?>
				</div>
				
				<div id="loading" style="display:none;">
					<img src="<?php echo base_url('assets/images/system/loading2.gif'); ?>"  style=""/> 
				</div>
			
			</div>
		
		</form>
		
	</div>

</body> 

</html>