<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

if (!function_exists('fnome')) {

	function fnome($nome_completo, $sobrenomes = 1){
		
		$nome_completo = explode(" ", $nome_completo);
		$nome = $nome_completo[0];

		if($sobrenomes > 0){
			for($i = 1; $i <= $sobrenomes; $i++){
				if(isset($nome_completo[$i]))
					$nome .= ' '.$nome_completo[$i];
			}
		}

		return $nome;
		
	}

}


?>