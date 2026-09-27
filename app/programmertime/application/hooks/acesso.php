<?php

function acesso(){
	
    $CI = & get_instance();
	$config = & get_config();
	
	if(!isset($CI->session))
		$CI->load->library('session');
	
	if($CI->session->userdata('logado')){
	
		$controle 	= $CI->router->class;
		$metodo		= $CI->router->method;
		
		$idusuario 			= $CI->session->userdata('id');

		$lanca_etapa 		= $CI->session->userdata('lanca_etapa');
		$lanca_pagamento 	= $CI->session->userdata('lanca_pagamento');
		$envia_relatorio 	= $CI->session->userdata('envia_relatorio');
		$cadastra_projeto 	= $CI->session->userdata('cadastra_projeto');
		$edita_projeto 		= $CI->session->userdata('edita_projeto');
		$cadastra_cliente 	= $CI->session->userdata('cadastra_cliente');
		$edita_cliente 		= $CI->session->userdata('edita_cliente');
		$cadastra_usuario	= $CI->session->userdata('cadastra_usuario');
		$edita_usuario 		= $CI->session->userdata('edita_usuario');
		
		
		if(($lanca_etapa == FALSE) && ($controle == 'etapa'))
			redirect('/dashboard/sem_acesso/');
		elseif(($lanca_pagamento == FALSE) && ($controle == 'financeiro'))
			redirect('/dashboard/sem_acesso/');
		elseif(($envia_relatorio == FALSE) && ($controle == 'relatorio'))
			redirect('/dashboard/sem_acesso/');
		elseif(($cadastra_projeto == FALSE) && (($controle == 'projeto') && ($metodo == 'cadastrar')))
			redirect('/dashboard/sem_acesso/');
		elseif(($edita_projeto == FALSE) && (($controle == 'projeto') && ($metodo == 'editar')))
			redirect('/dashboard/sem_acesso/');
		elseif(($edita_projeto == FALSE) && (($controle == 'projeto') && ($metodo == 'delete')))
			redirect('/dashboard/sem_acesso/');
		elseif(($cadastra_cliente == FALSE) && (($controle == 'cliente') && ($metodo == 'cadastrar')))
			redirect('/dashboard/sem_acesso/');
		elseif(($edita_cliente == FALSE) && (($controle == 'cliente') && ($metodo == 'editar'))) 
			redirect('/dashboard/sem_acesso/');
		elseif(($cadastra_usuario == FALSE) && (($controle == 'usuario') && ($metodo == 'cadastrar'))) 
			redirect('/dashboard/sem_acesso/');
		elseif(($edita_usuario == FALSE) && (($controle == 'usuario') && ($metodo == 'editar')))
			redirect('/dashboard/sem_acesso/');
		// elseif(($cadastra_usuario == FALSE) && ($controle == 'usuario')) redirect('/dashboard/sem_acesso/');
		
	}else{
		if (strtolower(get_class($CI)) != 'acesso'){
			if (strtolower(get_class($CI)) != 'json'){
				if (strtolower(get_class($CI)) != 'licenca'){
					$CI->session->sess_destroy();
					$CI->session->set_flashdata('mensagem', 'É preciso estar logado para utilizar o sistema.');
					redirect('/acesso/');
				}
			}
		}
	}
}

?>
