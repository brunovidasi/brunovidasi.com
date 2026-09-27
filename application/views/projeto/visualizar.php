<p class="titulo_pagina"  style="float:left;"><?php echo $projeto->nome . ' - ' . $projeto->cliente; ?></p> <br>

<link type="text/css" href="<?php echo base_url('assets/js/paginacao/paging.css'); ?>" rel="stylesheet" />
<script type="text/javascript" src="<?php echo base_url('assets/js/paginacao/paging.js'); ?>"></script>
<script src="<?php echo base_url('assets/js/highcharts/js/highcharts.js'); ?>"></script>

<div style="float:right;">
	<?php if($edita_projeto){ ?>
		<a href="<?php echo base_url('projeto/editar/'.$projeto->idprojeto); ?>"><span class="btn btn-warning" title="Editar Projeto"><i class='glyphicon glyphicon-edit'></i> <?php echo lang('btn_editar'); ?></span></a>
	<?php } ?>

	<a class="btn btn-info" alt="<?php echo lang('btn_visualizar_tarefas'); ?>" title="<?php echo lang('btn_visualizar_tarefas'); ?>" href="<?php echo base_url('tarefa/lista/0/'. $projeto->idprojeto); ?>">
		<i class='glyphicon glyphicon-list-alt'></i>
	</a>
	
	<?php if($envia_relatorio){ ?>
		<a href="<?php echo base_url('relatorio/gerar/'.$projeto->idprojeto); ?>"><span class="btn btn-info" title="<?php echo lang('btn_gerar_relatorio'); ?>"><i class='glyphicon glyphicon-file'></i> <?php echo lang('btn_gerar_relatorio'); ?></span></a>
	<?php } ?>

	<?php if($lanca_etapa){ ?>
		<a class="btn btn-info <?php if($projeto->status == 'concluido'){ echo "disabled"; } ?>" alt="<?php echo lang('btn_lancar_etapa'); ?>" title="<?php echo lang('btn_lancar_etapa'); ?>" href="<?php echo base_url('etapa/lancar/'. $projeto->idprojeto); ?>" id="lancar_etapa">
			<i class='glyphicon glyphicon-time'></i>
		</a>
	<?php } ?>
	
	<?php if($lanca_pagamento){ ?>
		<a class="btn btn-success <?php if($projeto->status == 'concluido'){ echo "disabled"; } ?>" alt="<?php echo lang('btn_lancar_pagamento'); ?>" title="<?php echo lang('btn_lancar_pagamento'); ?>" href="<?php echo base_url('financeiro/cadastrar/'.$projeto->idcliente.'/'.$projeto->idprojeto); ?>" id="lancar_pagamento">
			<i class='glyphicon glyphicon-usd'></i>
		</a>
	<?php } ?>
	
	<?php if($cadastra_projeto){ ?>
		<span class="btn btn-danger" alt="Remover Projeto" title="Remover Projeto" id="excluir_projeto_<?php echo $projeto->idprojeto; ?>">
			<i class='glyphicon glyphicon-remove'></i> <?php echo lang('btn_excluir'); ?>
		</span>
	<?php } ?>
</div><br><br>

<?php
	require('application/views/includes/mensagem.php');
	if(validation_errors() != '')
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	
?>

