<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

if (!function_exists('calcular_horas')) {

	function calcular_horas($hora_final, $hora_inicio){
	
		if(!empty($hora_final) AND !empty($hora_inicio)) {
		
			if($hora_final < $hora_inicio){
				return "";
			}
			
			$separar[1]=explode(':',$hora_final); 
			$separar[2]=explode(':',$hora_inicio); 

			$total_minutos_trasncorridos[1] = ($separar[1][0]*60)+$separar[1][1]; 
			$total_minutos_trasncorridos[2] = ($separar[2][0]*60)+$separar[2][1]; 
			
			$total_minutos_trasncorridos = $total_minutos_trasncorridos[1]-$total_minutos_trasncorridos[2]; 
			
			if($total_minutos_trasncorridos<=59){
				return($total_minutos_trasncorridos.' Minutos'); 
			}
			
			elseif($total_minutos_trasncorridos>59){
				$HORA_TRANSCORRIDA = round($total_minutos_trasncorridos/60); 
				
					if($HORA_TRANSCORRIDA<=9){
						$HORA_TRANSCORRIDA='0'.$HORA_TRANSCORRIDA; 
					}
					$MINUTOS_TRANSCORRIDOS = $total_minutos_trasncorridos%60; 
					
					if($MINUTOS_TRANSCORRIDOS<=9){ 
						$MINUTOS_TRANSCORRIDOS='0'.$MINUTOS_TRANSCORRIDOS; 
					}
					
				return ($HORA_TRANSCORRIDA.':'.$MINUTOS_TRANSCORRIDOS.' Horas'); 
			}
		}
	
	}

}

if (!function_exists('tira_segundos')) {

	function tira_segundos($hora){
	
		if(!empty($hora)){
			$partes = explode(':', $hora);
			return $partes[0].':'.(isset($partes[1]) ? $partes[1] : '00');
		}
	
	}

}

if (!function_exists('calcular_horas_total')) {

	function calcular_horas_total($hora_final, $hora_inicio){
	
		if(!empty($hora_final) AND !empty($hora_inicio)) {
		
			if($hora_final < $hora_inicio){
				return "";
			}
			
			$separar[1]=explode(':',$hora_final); 
			$separar[2]=explode(':',$hora_inicio); 

			$total_minutos_trasncorridos[1] = ($separar[1][0]*60)+$separar[1][1]; 
			$total_minutos_trasncorridos[2] = ($separar[2][0]*60)+$separar[2][1]; 
			
			$total_minutos_trasncorridos = $total_minutos_trasncorridos[1]-$total_minutos_trasncorridos[2]; 
			
			if($total_minutos_trasncorridos<=59){
				if($total_minutos_trasncorridos<=9){ 
					$total_minutos_trasncorridos='0'.$total_minutos_trasncorridos; 
				}
				return('00:'. $total_minutos_trasncorridos.':00'); 
			}
			
			elseif($total_minutos_trasncorridos>59){
				$HORA_TRANSCORRIDA = round($total_minutos_trasncorridos/60); 
				
					if($HORA_TRANSCORRIDA<=9){
						$HORA_TRANSCORRIDA='0'.$HORA_TRANSCORRIDA; 
					}
					$MINUTOS_TRANSCORRIDOS = $total_minutos_trasncorridos%60; 
					
					if($MINUTOS_TRANSCORRIDOS<=9){ 
						$MINUTOS_TRANSCORRIDOS='0'.$MINUTOS_TRANSCORRIDOS; 
					}
					
				return ($HORA_TRANSCORRIDA.':'.$MINUTOS_TRANSCORRIDOS.':00'); 
			}
		}
	
	}

}

if (!function_exists('somar_horas')) {

	function somar_horas($tempos){
				
		if(!empty($tempos)) {
			
			$segundos = 0;

			foreach($tempos as $tempo){
				
				#list($h, $m, $s) = explode(':', $tempo);
				$temp = explode(':', $tempo);
				
				$h = (!empty($temp[0])) ? $temp[0] : '00';
				$m = (!empty($temp[1])) ? $temp[1] : '00';
				$s = (!empty($temp[2])) ? $temp[2] : '00';
				
				$segundos += $h * 3600;
				$segundos += $m * 60;
				$segundos += $s;

			}
			
			$horas = floor( $segundos / 3600 );
			$segundos %= 3600;
			$minutos = floor( $segundos / 60 );
			$segundos %= 60;
			
			if($horas <= 9){
				$horas = '0' . $horas;
			}
			
			if($minutos <= 9){
				$minutos = '0' . $minutos;
			}
			
			if($segundos <= 9){
				$segundos = '0' . $segundos;
			}
			
			return $horas . ':' . $minutos;
		
		}

		return '00:00';
	
	}

}

