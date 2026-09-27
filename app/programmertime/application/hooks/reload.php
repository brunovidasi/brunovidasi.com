<?php

function reload(){
    $CI = & get_instance();
    if(strtolower(get_class($CI)) != 'auth'):
		if(strtolower(get_class($CI)) != 'json'):
			$user = $CI->user_model->get_user($CI->session->userdata('id'))->row();
			if($user->reload == 'yes'){

				$session_data = array(
					'name'			=> short_name($user->name, 0),
					'short_name'	=> short_name($user->name, 1),
					'full_name'		=> $user->name,
					'id'			=> $user->user_id,
					'login'			=> $user->login,
					'email'			=> $user->email,
					'image'			=> $user->image,
					'login_count'	=> $user->login_count,
					'access_level'	=> $user->access_level,
					'status'		=> $user->status,
					'color'			=> $user->color,
					'confirmed'		=> $user->confirmed,
					'employee_id'	=> $user->employee_id,
					'email_token'	=> $user->email_token
				);
				
				$CI->session->set_userdata($session_data);
				$CI->session->set_userdata("user", $user);
				$CI->user_model->reload('no');
			}
        endif;
    endif;
}

?>
