<link type="text/css" href="<?php echo base_url('assets/js/paginacao/paging.css'); ?>" rel="stylesheet" />
<script type="text/javascript" src="<?php echo base_url('assets/js/paginacao/paging.js'); ?>"></script>

<script>
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
</script>

<p class="titulo_pagina"  style="float:left;">Perfil de Usuário - <?php echo $usuario->nome; ?></p> <br>

<?php
	$cadastra_usuario = $this->session->userdata('cadastra_usuario');
	$edita_usuario = $this->session->userdata('edita_usuario');
	$lanca_etapa = $this->session->userdata('lanca_etapa');
	$lanca_pagamento = $this->session->userdata('lanca_pagamento');
	$cadastra_projeto = $this->session->userdata('cadastra_projeto');
	$cadastra_cliente = $this->session->userdata('cadastra_cliente');
	$envia_relatorio = $this->session->userdata('envia_relatorio');
?>

<div style="float:right;">

	<a class="btn btn-primary" title="Voltar para a listagem de usuários" href="<?php echo base_url('usuario/'); ?>"><i class='glyphicon glyphicon-arrow-left'></i> <?php echo lang('btn_ver_todos'); ?></a>

	<a href="<?php echo base_url('tarefa/lista/'.$usuario->idusuario); ?>"><span class="btn btn-info"><i class='glyphicon glyphicon-list-alt'></i> <?php echo lang('btn_visualizar_tarefas'); ?></span></a>
	
	<a href="<?php echo base_url('usuario/editar/'.$usuario->idusuario); ?>"><span class="btn btn-warning"><i class='glyphicon glyphicon-edit'></i> <?php echo lang('btn_editar'); ?></span></a>

	<?php if($usuario->status == 'ativo'){ ?>
		<button class="btn btn-danger" id="desativar"><i class='glyphicon glyphicon-remove'></i> <?php echo lang('btn_desativar'); ?></button>
	<?php }else{ ?>
		<a href="<?php echo base_url("usuario/muda_status/ativo/".$usuario->idusuario); ?>"><span class="btn btn-success"><i class='glyphicon glyphicon-ok'></i> <?php echo lang('btn_ativar'); ?></span></a>
	<?php } ?>

	<?php #if() ?>
	<button id="excluir_definitivamente" class="btn btn-danger"><i class='glyphicon glyphicon-trash'></i> <?php echo lang('btn_excluir'); ?></button>

	<script>
		reset = function () {
			alertify.set({
				labels : {
					ok     : "Desejo excluir o usuário <?php echo $usuario->nome; ?>",
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
					ok     : "Desejo desativar o usuário <?php echo $usuario->nome; ?>",
					cancel : "Deixa pra lá"
				},
				delay : 5000,
				buttonReverse : false,
				buttonFocus   : "cancel"
			});
		};

		$("#desativar").click(function(){
			reset_desativar();

			var texto = `<strong>Solicitação para desativação do usuário <?php echo $usuario->nome; ?></strong>
			<br /><br />
			Ao desativar um usuário, ele não terá mais acesso ao sistema, mas seus dados continuarão cadastrados, 
			bem como horas lançadas e informações cadastradas.<br />
			O usuário pode ser reativado a qualquer momento.
			<br /><br />

			Tem certeza que deseja desativar o usuário <?php echo $usuario->nome; ?>? <br /><br />
			`;

			alertify.confirm(texto, function (e){
				if (e) {
					var url = '<?php echo base_url("usuario/muda_status/inativo/".$usuario->idusuario); ?>';
				
					if(url){
						window.location = url;
					}
					
				} else {
					alertify.error("Usuário não desativado.");
				}
			});
			return false;
		});

		$("#excluir_definitivamente").click(function(){
			reset();

			var texto = `<strong>Solicitação para exclusão definitiva do usuário <?php echo $usuario->nome; ?></strong>
			<br /><br />
			Excluir um usuário pode ser algo um pouco perigoso. <br /><br />
			Ao excluir um usuário você está ciente de que tudo relacionado à ele será apagado no sistema, 
			comprometendo as informações relacionadas aos projetos em que ele participou, 
			inclusive as horas cadastradas por ele.
			<br /><br />
			Talvez seja preferível somente <a href="<?php echo base_url('usuario/muda_status/inativo/'.$usuario->idusuario); ?>">desativar o usuário</a>. 
			Pois desativando, <?php echo $usuario->nome; ?> não terá mais acesso ao sistema, mas seus dados continuarão cadastrados.
			<br /><br />
			Este usuário tem:<br />
			<?php echo ($etapas->num_rows() == 1) ? $etapas->num_rows() . ' etapa de projeto lançada' : $etapas->num_rows() . ' etapas de projeto lançadas'; ?>
			<br /><br />

			Tem certeza que deseja excluir o usuário <?php echo $usuario->nome; ?>, de ID <?php echo $usuario->idusuario; ?>? <br /><br />
			<strong>Esta ação não pode ser desfeita.</strong>
			
			`;

			alertify.confirm(texto, function (e){
				if (e) {
					var url = '<?php echo base_url("usuario/delete/".$usuario->idusuario); ?>';
				
					if(url){
						window.location = url;
					}
					
				} else {
					alertify.error("Usuário não excluído.");
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
<div class="panel-heading" style="<?php echo ($usuario->status == 'inativo')  ? 'background-color: #f2dede;' : ''; ?>">Informações de <?php echo $usuario->nome; ?></div>
<table class="table table-bordered table-condensed">

	<tr>
		<td rowspan="6" width="155px"><img title="<?php echo $usuario->imagem; ?>" src="<?php echo base_url('assets/images/usuarios/'. $usuario->imagem); ?>" class="img-thumbnail" width="200px" height="200px" style="background-color:<?php echo $this->session->userdata('cor'); ?>;"/></td>
		
		<td width="80px"><strong title="Nome Completo">Nome:</strong></td>
			<td><?php echo $usuario->nome; ?></td>
		
		<td width="80px"><strong title="E-mail">E-mail:</strong></td>
			<td><a href="mailto:<?php echo $usuario->email; ?>"><?php echo $usuario->email; ?></a></td>
		
		<td width="80px"><strong title="Nível de Acesso">Cargo:</strong></td>
			<td><?php echo  $usuario->cargo; ?></td>
	</tr>
	
	<tr>
		<td style="min-width: 100px;"><strong title="Nome de Usuário">Login:</strong></td>
			<td><?php echo $usuario->login; ?></td>
		
		<td style="min-width: 100px;"><strong title="Data de Nascimento">Data Nasc:</strong></td>
			<td><?php echo fdata($usuario->data_nascimento, "/"); ?></td>
		
		<td style="min-width: 100px;"><strong title="Idade do Usuário">Idade:</strong></td>
			<td>
			<?php 
			if(!empty($usuario->data_nascimento)){
				$date = new DateTime($usuario->data_nascimento);
				$interval = $date->diff(new DateTime(date('Y-m-d')));
				echo $interval->format('%Y Anos.');
			}
			?>
			</td>
	</tr>
	
	<tr>
		<td><strong title="Número de Matrícula">Matrícula:</strong></td>
			<td><?php echo $usuario->matricula; ?></td>
		
		<td><strong title="Número do Documento de Identidade">RG:</strong></td>
			<td><?php echo $usuario->rg; ?></td>
		
		<td><strong title="Número do CPF">CPF:</strong></td>
			<td><?php if(!empty($usuario->cpf)){ echo mask($usuario->cpf, '###.###.###-##'); } ?></td>
	</tr>
	
	<tr>
		<td><strong title="Data de Cadastro do Usuário">Cadastro:</strong></td>
			<td><?php $data_cadastro = fdatahora($usuario->data_cadastro, "/"); echo $data_cadastro['data'] . " - " . $data_cadastro['hora']; ?></td>
		
		<!--<td><strong title="Senha de Confirmação de E-mail">Confirmação:</strong></td>
			<td><?php if($usuario->usuario_confirmado == 'sim'){ echo $usuario->email_senha; } ?></td>-->

		<td></td>
			<td></td>
		
		<td><strong title="Verificador se o usuário tem e-mail confirmado.">Confirmado:</strong></td>
			<td>
			<?php if($usuario->usuario_confirmado == 'sim'){ ?><label class="label label-primary">Confirmado</label>
			<?php }else{ ?><label class="label label-danger">Não Confirmado</label><?php } ?>
			</td>
	</tr>
	
	<tr>
		<td><strong title="Data do Último Acesso ao Programmer Time">Último A.:</strong></td>
			<td><?php $ultimo_acesso = fdatahora($usuario->ultimo_acesso, "/"); echo $ultimo_acesso['data'] . " - " . $ultimo_acesso['hora']; ?></td>
		
		<td><strong title="Quantas vezes o usuário acessou o sistema">Número A.:</strong></td>
		
		<td><?php $sufixo_acesso = ($usuario->numero_acesso == 1) ? ' acesso.' : ' acessos.'; echo $usuario->numero_acesso . $sufixo_acesso; ?></td>
		
		<td><strong title="Status do Usuário">Status:</strong></td>
			<td>
			<?php if($usuario->status == 'ativo'){ ?><label class="label label-primary">Ativo</label>
			<?php }else{ ?><label class="label label-danger">Inativo</label><?php } ?>
			</td>
	</tr>

</table>
</div>


<div role="tabpanel">

	<!-- Nav tabs -->
	<ul class="nav nav-tabs" role="tablist">

		<li role="presentation" class="active">
			<a href="#settings" aria-controls="settings" role="tab" data-toggle="tab">Horas Trabalhadas</a>
		</li>

		<li role="presentation">
			<a href="#messages" aria-controls="messages" role="tab" data-toggle="tab">Projetos Envolvidos</a>
		</li>

		<li role="presentation">
			<a href="#home" aria-controls="home" role="tab" data-toggle="tab">Etapas de Projeto Realizadas</a>
		</li>

		<li role="presentation">
			<a href="#profile" aria-controls="profile" role="tab" data-toggle="tab">O que está fazendo agora</a>
		</li>


	</ul>

	<div class="tab-content">

		<div role="tabpanel" class="tab-pane active" id="settings">

			<br />

			<div class="">
				
				<div class="col-lg-8">
					<table class="table table-bordered table-hover table-condensed" id="table_horas">

					<tr>
						<th width="">Data</th>
						<th style="text-align:right; width: 150px;">Horas Trabalhadas</th>
						<th style="text-align:right; width: 150px;">Horas Normais</th>
						<th style="text-align:right; width: 150px;">Hora Extra</th>
						<th style="text-align:right; width: 150px;">Meta Diária</th>
					</tr>
					
					<?php if(count($horas_trabalhadas) == 0){ ?>
						<tr><td colspan="5" style="text-align:center;"><?php echo $usuario->nome; ?> ainda não tem horas de trabalho no sistema.</td></tr>
					<?php } ?>
					
					<?php /*<pre><?php print_r($horas_trabalhadas); ?></pre> */ ?>

					<?php 

					$h_hoje = array();
					$h_semana = array();
					$h_mes = array();
					$h_total = array();
					$primeira_data = "";
					$data_anterior = '0000-00-00';

					$cont_tr = 0;

					foreach($horas_trabalhadas as $data => $hora){

							if($data_anterior != '0000-00-00'){

								$begin 	= new DateTime($data);
								$end 	= new DateTime($data_anterior);

								$interval = DateInterval::createFromDateString('1 day');
								$period = new DatePeriod($begin, $interval, $end);

								$period = array_reverse(iterator_to_array($period));

								foreach($period as $dt){

									$data_f = odata($dt->format('Y-m-d H:i:s'));

									if($data_f->Ymd == $data OR $data_f->Ymd == $data_anterior)
										continue;

									echo '<tr>
											<td>'.$data_f->D .', '. $data_f->dmY.'</td>
											<td align="right">-</td>
											<td align="right">-</td>
											<td align="right">-</td>
											<td align="right">-</td>
										  </tr>';

									$cont_tr++;
								}
							}

							$data_anterior = $data;

							$h_data = odata($data.' 00:00:00');

							if($h_data->d == date('d') AND $h_data->m == date('m') AND $h_data->Y == date('Y'))
								$h_hoje[] = $hora;

							if($h_data->m == date('m') AND $h_data->Y == date('Y'))
								$h_mes[] = $hora;


							// print_r($h_data->w);

							// for($i = $h_data->w; $i > 0; $i--){
							// 	$h_semana[] = $hora;
							// }

							$h_total[] = $hora;

							$primeira_data = $data;

							$cont_tr++;
					?>

						<tr>
							<td><?php echo $h_data->D .', '. $h_data->dmY; ?></td>
							<td align="right"><?php echo $hora; ?></td>
							<td align="right"><?php echo horas_normais($hora); ?></td>
							<td align="right"><?php echo subtrair_horas($hora, '08:00'); ?></td>
							<td align="right">08:00</td>
						</tr>

					<?php } ?>

					</table>

					<?php if($cont_tr > 10){ ?>
						<div id="paginacao_horas" style="display:inline"></div>

						<script>
						var pager2 = new Pager('table_horas', 7);
						pager2.init();
						pager2.showPageNav('pager2', 'paginacao_horas');
						pager2.showPage(1);
						</script>
					<?php } ?>
				</div>

				<div class="col-lg-4">

					<table class="table table-bordered table-hover table-condensed">

					<tr>
						<th style="text-align:right;">Total de Hoje - <?php echo date('d/m/Y'); ?></th>
					</tr>

					<tr>
						<td align="right"><?php echo somar_horas($h_hoje); ?></td>
					</tr>

					</table>

					<!--<table class="table table-bordered table-hover table-condensed">

					<tr>
						<th style="text-align:right;">Total da Semana</th>
					</tr>

					<tr>
						<td align="right"><?php echo somar_horas($h_semana); ?></td>
					</tr>

					</table>-->

					<table class="table table-bordered table-hover table-condensed">

					<tr>
						<th style="text-align:right;">Total do Mês - <?php echo date('m/Y'); ?></th>
					</tr>

					<tr>
						<td align="right"><?php echo somar_horas($h_mes); ?></td>
					</tr>

					</table>

					<table class="table table-bordered table-hover table-condensed">

					<tr>
						<th style="text-align:right;">Total <?php echo ($primeira_data) ? 'desde '. fdata($primeira_data, "/") : ""; ?></th>
					</tr>

					<tr>
						<td align="right"><?php echo somar_horas($h_total); ?></td>
					</tr>

					</table>

				</div>

			</div>
		</div>

		<div role="tabpanel" class="tab-pane" id="messages">

			<br />

			<div class="col-lg-12">

			<table class="table table-bordered table-hover table-condensed" id="table_projetos">

				<tr>
					<th width="80px" style="text-align:center;">ID</th>
					<th width="80px" style="text-align:center;">Prioridade</th>
					<th width="80px" style="text-align:center;">Status</th>
					<th width="">Nome do Projeto</th>
					<th width="">Cliente</th>
					<th width="220px">Tipo</th>		
					<th width="" style="text-align:center;">Etapas</th>
					<th width="" style="text-align:center;">Horas</th>
					<!--<th width="80px">Data</th>-->
					<th width="110px">Prazo</th>
					<th width="">Responsável</th>
					<th width="180px"></th>
				</tr>

			<?php
	
			if(count($projetos_envolvidos) == 0){
				echo '<tr><td colspan="12" align="center">'.$usuario->nome.' ainda ão está envolvido em nenhum projeto..</td></tr>';
			}
				
				
			?>

			<?php foreach($projetos_envolvidos as $projeto_envolvido){
					$pe = $projeto_envolvido->row();

					$class_tr = "";
					$danger = "";
					
					if($pe->prazo <= date('Y-m-d H:i:s')){
						$class_tr = 'danger';
						$danger = 'danger';
					}
					
					if($pe->status == 'pausado')
						$class_tr = 'warning';
					
					if($pe->status == 'concluido')
						$class_tr = '';
				?>

				<tr class="<?php echo $class_tr; ?>" id="context_<?php echo $pe->idprojeto; ?>">

					<td align="center">
						<a href="<?php echo base_url('projeto/visualizar/'. $pe->idprojeto); ?>"><?php echo '# ' . $pe->idprojeto; ?></a>
					</td>

					<td align="center">
					<?php 
						if($pe->prioridade == 'baixa'){
							?>
								<input type='image' width="20px" alt='Baixa' title='Baixa' src='<?php echo base_url('assets/images/sistema/estrela_cinza.png'); ?>' onclick="prioridade(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'prioridade', '<?php echo $pe->idprojeto; ?>');" />
							<?php
						}
						
						elseif($pe->prioridade == 'normal'){
							?>
								<input type='image' width="20px" alt='Normal' title='Normal' src='<?php echo base_url('assets/images/sistema/estrela_preta.png'); ?>' onclick="prioridade(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'prioridade', '<?php echo $pe->idprojeto; ?>');" />
							<?php
						}
						
						elseif($pe->prioridade == 'urgente'){
							?>
								<input type='image' width="20px" alt='Urgente' title='Urgente' src='<?php echo base_url('assets/images/sistema/estrela_vermelha.png'); ?>' onclick="prioridade(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'prioridade', '<?php echo $pe->idprojeto; ?>');" />
							<?php
						}
					?>
					</td>
					
					<td align="center">
					<?php 
						if($pe->status == 'nao_comecado'){
							?>
								<input type='image' width="20px" alt='Não Começado' title='Não Começado' fora_prazo='<?php echo ($danger == 'danger') ? "sim" : "nao"; ?>' src='<?php echo base_url('assets/images/sistema/bola_azul.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'status', '<?php echo $pe->idprojeto; ?>');" />
							<?php
						}
						
						elseif($pe->status == 'desenvolvimento'){
							?>
								<input type='image' width="20px" alt='Em Desenvolvimento' title='Em Desenvolvimento' fora_prazo='<?php echo ($danger == 'danger') ? "sim" : "nao"; ?>' src='<?php echo base_url('assets/images/sistema/bola_verde.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'status', '<?php echo $pe->idprojeto; ?>');" />
							<?php
						}
						
						elseif($pe->status == 'pausado'){
							?>
								<input type='image' width="20px" alt='Pausado' title='Pausado' fora_prazo='<?php echo ($danger == 'danger') ? "sim" : "nao"; ?>' src='<?php echo base_url('assets/images/sistema/pausado.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'status', '<?php echo $pe->idprojeto; ?>');" />
							<?php
						}
						
						elseif($pe->status == 'concluido'){
							?>
								<input type='image' width="20px" alt='Concluído' title='Concluído' fora_prazo='<?php echo ($danger == 'danger') ? "sim" : "nao"; ?>' src='<?php echo base_url('assets/images/sistema/concluido.png'); ?>' onclick="status(this, '<?php echo base_url('assets/'); ?>', 'projeto', 'idprojeto', 'status', '<?php echo $pe->idprojeto; ?>');" />
							<?php
						}
					?>
					</td>
					
					<td>
						<a href="<?php echo base_url('projeto/visualizar/'.$pe->idprojeto) ?>" title="<?php echo htmlentities($pe->descricao); ?>">
							<?php echo $pe->nome; ?>
						</a>
					</td>
					
					<td>
						<?php echo '<a href="'. base_url('cliente/visualizar/'.$pe->idcliente) .'" alt="'. $pe->cliente_status .'">' . $pe->cliente . '</a>'; ?>
					</td>
					
					<td><?php echo  $pe->tipo; ?></td>
					
					<td align="center"><?php echo  $projeto_envolvido->numero_etapas; ?></td>
					<td align="center"><?php echo  $projeto_envolvido->horas_trabalhadas; ?></td>
					
					<!--<td><?php echo '<span class="label label-primary">' . fdata($pe->data_inicio, "/") . '</span>'; ?></td>-->
					
					<td>
					<?php
						$label_prazo = "";
						if($pe->prazo <= date('Y-m-d H:i:s')){
							if($pe->status == 'concluido'){
								$label_prazo = "label-primary";
							}else{
								$label_prazo = "label-danger";
							}
						}else{
							$label_prazo = "label-warning";
						}
						
						echo '<span class="label '. $label_prazo .'" id="prazo">' . fdatetime($pe->prazo,"/") . '</span>';
					?>
					</td>
					
					<td>
						<a href="<?php echo base_url('usuario/visualizar/'.$pe->idresponsavel); ?>" id="a-popover-<?php echo $pe->idprojeto; ?>">
							<?php echo fnome($pe->responsavel, 0); ?>
						</a>

						<div id="div-popover-<?php echo $pe->idprojeto; ?>" class="hide">
							
							<div style="width:80px;">
								<img src="<?php echo base_url('assets/images/usuarios/'.$pe->responsavel_imagem); ?>" class="img-thumbnail" style="background-color:<?php echo $pe->responsavel_cor; ?>;">
							</div>
						</div>

						<script type="text/javascript">
							
								$('#a-popover-<?php echo $pe->idprojeto; ?>').popover({
									trigger: 'hover',
									placement: 'top',
									html: true,
									content: $('#div-popover-<?php echo $pe->idprojeto; ?>').html()
								});
						   
						 </script>
					</td>
					
					<td align="right">
						<a class="btn btn-xs btn-primary" alt="Ver Projeto" title="Ver Projeto" href="<?php echo base_url('projeto/visualizar/'. $pe->idprojeto); ?>" id="ver_projeto">
							<i class='glyphicon glyphicon-th-large'></i>
						</a>
						
						<?php if($cadastra_projeto){ ?>
							<a class="btn btn-xs btn-info" alt="Gerar Relatório" title="Gerar Relatório" href="<?php echo base_url('relatorio/gerar/'. $pe->idprojeto); ?>" id="gerar_relatorio">
								<i class='glyphicon glyphicon-file'></i>
							</a>
						<?php } ?>
						
						<?php if($cadastra_projeto){ ?>
							<a class="btn btn-xs btn-warning" alt="Editar Projeto" title="Editar Projeto" href="<?php echo base_url('projeto/editar/'. $pe->idprojeto); ?>" id="cadastrar_projeto">
								<i class='glyphicon glyphicon-pencil'></i>
							</a>
						<?php } ?>
						
						<?php if($lanca_etapa){ ?>
							<a class="btn btn-xs btn-info <?php if($pe->status == 'concluido'){ echo "disabled"; } ?>" alt="Lançar Etapa" title="Lançar Etapa" href="<?php echo base_url('etapa/lancar/'. $pe->idprojeto); ?>" id="lancar_etapa">
								<i class='glyphicon glyphicon-time'></i>
							</a>
						<?php } ?>
						
						<?php if($lanca_pagamento){ ?>
							<a class="btn btn-xs btn-success <?php if($pe->status == 'concluido'){ echo "disabled"; } ?>" alt="Lançar Pagamento" title="Lançar Pagamento" href="<?php echo base_url('financeiro/cadastrar/'. $pe->idprojeto); ?>" id="lancar_pagamento">
								<i class='glyphicon glyphicon-usd'></i>
							</a>
						<?php } ?>
						
						<?php if($cadastra_projeto){ ?>
							<span class="btn btn-xs btn-danger" alt="Remover Projeto" title="Remover Projeto" id="excluir_projeto_<?php echo $pe->idprojeto; ?>">
								<i class='glyphicon glyphicon-remove'></i>
							</span>
						<?php } ?>
					</td>
				</tr>

			<?php } ?>

			</table>

			</div>

			<?php if($etapas->num_rows() > 10){ ?>
				<div id="paginacao_projetos" style="display:inline"></div>

				<script>
				var pager3 = new Pager('table_projetos', 10);
				pager3.init();
				pager3.showPageNav('pager3', 'paginacao_projetos');
				pager3.showPage(1);
				</script>
			<?php } ?>
		</div>

		<div role="tabpanel" class="tab-pane" id="home">

			<br />

			<div class="col-lg-12">
			<table class="table table-bordered table-hover table-condensed" id="table_etapas">
				
				<!--<tr class="info"><td colspan="10"><strong>Etapas Concluídas</strong></td></tr>-->
				
				<tr>
					<th width="">Projeto</th>
					<th width="">Fase</th>
					<th width="">Descrição Técnica</th>
					<!-- <th width="">Descrição Cliente</th> -->
					<th width="">Data</th>
					<th width="">Início</th>
					<th width="">Fim</th>
					<th width="">Tempo</th>
					<th width="70px"></th>		
				</tr>
				
				<?php if($etapas->num_rows() == 0){ ?>
					<tr><td colspan="9" style="text-align:center;"><?php echo $usuario->nome; ?> ainda não lançou etapas.</td></tr>
				<?php } ?>
				
				<?php foreach($etapas->result() as $etapa){ ?>
				<tr>
					<td title="<?php echo '# '.$etapa->idprojeto; ?>">
						<?php echo  '<a href="'. base_url('projeto/visualizar/'. $etapa->idprojeto) .'" title="'. $etapa->prioridade .'">' . $etapa->nomeprojeto . '</a>'; ?>
					</td>
					<td><?php echo  $etapa->fase; ?></td>
					<td><?php echo  $etapa->descricao_tecnica; ?></td>
					<!-- <td><?php echo  $etapa->descricao_cliente; ?></td> -->
					<td><?php echo fdata($etapa->data, '/'); ?></td>
					<td><?php echo fhora($etapa->inicio); ?></td>
					<td><?php echo fhora($etapa->fim); ?></td>
					<td><?php echo calcular_horas($etapa->fim, $etapa->inicio); ?></td>
					<td>
						<a class="btn btn-xs btn-warning" href="<?php echo base_url('etapa/editar/'. $etapa->idetapa); ?>"><i class="glyphicon glyphicon-edit"></i></a>
						<a class="btn btn-xs btn-danger" id="excluir_etapa_<?php echo $etapa->idetapa; ?>"><i class="glyphicon glyphicon-remove"></i></a>
					</td>

				</tr>

				<script>
				$("#excluir_etapa_<?php echo $etapa->idetapa; ?>").click(function () {
					reset();
					alertify.confirm("Tem certeza que deseja deletar esta Etapa de Projeto permanentemente? <br /><b><?php echo $etapa->fase; ?></b> - <?php echo $etapa->descricao_tecnica; ?>", function (e) {
						if (e) {
							var url = "<?php echo base_url('etapa/delete/'.$etapa->idetapa); ?>";
						
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
			</div>
				
			<?php if($etapas->num_rows() > 10){ ?>
				<div id="paginacao_etapas" style="display:inline"></div>

				<script>
				var pager = new Pager('table_etapas', 10);
				pager.init();
				pager.showPageNav('pager', 'paginacao_etapas');
				pager.showPage(1);
				</script>
			<?php } ?>
		</div>

		<div role="tabpanel" class="tab-pane" id="profile">

			<br />

			<?php if($etapa_aberta->num_rows() == 1){ $a_etapa = $etapa_aberta->row(); ?>
			<div class="col-lg-12">
				<table class="table table-bordered table-hover table-condensed" id="table_agora">

					<tr>
						<td width="150px;"><strong>Projeto:</strong></td>
						<td>
							<a href="<?php echo base_url('projeto/visualizar/'. $a_etapa->idprojeto); ?>" title="<?php echo $a_etapa->projeto_descricao; ?>"># <?php echo $a_etapa->idprojeto.' - '.$a_etapa->nomeprojeto.'</a>'; ?>
						</td>

						<td><strong>Prioridade:</strong></td>
						<td><?php echo ucfirst($a_etapa->projeto_prioridade); ?></td>
					</tr>

					<tr>
						<td><strong>Início:</strong></td>
						<td><?php echo fhora($a_etapa->inicio); ?></td>

						<td width="150px;"><strong>Cadastro:</strong></td>
						<td><?php echo fdatetime($a_etapa->data_cadastro, "/"); ?></td>
					</tr>

					<tr>
						<td><strong>Fase:</strong></td>
						<td><?php echo $a_etapa->fase; ?></td>

						<td title="Tempo calculado até agora (<?php echo date('H:i'); ?>)"><strong>Tempo:</strong></td>
						<td><?php echo calcular_horas(date('H:i:s'), $a_etapa->inicio); ?></td>
					</tr>

					<tr>
						<td><strong>Descrição Técnica:</strong></td>
						<td colspan="3"><?php echo $a_etapa->descricao_tecnica; ?></td>
					</tr>

					<tr>
						<td><strong>Descrição Cliente:</strong></td>
						<td colspan="3"><?php echo $a_etapa->descricao_cliente; ?></td>
					</tr>

				</table>
			</div>

			<?php }else{ ?>

				<div class="col-lg-12">O usuário está em inatividade no momento.</div>

			<?php } ?>

		</div>

	</div>
</div>