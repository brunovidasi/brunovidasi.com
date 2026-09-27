<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

if (!function_exists('generate_password')) {

    function generate_password($length = 8, $type = 'alphanumeric') {
		if($type == 'alphanumeric')
			$allowed_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		elseif($type == 'numeric')
			$allowed_chars = '0123456789';
		elseif($type == 'alphabetic')
			$allowed_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		elseif($type == 'special')
			$allowed_chars = '!@#$%&*+-?';
		elseif($type == 'full')
			$allowed_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*+-?';
        $max = strlen($allowed_chars) - 1;

        $password = "";

        for ($i = 0; $i < $length; $i++) {
            $password .= $allowed_chars[mt_rand(0, $max)];
        }

        return $password;
    }
}

if (!function_exists('generate_confirmation_code')) {

	function generate_confirmation_code($key = 8) {

		$seconds_random = date('s') * generate_password(2, 'numeric');
		$seconds_key = date('s') * $key;
		$key_random = $key * generate_password(2, 'numeric');

		$date_key = date('Ymd') * $key;
		$time_key = date('His') * $key;

        $code  = generate_password(1) . $key . (date('y') + 5) . generate_password(4) . $date_key . (date('d')) . generate_password(4) . date('m') . $time_key . generate_password(5) . date('wz') . $seconds_random . $key_random . $seconds_key . date('His') . generate_password(2);

        return $code;
    }

}

if (!function_exists('generate_salt')) {

	function generate_salt($length = 6, $uppercase = true, $numbers = true, $symbols = true) {

		$lower = 'abcdefghijklmnopqrstuvwxyz';
		$upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$digits = '1234567890';
		$special = '!@#$%*-';
		$salt = '';
		$characters = '';

		$characters .= $lower;
		if ($uppercase) $characters .= $upper;
		if ($numbers) $characters .= $digits;
		if ($symbols) $characters .= $special;

		$len = strlen($characters);
		for($n = 1; $n <= $length; $n++){
			$rand = mt_rand(1, $len);
			$salt .= $characters[$rand-1];
		}

		return $salt;
    }

}
