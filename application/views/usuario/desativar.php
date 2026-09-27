<br>

<?php
	require('application/views/includes/mensagem.php');
	if(validation_errors() != ''){
	echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	}
?>

<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">×</button>Solicitação de desligamento de conta no Programmer Time.</div>


<div id="desativar_usuario">
	
	<hr>
	
	<h1>ATENÇÃO!</h1>

	<p>Ao desativar-se, você não terá mais acesso ao Programmer Time.</p>
	
	<p>Suas atividades, projetos e horas continuarão cadastrados.</p>
	
	<p>Você só poderá ser reativado com a permissão do Administrador do Sistema.</p>

	<p>Você tem certeza que deseja ser desativado?</p>
	
	<br><br>
	
	<hr>
	
	<a href="<?php echo base_url('dashboard') ?>" class="btn btn-primary"><i class='glyphicon glyphicon-arrow-left'></i> Não desativar</a>
	
	<a href="<?php echo base_url('usuario/muda_status/inativo/'.$idusuario); ?>" class="btn btn-danger"><i class='glyphicon glyphicon-remove'></i> Desativar a minha conta</a>
	
	<hr>
	
</div>