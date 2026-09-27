<?php

function menu_header(){
	
    $CI = & get_instance();

    if (strtolower(get_class($CI)) != 'auth'):
		if (strtolower(get_class($CI)) != 'json'):
			if ($CI->session->userdata('logged_in')):
				
				$header = array(
					'dashboard' 	=> FALSE,
					'projects' 		=> FALSE,
					'clients' 		=> FALSE,
					'tasks' 		=> FALSE,
					'time_entries' 	=> FALSE,
					'finance' 		=> FALSE,
					'reports' 		=> FALSE,
					'users' 		=> FALSE,
					'settings'		=> FALSE,
					'edit_user'		=> FALSE,
					'message'		=> FALSE
				);

				if(strtolower(get_class($CI)) == 'dashboard') 		$header['dashboard'] 	= TRUE;
				if(strtolower(get_class($CI)) == 'image') 			$header['projects'] 	= TRUE;
				if(strtolower(get_class($CI)) == 'access_level') 	$header['users'] 		= TRUE;
				if(strtolower(get_class($CI)) == 'company') 		$header['settings'] 	= TRUE;
				if(strtolower(get_class($CI)) == 'project') 		$header['projects'] 	= TRUE;
				if(strtolower(get_class($CI)) == 'client') 			$header['clients'] 		= TRUE;
				if(strtolower(get_class($CI)) == 'task') 			$header['tasks'] 		= TRUE;
				if(strtolower(get_class($CI)) == 'time_entry') 		$header['time_entries'] = TRUE;
				if(strtolower(get_class($CI)) == 'finance') 		$header['finance'] 		= TRUE;
				if(strtolower(get_class($CI)) == 'report') 			$header['reports'] 		= TRUE;
				if(strtolower(get_class($CI)) == 'user') 			$header['users'] 		= TRUE;
				if(strtolower(get_class($CI)) == 'message') 		$header['message'] 		= TRUE;

				$CI->session->set_userdata($header);
				$CI->user_model->last_access($CI->session->userdata('id'));
				
			endif;
        endif;
    endif;

}

?>
