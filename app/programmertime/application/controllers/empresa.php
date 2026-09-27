<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Empresa extends CI_Controller {
	
	public function index(){
	    $this->visualizar();
	}
	
	function visualizar(){
		$dados['empresa'] = $this->empresa_model->get_empresa()->row();
		$dados['view'] = $this->load->view('empresa/visualizar', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	function editar(){
		$dados['empresa'] = $this->empresa_model->get_empresa()->row();
		$dados['view'] = $this->load->view('empresa/editar', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	function update(){
		if ($this->valida_form()) {
			$this->empresa_model->post();	
			$atualizado = $this->empresa_model->update();
			if ($atualizado) {
				$this->session->set_flashdata('mensagem_sucesso', lang('msg_empresa_editar_sucesso'));
				redirect('/empresa/visualizar/');
			}
			$this->session->set_flashdata('mensagem_erro', lang('msg_empresa_editar_erro'));
			redirect('/empresa/editar/');
			
		}
		$this->editar();
	}
	
	private function valida_form(){
		$this->form_validation->set_rules('idrepresentante', 	lang('lbl_representante'), 	'required');
		$this->form_validation->set_rules('nome', 				lang('lbl_nome'), 			'required');
		$this->form_validation->set_rules('razao_social', 		lang('lbl_razao_social'), 	'required');
		$this->form_validation->set_rules('cnpj', 				lang('lbl_cnpj'), 			'required');
		$this->form_validation->set_rules('email', 				lang('lbl_email'), 			'required');
		$this->form_validation->set_rules('telefone', 			lang('lbl_telefone'), 		'required');
		$this->form_validation->set_rules('celular');
		$this->form_validation->set_rules('website');
		$this->form_validation->set_rules('imagem_logo');
		$this->form_validation->set_rules('data_fundacao');
		$this->form_validation->set_rules('endereco');
		$this->form_validation->set_rules('endereco_numero');
		$this->form_validation->set_rules('endereco_bairro');
		$this->form_validation->set_rules('endereco_complemento');
		$this->form_validation->set_rules('endereco_municipio');
		$this->form_validation->set_rules('endereco_estado');
		$this->form_validation->set_rules('endereco_cep');
		
		return $this->form_validation->run();
	}
}