<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

if (!function_exists('fdata')) {

    function fdata($data, $tipo_transforma = "") {

		if(!empty($data)){
			if ($tipo_transforma == "-") {
				$parte = explode("/", $data);
				$data = $parte[2] . "-" . $parte[1] . "-" . $parte[0];
			}elseif($tipo_transforma == "/") {
				$parte = explode("-", $data);
				$parte[2] = substr($parte[2], 0, 2);
				$data = $parte[2] . "/" . $parte[1] . "/" . $parte[0];
			}
		}
        return $data;
    }

}

if (!function_exists('fhora')) {

    function fhora($hora) {

		if(!empty($hora)){
			$parte = explode(":", $hora);
			$hora = $parte[0] . ":" . $parte[1];
		}
        return $hora;
    }

}

if (!function_exists('fdatahora')) {

    function fdatahora($data, $tipo_transforma = "") {
		
		if(!empty($data)){
			
			// A DATETIME column can hold a bare date on SQLite (MySQL would have
			// padded it with 00:00:00), so the time part is optional.
			$data_completa = explode(" ", $data);
			$data = $data_completa[0];
			$hora = isset($data_completa[1]) ? $data_completa[1] : '00:00:00';
				
			if ($tipo_transforma == "-") {
				$parte = explode("/", $data);
				$data = $parte[2] . "-" . $parte[1] . "-" . $parte[0] . " " . $hora;
			} 
			
			elseif ($tipo_transforma == "/") {
				$parte = explode("-", $data);
				$data_barra = $parte[2] . "/" . $parte[1] . "/" . $parte[0];
				
				$data = array(
					"data" => $data_barra,
					"hora" => $hora
				);
			}
			
		}

        return $data;
    }

}

if (!function_exists('fdatetime')) {

    function fdatetime($data, $tipo_transforma = "") {
		
		if(!empty($data)){
			
			// A DATETIME column can hold a bare date on SQLite (MySQL would have
			// padded it with 00:00:00), so the time part is optional.
			$data_completa = explode(" ", $data);
			$data = $data_completa[0];
			$hora = isset($data_completa[1]) ? $data_completa[1] : '00:00:00';
				
			if ($tipo_transforma == "-") {
				$parte = explode("/", $data);
				$data = $parte[2] . "-" . $parte[1] . "-" . $parte[0] . " " . $hora;
			} 
			
			elseif ($tipo_transforma == "/") {
				$parte = explode("-", $data);
				$data_barra = $parte[2] . "/" . $parte[1] . "/" . $parte[0];
				$hora = explode(':', $hora);
				
				$data = $data_barra . " " . $hora[0] . ':' . $hora[1];
			}
			
		}

        return $data;
    }

}

if (!function_exists('calcula_dias')) {

    function calcula_dias($inicio, $final="") {
		
		$dias = "";
		
		if(!empty($inicio)){
			
			if((empty($final)) OR ($final == '0000-00-00')){
				$final = date('Y-m-d');
			}
			
			$diferenca = strtotime($final) - strtotime($inicio);
			
			$dias = floor($diferenca / (60 * 60 * 24));
			
		}

        return ($dias > 0) ? $dias : 0;
    }

}

if (!function_exists('soma_tempo')) {

    function soma_tempo($times = array()) {
		$tempo = "";
		if(!empty($times)){
			$seconds = 0;
			foreach ( $times as $time ){
				list( $g, $i, $s ) = explode( ':', $time );
				$seconds += $g * 3600;
				$seconds += $i * 60;
				$seconds += $s;
			}
			 
			$hours = floor( $seconds / 3600 );
			$hours = ($hours < 10) ? "0".$hours : $hours;
			$seconds -= $hours * 3600;
			$minutes = floor( $seconds / 60 );
			$minutes = ($minutes < 10) ? "0".$minutes : $minutes;
			$seconds -= $minutes * 60;
			$seconds = ($seconds < 10) ? "0".$seconds : $seconds;
			$tempo = "{$hours}:{$minutes}:{$seconds}";
		}	
		return $tempo;
    }

}

if (!function_exists('semana')) {

    function semana() {

		$semana = date('N');

		switch($semana){
			case '1': $semana = "Segunda-feira"; 	break; 
			case '2': $semana = "Terça-feira"; 		break; 
			case '3': $semana = "Quarta-feira"; 	break; 
			case '4': $semana = "Quinta-feira"; 	break; 
			case '5': $semana = "Sexta-feira"; 		break; 
			case '6': $semana = "Sábado"; 			break; 
			case '7': $semana = "Domingo"; 			break; 

			default: $semana = $semana; break;
		}

		return $semana;

    }

}

if (!function_exists('tdata')) {

    function tdata($data, $abreviado = FALSE) {

		if(empty($data)) return '';

		list($ano,$mes, $dia) = explode('-', $data);

		if($abreviado){

			switch($mes){
				case '01': $mes = "Jan"; break; 
				case '02': $mes = "Fev"; break; 
				case '03': $mes = "Mar"; break; 
				case '04': $mes = "Abr"; break; 
				case '05': $mes = "Mai"; break; 
				case '06': $mes = "Jun"; break; 
				case '07': $mes = "Jul"; break; 
				case '08': $mes = "Ago"; break; 
				case '09': $mes = "Set"; break; 
				case '10': $mes = "Out"; break; 
				case '11': $mes = "Nov"; break; 
				case '12': $mes = "Dez"; break; 

				default: $mes = $mes; break;
			}

			return $dia.' '.$mes.'. '.$ano;

		}

		switch($mes){
			case '01': $mes = "Janeiro"; 	break; 
			case '02': $mes = "Fevereiro"; 	break; 
			case '03': $mes = "Março"; 		break; 
			case '04': $mes = "Abril"; 		break; 
			case '05': $mes = "Maio"; 		break; 
			case '06': $mes = "Junho"; 		break; 
			case '07': $mes = "Julho"; 		break; 
			case '08': $mes = "Agosto"; 	break; 
			case '09': $mes = "Setembro"; 	break; 
			case '10': $mes = "Outubro"; 	break; 
			case '11': $mes = "Novembro"; 	break; 
			case '12': $mes = "Dezembro"; 	break; 

			default: $mes = $mes; break;
		}

		return $dia.' de '.$mes.' de '.$ano;

    }

}

