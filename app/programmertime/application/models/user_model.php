<?php  

class User_model extends CI_Model {

    function __construct() {
        parent::__construct();
    }
	
	function post($method = "insert"){
		if($this->input->server('REQUEST_METHOD') == 'POST'){
			
			$login = $this->input->post('login', TRUE);

			$data = new stdClass();

			$data->login		 			= $login;
			$data->name						= ucwords($this->input->post('name', TRUE));
			$data->email		 			= $this->input->post('email', TRUE);
			$data->employee_id		 		= $this->input->post('employee_id', TRUE);
			$data->id_number		 		= $this->input->post('id_number', TRUE);
			$data->tax_id		 			= trim($this->input->post('tax_id', TRUE));
			$data->access_level	 			= id_or_null($this->input->post('access_level', TRUE));
			
			$birth_date = $this->input->post('birth_date', TRUE);
			if(!empty($birth_date)) $data->birth_date = fdate($birth_date, "-");

			if($method == "insert"){
				
				$salt = generate_salt();

				$data->salt 				= $salt;
				$data->password		 		= hash_password($this->input->post('password', TRUE), $salt);
				$data->email_token			= generate_confirmation_code(strlen($login));
				$data->created_at 		= date('Y-m-d H:i:s');
				$data->status		 		= 'active';
				$data->image	 			= 'none.png';
				// $data->confirmed	= 'no';
				$data->confirmed	= 'yes';
				
			}

			elseif($method == "update"){

				$user = $this->user_model->get_user_by_login($login)->row();

				$data->color 				= $this->input->post('color', TRUE);
				$data->reload			= "yes";

				$status = $this->input->post('status', TRUE);
				if(!empty($status)) $data->status = $this->input->post('status', TRUE);

				$password = $this->input->post('password', TRUE);
				if(!empty($password)) $data->password = hash_password($password, $user->salt);
			}

			return $data;
		}

		return FALSE;
	}
	
	function post_first_access(){
		if ($this->input->server('REQUEST_METHOD') == 'POST') {
			
			$login = $this->input->post('login', TRUE);
			$salt = generate_salt();

			$data = new stdClass();

			$data->login		 		= $login;
			$data->salt 				= $salt;
			$data->password		 		= hash_password($this->input->post('password', TRUE), $salt);
			$data->name				= $this->input->post('name', TRUE);
			$data->email		 		= $this->input->post('email', TRUE);
			$data->email_token			= generate_confirmation_code(strlen($login));
			$data->created_at 		= date('Y-m-d H:i:s');
			$data->access_level	 	= '2'; // Manager
			$data->status		 		= 'active';
			$data->image	 			= 'none.png';
			$data->confirmed	= 'no';

			return $data;
		}

		return FALSE;
	}
	
	function get_projects($id, $limit = null){
		$id = (int) $id;
		$limit = (int) $limit;

		$sql = "SELECT 	
					P.*, 
					C.name as client_name, 
					C.status as client_status, 
					C.email as client_email,
					PT.type as project_type
				FROM 
					project as P
				LEFT JOIN 
					client as C ON C.client_id = P.client_id
				LEFT JOIN 
					project_type as PT ON PT.type_id = P.type_id
				WHERE 
					P.owner_id = '{$id}' 
				ORDER BY 
					P.start_date DESC"; 
				
		if(!empty($limit)) $sql .= " LIMIT {$limit}";
		
		return $this->db->query($sql);
	}
	
	function get_time_entries($id, $limit = null){
		$id = (int) $id;
		$limit = (int) $limit;

		$sql = "SELECT 	
					TE.*, 
					P.priority as priority, 
					P.name as project_name,
					PH.phase as phase
				FROM 
					time_entry as TE
				LEFT JOIN 
					project as P ON TE.project_id = P.project_id
				LEFT JOIN 
					project_phase as PH ON TE.phase_id = PH.phase_id
				WHERE 
					TE.user_id = '{$id}'
				ORDER BY 
					TE.date DESC, TE.time_entry_id DESC";
				
		if(!empty($limit)) $sql .= " LIMIT {$limit}";
		
		return $this->db->query($sql);
	}
	
	function get_user($id){
		$id = (int) $id;

		$sql = "SELECT 
					U.*, AL.role as role 
				FROM 
					user as U 
				LEFT JOIN 
					access_level as AL ON U.access_level = AL.id
				WHERE 
					U.user_id = '{$id}' 
				LIMIT 
					1";
		
		return $this->db->query($sql);
	}
	
