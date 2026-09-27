<?php  

class Usuario_model extends CI_Model {

    function __construct() {
        parent::__construct();
    }
	
	function post($method = "insert"){
		if($this->input->server('REQUEST_METHOD') == 'POST'){
			
			$login = $this->input->post('login', TRUE);

			$dados = new stdClass();

			$dados->login		 			= $login;
			$dados->nome					= ucwords($this->input->post('nome', TRUE));
			$dados->email		 			= $this->input->post('email', TRUE);
			$dados->matricula		 		= $this->input->post('matricula', TRUE);
			$dados->rg		 				= $this->input->post('rg', TRUE);
			$dados->nivel_acesso	 		= id_ou_null($this->input->post('nivel_acesso', TRUE));

			$cpf = $this->input->post('cpf', TRUE);
			if(!empty($cpf)) $dados->cpf = cpf($cpf);
			
			$data_nasc = $this->input->post('data_nascimento', TRUE);
			if(!empty($data_nasc)) $dados->data_nascimento = fdata($data_nasc, "-");

			if($method == "insert"){
				
				$salt = gera_salt();

				$dados->salt 				= $salt;
				$dados->senha		 		= cripto($this->input->post('senha', TRUE), $salt);
				$dados->email_senha			= gera_confirmacao(strlen($login));
				$dados->data_cadastro 		= date('Y-m-d H:i:s');
				$dados->status		 		= 'ativo';
				$dados->imagem	 			= 'none.png';
				// $dados->usuario_confirmado	= 'nao';
				$dados->usuario_confirmado	= 'sim';
				
			}

			elseif($method == "update"){

				$usuario = $this->usuario_model->get_usuario_login($login)->row();

				$dados->cor 				= $this->input->post('cor', TRUE);
				$dados->recarregar			= "sim";

				$status = $this->input->post('status', TRUE);
				if(!empty($status)) $dados->status = $this->input->post('status', TRUE);

				$senha = $this->input->post('senha', TRUE);
				if(!empty($senha)) $dados->senha = cripto($senha, $usuario->salt);
			}

			return $dados;
		}

		return FALSE;
	}
	
	function post_primeira_vez(){
		if ($this->input->server('REQUEST_METHOD') == 'POST') {
			
			$login = $this->input->post('login', TRUE);
			$salt = gera_salt();

			$dados = new stdClass();

			$dados->login		 		= $login;
			$dados->salt 				= $salt;
			$dados->senha		 		= cripto($this->input->post('senha', TRUE), $salt);
			$dados->nome				= $this->input->post('nome', TRUE);
			$dados->email		 		= $this->input->post('email', TRUE);
			$dados->email_senha			= gera_confirmacao(strlen($login));
			$dados->data_cadastro 		= date('Y-m-d H:i:s');
			$dados->nivel_acesso	 	= '2'; // Gerente
			$dados->status		 		= 'ativo';
			$dados->imagem	 			= 'none.png';
			$dados->usuario_confirmado	= 'nao';

			return $dados;
		}

		return FALSE;
	}
	
	function get_projetos($id, $limite = null){
		$id = (int) $id;
		$limite = (int) $limite;

		$sql = "SELECT 	
					P.*, 
					C.nome as clientenome, 
					C.status as clientestatus, 
					C.email as clienteemail,
					PT.tipo as tipoprojeto
				FROM 
					projeto as P
				LEFT JOIN 
					cliente as C ON C.idcliente = P.idcliente
				LEFT JOIN 
					projeto_tipo as PT ON PT.idtipo = P.idtipo
				WHERE 
					P.idresponsavel = '{$id}' 
				ORDER BY 
					P.data_inicio DESC"; 
				
		if(!empty($limite)) $sql .= " LIMIT {$limite}";
		
		return $this->db->query($sql);
	}
	
