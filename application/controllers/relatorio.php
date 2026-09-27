<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Relatorio extends CI_Controller {

	public function index(){
		$this->lista();
	}
	
	function lista(){
		$dados['idcresult']  = $this->get_projetos_cliente($this->input->post('idcliente'));
		$dados['clientes'] 	 = $this->cliente_model->get_clientes();
		$dados['cliente_id'] = "";

		$dados['view'] = $this->load->view('relatorio/lista', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	function gerar($idprojeto=""){		
		if(!empty($idprojeto)) 
			$dados['relatorio'] = $this->relatorio_model->gera_relatorio_html($idprojeto);
		else{
			$idprojeto_post = $this->input->post('idprojeto');
			
			if(!empty($idprojeto_post)) 
				$dados['relatorio'] = $this->relatorio_model->gera_relatorio_html($idprojeto_post);
			else
				redirect('/relatorio/');
		}

		$dados['view'] = $this->load->view('relatorio/gerar', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	function gerar_pdf($html=""){
		$this->relatorio_model->gera_relatorio_mpdf($html);
	}
	
	function get_projetos_cliente($idcliente, $idprojeto="") {
		$projetos = $this->etapa_model->get_projeto_cliente($idcliente)->result();
		
		$result = "";
		
		if(empty($projetos)){
			$result .= '<option hidden value="0">'.lang('msg_sem_projeto_cliente').'</option>';
		}else{
			$result .= '<option hidden>'.lang('msg_selecione_projeto').'</option>';
			
			foreach ($projetos as $projeto) {
				$prazo = fdatahora($projeto->prazo, "/");
				
				$data = $prazo['data'];
				$hora = $prazo['hora'];
				
				$horaprojeto = explode(":", $hora);
				$hora = $horaprojeto[0] . ':' . $horaprojeto[1];
				
				$selected = "";
				if(set_value('idprojeto', $idprojeto) == $projeto->idprojeto){
					$selected = 'selected="selected"';
				}
				$result .=  '<option value="'.$projeto->idprojeto .'" '. $selected .'>'.$projeto->nome.' - '.lang('msg_prazo').': '. $data .' '. $hora .' - '.lang('msg_prioridade').': '. $projeto->prioridade .'</option>';
			}
		}
		return $result;
	}
}