<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Download extends CI_Controller {

	public function index(){
		
	}
	
	public function technical_specs(){
		$this->download_model->technical_specs();
	}	
	
	public function support_info(){
		$this->download_model->support_info();
	}
	
}