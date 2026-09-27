<p class="page_title" style="float:left;">Create Project Report</p> <br><br><br>

<?php
	require('application/views/includes/message.php');
	if(validation_errors() != '')
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";

?>

<div class="" style="float:right; margin-bottom:10px; width:100%;">
	
	<form action="<?php echo base_url('report/generate') ?>" method="post" name="form1" class="form1">
	
	<table width="100%" border="0" cellpadding="3" cellspacing="3" class="table_main" style="padding:10px; margin-top: 100px;">
	
		<tr>
			<td width="100%" valign="middle" align="center">
				<div class="col-lg-6 col-md-8 col-sm-8 inputs" style="float: none;">
					<select name="client_id" class="form-control" id="client_id">
						<option hidden>Select the client</option>
						<?php
						foreach($clients->result() as $client){
							$selected = "";
							if(set_value('client_id', $client_id) == $client->client_id){
								$selected = 'selected="selected"';
							}
							echo '<option value="'. $client->client_id .'" '. $selected .'>'. $client->name .'</option>';
						}						
						?>
					</select>
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="100%" valign="middle" align="center">
				<div class="col-lg-6 col-md-8 col-sm-8 inputs" style="float: none;">
					<select name="project_id" class="form-control" id="project_id" style="display:none;">
						<option value=""></option>
						<?php
							$cid = set_value('client_id', $client_id);
							if(empty($cid)){
								echo '<option hidden>Select the client</option>';
							}else{
								echo $project_options;
							}
						?>
					</select>
				</div>
			</td>
		</tr>
		
		<tr>
			<td align="center"><button name="submit" type="submit" class="btn btn-primary disabled" id="submit"><i class="glyphicon glyphicon-file"></i> Create Report</button></td>
		</tr>
	
	</table>
	
	</form>
	
</div> <br>

<script>
jQuery(document).ready(function($){
	$("select[name=client_id]").change(function(){
		$("select[name=project_id]").show(500);
		$("select[name=project_id]").html('<option value="0">Loading...</option>');
		
		$.post("<?php print base_url("time_entry/get_projects/"); ?>", {client_id:$(this).val()}, function(response){
			$("select[name=project_id]").html(response);
		});
	});

	$("select[name=project_id]").change(function(){
		$("#submit").removeClass('disabled');
	});
});
</script>