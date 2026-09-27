<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Acesso extends CI_Controller {

	public function index(){
		
		$usuarios = $this->usuario_model->get_usuarios()->num_rows();
		if($usuarios == 1){
			redirect('/acesso/primeira_vez/');
		}
		
		if($this->session->userdata('logado') == TRUE){
			redirect('/dashboard/');
		}else{
			$this->load->view('acesso/login');
		}
	}
	
	function logar(){
		
        $login = $this->input->post('login');
        $senha = cripto($this->input->post('senha'));
		
		$informacao = $this->acesso_model->get_informacao($login);
		
		foreach($informacao->result() as $usuario){
			
			$id	= $usuario->idusuario;
			
			if ((!empty($id)) && ($id > 0)) {
			
				$numero_acesso  = $usuario->numero_acesso;
				$nivel_acesso   = $usuario->nivel_acesso;
				$senha_banco 	= $usuario->senha;
				$status		 	= $usuario->status;
				$nome_completo 	= $usuario->nome;
				$email		 	= $usuario->email;
				
				$nomec = explode(" ", $nome_completo);
				$nome = $nomec[0];
				
				if(($senha == $senha_banco) && ($status == 'ativo')){
				
					$navegador = $this->verificar_navegador();
					
					$usuario_data = array(
						'nome' 			=> $nome,
						'nome_completo'	=> $usuario->nome,
						'id' 			=> $usuario->idusuario,
						'login'			=> $usuario->login,
						'email'     	=> $usuario->email,
						'imagem' 		=> $usuario->imagem,
						'numero_acesso' => $usuario->numero_acesso,
						'nivel_acesso' 	=> $usuario->nivel_acesso,
						'status' 		=> $usuario->status,
						'confirmado' 	=> $usuario->usuario_confirmado,
						'matricula' 	=> $usuario->matricula,
						'email_senha' 	=> $usuario->email_senha,
						'navegador' 	=> $navegador,
						'logado' 		=> FALSE
					);
					
					$this->session->set_userdata($usuario_data);
					$this->session->set_userdata("usuario", $usuario);
				
				}
			}
		}
		
        if ((!empty($id)) && ($id > 0)) {
			
			if(($senha == $senha_banco) && ($status == 'ativo')){
				
				$this->acesso_model->log($numero_acesso, $id);
				$this->acesso_model->nivel_acesso($nivel_acesso);
				$this->header();
				
				$this->session->set_userdata('logado', TRUE);
				
				if($this->session->userdata('numero_acesso') == 1){
					redirect('/usuario/editar/');
				}
				
				elseif($this->session->userdata('confirmado') == 'nao'){
					redirect('/usuario/vizualizar/');
				}
				
				else{
					redirect('/dashboard/');
				}
				
			}else{
				
				if(($status == 'inativo') && ($senha == $senha_banco)){
					$this->session->set_flashdata('mensagem', 'Entre em contado com o administrador do sistema.');
					$this->session->set_flashdata('controle', 'usuario_inativo');
					
					$this->enviar_email->inativo($email, "contato@programmertime.com", $nome_completo, $login);
				}
				
				else{
					$this->session->set_flashdata('controle', 'senha_incorreta');
					$this->session->set_flashdata('login', $login);
				}
				
				redirect('/acesso/');
			}
			
        }else{
		    $this->session->set_flashdata('controle', 'usuario_incorreto');
			redirect('/acesso/');
		}
	}
	
	public function primeira_vez(){
		$usuarios = $this->usuario_model->get_usuarios()->num_rows();
		if($usuarios == '1'){
			$this->load->view('acesso/primeira_vez');
		}else{
			redirect('/acesso/');
		}
	}
	
	public function cadastrar(){
		$usuarios = $this->usuario_model->get_usuarios()->num_rows();
		if($usuarios == '1'){
		
			if ($this->valida_form()){
			
				$this->usuario_model->post_primeira_vez();
				$idinsert = $this->usuario_model->insert();
				
				if ($idinsert > 0){
					$this->session->set_flashdata('mensagem_sucesso', 'Cadastro efetuado com sucesso.');
					redirect('/acesso/');
				}else{
					$this->session->set_flashdata('mensagem_erro', 'Cadastro <strong>não</strong> efetuado. Tente novamente.');
					redirect('/acesso/primeira_vez/');
				}
				
			}else{
				$this->primeira_vez();	
			}
			
		}else{
			redirect('/acesso/');
		}
	}
	
	private function valida_form(){
	
		$this->form_validation->set_rules('login', 'Nome de Usuário', 'trim|required');
		$this->form_validation->set_rules('senha', 'Senha', 'trim|required|matches[confirmacao_senha]');
		$this->form_validation->set_rules('confirmacao_senha', 'Confirmação de Senha', 'trim');
		$this->form_validation->set_rules('nome', 'Nome', 'trim|required');
		$this->form_validation->set_rules('email', 'E-mail', 'trim|required|valid_email|is_unique[usuario.email]');
		
		return $this->form_validation->run();
		
	}
	
	
	function header(){
		$header = array(
			'projetos' 		=> FALSE,
			'clientes' 		=> FALSE,
			'etapas' 		=> FALSE,
			'financeiro' 	=> FALSE,
			'relatorios' 	=> FALSE,
			'usuarios' 		=> FALSE,
			'configuracao'	=> FALSE,
			'editar_usuario'=> FALSE
		);
		
		$this->session->set_userdata($header);
	}
	
	function verificar_navegador(){
		$useragent = $_SERVER['HTTP_USER_AGENT'];
		$navegador = new stdClass();
	 
		if (preg_match('|MSIE ([0-9].[0-9]{1,2})|',$useragent,$matched)) {
			$browser_version=$matched[1];
			$browser = 'Internet Explorer';
		}
		
		elseif (preg_match( '|Opera/([0-9].[0-9]{1,2})|',$useragent,$matched)) {
			$browser_version=$matched[1];
			$browser = 'Opera';
		}
		
		elseif(preg_match('|Firefox/([0-9\.]+)|',$useragent,$matched)) {
			$browser_version=$matched[1];
			$browser = 'Mozilla Firefox';
		}
		
		elseif(preg_match('|Chrome/([0-9\.]+)|',$useragent,$matched)) {
			$browser_version=$matched[1];
			$browser = 'Google Chrome';
		}
		
		elseif(preg_match('|Safari/([0-9\.]+)|',$useragent,$matched)) {
			$browser_version=$matched[1];
			$browser = 'Safari';
		}
		
		else {
			$browser_version = '';
			$browser= 'Outro';
		}
		
		$navegador->useragent = $useragent;
		$navegador->browser = $browser;
		$navegador->version  = $browser_version;
		$navegador->browser_version  = $browser . ' - ' . $browser_version;
		
		return $navegador;
	}
	
	function sair(){
        $this->session->sess_destroy();
        redirect('/acesso/');
    }
	
}