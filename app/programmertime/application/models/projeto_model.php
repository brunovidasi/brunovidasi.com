<?php

class Projeto_model extends CI_Model {
	
	function __construct() {
		parent::__construct();
	}
	
	function post($method = 'insert'){
		if ($this->input->server('REQUEST_METHOD') == 'POST') {

			$dados = new stdClass();

			$dados->idcliente	 	= $this->input->post('idcliente', TRUE);
			$dados->idtipo	 		= $this->input->post('idtipo', TRUE);
			$dados->idresponsavel	= $this->input->post('idresponsavel', TRUE);
			$dados->nome	 		= $this->input->post('nome', TRUE);
			$dados->imagem		 	= $this->input->post('imagem', TRUE);
			$dados->status	 		= $this->input->post('status', TRUE);
			$dados->prioridade		= $this->input->post('prioridade', TRUE);
			$dados->descricao		= $this->input->post('descricao');
			$dados->obs		 		= $this->input->post('obs');
			$dados->link		 	= $this->input->post('link', TRUE);
			$dados->prazo 			= fdata($this->input->post('prazo', TRUE), "-") . " " . $this->input->post('prazo_hora', TRUE);
			$dados->data_inicio 	= fdata($this->input->post('data_inicio', TRUE), "-");
			$dados->data_fim		= fdata($this->input->post('data_fim', TRUE), "-");

			return $dados;
		}
		return FALSE;
	}
	
	function post_obs(){
		if ($this->input->server('REQUEST_METHOD') == 'POST'){

			$dados = new stdClass();

			$dados->obs = $this->input->post('obs');

			return $dados;
		}
		return FALSE;
	}
	
	function insert($dados){
		return ($this->db->insert("projeto", $dados)) ? $this->id = $this->db->insert_id() : 0;
	}
	
	function update($id, $dados){
		$id = (int) $id;
		if($id > 0){		
			$this->db->where('idprojeto', $id);
			return ($this->db->update('projeto', $dados)) ? TRUE : FALSE;
		}
		return FALSE;
	}

	function delete($id){
		$id = (int) $id;
		if($id > 0)
			return $this->db->delete('projeto', array('idprojeto' => $id)) ? TRUE : FALSE ; 
		
		return FALSE;
	}
	
	function status($id, $status){
		$id = (int) $id;
		if($id > 0){
		
			$projeto = new stdClass();
			$projeto->status = $status;
			
			$this->db->where('idprojeto', $id);
			if($this->db->update('projeto', $projeto))
				return TRUE;
			
			return FALSE;
		}
		return FALSE;
	}
	
	function get_projeto($idprojeto){
		$idprojeto = (int) $idprojeto;


		$sql = "SELECT 	
					P.*, 
					C.nome as cliente, 
					C.status as cliente_status, 
					C.email as cliente_email,
					PT.tipo as tipo,
					U.status as responsavel_status, 
					U.nome as responsavel,
					U.imagem as responsavel_imagem,
					U.cor as responsavel_cor,
					U.email as responsavel_email,
					NV.cargo as responsavel_cargo
				FROM 
					projeto as P
				LEFT JOIN 
					cliente as C ON C.idcliente = P.idcliente
				LEFT JOIN 
					projeto_tipo as PT ON PT.idtipo = P.idtipo
				LEFT JOIN 
					usuario as U ON U.idusuario = P.idresponsavel
				LEFT JOIN 
					usuario_nivel_acesso as NV ON NV.id = U.nivel_acesso
				WHERE 
					P.idprojeto = '{$idprojeto}'
				ORDER BY 
					P.data_inicio DESC, P.idprojeto DESC
				LIMIT 1";
		
		return $this->db->query($sql);
	}
	
	function get_projetos($termo = ""){

		$termo = strsql($termo);

		$sql = "SELECT * FROM 
					projeto
				WHERE 
					(nome LIKE '%{$termo}%') OR (descricao LIKE '%{$termo}%') OR (idprojeto LIKE '%{$termo}%')
				ORDER BY 
					data_inicio DESC, idprojeto DESC";
		
		return $this->db->query($sql);
	}
	
	function get_projetos_lista($maximo, $inicio, $termo = ""){
		$maximo = (int) $maximo;
		$inicio = (int) $inicio;


		$termo = strsql($termo);

		$sql = "SELECT 	
					P.*, 
					C.nome as clientenome, 
					C.status as clientestatus, 
					C.email as clienteemail,
					PT.tipo as tipoprojeto,
					U.status as responsavelstatus, 
					U.nome as responsavelnome,
					U.cor as responsavel_cor,
					U.imagem as responsavel_imagem
				FROM 
					projeto as P
				LEFT JOIN 
					cliente as C ON C.idcliente = P.idcliente
				LEFT JOIN 
					projeto_tipo as PT ON PT.idtipo = P.idtipo
				LEFT JOIN 
					usuario as U ON U.idusuario = P.idresponsavel
				WHERE 
					(P.nome LIKE '%{$termo}%') OR (P.descricao LIKE '%{$termo}%') OR (P.idprojeto LIKE '%{$termo}%')
				ORDER BY 
					P.data_inicio DESC, P.idprojeto DESC
				LIMIT 
					{$inicio}, {$maximo}";
		
		$query = $this->db->query($sql);
		
		foreach($query->result() as $projeto){
			$novos_dados = $this->projeto_model->get_projetos_por_etapas($projeto->idprojeto);
			$projeto->total_horas = $novos_dados->total_horas;
			$projeto->numero_etapas = $novos_dados->numero_etapas;
		}
		
		return $query;
	}
	
