<?php  

class Financeiro_model extends CI_Model {

    function __construct() {
        parent::__construct();
    }
	
	function post(){
	    if($this->input->server('REQUEST_METHOD') == 'POST'){

	    	$dados = new stdClass();

            $dados->idprojeto 	= $this->input->post('idprojeto', TRUE);
			$dados->descricao	= $this->input->post('descricao', TRUE);
			$dados->obs			= $this->input->post('obs', TRUE);
			$dados->status		= $this->input->post('status', TRUE);
			$dados->tipo		= $this->input->post('tipo', TRUE);
			$dados->pago_por	= $this->input->post('pago_por', TRUE);
            $dados->link		= $this->input->post('link', TRUE);
			$dados->valor	   	= fmoeda($this->input->post('valor', TRUE));
			
			if(($dados->status == 'pago') || ($dados->status == 'parcialmente_pago')){
				$dados->data_pago		= fdata($this->input->post('data_pago', TRUE), "-");
				$dados->valor_pago		= fmoeda($this->input->post('valor_pago', TRUE));
			}
			// elseif($dados->status == 'cobrado')
				$dados->data_cobrado	= fdata($this->input->post('data_cobrado', TRUE), "-");

			return $dados;
		}

		return false;
	}
	
	function insert($dados){
		return ($this->db->insert("projeto_financeiro", $dados)) ? $this->id = $this->db->insert_id() : 0;
	}
	
	function update($id, $dados){
        $id = (int) $id;
        if($id > 0){		
            $this->db->where('idfinanceiro', $id);
            return ($this->db->update('projeto_financeiro', $dados)) ? TRUE : FALSE;
        }
        return FALSE;
    }

    function delete($id){
		$id = (int) $id;
        if($id > 0)
			return $this->db->delete('projeto_financeiro', array('idfinanceiro' => $id)) ? TRUE : FALSE ; 

        return FALSE;
	}
	
	function get_financeiro($idfinanceiro){
		$idfinanceiro = (int) $idfinanceiro;

		$sql = "SELECT * FROM projeto_financeiro WHERE idfinanceiro='{$idfinanceiro}' LIMIT 1";
		
		return $this->db->query($sql);
	}

	function get_financeiro_projeto($idprojeto){
		$idprojeto = (int) $idprojeto;

		$sql = "SELECT * FROM projeto_financeiro WHERE idprojeto='{$idprojeto}' ORDER BY data_cobrado ASC, data_pago ASC";
		
		return $this->db->query($sql);
	}
	
	function calcular_custos($idprojeto){

		$pagamentos = $this->financeiro_model->get_financeiro_projeto($idprojeto);
		
		$financeiro = new stdClass();
		$financeiro->total					= 0.0;
		$financeiro->total_empresa 			= 0.0;
		$financeiro->total_cliente 			= 0.0;
		$financeiro->custo_projeto 			= 0.0;
		$financeiro->custo_projeto_cliente 	= 0.0;
		$financeiro->custo_externo 			= 0.0;
		$financeiro->custo_externo_empresa 	= 0.0;
		$financeiro->custo_outro 			= 0.0;
		$financeiro->valor_a_receber		= 0.0;
		
		foreach($pagamentos->result() as $pagamento){
		
			$financeiro->total += $pagamento->valor_pago;
			
			# Calcula o valor ja pago pela empresa e pelo cliente
			if($pagamento->pago_por == 'empresa') $financeiro->total_empresa += $pagamento->valor_pago;
			
			elseif($pagamento->pago_por == 'cliente') $financeiro->total_cliente += $pagamento->valor_pago;
			
			# Calcula os valores para cada tipo de custo
			if($pagamento->tipo == 'custo_projeto'){
				$financeiro->custo_projeto += $pagamento->valor_pago;
				if($pagamento->pago_por == 'cliente') $financeiro->custo_projeto_cliente += $pagamento->valor_pago;
			}
			
			elseif($pagamento->tipo == 'custo_externo'){
				$financeiro->custo_externo += $pagamento->valor_pago;
				if($pagamento->pago_por == 'empresa') $financeiro->custo_externo_empresa += $pagamento->valor_pago;
			}
			
			elseif($pagamento->tipo == 'custo_outro') $financeiro->custo_outro += $pagamento->valor_pago;
			
			# Calcula o valor que a empresa tem a receber
			if(($pagamento->pago_por == 'cliente') && (($pagamento->status != 'pago') || ($pagamento->valor_pago != $pagamento->valor))){

				if(!empty($pagamento->valor_pago)) $financeiro->valor_a_receber += $pagamento->valor;
				
				else{
					$valor_que_falta = $pagamento->valor - $pagamento->valor_pago;
					$financeiro->valor_a_receber += $valor_que_falta;
				}
			}
		}
		
		# Calcula quanto a empresa lucrou em relação ao cliente
		$financeiro->lucro = $financeiro->total_cliente - $financeiro->total_empresa;
		$financeiro->lucro_projeto_gastos = $financeiro->custo_projeto_cliente - $financeiro->custo_externo_empresa;
		
		if($financeiro->lucro >= 0)
			$financeiro->status = 'positivo';
		else
			$financeiro->status = 'negativo';
		
		return $financeiro;
	}

	function get_id_cliente($idprojeto){
		$idprojeto = (int) $idprojeto;

		$projeto = $this->db->query("SELECT idcliente FROM projeto WHERE idprojeto='{$idprojeto}'")->row();

		return empty($projeto) ? NULL : $projeto->idcliente;
	}
	
}