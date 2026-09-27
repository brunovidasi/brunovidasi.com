<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Financeiro extends CI_Controller {

	public function index(){
		$this->cadastrar();
	}
	
	function cadastrar($idcliente = 0, $idprojeto = 0){

		$idcliente = (int) $idcliente;
		$idprojeto = (int) $idprojeto;

		$dados['idcresult'] = $this->get_projetos_value($idcliente, $idprojeto);

		$dados['clientes']	= $this->cliente_model->get_clientes();
		$dados['projeto']	= "";
		$dados['idcliente'] = $idcliente;
		
		$dados['view'] = $this->load->view('financeiro/cadastrar', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	function editar($idfinanceiro = 0){

		$idfinanceiro = (int) $idfinanceiro;

		if(empty($idfinanceiro)){
			$this->session->set_flashdata('mensagem_atencao', lang('msg_financeiro_inexistente'));
			redirect('/projeto/');
		}

		$financeiro = $this->financeiro_model->get_financeiro($idfinanceiro);
		$idprojeto  = "";

		if($financeiro->num_rows() <= 0){
			$this->session->set_flashdata('mensagem_atencao', lang('msg_financeiro_inexistente'));
			redirect('/projeto/');
		}

		$fin 		= $financeiro->row(); 
		$idprojeto 	= $fin->idprojeto;
		$idcliente 	= $this->financeiro_model->get_id_cliente($idprojeto);

		$dados['idcliente'] 	= $idcliente;
		$dados['idprojeto'] 	= $idprojeto;
		$dados['projeto'] 		= $this->projeto_model->get_projeto($idprojeto);

		$dados['idcresult']		= $this->get_projetos_value($idcliente, $idprojeto);

		$dados['retorno'] 		= $financeiro;
		$dados['ret'] 			= $financeiro->row();
		$dados['financeiro'] 	= $financeiro->row();
		$dados['clientes'] 		= $this->cliente_model->get_clientes();
		
		$dados['background'] = TRUE;

		$dados['view'] = $this->load->view('financeiro/editar', $dados, TRUE);
		$this->load->view('includes/interna', $dados);

	}
	
	public function insert(){
        if($this->valida_form('insert')){
            $dados = $this->financeiro_model->post();
			
			$idprojeto = $this->input->post('idprojeto');
			$idfinanceiro = $this->financeiro_model->insert($dados);
			
            if($idfinanceiro > 0){
			    $this->session->set_flashdata('mensagem_sucesso', lang('msg_financeiro_cadastro_sucesso'));
                redirect('/projeto/visualizar/'.$idprojeto);
            }
            
			$this->session->set_flashdata('mensagem_erro', lang('msg_financeiro_cadastro_erro'));
			redirect('/financeiro/cadastrar');
			
        }
		$this->cadastrar();
    }
	
	public function update() {
		$id = $this->input->post('idfinanceiro');
		
        if(!empty($id)){
			if($this->valida_form('update')){
				
				$dados = $this->financeiro_model->post();	
			    $atualizado = $this->financeiro_model->update($id, $dados);				
				
				if($atualizado){
				    $this->session->set_flashdata('mensagem_sucesso', lang('msg_financeiro_editar_sucesso'));
				    redirect('/financeiro/editar/'.$id);
				}

				$this->session->set_flashdata('mensagem_erro', lang('msg_financeiro_editar_erro'));
		        redirect('/financeiro/editar/'.$id);
			}
	    }
		$this->editar($id);
	}

	public function delete($idfinanceiro = 0){

		$financeiro = $this->financeiro_model->get_financeiro($idfinanceiro)->row();

		if(empty($financeiro)){
			$this->session->set_flashdata('mensagem_atencao', lang('msg_financeiro_inexistente'));
			redirect('/projeto/');
		}

		$excluido = $this->financeiro_model->delete($idfinanceiro);
		
		if($excluido)
			$this->session->set_flashdata('mensagem_sucesso', 'Pagamento excluído com sucesso.');
		else
			$this->session->set_flashdata('mensagem_erro', 'Pagamento <strong>não</strong> excluído. Tente novamente');

		redirect('/projeto/visualizar/'.$financeiro->idprojeto);
	}

	private function valida_form($method = 'insert'){

		$this->form_validation->set_rules('idprojeto',	lang('lbl_projeto'),		'required');
		$this->form_validation->set_rules('idcliente',	lang('lbl_cliente'),		'required');
		$this->form_validation->set_rules('descricao',	lang('lbl_descricao'),		'required');
		$this->form_validation->set_rules('status',		lang('lbl_status'),			'required');
		$this->form_validation->set_rules('valor',		lang('lbl_valor'),			'required');
		$this->form_validation->set_rules('tipo',		lang('lbl_tipo'),			'required');
		$this->form_validation->set_rules('pago_por',	lang('lbl_pago_por'),		'required');
		
		$this->form_validation->set_rules('obs');
		$this->form_validation->set_rules('link');
		
		if($this->input->post('status') == 'pago'){
			$this->form_validation->set_rules('valor_pago', lang('lbl_valor_pago'), 'required');
			$this->form_validation->set_rules('data_pago',  lang('lbl_data_pago'), 	'required');
			$this->form_validation->set_rules('data_cobrado');
		}elseif($this->input->post('status') == 'cobrado'){
			$this->form_validation->set_rules('valor_pago');
			$this->form_validation->set_rules('data_pago');
			$this->form_validation->set_rules('data_cobrado', lang('lbl_data_cobrado'), 'required');
		}elseif($this->input->post('status') == 'parcialmente_pago'){
			$this->form_validation->set_rules('valor_pago', lang('lbl_valor_pago'), 'required');
			$this->form_validation->set_rules('data_pago', 	lang('lbl_data_pago'), 	'required');
			$this->form_validation->set_rules('data_cobrado');
		}else{
			$this->form_validation->set_rules('valor_pago');
			$this->form_validation->set_rules('data_pago');
			$this->form_validation->set_rules('data_cobrado');
		}
		
		return $this->form_validation->run();
	}
	
	function get_projetos(){
		$idcliente = $this->input->post('idcliente');
		$projetos = $this->etapa_model->get_projeto_cliente($idcliente)->result();

		if(empty($projetos)){
			echo '<option value="0">'.lang('msg_sem_projeto_cliente').'</option>';
		}else{
			echo '<option value="">'.lang('msg_selecione_projeto').'</option>';
			foreach ($projetos as $projeto) {
				$prazo = fdatahora($projeto->prazo, "/");
				
				$data = $prazo['data'];
				$hora = $prazo['hora'];
				
				$horaprojeto = explode(":", $hora);
				$hora = $horaprojeto[0] . ':' . $horaprojeto[1];
				
				echo '<option value="'.$projeto->idprojeto .'">'.$projeto->nome.' - '.lang('msg_prazo').': '. $data .' '. $hora .' - '.lang('msg_prioridade').': '. $projeto->prioridade .'</option>';
			}
		}
	}
	
	function get_projetos_value($idcliente, $idprojeto = 0){
		$projetos = $this->etapa_model->get_projeto_cliente($idcliente)->result();
		$result = "";
		
		if(empty($projetos)){
			$result .= '<option value="">'.lang('msg_sem_projeto_cliente').'</option>';
		}else{
			
			// $result .= '<option hidden>'.lang('msg_sem_projeto_cliente').'</option>';
			
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