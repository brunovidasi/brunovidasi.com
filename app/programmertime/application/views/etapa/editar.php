<script src="<?php echo base_url('assets/js/jquery.mousewheel.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/timeentry/jquery.timeentry.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/dateentry/jquery.dateentry.js'); ?>" type="text/javascript"></script>

<p class="titulo_pagina" style="float:left"><?php echo lang('etapa_editar_titulo'); ?></p> <br><br><br>

<?php
	$data	 			= fdata($ret->data, "/");
	$inicio 			= $ret->inicio;
	$fim	 			= $ret->fim;
	
	$horainicio = explode(":", $inicio);
	$inicio = $horainicio[0] . ':' . $horainicio[1];
	
	if(!empty($ret->fim)){
		$horafim = explode(":", $fim);
		$fim = $horafim[0] . ':' . $horafim[1];
	}else{
		$fim = "";
	}

	require('application/views/includes/mensagem.php');
	if(validation_errors() != '')
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	
?>

<form action="<?php echo base_url('etapa/edit') ?>" method="post" name="form1" class="form1">

    <table width="100%" border="0" cellpadding="3" cellspacing="3" class="tabela_principal" style="padding:10px;">

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong><?php echo lang('lbl_cliente'); ?>:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-5 col-md-4 col-sm-5 inputs">
					<select name="idcliente" class="form-control select2" id="idcliente">
						<?php
						foreach($clientes->result() as $cliente){
							$selected = "";
							if($idcliente == $cliente->idcliente){
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
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong><?php echo lang('lbl_projeto'); ?>:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-6 col-md-8 col-sm-8 inputs">
					<select name="idprojeto" class="form-control select2" id="idprojeto">
						<?php
						foreach($projeto->result() as $proj){
							$prazo = fdatahora($proj->prazo, "/");
					
							$data_projeto = $prazo['data'];
							$hora = $prazo['hora'];
							
							echo '<option value="'.$proj->idprojeto .'">'.$proj->nome.' - '.lang('msg_prazo').': '. $data_projeto .' '. $hora .' - '.lang('msg_prioridade').': '. $proj->prioridade .'</option>';
						}
						?>
					</select>
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_tarefa'); ?>:</strong> <span class="obrigatorio"></span></div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-6 col-md-8 col-sm-8 inputs <?php if(form_error('idtarefa')){ echo 'has-error has-feedback'; }?>" style="display:inline">
					
					<select name="idtarefa" class="form-control select2" id="idtarefa">
						<?php
							$idp = set_value('idprojeto', $idprojeto);
							if(empty($idp)){
								echo '<option value="0">'.lang('msg_selecione_tarefa').'</option>';
							}else{
								echo $idpresult;
							}
						?>
					</select>
					<?php echo form_error('idtarefa', '<span><label class="control-label" for="idtarefa">', '</label></span>'); ?>
				</div>
				<div class="col-lg-6 col-md-4 col-sm-4 inputs" style="display:inline">
					<span id="loader_tarefas"></span>
				</div>
			</td>
		</tr>
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong><?php echo lang('lbl_fase'); ?>:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-3 col-md-4 col-sm-4 inputs">
					<select name="idfase" class="form-control" id="idfase">
						<option hidden></option>
						<?php
						foreach($fases->result() as $fase){
							$selected = "";
							if(set_value('idfase', $ret->idfase) == $fase->idfase){
								$selected = 'selected="selected"';
							}
							echo '<option value="'. $fase->idfase .'" '. $selected .'>'. $fase->fase .'</option>';
						}
						?>
					</select>
				</div>
			<td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong><?php echo lang('lbl_descricao_tecnica'); ?>:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-4 col-md-8 col-sm-8 inputs">
					<input name="descricao_tecnica" id="descricao_tecnica" type="text" class="form-control" id="descricao" value="<?php echo set_value('descricao_tecnica', $ret->descricao_tecnica); ?>" />
				</div>
				
				<span class="btn btn-primary" id="copiar_descricao"  title="<?php echo lang('title_clonar_descricao'); ?>" />
					<i class='glyphicon glyphicon-arrow-down'></i>
				</span>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong><?php echo lang('lbl_descricao_cliente'); ?>:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-4 col-md-8 col-sm-8 inputs">
					<input name="descricao_cliente" id="descricao_cliente" type="text" class="form-control" id="descricao" value="<?php echo set_value('descricao_cliente', $ret->descricao_cliente); ?>" />
				</div>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong><?php echo lang('lbl_data'); ?>:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-2 col-md-3 col-sm-4 inputs">
					<input name="data" type="text" class="form-control" id="datepicker" value="<?php echo set_value("data", $data); ?>" maxlength="10" />
				</div>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong><?php echo lang('lbl_hora_inicio'); ?>:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-1 col-md-2 col-sm-3 inputs">
					<input name="inicio" type="text" class="form-control" id="inicio" size="10" maxlength="5" value="<?php echo set_value('inicio', $inicio); ?>" />
				</div>
        
				<span class="btn btn-primary" onclick="pegahorainicio()" />
					<i class='glyphicon glyphicon-time'></i>
				</span>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs" style="margin-top: -20px;">
					<strong><?php echo lang('lbl_hora_final'); ?>:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-1 col-md-2 col-sm-3 inputs">
					<input name="fim" type="text" class="form-control" id="fim" size="10" maxlength="5" value="<?php echo set_value('fim', $fim); ?>" />&nbsp;
				</div>
				
				<span class="btn btn-primary" onclick="pegahorafinal()" />
					<i class='glyphicon glyphicon-time'></i>
				</span>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle"></td>
			
			<td style="padding-left:14px">
				<input name="idusuario" type="hidden" value="<?php echo set_value('idusuario', $ret->idusuario); ?>" />
				<input name="idetapa" type="hidden" value="<?php echo $ret->idetapa; ?>" />
				
				<input name="submit" type="submit" class="btn btn-primary" id="submit" value="<?php echo lang('btn_editar_contagem'); ?>" />
			</td>
		</tr>
		
		

	</table>
</form>
<br>

<script src="<?php echo base_url('assets/js/jquery.maskedinput.js');?>"></script>
<link rel="stylesheet" href="<?php echo base_url('assets/css/datepicker.css');?>" type="text/css">
<script src="<?php echo base_url('assets/js/bootstrap-datepicker.js');?>" type="text/javascript"></script>


<script>
	$(function() {
		$( "#datepicker" ).datepicker();
		$("#datepicker").mask("99/99/9999");
		$("#inicio").mask("99:99");
		$("#fim").mask("99:99");
	});
	
	$( "#copiar_descricao" ).click(function() {
		var msg = $('#descricao_tecnica').val();
		$('#descricao_cliente').val(msg);
	});
	
	jQuery(document).ready(function($){
		
		$('#inicio').timeEntry({
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
		
		$('#fim').timeEntry({
			show24Hours: true, 
			showSeconds: false,
			useMouseWheel: false,
			spinnerImage: '',
			separator: ':',
			timeSteps: [1, 1, 0]
		});
		
		$('#datepicker').dateEntry({
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
		
		$("select[name=idcliente]").change(function(){
			$("select[name=idprojeto]").html('<option value="0"><?php echo lang("carregando"); ?></option>');
			 
			$.post("<?php echo base_url('etapa/get_projetos/'.$idprojeto.'/'. $idcliente); ?>", {idcliente:$(this).val()}, function(valor){
				$("select[name=idprojeto]").html(valor);
			});
		});
		
		
	});

</script>
     