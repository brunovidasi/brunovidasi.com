<link type="text/css" href="<?php echo base_url('assets/js/paginacao/paging.css'); ?>" rel="stylesheet" />
<script type="text/javascript" src="<?php echo base_url('assets/js/paginacao/paging.js'); ?>"></script>

<p class="titulo_pagina"  style="float:left;">Cliente - <?php echo $cliente->nome ?></p> <br>

<?php
	$cadastra_usuario = $this->session->userdata('cadastra_usuario');
	$edita_usuario = $this->session->userdata('edita_usuario');
	$lanca_etapa = $this->session->userdata('lanca_etapa');
	$lanca_pagamento = $this->session->userdata('lanca_pagamento');
	$cadastra_projeto = $this->session->userdata('cadastra_projeto');
	$edita_projeto = $this->session->userdata('edita_projeto');
	$cadastra_cliente = $this->session->userdata('cadastra_cliente');
	$edita_cliente = $this->session->userdata('edita_cliente');
	$envia_relatorio = $this->session->userdata('envia_relatorio');
?>

<div style="float:right;">

	<a class="btn btn-primary" title="Voltar para a listagem de clientes" href="<?php echo base_url('cliente/'); ?>"><i class='glyphicon glyphicon-arrow-left'></i> Ver todos</a>

	<?php if($edita_cliente){ ?>
		<a href="<?php echo base_url('cliente/editar/'.$cliente->idcliente); ?>"><span class="btn btn-warning"><i class='glyphicon glyphicon-edit'></i> Editar</span></a>
		
		<?php if($this->session->userdata('nivel_acesso') == 1 || $this->session->userdata('nivel_acesso') == 2){ ?>

			<?php if($cliente->status == 'ativo'){ ?>
				<button class="btn btn-danger" title="Clique para desativar" id="desativar"><i class='glyphicon glyphicon-remove'></i> Desativar</button>
			<?php }else{ ?>
				<a class="btn btn-success" title="Clique para ativar" href="<?php echo base_url('cliente/muda_status/ativo/'.$cliente->idcliente); ?>"><i class='glyphicon glyphicon-ok'></i> Ativar</a>
			<?php } ?>

		<?php } ?>
	<?php } ?>

	<?php if($this->session->userdata('nivel_acesso') == 1 || $this->session->userdata('nivel_acesso') == 2){ ?>
		<button id="excluir_definitivamente" class="btn btn-danger"><i class='glyphicon glyphicon-trash'></i></button>
	<?php } ?>

	<script>
		reset = function () {
			alertify.set({
				labels : {
					ok     : "Desejo excluir o cliente <?php echo $cliente->nome; ?>",
					cancel : "Deixa pra lá"
				},
				delay : 5000,
				buttonReverse : false,
				buttonFocus   : "cancel"
			});
		};

		reset_desativar = function () {
			alertify.set({
				labels : {
					ok     : "Desejo desativar o cliente <?php echo $cliente->nome; ?>",
					cancel : "Deixa pra lá"
				},
				delay : 5000,
				buttonReverse : false,
				buttonFocus   : "cancel"
			});
		};

		$("#desativar").click(function(){
			reset_desativar();

			var texto = `<strong>Solicitação para desativação do cliente <?php echo $cliente->nome; ?></strong>
			<br /><br />
			Ao desativar um cliente, todos os seus projetos em desenvolvimento terão o status "Cancelado".
			<br /><br />
			Este cliente tem:<br />
			<?php $sufixo_projeto = ($projetos->num_rows() == 1) ? ' projeto' : ' projetos'; echo $projetos->num_rows() . $sufixo_projeto; ?><br />
			<?php $sufixo_projeto_ativo = ($projetos_ativos->num_rows() == 1) ? ' projeto' : ' projetos'; echo $projetos_ativos->num_rows() . $sufixo_projeto . ' ativos'; ?>

			<br /><br />

			Tem certeza que deseja desativar o cliente <?php echo $cliente->nome; ?>, de ID <?php echo $cliente->idcliente; ?>? <br /><br />
			`;

			alertify.confirm(texto, function (e){
				if (e) {
					var url = '<?php echo base_url("cliente/muda_status/inativo/".$cliente->idcliente); ?>';
				
					if(url){
						window.location = url;
					}
					
				} else {
					alertify.error("Cliente não desativado.");
				}
			});
			return false;
		});

		$("#excluir_definitivamente").click(function(){
			reset();

			var texto = `<strong>Solicitação para exclusão definitiva do cliente <?php echo $cliente->nome; ?></strong>
			<br /><br />
			Excluir um cliente pode ser algo um pouco perigoso. <br /><br />
			Ao excluir um cliente você está ciente de que tudo relacionado à ele será apagado no sistema, 
			inclusive as horas cadastradas pelos seus funcionários, projetos e todas as informações referentes aos respectivos projetos.
			<br /><br />
			Talvez seja preferível somente <a href="<?php echo base_url('cliente/muda_status/inativo/'.$cliente->idcliente); ?>">desativar o cliente</a>.
			<br /><br />
			Este cliente tem:<br />
			<?php $sufixo_projeto = ($projetos->num_rows() == 1) ? ' projeto' : ' projetos'; echo $projetos->num_rows() . $sufixo_projeto; ?><br />
			<?php $sufixo_projeto_ativo = ($projetos_ativos->num_rows() == 1) ? ' projeto' : ' projetos'; echo $projetos_ativos->num_rows() . $sufixo_projeto . ' ativos'; ?>

			<br /><br />

			Tem certeza que deseja excluir o cliente <?php echo $cliente->nome; ?>, de ID <?php echo $cliente->idcliente; ?> e todos os seus projetos? <br /><br />
			<strong>Esta ação não pode ser desfeita.</strong>
			
			`;

			alertify.confirm(texto, function (e){
				if (e) {
					var url = '<?php echo base_url("cliente/delete/".$cliente->idcliente); ?>';
				
					if(url){
						window.location = url;
					}
					
				} else {
					alertify.error("Cliente não excluído.");
				}
			});
			return false;
		});
	</script>


