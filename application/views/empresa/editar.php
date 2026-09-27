<p class="titulo_pagina" style="">Editar Perfil da Empresa</p> <br>

<?php
	require('application/views/includes/mensagem.php');
	if(validation_errors() != ''){
	echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	}
?>

<form action="<?php echo base_url('empresa/update/') ?>" method="post" name="form1" class="form1">

    <table width="100%" border="0" cellpadding="3" cellspacing="3" class="tabela_principal" style="padding:10px;">
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Nome da Empresa:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="nome" type="text" class="form-control" id="" value="<?php echo set_value("nome", $empresa->nome); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Razão Social:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="razao_social" type="text" class="form-control" id="" value="<?php echo set_value("razao_social", $empresa->razao_social); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>CNPJ:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="cnpj" type="text" class="form-control" id="" value="<?php echo set_value("cnpj", $empresa->cnpj); ?>" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>E-mail:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="email" type="text" class="form-control" id="" value="<?php echo set_value("email", $empresa->email); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Telefone:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="telefone" type="text" class="form-control" id="" value="<?php echo set_value("telefone", $empresa->telefone); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Celular:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="celular" type="text" class="form-control" id="" value="<?php echo set_value("celular", $empresa->celular); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Web Site:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="website" type="text" class="form-control" id="" value="<?php echo set_value("website", $empresa->website); ?>" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Data de Fundação:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="data_fundacao" type="text" class="form-control" id="" value="<?php echo set_value("data_fundacao", $empresa->data_fundacao); ?>" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Endereço:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="endereco" type="text" class="form-control" id="" value="<?php echo set_value("endereco", $empresa->endereco); ?>" />
				</div>
			</td>
		</tr>
		
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td valign="middle"></td>
			
			<td style="padding-left:14px">
				<button name="submit" type="submit" class="btn btn-primary" id="submit" />Atualizar Perfil da Empresa</button>
			</td>
		</tr>
		

	</table>
</form>
<br>

<script src="<?php echo base_url('assets/js/jquery.maskedinput.js');?>"></script>
<script src="<?php echo base_url('assets/js/jquery.maskedinput.js');?>"></script>
<link rel="stylesheet" href="<?php echo base_url('assets/js/datepiker/themes/base/jquery.ui.all.css');?>" type="text/css">
<script src="<?php echo base_url('assets/js/datepiker/ui/jquery.ui.core.js');?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/datepiker/ui/jquery.ui.datepicker.js');?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/datepiker/ui/i18n/jquery.ui.datepicker-pt-BR.js');?>" type="text/javascript"></script>

<script>
function empty(v){
	if ((v == null) || (v == 0) || (v == '') || (v == "") || (v == undefined)){
		return true
	}else {
		return false
	}	
}

$(document).ready(function () {
	
	$("#cnpj").mask("99.999.999/9999-99");
	$("#telefone").mask("(99) 9999-9999");
	$("#celular").mask("(99) 99999-9999");
	
	$("#data_fundacao").mask("99/99/9999");
	
	reset = function () {
		alertify.set({
			labels : {
				ok     : "Deletar",
				cancel : "Não Deletar"
			},
			delay : 5000,
			buttonReverse : false,
			buttonFocus   : "ok"
		});
	};
	
	
});
</script>