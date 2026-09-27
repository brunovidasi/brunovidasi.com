<?php  

class Cliente_model extends CI_Model {

	function __construct(){
		parent::__construct();
	}
	
	function post($method = 'insert'){
		if ($this->input->server('REQUEST_METHOD') == 'POST') {
			
			$dados = new stdClass();

			$dados->nome				= $this->input->post('nome', TRUE);
			$dados->website				= $this->input->post('website', TRUE);
			$dados->email 				= $this->input->post('email', TRUE);
			$dados->telefone			= numero($this->input->post('telefone', TRUE));
			$dados->celular				= numero($this->input->post('celular', TRUE));
			$dados->razao_social		= $this->input->post('razao_social', TRUE);
			$dados->nome_contato		= $this->input->post('nome_contato', TRUE);
			$dados->email_contato 		= $this->input->post('email_contato', TRUE);
			$dados->telefone_contato	= numero($this->input->post('telefone_contato', TRUE));
			$dados->endereco			= $this->input->post('endereco', TRUE);
			$dados->endereco_numero 	= numero($this->input->post('endereco_numero', TRUE));
			$dados->endereco_complemento= $this->input->post('endereco_complemento', TRUE);
			$dados->endereco_bairro		= $this->input->post('endereco_bairro', TRUE);
			$dados->endereco_estado		= $this->input->post('endereco_estado', TRUE);
			$dados->endereco_cidade		= $this->input->post('endereco_cidade', TRUE);
			$dados->endereco_cep		= numero($this->input->post('endereco_cep', TRUE));

			if($method == 'insert'){
				$dados->data_cadastro 	= date('Y-m-d H:i:s');
				$dados->status			= "ativo";
			}

			$cpf = $this->input->post('cpf', TRUE);
			if(!empty($cpf))
				$dados->cpf 			= cpf($cpf);
			
			$cnpj = $this->input->post('cnpj', TRUE);
			if(!empty($cnpj))
				$dados->cnpj 			= cnpj($cnpj);

			return $dados;
		}

		return FALSE;
	}
	
	function insert($dados){
		return ($this->db->insert("cliente", $dados)) ? $this->id = $this->db->insert_id() : 0;
	}
	
	function update($id, $dados){
		$id = (int) $id;
		if($id > 0){		
			$this->db->where('idcliente', $id);
			return ($this->db->update('cliente', $dados)) ? TRUE : FALSE;
		}
		return FALSE;
	}

	function delete($id){
		$id = (int) $id;
        if ($id > 0) {
			return $this->db->delete('cliente', array('idcliente' => $id)) ? TRUE : FALSE ; 
        }
        return FALSE;
	}

	function muda_status($id, $status = 'ativo'){
		$id = (int) $id;
		if($id > 0){
			$dados = new stdClass();
			$dados->status = $status;

			$this->db->where('idcliente', $id);
			return ($this->db->update('cliente', $dados)) ? TRUE : FALSE;
		}
		return FALSE;
	}
	
	function get_cliente($id){
		$id = (int) $id;

		$sql = "SELECT * FROM cliente WHERE idcliente = {$id} LIMIT 1";
		
		return $this->db->query($sql);
	}

	function get_clientes($termo = ""){

		$termo = strsql($termo);

		$sql = "SELECT 
					C.*
				FROM 
					cliente as C
				WHERE 
					(C.nome LIKE '%{$termo}%') OR (C.email LIKE '%{$termo}%')
				ORDER BY 
					C.data_cadastro DESC, C.idcliente DESC";
		
		return $this->db->query($sql);
	}

	function get_clientes_lista($maximo, $inicio, $termo = ""){
		$maximo = (int) $maximo;
		$inicio = (int) $inicio;


		$termo = strsql($termo);
		
		$sql = "SELECT 
					C.*
				FROM 
					cliente as C
				WHERE 
					(C.nome LIKE '%{$termo}%') OR (C.email LIKE '%{$termo}%')
				ORDER BY 
					C.data_cadastro DESC, C.idcliente DESC
				LIMIT 
					{$inicio}, {$maximo}";

		$clientes = $this->db->query($sql);

		foreach($clientes->result() as $cliente){

			$sql = "SELECT
						P.idprojeto
					FROM
						projeto as P
					WHERE
						P.idcliente = {$cliente->idcliente}
			";

			$cliente->num_projetos = $this->db->query($sql)->num_rows();
		}
					
		return $clientes;
	}
	
	function get_projetos($idcliente, $limite = ""){
		$idcliente = (int) $idcliente;
		$limite = (int) $limite;

		$sql = "SELECT 	
					P.*, 
					C.nome as clientenome, 
					C.status as clientestatus, 
					C.email as clienteemail,
					PT.tipo as tipoprojeto,
					U.status as responsavelstatus, 
					U.nome as responsavelnome,
					U.imagem as responsavel_imagem,
					U.cor as responsavel_cor
				FROM 
					projeto as P 
				LEFT JOIN 
					cliente as C ON C.idcliente = P.idcliente
				LEFT JOIN 
					projeto_tipo as PT ON PT.idtipo = P.idtipo
				LEFT JOIN 
					usuario as U ON U.idusuario = P.idresponsavel
				
				WHERE P.idcliente={$idcliente}
				
				ORDER BY P.data_inicio DESC, P.idprojeto DESC";
				
		if(!empty($limite))
			$sql .= " LIMIT {$limite}";
		
		$query = $this->db->query($sql);
		
		foreach($query->result() as $projeto){
			$novos_dados = $this->projeto_model->get_projetos_por_etapas($projeto->idprojeto);
			$projeto->total_horas = $novos_dados->total_horas;
			$projeto->numero_etapas = $novos_dados->numero_etapas;
		}
		
		return $query;
	}

	function get_horas_projetos($idcliente){
		$projetos = $this->cliente_model->get_projetos($idcliente);

		$horas = array();
		foreach($projetos->result() as $projeto)
			$horas[] = $projeto->total_horas;
		
		return somar_horas($horas);
	}
	
	function get_projetos_ativos($idcliente){
		$idcliente = (int) $idcliente;

		$sql = "SELECT * FROM projeto WHERE ((idcliente = {$idcliente}) AND (status = 'desenvolvimento')) ORDER BY data_inicio DESC";

		return $this->db->query($sql);
	}

	function cancelar_projetos($idcliente){
		$id = (int) $idcliente;
		if($id > 0){	

			$sql = "UPDATE 
						projeto 
					SET 
						status = 'cancelado'
					WHERE 
						status = 'desenvolvimento' OR status = 'pausado'
					AND
						idcliente = {$id}
			";

			return $this->db->query($sql);
		}
		return FALSE;
	}
}