<script src="<?php echo base_url('assets/js/colorpicker/bootstrap-colorpicker.js'); ?>" type="text/javascript"></script>
<link href="<?php echo base_url('assets/js/colorpicker/bootstrap-colorpicker.css'); ?>" rel="stylesheet" type="text/css" />

<p class="page_title" style="float:left">Edit User Profile</p> <br><br><br>

<?php
	require('application/views/includes/message.php');
	if(validation_errors() != '')
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
?>

<form action="<?php echo base_url('user/update/'.$profile->user_id) ?>" method="post" name="form1" class="form1">

    <table width="100%" border="0" cellpadding="3" cellspacing="3" class="table_main" style="padding:10px;">
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Full Name:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="name" type="text" class="form-control" id="" value="<?php echo set_value("name", $profile->name); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Access Level:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-3 col-md-4 inputs">
					<select name="access_level" class="form-control" id="access_level">
						<option value=""></option>
						<?php
						foreach($roles->result() as $role){
							$selected = "";
							if(set_value('access_level', $profile->access_level) == $role->id){
								$selected = 'selected="selected"';
							}
							
							if($profile->access_level == '1'){
								echo '<option value="1" '. $selected .'>Administrator</option>';
								break;
							}
							
							elseif($role->id != '1'){
								echo '<option value="'. $role->id .'" '. $selected .'>'. $role->role .'</option>';
							}
						}				
						?>
					</select>
				</div>
				<span class="btn btn-primary"><i class='glyphicon glyphicon-plus'></i></span>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Image:</strong> <span class="required">*</span>
				</div>
			</td> 
			
			<td>
				<?php 
					$image_config = $crop->source . "/" . $crop->destination . "/" . $crop->height . "/" . $crop->width; 
					$this->session->set_userdata('image_config', $image_config); 
				?>
				
				<input type="hidden" id="crop" name="crop" value='<?php echo serialize($crop); ?>' />
				<input type="hidden" name="clicked_image" id="clicked_image" value="" />
				
				<div class="modal fade" id="uploadimagem_1" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
					<div class="modal-dialog">
						<div class="modal-content">
							<span type="button" class="close" data-dismiss="modal" aria-hidden="true"></span>
							<div class="modal-body">
								<iframe id="frame_modal" src="<?php echo base_url("upload/upload_image/{$image_config}"); ?>" width="100%"  scrolling="auto" border="0" height="530px" style="border:0"></iframe>
							</div>
							
						</div>
					</div>
				</div>

				
				<div id="contents_images">
					<div id="content_image_1">
						<span class="addImg">
							<span class="uploadimage">
								<div style="margin-left: 17px; width:200px; cursor:pointer;">
									<span id="form-field-1" class="addImg thumbnail" href="#uploadimagem_1" data-toggle="modal" data-target="#uploadimagem_1"><img src="<?php echo base_url('assets/images/users/'. $profile->image); ?>"  id="upload_image_1" onclick="$('#clicked_image').val('1')" width="200px"/></span>
									<img id="remove_image_1" class="button_remove" src="<?php echo base_url('assets/images/system/no.png'); ?>" alt="Remover" style="float: right; width:20px; margin-top: -218px; margin-right: -25px; cursor:pointer;">
								</div>
							</span>
						</span>
						
						<input type="hidden" class="image_name" name="image_name[]" value="" />
						<input type="hidden" class="image_path" name="image_path[]" value="" />
						
					</div>
				</div>
				
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Username:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-2 col-md-6 inputs" id="username">
					<input name="login" type="text" class="form-control" id="user_name" value="<?php echo set_value("login", $profile->login); ?>" />
					<span class="" id="user_ico" style="top:0px; width: 60px;"></span>
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Email:</strong> <span class="required">*</span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-4 col-md-7 inputs">
					<input name="email" type="text" class="form-control" id="" value="<?php echo set_value("email", $profile->email); ?>" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="top">
				<div class="inputs">
					<strong>Password:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-2 col-md-6 inputs">
					<input name="password" type="password" class="form-control" id="password" value="<?php echo set_value("password"); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="top">
				<div class="inputs">
					<strong>Confirm Password:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-2 col-md-6 inputs" id="confirm_password">
					<input name="confirmation_password" type="password" class="form-control" id="confirmation_password" value="<?php echo set_value("confirmation_password"); ?>" />
					<span class="" id="confirm_ico" style="top:0px; width: 60px;"></span>
					<span class="help-block">Only if you want to change the password.</span>
				</div>
				<img style="display: none; margin-left: -14px; margin-bottom: 6px; width:25px;" id="img_confirm_password" src="">
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Employee ID:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-2 col-md-6 inputs">
					<input name="employee_id" type="text" class="form-control" id="" value="<?php echo set_value("employee_id", $profile->employee_id); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>ID Number:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-2 col-md-5 inputs">
					<input name="id_number" type="text" class="form-control" id="" value="<?php echo set_value("id_number", $profile->id_number); ?>" />
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Tax ID:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-2 col-md-5 inputs">
					<input name="tax_id" type="text" class="form-control" id="tax_id" value="<?php echo set_value("tax_id", $profile->tax_id); ?>" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Birth Date:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-2 col-md-5 inputs">
					<input name="birth_date" type="text" class="form-control" id="birth_date" value="<?php echo set_value("birth_date", fdate($profile->birth_date, "/")); ?>" />
				</div>
			</td>

		</tr>

		<tr>
			<td width="10%" valign="middle">
				<div class="inputs">
					<strong>Color:</strong>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<div class="col-lg-2 col-md-5 inputs">
					<div class="input-group colorpicker">
						<input name="color" type="text" class="form-control" id="color" value="<?php echo set_value("color", $profile->color); ?>" />
						<div class="input-group-addon">
	                        <i></i>
	                    </div>
					</div>
				</div>
			</td>

		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td width="10%" valign="top">
				<div class="inputs">
					<strong>Deactivate User:</strong> <span class="required"></span>
				</div>
			</td>
		
			<td width="90%" valign="middle">
				<!--<div class="col-lg-10 col-md-10 inputs" id="user_inactive">
					<input type="radio" name="status" value="inactive" <?php echo set_radio('status', 'inactive', (($profile->status=='inactive')?TRUE:FALSE)); ?> /> Yes
					<input type="radio" name="status" value="active" <?php echo set_radio('status', 'active', (($profile->status=='active')?TRUE:FALSE)); ?>  /> No
					<span class="help-block" id="deactivate"><strong>CAREFUL</strong>: once deactivated, the user will no longer have access to the system.</span>
				</div>-->
				
				
				
				<div class="col-lg-10 col-md-10 inputs" id="user_inactive">
				
					<div class="alert alert-danger fade in">
						<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
						<h4>Warning!</h4>
						<?php if($profile->status=='active'){ ?>
							<p>If you deactivate this user (<?php echo $profile->name; ?>), they will no longer have access to ProgrammerTime. An administrator or manager can reactivate them later.</p>
						<?php }else{ ?>
							<p>User <?php echo $profile->name; ?> is INACTIVE. If you reactivate them, they will have access to ProgrammerTime again.</p>
						<?php } ?>
						
					</div>
					
					<?php if($profile->status=='active'){ ?>
						<input type="checkbox" name="status" value="inactive" <?php echo set_checkbox('status', 'inactive'); ?> /> Deactivate
					<?php }else{ ?>
						<input type="checkbox" name="status" value="active" <?php echo set_checkbox('status', 'active'); ?> /> Reactivate
					<?php } ?>
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td valign="middle"></td>
			
			<td style="padding-left:14px">
				<button name="submit" type="submit" class="btn btn-primary" id="submit" />Save Changes</button>
			</td>
		</tr>
		

	</table>
