<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dashboard extends CI_Controller {

	public function index(){
		$dados["etapas"] = $this->etapa_model->get_etapas_relatorio("", "DESC");
		$dados["num_tarefas"] = $this->tarefa_model->get_tarefas(0, $this->session->userdata('id'), "ASC", 0, 0, "", 'numero', "concluido");
		$dados["tarefas"] = $this->tarefa_model->get_tarefas(0, $this->session->userdata('id'), "ASC", 0, 0, "", 'resultado', "concluido");
		$dados['minhas_etapas'] = $this->usuario_model->get_etapas($this->session->userdata('id'));
		$dados['horas_trabalhadas'] = $this->usuario_model->get_horas_trabalhadas($this->session->userdata('id'));
		$dados["etapa_aberta"] = $this->etapa_model->etapa_aberta($this->session->userdata('id'));
		$dados["projetos"] = $this->projeto_model->get_projetos();
		$dados["projetos_envolvidos"] = $this->usuario_model->get_projetos_envolvido($this->session->userdata('id'));
		$dados["mensagens"] = $this->mensagem_model->get_mensagens_nao_lidas()->result();

		$dados["logou"] = $this->session->flashdata("logou");
		
		$dados['view'] = $this->load->view('dashboard/timeline', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}

	public function blank(){
		$dados['view'] = "";
		$this->load->view('includes/interna', $dados);
	}
	
	public function calendario(){
		$dados["etapas"] = $this->etapa_model->get_etapas_relatorio("", "DESC");
		$dados["projetos"] = $this->projeto_model->get_projetos();
		
		$dados['view'] = $this->load->view('dashboard/calendario', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	public function mensagem(){
		$dados['view'] = $this->load->view('includes/mensagem', array(), TRUE);
		$this->load->view('includes/interna', $dados);
	}
	
	public function teste(){
		$imagens = $this->usuario_model->get_imagens();
		$dados['imagens'] = $imagens;
		$dados['view'] = $this->load->view('dashboard/teste', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
	}

	public function apresentacao(){

		$this->load->view('dashboard/apresentacao');

	}
	
	public function sem_acesso(){
		$this->session->set_flashdata('mensagem_atencao', lang('msg_sem_acesso'));
		redirect('dashboard/mensagem');
	}
	
	public function home(){
		$this->index();
	}
	
	public function interna(){
        $this->load->view('includes/interna');
    }	

    public function sobre(){
    	$info = $this->acesso_model->get_info()->row(); 

		$versao = explode('.', $info->versao);
		$data_lancamento = explode(" ", fdatetime($info->data_lancamento, "/"));

		$dados['versao'] = $versao[0].'.'.$versao[1];
		$dados['navegador'] = $this->session->userdata('navegador')->browser;
		$dados['data_lancamento'] = $data_lancamento[0];
		$dados['info'] = $info;

        $dados['view'] = $this->load->view('dashboard/sobre', $dados, TRUE);
		$this->load->view('includes/interna', $dados);
    }	
}