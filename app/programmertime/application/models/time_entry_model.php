<?php  

class Time_entry_model extends CI_Model {

    function __construct() {
        parent::__construct();
    }
	
	function post($method = 'insert'){
	    if($this->input->server('REQUEST_METHOD') == 'POST'){

	    	$data = new stdClass();

	    	if($method == 'insert'){	
	            $data->user_id			= $this->input->post('user_id', TRUE);
	            $data->project_id 	 		= $this->input->post('project_id', TRUE);
	            $data->task_id 	 		= id_or_null($this->input->post('task_id', TRUE));
				$data->phase_id 	     		= id_or_null($this->input->post('phase_id', TRUE));
				$data->technical_description    = $this->input->post('technical_description', TRUE);
				$data->client_description    = $this->input->post('client_description', TRUE);
				$data->date          		= fdate($this->input->post('date', TRUE), "-");
	            $data->start_time   	 		= $this->input->post('start_time', TRUE);
	            $data->backdated			= backdated($this->input->post('date', TRUE), $this->input->post('start_time', TRUE).':00');
	            $data->created_at		= date('Y-m-d H:i:s');

	            if($data->task_id == 0)
	            	unset($data->task_id);
	    	}

	    	if($method == 'update'){
	    		$data->end_time	    	 		= $this->input->post('end_time', TRUE);
				$data->technical_description	= $this->input->post('technical_description', TRUE);
				$data->client_description	= $this->input->post('client_description', TRUE);
	            $data->time_entry_id       		= $this->input->post('time_entry_id', TRUE);
	            $data->finished_at			= date('Y-m-d H:i:s');
	    	}

	    	if($method == 'edit'){
	    		$data->user_id			= $this->input->post('user_id', TRUE);
	            $data->project_id 	 		= $this->input->post('project_id', TRUE);
	            $data->task_id 	 		= id_or_null($this->input->post('task_id', TRUE));
				$data->phase_id 	     		= id_or_null($this->input->post('phase_id', TRUE));
				$data->technical_description    = $this->input->post('technical_description', TRUE);
				$data->client_description    = $this->input->post('client_description', TRUE);
				$data->date          		= fdate($this->input->post('date', TRUE), "-");
	            $data->start_time   	 		= $this->input->post('start_time', TRUE);
	            $data->end_time		   	 		= $this->input->post('end_time', TRUE);

	            if($data->task_id == 0)
	            	unset($data->task_id);
	    	}

	    	return $data;
		}

		return FALSE;
	}
	
	function insert($data){
	    if ($this->db->insert("time_entry", $data)){
			
			$this->project_model->status($data->project_id, 'in_progress');

			if($data->task_id > 0)
				$this->task_model->status($data->task_id, 'in_progress');

            return $this->db->insert_id();
        }
        return 0;
	}
	
	function update($id, $data){
        $id = (int) $id;
        if ($id > 0) {		
            $this->db->where('time_entry_id', $id);
            return ($this->db->update('time_entry', $data)) ? TRUE : FALSE;
        }
        return FALSE;
    }
	
	function delete($id){
		$id = (int) $id;
        if ($id > 0) {
			return $this->db->delete('time_entry', array('time_entry_id' => $id)) ? TRUE : FALSE ; 
        }
        return FALSE;
	}
	
	function open_time_entry($id){
		$id = (int) $id;

	    $sql = "SELECT 	
	    			TE.*,
					PH.phase as phase,
					U.name as owner,
					P.name as project_name,
					P.description as project_description,
					P.priority as project_priority	
				FROM 
					time_entry as TE
				LEFT JOIN 
					project as P ON TE.project_id = P.project_id
				LEFT JOIN 
					project_phase as PH ON TE.phase_id = PH.phase_id
				LEFT JOIN 
					user as U ON TE.user_id = U.user_id
				WHERE 
					(TE.user_id='{$id}') 
				AND 
					(TE.end_time IS NULL OR TE.end_time = 0) 
				LIMIT 1";
		
		return $this->db->query($sql);
	}
	
	function get_time_entry($time_entry_id){
		$time_entry_id = (int) $time_entry_id;

		$sql = "SELECT * FROM time_entry WHERE time_entry_id='{$time_entry_id}' LIMIT 1";
		
		return $this->db->query($sql);
	}
	
	function get_time_entries($project_id=""){
		$project_id = (int) $project_id;

		if(!empty($project_id))
			$sql = "SELECT 	
					TE.*,
					PH.phase as phase,
					U.name as owner,
					P.name as project_name
				FROM 
					time_entry as TE
				LEFT JOIN 
					project as P ON TE.project_id = P.project_id
				LEFT JOIN 
					project_phase as PH ON TE.phase_id = PH.phase_id
				LEFT JOIN 
					user as U ON TE.user_id = U.user_id 
				WHERE 
					TE.project_id='{$project_id}' 
				ORDER BY 
					TE.date DESC, TE.start_time DESC";
		else
			$sql = "SELECT * FROM time_entry ORDER BY date DESC, start_time DESC";
		
		return $this->db->query($sql);
	}
	
