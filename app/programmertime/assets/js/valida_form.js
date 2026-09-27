function avalia_form(){
	prodsMarcados = new Array();
	var mensagens = "";
	var invalido = "";
	var tipo = "";
	//varre a pagina e retorna todo o conteudo que contenha [valida=true]
	$("[valida=true]").each(
		function(){
			//verifica o valor do conteudo encontrado, caso não tenha nada preenchido ele mensagem recebe o erro
			if($(this).val().length < 1){
				mensagens += "<li>O campo <b>" + $(this).attr("text_valida") + "</b> é obrigatório</li>";
			}
			//verifica se há algum tipo de validação, caso haja ele quebra os tipos e verifica cada um
			if($(this).attr("tipo_valida") !== undefined){
				tipo = $(this).attr("tipo_valida").split("|");
				//quebra com o for o array
				for (var i in tipo){
					if(tipo[i] == "email"){
						//validando email
						if($(this).val() != ""){
							var filter = /^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i;
							if(!filter.test($(this).val())){
							  mensagens += "<li>O campo <b>E-mail</b> é inválido</li>";
							}
						}
					}if(tipo[i] == "login"){
						//verifica ser o input de verificação está como já utilizado
						//alert($(this).val());
						if($(this).val() == "ja utilizado"){
							//$("#senha").focus();
							mensagens += "<li><b>Login</b> já utilizado</li>";
						}
						/*
						else if($(this).val() == "vazio"){
							//mensagens += "<li>Ocorreu um erro, por favor tente novamente.</li>";
							$("#senha").focus();
							invalido = "invalido";
							$('.button_form').trigger('click');
						}else if(($(this).val() == "login atual") || ($(this).val() == "ok")){
							$("#senha").focus();
							invalido = "";
							alert("teste");
						}
						*/
					}
					if(tipo[i] == "arquivo"){
						if($(this).val() != ""){
							var valor = $(this).val().split(".");
							var ultimo = valor.length;
							var extensao = valor[ultimo-1];
							
							var tipos_aceitos = $(this).attr("tipo_aceito");
							var tipo_aceito = tipos_aceitos.split(", ");
							
							var formato_certo = 0;
							for (var i2 in tipo_aceito){
								if(tipo_aceito[i2] == extensao){
									formato_certo = 1;
								}
							}

							if(formato_certo == 0){
								var plural = "";
								if(tipo_aceito.length > 1){
									plural = "s";
								}
								mensagens += "<li>O campo <b>Arquivo</b> deve ter o"+plural+" formato"+plural+" <b>"+tipos_aceitos+"</b></li>";
							}
						}
					}
					//continuação para proximas funções
					/*
					if(tipo[i] == "cpf"){
						mensagens += "<li>Cpf invalido</li>";
					}
					*/
				}
			}
		}
	);
	//verifica se há alguma mensagem de erro, caso haja ele retorna a mensagem e trava o submit, caso não ele envia o formulario
	if(mensagens != ""){
		$("#erros_validacao_form").css("display", "block");
		$("#erros_valid").html(mensagens);
		$("#msg_controller").css("display", "none");
		return false;
	}else if(invalido != ""){
		return false;
	}else{
		$("#carregando").css("display", "block");
		return true;
	}
}