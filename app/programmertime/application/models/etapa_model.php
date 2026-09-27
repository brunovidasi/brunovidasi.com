<?php  

class Etapa_model extends CI_Model {

    function __construct() {
        parent::__construct();
    }
	
	function post($method = 'insert'){
	    if($this->input->server('REQUEST_METHOD') == 'POST'){

	    	$data = new stdClass();

	    	if($method == 'insert'){	
	            $data->idusuario			= $this->input->post('idusuario', TRUE);
	            $data->idprojeto 	 		= $this->input->post('idprojeto', TRUE);
	            $data->idtarefa 	 		= id_ou_null($this->input->post('idtarefa', TRUE));
				$data->idfase 	     		= id_ou_null($this->input->post('idfase', TRUE));
				$data->descricao_tecnica    = $this->input->post('descricao_tecnica', TRUE);
				$data->descricao_cliente    = $this->input->post('descricao_cliente', TRUE);
				$data->data          		= fdata($this->input->post('data', TRUE), "-");
	            $data->inicio   	 		= $this->input->post('inicio', TRUE);
	            $data->retroativa			= retroativa($this->input->post('data', TRUE), $this->input->post('inicio', TRUE).':00');
	            $data->data_cadastro		= date('Y-m-d H:i:s');

	            if($data->idtarefa == 0)
	            	unset($data->idtarefa);
	    	}

	    	if($method == 'update'){
	    		$data->fim	    	 		= $this->input->post('fim', TRUE);
				$data->descricao_tecnica	= $this->input->post('descricao_tecnica', TRUE);
				$data->descricao_cliente	= $this->input->post('descricao_cliente', TRUE);
	            $data->idetapa       		= $this->input->post('idetapa', TRUE);
	            $data->data_retorno			= date('Y-m-d H:i:s');
	    	}

	    	if($method == 'edit'){
	    		$data->idusuario			= $this->input->post('idusuario', TRUE);
	            $data->idprojeto 	 		= $this->input->post('idprojeto', TRUE);
	            $data->idtarefa 	 		= id_ou_null($this->input->post('idtarefa', TRUE));
				$data->idfase 	     		= id_ou_null($this->input->post('idfase', TRUE));
				$data->descricao_tecnica    = $this->input->post('descricao_tecnica', TRUE);
				$data->descricao_cliente    = $this->input->post('descricao_cliente', TRUE);
				$data->data          		= fdata($this->input->post('data', TRUE), "-");
	            $data->inicio   	 		= $this->input->post('inicio', TRUE);
	            $data->fim		   	 		= $this->input->post('fim', TRUE);

	            if($data->idtarefa == 0)
	            	unset($data->idtarefa);
	    	}

	    	return $data;
		}

		return FALSE;
	}
	
	function insert($data){
	    if ($this->db->insert("projeto_tarefa_hora", $data)){
			
			$this->projeto_model->status($data->idprojeto, 'desenvolvimento');

			if($data->idtarefa > 0)
				$this->tarefa_model->status($data->idtarefa, 'desenvolvimento');

            return $this->db->insert_id();
        }
        return 0;
	}
	
	function update($id, $data){
        $id = (int) $id;
        if ($id > 0) {		
            $this->db->where('idetapa', $id);
            return ($this->db->update('projeto_tarefa_hora', $data)) ? TRUE : FALSE;
        }
        return FALSE;
    }
	
	function delete($id){
		$id = (int) $id;
        if ($id > 0) {
			return $this->db->delete('projeto_tarefa_hora', array('idetapa' => $id)) ? TRUE : FALSE ; 
        }
        return FALSE;
	}
	
