<script src="<?php echo base_url('assets/js/colorpicker/bootstrap-colorpicker.js'); ?>" type="text/javascript"></script>
<link href="<?php echo base_url('assets/js/colorpicker/bootstrap-colorpicker.css'); ?>" rel="stylesheet" type="text/css" />

<p class="titulo_pagina" style="float:left">Editar Perfil de Usuário</p> <br><br><br>

<?php
	require('application/views/includes/mensagem.php');
	if(validation_errors() != '')
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
?>

<form action="<?php echo base_url('usuario/update/'.$perfil->idusuario) ?>" method="post" name="form1" class="form1">

    <table width="100%" border="0" cellpadding="3" cellspacing="3" class="tabela_principal" style="padding:10px;">
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Nome Completo:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="nome" type="text" class="form-control" id="" value="<?php echo set_value("nome", $perfil->nome); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Nivel de Acesso:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-3 col-md-4 inputs">
					<select name="nivel_acesso" class="form-control" id="nivel_acesso">
						<option value=""></option>
						<?php
						foreach($cargos->result() as $cargo){
							$selected = "";
							if(set_value('nivel_acesso', $perfil->nivel_acesso) == $cargo->id){
								$selected = 'selected="selected"';
							}
							
							if($perfil->nivel_acesso == '1'){
								echo '<option value="1" '. $selected .'>Administrador</option>';
								break;
							}
							
							elseif($cargo->id != '1'){
								echo '<option value="'. $cargo->id .'" '. $selected .'>'. $cargo->cargo .'</option>';
							}
						}				
						?>
					</select>
				</div>
				<span class="btn btn-primary"><i class='glyphicon glyphicon-plus'></i></span>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Imagem:</strong> <span class="obrigatorio">*</span>
				</div>
			</td> 
			
			<td>
				<?php 
					$config_imag = $crop->origem . "/" . $crop->destino . "/" . $crop->altura . "/" . $crop->largura; 
					$this->session->set_userdata('config_imag', $config_imag); 
				?>
				
				<input type="hidden" id="crop" name="crop" value='<?php echo serialize($crop); ?>' />
				<input type="hidden" name="imagem_clicada" id="imagem_clicada" value="" />
				
				<div class="modal fade" id="uploadimagem_1" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
					<div class="modal-dialog">
						<div class="modal-content">
							<span type="button" class="close" data-dismiss="modal" aria-hidden="true"></span>
							<div class="modal-body">
								<iframe id="frame_modal" src="<?php echo base_url("upload/upload_imagem/{$config_imag}"); ?>" width="100%"  scrolling="auto" border="0" height="530px" style="border:0"></iframe>
							</div>
							
						</div>
					</div>
				</div>

				
				<div id="conteudos_imagens">
					<div id="conteudo_imagem_1">
						<span class="addImg">
							<span class="uploadimagem">
								<div style="margin-left: 17px; width:200px; cursor:pointer;">
									<span id="form-field-1" class="addImg thumbnail" href="#uploadimagem_1" data-toggle="modal" data-target="#uploadimagem_1"><img src="<?php echo base_url('assets/images/usuarios/'. $perfil->imagem); ?>"  id="upload_imagem_1" onclick="$('#imagem_clicada').val('1')" width="200px"/></span>
									<img id="remover_imagem_1" class="button_remove" src="<?php echo base_url('assets/images/sistema/nao.png'); ?>" alt="Remover" style="float: right; width:20px; margin-top: -218px; margin-right: -25px; cursor:pointer;">
								</div>
							</span>
						</span>
						
						<input type="hidden" class="nomeimagem" name="nomeimagem[]" value="" />
						<input type="hidden" class="caminhoimagem" name="caminhoimagem[]" value="" />
						
					</div>
				</div>
				
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Nome de Usuário:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-2 col-md-6 inputs" id="nome_de_usuario">
					<input name="login" type="text" class="form-control" id="nome_usuario" value="<?php echo set_value("login", $perfil->login); ?>" />
					<span class="" id="usuario_ico" style="top:0px; width: 60px;"></span>
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
					<input name="email" type="text" class="form-control" id="" value="<?php echo set_value("email", $perfil->email); ?>" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="top">
				<div class="inputs">
					<strong>Senha:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-2 col-md-6 inputs">
					<input name="senha" type="password" class="form-control" id="senha" value="<?php echo set_value("senha"); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="top">
				<div class="inputs">
					<strong>Confirmação Senha:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-2 col-md-6 inputs" id="confirma_senha">
					<input name="confirmacao_senha" type="password" class="form-control" id="confirmacao_senha" value="<?php echo set_value("confirmacao_senha"); ?>" />
					<span class="" id="confirma_ico" style="top:0px; width: 60px;"></span>
					<span class="help-block">Somente se preferir mudar a senha.</span>
				</div>
				<img style="display: none; margin-left: -14px; margin-bottom: 6px; width:25px;" id="img_confirm_senha" src="">
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Matrícula:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-2 col-md-6 inputs">
					<input name="matricula" type="text" class="form-control" id="" value="<?php echo set_value("matricula", $perfil->matricula); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>RG:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-2 col-md-5 inputs">
					<input name="rg" type="text" class="form-control" id="" value="<?php echo set_value("rg", $perfil->rg); ?>" />
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
				<div class="col-lg-2 col-md-5 inputs">
					<input name="cpf" type="text" class="form-control" id="cpf" value="<?php echo set_value("cpf", $perfil->cpf); ?>" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Data Nasc.:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-2 col-md-5 inputs">
					<input name="data_nascimento" type="text" class="form-control" id="data_nascimento" value="<?php echo set_value("data_nascimento", fdata($perfil->data_nascimento, "/")); ?>" />
				</div>
			</td>

		</tr>

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Cor:</strong>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-2 col-md-5 inputs">
					<div class="input-group colorpicker">
						<input name="cor" type="text" class="form-control" id="cor" value="<?php echo set_value("cor", $perfil->cor); ?>" />
						<div class="input-group-addon">
	                        <i></i>
	                    </div>
					</div>
				</div>
			</td>

		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="top">
				<div class="inputs">
					<strong>Desativar Usuário:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<!--<div class="col-lg-10 col-md-10 inputs" id="usuario_inativo">
					<input type="radio" name="status" value="inativo" <?php echo set_radio('status', 'inativo', (($perfil->status=='inativo')?TRUE:FALSE)); ?> /> Sim
					<input type="radio" name="status" value="ativo" <?php echo set_radio('status', 'ativo', (($perfil->status=='ativo')?TRUE:FALSE)); ?>  /> Não
					<span class="help-block" id="desativar"><strong>CUIDADO</strong>: Ao desativar o usuário, ele não terá mais acesso ao sistema.</span>
				</div>-->
				
				
				
				<div class="col-lg-10 col-md-10 inputs" id="usuario_inativo">
				
					<div class="alert alert-danger fade in">
						<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
						<h4>Atenção!</h4>
						<?php if($perfil->status=='ativo'){ ?>
							<p>Se você escolher desativar este usuário (<?php echo $perfil->nome; ?>), ele não terá mais acesso ao sistema ProgrammerTime, podendo ser reativado futuramente pelo Administrador ou Gerente.</p>
						<?php }else{ ?>
							<p>O usuário <?php echo $perfil->nome; ?> está INATIVO no sistema. Se você optar por reativá-lo, ele terá acesso ao sistema ProgrammerTime novamente.</p>
						<?php } ?>
						
					</div>
					
					<?php if($perfil->status=='ativo'){ ?>
						<input type="checkbox" name="status" value="inativo" <?php echo set_checkbox('status', 'inativo'); ?> /> Desativar
					<?php }else{ ?>
						<input type="checkbox" name="status" value="ativo" <?php echo set_checkbox('status', 'ativo'); ?> /> Reativar
					<?php } ?>
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td valign="middle"></td>
			
			<td style="padding-left:14px">
				<button name="submit" type="submit" class="btn btn-primary" id="submit" />Atualizar Cadastro</button>
			</td>
		</tr>
		

	</table>
