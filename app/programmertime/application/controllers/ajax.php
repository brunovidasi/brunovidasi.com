<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * Inline edits (access-level flags, project status/priority, task status)
 * called from assets/js/atualizaAjax.js. Replaces the old standalone
 * assets/ajax/*.php scripts, which had no authentication. Running inside
 * CodeIgniter means the login hooks apply before these methods are reached.
 */
class Ajax extends CI_Controller {

	// table => id column, editable columns and the permission required to edit
	private $campos_permitidos = array(
		'projeto' => array(
			'idCampo'   => 'idprojeto',
			'nomeCampo' => array('status', 'prioridade'),
			'permissao' => 'edita_projeto',
		),
		'projeto_tarefa' => array(
			'idCampo'   => 'idtarefa',
			'nomeCampo' => array('status'),
			'permissao' => NULL,
		),
		'usuario_nivel_acesso' => array(
			'idCampo'   => 'id',
			'nomeCampo' => array(
				'cadastra_projeto', 'edita_projeto',
				'cadastra_cliente', 'edita_cliente',
				'lanca_etapa', 'lanca_pagamento',
				'envia_relatorio',
				'cadastra_usuario', 'edita_usuario',
			),
			'permissao' => 'edita_usuario',
		),
	);

	public function index(){
		show_404();
	}

	public function alterar_projeto(){
		$atualizado = $this->atualizar_campo($this->input->post('idprojeto'));
		$this->responder($atualizado);
	}

	public function alterar_nivel(){
		$idnivel = (int) $this->input->post('idnivel');

		if($this->input->post('tabela') != 'usuario_nivel_acesso')
			$this->responder(FALSE);

		$atualizado = $this->atualizar_campo($idnivel);

		// users of this level must reload their permissions
		if($atualizado){
			$this->db->where('nivel_acesso', $idnivel);
			$this->db->update('usuario', array('recarregar' => 'sim'));
		}

		$this->responder($atualizado);
	}

	private function atualizar_campo($idValor){
		if($this->input->server('REQUEST_METHOD') != 'POST') return FALSE;

		$tabela     = $this->input->post('tabela');
		$idCampo    = $this->input->post('idCampo');
		$nomeCampo  = $this->input->post('nomeCampo');
		$valorCampo = $this->input->post('valorCampo');

		if(!is_string($tabela) || !isset($this->campos_permitidos[$tabela])) return FALSE;
		$permitido = $this->campos_permitidos[$tabela];

		if($idCampo !== $permitido['idCampo']) return FALSE;
		if(!in_array($nomeCampo, $permitido['nomeCampo'], TRUE)) return FALSE;
		if(!is_string($valorCampo) || !preg_match('/^[a-z_]{1,50}$/', $valorCampo)) return FALSE;
		if(!is_numeric($idValor) || (int) $idValor <= 0) return FALSE;
		if(!empty($permitido['permissao']) && !$this->session->userdata($permitido['permissao'])) return FALSE;

		$this->db->where($idCampo, (int) $idValor);
		return $this->db->update($tabela, array($nomeCampo => $valorCampo)) ? TRUE : FALSE;
	}

	private function responder($ok){
		if(!$ok) $this->output->set_status_header(403);
		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode(array('ok' => (bool) $ok)));
		$this->output->_display();
		exit;
	}
}
