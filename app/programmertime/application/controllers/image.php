<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Image extends CI_Controller {
	
    public function __construct() {
        parent::__construct();
    }
	
	public function index() {
        $this->view();
    }
	
	public function view($image_id = 0){
		$image = $this->image_model->get_image($image_id);
		
		if($image->num_rows != 1){
			$this->session->set_flashdata('message_warning', lang('msg_image_not_found'));
			redirect('/project/list/');
		}
		
		$image_path = 'assets/images/projects/';
		$comments = $this->image_model->get_comments($image_id);
		
		$data['image'] = $image->row();
		$data['comments'] = $comments;
		$data['image_path'] = $image_path;
		
		$data['view'] = $this->load->view('image/view', $data, TRUE);
		$this->load->view('includes/internal', $data);

	}
	
	public function create(){
		$image_crop = new stdClass();
		$image_crop->source 	= "assets.images.temp.";
		$image_crop->destination	= "assets.images.projects";
		$image_crop->width 	= "1000";
		$image_crop->height 	= "1000";
		$data['image_crop'] 	= $image_crop;
		
		#$data["post_images"] = $this->image_model->post_images();
		
		$data['view'] = $this->load->view('image/create', $data, TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	public function edit(){
		$data['view'] = $this->load->view('image/edit', array(), TRUE);
		$this->load->view('includes/internal', $data);
	}
	
	public function insert_image(){
		 if ($this->validate_image_form()){
		
            $this->image_model->post_image();
			$insert_id = $this->image_model->insert_image();
			
            if ($insert_id > 0){
				$this->session->set_flashdata('message_success', lang('msg_image_create_success'));
                redirect('/image/view/'. $insert_id);
            }
			$this->session->set_flashdata('message_error', lang('msg_image_create_error'));
			redirect('/image/create/');
        }
		$this->view();
	}
	
	public function insert_comment(){
		 if ($this->validate_comment_form()){
		
            $this->image_model->post_comment();
			$insert_id = $this->image_model->insert_comment();
			
            if ($insert_id > 0){
				$this->session->set_flashdata('message_success', lang('msg_comment_create_success'));
                redirect('/image/view/'. $this->input->post('image_id'));
            }
			$this->session->set_flashdata('message_error', lang('msg_comment_create_error'));
			redirect('/image/view/'. $this->input->post('image_id'));
			
        }
		$this->view();
	}
	
	public function update_image($image_id = 0){
        if (!empty($image_id)) {
			if ($this->validate_image_form()){
				
				$this->image_model->post_image();	
			    $updated = $this->image_model->update_image($image_id);				
				
				if ($updated) {
				    $this->session->set_flashdata('message_success', lang('msg_image_edit_success'));
					redirect('/image/view/'.$image_id);
				}
				$this->session->set_flashdata('message_error', lang('msg_image_edit_error'));
				redirect('/image/view/'.$image_id);
			}
			$this->edit($image_id);
	    }
	}
	
	public function delete_image($project_id = 0, $image_id = 0){
		$deleted = $this->image_model->delete_image($image_id);
		
		if ($deleted){
			$this->session->set_flashdata('message_success', lang('msg_image_delete_success'));
			redirect('/project/view/'. $project_id);
		}
		$this->session->set_flashdata('message_error', lang('msg_image_delete_error'));
		redirect('/image/view/'. $image_id);
	}
	
	public function delete_comment($image_id = 0, $comment_id = 0){
		$deleted = $this->image_model->delete_comment($comment_id);
		
		if ($deleted){
			$this->session->set_flashdata('message_success', lang('msg_comment_delete_success'));
			redirect('/image/view/'. $image_id);
		}
		$this->session->set_flashdata('message_error', lang('msg_comment_delete_error'));
		redirect('/image/view/'. $image_id);	
	}
	
	private function validate_image_form(){
		$this->form_validation->set_rules('title', lang('lbl_title'), 'trim|required');
		$this->form_validation->set_rules('description', lang('lbl_description'), 'trim|required');
		
		return $this->form_validation->run();
	}
	
	private function validate_comment_form(){
		$this->form_validation->set_rules('image_id', lang('lbl_image'), 'trim|required');
		$this->form_validation->set_rules('comment', lang('lbl_comment'), 'trim|required');
		
		return $this->form_validation->run();
	}
}