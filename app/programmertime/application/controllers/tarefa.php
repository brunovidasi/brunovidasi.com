<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Tarefa extends CI_Controller {

	public function index(){
		$this->lista();
	}
	
	public function lista($idusuario = 0, $idprojeto = 0, $status = "todos", $inicio = 0){

		// URL: /tarefa/lista/{idusuario}/{idprojeto}/{status}/{offset}
		$idusuario	= (int) $idusuario;
		$idprojeto	= (int) $idprojeto;
		$inicio		= (int) $inicio;
		$maximo		= 15;

		$filtro_status = ($status == 'todos') ? '' : $status;

		$config['base_url']    	= site_url('tarefa/lista/'.$idusuario.'/'.$idprojeto.'/'.rawurlencode($status)).'/';
		$config['total_rows'] 	= $this->tarefa_model->get_tarefas($idprojeto, $idusuario, "DESC", 0, 0, $filtro_status, 'numero');
		$config['per_page']    	= $maximo;
		$config['uri_segment'] 	= 6;
		
		$this->pagination->initialize($config);
		
		$dados["paginacao"]    		= $this->pagination->create_links();
		$dados['tarefas_numero']	= $config['total_rows'];
		$dados['tarefas']			= $this->tarefa_model->get_tarefas($idprojeto, $idusuario, "DESC", $maximo, $inicio, $filtro_status, 'resultado');

		$dados['idusuario']			= $idusuario;
		$dados['idprojeto']			= $idprojeto;

		$dados['usuario']			= "";
		$dados['projeto']			= "";

		if($idusuario > 0){
			$usuario 				= $this->usuario_model->get_usuario($idusuario)->row();

			if(!isset($usuario->idusuario)){
				$this->session->set_flashdata('mensagem_erro', 'Usuário não encontrado');
				redirect('/tarefa/lista/');
			}

			$dados['usuario']		= fnome($usuario->nome, 1);
		}

		if($idprojeto > 0){
			$projeto 				= $this->projeto_model->get_projeto($idprojeto)->row();

			if(!isset($projeto->idprojeto)){
				$this->session->set_flashdata('mensagem_erro', 'Projeto não encontrado');
				redirect('/tarefa/lista');
			}

			$dados['projeto']		= $projeto->nome;
		}

		$dados['view'] = $this->load->view('tarefa/lista', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}

	public function visualizar($idtarefa = ""){

		if(empty($idtarefa)){
			$this->session->set_flashdata('mensagem_atencao', lang('msg_tarefa_inexistente'));
			redirect('/tarefa/');
		}
		
		$tarefa	= $this->tarefa_model->get_tarefa($idtarefa);

		if(empty($tarefa)){
			$this->session->set_flashdata('mensagem_atencao', lang('msg_tarefa_inexistente'));
			redirect('/tarefa/');
		}

		$dados['tarefa']		= $tarefa;
		
		$dados['view'] = $this->load->view('tarefa/visualizar', $dados, TRUE);
		$this->load->view('includes/interna', $dados);

	}

	public function cadastrar($idprojeto = 0, $idusuario = 0){

		$dados['idcresult'] 	= $this->get_projetos_value($this->input->post('idcliente'));
		$dados['usuarios']		= $this->usuario_model->get_usuarios_ativos();
		$dados['clientes']		= $this->cliente_model->get_clientes();
		$dados['fases'] 		= $this->etapa_model->get_fases();

		$dados['cliente_id'] = "";
		$dados['projeto'] 	 = "";
		$dados['usuario_id']  = "";
		
		if(!empty($idprojeto)){

			$idprojeto = (int) $idprojeto;
			$projeto = $this->projeto_model->get_projeto($idprojeto);
			
			$idcliente = "";
			if($projeto->num_rows() > 0){
			   $pro = $projeto->row(); 
			   $idcliente = $pro->idcliente;
			}
			
			$dados['projeto'] 	 = $idprojeto;
			$dados['cliente_id'] = $idcliente;
			$dados['idcresult']  = $this->get_projetos_value($idcliente, $idprojeto);
		}

		if(!empty($idusuario))
			$dados['usuario_id'] 	 = $idusuario;

		$dados['view'] = $this->load->view('tarefa/cadastrar', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}

	public function editar($idtarefa = null){

		if(empty($idtarefa)){
			$this->session->set_flashdata('mensagem_atencao', lang('msg_tarefa_inexistente'));
			redirect('/tarefa/');
		}
		
		$tarefa	= $this->tarefa_model->get_tarefa($idtarefa);

		if(empty($tarefa)){
			$this->session->set_flashdata('mensagem_atencao', lang('msg_tarefa_inexistente'));
			redirect('/tarefa/');
		}

		$dados['tarefa']		= $tarefa;
		// $dados['projetos']		= $this->projeto_model->get_projetos_cliente($tarefa->idcliente);
		$dados['usuarios']		= $this->usuario_model->get_usuarios_ativos();
		$dados['clientes']		= $this->cliente_model->get_clientes();
		$dados['fases'] 		= $this->etapa_model->get_fases();

		$dados['idcresult'] = $this->get_projetos_value($tarefa->idcliente, $tarefa->idprojeto);
		
		$dados['view'] = $this->load->view('tarefa/editar', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}

	public function insert(){

        if($this->valida_form('insert')){
            $dados = $this->tarefa_model->post();
			$idtarefa = $this->tarefa_model->insert($dados);
			
            if($idtarefa > 0){
			    $this->session->set_flashdata('mensagem_sucesso', lang('msg_tarefa_cadastro_sucesso'));
                redirect('/tarefa/visualizar/'.$idtarefa);
            }
			$this->session->set_flashdata('mensagem_erro', lang('msg_tarefa_cadastro_erro'));
			redirect('/tarefa/');
        }
		$this->cadastrar();
    }

    public function update($id = null){

        if(!empty($id)){
			if($this->valida_form('update', $id)){
				
				$dados = $this->tarefa_model->post('update');
			    $atualizado = $this->tarefa_model->update($id, $dados);
				
				if($atualizado)
				    $this->session->set_flashdata('mensagem_sucesso',  lang('msg_tarefa_editar_sucesso'));
				else
					$this->session->set_flashdata('mensagem_erro',  lang('msg_tarefa_editar_erro'));
				
				redirect('/tarefa/visualizar/'.$id);
			}
			$this->editar($id);
	    }
	    redirect('/tarefa/');
	}

	public function delete($idtarefa = 0){
		$excluido = $this->tarefa_model->delete($idtarefa);
		
		if($excluido)
			$this->session->set_flashdata('mensagem_sucesso', lang('msg_tarefa_excluir_sucesso'));
		else
			$this->session->set_flashdata('mensagem_erro', lang('msg_tarefa_excluir_erro'));

		redirect('/tarefa/');		
	}

	private function valida_form($method = 'insert', $id = ''){
		
		$this->form_validation->set_rules('idcliente', 	lang('lbl_cliente'), 			'trim|required');
		$this->form_validation->set_rules('idprojeto', 	lang('lbl_projeto'), 			'trim|required');
		$this->form_validation->set_rules('idusuario', 	lang('lbl_responsavel'), 		'trim|required');
		$this->form_validation->set_rules('idfase', 	lang('lbl_fase'), 				'trim|required');
		$this->form_validation->set_rules('nome', 		lang('lbl_nome'), 				'trim|required');
		$this->form_validation->set_rules('horas', 		lang('msg_horas_previstas'), 	'trim|required');
		$this->form_validation->set_rules('data', 		lang('lbl_prazo'), 				'trim|required');
		
		$this->form_validation->set_rules('status');
		$this->form_validation->set_rules('prazo_hora');
		$this->form_validation->set_rules('descricao');
		
		return $this->form_validation->run();
	}

	public function get_projetos_value($idcliente, $idprojeto="") {
		$projetos = $this->etapa_model->get_projeto_cliente($idcliente)->result();
		$result = "";
		
		if(empty($projetos)){
			$result .= '<option value="">'.lang('msg_sem_projeto_cliente').'</option>';
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