	function get_users($term = ""){

		$term = strsql($term);

		$sql = "SELECT 
					U.*, 
					AL.role as role 
				FROM 
					user as U 
				LEFT JOIN 
					access_level as AL ON U.access_level = AL.id
				WHERE 
					((U.name LIKE '%{$term}%') 
				OR 
					(U.email LIKE '%{$term}%') 
				OR 
					(U.login LIKE '%{$term}%') 
				OR 
					(U.employee_id LIKE '%{$term}%') 
				OR 
					(AL.role LIKE '%{$term}%')) 
				ORDER BY 
					U.user_id ASC";
		
		return $this->db->query($sql);
	}
	
	function get_active_users($term = ""){

		$term = strsql($term);

		$sql = "SELECT 
					U.*, 
					AL.role as role 
				FROM 
					user as U 
				LEFT JOIN 
					access_level as AL ON U.access_level = AL.id
				WHERE 
					U.status='active' 
				AND
					((U.name LIKE '%{$term}%') 
				OR 
					(U.email LIKE '%{$term}%') 
				OR 
					(U.login LIKE '%{$term}%') 
				OR 
					(U.employee_id LIKE '%{$term}%') 
				OR 
					(AL.role LIKE '%{$term}%')) 
				ORDER BY 
					U.name ASC";
		
		return $this->db->query($sql);
	}
	
	function get_users_list($per_page, $offset, $term = ""){
		$per_page = (int) $per_page;
		$offset = (int) $offset;


		$term = strsql($term);
		
		$sql = "SELECT 
					U.*, AL.role as role 
				FROM 
					user as U 
				LEFT JOIN 
					access_level as AL ON U.access_level = AL.id
				WHERE 
					((U.name LIKE '%{$term}%') 
				OR 
					(U.email LIKE '%{$term}%') 
				OR 
					(U.login LIKE '%{$term}%') 
				OR 
					(U.employee_id LIKE '%{$term}%') 
				OR 
					(AL.role LIKE '%{$term}%'))
				ORDER BY 
					U.status ASC, U.created_at DESC, U.user_id ASC 
				LIMIT 
					{$offset}, {$per_page}";
		
		return $this->db->query($sql);
	}
	
	function get_access_levels(){
		$sql = "SELECT * FROM access_level ORDER BY id ASC";
		
		return $this->db->query($sql);
	}
	
	function get_user_by_login($login){
		$login = $this->db->escape_str((string) $login);

		$sql = "SELECT * FROM user WHERE login = '{$login}'";
		
		return $this->db->query($sql);
	}

	function get_user_by_email($email){
		$email = $this->db->escape_str((string) $email);

		$sql = "SELECT * FROM user WHERE email = '{$email}'";
		
		return $this->db->query($sql);
	}
	
	function get_images(){
		$sql = "SELECT image FROM user";
		
		return $this->db->query($sql);
	}
	
	function insert($data = FALSE){
		if($data){
			if($data->access_level != '1'){
				if ($this->db->insert("user", $data)){
					$this->send_email->confirm_registration($data->email, $data->name, $data->login, $this->input->post('password'), $data->email_token);
					return $this->db->insert_id();
				}
			}
		}
		return 0;
	}
	
	function update($id, $data = FALSE){
        if($data){
        	$id = (int) $id;
	        if ($id > 0) {
	            $this->db->where('user_id', $id);
				if($this->db->update('user', $data)){
					return TRUE;
				}
	        }
        }
        return FALSE;
	}

	function reload($yes_not){
        $id = (int) $this->session->userdata('id');
        $user = new stdClass();
        $user->reload = $yes_not;

        if ($id > 0) {
            $this->db->where('user_id', $id);
			if($this->db->update('user', $user)){
				return TRUE;
			}
			return FALSE;
        }
        return FALSE;
	}
	
	function get_id_by_email_token($code){
		$code = $this->db->escape_str((string) $code);
		if($code === '') return 0;

		$query = $this->db->query("SELECT user_id FROM user WHERE email_token = '{$code}'");

		if($query->num_rows() > 0){
		   $user = $query->row(); 
		   return $user->user_id;
		}
		
		return 0;
	}
	