if (!function_exists('subtrair_horas')) {

	function subtrair_horas($hora1, $hora2){
				
		if(!empty($hora1) AND !empty($hora2)) {
			
			$segundos = 0;
			$segundos1 = 0;
			$segundos2 = 0;

			#list($h, $m, $s) = explode(':', $tempo);
			$temp = explode(':', $hora1);
			
			$h1 = (!empty($temp[0])) ? $temp[0] : '00';
			$m1 = (!empty($temp[1])) ? $temp[1] : '00';
			$s1 = (!empty($temp[2])) ? $temp[2] : '00';

			$temp = explode(':', $hora2);
			
			$h2 = (!empty($temp[0])) ? $temp[0] : '00';
			$m2 = (!empty($temp[1])) ? $temp[1] : '00';
			$s2 = (!empty($temp[2])) ? $temp[2] : '00';
			
			$segundos1 += $h1 * 3600;
			$segundos1 += $m1 * 60;
			$segundos1 += $s1;

			$segundos2 += $h2 * 3600;
			$segundos2 += $m2 * 60;
			$segundos2 += $s2;

			$segundos = $segundos1 - $segundos2;

			if($segundos < 0) 
				$negativo = TRUE;
			else
				$negativo = FALSE;

			$segundos = abs($segundos);
			
			$horas = floor( $segundos / 3600 );
			$segundos %= 3600;
			$minutos = floor( $segundos / 60 );
			$segundos %= 60;
			
			if($horas <= 9){
				$horas = '0' . $horas;
			}
			
			if($minutos <= 9){
				$minutos = '0' . $minutos;
			}
			
			if($segundos <= 9){
				$segundos = '0' . $segundos;
			}
			
			$retorno =  $horas . ':' . $minutos;

			if($negativo) 
				$retorno = '<span style="color:red;">-'.$retorno.'</span>';
			else
				$retorno = '<span style="color:green;">'.$retorno.'</span>';

			return $retorno;
		
		}
	
	}

}

if (!function_exists('horas_normais')) {

	function horas_normais($hora){
				
		if(!empty($hora)) {
			
			$segundos = 0;

			#list($h, $m, $s) = explode(':', $tempo);
			$temp = explode(':', $hora);
			
			$h = (!empty($temp[0])) ? $temp[0] : '00';
			$m = (!empty($temp[1])) ? $temp[1] : '00';
			$s = (!empty($temp[2])) ? $temp[2] : '00';

			$segundos += $h * 3600;
			$segundos += $m * 60;
			$segundos += $s;

			if($segundos == 28800)
				return '<span style="color:green;">08:00</span>';
			elseif($segundos > 28800)
				return '<span style="color:green;">08:00</span>';
			else
				return '<span style="color:red;">'.$hora.'</span>';

		}

		return "";
	
	}

}

if (!function_exists('transforma_segundos')) {

	function transforma_segundos($hora){
				
		if(!empty($hora)) {
			
			$formata_hora = explode(":", $hora);
			
			if(empty($formata_hora[2])){
				$hora = $hora . ':00';
			}
			
			$segundos = 0;
			
			list($h, $m, $s) = explode(':', $hora);
			
			$segundos += $h * 3600;
			$segundos += $m * 60;
			$segundos += $s;
			
			return $segundos;
		
		}
	
	}

}


if (!function_exists('retroativa')) {

	function retroativa($data, $inicio){
		
		$data_atual = date('d/m/Y');
		$hora_atual = date('H');

		list($hora, $minuto, $segundo) = explode(":", $inicio);

		if($data != $data_atual) $booleano = TRUE;
		if($hora != $hora_atual) $booleano = TRUE;
		
		if($data == $data_atual && $hora == $hora_atual) return FALSE;

		return $booleano;
		
	}

}

?>