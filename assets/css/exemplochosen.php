<!-- CADASTRAR --> 
<div class="control-group-margem">
	<label class="control-label" for="form-field-1"><strong>Tags:</strong></label>
	<div class="controls">
		<select name="idtag[]" id="tag" data-placeholder="Selecione as tags..." class="chosen-select span5" multiple >
			<option value=""></option>	
			<?php
			foreach($tags->result() as $row){
				print '<option value="'. $row->idtag .'">'. $row->tag .'</option>';
				
				if(set_select('idtag[]', $row->idtag) != ''){
					print "<script>$(\"#tag option[value=". $row->idtag ."]\").attr('selected', 'selected');</script>";
				}
			}
			?>
		</select>
	</div>
</div>	
				
<!-- EDITAR-->
<div class="control-group-margem">
	<label class="control-label" for="form-field-1"><strong>Tags:</strong></label>
	<div class="controls">
		<select name="idtag[]" id="tag" data-placeholder="Selecione as tags..." class="chosen-select span5" multiple >
			<option value=""></option>	
			<?php
			foreach($tags->result() as $row){
				print '<option value="'. $row->idtag .'">'. $row->tag .'</option>';
				
				if(set_select('idtag[]', $row->idtag) != ''){
					print "<script>$(\"#tag option[value=". $row->idtag ."]\").attr('selected', 'selected');</script>";
				}else{
					
					
				}
			}
			
			if($tags->num_rows() > 0 AND set_value('idtag[]') == ''){
				foreach($tags_projeto->result() as $tg){
					foreach($tags->result() as $row){
						if($row->idtag == $tg->idtag){
							print "<script>$(\"#tag option[value=". $row->idtag ."]\").attr('selected', 'selected');</script>";
						}
					}
				}
			}
			?>
		</select>
	</div>
</div>