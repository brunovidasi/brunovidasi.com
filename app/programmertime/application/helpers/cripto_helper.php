<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

if (!function_exists('cripto')) {
    function cripto($senha, $salt = "", $tipo_cripto = "md5") {

        $CI = & get_instance();
        $encryption_key = $CI->config->config['encryption_key'];

        //$senha = $salt . $senha . $salt . $salt . $senha;
        
		switch ($tipo_cripto) {
            case "md5":  $senha = md5($encryption_key . $senha); break;
            case "sha1": $senha = sha1($encryption_key . $senha); break;
            case "base64_encode": $senha = base64_encode($encryption_key . $senha); break;
            case "base64_decode": $senha = base64_decode($encryption_key . $senha); break;
            default : $senha = md5($encryption_key . $senha); break;
        }
        
        return $senha;
    }
}

?>