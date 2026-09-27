<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cliente extends CI_Controller {

	public function index(){
		$this->lista();
	}
	
	public function lista(){

		$termo = $this->input->post('termo');
		$this->session->set_userdata('idcliente', "");
		$this->session->set_userdata('c_termo', $termo);

		$inicio = (!$this->uri->segment("3")) ? 0 : $this->uri->segment("3");
		$maximo = 10;
		
		$config['base_url']    	= site_url('cliente/lista/');
		$config['total_rows'] 	= $this->cliente_model->get_clientes($termo)->num_rows;
		$config['per_page']    	= $maximo;
		$config['uri_segment'] 	= 3;
		
		$this->pagination->initialize($config);
		
		$dados["paginacao"]    	= $this->pagination->create_links();
		$dados['clientes']		= $this->cliente_model->get_clientes_lista($maximo, $inicio, $termo);
		
		$dados['view'] = $this->load->view('cliente/lista', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	public function visualizar($id = ""){
		
		$id = (int) $id;

		if(empty($id)){
			$this->session->set_flashdata('mensagem_atencao', lang('msg_cliente_inexistente'));
			redirect('/cliente/lista/');
		}
		
		$cliente = $this->cliente_model->get_cliente($id);
		$dados['dados_cliente'] 	= $cliente;
		$dados['cliente'] 			= $cliente->row();
		$dados['projetos'] 			= $this->cliente_model->get_projetos($id);
		$dados['projetos_ativos'] 	= $this->cliente_model->get_projetos_ativos($id);
		$dados['horas_projetos'] 	= $this->cliente_model->get_horas_projetos($id);
		
		if($cliente->num_rows == 0){
			$this->session->set_flashdata('mensagem_atencao', lang('msg_cliente_inexistente'));
			redirect('/cliente/lista/');
		}
		
		$dados['view'] = $this->load->view('cliente/visualizar', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	public function cadastrar(){
		$dados['view'] = $this->load->view('cliente/cadastrar', array(), TRUE);
		$this->load->view('includes/interna', $dados);
	}

	public function editar($id = ""){

		$id = (int) $id;

		if(empty($id)){
			$this->session->set_flashdata('mensagem_atencao', lang('msg_cliente_inexistente'));
			redirect('/cliente/lista/');
		}

		$cliente = $this->cliente_model->get_cliente($id);
		
		if($cliente->num_rows == 0){
			$this->session->set_flashdata('mensagem_atencao', lang('msg_cliente_inexistente'));
			redirect('/cliente/lista/');
		}
		
		$dados['cliente'] = $cliente->row();
		
		$dados['view'] = $this->load->view('cliente/editar', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	public function insert(){
		if($this->valida_form('insert')){

			$dados = $this->cliente_model->post('insert');

			if($dados){
				$idinsert = $this->cliente_model->insert($dados);
				
				if($idinsert > 0){
					$this->session->set_flashdata('mensagem_sucesso', lang('msg_cliente_cadastro_sucesso'));
					redirect('/cliente/');
				}
			}

			$this->session->set_flashdata('mensagem_erro', lang('msg_cliente_cadastro_erro'));
			redirect('/cliente/cadastrar/');
		}
		$this->cadastrar();
	}

	public function update($id = 0){

		$id = (int) $id;

		if(!empty($id)) {
			if ($this->valida_form('update', $id)){
				
				$dados = $this->cliente_model->post('update');

				if($dados){
					$atualizado = $this->cliente_model->update($id, $dados);
					
					if($atualizado) {
						$this->session->set_flashdata('mensagem_sucesso', lang('msg_cliente_editar_sucesso'));
						redirect('/cliente/');
					}
				}

				$this->session->set_flashdata('mensagem_erro', lang('msg_cliente_editar_erro'));
				redirect('/cliente/editar/'.$id);
			}
		}
		$this->editar($id);
	}

	public function delete($idcliente = 0){
		$excluido = $this->cliente_model->delete($idcliente);
		
		if($excluido){
			$this->session->set_flashdata('mensagem_sucesso', lang('msg_cliente_excluir_sucesso'));
			redirect('/cliente/');
		}
		$this->session->set_flashdata('mensagem_erro', lang('msg_cliente_excluir_erro'));
		redirect('/cliente/');	
	}

	public function muda_status($status = 'ativo', $id = 0){

		$id = (int) $id;

		if($status != 'ativo' AND $status != 'inativo')
			redirect('/cliente/');

		if($id > 0){

			$mudou = $this->cliente_model->muda_status($id, $status);

			if($mudou){

				if($status == 'inativo')
					$this->cliente_model->cancelar_projetos($id);

				$this->session->set_flashdata('mensagem_sucesso', lang('msg_cliente_editar_sucesso'));
			}
			else
				$this->session->set_flashdata('mensagem_erro', lang('msg_cliente_editar_erro'));
			
			redirect('/cliente/visualizar/'.$id);
			// redirect('/cliente/');
		}
		
		$this->session->set_flashdata('mensagem_erro', lang('msg_cliente_editar_erro'));
		redirect('/cliente/');
	}

	private function valida_form($method = 'insert', $id = 0){

		$id = (int) $id;

		if($method == 'update'){

			$cliente = $this->cliente_model->get_cliente($id)->row();

			$post = new stdClass();
			$post->nome = $this->input->post('nome', TRUE);
			$post->email = $this->input->post('email', TRUE);
			$post->razao_social = $this->input->post('razao_social', TRUE);
			$post->cpf = $this->input->post('cpf', TRUE);
			$post->cnpj = $this->input->post('cnpj', TRUE);

			if($cliente->nome != $post->nome)
				$this->form_validation->set_rules('nome', lang('lbl_nome'), 'trim|required|is_unique[cliente.nome]');
			else
				$this->form_validation->set_rules('nome', lang('lbl_nome'), 'trim|required');

			if($cliente->email != $post->email)
				$this->form_validation->set_rules('email', lang('lbl_email'), 'trim|required|valid_email|is_unique[cliente.email]');
			else
				$this->form_validation->set_rules('email', lang('lbl_email'), 'trim|required|valid_email');

			$this->form_validation->set_rules('website');
			$this->form_validation->set_rules('telefone');
			$this->form_validation->set_rules('celular');

			if($cliente->razao_social != $post->razao_social)
				$this->form_validation->set_rules('razao_social', lang('lbl_razao_social'), 'is_unique[cliente.razao_social]');
			else
				$this->form_validation->set_rules('razao_social');

			if($cliente->cpf != $post->cpf)
				$this->form_validation->set_rules('cpf', lang('lbl_cpf'), 'is_unique[cliente.cpf]');
			else
				$this->form_validation->set_rules('cpf');

			if($cliente->cnpj != $post->cnpj)
				$this->form_validation->set_rules('cnpj', lang('lbl_cnpj'), 'is_unique[cliente.cnpj]');
			else
				$this->form_validation->set_rules('cnpj');
		}

		else{
			$this->form_validation->set_rules('nome', 	lang('lbl_nome'), 	'trim|required|is_unique[cliente.nome]');
			$this->form_validation->set_rules('email', 	lang('lbl_email'), 	'trim|required|valid_email|is_unique[cliente.email]');
			$this->form_validation->set_rules('website');
			$this->form_validation->set_rules('telefone');
			$this->form_validation->set_rules('celular');
			$this->form_validation->set_rules('razao_social', 	lang('lbl_razao_social'), 	'is_unique[cliente.razao_social]');
			$this->form_validation->set_rules('cpf', 			lang('lbl_cpf'), 			'is_unique[cliente.cpf]');
			$this->form_validation->set_rules('cnpj', 			lang('lbl_cnpj'), 			'is_unique[cliente.cnpj]');
		}
		
		$this->form_validation->set_rules('nome_contato');
		$this->form_validation->set_rules('email_contato');
		$this->form_validation->set_rules('telefone_contato');
		$this->form_validation->set_rules('endereco_cep');
		$this->form_validation->set_rules('endereco');
		$this->form_validation->set_rules('endereco_numero');
		$this->form_validation->set_rules('endereco_complemento');
		$this->form_validation->set_rules('endereco_bairro');
		$this->form_validation->set_rules('endereco_cidade');
		$this->form_validation->set_rules('endereco_estado');
		
		return $this->form_validation->run();
	}
}