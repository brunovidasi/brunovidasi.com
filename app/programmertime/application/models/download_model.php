<?php

class Download_model extends CI_Model {

    function __construct() {
        parent::__construct();
    }

	function technical_specs(){

		$specs = 'PROGRAMMER TIME - Project Management\n
________________________________________________________________________________________\n\n

http://www.programmertime.com/ \n\n


Technical Specifications \n
________________________________________________________________________________________\n\n

Current System Version: 1.0.0.0 \n
CodeIgniter Version: 2.1.4 \n
Release Date: - \n
Last Updated: - \n


PHP Information \n
________________________________________________________________________________________\n\n

PHP Version: 5.3.28 \n
Other PHP versions may be incompatible with the system. \n\n


Database Information \n
________________________________________________________________________________________\n\n

Database: MySQL 5.5.36-cll \n
DBMS: PHPMyAdmin \n\n


Development Information \n
________________________________________________________________________________________\n\n

Web Developer: Bruno Vieira - bruno@programmertime.com - www.brunovidasi.com \n
Android Developer: Filipe Moreira - filipe@programmertime.com \n';

		$title = 'Programmer Time - Technical Specifications.txt';

		force_download($title, $specs);
	}


	function support_info(){

		$useragent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

		if (preg_match('|MSIE ([0-9].[0-9]{1,2})|',$useragent,$matched)) {
			$browser_version=$matched[1];
			$browser = 'IE';
		}elseif (preg_match( '|Opera/([0-9].[0-9]{1,2})|',$useragent,$matched)) {
			$browser_version=$matched[1];
			$browser = 'Opera';
		}elseif(preg_match('|Firefox/([0-9\.]+)|',$useragent,$matched)) {
			$browser_version=$matched[1];
			$browser = 'Firefox';
		}elseif(preg_match('|Chrome/([0-9\.]+)|',$useragent,$matched)) {
			$browser_version=$matched[1];
			$browser = 'Chrome';
		}elseif(preg_match('|Safari/([0-9\.]+)|',$useragent,$matched)) {
			$browser_version=$matched[1];
			$browser = 'Safari';
		}else {
			$browser_version = '';
			$browser= 'Other';
		}

		$info = 'User Information\n
________________________________________________________________________________________\n\n

Name: '. $this->session->userdata('full_name') .'\n
Login: '. $this->session->userdata('login') .' \n
ID: '. $this->session->userdata('id') .' \n
Email: '. $this->session->userdata('email') .' \n
Employee ID: '. $this->session->userdata('employee_id') .' \n
Image: '. $this->session->userdata('image') .' \n\n

Number of Logins: '. $this->session->userdata('login_count') .' \n
Access Level: '. $this->session->userdata('access_level') .' \n
Status: '. $this->session->userdata('status') .' \n
Confirmed: '. $this->session->userdata('confirmed') .' \n
Confirmation Code: '. $this->session->userdata('email_token') .' \n
Logged In: '. $this->session->userdata('logged_in') .' \n\n


Session Data \n
________________________________________________________________________________________\n\n

SESSION ID: '. $this->session->userdata('session_id') .' \n
USER AGENT: '. $this->session->userdata('user_agent') .' \n
LAST ACTIVITY: '. $this->session->userdata('last_activity') .' \n
LAST VISIT: '. $this->session->userdata('last_visit') .' \n

Browser: '.  $browser . ' - ' . $browser_version .' \n
IP: '. $this->session->userdata('ip_address') .' \n

System Information \n
________________________________________________________________________________________ \n\n

Programmer Time Version: 1.0.0.0 \n
PHP Version: '. phpversion() .'';

		$title = 'Session Information - '. $this->session->userdata('login') .'.txt';

		force_download($title, $info);
	}

}
