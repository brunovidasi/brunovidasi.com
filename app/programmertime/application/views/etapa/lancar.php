<script src="<?php echo base_url('assets/js/jquery.mousewheel.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/timeentry/jquery.timeentry.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/dateentry/jquery.dateentry.js'); ?>" type="text/javascript"></script>

<p class="titulo_pagina" style="float:left"><?php echo lang('etapa_titulo'); ?></p> <br><br><br>

<?php require('application/views/includes/mensagem.php'); ?>

<form action="<?php echo base_url('etapa/insert') ?>" method="post" name="form1" class="form1" id="lancar_etapa">

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
			<td width="10%" valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_tarefa'); ?>:</strong> <span class="obrigatorio"></span></div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-6 col-md-8 col-sm-8 inputs <?php if(form_error('idtarefa')){ echo 'has-error has-feedback'; }?>" style="display:inline">
					
					<select name="idtarefa" class="form-control select2" id="idtarefa">
						<option hidden></option>
						<?php
							$idp = set_value('idprojeto', $projeto_id);
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
				<div class="inputs"><strong><?php echo lang('lbl_fase'); ?>:</strong> <span class="obrigatorio">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-3 col-md-4 col-sm-4 inputs <?php if(form_error('idfase')){ echo 'has-error has-feedback'; }?>">
					<?php 
						$fase_disabled = '';
						if(set_value('idtarefa') != 0)
							$fase_disabled = 'disabled';
					?>
					<select name="idfase" class="form-control" id="idfase" <?php echo $fase_disabled; ?>>
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
					<?php if(empty($fase_disabled)) { ?>
						<input type="hidden" id="hfase" />
					<?php }else{ ?>
						<input type="hidden" id="hfase" name="idfase" value="<?php echo set_value('idfase'); ?>" />
					<?php } ?>
				</div>
			<td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_descricao_tecnica'); ?>:</strong> <span class="obrigatorio">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-4 col-md-8 col-sm-8 inputs <?php if(form_error('descricao_tecnica')){ echo 'has-error has-feedback'; }?>">
					<input name="descricao_tecnica" id="descricao_tecnica" type="text" class="form-control" id="descricao_tecnica" value="<?php echo set_value('descricao_tecnica'); ?>" placeholder="<?php echo lang('placeholder_descricao_tecnica'); ?>" />
					<?php echo form_error('descricao_tecnica', '<span><label class="control-label" for="descricao_tecnica">', '</label></span>'); ?>
				</div>
				<span class="btn btn-primary" id="copiar_descricao" title="<?php echo lang('title_clonar_descricao'); ?>" /><i class='glyphicon glyphicon-arrow-down'></i></span>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_descricao_cliente'); ?>:</strong> <span class="obrigatorio">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-4 col-md-8 col-sm-8 inputs <?php if(form_error('descricao_cliente')){ echo 'has-error has-feedback'; }?>">
					<input name="descricao_cliente" id="descricao_cliente" type="text" class="form-control" id="descricao_cliente" value="<?php echo set_value('descricao_cliente'); ?>" placeholder="<?php echo lang('placeholder_descricao_cliente'); ?>" />
					<?php echo form_error('descricao_cliente', '<span><label class="control-label" for="descricao_cliente">', '</label></span>'); ?>
				</div>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs"><strong><?php echo lang('lbl_data'); ?>:</strong> <span class="obrigatorio">*</span></div>
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
				<div class="inputs"><strong><?php echo lang('lbl_hora_inicio'); ?>:</strong> <span class="obrigatorio">*</span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-1 col-md-2 col-sm-3 inputs <?php if(form_error('inicio')){ echo 'has-error has-feedback'; }?>">
					<input name="inicio" type="text" class="form-control" id="inicio" size="10" maxlength="5" value="<?php echo set_value('inicio'); ?>"/>
					<?php echo form_error('inicio', '<span><label class="control-label" for="inicio">', '</label></span>'); ?>
				</div>
				<span class="btn btn-primary" id="btn_inicio" onclick="pegahorainicio()" /><i class='glyphicon glyphicon-time'></i></span>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs" style="margin-top: -20px;"><strong><?php echo lang('lbl_hora_final'); ?>:</strong> <span class="obrigatorio"></span></div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-1 col-md-2 col-sm-3 inputs">
					<input name="fim" type="text" class="form-control" id="fim" size="10" maxlength="5" disabled />&nbsp;
				</div>
				<span class="btn btn-primary disabled" onclick="#" /><i class='glyphicon glyphicon-time'></i></span>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle"></td>
			
			<td style="padding-left:14px">
				<input name="idusuario" type="hidden" value="<?php echo $this->session->userdata('id'); ?>" />
				
				<button type="submit" class="btn btn-primary" id="submit" /><i class="glyphicon glyphicon-import"></i> <?php echo lang('btn_iniciar_contagem'); ?></button>
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

	$("select[name=idprojeto]").change(function(){
		$("html").css("cursor", "progress");
		$("select[name=idtarefa]").html('<option value="0"><?php echo lang("carregando_tarefas"); ?></option>');
		$("#loader_tarefas").html("<img src='<?php echo base_url('assets/images/sistema/ajax_loader.gif'); ?>' width='30px'/>");
		
		$.post("<?php print base_url('etapa/get_tarefas/'.$projeto); ?>", {idprojeto:$(this).val()}, function(valor){
			$("select[name=idtarefa]").html(valor);
			$("#loader_tarefas").html("");
			$("html").css("cursor", "auto");
		});
	});

	$("select[name=idtarefa]").change(function(){
		
		var idtarefa = $(this).val();

		if(idtarefa == 0){
			$('select[name=idfase]').removeAttr('disabled');
			$('#hfase').attr('disabled', 'disabled');

		}else{
			$("html").css("cursor", "progress");
			$("select[name=idfase]").html('<option value="0"><?php echo lang("carregando_dados"); ?></option>');
			$("#loader_tarefas").html("<img src='<?php echo base_url('assets/images/sistema/ajax_loader.gif'); ?>' width='30px'/>");
			
			$.post("<?php print base_url('etapa/get_tarefa/'.$projeto); ?>", {idtarefa:idtarefa}, function(valor){
				var tarefa = $.parseJSON(valor);
				$('#descricao_tecnica').val(tarefa.nome);
				$('#descricao_cliente').val(tarefa.nome);
				$('select[name=idfase]').attr('disabled', 'disabled');
				$('select[name=idfase]').html('<option value="'+tarefa.idfase+'">'+tarefa.fase+'</option>');
				$('#hfase').removeAttr('disabled');
				$('#hfase').attr('name', 'idfase');
				$('#hfase').attr('value', tarefa.idfase);

				$("#loader_tarefas").html("");
				$("html").css("cursor", "auto");
			});
		}
	});
	
	$(function() {
		$( "#datepicker" ).datepicker();
		$("#datepicker").mask("99/99/9999");
		$("#inicio").mask("99:99");
	});

	$("#copiar_descricao").click(function() {
		var msg = $('#descricao_tecnica').val();
		$('#descricao_cliente').val(msg);
		$("#descricao_cliente").parent().removeClass("has-error");
		$("label[for='descricao_cliente']").parent().html("");		
	});

	$('#descricao_tecnica, #descricao_cliente, #inicio, #data, #idfase, #idprojeto, #idtarefa, #idcliente').change(function(){
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


	<?php if(!empty($tarefa_id)){ ?>

		var idtarefa = <?php echo (int) $tarefa_id; ?>;

		if(idtarefa == 0){
			$('select[name=idfase]').removeAttr('disabled');
			$('#hfase').attr('disabled', 'disabled');

		}else{
			$("html").css("cursor", "progress");
			$("select[name=idfase]").html('<option value="0"><?php echo lang("carregando_dados"); ?></option>');
			$("#loader_tarefas").html("<img src='<?php echo base_url('assets/images/sistema/ajax_loader.gif'); ?>' width='30px'/>");
			
			$.post("<?php print base_url('etapa/get_tarefa/'.$projeto); ?>", {idtarefa:idtarefa}, function(valor){
				var tarefa = $.parseJSON(valor);
				$('#descricao_tecnica').val(tarefa.nome);
				$('#descricao_cliente').val(tarefa.nome);
				$('select[name=idfase]').attr('disabled', 'disabled');
				$('select[name=idfase]').html('<option value="'+tarefa.idfase+'">'+tarefa.fase+'</option>');
				$('#hfase').removeAttr('disabled');
				$('#hfase').attr('name', 'idfase');
				$('#hfase').attr('value', tarefa.idfase);

				$("#loader_tarefas").html("");
				$("html").css("cursor", "auto");
			});
		}
	<?php } ?>
	
});
</script>