<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Json extends CI_Controller{

	public function index(){
		$this->logar();
	}
	
	public function logar($login = NULL, $senha = NULL, $view = 'json'){

		if(empty($login) && empty($senha)){
			$login = $this->input->post('login');
			$senha = cripto($this->input->post('senha'));
		}

		$usuario = $this->acesso_model->get_informacao($login)->row();
		$id	= (isset($usuario->idusuario)) ? $usuario->idusuario : 0;

		if ((!empty($id)) && ($id > 0)){

			if(($senha == $usuario->senha) && ($usuario->status == 'ativo')){

				if($view != 'verifica') $this->acesso_model->log($usuario->numero_acesso, $id);

				$nivel_acesso = $this->acesso_model->nivel_acesso($usuario->nivel_acesso);
				$empresa = $this->empresa_model->get_empresa()->row();
				$info = $this->acesso_model->get_info()->row();

				$cor['hexadecimal'] = $usuario->cor;
				$cor['rgb']['red'] = hexdec(substr(substr($usuario->cor, 1), 0, 2));
				$cor['rgb']['green'] = hexdec(substr(substr($usuario->cor, 1), 2, 2));
				$cor['rgb']['blue'] = hexdec(substr(substr($usuario->cor, 1), 4, 2));
				$cor['rgb']['color'] = "rgb(".$cor['rgb']['red'].",".$cor['rgb']['green'].",".$cor['rgb']['blue'].")";

				$imagem['image'] = $usuario->imagem;
				$imagem['link'] = base_url('assets/images/usuarios/'.$usuario->imagem);
				$imagem['path'] = FCPATH.'assets/images/usuarios/'.basename((string) $usuario->imagem);
				$imagem['extension'] = pathinfo($imagem['link'], PATHINFO_EXTENSION);
				$imagem['width'] = $imagem['height'] = $imagem['type'] = $imagem['attr'] = NULL;
				$imagem['base64'] = NULL;
				if(is_file($imagem['path'])){
					$tamanho = getimagesize($imagem['path']);
					if($tamanho) list($imagem['width'], $imagem['height'], $imagem['type'], $imagem['attr']) = $tamanho;
					$imagem['base64'] = base64_encode(file_get_contents($imagem['path']));
				}


				$asterisco = '';
				for($a = 0; $a < strlen($this->input->post('senha')); $a++) $asterisco .= '*';


				$result["login"] = array(
					"acesso"		=> $nivel_acesso, 
					"funcao"		=> 'logar('.$login.', '.$asterisco.', '.$view.')', 
					"erro"			=> 0, 
					"mensagem"		=> NULL, 
					"data_acesso"	=> date('Y-m-d H:i:s'), 
					"token"			=> $usuario->senha, 
					"email_token"	=> $usuario->email_senha, 
					"nome"			=> $usuario->nome, 
					"login"			=> $usuario->login, 
					"email"			=> $usuario->email, 
					"status"		=> $usuario->status, 
					"nivel_acesso"	=> $usuario->nivel_acesso, 
					"numero_acesso"	=> $usuario->numero_acesso, 
					"confirmado"	=> $usuario->usuario_confirmado, 
					"data_nasc"		=> $usuario->data_nascimento, 
					"data_cadastro"	=> $usuario->data_cadastro, 
					"ultimo_acesso"	=> $usuario->ultimo_acesso, 
					"matricula"		=> $usuario->matricula, 
					"rg"			=> $usuario->rg, 
					"cpf"			=> $usuario->cpf, 
					"imagem"		=> $imagem, 
					"cor"			=> $cor, 
					"empresa"		=> $empresa, 
					"ptime_info"	=> $info 
				);
				
				if($view == 'verifica') return $result["login"];

				else{
					echo json_encode($result);
					die();
				}
			}

			elseif(($senha == $usuario->senha) && ($usuario->status == 'inativo')){

					if($view != 'verifica')
						$this->enviar_email->inativo($usuario->email, "contato@programmertime.com", $usuario->nome, $login);

					$result["login"] = array(
						"acesso"	=> FALSE,
						"funcao"	=> 'logar('.$login.', *****, '.$view.')',
						"mensagem"	=> "user_inactive",
						"erro"		=> 3 // user inactive
					);

					echo json_encode($result);
					die();

			}elseif($senha != $usuario->senha){

				$result["login"] = array(
					"acesso"	=> FALSE,
					"funcao"	=> 'logar('.$login.', *****, '.$view.')',
					"mensagem"	=> "wrong_password",
					"erro"		=> 2 // wrong password
				);

				echo json_encode($result);
				die();

			}else{

				$result["login"] = array(
					"acesso"	=> FALSE,
					"funcao"	=> 'logar('.$login.', *****, '.$view.')',
					"mensagem"	=> "indefined_error",
					"erro"		=> 4 // indefined error
				);

				echo json_encode($result);
				die();
			}

		}

		else{

			$result["login"] = array(
				"acesso"	=> FALSE,
				"funcao"	=> 'logar('.$login.', *****, '.$view.')',
				"mensagem"	=> "user_not_found",
				"erro"		=> 1 // user not found
			);

			echo json_encode($result);
			die();
		}

	}

	private function verificaUsuario($login = NULL, $token = NULL, $cripto = 0){
		
		$login = $this->input->post('login');
		$cripto = (int) $this->input->post('cripto');

		$token = ($cripto == 1) ? cripto($this->input->post('senha')) : $this->input->post('senha');

		return $this->logar($login, $token, 'verifica');
	}

	public function getUsuario($idusuario = NULL){

		$this->verificaUsuario();

		if(empty($idusuario)) $idusuario = $this->input->post('idusuario');
		$idusuario = (int) $idusuario;

		$array["login"] = array(
			"acesso"	=> TRUE,
			"funcao"	=> 'getUsuario('.$idusuario.')',
			"mensagem"	=> NULL,
			"erro"		=> 0
		);

		$array["usuario"] = $this->usuario_model->get_usuario($idusuario)->row();
		$array["nivel_acesso"] = $this->acesso_model->nivel_acesso($array["usuario"]->nivel_acesso);

		echo json_encode($array);
		die();

	}

	public function getProjetos(){

		$this->verificaUsuario();

		$array["login"] = array(
			"acesso"	=> TRUE,
			"funcao"	=> 'getProjetos()',
			"mensagem"	=> NULL,
			"erro"		=> 0
		);

		$array["projetos"] = $this->projeto_model->get_projetos()->result();

		echo json_encode($array);
		die();
	}

	public function getProjeto($idprojeto = NULL){

		$this->verificaUsuario();

		if(empty($idprojeto)) $idprojeto = $this->input->post('idprojeto');
		$idprojeto = (int) $idprojeto;

		$array["login"] = array(
			"acesso"	=> TRUE,
			"funcao"	=> 'getProjeto('.$idprojeto.')',
			"mensagem"	=> NULL,
			"erro"		=> 0
		);

		$array["projeto"] = $this->projeto_model->get_informacoes_projeto($idprojeto)->row();

		echo json_encode($array);
		die();
	}

	public function getEtapas($idprojeto = NULL){

		$this->verificaUsuario();

		if(empty($idprojeto)) $idprojeto = $this->input->post('idprojeto');
		$idprojeto = (int) $idprojeto;

		$array["login"] = array(
			"acesso"	=> TRUE,
			"funcao"	=> 'getEtapas('.$idprojeto.')',
			"mensagem"	=> NULL,
			"erro"		=> 0
		);

		$array["etapas"] = $this->etapa_model->get_etapas($idprojeto)->result();

		echo json_encode($array);
		die();
	}

	public function getEtapa($idetapa = NULL){

		$this->verificaUsuario();

		if(empty($idetapa)) $idetapa = $this->input->post('idetapa');
		$idetapa = (int) $idetapa;

		$array["login"] = array(
			"acesso"	=> TRUE,
			"funcao"	=> 'getEtapa('.$idetapa.')',
			"mensagem"	=> NULL,
			"erro"		=> 0
		);
		
		$array["etapa"] = $this->etapa_model->get_etapa_relatorio($idetapa)->result();

		echo json_encode($array);
		die();
	}

	public function getClientes(){

		$this->verificaUsuario();

		$array["login"] = array(
			"acesso"	=> TRUE,
			"funcao"	=> 'getClientes()',
			"mensagem"	=> NULL,
			"erro"		=> 0
		);

		$array["clientes"] = $this->cliente_model->get_clientes()->result();

		echo json_encode($array);
		die();
	}

	public function getCliente($idcliente = NULL){

		$this->verificaUsuario();

		if(empty($idcliente)) $idcliente = $this->input->post('idcliente');
		$idcliente = (int) $idcliente;

		$array["login"] = array(
			"acesso"	=> TRUE,
			"funcao"	=> 'getCliente('.$idcliente.')',
			"mensagem"	=> NULL,
			"erro"		=> 0
		);

		$array["cliente"] = $this->cliente_model->get_cliente($idcliente)->row();

		echo json_encode($array);
		die();
	}

}

