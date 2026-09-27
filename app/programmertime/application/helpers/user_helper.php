<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

if (!function_exists('short_name')) {

	function short_name($full_name, $surnames = 1){
		
		$full_name = explode(" ", $full_name);
		$name = $full_name[0];

		if($surnames > 0){
			for($i = 1; $i <= $surnames; $i++){
				if(isset($full_name[$i]))
					$name .= ' '.$full_name[$i];
			}
		}

		return $name;
		
	}

}


?>