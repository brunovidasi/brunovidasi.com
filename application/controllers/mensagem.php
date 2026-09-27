<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mensagem extends CI_Controller {

	public function index(){
		$this->lista();
	}
	
	function lista(){
		$dados['mensagens'] = $this->mensagem_model->get_mensagens();
		$dados['mensagens_nao_lidas'] = $this->mensagem_model->get_mensagens_nao_lidas();
		
		$dados['view'] = $this->load->view('mensagem/lista', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}

	function visualizar($idmensagem = 1){
		$mensagem = $this->mensagem_model->get_mensagem($idmensagem); 
		$dados['mensagem_clicada'] = $idmensagem;

		if($mensagem->num_rows() != 1) redirect('/mensagem/lista/');

		$mensagem = $mensagem->row();
		$this->mensagem_model->marcar_como_lida($idmensagem, TRUE);

		if(!empty($mensagem->resposta_de)){
			$mensagem = $this->mensagem_model->get_mensagem($mensagem->resposta_de)->row();
			$idmensagem = $mensagem->idmensagem;
		}
		$this->mensagem_model->marcar_como_lida($idmensagem, TRUE);

		$dados['mensagem']  = $mensagem;
		$dados['msg_assoc'] = $this->mensagem_model->get_mensagens_associadas($idmensagem);

		$dados['view'] = $this->load->view('mensagem/visualizar', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}

	function nova($idprojeto = ""){
		$dados['clientes'] 	= $this->cliente_model->get_clientes();
		$dados['usuarios']	= $this->usuario_model->get_usuarios();
		$dados['idcresult'] = $this->get_projetos_value($this->input->post('idcliente'));

		$dados['cliente_id'] 	= "";
		$dados['projeto'] 		= "";
		if(!empty($idprojeto)){
			$projeto = $this->projeto_model->get_projeto($idprojeto);
			
			if ($projeto->num_rows() > 0){
			   $pro = $projeto->row(); 
			   $idcliente = $pro->idcliente;
			}
			
			$dados['projeto'] 		= $idprojeto;
			$dados['cliente_id'] 	= $idcliente;
			$dados['idcresult'] 	= $this->get_projetos_value($idcliente, $idprojeto);
		}
		
		$dados['background'] = TRUE;
		$dados['view'] = $this->load->view('mensagem/nova', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}

	function enviar_mensagem(){
		$from			= $this->session->userdata('id', TRUE);
		$to 			= $this->input->post('id_usuario_to', TRUE);
		$resposta_de 	= $this->input->post('resposta_de', TRUE);
		$assunto 		= $this->input->post('assunto', TRUE);
		$mensagem 		= $this->input->post('mensagem');
		$idprojeto 		= $this->input->post('idprojeto', TRUE);
		$nova 			= $this->input->post('nova', TRUE);

		if($nova == '1'){
			$idmensagem_enviada = $this->mensagem_model->enviar_mensagem($from, $to, $assunto, $mensagem, $resposta_de, $idprojeto, $to);
		}
		$idmensagem = $this->mensagem_model->enviar_mensagem($from, $to, $assunto, $mensagem, $resposta_de, $idprojeto, $from);

		redirect('/mensagem/visualizar/'.$idmensagem);
	}

	function marcar_como_favorita(){
		$this->mensagem_model->marcar_como_favorita($this->input->post('idmensagem'), $this->input->post('booleano'));
	}

	function marcar_como_lixo($idmensagem = 0, $booleano = 0){
		$this->mensagem_model->marcar_como_lixo($idmensagem, $booleano);
	}

	function delete($idmensagem = 0, $idmensagem_principal = 0){
		$excluido = $this->mensagem_model->delete($idmensagem);
		
		if ($excluido){
			$this->session->set_flashdata('mensagem_sucesso', 'Mensagem excluida com sucesso.');
			redirect('/mensagem/visualizar/'.$idmensagem_principal);
		}
		$this->session->set_flashdata('mensagem_erro', 'Mensagem <strong>não</strong> excluida, tente novamente.');
		redirect('/mensagem/visualizar/'.$idmensagem_principal);
	}

	function get_projetos_value($idcliente, $idprojeto="") {
		$projetos = $this->etapa_model->get_projeto_cliente($idcliente)->result();
		$result = "";
		
		if(empty($projetos)){
			$result .= '<option value="0">Não há projetos em desenvolvimento deste cliente.</option>';
		}else{
			
			$result .= '<option value="">Selecione o Projeto</option>';
			
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
				
				$result .=  '<option value="'.$projeto->idprojeto .'" '. $selected .'>'.$projeto->nome.' - Prazo: '. $data .' '. $hora .' - Prioridade: '. $projeto->prioridade .'</option>';
			}
		}
		return $result;
	}
}