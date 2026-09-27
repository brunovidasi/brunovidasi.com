<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

if (!function_exists('cpf')) {
	function cpf($cpf){
		$cpf_array = explode(".", $cpf);
		$cpf_verificador = explode("-", $cpf_array[2]);	
		return $cpf_array[0] . $cpf_array[1] . $cpf_verificador[0] . $cpf_verificador[1];
	}
}

if (!function_exists('cnpj')) {
	function cnpj($cnpj){
		$cnpj_array = explode(".", $cnpj);
		$cnpj_barra = explode("/", $cnpj_array[2]);
		$cnpj_verificador = explode("-", $cnpj_barra[1]);
		return $cnpj_array[0] . $cnpj_array[1] . $cnpj_barra[0] . $cnpj_verificador[0] . $cnpj_verificador[1];
	}
}

?>