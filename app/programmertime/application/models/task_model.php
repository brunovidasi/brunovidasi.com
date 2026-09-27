<?php  

class Task_model extends CI_Model {

    function __construct() {
        parent::__construct();
    }

    function post($method = 'insert'){
		if($this->input->server('REQUEST_METHOD') == 'POST'){

			$data = new stdClass();

			$data->project_id					= (int) $this->input->post('project_id', TRUE);
			$data->phase_id						= id_or_null($this->input->post('phase_id', TRUE));
			$data->name						= $this->input->post('name', TRUE);
			$data->description					= $this->input->post('description');
			$data->hours 						= (int) $this->input->post('hours', TRUE);
			$data->due_date 					= fdate($this->input->post('date', TRUE), "-") . " 00:00:00";
			$data->owner_id		= id_or_null($this->input->post('user_id', TRUE));
			
			if($method == 'insert'){
				$data->status	 				= 'not_started';
				$data->created_by		= $this->session->userdata('id');
				$data->created_at			= date('Y-m-d H:i:s');
			}

			elseif($method == 'update'){
				// do nothing 
			}

			return $data;
		}
		return FALSE;
	}

	function insert($data){
		return ($this->db->insert("task", $data)) ? $this->id = $this->db->insert_id() : 0;
	}

	function update($id, $data){
		$id = (int) $id;
		if($id > 0){		
			$this->db->where('task_id', $id);
			return ($this->db->update('task', $data)) ? TRUE : FALSE;
		}
		return FALSE;
	}

	function delete($id){
		$id = (int) $id;
		if($id > 0)
			return $this->db->delete('task', array('task_id' => $id)) ? TRUE : FALSE;
		
		return FALSE;
	}

	function status($id, $status){
		$id = (int) $id;
		if($id > 0){
		
			$task = new stdClass();
			$task->status = $status;
			
			$this->db->where('task_id', $id);
			if($this->db->update('task', $task))
				return TRUE;
			
			return FALSE;
		}
		return FALSE;
	}
	
	function get_task($task_id){

		$task_id = (int) $task_id;

		$sql = "SELECT 	
					T.*,
					U.name as owner,
					U.image as owner_image,
					U.color as owner_color,
					CB.name as created_by_name,
					P.name as project_name,
					PH.phase as phase,
					C.client_id as client_id,
					C.name as client_name
				FROM 
					task as T
				LEFT JOIN 
					project as P ON T.project_id = P.project_id
				LEFT JOIN 
					project_phase as PH ON PH.phase_id = T.phase_id
				LEFT JOIN 
					user as U ON T.owner_id = U.user_id
				LEFT JOIN 
					user as CB ON T.created_by = CB.user_id
				LEFT JOIN 
					client as C ON P.client_id = C.client_id
				
				WHERE
					T.task_id = {$task_id}
				";
		
		$task = $this->db->query($sql)->row();
		if(empty($task)) return FALSE;

		$task->estimated_hours = $task->hours . ':00';
		$task->estimated_hrs = $task->hours;

		$sql = "SELECT 
					TE.*,
					U.name as user
				FROM 
					time_entry as TE
				LEFT JOIN
					user as U ON TE.user_id = U.user_id
				WHERE
					TE.task_id = '{$task->task_id}'

				ORDER BY date ASC, start_time ASC, time_entry_id ASC
		";

		$hours = $this->db->query($sql);

		$task->time_entry_count = $hours->num_rows();

		$total_hours = [];
		
		foreach($hours->result() as $time_entry){
			$time_entry->total_time = calculate_total_hours($time_entry->end_time, $time_entry->start_time);
			$total_hours[] = $time_entry->total_time;
		}

		$task->hours = $hours->result();
		$task->total_hours = sum_hours($total_hours);
		$task->time_entry_count = $hours->num_rows();
		$task->hours_remaining = '';
		$task->percentage = '';

		return $task;
	}

	function get_tasks($project_id = 0, $user_id = 0, $order = "ASC", $per_page = 0, $offset = 0, $status = "", $result = 'result', $exclude_status = ""){
		$exclude_status = $this->db->escape_str((string) $exclude_status);


		$limit = "";
		$where = " WHERE T.task_id > 0 ";

		$status = strsql($status);
		$order 	= (strtoupper((string) $order) == 'DESC') ? 'DESC' : 'ASC';
		$per_page = (int) $per_page;
		$offset = (int) $offset;

		$project_id 	= (int) $project_id;
		$user_id 	= (int) $user_id;

		if(!empty($project_id)) 	$where .= " AND T.project_id = '{$project_id}' ";
		if(!empty($user_id)) 	$where .= " AND T.owner_id = '{$user_id}' ";
		if(!empty($status)) 	$where .= " AND T.status = '{$status}' ";
		if(!empty($exclude_status)) $where .= " AND T.status != '{$exclude_status}' ";

		if((!empty($offset)) OR (!empty($per_page))) $limit = " LIMIT {$offset}, {$per_page} ";
		
		$sql = "SELECT 	
					T.*,
					U.name as owner,
					U.image as owner_image,
					U.color as owner_color,
					P.name as project_name
				FROM 
					task as T
				LEFT JOIN 
					project as P ON T.project_id = P.project_id
				LEFT JOIN 
					user as U ON T.owner_id = U.user_id
				
				{$where}
				
				ORDER BY 
					T.created_at {$order}, T.task_id {$order}

				{$limit}
		";
		
		$tasks = $this->db->query($sql);

		// echo '<pre>';
		// print_r($sql);
		// echo '</pre>';
		// die();

		if($result == 'count')
			return $tasks->num_rows();
		else
			$tasks = $tasks->result();

		foreach($tasks as $task){

			$sql = "SELECT 
						TE.* 
					FROM 
						time_entry as TE
					WHERE
						TE.task_id = '{$task->task_id}'

					ORDER BY date DESC, start_time DESC, time_entry_id DESC
			";

			$hours = $this->db->query($sql);

			$task->time_entry_count = $hours->num_rows();

			$total_hours = [];
			
			foreach($hours->result() as $time_entry){
				$total_hours[] = calculate_total_hours($time_entry->end_time, $time_entry->start_time);
			}

			$task->total_hours = sum_hours($total_hours);
			$task->time_entry_count = $hours->num_rows();
			$task->hours_remaining = '';
			$task->percentage = '';

			unset($sql);
			unset($hours);
			unset($total_hours);
			unset($task);

		}

		return $tasks;
	}

}