if (!function_exists('odata')) {

    function odata($datetime = null, $array = FALSE) {
		
		if(empty($datetime)) $datetime = date('Y-m-d H:i:s');

		$data = new stdClass();
		$data->strtotime = strtotime($datetime);

		list($data->Ymd, $data->His) = explode(' ', $datetime);
		list($data->Y,$data->m,$data->d) = explode('-', $data->Ymd);
		list($data->H,$data->i,$data->s) = explode(':', $data->His);
		
		$data->dmY = fdata($data->Ymd, "/");
		
		$data->y = date('y', $data->strtotime);
		$data->a = date('a', $data->strtotime);
		$data->A = date('A', $data->strtotime);
		$data->B = date('B', $data->strtotime);
		$data->g = date('g', $data->strtotime);
		$data->G = date('G', $data->strtotime);
		$data->e = date('e', $data->strtotime);

		$data->datetime = $datetime;
		$data->dmYHis = $data->dmY.' '.$data->His;
		$data->Hi =  $data->H.':'.$data->i;
		$data->dmYHi = $data->dmY.' '.$data->Hi;
		$data->h = date('h', $data->strtotime);
		$data->r = date('r', $data->strtotime);
		$data->w = date('w', $data->strtotime);
		$data->W = date('W', $data->strtotime);
		$data->W = date('W', $data->strtotime);
		$data->o = date('o', $data->strtotime);
		$data->u = date('u', $data->strtotime);

		$data->j = date('j', $data->strtotime);
		$data->N = date('N', $data->strtotime);
		$data->S = date('S', $data->strtotime);
		$data->z = date('z', $data->strtotime);
		$data->t = date('t', $data->strtotime);
		$data->L = date('L', $data->strtotime);
		$data->I = date('I', $data->strtotime);
		$data->O = date('O', $data->strtotime);
		$data->P = date('P', $data->strtotime);
		$data->T = date('T', $data->strtotime);
		$data->Z = date('Z', $data->strtotime);
		$data->c = date('c', $data->strtotime);
		$data->r = date('r', $data->strtotime);
		$data->U = date('U', $data->strtotime);

		switch($data->w){
			case '0': $data->lc = "domingo"; break; 
			case '1': $data->lc = "segunda-feira"; break; 
			case '2': $data->lc = "terça-feira"; break; 
			case '3': $data->lc = "quarta-feira"; break; 
			case '4': $data->lc = "quinta-feira"; break; 
			case '5': $data->lc = "sexta-feira"; break;
			case '6': $data->lc = "sábado"; break;

			default: $data->lc = $data->w; break;
		}

		switch($data->w){
			case '0': $data->l = "Domingo"; break; 
			case '1': $data->l = "Segunda"; break; 
			case '2': $data->l = "Terça"; break; 
			case '3': $data->l = "Quarta"; break; 
			case '4': $data->l = "Quinta"; break; 
			case '5': $data->l = "Sexta"; break;
			case '6': $data->l = "Sábado"; break;

			default: $data->l = $data->w; break;
		}

		switch($data->w){
			case '0': $data->D = "Dom"; break; 
			case '1': $data->D = "Seg"; break; 
			case '2': $data->D = "Ter"; break; 
			case '3': $data->D = "Qua"; break; 
			case '4': $data->D = "Qui"; break; 
			case '5': $data->D = "Sex"; break;
			case '6': $data->D = "Sáb"; break;

			default: $data->D = $data->w; break;
		}

		switch($data->m){
			case '01': $data->M = "Jan"; break; 
			case '02': $data->M = "Fev"; break; 
			case '03': $data->M = "Mar"; break; 
			case '04': $data->M = "Abr"; break; 
			case '05': $data->M = "Mai"; break; 
			case '06': $data->M = "Jun"; break; 
			case '07': $data->M = "Jul"; break; 
			case '08': $data->M = "Ago"; break; 
			case '09': $data->M = "Set"; break; 
			case '10': $data->M = "Out"; break; 
			case '11': $data->M = "Nov"; break; 
			case '12': $data->M = "Dez"; break; 

			default: $data->M = $data->m; break;
		}

		switch($data->m){
			case '01': $data->F = "Janeiro"; 	break; 
			case '02': $data->F = "Fevereiro"; break; 
			case '03': $data->F = "Março"; break; 
			case '04': $data->F = "Abril"; break; 
			case '05': $data->F = "Maio"; break; 
			case '06': $data->F = "Junho"; break; 
			case '07': $data->F = "Julho"; break; 
			case '08': $data->F = "Agosto"; break; 
			case '09': $data->F = "Setembro"; break; 
			case '10': $data->F = "Outubro"; break; 
			case '11': $data->F = "Novembro"; break; 
			case '12': $data->F = "Dezembro"; break; 

			default: $data->F = $data->m; break;
		}

		if($array == TRUE) return json_decode(json_encode($data), true);

		return $data;
    }
}
?>