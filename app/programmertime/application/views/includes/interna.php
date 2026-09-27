<!DOCTYPE html>
<html>
<head>
	<title>ProgrammerTime</title>
	
	<link href="<?php echo base_url('favicon.ico');?>" rel='shortcut icon' type="image/x-icon" />
	
	<link href="<?php echo base_url('assets/css/estilo.css');?>" rel="stylesheet">
	
	<meta charset="UTF-8">
	<meta name="description" content="Sistema Gerenciador de Projetos para Desenvolvimento de Softwares">
	<meta name="keywords" content="programmer time, ptime, programmer, time">
	<meta name="author" content="Bruno Vieira">
	
	<script src="<?php echo base_url('assets/js/jquery.js');?>"></script>
	<script src="<?php echo base_url('assets/js/jquery.maskedinput.js');?>"></script>
	<script src="<?php echo base_url('assets/js/pegarhoras.js');?>"></script>
	
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
	<script src="<?php echo base_url('assets/js/convertermoeda.js'); ?>"></script>
	<script type="text/javascript" src="<?php echo base_url('assets/js/atualizaAjax.js'); ?>"></script> 

	<!--<script src="<?php echo base_url('assets/adminLTE/js/select2/i18n/pt-BR.js'); ?>" type="text/javascript"></script>-->
    <link href="<?php echo base_url('assets/adminLTE/js/select2/select2.css'); ?>" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url('assets/adminLTE/js/select2/select2-bootstrap.css'); ?>" rel="stylesheet" type="text/css" />
    <script src="<?php echo base_url('assets/adminLTE/js/select2/select2.full.min.js'); ?>" type="text/javascript"></script>
	
	<style>
		<?php if(!empty($background)){ 
				if($background == TRUE){ ?>
		body{
			background-image: url("<?php echo base_url('assets/images/sistema/fundo.png'); ?>");
			background-repeat: no-repeat;
			background-attachment: fixed;
		}
		<?php } } ?>
	</style>

	<?php if(!$this->session->flashdata("logou")){ ?>
		<script language="JavaScript">
			var palavra = "_";
			var velocidade = 1000;
			var valor = 1;
			function pisca() {
			
				if (valor == 1) {
					texto.innerHTML = palavra;
					texto_mobile.innerHTML = palavra;
					valor=0;
				} else {
					texto.innerHTML = "";
					texto_mobile.innerHTML = "";
					valor=1;
				}
				
			setTimeout("pisca();",velocidade);
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

<body <?php if(!$this->session->flashdata("logou")){ echo 'onload="pisca();"'; }?>>
	<?php 
		require('application/views/includes/header.php'); 
		if(!empty($view)) echo $view;
		require('application/views/includes/footer.php'); 
	?>
</body>

</html>