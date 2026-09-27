<p class="titulo_pagina" style="float:left;"><?php 

$session_id = $this->session->userdata('id');

if($session_id == $idusuario && $idprojeto == 0)
	echo lang('titulo_lista_tarefas');
elseif($session_id == $idusuario && $idprojeto > 0)
	echo 'Minhas Tarefas - '.$projeto;
elseif($idusuario == 0 AND $idprojeto > 0)
	echo 'Tarefas - '.$projeto;
elseif($idusuario > 0 AND $idprojeto == 0)
	echo 'Tarefas - '.$usuario;
elseif($idusuario > 0 AND $idprojeto > 0)
	echo 'Tarefas - ' . $usuario . ' - ' . $projeto;
else
	echo 'Tarefas';


?></p> 

<div class="" style="float:right; margin-bottom:10px; width:50%;">

	<form action="<?php print base_url('tarefa/lista'); ?>" method="post" name="formfiltrotermo" id="formfiltrotermo" class="form-inline" style="display:inline; float:right; margin-top:26px; width:628px;">
		
		<input type="text" name="termo" placeholder="<?php echo lang('placeholder_filtro_tarefa'); ?>" class="form-control" value="<?php print $this->session->userdata('c_termo'); ?>" style="width:500px; float:left;"/>
		<a href="<?php echo base_url('tarefa/cadastrar/'.$idprojeto.'/'.$idusuario); ?>" title="<?php echo lang('alt_cadastrar_tarefa'); ?>" class="btn btn-primary" style="float:right; margin-right: 0px;"><i class='glyphicon glyphicon-plus'></i> <?php echo lang('btn_novo'); ?></a>
		<button type="submit" title="<?php echo lang('alt_btn_cadastrar_tarefa'); ?>" class="btn btn-primary" style="float:right; margin-right: 5px;"><i class='glyphicon glyphicon-search'></i></button>
	
	</form>
</div>

<br><br><br><br>

<?php
	$lanca_etapa = $this->session->userdata('lanca_etapa');

	require('application/views/includes/mensagem.php');
	if(validation_errors() != '')
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
?>
	
<table width="100%" class="table table-hover table-condensed" style="padding:10px;">
	
	<tr>
		<th width="80px" style="text-align:center;"><?php echo lang('tabela_status'); ?></th>
		<th width=""><?php echo lang('tabela_nome'); ?></th>
		<th width=""><?php echo lang('tabela_projeto'); ?></th>
		<th width=""><?php echo lang('tabela_responsavel'); ?></th>
		<th width=""><?php echo lang('tabela_horas_gastas'); ?></th>
		<th width=""><?php echo lang('tabela_horas_previstas'); ?></th>
		<!-- <th width=""><?php echo lang('tabela_porcentagem'); ?></th> -->
		<th width=""><?php echo lang('tabela_prazo'); ?></th>
		<th width="120px" style="text-align:right;"></th>		
	</tr>
	
	<?php if($tarefas_numero == 0){ ?>
		<tr><td colspan="10" style="text-align:center;"><?php echo lang('nao_existe_tarefas'); ?></td></tr>
	<?php } ?>
	
	<?php foreach($tarefas as $tarefa){ 

		$class_tr = "";
		$danger = "";
		
		if($tarefa->data_prazo <= date('Y-m-d H:i:s')){
			$class_tr = 'danger';
			$danger = 'danger';
		}
		
		if($tarefa->status == 'em_desenvolvimento')
			$class_tr = 'info';

		if($tarefa->status == 'cancelado')
			$class_tr = 'danger';
		
		if($tarefa->status == 'concluido')
			$class_tr = '';

	?>


	<tr class="<?php echo $class_tr; ?>" id="context_<?php echo $tarefa->idprojeto; ?>">

		<td align="center">
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
		

		<td>
			<a href="<?php echo base_url('tarefa/visualizar/'.$tarefa->idtarefa); ?>"><?php echo $tarefa->nome; ?></a>
		</td>

		<td>
			<a href="<?php echo base_url('projeto/visualizar/'.$tarefa->idprojeto); ?>"><?php echo $tarefa->nomeprojeto; ?></a>
		</td>

		<td>
			<a href="<?php echo base_url('usuario/visualizar/'.$tarefa->idusuario_responsavel); ?>" id="a-popover-<?php echo $tarefa->idtarefa; ?>">
				<?php echo fnome($tarefa->responsavel, 0); ?>
			</a>

			<div id="div-popover-<?php echo $tarefa->idtarefa; ?>" class="hide">
				
				<div style="width:80px;">
					<img src="<?php echo base_url('assets/images/usuarios/'.$tarefa->responsavel_imagem); ?>" class="img-thumbnail" style="background-color:<?php echo $tarefa->responsavel_cor; ?>;">
				</div>
			</div>

			<script type="text/javascript">
				
					$('#a-popover-<?php echo $tarefa->idtarefa; ?>').popover({
						trigger: 'hover',
						placement: 'top',
						html: true,
						content: $('#div-popover-<?php echo $tarefa->idtarefa; ?>').html()
					});
			   
			 </script>
		</td>

		<td>
			<?php echo $tarefa->total_horas; ?>
		</td>

		<td>
			<?php echo ($tarefa->horas < 10) ? '0'. $tarefa->horas . ':00' : $tarefa->horas . ':00'; ?>
		</td>

		<!--<td>
			<?php echo $tarefa->porcentagem; ?>
		</td>-->

		<td>
			<?php echo fdatetime($tarefa->data_prazo, '/'); ?>
		</td>

		<td style="text-align:right;">
			<a class="btn btn-xs btn-primary" href="<?php echo base_url('tarefa/visualizar/'. $tarefa->idtarefa); ?>"><i class="glyphicon glyphicon-th-large"></i></a>
			<a class="btn btn-xs btn-info" href="<?php echo base_url('etapa/lancar/'.$tarefa->idprojeto.'/'. $tarefa->idtarefa); ?>"><i class="glyphicon glyphicon-time"></i></a>
			<a class="btn btn-xs btn-warning" href="<?php echo base_url('tarefa/editar/'. $tarefa->idtarefa); ?>"><i class="glyphicon glyphicon-edit"></i></a>
			<?php if($this->session->userdata('nivel_acesso') == 1 || $this->session->userdata('nivel_acesso') == 2){ ?>
				<a class="btn btn-xs btn-danger" href="<?php echo base_url('tarefa/delete/'. $tarefa->idtarefa); ?>"><i class="glyphicon glyphicon-remove"></i></a>
			<?php } ?>
		</td>
	</tr>
	
	<?php } ?>

</table>
	
		<div style="text-align:center;"><?php print $paginacao; ?></div>
	<br>