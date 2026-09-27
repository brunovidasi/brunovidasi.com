<p class="titulo_pagina" style="float:left">Cadastrar Usuário</p> <br>
<br><br>
<?php
	require('application/views/includes/mensagem.php');
	if(validation_errors() != '')
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
?>

<form action="<?php echo base_url('usuario/insert') ?>" method="post" name="form1" class="form1">

    <table width="100%" border="0" cellpadding="3" cellspacing="3" class="tabela_principal" style="padding:10px;">
		
		<tr>
			<td width="11%" valign="middle">
				<div class="inputs">
					<strong>Nome Completo:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="89%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="nome" type="text" class="form-control" id="input_nome" value="<?php echo set_value("nome"); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="11%" valign="middle">
				<div class="inputs">
					<strong>Nivel de Acesso:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="89%" valign="middle">
				<div class="col-lg-3 col-md-4 inputs">
					<select name="nivel_acesso" class="form-control" id="nivel_acesso">
						<option hidden></option>
						<?php
						foreach($cargos->result() as $cargo){
							$selected = "";
							if(set_value('nivel_acesso') == $cargo->id)
								$selected = 'selected="selected"';
							if($cargo->id != '1')
								echo '<option value="'. $cargo->id .'" '. $selected .'>'. $cargo->cargo .'</option>';
						}				
						?>
					</select>
				</div>
				<a href="<?php echo base_url('nivel_acesso/cadastrar'); ?>" class="btn btn-primary"><i class='glyphicon glyphicon-plus'></i></a>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="9%" valign="middle">
				<div class="inputs">
					<strong>Nome de Usuário:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="89%" valign="middle">
				<div class="col-lg-2 col-md-6 inputs" id="nome_de_usuario">
					<input name="login" type="text" class="form-control" id="nome_usuario" value="<?php echo set_value("login"); ?>" placeholder="" />
					<span class="" id="usuario_ico" style="top:0px; width: 60px;"></span>
					<span><label class="control-label" for="login" id="label_login"></label></span>
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="9%" valign="middle">
				<div class="inputs">
					<strong>E-mail:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="89%" valign="middle">
				<div class="col-lg-4 col-md-7 inputs" id="email">
					<input name="email" type="text" class="form-control" id="email_campo" value="<?php echo set_value("email"); ?>" />
					<span class="" id="email_ico" style="top:0px; width: 60px;"></span>
					<span><label class="control-label" for="email" id="label_email"></label></span>
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="9%" valign="middle">
				<div class="inputs">
					<strong>Senha:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="89%" valign="middle">
				<div class="col-lg-2 col-md-6 inputs">
					<input name="senha" type="password" class="form-control" id="senha" value="<?php echo set_value("senha"); ?>" />
				</div>
				<span class="btn btn-primary" id="gera_senha"><i class="glyphicon glyphicon-arrow-left"></i> Gerar Senha</span>
			</td>
		</tr>
		
		<tr>
			<td width="9%" valign="middle">
				<div class="inputs">
					<strong>Confirmar Senha:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
			
			<td width="89%" valign="middle">
				<div class="col-lg-2 col-md-6 inputs" id="confirma_senha">
					<input name="confirmacao_senha" type="password" class="form-control" id="confirmacao_senha" value="<?php echo set_value("confirmacao_senha"); ?>" />
					<span class="" id="confirma_ico" style="top:0px; width: 60px;"></span>
				</div>
				<img style="display: none; margin-left: -14px; margin-bottom: 6px; width:25px;" id="img_confirm_senha" src="">
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="9%" valign="middle">
				<div class="inputs">
					<strong>Matrícula:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="89%" valign="middle">
				<div class="col-lg-2 col-md-6 inputs">
					<input name="matricula" type="text" class="form-control" id="" value="<?php echo set_value("matricula"); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="9%" valign="middle">
				<div class="inputs">
					<strong>RG:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="89%" valign="middle">
				<div class="col-lg-2 col-md-5 inputs">
					<input name="rg" type="text" class="form-control" id="" value="<?php echo set_value("rg"); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="9%" valign="middle">
				<div class="inputs">
					<strong>CPF:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="89%" valign="middle">
				<div class="col-lg-2 col-md-5 inputs">
					<input name="cpf" type="text" class="form-control" id="cpf" value="<?php echo set_value("cpf"); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td valign="middle"></td>
			
			<td style="padding-left:14px">
				<button name="submit" type="submit" class="btn btn-primary" id="submit" /><i class="glyphicon glyphicon-import"></i> Cadastrar Usuário</button>
			</td>
		</tr>
		
		

	</table>
