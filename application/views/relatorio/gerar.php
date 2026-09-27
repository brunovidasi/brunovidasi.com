<form action="<?php echo base_url('relatorio/gerar_pdf/') ?>" method="post" name="form1" class="form1" target="_blank">
	
	<p class="titulo_pagina" style="float:left;"><?php echo $relatorio->titulo; ?></p>
	
	<?php if($relatorio->titulo != "ERRO"){ ?>
	<div style="float:right; margin-top: 20px; margin-right: 12px;">
		<button name="submit" type="submit" class="btn btn-lg btn-danger" id="submit" value="Enviar Relatório" ><i class="glyphicon glyphicon-download-alt"></i> Exportar para PDF</a></button>
	</div>
	<?php } ?>
	
	<?php
		require('application/views/includes/mensagem.php');
		if(validation_errors() != ''){
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
		}
	?>
	
    <table width="100%" border="0" cellpadding="3" cellspacing="3" class="tabela_principal" style="">
		
		<tr>
			<td style="width:100%;" valign="middle">
				<div class="col-lg-4 col-md-8 col-sm-8 inputs" style="width:100%;">
					<div class="gerando">
						Gerando Relatório... 
						<br /><br /><img src="<?php echo base_url('assets/images/sistema/ajax_loader.gif'); ?>" />
					</div>
					<textarea name="relatorio" id="relatorio" class="form-control relatorio" style="display:none;">
						<?php  echo set_value('relatorio', $relatorio->html); ?>
					</textarea>


				</div>
			</td>
		</tr>
		
		<tr>
			<td style="padding-left:14px">
				<input name="idusuario" type="hidden" value="<?php echo $this->session->userdata('id'); ?>" />
				<input name="titulo" type="hidden" value="<?php echo $relatorio->titulo; ?>" />
				
			</td>
		</tr>
		
	</table>
	
</form>
<br>

<script src="<?php echo base_url('assets/js/jquery.maskedinput.js');?>"></script>
<script src="<?php echo base_url('assets/js/ckeditor/ckeditor.js'); ?>"></script>

<script>
	CKEDITOR.replace("relatorio", {
		customConfig: '<?php echo base_url('assets/js/ckeditor/custom/editor_chamada_config.js'); ?>',
		height: '550px',
		width: '100%',

	});

	$(document).ready(function(){
		$(".gerando").slideUp(1000);
	});
</script>
     