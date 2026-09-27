<script src="<?php echo base_url('assets/js/texteditor/jquery-te-1.4.0.min.js');?>" type="text/javascript"></script>
<link rel="stylesheet" href="<?php echo base_url('assets/js/texteditor/jquery-te-1.4.0.css');?>" type="text/css">
<script type="text/javascript" src="<?php echo base_url('assets/js/moment.js'); ?>"></script>

<br>

<?php
	require('application/views/includes/message.php');
	if(validation_errors() != ''){
	echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	}
?>


	<div class="col-md-3 col-sm-4" style="margin-top:0px;">
		<div class="box-header"></div>
		
		<a class="btn btn-block btn-primary" href="<?php echo base_url('message/compose'); ?>" ><i class="glyphicon glyphicon-pencil"></i> New Message</a>
		<div style="margin-top: 15px;">

			

			<table class="table">
				<tr>
					<td style="text-align:center;" colspan="2">
						<i class="glyphicon <?php if($message->is_favorite){ echo 'glyphicon-star'; }else{ echo 'glyphicon-star-empty'; };?>" style="color: #f39c12; cursor: pointer; font-size: 20px;"></i>
					</td>
				</tr>

				<tr><th>Date:</th><td><?php echo fdatetime($message->sent_at, "/"); ?></td></tr>

				<tr><th>From:</th><td><?php echo $message->from_user_id; ?></td></tr>

				<tr><th>To:</th><td><?php echo $message->to_user_id; ?></td></tr>

				<tr>
					<th>Project:</th>
					<td>
						<a href="<?php echo base_url('project/view/'.$message->project_id); ?>"><?php echo '# '.$message->project_id .' '.'project name'; ?></a>
					</td>
				</tr>

				<tr>
					<th>Replies:</th><td><?php echo $related_messages->num_rows() .' replies.'; ?></td>
				</tr>

				<tr>
					<td colspan="2" style="text-align:center;">
						<a href="<?php echo base_url('message/mark_as_trash/'.$message->message_id.'/1'); ?>" style="width:100%;" class="btn btn-danger">
							<i class="glyphicon glyphicon-trash"></i> Move to trash
						</a>
					</td>
				</tr>

				<tr>
					<td colspan="2" style="text-align:center;">
						<a href="<?php echo base_url('message/mark_as_unread/'.$message->message_id.'/1'); ?>" style="width:100%;" style="width:100%;" class="btn btn-warning">
							Mark as unread
						</a>
					</td>
				</tr>


			</table>
		</div>
	</div>


