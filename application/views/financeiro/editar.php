<script src="<?php echo base_url('assets/js/jquery.mousewheel.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/timeentry/jquery.timeentry.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/dateentry/jquery.dateentry.js'); ?>" type="text/javascript"></script>

<p class="titulo_pagina" style="float:left;">Editar Informação Financeira de Projeto</p> <br>

<?php
	require('application/views/includes/mensagem.php');
	if(validation_errors() != '')
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
?>

<form action="<?php echo base_url('financeiro/update') ?>" method="post" name="form1" class="form1">

    <table width="100%" border="0" cellpadding="3" cellspacing="3" class="tabela_principal" style="padding:10px;">

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Cliente:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-5 col-md-4 inputs">
					<select name="idcliente" class="form-control select2" id="idcliente">
						<option value=""></option>
						<?php
						foreach($clientes->result() as $cliente){
							$selected = "";
							if(set_value('idcliente', $idcliente) == $cliente->idcliente){
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
					<strong>Projeto:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-6 col-md-8 inputs">
					<select name="idprojeto" class="form-control select2" id="idprojeto">
						<option value=""></option>
						<?php
							$idc = set_value('idcliente', $idcliente);
							if(empty($idc)){
								echo '<option value="">Selecione o Cliente</option>';
							}else{
								echo $idcresult;
							}
						?>
					</select>
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Tipo:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-8 inputs">
					<input type="radio" name="tipo" value="custo_projeto"  <?php echo (set_value('tipo', $financeiro->tipo) == 'custo_projeto') ? 'checked' : ""; ?> /> Valor do Projeto
					<input type="radio" name="tipo" value="custo_externo" <?php echo (set_value('tipo', $financeiro->tipo) == 'custo_externo') ? 'checked' : ""; ?> /> Custo Operacional / Externo
					<input type="radio" name="tipo" value="custo_outro"  <?php echo (set_value('tipo', $financeiro->tipo) == 'custo_outro') ? 'checked' : ""; ?> /> Outro
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Pago Por:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-8 inputs">
					<input type="radio" name="pago_por" value="empresa"  <?php echo (set_value('pago_por', $financeiro->pago_por) == 'empresa') ? 'checked' : ""; ?>/> Empresa
					<input type="radio" name="pago_por" value="cliente" <?php echo (set_value('pago_por', $financeiro->pago_por) == 'cliente') ? 'checked' : ""; ?>/> Cliente
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong>Descrição:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-5 inputs">
					<input name="descricao" type="text" class="form-control" id="" value="<?php echo set_value("descricao", $financeiro->descricao); ?>" />
				</div>
			<td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong>Valor:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-2 col-md-3 inputs">
					<input name="valor" type="text" class="form-control moeda" id="" value="<?php echo set_value("valor", $financeiro->valor); ?>" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Status:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-8 inputs">
					<input type="radio" name="status" value="nao_pago" onClick="habilita('nao_pago')" <?php echo (set_value('status', $financeiro->status) == 'nao_pago') ? 'checked' : ""; ?>/> Não Pago
					<input type="radio" name="status" value="cobrado" onClick="habilita('cobrado')" <?php echo (set_value('status', $financeiro->status) == 'cobrado') ? 'checked' : ""; ?>/> Cobrado
					<input type="radio" name="status" value="parcialmente_pago" onClick="habilita('parcialmente_pago')" <?php echo (set_value('status', $financeiro->status) == 'parcialmente_pago') ? 'checked' : ""; ?> /> Parcialmente Pago
					<input type="radio" name="status" value="pago" onClick="habilita('pago')" <?php echo (set_value('status', $financeiro->status) == 'pago') ? 'checked' : ""; ?> /> Pago
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td valign="middle">
				<div class="inputs" id="valor_pago" style="display:block;">
					<strong>Valor Pago:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-2 col-md-3 inputs" id="valor_pago2" style="display:block;">
					<input name="valor_pago" type="text" class="form-control moeda" id="" value="<?php echo set_value("valor_pago", $financeiro->valor_pago); ?>" />
				</div>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs" id="data_cobranca" style="display:block;">
					<strong>Data da Cobrança:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-2 col-md-3 inputs" id="data_cobranca2" style="display:block;">
					<input name="data_cobrado" type="text" class="form-control datepicker" id="data_cobrado" value="<?php echo fdata(set_value("data_cobrado", $financeiro->data_cobrado), "/"); ?>" maxlength="10" />
				</div>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs" id="data_pagamento" style="display:block;">
					<strong>Data Pagamento:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-2 col-md-3 inputs" id="data_pagamento2" style="display:block;">
					<input name="data_pago" type="text" class="form-control datepicker" id="data_pago" value="<?php echo fdata(set_value("data_pago", $financeiro->data_pago)); ?>" maxlength="10" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Link Externo:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-6 col-md-8 inputs">
					<input name="link" type="text" class="form-control" id="" value="<?php echo set_value("link", $financeiro->link); ?>" />
				</div>
			</td>
		</tr>
		
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong>Observação:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-5 com-md-8 inputs">
					<textarea name="obs" class="form-control" id="obs" rows="4"><?php echo set_value('obs', $financeiro->obs);  ?></textarea>
					<div id="obs_num_caracter"></div>
				</div>
			<td>
		</tr>
		
		
		<tr>
			<td valign="middle"></td>
			
			<td style="padding-left:14px">
				<input type="hidden" name="idfinanceiro" value="<?php echo $financeiro->idfinanceiro; ?>"/>
				<input name="submit" type="submit" class="btn btn-primary" id="submit" value="Editar Pagamento" />
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
		$( "#data_cobrado" ).datepicker();
		$( "#data_pago" ).datepicker();
		$("#datepicker").mask("99/99/9999");
		$(".datepicker").mask("99/99/9999");
	});
	
	// CKEDITOR.replace("obs", {
		// customConfig: '<?php echo base_url('assets/js/ckeditor/custom/editor_chamada_config.js'); ?>',
		// height: '100px',
		// width: '800px',
		// toolbar: [
				// ['Undo','Redo'],
				// ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
				// ['Cut','Copy','Paste','PasteText','PasteFromWord'],
				// ['Bold','Italic','Strike'],
				// ['Source'],
				// ['NumberedList','BulletedList'],
				// ['Link','Unlink'],
				// ['Maximize']
			// ],
	// });
	
	jQuery(document).ready(function($){
		$("select[name=idcliente]").change(function(){
			$("select[name=idprojeto]").html('<option value="0">Carregando...</option>');
			
			$.post("<?php echo base_url("financeiro/get_projetos/"); ?>", {idcliente:$(this).val()}, function(valor){
				$("select[name=idprojeto]").html(valor);
			});
		});
		
		$('.moeda').priceFormat({
			prefix: 'R$ ',
			centsSeparator: ',',
			thousandsSeparator: '.'
		});
		
		//Limitar caracteres no campo descrição
		var text_max = 500;
		$('#obs_num_caracter').html(text_max + ' caracteres restantes.');
		$('#obs').keyup(function() {
			var text_length = $('#obs').val().length;
			var text_remaining = text_max - text_length;
			$('#obs_num_caracter').html(text_remaining + ' caracteres restantes.');
		});
		
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
		
	});

	function habilita(opcao) {

		if(opcao == 'pago') {
			document.getElementById('valor_pago').style.display = 'block';
			document.getElementById('data_pagamento').style.display = 'block';
			document.getElementById('data_cobranca').style.display = 'none';
			document.getElementById('valor_pago2').style.display = 'block';
			document.getElementById('data_pagamento2').style.display = 'block';
			document.getElementById('data_cobranca2').style.display = 'none';
		}

		if(opcao == 'nao_pago') {
			document.getElementById('valor_pago').style.display = 'none';
			document.getElementById('data_pagamento').style.display = 'none';
			document.getElementById('data_cobranca').style.display = 'none';
			document.getElementById('valor_pago2').style.display = 'none';
			document.getElementById('data_pagamento2').style.display = 'none';
			document.getElementById('data_cobranca2').style.display = 'none';
		}

		if(opcao == 'cobrado') {
			document.getElementById('valor_pago').style.display = 'none';
			document.getElementById('data_pagamento').style.display = 'none';
			document.getElementById('data_cobranca').style.display = 'block';
			document.getElementById('valor_pago2').style.display = 'none';
			document.getElementById('data_pagamento2').style.display = 'none';
			document.getElementById('data_cobranca2').style.display = 'block';
		}
		
		if(opcao == 'parcialmente_pago') {
			document.getElementById('valor_pago').style.display = 'block';
			document.getElementById('data_pagamento').style.display = 'block';
			document.getElementById('data_cobranca').style.display = 'none';
			document.getElementById('valor_pago2').style.display = 'block';
			document.getElementById('data_pagamento2').style.display = 'block';
			document.getElementById('data_cobranca2').style.display = 'none';
		}
	}
	
	<?php
	if(set_value('status') == 'pago'){
	?>
		document.getElementById('valor_pago').style.display = 'block';
		document.getElementById('data_pagamento').style.display = 'block';
		document.getElementById('data_cobranca').style.display = 'none';
		document.getElementById('valor_pago2').style.display = 'block';
		document.getElementById('data_pagamento2').style.display = 'block';
		document.getElementById('data_cobranca2').style.display = 'none';
	<?php } ?>
	
	<?php
	if(set_value('status') == 'nao_pago'){
	?>
		document.getElementById('valor_pago').style.display = 'none';
		document.getElementById('data_pagamento').style.display = 'none';
		document.getElementById('data_cobranca').style.display = 'none';
		document.getElementById('valor_pago2').style.display = 'none';
		document.getElementById('data_pagamento2').style.display = 'none';
		document.getElementById('data_cobranca2').style.display = 'none';
	<?php } ?>
	
	<?php
	if(set_value('status') == 'cobrado'){
	?>
		document.getElementById('valor_pago').style.display = 'none';
		document.getElementById('data_pagamento').style.display = 'none';
		document.getElementById('data_cobranca').style.display = 'block';
		document.getElementById('valor_pago2').style.display = 'none';
		document.getElementById('data_pagamento2').style.display = 'none';
		document.getElementById('data_cobranca2').style.display = 'block';
	<?php } ?>
	
	<?php
	if(set_value('status') == 'parcialmente_pago'){
	?>
		document.getElementById('valor_pago').style.display = 'block';
		document.getElementById('data_pagamento').style.display = 'block';
		document.getElementById('data_cobranca').style.display = 'none';
		document.getElementById('valor_pago2').style.display = 'block';
		document.getElementById('data_pagamento2').style.display = 'block';
		document.getElementById('data_cobranca2').style.display = 'none';
	<?php } ?>
</script> 
     