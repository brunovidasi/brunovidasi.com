<link href="<?php echo base_url('assets/js/alertify/themes/alertify.ptime.css'); ?>" rel="stylesheet" type="text/css" id="toggleCSS"/>

<p class="titulo_pagina" style="float:left;"><?php echo lang('titulo_lista_projetos'); ?></p>

<div class="" style="float:right; margin-bottom:10px; width:50%;">
		
	<!--<div class="btn-group" style="float:right">
		<button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" style="float:right; margin-top:26px;">
			<i class='glyphicon glyphicon-search'></i> Pesquisar por Clientes
			<span class="caret"></span>
		</button>
		<ul class="dropdown-menu">
			<li><a href="<?php echo base_url('projeto/lista/'); ?>">Listar Todos</a></li>
			<li class="divider"></li>
			
			<?php foreach($clientes->result() as $cl){ ?>
			
				<li><a href="<?php echo base_url('projeto/cliente/'.$cl->idcliente); ?>"><?php echo $cl->nome; ?></a></li>
		
			<?php  } ?>
		</ul>
	</div>-->

	<form action="<?php print base_url('projeto/lista'); ?>" method="post" name="formfiltrotermo" id="formfiltrotermo" class="form-inline" style="display:inline; float:right; margin-top:26px; width:628px;">
		
		<input type="text" name="termo" placeholder="<?php echo lang('placeholder_filtro_projeto'); ?>" class="form-control" value="<?php print $this->session->userdata('c_termo'); ?>" style="width:500px; float:left;"/>
		<a href="<?php echo base_url('projeto/cadastrar'); ?>" title="<?php echo lang('alt_cadastrar_projeto'); ?>" class="btn btn-primary" style="float:right; margin-right: 0px;"><i class='glyphicon glyphicon-plus'></i> <?php echo lang('btn_novo'); ?></a>
		<button type="submit" title="<?php echo lang('alt_btn_cadastrar_projeto'); ?>" class="btn btn-primary" style="float:right; margin-right: 5px;"><i class='glyphicon glyphicon-search'></i></button>
	
	</form>
	
</div> <br><br><br><br>

<?php
	$lanca_etapa = $this->session->userdata('lanca_etapa');
	$lanca_pagamento = $this->session->userdata('lanca_pagamento');
	$cadastra_projeto = $this->session->userdata('cadastra_projeto');
	$cadastra_cliente = $this->session->userdata('cadastra_cliente');
	$envia_relatorio = $this->session->userdata('envia_relatorio');

	require('application/views/includes/mensagem.php');
	if(validation_errors() != '')
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
?>

