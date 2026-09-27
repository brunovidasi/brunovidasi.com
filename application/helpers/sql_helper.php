<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

if (!function_exists('strsql')) {

	function strsql($string = ""){

		if(empty($string)) return "";

		$CI =& get_instance();
		$string = $CI->db->escape_str((string) $string);

		return $string;
	}

}

if (!function_exists('id_ou_null')) {

	// An optional reference (an unselected <select> posts '' or 0) must be
	// stored as NULL: '' and 0 match no row, so a foreign key rejects them.
	function id_ou_null($valor){
		$valor = (int) $valor;
		return $valor > 0 ? $valor : NULL;
	}

}

?>