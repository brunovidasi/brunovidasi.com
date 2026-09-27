<?php  

class Enviar_email extends CI_Model {

    function __construct() {
        parent::__construct();
    }
	
	public function mail($from, $to, $nome, $mensagem, $assunto){
		$this->email->clear();
		$this->email->initialize(array("mailtype" => "html"));
		
		$this->email->from($from, $nome);
		$this->email->to($to); 

		$this->email->subject($assunto);
		$this->email->message($mensagem);	

		return $this->email->send();
	}
	
	function confirmar_cadastro($email, $nome, $login, $senha, $senha_confirmacao){
		
		$mensagem = "
			Seja bem vindo ao sistema ProgrammerTime!
			<br><br>
			Para confirmar seu e-mail e começar a usar o ProgrammerTime, clique no link abaixo, ou copie e cole na barra de endereços do seu navegador.
			<br><br>
				<hr>
				<span><strong><a href='". base_url('acesso/confirma_email/'.$senha_confirmacao) ."'>". base_url('acesso/confirma_email/'.$senha_confirmacao) ."</a></strong></span>
				<hr>
			<br><br>
			Suas Informações:
			<br><br>
			<b>Nome:</b> ". $nome ."
			<br>
			<b>Login:</b> ". $login ."
			<br>
			<b>Senha:</b> ". $senha ."
			
			<br><br><b>Este email foi gerado automaticamente pelo sistema, não deve ser respondido.</b>
		";
		
		$this->email->clear();
		$this->email->initialize(array("mailtype" => "html"));
		$this->email->from('naoresponda@programmertime.com', 'ProgrammerTime');
		$this->email->to($email);
		$this->email->subject('Bem Vindo ao Programmer Time!');
		$this->email->message($mensagem);

		return $this->email->send();
	}
	
	function inativo($email_usuario, $email_gerente, $nome, $login){
		
		$mensagem_gerente = "
			Um usuário inativo tentou entrar no sistema. Verifique se há necessidade de reativá-lo.
			<br><br>
			Informações do Usuário:
			<br><br>
			<b>Nome:</b> ". $nome ."
			<br>
			<b>Login:</b> ". $login ."
			
			<br><br>
			Lembre-se que para reativar o usuário, você deve acessar o sistema Programmer Time ou entrar em contato com o administrador.
			<br><br><b>Este email foi gerado automaticamente pelo sistema, não deve ser respondido.</b>
		";
		
		$mensagem_usuario = "
			Olá ". $nome .", <br><br>
			
			Você tentou logar no Programmer Time como <b>". $login ."</b>, mas a sua conta está inativa. Já foi enviado um e-mail para o Administrador para caso haja necessidade de reativá-lo.
			<br>
			Se possível, entre em contato com o seu Gerente.
			<br><br><b>Este email foi gerado automaticamente pelo sistema, não deve ser respondido.</b>
			<br>
		";
		
		$this->email->clear();
		$this->email->initialize(array("mailtype" => "html"));
		$this->email->from('naoresponda@programmertime.com', 'Programmer Time');
		$this->email->to($email_gerente);
		$this->email->subject('[Não Responda] Usuário Inativo');
		$this->email->message($mensagem_gerente);	

		$this->email->send();
		
		
		$this->email->clear();
		$this->email->initialize(array("mailtype" => "html"));
		$this->email->from('naoresponda@programmertime.com', 'Programmer Time');
		$this->email->to($email_usuario);
		$this->email->subject('[Não Responda] Usuário Inativo');
		$this->email->message($mensagem_usuario);

		return $this->email->send();
		
	}
	
	function relatorio_cliente(){
		
		$idprojeto 		= $this->input->post('idprojeto');
		$nome_gerente	= $this->input->post('nome_gerente');
		$email_gerente	= $this->input->post('email_gerente');
		$email_cliente 	= $this->input->post('email_cliente');
		$assunto		= $this->input->post('assunto');
		$mensagem		= $this->input->post('mensagem');
		
		$email = new stdClass();
		
		$email->idprojeto 	= $idprojeto;
		$email->assunto 	= $assunto;
		$email->mensagem 	= $mensagem;
		$email->de_nome 	= $nome_gerente;
		$email->de_email 	= $email_gerente;
		$email->para_email 	= $email_cliente;
		$email->copia 		= $email_gerente;
		$email->data 		= date('Y-m-d H:i:s');
		
		if ($this->db->insert("projeto_email", $email)){
		
			$this->email->clear();
			$this->email->initialize(array("mailtype" => "html"));
			
			$this->email->from($email_gerente, $nome_gerente);
			$this->email->to($email_cliente);
			
			$this->email->cc($email_gerente);
			$this->email->reply_to($email_gerente, $nome_gerente);
			
			$this->email->subject($assunto);
			$this->email->message($mensagem);
			
		    return $this->email->send();
		
		}else{
            return FALSE;
		}

	}

}