<?php

function menu_header(){
	
    $CI = & get_instance();

    if (strtolower(get_class($CI)) != 'acesso'):
		if (strtolower(get_class($CI)) != 'java'):
			if (strtolower(get_class($CI)) != 'licenca'):
				if ($CI->session->userdata('logado')):
					
					$header = array(
						'dashboard' 	=> FALSE,
						'projetos' 		=> FALSE,
						'clientes' 		=> FALSE,
						'tarefas' 		=> FALSE,
						'etapas' 		=> FALSE,
						'financeiro' 	=> FALSE,
						'relatorios' 	=> FALSE,
						'usuarios' 		=> FALSE,
						'configuracao'	=> FALSE,
						'editar_usuario'=> FALSE,
						'mensagem'		=> FALSE
					);

					if(strtolower(get_class($CI)) == 'dashboard') 		$header['dashboard'] 	= TRUE;
					if(strtolower(get_class($CI)) == 'imagem') 			$header['projetos'] 	= TRUE;
					if(strtolower(get_class($CI)) == 'nivel_acesso') 	$header['usuarios'] 	= TRUE;
					if(strtolower(get_class($CI)) == 'empresa') 		$header['configuracao'] = TRUE;
					if(strtolower(get_class($CI)) == 'projeto') 		$header['projetos'] 	= TRUE;
					if(strtolower(get_class($CI)) == 'cliente') 		$header['clientes'] 	= TRUE;
					if(strtolower(get_class($CI)) == 'tarefa') 			$header['tarefas'] 		= TRUE;
					if(strtolower(get_class($CI)) == 'etapa') 			$header['etapas'] 		= TRUE;
					if(strtolower(get_class($CI)) == 'financeiro') 		$header['financeiro'] 	= TRUE;
					if(strtolower(get_class($CI)) == 'relatorio') 		$header['relatorios'] 	= TRUE;
					if(strtolower(get_class($CI)) == 'usuario') 		$header['usuarios'] 	= TRUE;
					if(strtolower(get_class($CI)) == 'configuracao') 	$header['configuracao'] = TRUE;
					if(strtolower(get_class($CI)) == 'mensagem') 		$header['mensagem'] 	= TRUE;

					$CI->session->set_userdata($header);
					$CI->usuario_model->ultimo_acesso($CI->session->userdata('id'));
					
				endif;
			endif;
        endif;
    endif;

}

?>