	function etapa_aberta($id){
		$id = (int) $id;

	    $sql = "SELECT 	
	    			PE.*,
					PEF.fase as fase,
					U.nome as responsavel,
					P.nome as nomeprojeto,
					P.descricao as projeto_descricao,
					P.prioridade as projeto_prioridade	
				FROM 
					projeto_tarefa_hora as PE
				LEFT JOIN 
					projeto as P ON PE.idprojeto = P.idprojeto
				LEFT JOIN 
					projeto_fase as PEF ON PE.idfase = PEF.idfase
				LEFT JOIN 
					usuario as U ON PE.idusuario = U.idusuario
				WHERE 
					(PE.idusuario='{$id}') 
				AND 
					(PE.fim IS NULL OR PE.fim = 0) 
				LIMIT 1";
		
		return $this->db->query($sql);
	}
	
	function get_etapa($idetapa){
		$idetapa = (int) $idetapa;

		$sql = "SELECT * FROM projeto_tarefa_hora WHERE idetapa='{$idetapa}' LIMIT 1";
		
		return $this->db->query($sql);
	}
	
	function get_etapas($idprojeto=""){
		$idprojeto = (int) $idprojeto;

		if(!empty($idprojeto))
			$sql = "SELECT 	
					PE.*,
					PEF.fase as fase,
					U.nome as responsavel,
					P.nome as nomeprojeto
				FROM 
					projeto_tarefa_hora as PE
				LEFT JOIN 
					projeto as P ON PE.idprojeto = P.idprojeto
				LEFT JOIN 
					projeto_fase as PEF ON PE.idfase = PEF.idfase
				LEFT JOIN 
					usuario as U ON PE.idusuario = U.idusuario 
				WHERE 
					PE.idprojeto='{$idprojeto}' 
				ORDER BY 
					PE.data DESC, PE.inicio DESC";
		else
			$sql = "SELECT * FROM projeto_tarefa_hora ORDER BY data DESC, inicio DESC";
		
		return $this->db->query($sql);
	}
	
	function get_etapas_relatorio($idprojeto="", $order="ASC", $maximo="", $inicio="", $andamento=FALSE){
		$idprojeto = (int) $idprojeto;
		$order = (strtoupper((string) $order) == 'DESC') ? 'DESC' : 'ASC';
		$maximo = (int) $maximo;
		$inicio = (int) $inicio;

		$limit = "";
		$where = "";
		
		if($andamento) $where .= " WHERE PE.fim IS NULL ";
		else $where .= " WHERE PE.fim IS NOT NULL ";

		if(!empty($idprojeto)) $where .= " AND PE.idprojeto='{$idprojeto}'";
		
		if((!empty($inicio)) OR (!empty($maximo))) $limit = "LIMIT {$inicio}, {$maximo}";
		
		$sql = "SELECT 	
					PE.*,
					PEF.fase as fase,
					U.nome as responsavel,
					P.nome as nomeprojeto
				FROM 
					projeto_tarefa_hora as PE
				LEFT JOIN 
					projeto as P ON PE.idprojeto = P.idprojeto
				LEFT JOIN 
					projeto_fase as PEF ON PE.idfase = PEF.idfase
				LEFT JOIN 
					usuario as U ON PE.idusuario = U.idusuario
				
				{$where}
				
				ORDER BY data {$order},  inicio {$order}  
				{$limit}";
		
		return $this->db->query($sql);
	}

	function get_etapa_relatorio($idetapa = NULL){
		$idetapa = (int) $idetapa;

		
		$sql = "SELECT 	
					PE.*,
					PEF.fase as fase,
					U.nome as responsavel,
					P.nome as nomeprojeto
				FROM 
					projeto_tarefa_hora  as PE
				LEFT JOIN 
					projeto as P ON PE.idprojeto = P.idprojeto
				LEFT JOIN 
					projeto_fase as PEF ON PE.idfase = PEF.idfase
				LEFT JOIN 
					usuario as U ON PE.idusuario = U.idusuario
				
				WHERE PE.idetapa='{$idetapa}'
				
				LIMIT 1";
		
		return $this->db->query($sql);
	}
	
	function get_etapas_usuario($idprojeto, $idusuario, $idfase){
		$idprojeto = (int) $idprojeto;
		$idusuario = (int) $idusuario;
		$idfase = (int) $idfase;

		$sql = "SELECT 	*FROM projeto_tarefa_hora WHERE ((idprojeto = '{$idprojeto}') AND (idusuario = '{$idusuario}') AND (idfase = '{$idfase}'))";
		
		return $this->db->query($sql);
	}
	
