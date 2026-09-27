<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

if (!function_exists('mask')) {

	function mask($val, $mask){
		$mascara = '';
		$k = 0;
		
		for($i = 0; $i<=strlen($mask)-1; $i++){
			if($mask[$i] == '#'){
				if(isset($val[$k]))
					$mascara .= $val[$k++];
			}else{
				if(isset($mask[$i]))
					$mascara .= $mask[$i];
			}
		}
		
		return $mascara;
	}

}

?>