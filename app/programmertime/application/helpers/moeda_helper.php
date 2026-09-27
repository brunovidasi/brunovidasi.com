<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

if (!function_exists('moeda')) {

	function moeda($moeda, $prefixo = "R$ "){
		return $prefixo . number_format(is_numeric($moeda) ? (float) $moeda : 0, 2, ',', '.');
	}

}

if (!function_exists('fmoeda')) {

	function fmoeda($valor = ""){
		
		if(empty($valor))
			return FALSE;

		$valor = str_replace('R$ ', '', $valor);
		$valor = str_replace('.', '', $valor);
		$valor = str_replace(',', '.', $valor);
		
		return $valor;
		
	}

}

?>