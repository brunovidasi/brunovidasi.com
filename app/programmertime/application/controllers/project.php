<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Project extends CI_Controller {
	
    public function __construct(){
        parent::__construct();
    }
	
	public function index(){
        $this->list();
    }
	
	public function list(){

		$term = $this->input->post('term');
		$this->session->set_userdata('client_id', "");
		$this->session->set_userdata('term', $term);
		
		if(!empty($term)){
			$first_char = substr($term, 0, 1);
			
			if($first_char == '#'){
				$term_array = explode('#', $term);
				$term_1 = (int) $term_array[1];
				if($this->project_model->get_project($term_1)->num_rows() == 1)
					redirect('/project/view/'.$term_1);
			}
		}
		
		$offset = (!$this->uri->segment("3")) ? 0 : $this->uri->segment("3");
		$per_page = 15;
		
		$config['base_url']   	= site_url('project/list/');
		$config['total_rows']  	= $this->project_model->get_projects($term)->num_rows;
		$config['per_page']    	= $per_page;
		$config['uri_segment'] 	= 3;
		
		$this->pagination->initialize($config);
		
		$data["pagination"]    	= $this->pagination->create_links();
		$data['projects'] 		= $this->project_model->get_projects_list($per_page, $offset, $term);
		$data['clients'] 		= $this->client_model->get_clients();
		
		$data['view'] = $this->load->view('project/list', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	public function view($project_id = null){

		if(empty($project_id)){
			$this->session->set_flashdata('message_warning', lang('msg_project_not_found'));
			redirect('/project/');
		}

		$project_query = $this->project_model->get_project($project_id);
		$data['project_query'] = $project_query;
		
		if($project_query->num_rows() == 0){
			$this->session->set_flashdata('message_warning', lang('msg_project_not_found'));
			redirect('/project/list/');
		}
		
		$project 			= $project_query->row();
		$time_entries 			= $this->time_entry_model->get_time_entries_report($project_id, "DESC");
		$time_entries_in_progress 	= $this->time_entry_model->get_time_entries_report($project_id, "DESC", NULL, NULL, TRUE);
		$payments 		= $this->finance_model->get_project_payments($project_id);
		$finance 		= $this->finance_model->calculate_costs($project_id);
		$images 			= $this->image_model->get_images($project_id);
		$involved 		= $this->project_model->get_involved_users($project_id);
		
		$data['project'] = $project;
		$data['time_entries'] = $time_entries;
		$data['time_entries_in_progress'] = $time_entries_in_progress;
		$data['payments'] = $payments;
		$data['finance'] = $finance;
		$data['images'] = $images;
		$data['involved'] = $involved;
			
		$data['time_entry_count'] = $time_entries->num_rows() + $time_entries_in_progress->num_rows();
		$data['payment_count'] = $payments->num_rows();
		$data['image_count'] = 0;
		
		$class_tr = ""; $danger = "";
		if($project->deadline <= date('Y-m-d H:i:s')){ $class_tr = 'danger'; $danger = 'danger'; }
		if($project->status == 'paused'){ $class_tr = 'warning'; }
		if($project->status == 'cancelled'){ $class_tr = 'danger'; $danger = 'danger'; }
		if($project->status == 'completed'){ $class_tr = '';}
		
		$data['class_tr'] = $class_tr;
		$data['danger'] = $danger;
		
		if($finance->status == 'positive')
			$data['payment_class'] = "";
		else
			$data['payment_class'] = "danger";
	
		$data['can_log_time'] 		= $this->session->userdata('can_log_time');
		$data['can_log_payment'] 	= $this->session->userdata('can_log_payment');
		$data['can_create_project'] 	= $this->session->userdata('can_create_project');
		$data['can_edit_project'] 	= $this->session->userdata('can_edit_project');
		$data['can_create_client'] 	= $this->session->userdata('can_create_client');
		$data['can_send_report'] 	= $this->session->userdata('can_send_report');
		

		if($time_entries->num_rows() > 0){
		
			$time_entry_phases = array();
			$user_names = array();
			$total_hours = array();
			$user_time_entries = array();
			
			foreach($time_entries->result() as $time_entry){
				$time_entry_phases['id'][] = $time_entry->phase_id;
				$time_entry_phases[$time_entry->phase_id] = $time_entry->phase;
				$user_names[$time_entry->user_id] = $time_entry->owner;
				$total_hours[] = calculate_total_hours($time_entry->end_time, $time_entry->start_time);
			}
			
			$data['hours_spent'] = sum_hours($total_hours);
			
			$phase_counts = array_count_values($time_entry_phases['id']);
			
			foreach($phase_counts as $key => $count){
				$percentage[$key]['percentage'] = ($phase_counts[$key] * 100) / array_sum($phase_counts);
				$percentage[$key]['name'] = $time_entry_phases[$key];
				
				if($phase_counts[$key] != 1){ $entry_suffix = " entries"; }else{ $entry_suffix = " entry"; }
				
				$percentage[$key]['count'] = $phase_counts[$key];
				$percentage[$key]['time_entry_count'] = $phase_counts[$key] . $entry_suffix;
				
				foreach($user_names as $key2 => $value2){
					$time_entry_user = $this->time_entry_model->get_user_time_entries($project_id, $key2, $key);
					$user_time_entries[$key2][$key] = $time_entry_user->num_rows();
					$user_time_entries[$key2]['name'] = $user_names[$key2];
				}
			}
			
			$data['percentage'] 	 = $percentage;
			$data['user_time_entries']  = $user_time_entries;
			$data['time_entry_phases'] 	 = $time_entry_phases;
			$data['phase_counts'] = $phase_counts;
			
			$users = $this->time_entry_model->get_project_time_entries($project_id);
			
			$phase_users[] = array();
			
			foreach($users->result() as $user){
				$phase_users[$user->phase_id][] = $user->user_id;
				
				$phase_user_counts[$user->phase_id] = array_count_values($phase_users[$user->phase_id]);
			}
			
			$data['phase_users'] = $phase_users;
			$data['phase_user_counts'] = $phase_user_counts;
		
		}else{
			$data['percentage'] 	= "";
			$data['time_entry_phases'] 	= "";
			$data['phase_users'] 	= "";
			$data['phase_user_counts'] = "";
			$data['hours_spent'] 	= "00:00";
		}
		
		$data['view'] = $this->load->view('project/view', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	public function create(){

		$data['types']			= $this->project_model->get_types();
		$data['users']		= $this->user_model->get_active_users();
		$data['clients']		= $this->client_model->get_clients();
		$data["post_images"]	= $this->project_model->post_images();
		
		$data['view'] = $this->load->view('project/create', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	public function edit($project_id = null){

		if(empty($project_id)){
			$this->session->set_flashdata('message_warning', lang('msg_project_not_found'));
			redirect('/project/');
		}
		
		$crop = new stdClass();
		$crop->source 	= "assets.upload.temp.";
		$crop->destination 	= "assets.upload.images.imgs_project";
		$crop->width 	= "400";
		$crop->height 	= "300";

		$project = $this->project_model->get_project($project_id);
		if($project->num_rows() == 0){
			$this->session->set_flashdata('message_warning', lang('msg_project_not_found'));
			redirect('/project/');
		}

		$data['project_crop'] 	= $crop;
		$data['project']		= $project->row();
		$data['types']			= $this->project_model->get_types();
		$data['users']		= $this->user_model->get_active_users();
		$data['clients']		= $this->client_model->get_clients();
		
		//$data["post_images"] = $this->project_model->post_images($this->project_model->fetch_images($project_id));
		
		$data['view'] = $this->load->view('project/edit', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	public function insert(){

        if($this->validate_form('insert')){
            $data = $this->project_model->post();
			$project_id = $this->project_model->insert($data);
			
            if($project_id > 0){
			    $this->session->set_flashdata('message_success', lang('msg_project_create_success'));
                redirect('/project/view/'.$project_id);
            }
			$this->session->set_flashdata('message_error', lang('msg_project_create_error'));
			redirect('/project/');
        }
		$this->create();
    }
	
	public function update($id = null){

        if(!empty($id)){
			if($this->validate_form('update', $id)){
				
				$data = $this->project_model->post();
			    $updated = $this->project_model->update($id, $data);
				
				if($updated)
				    $this->session->set_flashdata('message_success', lang('msg_project_edit_success'));
				else
					$this->session->set_flashdata('message_error', lang('msg_project_edit_error'));
				
				redirect('/project/view/'.$id);
			}
			$this->edit($id);
	    }
	    redirect('/project/');
	}
	
	public function notes($id = null){

        if(!empty($id)){
        	if($this->validate_notes()){
				$data = $this->project_model->post_notes();
			    $updated = $this->project_model->update($id, $data);
				
				if($updated)
				    $this->session->set_flashdata('message_success', lang('msg_notes_edit_success'));
				else
					$this->session->set_flashdata('message_error', lang('msg_notes_edit_error'));

				redirect('/project/view/'.$id);
			}
	    }
	    redirect('/project/');
	}
	
	public function delete($project_id = 0){
		$deleted = $this->project_model->delete($project_id);
		
		if($deleted)
			$this->session->set_flashdata('message_success', lang('msg_project_delete_success'));
		else
			$this->session->set_flashdata('message_error', lang('msg_project_delete_error'));

		redirect('/project/');		
	}
	
	private function validate_form($method = 'insert', $id = ''){
		
		$this->form_validation->set_rules('client_id', 		lang('lbl_client'), 		'trim|required');
		$this->form_validation->set_rules('type_id', 		lang('lbl_type'), 			'trim|required');
		$this->form_validation->set_rules('owner_id', 	lang('lbl_owner'), 	'trim|required');
		$this->form_validation->set_rules('name', 			lang('lbl_name'), 			'trim|required');
		$this->form_validation->set_rules('description', 		lang('lbl_description'), 		'trim|required');
		$this->form_validation->set_rules('deadline', 			lang('lbl_deadline'), 			'trim|required');
		$this->form_validation->set_rules('deadline_time', 	lang('lbl_deadline_time'), 	'trim|required');
		$this->form_validation->set_rules('start_date', 	lang('lbl_start_date'), 	'trim|required');
		$this->form_validation->set_rules('end_date');
		$this->form_validation->set_rules('image');
		$this->form_validation->set_rules('notes');
		$this->form_validation->set_rules('link');
		$this->form_validation->set_rules('status', 		lang('lbl_status'), 		'trim|required');
		$this->form_validation->set_rules('priority', 	lang('lbl_priority'), 	'trim|required');
		
		return $this->form_validation->run();
	}
	
	private function validate_notes(){
		$this->form_validation->set_rules('notes');
		return $this->form_validation->run();
	}
}