</div>
<br><br><br>

<?php
	require('application/views/includes/mensagem.php');
	if(validation_errors() != ''){
	echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	}
?>

<div class="panel panel-default">
<div class="panel-heading" style="<?php echo ($cliente->status == 'inativo') ? 'background-color: #f2dede;' : ''; ?>">Informações sobre <?php echo $cliente->nome; ?></div>
<table class="table table-bordered table-condensed">

	<tr>		
		<td width="150px"><strong title="Nome Completo">Nome:</strong></td>
			<td><?php echo $cliente->nome; ?></td>
		
		<td width="130px"><strong title="E-mail">E-mail:</strong></td>
			<td><a href="mailto:<?php echo $cliente->email; ?>"><?php echo $cliente->email; ?></a></td>
		
		<td width="90px"><strong title="Endereço eletrônico">Website:</strong></td>
			<td><a href="<?php echo $cliente->website; ?>" target="_blank"><?php echo $cliente->website; ?></a></td>
	</tr>
	
	<tr>
		<td><strong title="Razão Social">Razão Social:</strong></td>
			<td><?php echo $cliente->razao_social; ?></td>
		
		<td><strong title="Número de CNPJ">CNPJ:</strong></td>
			<td><?php if(!empty($cliente->cnpj)){ echo mask($cliente->cnpj, '##.###.###/####-##'); } ?></td>
		
		<td><strong title="Número do CPF">CPF:</strong></td>
			<td><?php if(!empty($cliente->cpf)){ echo mask($cliente->cpf, '###.###.###-##'); } ?></td>
	</tr>
	
	<tr>
		<td><strong title="Telefone">Telefone:</strong></td>
			<td><?php if(!empty($cliente->telefone)){ echo mask($cliente->telefone, '(##) ####-#####'); } ?></td>
		
		<td><strong title="Celular">Celular:</strong></td>
			<td><?php if(!empty($cliente->celular)){ echo mask($cliente->celular, '(##) ####-#####'); } ?></td>
		
		<td><strong title="Data de Cadastro do Cliente">Cadastro:</strong></td>
			<td><?php echo fdatetime($cliente->data_cadastro, "/"); ?></td>
	</tr>
	
	<tr>
		<td><strong title="Quantidade de projetos deste cliente">Total de Projetos:</strong></td>
			<td><?php $sufixo_projeto = ($projetos->num_rows() == 1) ? ' projeto' : ' projetos'; echo $projetos->num_rows() . $sufixo_projeto; ?></td>
		
		<td><strong title="Quantidade de projetos em desenvolvimento deste cliente">Projetos Ativos:</strong></td>
			<td><?php $sufixo_projeto_ativo = ($projetos_ativos->num_rows() == 1) ? ' projeto' : ' projetos'; echo $projetos_ativos->num_rows() . $sufixo_projeto; ?></td>
		
		

		<td><strong title="Quantidade de horas gastas em projetos deste cliente">Horas:</strong></td>
			<td><?php echo $horas_projetos; ?></td>
	</tr>

	<tr>
		<td><strong title="Endereço">Endereço:</strong></td>
			<td colspan="3"><?php echo ($cliente->endereco) ? $cliente->endereco.', '.$cliente->endereco_numero.' '.$cliente->endereco_complemento.' - '.$cliente->endereco_bairro.' - '.$cliente->endereco_cidade.' - '.$cliente->endereco_estado : ""; ?></td>
		
		<td><strong title="CEP">CEP:</strong></td>
			<td><?php echo mask($cliente->endereco_cep, '#####-###'); ?></td>
		
	</tr>

	<tr>
		<td><strong title="Contato">Contato:</strong></td>
			<td colspan="3"><?php echo ($cliente->nome_contato) ? $cliente->nome_contato.' - <a href="'.$cliente->email_contato.'">'.$cliente->email_contato.'</a> - '.mask($cliente->telefone_contato, "(##) ####-#####") : ""; ?></td>
		
		<td><strong title="Status do Cliente">Status:</strong></td>
			<td>
				<?php if($cliente->status == 'ativo'){ ?>
					<label class="label label-success">Ativo</label>
				<?php }else{ ?>
					<label class="label label-danger">Inativo</label>
				<?php } ?>
			</td>
	</tr>