<table class="table table-bordered table-condensed">

	<tr class="<?php echo $class_tr; ?>">
	
		<td rowspan="7" width="200px">
			<div id="grafico_etapa" style="min-width: 200px; height: 200px; margin: 0 auto"></div>
		</td>
		
		<td colspan="4">
			<strong><?php echo '# ' . $projeto->idprojeto . ' - ' . $projeto->nome . ' - ' . $projeto->tipo; ?></strong>
		</td>
		
		<td align="center" valign="middle" width="10%">
		<?php 
			if($projeto->status == 'nao_comecado'){
				?> <input type='image' width="20px" alt='Não Começado' title='Não Começado' fora_prazo='<?php echo ($danger == 'danger') ? "sim" : "nao"; ?>' src='<?php echo base_url('assets/images/sistema/bola_azul.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'status', '<?php echo $projeto->idprojeto; ?>');" /><?php
			}
			
			elseif($projeto->status == 'desenvolvimento'){
				?><input type='image' width="20px" alt='Em Desenvolvimento' title='Em Desenvolvimento' fora_prazo='<?php echo ($danger == 'danger') ? "sim" : "nao"; ?>' src='<?php echo base_url('assets/images/sistema/bola_verde.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'status', '<?php echo $projeto->idprojeto; ?>');" /><?php
			}
			
			elseif($projeto->status == 'pausado'){
				?><input type='image' width="20px" alt='Pausado' title='Pausado' fora_prazo='<?php echo ($danger == 'danger') ? "sim" : "nao"; ?>' src='<?php echo base_url('assets/images/sistema/pausado.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'status', '<?php echo $projeto->idprojeto; ?>');" /><?php
			}
			
			elseif($projeto->status == 'concluido'){
				?><input type='image' width="20px" alt='Concluído' title='Concluído' fora_prazo='<?php echo ($danger == 'danger') ? "sim" : "nao"; ?>' src='<?php echo base_url('assets/images/sistema/concluido.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'status', '<?php echo $projeto->idprojeto; ?>');" /><?php
			}
		?>
		</td>
		
	</tr>
	
	<tr>
		<td><strong>Cliente:</strong></td>
		
		<td colspan="3"><a href="<?php echo base_url('cliente/visualizar/'. $projeto->idcliente) ?>"><?php echo $projeto->cliente . '</a> - <a href="mailto:'. $projeto->cliente_email .'">' . $projeto->cliente_email; ?></a></td>
		
		<td align="center" valign="middle">
		<?php 
			if($projeto->prioridade == 'baixa'){
				?><input type='image' width="20px" alt='Baixa' title='Baixa' src='<?php echo base_url('assets/images/sistema/estrela_cinza.png'); ?>' onclick="prioridade(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'prioridade', '<?php echo $projeto->idprojeto; ?>');" /><?php
			}
			
			elseif($projeto->prioridade == 'normal'){
				?><input type='image' width="20px" alt='Normal' title='Normal' src='<?php echo base_url('assets/images/sistema/estrela_preta.png'); ?>' onclick="prioridade(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'prioridade', '<?php echo $projeto->idprojeto; ?>');" /><?php
			}
			
			elseif($projeto->prioridade == 'urgente'){
				?><input type='image' width="20px" alt='Urgente' title='Urgente' src='<?php echo base_url('assets/images/sistema/estrela_vermelha.png'); ?>' onclick="prioridade(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'prioridade', '<?php echo $projeto->idprojeto; ?>');" /><?php
			}
		?>
		</td>
	</tr>
	
	<tr>
		<td><strong>Responsável:</strong></td>
		<td colspan="3"><a href="<?php echo base_url('usuario/visualizar/'. $projeto->idresponsavel) ?>"><?php echo $projeto->responsavel . '</a> - <a href="mailto:'. $projeto->responsavel_email .'">' . $projeto->responsavel_email; ?></a></td>
		<td></td>
	</tr>
	
	<tr>
		<td><strong>Início:</strong></td>
		<td><?php echo fdata($projeto->data_inicio, '/'); ?></td>
		<td rowspan="4" colspan="4" width="50%"><?php echo $projeto->descricao; ?></td>
	</tr>
	
	<tr>
		<td><strong>Prazo:</strong></td>
		<td><?php echo fdatetime($projeto->prazo, '/'); ?></td>
	</tr>
	
	<tr>
		<td><strong>Fim:</strong></td>
		<td><?php if((!empty($projeto->data_fim)) OR ($projeto->data_fim != '0000-00-00')){ echo fdata($projeto->data_fim, '/'); } ?></td>
	</tr>
	
	<tr>
		<td><strong>Link:</strong></td>
		<td><a href="<?php echo $projeto->link; ?>" target="_blank"><?php echo $projeto->link; ?></a></td>
	</tr>

