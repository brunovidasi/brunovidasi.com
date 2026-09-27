<?php

function bloqueado(){
    $CI = & get_instance();
    if (strtolower(get_class($CI)) != 'acesso'):
		if (strtolower(get_class($CI)) != 'json'):
			if (strtolower(get_class($CI)) != 'licenca'):
				if ($CI->session->userdata('bloqueado')):			
					$CI->session->set_flashdata('mensagem', 'É preciso estar logado para utilizar o sistema.');
					redirect('/acesso/bloquear');
				endif;
			endif;
        endif;
    endif;
}

?>
