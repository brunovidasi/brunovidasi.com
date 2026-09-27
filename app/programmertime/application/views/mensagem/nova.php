<script src="<?php echo base_url('assets/js/texteditor/jquery-te-1.4.0.min.js');?>" type="text/javascript"></script>
<link rel="stylesheet" href="<?php echo base_url('assets/js/texteditor/jquery-te-1.4.0.css');?>" type="text/css">

<br>

<?php
	require('application/views/includes/mensagem.php');
	if(validation_errors() != ''){
	echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	}
?>
	
	<div class="formulario_nova_mensagem">
		<form  action="<?php echo base_url('mensagem/enviar_mensagem') ?>" method="post" name="form-comentario" class="form-comentario">
			<table width="100%" class="table">
				<tr>
					<td>
						<select name="id_usuario_to" class="form-control" id="id_usuario_to">
							<option value="">Enviar mensagem para:</option>
							<?php foreach($usuarios->result() as $usuario){
								$selected = "";
								if(set_value('id_usuario_to', $usuario_id) == $usuario->idusuario){
									$selected = 'selected="selected"';
								}
								echo '<option value="'. $usuario->idusuario .'" '. $selected .'>'. fnome($usuario->nome) .'</option>';
							} ?>
						</select>
					</td>

					<td>
						<select name="idcliente" class="form-control" id="idcliente">
							<option value="">Cliente</option>
							<?php foreach($clientes->result() as $cliente){
								$selected = "";
								if(set_value('idcliente', $cliente_id) == $cliente->idcliente){
									$selected = 'selected="selected"';
								}
								echo '<option value="'. $cliente->idcliente .'" '. $selected .'>'. $cliente->nome .'</option>';
							} ?>
						</select>
					</td>

				</tr>

				<tr>
					<td><input type="text" name="assunto" id="assunto" value="<?php echo set_value('assunto'); ?>" class="form-control" placeholder="Assunto" /></td>

					<td>
						<select name="idprojeto" class="form-control" id="idprojeto">
							<option value=""></option>
							<?php
								$idc = set_value('idcliente', $cliente_id);
								if(empty($idc)){
									echo '<option value="">Selecione o Cliente</option>';
								}else{
									echo $idcresult;
								}
							?>
						</select>
					</td>
				</tr>

				<tr>
					<td colspan="2">
						<textarea class="form-control" rows="3" class="col-lg-12" name="mensagem" id="nova_mensagem"><?php echo set_value('mensagem'); ?></textarea>
						<input type="hidden" name="resposta_de" value="0"/>
						<input type="hidden" name="nova" value="1"/>
					</td>
				</tr>
				
				<tr>
					<td align="right" colspan="2">
						<button type="button" class="btn btn-warning btn-lg"><i class="glyphicon glyphicon-floppy-disk"></i> Salvar nos Rascunhos</button>
						<button type="submit" id="submit" class="btn btn-primary btn-lg"><i class="glyphicon glyphicon-share-alt"></i> Enviar Mensagem</button>
					</td>
				</tr>
			</table>
		</form>
	</div>
	
</div>

<script>
jQuery(document).ready(function($){
	$("#nova_mensagem").jqte({ol: false, ul: false, format: false});
	
	$("select[name=idcliente]").change(function(){
		$("html").css("cursor", "progress");
		$("select[name=idprojeto]").html('<option value="0">Carregando projetos ...</option>');
		$("#loader_projetos").html("<img src='<?php echo base_url('assets/images/sistema/ajax_loader.gif'); ?>' width='30px'/>");
		
		$.post("<?php print base_url("etapa/get_projetos/".$projeto); ?>", {idcliente:$(this).val()}, function(valor){
			$("select[name=idprojeto]").html(valor);
			$("#loader_projetos").html("");
			$("html").css("cursor", "auto");
		});
	});
	
	$('#submit').click(function(){
		$("html").css("cursor", "progress");
		$("#submit").addClass("disabled");
		$("#loader_submit").html("<img src='<?php echo base_url('assets/images/sistema/ajax_loader.gif'); ?>' width='20px'/>");
		
		$(".form-comentario").submit();
	});

});
</script>

