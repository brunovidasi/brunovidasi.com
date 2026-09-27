<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class upload extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function upload_imagem($origem = "assets.images.temp." , $destino = "assets.images.", $altura = "200", $largura = "200") {
        
        $dados = array();
        $dados["parms"] = new stdClass();
        $dados["parms"]->origem = $origem;
        $dados["parms"]->destino = $destino;
        $dados["parms"]->altura = $altura;
        $dados["parms"]->largura = $largura;
		 
        $this->load->view('upload/upload_imagem', $dados);
    }

    public function salva_upload() {

        $dados = new stdClass();
		
        $dados->origem     = $this->input->post('origem');
        $dados->destino    = $this->input->post('destino');
        $dados->altura     = $this->input->post('altura');
        $dados->largura    = $this->input->post('largura');
		
        $file_types_permitidos = array("image/gif", "image/jpeg", "image/pjpeg", "image/png", "image/x-png");

        if (isset($_FILES["foto"]["type"]) && in_array($_FILES["foto"]["type"], $file_types_permitidos)) {
			
            $origem_uso     = str_replace(".", "/", $dados->origem);
            $destino_uso    = str_replace(".", "/", $dados->destino);

            $config = array('upload_path' => "./{$origem_uso}", 'allowed_types' => 'gif|jpg|jpeg|png|bmp', 'max_size' => '4096', 'encrypt_name' => 'true');
            $this->upload->initialize($config);


            if($this->upload->do_upload('foto')){
                $dados->upload = $this->upload->data();

                setcookie('ptime_img_crop', json_encode($dados), time()+5, '/');
                redirect('/upload/crop/');
            }
            $this->session->set_flashdata('msg_controller_erro', lang('msg_upload_erro'));
            redirect("/upload/upload_imagem/" . $dados->origem . "/" . $dados->destino . "/" . $dados->altura . "/" . $dados->largura);
        }
        $this->session->set_flashdata('msg_controller_erro', lang('msg_upload_erro_tipo_imagem'));
        redirect("/upload/upload_imagem/" . $dados->origem . "/" . $dados->destino . "/" . $dados->altura . "/" . $dados->largura);
    }

    public function crop(){

        $imagem = isset($_COOKIE["ptime_img_crop"]) ? json_decode($_COOKIE["ptime_img_crop"]) : NULL;

        if(!is_object($imagem) || empty($imagem->upload)){
            $this->session->set_flashdata('msg_controller_erro', lang('msg_upload_erro'));
            redirect("/upload/upload_imagem/");
        }

        $imagem->upload = (array) $imagem->upload;
        $imagem->altura = max(1, (int) $imagem->altura);
        $imagem->largura = max(1, (int) $imagem->largura);

        $this->load->library('image_lib');

        $dados = array();
		$dados["parms"] = new stdClass();
        $dados["parms"]->origem     = $imagem->origem;
        $dados["parms"]->destino    = $imagem->destino;
        $dados["parms"]->altura     = $imagem->altura;
        $dados["parms"]->largura    = $imagem->largura;
        
        $origem_uso  = str_replace(".", "/", $imagem->origem);
        $destino_uso = str_replace(".", "/", $imagem->destino);
		
        $dados["nome_arquivo_original"] = $imagem->upload['orig_name'];
        $dados["nome_arquivo"] = $imagem->upload['file_name'];

        $dir_temp = "./$origem_uso";
        $dir_imag = "./$destino_uso";
		
        list($width, $height, $type, $attr) = getimagesize($dir_temp . $imagem->upload['file_name']);
		
        $x = "1000";
        $y = "500";
		
        $xt = $imagem->largura;
        $yt = $imagem->altura;
		
        $raito = $xt / $yt;
        $proporcao = 1;
        $Alarg = $width;
        $Aalt = $height;
        $propa = 1;
		
        $Lalt = $height;
        $Llarg = $width;
        $propw = 1;

        if($width > 700){
            $propw  = $width / 700;
            $Llarg  = 700;
            $Lalt   = $height / $propw;
        }

        if(($Llarg <= 700) and ($Lalt <= 400)){

            $proporcao  = $propw;
            $width      = $Llarg;
            $height     = $Lalt;

        }else{

            if($height > 400){
                $propa  = $height / 400;
                $Aalt   = 400;
                $Alarg  = $width / $propa;
            }

            if(($Alarg <= 700) and ($Aalt <= 400)){
                $proporcao  = $propa;
                $width      = $Alarg;
                $height     = $Aalt;
            }
        }
		
        $dados["medidas"] = new stdClass();

        $dados["medidas"]->tam_h        = $yt;
        $dados["medidas"]->tam_w        = $xt;
        $dados["medidas"]->raito        = $raito;
        $dados["medidas"]->imgL         = $width;
        $dados["medidas"]->imgA         = $height;
        $dados["medidas"]->proporcao    = $proporcao;
        $dados["medidas"]->tipo_imagem  = $type;
		
        $this->load->view('upload/crop', $dados);
    }

    public function upload_crop(){
		
        $imagem = new stdClass();

        $imagem->nome_arquivo   = $this->input->post('nome_arquivo');
        $imagem->nome_original  = $this->input->post('nome_original');
        $imagem->origem         = $this->input->post('origem');
        $imagem->destino        = $this->input->post('destino');
        $imagem->altura         = $this->input->post('altura');
        $imagem->largura        = $this->input->post('largura');

        $imagem->w  = $this->input->post('w');
        $imagem->ax = $this->input->post('ax');
        $imagem->h  = $this->input->post('h');
        $imagem->ay = $this->input->post('ay');
		
        if(!empty($imagem->nome_arquivo)){
		
            $origem_uso     = str_replace(".", "/", $imagem->origem);
            $destino_uso    = str_replace(".", "/", $imagem->destino);
			
            $xt = max(1, (int) $imagem->largura);
            $yt = max(1, (int) $imagem->altura);
			
            $dir_temp = "./{$origem_uso}";
            $dir_imag = "./{$destino_uso}";

            $imagem->tipo = (float) str_replace(",", ".", (string) $this->input->post('tipo'));
            if($imagem->tipo <= 0) $imagem->tipo = 1;
			
            // Empty/non-numeric strings in arithmetic throw a TypeError in PHP 8
            $ww     = intval((float) $imagem->w * $imagem->tipo);
            $aax    = intval((float) $imagem->ax * $imagem->tipo);
            $hh     = intval((float) $imagem->h * $imagem->tipo);
            $aay    = intval((float) $imagem->ay * $imagem->tipo);
			
            $tipo_imagem = $this->input->post('tipo_imagem');

            if($tipo_imagem == "2"){
			
                $jpeg_quality = 100;
                $img_r = imagecreatefromjpeg($dir_temp . $imagem->nome_arquivo);
                $dst_r = imagecreatetruecolor($xt, $yt);
                $nomeArqP = "&_" . $imagem->nome_arquivo;
                imagecopyresampled($dst_r, $img_r, 0, 0, $aax, $aay, $xt, $yt, $ww, $hh);
				
                if(imagejpeg($dst_r, $dir_temp . $nomeArqP, $jpeg_quality)){
                    chmod($dir_temp . $nomeArqP, 0777);
                }else{
                    $this->session->set_flashdata('msg_controller_erro', lang('msg_upload_erro_crop'));
                    redirect("/upload/upload_imagem/" . $imagem->origem . "/" . $imagem->destino . "/" . $imagem->altura . "/" . $imagem->largura);
                }

            }elseif($tipo_imagem == "1"){
			
                $jpeg_quality = 100;
                $img_r = imagecreatefromgif($dir_temp . $imagem->nome_arquivo);
                $dst_r = imagecreatetruecolor($xt, $yt);
                $nomeArqP = "&_" . $imagem->nome_arquivo;
                imagecopyresampled($dst_r, $img_r, 0, 0, $aax, $aay, $xt, $yt, $ww, $hh);
				
                if(imagegif($dst_r, $dir_temp . $nomeArqP)){
                    chmod($dir_temp . $nomeArqP, 0777);
                }else{
                    $this->session->set_flashdata('msg_controller_erro', lang('msg_upload_erro_crop'));
                    redirect("/upload/upload_imagem/" . $imagem->origem . "/" . $imagem->destino . "/" . $imagem->altura . "/" . $imagem->largura);
                }

            }elseif($tipo_imagem == "3"){
			
                $jpeg_quality = 100;
                $img_r = imagecreatefrompng($dir_temp . $imagem->nome_arquivo);
                $dst_r = imagecreatetruecolor($xt, $yt);
                $nomeArqP = "&_" . $imagem->nome_arquivo;
                imagecopyresampled($dst_r, $img_r, 0, 0, $aax, $aay, $xt, $yt, $ww, $hh);
				
                if(imagepng($dst_r, $dir_temp . $nomeArqP)){
                    chmod($dir_temp . $nomeArqP, 0777);
                }else{
                    $this->session->set_flashdata('msg_controller_erro', lang('msg_upload_erro_crop'));
                    redirect("/upload/upload_imagem/" . $imagem->origem . "/" . $imagem->destino . "/" . $imagem->altura . "/" . $imagem->largura);
                }
            }
			
            $caminho_image = base_url("$origem_uso$nomeArqP");
			
            $caminho_temp = $dir_temp . $imagem->nome_arquivo;
            @unlink($caminho_temp);

			echo "<script> console.log(parent); parent.insereimagem('$caminho_image', '$nomeArqP','$imagem->nome_original'); </script>\n";
			echo "<script>  parent.$('.close').trigger('click'); </script>";

			echo '<link href="'. base_url("assets/css/bootstrap.css") .'" rel="stylesheet" />
			<img src="'. $caminho_image .'" /><br><br>
			<a href="'. base_url("upload/upload_imagem/". $this->session->userdata('config_imag')) .'" class="btn btn-primary">'.lang('msg_trocar_imagem').'</a>';

            exit();

        }else{
            $this->session->set_flashdata('msg_controller_erro', lang('msg_upload_erro'));
            redirect("/upload/upload_imagem/" . $imagem->origem . "/" . $imagem->destino . "/" . $imagem->altura . "/" . $imagem->largura);
        }
    }

    private function salva_imagem($dir_imag, $dir_temp, $nome_arquivo){
		
        $config = array('source_image' => $dir_temp . $nome_arquivo, 'new_image' => $dir_imag);
		$this->image_lib->clear();
        $this->image_lib->initialize($config);
        $this->image_lib->resize();

        $caminho_temp = $dir_temp . $nome_arquivo;
        @unlink($caminho_temp);
    }

    public function valida(){
        $this->form_validation->set_message('valida_check', lang('msg_upload_erro'));
        return false;
    }

}