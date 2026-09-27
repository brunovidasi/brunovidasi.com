<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class upload extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function upload_image($source = "assets.images.temp." , $destination = "assets.images.", $height = "200", $width = "200") {
        
        $data = array();
        $data["parms"] = new stdClass();
        $data["parms"]->source = $source;
        $data["parms"]->destination = $destination;
        $data["parms"]->height = $height;
        $data["parms"]->width = $width;
		 
        $this->load->view('upload/upload_image', $data);
    }

    public function save_upload() {

        $data = new stdClass();
		
        $data->source     = $this->input->post('source');
        $data->destination    = $this->input->post('destination');
        $data->height     = $this->input->post('height');
        $data->width    = $this->input->post('width');
		
        $file_types_allowed = array("image/gif", "image/jpeg", "image/pjpeg", "image/png", "image/x-png");

        if (isset($_FILES["photo"]["type"]) && in_array($_FILES["photo"]["type"], $file_types_allowed)) {
			
            $source_use     = str_replace(".", "/", $data->source);
            $destination_use    = str_replace(".", "/", $data->destination);

            $config = array('upload_path' => "./{$source_use}", 'allowed_types' => 'gif|jpg|jpeg|png|bmp', 'max_size' => '4096', 'encrypt_name' => 'true');
            $this->upload->initialize($config);


            if($this->upload->do_upload('photo')){
                $data->upload = $this->upload->data();

                setcookie('ptime_img_crop', json_encode($data), time()+5, '/');
                redirect('/upload/crop/');
            }
            $this->session->set_flashdata('msg_controller_error', lang('msg_upload_error'));
            redirect("/upload/upload_image/" . $data->source . "/" . $data->destination . "/" . $data->height . "/" . $data->width);
        }
        $this->session->set_flashdata('msg_controller_error', lang('msg_upload_error_type_image'));
        redirect("/upload/upload_image/" . $data->source . "/" . $data->destination . "/" . $data->height . "/" . $data->width);
    }

    public function crop(){

        $image = isset($_COOKIE["ptime_img_crop"]) ? json_decode($_COOKIE["ptime_img_crop"]) : NULL;

        if(!is_object($image) || empty($image->upload)){
            $this->session->set_flashdata('msg_controller_error', lang('msg_upload_error'));
            redirect("/upload/upload_image/");
        }

        $image->upload = (array) $image->upload;
        $image->height = max(1, (int) $image->height);
        $image->width = max(1, (int) $image->width);

        $this->load->library('image_lib');

        $data = array();
		$data["parms"] = new stdClass();
        $data["parms"]->source     = $image->source;
        $data["parms"]->destination    = $image->destination;
        $data["parms"]->height     = $image->height;
        $data["parms"]->width    = $image->width;
        
        $source_use  = str_replace(".", "/", $image->source);
        $destination_use = str_replace(".", "/", $image->destination);
		
        $data["original_file_name"] = $image->upload['orig_name'];
        $data["file_name"] = $image->upload['file_name'];

        $dir_temp = "./$source_use";
        $dir_imag = "./$destination_use";
		
        list($width, $height, $type, $attr) = getimagesize($dir_temp . $image->upload['file_name']);
		
        $x = "1000";
        $y = "500";
		
        $xt = $image->width;
        $yt = $image->height;
		
        $aspect_ratio = $xt / $yt;
        $scale = 1;
        $hfit_width = $width;
        $hfit_height = $height;
        $height_scale = 1;
		
        $wfit_height = $height;
        $wfit_width = $width;
        $width_scale = 1;

        if($width > 700){
            $width_scale  = $width / 700;
            $wfit_width  = 700;
            $wfit_height   = $height / $width_scale;
        }

        if(($wfit_width <= 700) and ($wfit_height <= 400)){

            $scale  = $width_scale;
            $width      = $wfit_width;
            $height     = $wfit_height;

        }else{

            if($height > 400){
                $height_scale  = $height / 400;
                $hfit_height   = 400;
                $hfit_width  = $width / $height_scale;
            }

            if(($hfit_width <= 700) and ($hfit_height <= 400)){
                $scale  = $height_scale;
                $width      = $hfit_width;
                $height     = $hfit_height;
            }
        }
		
        $data["dimensions"] = new stdClass();

        $data["dimensions"]->target_h        = $yt;
        $data["dimensions"]->target_w        = $xt;
        $data["dimensions"]->aspect_ratio        = $aspect_ratio;
        $data["dimensions"]->imgW         = $width;
        $data["dimensions"]->imgH         = $height;
        $data["dimensions"]->scale    = $scale;
        $data["dimensions"]->image_type  = $type;
		
        $this->load->view('upload/crop', $data);
    }

    public function upload_crop(){
		
        $image = new stdClass();

        $image->file_name   = $this->input->post('file_name');
        $image->original_name  = $this->input->post('original_name');
        $image->source         = $this->input->post('source');
        $image->destination        = $this->input->post('destination');
        $image->height         = $this->input->post('height');
        $image->width        = $this->input->post('width');

        $image->w  = $this->input->post('w');
        $image->ax = $this->input->post('ax');
        $image->h  = $this->input->post('h');
        $image->ay = $this->input->post('ay');
		
        if(!empty($image->file_name)){
		
            $source_use     = str_replace(".", "/", $image->source);
            $destination_use    = str_replace(".", "/", $image->destination);
			
            $xt = max(1, (int) $image->width);
            $yt = max(1, (int) $image->height);
			
            $dir_temp = "./{$source_use}";
            $dir_imag = "./{$destination_use}";

            $image->scale = (float) str_replace(",", ".", (string) $this->input->post('scale'));
            if($image->scale <= 0) $image->scale = 1;
			
            // Empty/non-numeric strings in arithmetic throw a TypeError in PHP 8
            $ww     = intval((float) $image->w * $image->scale);
            $aax    = intval((float) $image->ax * $image->scale);
            $hh     = intval((float) $image->h * $image->scale);
            $aay    = intval((float) $image->ay * $image->scale);
			
            $image_type = $this->input->post('image_type');

            if($image_type == "2"){
			
                $jpeg_quality = 100;
                $img_r = imagecreatefromjpeg($dir_temp . $image->file_name);
                $dst_r = imagecreatetruecolor($xt, $yt);
                $croppedName = "&_" . $image->file_name;
                imagecopyresampled($dst_r, $img_r, 0, 0, $aax, $aay, $xt, $yt, $ww, $hh);
				
                if(imagejpeg($dst_r, $dir_temp . $croppedName, $jpeg_quality)){
                    chmod($dir_temp . $croppedName, 0777);
                }else{
                    $this->session->set_flashdata('msg_controller_error', lang('msg_upload_error_crop'));
                    redirect("/upload/upload_image/" . $image->source . "/" . $image->destination . "/" . $image->height . "/" . $image->width);
                }

            }elseif($image_type == "1"){
			
                $jpeg_quality = 100;
                $img_r = imagecreatefromgif($dir_temp . $image->file_name);
                $dst_r = imagecreatetruecolor($xt, $yt);
                $croppedName = "&_" . $image->file_name;
                imagecopyresampled($dst_r, $img_r, 0, 0, $aax, $aay, $xt, $yt, $ww, $hh);
				
                if(imagegif($dst_r, $dir_temp . $croppedName)){
                    chmod($dir_temp . $croppedName, 0777);
                }else{
                    $this->session->set_flashdata('msg_controller_error', lang('msg_upload_error_crop'));
                    redirect("/upload/upload_image/" . $image->source . "/" . $image->destination . "/" . $image->height . "/" . $image->width);
                }

            }elseif($image_type == "3"){
			
                $jpeg_quality = 100;
                $img_r = imagecreatefrompng($dir_temp . $image->file_name);
                $dst_r = imagecreatetruecolor($xt, $yt);
                $croppedName = "&_" . $image->file_name;
                imagecopyresampled($dst_r, $img_r, 0, 0, $aax, $aay, $xt, $yt, $ww, $hh);
				
                if(imagepng($dst_r, $dir_temp . $croppedName)){
                    chmod($dir_temp . $croppedName, 0777);
                }else{
                    $this->session->set_flashdata('msg_controller_error', lang('msg_upload_error_crop'));
                    redirect("/upload/upload_image/" . $image->source . "/" . $image->destination . "/" . $image->height . "/" . $image->width);
                }
            }
			
            $path_image = base_url("$source_use$croppedName");
			
            $path_temp = $dir_temp . $image->file_name;
            @unlink($path_temp);

			echo "<script> console.log(parent); parent.insertImage('$path_image', '$croppedName','$image->original_name'); </script>\n";
			echo "<script>  parent.$('.close').trigger('click'); </script>";

			echo '<link href="'. base_url("assets/css/bootstrap.css") .'" rel="stylesheet" />
			<img src="'. $path_image .'" /><br><br>
			<a href="'. base_url("upload/upload_image/". $this->session->userdata('image_config')) .'" class="btn btn-primary">'.lang('msg_change_image').'</a>';

            exit();

        }else{
            $this->session->set_flashdata('msg_controller_error', lang('msg_upload_error'));
            redirect("/upload/upload_image/" . $image->source . "/" . $image->destination . "/" . $image->height . "/" . $image->width);
        }
    }

    private function save_image($dir_imag, $dir_temp, $file_name){
		
        $config = array('source_image' => $dir_temp . $file_name, 'new_image' => $dir_imag);
		$this->image_lib->clear();
        $this->image_lib->initialize($config);
        $this->image_lib->resize();

        $path_temp = $dir_temp . $file_name;
        @unlink($path_temp);
    }

    public function validate(){
        $this->form_validation->set_message('validate_check', lang('msg_upload_error'));
        return false;
    }

}