</table>
</div>

<div class="panel panel-default">
	<div class="panel-heading">Projetos de <?php echo $cliente->nome; ?></div>
	<table class="table table-hover table-condensed" id="table_projetos">
		<tr>
			<th width="80px" style="text-align:center;">ID</th>
			<th width="80px" style="text-align:center;">Prioridade</th>
			<th width="80px" style="text-align:center;">Status</th>
			<th width="">Nome do Projeto</th>
			<th width="220px">Tipo</th>
			<th width="" style="text-align:center;">Etapas</th>
			<th width="" style="text-align:center;">Horas</th>
			<th width="80px">Data</th>
			<th width="110px">Prazo</th>
			<th width="">Responsável</th>
			<th width="180px"></th>
		</tr>
		
		<?php
	
	if($projetos->num_rows() == 0){
		echo '<tr><td colspan="11" align="center"><strong>Não existe projetos de '. $cliente->nome .'.</strong></td></tr>';
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
		elseif($projeto->status == 'cancelado')
			$class_tr = 'danger';
		
	?>
	
	<tr class="<?php echo $class_tr; ?>" id="context_<?php echo $projeto->idprojeto; ?>">
		
		<td align="center">
			<a href="<?php echo base_url('projeto/visualizar/'. $projeto->idprojeto); ?>"><?php echo '# ' . $projeto->idprojeto; ?></a>
		</td>
		
		<td align="center">
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
		
		<td align="center">
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

			elseif($projeto->status == 'cancelado'){
				?>
					<input type='image' width="20px" alt='Cancelado' title='Cancelado' fora_prazo='<?php echo ($danger == 'danger') ? "sim" : "nao"; ?>' src='<?php echo base_url('assets/images/sistema/cancelado.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'status', '<?php echo $projeto->idprojeto; ?>');" />
				<?php
			}
			
			elseif($projeto->status == 'concluido'){
				?>
					<input type='image' width="20px" alt='Concluído' title='Concluído' fora_prazo='<?php echo ($danger == 'danger') ? "sim" : "nao"; ?>' src='<?php echo base_url('assets/images/sistema/concluido.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'status', '<?php echo $projeto->idprojeto; ?>');" />
				<?php
			}
		?>
		</td>
		
		<td><?php echo $projeto->nome; ?></td>
		<td><?php echo  $projeto->tipoprojeto; ?></td>
		<td align="center"><?php echo  $projeto->numero_etapas; ?></td>
		<td align="center"><?php echo  $projeto->total_horas; ?></td>
		
		<td><?php echo '<span class="label label-primary">' . fdata($projeto->data_inicio, "/") . '</span>';?></td>
		
		<td>
		<?php
			$label_prazo = "";
			if($projeto->prazo <= date('Y-m-d H:i:s')){
				if($projeto->status == 'concluido')
					$label_prazo = "label-primary";
				else
					$label_prazo = "label-danger";
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
			<a class="btn btn-xs btn-primary" alt="Ver Projeto" title="Ver Projeto" href="<?php echo base_url('projeto/visualizar/'. $projeto->idprojeto); ?>" id="ver_projeto">
				<i class='glyphicon glyphicon-th-large'></i>
			</a>

			<?php if($cadastra_projeto){ ?>
				<a class="btn btn-xs btn-info" alt="Gerar Relatório" title="Gerar Relatório" href="<?php echo base_url('relatorio/gerar/'. $projeto->idprojeto); ?>" id="gerar_relatorio">
					<i class='glyphicon glyphicon-file'></i>
				</a>
			<?php } ?>
			
			<?php if($edita_projeto){ ?>
				<a class="btn btn-xs btn-warning" alt="Editar Projeto" title="Editar Projeto" href="<?php echo base_url('projeto/editar/'. $projeto->idprojeto); ?>" id="cadastrar_projeto">
					<i class='glyphicon glyphicon-pencil'></i>
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
			
			<?php if($edita_projeto){ ?>
				<span class="btn btn-xs btn-danger" alt="Remover Projeto" title="Remover Projeto" id="excluir_projeto_<?php echo $projeto->idprojeto; ?>">
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
					alertify.confirm("Ao deletar este projeto, todas as etapas, controles financeiros e outras informações envolvidas neste projeto serão também deletados. Você tem certeza que deseja excluir este projeto?", function (e) {
						if (e) {
							var url = '<?php echo base_url("projeto/delete/".$projeto->idprojeto); ?>';
						
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
					var url = '<?php echo base_url("etapa/lancar/".$projeto->idprojeto); ?>';
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "financeiro"){
					var url = '<?php echo base_url("financeiro/cadastrar/".$projeto->idprojeto); ?>';
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
				<?php if($lanca_etapa && $projeto->status != 'concluido'){ ?>
				"etapa": {name: "Lançar Etapa", icon: "time"},
				<?php } ?>
				<?php if($lanca_pagamento && $projeto->status != 'concluido'){ ?>
				"financeiro": {name: "Lançar Financeiro", icon: "usd"},
				<?php } ?>
			}
		});
	});

	</script>
	
	<?php } ?>

  </table>
  
</div>

<?php if($projetos->num_rows() > 10){ ?>
	<div id="paginacao_projetos" style="display:inline"></div>

	<script>
	var pager = new Pager('table_projetos', 10);
	pager.init();
	pager.showPageNav('pager', 'paginacao_projetos');
	pager.showPage(1);
	</script>
<?php } ?>