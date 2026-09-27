<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Client extends CI_Controller {

	public function index(){
		$this->list();
	}
	
	public function list(){

		$term = $this->input->post('term');
		$this->session->set_userdata('client_id', "");
		$this->session->set_userdata('c_term', $term);

		$offset = (!$this->uri->segment("3")) ? 0 : $this->uri->segment("3");
		$per_page = 10;
		
		$config['base_url']    	= site_url('client/list/');
		$config['total_rows'] 	= $this->client_model->get_clients($term)->num_rows;
		$config['per_page']    	= $per_page;
		$config['uri_segment'] 	= 3;
		
		$this->pagination->initialize($config);
		
		$data["pagination"]    	= $this->pagination->create_links();
		$data['clients']		= $this->client_model->get_clients_list($per_page, $offset, $term);
		
		$data['view'] = $this->load->view('client/list', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	public function view($id = ""){
		
		$id = (int) $id;

		if(empty($id)){
			$this->session->set_flashdata('message_warning', lang('msg_client_not_found'));
			redirect('/client/list/');
		}
		
		$client = $this->client_model->get_client($id);
		$data['client_query'] 	= $client;
		$data['client'] 			= $client->row();
		$data['projects'] 			= $this->client_model->get_projects($id);
		$data['active_projects'] 	= $this->client_model->get_active_projects($id);
		$data['project_hours'] 	= $this->client_model->get_projects_hours($id);
		
		if($client->num_rows == 0){
			$this->session->set_flashdata('message_warning', lang('msg_client_not_found'));
			redirect('/client/list/');
		}
		
		$data['view'] = $this->load->view('client/view', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	public function create(){
		$data['view'] = $this->load->view('client/create', array(), TRUE);
		$this->load->view('includes/internal', $data);
	}

	public function edit($id = ""){

		$id = (int) $id;

		if(empty($id)){
			$this->session->set_flashdata('message_warning', lang('msg_client_not_found'));
			redirect('/client/list/');
		}

		$client = $this->client_model->get_client($id);
		
		if($client->num_rows == 0){
			$this->session->set_flashdata('message_warning', lang('msg_client_not_found'));
			redirect('/client/list/');
		}
		
		$data['client'] = $client->row();
		
		$data['view'] = $this->load->view('client/edit', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	public function insert(){
		if($this->validate_form('insert')){

			$data = $this->client_model->post('insert');

			if($data){
				$insert_id = $this->client_model->insert($data);
				
				if($insert_id > 0){
					$this->session->set_flashdata('message_success', lang('msg_client_create_success'));
					redirect('/client/');
				}
			}

			$this->session->set_flashdata('message_error', lang('msg_client_create_error'));
			redirect('/client/create/');
		}
		$this->create();
	}

	public function update($id = 0){

		$id = (int) $id;

		if(!empty($id)) {
			if ($this->validate_form('update', $id)){
				
				$data = $this->client_model->post('update');

				if($data){
					$updated = $this->client_model->update($id, $data);
					
					if($updated) {
						$this->session->set_flashdata('message_success', lang('msg_client_edit_success'));
						redirect('/client/');
					}
				}

				$this->session->set_flashdata('message_error', lang('msg_client_edit_error'));
				redirect('/client/edit/'.$id);
			}
		}
		$this->edit($id);
	}

	public function delete($client_id = 0){
		$deleted = $this->client_model->delete($client_id);
		
		if($deleted){
			$this->session->set_flashdata('message_success', lang('msg_client_delete_success'));
			redirect('/client/');
		}
		$this->session->set_flashdata('message_error', lang('msg_client_delete_error'));
		redirect('/client/');	
	}

	public function change_status($status = 'active', $id = 0){

		$id = (int) $id;

		if($status != 'active' AND $status != 'inactive')
			redirect('/client/');

		if($id > 0){

			$changed = $this->client_model->change_status($id, $status);

			if($changed){

				if($status == 'inactive')
					$this->client_model->cancel_projects($id);

				$this->session->set_flashdata('message_success', lang('msg_client_edit_success'));
			}
			else
				$this->session->set_flashdata('message_error', lang('msg_client_edit_error'));
			
			redirect('/client/view/'.$id);
			// redirect('/client/');
		}
		
		$this->session->set_flashdata('message_error', lang('msg_client_edit_error'));
		redirect('/client/');
	}

	private function validate_form($method = 'insert', $id = 0){

		$id = (int) $id;

		if($method == 'update'){

			$client = $this->client_model->get_client($id)->row();

			$post = new stdClass();
			$post->name = $this->input->post('name', TRUE);
			$post->email = $this->input->post('email', TRUE);
			$post->legal_name = $this->input->post('legal_name', TRUE);
			$post->tax_id = $this->input->post('tax_id', TRUE);
			$post->company_tax_id = $this->input->post('company_tax_id', TRUE);

			if($client->name != $post->name)
				$this->form_validation->set_rules('name', lang('lbl_name'), 'trim|required|is_unique[client.name]');
			else
				$this->form_validation->set_rules('name', lang('lbl_name'), 'trim|required');

			if($client->email != $post->email)
				$this->form_validation->set_rules('email', lang('lbl_email'), 'trim|required|valid_email|is_unique[client.email]');
			else
				$this->form_validation->set_rules('email', lang('lbl_email'), 'trim|required|valid_email');

			$this->form_validation->set_rules('website');
			$this->form_validation->set_rules('phone');
			$this->form_validation->set_rules('mobile');

			if($client->legal_name != $post->legal_name)
				$this->form_validation->set_rules('legal_name', lang('lbl_legal_name'), 'is_unique[client.legal_name]');
			else
				$this->form_validation->set_rules('legal_name');

			if($client->tax_id != $post->tax_id)
				$this->form_validation->set_rules('tax_id', lang('lbl_tax_id'), 'is_unique[client.tax_id]');
			else
				$this->form_validation->set_rules('tax_id');

			if($client->company_tax_id != $post->company_tax_id)
				$this->form_validation->set_rules('company_tax_id', lang('lbl_company_tax_id'), 'is_unique[client.company_tax_id]');
			else
				$this->form_validation->set_rules('company_tax_id');
		}

		else{
			$this->form_validation->set_rules('name', 	lang('lbl_name'), 	'trim|required|is_unique[client.name]');
			$this->form_validation->set_rules('email', 	lang('lbl_email'), 	'trim|required|valid_email|is_unique[client.email]');
			$this->form_validation->set_rules('website');
			$this->form_validation->set_rules('phone');
			$this->form_validation->set_rules('mobile');
			$this->form_validation->set_rules('legal_name', 	lang('lbl_legal_name'), 	'is_unique[client.legal_name]');
			$this->form_validation->set_rules('tax_id', 			lang('lbl_tax_id'), 			'is_unique[client.tax_id]');
			$this->form_validation->set_rules('company_tax_id', 			lang('lbl_company_tax_id'), 			'is_unique[client.company_tax_id]');
		}
		
		$this->form_validation->set_rules('contact_name');
		$this->form_validation->set_rules('contact_email');
		$this->form_validation->set_rules('contact_phone');
		$this->form_validation->set_rules('address_postcode');
		$this->form_validation->set_rules('address');
		$this->form_validation->set_rules('address_number');
		$this->form_validation->set_rules('address_line2');
		$this->form_validation->set_rules('address_district');
		$this->form_validation->set_rules('address_city');
		$this->form_validation->set_rules('address_state');
		
		return $this->form_validation->run();
	}
}