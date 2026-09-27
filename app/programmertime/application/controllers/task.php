<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Task extends CI_Controller {

	public function index(){
		$this->list();
	}
	
	public function list($user_id = 0, $project_id = 0, $status = "all", $offset = 0){

		// URL: /task/list/{user_id}/{project_id}/{status}/{offset}
		$user_id	= (int) $user_id;
		$project_id	= (int) $project_id;
		$offset		= (int) $offset;
		$per_page		= 15;

		$filter_status = ($status == 'all') ? '' : $status;

		$config['base_url']    	= site_url('task/list/'.$user_id.'/'.$project_id.'/'.rawurlencode($status)).'/';
		$config['total_rows'] 	= $this->task_model->get_tasks($project_id, $user_id, "DESC", 0, 0, $filter_status, 'count');
		$config['per_page']    	= $per_page;
		$config['uri_segment'] 	= 6;
		
		$this->pagination->initialize($config);
		
		$data["pagination"]    		= $this->pagination->create_links();
		$data['task_count']	= $config['total_rows'];
		$data['tasks']			= $this->task_model->get_tasks($project_id, $user_id, "DESC", $per_page, $offset, $filter_status, 'result');

		$data['user_id']			= $user_id;
		$data['project_id']			= $project_id;

		$data['user']			= "";
		$data['project']			= "";

		if($user_id > 0){
			$user 				= $this->user_model->get_user($user_id)->row();

			if(!isset($user->user_id)){
				$this->session->set_flashdata('message_error', lang('msg_user_not_found'));
				redirect('/task/list/');
			}

			$data['user']		= short_name($user->name, 1);
		}

		if($project_id > 0){
			$project 				= $this->project_model->get_project($project_id)->row();

			if(!isset($project->project_id)){
				$this->session->set_flashdata('message_error', lang('msg_project_not_found'));
				redirect('/task/list');
			}

			$data['project']		= $project->name;
		}

		$data['view'] = $this->load->view('task/list', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}

	public function view($task_id = ""){

		if(empty($task_id)){
			$this->session->set_flashdata('message_warning', lang('msg_task_not_found'));
			redirect('/task/');
		}
		
		$task	= $this->task_model->get_task($task_id);

		if(empty($task)){
			$this->session->set_flashdata('message_warning', lang('msg_task_not_found'));
			redirect('/task/');
		}

		$data['task']		= $task;
		
		$data['view'] = $this->load->view('task/view', $data, TRUE);
		$this->load->view('includes/internal', $data);

	}

	public function create($project_id = 0, $user_id = 0){

		$data['project_options'] 	= $this->get_project_options($this->input->post('client_id'));
		$data['users']		= $this->user_model->get_active_users();
		$data['clients']		= $this->client_model->get_clients();
		$data['phases'] 		= $this->time_entry_model->get_phases();

		$data['client_id'] = "";
		$data['project'] 	 = "";
		$data['user_id']  = "";
		
		if(!empty($project_id)){

			$project_id = (int) $project_id;
			$project = $this->project_model->get_project($project_id);
			
			$client_id = "";
			if($project->num_rows() > 0){
			   $pro = $project->row(); 
			   $client_id = $pro->client_id;
			}
			
			$data['project'] 	 = $project_id;
			$data['client_id'] = $client_id;
			$data['project_options']  = $this->get_project_options($client_id, $project_id);
		}

		if(!empty($user_id))
			$data['user_id'] 	 = $user_id;

		$data['view'] = $this->load->view('task/create', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}

	public function edit($task_id = null){

		if(empty($task_id)){
			$this->session->set_flashdata('message_warning', lang('msg_task_not_found'));
			redirect('/task/');
		}
		
		$task	= $this->task_model->get_task($task_id);

		if(empty($task)){
			$this->session->set_flashdata('message_warning', lang('msg_task_not_found'));
			redirect('/task/');
		}

		$data['task']		= $task;
		// $data['projects']		= $this->project_model->get_client_projects($task->client_id);
		$data['users']		= $this->user_model->get_active_users();
		$data['clients']		= $this->client_model->get_clients();
		$data['phases'] 		= $this->time_entry_model->get_phases();

		$data['project_options'] = $this->get_project_options($task->client_id, $task->project_id);
		
		$data['view'] = $this->load->view('task/edit', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}

	public function insert(){

        if($this->validate_form('insert')){
            $data = $this->task_model->post();
			$task_id = $this->task_model->insert($data);
			
            if($task_id > 0){
			    $this->session->set_flashdata('message_success', lang('msg_task_create_success'));
                redirect('/task/view/'.$task_id);
            }
			$this->session->set_flashdata('message_error', lang('msg_task_create_error'));
			redirect('/task/');
        }
		$this->create();
    }

    public function update($id = null){

        if(!empty($id)){
			if($this->validate_form('update', $id)){
				
				$data = $this->task_model->post('update');
			    $updated = $this->task_model->update($id, $data);
				
				if($updated)
				    $this->session->set_flashdata('message_success',  lang('msg_task_edit_success'));
				else
					$this->session->set_flashdata('message_error',  lang('msg_task_edit_error'));
				
				redirect('/task/view/'.$id);
			}
			$this->edit($id);
	    }
	    redirect('/task/');
	}

	public function delete($task_id = 0){
		$deleted = $this->task_model->delete($task_id);
		
		if($deleted)
			$this->session->set_flashdata('message_success', lang('msg_task_delete_success'));
		else
			$this->session->set_flashdata('message_error', lang('msg_task_delete_error'));

		redirect('/task/');		
	}

	private function validate_form($method = 'insert', $id = ''){
		
		$this->form_validation->set_rules('client_id', 	lang('lbl_client'), 			'trim|required');
		$this->form_validation->set_rules('project_id', 	lang('lbl_project'), 			'trim|required');
		$this->form_validation->set_rules('user_id', 	lang('lbl_owner'), 		'trim|required');
		$this->form_validation->set_rules('phase_id', 	lang('lbl_phase'), 				'trim|required');
		$this->form_validation->set_rules('name', 		lang('lbl_name'), 				'trim|required');
		$this->form_validation->set_rules('hours', 		lang('msg_estimated_hours'), 	'trim|required');
		$this->form_validation->set_rules('date', 		lang('lbl_deadline'), 				'trim|required');
		
		$this->form_validation->set_rules('status');
		$this->form_validation->set_rules('deadline_time');
		$this->form_validation->set_rules('description');
		
		return $this->form_validation->run();
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
	
}