	function get_etapas_andamento($idprojeto="", $order="ASC", $maximo="", $inicio=""){
		$idprojeto = (int) $idprojeto;
		$order = (strtoupper((string) $order) == 'DESC') ? 'DESC' : 'ASC';
		$maximo = (int) $maximo;
		$inicio = (int) $inicio;

		
		if(!empty($idprojeto)){
			$where = "WHERE idprojeto='{$idprojeto}'";
			$limit = "";
		}else{
			$where = "";
			$limit = "LIMIT {$inicio}, {$maximo}";
		}
		
		$sql = "SELECT 	
					PE.*,
					PEF.fase as fase,
					U.nome as responsavel,
					P.nome as nomeprojeto
				FROM 
					projeto_tarefa_hora  as PE
				LEFT JOIN 
					projeto as P ON PE.idprojeto = P.idprojeto
				LEFT JOIN 
					projeto_fase as PEF ON PE.idfase = PEF.idfase
				LEFT JOIN 
					usuario as U ON PE.idusuario = U.idusuario
				
				{$where}
				
				ORDER BY data {$order} 
				{$limit}";
		
		return $this->db->query($sql);
	}
	
	function get_informacoes_usuario($idprojeto, $idusuario, $idfase){
		$idprojeto = (int) $idprojeto;
		$idusuario = (int) $idusuario;
		$idfase = (int) $idfase;

	
		$sql = "SELECT 	
					PE.*,
					PEF.fase as fase,
					U.nome as responsavel
				FROM 
					projeto_tarefa_hora as PE
				LEFT JOIN 
					projeto_fase as PEF ON PE.idfase = PEF.idfase
				LEFT JOIN 
					usuario as U ON U.idusuario = {$idusuario}
				
				WHERE 
					(PE.idprojeto='{$idprojeto}') 
				AND 
					(PE.idusuario='{$idusuario}') 
				AND 
					(PE.idfase='{$idfase}')
				
				ORDER BY data DESC";
		
		return $this->db->query($sql);
	}
	
	function get_id_usuario($idprojeto){
		$idprojeto = (int) $idprojeto;

		$sql = "SELECT * FROM projeto_tarefa_hora WHERE idprojeto='{$idprojeto}'";
		
		return $this->db->query($sql);
	}
	
	function get_id_cliente($idprojeto){
		$idprojeto = (int) $idprojeto;

		$projeto = $this->db->query("SELECT idcliente FROM projeto WHERE idprojeto='{$idprojeto}'")->row();

		return empty($projeto) ? NULL : $projeto->idcliente;
	}
	
	function get_fases(){
		$sql = "SELECT * FROM projeto_fase ORDER BY idfase";
		
		return $this->db->query($sql);
	}
	
	function get_fase($id){
		$id = (int) $id;

		$sql = "SELECT * FROM projeto_fase WHERE idfase='{$id}'";
		
		return $this->db->query($sql);
	}
	
	function get_cliente($idcliente){
		$idcliente = (int) $idcliente;

		$sql = "SELECT * FROM cliente WHERE idcliente='{$idcliente}' LIMIT 1";
		
		return $this->db->query($sql);
	}
	
	function get_projeto_cliente($idcliente){
		$idcliente = (int) $idcliente;

		$sql = "SELECT 
					* 
				FROM 
					projeto 
				WHERE 
					((status = 'nao_comecado') 
					OR 
					(status = 'desenvolvimento') 
					OR 
					(status = 'pausado'))
				AND 
					(idcliente = '{$idcliente}')
				ORDER BY 
					prazo ASC";
		
		return $this->db->query($sql);
	}
	
	function get_projeto_tarefa_hora($idprojeto){
		$idprojeto = (int) $idprojeto;

		$sql = "SELECT * FROM projeto WHERE idprojeto='{$idprojeto}'";
		
		return $this->db->query($sql);
	}
	
}