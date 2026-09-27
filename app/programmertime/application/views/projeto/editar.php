<script src="<?php echo base_url('assets/js/jquery.mousewheel.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/timeentry/jquery.timeentry.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/dateentry/jquery.dateentry.js'); ?>" type="text/javascript"></script>

<p class="titulo_pagina" style="float:left;">Editar Projeto - <?php echo $projeto->nome; ?></p> <br><br><br>

<?php
	$prazo		 	= fdatahora($projeto->prazo, "/");
	$prazo_data		= $prazo['data'];
	$hora			= explode(":", $prazo['hora']);
	$prazo_hora 	= $hora[0] . ':' . $hora[1];
	
	if($projeto->data_fim == "00/00/0000" OR $projeto->data_fim == "0000-00-00")
		$projeto->data_fim = "";

	require('application/views/includes/mensagem.php');
	if(validation_errors() != '')
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";

?>

<form action="<?php echo base_url('projeto/update/'.$projeto->idprojeto) ?>" method="post" name="form1" class="form1">

    <table border="0" cellpadding="3" cellspacing="3" class="tabela_principal col-lg-12" style="padding:10px;">
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Nome do Projeto:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-6 inputs">
					<input name="nome" type="text" class="form-control" id="" value="<?php echo set_value("nome", $projeto->nome); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Cliente:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-5 inputs">
					<select name="idcliente" class="form-control select2" id="idcliente">
						<option hidden></option>
						<?php
						foreach($clientes->result() as $cliente){
							$selected = "";
							if(set_value('idcliente', $projeto->idcliente) == $cliente->idcliente){
								$selected = 'selected="selected"';
							}
							echo '<option value="'. $cliente->idcliente .'" '. $selected .'>'. $cliente->nome .'</option>';
						}						
						?>
					</select>
				</div>
			</td>
		</tr>
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong>Tipo:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-5 inputs">
					<select name="idtipo" class="form-control" id="idtipo">
						<option hidden></option>
						<?php
						foreach($tipos->result() as $tipo){
							$selected = "";
							if(set_value('idtipo', $projeto->idtipo) == $tipo->idtipo){
								$selected = 'selected="selected"';
							}
							echo '<option value="'. $tipo->idtipo .'" '. $selected .'>'. $tipo->tipo .'</option>';
						}
						?>
					</select>
				</div>
			<td>
		</tr>
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong>Responsável:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-5 inputs">
					<select name="idresponsavel" class="form-control select2" id="idresponsavel">
						<option hidden></option>
						<?php
						foreach($usuarios->result() as $usuario){
							$selected = "";
							if(set_value('idresponsavel', $projeto->idresponsavel) == $usuario->idusuario){
								$selected = 'selected="selected"';
							}
							echo '<option value="'. $usuario->idusuario .'" '. $selected .'>'. $usuario->nome .'</option>';
						}
						?>
					</select>
				</div>
			<td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong>Descrição:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-6 inputs">
					<textarea name="descricao" class="form-control" id="descricao"><?php echo set_value('descricao', $projeto->descricao);  ?></textarea>
				</div>
			<td>
		</tr>

		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong>Observação:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-6 inputs">
					<textarea name="obs" class="form-control" id="obs"><?php echo set_value('obs', $projeto->obs);  ?></textarea>
				</div>
			<td>
		</tr>
		
	</table>

	<table border="0" cellpadding="3" cellspacing="3" class="tabela_principal col-lg-12" style="padding:10px;">

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Status:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-12 inputs">
					<input type="radio" name="status" value="nao_comecado" <?php echo set_radio('status', 'nao_comecado', (($projeto->status=='nao_comecado')?TRUE:FALSE)); ?> /> Não Começado
					<input type="radio" name="status" value="desenvolvimento" <?php echo set_radio('status', 'desenvolvimento', (($projeto->status=='desenvolvimento')?TRUE:FALSE)); ?> /> Em Desenvolvimento
					<input type="radio" name="status" value="pausado" <?php echo set_radio('status', 'pausado', (($projeto->status=='pausado')?TRUE:FALSE)); ?> /> Pausado
					<input type="radio" name="status" value="concluido" <?php echo set_radio('status', 'concluido', (($projeto->status=='concluido')?TRUE:FALSE)); ?> /> Concluído
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong>Data Início:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-3 inputs">
					<input name="data_inicio" type="text" class="form-control datepicker" id="data_inicio" value="<?php echo set_value("data_inicio", fdata($projeto->data_inicio, "/")); ?>" maxlength="10" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>

		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong>Prazo:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-3 inputs">
					<input name="prazo" type="text" class="form-control datepicker" id="prazo" value="<?php echo set_value("prazo", $prazo_data); ?>" maxlength="10" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong>Prazo Hora:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-2 inputs">
					<input name="prazo_hora" type="text" class="form-control" id="prazo_hora" value="<?php echo set_value("prazo_hora", $prazo_hora); ?>" maxlength="10" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>

		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong>Data Fim:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-3 inputs">
					<input name="data_fim" type="text" class="form-control datepicker" id="data_fim" value="<?php if((!empty($projeto->data_fim)) OR ($projeto->data_fim != "0000-00-00")){ echo set_value("data_fim", fdata($projeto->data_fim, "/")); } ?>" maxlength="10" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Prioridade:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-8 inputs">
					<input type="radio" name="prioridade" value="baixa" <?php echo set_radio('prioridade', 'baixa', (($projeto->prioridade=='baixa')?TRUE:FALSE)); ?> /> Baixa
					<input type="radio" name="prioridade" value="normal" <?php echo set_radio('prioridade', 'normal', (($projeto->prioridade=='normal')?TRUE:FALSE)); ?> /> Normal
					<input type="radio" name="prioridade" value="urgente" <?php echo set_radio('prioridade', 'urgente', (($projeto->prioridade=='urgente')?TRUE:FALSE)); ?> /> Urgente
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Link Externo:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-6 inputs">
					<input name="link" type="text" class="form-control" id="" value="<?php echo set_value("link", $projeto->link); ?>" />
				</div>
			</td>
		</tr>

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-12 inputs">
					<input name="submit" type="submit" class="btn btn-primary" id="submit" value="Atualizar Projeto" />
				</div>
			</td>
		</tr>

	</table>

