<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Imagem extends CI_Controller {
	
    public function __construct() {
        parent::__construct();
    }
	
	public function index() {
        $this->visualizar();
    }
	
	public function visualizar($idimagem = 0){
		$imagem = $this->imagem_model->get_imagem($idimagem);
		
		if($imagem->num_rows != 1){
			$this->session->set_flashdata('mensagem_atencao', lang('msg_imagem_inexistente'));
			redirect('/projeto/lista/');
		}
		
		$caminho_imagem = 'assets/images/projetos/';
		$comentarios = $this->imagem_model->get_comentarios($idimagem);
		
		$dados['imagem'] = $imagem->row();
		$dados['comentarios'] = $comentarios;
		$dados['caminho_imagem'] = $caminho_imagem;
		
		$dados['view'] = $this->load->view('imagem/visualizar', $dados, TRUE);
		$this->load->view('includes/interna', $dados);

	}
	
	public function cadastrar(){
		$crop_imagem = new stdClass();
		$crop_imagem->origem 	= "assets.images.temp.";
		$crop_imagem->destino	= "assets.images.projetos";
		$crop_imagem->largura 	= "1000";
		$crop_imagem->altura 	= "1000";
		$dados['crop_imagem'] 	= $crop_imagem;
		
		#$dados["post_imagens"] = $this->imagem_model->post_imagens();
		
		$dados['view'] = $this->load->view('imagem/cadastrar', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	public function editar(){
		$dados['view'] = $this->load->view('imagem/editar', array(), TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	public function inserir_imagem(){
		 if ($this->valida_form_imagem()){
		
            $this->imagem_model->post_imagem();
			$idinsert = $this->imagem_model->inserir_imagem();
			
            if ($idinsert > 0){
				$this->session->set_flashdata('mensagem_sucesso', lang('msg_imagem_cadastro_sucesso'));
                redirect('/imagem/visualizar/'. $idinsert);
            }
			$this->session->set_flashdata('mensagem_erro', lang('msg_imagem_cadastro_erro'));
			redirect('/imagem/cadastrar/');
        }
		$this->visualizar();
	}
	
	public function inserir_comentario(){
		 if ($this->valida_form_comentario()){
		
            $this->imagem_model->post_comentario();
			$idinsert = $this->imagem_model->inserir_comentario();
			
            if ($idinsert > 0){
				$this->session->set_flashdata('mensagem_sucesso', lang('msg_comentario_cadastro_sucesso'));
                redirect('/imagem/visualizar/'. $this->input->post('idimagem'));
            }
			$this->session->set_flashdata('mensagem_erro', lang('msg_comentario_cadastro_erro'));
			redirect('/imagem/visualizar/'. $this->input->post('idimagem'));
			
        }
		$this->visualizar();
	}
	
	public function atualizar_imagem($idimagem = 0){
        if (!empty($idimagem)) {
			if ($this->valida_form_imagem()){
				
				$this->imagem_model->post_imagem();	
			    $atualizado = $this->imagem_model->atualizar_imagem($idimagem);				
				
				if ($atualizado) {
				    $this->session->set_flashdata('mensagem_sucesso', lang('msg_imagem_editar_sucesso'));
					redirect('/imagem/visualizar/'.$idimagem);
				}
				$this->session->set_flashdata('mensagem_erro', lang('msg_imagem_editar_erro'));
				redirect('/imagem/visualizar/'.$idimagem);
			}
			$this->editar($idimagem);
	    }
	}
	
	public function deletar_imagem($idprojeto = 0, $idimagem = 0){
		$excluido = $this->imagem_model->deletar_imagem($idimagem);
		
		if ($excluido){
			$this->session->set_flashdata('mensagem_sucesso', lang('msg_imagem_excluir_sucesso'));
			redirect('/projeto/visualizar/'. $idprojeto);
		}
		$this->session->set_flashdata('mensagem_erro', lang('msg_imagem_excluir_erro'));
		redirect('/imagem/visualizar/'. $idimagem);
	}
	
	public function deletar_comentario($idimagem = 0, $idcomentario = 0){
		$excluido = $this->imagem_model->deletar_comentario($idcomentario);
		
		if ($excluido){
			$this->session->set_flashdata('mensagem_sucesso', lang('msg_comentario_excluir_sucesso'));
			redirect('/imagem/visualizar/'. $idimagem);
		}
		$this->session->set_flashdata('mensagem_erro', lang('msg_comentario_excluir_erro'));
		redirect('/imagem/visualizar/'. $idimagem);	
	}
	
	private function valida_form_imagem(){
		$this->form_validation->set_rules('titulo', lang('lbl_titulo'), 'trim|required');
		$this->form_validation->set_rules('descricao', lang('lbl_descricao'), 'trim|required');
		
		return $this->form_validation->run();
	}
	
	private function valida_form_comentario(){
		$this->form_validation->set_rules('idimagem', lang('lbl_imagem'), 'trim|required');
		$this->form_validation->set_rules('comentario', lang('lbl_comentario'), 'trim|required');
		
		return $this->form_validation->run();
	}
}