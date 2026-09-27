<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

if (!function_exists('calculate_hours')) {

	// Human-readable duration between two HH:MM times, e.g. "45 minutes" or "01:30 hours".
	function calculate_hours($end_time, $start_time){

		if(!empty($end_time) AND !empty($start_time)) {

			if($end_time < $start_time){
				return "";
			}

			$parts[1]=explode(':',$end_time);
			$parts[2]=explode(':',$start_time);

			$elapsed_minutes[1] = ($parts[1][0]*60)+$parts[1][1];
			$elapsed_minutes[2] = ($parts[2][0]*60)+$parts[2][1];

			$elapsed_minutes = $elapsed_minutes[1]-$elapsed_minutes[2];

			if($elapsed_minutes<=59){
				return($elapsed_minutes.' minutes');
			}

			elseif($elapsed_minutes>59){
				$elapsed_hours = floor($elapsed_minutes/60);

					if($elapsed_hours<=9){
						$elapsed_hours='0'.$elapsed_hours;
					}
					$remaining_minutes = $elapsed_minutes%60;

					if($remaining_minutes<=9){
						$remaining_minutes='0'.$remaining_minutes;
					}

				return ($elapsed_hours.':'.$remaining_minutes.' hours');
			}
		}

	}

}

if (!function_exists('strip_seconds')) {

	function strip_seconds($time){

		if(!empty($time)){
			$parts = explode(':', $time);
			return $parts[0].':'.(isset($parts[1]) ? $parts[1] : '00');
		}

	}

}

if (!function_exists('calculate_total_hours')) {

	// Duration between two HH:MM times as HH:MM:SS, for summing with sum_hours().
	function calculate_total_hours($end_time, $start_time){

		if(!empty($end_time) AND !empty($start_time)) {

			if($end_time < $start_time){
				return "";
			}

			$parts[1]=explode(':',$end_time);
			$parts[2]=explode(':',$start_time);

			$elapsed_minutes[1] = ($parts[1][0]*60)+$parts[1][1];
			$elapsed_minutes[2] = ($parts[2][0]*60)+$parts[2][1];

			$elapsed_minutes = $elapsed_minutes[1]-$elapsed_minutes[2];

			if($elapsed_minutes<=59){
				if($elapsed_minutes<=9){
					$elapsed_minutes='0'.$elapsed_minutes;
				}
				return('00:'. $elapsed_minutes.':00');
			}

			elseif($elapsed_minutes>59){
				$elapsed_hours = floor($elapsed_minutes/60);

					if($elapsed_hours<=9){
						$elapsed_hours='0'.$elapsed_hours;
					}
					$remaining_minutes = $elapsed_minutes%60;

					if($remaining_minutes<=9){
						$remaining_minutes='0'.$remaining_minutes;
					}

				return ($elapsed_hours.':'.$remaining_minutes.':00');
			}
		}

	}

}

if (!function_exists('sum_hours')) {

	function sum_hours($times){

		if(!empty($times)) {

			$seconds = 0;

			foreach($times as $time){

				#list($h, $m, $s) = explode(':', $time);
				$temp = explode(':', $time);

				$h = (!empty($temp[0])) ? $temp[0] : '00';
				$m = (!empty($temp[1])) ? $temp[1] : '00';
				$s = (!empty($temp[2])) ? $temp[2] : '00';

				$seconds += $h * 3600;
				$seconds += $m * 60;
				$seconds += $s;

			}

			$hours = floor( $seconds / 3600 );
			$seconds %= 3600;
			$minutes = floor( $seconds / 60 );
			$seconds %= 60;

			if($hours <= 9){
				$hours = '0' . $hours;
			}

			if($minutes <= 9){
				$minutes = '0' . $minutes;
			}

			if($seconds <= 9){
				$seconds = '0' . $seconds;
			}

			return $hours . ':' . $minutes;

		}

		return '00:00';

	}

}

if (!function_exists('subtract_hours')) {

	function subtract_hours($time1, $time2){

		if(!empty($time1) AND !empty($time2)) {

			$seconds = 0;
			$seconds1 = 0;
			$seconds2 = 0;

			#list($h, $m, $s) = explode(':', $time);
			$temp = explode(':', $time1);

			$h1 = (!empty($temp[0])) ? $temp[0] : '00';
			$m1 = (!empty($temp[1])) ? $temp[1] : '00';
			$s1 = (!empty($temp[2])) ? $temp[2] : '00';

			$temp = explode(':', $time2);

			$h2 = (!empty($temp[0])) ? $temp[0] : '00';
			$m2 = (!empty($temp[1])) ? $temp[1] : '00';
			$s2 = (!empty($temp[2])) ? $temp[2] : '00';

			$seconds1 += $h1 * 3600;
			$seconds1 += $m1 * 60;
			$seconds1 += $s1;

			$seconds2 += $h2 * 3600;
			$seconds2 += $m2 * 60;
			$seconds2 += $s2;

			$seconds = $seconds1 - $seconds2;

			if($seconds < 0)
				$negative = TRUE;
			else
				$negative = FALSE;

			$seconds = abs($seconds);

			$hours = floor( $seconds / 3600 );
			$seconds %= 3600;
			$minutes = floor( $seconds / 60 );
			$seconds %= 60;

			if($hours <= 9){
				$hours = '0' . $hours;
			}

			if($minutes <= 9){
				$minutes = '0' . $minutes;
			}

			if($seconds <= 9){
				$seconds = '0' . $seconds;
			}

			$result =  $hours . ':' . $minutes;

			if($negative)
				$result = '<span style="color:red;">-'.$result.'</span>';
			else
				$result = '<span style="color:green;">'.$result.'</span>';

			return $result;

		}

	}

}

if (!function_exists('regular_hours')) {

	// Shows a day's worked time against an 8-hour day: green once it reaches 08:00, red below.
	function regular_hours($time){

		if(!empty($time)) {

			$seconds = 0;

			#list($h, $m, $s) = explode(':', $time);
			$temp = explode(':', $time);

			$h = (!empty($temp[0])) ? $temp[0] : '00';
			$m = (!empty($temp[1])) ? $temp[1] : '00';
			$s = (!empty($temp[2])) ? $temp[2] : '00';

			$seconds += $h * 3600;
			$seconds += $m * 60;
			$seconds += $s;

			if($seconds == 28800)
				return '<span style="color:green;">08:00</span>';
			elseif($seconds > 28800)
				return '<span style="color:green;">08:00</span>';
			else
				return '<span style="color:red;">'.$time.'</span>';

		}

		return "";

	}

}

if (!function_exists('to_seconds')) {

	function to_seconds($time){

		if(!empty($time)) {

			$time_parts = explode(":", $time);

			if(empty($time_parts[2])){
				$time = $time . ':00';
			}

			$seconds = 0;

			list($h, $m, $s) = explode(':', $time);

			$seconds += $h * 3600;
			$seconds += $m * 60;
			$seconds += $s;

			return $seconds;

		}

	}

}


if (!function_exists('backdated')) {

	// Whether a time entry was logged for a date/hour other than the current one.
	function backdated($date, $start_time){

		$current_date = date('d/m/Y');
		$current_time = date('H');

		list($hour, $minute, $second) = explode(":", $start_time);

		$is_backdated = FALSE;
		if($date != $current_date) $is_backdated = TRUE;
		if($hour != $current_time) $is_backdated = TRUE;

		return $is_backdated;

	}

}

?>
