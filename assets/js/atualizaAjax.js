function nivel(campo, caminho, tabela, idCampo, nomeCampo, idnivel, idusuario, nomenivel, nomeacao){
	
    var img_atual = $(campo).attr("src");
	
    if(img_atual == caminho+"/images/sistema/sim.png"){
        $(campo).attr("src", caminho+"/images/sistema/nao.png");
        $(campo).attr('alt', 'não');
        $(campo).attr('title', 'não');
        var pag = caminho.replace(/\/assets\/?$/, "")+"/ajax/alterar_nivel";
        var valorCampo = "nao";
		
		alertify.error(nomenivel+" não "+nomeacao+" mais.");
    }else{
        $(campo).attr("src", caminho+"/images/sistema/sim.png");
		$(campo).attr('alt', 'sim');
		$(campo).attr('title', 'sim');
        var pag = caminho.replace(/\/assets\/?$/, "")+"/ajax/alterar_nivel";
        var valorCampo = "sim";
		
		alertify.success(nomenivel+" agora "+nomeacao+".");
    }
	
    $.post(pag,{
        tabela: tabela, 
        nomeCampo: nomeCampo, 
        valorCampo: valorCampo, 
        idCampo: idCampo, 
        idnivel: idnivel,
        idusuario: idusuario
    });

};

function prioridade(campo, caminho, tabela, idCampo, nomeCampo, idprojeto){

    var img_atual = $(campo).attr("src");
	
    if($(campo).attr("src") == caminho+"/images/sistema/estrela_cinza.png"){
        $(campo).attr("src", caminho+"/images/sistema/estrela_preta.png");
		$(campo).attr('alt', 'Normal');
        $(campo).attr('title', 'Normal');
        var pag = caminho.replace(/\/assets\/?$/, "")+"/ajax/alterar_projeto";
        var valorCampo = "normal";
		alertify.success("Prioridade Normal.");
    }
	else if($(campo).attr("src") == caminho+"/images/sistema/estrela_preta.png"){
        $(campo).attr("src", caminho+"/images/sistema/estrela_vermelha.png");
		$(campo).attr('alt', 'Urgente');
        $(campo).attr('title', 'Urgente');
        var pag = caminho.replace(/\/assets\/?$/, "")+"/ajax/alterar_projeto";
        var valorCampo = "urgente";
		alertify.error("Prioridade Urgente.");
    } 
	else{ 
		$(campo).attr("src", caminho+"/images/sistema/estrela_cinza.png");
		$(campo).attr('alt', 'Baixa');
        $(campo).attr('title', 'Baixa');
        var pag = caminho.replace(/\/assets\/?$/, "")+"/ajax/alterar_projeto";
        var valorCampo = "baixa";
		alertify.success("Prioridade Baixa.");
    }
	
    $.post(pag,{
        tabela: tabela, 
        nomeCampo: nomeCampo, 
        valorCampo: valorCampo, 
        idCampo: idCampo, 
        idprojeto: idprojeto
    });

};

