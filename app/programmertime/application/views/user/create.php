<p class="page_title" style="float:left">Create User</p> <br>
<br><br>
<?php
	require('application/views/includes/message.php');
	if(validation_errors() != '')
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
?>

<form action="<?php echo base_url('user/insert') ?>" method="post" name="form1" class="form1">

    <table width="100%" border="0" cellpadding="3" cellspacing="3" class="table_main" style="padding:10px;">
		
		<tr>
			<td width="11%" valign="middle">
				<div class="inputs">
					<strong>Full Name:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="89%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="name" type="text" class="form-control" id="input_name" value="<?php echo set_value("name"); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="11%" valign="middle">
				<div class="inputs">
					<strong>Access Level:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="89%" valign="middle">
				<div class="col-lg-3 col-md-4 inputs">
					<select name="access_level" class="form-control" id="access_level">
						<option hidden></option>
						<?php
						foreach($roles->result() as $role){
							$selected = "";
							if(set_value('access_level') == $role->id)
								$selected = 'selected="selected"';
							if($role->id != '1')
								echo '<option value="'. $role->id .'" '. $selected .'>'. $role->role .'</option>';
						}				
						?>
					</select>
				</div>
				<a href="<?php echo base_url('access_level/create'); ?>" class="btn btn-primary"><i class='glyphicon glyphicon-plus'></i></a>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="9%" valign="middle">
				<div class="inputs">
					<strong>Username:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="89%" valign="middle">
				<div class="col-lg-2 col-md-6 inputs" id="username">
					<input name="login" type="text" class="form-control" id="user_name" value="<?php echo set_value("login"); ?>" placeholder="" />
					<span class="" id="user_ico" style="top:0px; width: 60px;"></span>
					<span><label class="control-label" for="login" id="label_login"></label></span>
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="9%" valign="middle">
				<div class="inputs">
					<strong>Email:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="89%" valign="middle">
				<div class="col-lg-4 col-md-7 inputs" id="email">
					<input name="email" type="text" class="form-control" id="email_field" value="<?php echo set_value("email"); ?>" />
					<span class="" id="email_ico" style="top:0px; width: 60px;"></span>
					<span><label class="control-label" for="email" id="label_email"></label></span>
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="9%" valign="middle">
				<div class="inputs">
					<strong>Password:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="89%" valign="middle">
				<div class="col-lg-2 col-md-6 inputs">
					<input name="password" type="password" class="form-control" id="password" value="<?php echo set_value("password"); ?>" />
				</div>
				<span class="btn btn-primary" id="generate_password"><i class="glyphicon glyphicon-arrow-left"></i> Generate Password</span>
			</td>
		</tr>
		
		<tr>
			<td width="9%" valign="middle">
				<div class="inputs">
					<strong>Confirm Password:</strong> <span class="required">*</span>
				</div>
			</td>
			
			<td width="89%" valign="middle">
				<div class="col-lg-2 col-md-6 inputs" id="confirm_password">
					<input name="confirmation_password" type="password" class="form-control" id="confirmation_password" value="<?php echo set_value("confirmation_password"); ?>" />
					<span class="" id="confirm_ico" style="top:0px; width: 60px;"></span>
				</div>
				<img style="display: none; margin-left: -14px; margin-bottom: 6px; width:25px;" id="img_confirm_password" src="">
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="9%" valign="middle">
				<div class="inputs">
					<strong>Employee ID:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="89%" valign="middle">
				<div class="col-lg-2 col-md-6 inputs">
					<input name="employee_id" type="text" class="form-control" id="" value="<?php echo set_value("employee_id"); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="9%" valign="middle">
				<div class="inputs">
					<strong>ID Number:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="89%" valign="middle">
				<div class="col-lg-2 col-md-5 inputs">
					<input name="id_number" type="text" class="form-control" id="" value="<?php echo set_value("id_number"); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="9%" valign="middle">
				<div class="inputs">
					<strong>Tax ID:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="89%" valign="middle">
				<div class="col-lg-2 col-md-5 inputs">
					<input name="tax_id" type="text" class="form-control" id="tax_id" value="<?php echo set_value("tax_id"); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td valign="middle"></td>
			
			<td style="padding-left:14px">
				<button name="submit" type="submit" class="btn btn-primary" id="submit" /><i class="glyphicon glyphicon-import"></i> Create User</button>
			</td>
		</tr>
		
		

	</table>