	function confirm_email($id){
        if ($id > 0) {
            $this->db->where('user_id', $id);
			
			$confirmation = new stdClass();
			$confirmation->confirmed = 'yes';
			$confirmation->reload = 'yes';
			
			if($this->db->update('user', $confirmation)){
				return TRUE;
			}
			return FALSE;
        }
        return FALSE;
	}
	
	function last_access($id){
        $id = (int) $id;
        if ($id > 0) {
		
			$last_access = array(
			   'last_access' => date('Y-m-d H:i:s')
			);
		
			$this->db->where('user_id', $id);
			$updated = $this->db->update('user', $last_access);
			
			if($updated) 
				return TRUE;

			return FALSE;
		}
        return FALSE;
	}

	function change_status($id, $status = 'active'){
		$id = (int) $id;
		if($id > 0){
			$data = new stdClass();
			$data->status = $status;

			$this->db->where('user_id', $id);
			return ($this->db->update('user', $data)) ? TRUE : FALSE;
		}
		return FALSE;
	}

	function post_images($images_saved = array()){
		$images = array();
		if (($this->uri->segment(2) == "insert") || ($this->uri->segment(2) == "update")) {
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
					$item->path = base_url('assets/images/users/'. $image->name);
					$item->image = $image->name;
					$images[] = $item;				
				}
			}
		}
		
		return $images;
	}
	
	function save_images($saved_id = 0){
		if(!empty($saved_id)){
			$images = $this->post_images();
			
			$images_saved = array();
			foreach($images as $image){
				if(strpos($image->path, "assets/images/temp/") !== false){
					$config = array('source_image' => "assets/images/temp/" . $image->image, 'new_image' => "assets/images/users/");
					$this->image_lib->clear();
					$this->image_lib->initialize($config);
					$this->image_lib->resize();
				}
				
				$item = new stdClass();
				$item->image = $image->image;
				
				$this->db->where('user_id', $saved_id);
				$images_saved[] = $this->db->update("user", $item) ? TRUE : FALSE;
				
			}

			return $images_saved;
		}
	}
	
	function fetch_images($user_id){
		$user_id = (int) $user_id;


		// TODO: rename this method to get_image
		$sql = "SELECT image FROM user WHERE user_id = {$user_id}";
		
		return $this->db->query($sql)->result();

		// $this->db->select('image');
		// $this->db->from('user');
		// $this->db->where("user_id", $user_id);	
		// return $this->db->get()->result();
	}

	function get_hours_worked($user_id){
		$user_id = (int) $user_id;


		$time_entries = $this->db->query("SELECT * FROM time_entry WHERE user_id = '{$user_id}' ORDER BY date DESC, start_time DESC, time_entry_id DESC");

		foreach($time_entries->result() as $entry) 
			$dates[$entry->date][] = calculate_total_hours($entry->end_time, $entry->start_time);

		$total = array();
		
		if(isset($dates))
			foreach($dates as $date => $hours) $total[$date] = sum_hours($hours);

		return $total;
	}
	
	function get_projects_involved($user_id){
		$user_id = (int) $user_id;

		$projects_involved = $this->db->query("SELECT * FROM time_entry WHERE user_id = '{$user_id}' ORDER BY date DESC, start_time DESC, time_entry_id DESC");
		
		$project_ids = array();
		$all_project_ids = array();
		$project_info = array();
		
		// $hours_total = [];
		$hours_total = array();
		
		foreach($projects_involved->result() as $time_entry){
			$all_project_ids[] = $time_entry->project_id;
			$hours[$time_entry->project_id][] = calculate_total_hours($time_entry->end_time, $time_entry->start_time);
			$hours_total[] = calculate_total_hours($time_entry->end_time, $time_entry->start_time);
		}
		
		$grand_total_hours = sum_hours($hours_total);
		$project_ids = array_unique($all_project_ids);
		$time_entry_counts = array_count_values($all_project_ids);
		
		foreach($project_ids as $project_id){
			$project_row = $this->project_model->get_project($project_id);
			$project_row->time_entry_count = $time_entry_counts[$project_id];
			$project_row->hours_worked = sum_hours($hours[$project_id]);
			if(to_seconds($grand_total_hours) != 0){
				$project_row->percentage = (to_seconds(sum_hours($hours[$project_id])) * 100) / to_seconds($grand_total_hours);
			}else{
				$project_row->percentage = null;
			}
			$project_info[$project_id] = $project_row;
		}
		
		return $project_info;
	}

}