function status(campo, caminho, tabela, idCampo, nomeCampo, idprojeto){

    var img_atual = $(campo).attr("src");
	var pag = caminho.replace(/\/assets\/?$/, "")+"/ajax/alterar_projeto";
	
    if($(campo).attr("src") == caminho+"/images/sistema/bola_azul.png"){
        
		$(campo).attr("src", caminho+"/images/sistema/bola_verde.png");
		$(campo).attr('alt', 'Em Desenvolvimento');
        $(campo).attr('title', 'Em Desenvolvimento');
		
		if($(campo).attr("fora_prazo") == "sim"){
			$(campo).parent().parent().attr("class", 'danger');
			$(campo).parent().parent().find('#prazo').attr('class', 'label label-danger');
		}else{
			$(campo).parent().parent().attr("class", '');
			$(campo).parent().parent().find('#prazo').attr('class', 'label label-warning');
		}
		
		$(campo).parent().parent().find('#lancar_etapa').removeClass('disabled');
		$(campo).parent().parent().find('#lancar_pagamento').removeClass('disabled');
		
        var valorCampo = "desenvolvimento";
		alertify.success("Projeto em desenvolvimento.");
		
    }
	
	else if($(campo).attr("src") == caminho+"/images/sistema/bola_verde.png"){
       
	   $(campo).attr("src", caminho+"/images/sistema/pausado.png");
		$(campo).attr('alt', 'Pausado');
        $(campo).attr('title', 'Pausado');
		$(campo).parent().parent().attr("class", 'warning');
		
		if($(campo).attr("fora_prazo") == "sim"){
			$(campo).parent().parent().find('#prazo').attr('class', 'label label-danger');
		}else{
			$(campo).parent().parent().find('#prazo').attr('class', 'label label-warning');
		}
		
		$(campo).parent().parent().find('#lancar_etapa').removeClass('disabled');
		$(campo).parent().parent().find('#lancar_pagamento').removeClass('disabled');
		
        var valorCampo = "pausado";
		
		alertify.success("Projeto pausado.");
		
    }

    else if($(campo).attr("src") == caminho+"/images/sistema/pausado.png"){
       
	   $(campo).attr("src", caminho+"/images/sistema/cancelado.png");
		$(campo).attr('alt', 'Cancelado');
        $(campo).attr('title', 'Cancelado');
		$(campo).parent().parent().attr("class", 'danger');
		
		if($(campo).attr("fora_prazo") == "sim"){
			$(campo).parent().parent().find('#prazo').attr('class', 'label label-danger');
		}else{
			$(campo).parent().parent().find('#prazo').attr('class', 'label label-warning');
		}
		
		$(campo).parent().parent().find('#lancar_etapa').addClass('disabled');
		$(campo).parent().parent().find('#lancar_pagamento').addClass('disabled');
		
        var valorCampo = "cancelado";
		
		alertify.success("Projeto cancelado.");
		
    }
	
	else if($(campo).attr("src") == caminho+"/images/sistema/cancelado.png"){
        $(campo).attr("src", caminho+"/images/sistema/concluido.png");
		$(campo).attr('alt', 'Concluído');
        $(campo).attr('title', 'Concluído');
		
		if($(campo).attr("fora_prazo") == "sim"){
			$(campo).parent().parent().removeClass('danger');
			$(campo).parent().parent().removeClass('warning');
			$(campo).parent().parent().attr("class", '');
			$(campo).parent().parent().find('#prazo').attr('class', 'label label-primary');
		}else{
			$(campo).parent().parent().attr("class", '');
			$(campo).parent().parent().find('#prazo').attr('class', 'label label-warning');
		}
		
		$(campo).parent().parent().find('#lancar_etapa').addClass('disabled');
		$(campo).parent().parent().find('#lancar_pagamento').addClass('disabled');
		
        var valorCampo = "concluido";
		
		alertify.success("Projeto Concluído.");
    } 
	
	else{ 
		$(campo).attr("src", caminho+"/images/sistema/bola_azul.png");
		$(campo).attr('alt', 'Não Começado');
        $(campo).attr('title', 'Não Começado');
        
		if($(campo).attr("fora_prazo") == "sim"){
			$(campo).parent().parent().attr("class", 'danger');
			$(campo).parent().parent().find('#prazo').attr('class', 'label label-danger');
		}else{
			$(campo).parent().parent().attr("class", '');
			$(campo).parent().parent().find('#prazo').attr('class', 'label label-warning');
		}
		
		$(campo).parent().parent().find('#lancar_etapa').removeClass('disabled');
		$(campo).parent().parent().find('#lancar_pagamento').removeClass('disabled');
		
        var valorCampo = "nao_comecado";
		
		alertify.success("Projeto não começado.");
    }
	
    $.post(pag,{
        tabela: tabela, 
        nomeCampo: nomeCampo, 
        valorCampo: valorCampo, 
        idCampo: idCampo, 
        idprojeto: idprojeto
    });

};

