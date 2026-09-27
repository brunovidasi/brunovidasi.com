<?php

function recarregar(){
    $CI = & get_instance();
    if(strtolower(get_class($CI)) != 'acesso'):
		if(strtolower(get_class($CI)) != 'json'):
			if(strtolower(get_class($CI)) != 'licenca'):
				$usuario = $CI->usuario_model->get_usuario($CI->session->userdata('id'))->row();
				if($usuario->recarregar == 'sim'){

					$usuario_data = array(
						'nome'			=> fnome($usuario->nome, 0),
						'nome_duplo'	=> fnome($usuario->nome, 1),
						'nome_completo'	=> $usuario->nome,
						'id'			=> $usuario->idusuario,
						'login'			=> $usuario->login,
						'email'			=> $usuario->email,
						'imagem'		=> $usuario->imagem,
						'numero_acesso'	=> $usuario->numero_acesso,
						'nivel_acesso'	=> $usuario->nivel_acesso,
						'status'		=> $usuario->status,
						'cor'			=> $usuario->cor,
						'confirmado'	=> $usuario->usuario_confirmado,
						'matricula'		=> $usuario->matricula,
						'email_senha'	=> $usuario->email_senha
					);
					
					$CI->session->set_userdata($usuario_data);
					$CI->session->set_userdata("usuario", $usuario);
					$CI->usuario_model->recarregar('nao');
				}
        	endif;
        endif;
    endif;
}

?>