</table>

<table width="100%" class="table table-bordered table-condensed" style="padding:0px;">

	<tr class="<?php echo $class_financeiro; ?>">
		<td width="150px"><strong>Dias de Projeto:</strong></td>
			<td><span rel="tooltip" title="Número de dias desde o início do projeto"><?php echo calcula_dias($projeto->data_inicio, $projeto->data_fim); ?> dias</span></td>
			
		<td width="150px"><strong>Horas de Projeto:</strong></td>
			<td><span rel="tooltip" title="Horas gastas com etapas de projeto"><?php echo $horas_gastas . ' Horas gastas'; ?></span></td>

		<td width="150px"><strong>Custos de Projeto:</strong></td>
			<td><span rel="tooltip" title="Quanto a empresa gastou com o projeto"><?php echo moeda($financeiro->total_empresa); ?></span></td>
		
	</tr>

	<tr class="<?php echo $class_financeiro; ?>">

		<td width="150px"><strong>Lucro da Empresa:</strong></td>
			<td><span rel="tooltip" title="Quanto a empresa lucrou com o projeto"><?php echo moeda($financeiro->lucro); ?></span></td>
			
		<td width="150px"><strong>Ganhos de Projeto:</strong></td>
			<td><span rel="tooltip" title="Quanto o cliente pagou pelo projeto"><?php echo moeda($financeiro->total_cliente); ?></span></td>

		<td width="150px"><strong>Saldo Financeiro:</strong></td>
			<td align="middle">
			<?php if($financeiro->status == 'positivo'){ ?>
				<?php if(empty($financeiro->lucro)){ ?>
					<input type='image' rel="tooltip" width="18px" alt='Neutro' title='Neutro' src='<?php echo base_url('assets/images/sistema/bola_azul.png'); ?>' />
				<?php }else{ ?>
					<input type='image' rel="tooltip" width="18px" alt='Positivo' title='Positivo' src='<?php echo base_url('assets/images/sistema/bola_verde.png'); ?>' />
				<?php } ?>
			<?php } else{  ?>
				<input type='image' rel="tooltip" width="18px" alt='Negativo' title='Negativo' src='<?php echo base_url('assets/images/sistema/bola_vermelha.png'); ?>' />
			<?php } ?>
			</td>
	</tr>

</table>

<ul class="nav nav-pills" style="cursor:pointer;">
	<li class="active" id="grafico_menu"><a>Gráficos</a></li>
	<li id="etapa_menu"><a>Horas <?php if($numero_etapas > 0){ echo '<span class="badge">'. $numero_etapas .'</span>'; } ?></a></li>
	<li id="financeiro_menu"><a>Controle Financeiro <?php if($numero_pagamentos > 0){ echo '<span class="badge">'. $numero_pagamentos .'</span>'; } ?></a></li>
	<li id="usuarios_menu"><a>Usuários Envolvidos <?php if(!empty($envolvidos)){ echo '<span class="badge">'. count($envolvidos) .'</span>';} ?></a></li>
	<!-- <li id="imagem_menu"><a>Imagens <?php if($imagens->num_rows() > 0){ echo '<span class="badge">'. $imagens->num_rows() .'</span>';} ?></a></li> -->
	<li id="obs_menu"><a>Observação <?php if(!empty($projeto->obs)){ echo '<span class="badge">1</span>';} ?></a></li>
</ul>

<br>

<div id="controle_grafico" style="display:block;">
	<div id="grafico_etapa_detalhes" style="min-width: 500px; height: 350px; margin: 0 auto"></div>
</div>

<div id="controle_etapa" style="display:none;">

