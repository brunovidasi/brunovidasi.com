<p class="titulo_pagina" style="">Cadastrar Ajuda</p> <br>

<?php
	require('application/views/includes/mensagem.php');
	if(validation_errors() != ''){
	echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	}
?>

<form action="<?php echo base_url('ajuda/update/'.$ajuda->idajuda) ?>" method="post" name="form1" class="form1">

    <table width="100%" border="0" cellpadding="3" cellspacing="3" class="tabela_principal" style="padding:10px;">
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Título:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="titulo" type="text" class="form-control" id="" placeholder="Ex: Como lançar etapa?" value="<?php echo set_value("titulo", $ajuda->titulo); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Texto:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-6 col-md-9 inputs">
					<textarea name="texto" class="form-control" id="texto_ajuda"><?php echo set_value("texto", $ajuda->texto); ?></textarea>
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Tipo:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="tipo" type="text" class="form-control" id="" placeholder="Ex: Lançar Etapa" value="<?php echo set_value("tipo", $ajuda->tipo); ?>" />
				</div>
			</td>
		</tr>
		
		<input type="hidden" name="status" value="ativo" />
		
		<tr>
			<td valign="middle"></td>
			
			<td style="padding-left:14px">
				<input name="submit" type="submit" class="btn btn-primary" id="submit" value="Editar Ajuda" />
			</td>
		</tr>

	</table>
</form>
<br>

<script src="<?php echo base_url('assets/js/texteditor/jquery-te-1.4.0.min.js');?>" type="text/javascript"></script>
<link rel="stylesheet" href="<?php echo base_url('assets/js/texteditor/jquery-te-1.4.0.css');?>" type="text/css">

<script>$("#texto_ajuda").jqte();</script>