/*

// MANEIRAS DE REQUISIÇÃO COM PHP


if($_POST){

	$url = $_POST['url']; // URL WITH JSON REQUEST
	
	// DATA TO POST
	$data = array(
		'login' => $_POST['username'], 
		'senha' => $_POST['password'], 
		'cripto' => $_POST['cripto'], 
		'idprojeto' => $_POST['project_id'],
		'idetapa' => $_POST['stage_id'],
		'idcliente' => $_POST['client_id'],
	);

	// CURL FUNCTION
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_POST, true); // ALLOW POST
	curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // DATA POST IN ARRAY
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	
	$result = curl_exec($curl); // GET RESULT IN JSON
	$error = curl_error($curl); // GET ERROR
	
	$object = json_decode($result); // JSON TO OBJECT

	curl_close($curl); // CLOSE CURL FUNCTION
	
	// PRINT THE OBJECT
	echo "<pre>";
	print_r($object);
	echo "</pre>";
	die();

}
	
## OR FILE GET CONTENTS

	// $options = array(

	// 	'http' => array(

	// 		'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
	// 		'method'  => 'POST',
	// 		'content' => http_build_query($data),

	// 	),
	// );

	// $context  = stream_context_create($options);
	// $result = file_get_contents($url, false, $context);

	// $object = json_decode($result);

	// echo '<pre>';
	// print_r($object);
	// echo '</pre>';
	// die();

*/