<?php if($etapas_andamento->num_rows() != 0){ ?>
	
<table width="100%" class="table table-hover table-condensed" style="padding:10px;">
	
	<tr class="info"><td colspan="10"><strong>Etapas Em Andamento</strong></td></tr>
	
	<tr>
		<th width="">Fase</th>
		<th width="">Descrição Técnica</th>
		<th width="">Descrição Cliente</th>
		<th width="">Usuário</th>
		<th width="">Data</th>
		<th width="">Início</th>
		<th width="" colspan="2">Tempo</th>
		<?php if($edita_projeto){ ?>
		<th width="40px"></th>
		<?php } ?>
	</tr>
	
	<?php foreach($etapas_andamento->result() as $etapa){ ?>
		
	<tr>
		<td><?php echo $etapa->fase; ?></td>
		<td><?php echo $etapa->descricao_tecnica; ?></td>
		<td><?php echo $etapa->descricao_cliente; ?></td>
		<td><a href="<?php echo base_url('usuario/visualizar/'. $etapa->idusuario); ?>"><?php echo $etapa->responsavel; ?></a></td>
		<td><?php echo fdata($etapa->data, '/'); ?></td>
		<td><?php echo fhora($etapa->inicio); ?></td>
		<td colspan="2"><?php 
		if($etapa->data == date('Y-m-d')){
			echo calcular_horas(date('H:i:s'), $etapa->inicio);
		}else{
			echo "<span style='color:red'>Encerrar</span>";
		}
		?></td>
		<?php if($edita_projeto){ ?>
		<td><span class="btn btn-xs btn-danger" id="excluir_etapa_<?php echo $etapa->idetapa; ?>"><i class="glyphicon glyphicon-remove"></i></span></td>
		<?php } ?>
	</tr>
	
	<script>
		$("#excluir_etapa_<?php echo $etapa->idetapa; ?>").click(function () {
			reset();
			alertify.confirm("Você tem certeza que deseja deletar esta etapa?", function (e) {
				if (e) {
					var url = '<?php echo base_url('etapa/delete/'.$etapa->idetapa); ?>';
				
					if (url) {
						window.location = url;
					}
					
				} else {
					alertify.error("Etapa não removida.");
				}
			});
			return false;
		});
		</script>
	
	<?php } ?>
	
	</table>
	
<?php } ?>
	
	<table class="table table-hover" id="table_etapa">
		<?php if($etapas_andamento->num_rows() != 0){ ?>
			<tr class="info"><td colspan="10"><strong>Etapas Concluídas</strong></td></tr>
		<?php } ?>
		
		<tr>
			<th>Fase</th>
			<th>Descrição Técnica</th>
			<th>Descrição Cliente</th>
			<th>Usuário</th>
			<th>Data</th>
			<th>Início</th>
			<th>Fim</th>
			<th>Tempo</th>
			<?php if($edita_projeto){ ?>
			<th width="70px"></th>
			<?php } ?>
		</tr>
		
		<?php if($etapas->num_rows() == 0){ ?>
			<tr><td colspan="9" style="text-align:center;">Não existe etapa lançada neste projeto.</td></tr>
		<?php }else{ ?>
		
		<?php foreach($etapas->result() as $etapa){ ?>
			
		<tr>
			<td><?php echo $etapa->fase; ?></td>
			<td><?php echo $etapa->descricao_tecnica; ?></td>
			<td><?php echo $etapa->descricao_cliente; ?></td>
			<td><a href="<?php echo base_url('usuario/visualizar/'. $etapa->idusuario); ?>"><?php echo $etapa->responsavel; ?></a></td>
			<td><?php echo fdata($etapa->data, '/'); ?></td>
			<td><?php echo fhora($etapa->inicio); ?></td>
			<td><?php echo fhora($etapa->fim); ?></td>
			<td><?php echo calcular_horas($etapa->fim, $etapa->inicio); ?></td>
			<?php if($edita_projeto){ ?>
			<td><a class="btn btn-xs btn-warning" href="<?php echo base_url('etapa/editar/'. $etapa->idetapa); ?>"><i class="glyphicon glyphicon-edit"></i></a>
				<span class="btn btn-xs btn-danger" id="excluir_etapa_<?php echo $etapa->idetapa; ?>"><i class="glyphicon glyphicon-remove"></i></span></td>
			<?php } ?>
		</tr>
		
		<?php if($edita_projeto){ ?>
		<script>
		$("#excluir_etapa_<?php echo $etapa->idetapa; ?>").click(function () {
			reset();
			alertify.confirm("Você tem certeza que deseja deletar esta etapa?", function (e) {
				if (e) {
					var url = '<?php echo base_url('etapa/delete/'.$etapa->idetapa); ?>';
				
					if (url) {
						window.location = url;
					}
					
				} else {
					alertify.error("Etapa não removida.");
				}
			});
			return false;
		});
		</script>
		<?php } ?>
		
		<?php }
		} ?>
	
	</table>
	
	<?php if($etapas->num_rows() > 10){ ?>
		<div id="paginacao_etapa"></div>
	<?php } ?>
	
