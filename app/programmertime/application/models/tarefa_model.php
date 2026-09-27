<?php  

class Tarefa_model extends CI_Model {

    function __construct() {
        parent::__construct();
    }

    function post($method = 'insert'){
		if($this->input->server('REQUEST_METHOD') == 'POST'){

			$dados = new stdClass();

			$dados->idprojeto					= (int) $this->input->post('idprojeto', TRUE);
			$dados->idfase						= id_ou_null($this->input->post('idfase', TRUE));
			$dados->nome						= $this->input->post('nome', TRUE);
			$dados->descricao					= $this->input->post('descricao');
			$dados->horas 						= (int) $this->input->post('horas', TRUE);
			$dados->data_prazo 					= fdata($this->input->post('data', TRUE), "-") . " 00:00:00";
			$dados->idusuario_responsavel		= id_ou_null($this->input->post('idusuario', TRUE));
			
			if($method == 'insert'){
				$dados->status	 				= 'nao_comecado';
				$dados->idusuario_cadastro		= $this->session->userdata('id');
				$dados->data_cadastro			= date('Y-m-d H:i:s');
			}

			elseif($method == 'update'){
				// do nothing 
			}

			return $dados;
		}
		return FALSE;
	}

	function insert($dados){
		return ($this->db->insert("projeto_tarefa", $dados)) ? $this->id = $this->db->insert_id() : 0;
	}

	function update($id, $dados){
		$id = (int) $id;
		if($id > 0){		
			$this->db->where('idtarefa', $id);
			return ($this->db->update('projeto_tarefa', $dados)) ? TRUE : FALSE;
		}
		return FALSE;
	}

	function delete($id){
		$id = (int) $id;
		if($id > 0)
			return $this->db->delete('projeto_tarefa', array('idtarefa' => $id)) ? TRUE : FALSE;
		
		return FALSE;
	}

	function status($id, $status){
		$id = (int) $id;
		if($id > 0){
		
			$tarefa = new stdClass();
			$tarefa->status = $status;
			
			$this->db->where('idtarefa', $id);
			if($this->db->update('projeto_tarefa', $tarefa))
				return TRUE;
			
			return FALSE;
		}
		return FALSE;
	}
	
	function get_tarefa($idtarefa){

		$idtarefa = (int) $idtarefa;

		$sql = "SELECT 	
					PT.*,
					U.nome as responsavel,
					U.imagem as responsavel_imagem,
					U.cor as responsavel_cor,
					UC.nome as cadastrado_por,
					P.nome as nomeprojeto,
					PF.fase as fase,
					C.idcliente as idcliente,
					C.nome as cliente_nome
				FROM 
					projeto_tarefa as PT
				LEFT JOIN 
					projeto as P ON PT.idprojeto = P.idprojeto
				LEFT JOIN 
					projeto_fase as PF ON PF.idfase = PT.idfase
				LEFT JOIN 
					usuario as U ON PT.idusuario_responsavel = U.idusuario
				LEFT JOIN 
					usuario as UC ON PT.idusuario_cadastro = UC.idusuario
				LEFT JOIN 
					cliente as C ON P.idcliente = C.idcliente
				
				WHERE
					PT.idtarefa = {$idtarefa}
				";
		
		$tarefa = $this->db->query($sql)->row();
		if(empty($tarefa)) return FALSE;

		$tarefa->horas_previstas = $tarefa->horas . ':00';
		$tarefa->hr_previstas = $tarefa->horas;

		$sql = "SELECT 
					PTE.*,
					U.nome as usuario
				FROM 
					projeto_tarefa_hora as PTE
				LEFT JOIN
					usuario as U ON PTE.idusuario = U.idusuario
				WHERE
					PTE.idtarefa = '{$tarefa->idtarefa}'

				ORDER BY data ASC, inicio ASC, idetapa ASC
		";

		$horas = $this->db->query($sql);

		$tarefa->qtd_etapas = $horas->num_rows();

		$total_horas = [];
		
		foreach($horas->result() as $etapa){
			$etapa->total_hora = calcular_horas_total($etapa->fim, $etapa->inicio);
			$total_horas[] = $etapa->total_hora;
		}

		$tarefa->horas = $horas->result();
		$tarefa->total_horas = somar_horas($total_horas);
		$tarefa->numero_etapas = $horas->num_rows();
		$tarefa->qtd_horas_faltando = '';
		$tarefa->porcentagem = '';

		return $tarefa;
	}

	function get_tarefas($idprojeto = 0, $idusuario = 0, $order = "ASC", $maximo = 0, $inicio = 0, $status = "", $resultado = 'resultado', $todos_status_menos = ""){
		$todos_status_menos = $this->db->escape_str((string) $todos_status_menos);


		$limit = "";
		$where = " WHERE PT.idtarefa > 0 ";

		$status = strsql($status);
		$order 	= (strtoupper((string) $order) == 'DESC') ? 'DESC' : 'ASC';
		$maximo = (int) $maximo;
		$inicio = (int) $inicio;

		$idprojeto 	= (int) $idprojeto;
		$idusuario 	= (int) $idusuario;

		if(!empty($idprojeto)) 	$where .= " AND PT.idprojeto = '{$idprojeto}' ";
		if(!empty($idusuario)) 	$where .= " AND PT.idusuario_responsavel = '{$idusuario}' ";
		if(!empty($status)) 	$where .= " AND PT.status = '{$status}' ";
		if(!empty($todos_status_menos)) $where .= " AND PT.status != '{$todos_status_menos}' ";

		if((!empty($inicio)) OR (!empty($maximo))) $limit = " LIMIT {$inicio}, {$maximo} ";
		
		$sql = "SELECT 	
					PT.*,
					U.nome as responsavel,
					U.imagem as responsavel_imagem,
					U.cor as responsavel_cor,
					P.nome as nomeprojeto
				FROM 
					projeto_tarefa as PT
				LEFT JOIN 
					projeto as P ON PT.idprojeto = P.idprojeto
				LEFT JOIN 
					usuario as U ON PT.idusuario_responsavel = U.idusuario
				
				{$where}
				
				ORDER BY 
					PT.data_cadastro {$order}, PT.idtarefa {$order}

				{$limit}
		";
		
		$tarefas = $this->db->query($sql);

		// echo '<pre>';
		// print_r($sql);
		// echo '</pre>';
		// die();

		if($resultado == 'numero')
			return $tarefas->num_rows();
		else
			$tarefas = $tarefas->result();

		foreach($tarefas as $tarefa){

			$sql = "SELECT 
						PTE.* 
					FROM 
						projeto_tarefa_hora as PTE
					WHERE
						PTE.idtarefa = '{$tarefa->idtarefa}'

					ORDER BY data DESC, inicio DESC, idetapa DESC
			";

			$horas = $this->db->query($sql);

			$tarefa->qtd_etapas = $horas->num_rows();

			$total_horas = [];
			
			foreach($horas->result() as $etapa){
				$total_horas[] = calcular_horas_total($etapa->fim, $etapa->inicio);
			}

			$tarefa->total_horas = somar_horas($total_horas);
			$tarefa->numero_etapas = $horas->num_rows();
			$tarefa->qtd_horas_faltando = '';
			$tarefa->porcentagem = '';

			unset($sql);
			unset($horas);
			unset($total_horas);
			unset($tarefa);

		}

		return $tarefas;
	}

}