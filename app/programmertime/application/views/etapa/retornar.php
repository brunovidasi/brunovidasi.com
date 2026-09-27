<script src="<?php echo base_url('assets/js/jquery.mousewheel.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/timeentry/jquery.timeentry.js'); ?>" type="text/javascript"></script>

<p class="titulo_pagina" style="float:left"><?php echo lang('etapa_titulo'); ?></p> <br><br><br>

<?php
	$data	 			= fdata($ret->data, "/");
	$inicio 			= $ret->inicio;
	$horainicio = explode(":", $inicio);
	$inicio = $horainicio[0] . ':' . $horainicio[1];
		
	require('application/views/includes/mensagem.php');
?>

<form action="<?php echo base_url('etapa/update') ?>" method="post" name="form1" class="form1">

    <table width="100%" border="0" cellpadding="3" cellspacing="3" class="tabela_principal" style="padding:10px;">

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_cliente'); ?>:</strong> <span class="obrigatorio">*</span></div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-5 col-md-4 col-sm-5 inputs">
					<select name="idcliente" class="form-control" id="idcliente" disabled>
						<?php foreach($clientes->result() as $cliente){
							$selected = "";
							if($idcliente == $cliente->idcliente)
								$selected = 'selected="selected"';

							echo '<option value="'. $cliente->idcliente .'" '. $selected .'>'. $cliente->nome .'</option>';
						} ?>
					</select>
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_projeto'); ?>:</strong> <span class="obrigatorio">*</span></div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-6 col-md-8 col-sm-8 inputs">
					<select name="idprojeto" class="form-control" id="idprojeto" disabled>
						<?php foreach($projeto->result() as $proj){
							$prazo = fdatahora($proj->prazo, "/");
					
							$data_projeto = $prazo['data'];
							$hora = $prazo['hora'];
							
							echo '<option value="'.$proj->idprojeto .'">'.$proj->nome.' - '.lang('msg_prazo').': '. $data_projeto .' '. $hora .' - '.lang('msg_prioridade').': '. $proj->prioridade .'</option>';
						} ?>
					</select>
				</div>
			</td>
		</tr>

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_tarefa'); ?>:</strong> <span class="obrigatorio"></span></div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-6 col-md-8 col-sm-8 inputs">
					<select name="idtarefa" class="form-control" id="idtarefa" disabled>
						<?php echo $tarefas; ?>
					</select>
				</div>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_fase'); ?>:</strong> <span class="obrigatorio">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-3 col-md-4 col-sm-4 inputs <?php if(form_error('idfase')){ echo 'has-error has-feedback'; }?>">
					<select name="idfase" class="form-control" id="idfase" disabled>
						<option hidden></option>
						<?php foreach($fases->result() as $fase){
							$selected = "";
							if(set_value('idfase', $ret->idfase) == $fase->idfase)
								$selected = 'selected="selected"';
							
							echo '<option value="'. $fase->idfase .'" '. $selected .'>'. $fase->fase .'</option>';
						} ?>
					</select>
					<?php echo form_error('idfase', '<span><label class="control-label" for="idfase">', '</label></span>'); ?>
				</div>
			<td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_descricao_tecnica'); ?>:</strong> <span class="obrigatorio">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-4 col-md-8 col-sm-8 inputs <?php if(form_error('descricao_tecnica')){ echo 'has-error has-feedback'; }?>">
					<input name="descricao_tecnica" id="descricao_tecnica" type="text" class="form-control" id="descricao" value="<?php echo set_value('descricao_tecnica', $ret->descricao_tecnica); ?>" />
					<?php echo form_error('descricao_tecnica', '<span><label class="control-label" for="descricao_tecnica">', '</label></span>'); ?>
				</div>
				<span class="btn btn-primary" id="copiar_descricao"  title="<?php echo lang('title_clonar_descricao'); ?>" /><i class='glyphicon glyphicon-arrow-down'></i></span>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_descricao_cliente'); ?>:</strong> <span class="obrigatorio">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-4 col-md-8 col-sm-8 inputs <?php if(form_error('descricao_cliente')){ echo 'has-error has-feedback'; }?>">
					<input name="descricao_cliente" id="descricao_cliente" type="text" class="form-control" id="descricao" value="<?php echo set_value('descricao_cliente', $ret->descricao_cliente); ?>" />
					<?php echo form_error('descricao_cliente', '<span><label class="control-label" for="descricao_cliente">', '</label></span>'); ?>
				</div>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_data'); ?>:</strong> <span class="obrigatorio">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-2 col-md-3 col-sm-4 inputs">
					<input name="data" type="text" class="form-control" id="" value="<?php echo $data; ?>" maxlength="10" disabled />
				</div>
				<span class="btn btn-primary disabled" onclick="pegadata()" /> <?php echo lang('btn_hoje'); ?> </span>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_hora_inicio'); ?>:</strong> <span class="obrigatorio">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-1 col-md-2 col-sm-3 inputs">
					<input name="inicio_disabled" type="text" class="form-control" id="inicio_disabled" size="10" maxlength="5" value="<?php echo $inicio; ?>" disabled />
				</div>
				<span class="btn btn-primary disabled" onclick="#" /><i class='glyphicon glyphicon-time'></i></span>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs" style="margin-top: -20px;"><strong><?php echo lang('lbl_hora_final'); ?>:</strong> <span class="obrigatorio">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-1 col-md-2 col-sm-3 inputs <?php if(form_error('fim')){ echo 'has-error has-feedback'; }?>">
					<input name="fim" type="text" class="form-control" id="fim" size="10" maxlength="5" value="<?php echo set_value('fim'); ?>" />&nbsp;
					<?php echo form_error('fim', '<span><label class="control-label" for="fim">', '</label></span>'); ?>
				</div>
				<span class="btn btn-primary" id="btn_fim" onclick="pegahorafinal()" /><i class='glyphicon glyphicon-time'></i></span>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle"></td>
			
			<td style="padding-left:14px">
				<input name="idusuario" type="hidden" value="<?php echo $this->session->userdata('id'); ?>" />
				<input name="inicio" type="hidden" class="form-control" id="inicio" size="10" maxlength="5" value="<?php echo $inicio; ?>" />
				<input name="idetapa" type="hidden" value="<?php echo $ret->idetapa; ?>" />
				
				<button type="submit" class="btn btn-primary" id="submit" /><i class="glyphicon glyphicon-import"></i> <?php echo lang('btn_terminar_contagem'); ?></button>
				<span id="loader_submit"></span>
			</td>
		</tr>
		
	</table>
