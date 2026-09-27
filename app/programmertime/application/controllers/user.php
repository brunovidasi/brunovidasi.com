<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User extends CI_Controller {
	
	public function index(){
	    $this->list();
	}
	
	public function list(){

		$term = $this->input->post('term');
		$this->session->set_userdata('term', $term);
		
		$offset = (!$this->uri->segment("3")) ? 0 : $this->uri->segment("3");
		$per_page = 15;
		
		$config['base_url']    	= site_url('user/list/');
		$config['total_rows'] 	= $this->user_model->get_users($term)->num_rows;
		$config['per_page']    	= $per_page;
		$config['uri_segment'] 	= 3;
		
		$this->pagination->initialize($config);
		
		$data["term"]    		= $term;
		$data["pagination"]    	= $this->pagination->create_links();
		$data['users']		= $this->user_model->get_users_list($per_page, $offset, $term);
		
		$data['view'] = $this->load->view('user/list', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}

	public function roles(){
		$offset = (!$this->uri->segment("3")) ? 0 : $this->uri->segment("3");
		$per_page = 15;
		
		$config['base_url']    	= site_url('user/roles/');
		$config['total_rows'] 	= $this->user_model->get_users()->num_rows;
		$config['per_page']    	= $per_page;
		$config['uri_segment'] 	= 3;
		
		$this->pagination->initialize($config);
		
		$data["pagination"]    	= $this->pagination->create_links();
		$data['users']		= $this->user_model->get_users_list($per_page, $offset);
		$data['access_levels']	= $this->user_model->get_access_levels();
		
		$data['view'] = $this->load->view('user/list_roles', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	public function view($id = ""){
		if(empty($id)) $id = $this->session->userdata('id');

		if($id == 1){
			$user_id = $this->session->userdata('id');

			if($user_id != 1)
				redirect('/user/list/');
		}
		
		$user = $this->user_model->get_user($id);
		
		if($user->num_rows() == 0){
			$this->session->set_flashdata('message_warning', lang('msg_user_not_found'));
			redirect('/user/list/');
		}
		
		$data['user_query'] = $user;
		$data['user'] 		= $user->row();
		$data['projects'] 		= $this->user_model->get_projects($id);
		$data['time_entries'] 		= $this->user_model->get_time_entries($id);

		$data['hours_worked']		= $this->user_model->get_hours_worked($id);
		$data['projects_involved']	= $this->user_model->get_projects_involved($id);
		$data['open_time_entry'] 			= $this->time_entry_model->open_time_entry($id);
		
		$data['view'] = $this->load->view('user/view', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	public function create(){
		$data['roles'] = $this->user_model->get_access_levels();
		
		$data['view'] = $this->load->view('user/create', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	public function edit($id = ""){

		if(empty($id)) $id = $this->session->userdata('id');

		if($id == 1){
			$user_id = $this->session->userdata('id');
			
			if($user_id != 1)
				redirect('/user/list/');
		}
		
		$user = $this->user_model->get_user($id);
		
		if($user->num_rows() == 0){
			$this->session->set_flashdata('message_warning', lang('msg_user_not_found'));
			redirect('/user/list/');
		}
		
		$crop = new stdClass();
		$crop->source 	= "assets.images.temp.";
		$crop->destination 	= "assets.images.users";
		$crop->width 	= "200";
		$crop->height 	= "200";

		$data['crop']	 = $crop;
		$data['roles'] = $this->user_model->get_access_levels();
		$data['profile'] = $user->row();
		
		$data['view'] = $this->load->view('user/edit', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	public function deactivate(){
		$data['user_id'] = $this->session->userdata('id');
		
		$data['view'] = $this->load->view('user/deactivate', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	public function insert(){
		if($this->validate_form('insert')){
		
			$data = $this->user_model->post('insert');
			
			if($data){
				$insert_id = $this->user_model->insert($data);
				
				if ($insert_id > 0){
					$this->session->set_flashdata('message_success', lang('msg_user_create_success'));
					redirect('/user/');
				}
			}
			$this->session->set_flashdata('message_error', lang('msg_user_create_error'));
			redirect('/user/create/');
		}
		$this->create();		
	}
	
	public function update($id = 0){
		$id = (int) $id;
		if($id > 0){
			if($this->validate_form('update', $id)){
				
				$data = $this->user_model->post('update');
				if($data){
					$updated = $this->user_model->update($id, $data);				
					
					if($updated){
						$images = $this->user_model->save_images($id);
						
						$this->session->set_flashdata('message_success', lang('msg_user_edit_success'));
						redirect('/user/view/'.$id);
					}
				}
				$this->session->set_flashdata('message_error', lang('msg_user_edit_error'));
				redirect('/user/edit/'.$id);
			}
			$this->edit($id);
			return;
		}
		redirect('/user/');
	}
	
	public function change_status($status = 'active', $id = 0){

		$id = (int) $id;

		if($status != 'active' AND $status != 'inactive')
			redirect('/user/');

		if($id > 0){

			if($id == 1){
				$user_id = $this->session->userdata('id');
				
				if($user_id != 1)
					redirect('/user/list/');
			}
			
			$changed = $this->user_model->change_status($id, $status);

			if($changed)
				$this->session->set_flashdata('message_success', lang('msg_user_edit_success'));
			else
				$this->session->set_flashdata('message_error', lang('msg_user_edit_error'));
			
			redirect('/user/view/'.$id);
			// redirect('/user/');
		}
		
		$this->session->set_flashdata('message_error', lang('msg_user_edit_error'));
		redirect('/user/');
	}
	
	private function validate_form($method = 'insert', $id = 0){

		if($method == "insert"){
			$this->form_validation->set_rules('login', 			lang('lbl_username'), 	'trim|required|is_unique[user.login]');
			$this->form_validation->set_rules('password', 			lang('lbl_password'), 			'trim|required|matches[confirmation_password]');
			$this->form_validation->set_rules('email', 			lang('lbl_email'), 			'trim|required|valid_email|is_unique[user.email]');
			$this->form_validation->set_rules('access_level', 	lang('lbl_access_level'), 	'trim|required');
		}

		elseif($method == "update"){

			$user = $this->user_model->get_user($id)->row();

			$post = new stdClass();
			$post->login = $this->input->post('login', TRUE);
			$post->email = $this->input->post('email', TRUE);

			if($user->login != $post->login)
				$this->form_validation->set_rules('login', lang('lbl_username'), 'trim|required|is_unique[user.login]');
			else
				$this->form_validation->set_rules('login', lang('lbl_username'), 'trim|required');

			$this->form_validation->set_rules('password', lang('lbl_password'), 'trim|matches[confirmation_password]');

			if($user->email != $post->email)
				$this->form_validation->set_rules('email', lang('lbl_email'), 'trim|required|valid_email|is_unique[user.email]');
			else
				$this->form_validation->set_rules('email', lang('lbl_email'), 'trim|required|valid_email');

			$this->form_validation->set_rules('status');
		}
		
		$this->form_validation->set_rules('access_level', 		lang('lbl_access_level'), 		'trim|required');
		$this->form_validation->set_rules('confirmation_password', 	lang('lbl_confirm_password'), 	'trim');
		$this->form_validation->set_rules('name', 				lang('lbl_name'),			 	'trim|required');
		$this->form_validation->set_rules('birth_date');
		$this->form_validation->set_rules('employee_id');
		$this->form_validation->set_rules('id_number');
		$this->form_validation->set_rules('tax_id');
		
		return $this->form_validation->run();
	}
	
	public function send_confirmation_email(){
		$this->send_email->confirm_registration($this->session->userdata('email'), $this->session->userdata('full_name'), $this->session->userdata('login'), "*****", $this->session->userdata('email_token'));
		
		$this->session->set_flashdata('message_warning', lang('msg_email_confirmation'));
		redirect('/dashboard/');
	}
	
	public function confirm_email($code = ''){
		$id = (int) $this->user_model->get_id_by_email_token($code);
		
		if(!empty($id) && $id > 0){
			$confirmed = $this->user_model->confirm_email($id);
			if($confirmed){
				$this->session->set_flashdata('message', lang('msg_user_confirmed'));
				redirect('/auth/logout/');
			}
		}
		$this->session->set_flashdata('message', lang('msg_user_not_confirmed'));
		redirect('/auth/logout/');
	}
	
	public function check_login_exists(){
		$new_login = $this->input->post('new_name');
		$current_login = $this->input->post('current_name');
		
		if($current_login == $new_login){
			$user_exists = 0;
		}else{
			$user = $this->user_model->get_user_by_login($new_login);
			$user_exists = $user->num_rows();
		}
		echo $user_exists; 
	}

	public function check_email_exists(){
		$new_email = $this->input->post('new_email');
		$current_email = $this->input->post('current_email');
		
		if($current_email == $new_email){
			$user_exists = 0;
		}else{
			$user = $this->user_model->get_user_by_email($new_email);
			$user_exists = $user->num_rows();
		}
		echo $user_exists; 
	}
}