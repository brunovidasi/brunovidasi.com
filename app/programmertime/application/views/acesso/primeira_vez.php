<!DOCTYPE HTML>
<html>

<head>

	<meta charset="utf-8">
	<title><?php echo 'ProgrammerTime'; ?></title>
	
	<link href="<?php echo base_url('favicon.ico'); ?>" rel='shortcut icon' type="image/x-icon" /> 
	
	<link rel="stylesheet" href="<?php echo base_url('assets/css/primeira_vez.css'); ?>">
	
	<script type="text/javascript">
	function loading() {
		document.getElementById('mensagem').style.display = 'none';
		document.getElementById('loading').style.display = 'block';
		document.getElementById('btn').setAttribute("style","background-color: #0077bb; cursor: default;");
		document.getElementById('btn').setAttribute("disabled","disabled");
		document.getElementById('form_login').submit();
	}
	</script> 
	
</head>

<body ondragstart="return false" oncontextmenu="return false" onselectstart="return false" class="touch" data-twttr-rendered="true">
	
	<div id='wrap'>
	
		<form action="<?php print base_url('acesso/cadastrar') ?>" id="form_login" method="post">
		
			<div id="box_login">
				<div id="mensagem">
					<?php
						if(validation_errors() != ''){
							echo validation_errors('', '<br/>');
						}else{
							echo lang('msg_bem_vindo');
						}
					?>
				</div>
				
				<br>
				
				<div class='input'>
					<section>
						<div>
							<input type='text' name="nome" placeholder='<?php lang('lbl_seu_nome_completo'); ?>'>
						</div>
					</section>			
				</div>
				
				<div class='input'>
					<section>
						<div>
							<input type='text' name="email" placeholder='<?php lang('lbl_seu_email'); ?>'>
						</div>
					</section>			
				</div>
				
				<div class='input'>
					<section>
						<div>
							<input type='text' name="login" placeholder='<?php lang('lbl_seu_nome_usuario'); ?>'>
						</div>
					</section>			
				</div>
				
				<div class='input'>
					<section>
						<div>
							<input type='password' name="senha" placeholder='<?php lang('lbl_sua_senha'); ?>'>
						</div>
					</section>
				</div>
				
				<div class='input'>
					<section>
						<div>
							<input type='password' name="confirmacao_senha" placeholder='<?php lang('lbl_repita_senha'); ?>'>
						</div>
					</section>
				</div>
				
				<button type="submit" class="btn btn-entrar" onClick="loading()" id="btn"><?php echo lang('btn_prosseguir'); ?> ></button>
				
				<div id="mensagem">
					<?php echo $this->session->flashdata('mensagem'); ?>
				</div>
				
				<div id="loading" style="display:none;">
					<img src="<?php echo base_url('assets/images/sistema/loading2.gif'); ?>"  style=""/> 
				</div>
			
			</div>
		
		</form>
		
	</div>

</body> 

</html>