</div>

<div id="controle_financeiro" style="display:none;">
	<?php if($lanca_pagamento){ ?>
	<table class="table table-hover" id="table_pagamento">
	
		<tr>
			<th style="text-align:center;">Status</th>
			<th>Tipo</th>
			<th>Descrição</th>
			<th>Valor</th>
			<th>Pago</th>
			<th>Pago por</th>
			<th>Data Cobrado</th>
			<th>Data Pago</th>
			<th width="100px"></th>
		</tr>
		
		<?php if($pagamentos->num_rows() == 0){ ?>
			<tr><td colspan="9" style="text-align:center;">Não existe controle de pagamento lançado neste projeto.</td></tr>
		<?php }else{ ?>
		
		<?php foreach($pagamentos->result() as $pagamento){ ?>
			
		<tr>
			<td style="text-align:center; width: 60px;"><?php 
			if($pagamento->status == 'pago'){ $status_financeiro = "concluido.png"; }
			elseif($pagamento->status == 'nao_pago'){ $status_financeiro = "bola_vermelha.png"; }
			elseif($pagamento->status == 'cobrado'){ $status_financeiro = "bola_verde.png"; }
			elseif($pagamento->status == 'parcialmente_pago'){ $status_financeiro = "bola_azul.png"; }
			
			echo '<img src="'. base_url('assets/images/sistema/'. $status_financeiro) .'" width="18px" alt="'. ucfirst(str_replace("_"," ",$pagamento->status)) .'" title="'. ucfirst(str_replace("_"," ",$pagamento->status)) .'"/>'; 
			?></td>
			<td><?php
			if($pagamento->tipo == 'custo_projeto'){ echo 'Custo de Projeto'; }
			if($pagamento->tipo == 'custo_externo'){ echo 'Custo Externo'; }
			if($pagamento->tipo == 'custo_outro'){ echo 'Outro'; }
			?></td>
			<td><span title="<?php echo $pagamento->obs; ?>"><?php echo $pagamento->descricao; ?></span></td>
			<td><?php echo moeda($pagamento->valor); ?></td>
			<td><?php echo moeda($pagamento->valor_pago); ?></td>
			<td><?php echo ucfirst($pagamento->pago_por); ?></td>
			<td><?php echo fdatetime($pagamento->data_cobrado, '/'); ?></td>
			<td><?php echo fdatetime($pagamento->data_pago, '/'); ?></td>
			<td><a class="btn btn-xs btn-warning" href="<?php echo base_url('financeiro/editar/'. $pagamento->idfinanceiro); ?>"><i class="glyphicon glyphicon-edit"></i></a>
				<a class="btn btn-xs btn-danger" id="excluir_pagamento_<?php echo $pagamento->idfinanceiro; ?>"><i class="glyphicon glyphicon-remove"></i></a></td>
		</tr>
		
		<?php if($edita_projeto){ ?>
		<script>
		$("#excluir_pagamento_<?php echo $pagamento->idfinanceiro; ?>").click(function () {
			reset();
			alertify.confirm("Você tem certeza que deseja deletar este pagamento?", function (e) {
				if (e) {
					var url = '<?php echo base_url('financeiro/delete/'.$pagamento->idfinanceiro); ?>';
				
					if (url) {
						window.location = url;
					}
					
				} else {
					alertify.error("Pagamento não removido.");
				}
			});
			return false;
		});
		</script>
		
		<?php } ?>
		
		<?php } 
		} ?>
	
	</table>
	
	<div id="paginacao_pagamento"></div>
	
	<?php } ?>

