<?php

function locked(){
    $CI = & get_instance();
    if (strtolower(get_class($CI)) != 'auth'):
		if (strtolower(get_class($CI)) != 'json'):
			if ($CI->session->userdata('locked')):
				$CI->session->set_flashdata('message', 'You need to be logged in to use the system.');
				redirect('/auth/lock');
			endif;
        endif;
    endif;
}

?>