</form>
<br>

<script src="<?php echo base_url('assets/js/jquery.maskedinput.js');?>"></script>
<link rel="stylesheet" href="<?php echo base_url('assets/css/datepicker.css');?>" type="text/css">
<script src="<?php echo base_url('assets/js/bootstrap-datepicker.js');?>" type="text/javascript"></script>

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
	
	
	$("#birth_date").mask("99/99/9999");
	//$("#birth_date").datepicker();

    $(".colorpicker").colorpicker();
	
	reset = function () {
		alertify.set({
			labels : {
				ok     : "Delete",
				cancel : "Don't Delete"
			},
			delay : 5000,
			buttonReverse : false,
			buttonFocus   : "ok"
		});
	};
	
	$(document).on('click', ".button_remove", function(){
		reset();
		alertify.confirm("Are you sure you want to delete this image?", function (e) {
			if (e) {
				$(document).find(".image_name").val("none.png");
				$(document).find("#upload_image_1").attr('src', "<?php echo base_url('assets/images/users/none.png'); ?>");
				console.log('image removed');
			}
		});
		return false;
	});
	
	$("#username").on("change", "#user_name", function(){
		current_name = '<?php echo $profile->login; ?>';
		$.post("<?php echo base_url("user/check_login_exists")?>", { new_name:$("#user_name").val(), current_name:current_name }, function(data){
			if(data){
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
			}
		});
	});
	
});

function insertImage(path, name_img, original_name){
	var content_img = "<img src='"+path+"'>";
	var clicked_image = $("#clicked_image").val();
	var div_content = $("#content_image_"+clicked_image);
	div_content.find(".image_name").val(name_img);
	div_content.find(".image_path").val(path);
	div_content.find(".uploadimage").html('<div style="margin-left: 17px; width:200px; cursor:pointer;"><span id="form-field-1" class="addImg thumbnail" href="#uploadimagem_'+ clicked_image +'" data-toggle="modal" data-target="#uploadimagem_'+ clicked_image +'"><img src="'+ path  +'"  id="upload_image_'+ clicked_image +'" onclick="$(\'#clicked_image\').val(\''+ clicked_image +'\')" width="200px"/></span><img id="remove_image_'+ clicked_image +'" class="button_remove" src="<?php echo base_url('assets/images/system/no.png'); ?>" alt="Remover" style="float: right; width:20px; margin-top: -218px; margin-right: -25px; cursor:pointer;"></div>');
}
</script>