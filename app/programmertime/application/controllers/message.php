<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Message extends CI_Controller {

	public function index(){
		$this->list();
	}
	
	function list(){
		$data['messages'] = $this->message_model->get_messages();
		$data['unread_messages'] = $this->message_model->get_unread_messages();
		
		$data['view'] = $this->load->view('message/list', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}

	function view($message_id = 1){
		$message = $this->message_model->get_message($message_id); 
		$data['clicked_message'] = $message_id;

		if($message->num_rows() != 1) redirect('/message/list/');

		$message = $message->row();
		$this->message_model->mark_as_read($message_id, TRUE);

		if(!empty($message->reply_to)){
			$message = $this->message_model->get_message($message->reply_to)->row();
			$message_id = $message->message_id;
		}
		$this->message_model->mark_as_read($message_id, TRUE);

		$data['message']  = $message;
		$data['related_messages'] = $this->message_model->get_related_messages($message_id);

		$data['view'] = $this->load->view('message/view', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}

	function compose($project_id = ""){
		$data['clients'] 	= $this->client_model->get_clients();
		$data['users']	= $this->user_model->get_users();
		$data['project_options'] = $this->get_project_options($this->input->post('client_id'));

		$data['client_id'] 	= "";
		$data['user_id'] 	= "";
		$data['project'] 		= "";
		if(!empty($project_id)){
			$project = $this->project_model->get_project($project_id);
			
			if ($project->num_rows() > 0){
			   $pro = $project->row(); 
			   $client_id = $pro->client_id;
			}
			
			$data['project'] 		= $project_id;
			$data['client_id'] 	= $client_id;
			$data['project_options'] 	= $this->get_project_options($client_id, $project_id);
		}
		
		$data['background'] = TRUE;
		$data['view'] = $this->load->view('message/compose', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}

	function send_message(){
		$from			= $this->session->userdata('id', TRUE);
		$to 			= $this->input->post('to_user_id', TRUE);
		$reply_to 	= $this->input->post('reply_to', TRUE);
		$subject 		= $this->input->post('subject', TRUE);
		$message 		= $this->input->post('message');
		$project_id 		= $this->input->post('project_id', TRUE);
		$compose 			= $this->input->post('compose', TRUE);

		if($compose == '1'){
			$sent_message_id = $this->message_model->send_message($from, $to, $subject, $message, $reply_to, $project_id, $to);
		}
		$message_id = $this->message_model->send_message($from, $to, $subject, $message, $reply_to, $project_id, $from);

		redirect('/message/view/'.$message_id);
	}

	function mark_as_favorite(){
		$this->message_model->mark_as_favorite($this->input->post('message_id'), $this->input->post('flag'));
	}

	function mark_as_trash($message_id = 0, $flag = 0){
		$this->message_model->mark_as_trash($message_id, $flag);
	}

	function delete($message_id = 0, $message_id_main = 0){
		$deleted = $this->message_model->delete($message_id);
		
		if ($deleted){
			$this->session->set_flashdata('message_success', 'Message deleted successfully.');
			redirect('/message/view/'.$message_id_main);
		}
		$this->session->set_flashdata('message_error', 'Message <strong>not</strong> deleted. Please try again.');
		redirect('/message/view/'.$message_id_main);
	}

	function get_project_options($client_id, $project_id="") {
		$projects = $this->time_entry_model->get_client_projects($client_id)->result();
		$result = "";
		
		if(empty($projects)){
			$result .= '<option value="0">'.lang('msg_no_client_projects').'</option>';
		}else{
			
			$result .= '<option value="">'.lang('msg_select_project').'</option>';
			
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