<?php

function auth(){
	
    $CI = & get_instance();
	$config = & get_config();
	
	if(!isset($CI->session))
		$CI->load->library('session');
	
	if($CI->session->userdata('logged_in')){
	
		$control 	= $CI->router->class;
		$method		= $CI->router->method;
		
		$user_id 			= $CI->session->userdata('id');

		$can_log_time 		= $CI->session->userdata('can_log_time');
		$can_log_payment 	= $CI->session->userdata('can_log_payment');
		$can_send_report 	= $CI->session->userdata('can_send_report');
		$can_create_project 	= $CI->session->userdata('can_create_project');
		$can_edit_project 		= $CI->session->userdata('can_edit_project');
		$can_create_client 	= $CI->session->userdata('can_create_client');
		$can_edit_client 		= $CI->session->userdata('can_edit_client');
		$can_create_user	= $CI->session->userdata('can_create_user');
		$can_edit_user 		= $CI->session->userdata('can_edit_user');
		
		
		if(($can_log_time == FALSE) && ($control == 'time_entry'))
			redirect('/dashboard/no_access/');
		elseif(($can_log_payment == FALSE) && ($control == 'finance'))
			redirect('/dashboard/no_access/');
		elseif(($can_send_report == FALSE) && ($control == 'report'))
			redirect('/dashboard/no_access/');
		elseif(($can_create_project == FALSE) && (($control == 'project') && ($method == 'create')))
			redirect('/dashboard/no_access/');
		elseif(($can_edit_project == FALSE) && (($control == 'project') && ($method == 'edit')))
			redirect('/dashboard/no_access/');
		elseif(($can_edit_project == FALSE) && (($control == 'project') && ($method == 'delete')))
			redirect('/dashboard/no_access/');
		elseif(($can_create_client == FALSE) && (($control == 'client') && ($method == 'create')))
			redirect('/dashboard/no_access/');
		elseif(($can_edit_client == FALSE) && (($control == 'client') && ($method == 'edit'))) 
			redirect('/dashboard/no_access/');
		elseif(($can_create_user == FALSE) && (($control == 'user') && ($method == 'create'))) 
			redirect('/dashboard/no_access/');
		elseif(($can_edit_user == FALSE) && (($control == 'user') && ($method == 'edit')))
			redirect('/dashboard/no_access/');
		// elseif(($can_create_user == FALSE) && ($control == 'user')) redirect('/dashboard/no_access/');
		
	}else{
		if (strtolower(get_class($CI)) != 'auth'){
			if (strtolower(get_class($CI)) != 'json'){
				$CI->session->sess_destroy();
				$CI->session->set_flashdata('message', 'You need to be logged in to use the system.');
				redirect('/auth/');
			}
		}
	}
}

?>
