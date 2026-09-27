<?php
if(!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('save_log')) {

    function save_log($controller, $method, $record_id = 0, $name = ''){

        $CI = & get_instance();

        if(($controller != "auth") || ($method != "new_password")){
            
            if(empty($CI->session->userdata('user')->user_id)){
                $CI->session->sess_destroy();
                redirect("/");
            }

            $user_id = $CI->session->userdata('user')->user_id;
			
        }else{
            $user_id = $record_id;
        }
		
        if(!empty($user_id)){
			$entry              = new stdClass();
			$entry->date        = date("Y-m-d H:i:s");
			$entry->user_id     = $user_id;
			$entry->controller  = $controller;
			$entry->method      = $method;
			$entry->record_id   = $record_id;
			$entry->name        = $name;
			$entry->ip          = $CI->session->userdata('ip_address');
			$entry->user_agent  = $CI->session->userdata('user_agent');
			
            $saved = $CI->db->insert("log", $entry);

            if($saved)
                return TRUE;
            else
                return FALSE;
			
        }else
            return FALSE;
			
    }

}