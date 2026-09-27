<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Licenca extends CI_Controller {

	public function index(){
		$this->enviar_dados();
	}

	public function verificar(){
		return false;
	}
	
	public function enviar_dados($senha_master = '', $completo = false){
		
		if($senha_master == PT_USER){

			$dados = new stdClass();

			# Dados do CodeIgniter
			$dados->ci_version 		= CI_VERSION;
			$dados->ci_environment	= ENVIRONMENT;
			$dados->ci_basepath		= BASEPATH;
			$dados->ci_sysdir		= SYSDIR;

			# Dados de Licença do Programmer Time
			$dados->versao			= PT_VERSION;			# versão
			$dados->tipo			= PT_TYPE;				# trial, free, ptime, vitalicy, testing, production
			$dados->usuario			= PT_USER;				# usuário do programmertime
			$dados->licenca			= PT_LINCENSE_KEY;		# licença de uso no caso de vitalício
			$dados->senha_acesso	= PT_ACCESS_PASS;		# senha de acesso ao programmertime
			$dados->verificacao		= PT_VERIFICATION;		# senha de verificação

			# Dados de configuração do PTime
			$dados->bd_host  	= DB_HOST_P;
			$dados->bd_user  	= DB_USER_P;
			$dados->bd_pass  	= DB_PASS_P;
			$dados->bd_table	= DB_DATABASE_P;
			$dados->url 	 	= base_url();

			# Dados de Projetos e Usuários
			$dados->nm_usuarios 			= $this->usuario_model->get_usuarios()->num_rows();
			$dados->num_usuarios_ativos 	= $this->usuario_model->get_usuarios_ativos()->num_rows();
			$dados->num_usuarios_inativos	= $dados->nm_usuarios - $dados->num_usuarios_ativos;
			$dados->num_clientes			= $this->cliente_model->get_clientes()->num_rows();
			$dados->num_projetos			= $this->projeto_model->get_projetos()->num_rows();

			# Dados da Empresa que contratou o PTime e seu Representante
			$dados->empresa			= $this->empresa_model->get_empresa()->row();
			$dados->responsavel 	= $this->usuario_model->get_usuario($dados->empresa->idrepresentante)->row();

			# Dados dos usuários, projetos e clientes
			if($completo){
				$dados->usuarios = $this->usuario_model->get_usuarios()->result();
				$dados->projetos = $this->projeto_model->get_projetos()->result();
				$dados->clientes = $this->cliente_model->get_clientes()->result();
			}

			##################### Licença #####################

			$dados->serial_key = md5(PT_VERSION.PT_TYPE.PT_USER.PT_LINCENSE_KEY.PT_ACCESS_PASS.PT_VERIFICATION);
			$dados->complete_key = cripto(PT_VERSION.PT_TYPE.PT_USER.PT_LINCENSE_KEY.PT_ACCESS_PASS.PT_VERIFICATION);
			$dados->encription_key = md5($this->config->config['encryption_key']);

			###################################################

			# Data da atualização
			$dados->data = date('Y-m-d H:i:s');

			return json_encode($dados);
		}

		else return 'acesso indevido.';
		
	}

	public function verificar_licenca($license_key = 'PT_4PWZZ-JKFNI-IS108J-KW521T-BK0OP_PT_*41211*', $senha_acesso = 'PT_7286_6075_6903_5*', $data = '2014-10-21'){
		
		$licenca_valida = 'true';

		if(strlen($license_key) != 45) $licenca_valida = 'não tem 45 caracteres';
		
		list($l_tipo, $l_serial, $l_pt, $l_vdata) = explode('_', $license_key);

		list($s_pt, $s_r1, $s_r2, $s_r3, $s_tipo) = explode('_', $senha_acesso);

		list($ano, $mes, $dia) = explode('-', $data);

		if($s_tipo == '9*') $tipo_s = "trial";
		elseif($s_tipo == '7*') $tipo_s = "free";
		elseif($s_tipo == '5*') $tipo_s = "ptime";
		elseif($s_tipo == '0*') $tipo_s = "vitalicy";
		else{
			$tipo_s = null;
			$licenca_valida = 'não tem tipo definido na licença';
		}

		if($l_tipo == 'TR') $tipo_l = "trial";
		elseif($l_tipo == 'FR') $tipo_l = "free";
		elseif($l_tipo == 'PT') $tipo_l = "ptime";
		elseif($l_tipo == 'VT') $tipo_l = "vitalicy";
		else{
			$tipo_l = null;
			$licenca_valida = 'não tem tipo definido na senha';
		} 

		if($tipo_l != $tipo_s) $licenca_valida = 'tipo da senha diferente da licença';

		echo $licenca_valida;

	}

	public function gerar_licenca($usuario = 'bvidasi', $tipo = 'ptime', $versao = '1.0'){

		$dados = new stdClass();

		$dados->data = date('Y-m-d');
		$dados->usuario = $usuario;
		$dados->tipo = $tipo;
		$dados->versao = $versao;
		
		# Prefixo definindo a versão

		if($tipo == 'trial') 	$license_key = "TR_";
		if($tipo == 'free')  	$license_key = "FR_";
		if($tipo == 'ptime') 	$license_key = "PT_";
		if($tipo == 'vitalicy') $license_key = "VT_";

		# 1ª Casa - 5 caracteres randômicos de 0-5 e P-Z

		$caracteres = 'PQRSTUWXYZ';
	    $caracteres .= '012345';
	    $max = strlen($caracteres)-1;

	    $senha = null;

	    for($i=0; $i < 5; $i++){
	       $senha .= $caracteres[mt_rand(0, $max)];
	    }

	    $license_key .= $senha.'-';

		# 2ª Casa - 5 caracteres randômicos de 6-9 e E-P

		$caracteres = 'EFGHIJKLMNOP';
	    $caracteres .= '6789';
	    $max = strlen($caracteres)-1;

	    $senha = null;

	    for($i=0; $i < 5; $i++){
	       $senha .= $caracteres[mt_rand(0, $max)];
	    }

	    $license_key .= $senha.'-';

		# 3ª Casa - 6 caracteres IS-MES-8J

		$license_key .= 'IS'. date('m') .'8J-';

		# 4ª Casa - 6 caracteres KW5-DIA-T

		$license_key .= 'KW5'. date('d') .'T-';

		# 5ª Casa - 5 caracteres randômicos 0-9 e A-Z

	    $caracteres = 'ABCDEFGHIJKLMNOP';
	    $caracteres .= '0123456789';
	    $max = strlen($caracteres)-1;

	    $senha = null;

	    for($i=0; $i < 5; $i++){
	       $senha .= $caracteres[mt_rand(0, $max)];
	    }

	    $license_key .= $senha;

		# Verificador ptime
		$license_key .= '_PT_';

		# Verificador de data - 5 caracteres randômicos ANO DIA E MES

		$caracteres = date('Ydm');
	    $max = strlen($caracteres)-1;

	    $senha = null;

	    for($i=0; $i < 5; $i++){
	       $senha .= $caracteres[mt_rand(0, $max)];
	    }


		$license_key .= '*'.$senha.'*';

		# Grava a licença
		$dados->license_key = $license_key;

		echo $license_key;



		# SENHA DE ACESSO - PT_4432_4678_0089_5*

		# prefixo sempre será PT_
		$senha_acesso = 'PT_';

	    $caracteres = '0123456789';
	    $max = strlen($caracteres)-1;

	    $senha = null;
	    for($i=0; $i < 4; $i++){
	       $senha .= $caracteres[mt_rand(0, $max)];
	    }

	    $senha_acesso .= $senha.'_';

	    $senha = null;
	    for($i=0; $i < 4; $i++){
	       $senha .= $caracteres[mt_rand(0, $max)];
	    }

	    $senha_acesso .= $senha.'_';

	    $senha = null;
	    for($i=0; $i < 4; $i++){
	       $senha .= $caracteres[mt_rand(0, $max)];
	    }

	    $senha_acesso .= $senha.'_';

	    # Verificador de tipo

	    if($tipo == 'trial') 	$senha_acesso .= "9*";
		if($tipo == 'free')  	$senha_acesso .= "7*";
		if($tipo == 'ptime') 	$senha_acesso .= "5*";
		if($tipo == 'vitalicy') $senha_acesso .= "0*";

		echo '<br><br>'. $senha_acesso;

		$dados->senha_acesso = $senha_acesso;



		# VERIFICAÇÃO - PT_2655_1144_RFC_TESTING_5*

		# USUARIO - bvidasi

		# TIPO ptime

		# VERSAO

	}

}