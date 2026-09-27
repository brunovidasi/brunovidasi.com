<?php

class Mensagem_model extends CI_Model {
	
    function __construct() {
        parent::__construct();
    }

	function enviar_mensagem($from, $to, $assunto, $msg, $resposta_de, $idprojeto, $idusuario){
		
		$mensagem = new stdClass();
		$mensagem->idusuario 		= $idusuario;
		$mensagem->id_usuario_from 	= $from;
		$mensagem->idusuario_to 	= id_ou_null($to);
		$mensagem->idprojeto		= id_ou_null($idprojeto);
		$mensagem->mensagem 		= $msg;
		$mensagem->assunto 			= $assunto;
		$mensagem->resposta_de 		= id_ou_null($resposta_de);
		$mensagem->data_envio		= date('Y-m-d H:i:s');
		$mensagem->rascunho 		= FALSE;
		$mensagem->favorito	 		= FALSE;
		$mensagem->lixo 	 		= FALSE;

		if($this->db->insert("mensagem", $mensagem)){
			return $this->db->insert_id();
		}
		return 0;
	}

	function get_mensagem($idmensagem){
		$idmensagem = (int) $idmensagem;

		$sql = "SELECT 
					m.*,
					u.nome,
					u.imagem,
					u.status,
					u.cor
				FROM 
					mensagem as m
				INNER JOIN
					usuario as u ON(m.id_usuario_from = u.idusuario)
				WHERE 
					m.idmensagem = {$idmensagem}
				";
		
		$query = $this->db->query($sql);
		return $query;
	}

	function get_mensagens_associadas($idmensagem){
		$idmensagem = (int) $idmensagem;

		$idmensagem = (int) $idmensagem;
		$idusuario = $this->session->userdata('id');

		$sql = "SELECT 
					m.*,
					u.nome,
					u.imagem,
					u.status,
					u.cor
				FROM 
					mensagem as m
				INNER JOIN
					usuario as u ON(m.id_usuario_from = u.idusuario)
				WHERE 
					m.resposta_de = '{$idmensagem}'
				AND
					m.idusuario = '{$idusuario}'
				ORDER BY 
					m.data_envio ASC, m.idmensagem ASC
				";
		
		return $this->db->query($sql);
	}
	
	function get_mensagens(){
		$idusuario = $this->session->userdata('id');

		$sql = "SELECT 
					m.*,
					u.nome,
					u.imagem,
					u.status,
					u.cor
				FROM 
					mensagem as m
				INNER JOIN
					usuario as u ON(m.id_usuario_from = u.idusuario)
				WHERE 
					m.idusuario = '{$idusuario}'
				AND
					m.id_usuario_from != '{$idusuario}'
				ORDER BY 
					m.data_envio DESC, m.idmensagem DESC
				";
		
		return $this->db->query($sql);
	}

	function get_mensagens_nao_lidas(){
		$idusuario = $this->session->userdata('id');

		$sql = "SELECT 
					m.*,
					u.nome,
					u.imagem,
					u.status,
					u.cor
				FROM 
					mensagem as m
				INNER JOIN
					usuario as u ON(m.id_usuario_from = u.idusuario)
				WHERE 
					m.idusuario = '{$idusuario}'
				AND
					m.lida = false
				ORDER BY 
					m.data_envio DESC, m.idmensagem DESC
				";
		
		return $this->db->query($sql);
	}

	function get_mensagens_lixeira(){
		$idusuario = $this->session->userdata('id');

		$sql = "SELECT 
					m.*,
					u.nome,
					u.imagem,
					u.status,
					u.cor
				FROM 
					mensagem as m
				INNER JOIN
					usuario as u ON(m.id_usuario_from = u.idusuario)
				WHERE 
					m.idusuario = '{$idusuario}'
				AND
					m.lixo = true
				ORDER BY 
					m.data_envio DESC, m.idmensagem DESC
				";
		
		return $this->db->query($sql);
	}

	function get_mensagens_rascunho(){
		$idusuario = $this->session->userdata('id');

		$sql = "SELECT 
					m.*,
					u.nome,
					u.imagem,
					u.status,
					u.cor
				FROM 
					mensagem as m
				INNER JOIN
					usuario as u ON(m.id_usuario_from = u.idusuario)
				WHERE 
					m.idusuario = '{$idusuario}'
				AND
					m.rascunho = true
				ORDER BY 
					m.data_envio DESC, m.idmensagem DESC
				";
		
		return $this->db->query($sql);
	}

	function marcar_como_lida($idmensagem, $booleano){
		$idmensagem = (int) $idmensagem;
		$booleano = filter_var($booleano, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

		$sql = "UPDATE
					mensagem
				SET 
					lida = {$booleano}
				WHERE
					idmensagem = {$idmensagem}
				";
		
		return $this->db->query($sql);
	}

	function marcar_como_favorita($idmensagem, $booleano){
		$idmensagem = (int) $idmensagem;
		$booleano = filter_var($booleano, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

		$sql = "UPDATE
					mensagem
				SET 
					favorito = {$booleano}
				WHERE
					idmensagem = {$idmensagem}
				";
		
		return $this->db->query($sql);
	}

	function marcar_como_rascunho($idmensagem, $booleano){
		$idmensagem = (int) $idmensagem;
		$booleano = filter_var($booleano, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

		$sql = "UPDATE
					mensagem
				SET 
					rascunho = {$booleano}
				WHERE
					idmensagem = {$idmensagem}
				";
		
		return $this->db->query($sql);
	}

	function marcar_como_lixo($idmensagem, $booleano){
		$idmensagem = (int) $idmensagem;
		$booleano = filter_var($booleano, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

		$sql = "UPDATE
					mensagem
				SET 
					lixo = {$booleano}
				WHERE
					idmensagem = {$idmensagem}
				";
		
		return $this->db->query($sql);
	}

	function delete($id){
		$id = (int) $id;
        if ($id > 0)
			return $this->db->delete('mensagem', array('idmensagem' => $id)) ? TRUE : FALSE;
        else
            return FALSE;
	}

	
}