</form>
<br>

<link rel="stylesheet" href="<?php echo base_url('assets/css/datepicker.css');?>" type="text/css">
<script src="<?php echo base_url('assets/js/bootstrap-datepicker.js');?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/jquery.maskedinput.js');?>"></script>

<script>
jQuery(document).ready(function($){
	$( "#copiar_descricao" ).click(function() {
		var msg = $('#descricao_tecnica').val();
		$('#descricao_cliente').val(msg);
		$("#descricao_cliente").parent().removeClass("has-error");
		$("label[for='descricao_cliente']").parent().html("");	
	});
	
	$('#descricao_tecnica, #descricao_cliente, #fim, #idfase').change(function(){
		var nome = $(this).attr('id');
		$("#"+nome).parent().removeClass("has-error");
		$("#"+nome).parent().removeClass("has-error");
		$("label[for='"+ nome +"']").parent().html("");
	});
	
	$('#btn_fim').click(function(){
		$("#fim").parent().removeClass("has-error");
		$("label[for='fim']").parent().html("");
	});
	
	$("#fim").mask("99:99");
	
	$('#fim').timeEntry({
		show24Hours: true, 
		showSeconds: false,
		useMouseWheel: false,
		spinnerImage: '',
		separator: ':',
		timeSteps: [1, 1, 0]
	});
	
	$('#submit').click(function(){
		$("html").css("cursor", "progress");
		$("#submit").addClass("disabled");
		$("#loader_submit").html("<img src='<?php echo base_url('assets/images/sistema/ajax_loader.gif'); ?>' width='20px'/>");
		
		$(".form1").submit();
	});
});
</script>