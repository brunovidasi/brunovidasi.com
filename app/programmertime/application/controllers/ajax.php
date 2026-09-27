<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * Inline edits (access-level flags, project status/priority, task status)
 * called from assets/js/inlineUpdate.js. Replaces the old standalone
 * assets/ajax/*.php scripts, which had no authentication. Running inside
 * CodeIgniter means the login hooks apply before these methods are reached.
 */
class Ajax extends CI_Controller {

	// table => id column, editable columns and the permission required to edit
	private $allowed_fields = array(
		'project' => array(
			'idField'   => 'project_id',
			'fieldName' => array('status', 'priority'),
			'permission' => 'can_edit_project',
		),
		'task' => array(
			'idField'   => 'task_id',
			'fieldName' => array('status'),
			'permission' => NULL,
		),
		'access_level' => array(
			'idField'   => 'id',
			'fieldName' => array(
				'can_create_project', 'can_edit_project',
				'can_create_client', 'can_edit_client',
				'can_log_time', 'can_log_payment',
				'can_send_report',
				'can_create_user', 'can_edit_user',
			),
			'permission' => 'can_edit_user',
		),
	);

	public function index(){
		show_404();
	}

	public function update_project(){
		$updated = $this->update_field($this->input->post('project_id'));
		$this->respond($updated);
	}

	public function update_level(){
		$level_id = (int) $this->input->post('level_id');

		if($this->input->post('table') != 'access_level')
			$this->respond(FALSE);

		$updated = $this->update_field($level_id);

		// users of this level must reload their permissions
		if($updated){
			$this->db->where('access_level', $level_id);
			$this->db->update('user', array('reload' => 'yes'));
		}

		$this->respond($updated);
	}

	private function update_field($idValue){
		if($this->input->server('REQUEST_METHOD') != 'POST') return FALSE;

		$table     = $this->input->post('table');
		$idField    = $this->input->post('idField');
		$fieldName  = $this->input->post('fieldName');
		$fieldValue = $this->input->post('fieldValue');

		if(!is_string($table) || !isset($this->allowed_fields[$table])) return FALSE;
		$allowed = $this->allowed_fields[$table];

		if($idField !== $allowed['idField']) return FALSE;
		if(!in_array($fieldName, $allowed['fieldName'], TRUE)) return FALSE;
		if(!is_string($fieldValue) || !preg_match('/^[a-z_]{1,50}$/', $fieldValue)) return FALSE;
		if(!is_numeric($idValue) || (int) $idValue <= 0) return FALSE;
		if(!empty($allowed['permission']) && !$this->session->userdata($allowed['permission'])) return FALSE;

		$this->db->where($idField, (int) $idValue);
		return $this->db->update($table, array($fieldName => $fieldValue)) ? TRUE : FALSE;
	}

	private function respond($ok){
		if(!$ok) $this->output->set_status_header(403);
		$this->output->set_content_type('application/json');
		$this->output->set_output(json_encode(array('ok' => (bool) $ok)));
		$this->output->_display();
		exit;
	}
}
