<script type="text/javascript" src="<?php echo base_url('assets/js/moment.js'); ?>"></script>

<style>
.unread{
background: #f9f9f9;
}

.small-col{
width: 35px;
}

.glyphicon-star {
color: #f39c12;
cursor: pointer;
font-size: 20px;
}

.glyphicon-star-empty {
color: #f39c12;
cursor: pointer;
font-size: 20px;
}

.time{
width: 150px;
text-align:right;
}
</style>

<div class="box-body">
	<div class="row">
		
		<div class="col-md-3 col-sm-4" style="margin-top:20px;">
			<div class="box-header"></div>
			
			<a class="btn btn-block btn-primary" href="<?php echo base_url('message/compose'); ?>"><i class="glyphicon glyphicon-pencil"></i> New Message</a>
			<div style="margin-top: 15px;">
				<ul class="nav nav-pills nav-stacked">
					<li class="header"></li>

					<li class="active">
						<a href="#"><i class="glyphicon glyphicon-inbox"></i> Inbox 
						<?php
							if($unread_messages->num_rows() > 0){
								echo '<span class="badge">'. $unread_messages->num_rows() .'</span>';
							}
						?>
						</a>
					</li>

					<li>
						<a href="<?php echo base_url('message/drafts'); ?>">
							<i class="glyphicon glyphicon-edit"></i> Drafts
						</a>
					</li>

					<li>
						<a href="<?php echo base_url('message/sent'); ?>">
							<i class="glyphicon glyphicon-share-alt"></i> Sent
						</a>
					</li>

					<li>
						<a href="<?php echo base_url('message/starred'); ?>">
							<i class="glyphicon glyphicon-star"></i> Starred
						</a>
					</li>

					<li>
						<a href="<?php echo base_url('message/trash'); ?>">
							<i class="glyphicon glyphicon-trash"></i> Trash
						</a>
					</li>

				</ul>
			</div>
		</div>
		
		<div class="col-md-9 col-sm-8" style="margin-top:20px;">
			<div class="row pad" style="margin-bottom:10px;">
				<div class="col-sm-6">
					<label style="margin-right: 10px;" class="">
						<input type="checkbox" id="check-all" style="position: absolute; opacity: 0;">
					</label>
					
					<div class="btn-group">
						<button type="button" class="btn btn-primary btn-sm btn-flat dropdown-toggle" data-toggle="dropdown">
							Action <span class="caret"></span>
						</button>
						<ul class="dropdown-menu" role="menu">
							<li><a href="#">Mark as read</a></li>
							<li><a href="#">Mark as unread</a></li>
							<li class="divider"></li>
							<li><a href="#">Move to trash</a></li>
							<li class="divider"></li>
							<li><a href="#">Delete</a></li>
						</ul>
					</div>

				</div>
				
				<div class="col-sm-6 search-form">
					<form action="#" class="text-right">
						<div class="input-group">
							<input type="text" class="form-control input-sm" placeholder="Search">
							<div class="input-group-btn">
								<button type="submit" name="q" class="btn btn-sm btn-primary"><i class="glyphicon glyphicon-search"></i></button>
							</div>
						</div>
					</form>
				</div>
				
			</div>

			<div class="table-responsive">
				<table class="table table-mailbox">
					<tbody>
					
					<?php foreach($messages->result() as $message){ 
						$b = "";
						$end_b = "";

						if(!$message->is_read){
							$b = '<b>';
							$end_b = '</b>';
						}
					?>

					<tr class="<?php if($message->is_read) echo 'unread';?>">
					
						<td class="small-col">
							<input type="checkbox" style="position: absolute; opacity: 0;">
						</td>
						
						<td class="small-col">
							<i class="glyphicon <?php if($message->is_favorite){ echo 'glyphicon-star'; }else{ echo 'glyphicon-star-empty'; };?>" is_favorite="<?php if($message->is_favorite){ echo 'true'; }else{ echo 'false';}?>"></i>
						</td>
						
						<td class="subject">
							<?php echo $b; ?><a href="<?php echo base_url('message/view/'.$message->message_id); ?>"><?php echo $message->subject; ?></a> <?php echo $end_b; ?>
						</td>

						<td class="name">
							<?php echo $b; ?><a href="<?php echo base_url('user/view/'.$message->from_user_id); ?>"><?php echo short_name($message->name); ?></a><?php echo $end_b; ?>
						</td>
						
						<td class="time">

							<?php 

							list($date, $time) = explode(" ", $message->sent_at);
							
							if($date == date('Y-m-d')){
								$time = explode(":", $time);
								$alt = $time[0] . ":" . $time[1];
							}else{
								$alt = fdatetime($message->sent_at, "/");
							} ?>
							<strong><span id="message_<?php echo $message->message_id; ?>" title="<?php echo $alt; ?>"></span></strong>

						<script>
					    	var when = moment("<?php echo $message->sent_at; ?>", "YYYY/MM/DD HH:mm:ss").startOf("second").fromNow();
			 				$("#message_<?php echo $message->message_id; ?>").html(when);
					    </script>

						</td>
					</tr>
					<?php } ?>
				</tbody></table>
			</div><!-- /.table-responsive -->
		</div><!-- /.col (RIGHT) -->
	</div><!-- /.row -->
</div>

<script type="text/javascript">
$(function() {

	"use strict";

	$('input[type="checkbox"]').iCheck({
		checkboxClass: 'icheckbox_minimal-blue',
		radioClass: 'iradio_minimal-blue'
	});

	$("#check-all").on('ifUnchecked', function(event) {
		$("input[type='checkbox']", ".table-mailbox").iCheck("uncheck");
	});

	$("#check-all").on('ifChecked', function(event) {
		$("input[type='checkbox']", ".table-mailbox").iCheck("check");
	});

	$(".glyphicon-star, .glyphicon-star-empty").click(function(e) {
		e.preventDefault();

		var glyph = $(this).hasClass("glyphicon");

		if (glyph) {

			var current_class = $(this).attr("is_favorite");
		    var url = "<?php echo base_url('message/mark_as_favorite/'); ?>";
		    var message_id = "<?php echo isset($message) ? $message->message_id : ''; ?>";

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

});
</script>