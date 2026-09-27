<p class="page_title" style="">Have questions about Programmer Time? </p> 

<!--<div class="" style="float:right; margin-bottom:10px; width:100%;">
	
	<form action="<?php print base_url('client/term'); ?>" method="post" name="filter_form" id="filter_form" class="form-inline" style="display:inline; float:right; margin-top:26px; width:550px;">
		<input type="text" name="term" class="form-control" value="<?php print $this->session->userdata('term'); ?>" style="width:500px; float:left;"/>
		<button type="submit" title="Search" class="btn btn-primary" style="float:right; margin-right: 5px;"><i class='glyphicon glyphicon-search'></i></button>
	</form>
	
</div> <br>-->

<?php

	$can_create_client = $this->session->userdata('can_create_client');
	$can_edit_client = $this->session->userdata('can_edit_client');

	require('application/views/includes/message.php');
	if(validation_errors() != ''){
	echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	}
?>

<div >
<div class="panel-group" id="accordion">

<?php
	if($help_articles->num_rows() == 0){
		echo 'There are no help articles yet.';
	}
	
	foreach($help_articles->result() as $help){
		
	?>
	
	<div class="col-lg-12">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h4 class="panel-title" style="font-size: 13px;"><a data-toggle="collapse" data-parent="#accordion" href="#collapse<?php echo $help->help_id ?>"><?php echo $help->title; ?></a></h4>
			</div>
			<div id="collapse<?php echo $help->help_id; ?>" class="panel-collapse collapse">
				<div class="panel-body">
						<?php echo $help->text; ?>
						
						<?php if($this->session->userdata('access_level') == 1){ ?>
							<hr>
							<a href="<?php echo base_url('help/edit/'.$help->help_id);?>" class="btn btn-warning btn-xs">Edit</a>
							<a href="<?php echo base_url('help/delete/'.$help->help_id);?>" class="btn btn-danger btn-xs">Delete</a>
						<?php }?>
				</div>
			</div>
		</div><br>
	</div>
	
	<?php } ?>
</div>