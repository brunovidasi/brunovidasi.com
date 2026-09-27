<?php

class Project_model extends CI_Model {
	
	function __construct() {
		parent::__construct();
	}
	
	function post($method = 'insert'){
		if ($this->input->server('REQUEST_METHOD') == 'POST') {

			$data = new stdClass();

			$data->client_id	 	= $this->input->post('client_id', TRUE);
			$data->type_id	 		= $this->input->post('type_id', TRUE);
			$data->owner_id	= $this->input->post('owner_id', TRUE);
			$data->name	 		= $this->input->post('name', TRUE);
			$data->image		 	= $this->input->post('image', TRUE);
			$data->status	 		= $this->input->post('status', TRUE);
			$data->priority		= $this->input->post('priority', TRUE);
			$data->description		= $this->input->post('description');
			$data->notes		 		= $this->input->post('notes');
			$data->link		 	= $this->input->post('link', TRUE);
			$data->deadline 			= fdate($this->input->post('deadline', TRUE), "-") . " " . $this->input->post('deadline_time', TRUE);
			$data->start_date 	= fdate($this->input->post('start_date', TRUE), "-");
			$data->end_date		= fdate($this->input->post('end_date', TRUE), "-");

			return $data;
		}
		return FALSE;
	}
	
	function post_notes(){
		if ($this->input->server('REQUEST_METHOD') == 'POST'){

			$data = new stdClass();

			$data->notes = $this->input->post('notes');

			return $data;
		}
		return FALSE;
	}
	
	function insert($data){
		return ($this->db->insert("project", $data)) ? $this->id = $this->db->insert_id() : 0;
	}
	
	function update($id, $data){
		$id = (int) $id;
		if($id > 0){		
			$this->db->where('project_id', $id);
			return ($this->db->update('project', $data)) ? TRUE : FALSE;
		}
		return FALSE;
	}

	function delete($id){
		$id = (int) $id;
		if($id > 0)
			return $this->db->delete('project', array('project_id' => $id)) ? TRUE : FALSE ; 
		
		return FALSE;
	}
	
	function status($id, $status){
		$id = (int) $id;
		if($id > 0){
		
			$project = new stdClass();
			$project->status = $status;
			
			$this->db->where('project_id', $id);
			if($this->db->update('project', $project))
				return TRUE;
			
			return FALSE;
		}
		return FALSE;
	}
	
	function get_project($project_id){
		$project_id = (int) $project_id;


		$sql = "SELECT 	
					P.*, 
					C.name as client, 
					C.status as client_status, 
					C.email as client_email,
					PT.type as type,
					U.status as owner_status, 
					U.name as owner,
					U.image as owner_image,
					U.color as owner_color,
					U.email as owner_email,
					AL.role as owner_role
				FROM 
					project as P
				LEFT JOIN 
					client as C ON C.client_id = P.client_id
				LEFT JOIN 
					project_type as PT ON PT.type_id = P.type_id
				LEFT JOIN 
					user as U ON U.user_id = P.owner_id
				LEFT JOIN 
					access_level as AL ON AL.id = U.access_level
				WHERE 
					P.project_id = '{$project_id}'
				ORDER BY 
					P.start_date DESC, P.project_id DESC
				LIMIT 1";
		
		return $this->db->query($sql);
	}
	
	function get_projects($term = ""){

		$term = strsql($term);

		$sql = "SELECT * FROM 
					project
				WHERE 
					(name LIKE '%{$term}%') OR (description LIKE '%{$term}%') OR (project_id LIKE '%{$term}%')
				ORDER BY 
					start_date DESC, project_id DESC";
		
		return $this->db->query($sql);
	}
	
	function get_projects_list($per_page, $offset, $term = ""){
		$per_page = (int) $per_page;
		$offset = (int) $offset;


		$term = strsql($term);

		$sql = "SELECT 	
					P.*, 
					C.name as client_name, 
					C.status as client_status, 
					C.email as client_email,
					PT.type as project_type,
					U.status as owner_status, 
					U.name as owner_name,
					U.color as owner_color,
					U.image as owner_image
				FROM 
					project as P
				LEFT JOIN 
					client as C ON C.client_id = P.client_id
				LEFT JOIN 
					project_type as PT ON PT.type_id = P.type_id
				LEFT JOIN 
					user as U ON U.user_id = P.owner_id
				WHERE 
					(P.name LIKE '%{$term}%') OR (P.description LIKE '%{$term}%') OR (P.project_id LIKE '%{$term}%')
				ORDER BY 
					P.start_date DESC, P.project_id DESC
				LIMIT 
					{$offset}, {$per_page}";
		
		$query = $this->db->query($sql);
		
		foreach($query->result() as $project){
			$hours_data = $this->project_model->get_project_hours($project->project_id);
			$project->total_hours = $hours_data->total_hours;
			$project->time_entry_count = $hours_data->time_entry_count;
		}
		
		return $query;
	}
	
	function get_client_projects($id){
		$id = (int) $id;


		$sql = "SELECT * FROM 
					project
				WHERE 
					client_id = '{$id}'
				ORDER BY 
					start_date DESC, project_id DESC";
		
		return $this->db->query($sql);
	}
	
