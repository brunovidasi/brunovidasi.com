function level(field, path, table, idField, fieldName, level_id, user_id, levelName, actionName){
	
    var current_img = $(field).attr("src");
	
    if(current_img == path+"/images/system/yes.png"){
        $(field).attr("src", path+"/images/system/no.png");
        $(field).attr('alt', 'no');
        $(field).attr('title', 'no');
        var url = path.replace(/\/assets\/?$/, "")+"/ajax/update_level";
        var fieldValue = "no";
		
		alertify.error(levelName+" can no longer "+actionName+".");
    }else{
        $(field).attr("src", path+"/images/system/yes.png");
		$(field).attr('alt', 'yes');
		$(field).attr('title', 'yes');
        var url = path.replace(/\/assets\/?$/, "")+"/ajax/update_level";
        var fieldValue = "yes";
		
		alertify.success(levelName+" can now "+actionName+".");
    }
	
    $.post(url,{
        table: table, 
        fieldName: fieldName, 
        fieldValue: fieldValue, 
        idField: idField, 
        level_id: level_id,
        user_id: user_id
    });

};

function priority(field, path, table, idField, fieldName, project_id){

    var current_img = $(field).attr("src");
	
    if($(field).attr("src") == path+"/images/system/star_grey.png"){
        $(field).attr("src", path+"/images/system/star_black.png");
		$(field).attr('alt', 'Normal');
        $(field).attr('title', 'Normal');
        var url = path.replace(/\/assets\/?$/, "")+"/ajax/update_project";
        var fieldValue = "normal";
		alertify.success("Normal priority.");
    }
	else if($(field).attr("src") == path+"/images/system/star_black.png"){
        $(field).attr("src", path+"/images/system/star_red.png");
		$(field).attr('alt', 'Urgent');
        $(field).attr('title', 'Urgent');
        var url = path.replace(/\/assets\/?$/, "")+"/ajax/update_project";
        var fieldValue = "urgent";
		alertify.error("Urgent priority.");
    } 
	else{ 
		$(field).attr("src", path+"/images/system/star_grey.png");
		$(field).attr('alt', 'Low');
        $(field).attr('title', 'Low');
        var url = path.replace(/\/assets\/?$/, "")+"/ajax/update_project";
        var fieldValue = "low";
		alertify.success("Low priority.");
    }
	
    $.post(url,{
        table: table, 
        fieldName: fieldName, 
        fieldValue: fieldValue, 
        idField: idField, 
        project_id: project_id
    });

};

function status(field, path, table, idField, fieldName, project_id){

    var current_img = $(field).attr("src");
	var url = path.replace(/\/assets\/?$/, "")+"/ajax/update_project";
	
    if($(field).attr("src") == path+"/images/system/dot_blue.png"){
        
		$(field).attr("src", path+"/images/system/dot_green.png");
		$(field).attr('alt', 'In Progress');
        $(field).attr('title', 'In Progress');
		
		if($(field).attr("overdue") == "yes"){
			$(field).parent().parent().attr("class", 'danger');
			$(field).parent().parent().find('#deadline').attr('class', 'label label-danger');
		}else{
			$(field).parent().parent().attr("class", '');
			$(field).parent().parent().find('#deadline').attr('class', 'label label-warning');
		}
		
		$(field).parent().parent().find('#log_time_entry').removeClass('disabled');
		$(field).parent().parent().find('#log_payment').removeClass('disabled');
		
        var fieldValue = "in_progress";
		alertify.success("Project em in_progress.");
		
    }
	
	else if($(field).attr("src") == path+"/images/system/dot_green.png"){
       
	   $(field).attr("src", path+"/images/system/paused.png");
		$(field).attr('alt', 'Paused');
        $(field).attr('title', 'Paused');
		$(field).parent().parent().attr("class", 'warning');
		
		if($(field).attr("overdue") == "yes"){
			$(field).parent().parent().find('#deadline').attr('class', 'label label-danger');
		}else{
			$(field).parent().parent().find('#deadline').attr('class', 'label label-warning');
		}
		
		$(field).parent().parent().find('#log_time_entry').removeClass('disabled');
		$(field).parent().parent().find('#log_payment').removeClass('disabled');
		
        var fieldValue = "paused";
		
		alertify.success("Project paused.");
		
    }

    else if($(field).attr("src") == path+"/images/system/paused.png"){
       
	   $(field).attr("src", path+"/images/system/cancelled.png");
		$(field).attr('alt', 'Cancelled');
        $(field).attr('title', 'Cancelled');
		$(field).parent().parent().attr("class", 'danger');
		
		if($(field).attr("overdue") == "yes"){
			$(field).parent().parent().find('#deadline').attr('class', 'label label-danger');
		}else{
			$(field).parent().parent().find('#deadline').attr('class', 'label label-warning');
		}
		
		$(field).parent().parent().find('#log_time_entry').addClass('disabled');
		$(field).parent().parent().find('#log_payment').addClass('disabled');
		
        var fieldValue = "cancelled";
		
		alertify.success("Project cancelled.");
		
    }
	
	else if($(field).attr("src") == path+"/images/system/cancelled.png"){
        $(field).attr("src", path+"/images/system/completed.png");
		$(field).attr('alt', 'Completed');
        $(field).attr('title', 'Completed');
		
		if($(field).attr("overdue") == "yes"){
			$(field).parent().parent().removeClass('danger');
			$(field).parent().parent().removeClass('warning');
			$(field).parent().parent().attr("class", '');
			$(field).parent().parent().find('#deadline').attr('class', 'label label-primary');
		}else{
			$(field).parent().parent().attr("class", '');
			$(field).parent().parent().find('#deadline').attr('class', 'label label-warning');
		}
		
		$(field).parent().parent().find('#log_time_entry').addClass('disabled');
		$(field).parent().parent().find('#log_payment').addClass('disabled');
		
        var fieldValue = "completed";
		
		alertify.success("Project completed.");
    } 
	
	else{ 
		$(field).attr("src", path+"/images/system/dot_blue.png");
		$(field).attr('alt', 'Not Started');
        $(field).attr('title', 'Not Started');
        
		if($(field).attr("overdue") == "yes"){
			$(field).parent().parent().attr("class", 'danger');
			$(field).parent().parent().find('#deadline').attr('class', 'label label-danger');
		}else{
			$(field).parent().parent().attr("class", '');
			$(field).parent().parent().find('#deadline').attr('class', 'label label-warning');
		}
		
		$(field).parent().parent().find('#log_time_entry').removeClass('disabled');
		$(field).parent().parent().find('#log_payment').removeClass('disabled');
		
        var fieldValue = "not_started";
		
		alertify.success("Project not started.");
    }
	
    $.post(url,{
        table: table, 
        fieldName: fieldName, 
        fieldValue: fieldValue, 
        idField: idField, 
        project_id: project_id
    });

};

