<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dashboard extends CI_Controller {

	public function index(){
		$data["time_entries"] = $this->time_entry_model->get_time_entries_report("", "DESC");
		$data["task_count"] = $this->task_model->get_tasks(0, $this->session->userdata('id'), "ASC", 0, 0, "", 'count', "completed");
		$data["tasks"] = $this->task_model->get_tasks(0, $this->session->userdata('id'), "ASC", 0, 0, "", 'result', "completed");
		$data['my_time_entries'] = $this->user_model->get_time_entries($this->session->userdata('id'));
		$data['hours_worked'] = $this->user_model->get_hours_worked($this->session->userdata('id'));
		$data["open_time_entry"] = $this->time_entry_model->open_time_entry($this->session->userdata('id'));
		$data["projects"] = $this->project_model->get_projects();
		$data["projects_involved"] = $this->user_model->get_projects_involved($this->session->userdata('id'));
		$data["messages"] = $this->message_model->get_unread_messages()->result();

		$data["just_logged_in"] = $this->session->flashdata("just_logged_in");
		
		$data['view'] = $this->load->view('dashboard/timeline', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}

	public function blank(){
		$data['view'] = "";
		$this->load->view('includes/internal', $data);
	}
	
	public function calendar(){
		$data["time_entries"] = $this->time_entry_model->get_time_entries_report("", "DESC");
		$data["projects"] = $this->project_model->get_projects();
		
		$data['view'] = $this->load->view('dashboard/calendar', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	public function message(){
		$data['view'] = $this->load->view('includes/message', array(), TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	public function test(){
		$images = $this->user_model->get_images();
		$data['images'] = $images;
		$data['view'] = $this->load->view('dashboard/test', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}

	public function presentation(){

		$this->load->view('dashboard/presentation');

	}
	
	public function no_access(){
		$this->session->set_flashdata('message_warning', lang('msg_no_access'));
		redirect('dashboard/message');
	}
	
	public function home(){
		$this->index();
	}
	
	public function internal(){
        $this->load->view('includes/internal');
    }	

    public function about(){
    	$info = $this->auth_model->get_info()->row(); 

		$version = explode('.', $info->version);
		$release_date = explode(" ", fdatetime($info->release_date, "/"));

		$data['version'] = $version[0].'.'.$version[1];
		$data['browser_info'] = $this->session->userdata('browser_info')->browser;
		$data['release_date'] = $release_date[0];
		$data['info'] = $info;

        $data['view'] = $this->load->view('dashboard/about', $data, TRUE);
		$this->load->view('includes/internal', $data);
    }	
}