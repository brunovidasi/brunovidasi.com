<?php  

class Finance_model extends CI_Model {

    function __construct() {
        parent::__construct();
    }
	
	function post(){
	    if($this->input->server('REQUEST_METHOD') == 'POST'){

	    	$data = new stdClass();

            $data->project_id 	= $this->input->post('project_id', TRUE);
			$data->description	= $this->input->post('description', TRUE);
			$data->notes			= $this->input->post('notes', TRUE);
			$data->status		= $this->input->post('status', TRUE);
			$data->type		= $this->input->post('type', TRUE);
			$data->paid_by	= $this->input->post('paid_by', TRUE);
            $data->link		= $this->input->post('link', TRUE);
			$data->amount	   	= parse_currency($this->input->post('amount', TRUE));
			
			if(($data->status == 'paid') || ($data->status == 'partially_paid')){
				$data->paid_date		= fdate($this->input->post('paid_date', TRUE), "-");
				$data->amount_paid		= parse_currency($this->input->post('amount_paid', TRUE));
			}
			// elseif($data->status == 'invoiced')
				$data->invoiced_date	= fdate($this->input->post('invoiced_date', TRUE), "-");

			return $data;
		}

		return false;
	}
	
	function insert($data){
		return ($this->db->insert("payment", $data)) ? $this->id = $this->db->insert_id() : 0;
	}
	
	function update($id, $data){
        $id = (int) $id;
        if($id > 0){		
            $this->db->where('payment_id', $id);
            return ($this->db->update('payment', $data)) ? TRUE : FALSE;
        }
        return FALSE;
    }

    function delete($id){
		$id = (int) $id;
        if($id > 0)
			return $this->db->delete('payment', array('payment_id' => $id)) ? TRUE : FALSE ; 

        return FALSE;
	}
	
	function get_payment($payment_id){
		$payment_id = (int) $payment_id;

		$sql = "SELECT * FROM payment WHERE payment_id='{$payment_id}' LIMIT 1";
		
		return $this->db->query($sql);
	}

	function get_project_payments($project_id){
		$project_id = (int) $project_id;

		$sql = "SELECT * FROM payment WHERE project_id='{$project_id}' ORDER BY invoiced_date ASC, paid_date ASC";
		
		return $this->db->query($sql);
	}
	
	function calculate_costs($project_id){

		$payments = $this->finance_model->get_project_payments($project_id);
		
		$finance = new stdClass();
		$finance->total					= 0.0;
		$finance->total_company 			= 0.0;
		$finance->total_client 			= 0.0;
		$finance->project_cost 			= 0.0;
		$finance->project_cost_client 	= 0.0;
		$finance->external_cost 			= 0.0;
		$finance->external_cost_company 	= 0.0;
		$finance->other_cost 			= 0.0;
		$finance->amount_receivable		= 0.0;
		
		foreach($payments->result() as $payment){
		
			$finance->total += $payment->amount_paid;
			
			# Amount already paid by the company and by the client
			if($payment->paid_by == 'company') $finance->total_company += $payment->amount_paid;
			
			elseif($payment->paid_by == 'client') $finance->total_client += $payment->amount_paid;
			
			# Totals for each type of cost
			if($payment->type == 'project_cost'){
				$finance->project_cost += $payment->amount_paid;
				if($payment->paid_by == 'client') $finance->project_cost_client += $payment->amount_paid;
			}
			
			elseif($payment->type == 'external_cost'){
				$finance->external_cost += $payment->amount_paid;
				if($payment->paid_by == 'company') $finance->external_cost_company += $payment->amount_paid;
			}
			
			elseif($payment->type == 'other_cost') $finance->other_cost += $payment->amount_paid;
			
			# Amount the company still has to receive
			if(($payment->paid_by == 'client') && (($payment->status != 'paid') || ($payment->amount_paid != $payment->amount))){

				if(!empty($payment->amount_paid)) $finance->amount_receivable += $payment->amount;
				
				else{
					$amount_remaining = $payment->amount - $payment->amount_paid;
					$finance->amount_receivable += $amount_remaining;
				}
			}
		}
		
		# How much the company made from the client
		$finance->profit = $finance->total_client - $finance->total_company;
		$finance->project_profit = $finance->project_cost_client - $finance->external_cost_company;
		
		if($finance->profit >= 0)
			$finance->status = 'positive';
		else
			$finance->status = 'negative';
		
		return $finance;
	}

	function get_client_id($project_id){
		$project_id = (int) $project_id;

		$project = $this->db->query("SELECT client_id FROM project WHERE project_id='{$project_id}'")->row();

		return empty($project) ? NULL : $project->client_id;
	}
	
}