</form>
<br>

<script src="<?php echo base_url('assets/js/jquery.maskedinput.js');?>"></script>
<script>
function empty(v){
	if ((v == null) || (v == 0) || (v == '') || (v == "") || (v == undefined)){
		return true
	}else {
		return false
	}	
}

$.extend({
  password: function (length, special) {
    var iteration = 0;
    var password = "";
    var randomNumber;
    if(special == undefined){
        var special = false;
    }
    while(iteration < length){
        randomNumber = (Math.floor((Math.random() * 100)) % 94) + 33;
        if(!special){
            if ((randomNumber >=33) && (randomNumber <=47)) { continue; }
            if ((randomNumber >=58) && (randomNumber <=64)) { continue; }
            if ((randomNumber >=91) && (randomNumber <=96)) { continue; }
            if ((randomNumber >=123) && (randomNumber <=126)) { continue; }
        }
        iteration++;
        password += String.fromCharCode(randomNumber);
    }
    return password;
  }
});

$(document).ready(function () {
	$('.form1').on("focusout", "#confirmation_password, #password",function() {
		var password = $("#password").val();
		var confirm_password = $("#confirmation_password").val();
		if((!empty(password)) && (!empty(confirm_password))){
			if(password == confirm_password){
				$("#confirm_password").removeClass('form-group has-error has-feedback');
				$("#confirm_ico").removeClass('glyphicon glyphicon-remove form-control-feedback');
				$("#confirm_password").addClass('form-group has-success has-feedback');
				$("#confirm_ico").addClass('glyphicon glyphicon-ok form-control-feedback');
				$("#confirm_ico").fadeIn();
			}else{
				$("#confirm_password").removeClass('form-group has-success has-feedback');
				$("#confirm_ico").removeClass('glyphicon glyphicon-ok form-control-feedback');
				$("#confirm_password").addClass('form-group has-error has-feedback');
				$("#confirm_ico").addClass('glyphicon glyphicon-remove form-control-feedback');
				$("#confirm_ico").fadeIn();
			}
		}
		
		else{
			$("#confirm_password").removeClass('form-group has-success has-feedback');
			$("#confirm_ico").removeClass('glyphicon glyphicon-ok form-control-feedback');
			$("#confirm_password").removeClass('form-group has-error has-feedback');
			$("#confirm_ico").removeClass('glyphicon glyphicon-remove form-control-feedback');
			$("#confirm_ico").fadeOut();
		}
	});
	$('.form1').on("keydown keypress keyup", "#confirmation_password, #password",function() {
		$("#img_confirm_password").fadeOut();
	});
	
	$("#password").trigger("focusout");
	
	$('#generate_password').click(function(e){
		password = $.password(6,false);
		$('#password').attr('type', 'text');
		
		$('#confirmation_password').attr('type', 'text');
		$('#password').val(password);
		$('#confirmation_password').val(password);
		
		$("#confirm_password").removeClass('form-group has-error has-feedback');
		$("#confirm_ico").removeClass('glyphicon glyphicon-remove form-control-feedback');
		$("#confirm_password").addClass('form-group has-success has-feedback');
		$("#confirm_ico").addClass('glyphicon glyphicon-ok form-control-feedback');
		$("#confirm_ico").fadeIn();
		
		e.preventDefault();
	});
	
	$('#password').click(function(e){
		$('#password').attr('type', 'password');
		$('#confirmation_password').attr('type', 'password');
	});
	
	$('#confirmation_password').click(function(e){
		$('#password').attr('type', 'password');
		$('#confirmation_password').attr('type', 'password');
	});
	
	$(".form1").on("change", "#input_name", function(){
		var user_name = $('#input_name').val();
		var name_array = user_name.split(" ");
		
		var name_login = "";
		
		if(empty(name_array[1])){
			name_login = name_array[0];
		}else{
			name_login = name_array[0] + "." + name_array[1];
		}
		var name_login_lowercase = name_login.toLowerCase();
		
		name_old = $('#user_name').val();

		if(empty(name_old)){
			$('#user_name').val(name_login_lowercase);
		
			var new_name = $("#user_name").val();
			$.post("<?php echo base_url('user/check_login_exists')?>", { new_name:new_name }, function(data){
				if(data){
					if(!empty(new_name)){
						if(data == 0){
							$("#username").removeClass('form-group has-error has-feedback');
							$("#user_ico").removeClass('glyphicon glyphicon-remove form-control-feedback');
							$("#username").addClass('form-group has-success has-feedback');
							$("#user_ico").addClass('glyphicon glyphicon-ok form-control-feedback');
							$("#user_ico").fadeIn();
						}else{
							$("#username").removeClass('form-group has-success has-feedback');
							$("#user_ico").removeClass('glyphicon glyphicon-ok form-control-feedback');
							$("#username").addClass('form-group has-error has-feedback');
							$("#user_ico").addClass('glyphicon glyphicon-remove form-control-feedback');
							$("#user_ico").fadeIn();
						}
					}else{
						$("#username").removeClass('form-group has-success has-feedback');
						$("#user_ico").removeClass('glyphicon glyphicon-ok form-control-feedback');
						$("#username").removeClass('form-group has-error has-feedback');
						$("#user_ico").removeClass('glyphicon glyphicon-remove form-control-feedback');
					}
				}
			});
		}
	});
	
	$("#username").on("change", "#user_name", function(){
		var new_name = $("#user_name").val();
		$.post("<?php echo base_url('user/check_login_exists')?>", { new_name:new_name }, function(data){
			if(data){
				if(!empty(new_name)){
					if(data == 0){
						$("#username").removeClass('form-group has-error has-feedback');
						$("#user_ico").removeClass('glyphicon glyphicon-remove form-control-feedback');
						$("#username").addClass('form-group has-success has-feedback');
						$("#user_ico").addClass('glyphicon glyphicon-ok form-control-feedback');
						$("#user_ico").fadeIn();
					}else{
						$("#username").removeClass('form-group has-success has-feedback');
						$("#user_ico").removeClass('glyphicon glyphicon-ok form-control-feedback');
						$("#username").addClass('form-group has-error has-feedback');
						$("#user_ico").addClass('glyphicon glyphicon-remove form-control-feedback');
						$("#user_ico").fadeIn();
					}
				}else{
					$("#username").removeClass('form-group has-success has-feedback');
					$("#user_ico").removeClass('glyphicon glyphicon-ok form-control-feedback');
					$("#username").removeClass('form-group has-error has-feedback');
					$("#user_ico").removeClass('glyphicon glyphicon-remove form-control-feedback');
				}
			}
		});
	});

	$("#email").on("change", "#email_field", function(){
		var new_email = $("#email_field").val();

		er = /^[a-zA-Z0-9][a-zA-Z0-9\._-]+@([a-zA-Z0-9\._-]+\.)[a-zA-Z-0-9]{2}/;

		$.post("<?php echo base_url("user/check_email_exists")?>", { new_email:new_email }, function(data){
			if(data){
				if(!empty(new_email)){
					if(data == 0 && er.exec(new_email)){
						$("#email").removeClass('form-group has-error has-feedback');
						$("#email_ico").removeClass('glyphicon glyphicon-remove form-control-feedback');
						$("#email").addClass('form-group has-success has-feedback');
						$("#email_ico").addClass('glyphicon glyphicon-ok form-control-feedback');
						$("#email_ico").fadeIn();
					}else{
						$("#email").removeClass('form-group has-success has-feedback');
						$("#email_ico").removeClass('glyphicon glyphicon-ok form-control-feedback');
						$("#email").addClass('form-group has-error has-feedback');
						$("#email_ico").addClass('glyphicon glyphicon-remove form-control-feedback');
						$("#email_ico").fadeIn();
					}
				}else{
					$("#email").removeClass('form-group has-success has-feedback');
					$("#email_ico").removeClass('glyphicon glyphicon-ok form-control-feedback');
					$("#email").removeClass('form-group has-error has-feedback');
					$("#email_ico").removeClass('glyphicon glyphicon-remove form-control-feedback');
				}
			}
		});

	});
});
</script>