function tstatus(campo, caminho, tabela, idCampo, nomeCampo, idprojeto){

    var img_atual = $(campo).attr("src");
	var pag = caminho.replace(/\/assets\/?$/, "")+"/ajax/alterar_projeto";
	
    if($(campo).attr("src") == caminho+"/images/sistema/bola_azul.png"){
        
		$(campo).attr("src", caminho+"/images/sistema/bola_verde.png");
		$(campo).attr('alt', 'Em Desenvolvimento');
        $(campo).attr('title', 'Em Desenvolvimento');
		
		if($(campo).attr("fora_prazo") == "sim"){
			$(campo).parent().parent().attr("class", 'danger');
			$(campo).parent().parent().find('#prazo').attr('class', 'label label-danger');
		}else{
			$(campo).parent().parent().attr("class", '');
			$(campo).parent().parent().find('#prazo').attr('class', 'label label-warning');
		}
		
		$(campo).parent().parent().find('#lancar_etapa').removeClass('disabled');
		$(campo).parent().parent().find('#lancar_pagamento').removeClass('disabled');
		
        var valorCampo = "desenvolvimento";
		alertify.success("Tarefa em desenvolvimento.");
		
    }
	
	else if($(campo).attr("src") == caminho+"/images/sistema/bola_verde.png"){
       
	   $(campo).attr("src", caminho+"/images/sistema/cancelado.png");
		$(campo).attr('alt', 'Cancelado');
        $(campo).attr('title', 'Cancelado');
		$(campo).parent().parent().attr("class", 'danger');
		
		if($(campo).attr("fora_prazo") == "sim"){
			$(campo).parent().parent().find('#prazo').attr('class', 'label label-danger');
		}else{
			$(campo).parent().parent().find('#prazo').attr('class', 'label label-warning');
		}
		
		$(campo).parent().parent().find('#lancar_etapa').addClass('disabled');
		$(campo).parent().parent().find('#lancar_pagamento').addClass('disabled');
		
        var valorCampo = "cancelado";
		
		alertify.success("Tarefa cancelada.");
		
    }
	
	else if($(campo).attr("src") == caminho+"/images/sistema/cancelado.png"){
        $(campo).attr("src", caminho+"/images/sistema/concluido.png");
		$(campo).attr('alt', 'Concluído');
        $(campo).attr('title', 'Concluído');
		
		if($(campo).attr("fora_prazo") == "sim"){
			$(campo).parent().parent().removeClass('danger');
			$(campo).parent().parent().removeClass('warning');
			$(campo).parent().parent().attr("class", '');
			$(campo).parent().parent().find('#prazo').attr('class', 'label label-primary');
		}else{
			$(campo).parent().parent().attr("class", '');
			$(campo).parent().parent().find('#prazo').attr('class', 'label label-warning');
		}
		
		$(campo).parent().parent().find('#lancar_etapa').addClass('disabled');
		$(campo).parent().parent().find('#lancar_pagamento').addClass('disabled');
		
        var valorCampo = "concluido";
		
		alertify.success("Tarefa Concluída.");
    } 
	
	else{ 
		$(campo).attr("src", caminho+"/images/sistema/bola_azul.png");
		$(campo).attr('alt', 'Não Começado');
        $(campo).attr('title', 'Não Começado');
        
		if($(campo).attr("fora_prazo") == "sim"){
			$(campo).parent().parent().attr("class", 'danger');
			$(campo).parent().parent().find('#prazo').attr('class', 'label label-danger');
		}else{
			$(campo).parent().parent().attr("class", '');
			$(campo).parent().parent().find('#prazo').attr('class', 'label label-warning');
		}
		
		$(campo).parent().parent().find('#lancar_etapa').removeClass('disabled');
		$(campo).parent().parent().find('#lancar_pagamento').removeClass('disabled');
		
        var valorCampo = "nao_comecado";
		
		alertify.success("Tarefa não começada.");
    }
	
    $.post(pag,{
        tabela: tabela, 
        nomeCampo: nomeCampo, 
        valorCampo: valorCampo, 
        idCampo: idCampo, 
        idprojeto: idprojeto
    });

};