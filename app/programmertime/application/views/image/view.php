<link type="text/css" href="<?php echo base_url('assets/js/pagination/paging.css'); ?>" rel="stylesheet" />
<script type="text/javascript" src="<?php echo base_url('assets/js/pagination/paging.js'); ?>"></script>
<script src="<?php echo base_url('assets/js/texteditor/jquery-te-1.4.0.min.js');?>" type="text/javascript"></script>
<link rel="stylesheet" href="<?php echo base_url('assets/js/texteditor/jquery-te-1.4.0.css');?>" type="text/css">

<p class="page_title" style="">Images for Project <?php echo $image->project_name; ?></p> 

<br>

<?php
	require('application/views/includes/message.php');
	if(validation_errors() != ''){
	echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	}
?>

<div class="row">
	<div class="col-xs-4 col-md-4" >
		<a href="" class="thumbnail" data-toggle="modal" data-target=".bs-example-modal-lg">
			<img src="<?php echo base_url($image_path . $image->image);?>" alt="" />
		</a>
	</div>
	
	<div class="col-xs-8 col-md-8">		
		<table class="table">
			<tr>
				<th style="font-size:20px;"><?php echo $image->title; ?></th>
				<td style="text-align:right;">
					<button class="btn btn-primary" data-toggle="modal" data-target=".bs-example-modal-lg">View Full Size</button>
					<a class="btn btn-danger"><i class="glyphicon glyphicon-remove"></i> Remove Image</a>
				</td>
			</tr>
			
			<tr>
				<td colspan="2"><?php echo $image->caption; ?></td>
			</tr>
		</table>
		
		<table class="table">
			<tr>
				<th style="width: 150px;">Project Image: </th>
				<td><a href="<?php echo base_url('project/view/'. $image->project_id); ?>"><?php echo $image->project_name; ?></a></td>
			</tr>
			
			<tr>
				<th>Posted by: </th>
				<td><a href="<?php echo base_url('user/view/'. $image->user_id); ?>"><?php echo $image->user_name; ?></a></td>
			</tr>
			
			<tr>
				<th>Em: </th>
				<td><?php echo fdatetime($image->date, "/"); ?></td>
			</tr>
			
			<tr><td></td><td></td></tr>
		</table>
	</div>
	
	<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<img src="<?php echo base_url($image_path . $image->image);?>" />
			</div>
		</div>
	</div>
	
</div>

<hr>

<div class="comments">

	<table width="100%" id="table_comments">
		<tr>
			<th></th>
			<th></th>
			<th></th>
			<th></th>
		</tr>
		<?php foreach($comments->result() as $comment){ ?>
		<tr>
			<td style="width:60px" valign="top">
				<a href="<?php echo base_url('user/view/'. $comment->user_id); ?>" class="thumbnail" style="background:<?php echo $comment->user_color; ?>;">
					<img src="<?php echo base_url('assets/images/users/'. $comment->user_image); ?>" alt="">
				</a>
			</td>
			
			<td valign="top">
				<span>
					<strong style="margin-left:10px;"><a href="<?php echo base_url('user/view/'. $comment->user_id); ?>"><?php echo $comment->name; ?></a>:</strong>
					<p style="margin-left:10px;"><?php echo $comment->comment; ?></p>
				</span>
			</td>
			
			<td valign="top" style="width:130px">
				<p><?php echo fdatetime($comment->date, "/"); ?></p>
			</td>
			
			<td valign="top" style="width:30px">
				<?php if(($comment->user_id == $this->session->userdata('id')) OR ($this->session->userdata('role') == 'Administrator') OR ($this->session->userdata('role') == 'Manager')){ ?>
				<button class="btn btn-danger btn-xs" id="delete_comment_<?php echo $comment->comment_id; ?>"><i class="glyphicon glyphicon-remove"></i></button>
				<?php } ?>
			</td>
		</tr>

		<?php if(($comment->user_id == $this->session->userdata('id')) OR ($this->session->userdata('role') == 'Administrator') OR ($this->session->userdata('role') == 'Manager')){ ?>
		
		<script>
			$("#delete_comment_<?php echo $comment->comment_id; ?>").click(function () {
				reset();
				alertify.confirm("Are you sure you want to delete this comment?", function (e) {
					if (e) {
						var url = '<?php echo base_url('image/delete_comment/'. $comment->image_id .'/'.$comment->comment_id); ?>';
					
						if (url) {
							window.location = url;
						}
						
					} else {
						alertify.error("Comment not removed.");
					}
				});
				return false;
			});
		</script>
		<?php } ?>

		<?php } ?>
	
	</table>
	
	<?php if($comments->num_rows() > 5){ ?>
		<div id="pagination_comments" style="display:inline;"></div>

		<script>
		var pager = new Pager('table_comments', 5);
		pager.init();
		pager.showPageNav('pager', 'pagination_comments');
		pager.showPage(1);
		</script>
	<?php } ?>
	
	
	<div class="form_comment">
		<form  action="<?php echo base_url('image/insert_comment') ?>" method="post" name="form-comment" class="form-comment">
			<table width="100%">
				<tr>
					<td width="90%">
						<textarea class="form-control" rows="2" class="col-lg-12" name="comment" id="comment"><?php echo set_value('comment'); ?></textarea>
						<input type="hidden" name="image_id" value="<?php echo $image->image_id ?>"/>
					</td>
					
					<td align="middle" width="10%">
						<button type="submit" class="btn btn-primary btn-lg">Comment</button>
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
	$("#comment").jqte({ol: false, ul: false, format: false});
	
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
</script>