<table width="100%" class="table table-hover table-condensed" style="padding:10px;">

	<tr>
		<th width="80px" style="text-align:center;"><?php echo lang('tabela_id'); ?></th>
		<th width="80px" style="text-align:center;"><?php echo lang('tabela_prioridade'); ?></th>
		<th width="80px" style="text-align:center;"><?php echo lang('tabela_status'); ?></th>
		<th width=""><?php echo lang('tabela_nome_projeto'); ?></th>
		<th width=""><?php echo lang('tabela_cliente'); ?></th>
		<th width=""><?php echo lang('tabela_tipo'); ?></th>		
		<th width="" style="text-align:center;"><?php echo lang('tabela_etapas'); ?></th>
		<th width="" style="text-align:center;"><?php echo lang('tabela_horas'); ?></th>
		<th width="80px"><?php echo lang('tabela_data'); ?></th>
		<th width="110px"><?php echo lang('tabela_prazo'); ?></th>
		<th width=""><?php echo lang('tabela_responsavel'); ?></th>
		<th width="210px"></th>
	</tr>
	
	<?php
	
	if($projetos->num_rows() == 0){
		echo '<tr><td colspan="12" align="center">'.lang('tabela_sem_resultado').'</td></tr>';
	}
	
	foreach($projetos->result() as $projeto){
		$class_tr = "";
		$danger = "";
		
		if($projeto->prazo <= date('Y-m-d H:i:s')){
			$class_tr = 'danger';
			$danger = 'danger';
		}
		
		if($projeto->status == 'pausado')
			$class_tr = 'warning';

		if($projeto->status == 'cancelado')
			$class_tr = 'danger';
		
		if($projeto->status == 'concluido')
			$class_tr = '';
		
	?>
	
	<tr class="<?php echo $class_tr; ?>" id="context_<?php echo $projeto->idprojeto; ?>">
		
		<td align="center">
			<a href="<?php echo base_url('projeto/visualizar/'. $projeto->idprojeto); ?>"><?php echo '# ' . $projeto->idprojeto; ?></a>
		</td>
		
		<td align="center">
		<?php 
			if($projeto->prioridade == 'baixa'){
				?>
					<input type='image' width="20px" alt='<?php echo lang('prioridade_baixa'); ?>' title='<?php echo lang('prioridade_baixa'); ?>' src='<?php echo base_url('assets/images/sistema/estrela_cinza.png'); ?>' onclick="prioridade(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'prioridade', '<?php echo $projeto->idprojeto; ?>');" />
				<?php
			}
			
			elseif($projeto->prioridade == 'normal'){
				?>
					<input type='image' width="20px" alt='<?php echo lang('prioridade_normal'); ?>' title='<?php echo lang('prioridade_normal'); ?>' src='<?php echo base_url('assets/images/sistema/estrela_preta.png'); ?>' onclick="prioridade(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'prioridade', '<?php echo $projeto->idprojeto; ?>');" />
				<?php
			}
			
			elseif($projeto->prioridade == 'urgente'){
				?>
					<input type='image' width="20px" alt='<?php echo lang('prioridade_urgente'); ?>' title='<?php echo lang('prioridade_urgente'); ?>' src='<?php echo base_url('assets/images/sistema/estrela_vermelha.png'); ?>' onclick="prioridade(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'prioridade', '<?php echo $projeto->idprojeto; ?>');" />
				<?php
			}
		?>
		</td>
		
		<td align="center">
		<?php 
			if($projeto->status == 'nao_comecado'){
				?>
					<input type='image' width="20px" alt='<?php echo lang('status_nao_comecado'); ?>' title='<?php echo lang('status_nao_comecado'); ?>' fora_prazo='<?php echo ($danger == 'danger') ? "sim" : "nao"; ?>' src='<?php echo base_url('assets/images/sistema/bola_azul.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'status', '<?php echo $projeto->idprojeto; ?>');" />
				<?php
			}
			
			elseif($projeto->status == 'desenvolvimento'){
				?>
					<input type='image' width="20px" alt='<?php echo lang('status_desenvolvimento'); ?>' title='<?php echo lang('status_desenvolvimento'); ?>' fora_prazo='<?php echo ($danger == 'danger') ? "sim" : "nao"; ?>' src='<?php echo base_url('assets/images/sistema/bola_verde.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'status', '<?php echo $projeto->idprojeto; ?>');" />
				<?php
			}
			
			elseif($projeto->status == 'pausado'){
				?>
					<input type='image' width="20px" alt='<?php echo lang('status_pausado'); ?>' title='<?php echo lang('status_pausado'); ?>' fora_prazo='<?php echo ($danger == 'danger') ? "sim" : "nao"; ?>' src='<?php echo base_url('assets/images/sistema/pausado.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'status', '<?php echo $projeto->idprojeto; ?>');" />
				<?php
			}
			
			elseif($projeto->status == 'concluido'){
				?>
					<input type='image' width="20px" alt='<?php echo lang('status_concluido'); ?>' title='<?php echo lang('status_concluido'); ?>' fora_prazo='<?php echo ($danger == 'danger') ? "sim" : "nao"; ?>' src='<?php echo base_url('assets/images/sistema/concluido.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'status', '<?php echo $projeto->idprojeto; ?>');" />
				<?php
			}

			elseif($projeto->status == 'cancelado'){
				?>
					<input type='image' width="20px" alt='<?php echo lang('status_cancelado'); ?>' title='<?php echo lang('status_cancelado'); ?>' fora_prazo='<?php echo ($danger == 'danger') ? "sim" : "nao"; ?>' src='<?php echo base_url('assets/images/sistema/Cancelado.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'status', '<?php echo $projeto->idprojeto; ?>');" />
				<?php
			}
		?>
		</td>
		
		<td>
			<a href="<?php echo base_url('projeto/visualizar/'.$projeto->idprojeto) ?>" title="<?php echo htmlentities($projeto->descricao); ?>">
				<?php echo $projeto->nome; ?>
			</a>
		</td>
		
		<td>
			<?php echo '<a href="'. base_url('cliente/visualizar/'.$projeto->idcliente) .'" alt="'. $projeto->clientestatus .'">' . $projeto->clientenome . '</a>'; ?>
		</td>
		
		<td><?php echo  $projeto->tipoprojeto; ?></td>
		
		<td align="center"><?php echo  $projeto->numero_etapas; ?></td>
		<td align="center"><?php echo  $projeto->total_horas; ?></td>
		
		<td><?php echo '<span class="label label-primary">' . fdata($projeto->data_inicio, "/") . '</span>'; ?></td>
		
		<td>
		<?php
			$label_prazo = "";
			if($projeto->prazo <= date('Y-m-d H:i:s')){
				if($projeto->status == 'concluido'){
					$label_prazo = "label-primary";
				}else{
					$label_prazo = "label-danger";
				}
			}else{
				$label_prazo = "label-warning";
			}
			
			echo '<span class="label '. $label_prazo .'" id="prazo">' . fdatetime($projeto->prazo,"/") . '</span>';
		?>
		</td>
		
		<td>
			<a href="<?php echo base_url('usuario/visualizar/'.$projeto->idresponsavel); ?>" id="a-popover-<?php echo $projeto->idprojeto; ?>">
				<?php echo fnome($projeto->responsavelnome, 0); ?>
			</a>

			<div id="div-popover-<?php echo $projeto->idprojeto; ?>" class="hide">
				
				<div style="width:80px;">
					<img src="<?php echo base_url('assets/images/usuarios/'.$projeto->responsavel_imagem); ?>" class="img-thumbnail" style="background-color:<?php echo $projeto->responsavel_cor; ?>;">
				</div>
			</div>

			<script type="text/javascript">
				
					$('#a-popover-<?php echo $projeto->idprojeto; ?>').popover({
						trigger: 'hover',
						placement: 'top',
						html: true,
						content: $('#div-popover-<?php echo $projeto->idprojeto; ?>').html()
					});
			   
			 </script>
		</td>
		
		<td align="right">
			<a class="btn btn-xs btn-primary" alt="<?php echo lang('btn_visualizar_projeto'); ?>" title="<?php echo lang('btn_visualizar_projeto'); ?>" href="<?php echo base_url('projeto/visualizar/'. $projeto->idprojeto); ?>" id="ver_projeto">
				<i class='glyphicon glyphicon-th-large'></i>
			</a>

			<a class="btn btn-xs btn-info" alt="<?php echo lang('btn_visualizar_tarefas'); ?>" title="<?php echo lang('btn_visualizar_tarefas'); ?>" href="<?php echo base_url('tarefa/lista/0/'. $projeto->idprojeto); ?>">
				<i class='glyphicon glyphicon-list-alt'></i>
			</a>
			
			<?php if($cadastra_projeto){ ?>
				<a class="btn btn-xs btn-info" alt="<?php echo lang('btn_gerar_relatorio'); ?>" title="<?php echo lang('btn_gerar_relatorio'); ?>" href="<?php echo base_url('relatorio/gerar/'. $projeto->idprojeto); ?>" id="gerar_relatorio">
					<i class='glyphicon glyphicon-file'></i>
				</a>

				<a class="btn btn-xs btn-warning" alt="<?php echo lang('btn_editar_projeto'); ?>" title="<?php echo lang('btn_editar_projeto'); ?>" href="<?php echo base_url('projeto/editar/'. $projeto->idprojeto); ?>" id="cadastrar_projeto">
					<i class='glyphicon glyphicon-pencil'></i>
				</a>
			<?php } ?>
			
			<?php if($lanca_etapa){ ?>
				<a class="btn btn-xs btn-info <?php if($projeto->status == 'concluido'){ echo "disabled"; } ?>" alt="<?php echo lang('btn_lancar_etapa'); ?>" title="<?php echo lang('btn_lancar_etapa'); ?>" href="<?php echo base_url('etapa/lancar/'. $projeto->idprojeto); ?>" id="lancar_etapa">
					<i class='glyphicon glyphicon-time'></i>
				</a>
			<?php } ?>
			
			<?php if($lanca_pagamento){ ?>
				<a class="btn btn-xs btn-success <?php if($projeto->status == 'concluido'){ echo "disabled"; } ?>" alt="<?php echo lang('btn_lancar_pagamento'); ?>" title="<?php echo lang('btn_lancar_pagamento'); ?>" href="<?php echo base_url('financeiro/cadastrar/'.$projeto->idcliente.'/'.$projeto->idprojeto); ?>" id="lancar_pagamento">
					<i class='glyphicon glyphicon-usd'></i>
				</a>
			<?php } ?>
			
			<?php if($this->session->userdata('nivel_acesso') == 1 || $this->session->userdata('nivel_acesso') == 2){ ?>
				<span class="btn btn-xs btn-danger" alt="<?php echo lang('btn_remover_projeto'); ?>" title="<?php echo lang('btn_remover_projeto'); ?>" id="excluir_projeto_<?php echo $projeto->idprojeto; ?>">
					<i class='glyphicon glyphicon-remove'></i>
				</span>
			<?php } ?>
		</td>
		
	</tr>
	
	<script>	
	$(function(){
		$.contextMenu({
			selector: '#context_<?php echo $projeto->idprojeto; ?>', 
			
			callback: function(key, options) {
				
				if(key == "ver"){
					var url = '<?php echo base_url("projeto/visualizar/".$projeto->idprojeto); ?>';
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "editar"){
					var url = '<?php echo base_url("projeto/editar/".$projeto->idprojeto); ?>';
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "deletar"){
					reset();
					alertify.confirm("<?php echo lang('projeto_confirma_exclusao'); ?>", function (e) {
						if (e) {
							var url = '<?php echo base_url('projeto/delete/'.$projeto->idprojeto); ?>';
						
							if (url) {
								window.location = url;
							}
							
						} else {
							alertify.error("<?php echo lang('projeto_nao_removido'); ?>");
						}
					});
					return false;
				}
				
				if(key == "etapa"){
					var url = '<?php echo base_url("etapa/lancar/".$projeto->idprojeto); ?>';
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "financeiro"){
					var url = '<?php echo base_url("financeiro/cadastrar/".$projeto->idcliente."/".$projeto->idprojeto); ?>';
					if (url) {
						window.location = url;
					}
				}
				
			},
			
			items: {
				"ver": {name: "<?php echo lang('btn_visualizar_projeto'); ?>", icon: "paste"},
				<?php if($cadastra_projeto){ ?>
				"editar": {name: "<?php echo lang('btn_editar_projeto'); ?>", icon: "edit"},
				"deletar": {name: "<?php echo lang('btn_remover_projeto'); ?>", icon: "delete"},
				<?php } ?>
				"sep1": "---------",
				<?php if($lanca_etapa && $projeto->status != 'concluido'){ ?>
				"etapa": {name: "<?php echo lang('btn_lancar_etapa'); ?>", icon: "time"},
				<?php } ?>
				<?php if($lanca_pagamento && $projeto->status != 'concluido'){ ?>
				"financeiro": {name: "<?php echo lang('btn_lancar_pagamento'); ?>", icon: "usd"},
				<?php } ?>
			}
		});
	});
	

		reset = function () {
			$("toggleCSS").href = "<?php echo base_url('assets/js/alertify/themes/alertify.ptime.css'); ?>";
			alertify.set({
				labels : {
					ok     : "<?php echo lang('btn_excluir'); ?>",
					cancel : "<?php echo lang('btn_nao_excluir'); ?>"
				},
				delay : 5000,
				buttonReverse : false,
				buttonFocus   : "ok"
			});
		};
		
	$("#excluir_projeto_<?php echo $projeto->idprojeto; ?>").click(function () {
		reset();
		alertify.confirm("<?php echo lang('projeto_confirma_exclusao'); ?>", function (e) {
			if (e) {
				var url = '<?php echo base_url("projeto/delete/".$projeto->idprojeto); ?>';
			
				if (url) {
					window.location = url;
				}
				
			} else {
				alertify.error("<?php echo lang('projeto_nao_removido'); ?>");
			}
		});
		return false;
	});
	</script>
	
	<?php } ?>

</table>
	
<div style="text-align: center;"><?php print $paginacao; ?></div>