function taskStatus(field, path, table, idField, fieldName, project_id){

    var current_img = $(field).attr("src");
	var url = path.replace(/\/assets\/?$/, "")+"/ajax/update_project";
	
    if($(field).attr("src") == path+"/images/system/dot_blue.png"){
        
		$(field).attr("src", path+"/images/system/dot_green.png");
		$(field).attr('alt', 'In Progress');
        $(field).attr('title', 'In Progress');
		
		if($(field).attr("overdue") == "yes"){
			$(field).parent().parent().attr("class", 'danger');
			$(field).parent().parent().find('#deadline').attr('class', 'label label-danger');
		}else{
			$(field).parent().parent().attr("class", '');
			$(field).parent().parent().find('#deadline').attr('class', 'label label-warning');
		}
		
		$(field).parent().parent().find('#log_time_entry').removeClass('disabled');
		$(field).parent().parent().find('#log_payment').removeClass('disabled');
		
        var fieldValue = "in_progress";
		alertify.success("Task em in_progress.");
		
    }
	
	else if($(field).attr("src") == path+"/images/system/dot_green.png"){
       
	   $(field).attr("src", path+"/images/system/cancelled.png");
		$(field).attr('alt', 'Cancelled');
        $(field).attr('title', 'Cancelled');
		$(field).parent().parent().attr("class", 'danger');
		
		if($(field).attr("overdue") == "yes"){
			$(field).parent().parent().find('#deadline').attr('class', 'label label-danger');
		}else{
			$(field).parent().parent().find('#deadline').attr('class', 'label label-warning');
		}
		
		$(field).parent().parent().find('#log_time_entry').addClass('disabled');
		$(field).parent().parent().find('#log_payment').addClass('disabled');
		
        var fieldValue = "cancelled";
		
		alertify.success("Task cancelada.");
		
    }
	
	else if($(field).attr("src") == path+"/images/system/cancelled.png"){
        $(field).attr("src", path+"/images/system/completed.png");
		$(field).attr('alt', 'Completed');
        $(field).attr('title', 'Completed');
		
		if($(field).attr("overdue") == "yes"){
			$(field).parent().parent().removeClass('danger');
			$(field).parent().parent().removeClass('warning');
			$(field).parent().parent().attr("class", '');
			$(field).parent().parent().find('#deadline').attr('class', 'label label-primary');
		}else{
			$(field).parent().parent().attr("class", '');
			$(field).parent().parent().find('#deadline').attr('class', 'label label-warning');
		}
		
		$(field).parent().parent().find('#log_time_entry').addClass('disabled');
		$(field).parent().parent().find('#log_payment').addClass('disabled');
		
        var fieldValue = "completed";
		
		alertify.success("Task completed.");
    } 
	
	else{ 
		$(field).attr("src", path+"/images/system/dot_blue.png");
		$(field).attr('alt', 'Not Started');
        $(field).attr('title', 'Not Started');
        
		if($(field).attr("overdue") == "yes"){
			$(field).parent().parent().attr("class", 'danger');
			$(field).parent().parent().find('#deadline').attr('class', 'label label-danger');
		}else{
			$(field).parent().parent().attr("class", '');
			$(field).parent().parent().find('#deadline').attr('class', 'label label-warning');
		}
		
		$(field).parent().parent().find('#log_time_entry').removeClass('disabled');
		$(field).parent().parent().find('#log_payment').removeClass('disabled');
		
        var fieldValue = "not_started";
		
		alertify.success("Task not started.");
    }
	
    $.post(url,{
        table: table, 
        fieldName: fieldName, 
        fieldValue: fieldValue, 
        idField: idField, 
        project_id: project_id
    });

};