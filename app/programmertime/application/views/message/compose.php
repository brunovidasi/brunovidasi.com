<script src="<?php echo base_url('assets/js/texteditor/jquery-te-1.4.0.min.js');?>" type="text/javascript"></script>
<link rel="stylesheet" href="<?php echo base_url('assets/js/texteditor/jquery-te-1.4.0.css');?>" type="text/css">

<br>

<?php
	require('application/views/includes/message.php');
	if(validation_errors() != ''){
	echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	}
?>
	
	<div class="new_message_form">
		<form  action="<?php echo base_url('message/send_message') ?>" method="post" name="form-comment" class="form-comment">
			<table width="100%" class="table">
				<tr>
					<td>
						<select name="to_user_id" class="form-control" id="to_user_id">
							<option value="">Send message to:</option>
							<?php foreach($users->result() as $user){
								$selected = "";
								if(set_value('to_user_id', $user_id) == $user->user_id){
									$selected = 'selected="selected"';
								}
								echo '<option value="'. $user->user_id .'" '. $selected .'>'. short_name($user->name) .'</option>';
							} ?>
						</select>
					</td>

					<td>
						<select name="client_id" class="form-control" id="client_id">
							<option value="">Client</option>
							<?php foreach($clients->result() as $client){
								$selected = "";
								if(set_value('client_id', $client_id) == $client->client_id){
									$selected = 'selected="selected"';
								}
								echo '<option value="'. $client->client_id .'" '. $selected .'>'. $client->name .'</option>';
							} ?>
						</select>
					</td>

				</tr>

				<tr>
					<td><input type="text" name="subject" id="subject" value="<?php echo set_value('subject'); ?>" class="form-control" placeholder="Subject" /></td>

					<td>
						<select name="project_id" class="form-control" id="project_id">
							<option value=""></option>
							<?php
								$cid = set_value('client_id', $client_id);
								if(empty($cid)){
									echo '<option value="">Select the client</option>';
								}else{
									echo $project_options;
								}
							?>
						</select>
					</td>
				</tr>

				<tr>
					<td colspan="2">
						<textarea class="form-control" rows="3" class="col-lg-12" name="message" id="new_message"><?php echo set_value('message'); ?></textarea>
						<input type="hidden" name="reply_to" value="0"/>
						<input type="hidden" name="compose" value="1"/>
					</td>
				</tr>
				
				<tr>
					<td align="right" colspan="2">
						<button type="button" class="btn btn-warning btn-lg"><i class="glyphicon glyphicon-floppy-disk"></i> Save as Draft</button>
						<button type="submit" id="submit" class="btn btn-primary btn-lg"><i class="glyphicon glyphicon-share-alt"></i> Send Message</button>
					</td>
				</tr>
			</table>
		</form>
	</div>
	
</div>

<script>
jQuery(document).ready(function($){
	$("#new_message").jqte({ol: false, ul: false, format: false});
	
	$("select[name=client_id]").change(function(){
		$("html").css("cursor", "progress");
		$("select[name=project_id]").html('<option value="0">Loading projects ...</option>');
		$("#loader_projects").html("<img src='<?php echo base_url('assets/images/system/ajax_loader.gif'); ?>' width='30px'/>");
		
		$.post("<?php print base_url("time_entry/get_projects/".$project); ?>", {client_id:$(this).val()}, function(response){
			$("select[name=project_id]").html(response);
			$("#loader_projects").html("");
			$("html").css("cursor", "auto");
		});
	});
	
	$('#submit').click(function(){
		$("html").css("cursor", "progress");
		$("#submit").addClass("disabled");
		$("#loader_submit").html("<img src='<?php echo base_url('assets/images/system/ajax_loader.gif'); ?>' width='20px'/>");
		
		$(".form-comment").submit();
	});

});
</script>

