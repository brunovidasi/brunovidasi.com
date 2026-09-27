<?php  

class Client_model extends CI_Model {

	function __construct(){
		parent::__construct();
	}
	
	function post($method = 'insert'){
		if ($this->input->server('REQUEST_METHOD') == 'POST') {
			
			$data = new stdClass();

			$data->name					= $this->input->post('name', TRUE);
			$data->website				= $this->input->post('website', TRUE);
			$data->email 				= $this->input->post('email', TRUE);
			$data->phone				= trim($this->input->post('phone', TRUE));
			$data->mobile				= trim($this->input->post('mobile', TRUE));
			$data->legal_name			= $this->input->post('legal_name', TRUE);
			$data->contact_name			= $this->input->post('contact_name', TRUE);
			$data->contact_email 		= $this->input->post('contact_email', TRUE);
			$data->contact_phone		= trim($this->input->post('contact_phone', TRUE));
			$data->address				= $this->input->post('address', TRUE);
			$data->address_number 		= trim($this->input->post('address_number', TRUE));
			$data->address_line2		= $this->input->post('address_line2', TRUE);
			$data->address_district		= $this->input->post('address_district', TRUE);
			$data->address_state		= $this->input->post('address_state', TRUE);
			$data->address_city			= $this->input->post('address_city', TRUE);
			$data->address_postcode		= trim($this->input->post('address_postcode', TRUE));
			$data->tax_id 				= trim($this->input->post('tax_id', TRUE));
			$data->company_tax_id 		= trim($this->input->post('company_tax_id', TRUE));

			if($method == 'insert'){
				$data->created_at 		= date('Y-m-d H:i:s');
				$data->status			= "active";
			}

			return $data;
		}

		return FALSE;
	}
	
	function insert($data){
		return ($this->db->insert("client", $data)) ? $this->id = $this->db->insert_id() : 0;
	}
	
	function update($id, $data){
		$id = (int) $id;
		if($id > 0){		
			$this->db->where('client_id', $id);
			return ($this->db->update('client', $data)) ? TRUE : FALSE;
		}
		return FALSE;
	}

	function delete($id){
		$id = (int) $id;
        if ($id > 0) {
			return $this->db->delete('client', array('client_id' => $id)) ? TRUE : FALSE ; 
        }
        return FALSE;
	}

	function change_status($id, $status = 'active'){
		$id = (int) $id;
		if($id > 0){
			$data = new stdClass();
			$data->status = $status;

			$this->db->where('client_id', $id);
			return ($this->db->update('client', $data)) ? TRUE : FALSE;
		}
		return FALSE;
	}
	
	function get_client($id){
		$id = (int) $id;

		$sql = "SELECT * FROM client WHERE client_id = {$id} LIMIT 1";
		
		return $this->db->query($sql);
	}

	function get_clients($term = ""){

		$term = strsql($term);

		$sql = "SELECT 
					C.*
				FROM 
					client as C
				WHERE 
					(C.name LIKE '%{$term}%') OR (C.email LIKE '%{$term}%')
				ORDER BY 
					C.created_at DESC, C.client_id DESC";
		
		return $this->db->query($sql);
	}

	function get_clients_list($per_page, $offset, $term = ""){
		$per_page = (int) $per_page;
		$offset = (int) $offset;


		$term = strsql($term);
		
		$sql = "SELECT 
					C.*
				FROM 
					client as C
				WHERE 
					(C.name LIKE '%{$term}%') OR (C.email LIKE '%{$term}%')
				ORDER BY 
					C.created_at DESC, C.client_id DESC
				LIMIT 
					{$offset}, {$per_page}";

		$clients = $this->db->query($sql);

		foreach($clients->result() as $client){

			$sql = "SELECT
						P.project_id
					FROM
						project as P
					WHERE
						P.client_id = {$client->client_id}
			";

			$client->project_count = $this->db->query($sql)->num_rows();
		}
					
		return $clients;
	}
	
	function get_projects($client_id, $limit = ""){
		$client_id = (int) $client_id;
		$limit = (int) $limit;

		$sql = "SELECT 	
					P.*, 
					C.name as client_name, 
					C.status as client_status, 
					C.email as client_email,
					PT.type as project_type,
					U.status as owner_status, 
					U.name as owner_name,
					U.image as owner_image,
					U.color as owner_color
				FROM 
					project as P 
				LEFT JOIN 
					client as C ON C.client_id = P.client_id
				LEFT JOIN 
					project_type as PT ON PT.type_id = P.type_id
				LEFT JOIN 
					user as U ON U.user_id = P.owner_id
				
				WHERE P.client_id={$client_id}
				
				ORDER BY P.start_date DESC, P.project_id DESC";
				
		if(!empty($limit))
			$sql .= " LIMIT {$limit}";
		
		$query = $this->db->query($sql);
		
		foreach($query->result() as $project){
			$hours_data = $this->project_model->get_project_hours($project->project_id);
			$project->total_hours = $hours_data->total_hours;
			$project->time_entry_count = $hours_data->time_entry_count;
		}
		
		return $query;
	}

	function get_projects_hours($client_id){
		$projects = $this->client_model->get_projects($client_id);

		$hours = array();
		foreach($projects->result() as $project)
			$hours[] = $project->total_hours;
		
		return sum_hours($hours);
	}
	
	function get_active_projects($client_id){
		$client_id = (int) $client_id;

		$sql = "SELECT * FROM project WHERE ((client_id = {$client_id}) AND (status = 'in_progress')) ORDER BY start_date DESC";

		return $this->db->query($sql);
	}

	function cancel_projects($client_id){
		$id = (int) $client_id;
		if($id > 0){	

			$sql = "UPDATE 
						project 
					SET 
						status = 'cancelled'
					WHERE 
						(status = 'in_progress' OR status = 'paused')
					AND
						client_id = {$id}
			";

			return $this->db->query($sql);
		}
		return FALSE;
	}
}