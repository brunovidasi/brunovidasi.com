<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Java extends CI_Controller {

	public function index(){
	    $this->logar();
	}
	
	public function logar($login = NULL, $senha = NULL){
		
		$login = $this->input->post('login');
		$senha = cripto($this->input->post('senha'));
		
		$informacao = $this->acesso_model->get_informacao($login);
		
		foreach($informacao->result() as $usuario){
			
			$id	= $usuario->idusuario;
			
			if ((!empty($id)) && ($id > 0)) {
				$nivel_acesso  	= $usuario->nivel_acesso;
				$numero_acesso  = $usuario->numero_acesso;
				$senha_banco 	= $usuario->senha;
				$status		 	= $usuario->status;
				$nome		 	= $usuario->nome;
				$email		 	= $usuario->email;
				$imagem		 	= $usuario->imagem;
				$confirmado	 	= $usuario->usuario_confirmado;
				$matricula	 	= $usuario->matricula;
				$data_nascimento= $usuario->data_nascimento;
				$rg				= $usuario->rg;
				$cpf			= $usuario->cpf;
			}
		}
		
		if ((!empty($id)) && ($id > 0)) {
			
			if(($senha == $senha_banco) && ($status == 'ativo')){
				
				$this->acesso_model->log($numero_acesso, $id);
				$result["login"][] = array(
					"acesso"		=> TRUE,
					"data_acesso"	=> date('Y-m-d H:i:s'), 
					"nome"			=> $nome, 
					"email"			=> $email, 
					"imagem"		=> $imagem, 
					"nivel_acesso"	=> $nivel_acesso, 
					"confirmado"	=> $confirmado, 
					"matricula"		=> $matricula, 
					"data_nasc"		=> $data_nascimento, 
					"rg"			=> $rg, 
					"cpf"			=> $cpf 
				);
				
				echo json_encode($result);
				
			}else{
				
				if(($status == 'inativo') && ($senha == $senha_banco)){
					
					$this->enviar_email->inativo($email, "contato@programmertime.com", $nome, $login);
					
					$result["login"][] = array(
						"acesso"	=> FALSE,
						"mensagem"	=> "erro_inativo"
					);
					echo json_encode($result);
					
				}
				
				else{
					$result["login"][] = array(
						"acesso"	=> FALSE,
						"mensagem"	=> "erro_senha"
					);
					echo json_encode($result);
				}
			}
			
        }else{
		    $result["login"][] = array(
				"acesso"	=> FALSE,
				"mensagem"	=> "erro_usuario"
			);
			echo json_encode($result);
		}
		
	}
	
}

/*
$url = "http://www.veritime.com.br/admin/acesso/logarApp";

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);

//curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
//curl_setopt($curl, CURLOPT_POST, true);

//curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);

curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);


$ret = curl_exec($curl);
$ret2 = curl_error($curl);

curl_close($curl);

echo "<pre>";
print_r ($ret);
echo "</pre>";
*/