	function get_projetos_cliente($id){
		$id = (int) $id;


		$sql = "SELECT * FROM 
					projeto
				WHERE 
					idcliente = '{$id}'
				ORDER BY 
					data_inicio DESC, idprojeto DESC";
		
		return $this->db->query($sql);
	}
	
	function get_projetos_cliente_lista($maximo, $inicio, $id){
		$maximo = (int) $maximo;
		$inicio = (int) $inicio;
		$id = (int) $id;


		$sql = "SELECT 	
					P.*, 
					C.nome as clientenome, 
					C.status as clientestatus, 
					C.email as clienteemail,
					PT.tipo as tipoprojeto,
					U.status as responsavelstatus, 
					U.nome as responsavelnome
				FROM 
					projeto as P 
				LEFT JOIN 
					cliente as C ON C.idcliente = P.idcliente
				LEFT JOIN 
					projeto_tipo as PT ON PT.idtipo = P.idtipo
				LEFT JOIN 
					usuario as U ON U.idusuario = P.idresponsavel
				WHERE 
					P.idcliente = '{$id}'
				ORDER BY 
					P.data_inicio DESC, P.idprojeto DESC
				LIMIT 
					{$inicio}, {$maximo}";
		
		return $this->db->query($sql);
	}
	
	function get_tipos(){
		$sql = "SELECT * FROM projeto_tipo ORDER BY idtipo ASC";
		
		return $this->db->query($sql);
	}
	
	function post_imagens($imagens_salvas = array()){
		$imagens = array();
		if (($this->uri->segment(3) == "insert") || ($this->uri->segment(3) == "update")) {
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
					$objeto->caminho = base_url('assets/imagens/projeto/'. $imagem->imagem);
					$objeto->imagem = $imagem->imagem;
					$imagens[] = $objeto;				
				}
			}
		}
		
		return $imagens;
	}
	
	function salva_imagens($idsalvo = 0){
		if(!empty($idsalvo)){
			$imagens = $this->post_imagens();
			$this->db->delete('projeto_imagem', array('idprojeto' => $idsalvo));
			
			$imagens_salvas = array();
			foreach($imagens as $imagem){
				if(strpos($imagem->caminho, "assets/upload/temp/") !== false){
					$config = array('source_image' => "assets/upload/temp/" . $imagem->imagem, 'new_image' => "assets/imagens/projeto/");
					$this->image_lib->clear();
					$this->image_lib->initialize($config);
					$this->image_lib->resize();
				}
				
				$objeto = new stdClass();
				$objeto->idprojeto = $idsalvo;
				$objeto->imagem = $imagem->imagem;
				$imagens_salvas[] = $this->db->insert("projeto_imagem", $objeto) ? TRUE : FALSE;

			}
		}
	}
	
	function retorna_imagens($idprojeto){
		$idprojeto = (int) $idprojeto;

		$sql = "SELECT * FROM projeto_imagem WHERE idprojeto = {$idprojeto}";

		return $this->db->query($sql);
	}
	
	function get_usuarios_envolvidos($idprojeto){
		$idprojeto = (int) $idprojeto;

		
		$etapas_do_projeto = $this->db->query("SELECT * FROM projeto_tarefa_hora WHERE idprojeto = '{$idprojeto}' ORDER BY data DESC, inicio DESC, idetapa DESC");
		
		$ids_usuarios 		 = array();
		$ids_usuarios_todos  = array();
		$informacoes_usuario = array();
		$horas 				 = array();
		$horas_total 		 = array();
		
		foreach($etapas_do_projeto->result() as $etapa){
			$ids_usuarios_todos[] = $etapa->idusuario;
			$horas[$etapa->idusuario][] = calcular_horas_total($etapa->fim, $etapa->inicio);
			$horas_total[] = calcular_horas_total($etapa->fim, $etapa->inicio);
		}
		
		$total_de_horas 	= somar_horas($horas_total);
		$quantidade_etapas 	= array_count_values($ids_usuarios_todos);
		$ids_usuarios 		= array_unique($ids_usuarios_todos);
		
		foreach($ids_usuarios as $id_usuario){
			$informacoes_usuario_etapa = $this->usuario_model->get_usuario($id_usuario);
			$informacoes_usuario_etapa->numero_etapas = $quantidade_etapas[$id_usuario];
			$informacoes_usuario_etapa->horas_trabalhadas = somar_horas($horas[$id_usuario]);

			if(transforma_segundos($total_de_horas) > 0.0) 
				$informacoes_usuario_etapa->porcentagem = (transforma_segundos(somar_horas($horas[$id_usuario])) * 100) / transforma_segundos($total_de_horas);
			else 
				$informacoes_usuario_etapa->porcentagem = 0.0;
			
			$informacoes_usuario[$id_usuario] = $informacoes_usuario_etapa;
		}
		
		return $informacoes_usuario;
	}
	
	function get_projetos_por_etapas($idprojeto){
		$idprojeto = (int) $idprojeto;


		$etapas_do_projeto = $this->db->query("SELECT * FROM projeto_tarefa_hora WHERE idprojeto = '{$idprojeto}' ORDER BY data DESC, inicio DESC, idetapa DESC");
		
		$horas = array();
		foreach($etapas_do_projeto->result() as $etapa)
			$horas[] = calcular_horas_total($etapa->fim, $etapa->inicio);
		
		$projeto = new stdClass();
		$projeto->total_horas 	= somar_horas($horas);
		$projeto->numero_etapas = $etapas_do_projeto->num_rows();
		
		return $projeto;
	}
	
	
	
}