</div>

<div id="controle_imagem" style="display:none;">
	
	<div id="conteudo_imagens" class="col-lg-12">
	<?php foreach($imagens->result() as $imagem){ ?>
		<div class="imagem imagem-projeto col-lg-2 col-sm-3" id="imagem_id_<?php echo $imagem->idimagem; ?>">
			<a href="<?php echo base_url('imagem/visualizar/'. $imagem->idimagem); ?>">
				<img src="<?php echo base_url('assets/images/projetos/'. $imagem->imagem); ?>" alt="<?php echo $imagem->titulo; ?>" title="<?php echo $imagem->titulo; ?>" class="img-thumbnail" />
			</a>
		</div>
	<?php } ?>
	</div>

</div>

<div id="controle_usuarios" style="display:none;">
	<table class="table table-hover" id="table_envolvidos">
	
	<tr>
		<th style="width:25px;"></th>
		<th>Nome</th>
		<th>Cargo</th>
		<th>Login</th>
		<th>E-mail</th>
		<th title="Número de etapas neste projeto">Etapas</th>
		<th title="Horas trabalhadas neste projeto">Horas</th>
		<th title="Porcentagem de horas trabalhadas neste projeto">%</th>
		<th title="Data do último acesso do usuário no sistema">Último Acesso</th>
		<th style="text-align:center; width:40px;" title="Status do usuário no sistema">Status</th>
	</tr>
	
	<?php 
	if(count($envolvidos) == 0){
		?><tr><td colspan="10" style="text-align:center;">Não existe usuários envolvidos neste projeto.</td></tr><?php
	}
	
	foreach($envolvidos as $envolvidos_array){
		$envolvido = $envolvidos_array->row(); ?>
	
			<tr>
				<td style="text-align:center;"><a href="<?php echo base_url('usuario/visualizar/'. $envolvido->idusuario); ?>"><img src="<?php echo base_url('assets/images/usuarios/'. $envolvido->imagem); ?>" class="img img-circle" style="width:25px;" /></a></td>
				<td><a href="<?php echo base_url('usuario/visualizar/'. $envolvido->idusuario); ?>"><?php echo fnome($envolvido->nome); ?></a></td>
				<td><?php echo $envolvido->cargo; ?></td>
				<td><?php echo $envolvido->login; ?></td>
				<td><?php echo $envolvido->email; ?></td>
				<td><?php echo $envolvidos_array->numero_etapas; ?></td>
				<td><?php echo $envolvidos_array->horas_trabalhadas; ?></td>
				<td><?php echo number_format($envolvidos_array->porcentagem, 2, ',', '.'); ?>%</td>
				<td><?php echo fdatetime($envolvido->ultimo_acesso, "/"); ?></td>
				<td style="text-align:center;"><?php echo ($envolvido->status == 'ativo') ? '<img src="'. base_url('assets/images/sistema/bola_verde.png') .'" alt="ativo" title="ativo" style="width:20px;">' : '<img src="'. base_url('assets/images/sistema/bola_vermelha.png') .'" alt="inativo" title="inativo" style="width:20px;">'; ?></td>
			</tr>
		
	<?php } ?>
	</table>
	
	<div id="paginacao_envolvidos"></div>
</div>

<div id="controle_obs" style="display:none;">
	<form action="<?php echo base_url('projeto/observacao/'. $projeto->idprojeto) ?>" class="" method="post" name="form1" class="form1">
		<textarea name="obs" id="obs" class="form-control"><?php echo set_value('obs', $projeto->obs); ?></textarea>
		<br><button type="submit" name="submit" class="btn btn-primary">Salvar</button>
	</form>
