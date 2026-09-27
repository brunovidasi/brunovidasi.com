<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Ajuda extends CI_Controller {
	
	public function index(){
	    $this->lista();
	}
	
	function lista($id = ""){
		$dados['ajudas'] = $this->ajuda_model->get_ajudas();
		
		$dados['view'] = $this->load->view('ajuda/lista', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
		
	}
	
	function cadastrar(){
		$dados['view'] = $this->load->view('ajuda/cadastrar', array(), TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	function editar($id = ""){
		$dados['ajuda'] = $this->ajuda_model->get_ajuda($id)->row();
		
		$dados['view'] = $this->load->view('ajuda/editar', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	
	function insert(){
        if ($this->valida_form()){
		
            $this->ajuda_model->post();
			$idinsert = $this->ajuda_model->insert();
			
            if ($idinsert > 0){
				$this->session->set_flashdata('mensagem_sucesso', 'Cadastro efetuado com sucesso.');
                redirect('/ajuda/');
            }
			$this->session->set_flashdata('mensagem_erro', 'Cadastro <strong>não</strong> efetuado. Tente novamente.');
		    redirect('/ajuda/cadastrar/');
        }
		$this->cadastrar();		
    }
	
	function update($id = 0){
        if (!empty($id)) {
			if ($this->valida_form()) {
				
				$this->ajuda_model->post();	
			    $atualizado = $this->ajuda_model->update($id);				
				
				if ($atualizado) {
				    $this->session->set_flashdata('mensagem_sucesso', 'Ajuda atualizada com sucesso.');
					redirect('/ajuda/');
				}
				$this->session->set_flashdata('mensagem_erro', 'Ajuda <strong>não</strong> atualizada. Tente novamente.');
				redirect('/ajuda/editar/'.$id);
			}
			$this->editar($id);
			
	    }
	}
	
	function delete($idajuda = 0){
		$excluido = $this->ajuda_model->delete($idajuda);
		
		if ($excluido){
			$this->session->set_flashdata('mensagem_sucesso', 'Ajuda excluida com sucesso.');
			redirect('/ajuda/');
		}
		$this->session->set_flashdata('mensagem_erro', 'Ajuda <strong>não</strong> excluida, tente novamente.');
		redirect('/ajuda/');
	}
	
	private function valida_form(){
		$this->form_validation->set_rules('titulo', 'Título', 'trim|required');
		$this->form_validation->set_rules('texto', 'Texto', 'trim|required');
		$this->form_validation->set_rules('tipo', 'Tipo', 'trim|required');
		$this->form_validation->set_rules('status', 'Status', 'trim|required');
	
		return $this->form_validation->run();
	}
	
}