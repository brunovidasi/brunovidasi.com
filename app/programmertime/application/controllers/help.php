<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Help extends CI_Controller {
	
	public function index(){
	    $this->list();
	}
	
	function list($id = ""){
		$data['help_articles'] = $this->help_model->get_help_articles();
		
		$data['view'] = $this->load->view('help/list', $data, TRUE);
		$this->load->view('includes/internal', $data);
		
	}
	
	function create(){
		$data['view'] = $this->load->view('help/create', array(), TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	function edit($id = ""){
		$data['help'] = $this->help_model->get_help($id)->row();
		
		$data['view'] = $this->load->view('help/edit', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	
	function insert(){
        if ($this->validate_form()){
		
            $this->help_model->post();
			$insert_id = $this->help_model->insert();
			
            if ($insert_id > 0){
				$this->session->set_flashdata('message_success', 'Help article created successfully.');
                redirect('/help/');
            }
			$this->session->set_flashdata('message_error', 'Help article <strong>not</strong> created. Please try again.');
		    redirect('/help/create/');
        }
		$this->create();		
    }
	
	function update($id = 0){
        if (!empty($id)) {
			if ($this->validate_form()) {
				
				$this->help_model->post();	
			    $updated = $this->help_model->update($id);				
				
				if ($updated) {
				    $this->session->set_flashdata('message_success', 'Help article updated successfully.');
					redirect('/help/');
				}
				$this->session->set_flashdata('message_error', 'Help article <strong>not</strong> updated. Please try again.');
				redirect('/help/edit/'.$id);
			}
			$this->edit($id);
			
	    }
	}
	
	function delete($help_id = 0){
		$deleted = $this->help_model->delete($help_id);
		
		if ($deleted){
			$this->session->set_flashdata('message_success', 'Help article deleted successfully.');
			redirect('/help/');
		}
		$this->session->set_flashdata('message_error', 'Help article <strong>not</strong> deleted. Please try again.');
		redirect('/help/');
	}
	
	private function validate_form(){
		$this->form_validation->set_rules('title', 'Title', 'trim|required');
		$this->form_validation->set_rules('text', 'Text', 'trim|required');
		$this->form_validation->set_rules('type', 'Type', 'trim|required');
		$this->form_validation->set_rules('status', 'Status', 'trim|required');
	
		return $this->form_validation->run();
	}
	
}