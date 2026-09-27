<?php  

class Acesso_model extends CI_Model {

	function __construct() {
		parent::__construct();
	}
	
	function post(){
		if ($this->input->server('REQUEST_METHOD') == 'POST') {
			$this->cargo		 		= $this->input->post('cargo', TRUE);
			$this->cadastra_projeto	 	= $this->input->post('cadastra_projeto', TRUE);
			$this->edita_projeto	 	= $this->input->post('edita_projeto', TRUE);
			$this->cadastra_cliente	 	= $this->input->post('cadastra_cliente', TRUE);
			$this->edita_cliente	 	= $this->input->post('edita_cliente', TRUE);
			$this->cadastra_usuario	 	= $this->input->post('cadastra_usuario', TRUE);
			$this->edita_usuario	 	= $this->input->post('edita_usuario', TRUE);
			$this->envia_relatorio	 	= $this->input->post('envia_relatorio', TRUE);
			$this->lanca_etapa		 	= $this->input->post('lanca_etapa', TRUE);
			$this->lanca_pagamento	 	= $this->input->post('lanca_pagamento', TRUE);
		}
	}
	
	function insert(){
		if ($this->db->insert("usuario_nivel_acesso", $this)){
			$insert_id = $this->db->insert_id();
			return $insert_id;
		}
		return 0;
	}
	
	function get_informacao($login){
		$login = $this->db->escape_str((string) $login);

		$sql = "SELECT * FROM usuario WHERE (login = '{$login}' OR email = '{$login}') LIMIT 1";
		
		return $this->db->query($sql);
	}
	
	function get_informacao_id($id){
		$id = (int) $id;

		$sql = "SELECT * FROM usuario WHERE idusuario = '{$id}' LIMIT 1";
		
		return $this->db->query($sql);
	}
	
	function nivel_acesso($id_nivel_acesso){
		$id_nivel_acesso = (int) $id_nivel_acesso;

		$sql = "SELECT * FROM usuario_nivel_acesso WHERE id = '{$id_nivel_acesso}'";
		$query = $this->db->query($sql);
		
		$nivel_acesso = null;
		
		if ($query->num_rows() > 0){
		
			$nivel = $query->row(); 
			
			$lanca_etapa 		= ($nivel->lanca_etapa == 'sim') ? TRUE : FALSE;
			$lanca_pagamento 	= ($nivel->lanca_pagamento == 'sim') ? TRUE : FALSE;
			$envia_relatorio 	= ($nivel->envia_relatorio == 'sim') ? TRUE : FALSE;
			$cadastra_projeto 	= ($nivel->cadastra_projeto == 'sim') ? TRUE : FALSE;
			$edita_projeto 		= ($nivel->edita_projeto == 'sim') ? TRUE : FALSE;
			$cadastra_cliente 	= ($nivel->cadastra_cliente == 'sim') ? TRUE : FALSE;
			$edita_cliente 		= ($nivel->edita_cliente == 'sim') ? TRUE : FALSE;
			$cadastra_usuario 	= ($nivel->cadastra_usuario == 'sim') ? TRUE : FALSE;
			$edita_usuario 		= ($nivel->edita_usuario == 'sim') ? TRUE : FALSE;
			
			$nivel_acesso = array(
				'lanca_etapa' 		=> $lanca_etapa,
				'lanca_pagamento' 	=> $lanca_pagamento,
				'envia_relatorio'	=> $envia_relatorio,
				'cadastra_usuario'	=> $cadastra_usuario,
				'edita_usuario'		=> $edita_usuario,
				'cadastra_projeto'	=> $cadastra_projeto,
				'edita_projeto'		=> $edita_projeto,
				'cadastra_cliente'	=> $cadastra_cliente,
				'edita_cliente'		=> $edita_cliente
			);
			
			$this->session->set_userdata($nivel_acesso);

		}
		
		return $nivel_acesso;
	}

	function get_info($id = 1){
		$id = (int) $id;

		$sql = "SELECT * FROM info WHERE idinfo = '{$id}'";
		
		return $this->db->query($sql);
	}
	
	function get_recarregar($id){
		$id = (int) $id;

		$sql = "SELECT recarregar FROM usuario WHERE idusuario = '{$id}'";
		
		$query = $this->db->query($sql);
		
		if ($query->num_rows() > 0){
		   $row = $query->row(); 
		   return $row->recarregar;
		}
		
		return;
	}
	
	function log($numero_acesso, $id){
        $id = (int) $id;
		
        if ($id > 0) {
            
			$numero_acesso++;
			$acesso = array(
			   'ultimo_acesso' => date('Y-m-d H:i:s'),
			   'numero_acesso' => $numero_acesso
			);

			$this->db->where('idusuario', $id);
			$atualizado = $this->db->update('usuario', $acesso);
			
			if($atualizado) return TRUE;
			
			return FALSE;
		}
        return FALSE;
	}
}