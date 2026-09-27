<?php  

class Report_model extends CI_Model {

    function __construct() {
        parent::__construct();
    }
	
	function post(){
	    if ($this->input->server('REQUEST_METHOD') == 'POST'){
            $this->project_id	= $this->input->post('project_id', TRUE);
            $this->report 	= $this->input->post('report');
			$this->date 		= date('Y-m-d H:i:s');
		}
	}
	
	function insert(){
	    if ($this->db->insert("report", $this)){
            return $this->id = $this->db->insert_id();
        }
        return 0;
	}
	
	function update($id) {
        $id = (int) $id;
        if ($id > 0) {
            $this->db->where('report_id', $id);
			if($this->db->update('report', $this)){
				return TRUE;
			}
			return FALSE;
        }
        return FALSE;
	}
	
	function get_report($report_id){
		$report_id = (int) $report_id;

		$sql = "SELECT * FROM report WHERE report_id='{$report_id}' LIMIT 1";
		
		return $this->db->query($sql);
	}
	
	function get_reports(){
		$sql = "SELECT	
					R.*, 
					P.name as project_name,
					C.name as client_name
				FROM 
					report as R 
				RIGHT JOIN 
					project as P ON R.project_id = P.project_id
				LEFT JOIN 
					client as C ON P.client_id = C.client_id
				ORDER BY 
					R.date DESC
		";
		
		return $this->db->query($sql);
	}
	
	function get_reports_list($per_page, $offset){
		$per_page = (int) $per_page;
		$offset = (int) $offset;

		$sql = "SELECT	
					R.*, 
					P.name as project_name,
					C.name as client_name
				FROM 
					report as R
				RIGHT JOIN 
					project as P ON R.project_id = P.project_id
				LEFT JOIN 
					client as C ON P.client_id = C.client_id
				ORDER BY 
					R.date DESC
				LIMIT 
					{$offset}, {$per_page}
		";
		
		return $this->db->query($sql);
	}
	
	function get_info($project_id){
		$project_id = (int) $project_id;

		$sql = "SELECT 	
					P.*, 
					PT.type as ProjectType, 
					U.name as UserName, 
					U.email as UserEmail, 
					C.name as ClientName, 
					C.email as ClientEmail	
				FROM 
					project as P
				LEFT JOIN 
					project_type as PT ON PT.type_id = P.type_id
				LEFT JOIN 
					user as U ON U.user_id = P.owner_id
				LEFT JOIN 
					client as C ON C.client_id = P.client_id
				WHERE 
					P.project_id='{$project_id}'";
		
		return $this->db->query($sql);
	}
	
