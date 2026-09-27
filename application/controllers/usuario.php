<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Usuario extends CI_Controller {
	
	public function index(){
	    $this->lista();
	}
	
	public function lista(){

		$termo = $this->input->post('termo');
		$this->session->set_userdata('termo', $termo);
		
		$inicio = (!$this->uri->segment("3")) ? 0 : $this->uri->segment("3");
		$maximo = 15;
		
		$config['base_url']    	= site_url('usuario/lista/');
		$config['total_rows'] 	= $this->usuario_model->get_usuarios($termo)->num_rows;
		$config['per_page']    	= $maximo;
		$config['uri_segment'] 	= 3;
		
		$this->pagination->initialize($config);
		
		$dados["termo"]    		= $termo;
		$dados["paginacao"]    	= $this->pagination->create_links();
		$dados['usuarios']		= $this->usuario_model->get_usuarios_lista($maximo, $inicio, $termo);
		
		$dados['view'] = $this->load->view('usuario/lista', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}

	public function cargo(){
		$inicio = (!$this->uri->segment("3")) ? 0 : $this->uri->segment("3");
		$maximo = 15;
		
		$config['base_url']    	= site_url('usuario/cargo/');
		$config['total_rows'] 	= $this->usuario_model->get_usuarios()->num_rows;
		$config['per_page']    	= $maximo;
		$config['uri_segment'] 	= 3;
		
		$this->pagination->initialize($config);
		
		$dados["paginacao"]    	= $this->pagination->create_links();
		$dados['usuarios']		= $this->usuario_model->get_usuarios_lista($maximo, $inicio);
		$dados['niveis_acesso']	= $this->usuario_model->get_niveis_acesso();
		
		$dados['view'] = $this->load->view('usuario/lista_cargo', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	public function visualizar($id = ""){
		if(empty($id)) $id = $this->session->userdata('id');

		if($id == 1){
			$idusuario = $this->session->userdata('id');

			if($idusuario != 1)
				redirect('/usuario/lista/');
		}
		
		$usuario = $this->usuario_model->get_usuario($id);
		
		if($usuario->num_rows() == 0){
			$this->session->set_flashdata('mensagem_atencao', lang('msg_usuario_inexistente'));
			redirect('/usuario/lista/');
		}
		
		$dados['dados_usuario'] = $usuario;
		$dados['usuario'] 		= $usuario->row();
		$dados['projetos'] 		= $this->usuario_model->get_projetos($id);
		$dados['etapas'] 		= $this->usuario_model->get_etapas($id);

		$dados['horas_trabalhadas']		= $this->usuario_model->get_horas_trabalhadas($id);
		$dados['projetos_envolvidos']	= $this->usuario_model->get_projetos_envolvido($id);
		$dados['etapa_aberta'] 			= $this->etapa_model->etapa_aberta($id);
		
		$dados['view'] = $this->load->view('usuario/visualizar', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	public function cadastrar(){
		$dados['cargos'] = $this->usuario_model->get_niveis_acesso();
		
		$dados['view'] = $this->load->view('usuario/cadastrar', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	public function editar($id = ""){

		if(empty($id)) $id = $this->session->userdata('id');

		if($id == 1){
			$idusuario = $this->session->userdata('id');
			
			if($idusuario != 1)
				redirect('/usuario/lista/');
		}
		
		$usuario = $this->usuario_model->get_usuario($id);
		
		if($usuario->num_rows() == 0){
			$this->session->set_flashdata('mensagem_atencao', lang('msg_usuario_inexistente'));
			redirect('/usuario/lista/');
		}
		
		$crop = new stdClass();
		$crop->origem 	= "assets.images.temp.";
		$crop->destino 	= "assets.images.usuarios";
		$crop->largura 	= "200";
		$crop->altura 	= "200";

		$dados['crop']	 = $crop;
		$dados['cargos'] = $this->usuario_model->get_niveis_acesso();
		$dados['perfil'] = $usuario->row();
		
		$dados['view'] = $this->load->view('usuario/editar', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	public function desativar_usuario(){
		$dados['idusuario'] = $this->session->userdata('id');
		
		$dados['view'] = $this->load->view('usuario/desativar', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	public function insert(){
		if($this->valida_form('insert')){
		
			$dados = $this->usuario_model->post('insert');
			
			if($dados){
				$idinsert = $this->usuario_model->insert($dados);
				
				if ($idinsert > 0){
					$this->session->set_flashdata('mensagem_sucesso', lang('msg_usuario_cadastro_sucesso'));
					redirect('/usuario/');
				}
			}
			$this->session->set_flashdata('mensagem_erro', lang('msg_usuario_cadastro_erro'));
			redirect('/usuario/cadastrar/');
		}
		$this->cadastrar();		
	}
	
	public function update($id = 0){
		$id = (int) $id;
		if($id > 0){
			if($this->valida_form('update', $id)){
				
				$dados = $this->usuario_model->post('update');
				if($dados){
					$atualizado = $this->usuario_model->update($id, $dados);				
					
					if($atualizado){
						$imagens = $this->usuario_model->salva_imagens($id);
						
						$this->session->set_flashdata('mensagem_sucesso', lang('msg_usuario_editar_sucesso'));
						redirect('/usuario/visualizar/'.$id);
					}
				}
				$this->session->set_flashdata('mensagem_erro', lang('msg_usuario_editar_erro'));
				redirect('/usuario/editar/'.$id);
			}
			$this->editar($id);
			return;
		}
		redirect('/usuario/');
	}
	
	public function muda_status($status = 'ativo', $id = 0){

		$id = (int) $id;

		if($status != 'ativo' AND $status != 'inativo')
			redirect('/usuario/');

		if($id > 0){

			if($id == 1){
				$idusuario = $this->session->userdata('id');
				
				if($idusuario != 1)
					redirect('/usuario/lista/');
			}
			
			$mudou = $this->usuario_model->muda_status($id, $status);

			if($mudou)
				$this->session->set_flashdata('mensagem_sucesso', lang('msg_usuario_editar_sucesso'));
			else
				$this->session->set_flashdata('mensagem_erro', lang('msg_usuario_editar_erro'));
			
			redirect('/usuario/visualizar/'.$id);
			// redirect('/usuario/');
		}
		
		$this->session->set_flashdata('mensagem_erro', lang('msg_usuario_editar_erro'));
		redirect('/usuario/');
	}
	
	private function valida_form($method = 'insert', $id = 0){

		if($method == "insert"){
			$this->form_validation->set_rules('login', 			lang('lbl_nome_usuario'), 	'trim|required|is_unique[usuario.login]');
			$this->form_validation->set_rules('senha', 			lang('lbl_senha'), 			'trim|required|matches[confirmacao_senha]');
			$this->form_validation->set_rules('email', 			lang('lbl_email'), 			'trim|required|valid_email|is_unique[usuario.email]');
			$this->form_validation->set_rules('nivel_acesso', 	lang('lbl_nivel_acesso'), 	'trim|required');
		}

		elseif($method == "update"){

			$usuario = $this->usuario_model->get_usuario($id)->row();

			$post = new stdClass();
			$post->login = $this->input->post('login', TRUE);
			$post->email = $this->input->post('email', TRUE);

			if($usuario->login != $post->login)
				$this->form_validation->set_rules('login', lang('lbl_nome_usuario'), 'trim|required|is_unique[usuario.login]');
			else
				$this->form_validation->set_rules('login', lang('lbl_nome_usuario'), 'trim|required');

			$this->form_validation->set_rules('senha', lang('lbl_senha'), 'trim|matches[confirmacao_senha]');

			if($usuario->email != $post->email)
				$this->form_validation->set_rules('email', lang('lbl_email'), 'trim|required|valid_email|is_unique[usuario.email]');
			else
				$this->form_validation->set_rules('email', lang('lbl_email'), 'trim|required|valid_email');

			$this->form_validation->set_rules('status');
		}
		
		$this->form_validation->set_rules('nivel_acesso', 		lang('lbl_nivel_acesso'), 		'trim|required');
		$this->form_validation->set_rules('confirmacao_senha', 	lang('lbl_confirma_senha'), 	'trim');
		$this->form_validation->set_rules('nome', 				lang('lbl_nome'),			 	'trim|required');
		$this->form_validation->set_rules('data_nascimento');
		$this->form_validation->set_rules('matricula');
		$this->form_validation->set_rules('rg');
		$this->form_validation->set_rules('cpf', lang('lbl_cpf'), 'callback_valida_cpf');
		
		return $this->form_validation->run();
	}
	
	public function envia_email_confirmacao(){
		$this->enviar_email->confirmar_cadastro($this->session->userdata('email'), $this->session->userdata('nome_completo'), $this->session->userdata('login'), "*****", $this->session->userdata('email_senha'));
		
		$this->session->set_flashdata('mensagem_atencao', lang('msg_email_confirmacao'));
		redirect('/dashboard/');
	}
	
	public function confirma_email($senha = ''){
		$id = (int) $this->usuario_model->get_senha_email($senha);
		
		if(!empty($id) && $id > 0){
			$confirmado = $this->usuario_model->confirma_email($id);
			if($confirmado){
				$this->session->set_flashdata('mensagem', lang('msg_usuario_confirmado'));
				redirect('/acesso/sair/');
			}
		}
		$this->session->set_flashdata('mensagem', lang('msg_usuario_nao_confirmado'));
		redirect('/acesso/sair/');
	}
	
	public function verificar_nome_existente(){
		$login_novo = $this->input->post('nome_novo');
		$login_atual = $this->input->post('nome_atual');
		
		if($login_atual == $login_novo){
			$existe_usuario = 0;
		}else{
			$usuario = $this->usuario_model->get_usuario_login($login_novo);
			$existe_usuario = $usuario->num_rows();
		}
		echo $existe_usuario; 
	}

	public function verificar_email_existente(){
		$email_novo = $this->input->post('email_novo');
		$email_atual = $this->input->post('email_atual');
		
		if($email_atual == $email_novo){
			$existe_usuario = 0;
		}else{
			$usuario = $this->usuario_model->get_usuario_email($email_novo);
			$existe_usuario = $usuario->num_rows();
		}
		echo $existe_usuario; 
	}
	
	public function valida_cpf(){
		$cpfpost = $this->input->post('cpf');
		$cpfsemponto = str_replace('.', '', $cpfpost);
		
		$cpf = str_replace('-', '', $cpfsemponto);
		
		if(empty($cpf)) return true;
		
		$cpf = preg_replace('/[^0-9]/', '', $cpf);
		$cpf = str_pad($cpf, 11, '0', STR_PAD_LEFT);
		
		if(strlen($cpf) != 11){
			$this->form_validation->set_message('valida_cpf', lang('msg_cpf_invalido'));
			return false;
		}elseif ($cpf == '00000000000' || $cpf == '11111111111' || $cpf == '22222222222' || $cpf == '33333333333' || $cpf == '44444444444' || $cpf == '55555555555' || $cpf == '66666666666' || $cpf == '77777777777' || $cpf == '88888888888' || $cpf == '99999999999'){
			$this->form_validation->set_message('valida_cpf', lang('msg_cpf_invalido'));
			return false;
		}else{
			for ($t = 9; $t < 11; $t++){
				for ($d = 0, $c = 0; $c < $t; $c++){
					$d += $cpf[$c] * (($t + 1) - $c);
				}
				$d = ((10 * $d) % 11) % 10;
				if ($cpf[$c] != $d){
					$this->form_validation->set_message('valida_cpf', lang('msg_cpf_invalido'));
					return false;
				}
			}
			return true;
		}
	}
}