<link href="<?php echo base_url('assets/js/alertify/themes/alertify.ptime.css'); ?>" rel="stylesheet" type="text/css" id="toggleCSS"/>

<p class="titulo_pagina" style="float:left;">Lista de Projetos</p>

<div class="" style="float:right; margin-bottom:10px; width:50%;">
	
	<form action="<?php print base_url('projeto/grade'); ?>" method="post" name="formfiltrotermo" id="formfiltrotermo" class="form-inline" style="display:inline; float:right; margin-top:26px; width:550px;">
	
		<input type="text" name="termo" class="form-control" value="<?php print $this->session->userdata('termo'); ?>" style="width:500px; float:left;"/>
		<button type="submit" title="Filtrar por nome de projeto" class="btn btn-primary" style="float:right; margin-right: 5px;"><i class='glyphicon glyphicon-search'></i></button>
		
	</form>
	
</div> <br><br><br><br>

<?php
	$lanca_etapa = $this->session->userdata('lanca_etapa');
	$lanca_pagamento = $this->session->userdata('lanca_pagamento');
	$cadastra_projeto = $this->session->userdata('cadastra_projeto');
	$cadastra_cliente = $this->session->userdata('cadastra_cliente');
	$envia_relatorio = $this->session->userdata('envia_relatorio');

	require('application/views/includes/mensagem.php');
	if(validation_errors() != ''){
	echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	}
