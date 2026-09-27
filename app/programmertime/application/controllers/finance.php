<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Finance extends CI_Controller {

	public function index(){
		$this->create();
	}
	
	function create($client_id = 0, $project_id = 0){

		$client_id = (int) $client_id;
		$project_id = (int) $project_id;

		$data['project_options'] = $this->get_project_options($client_id, $project_id);

		$data['clients']	= $this->client_model->get_clients();
		$data['project']	= "";
		$data['client_id'] = $client_id;
		
		$data['view'] = $this->load->view('finance/create', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	function edit($payment_id = 0){

		$payment_id = (int) $payment_id;

		if(empty($payment_id)){
			$this->session->set_flashdata('message_warning', lang('msg_finance_not_found'));
			redirect('/project/');
		}

		$finance = $this->finance_model->get_payment($payment_id);
		$project_id  = "";

		if($finance->num_rows() <= 0){
			$this->session->set_flashdata('message_warning', lang('msg_finance_not_found'));
			redirect('/project/');
		}

		$fin 		= $finance->row(); 
		$project_id 	= $fin->project_id;
		$client_id 	= $this->finance_model->get_client_id($project_id);

		$data['client_id'] 	= $client_id;
		$data['project_id'] 	= $project_id;
		$data['project'] 		= $this->project_model->get_project($project_id);

		$data['project_options']		= $this->get_project_options($client_id, $project_id);

		$data['entry'] 		= $finance;
		$data['ret'] 			= $finance->row();
		$data['finance'] 	= $finance->row();
		$data['clients'] 		= $this->client_model->get_clients();
		
		$data['background'] = TRUE;

		$data['view'] = $this->load->view('finance/edit', $data, TRUE);
		$this->load->view('includes/internal', $data);

	}
	
	public function insert(){
        if($this->validate_form('insert')){
            $data = $this->finance_model->post();
			
			$project_id = $this->input->post('project_id');
			$payment_id = $this->finance_model->insert($data);
			
            if($payment_id > 0){
			    $this->session->set_flashdata('message_success', lang('msg_finance_create_success'));
                redirect('/project/view/'.$project_id);
            }
            
			$this->session->set_flashdata('message_error', lang('msg_finance_create_error'));
			redirect('/finance/create');
			
        }
		$this->create();
    }
	
	public function update() {
		$id = $this->input->post('payment_id');
		
        if(!empty($id)){
			if($this->validate_form('update')){
				
				$data = $this->finance_model->post();	
			    $updated = $this->finance_model->update($id, $data);				
				
				if($updated){
				    $this->session->set_flashdata('message_success', lang('msg_finance_edit_success'));
				    redirect('/finance/edit/'.$id);
				}

				$this->session->set_flashdata('message_error', lang('msg_finance_edit_error'));
		        redirect('/finance/edit/'.$id);
			}
	    }
		$this->edit($id);
	}

	public function delete($payment_id = 0){

		$finance = $this->finance_model->get_payment($payment_id)->row();

		if(empty($finance)){
			$this->session->set_flashdata('message_warning', lang('msg_finance_not_found'));
			redirect('/project/');
		}

		$deleted = $this->finance_model->delete($payment_id);
		
		if($deleted)
			$this->session->set_flashdata('message_success', lang('msg_finance_delete_success'));
		else
			$this->session->set_flashdata('message_error', lang('msg_finance_delete_error'));

		redirect('/project/view/'.$finance->project_id);
	}

	private function validate_form($method = 'insert'){

		$this->form_validation->set_rules('project_id',	lang('lbl_project'),		'required');
		$this->form_validation->set_rules('client_id',	lang('lbl_client'),		'required');
		$this->form_validation->set_rules('description',	lang('lbl_description'),		'required');
		$this->form_validation->set_rules('status',		lang('lbl_status'),			'required');
		$this->form_validation->set_rules('amount',		lang('lbl_amount'),			'required');
		$this->form_validation->set_rules('type',		lang('lbl_type'),			'required');
		$this->form_validation->set_rules('paid_by',	lang('lbl_paid_by'),		'required');
		
		$this->form_validation->set_rules('notes');
		$this->form_validation->set_rules('link');
		
		if($this->input->post('status') == 'paid'){
			$this->form_validation->set_rules('amount_paid', lang('lbl_amount_paid'), 'required');
			$this->form_validation->set_rules('paid_date',  lang('lbl_paid_date'), 	'required');
			$this->form_validation->set_rules('invoiced_date');
		}elseif($this->input->post('status') == 'invoiced'){
			$this->form_validation->set_rules('amount_paid');
			$this->form_validation->set_rules('paid_date');
			$this->form_validation->set_rules('invoiced_date', lang('lbl_invoiced_date'), 'required');
		}elseif($this->input->post('status') == 'partially_paid'){
			$this->form_validation->set_rules('amount_paid', lang('lbl_amount_paid'), 'required');
			$this->form_validation->set_rules('paid_date', 	lang('lbl_paid_date'), 	'required');
			$this->form_validation->set_rules('invoiced_date');
		}else{
			$this->form_validation->set_rules('amount_paid');
			$this->form_validation->set_rules('paid_date');
			$this->form_validation->set_rules('invoiced_date');
		}
		
		return $this->form_validation->run();
	}
	
	function get_projects(){
		$client_id = $this->input->post('client_id');
		$projects = $this->time_entry_model->get_client_projects($client_id)->result();

		if(empty($projects)){
			echo '<option value="0">'.lang('msg_no_client_projects').'</option>';
		}else{
			echo '<option value="">'.lang('msg_select_project').'</option>';
			foreach ($projects as $project) {
				$deadline = fdatetime_parts($project->deadline, "/");
				
				$date = $deadline['date'];
				$time = $deadline['time'];
				
				$project_time = explode(":", $time);
				$time = $project_time[0] . ':' . $project_time[1];
				
				echo '<option value="'.$project->project_id .'">'.$project->name.' - '.lang('msg_deadline').': '. $date .' '. $time .' - '.lang('msg_priority').': '. $project->priority .'</option>';
			}
		}
	}
	
	function get_project_options($client_id, $project_id = 0){
		$projects = $this->time_entry_model->get_client_projects($client_id)->result();
		$result = "";
		
		if(empty($projects)){
			$result .= '<option value="">'.lang('msg_no_client_projects').'</option>';
		}else{
			
			
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