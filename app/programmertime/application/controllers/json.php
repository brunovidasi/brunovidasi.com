<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Json extends CI_Controller{

	public function index(){
		$this->login();
	}
	
	public function login($login = NULL, $password = NULL, $view = 'json'){

		if(empty($login) && empty($password)){
			$login = $this->input->post('login');
			$password = hash_password($this->input->post('password'));
		}

		$user = $this->auth_model->get_by_login($login)->row();
		$id	= (isset($user->user_id)) ? $user->user_id : 0;

		if ((!empty($id)) && ($id > 0)){

			if(($password == $user->password) && ($user->status == 'active')){

				if($view != 'verify') $this->auth_model->log($user->login_count, $id);

				$access_level = $this->auth_model->access_level($user->access_level);
				$company = $this->company_model->get_company()->row();
				$info = $this->auth_model->get_info()->row();

				$color['hexadecimal'] = $user->color;
				$color['rgb']['red'] = hexdec(substr(substr($user->color, 1), 0, 2));
				$color['rgb']['green'] = hexdec(substr(substr($user->color, 1), 2, 2));
				$color['rgb']['blue'] = hexdec(substr(substr($user->color, 1), 4, 2));
				$color['rgb']['color'] = "rgb(".$color['rgb']['red'].",".$color['rgb']['green'].",".$color['rgb']['blue'].")";

				$image['image'] = $user->image;
				$image['link'] = base_url('assets/images/users/'.$user->image);
				$image['path'] = FCPATH.'assets/images/users/'.basename((string) $user->image);
				$image['extension'] = pathinfo($image['link'], PATHINFO_EXTENSION);
				$image['width'] = $image['height'] = $image['type'] = $image['attr'] = NULL;
				$image['base64'] = NULL;
				if(is_file($image['path'])){
					$size = getimagesize($image['path']);
					if($size) list($image['width'], $image['height'], $image['type'], $image['attr']) = $size;
					$image['base64'] = base64_encode(file_get_contents($image['path']));
				}


				$asterisks = '';
				for($a = 0; $a < strlen($this->input->post('password')); $a++) $asterisks .= '*';


				$result["login"] = array(
					"access"		=> $access_level, 
					"function"		=> 'login('.$login.', '.$asterisks.', '.$view.')', 
					"error"		=> 0, 
					"message"		=> NULL, 
					"access_date"	=> date('Y-m-d H:i:s'), 
					"token"			=> $user->password, 
					"email_token"	=> $user->email_token, 
					"name"			=> $user->name, 
					"login"			=> $user->login, 
					"email"			=> $user->email, 
					"status"		=> $user->status, 
					"access_level"	=> $user->access_level, 
					"login_count"	=> $user->login_count, 
					"confirmed"	=> $user->confirmed, 
					"birth_date"		=> $user->birth_date, 
					"created_at"	=> $user->created_at, 
					"last_access"	=> $user->last_access, 
					"employee_id"		=> $user->employee_id, 
					"id_number"			=> $user->id_number, 
					"tax_id"			=> $user->tax_id, 
					"image"		=> $image, 
					"color"			=> $color, 
					"company"		=> $company, 
					"ptime_info"	=> $info 
				);
				
				if($view == 'verify') return $result["login"];

				else{
					echo json_encode($result);
					die();
				}
			}

			elseif(($password == $user->password) && ($user->status == 'inactive')){

					if($view != 'verify')
						$this->send_email->inactive($user->email, "contact@programmertime.com", $user->name, $login);

					$result["login"] = array(
						"access"		=> FALSE,
						"function"		=> 'login('.$login.', *****, '.$view.')',
						"message"		=> "user_inactive",
						"error"		=> 3 // user inactive
					);

					echo json_encode($result);
					die();

			}elseif($password != $user->password){

				$result["login"] = array(
					"access"		=> FALSE,
					"function"		=> 'login('.$login.', *****, '.$view.')',
					"message"		=> "wrong_password",
					"error"		=> 2 // wrong password
				);

				echo json_encode($result);
				die();

			}else{

				$result["login"] = array(
					"access"		=> FALSE,
					"function"		=> 'login('.$login.', *****, '.$view.')',
					"message"		=> "undefined_error",
					"error"		=> 4 // undefined error
				);

				echo json_encode($result);
				die();
			}

		}

		else{

			$result["login"] = array(
				"access"		=> FALSE,
				"function"		=> 'login('.$login.', *****, '.$view.')',
				"message"		=> "user_not_found",
				"error"		=> 1 // user not found
			);

			echo json_encode($result);
			die();
		}

	}

	private function verifyUser($login = NULL, $token = NULL, $hash = 0){
		
		$login = $this->input->post('login');
		$hash = (int) $this->input->post('hash'); // 1 = 'password' is plain text and must be hashed

		$token = ($hash == 1) ? hash_password($this->input->post('password')) : $this->input->post('password');

		return $this->login($login, $token, 'verify');
	}

	public function getUser($user_id = NULL){

		$this->verifyUser();

		if(empty($user_id)) $user_id = $this->input->post('user_id');
		$user_id = (int) $user_id;

		$array["login"] = array(
			"access"		=> TRUE,
			"function"		=> 'getUser('.$user_id.')',
			"message"		=> NULL,
			"error"		=> 0
		);

		$array["user"] = $this->user_model->get_user($user_id)->row();
		$array["access_level"] = $this->auth_model->access_level($array["user"]->access_level);

		echo json_encode($array);
		die();

	}

	public function getProjects(){

		$this->verifyUser();

		$array["login"] = array(
			"access"		=> TRUE,
			"function"		=> 'getProjects()',
			"message"		=> NULL,
			"error"		=> 0
		);

		$array["projects"] = $this->project_model->get_projects()->result();

		echo json_encode($array);
		die();
	}

	public function getProject($project_id = NULL){

		$this->verifyUser();

		if(empty($project_id)) $project_id = $this->input->post('project_id');
		$project_id = (int) $project_id;

		$array["login"] = array(
			"access"		=> TRUE,
			"function"		=> 'getProject('.$project_id.')',
			"message"		=> NULL,
			"error"		=> 0
		);

		$array["project"] = $this->project_model->get_project($project_id)->row();

		echo json_encode($array);
		die();
	}

	public function getTimeEntries($project_id = NULL){

		$this->verifyUser();

		if(empty($project_id)) $project_id = $this->input->post('project_id');
		$project_id = (int) $project_id;

		$array["login"] = array(
			"access"		=> TRUE,
			"function"		=> 'getTimeEntries('.$project_id.')',
			"message"		=> NULL,
			"error"		=> 0
		);

		$array["time_entries"] = $this->time_entry_model->get_time_entries($project_id)->result();

		echo json_encode($array);
		die();
	}

	public function getTimeEntry($time_entry_id = NULL){

		$this->verifyUser();

		if(empty($time_entry_id)) $time_entry_id = $this->input->post('time_entry_id');
		$time_entry_id = (int) $time_entry_id;

		$array["login"] = array(
			"access"		=> TRUE,
			"function"		=> 'getTimeEntry('.$time_entry_id.')',
			"message"		=> NULL,
			"error"		=> 0
		);
		
		$array["time_entry"] = $this->time_entry_model->get_time_entry_report($time_entry_id)->result();

		echo json_encode($array);
		die();
	}

	public function getClients(){

		$this->verifyUser();

		$array["login"] = array(
			"access"		=> TRUE,
			"function"		=> 'getClients()',
			"message"		=> NULL,
			"error"		=> 0
		);

		$array["clients"] = $this->client_model->get_clients()->result();

		echo json_encode($array);
		die();
	}

	public function getClient($client_id = NULL){

		$this->verifyUser();

		if(empty($client_id)) $client_id = $this->input->post('client_id');
		$client_id = (int) $client_id;

		$array["login"] = array(
			"access"		=> TRUE,
			"function"		=> 'getClient('.$client_id.')',
			"message"		=> NULL,
			"error"		=> 0
		);

		$array["client"] = $this->client_model->get_client($client_id)->row();

		echo json_encode($array);
		die();
	}

}

