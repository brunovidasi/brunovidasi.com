<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

if (!function_exists('fdate')) {

	// Converts a date between dd/mm/yyyy ("/") and yyyy-mm-dd ("-").
    function fdate($date, $separator = "") {

		if(!empty($date)){
			if ($separator == "-") {
				$part = explode("/", $date);
				$date = $part[2] . "-" . $part[1] . "-" . $part[0];
			}elseif($separator == "/") {
				$part = explode("-", $date);
				$part[2] = substr($part[2], 0, 2);
				$date = $part[2] . "/" . $part[1] . "/" . $part[0];
			}
		}
        return $date;
    }

}

if (!function_exists('ftime')) {

    function ftime($time) {

		if(!empty($time)){
			$part = explode(":", $time);
			$time = $part[0] . ":" . $part[1];
		}
        return $time;
    }

}

if (!function_exists('fdatetime_parts')) {

	// With "/": splits yyyy-mm-dd hh:mm:ss into array('date' => dd/mm/yyyy, 'time' => hh:mm:ss).
	// With "-": turns dd/mm/yyyy hh:mm:ss into yyyy-mm-dd hh:mm:ss.
    function fdatetime_parts($date, $separator = "") {

		if(!empty($date)){

			// A DATETIME column can hold a bare date on SQLite (MySQL would have
			// padded it with 00:00:00), so the time part is optional.
			$full_date = explode(" ", $date);
			$date = $full_date[0];
			$time = isset($full_date[1]) ? $full_date[1] : '00:00:00';

			if ($separator == "-") {
				$part = explode("/", $date);
				$date = $part[2] . "-" . $part[1] . "-" . $part[0] . " " . $time;
			}

			elseif ($separator == "/") {
				$part = explode("-", $date);
				$date_slash = $part[2] . "/" . $part[1] . "/" . $part[0];

				$date = array(
					"date" => $date_slash,
					"time" => $time
				);
			}

		}

        return $date;
    }

}

if (!function_exists('fdatetime')) {

    function fdatetime($date, $separator = "") {

		if(!empty($date)){

			// A DATETIME column can hold a bare date on SQLite (MySQL would have
			// padded it with 00:00:00), so the time part is optional.
			$full_date = explode(" ", $date);
			$date = $full_date[0];
			$time = isset($full_date[1]) ? $full_date[1] : '00:00:00';

			if ($separator == "-") {
				$part = explode("/", $date);
				$date = $part[2] . "-" . $part[1] . "-" . $part[0] . " " . $time;
			}

			elseif ($separator == "/") {
				$part = explode("-", $date);
				$date_slash = $part[2] . "/" . $part[1] . "/" . $part[0];
				$time = explode(':', $time);

				$date = $date_slash . " " . $time[0] . ':' . $time[1];
			}

		}

        return $date;
    }

}

if (!function_exists('count_days')) {

    function count_days($start, $end="") {

		$days = "";

		if(!empty($start)){

			if((empty($end)) OR ($end == '0000-00-00')){
				$end = date('Y-m-d');
			}

			$difference = strtotime($end) - strtotime($start);

			$days = floor($difference / (60 * 60 * 24));

		}

        return ($days > 0) ? $days : 0;
    }

}

if (!function_exists('sum_time')) {

    function sum_time($times = array()) {
		$total = "";
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
			$total = "{$hours}:{$minutes}:{$seconds}";
		}
		return $total;
    }

}

if (!function_exists('weekday')) {

	// Today's weekday name, e.g. "Monday".
    function weekday() {

		return date('l');

    }

}

if (!function_exists('tdate')) {

	// yyyy-mm-dd as text: "5 March 2014", or "5 Mar. 2014" when $short.
    function tdate($date, $short = FALSE) {

		if(empty($date)) return '';

		list($year, $month, $day) = explode('-', $date);

		$timestamp = mktime(0, 0, 0, (int) $month, 1, (int) $year);

		if($short){
			return $day.' '.date('M', $timestamp).'. '.$year;
		}

		return $day.' '.date('F', $timestamp).' '.$year;

    }

}

if (!function_exists('odate')) {

	// Every date() format character of a datetime, as properties of an object
	// (plus a few combined formats), e.g. odate()->dmY, odate()->Hi, odate()->F.
    function odate($datetime = null, $array = FALSE) {

		if(empty($datetime)) $datetime = date('Y-m-d H:i:s');

		$date = new stdClass();
		$date->strtotime = strtotime($datetime);

		list($date->Ymd, $date->His) = explode(' ', $datetime);
		list($date->Y,$date->m,$date->d) = explode('-', $date->Ymd);
		list($date->H,$date->i,$date->s) = explode(':', $date->His);

		$date->dmY = fdate($date->Ymd, "/");

		$date->y = date('y', $date->strtotime);
		$date->a = date('a', $date->strtotime);
		$date->A = date('A', $date->strtotime);
		$date->B = date('B', $date->strtotime);
		$date->g = date('g', $date->strtotime);
		$date->G = date('G', $date->strtotime);
		$date->e = date('e', $date->strtotime);

		$date->datetime = $datetime;
		$date->dmYHis = $date->dmY.' '.$date->His;
		$date->Hi =  $date->H.':'.$date->i;
		$date->dmYHi = $date->dmY.' '.$date->Hi;
		$date->h = date('h', $date->strtotime);
		$date->r = date('r', $date->strtotime);
		$date->w = date('w', $date->strtotime);
		$date->W = date('W', $date->strtotime);
		$date->o = date('o', $date->strtotime);
		$date->u = date('u', $date->strtotime);

		$date->j = date('j', $date->strtotime);
		$date->N = date('N', $date->strtotime);
		$date->S = date('S', $date->strtotime);
		$date->z = date('z', $date->strtotime);
		$date->t = date('t', $date->strtotime);
		$date->L = date('L', $date->strtotime);
		$date->I = date('I', $date->strtotime);
		$date->O = date('O', $date->strtotime);
		$date->P = date('P', $date->strtotime);
		$date->T = date('T', $date->strtotime);
		$date->Z = date('Z', $date->strtotime);
		$date->c = date('c', $date->strtotime);
		$date->U = date('U', $date->strtotime);

		$date->lc = strtolower(date('l', $date->strtotime));	# "monday"
		$date->l  = date('l', $date->strtotime);				# "Monday"
		$date->D  = date('D', $date->strtotime);				# "Mon"
		$date->M  = date('M', $date->strtotime);				# "Mar"
		$date->F  = date('F', $date->strtotime);				# "March"

		if($array == TRUE) return json_decode(json_encode($date), true);

		return $date;
    }
}
?>
