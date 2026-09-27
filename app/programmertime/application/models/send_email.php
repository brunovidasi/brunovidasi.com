<?php

class Send_email extends CI_Model {

    function __construct() {
        parent::__construct();
    }

	public function mail($from, $to, $name, $message, $subject){
		$this->email->clear();
		$this->email->initialize(array("mailtype" => "html"));

		$this->email->from($from, $name);
		$this->email->to($to);

		$this->email->subject($subject);
		$this->email->message($message);

		return $this->email->send();
	}

	function confirm_registration($email, $name, $login, $password, $confirmation_code){

		$message = "
			Welcome to ProgrammerTime!
			<br><br>
			To confirm your email address and start using ProgrammerTime, click the link below, or copy and paste it into your browser's address bar.
			<br><br>
				<hr>
				<span><strong><a href='". base_url('auth/confirm_email/'.$confirmation_code) ."'>". base_url('auth/confirm_email/'.$confirmation_code) ."</a></strong></span>
				<hr>
			<br><br>
			Your details:
			<br><br>
			<b>Name:</b> ". $name ."
			<br>
			<b>Login:</b> ". $login ."
			<br>
			<b>Password:</b> ". $password ."

			<br><br><b>This email was generated automatically by the system. Please do not reply.</b>
		";

		$this->email->clear();
		$this->email->initialize(array("mailtype" => "html"));
		$this->email->from('noreply@programmertime.com', 'ProgrammerTime');
		$this->email->to($email);
		$this->email->subject('Welcome to Programmer Time!');
		$this->email->message($message);

		return $this->email->send();
	}

	function inactive($user_email, $manager_email, $name, $login){

		$manager_message = "
			An inactive user tried to log in to the system. Check whether they need to be reactivated.
			<br><br>
			User details:
			<br><br>
			<b>Name:</b> ". $name ."
			<br>
			<b>Login:</b> ". $login ."

			<br><br>
			Remember that to reactivate the user, you need to log in to Programmer Time or contact the administrator.
			<br><br><b>This email was generated automatically by the system. Please do not reply.</b>
		";

		$user_message = "
			Hi ". $name .", <br><br>

			You tried to log in to Programmer Time as <b>". $login ."</b>, but your account is inactive. An email has been sent to the administrator in case it needs to be reactivated.
			<br>
			If possible, contact your manager.
			<br><br><b>This email was generated automatically by the system. Please do not reply.</b>
			<br>
		";

		$this->email->clear();
		$this->email->initialize(array("mailtype" => "html"));
		$this->email->from('noreply@programmertime.com', 'Programmer Time');
		$this->email->to($manager_email);
		$this->email->subject('[Do Not Reply] Inactive User');
		$this->email->message($manager_message);

		$this->email->send();


		$this->email->clear();
		$this->email->initialize(array("mailtype" => "html"));
		$this->email->from('noreply@programmertime.com', 'Programmer Time');
		$this->email->to($user_email);
		$this->email->subject('[Do Not Reply] Inactive User');
		$this->email->message($user_message);

		return $this->email->send();

	}

	function client_report(){

		$project_id 	= $this->input->post('project_id');
		$manager_name	= $this->input->post('manager_name');
		$manager_email	= $this->input->post('manager_email');
		$client_email 	= $this->input->post('client_email');
		$subject		= $this->input->post('subject');
		$message		= $this->input->post('message');

		$email = new stdClass();

		$email->project_id 	= $project_id;
		$email->subject 	= $subject;
		$email->message 	= $message;
		$email->from_name 	= $manager_name;
		$email->from_email 	= $manager_email;
		$email->to_email 	= $client_email;
		$email->cc 			= $manager_email;
		$email->date 		= date('Y-m-d H:i:s');

		if ($this->db->insert("project_email", $email)){

			$this->email->clear();
			$this->email->initialize(array("mailtype" => "html"));

			$this->email->from($manager_email, $manager_name);
			$this->email->to($client_email);

			$this->email->cc($manager_email);
			$this->email->reply_to($manager_email, $manager_name);

			$this->email->subject($subject);
			$this->email->message($message);

		    return $this->email->send();

		}else{
            return FALSE;
		}

	}

}