	function get_etapas($id, $limite = null){
		$id = (int) $id;
		$limite = (int) $limite;

		$sql = "SELECT 	
					PE.*, 
					P.prioridade as prioridade, 
					P.nome as nomeprojeto,
					PEF.fase as fase
				FROM 
					projeto_tarefa_hora as PE
				LEFT JOIN 
					projeto as P ON PE.idprojeto = P.idprojeto
				LEFT JOIN 
					projeto_fase as PEF ON PE.idfase = PEF.idfase
				WHERE 
					PE.idusuario = '{$id}'
				ORDER BY 
					PE.data DESC, PE.idetapa DESC";
				
		if(!empty($limite)) $sql .= " LIMIT {$limite}";
		
		return $this->db->query($sql);
	}
	
	function get_usuario($id){
		$id = (int) $id;

		$sql = "SELECT 
					U.*, UNA.cargo as cargo 
				FROM 
					usuario as U 
				LEFT JOIN 
					usuario_nivel_acesso as UNA ON U.nivel_acesso = UNA.id
				WHERE 
					U.idusuario = '{$id}' 
				LIMIT 
					1";
		
		return $this->db->query($sql);
	}
	
	function get_usuarios($termo = ""){

		$termo = strsql($termo);

		$sql = "SELECT 
					U.*, 
					UNA.cargo as cargo 
				FROM 
					usuario as U 
				LEFT JOIN 
					usuario_nivel_acesso as UNA ON U.nivel_acesso = UNA.id
				WHERE 
					((U.nome LIKE '%{$termo}%') 
				OR 
					(U.email LIKE '%{$termo}%') 
				OR 
					(U.login LIKE '%{$termo}%') 
				OR 
					(U.matricula LIKE '%{$termo}%') 
				OR 
					(UNA.cargo LIKE '%{$termo}%')) 
				ORDER BY 
					U.idusuario ASC";
		
		return $this->db->query($sql);
	}
	
	function get_usuarios_ativos($termo = ""){

		$termo = strsql($termo);

		$sql = "SELECT 
					U.*, 
					UNA.cargo as cargo 
				FROM 
					usuario as U 
				LEFT JOIN 
					usuario_nivel_acesso as UNA ON U.nivel_acesso = UNA.id
				WHERE 
					U.status='ativo' 
				AND
					((U.nome LIKE '%{$termo}%') 
				OR 
					(U.email LIKE '%{$termo}%') 
				OR 
					(U.login LIKE '%{$termo}%') 
				OR 
					(U.matricula LIKE '%{$termo}%') 
				OR 
					(UNA.cargo LIKE '%{$termo}%')) 
				ORDER BY 
					U.nome ASC";
		
		return $this->db->query($sql);
	}
	
	function get_usuarios_lista($maximo, $inicio, $termo = ""){
		$maximo = (int) $maximo;
		$inicio = (int) $inicio;


		$termo = strsql($termo);
		
		$sql = "SELECT 
					U.*, UNA.cargo as cargo 
				FROM 
					usuario as U 
				LEFT JOIN 
					usuario_nivel_acesso as UNA ON U.nivel_acesso = UNA.id
				WHERE 
					((U.nome LIKE '%{$termo}%') 
				OR 
					(U.email LIKE '%{$termo}%') 
				OR 
					(U.login LIKE '%{$termo}%') 
				OR 
					(U.matricula LIKE '%{$termo}%') 
				OR 
					(UNA.cargo LIKE '%{$termo}%'))
				ORDER BY 
					U.status ASC, U.data_cadastro DESC, U.idusuario ASC 
				LIMIT 
					{$inicio}, {$maximo}";
		
		return $this->db->query($sql);
	}
	
	function get_niveis_acesso(){
		$sql = "SELECT * FROM usuario_nivel_acesso ORDER BY id ASC";
		
		return $this->db->query($sql);
	}
	
	function get_usuario_login($login){
		$login = $this->db->escape_str((string) $login);

		$sql = "SELECT * FROM usuario WHERE login = '{$login}'";
		
		return $this->db->query($sql);
	}

