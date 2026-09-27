<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Company extends CI_Controller {
	
	public function index(){
	    $this->edit();
	}
	
	// There is no read-only company page; the profile is shown in its edit form.
	function view(){
		$this->edit();
	}
	
	function edit(){
		$data['company'] = $this->company_model->get_company()->row();
		$data['view'] = $this->load->view('company/edit', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	function update(){
		if ($this->validate_form()) {
			$this->company_model->post();	
			$updated = $this->company_model->update();
			if ($updated) {
				$this->session->set_flashdata('message_success', lang('msg_company_edit_success'));
				redirect('/company/edit/');
			}
			$this->session->set_flashdata('message_error', lang('msg_company_edit_error'));
			redirect('/company/edit/');
			
		}
		$this->edit();
	}
	
	private function validate_form(){
		$this->form_validation->set_rules('representative_id');
		$this->form_validation->set_rules('name', 				lang('lbl_name'), 			'required');
		$this->form_validation->set_rules('legal_name', 		lang('lbl_legal_name'), 	'required');
		$this->form_validation->set_rules('company_tax_id');
		$this->form_validation->set_rules('email', 				lang('lbl_email'), 			'required');
		$this->form_validation->set_rules('phone', 			lang('lbl_phone'), 		'required');
		$this->form_validation->set_rules('mobile');
		$this->form_validation->set_rules('website');
		$this->form_validation->set_rules('logo_image');
		$this->form_validation->set_rules('founding_date');
		$this->form_validation->set_rules('address');
		$this->form_validation->set_rules('address_number');
		$this->form_validation->set_rules('address_district');
		$this->form_validation->set_rules('address_line2');
		$this->form_validation->set_rules('address_city');
		$this->form_validation->set_rules('address_state');
		$this->form_validation->set_rules('address_postcode');
		
		return $this->form_validation->run();
	}
}