<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Projeto extends CI_Controller {
	
    public function __construct(){
        parent::__construct();
    }
	
	public function index(){
        $this->lista();
    }
	
	public function lista(){

		$termo = $this->input->post('termo');
		$this->session->set_userdata('idcliente', "");
		$this->session->set_userdata('termo', $termo);
		
		if(!empty($termo)){
			$primeiro_caracter = substr($termo, 0, 1);
			
			if($primeiro_caracter == '#'){
				$termo_array = explode('#', $termo);
				$termo_1 = (int) $termo_array[1];
				if($this->projeto_model->get_projeto($termo_1)->num_rows() == 1)
					redirect('/projeto/visualizar/'.$termo_1);
			}
		}
		
		$inicio = (!$this->uri->segment("3")) ? 0 : $this->uri->segment("3");
		$maximo = 15;
		
		$config['base_url']   	= site_url('projeto/lista/');
		$config['total_rows']  	= $this->projeto_model->get_projetos($termo)->num_rows;
		$config['per_page']    	= $maximo;
		$config['uri_segment'] 	= 3;
		
		$this->pagination->initialize($config);
		
		$dados["paginacao"]    	= $this->pagination->create_links();
		$dados['projetos'] 		= $this->projeto_model->get_projetos_lista($maximo, $inicio, $termo);
		$dados['clientes'] 		= $this->cliente_model->get_clientes();
		
		$dados['view'] = $this->load->view('projeto/lista', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	public function visualizar($idprojeto = null){

		if(empty($idprojeto)){
			$this->session->set_flashdata('mensagem_atencao', lang('msg_projeto_inexistente'));
			redirect('/projeto/');
		}

		$dados_projeto = $this->projeto_model->get_projeto($idprojeto);
		$dados['dados_projeto'] = $dados_projeto;
		
		if($dados_projeto->num_rows() == 0){
			$this->session->set_flashdata('mensagem_atencao', lang('msg_projeto_inexistente'));
			redirect('/projeto/lista/');
		}
		
		$projeto 			= $dados_projeto->row();
		$etapas 			= $this->etapa_model->get_etapas_relatorio($idprojeto, "DESC");
		$etapas_andamento 	= $this->etapa_model->get_etapas_relatorio($idprojeto, "DESC", NULL, NULL, TRUE);
		$pagamentos 		= $this->financeiro_model->get_financeiro_projeto($idprojeto);
		$financeiro 		= $this->financeiro_model->calcular_custos($idprojeto);
		$imagens 			= $this->imagem_model->get_imagens($idprojeto);
		$envolvidos 		= $this->projeto_model->get_usuarios_envolvidos($idprojeto);
		
		$dados['projeto'] = $projeto;
		$dados['etapas'] = $etapas;
		$dados['etapas_andamento'] = $etapas_andamento;
		$dados['pagamentos'] = $pagamentos;
		$dados['financeiro'] = $financeiro;
		$dados['imagens'] = $imagens;
		$dados['envolvidos'] = $envolvidos;
			
		$dados['numero_etapas'] = $etapas->num_rows() + $etapas_andamento->num_rows();
		$dados['numero_pagamentos'] = $pagamentos->num_rows();
		$dados['numero_imagens'] = 0;
		
		$class_tr = ""; $danger = "";
		if($projeto->prazo <= date('Y-m-d H:i:s')){ $class_tr = 'danger'; $danger = 'danger'; }
		if($projeto->status == 'pausado'){ $class_tr = 'warning'; }
		if($projeto->status == 'cancelado'){ $class_tr = 'danger'; $danger = 'danger'; }
		if($projeto->status == 'concluido'){ $class_tr = '';}
		
		$dados['class_tr'] = $class_tr;
		$dados['danger'] = $danger;
		
		if($financeiro->status == 'positivo')
			$dados['class_financeiro'] = "";
		else
			$dados['class_financeiro'] = "danger";
	
		$dados['lanca_etapa'] 		= $this->session->userdata('lanca_etapa');
		$dados['lanca_pagamento'] 	= $this->session->userdata('lanca_pagamento');
		$dados['cadastra_projeto'] 	= $this->session->userdata('cadastra_projeto');
		$dados['edita_projeto'] 	= $this->session->userdata('edita_projeto');
		$dados['cadastra_cliente'] 	= $this->session->userdata('cadastra_cliente');
		$dados['envia_relatorio'] 	= $this->session->userdata('envia_relatorio');
		

		if($etapas->num_rows() > 0){
		
			$array_etapas = array();
			$idusuario = array();
			$total_horas = array();
			$usuario_etapa = array();
			
			foreach($etapas->result() as $etapa){
				$array_etapas['id'][] = $etapa->idfase;
				$array_etapas[$etapa->idfase] = $etapa->fase;
				$idusuario[$etapa->idusuario] = $etapa->responsavel;
				$total_horas[] = calcular_horas_total($etapa->fim, $etapa->inicio);
			}
			
			$dados['horas_gastas'] = somar_horas($total_horas);
			
			$valores_etapas = array_count_values($array_etapas['id']);
			
			foreach($valores_etapas as $key => $valor){
				$porcentagem[$key]['porcentagem'] = ($valores_etapas[$key] * 100) / array_sum($valores_etapas);
				$porcentagem[$key]['nome'] = $array_etapas[$key];
				
				if($valores_etapas[$key] != 1){ $concatena_etapa = " etapas"; }else{ $concatena_etapa = " etapa"; }
				
				$porcentagem[$key]['quantidade'] = $valores_etapas[$key];
				$porcentagem[$key]['numero_etapas'] = $valores_etapas[$key] . $concatena_etapa;
				
				foreach($idusuario as $key2 => $valor2){
					$etapa_usuario = $this->etapa_model->get_etapas_usuario($idprojeto, $key2, $key);
					$usuario_etapa[$key2][$key] = $etapa_usuario->num_rows();
					$usuario_etapa[$key2]['nome'] = $idusuario[$key2];
				}
			}
			
			$dados['porcentagem'] 	 = $porcentagem;
			$dados['usuario_etapa']  = $usuario_etapa;
			$dados['array_etapas'] 	 = $array_etapas;
			$dados['valores_etapas'] = $valores_etapas;
			
			$usuarios = $this->etapa_model->get_id_usuario($idprojeto);
			
			$array_fase[] = array();
			
			foreach($usuarios->result() as $usuario){
				$usuario->idusuario;
				$usuario->idfase;
				
				$array_fase[$usuario->idfase][] = $usuario->idusuario;
				
				$array_fase_contador[$usuario->idfase] = array_count_values($array_fase[$usuario->idfase]);
			}
			
			$dados['array_fase'] = $array_fase;
			$dados['array_fase_contador'] = $array_fase_contador;
		
		}else{
			$dados['porcentagem'] 	= "";
			$dados['array_etapas'] 	= "";
			$dados['array_fase'] 	= "";
			$dados['array_fase_contador'] = "";
			$dados['horas_gastas'] 	= "00:00";
		}
		
		$dados['view'] = $this->load->view('projeto/visualizar', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	public function cadastrar(){

		$dados['tipos']			= $this->projeto_model->get_tipos();
		$dados['usuarios']		= $this->usuario_model->get_usuarios_ativos();
		$dados['clientes']		= $this->cliente_model->get_clientes();
		$dados["post_imagens"]	= $this->projeto_model->post_imagens();
		
		$dados['view'] = $this->load->view('projeto/cadastrar', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	public function editar($idprojeto = null){

		if(empty($idprojeto)){
			$this->session->set_flashdata('mensagem_atencao', lang('msg_projeto_inexistente'));
			redirect('/projeto/');
		}
		
		$crop = new stdClass();
		$crop->origem 	= "assets.upload.temp.";
		$crop->destino 	= "assets.upload.imagens.imgs_projeto";
		$crop->largura 	= "400";
		$crop->altura 	= "300";

		$projeto = $this->projeto_model->get_projeto($idprojeto);
		if($projeto->num_rows() == 0){
			$this->session->set_flashdata('mensagem_atencao', lang('msg_projeto_inexistente'));
			redirect('/projeto/');
		}

		$dados['crop_projeto'] 	= $crop;
		$dados['projeto']		= $projeto->row();
		$dados['tipos']			= $this->projeto_model->get_tipos();
		$dados['usuarios']		= $this->usuario_model->get_usuarios_ativos();
		$dados['clientes']		= $this->cliente_model->get_clientes();
		
		//$dados["post_imagens"] = $this->projeto_model->post_imagens($this->projeto_model->retorna_imagens($idprojeto));
		
		$dados['view'] = $this->load->view('projeto/editar', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	public function insert(){

        if($this->valida_form('insert')){
            $dados = $this->projeto_model->post();
			$idprojeto = $this->projeto_model->insert($dados);
			
            if($idprojeto > 0){
			    $this->session->set_flashdata('mensagem_sucesso', lang('msg_projeto_cadastro_sucesso'));
                redirect('/projeto/visualizar/'.$idprojeto);
            }
			$this->session->set_flashdata('mensagem_erro', lang('msg_projeto_cadastro_erro'));
			redirect('/projeto/');
        }
		$this->cadastrar();
    }
	
	public function update($id = null){

        if(!empty($id)){
			if($this->valida_form('update', $id)){
				
				$dados = $this->projeto_model->post();
			    $atualizado = $this->projeto_model->update($id, $dados);
				
				if($atualizado)
				    $this->session->set_flashdata('mensagem_sucesso', lang('msg_projeto_editar_sucesso'));
				else
					$this->session->set_flashdata('mensagem_erro', lang('msg_projeto_editar_erro'));
				
				redirect('/projeto/visualizar/'.$id);
			}
			$this->editar($id);
	    }
	    redirect('/projeto/');
	}
	
	public function observacao($id = null){

        if(!empty($id)){
        	if($this->valida_obs()){
				$dados = $this->projeto_model->post_obs();
			    $atualizado = $this->projeto_model->update($id, $dados);
				
				if($atualizado)
				    $this->session->set_flashdata('mensagem_sucesso', lang('msg_observacao_editar_sucesso'));
				else
					$this->session->set_flashdata('mensagem_erro', lang('msg_observacao_editar_erro'));

				redirect('/projeto/visualizar/'.$id);
			}
	    }
	    redirect('/projeto/');
	}
	
	public function delete($idprojeto = 0){
		$excluido = $this->projeto_model->delete($idprojeto);
		
		if($excluido)
			$this->session->set_flashdata('mensagem_sucesso', lang('msg_projeto_excluir_sucesso'));
		else
			$this->session->set_flashdata('mensagem_erro', lang('msg_projeto_excluir_erro'));

		redirect('/projeto/');		
	}
	
	private function valida_form($method = 'insert', $id = ''){
		
		$this->form_validation->set_rules('idcliente', 		lang('lbl_cliente'), 		'trim|required');
		$this->form_validation->set_rules('idtipo', 		lang('lbl_tipo'), 			'trim|required');
		$this->form_validation->set_rules('idresponsavel', 	lang('lbl_responsavel'), 	'trim|required');
		$this->form_validation->set_rules('nome', 			lang('lbl_nome'), 			'trim|required');
		$this->form_validation->set_rules('descricao', 		lang('lbl_descricao'), 		'trim|required');
		$this->form_validation->set_rules('prazo', 			lang('lbl_prazo'), 			'trim|required');
		$this->form_validation->set_rules('prazo_hora', 	lang('lbl_prazo_hora'), 	'trim|required');
		$this->form_validation->set_rules('data_inicio', 	lang('lbl_data_inicio'), 	'trim|required');
		$this->form_validation->set_rules('data_fim');
		$this->form_validation->set_rules('imagem');
		$this->form_validation->set_rules('obs');
		$this->form_validation->set_rules('link');
		$this->form_validation->set_rules('status', 		lang('lbl_status'), 		'trim|required');
		$this->form_validation->set_rules('prioridade', 	lang('lbl_prioridade'), 	'trim|required');
		
		return $this->form_validation->run();
	}
	
	private function valida_obs(){
		$this->form_validation->set_rules('obs');
		return $this->form_validation->run();
	}
}