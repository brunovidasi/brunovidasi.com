<?php

function logado(){
    $CI = & get_instance();
    if (strtolower(get_class($CI)) != 'acesso'):
		if (strtolower(get_class($CI)) != 'json'):
			if (strtolower(get_class($CI)) != 'licenca'):
				if (!$CI->session->userdata('logado')):
					$CI->session->sess_destroy();
					$CI->session->set_flashdata('mensagem', 'É preciso estar logado para utilizar o sistema.');
					redirect('/acesso/');
				endif;
			endif;
        endif;
    endif;
}

?>
