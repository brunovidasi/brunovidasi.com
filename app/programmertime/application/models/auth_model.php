<?php  

class Auth_model extends CI_Model {

	function __construct() {
		parent::__construct();
	}
	
	function post(){
		if ($this->input->server('REQUEST_METHOD') == 'POST') {
			$this->role		 		= $this->input->post('role', TRUE);
			$this->can_create_project	 	= $this->input->post('can_create_project', TRUE);
			$this->can_edit_project	 	= $this->input->post('can_edit_project', TRUE);
			$this->can_create_client	 	= $this->input->post('can_create_client', TRUE);
			$this->can_edit_client	 	= $this->input->post('can_edit_client', TRUE);
			$this->can_create_user	 	= $this->input->post('can_create_user', TRUE);
			$this->can_edit_user	 	= $this->input->post('can_edit_user', TRUE);
			$this->can_send_report	 	= $this->input->post('can_send_report', TRUE);
			$this->can_log_time		 	= $this->input->post('can_log_time', TRUE);
			$this->can_log_payment	 	= $this->input->post('can_log_payment', TRUE);
		}
	}
	
	function insert(){
		if ($this->db->insert("access_level", $this)){
			$insert_id = $this->db->insert_id();
			return $insert_id;
		}
		return 0;
	}
	
	function get_by_login($login){
		$login = $this->db->escape_str((string) $login);

		$sql = "SELECT * FROM user WHERE (login = '{$login}' OR email = '{$login}') LIMIT 1";
		
		return $this->db->query($sql);
	}
	
	function get_by_id($id){
		$id = (int) $id;

		$sql = "SELECT * FROM user WHERE user_id = '{$id}' LIMIT 1";
		
		return $this->db->query($sql);
	}
	
	function access_level($access_level_id){
		$access_level_id = (int) $access_level_id;

		$sql = "SELECT * FROM access_level WHERE id = '{$access_level_id}'";
		$query = $this->db->query($sql);
		
		$access_level = null;
		
		if ($query->num_rows() > 0){
		
			$level = $query->row(); 
			
			$can_log_time 		= ($level->can_log_time == 'yes') ? TRUE : FALSE;
			$can_log_payment 	= ($level->can_log_payment == 'yes') ? TRUE : FALSE;
			$can_send_report 	= ($level->can_send_report == 'yes') ? TRUE : FALSE;
			$can_create_project 	= ($level->can_create_project == 'yes') ? TRUE : FALSE;
			$can_edit_project 		= ($level->can_edit_project == 'yes') ? TRUE : FALSE;
			$can_create_client 	= ($level->can_create_client == 'yes') ? TRUE : FALSE;
			$can_edit_client 		= ($level->can_edit_client == 'yes') ? TRUE : FALSE;
			$can_create_user 	= ($level->can_create_user == 'yes') ? TRUE : FALSE;
			$can_edit_user 		= ($level->can_edit_user == 'yes') ? TRUE : FALSE;
			
			$access_level = array(
				'can_log_time' 		=> $can_log_time,
				'can_log_payment' 	=> $can_log_payment,
				'can_send_report'	=> $can_send_report,
				'can_create_user'	=> $can_create_user,
				'can_edit_user'		=> $can_edit_user,
				'can_create_project'	=> $can_create_project,
				'can_edit_project'		=> $can_edit_project,
				'can_create_client'	=> $can_create_client,
				'can_edit_client'		=> $can_edit_client
			);
			
			$this->session->set_userdata($access_level);

		}
		
		return $access_level;
	}

	function get_info($id = 1){
		$id = (int) $id;

		$sql = "SELECT * FROM info WHERE info_id = '{$id}'";
		
		return $this->db->query($sql);
	}
	
	function get_reload($id){
		$id = (int) $id;

		$sql = "SELECT reload FROM user WHERE user_id = '{$id}'";
		
		$query = $this->db->query($sql);
		
		if ($query->num_rows() > 0){
		   $row = $query->row(); 
		   return $row->reload;
		}
		
		return;
	}
	
	function log($login_count, $id){
        $id = (int) $id;
		
        if ($id > 0) {
            
			$login_count++;
			$auth = array(
			   'last_access' => date('Y-m-d H:i:s'),
			   'login_count' => $login_count
			);

			$this->db->where('user_id', $id);
			$updated = $this->db->update('user', $auth);
			
			if($updated) return TRUE;
			
			return FALSE;
		}
        return FALSE;
	}
}