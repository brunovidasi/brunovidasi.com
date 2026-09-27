<?php  

class Ajuda_model extends CI_Model {

    function __construct() {
        parent::__construct();
    }
	
	function post(){
		if ($this->input->server('REQUEST_METHOD') == 'POST') {
			
			$this->titulo	= $this->input->post('titulo', TRUE);
			$this->texto	= $this->input->post('texto');
			$this->tipo		= $this->input->post('tipo', TRUE);
			$this->status	= $this->input->post('status', TRUE);
			
		}
	}
	
	function get_ajuda($idajuda = 0){
		$idajuda = (int) $idajuda;

		$sql = "SELECT * FROM ajuda WHERE idajuda = {$idajuda}";
		
		return $this->db->query($sql);
	}
	
	function get_ajudas(){
		$sql = "SELECT 	* FROM ajuda";
		
		return $this->db->query($sql);
	}
	
	function insert(){
		if ($this->db->insert("ajuda", $this)){
			return $this->db->insert_id();
		}
		return 0;
	}
	
	function update($id) {
        $id = (int) $id;
        if ($id > 0) {
            $this->db->where('idajuda', $id);
			if($this->db->update('ajuda', $this)) return TRUE;
			
			return FALSE;
        }
        return FALSE;
	}
	
	function delete($id){
		$id = (int) $id;
        if ($id > 0) {
			return $this->db->delete('ajuda', array('idajuda' => $id)) ? TRUE : FALSE ; 
        }
        return FALSE;
	}

}