</form>
<br>

<script src="<?php echo base_url('assets/js/jquery.maskedinput.js');?>"></script>
<script>
function empty(v){
	if ((v == null) || (v == 0) || (v == '') || (v == "") || (v == undefined)){
		return true
	}else {
		return false
	}	
}

$.extend({
  password: function (length, special) {
    var iteration = 0;
    var password = "";
    var randomNumber;
    if(special == undefined){
        var special = false;
    }
    while(iteration < length){
        randomNumber = (Math.floor((Math.random() * 100)) % 94) + 33;
        if(!special){
            if ((randomNumber >=33) && (randomNumber <=47)) { continue; }
            if ((randomNumber >=58) && (randomNumber <=64)) { continue; }
            if ((randomNumber >=91) && (randomNumber <=96)) { continue; }
            if ((randomNumber >=123) && (randomNumber <=126)) { continue; }
        }
        iteration++;
        password += String.fromCharCode(randomNumber);
    }
    return password;
  }
});

$(document).ready(function () {
	$('.form1').on("focusout", "#confirmacao_senha, #senha",function() {
		var senha = $("#senha").val();
		var confirma_senha = $("#confirmacao_senha").val();
		if((!empty(senha)) && (!empty(confirma_senha))){
			if(senha == confirma_senha){
				$("#confirma_senha").removeClass('form-group has-error has-feedback');
				$("#confirma_ico").removeClass('glyphicon glyphicon-remove form-control-feedback');
				$("#confirma_senha").addClass('form-group has-success has-feedback');
				$("#confirma_ico").addClass('glyphicon glyphicon-ok form-control-feedback');
				$("#confirma_ico").fadeIn();
			}else{
				$("#confirma_senha").removeClass('form-group has-success has-feedback');
				$("#confirma_ico").removeClass('glyphicon glyphicon-ok form-control-feedback');
				$("#confirma_senha").addClass('form-group has-error has-feedback');
				$("#confirma_ico").addClass('glyphicon glyphicon-remove form-control-feedback');
				$("#confirma_ico").fadeIn();
			}
		}
		
		else{
			$("#confirma_senha").removeClass('form-group has-success has-feedback');
			$("#confirma_ico").removeClass('glyphicon glyphicon-ok form-control-feedback');
			$("#confirma_senha").removeClass('form-group has-error has-feedback');
			$("#confirma_ico").removeClass('glyphicon glyphicon-remove form-control-feedback');
			$("#confirma_ico").fadeOut();
		}
	});
	$('.form1').on("keydown keypress keyup", "#confirmacao_senha, #senha",function() {
		$("#img_confirm_senha").fadeOut();
	});
	
	$("#senha").trigger("focusout");
	$("#cpf").mask("999.999.999-99");
	
	$('#gera_senha').click(function(e){
		password = $.password(6,false);
		$('#senha').attr('type', 'text');
		
		$('#confirmacao_senha').attr('type', 'text');
		$('#senha').val(password);
		$('#confirmacao_senha').val(password);
		
		$("#confirma_senha").removeClass('form-group has-error has-feedback');
		$("#confirma_ico").removeClass('glyphicon glyphicon-remove form-control-feedback');
		$("#confirma_senha").addClass('form-group has-success has-feedback');
		$("#confirma_ico").addClass('glyphicon glyphicon-ok form-control-feedback');
		$("#confirma_ico").fadeIn();
		
		e.preventDefault();
	});
	
	$('#senha').click(function(e){
		$('#senha').attr('type', 'password');
		$('#confirmacao_senha').attr('type', 'password');
	});
	
	$('#confirmacao_senha').click(function(e){
		$('#senha').attr('type', 'password');
		$('#confirmacao_senha').attr('type', 'password');
	});
	
	$(".form1").on("change", "#input_nome", function(){
		var nome_usuario = $('#input_nome').val();
		var nome_array = nome_usuario.split(" ");
		
		var nome_login = "";
		
		if(empty(nome_array[1])){
			nome_login = nome_array[0];
		}else{
			nome_login = nome_array[0] + "." + nome_array[1];
		}
		var nome_login_minusculo = nome_login.toLowerCase();
		
		nome_velho = $('#nome_usuario').val();

		if(empty(nome_velho)){
			$('#nome_usuario').val(nome_login_minusculo);
		
			var nome_novo = $("#nome_usuario").val();
			$.post("<?php echo base_url('usuario/verificar_nome_existente')?>", { nome_novo:nome_novo }, function(data){
				if(data){
					if(!empty(nome_novo)){
						if(data == 0){
							$("#nome_de_usuario").removeClass('form-group has-error has-feedback');
							$("#usuario_ico").removeClass('glyphicon glyphicon-remove form-control-feedback');
							$("#nome_de_usuario").addClass('form-group has-success has-feedback');
							$("#usuario_ico").addClass('glyphicon glyphicon-ok form-control-feedback');
							$("#usuario_ico").fadeIn();
						}else{
							$("#nome_de_usuario").removeClass('form-group has-success has-feedback');
							$("#usuario_ico").removeClass('glyphicon glyphicon-ok form-control-feedback');
							$("#nome_de_usuario").addClass('form-group has-error has-feedback');
							$("#usuario_ico").addClass('glyphicon glyphicon-remove form-control-feedback');
							$("#usuario_ico").fadeIn();
						}
					}else{
						$("#nome_de_usuario").removeClass('form-group has-success has-feedback');
						$("#usuario_ico").removeClass('glyphicon glyphicon-ok form-control-feedback');
						$("#nome_de_usuario").removeClass('form-group has-error has-feedback');
						$("#usuario_ico").removeClass('glyphicon glyphicon-remove form-control-feedback');
					}
				}
			});
		}
	});
	
	$("#nome_de_usuario").on("change", "#nome_usuario", function(){
		var nome_novo = $("#nome_usuario").val();
		$.post("<?php echo base_url('usuario/verificar_nome_existente')?>", { nome_novo:nome_novo }, function(data){
			if(data){
				if(!empty(nome_novo)){
					if(data == 0){
						$("#nome_de_usuario").removeClass('form-group has-error has-feedback');
						$("#usuario_ico").removeClass('glyphicon glyphicon-remove form-control-feedback');
						$("#nome_de_usuario").addClass('form-group has-success has-feedback');
						$("#usuario_ico").addClass('glyphicon glyphicon-ok form-control-feedback');
						$("#usuario_ico").fadeIn();
					}else{
						$("#nome_de_usuario").removeClass('form-group has-success has-feedback');
						$("#usuario_ico").removeClass('glyphicon glyphicon-ok form-control-feedback');
						$("#nome_de_usuario").addClass('form-group has-error has-feedback');
						$("#usuario_ico").addClass('glyphicon glyphicon-remove form-control-feedback');
						$("#usuario_ico").fadeIn();
					}
				}else{
					$("#nome_de_usuario").removeClass('form-group has-success has-feedback');
					$("#usuario_ico").removeClass('glyphicon glyphicon-ok form-control-feedback');
					$("#nome_de_usuario").removeClass('form-group has-error has-feedback');
					$("#usuario_ico").removeClass('glyphicon glyphicon-remove form-control-feedback');
				}
			}
		});
	});

	$("#email").on("change", "#email_campo", function(){
		var email_novo = $("#email_campo").val();

		er = /^[a-zA-Z0-9][a-zA-Z0-9\._-]+@([a-zA-Z0-9\._-]+\.)[a-zA-Z-0-9]{2}/;

		$.post("<?php echo base_url("usuario/verificar_email_existente")?>", { email_novo:email_novo }, function(data){
			if(data){
				if(!empty(email_novo)){
					if(data == 0 && er.exec(email_novo)){
						$("#email").removeClass('form-group has-error has-feedback');
						$("#email_ico").removeClass('glyphicon glyphicon-remove form-control-feedback');
						$("#email").addClass('form-group has-success has-feedback');
						$("#email_ico").addClass('glyphicon glyphicon-ok form-control-feedback');
						$("#email_ico").fadeIn();
					}else{
						$("#email").removeClass('form-group has-success has-feedback');
						$("#email_ico").removeClass('glyphicon glyphicon-ok form-control-feedback');
						$("#email").addClass('form-group has-error has-feedback');
						$("#email_ico").addClass('glyphicon glyphicon-remove form-control-feedback');
						$("#email_ico").fadeIn();
					}
				}else{
					$("#email").removeClass('form-group has-success has-feedback');
					$("#email_ico").removeClass('glyphicon glyphicon-ok form-control-feedback');
					$("#email").removeClass('form-group has-error has-feedback');
					$("#email_ico").removeClass('glyphicon glyphicon-remove form-control-feedback');
				}
			}
		});

	});
});
</script>