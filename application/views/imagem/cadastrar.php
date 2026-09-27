<p class="titulo_pagina" style="">Cadastrar Imagem</p> <br>

<?php
	require('application/views/includes/mensagem.php');
	if(validation_errors() != ''){
	echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	}
?>


<form action="<?php echo base_url('imagem/inserir_imagem/') ?>" method="post" name="form1" class="form1">
	<table width="100%" border="0" cellpadding="3" cellspacing="3" class="tabela_principal" style="padding:10px;">
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Título da Imagem:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="titulo" type="text" class="form-control" id="" value="<?php echo set_value("titulo"); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td valign="middle">
				<div class="inputs">
					<strong>Descrição:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
			
			<td valign="middle">
				<div class="col-lg-5 com-md-8 inputs">
					<textarea name="descricao" class="form-control" id="descricao" rows="4"><?php echo set_value('descricao');  ?></textarea>
					<div id="descricao_num_caracter"></div>
				</div>
			<td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Imagem:</strong> <span class="obrigatorio">*</span>
				</div>
			</td> 
			
			<td>
				
			</td>
		</tr>
		
		<tr>
			<td valign="middle"></td>
			
			<td style="padding-left:14px">
				<button type="submit" name="submit" id="submit" class="btn btn-primary"> Cadastrar Imagem</button> 
			</td>
		</tr>
		
	</table>
</form>

<script>
jQuery(document).ready(function($){
	var text_max = 1000;
	
	$('#descricao_num_caracter').html(text_max + ' caracteres restantes.');
	
	$('#descricao').keyup(function() {
		var text_length = $('#descricao').val().length;
		var text_remaining = text_max - text_length;
		$('#descricao_num_caracter').html(text_remaining + ' caracteres restantes.');
	});
});
</script>