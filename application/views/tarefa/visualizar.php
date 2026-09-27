<script src="<?php echo base_url('assets/js/jquery.mousewheel.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/timeentry/jquery.timeentry.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/dateentry/jquery.dateentry.js'); ?>" type="text/javascript"></script>

<p class="titulo_pagina" style="float:left">Tarefa: <?php echo $tarefa->nome; ?></p> <br>


<div style="float:right;">

	<a class="btn btn-primary" title="Ver minhas tarefas" href="<?php echo base_url('tarefa/lista/'.$this->session->userdata('id')); ?>"><i class="glyphicon glyphicon-arrow-left"></i> Ver minhas tarefas</a>
	<a class="btn btn-primary" title="Ver tarefas deste projeto" href="<?php echo base_url('tarefa/lista/0/'.$tarefa->idprojeto); ?>">Ver tarefas deste projeto</a>
	<a class="btn btn-info" title="Nova Tarefa" href="<?php echo base_url('tarefa/cadastrar/'.$tarefa->idprojeto.'/'.$tarefa->idusuario_responsavel); ?>"><i class="glyphicon glyphicon-plus"></i> Nova Tarefa</a>
	<a class="btn btn-info" title="Lançar Horas" href="<?php echo base_url('etapa/lancar/'.$tarefa->idprojeto.'/'. $tarefa->idtarefa); ?>"><i class="glyphicon glyphicon-time"></i></a>
	<a href="<?php echo base_url('tarefa/editar/'.$tarefa->idtarefa); ?>"><span class="btn btn-warning" title="Editar Tarefa"><i class='glyphicon glyphicon-edit'></i> <?php echo lang('btn_editar'); ?></span></a>

</div><br><br><br>

