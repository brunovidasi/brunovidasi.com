<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Auth extends CI_Controller {

	public function index($message = null){
		
		if($this->session->userdata('logged_in') == TRUE) redirect('/dashboard/');
		
		$users = $this->user_model->get_users()->num_rows();
		if($users == 1) redirect('/auth/first_access/');

		$data = array();
		$data['message'] = $message;
		
		$this->load->view('auth/login', $data);
	}
	
	public function login($user_name = null, $password = null, $token = '2344'){

		$system_active = TRUE;

		if(!$system_active){
			$this->session->set_flashdata('message', lang('msg_system_inactive'));
			redirect('/auth/');
		}

		if(empty($user_name)){
			$user_name = $this->input->post('login');
			$password = hash_password($this->input->post('password'));
		}else{
			// SECURITY: this shared token (an API-key-style credential for
			// trusted external/JSON clients) was exposed on a public GitHub
			// repo. Rotate it on any real deployment and update this
			// constant (real password verification still happens below
			// regardless of this token).
			if($token != 'YOUR_API_TOKEN_ROTATE_BEFORE_DEPLOY')
				$user_name = null;
		}

		$user = $this->auth_model->get_by_login($user_name)->row();

		if((!empty($user->user_id)) && ($user->user_id > 0)){
			if(($password == $user->password) && ($user->status == 'active')){
				$this->start_session($user);
				
			}else{
				
				if(($user->status == 'inactive') && ($password == $user->password)){
					$this->session->set_flashdata('message', lang('msg_contact_admin'));
					$this->session->set_flashdata('login_error', 'user_inactive');
					$this->send_email->inactive($user->email, "contact@programmertime.com", $user->name, $user->login);
				}else{
					$this->session->set_flashdata('login_error', 'wrong_password');
					$this->session->set_flashdata('login', $user->login);
				}
				redirect('/auth/');
			}
		}else{
			$this->session->set_flashdata('login_error', 'wrong_user');
			redirect('/auth/');
		}
	}

	// Starts the session for an already-authenticated user and redirects.
	private function start_session($user){
		$browser_info = $this->detect_browser();
		
		$session_data = array(
			'name'			=> short_name($user->name, 0),
			'short_name'	=> short_name($user->name, 1),
			'full_name'		=> $user->name,
			'id'			=> $user->user_id,
			'login'			=> $user->login,
			'email'			=> $user->email,
			'image'			=> $user->image,
			'login_count'	=> $user->login_count,
			'access_level'	=> $user->access_level,
			'status'		=> $user->status,
			'color'			=> $user->color,
			'confirmed'	=> $user->confirmed,
			'employee_id'		=> $user->employee_id,
			'email_token'	=> $user->email_token,
			'browser_info'		=> $browser_info,
			'logged_in'		=> TRUE,
			'locked'		=> FALSE
		);
		
		$this->session->set_userdata($session_data);
		$this->session->set_userdata("user", $user);

		$this->session->set_flashdata("just_logged_in", TRUE);
			
		$this->auth_model->log($user->login_count, $user->user_id);
		$this->auth_model->access_level($user->access_level);
		
		if($user->login_count == 1) redirect('/user/edit/');
		elseif($user->confirmed == 'no') redirect('/user/view/');
		else redirect('/dashboard/');
	}
	
	public function first_access(){
		$users = $this->user_model->get_users()->num_rows();

		if($users == '1') 
			$this->load->view('auth/first_access');
		else 
			redirect('/auth/');
	}
	
	public function create(){
		$users = $this->user_model->get_users()->num_rows();
		if($users == '1'){
		
			if ($this->validate_form()){
			
				$data = $this->user_model->post_first_access();
				$insert_id = $this->user_model->insert($data);
				
				if($insert_id > 0){
					$user = $this->auth_model->get_by_id($insert_id)->row();
					$this->start_session($user);
				}else{
					$this->session->set_flashdata('message_error', lang('msg_user_not_created'));
					redirect('/auth/first_access/');
				}
				
			}else{
				$this->first_access();	
			}
			
		}else{
			redirect('/auth/');
		}
	}
	
	private function validate_form(){
		$this->form_validation->set_rules('name', 				lang('lbl_name'), 			'trim|required');
		$this->form_validation->set_rules('email', 				lang('lbl_email'), 			'trim|required|valid_email|is_unique[user.email]');
		$this->form_validation->set_rules('login', 				lang('lbl_username'), 	'trim|required');
		$this->form_validation->set_rules('password', 				lang('lbl_password'), 			'trim|required|matches[confirmation_password]');
		$this->form_validation->set_rules('confirmation_password', 	lang('lbl_confirm_password'), 'trim');
		
		return $this->form_validation->run();
	}
	
	private function detect_browser(){
		$useragent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$browser_info = new stdClass();
	
		if (preg_match('|MSIE ([0-9].[0-9]{1,2})|',$useragent,$matched)){
			$browser = 'Internet Explorer';
			$browser_version = $matched[1];
		}elseif (preg_match( '|Opera/([0-9].[0-9]{1,2})|',$useragent,$matched)){
			$browser = 'Opera';
			$browser_version = $matched[1];
		}elseif(preg_match('|Firefox/([0-9\.]+)|',$useragent,$matched)){
			$browser = 'Mozilla Firefox';
			$browser_version = $matched[1];
		}elseif(preg_match('|Chrome/([0-9\.]+)|',$useragent,$matched)){
			$browser = 'Google Chrome';
			$browser_version = $matched[1];
		}elseif(preg_match('|Safari/([0-9\.]+)|',$useragent,$matched)){
			$browser = 'Safari';
			$browser_version = $matched[1];
		}else{
			$browser= 'Unknown';
			$browser_version = '';
		}
		
		$browser_info->useragent = $useragent;
		$browser_info->browser = $browser;
		$browser_info->version  = $browser_version;
		$browser_info->browser_version  = $browser . ' - ' . $browser_version;
		
		return $browser_info;
	}
	
	public function confirm_email($code = ''){
		
		$id = (int) $this->user_model->get_id_by_email_token($code);

		$this->session->sess_destroy();
		
		if(!empty($id) && $id > 0){
			$confirmed = $this->user_model->confirm_email($id);

			if($confirmed){
				$this->session->set_flashdata('message', lang('msg_user_confirmed'));
				redirect('/auth/');
			}else{
				$this->session->set_flashdata('message', lang('msg_user_not_confirmed'));
				redirect('/auth/');
			}
		}else{
			$this->session->set_flashdata('message', lang('msg_user_not_confirmed'));
			redirect('/auth/');
		}
	}
	
	public function lock(){
		$this->session->set_userdata('locked', TRUE);
		$this->load->view('auth/lock');
	}

	public function unlock(){
		$password = hash_password($this->input->post('password'));
		$user = $this->auth_model->get_by_login($this->session->userdata('login'))->row();

		if(empty($user) || $password != $user->password){
			$this->session->set_flashdata('message', lang('wrong_password'));
			redirect('/auth/lock/');
		}

		$this->session->set_userdata('locked', FALSE);
		redirect('/dashboard/');
	}

	public function logout(){
		$this->session->sess_destroy();
		redirect('/auth/');
	}
	
}