</form>
<br>

<script src="<?php echo base_url('assets/js/jquery.maskedinput.js');?>"></script>
<link rel="stylesheet" href="<?php echo base_url('assets/css/datepicker.css');?>" type="text/css">
<script src="<?php echo base_url('assets/js/bootstrap-datepicker.js');?>" type="text/javascript"></script>

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
	
	$("#data_nascimento").mask("99/99/9999");
	//$("#data_nascimento").datepicker();

    $(".colorpicker").colorpicker();
	
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
	
	$(document).on('click', ".button_remove", function(){
		reset();
		alertify.confirm("Você tem certeza que deseja deletar esta imagem?", function (e) {
			if (e) {
				$(document).find(".nomeimagem").val("none.png");
				$(document).find("#upload_imagem_1").attr('src', "<?php echo base_url('assets/images/usuarios/none.png'); ?>");
				console.log('passei aqui');
			}
		});
		return false;
	});
	
	$("#nome_de_usuario").on("change", "#nome_usuario", function(){
		nome_atual = '<?php echo $perfil->login; ?>';
		$.post("<?php echo base_url("usuario/verificar_nome_existente")?>", { nome_novo:$("#nome_usuario").val(), nome_atual:nome_atual }, function(data){
			if(data){
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
			}
		});
	});
	
});

function insereimagem(caminho, nome_img, nome_original){
	var conteudo_img = "<img src='"+caminho+"'>";
	var imagem_clicada = $("#imagem_clicada").val();
	var div_conteudo = $("#conteudo_imagem_"+imagem_clicada);
	div_conteudo.find(".nomeimagem").val(nome_img);
	div_conteudo.find(".caminhoimagem").val(caminho);
	div_conteudo.find(".uploadimagem").html('<div style="margin-left: 17px; width:200px; cursor:pointer;"><span id="form-field-1" class="addImg thumbnail" href="#uploadimagem_'+ imagem_clicada +'" data-toggle="modal" data-target="#uploadimagem_'+ imagem_clicada +'"><img src="'+ caminho  +'"  id="upload_imagem_'+ imagem_clicada +'" onclick="$(\'#imagem_clicada\').val(\''+ imagem_clicada +'\')" width="200px"/></span><img id="remover_imagem_'+ imagem_clicada +'" class="button_remove" src="<?php echo base_url('assets/images/sistema/nao.png'); ?>" alt="Remover" style="float: right; width:20px; margin-top: -218px; margin-right: -25px; cursor:pointer;"></div>');
}
</script>