/*

// WAYS TO CALL THIS API FROM PHP


if($_POST){

	$url = $_POST['url']; // URL WITH JSON REQUEST
	
	// DATA TO POST
	$data = array(
		'login' => $_POST['username'], 
		'password' => $_POST['password'], 
		'hash' => $_POST['hash'], 
		'project_id' => $_POST['project_id'],
		'time_entry_id' => $_POST['time_entry_id'],
		'client_id' => $_POST['client_id'],
	);

	// CURL FUNCTION
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_POST, true); // ALLOW POST
	curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // DATA POST IN ARRAY
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	
	$result = curl_exec($curl); // GET RESULT IN JSON
	$error = curl_error($curl); // GET ERROR
	
	$object = json_decode($result); // JSON TO OBJECT

	curl_close($curl); // CLOSE CURL FUNCTION
	
	// PRINT THE OBJECT
	echo "<pre>";
	print_r($object);
	echo "</pre>";
	die();

}
	
## OR FILE GET CONTENTS

	// $options = array(

	// 	'http' => array(

	// 		'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
	// 		'method'  => 'POST',
	// 		'content' => http_build_query($data),

	// 	),
	// );

	// $context  = stream_context_create($options);
	// $result = file_get_contents($url, false, $context);

	// $object = json_decode($result);

	// echo '<pre>';
	// print_r($object);
	// echo '</pre>';
	// die();

*/