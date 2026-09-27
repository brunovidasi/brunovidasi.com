<?php

class Message_model extends CI_Model {
	
    function __construct() {
        parent::__construct();
    }

	function send_message($from, $to, $subject, $msg, $reply_to, $project_id, $user_id){
		
		$message = new stdClass();
		$message->user_id 		= $user_id;
		$message->from_user_id 	= $from;
		$message->to_user_id 	= id_or_null($to);
		$message->project_id		= id_or_null($project_id);
		$message->message 		= $msg;
		$message->subject 			= $subject;
		$message->reply_to 		= id_or_null($reply_to);
		$message->sent_at		= date('Y-m-d H:i:s');
		$message->is_draft 		= FALSE;
		$message->is_favorite	 		= FALSE;
		$message->is_trash 	 		= FALSE;

		if($this->db->insert("message", $message)){
			return $this->db->insert_id();
		}
		return 0;
	}

	function get_message($message_id){
		$message_id = (int) $message_id;

		$sql = "SELECT 
					m.*,
					u.name,
					u.image,
					u.status,
					u.color
				FROM 
					message as m
				INNER JOIN
					user as u ON(m.from_user_id = u.user_id)
				WHERE 
					m.message_id = {$message_id}
				";
		
		$query = $this->db->query($sql);
		return $query;
	}

	function get_related_messages($message_id){
		$message_id = (int) $message_id;

		$message_id = (int) $message_id;
		$user_id = $this->session->userdata('id');

		$sql = "SELECT 
					m.*,
					u.name,
					u.image,
					u.status,
					u.color
				FROM 
					message as m
				INNER JOIN
					user as u ON(m.from_user_id = u.user_id)
				WHERE 
					m.reply_to = '{$message_id}'
				AND
					m.user_id = '{$user_id}'
				ORDER BY 
					m.sent_at ASC, m.message_id ASC
				";
		
		return $this->db->query($sql);
	}
	
	function get_messages(){
		$user_id = $this->session->userdata('id');

		$sql = "SELECT 
					m.*,
					u.name,
					u.image,
					u.status,
					u.color
				FROM 
					message as m
				INNER JOIN
					user as u ON(m.from_user_id = u.user_id)
				WHERE 
					m.user_id = '{$user_id}'
				AND
					m.from_user_id != '{$user_id}'
				ORDER BY 
					m.sent_at DESC, m.message_id DESC
				";
		
		return $this->db->query($sql);
	}

	function get_unread_messages(){
		$user_id = $this->session->userdata('id');

		$sql = "SELECT 
					m.*,
					u.name,
					u.image,
					u.status,
					u.color
				FROM 
					message as m
				INNER JOIN
					user as u ON(m.from_user_id = u.user_id)
				WHERE 
					m.user_id = '{$user_id}'
				AND
					m.is_read = false
				ORDER BY 
					m.sent_at DESC, m.message_id DESC
				";
		
		return $this->db->query($sql);
	}

	function get_trashed_messages(){
		$user_id = $this->session->userdata('id');

		$sql = "SELECT 
					m.*,
					u.name,
					u.image,
					u.status,
					u.color
				FROM 
					message as m
				INNER JOIN
					user as u ON(m.from_user_id = u.user_id)
				WHERE 
					m.user_id = '{$user_id}'
				AND
					m.is_trash = true
				ORDER BY 
					m.sent_at DESC, m.message_id DESC
				";
		
		return $this->db->query($sql);
	}

	function get_draft_messages(){
		$user_id = $this->session->userdata('id');

		$sql = "SELECT 
					m.*,
					u.name,
					u.image,
					u.status,
					u.color
				FROM 
					message as m
				INNER JOIN
					user as u ON(m.from_user_id = u.user_id)
				WHERE 
					m.user_id = '{$user_id}'
				AND
					m.is_draft = true
				ORDER BY 
					m.sent_at DESC, m.message_id DESC
				";
		
		return $this->db->query($sql);
	}

	function mark_as_read($message_id, $flag){
		$message_id = (int) $message_id;
		$flag = filter_var($flag, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

		$sql = "UPDATE
					message
				SET 
					is_read = {$flag}
				WHERE
					message_id = {$message_id}
				";
		
		return $this->db->query($sql);
	}

	function mark_as_favorite($message_id, $flag){
		$message_id = (int) $message_id;
		$flag = filter_var($flag, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

		$sql = "UPDATE
					message
				SET 
					is_favorite = {$flag}
				WHERE
					message_id = {$message_id}
				";
		
		return $this->db->query($sql);
	}

	function mark_as_draft($message_id, $flag){
		$message_id = (int) $message_id;
		$flag = filter_var($flag, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

		$sql = "UPDATE
					message
				SET 
					is_draft = {$flag}
				WHERE
					message_id = {$message_id}
				";
		
		return $this->db->query($sql);
	}

	function mark_as_trash($message_id, $flag){
		$message_id = (int) $message_id;
		$flag = filter_var($flag, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

		$sql = "UPDATE
					message
				SET 
					is_trash = {$flag}
				WHERE
					message_id = {$message_id}
				";
		
		return $this->db->query($sql);
	}

	function delete($id){
		$id = (int) $id;
        if ($id > 0)
			return $this->db->delete('message', array('message_id' => $id)) ? TRUE : FALSE;
        else
            return FALSE;
	}

	
}