</div>

<script src="<?php echo base_url('assets/js/texteditor/jquery-te-1.4.0.min.js');?>" type="text/javascript"></script>
<link rel="stylesheet" href="<?php echo base_url('assets/js/texteditor/jquery-te-1.4.0.css');?>" type="text/css">

<script>
$("#obs").jqte({ol: false, ul: false, format: false});	

$("#grafico_menu").click(function() {
	$("#controle_grafico").show();
	$("#controle_etapa").hide();
	$("#controle_financeiro").hide();
	$("#controle_imagem").hide();
	$("#controle_obs").hide();
	$("#controle_usuarios").hide();
	
	$("#grafico_menu").attr('class', 'active');
	$("#etapa_menu").attr('class', '');
	$("#financeiro_menu").attr('class', '');
	$("#imagem_menu").attr('class', '');
	$("#obs_menu").attr('class', '');
	$("#usuarios_menu").attr('class', '');
});

$("#etapa_menu").click(function() {
    $("#controle_grafico").hide();
	$("#controle_etapa").show();
	$("#controle_financeiro").hide();
	$("#controle_imagem").hide();
	$("#controle_obs").hide();
	$("#controle_usuarios").hide();
	
	$("#grafico_menu").attr('class', '');
	$("#etapa_menu").attr('class', 'active');
	$("#financeiro_menu").attr('class', '');
	$("#imagem_menu").attr('class', '');
	$("#obs_menu").attr('class', '');
	$("#usuarios_menu").attr('class', '');
});

$("#financeiro_menu").click(function() {
    $("#controle_grafico").hide();
	$("#controle_etapa").hide();
	$("#controle_financeiro").show();
	$("#controle_imagem").hide();
	$("#controle_obs").hide();
	$("#controle_usuarios").hide();
	
	$("#grafico_menu").attr('class', '');
	$("#etapa_menu").attr('class', '');
	$("#financeiro_menu").attr('class', 'active');
	$("#imagem_menu").attr('class', '');
	$("#obs_menu").attr('class', '');
	$("#usuarios_menu").attr('class', '');
});

$("#imagem_menu").click(function() { 
	$("#controle_grafico").hide();
	$("#controle_etapa").hide();
	$("#controle_financeiro").hide();
	$("#controle_imagem").show();
	$("#controle_obs").hide();
	$("#controle_usuarios").hide();
	
	$("#grafico_menu").attr('class', '');
	$("#etapa_menu").attr('class', '');
	$("#financeiro_menu").attr('class', '');
	$("#imagem_menu").attr('class', 'active');
	$("#obs_menu").attr('class', '');
	$("#usuarios_menu").attr('class', '');
});

$("#obs_menu").click(function() { 
    $("#controle_grafico").hide();
	$("#controle_etapa").hide();
	$("#controle_financeiro").hide();
	$("#controle_imagem").hide();
	$("#controle_obs").show();
	$("#controle_usuarios").hide();
	
	$("#grafico_menu").attr('class', '');
	$("#etapa_menu").attr('class', '');
	$("#financeiro_menu").attr('class', '');
	$("#imagem_menu").attr('class', '');
	$("#obs_menu").attr('class', 'active');
	$("#usuarios_menu").attr('class', '');
});

$("#usuarios_menu").click(function() { 
    $("#controle_grafico").hide();
	$("#controle_etapa").hide();
	$("#controle_financeiro").hide();
	$("#controle_imagem").hide();
	$("#controle_obs").hide();
	$("#controle_usuarios").show();
	
	$("#grafico_menu").attr('class', '');
	$("#etapa_menu").attr('class', '');
	$("#financeiro_menu").attr('class', '');
	$("#imagem_menu").attr('class', '');
	$("#obs_menu").attr('class', '');
	$("#usuarios_menu").attr('class', 'active');
});

	reset = function () {
			$("toggleCSS").href = "<?php echo base_url('assets/js/alertify/themes/alertify.ptime.css'); ?>";
			alertify.set({
				labels : {
					ok     : "Deletar",
					cancel : "Não Deletar"
				},
				delay : 5000,
				buttonReverse : false,
				buttonFocus   : "ok"
			});
		};
		
