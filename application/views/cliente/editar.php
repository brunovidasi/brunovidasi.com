<p class="titulo_pagina" style="float:left">Editar Cliente</p> <br>
<br><br>
<?php
	require('application/views/includes/mensagem.php');
	if(validation_errors() != '')
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
?>

<form action="<?php echo base_url('cliente/update/'.$cliente->idcliente) ?>" method="post" name="form1" class="form1">

    <table width="100%" border="0" cellpadding="3" cellspacing="3" class="tabela_principal" style="padding:10px;">
		
		<tr><td><br></td><td><strong style="margin-left: 15px;">Dados Gerais</strong></td></tr>

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Nome:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="nome" type="text" class="form-control" id="" value="<?php echo set_value("nome", $cliente->nome); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Website:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="website" type="url" class="form-control" id="" value="<?php echo set_value("website", $cliente->website); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>E-mail:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-7 inputs">
					<input name="email" type="text" class="form-control" id="" value="<?php echo set_value("email", $cliente->email); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Telefone:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-3 col-md-6 inputs">
					<input name="telefone" type="text" class="form-control telefone" id="" value="<?php echo set_value("telefone", $cliente->telefone); ?>" style="width:140px;" />
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
				<div class="col-lg-3 col-md-6 inputs">
					<input name="celular" type="text" class="form-control telefone" id="" value="<?php echo set_value("celular", $cliente->celular); ?>" style="width:140px;" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Razão Social:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="razao_social" type="text" class="form-control" id="" value="<?php echo set_value("razao_social", $cliente->razao_social); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>CNPJ:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="cnpj" type="text" class="form-control" id="cnpj" value="<?php echo set_value("cnpj", $cliente->cnpj); ?>" style="width:160px;" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>CPF:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="cpf" type="text" class="form-control" id="cpf" value="<?php echo set_value("cpf", $cliente->cpf); ?>" style="width:130px;" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		<tr><td><br></td><td><strong style="margin-left: 15px;">Contato Adicional</strong></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Nome:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="nome_contato" type="text" class="form-control" id="" value="<?php echo set_value("nome_contato", $cliente->nome_contato); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>E-mail:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="email_contato" type="text" class="form-control" id="" value="<?php echo set_value("email_contato", $cliente->email_contato); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Telefone:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="telefone_contato" type="text" class="form-control telefone" id="" value="<?php echo set_value("telefone_contato", $cliente->telefone_contato); ?>" style="width:140px;" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		<tr><td><br></td><td><strong style="margin-left: 15px;">Endereço</strong></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>CEP:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-2 col-md-3 inputs">
					<input name="endereco_cep" type="text" class="form-control" id="cep" value="<?php echo set_value("endereco_cep", $cliente->endereco_cep); ?>" />
				</div>
			</td>
		</tr>

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Logradouro:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="endereco" type="text" class="form-control" id="logradouro" value="<?php echo set_value("endereco", $cliente->endereco); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Número:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-1 col-md-2 inputs">
					<input name="endereco_numero" type="text" class="form-control" id="numero" value="<?php echo set_value("endereco_numero", $cliente->endereco_numero); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Complemento:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="endereco_complemento" type="text" class="form-control" id="" value="<?php echo set_value("endereco_complemento", $cliente->endereco_complemento); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Bairro:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-2 col-md-3 inputs">
					<input name="endereco_bairro" type="text" class="form-control" id="bairro" value="<?php echo set_value("endereco_bairro", $cliente->endereco_bairro); ?>" />
				</div>
			</td>
		</tr>

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Cidade:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-2 col-md-3 inputs">
					<input name="endereco_cidade" type="text" class="form-control" id="cidade" value="<?php echo set_value("endereco_cidade", $cliente->endereco_cidade); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Estado:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-1 col-md-2 inputs">
					<input name="endereco_estado" type="text" class="form-control" id="estado" maxlength="2" value="<?php echo set_value("endereco_estado", $cliente->endereco_estado); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td valign="middle"></td>
			
			<td style="padding-left:14px">
				<button name="submit" type="submit" class="btn btn-primary" id="submit" />Salvar Alterações</button>
			</td>
		</tr>
		
		

	</table>
</form>
<br>
<script src="<?php echo base_url('assets/js/jquery.maskedinput.js');?>"></script>
<script>
$(document).ready(function(){

	$("#cpf").mask("999.999.999-99");
	$("#cnpj").mask("99.999.999/9999-99");

	$('.telefone').mask("(99) 9999-9999?9").ready(function(event) {
		var target, phone, element;
		target = (event.currentTarget) ? event.currentTarget : event.srcElement;
		phone = target.value.replace(/\D/g, '');
		element = $(target);
		element.unmask();
		if(phone.length > 10) {
			element.mask("(99) 99999-999?9");
		} else {
			element.mask("(99) 9999-9999?9");  
		}
	});

	$("#cep").mask("99999-999");

	$("#cep").blur(function(e){
		reset();
		if($.trim($("#cep").val()) != ""){
			$.getScript("http://cep.republicavirtual.com.br/web_cep.php?formato=javascript&cep="+$("#cep").val(), function(){
				if(resultadoCEP["resultado"]){
					$("#logradouro").val(unescape(resultadoCEP["tipo_logradouro"])+" "+unescape(resultadoCEP["logradouro"]));
					$("#bairro").val(unescape(resultadoCEP["bairro"]));
					$("#cidade").val(unescape(resultadoCEP["cidade"]));
					$("#estado").val(unescape(resultadoCEP["uf"]));
				}else{
					alertify.success("Não foi possivel encontrar o endereço");
				}

				if(empty(resultadoCEP["uf"])){
					alertify.success("Não foi possivel encontrar o endereço");
				}
			});
		}
	});

	function remove(str, sub){
		i = str.indexOf(sub);
		r = "";
		if (i == -1) return str;
		{
		  r += str.substring(0,i) + remove(str.substring(i + sub.length), sub);
		}
		
		return r;
	}

	function empty(v){
		if ((v == null) || (v == 0) || (v == '') || (v == "") || (v == undefined)){
			return true
		}else {
			return false
		}
	}
});
</script>