	function get_usuario_email($email){
		$email = $this->db->escape_str((string) $email);

		$sql = "SELECT * FROM usuario WHERE email = '{$email}'";
		
		return $this->db->query($sql);
	}
	
	function get_imagens(){
		$sql = "SELECT imagem FROM usuario";
		
		return $this->db->query($sql);
	}
	
	function insert($dados = FALSE){
		if($dados){
			if($dados->nivel_acesso != '1'){
				if ($this->db->insert("usuario", $dados)){
					$this->enviar_email->confirmar_cadastro($dados->email, $dados->nome, $dados->login, $this->input->post('senha'), $dados->email_senha);
					return $this->db->insert_id();
				}
			}
		}
		return 0;
	}
	
	function update($id, $dados = FALSE){
        if($dados){
        	$id = (int) $id;
	        if ($id > 0) {
	            $this->db->where('idusuario', $id);
				if($this->db->update('usuario', $dados)){
					return TRUE;
				}
	        }
        }
        return FALSE;
	}

	function recarregar($sim_nao){
        $id = (int) $this->session->userdata('id');
        $usuario = new stdClass();
        $usuario->recarregar = $sim_nao;

        if ($id > 0) {
            $this->db->where('idusuario', $id);
			if($this->db->update('usuario', $usuario)){
				return TRUE;
			}
			return FALSE;
        }
        return FALSE;
	}
	
	function get_senha_email($senha){
		$senha = $this->db->escape_str((string) $senha);
		if($senha === '') return 0;

		$query = $this->db->query("SELECT idusuario FROM usuario WHERE email_senha = '{$senha}'");

		if($query->num_rows() > 0){
		   $usuario = $query->row(); 
		   return $usuario->idusuario;
		}
		
		return 0;
	}
	
	function confirma_email($id){
        if ($id > 0) {
            $this->db->where('idusuario', $id);
			
			$confirma = new stdClass();
			$confirma->usuario_confirmado = 'sim';
			$confirma->recarregar = 'sim';
			
			if($this->db->update('usuario', $confirma)){
				return TRUE;
			}
			return FALSE;
        }
        return FALSE;
	}
	
	function ultimo_acesso($id){
        $id = (int) $id;
        if ($id > 0) {
		
			$ultimo_acesso = array(
			   'ultimo_acesso' => date('Y-m-d H:i:s')
			);
		
			$this->db->where('idusuario', $id);
			$atualizado = $this->db->update('usuario', $ultimo_acesso);
			
			if($atualizado) 
				return TRUE;

			return FALSE;
		}
        return FALSE;
	}

	function muda_status($id, $status = 'ativo'){
		$id = (int) $id;
		if($id > 0){
			$dados = new stdClass();
			$dados->status = $status;

			$this->db->where('idusuario', $id);
			return ($this->db->update('usuario', $dados)) ? TRUE : FALSE;
		}
		return FALSE;
	}

	function post_imagens($imagens_salvas = array()){
		$imagens = array();
		if (($this->uri->segment(2) == "insert") || ($this->uri->segment(2) == "update")) {
			$imagens_post = $this->input->post("nomeimagem");
			$caminhoimagem = $this->input->post("caminhoimagem");
			if((!empty($imagens_post)) && (!empty($caminhoimagem))){
				foreach($imagens_post as $key => $imagem){
					if(!empty($imagem)){
						$objeto = new stdClass();
						$objeto->caminho = $caminhoimagem[$key];
						$objeto->imagem = $imagem;
						$imagens[] = $objeto;
					}
				}
			}
		}else{
			if(!empty($imagens_salvas)){
				foreach($imagens_salvas as $imagem){
					$objeto = new stdClass();
					$objeto->caminho = base_url('assets/images/usuarios/'. $imagem->nome);
					$objeto->imagem = $imagem->nome;
					$imagens[] = $objeto;				
				}
			}
		}
		
		return $imagens;
	}
	