$("#excluir_projeto_<?php echo $projeto->idprojeto; ?>").click(function () {
	reset();
	alertify.confirm("Ao deletar este projeto, todas as etapas, controles financeiros e outras informações envolvidas neste projeto serão também deletados. Você tem certeza que deseja excluir este projeto?", function (e) {
		if (e) {
			var url = '<?php echo base_url('projeto/delete/'.$projeto->idprojeto); ?>';
		
			if (url) {
				window.location = url;
			}
			
		} else {
			alertify.error("Projeto não removido.");
		}
	});
	return false;
});

<?php if($etapas->num_rows() > 10){ ?>
var pager = new Pager('table_etapa', 10);
pager.init();
pager.showPageNav('pager', 'paginacao_etapa');
pager.showPage(1);
<?php } ?>

<?php if($pagamentos->num_rows() > 10){ ?>
var pager2 = new Pager('table_pagamento', 10);
pager2.init();
pager2.showPageNav('pager2', 'paginacao_pagamento');
pager2.showPage(1);
<?php } ?>

<?php if(count($envolvidos) > 10){ ?>
var pager3 = new Pager('table_envolvidos', 10);
pager3.init();
pager3.showPageNav('pager3', 'paginacao_envolvidos');
pager3.showPage(1);
<?php } ?>

    $(function () {
        $("[rel='tooltip']").tooltip();
    });

$(function () {
    var chart;
    
    $(document).ready(function () {
        $('#grafico_etapa').highcharts({
            credits: false,
			chart: {
                plotBackgroundColor: null,
                plotBorderWidth: null,
                plotShadow: true
            },
            title: {
                text: ''
            },
            tooltip: {
        	    pointFormat: '<b>{point.percentage:.1f}%</b>'
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: false
                    },
                    showInLegend: false
                }
            },
            series: [{
                type: 'pie',
                name: 'Etapa',
                data: [
					<?php 
					if(!empty($porcentagem)){
						foreach($porcentagem as $key => $valor){
							echo '["' . $porcentagem[$key]['nome'] . ' - ' . $porcentagem[$key]['numero_etapas'] . ' ' . '", ' . $porcentagem[$key]['porcentagem'] . "],";
						}
					}
					?>
                ]
            }]
        });
    });
});

$(function () {
        $('#grafico_etapa_detalhes').highcharts({
			credits: false,
            chart: {
            },
            title: {
                text: 'Gráfico de Etapas de Projeto'
            },
            xAxis: {
				categories: [
				<?php 
					if(!empty($porcentagem)){
						foreach($porcentagem as $key => $valor){
							echo "'" . $porcentagem[$key]['nome'] . "',";
						}
					}
				?>
				]
            },
			yAxis: {
				title: {
                    text: 'Quantidade de Etapas'
                },
            },
			
            series: [
			<?php 
				if(!empty($usuario_etapa)){
					foreach($usuario_etapa as $key2 => $valor2){
						echo "{
								type: 'column',
								name: '". $usuario_etapa[$key2]['nome'] ."',
								data: [";
						foreach($usuario_etapa[$key2] as $key => $valor){
							if($key != 'nome'){
								echo $usuario_etapa[$key2][$key] . ",";
							}
						
						}
					
						echo "]},";
					}
				}
			?>
			
			{
                type: 'spline',
                name: 'Quantidade',
                data: [
				<?php 
					if(!empty($porcentagem)){
						foreach($porcentagem as $key => $valor){
							echo $porcentagem[$key]['quantidade'] . ",";
						}
					}					
				?>],
                marker: {
                	lineWidth: 2,
                	lineColor: Highcharts.getOptions().colors[3],
                	fillColor: 'white'
                }
            }]
        });
    });
    
</script>