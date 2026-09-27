<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

if (!function_exists('hash_password')) {
    function hash_password($password, $salt = "", $hash_type = "md5") {

        $CI = & get_instance();
        $encryption_key = $CI->config->config['encryption_key'];

        //$password = $salt . $password . $salt . $salt . $password;

		switch ($hash_type) {
            case "md5":  $password = md5($encryption_key . $password); break;
            case "sha1": $password = sha1($encryption_key . $password); break;
            case "base64_encode": $password = base64_encode($encryption_key . $password); break;
            case "base64_decode": $password = base64_decode($encryption_key . $password); break;
            default : $password = md5($encryption_key . $password); break;
        }

        return $password;
    }
}

?>
