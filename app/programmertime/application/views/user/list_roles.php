<p class="page_title" style="float:left;">System Users</p> 

<?php

	$can_create_user = $this->session->userdata('can_create_user');
	$can_edit_user = $this->session->userdata('can_edit_user');

	require('application/views/includes/message.php');
	if(validation_errors() != ''){
	echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	}

	$edit = '';
if($can_edit_user){ 
	$edit = '"edit": {name: "Edit User", icon: "edit"},
				"delete": {name: "Deactivate User", icon: "delete"},';
} 

foreach($users->result() as $user){ 
	if($user->status == 'active' || $user->user_id != 1){
	$name = explode(" ", $user->name);
	
	if(!isset($user_array[$user->access_level])){
		$user_array[$user->access_level] = "";
	}

	$user_array[$user->access_level] .= '<div class="col-lg-1 col-md-3 col-xs-5" style="text-align:center; padding-left: 0px;" id="context2_'. $user->user_id .'">

		<a href="'. base_url('user/view/'. $user->user_id) .'" >
			<img src="'. base_url('assets/images/users/'.$user->image) .'" class="img-thumbnail" style="width:100%;" />
		</a>

		<div class="description_user">
			<strong>'. $name[0] . (isset($name[1]) ? ' ' . $name[1] : '').'</strong> <br />

			<small title="Last login" style="font-size:12px;">
				<span title="'. $user->login_count .' logins">
					'. fdatetime($user->last_access ,"/") . '
				</span>
			</small>
		</div>
	</div>

	<script>	
	$(function(){
		$.contextMenu({
			selector: "#context2_'. $user->user_id .'", 
			
			callback: function(key, options) {
				
				if(key == "view"){
					var url = "'. base_url('user/view/'.$user->user_id) .'";
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "edit"){
					var url = "'. base_url('user/edit/'.$user->user_id) .'";
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "delete"){
					if(validateForm()){
						var url = "'. base_url('user/change_status/inactive/'.$user->user_id) .'";
						if (url) {
							window.location = url;
						}
					}
				}
				
			},
			
			items: {
				"view": {name: "View User", icon: "paste"},
				'. $edit .'
			}
		});
	});
	</script>';

	}
} ?>

<div class="col-lg-12">

<?php 

// var_dump($user_array);
// die();

foreach($access_levels->result() as $access_level){

	if(isset($user_array[$access_level->id])){
		if($this->session->userdata('access_level') == 1 && $access_level->id == 1){
			echo '<div class="col-lg-12 "><p class="page_title">' . $access_level->role . '</p><br />';
		}
		elseif($access_level->id != 1){
			echo '<div class="col-lg-12 "><p class="page_title">' . $access_level->role . '</p><br />';
		}

		foreach($user_array as $role_id => $content){

			if($access_level->id == $role_id){

				if($this->session->userdata('access_level') == 1 && $access_level->id == 1){
					echo $user_array[$role_id];
				}

				elseif($access_level->id != 1){
					echo $user_array[$role_id];
				}


			}
		}

		echo '</div>';
	}


} 

?>
</div>


	
	
