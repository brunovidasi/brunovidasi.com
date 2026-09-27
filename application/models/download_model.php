<?php  

class Download_model extends CI_Model {

    function __construct() {
        parent::__construct();
    }
	
	function especificacoes_tecnicas(){
	
		$especificações = 'PROGRAMMER TIME - Gerenciamento de Projetos\n
________________________________________________________________________________________\n\n

http://www.programmertime.com/ \n\n


Especificações Técnicas \n
________________________________________________________________________________________\n\n

Versão do Sistema Atual: 1.0.0.0 \n
Versão do CodeIgniter: 2.1.4 \n
Data de Lançamento: - \n
Data de Atualização: - \n


Informações de PHP \n
________________________________________________________________________________________\n\n

Versão do PHP: 5.3.28 \n
Outras versões do PHP podem causar incompatibilidade com o sistema. \n\n


Informações de Banco de Dados \n
________________________________________________________________________________________\n\n

Database: MySQL 5.5.36-cll \n
SGBD: PHPMyAdmin \n\n


Informações de Desenvolvimento \n
________________________________________________________________________________________\n\n

Desenvolvedor WEB: Bruno Vieira - bruno@programmertime.com - www.brunovidasi.com \n
Desenvolvedor ANDORID: Filipe Moreira - filipe@programmertime.com \n';
		
		$título = 'Programmer Time - Especificações Técnicas.txt';

		force_download($título, $especificações);
	}
	
	
	function informacao_suporte(){
	
		$useragent = $_SERVER['HTTP_USER_AGENT'];
	 
		if (preg_match('|MSIE ([0-9].[0-9]{1,2})|',$useragent,$matched)) {
			$browser_version=$matched[1];
			$browser = 'IE';
		}elseif (preg_match( '|Opera/([0-9].[0-9]{1,2})|',$useragent,$matched)) {
			$browser_version=$matched[1];
			$browser = 'Opera';
		}elseif(preg_match('|Firefox/([0-9\.]+)|',$useragent,$matched)) {
			$browser_version=$matched[1];
			$browser = 'Firefox';
		}elseif(preg_match('|Chrome/([0-9\.]+)|',$useragent,$matched)) {
			$browser_version=$matched[1];
			$browser = 'Chrome';
		}elseif(preg_match('|Safari/([0-9\.]+)|',$useragent,$matched)) {
			$browser_version=$matched[1];
			$browser = 'Safari';
		}else {
			$browser_version = '';
			$browser= 'Outro';
		}

		$informações = 'Informações de Usuário\n
________________________________________________________________________________________\n\n

Nome: '. $this->session->userdata('nome_completo') .'\n
Login: '. $this->session->userdata('login') .' \n
ID: '. $this->session->userdata('id') .' \n
E-mail: '. $this->session->userdata('email') .' \n
Matrícula: '. $this->session->userdata('matricula') .' \n
Imagem: '. $this->session->userdata('imagem') .' \n\n

Número de Acessos ao Sistema: '. $this->session->userdata('numero_acesso') .' \n
Nível de Acesso: '. $this->session->userdata('nivel_acesso') .' \n
Status: '. $this->session->userdata('status') .' \n
Confirmado: '. $this->session->userdata('confirmado') .' \n
Confirmação: '. $this->session->userdata('email_senha') .' \n
Logado: '. $this->session->userdata('logado') .' \n\n


Dados da Sessão \n
________________________________________________________________________________________\n\n

SESSION ID: '. $this->session->userdata('session_id') .' \n
USER AGENT: '. $this->session->userdata('user_agent') .' \n
LAST ACTIVITY: '. $this->session->userdata('last_activity') .' \n
LAST VISIT: '. $this->session->userdata('last_visit') .' \n

Browser: '.  $browser . ' - ' . $browser_version .' \n
IP: '. $this->session->userdata('ip_address') .' \n

Informações do Sistema \n
________________________________________________________________________________________ \n\n

Versão do Programmer Time: 1.0.0.0 \n
Versão do PHP: '. phpversion() .'';
		 
		$título = 'Informações de Sessão - '. $this->session->userdata('login') .'.txt';

		force_download($título, $informações);
	}

}