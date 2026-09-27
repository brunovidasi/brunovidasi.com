<!DOCTYPE HTML>
<html>

<head>

	<meta charset="utf-8">
	<meta name="google-site-verification" content="zshqFWpEUnvEfLyeFENFfk86vxqcLR_nu_cc5DCV74c" />
	<link href="<?php echo base_url('favicon.ico');?>" rel='shortcut icon' type="image/x-icon" /> 
	<title><?php echo 'Login - ProgrammerTime'; ?></title>
	
	<link rel='shortcut icon' href='' type='image/x-icon'>
	<link href='' rel='icon'/>
	
	<link rel="stylesheet" href="<?php echo base_url('assets/css/login.css'); ?>">
	<script src="<?php echo base_url('assets/js/jquery.js');?>"></script>

	<script type="text/javascript">
	function loading() {
		document.getElementById('message').style.display = 'none';
		document.getElementById('loading').style.display = 'block';
		document.getElementById('form-login').style.display = 'none';
		document.getElementById('btn').setAttribute("style","background-color: #0077bb; cursor: default;");
		document.getElementById('btn').setAttribute("disabled","disabled");
		document.getElementById('form_login').submit();
	}
	</script> 

	<script type="text/javascript">
		var word = "<span style='color:#CCC;'>_</span>";
		var speed = 1000;
		var visible = 1;
		function blink() {
		
			if (visible == 1) {
				text.innerHTML = word;
				visible=0;
			} else {
				text.innerHTML = "<span style='color: transparent;'>_</span>";
				visible=1;
			}
			
		setTimeout("blink();",speed);
		}
	</script>
	
</head>

<body onload="blink();" ondragstart="return false" oncontextmenu="return false" onselectstart="return false" class="touch" data-twttr-rendered="true">
	
	<?php 
	
	$placeholder_user = lang('user');
	$placeholder_password = lang('password');
	$value_user = "";
	
	if($this->session->flashdata('login_error') == 'user_inactive'){
		$placeholder_user = lang('user_inactive');
	}
	
	elseif($this->session->flashdata('login_error') == 'wrong_user'){ 
		$placeholder_user = lang('user_not_found');
	}
	
	elseif($this->session->flashdata('login_error') == 'wrong_password'){ 
		$placeholder_password = lang('wrong_password');
		$value_user = $this->session->flashdata('login');
	}
	
	
	?>
	
	<div id='wrap'>
	
		<form action="<?php print base_url('auth/login') ?>" id="form_login" method="post">
		
			<div id="box_login">
				
				<!--img src="<?php echo base_url('assets/images/system/logo.png'); ?>"  style="width:20%"/--> 

				<div id="programmer_time" class="visible-lg visible-md visible-sm">P<span style="color:#CCC;" >rogrammer</span> Time <span id="text"></span></div>
				<!--<div id="programmer_time" class="visible-lg visible-md visible-sm">P <span style="color:#CCC;"><span id="text"></span></span></div>-->
				
				<div id="form-login" style="display:block;">
				
				<div id='login'>
					<section>
						<div>
							<input type='text' name="login" placeholder='<?php echo $placeholder_user ?>' value="<?php echo $value_user ?>">
						</div>
					</section>			
				</div>
				
				<div id='password'>
					<section>
						<div>
							<input type='password' name="password" placeholder='<?php echo $placeholder_password ?>'>
						</div>
					</section>
				</div>
				
				<button type="submit" class="btn btn-signin" onClick="loading()" id="btn"> <?php echo lang('btn_login'); ?> </button>
				
				<div id="message">
					<?php 

						echo $this->session->flashdata('message'); 

						if(isset($message))
							echo $message;

					?>
				</div>
				
				</div>
				
				<div id="loading" style="display:none;">
					<!--<img src="<?php echo base_url('assets/images/system/loading2.gif'); ?>"  style=""/> -->
					<?php # http://preloaders.net/ ?>
					<div class="bubblingG">
						<span id="bubblingG_1"></span>
						<span id="bubblingG_2"></span>
						<span id="bubblingG_3"></span>
					</div> 
					<!--div id="circularG">
						<div id="circularG_1" class="circularG"></div>
						<div id="circularG_2" class="circularG"></div>
						<div id="circularG_3" class="circularG"></div>
						<div id="circularG_4" class="circularG"></div>
						<div id="circularG_5" class="circularG"></div>
						<div id="circularG_6" class="circularG"></div>
						<div id="circularG_7" class="circularG"></div>
						<div id="circularG_8" class="circularG"></div>
					</div-->

				</div>
			
			</div>
		
		</form>
		
	</div>

</body> 

</html>