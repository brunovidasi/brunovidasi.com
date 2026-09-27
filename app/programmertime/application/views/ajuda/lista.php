<p class="titulo_pagina" style="">Está com dúvidas no Programmer Time? </p> 

<!--<div class="" style="float:right; margin-bottom:10px; width:100%;">
	
	<form action="<?php print base_url('cliente/termo'); ?>" method="post" name="formfiltrotermo" id="formfiltrotermo" class="form-inline" style="display:inline; float:right; margin-top:26px; width:550px;">
		<input type="text" name="termo" class="form-control" value="<?php print $this->session->userdata('termo'); ?>" style="width:500px; float:left;"/>
		<button type="submit" title="Filtrar Usuário" class="btn btn-primary" style="float:right; margin-right: 5px;"><i class='glyphicon glyphicon-search'></i></button>
	</form>
	
</div> <br>-->

<?php

	$cadastra_cliente = $this->session->userdata('cadastra_cliente');
	$edita_cliente = $this->session->userdata('edita_cliente');

	require('application/views/includes/mensagem.php');
	if(validation_errors() != ''){
	echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	}
?>

<div >
<div class="panel-group" id="accordion">

<?php
	if($ajudas->num_rows() == 0){
		echo 'Não existe ajuda cadastrada.';
	}
	
	foreach($ajudas->result() as $ajuda){
		
	?>
	
	<div class="col-lg-12">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h4 class="panel-title" style="font-size: 13px;"><a data-toggle="collapse" data-parent="#accordion" href="#collapse<?php echo $ajuda->idajuda ?>"><?php echo $ajuda->titulo; ?></a></h4>
			</div>
			<div id="collapse<?php echo $ajuda->idajuda; ?>" class="panel-collapse collapse">
				<div class="panel-body">
						<?php echo $ajuda->texto; ?>
						
						<?php if($this->session->userdata('nivel_acesso') == 1){ ?>
							<hr>
							<a href="<?php echo base_url('ajuda/editar/'.$ajuda->idajuda);?>" class="btn btn-warning btn-xs">Editar</a>
							<a href="<?php echo base_url('ajuda/delete/'.$ajuda->idajuda);?>" class="btn btn-danger btn-xs">Excluir</a>
						<?php }?>
				</div>
			</div>
		</div><br>
	</div>
	
	<?php } ?>
</div>