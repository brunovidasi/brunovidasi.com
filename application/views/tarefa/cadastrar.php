<script src="<?php echo base_url('assets/js/jquery.mousewheel.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/timeentry/jquery.timeentry.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/dateentry/jquery.dateentry.js'); ?>" type="text/javascript"></script>

<p class="titulo_pagina" style="float:left"><?php echo lang('titulo_cadastrar_tarefa'); ?></p> <br><br><br>

<?php require('application/views/includes/mensagem.php'); ?>

<form action="<?php echo base_url('tarefa/insert') ?>" method="post" name="form1" class="form1" id="lancar_etapa">

    <table width="100%" border="0" cellpadding="3" cellspacing="3" class="tabela_principal" style="padding:10px;">

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_cliente'); ?>:</strong> <span class="obrigatorio">*</span></div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-5 col-md-4 col-sm-5 inputs <?php if(form_error('idcliente')){ echo 'has-error has-feedback'; }?>">
					<select name="idcliente" class="form-control select2" id="idcliente">
						<option hidden></option>
						<?php foreach($clientes->result() as $cliente){
							$selected = "";
							if(set_value('idcliente', $cliente_id) == $cliente->idcliente){
								$selected = 'selected="selected"';
							}
							echo '<option value="'. $cliente->idcliente .'" '. $selected .'>'. $cliente->nome .'</option>';
						} ?>
					</select>
					<?php echo form_error('idcliente', '<span><label class="control-label" for="idcliente">', '</label></span>'); ?>
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_projeto'); ?>:</strong> <span class="obrigatorio">*</span></div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-6 col-md-8 col-sm-8 inputs <?php if(form_error('idprojeto')){ echo 'has-error has-feedback'; }?>" style="display:inline">
					<select name="idprojeto" class="form-control select2" id="idprojeto">
						<option hidden></option>
						<?php
							$idc = set_value('idcliente', $cliente_id);
							if(empty($idc)){
								echo '<option value="">'.lang('msg_selecione_cliente').'</option>';
							}else{
								echo $idcresult;
							}
						?>
					</select>
					<?php echo form_error('idprojeto', '<span><label class="control-label" for="idprojeto">', '</label></span>'); ?>
				</div>
				<div class="col-lg-6 col-md-4 col-sm-4 inputs" style="display:inline">
					<span id="loader_projetos"></span>
				</div>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_fase'); ?>:</strong> <span class="obrigatorio">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-3 col-md-4 col-sm-4 inputs <?php if(form_error('idfase')){ echo 'has-error has-feedback'; }?>">
					<select name="idfase" class="form-control" id="idfase">
						<option hidden></option>
						<?php foreach($fases->result() as $fase){
							$selected = "";
							if(set_value('idfase') == $fase->idfase){
								$selected = 'selected="selected"';
							}
							echo '<option value="'. $fase->idfase .'" '. $selected .'>'. $fase->fase .'</option>';
						} ?>
					</select>
					<?php echo form_error('idfase', '<span><label class="control-label" for="idfase">', '</label></span>'); ?>
				</div>
			<td>
		</tr>

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_responsavel'); ?>:</strong> <span class="obrigatorio">*</span></div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-5 col-md-4 col-sm-5 inputs <?php if(form_error('idusuario')){ echo 'has-error has-feedback'; }?>">
					<select name="idusuario" class="form-control select2" id="idusuario">
						<option hidden></option>
						<?php foreach($usuarios->result() as $usuario){
							if($usuario->idusuario == 1)
								continue;

							$selected = "";
							if(set_value('idusuario', $usuario_id) == $usuario->idusuario)
								$selected = 'selected="selected"';
							
							echo '<option value="'. $usuario->idusuario .'" '. $selected .'>'. $usuario->nome . ' - ' . $usuario->cargo .'</option>';
						} ?>
					</select>
					<?php echo form_error('idusuario', '<span><label class="control-label" for="idusuario">', '</label></span>'); ?>
				</div>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_nome'); ?>:</strong> <span class="obrigatorio">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-4 col-md-8 col-sm-8 inputs <?php if(form_error('nome')){ echo 'has-error has-feedback'; }?>">
					<input name="nome" id="nome" type="text" class="form-control" id="nome" value="<?php echo set_value('nome'); ?>" />
					<?php echo form_error('nome', '<span><label class="control-label" for="nome">', '</label></span>'); ?>
				</div>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_descricao'); ?>:</strong> <span class="obrigatorio"></span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-6 col-md-10 col-sm-10 inputs <?php if(form_error('descricao')){ echo 'has-error has-feedback'; }?>">
					<textarea name="descricao" id="descricao" class="form-control" id="descricao" rows="3"><?php echo set_value('descricao'); ?></textarea>
					<?php echo form_error('descricao', '<span><label class="control-label" for="descricao">', '</label></span>'); ?>
				</div>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_prazo'); ?>:</strong> <span class="obrigatorio">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-2 col-md-3 col-sm-4 inputs <?php if(form_error('data')){ echo 'has-error has-feedback'; }?>">
					<input name="data" type="text" class="form-control" id="datepicker" value="<?php echo set_value("data", date("d/m/Y")); ?>" maxlength="10" />
					<?php echo form_error('data', '<span><label class="control-label" for="data">', '</label></span>'); ?>
				</div>
				<span class="btn btn-primary" id="btn_data" onclick="pegadata()" /> <?php echo lang('btn_hoje'); ?> </span>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('msg_horas_previstas'); ?>:</strong> <span class="obrigatorio">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-1 col-md-2 col-sm-3 inputs <?php if(form_error('horas')){ echo 'has-error has-feedback'; }?>">
					<input name="horas" type="number" class="form-control" id="horas" size="10" maxlength="5" value="<?php echo set_value('horas'); ?>"/>
					<?php echo form_error('horas', '<span><label class="control-label" for="horas">', '</label></span>'); ?>
				</div>
			</td>
		</tr>		
		
		<tr>
			<td valign="middle"></td>
			
			<td style="padding-left:14px">
				<!-- <input name="idusuario" type="hidden" value="<?php echo $this->session->userdata('id'); ?>" /> -->
				
				<button type="submit" class="btn btn-primary" id="submit" /><i class="glyphicon glyphicon-import"></i> <?php echo lang('btn_cadastrar'); ?></button>
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
	
	$("select[name=idcliente]").change(function(){
		$("html").css("cursor", "progress");
		$("select[name=idprojeto]").html('<option value="0"><?php echo lang("carregando_projetos"); ?></option>');
		$("#loader_projetos").html("<img src='<?php echo base_url('assets/images/sistema/ajax_loader.gif'); ?>' width='30px'/>");
		
		$.post("<?php print base_url('etapa/get_projetos/'.$projeto); ?>", {idcliente:$(this).val()}, function(valor){
			$("select[name=idprojeto]").html(valor);
			$("#loader_projetos").html("");
			$("html").css("cursor", "auto");
		});
	});
	
	$(function() {
		$( "#datepicker" ).datepicker();
		$("#datepicker").mask("99/99/9999");
		$("#inicio").mask("99:99");
	});

	$( "#copiar_descricao" ).click(function() {
		var msg = $('#descricao_tecnica').val();
		$('#descricao_cliente').val(msg);
		$("#descricao_cliente").parent().removeClass("has-error");
		$("label[for='descricao_cliente']").parent().html("");		
	});

	$('#descricao_tecnica, #descricao_cliente, #inicio, #data, #idfase, #idprojeto, #idcliente').change(function(){
		var nome = $(this).attr('id');
		$("#"+nome).parent().removeClass("has-error");
		$("#"+nome).parent().removeClass("has-error");
		$("label[for='"+ nome +"']").parent().html("");
	});
	
	$('#submit').click(function(){
		$("html").css("cursor", "progress");
		$("#submit").addClass("disabled");
		$("#loader_submit").html("<img src='<?php echo base_url('assets/images/sistema/ajax_loader.gif'); ?>' width='20px'/>");
		
		$(".form1").submit();
	});
	
	$('#btn_inicio').click(function(){
		$("#inicio").parent().removeClass("has-error");
		$("label[for='inicio']").parent().html("");
	});
	
	$('#btn_data').click(function(){
		$("#data").parent().removeClass("has-error");
		$("label[for='data']").parent().html("");
	});

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
	
});
</script>