<div class="messages col-md-9 col-sm-8">

	<table width="100%" id="table_message">
		<tr>
			<th></th>
			<th></th>
			<th></th>
			<th></th>
		</tr>

		<tr>
			<td style="width:60px" valign="top">
				<a href="<?php echo base_url('user/view/'. $message->from_user_id); ?>" class="thumbnail">
					<img src="<?php echo base_url('assets/images/users/'. $message->image); ?>" alt="">
				</a>
			</td>
			
			<td valign="top">
				<span>
					<strong style="margin-left:10px;"><a href="<?php echo base_url('user/view/'. $message->from_user_id); ?>"><?php echo short_name($message->name); ?></a>:</strong>
					<p style="margin-left:10px;"><?php echo $message->message; ?></p>
				</span>
			</td>
			
			<td valign="top" style="width:130px" title="<?php echo fdatetime($message->sent_at, "/"); ?>">
				<b><p id="message_<?php echo $message->message_id; ?>"><?php echo fdatetime($message->sent_at, "/"); ?></p></b>
			</td>
			
			<td valign="top" style="width:30px">
				<?php if(($message->user_id == $this->session->userdata('id')) OR ($this->session->userdata('role') == 'Administrator') OR ($this->session->userdata('role') == 'Manager')){ ?>
				<button class="btn btn-danger btn-xs" id="delete_message_<?php echo $message->message_id; ?>"><i class="glyphicon glyphicon-remove"></i></button>
				<?php } ?>
			</td>
		</tr>
		
		<script>
			$("#delete_message_<?php echo $message->message_id; ?>").click(function () {
				reset();
				alertify.confirm("Are you sure you want to delete this message?", function (e) {
					if (e) {
						var url = "<?php echo base_url('message/delete/'. $message->message_id .'/'.$message->message_id); ?>";
					
						if (url) {
							window.location = url;
						}
						
					} else {
						alertify.error("Message not removed.");
					}
				});
				return false;
			});

	    	var when = moment("<?php echo $message->sent_at; ?>", "YYYY/MM/DD HH:mm:ss").startOf("second").fromNow();
			$("#<?php echo 'message_'.$message->message_id; ?>").html(when);

		</script>

		<tr>
			<td colspan="4"><hr /></td>
		</tr>

	</table>

	<table width="100%" id="table_messages">

		<?php foreach($related_messages->result() as $msg){ ?>
		<tr>
			<td style="width:60px" valign="top">
				<a href="<?php echo base_url('user/view/'. $msg->from_user_id); ?>" class="thumbnail">
					<img src="<?php echo base_url('assets/images/users/'. $msg->image); ?>" alt="">
				</a>
			</td>
			
			<td valign="top">
				<span>
					<strong style="margin-left:10px;"><a href="<?php echo base_url('user/view/'. $msg->from_user_id); ?>"><?php echo short_name($msg->name); ?></a>:</strong>
					<p style="margin-left:10px;"><?php echo $msg->message; ?></p>
				</span>
			</td>
			
			<td valign="top" style="width:130px" title="<?php echo fdatetime($msg->sent_at, "/"); ?>">
				<b><p id="message_<?php echo $msg->message_id; ?>"><?php echo fdatetime($msg->sent_at, "/"); ?></p></b>
			</td>
			
			<!--td valign="top" style="width:30px">
				<?php if(($msg->user_id == $this->session->userdata('id')) OR ($this->session->userdata('role') == 'Administrator') OR ($this->session->userdata('role') == 'Manager')){ ?>
				<button class="btn btn-danger btn-xs" id="delete_message_<?php echo $msg->message_id; ?>"><i class="glyphicon glyphicon-remove"></i></button>
				<?php } ?>
			</td-->
			<td style="color:#ccc"><i class="glyphicon glyphicon-ok"></i> Read.</td>
		</tr>
		
		<script>
			$("#delete_message_<?php echo $msg->message_id; ?>").click(function () {
				reset();
				alertify.confirm("Are you sure you want to delete this message?", function (e) {
					if (e) {
						var url = "<?php echo base_url('message/delete/'. $msg->message_id .'/'.$message->message_id); ?>";
					
						if (url) {
							window.location = url;
						}
						
					} else {
						alertify.error("Message not removed.");
					}
				});
				return false;
			});

			var when = moment("<?php echo $msg->sent_at; ?>", "YYYY/MM/DD HH:mm:ss").startOf("second").fromNow();
			$("#<?php echo 'message_'.$msg->message_id; ?>").html(when);
		</script>
		<?php } ?>
	
	</table>
	
	<div class="new_message_form">
		<form  action="<?php echo base_url('message/send_message') ?>" method="post" name="form-comment" class="form-comment">
			<table width="100%">
				<tr>
					<td>
						<?php
							if($message->from_user_id == $this->session->userdata('id')){
								$to_user = $message->to_user_id;
							}else{
								$to_user = $message->from_user_id;
							}

							if(preg_match('/^RE:/', $message->subject)){
								$message->subject = $message->subject;
							}else{
								$message->subject = 'RE: '. $message->subject;
							}
						?>
						<textarea class="form-control" rows="2" class="col-lg-12" name="message" id="new_message"><?php echo set_value('message'); ?></textarea>
						<input type="hidden" name="reply_to" value="<?php echo $message->message_id; ?>"/>
						<input type="hidden" name="project_id" value="<?php echo $message->project_id; ?>"/>
						<input type="hidden" name="to_user_id" value="<?php echo $to_user; ?>"/>
						<input type="hidden" name="subject" value="<?php echo $message->subject; ?>"/>
						<input type="hidden" name="compose" value="0"/>
					</td>
				</tr>

				<tr><td><br /></td></tr>
				
				<tr>
					<td align="right">
						<button type="submit" class="btn btn-primary btn-lg"><i class="glyphicon glyphicon-share-alt"></i> Reply</button>
					</td>
				</tr>
			</table>
		</form>
	</div>
	
</div>

<div class="other_images">
	<br><br>
</div>

<script>
	$("#new_message").jqte({ol: false, ul: false, format: false});
	
	reset = function () {
	$("toggleCSS").href = "<?php echo base_url('assets/js/alertify/themes/alertify.ptime.css'); ?>";
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

	$(".glyphicon-star, .glyphicon-star-empty").click(function(e) {
		e.preventDefault();

		var glyph = $(this).hasClass("glyphicon");

		if (glyph) {

			var current_class = $(this).attr("is_favorite");
		    var url = "<?php echo base_url('message/mark_as_favorite/'); ?>";
		    var message_id = "<?php echo $message->message_id; ?>";

		    if(current_class == "true"){
		        $(this).attr("class", "glyphicon glyphicon-star-empty");
		        $(this).attr("is_favorite", "false");
		        var flag = false;
		    }else{
				$(this).attr("class", "glyphicon glyphicon-star");
				$(this).attr("is_favorite", "true");
				var flag = true;
		    }
			
		    $.post(url,{
		        message_id: message_id, 
		        flag: flag
		    });

		}

	});
</script>

