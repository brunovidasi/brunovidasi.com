<link type="text/css" href="<?php echo base_url('assets/js/paginacao/paging.css'); ?>" rel="stylesheet" />
<script type="text/javascript" src="<?php echo base_url('assets/js/paginacao/paging.js'); ?>"></script>

<p class="titulo_pagina"  style="float:left;">Olá! Como foi seu dia? Vamos aos nossos Projetos!</p> <br><br><br><br>

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

<div class="pull-left" style="width:58%">

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
					<td><?php echo $projeto->nome; ?></td>
					<td><?php echo $projeto->tipo; ?></td>
					<td><a href="<?php echo base_url('usuario/visualizar/'.$projeto->idresponsavel); ?>"><?php echo $projeto->responsavel; ?></a></td>
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
				<?php } ?>
				
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
	
	<button id="form-field-1" class="btn btn-primary" href="#etapas_modal" data-toggle="modal" data-target="#etapas_modal"></button>
	
	<div class="modal fade" id="etapas_modal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<span type="button" class="close" data-dismiss="modal" aria-hidden="true"></span>
				<div class="modal-body">
					<iframe id="frame_modal" src="<?php echo base_url("teste/modal/"); ?>" width="100%"  scrolling="auto" border="0" height="530px" style="border:0"></iframe>
				</div>
				
			</div>
		</div>
	</div>
	
</div>


<div class="pull-right" style="width:40%">
<div class="panel-group" id="accordion">
<div class="panel panel-default">

	<div class="panel-heading">
		<h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#collapse">Avisos Gerais</a> <span class="badge pull-right">3</span></h4>
	</div>
	
	<div id="collapse" class="panel-collapse collapse">
		<div class="panel-body">
			<table class="table">
			
				<tr>
					<td rowspan="2"><img src="http://placehold.it/100x100" /></td>
					<td><strong><a href="">Bruno Vieira</a>:</strong> Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS.</td>
					
				</tr>
				<tr>
					<td><span class="pull-right"><strong><i class="glyphicon glyphicon-time"></i> 20/05/2014 10:00</strong></span></td>
				</tr>
				
				<tr>
					<td rowspan="2"><img src="<?php echo base_url('assets/images/sistema/logo.png'); ?>" width="100px" height="100px"/></td>
					<td><strong>Aviso Automático:</strong> Prazo do projeto Site Pessoal, vai expirar em 10 dias.</td>
					
				</tr>
				<tr>
					<td><span class="pull-right"><strong><i class="glyphicon glyphicon-time"></i> 20/05/2014 10:00</strong></span></td>
				</tr>
				
				<tr>
					<td rowspan="2"><img src="http://placehold.it/100x100" /></td>
					<td><strong><a href="">Bruno Vieira</a>:</strong> Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS.</td>
					
				</tr>
				<tr>
					<td><span class="pull-right"><strong><i class="glyphicon glyphicon-time"></i> 20/05/2014 10:00</strong></span></td>
				</tr>
				
				
			</table>
		</div>
	</div>
	
	<div class="panel-body">
		Existem 3 novos avisos desde a última vez que você entrou no Programmer Time! <a data-toggle="collapse" data-parent="#accordion" href="#collapse">Clique para ver!</a>
	</div>
</div>

<div class="panel panel-default">

	<div class="panel-heading">
		<h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#collapse3">O que estou fazendo agora?</a> <?php if($etapa_aberta->num_rows() == 1){ echo'<span class="badge pull-right">1</span>';} ?></h4>
	</div>
	
	<div id="collapse3" class="panel-collapse collapse in">
		<?php if($etapa_aberta->num_rows() == 1){ $a_etapa = $etapa_aberta->row();?>
			
			<table class="table table-condensed">
			<tr>
				<td><strong>Projeto:</strong></td>
				<td><?php echo $a_etapa->nomeprojeto; ?></td>
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
	</div>
	
	<div class="panel-body">
		<?php if($etapa_aberta->num_rows() == 1){ $a_etapa = $etapa_aberta->row();?> <span class="pull-left"><strong><?php echo fdata($a_etapa->data, "/") . ' - ' . fhora($a_etapa->inicio); ?></strong><br> <?php echo $a_etapa->fase . ' - ' . $a_etapa->nomeprojeto; ?></span>
			<span class="pull-right"><a href="<?php echo base_url('etapa'); ?>" class="btn btn-primary"><i class="glyphicon glyphicon-time"></i> Terminar Contagem</a>
			<a href="<?php echo base_url('etapa'); ?>" class="btn btn-danger"><i class="glyphicon glyphicon-remove"></i> Excluir Contagem</a></span>
		<?php }else{ ?>
			<span class="pull-right"><a href="<?php echo base_url('etapa'); ?>" class="btn btn-primary"><i class="glyphicon glyphicon-time"></i> Lançar Horas de Etapa</a></span>
		<?php } ?>
	</div>
</div>


<div class="panel panel-default">

	<div class="panel-heading">
		<h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#collapse2">Minhas Anotações</a></h4>
	</div>
	
	<div id="collapse2" class="panel-collapse collapse">
		<div class="panel-body">
			<label>O que temos para hoje?</label>
			<form action="<?php echo base_url('usuario/obs') ?>" method="post" name="form1" class="form1">
				<textarea name="obs" class="form-control" id="obs" rows="6"><?php echo set_value('obs', 'Minhas Anotações blablabla');  ?></textarea>
				<br><button type="submit" name="submit" class="btn btn-primary pull-right"><i class="glyphicon glyphicon-file"></i> Salvar Nota</button>
			</form>
		</div>
	</div>
	
	<div class="panel-body">
		<strong>20/05/2014: </strong>Minhas Anotações blablabla
	</div>
</div>

<div class="panel panel-default">

	<div class="panel-heading">
		<h4 class="panel-title"><a data-toggle="collapse" data-parent="#accordion" href="#collapse4">Outro menu</a> <span class="badge pull-right">1</span></h4>
	</div>
	
	<div id="collapse4" class="panel-collapse collapse">
		<div class="panel-body">
			
		</div>
	</div>
	
	<div class="panel-body">
		<strong>20/05/2014 - 12:54 </strong> Projeto Tal
	</div>
</div>


</div>

</div>

