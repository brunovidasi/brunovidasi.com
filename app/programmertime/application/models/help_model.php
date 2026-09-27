<?php  

class Help_model extends CI_Model {

    function __construct() {
        parent::__construct();
    }
	
	function post(){
		if ($this->input->server('REQUEST_METHOD') == 'POST') {
			
			$this->title	= $this->input->post('title', TRUE);
			$this->text	= $this->input->post('text');
			$this->type		= $this->input->post('type', TRUE);
			$this->status	= $this->input->post('status', TRUE);
			
		}
	}
	
	function get_help($help_id = 0){
		$help_id = (int) $help_id;

		$sql = "SELECT * FROM help WHERE help_id = {$help_id}";
		
		return $this->db->query($sql);
	}
	
	function get_help_articles(){
		$sql = "SELECT 	* FROM help";
		
		return $this->db->query($sql);
	}
	
	function insert(){
		if ($this->db->insert("help", $this)){
			return $this->db->insert_id();
		}
		return 0;
	}
	
	function update($id) {
        $id = (int) $id;
        if ($id > 0) {
            $this->db->where('help_id', $id);
			if($this->db->update('help', $this)) return TRUE;
			
			return FALSE;
        }
        return FALSE;
	}
	
	function delete($id){
		$id = (int) $id;
        if ($id > 0) {
			return $this->db->delete('help', array('help_id' => $id)) ? TRUE : FALSE ; 
        }
        return FALSE;
	}

}