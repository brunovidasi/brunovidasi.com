<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Report extends CI_Controller {

	public function index(){
		$this->list();
	}
	
	function list(){
		$data['project_options']  = $this->get_client_projects($this->input->post('client_id'));
		$data['clients'] 	 = $this->client_model->get_clients();
		$data['client_id'] = "";

		$data['view'] = $this->load->view('report/list', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	function generate($project_id=""){		
		if(!empty($project_id)) 
			$data['report'] = $this->report_model->generate_report_html($project_id);
		else{
			$project_id_post = $this->input->post('project_id');
			
			if(!empty($project_id_post)) 
				$data['report'] = $this->report_model->generate_report_html($project_id_post);
			else
				redirect('/report/');
		}

		$data['view'] = $this->load->view('report/generate', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	function generate_pdf($html=""){
		$this->report_model->generate_report_pdf($html);
	}
	
	function get_client_projects($client_id, $project_id="") {
		$projects = $this->time_entry_model->get_client_projects($client_id)->result();
		
		$result = "";
		
		if(empty($projects)){
			$result .= '<option hidden value="0">'.lang('msg_no_client_projects').'</option>';
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