<?php require('application/views/includes/mensagem.php'); ?>
<!--
<pre>
<?php print_r($tarefa); ?>
</pre>
-->
<div class="col-lg-6">

	<div class="jumbotron">
	  <p><?php echo $tarefa->descricao; ?></p>
	</div>

	<?php
		$class_tr = "";
		$danger = "";
		
		if($tarefa->data_prazo <= date('Y-m-d H:i:s')){
			$class_tr = 'danger';
			$danger = 'danger';
		}

		if($tarefa->status == 'cancelado')
			$class_tr = 'danger';
		
		if($tarefa->status == 'concluido')
			$class_tr = '';
	?>

	<table class="table table-condensed">
		<tr>
			<th>Projeto</th>
			<td><a href="<?php echo base_url('projeto/visualizar/'.$tarefa->idprojeto); ?>"><?php echo $tarefa->nomeprojeto; ?></a></td>
		</tr>

		<tr>
			<th>Cliente</th>
			<td><a href="<?php echo base_url('cliente/visualizar/'.$tarefa->idcliente); ?>"><?php echo $tarefa->cliente_nome; ?></a></td>
		</tr>

		<tr>
			<th>Fase</th>
			<td><?php echo $tarefa->fase; ?></td>
		</tr>

		<tr>
			<th>Responsável</th>
			<td><a href="<?php echo base_url('usuario/visualizar/'.$tarefa->idusuario_responsavel); ?>"><?php echo $tarefa->responsavel; ?></a></td>
		</tr>

		<tr>
			<th>Cadastrado por</th>
			<td><a href="<?php echo base_url('usuario/visualizar/'.$tarefa->idusuario_cadastro); ?>"><?php echo $tarefa->cadastrado_por; ?></a></td>
		</tr>

		<tr>
			<th>Cadastro</th>
			<td><?php echo fdatetime($tarefa->data_cadastro, "/"); ?></td>
		</tr>

		<tr>
			<th>Prazo</th>
			<td><?php echo fdatetime($tarefa->data_prazo, "/"); ?></td>
		</tr>

		<tr class="<?php echo $class_tr; ?>">
			<th>Status</th>
			<td>
				<?php 
					if($tarefa->status == 'nao_comecado'){
						?>
							<input type='image' width="20px" alt='<?php echo lang('status_nao_comecado'); ?>' title='<?php echo lang('status_nao_comecado'); ?>' fora_prazo='<?php echo ($danger == 'danger') ? "sim" : "nao"; ?>' src='<?php echo base_url('assets/images/sistema/bola_azul.png'); ?>' onclick="tstatus(this, '<?php echo base_url('assets/'); ?>', 'projeto_tarefa', 'idtarefa', 'status', '<?php echo $tarefa->idtarefa; ?>');" />
						<?php
					}
					
					elseif($tarefa->status == 'desenvolvimento'){
						?>
							<input type='image' width="20px" alt='<?php echo lang('status_desenvolvimento'); ?>' title='<?php echo lang('status_desenvolvimento'); ?>' fora_prazo='<?php echo ($danger == 'danger') ? "sim" : "nao"; ?>' src='<?php echo base_url('assets/images/sistema/bola_verde.png'); ?>' onclick="tstatus(this, '<?php echo base_url('assets/'); ?>', 'projeto_tarefa', 'idtarefa', 'status', '<?php echo $tarefa->idtarefa; ?>');" />
						<?php
					}
					
					elseif($tarefa->status == 'concluido'){
						?>
							<input type='image' width="20px" alt='<?php echo lang('status_concluido'); ?>' title='<?php echo lang('status_concluido'); ?>' fora_prazo='<?php echo ($danger == 'danger') ? "sim" : "nao"; ?>' src='<?php echo base_url('assets/images/sistema/concluido.png'); ?>' onclick="tstatus(this, '<?php echo base_url('assets/'); ?>', 'projeto_tarefa', 'idtarefa', 'status', '<?php echo $tarefa->idtarefa; ?>');" />
						<?php
					}

					elseif($tarefa->status == 'cancelado'){
						?>
							<input type='image' width="20px" alt='<?php echo lang('status_cancelado'); ?>' title='<?php echo lang('status_cancelado'); ?>' fora_prazo='<?php echo ($danger == 'danger') ? "sim" : "nao"; ?>' src='<?php echo base_url('assets/images/sistema/Cancelado.png'); ?>' onclick="tstatus(this, '<?php echo base_url('assets/'); ?>', 'projeto_tarefa', 'idtarefa', 'status', '<?php echo $tarefa->idtarefa; ?>');" />
						<?php
					}
				?>
			</td>
		</tr>

	</table>

</div>

<div class="col-lg-6">

	<table class="table table-condensed">
		<tr>
			<th>Descrição</th>
			<th>Data</th>
			<th>Início</th>
			<th>Fim</th>
			<th>Usuário</th>
			<th>Total</th>
		</tr>

		<?php foreach($tarefa->horas as $hora){ ?>
			<tr>
				<td><?php echo $hora->descricao_tecnica;  ?></td>
				<td><?php echo fdata($hora->data, "/");?></td>
				<td><?php echo tira_segundos($hora->inicio);  ?></td>
				<td><?php echo tira_segundos($hora->fim);  ?></td>
				<td><a href="<?php echo base_url('usuario/visualizar/'.$hora->idusuario); ?>"><?php echo fnome($hora->usuario, 0); ?></a></td>
				<td><?php echo tira_segundos($hora->total_hora); ?></td>
			</tr>
		<?php } ?>

		<tr>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td align="right">Total:</td>
			<td>
				<?php if($tarefa->total_horas > $tarefa->horas_previstas){ ?>
					<strong style="color:red;"><?php echo $tarefa->total_horas; ?></strong>
				<?php }else{ ?>
					<strong><?php echo $tarefa->total_horas; ?></strong>
				<?php } ?>
			</td>
		</tr>

		<tr>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td align="right">Previstas:</td>
			<td><strong><?php echo $tarefa->horas_previstas; ?></strong></td>
		</tr>
	</table>

</div>