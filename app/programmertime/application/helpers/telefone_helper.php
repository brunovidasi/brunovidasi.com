<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

if (!function_exists('numero')) {

	function numero($string){
		return preg_replace("/[^0-9]/", "", $string);
	}

}

if (!function_exists('telefone')) {

	function telefone($telefone){
		
		# (99) 9999-9999
		
		$telefone_traco = explode("-", $telefone);
		$telefone_ddd = explode(" ", $telefone_traco[0]);
		
		$numero = $telefone_ddd[1] . $telefone_traco[1];
		$ddd = substr($telefone_ddd[0], -3, -1);
		
		return $ddd . $numero;
		
	}

}

if (!function_exists('celular')) {

	function celular($telefone){
		
		# (99) 9 9999-9999
		
		$telefone_traco = explode("-", $telefone);
		$telefone_ddd = explode(" ", $telefone_traco[0]);
		
		$numero = $telefone_ddd[1] . $telefone_ddd[2] . $telefone_traco[1];
		$ddd = substr($telefone_ddd[0], -3, -1);
		
		return $ddd . $numero;
		
	}
	
}

?>