</form>
<br>

<script src="<?php echo base_url('assets/js/jquery.maskedinput.js');?>"></script>
<link rel="stylesheet" href="<?php echo base_url('assets/css/datepicker.css');?>" type="text/css">
<script src="<?php echo base_url('assets/js/bootstrap-datepicker.js');?>" type="text/javascript"></script>

<script src="<?php echo base_url('assets/js/texteditor/jquery-te-1.4.0.min.js');?>" type="text/javascript"></script>
<link rel="stylesheet" href="<?php echo base_url('assets/js/texteditor/jquery-te-1.4.0.css');?>" type="text/css">

<script>
	$(function() {
		$( "#prazo" ).datepicker();
		$( "#data_inicio" ).datepicker();
		$( "#data_fim" ).datepicker();
		$(".datepicker").mask("99/99/9999");
		$("#prazo_hora").mask("99:99");
	});
	
	$("#descricao").jqte({ol: false, ul: false, format: false});	
	$("#obs").jqte({ol: false, ul: false, format: false});	
	
	$('.datepicker').dateEntry({
		dateFormat: 'dmy/',
		spinnerImage: '',
		monthNames: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
		monthNamesShort: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
		dayNames: ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'],
		dayNamesShort: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'],
		useMouseWheel: false,
		minDate: null, 
		maxDate: null,
	});
	
	$('#prazo_hora').timeEntry({
		show24Hours: true, 
		showSeconds: false,
		useMouseWheel: false,
		spinnerImage: '',
		separator: ':',
		timeSteps: [1, 1, 0],
		defaultTime: null,
		minTime: null,
		maxTime: null,
	});
	
</script> 
     