<?php

function logged_in(){
    $CI = & get_instance();
    if (strtolower(get_class($CI)) != 'auth'):
		if (strtolower(get_class($CI)) != 'json'):
			if (!$CI->session->userdata('logged_in')):
				$CI->session->sess_destroy();
				$CI->session->set_flashdata('message', 'You need to be logged in to use the system.');
				redirect('/auth/');
			endif;
        endif;
    endif;
}

?>