?>

	<div class="panel-group" id="accordion">
	
	<?php
	if($projetos->num_rows() == 0){
		echo 'Não existe resultado para a pesquisa.';
	}
	
	foreach($projetos->result() as $projeto){
		$class_tr = "";
		$danger = "";
		
		if($projeto->prazo <= date('Y-m-d H:i:s')){
			$class_tr = 'danger';
			$danger = 'danger';
		}
		
		if($projeto->status == 'pausado'){
			$class_tr = 'warning';
		}
		
		if($projeto->status == 'concluido'){
			$class_tr = '';
		}
		
		// $class_th = "info";
		
		// if(!empty($class_tr)){
			// $class_th = $class_tr;
		// }
	?>
		<div class="panel panel-default">
			<div class="panel-heading">
				<h4 class="panel-title" style="font-size: 13px;"><a data-toggle="collapse" data-parent="#accordion" href="#collapse<?php echo $projeto->idprojeto ?>"><?php echo '# ' . $projeto->idprojeto . ' - ' . $projeto->nome . ' ('. $projeto->tipoprojeto .') - ' . $projeto->clientenome; ?></a></h4>
			</div>
			<div id="collapse<?php echo $projeto->idprojeto ?>" class="panel-collapse collapse">
				<div class="panel-body">
					
					<table class="table table-condesed" style="margin-bottom: 0px;">
						<tr><td rowspan="3" width="220px"><img src="http://placehold.it/250x100" /></td>
							<td width="140px"><strong>Projeto: </strong></td>
							<td><?php echo $projeto->nome .' - '. $projeto->tipoprojeto; ?></td>
							
							<td width="140px"><strong>Data de Início: </strong></td>
							<td width="150px"><?php echo fdata($projeto->data_inicio, "/"); ?></td></tr>
						
						<tr><td><strong>Cliente: </strong></td>
							<td><?php echo '<a href="'. base_url('cliente/visualizar/'.$projeto->idcliente) .'" alt="'. $projeto->clientestatus .'" target="_blank">' . $projeto->clientenome . '</a>'; ?></td>
							
							<td><strong>Prazo: </strong></td>
							<td><?php echo fdatetime($projeto->prazo,"/"); ?></td></tr>
						
						<tr><td><strong>Responsável: </strong></td>
							<td><?php echo '<a href="'. base_url('usuario/visualizar/'.$projeto->idresponsavel) .'" title="'. $projeto->responsavelstatus .'" target="_blank">' . $projeto->responsavelnome . '</a>'; ?></td>
							
							<td width="140px"><strong>Data Final: </strong></td>
							<td><?php echo fdata($projeto->data_fim, "/"); ?></td></tr></tr>
					</table>
					
					<table class="table table-condesed" style="margin-bottom: 0px;">
						<tr><th class="<?php #echo $class_th; ?>"><strong>Descrição: </strong></th></tr><tr><td><?php echo $projeto->descricao; ?></td></tr>
					</table>
					
					<?php if(!empty($projeto->obs)){ ?><table class="table table-condesed" style="margin-bottom: 0px;">
						<tr><th class="<?php #echo $class_th; ?>"><strong>Observação:</strong></th></tr><tr><td><?php echo $projeto->obs; ?></td></tr>
					</table><?php } ?>
						
				</div>
			</div>
			<div class="col-lg-12">
				<div class="row">
					<table class="table table-condesed col-lg-12" style="margin-bottom: 0px;">
						<tr class=" <?php echo $class_tr; ?>">
						
						<td align="center" width="80px" style="text-align:center;">
						<?php 
							if($projeto->prioridade == 'baixa'){
								?>
									<input type='image' width="20px" alt='Baixa' title='Baixa' src='<?php echo base_url('assets/images/sistema/estrela_cinza.png'); ?>' onclick="prioridade(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'prioridade', '<?php echo $projeto->idprojeto; ?>');" />
								<?php
							}
							
							elseif($projeto->prioridade == 'normal'){
								?>
									<input type='image' width="20px" alt='Normal' title='Normal' src='<?php echo base_url('assets/images/sistema/estrela_preta.png'); ?>' onclick="prioridade(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'prioridade', '<?php echo $projeto->idprojeto; ?>');" />
								<?php
							}
							
							elseif($projeto->prioridade == 'urgente'){
								?>
									<input type='image' width="20px" alt='Urgente' title='Urgente' src='<?php echo base_url('assets/images/sistema/estrela_vermelha.png'); ?>' onclick="prioridade(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'prioridade', '<?php echo $projeto->idprojeto; ?>');" />
								<?php
							}
						?>
						</td>
						
						<td align="center" width="80px" style="text-align:center;">
						<?php 
							if($projeto->status == 'nao_comecado'){
								?>
									<input type='image' width="20px" alt='Não Começado' title='Não Começado' fora_prazo='<?php echo ($danger == 'danger') ? "sim" : "nao"; ?>' src='<?php echo base_url('assets/images/sistema/bola_azul.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'status', '<?php echo $projeto->idprojeto; ?>');" />
								<?php
							}
							
							elseif($projeto->status == 'desenvolvimento'){
								?>
									<input type='image' width="20px" alt='Em Desenvolvimento' title='Em Desenvolvimento' fora_prazo='<?php echo ($danger == 'danger') ? "sim" : "nao"; ?>' src='<?php echo base_url('assets/images/sistema/bola_verde.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'status', '<?php echo $projeto->idprojeto; ?>');" />
								<?php
							}
							
							elseif($projeto->status == 'pausado'){
								?>
									<input type='image' width="20px" alt='Pausado' title='Pausado' fora_prazo='<?php echo ($danger == 'danger') ? "sim" : "nao"; ?>' src='<?php echo base_url('assets/images/sistema/pausado.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'status', '<?php echo $projeto->idprojeto; ?>');" />
								<?php
							}
							
							elseif($projeto->status == 'concluido'){
								?>
									<input type='image' width="20px" alt='Concluído' title='Concluído' fora_prazo='<?php echo ($danger == 'danger') ? "sim" : "nao"; ?>' src='<?php echo base_url('assets/images/sistema/concluido.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'status', '<?php echo $projeto->idprojeto; ?>');" />
								<?php
							}
						?>
						</td>
						<td align="right">
							<?php if($cadastra_projeto){ ?>
								<span class="btn btn-xs btn-danger" alt="Remover Projeto" title="Remover Projeto" id="excluir_projeto_<?php echo $projeto->idprojeto; ?>">
									<i class='glyphicon glyphicon-remove'></i>
								</span>
							<?php } ?>
							
							<?php if($cadastra_projeto){ ?>
								<a class="btn btn-xs btn-warning" alt="Editar Projeto" title="Editar Projeto" href="<?php echo base_url('projeto/editar/'. $projeto->idprojeto); ?>" id="cadastrar_projeto">
									<i class='glyphicon glyphicon-edit'></i>
								</a>
							<?php } ?>
							
							<?php if($lanca_etapa){ ?>
								<a class="btn btn-xs btn-info <?php if($projeto->status == 'concluido'){ echo "disabled"; } ?>" alt="Lançar Etapa" title="Lançar Etapa" href="<?php echo base_url('etapa/lancar/'. $projeto->idprojeto); ?>" id="lancar_etapa">
									<i class='glyphicon glyphicon-time'></i>
								</a>
							<?php } ?>
							
							<?php if($lanca_pagamento){ ?>
								<a class="btn btn-xs btn-success <?php if($projeto->status == 'concluido'){ echo "disabled"; } ?>" alt="Lançar Pagamento" title="Lançar Pagamento" href="<?php echo base_url('financeiro/cadastrar/'. $projeto->idprojeto); ?>" id="lancar_pagamento">
									<i class='glyphicon glyphicon-usd'></i>
								</a>
							<?php } ?>
							
							<?php if($cadastra_projeto){ ?>
								<a class="btn btn-xs btn-info" alt="Gerar Relatório" title="Gerar Relatório" href="<?php echo base_url('relatorio/gerar/'. $projeto->idprojeto); ?>" id="gerar_relatorio">
									<i class='glyphicon glyphicon-file'></i>
								</a>
							<?php } ?>
							
							<a class="btn btn-xs btn-primary" alt="Ver Projeto" title="Ver Projeto" href="<?php echo base_url('projeto/visualizar/'. $projeto->idprojeto); ?>" id="ver_projeto">
								<i class='glyphicon glyphicon-th-large'></i> Vizualizar Projeto
							</a>
						</td>
						</tr>
					</table>
				</div>
			</div>
		</div><br>
	
	<script>	
	$(function(){
		$.contextMenu({
			selector: '#context_<?php echo $projeto->idprojeto; ?>', 
			
			callback: function(key, options) {
				
				if(key == "ver"){
					var url = '<?php echo base_url('projeto/visualizar/'.$projeto->idprojeto); ?>';
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "editar"){
					var url = '<?php echo base_url('projeto/editar/'.$projeto->idprojeto); ?>';
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "deletar"){
					reset();
					alertify.confirm("Você tem certeza que deseja deletar este projeto?", function (e) {
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
				}
				
				if(key == "etapa"){
					var url = '<?php echo base_url('etapa/lancar/'.$projeto->idprojeto); ?>';
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "financeiro"){
					var url = '<?php echo base_url('financeiro/cadastrar/'.$projeto->idprojeto); ?>';
					if (url) {
						window.location = url;
					}
				}
				
			},
			
			items: {
				"ver": {name: "Ver Projeto", icon: "paste"},
				<?php if($cadastra_projeto){ ?>
				"editar": {name: "Editar Projeto", icon: "edit"},
				"deletar": {name: "Deletar Projeto", icon: "delete"},
				<?php } ?>
				"sep1": "---------",
				<?php if($lanca_etapa){ ?>
				"etapa": {name: "Lançar Etapa", icon: "time"},
				<?php } ?>
				<?php if($lanca_pagamento){ ?>
				"financeiro": {name: "Lançar Financeiro", icon: "usd"},
				<?php } ?>
			}
		});
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
		alertify.confirm("Você tem certeza que deseja deletar este projeto?", function (e) {
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
	</script>
	
	<?php } ?>
	
	</div>
	
	<div style="text-align: center;"><?php print $paginacao; ?></div>