	function get_time_entries_report($project_id="", $order="ASC", $per_page="", $offset="", $in_progress=FALSE){
		$project_id = (int) $project_id;
		$order = (strtoupper((string) $order) == 'DESC') ? 'DESC' : 'ASC';
		$per_page = (int) $per_page;
		$offset = (int) $offset;

		$limit = "";
		$where = "";
		
		if($in_progress) $where .= " WHERE TE.end_time IS NULL ";
		else $where .= " WHERE TE.end_time IS NOT NULL ";

		if(!empty($project_id)) $where .= " AND TE.project_id='{$project_id}'";
		
		if((!empty($offset)) OR (!empty($per_page))) $limit = "LIMIT {$offset}, {$per_page}";
		
		$sql = "SELECT 	
					TE.*,
					PH.phase as phase,
					U.name as owner,
					P.name as project_name
				FROM 
					time_entry as TE
				LEFT JOIN 
					project as P ON TE.project_id = P.project_id
				LEFT JOIN 
					project_phase as PH ON TE.phase_id = PH.phase_id
				LEFT JOIN 
					user as U ON TE.user_id = U.user_id
				
				{$where}
				
				ORDER BY date {$order},  start_time {$order}  
				{$limit}";
		
		return $this->db->query($sql);
	}

	function get_time_entry_report($time_entry_id = NULL){
		$time_entry_id = (int) $time_entry_id;

		
		$sql = "SELECT 	
					TE.*,
					PH.phase as phase,
					U.name as owner,
					P.name as project_name
				FROM 
					time_entry  as TE
				LEFT JOIN 
					project as P ON TE.project_id = P.project_id
				LEFT JOIN 
					project_phase as PH ON TE.phase_id = PH.phase_id
				LEFT JOIN 
					user as U ON TE.user_id = U.user_id
				
				WHERE TE.time_entry_id='{$time_entry_id}'
				
				LIMIT 1";
		
		return $this->db->query($sql);
	}
	
	function get_user_time_entries($project_id, $user_id, $phase_id){
		$project_id = (int) $project_id;
		$user_id = (int) $user_id;
		$phase_id = (int) $phase_id;

		$sql = "SELECT 	*FROM time_entry WHERE ((project_id = '{$project_id}') AND (user_id = '{$user_id}') AND (phase_id = '{$phase_id}'))";
		
		return $this->db->query($sql);
	}
	
	function get_time_entries_in_progress($project_id="", $order="ASC", $per_page="", $offset=""){
		$project_id = (int) $project_id;
		$order = (strtoupper((string) $order) == 'DESC') ? 'DESC' : 'ASC';
		$per_page = (int) $per_page;
		$offset = (int) $offset;

		
		if(!empty($project_id)){
			$where = "WHERE project_id='{$project_id}'";
			$limit = "";
		}else{
			$where = "";
			$limit = "LIMIT {$offset}, {$per_page}";
		}
		
		$sql = "SELECT 	
					TE.*,
					PH.phase as phase,
					U.name as owner,
					P.name as project_name
				FROM 
					time_entry  as TE
				LEFT JOIN 
					project as P ON TE.project_id = P.project_id
				LEFT JOIN 
					project_phase as PH ON TE.phase_id = PH.phase_id
				LEFT JOIN 
					user as U ON TE.user_id = U.user_id
				
				{$where}
				
				ORDER BY date {$order} 
				{$limit}";
		
		return $this->db->query($sql);
	}
	
	function get_user_info($project_id, $user_id, $phase_id){
		$project_id = (int) $project_id;
		$user_id = (int) $user_id;
		$phase_id = (int) $phase_id;

	
		$sql = "SELECT 	
					TE.*,
					PH.phase as phase,
					U.name as owner
				FROM 
					time_entry as TE
				LEFT JOIN 
					project_phase as PH ON TE.phase_id = PH.phase_id
				LEFT JOIN 
					user as U ON U.user_id = {$user_id}
				
				WHERE 
					(TE.project_id='{$project_id}') 
				AND 
					(TE.user_id='{$user_id}') 
				AND 
					(TE.phase_id='{$phase_id}')
				
				ORDER BY date DESC";
		
		return $this->db->query($sql);
	}
	
	function get_project_time_entries($project_id){
		$project_id = (int) $project_id;

		$sql = "SELECT * FROM time_entry WHERE project_id='{$project_id}'";
		
		return $this->db->query($sql);
	}
	
	function get_client_id($project_id){
		$project_id = (int) $project_id;

		$project = $this->db->query("SELECT client_id FROM project WHERE project_id='{$project_id}'")->row();

		return empty($project) ? NULL : $project->client_id;
	}
	
	function get_phases(){
		$sql = "SELECT * FROM project_phase ORDER BY phase_id";
		
		return $this->db->query($sql);
	}
	
	function get_phase($id){
		$id = (int) $id;

		$sql = "SELECT * FROM project_phase WHERE phase_id='{$id}'";
		
		return $this->db->query($sql);
	}
	
	function get_client($client_id){
		$client_id = (int) $client_id;

		$sql = "SELECT * FROM client WHERE client_id='{$client_id}' LIMIT 1";
		
		return $this->db->query($sql);
	}
	
	function get_client_projects($client_id){
		$client_id = (int) $client_id;

		$sql = "SELECT 
					* 
				FROM 
					project 
				WHERE 
					((status = 'not_started') 
					OR 
					(status = 'in_progress') 
					OR 
					(status = 'paused'))
				AND 
					(client_id = '{$client_id}')
				ORDER BY 
					deadline ASC";
		
		return $this->db->query($sql);
	}
	
	function get_time_entry_project($project_id){
		$project_id = (int) $project_id;

		$sql = "SELECT * FROM project WHERE project_id='{$project_id}'";
		
		return $this->db->query($sql);
	}
	
}