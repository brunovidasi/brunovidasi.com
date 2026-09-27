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
		document.getElementById('mensagem').style.display = 'none';
		document.getElementById('loading').style.display = 'block';
		document.getElementById('form-login').style.display = 'none';
		document.getElementById('btn').setAttribute("style","background-color: #0077bb; cursor: default;");
		document.getElementById('btn').setAttribute("disabled","disabled");
		document.getElementById('form_login').submit();
	}
	</script> 

	<script type="text/javascript">
		var palavra = "<span style='color:#CCC;'>_</span>";
		var velocidade = 1000;
		var valor = 1;
		function pisca() {
		
			if (valor == 1) {
				texto.innerHTML = palavra;
				valor=0;
			} else {
				texto.innerHTML = "<span style='color: transparent;'>_</span>";
				valor=1;
			}
			
		setTimeout("pisca();",velocidade);
		}
	</script>
	
</head>

<body onload="pisca();" ondragstart="return false" oncontextmenu="return false" onselectstart="return false" class="touch" data-twttr-rendered="true">
	
	<?php 
	
	$placeholder_usuario = lang('usuario');
	$placeholder_senha = lang('senha');
	$value_usuario = "";
	
	if($this->session->flashdata('controle') == 'usuario_inativo'){
		$placeholder_usuario = lang('usuario_inativo');
	}
	
	elseif($this->session->flashdata('controle') == 'usuario_incorreto'){ 
		$placeholder_usuario = lang('usuario_nao_encontrado');
	}
	
	elseif($this->session->flashdata('controle') == 'senha_incorreta'){ 
		$placeholder_senha = lang('senha_incorreta');
		$value_usuario = $this->session->flashdata('login');
	}
	
	
	?>
	
	<div id='wrap'>
	
		<form action="<?php print base_url('acesso/logar') ?>" id="form_login" method="post">
		
			<div id="box_login">
				
				<!--img src="<?php echo base_url('assets/images/sistema/logo.png'); ?>"  style="width:20%"/--> 

				<div id="programmer_time" class="visible-lg visible-md visible-sm">P<span style="color:#CCC;" >rogrammer</span> Time <span id="texto"></span></div>
				<!--<div id="programmer_time" class="visible-lg visible-md visible-sm">P <span style="color:#CCC;"><span id="texto"></span></span></div>-->
				
				<div id="form-login" style="display:block;">
				
				<div id='login'>
					<section>
						<div>
							<input type='text' name="login" placeholder='<?php echo $placeholder_usuario ?>' value="<?php echo $value_usuario ?>">
						</div>
					</section>			
				</div>
				
				<div id='senha'>
					<section>
						<div>
							<input type='password' name="senha" placeholder='<?php echo $placeholder_senha ?>'>
						</div>
					</section>
				</div>
				
				<button type="submit" class="btn btn-entrar" onClick="loading()" id="btn"> <?php echo lang('btn_login'); ?> </button>
				
				<div id="mensagem">
					<?php 

						echo $this->session->flashdata('mensagem'); 

						if(isset($mensagem))
							echo $mensagem;

					?>
				</div>
				
				</div>
				
				<div id="loading" style="display:none;">
					<!--<img src="<?php echo base_url('assets/images/sistema/loading2.gif'); ?>"  style=""/> -->
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