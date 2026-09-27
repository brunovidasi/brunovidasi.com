<?php
if(!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('salvar_log')) {

    function salvar_log($controlador, $metodo, $idregistro = 0, $nome = ''){

        $CI = & get_instance();

        if(($controlador != "acesso") || ($metodo != "nova_senha")){
            
            if(empty($CI->session->userdata('usuario')->idusuario)){
                $CI->session->sess_destroy();
                redirect("/");
            }

            $idusuario = $CI->session->userdata('usuario')->idusuario;
			
        }else{
            $idusuario = $idregistro;
        }
		
        if(!empty($idusuario)){
			$objeto              = new stdClass();
			$objeto->data        = date("Y-m-d H:i:s");
			$objeto->idusuario   = $idusuario;
			$objeto->controle    = $controlador;
			$objeto->metodo      = $metodo;
			$objeto->idregistro  = $idregistro;
			$objeto->nome        = $nome;
			$objeto->ip          = $CI->session->userdata('ip_address');
			$objeto->user_agent  = $CI->session->userdata('user_agent');
			
            $log_salvo = $CI->db->insert("log", $objeto);

            if($log_salvo)
                return TRUE;
            else
                return FALSE;
			
        }else
            return FALSE;
			
    }

}