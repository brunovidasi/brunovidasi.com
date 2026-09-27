<?php

class Image_model extends CI_Model {
	
    function __construct() {
        parent::__construct();
    }
	
	function post_image(){
		if ($this->input->server('REQUEST_METHOD') == 'POST') {
			$this->title	 	= $this->input->post('title', TRUE);
			$this->caption	 	= $this->input->post('description', TRUE);
			$this->project_id 	= $this->input->post('project_id', TRUE);
			$this->image	 	= $this->input->post('image_name', TRUE);
			$this->user_id 	= $this->session->userdata('id');
			$this->date		 	= date('Y-m-d H:i:s');
		}
	}
	
	function post_comment(){
		if ($this->input->server('REQUEST_METHOD') == 'POST') {
			$this->image_id		 = $this->input->post('image_id', TRUE);
			$this->user_id	 = $this->session->userdata('id');
			$this->comment	 = $this->input->post('comment', TRUE);
			$this->date			 = date('Y-m-d H:i:s');
		}
	}
	
	function insert_image(){
		if ($this->db->insert("project_image", $this)){
		    return $this->db->insert_id();
		}
        return 0;
	}
	
	function insert_comment(){
		if ($this->db->insert("image_comment", $this)){
		    return $this->db->insert_id();
		}
        return 0;
	}
	
	function update_image($id){
        $id = (int) $id;
        if ($id > 0){
            $this->db->where('image_id', $id);
			if($this->db->update('project_image', $this)){
				return TRUE;
			}
			return FALSE;
        }
        return FALSE;
	}
	
	function delete_image($id){
		$id = (int) $id;
        if ($id > 0) {
			return $this->db->delete('project_image', array('image_id' => $id)) ? TRUE : FALSE ; 
        }
        return FALSE;
	}
	
	function delete_comment($id){
		$id = (int) $id;
        if ($id > 0) {
			return $this->db->delete('image_comment', array('comment_id' => $id)) ? TRUE : FALSE ; 
        }
        return FALSE;
	}
	
	function get_image($id){
		$id = (int) $id;

		$sql = "SELECT 	
					I.*,
					P.name as project_name,
					U.name as user_name
				FROM 
					project_image as I 
				LEFT JOIN 
					project as P ON P.project_id = I.project_id
				LEFT JOIN 
					user as U ON U.user_id = I.user_id
				WHERE 
					I.image_id = '{$id}' 
				LIMIT 1";
		
		return $this->db->query($sql);
	}
	
	function get_images($project_id){
		$project_id = (int) $project_id;

		$sql = "SELECT * FROM project_image WHERE project_id = '{$project_id}' ORDER BY date DESC, image_id DESC";
		
		return $this->db->query($sql);
	}
	
	function get_comments($image_id){
		$image_id = (int) $image_id;

		$sql = "SELECT 	
					C.*, 
					U.name as name, 
					U.image as user_image,
					U.color as user_color
				FROM 
					image_comment as C
				LEFT JOIN 
					user as U ON U.user_id = C.user_id
				WHERE 
					C.image_id = '{$image_id}'
				ORDER BY 
					C.date DESC";
		
		return $this->db->query($sql);
	}
	
	function save_images($saved_id = 0){
		if(!empty($saved_id)){
			$images = $this->post_images();
			$this->db->delete('project_image', array('project_id' => $saved_id));
			
			$images_saved = array();
			foreach($images as $image){
				if(strpos($image->path, "assets/upload/temp/") !== false){
					$config = array('source_image' => "assets/upload/temp/" . $image->image, 'new_image' => "assets/images/project/");
					$this->image_lib->clear();
					$this->image_lib->initialize($config);
					$this->image_lib->resize();
				}
				
				$item = new stdClass();
				$item->project_id = $saved_id;
				$item->name = $image->image;
				$images_saved[] = $this->db->insert("project_image", $item) ? TRUE : FALSE;
			}
		}
	}
	
	function fetch_images($project_id){
		$project_id = (int) $project_id;

		$this->db->select('*');
		$this->db->from('project_image');
		$this->db->where("project_id", $project_id);	
		return $this->db->get()->result();
	}	
	
}