function check_form(){
	var messages = "";
	var invalid = "";
	var type = "";
	// walks the page and checks every element marked [validate=true]
	$("[validate=true]").each(
		function(){
			// an empty field adds a "required" error
			if($(this).val().length < 1){
				messages += "<li>The <b>" + $(this).attr("validate_label") + "</b> field is required</li>";
			}
			// extra validation types, separated by "|", are checked one by one
			if($(this).attr("validate_type") !== undefined){
				type = $(this).attr("validate_type").split("|");
				for (var i in type){
					if(type[i] == "email"){
						if($(this).val() != ""){
							var filter = /^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i;
							if(!filter.test($(this).val())){
							  messages += "<li>The <b>Email</b> field is invalid</li>";
							}
						}
					}if(type[i] == "login"){
						// the login-check input is set to "already used" when the login is taken
						if($(this).val() == "already used"){
							messages += "<li><b>Login</b> is already in use</li>";
						}
					}
					if(type[i] == "file"){
						if($(this).val() != ""){
							var parts = $(this).val().split(".");
							var last = parts.length;
							var extension = parts[last-1];

							var types_accepted = $(this).attr("accepted_type");
							var accepted_type = types_accepted.split(", ");

							var format_valid = 0;
							for (var i2 in accepted_type){
								if(accepted_type[i2] == extension){
									format_valid = 1;
								}
							}

							if(format_valid == 0){
								var plural = "";
								if(accepted_type.length > 1){
									plural = "s";
								}
								messages += "<li>The <b>File</b> field must be in the format"+plural+" <b>"+types_accepted+"</b></li>";
							}
						}
					}
				}
			}
		}
	);
	// any error shows the message list and blocks the submit; otherwise the form is sent
	if(messages != ""){
		$("#form_validation_errors").css("display", "block");
		$("#validation_errors").html(messages);
		$("#msg_controller").css("display", "none");
		return false;
	}else if(invalid != ""){
		return false;
	}else{
		$("#loading").css("display", "block");
		return true;
	}
}
