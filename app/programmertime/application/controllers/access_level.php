<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Access_level extends CI_Controller {

	public function index(){
		$this->list();
	}
	
	public function list(){
		$data['access_level'] = $this->user_model->get_access_levels();
		
		$data['view'] = $this->load->view('access_level/list', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	public function create(){
		$data['access_level'] = "";
		
		$data['view'] = $this->load->view('access_level/create', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	public function insert(){
        if ($this->validate_form()){
            $this->auth_model->post();
			$insert_id = $this->auth_model->insert();
			
            if ($insert_id > 0){
				$this->session->set_flashdata('message_success', lang('msg_level_create_success'));
                redirect('/access_level/');
            }
			$this->session->set_flashdata('message_error', lang('msg_level_create_error'));
			redirect('/access_level/create/');
        }
		$this->create();			
    }
	
	private function validate_form(){
		$this->form_validation->set_rules('role', lang('lbl_role_name'), 'trim|required|is_unique[access_level.role]');
		$this->form_validation->set_rules('can_create_project');
		$this->form_validation->set_rules('can_edit_project');
		$this->form_validation->set_rules('can_create_client');
		$this->form_validation->set_rules('can_edit_client');
		$this->form_validation->set_rules('can_create_user');
		$this->form_validation->set_rules('can_edit_user');
		$this->form_validation->set_rules('can_send_report');
		$this->form_validation->set_rules('can_log_time');
		$this->form_validation->set_rules('can_log_payment');
		
		return $this->form_validation->run();
	}	
}