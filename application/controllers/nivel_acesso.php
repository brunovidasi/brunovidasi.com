<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Nivel_acesso extends CI_Controller {

	public function index(){
		$this->lista();
	}
	
	public function lista(){
		$dados['nivel_acesso'] = $this->usuario_model->get_niveis_acesso();
		
		$dados['view'] = $this->load->view('nivel_acesso/lista', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	public function cadastrar(){
		$dados['nivel_acesso'] = "";
		
		$dados['view'] = $this->load->view('nivel_acesso/cadastrar', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	public function insert(){
        if ($this->valida_form()){
            $this->acesso_model->post();
			$idinsert = $this->acesso_model->insert();
			
            if ($idinsert > 0){
				$this->session->set_flashdata('mensagem_sucesso', lang('msg_na_cadastro_sucesso'));
                redirect('/nivel_acesso/');
            }
			$this->session->set_flashdata('mensagem_erro', lang('msg_na_cadastro_erro'));
			redirect('/nivel_acesso/cadastrar/');
        }
		$this->cadastrar();			
    }
	
	private function valida_form(){
		$this->form_validation->set_rules('cargo', lang('nome_cargo'), 'trim|required|is_unique[usuario_nivel_acesso.cargo]');
		$this->form_validation->set_rules('cadastra_projeto');
		$this->form_validation->set_rules('edita_projeto');
		$this->form_validation->set_rules('cadastra_cliente');
		$this->form_validation->set_rules('edita_cliente');
		$this->form_validation->set_rules('cadastra_usuario');
		$this->form_validation->set_rules('edita_usuario');
		$this->form_validation->set_rules('envia_relatorio');
		$this->form_validation->set_rules('lanca_etapa');
		$this->form_validation->set_rules('lanca_pagamento');
		
		return $this->form_validation->run();
	}	
}