	function generate_report_html($project_id){
		
		# Time entry rows
		$time_entry_rows = "<tr>
							<th align='right'>Phase</th>
							<th>Technical Description</th>
							<!--<th>Client Description</th>-->
							<th>Owner</th>
							<th>Date</th>
							<th>Time</th>
						</tr>";

		$time_entries = $this->time_entry_model->get_time_entries_report($project_id);
		$total_hours = array();
		
		foreach($time_entries->result() as $time_entry){
			$time_entry_rows .= '
			<tr>
				<td>'. $time_entry->phase .'</td>
				<td>'. $time_entry->technical_description .'</td>
				<!--<td>'. $time_entry->client_description .'</td>-->
				<td>'. short_name($time_entry->owner, 2) .'</td>
				<td>'. fdate($time_entry->date, "/") .'</td>
				<td>'. calculate_hours($time_entry->end_time, $time_entry->start_time) .'</td>
			</tr>
			';
			
			$total_hours[] = calculate_total_hours($time_entry->end_time, $time_entry->start_time);
		}
		
		# Payment rows
		$payment_rows = "<tr><th>Description</th><th>Status</th><th>Amount</th><th>Amount Paid</th><th>Invoiced On</th><th>Paid On</th></tr>";
		$finances = $this->finance_model->get_project_payments($project_id);
		
		foreach($finances->result() as $finance){
			
			$paid = $finance->status;
			if($finance->status == 'unpaid')
				$paid = 'Unpaid';
			elseif($finance->status == 'invoiced')
				$paid = 'Invoiced';
			elseif($finance->status == 'partially_paid')
				$paid = 'Partially Paid';
			elseif($finance->status == 'paid')
				$paid = 'Paid';
			
			$payment_rows .= '
			<tr>
				<td>'. $finance->description .'</td>
				<td>'. $paid .'</td>
				<td>'. currency($finance->amount) .'</td>
				<td>'. currency($finance->amount_paid) .'</td>
				<td>'. fdatetime($finance->invoiced_date, "/") .'</td>
				<td>'. fdatetime($finance->paid_date, "/") .'</td>
			</tr>
			';
		}
		
		$info_rows = $this->report_model->get_info($project_id);

		$client_id = $owner_id = $project_type_name = $owner_name = $owner_email = $client_name = $client_email = '';

		foreach($info_rows->result() as $row){
			$client_id = $row->client_id;
			$owner_id = $row->owner_id;
			$project_type_name = $row->ProjectType;
			$owner_name = $row->UserName;
			$owner_email = $row->UserEmail;
			$client_name = $row->ClientName;
			$client_email = $row->ClientEmail;
		}
		
		$projects = $this->project_model->get_project($project_id);
		
		if($projects->num_rows() > 0){
		
			foreach($projects->result() as $project){
				
				$report = new stdClass();
				$report->title = "Report - " . $project->name . " - " . date('d/m/Y');
				
				$report->html = '
				
				<div style="background:#eee; border:1px solid #ccc; padding:5px 10px"><strong>Project Report - '. $project->name .'</strong></div><hr />
				
				<table style="width:100%;">
					
					<tr>
						<td><strong>Project:</strong></td>
						<td>'. $project->name .' ('. $project_type_name .') ['. $project_id .']</td>
						
						<td><strong>Started:</strong></td>
						<td>'. fdate($project->start_date , "/") .'</td>
					</tr>
					
					<tr>
						<td><strong>Client:</strong></td>
						<td>'. $client_name .' ['. $client_id .']</td>
						
						<td><strong>Today:</strong></td>
						<td>'. date('d/m/Y') .'</td>
					</tr>
					
					<tr>
						<td><strong>Email:</strong></td>
						<td>'. $client_email .'</td>
						
						<td><strong>Deadline:</strong></td>
						<td>'. fdatetime($project->deadline,"/") .'</td>
					</tr>
					
					<tr>
						<td><strong>Owner: </strong></td>
						<td>'. $owner_name . ' - ' . $owner_email . ' [' . $owner_id .']</td>
						
						<td><strong>Finished:</strong></td>
						<td>'. fdate($project->end_date , "/") .'</td>
					</tr>
				
				</table>
				
				<hr><div style="background:#eee; border:1px solid #ccc; padding:5px 10px"><strong>Completed Time Entries</strong></div><hr>
				
				<table style="width:100%;">
					'. $time_entry_rows .'
					<tr>
					<td></td><td></td><td></td><td></td><td><strong>'. sum_hours($total_hours) .' hours</strong></td>
					</tr>
				</table>
				
				<hr><div style="background:#eee; border:1px solid #ccc; padding:5px 10px"><strong>Payments</strong></div><hr>
				
				<table style="width:100%;">
					'. $payment_rows .'
				</table>
				
				<hr>';
			}
		
		}else{
			$report = new stdClass();
			$report->title = "ERROR";
			$report->html = '
				<blockquote>
				<p><span class="marker">ERROR (no report, project or client selected).&nbsp;Go back and select an existing report.</span></p>
				</blockquote>
			';
		}
		
		return $report;
	}
	
	function generate_report_pdf($html="", $title=""){
		
		if(empty($html)){
			$html 	= $this->input->post('report');
		}
		
		if(empty($title)){
			$title = $this->input->post('title');
		}
		
		include_once(FCPATH."assets/mpdf/mpdf.php");
		
		$stylesheet = file_get_contents(FCPATH.'assets/mpdf/mpdf_style.css');

		// Anything echoed while building (e.g. PHP notices in development mode)
		// would corrupt the PDF or make mPDF abort, so discard it.
		ob_start();
		$mpdf = new mPDF();
		$mpdf->Bookmark('Start of the document');
		$mpdf->WriteHTML($stylesheet,1);
		$mpdf->WriteHTML((string) $html, 2);
		$pdf = $mpdf->Output('', 'S');
		while (ob_get_level() > 0) ob_end_clean();

		header('Content-Type: application/pdf');
		header('Content-Disposition: inline; filename="report.pdf"');
		header('Content-Length: '.strlen($pdf));
		echo $pdf;
		// $mpdf->Output($title.'.pdf', 'D');

		exit;
	
	}
	
}