	function salva_imagens($idsalvo = 0){
		if(!empty($idsalvo)){
			$imagens = $this->post_imagens();
			
			$imagens_salvas = array();
			foreach($imagens as $imagem){
				if(strpos($imagem->caminho, "assets/images/temp/") !== false){
					$config = array('source_image' => "assets/images/temp/" . $imagem->imagem, 'new_image' => "assets/images/usuarios/");
					$this->image_lib->clear();
					$this->image_lib->initialize($config);
					$this->image_lib->resize();
				}
				
				$objeto = new stdClass();
				$objeto->imagem = $imagem->imagem;
				
				$this->db->where('idusuario', $idsalvo);
				$imagens_salvas[] = $this->db->update("usuario", $objeto) ? TRUE : FALSE;
				
			}

			return $imagens_salvas;
		}
	}
	
	function retorna_imagens($idusuario){
		$idusuario = (int) $idusuario;


		// MUDAR PARA NOME DO MÉTODO PARA GET_IMAGEM
		$sql = "SELECT imagem FROM usuario WHERE idusuario = {$idusuario}";
		
		return $this->db->query($sql)->result();

		// $this->db->select('imagem');
		// $this->db->from('usuario');
		// $this->db->where("idusuario", $idusuario);	
		// return $this->db->get()->result();
	}

	function get_horas_trabalhadas($idusuario){
		$idusuario = (int) $idusuario;


		$etapas = $this->db->query("SELECT * FROM projeto_tarefa_hora WHERE idusuario = '{$idusuario}' ORDER BY data DESC, inicio DESC, idetapa DESC");

		foreach($etapas->result() as $etp) 
			$datas[$etp->data][] = calcular_horas_total($etp->fim, $etp->inicio);

		$total = array();
		
		if(isset($datas))
			foreach($datas as $data => $horas) $total[$data] = somar_horas($horas);

		return $total;
	}
	
	function get_projetos_envolvido($idusuario){
		$idusuario = (int) $idusuario;

		$projetos_envolvidos = $this->db->query("SELECT * FROM projeto_tarefa_hora WHERE idusuario = '{$idusuario}' ORDER BY data DESC, inicio DESC, idetapa DESC");
		
		$ids_projetos = array();
		$ids_projetos_todos = array();
		$informacoes_projeto = array();
		
		// $horas_total = [];
		$horas_total = array();
		
		foreach($projetos_envolvidos->result() as $etapa){
			$ids_projetos_todos[] = $etapa->idprojeto;
			$horas[$etapa->idprojeto][] = calcular_horas_total($etapa->fim, $etapa->inicio);
			$horas_total[] = calcular_horas_total($etapa->fim, $etapa->inicio);
		}
		
		$total_de_horas = somar_horas($horas_total);
		$ids_projetos = array_unique($ids_projetos_todos);
		$quantidade_etapas = array_count_values($ids_projetos_todos);
		
		foreach($ids_projetos as $id_projeto){
			$informacoes_projeto_tarefa_hora = $this->projeto_model->get_projeto($id_projeto);
			$informacoes_projeto_tarefa_hora->numero_etapas = $quantidade_etapas[$id_projeto];
			$informacoes_projeto_tarefa_hora->horas_trabalhadas = somar_horas($horas[$id_projeto]);
			if(transforma_segundos($total_de_horas) != 0){
				$informacoes_projeto_tarefa_hora->porcentagem = (transforma_segundos(somar_horas($horas[$id_projeto])) * 100) / transforma_segundos($total_de_horas);
			}else{
				$informacoes_projeto_tarefa_hora->porcentagem = null;
			}
			$informacoes_projeto[$id_projeto] = $informacoes_projeto_tarefa_hora;
		}
		
		return $informacoes_projeto;
	}

}