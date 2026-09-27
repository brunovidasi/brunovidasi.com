<p class="titulo_pagina" style="float:left;"><?php echo lang('titulo_lista_etapas'); ?></p> <br><br><br>

<?php

	$lanca_etapa = $this->session->userdata('lanca_etapa');

	require('application/views/includes/mensagem.php');
	if(validation_errors() != '')
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
?>
<?php if($etapas_andamento->num_rows() != 0){ ?>
	
<table width="100%" class="table table-hover table-condensed" style="padding:10px;">
	
	<tr class="info"><td colspan="10"><strong><?php echo lang('etapas_andamento'); ?></strong></td></tr>
	
	<tr>
		<th width=""><?php echo lang('tabela_projeto'); ?></th>
		<th width=""><?php echo lang('tabela_fase'); ?></th>
		<th width=""><?php echo lang('tabela_descricao_tecnica'); ?></th>
		<!-- <th width=""><?php echo lang('tabela_descricao_cliente'); ?></th> -->
		<th width=""><?php echo lang('tabela_usuario'); ?></th>
		<th width=""><?php echo lang('tabela_data'); ?></th>
		<th width=""><?php echo lang('tabela_inicio'); ?></th>
		<th width="" colspan="2"><?php echo lang('tabela_tempo'); ?></th>
		<th width="70px"></th>		
	</tr>
	
	<?php foreach($etapas_andamento->result() as $etapa){ ?>
		
	<tr>
		<td><a href="<?php echo base_url('projeto/visualizar/'. $etapa->idprojeto); ?>"><?php echo $etapa->nomeprojeto; ?></a></td>
		<td><?php echo $etapa->fase; ?></td>
		<td><?php echo $etapa->descricao_tecnica; ?></td>
		<!-- <td><?php echo $etapa->descricao_cliente; ?></td> -->
		<td><a href="<?php echo base_url('usuario/visualizar/'. $etapa->idusuario); ?>"><?php echo $etapa->responsavel; ?></a></td>
		<td><?php echo fdata($etapa->data, '/'); ?></td>
		<td><?php echo fhora($etapa->inicio); ?></td>
		<td colspan="2"><?php if($etapa->data == date('Y-m-d')){
			echo calcular_horas(date('H:i:s'), $etapa->inicio);
		}else{
			echo "<span style='color:red'>".lang('encerrar')."</span>";
		} ?></td>
		<td><a class="btn btn-xs btn-warning" href="<?php echo base_url('etapa/editar/'. $etapa->idetapa); ?>"><i class="glyphicon glyphicon-edit"></i></a>
			<a class="btn btn-xs btn-danger" href="<?php echo base_url('etapa/delete/'. $etapa->idetapa); ?>"><i class="glyphicon glyphicon-remove"></i></a></td>
	</tr>
	
	<?php } ?>
	
	</table>
	
<?php } ?>
	
	<table width="100%" class="table table-hover table-condensed" style="padding:10px;">
	
	<tr class="info"><td colspan="10"><strong><?php echo lang('etapas_concluidas'); ?></strong></td></tr>
	
	<tr>
		<th width=""><?php echo lang('tabela_projeto'); ?></th>
		<th width=""><?php echo lang('tabela_fase'); ?></th>
		<th width=""><?php echo lang('tabela_descricao_tecnica'); ?></th>
		<!-- <th width=""><?php echo lang('tabela_descricao_cliente'); ?></th> -->
		<th width=""><?php echo lang('tabela_usuario'); ?></th>
		<th width=""><?php echo lang('tabela_data'); ?></th>
		<th width=""><?php echo lang('tabela_inicio'); ?></th>
		<th width=""><?php echo lang('tabela_fim'); ?></th>
		<th width=""><?php echo lang('tabela_tempo'); ?></th>
		<th width="70px"></th>		
	</tr>
	
	<?php if($etapas->num_rows() == 0){ ?>
		<tr><td colspan="10" style="text-align:center;"><?php echo lang('nao_existe_etapas'); ?></td></tr>
	<?php } ?>
	
	<?php foreach($etapas->result() as $etapa){ ?>
		
	<tr>
		<td><a href="<?php echo base_url('projeto/visualizar/'. $etapa->idprojeto); ?>"><?php echo $etapa->nomeprojeto; ?></a></td>
		<td><?php echo $etapa->fase; ?></td>
		<td><?php echo $etapa->descricao_tecnica; ?></td>
		<!-- <td><?php echo $etapa->descricao_cliente; ?></td> -->
		<td><a href="<?php echo base_url('usuario/visualizar/'. $etapa->idusuario); ?>"><?php echo $etapa->responsavel; ?></a></td>
		<td><?php echo fdata($etapa->data, '/'); ?></td>
		<td><?php echo fhora($etapa->inicio); ?></td>
		<td><?php echo fhora($etapa->fim); ?></td>
		<td><?php echo calcular_horas($etapa->fim, $etapa->inicio); ?></td>
		<td><a class="btn btn-xs btn-warning" href="<?php echo base_url('etapa/editar/'. $etapa->idetapa); ?>"><i class="glyphicon glyphicon-edit"></i></a>
			<a class="btn btn-xs btn-danger" href="<?php echo base_url('etapa/delete/'. $etapa->idetapa); ?>"><i class="glyphicon glyphicon-remove"></i></a></td>
	</tr>
	
	<?php } ?>

</table>
	
		<div style="text-align:center;"><?php print $paginacao; ?></div>
	<br>