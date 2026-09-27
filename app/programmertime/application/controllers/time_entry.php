<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Time_entry extends CI_Controller {

	public function index(){
		$this->start();
	}
	
	public function start($project_id = "", $task_id = ""){

		$open_time_entry = $this->time_entry_model->open_time_entry($this->session->userdata('id'));
		if($open_time_entry->num_rows() > 0) redirect('/time_entry/finish/');
		
		$data['project_options'] = $this->get_project_options($this->input->post('client_id'));
		$data['task_options'] = $this->get_task_options($this->input->post('project_id'));
		$data['clients'] 	= $this->client_model->get_clients();
		$data['phases'] 	= $this->time_entry_model->get_phases();
		
		$data['client_id'] = "";
		$data['project_id'] = "";
		$data['project'] 	 = "";
		$data['tasks'] 	 = "";

		if(!empty($project_id)){

			$project_id = (int) $project_id;
			$project = $this->project_model->get_project($project_id);
			
			$client_id = "";
			if($project->num_rows() > 0){
			   $pro = $project->row(); 
			   $client_id = $pro->client_id;
			}
			
			$data['tasks']	 = $this->task_model->get_tasks($project_id);
			$data['project'] 	 = $project_id;
			$data['project_id'] = $project_id;
			$data['client_id'] = $client_id;
			$data['task_id']  = $task_id;
			$data['project_options']  = $this->get_project_options($client_id, $project_id);
			$data['task_options']  = $this->get_task_options($project_id, $task_id);
		}
		
		// print_r($data);
		// die();

		$data['background'] = TRUE;
		$data['view'] = $this->load->view('time_entry/start', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	public function finish(){

		$open_time_entry = $this->time_entry_model->open_time_entry($this->session->userdata('id'));
		if($open_time_entry->num_rows() == 0) redirect('/time_entry/start/');
		
		$time_entries = $this->time_entry_model->open_time_entry($this->session->userdata('id'));
		if($time_entries->num_rows() > 0){
		   $time_entry 		= $time_entries->row(); 
		   $project_id 	= $time_entry->project_id;
		   $task_id 	= $time_entry->task_id;
		}

		$data['client_id'] = $this->time_entry_model->get_client_id($project_id);
		$data['project'] 	= $this->project_model->get_project($project_id);
		$data['entry'] 	= $time_entries;
		$data['ret'] 		= $time_entries->row();
		$data['clients'] 	= $this->client_model->get_clients();
		$data['phases'] 	= $this->time_entry_model->get_phases();
		$data['tasks'] 	= $this->get_task_options($project_id, $task_id);
		
		$data['background'] = TRUE;
		$data['view'] = $this->load->view('time_entry/finish', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	public function edit($time_entry_id = ""){

		if(empty($time_entry_id)){
			$this->session->set_flashdata('message_warning', lang('msg_time_entry_not_found'));
			redirect('/project/');
		}

		$time_entry = $this->time_entry_model->get_time_entry($time_entry_id);
		$project_id = "";

		if($time_entry->num_rows() > 0){
			$et 		= $time_entry->row(); 
			$project_id 	= $et->project_id;
			$task_id 	= $et->task_id;
			$end_time 		= $et->end_time;
		}else{
			$this->session->set_flashdata('message_warning', lang('msg_time_entry_not_found'));
			redirect('/project/');
		}
		
		if(empty($end_time)){
			$this->session->set_flashdata('message_warning', lang('msg_time_entry_error_in_progress'));
			redirect('/project/view/'.$project_id);
		}
		
		$data['client_id'] 	= $this->time_entry_model->get_client_id($project_id);
		$data['project'] 		= $this->project_model->get_project($project_id);
		$data['project_id'] 	= $project_id;
		$data['task_id'] 		= $task_id;
		$data['task'] 		= $this->task_model->get_task($task_id);
		
		$data['entry'] 		= $time_entry;
		$data['ret'] 			= $time_entry->row();
		$data['clients'] 		= $this->client_model->get_clients();
		$data['phases'] 		= $this->time_entry_model->get_phases();
		$data['tasks'] 		= $this->task_model->get_tasks($project_id);
		$data['task_options']  	= $this->get_task_options($project_id, $task_id);
		
		$data['background'] = TRUE;
		$data['view'] = $this->load->view('time_entry/edit', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	public function list(){

		$offset = (!$this->uri->segment("3")) ? 0 : $this->uri->segment("3");
		$per_page = 15;
		
		$config['base_url']    	= site_url('time_entry/list/');
		$config['total_rows'] 	= $this->time_entry_model->get_time_entries_report("", "DESC")->num_rows;
		$config['per_page']    	= $per_page;
		$config['uri_segment'] 	= 3;
		
		$this->pagination->initialize($config);
		
		$data["pagination"]    	= $this->pagination->create_links();
		$data['time_entries']		= $this->time_entry_model->get_time_entries_report("", "DESC", $per_page, $offset);
		$data['time_entries_in_progress'] = $this->time_entry_model->get_time_entries_report("", "DESC", $per_page, $offset, TRUE);
		
		$data['view'] = $this->load->view('time_entry/list', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	public function insert(){
        if ($this->validate_form('insert')){
            $data = $this->time_entry_model->post('insert');
			
			$time_entry_id = $this->time_entry_model->insert($data);
			
            if($time_entry_id > 0)
			    $this->session->set_flashdata('message_success', lang('msg_time_entry_create_success'));
            else
				$this->session->set_flashdata('message_error', lang('msg_time_entry_create_error'));
			
			redirect('/time_entry/start/');
        }
		$this->start();
    }
	
	public function update(){
		$id = $this->input->post('time_entry_id');
		
        if(!empty($id)){
			if($this->validate_form('update', $id)){
				
				$data = $this->time_entry_model->post('update');	
			    $updated = $this->time_entry_model->update($id, $data);				
				
				if($updated)
				    $this->session->set_flashdata('message_success', lang('msg_time_entry_finish_success'));
				else
					$this->session->set_flashdata('message_error', lang('msg_time_entry_finish_error'));

		        redirect('/time_entry/');
			}
	    }
		$this->finish();
	}
	
	// Saves the edit form (edit() shows it).
	public function save(){
		$id = $this->input->post('time_entry_id');
		
        if(!empty($id)){
			if($this->validate_form('edit', $id)){
				
				$data = $this->time_entry_model->post('edit');	
			    $updated = $this->time_entry_model->update($id, $data);				
				
				if($updated)
				    $this->session->set_flashdata('message_success', lang('msg_time_entry_edit_success'));
				else
					$this->session->set_flashdata('message_error', lang('msg_time_entry_edit_error'));
				
		        redirect('/time_entry/edit/'.$id);
			}
			$this->edit($id);
	    }
	}
	
	public function delete($time_entry_id = 0){
		$deleted = $this->time_entry_model->delete($time_entry_id);
		
		if($deleted){
			$this->session->set_flashdata('message_success', lang('msg_time_entry_delete_success'));
			redirect('/project/');
		}
		$this->session->set_flashdata('message_error', lang('msg_time_entry_delete_error'));
		redirect('/project/');	
	}
	
	private function validate_form($method = "insert", $id = null){

		if($method == "insert"){
			$this->form_validation->set_rules('user_id',			lang('lbl_user'), 			'required');
			$this->form_validation->set_rules('client_id',			lang('lbl_client'),			'required');
			$this->form_validation->set_rules('project_id',			lang('lbl_project'),			'required');
			$this->form_validation->set_rules('phase_id',				lang('lbl_phase'),				'required');
			$this->form_validation->set_rules('technical_description',	lang('lbl_technical_description'),	'required');
			$this->form_validation->set_rules('client_description',	lang('lbl_client_description'),	'required');
			$this->form_validation->set_rules('date',				lang('lbl_date'),				'required|callback_validate_date');
			$this->form_validation->set_rules('start_time',				lang('lbl_start_time'),		'required|callback_check_hours');
		}

		if($method == "update"){
			$this->form_validation->set_rules('technical_description',	lang('lbl_technical_description'),	'required');
			$this->form_validation->set_rules('client_description',	lang('lbl_client_description'),	'required');
			$this->form_validation->set_rules('start_time',				lang('lbl_start_time'),		'required|callback_check_hours');
			$this->form_validation->set_rules('end_time',				lang('lbl_end_time'),			'required|callback_validate_hours|callback_check_hours');
			$this->form_validation->set_rules('time_entry_id',       		lang('lbl_time_entry'),   			'required');
		}

		if($method == 'edit'){
			$this->form_validation->set_rules('user_id',			lang('lbl_user'), 			'required');
			$this->form_validation->set_rules('client_id',			lang('lbl_client'),			'required');
			$this->form_validation->set_rules('project_id',			lang('lbl_project'),			'required');
			$this->form_validation->set_rules('phase_id',				lang('lbl_phase'),				'required');
			$this->form_validation->set_rules('technical_description',	lang('lbl_technical_description'),	'required');
			$this->form_validation->set_rules('client_description',	lang('lbl_client_description'),	'required');
			$this->form_validation->set_rules('date',				lang('lbl_date'),				'required|callback_validate_date');
			$this->form_validation->set_rules('start_time',				lang('lbl_start_time'),		'required|callback_check_hours');
			$this->form_validation->set_rules('end_time',				lang('lbl_end_time'),			'required|callback_validate_hours|callback_check_hours');
			$this->form_validation->set_rules('time_entry_id',       		lang('lbl_time_entry'),   			'required');
		}
		
		$this->form_validation->set_rules('task_id');
		
		
		return $this->form_validation->run();
	}
	
	public function validate_hours(){
		$start_time = $this->input->post('start_time');
		$end_time = $this->input->post('end_time');
		
		if($start_time > $end_time){
			$this->form_validation->set_message('validate_hours', lang('msg_end_time_before_start'));
			return FALSE;
		}elseif($start_time == $end_time){
			$this->form_validation->set_message('validate_hours', lang('msg_end_time_equals_start'));
			return FALSE;
		}
		return TRUE;
	}
	
	public function validate_date(){
		$date = (string) $this->input->post('date');

		// checkdate() throws a TypeError on non-numeric parts in PHP 8
		$valid = FALSE;
		if(preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $date, $parts))
			$valid = checkdate((int) $parts[2], (int) $parts[1], (int) $parts[3]);

		if(!$valid){
			$this->form_validation->set_message('validate_date', lang('msg_date_invalid'));
			return FALSE;
		}
		return TRUE;
	}
	
	public function check_hours(){
		$start_time = $this->input->post('start_time');
		$end_time = $this->input->post('end_time');
		
		if(!empty($start_time)){
			list($h, $m) = explode(':', $this->input->post('start_time'));
			if(($h >= 24) OR ($m > 59)){
				$this->form_validation->set_message('check_hours', lang('msg_start_time_invalid'));
				return FALSE;
			}
		}
		
		if(!empty($end_time)){
			list($h, $m) = explode(':', $this->input->post('end_time'));
			if(($h >= 24) OR ($m > 59)){
				$this->form_validation->set_message('check_hours', lang('msg_end_time_invalid'));
				return FALSE;
			}
		}
		return TRUE;
	}
	
	public function get_projects($project_id="", $client_id="") {
		if(empty($client_id)) $client_id = $this->input->post('client_id');
		
		$projects = $this->time_entry_model->get_client_projects($client_id)->result();

		if(empty($projects)){
			echo '<option value="">'.lang('msg_no_client_projects').'</option>';
		}else{
			echo '<option hidden>'.lang('msg_select_project').'</option>';
			foreach ($projects as $project) {
				$deadline = fdatetime_parts($project->deadline, "/");
				
				$date = $deadline['date'];
				$time = $deadline['time'];
				
				$project_time = explode(":", $time);
				$time = $project_time[0] . ':' . $project_time[1];
				
				$selected = "";
				if($project_id == $project->project_id){
					$selected = "selected='selected'";
				}
				echo '<option value="'.$project->project_id .'" '. $selected .'>'.$project->name.' - '.lang('msg_deadline').': '. $date .' '. $time .' - '.lang('msg_priority').': '. $project->priority .'</option>';
			}
		}
	}
	
	public function get_project_options($client_id, $project_id="") {
		$projects = $this->time_entry_model->get_client_projects($client_id)->result();
		$result = "";
		
		if(empty($projects)){
			$result .= '<option value="">'.lang('msg_no_client_projects').'</option>';
		}else{
			
			$result .= '<option hidden>'.lang('msg_select_project').'</option>';
			
			foreach ($projects as $project) {
				$deadline = fdatetime_parts($project->deadline, "/");
				
				$date = $deadline['date'];
				$time = $deadline['time'];
				
				$project_time = explode(":", $time);
				$time = $project_time[0] . ':' . $project_time[1];
				
				$selected = "";
				if(set_value('project_id', $project_id) == $project->project_id){
					$selected = 'selected="selected"';
				}
				$result .=  '<option value="'.$project->project_id .'" '. $selected .'>'.$project->name.' - '.lang('msg_deadline').': '. $date .' '. $time .' - '.lang('msg_priority').': '. $project->priority .'</option>';
			}
		}
		return $result;
	}

	public function get_task($project_id="", $task_id="") {
		if(empty($task_id)) $task_id = $this->input->post('task_id');
		
		$task = $this->task_model->get_task($task_id);

		if(empty($task))
			echo '';
		else
			echo json_encode($task);
		
	}

	public function get_tasks($project_id="", $task_id="") {
		if(empty($task_id)) $task_id = $this->input->post('task_id');
		
		$tasks = $this->task_model->get_tasks($project_id);

		if(empty($tasks)){
			echo '<option value="">'.lang('msg_no_project_tasks').'</option>';
		}else{
			echo '<option value="0">'.lang('msg_no_task_selected').'</option>';
			foreach ($tasks as $task) {
				$deadline = fdatetime_parts($task->due_date, "/");
				
				$date = $deadline['date'];
				$time = $deadline['time'];
				
				$task_time = explode(":", $time);
				$time = $task_time[0] . ':' . $task_time[1];
				
				$selected = "";
				if($task_id == $task->task_id)
					$selected = "selected='selected'";
				
				echo '<option value="'.$task->task_id .'" '. $selected .'>'.$task->name.' - '.lang('msg_deadline').': '. $date .' - '.lang('msg_estimated_hours').': '. $task->hours .'</option>';
			}
		}
	}

	public function get_task_options($project_id = "", $task_id="") {
		$tasks = $this->task_model->get_tasks($project_id);
		$result = "";
		
		if(empty($tasks)){
			$result .= '<option value="">'.lang('msg_no_project_tasks').'</option>';
		}else{
			$selected = "";
			if(set_value('task_id', $task_id) == 0)
				$selected = 'selected="selected"';

			$result .= '<option value="0" '.$selected.'>'.lang('msg_no_task_selected').'</option>';
			
			foreach($tasks as $task) {
				$deadline = fdatetime_parts($task->due_date, "/");
				
				$date = $deadline['date'];
				$time = $deadline['time'];
				
				$task_time = explode(":", $time);
				$time = $task_time[0] . ':' . $task_time[1];
				
				$selected = "";
				if(set_value('task_id', $task_id) == $task->task_id)
					$selected = 'selected="selected"';
				
				$result .=  '<option value="'.$task->task_id .'" '. $selected .'>'.$task->name.' - '.lang('msg_deadline').': '. $date .' - '.lang('msg_estimated_hours').': '. $task->hours .'</option>';
			}
		}
		return $result;
	}
}