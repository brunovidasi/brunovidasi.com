<p class="titulo_pagina" style="float:left;">Gerar Relatório de Projeto</p> <br><br><br>

<?php
	require('application/views/includes/mensagem.php');
	if(validation_errors() != '')
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";

?>

<div class="" style="float:right; margin-bottom:10px; width:100%;">
	
	<form action="<?php echo base_url('relatorio/gerar') ?>" method="post" name="form1" class="form1">
	
	<table width="100%" border="0" cellpadding="3" cellspacing="3" class="tabela_principal" style="padding:10px; margin-top: 100px;">
	
		<tr>
			<td width="100%" valign="middle" align="center">
				<div class="col-lg-6 col-md-8 col-sm-8 inputs" style="float: none;">
					<select name="idcliente" class="form-control" id="idcliente">
						<option hidden>Selecione o Cliente</option>
						<?php
						foreach($clientes->result() as $cliente){
							$selected = "";
							if(set_value('idcliente', $cliente_id) == $cliente->idcliente){
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
			<td width="100%" valign="middle" align="center">
				<div class="col-lg-6 col-md-8 col-sm-8 inputs" style="float: none;">
					<select name="idprojeto" class="form-control" id="idprojeto" style="display:none;">
						<option value=""></option>
						<?php
							$idc = set_value('idcliente', $cliente_id);
							if(empty($idc)){
								echo '<option hidden>Selecione o Cliente</option>';
							}else{
								echo $idcresult;
							}
						?>
					</select>
				</div>
			</td>
		</tr>
		
		<tr>
			<td align="center"><button name="submit" type="submit" class="btn btn-primary disabled" id="submit"><i class="glyphicon glyphicon-file"></i> Gerar Relatório</button></td>
		</tr>
	
	</table>
	
	</form>
	
</div> <br>

<script>
jQuery(document).ready(function($){
	$("select[name=idcliente]").change(function(){
		$("select[name=idprojeto]").show(500);
		$("select[name=idprojeto]").html('<option value="0">Carregando...</option>');
		
		$.post("<?php print base_url("etapa/get_projetos/"); ?>", {idcliente:$(this).val()}, function(valor){
			$("select[name=idprojeto]").html(valor);
		});
	});

	$("select[name=idprojeto]").change(function(){
		$("#submit").removeClass('disabled');
	});
});
</script>