	function get_client_projects_list($per_page, $offset, $id){
		$per_page = (int) $per_page;
		$offset = (int) $offset;
		$id = (int) $id;


		$sql = "SELECT 	
					P.*, 
					C.name as client_name, 
					C.status as client_status, 
					C.email as client_email,
					PT.type as project_type,
					U.status as owner_status, 
					U.name as owner_name
				FROM 
					project as P 
				LEFT JOIN 
					client as C ON C.client_id = P.client_id
				LEFT JOIN 
					project_type as PT ON PT.type_id = P.type_id
				LEFT JOIN 
					user as U ON U.user_id = P.owner_id
				WHERE 
					P.client_id = '{$id}'
				ORDER BY 
					P.start_date DESC, P.project_id DESC
				LIMIT 
					{$offset}, {$per_page}";
		
		return $this->db->query($sql);
	}
	
	function get_types(){
		$sql = "SELECT * FROM project_type ORDER BY type_id ASC";
		
		return $this->db->query($sql);
	}
	
	function post_images($images_saved = array()){
		$images = array();
		if (($this->uri->segment(3) == "insert") || ($this->uri->segment(3) == "update")) {
			$images_post = $this->input->post("image_name");
			$image_path = $this->input->post("image_path");
			if((!empty($images_post)) && (!empty($image_path))){
				foreach($images_post as $key => $image){
					if(!empty($image)){
						$item = new stdClass();
						$item->path = $image_path[$key];
						$item->image = $image;
						$images[] = $item;
					}
				}
			}
		}else{
			if(!empty($images_saved)){
				foreach($images_saved as $image){
					$item = new stdClass();
					$item->path = base_url('assets/images/project/'. $image->image);
					$item->image = $image->image;
					$images[] = $item;				
				}
			}
		}
		
		return $images;
	}
	
	function save_images($saved_id = 0){
		if(!empty($saved_id)){
			$images = $this->post_images();
			$this->db->delete('project_image', array('project_id' => $saved_id));
			
			$images_saved = array();
			foreach($images as $image){
				if(strpos($image->path, "assets/upload/temp/") !== false){
					$config = array('source_image' => "assets/upload/temp/" . $image->image, 'new_image' => "assets/images/project/");
					$this->image_lib->clear();
					$this->image_lib->initialize($config);
					$this->image_lib->resize();
				}
				
				$item = new stdClass();
				$item->project_id = $saved_id;
				$item->image = $image->image;
				$images_saved[] = $this->db->insert("project_image", $item) ? TRUE : FALSE;

			}
		}
	}
	
	function fetch_images($project_id){
		$project_id = (int) $project_id;

		$sql = "SELECT * FROM project_image WHERE project_id = {$project_id}";

		return $this->db->query($sql);
	}
	
	function get_involved_users($project_id){
		$project_id = (int) $project_id;

		
		$project_time_entries = $this->db->query("SELECT * FROM time_entry WHERE project_id = '{$project_id}' ORDER BY date DESC, start_time DESC, time_entry_id DESC");
		
		$user_ids 		 = array();
		$all_user_ids  = array();
		$user_info = array();
		$hours 				 = array();
		$hours_total 		 = array();
		
		foreach($project_time_entries->result() as $time_entry){
			$all_user_ids[] = $time_entry->user_id;
			$hours[$time_entry->user_id][] = calculate_total_hours($time_entry->end_time, $time_entry->start_time);
			$hours_total[] = calculate_total_hours($time_entry->end_time, $time_entry->start_time);
		}
		
		$grand_total_hours 	= sum_hours($hours_total);
		$time_entry_counts 	= array_count_values($all_user_ids);
		$user_ids 		= array_unique($all_user_ids);
		
		foreach($user_ids as $user_id){
			$user_info_entry = $this->user_model->get_user($user_id);
			$user_info_entry->time_entry_count = $time_entry_counts[$user_id];
			$user_info_entry->hours_worked = sum_hours($hours[$user_id]);

			if(to_seconds($grand_total_hours) > 0.0) 
				$user_info_entry->percentage = (to_seconds(sum_hours($hours[$user_id])) * 100) / to_seconds($grand_total_hours);
			else 
				$user_info_entry->percentage = 0.0;
			
			$user_info[$user_id] = $user_info_entry;
		}
		
		return $user_info;
	}
	
	function get_project_hours($project_id){
		$project_id = (int) $project_id;


		$project_time_entries = $this->db->query("SELECT * FROM time_entry WHERE project_id = '{$project_id}' ORDER BY date DESC, start_time DESC, time_entry_id DESC");
		
		$hours = array();
		foreach($project_time_entries->result() as $time_entry)
			$hours[] = calculate_total_hours($time_entry->end_time, $time_entry->start_time);
		
		$project = new stdClass();
		$project->total_hours 	= sum_hours($hours);
		$project->time_entry_count = $project_time_entries->num_rows();
		
		return $project;
	}
	
	
	
}