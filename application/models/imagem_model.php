<?php

class Imagem_model extends CI_Model {
	
    function __construct() {
        parent::__construct();
    }
	
	function post_imagem(){
		if ($this->input->server('REQUEST_METHOD') == 'POST') {
			$this->titulo	 	= $this->input->post('titulo', TRUE);
			$this->legenda	 	= $this->input->post('descricao', TRUE);
			$this->idprojeto 	= $this->input->post('idprojeto', TRUE);
			$this->imagem	 	= $this->input->post('imagem_nome', TRUE);
			$this->idusuario 	= $this->session->userdata('id');
			$this->data		 	= date('Y-m-d H:i:s');
		}
	}
	
	function post_comentario(){
		if ($this->input->server('REQUEST_METHOD') == 'POST') {
			$this->idimagem		 = $this->input->post('idimagem', TRUE);
			$this->idusuario	 = $this->session->userdata('id');
			$this->comentario	 = $this->input->post('comentario', TRUE);
			$this->data			 = date('Y-m-d H:i:s');
		}
	}
	
	function inserir_imagem(){
		if ($this->db->insert("projeto_imagem", $this)){
		    return $this->db->insert_id();
		}
        return 0;
	}
	
	function inserir_comentario(){
		if ($this->db->insert("projeto_imagem_comentario", $this)){
		    return $this->db->insert_id();
		}
        return 0;
	}
	
	function atualizar_imagem($id){
        $id = (int) $id;
        if ($id > 0){
            $this->db->where('idimagem', $id);
			if($this->db->update('projeto_imagem', $this)){
				return TRUE;
			}
			return FALSE;
        }
        return FALSE;
	}
	
	function deletar_imagem($id){
		$id = (int) $id;
        if ($id > 0) {
			return $this->db->delete('projeto_imagem', array('idimagem' => $id)) ? TRUE : FALSE ; 
        }
        return FALSE;
	}
	
	function deletar_comentario($id){
		$id = (int) $id;
        if ($id > 0) {
			return $this->db->delete('projeto_imagem_comentario', array('idcomentario' => $id)) ? TRUE : FALSE ; 
        }
        return FALSE;
	}
	
	function get_imagem($id){
		$id = (int) $id;

		$sql = "SELECT 	
					I.*,
					P.nome as nome_projeto,
					U.nome as nome_usuario
				FROM 
					projeto_imagem as I 
				LEFT JOIN 
					projeto as P ON P.idprojeto = I.idprojeto
				LEFT JOIN 
					usuario as U ON U.idusuario = I.idusuario
				WHERE 
					I.idimagem = '{$id}' 
				LIMIT 1";
		
		return $this->db->query($sql);
	}
	
	function get_imagens($idprojeto){
		$idprojeto = (int) $idprojeto;

		$sql = "SELECT * FROM projeto_imagem WHERE idprojeto = '{$idprojeto}' ORDER BY data DESC, idimagem DESC";
		
		return $this->db->query($sql);
	}
	
	function get_comentarios($idimagem){
		$idimagem = (int) $idimagem;

		$sql = "SELECT 	
					C.*, 
					U.nome as nome, 
					U.imagem as imagem_usuario,
					U.cor as cor_usuario
				FROM 
					projeto_imagem_comentario as C
				LEFT JOIN 
					usuario as U ON U.idusuario = C.idusuario
				WHERE 
					C.idimagem = '{$idimagem}'
				ORDER BY 
					C.data DESC";
		
		return $this->db->query($sql);
	}
	
	function salva_imagens($idsalvo = 0){
		if(!empty($idsalvo)){
			$imagens = $this->post_imagens();
			$this->db->delete('projeto_imagem', array('idprojeto' => $idsalvo));
			
			$imagens_salvas = array();
			foreach($imagens as $imagem){
				if(strpos($imagem->caminho, "assets/upload/temp/") !== false){
					$config = array('source_image' => "assets/upload/temp/" . $imagem->imagem, 'new_image' => "assets/imagens/projeto/");
					$this->image_lib->clear();
					$this->image_lib->initialize($config);
					$this->image_lib->resize();
				}
				
				$objeto = new stdClass();
				$objeto->idprojeto = $idsalvo;
				$objeto->nome = $imagem->imagem;
				$imagens_salvas[] = $this->db->insert("projeto_imagem", $objeto) ? TRUE : FALSE;
			}
		}
	}
	
	function retorna_imagens($idprojeto){
		$idprojeto = (int) $idprojeto;

		$this->db->select('*');
		$this->db->from('projeto_imagem');
		$this->db->where("idprojeto", $idprojeto);	
		return $this->db->get()->result();
	}	
	
}