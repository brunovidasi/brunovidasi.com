<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Download extends CI_Controller {

	public function index(){
		
	}
	
	public function especificacoes_tecnicas(){
		$this->download_model->especificacoes_tecnicas();
	}	
	
	public function informacao_suporte(){
		$this->download_model->informacao_suporte();
	}
	
}