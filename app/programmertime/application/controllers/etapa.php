<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Etapa extends CI_Controller {

	public function index(){
		$this->lancar();
	}
	
	public function lancar($idprojeto = "", $idtarefa = ""){

		$etapa_aberta = $this->etapa_model->etapa_aberta($this->session->userdata('id'));
		if($etapa_aberta->num_rows() > 0) redirect('/etapa/retornar/');
		
		$dados['idcresult'] = $this->get_projetos_value($this->input->post('idcliente'));
		$dados['idpresult'] = $this->get_tarefas_value($this->input->post('idprojeto'));
		$dados['clientes'] 	= $this->cliente_model->get_clientes();
		$dados['fases'] 	= $this->etapa_model->get_fases();
		
		$dados['cliente_id'] = "";
		$dados['projeto_id'] = "";
		$dados['projeto'] 	 = "";
		$dados['tarefas'] 	 = "";

		if(!empty($idprojeto)){

			$idprojeto = (int) $idprojeto;
			$projeto = $this->projeto_model->get_projeto($idprojeto);
			
			$idcliente = "";
			if($projeto->num_rows() > 0){
			   $pro = $projeto->row(); 
			   $idcliente = $pro->idcliente;
			}
			
			$dados['tarefas']	 = $this->tarefa_model->get_tarefas($idprojeto);
			$dados['projeto'] 	 = $idprojeto;
			$dados['projeto_id'] = $idprojeto;
			$dados['cliente_id'] = $idcliente;
			$dados['tarefa_id']  = $idtarefa;
			$dados['idcresult']  = $this->get_projetos_value($idcliente, $idprojeto);
			$dados['idpresult']  = $this->get_tarefas_value($idprojeto, $idtarefa);
		}
		
		// print_r($dados);
		// die();

		$dados['background'] = TRUE;
		$dados['view'] = $this->load->view('etapa/lancar', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	public function retornar(){

		$etapa_aberta = $this->etapa_model->etapa_aberta($this->session->userdata('id'));
		if($etapa_aberta->num_rows() == 0) redirect('/etapa/lancar/');
		
		$etapas = $this->etapa_model->etapa_aberta($this->session->userdata('id'));
		if($etapas->num_rows() > 0){
		   $etapa 		= $etapas->row(); 
		   $idprojeto 	= $etapa->idprojeto;
		   $idtarefa 	= $etapa->idtarefa;
		}

		$dados['idcliente'] = $this->etapa_model->get_id_cliente($idprojeto);
		$dados['projeto'] 	= $this->projeto_model->get_projeto($idprojeto);
		$dados['retorno'] 	= $etapas;
		$dados['ret'] 		= $etapas->row();
		$dados['clientes'] 	= $this->cliente_model->get_clientes();
		$dados['fases'] 	= $this->etapa_model->get_fases();
		$dados['tarefas'] 	= $this->get_tarefas_value($idprojeto, $idtarefa);
		
		$dados['background'] = TRUE;
		$dados['view'] = $this->load->view('etapa/retornar', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	public function editar($idetapa = ""){

		if(empty($idetapa)){
			$this->session->set_flashdata('mensagem_atencao', lang('msg_etapa_inexistente'));
			redirect('/projeto/');
		}

		$etapa = $this->etapa_model->get_etapa($idetapa);
		$idprojeto = "";

		if($etapa->num_rows() > 0){
			$et 		= $etapa->row(); 
			$idprojeto 	= $et->idprojeto;
			$idtarefa 	= $et->idtarefa;
			$fim 		= $et->fim;
		}else{
			$this->session->set_flashdata('mensagem_atencao', lang('msg_etapa_inexistente'));
			redirect('/projeto/');
		}
		
		if(empty($fim)){
			$this->session->set_flashdata('mensagem_atencao', lang('msg_etapa_erro_andamento'));
			redirect('/projeto/visualizar/'.$idprojeto);
		}
		
		$dados['idcliente'] 	= $this->etapa_model->get_id_cliente($idprojeto);
		$dados['projeto'] 		= $this->projeto_model->get_projeto($idprojeto);
		$dados['idprojeto'] 	= $idprojeto;
		$dados['idtarefa'] 		= $idtarefa;
		$dados['tarefa'] 		= $this->tarefa_model->get_tarefa($idtarefa);
		
		$dados['retorno'] 		= $etapa;
		$dados['ret'] 			= $etapa->row();
		$dados['clientes'] 		= $this->cliente_model->get_clientes();
		$dados['fases'] 		= $this->etapa_model->get_fases();
		$dados['tarefas'] 		= $this->tarefa_model->get_tarefas($idprojeto);
		$dados['idpresult']  	= $this->get_tarefas_value($idprojeto, $idtarefa);
		
		$dados['background'] = TRUE;
		$dados['view'] = $this->load->view('etapa/editar', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	public function lista(){

		$inicio = (!$this->uri->segment("3")) ? 0 : $this->uri->segment("3");
		$maximo = 15;
		
		$config['base_url']    	= site_url('etapa/lista/');
		$config['total_rows'] 	= $this->etapa_model->get_etapas_relatorio("", "DESC")->num_rows;
		$config['per_page']    	= $maximo;
		$config['uri_segment'] 	= 3;
		
		$this->pagination->initialize($config);
		
		$dados["paginacao"]    	= $this->pagination->create_links();
		$dados['etapas']		= $this->etapa_model->get_etapas_relatorio("", "DESC", $maximo, $inicio);
		$dados['etapas_andamento'] = $this->etapa_model->get_etapas_relatorio("", "DESC", $maximo, $inicio, TRUE);
		
		$dados['view'] = $this->load->view('etapa/lista', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	public function insert(){
        if ($this->valida_form('insert')){
            $data = $this->etapa_model->post('insert');
			
			$idetapa = $this->etapa_model->insert($data);
			
            if($idetapa > 0)
			    $this->session->set_flashdata('mensagem_sucesso', lang('msg_etapa_cadastro_sucesso'));
            else
				$this->session->set_flashdata('mensagem_erro', lang('msg_etapa_cadastro_erro'));
			
			redirect('/etapa/lancar/');
        }
		$this->lancar();
    }
	
	public function update(){
		$id = $this->input->post('idetapa');
		
        if(!empty($id)){
			if($this->valida_form('update', $id)){
				
				$data = $this->etapa_model->post('update');	
			    $atualizado = $this->etapa_model->update($id, $data);				
				
				if($atualizado)
				    $this->session->set_flashdata('mensagem_sucesso', lang('msg_etapa_retorno_sucesso'));
				else
					$this->session->set_flashdata('mensagem_erro', lang('msg_etapa_retorno_erro'));

		        redirect('/etapa/');
			}
	    }
		$this->retornar();
	}
	
	public function edit(){
		$id = $this->input->post('idetapa');
		
        if(!empty($id)){
			if($this->valida_form('edit', $id)){
				
				$data = $this->etapa_model->post('edit');	
			    $atualizado = $this->etapa_model->update($id, $data);				
				
				if($atualizado)
				    $this->session->set_flashdata('mensagem_sucesso', lang('msg_etapa_editar_sucesso'));
				else
					$this->session->set_flashdata('mensagem_erro', lang('msg_etapa_editar_erro'));
				
		        redirect('/etapa/editar/'.$id);
			}
			$this->editar();
	    }
	}
	
	public function delete($idetapa = 0){
		$excluido = $this->etapa_model->delete($idetapa);
		
		if($excluido){
			$this->session->set_flashdata('mensagem_sucesso', lang('msg_etapa_excluir_sucesso'));
			redirect('/projeto/');
		}
		$this->session->set_flashdata('mensagem_erro', lang('msg_etapa_excluir_erro'));
		redirect('/projeto/');	
	}
	
	private function valida_form($method = "insert", $id = null){

		if($method == "insert"){
			$this->form_validation->set_rules('idusuario',			lang('lbl_usuario'), 			'required');
			$this->form_validation->set_rules('idcliente',			lang('lbl_cliente'),			'required');
			$this->form_validation->set_rules('idprojeto',			lang('lbl_projeto'),			'required');
			$this->form_validation->set_rules('idfase',				lang('lbl_fase'),				'required');
			$this->form_validation->set_rules('descricao_tecnica',	lang('lbl_descricao_tecnica'),	'required');
			$this->form_validation->set_rules('descricao_cliente',	lang('lbl_descricao_cliente'),	'required');
			$this->form_validation->set_rules('data',				lang('lbl_data'),				'required|callback_valida_data');
			$this->form_validation->set_rules('inicio',				lang('lbl_hora_inicio'),		'required|callback_verifica_horas');
		}

		if($method == "update"){
			$this->form_validation->set_rules('descricao_tecnica',	lang('lbl_descricao_tecnica'),	'required');
			$this->form_validation->set_rules('descricao_cliente',	lang('lbl_descricao_cliente'),	'required');
			$this->form_validation->set_rules('inicio',				lang('lbl_hora_inicio'),		'required|callback_verifica_horas');
			$this->form_validation->set_rules('fim',				lang('lbl_hora_final'),			'required|callback_valida_horas|callback_verifica_horas');
			$this->form_validation->set_rules('idetapa',       		lang('lbl_etapa'),   			'required');
		}

		if($method == 'edit'){
			$this->form_validation->set_rules('idusuario',			lang('lbl_usuario'), 			'required');
			$this->form_validation->set_rules('idcliente',			lang('lbl_cliente'),			'required');
			$this->form_validation->set_rules('idprojeto',			lang('lbl_projeto'),			'required');
			$this->form_validation->set_rules('idfase',				lang('lbl_fase'),				'required');
			$this->form_validation->set_rules('descricao_tecnica',	lang('lbl_descricao_tecnica'),	'required');
			$this->form_validation->set_rules('descricao_cliente',	lang('lbl_descricao_cliente'),	'required');
			$this->form_validation->set_rules('data',				lang('lbl_data'),				'required|callback_valida_data');
			$this->form_validation->set_rules('inicio',				lang('lbl_hora_inicio'),		'required|callback_verifica_horas');
			$this->form_validation->set_rules('fim',				lang('lbl_hora_final'),			'required|callback_valida_horas|callback_verifica_horas');
			$this->form_validation->set_rules('idetapa',       		lang('lbl_etapa'),   			'required');
		}
		
		$this->form_validation->set_rules('idtarefa');
		
		
		return $this->form_validation->run();
	}
	
	public function valida_horas(){
		$inicio = $this->input->post('inicio');
		$fim = $this->input->post('fim');
		
		if($inicio > $fim){
			$this->form_validation->set_message('valida_horas', lang('msg_hora_final_menor_inicial'));
			return FALSE;
		}elseif($inicio == $fim){
			$this->form_validation->set_message('valida_horas', lang('msg_hora_final_igual_inicial'));
			return FALSE;
		}
		return TRUE;
	}
	
	public function valida_data(){
		$data = (string) $this->input->post('data');

		// checkdate() throws a TypeError on non-numeric parts in PHP 8
		$valida = FALSE;
		if(preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $data, $partes))
			$valida = checkdate((int) $partes[2], (int) $partes[1], (int) $partes[3]);

		if(!$valida){
			$this->form_validation->set_message('valida_data', lang('msg_data_invalida'));
			return FALSE;
		}
		return TRUE;
	}
	
	public function verifica_horas(){
		$inicio = $this->input->post('inicio');
		$fim = $this->input->post('fim');
		
		if(!empty($inicio)){
			list($h, $m) = explode(':', $this->input->post('inicio'));
			if(($h >= 24) OR ($m > 59)){
				$this->form_validation->set_message('verifica_horas', lang('msg_hora_inicio_invalida'));
				return FALSE;
			}
		}
		
		if(!empty($fim)){
			list($h, $m) = explode(':', $this->input->post('fim'));
			if(($h >= 24) OR ($m > 59)){
				$this->form_validation->set_message('verifica_horas', lang('msg_hora_final_invalida'));
				return FALSE;
			}
		}
		return TRUE;
	}
	
	public function get_projetos($idprojeto="", $idcliente="") {
		if(empty($idcliente)) $idcliente = $this->input->post('idcliente');
		
		$projetos = $this->etapa_model->get_projeto_cliente($idcliente)->result();

		if(empty($projetos)){
			echo '<option value="">'.lang('msg_sem_projeto_cliente').'</option>';
		}else{
			echo '<option hidden>'.lang('msg_selecione_projeto').'</option>';
			foreach ($projetos as $projeto) {
				$prazo = fdatahora($projeto->prazo, "/");
				
				$data = $prazo['data'];
				$hora = $prazo['hora'];
				
				$horaprojeto = explode(":", $hora);
				$hora = $horaprojeto[0] . ':' . $horaprojeto[1];
				
				$selected = "";
				if($idprojeto == $projeto->idprojeto){
					$selected = "selected='selected'";
				}
				echo '<option value="'.$projeto->idprojeto .'" '. $selected .'>'.$projeto->nome.' - '.lang('msg_prazo').': '. $data .' '. $hora .' - '.lang('msg_prioridade').': '. $projeto->prioridade .'</option>';
			}
		}
	}
	
	public function get_projetos_value($idcliente, $idprojeto="") {
		$projetos = $this->etapa_model->get_projeto_cliente($idcliente)->result();
		$result = "";
		
		if(empty($projetos)){
			$result .= '<option value="">'.lang('msg_sem_projeto_cliente').'</option>';
		}else{
			
			$result .= '<option hidden>'.lang('msg_sem_projeto_cliente').'</option>';
			
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

	public function get_tarefa($idprojeto="", $idtarefa="") {
		if(empty($idtarefa)) $idtarefa = $this->input->post('idtarefa');
		
		$tarefa = $this->tarefa_model->get_tarefa($idtarefa);

		if(empty($tarefa))
			echo '';
		else
			echo json_encode($tarefa);
		
	}

	public function get_tarefas($idprojeto="", $idtarefa="") {
		if(empty($idtarefa)) $idtarefa = $this->input->post('idtarefa');
		
		$tarefas = $this->tarefa_model->get_tarefas($idprojeto);

		if(empty($tarefas)){
			echo '<option value="">'.lang('msg_sem_tarefa_projeto').'</option>';
		}else{
			echo '<option value="0">'.lang('msg_selecione_tarefa').'</option>';
			foreach ($tarefas as $tarefa) {
				$prazo = fdatahora($tarefa->data_prazo, "/");
				
				$data = $prazo['data'];
				$hora = $prazo['hora'];
				
				$horatarefa = explode(":", $hora);
				$hora = $horatarefa[0] . ':' . $horatarefa[1];
				
				$selected = "";
				if($idtarefa == $tarefa->idtarefa)
					$selected = "selected='selected'";
				
				echo '<option value="'.$tarefa->idtarefa .'" '. $selected .'>'.$tarefa->nome.' - '.lang('msg_prazo').': '. $data .' - '.lang('msg_horas_previstas').': '. $tarefa->horas .'</option>';
			}
		}
	}

	public function get_tarefas_value($idprojeto = "", $idtarefa="") {
		$tarefas = $this->tarefa_model->get_tarefas($idprojeto);
		$result = "";
		
		if(empty($tarefas)){
			$result .= '<option value="">'.lang('msg_sem_tarefa_projeto').'</option>';
		}else{
			$selected = "";
			if(set_value('idtarefa', $idtarefa) == 0)
				$selected = 'selected="selected"';

			$result .= '<option value="0" '.$selected.'>'.lang('msg_selecione_tarefa').'</option>';
			
			foreach($tarefas as $tarefa) {
				$prazo = fdatahora($tarefa->data_prazo, "/");
				
				$data = $prazo['data'];
				$hora = $prazo['hora'];
				
				$horatarefa = explode(":", $hora);
				$hora = $horatarefa[0] . ':' . $horatarefa[1];
				
				$selected = "";
				if(set_value('idtarefa', $idtarefa) == $tarefa->idtarefa)
					$selected = 'selected="selected"';
				
				$result .=  '<option value="'.$tarefa->idtarefa .'" '. $selected .'>'.$tarefa->nome.' - '.lang('msg_prazo').': '. $data .' - '.lang('msg_horas_previstas').': '. $tarefa->horas .'</option>';
			}
		}
		return $result;
	}
}