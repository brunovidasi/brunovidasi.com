<link type="text/css" href="<?php echo base_url('assets/js/paginacao/paging.css'); ?>" rel="stylesheet" />
<script type="text/javascript" src="<?php echo base_url('assets/js/paginacao/paging.js'); ?>"></script>
<script type="text/javascript" src="<?php echo base_url('assets/js/moment.js'); ?>"></script>
<script src="<?php echo base_url('assets/js/highcharts/js/highcharts.js'); ?>"></script>

<!--<p class="titulo_pagina"  style="float:left;">Olá! Como foi seu dia? Vamos aos nossos Projetos!</p> <br><br><br><br>--><br />

<?php 
if($logou == TRUE){ ?>
	<script>
	$(document).ready(function(){
		$(".timeline").slideDown(1000);

		setTimeout(function(){
			$(".projetos-recentes").slideDown(1000);
		}, 1000);
	});
	</script>
<?php } ?>

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


	$item = array();

	# TAREFAS ##########################################################################

	if($num_tarefas > 0){
		$tarefa['datetime'] = date('Y-m-d H:i:s');

		if($num_tarefas == 1)
			$numero_tarefas = 'Você tem <strong>1</strong> tarefa pendente.';
		else
			$numero_tarefas = 'Você tem <strong>'. $num_tarefas .'</strong> tarefas pendentes.';

		$bg = 'bg-aqua';

		$tarefa['conteudo'] = '<li>
		    <i class="glyphicon glyphicon-list-alt '.$bg.'"></i>
		    	<div class="timeline-item">
			       	<span class="time"><i class="glyphicon glyphicon-time"></i> <span title="'. fdatetime($tarefa['datetime'], "/") .'">'. fdatetime($tarefa['datetime'], "/") .'</span></span>

			        <h3 class="timeline-header">
			        	<a href="'. base_url('tarefa/lista/'. $this->session->userdata('id')) .'">Minhas Tarefas</a>
			        </h3>
		            
		            <div class="timeline-body">
		            	'. $numero_tarefas .'
		            </div>

		            <div class="timeline-footer">
		                <a href="'. base_url('tarefa/lista/'. $this->session->userdata('id')) .'" class="btn btn-primary btn-xs"><i class="glyphicon glyphicon-arrow-right"></i> Vizualizar Tarefas</a>
		            </div>
		    	</div>
		    </li>
		';

		$item[] = $tarefa;
	}

	# ETAPA EM ABERTO ##################################################################

	if($etapa_aberta->num_rows() == 1){

		$a_etapa = $etapa_aberta->row();

		$etapa['datetime'] = $a_etapa->data . ' ' . $a_etapa->inicio;

		if($a_etapa->data == date('Y-m-d')){
			$bg = 'bg-aqua';
		}else{
			$bg = 'bg-red';
		}

		$etapa['conteudo'] = '<li>
		    <i class="glyphicon glyphicon-time '.$bg.'"></i>
		    	<div class="timeline-item">
			       	<span class="time"><i class="glyphicon glyphicon-time"></i> <span id="etapa_'.$a_etapa->idetapa.'" title="'. fdatetime($etapa['datetime'], "/") .'">'. fhora($a_etapa->inicio) .'</span></span>

			        <h3 class="timeline-header">
			        	<a href="'. base_url('projeto/visualizar/'. $a_etapa->idprojeto) .'">'. $a_etapa->nomeprojeto .'</a>
			        </h3>
		            
		            <div class="timeline-body">
		            	<strong>'. $a_etapa->fase .'</strong><br />
		                '. $a_etapa->descricao_tecnica .'
		            </div>

		            <div class="timeline-footer">
		                <a href="'. base_url('etapa') .'" class="btn btn-primary btn-xs"><i class="glyphicon glyphicon-arrow-right"></i> Terminar Contagem</a>
		                <a class="btn btn-danger btn-xs" id="excluir_etapa_lt_'.$a_etapa->idetapa.'"><i class="glyphicon glyphicon-remove"></i> Excluir</a>
		            </div>
		    	</div>
		    </li>

			<script>
		    	var momento = moment("'.$a_etapa->data.' '.$a_etapa->inicio.'", "YYYY/MM/DD HH:mm:ss").startOf("second").fromNow();
				$("#etapa_'.$a_etapa->idetapa.'").html(momento);

				$("#excluir_etapa_lt_'.$a_etapa->idetapa.'").click(function () {
					reset();
					alertify.confirm("Tem certeza que deseja deletar esta Etapa de Projeto permanentemente? <b>'.$a_etapa->fase.'</b> - '.$a_etapa->descricao_tecnica.'", function (e) {
						if (e) {
							var url = "'. base_url('etapa/delete/'.$a_etapa->idetapa). '";
						
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

		    ';

		    $item[] = $etapa;
	}

	# MENSAGENS ########################################################################

	foreach($mensagens as $msg){
		
		$mensagem['datetime'] = $msg->data_envio;

		$mensagem['conteudo'] = '<li>
		    <i class="glyphicon glyphicon-envelope bg-blue"></i>
		    	<div class="timeline-item">
			       	<span class="time"><i class="glyphicon glyphicon-time"></i> <span id="mensagem_'.$msg->idmensagem.'" title="'. fdatetime($msg->data_envio, "/") .'">'. fdatetime($msg->data_envio, "/") .'</span></span>

			        <h3 class="timeline-header">
			        	<a href="'.base_url('mensagem/visualizar/'.$msg->idmensagem).'">'.$msg->assunto.'</a>
			        </h3>
		            
		            <div class="timeline-body">

		            <a href="'. base_url('usuario/visualizar/'. $msg->id_usuario_from) .'" class="thumbnail" style="width:50px; margin-right:8px; margin-bottom: 5px; float: left; background: '.$msg->cor.';">
		            	<img src="'. base_url('assets/images/usuarios/'. $msg->imagem) .'"  />
		            </a>

		            <a href="'. base_url('usuario/visualizar/'. $msg->id_usuario_from) .'"><strong>'.fnome($msg->nome).'</a>:</strong> '.$msg->mensagem.'

		            </div>

		            <div class="timeline-footer">
		            	<a href="'.base_url('mensagem/visualizar/'.$msg->idmensagem).'" class="btn btn-primary btn-xs">
		            	 	<i class="glyphicon glyphicon-th-large"></i> Ver
		            	</a>

		            	<a href="'.base_url('mensagem/visualizar/'.$msg->idmensagem).'" class="btn btn-warning btn-xs">
		            	 	<i class="glyphicon glyphicon-share-alt"></i> Responder
		            	</a>
		            </div>
		    	</div>
		    </li>

		    <script>
		    	var momento = moment("'.$msg->data_envio.'", "YYYY/MM/DD HH:mm:ss").startOf("second").fromNow();
 				$("#mensagem_'.$msg->idmensagem.'").html(momento);
		    </script>

		';

		$item[] = $mensagem;
	}


	# ORDENANDO ARRAY PELA DATA ########################################################

	// function cmp($item,$b){
	//     return strtotime($item['datetime'])<strtotime($b['datetime'])?1:-1;
	// }

	// uasort($item,'cmp');

	$ord = array();
	foreach ($item as $key => $value){
	    $ord[] = strtotime($value['datetime']);
	}

	array_multisort($ord, SORT_DESC, $item);

	####################################################################################

	#print_r($item);
?>

<div class="row">
    <div class="col-md-4">
        <ul class="timeline" <?php if($logou) echo 'style="display:none;"';?>>

            <?php 

			if(count($item) == 0){
				echo '<li class="time-label">
						<span class="bg-blue">
							'.semana().', '. tdata(date('Y-m-d')) .'
						</span>
					</li>
					<li>
					<i class="glyphicon glyphicon-ok bg-aqua"></i>
						<div class="timeline-item">
							<div class="timeline-body">Nenhuma notificação hoje.</div>
						</div>
					</li>
					';
			}

            for($i = 0; $i < count($item); $i++){

            	list($item[$i]['data'], $item[$i]['hora']) = explode(" ", $item[$i]['datetime']);

            	$bg = ($item[$i]['data'] == date('Y-m-d')) ? 'bg-blue' : 'bg-yellow';
            	
            	if($i == 0){
            		if($item[$i]['data'] != date('Y-m-d')){
            			echo '<li class="time-label">
			               <span class="bg-blue">
			                    '.semana().', '. tdata(date('Y-m-d')) .'
			                </span>
			            </li>
			            <li>
			            <i class="glyphicon glyphicon-ok bg-aqua"></i>
		    				<div class="timeline-item">
		    					<div class="timeline-body">Nenhuma notificação hoje.</div>
		    				</div>
			            </li>
			            ';
            		}

            		echo '<li class="time-label">
		               <span class="'.$bg.'">
		                    '. tdata($item[$i]['data']) .'
		                </span>
		            </li>';
            	}else{
            		if($item[$i-1]['data'] != $item[$i]['data']){
            			echo '<li class="time-label">
			               <span class="'.$bg.'">
			                    '. tdata($item[$i]['data']) .'
			                </span>
			            </li>';
            		}
            	}

            	echo $item[$i]['conteudo'];

            }

            ?>

            <li>
                <i class="glyphicon glyphicon-clock-o"></i>
            </li>
        </ul>
    </div><!-- /.col --> 


    <div class="projetos-recentes col-md-8" <?php //if($logou) echo 'style="display:none;"';?>>


    	<div role="tabpanel">

		  <!-- Nav tabs -->
			<ul class="nav nav-tabs" role="tablist">

				<li role="presentation" class="active">
					<a href="#settings" aria-controls="settings" role="tab" data-toggle="tab"><i class="glyphicon glyphicon-signal"></i> Horas Trabalhadas</a>
				</li>

				<li role="presentation">
					<a href="#home" aria-controls="home" role="tab" data-toggle="tab"><i class="glyphicon glyphicon-book"></i> Meus Projetos</a>
				</li>

				<li role="presentation">
					<a href="#messages" aria-controls="messages" role="tab" data-toggle="tab"><i class="glyphicon glyphicon-time"></i> Minhas Etapas</a>
				</li>

				<li role="presentation">
					<a href="#profile" aria-controls="profile" role="tab" data-toggle="tab"><i class="glyphicon glyphicon-flash"></i> Agora</a>
				</li>


			</ul>

			<!-- Tab panes -->
			<div class="tab-content">
				<div role="tabpanel" class="tab-pane" id="home">

					<br />

					<div class="panel panel-default">
						<div class="panel-heading">Meus Projetos Recentes</div>
						
						<div class="panel-body">
							<table width="100%" class="table table-hover" id="tabela_projetos">
								<tr>
									<th>ID</th>
									<th>Nome</th>
									<th>Tipo</th>
									<th title="Responsável pelo projeto">Responsável</th>
									<th title="Quantidade de etapas que você realizou neste projeto">Etapas</th>
									<th title="Horas que você realizou neste projeto">Horas</th>
									<th>Prazo</th>
									<th></th>
								</tr>
								
								<?php foreach($projetos_envolvidos as $projeto_envolvido){
								$projeto = $projeto_envolvido->row();  ?>
								<tr>
									<td><a href="<?php echo base_url('projeto/visualizar/'.$projeto->idprojeto); ?>"># <?php echo $projeto->idprojeto; ?></a></td>
									<td><a href="<?php echo base_url('projeto/visualizar/'.$projeto->idprojeto); ?>"><?php echo $projeto->nome; ?></a></td>
									<td><?php echo $projeto->tipo; ?></td>
									
									<td>
										<a href="<?php echo base_url('usuario/visualizar/'.$projeto->idresponsavel); ?>" id="a-popover-<?php echo $projeto->idprojeto; ?>">
											<?php echo $projeto->responsavel; ?>
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

									<td><?php echo $projeto_envolvido->numero_etapas; ?></td>
									<td><?php echo $projeto_envolvido->horas_trabalhadas; ?></td>
									<td><?php echo fdatetime($projeto->prazo, '/'); ?></td>
									<td>
										<a href="<?php echo base_url('projeto/visualizar/'.$projeto->idprojeto); ?>" class="btn btn-primary btn-xs"><i class="glyphicon glyphicon-th-large"></i></a>
										<?php if($cadastra_projeto){ ?>
											<a class="btn btn-xs btn-info" alt="Gerar Relatório" title="Gerar Relatório" href="<?php echo base_url('relatorio/gerar/'. $projeto->idprojeto); ?>" id="gerar_relatorio">
												<i class='glyphicon glyphicon-file'></i>
											</a>
										<?php } ?>
										
										<?php if($cadastra_projeto){ ?>
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
									</td>
								</tr>
								<?php } 

								if(count($projetos_envolvidos) == 0){
									echo '<td colspan="8" style="text-align:center;">Você não tem projetos recentes.</td>';
								}

								?>
								
							</table>
							
							<?php if(count($projetos_envolvidos) > 5){ ?>
								<div id="paginacao_projetos" style="display:inline;"></div>

								<script>
								var pager = new Pager('tabela_projetos', 5);
								pager.init();
								pager.showPageNav('pager', 'paginacao_projetos');
								pager.showPage(1);
								</script>
							<?php } ?>
						</div>

						
					</div>

				</div>

				<div role="tabpanel" class="tab-pane" id="messages">

					<br />

					<div class="panel panel-default">
						<div class="panel-heading">Últimas etapas de projeto realizadas</div>
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
							
							<?php if($minhas_etapas->num_rows() == 0){ ?>
								<tr><td colspan="9" style="text-align:center;">Você ainda não lançou etapas.</td></tr>
							<?php } ?>
							
							<?php foreach($minhas_etapas->result() as $etapa){ ?>
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

					<div class="panel panel-default">

						<div class="panel-heading">
							<h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#collapse3">O que estou fazendo agora?</a> <?php if($etapa_aberta->num_rows() == 1){ echo'<span class="badge pull-right">1</span>';} ?></h4>
						</div>
						
						<div class="panel-body">

							<?php if($etapa_aberta->num_rows() == 1){ $a_etapa = $etapa_aberta->row();?>

								<br />
								
								<table class="table table-hover">

									<tr>
										<td width="150px;"><strong>Projeto:</strong></td>
										<td>
											<a href="<?php echo base_url('projeto/visualizar/'. $a_etapa->idprojeto); ?>" title="<?php echo $a_etapa->projeto_descricao; ?>"><?php echo $a_etapa->nomeprojeto.'</a> - <strong>Prioridade: </strong>'.ucfirst($a_etapa->projeto_prioridade); ?>
										</td>
									</tr>

									<tr>
										<td><strong>Fase:</strong></td>
										<td><?php echo $a_etapa->fase; ?></td>
									</tr>

									<tr>
										<td><strong>Descrição Técnica:</strong></td>
										<td><?php echo $a_etapa->descricao_tecnica; ?></td>
									</tr>

									<tr>
										<td><strong>Descrição Cliente:</strong></td>
										<td><?php echo $a_etapa->descricao_cliente; ?></td>
									</tr>

								</table>

							<?php }else{
								#echo 'Não existe etapa em aberto no momento.';
							} ?>

							<?php if($etapa_aberta->num_rows() == 1){ $a_etapa = $etapa_aberta->row();?> <span class="pull-left"><strong><?php echo fdata($a_etapa->data, "/") . ' - ' . fhora($a_etapa->inicio); ?></strong><br> <?php echo $a_etapa->fase . ' - ' . $a_etapa->nomeprojeto; ?></span>
								<span class="pull-right"><a href="<?php echo base_url('etapa'); ?>" class="btn btn-primary"><i class="glyphicon glyphicon-time"></i> Terminar Contagem</a>
								<a href="<?php echo base_url('etapa'); ?>" class="btn btn-danger"><i class="glyphicon glyphicon-remove"></i> Excluir Contagem</a></span>
							<?php }else{ ?>
								<span class="pull-right"><a href="<?php echo base_url('etapa'); ?>" class="btn btn-primary"><i class="glyphicon glyphicon-time"></i> Lançar Horas de Etapa</a></span>
							<?php } ?>
						</div>
					</div>

				</div>

				

				<div role="tabpanel" class="tab-pane active" id="settings">
					<br />
					<div class="panel panel-default">

						<div class="panel-heading">Quantidades de horas trabalhadas por dia</div>

						<div id="grafico_horas_trabalhadas" style="min-width: 500px; width: 100%; height: 350px; margin: 0 auto"></div>

						<?php /*
						<table class="table table-bordered table-hover table-condensed" id="table_horas_trabalhadas">

							<!--<tr class="info"><td colspan="10"><strong>Etapas Concluídas</strong></td></tr>-->
							
							<tr>
								<th width="150px;" title="Data">Data</th>
								<th width="" title="Quantidade de horas realizadas no dia">Horas</th>	
							</tr>
							
							<?php if(count($horas_trabalhadas) == 0){ ?>
								<tr><td colspan="2" style="text-align:center;">Você ainda não tem horas trabalhadas.</td></tr>
							<?php } ?>
							
							<?php foreach($horas_trabalhadas as $hr_data => $hora){ ?>
							<tr>
								<td><?php echo fdata($hr_data, '/'); ?></td>
								<td><?php echo $hora; ?></td>
							</tr>
							<?php } ?>
							
						</table>

						*/ ?>
					</div>
							
						<?php /*if($etapas->num_rows() > 10){ ?>
							<div id="paginacao_horas_trabalhadas" style="display:inline"></div>

							<script>
							var pager2 = new Pager('table_horas_trabalhadas', 10);
							pager2.init();
							pager2.showPageNav('pager2', 'paginacao_horas_trabalhadas');
							pager2.showPage(1);
							</script>
						<?php } */ ?>


				</div>
			</div>

		</div>



    </div>

</div><!-- /.row -->

<script>
$(function () {
        $('#grafico_horas_trabalhadas').highcharts({
			credits: false,
            chart: {
            },
            title: {
                text: 'Últimos 10 dias'
            },
            xAxis: {
				categories: [
				<?php 
				$cont = 0;
				foreach($horas_trabalhadas as $hr_data => $hora){ 
					echo "'". fdata($hr_data, '/') . "',";
					$cont++;
					if($cont == 10) break;
				} ?>
				]
            },
			yAxis: {
				title: {
                    text: 'Quantidade de Horas'
                },
            },
			
            series: [	
			{
                type: 'column',
                name: 'Horas',
                data: [
                <?php 
				$cont = 0;
				foreach($horas_trabalhadas as $hr_data => $hora){ 
					$hr = explode(':', $hora);

					echo (float) $hr[0].'.'.$hr[1];
					echo ",";

					$cont++;
					if($cont == 10) break;
				} ?>		
                ],
                marker: {
                	lineWidth: 2,
                	lineColor: Highcharts.getOptions().colors[3],
                	fillColor: 'white'
                }
            }]
        });
    });
</script>


