<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

if (!function_exists('currency')) {

	function currency($amount, $prefix = "$ "){
		return $prefix . number_format(is_numeric($amount) ? (float) $amount : 0, 2, '.', ',');
	}

}

if (!function_exists('parse_currency')) {

	// Turns a formatted amount ("$ 1,234.56") back into a plain decimal string.
	function parse_currency($amount = ""){
		
		if(empty($amount))
			return FALSE;

		$amount = str_replace('$', '', $amount);
		$amount = str_replace(',', '', $amount);
		$amount = trim($amount);
		
		return $amount;
		
	}

}

?>