<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

if (!function_exists('gera_senha')) {

    function gera_senha($numero_caracteres = 8, $tipo = 'alfanumerica') {
		if($tipo == 'alfanumerica')
			$CaracteresAceitos = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		elseif($tipo == 'numerica')
			$CaracteresAceitos = '0123456789';
		elseif($tipo == 'alfabetica')
			$CaracteresAceitos = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		elseif($tipo == 'especial')
			$CaracteresAceitos = '!@#$%&*+-?';
		elseif($tipo == 'completa')
			$CaracteresAceitos = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*+-?';
        $max = strlen($CaracteresAceitos) - 1;
		
        $password = "";
		
        for ($i = 0; $i < $numero_caracteres; $i++) {
            $password .= $CaracteresAceitos[mt_rand(0, $max)];
        }
		
        return $password;
    }
}

if (!function_exists('gera_confirmacao')) {
	
	function gera_confirmacao($chave = 8) {
		
		$segundos_random = date('s') * gera_senha(2, 'numerica');
		$segundos_chave = date('s') * $chave;
		$chave_random = $chave * gera_senha(2, 'numerica');
		
		$data_chave = date('Ymd') * $chave;
		$tempo_chave = date('His') * $chave;
		
        $password  = gera_senha(1) . $chave . (date('y') + 5) . gera_senha(4) . $data_chave . (date('d')) . gera_senha(4) . date('m') . $tempo_chave . gera_senha(5) . date('wz') . $segundos_random . $chave_random . $segundos_chave . date('His') . gera_senha(2);
		
        return $password;
    }

}

if (!function_exists('gera_salt')) {
	
	function gera_salt($tamanho = 6, $maiusculas = true, $numeros = true, $simbolos = true) {
		
		$lmin = 'abcdefghijklmnopqrstuvwxyz';
		$lmai = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$num = '1234567890';
		$simb = '!@#$%*-';
		$salt = '';
		$caracteres = '';

		$caracteres .= $lmin;
		if ($maiusculas) $caracteres .= $lmai;
		if ($numeros) $caracteres .= $num;
		if ($simbolos) $caracteres .= $simb;

		$len = strlen($caracteres);
		for($n = 1; $n <= $tamanho; $n++){
			$rand = mt_rand(1, $len);
			$salt .= $caracteres[$rand-1];
		}

		return $salt;
    }

}