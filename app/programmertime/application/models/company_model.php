<?php  

class Company_model extends CI_Model {

    function __construct() {
        parent::__construct();
    }
	
	function post(){
	    if ($this->input->server('REQUEST_METHOD') == 'POST') {
            // The profile form has no representative field; keep the stored one.
            if($this->input->post('representative_id') !== FALSE)
                $this->representative_id = id_or_null($this->input->post('representative_id', TRUE));
            $this->name = $this->input->post('name', TRUE);
            $this->legal_name = $this->input->post('legal_name', TRUE);
            $this->company_tax_id = $this->input->post('company_tax_id', TRUE);
            $this->email = $this->input->post('email', TRUE);
            $this->phone = $this->input->post('phone', TRUE);
            $this->mobile = $this->input->post('mobile', TRUE);
            $this->website = $this->input->post('website', TRUE);
            $this->logo_image = $this->input->post('logo_image', TRUE);
            $this->founding_date = fdate($this->input->post('founding_date', TRUE), "-");
            #$this->created_at = fdate($this->input->post('created_at', TRUE), "-");
			$this->plan = $this->input->post('plan', TRUE);
			$this->activation = $this->input->post('activation', TRUE);
			$this->address = $this->input->post('address', TRUE);
			$this->address_number = $this->input->post('address_number', TRUE);
			$this->address_district = $this->input->post('address_district', TRUE);
			$this->address_line2 = $this->input->post('address_line2', TRUE);
			$this->address_city = $this->input->post('address_city', TRUE);
			$this->address_state = $this->input->post('address_state', TRUE);
			$this->address_postcode = $this->input->post('address_postcode', TRUE);
		}
	}
	
	function update(){
        $id = 1;
		$this->db->where('company_id', $id);
		return ($this->db->update('company', $this)) ? TRUE : FALSE;
    }
	
	function get_company(){
		$sql = "SELECT * FROM company WHERE company_id='1' LIMIT 1";
		return $this->db->query($sql);
	}
	
}