<?php  

class Empresa_model extends CI_Model {

    function __construct() {
        parent::__construct();
    }
	
	function post(){
	    if ($this->input->server('REQUEST_METHOD') == 'POST') {
            $this->idrepresentante = id_ou_null($this->input->post('idrepresentante', TRUE));
            $this->nome = $this->input->post('nome', TRUE);
            $this->razao_social = $this->input->post('razao_social', TRUE);
            $this->cnpj = $this->input->post('cnpj', TRUE);
            $this->email = $this->input->post('email', TRUE);
            $this->telefone = $this->input->post('telefone', TRUE);
            $this->celular = $this->input->post('celular', TRUE);
            $this->website = $this->input->post('website', TRUE);
            $this->imagem_logo = $this->input->post('imagem_logo', TRUE);
            $this->data_fundacao = fdata($this->input->post('data_fundacao', TRUE), "-");
            #$this->data_cadastro = fdata($this->input->post('data_cadastro', TRUE), "-");
			$this->plano = $this->input->post('plano', TRUE);
			$this->ativacao = $this->input->post('ativacao', TRUE);
			$this->endereco = $this->input->post('endereco', TRUE);
			$this->endereco_numero = $this->input->post('endereco_numero', TRUE);
			$this->endereco_bairro = $this->input->post('endereco_bairro', TRUE);
			$this->endereco_complemento = $this->input->post('endereco_complemento', TRUE);
			$this->endereco_municipio = $this->input->post('endereco_municipio', TRUE);
			$this->endereco_estado = $this->input->post('endereco_estado', TRUE);
			$this->endereco_cep = $this->input->post('endereco_cep', TRUE);
		}
	}
	
	function update(){
        $id = 1;
		$this->db->where('idempresa', $id);
		return ($this->db->update('projeto_financeiro', $this)) ? TRUE : FALSE;
    }
	
	function get_empresa(){
		$sql = "SELECT * FROM empresa WHERE idempresa='1' LIMIT 1";
		return $this->db->query($sql);
	}
	
}