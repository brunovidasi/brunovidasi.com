
<div id="modal" style="padding:20px;">
    <div class="header">
        <h4>Escolha a Imagem</h4>
        <div class="clear"></div>
    </div>
    <div id="erros_validacao_form" style="<?php echo (validation_errors()) ? "display:block;" : "display:none;"; ?>">
        <ul id="erros_valid">
            <?php echo validation_errors('<li>', '</li>'); ?>
        </ul>
    </div>

    <?php
	require('application/views/includes/mensagem.php');
	?>
	<div class="form-style">
		<form action="<?php echo base_url('upload/salva_upload'); ?>" method="post" id="form_upload" onsubmit="return avalia_form(this)" enctype="multipart/form-data">
			<input type="hidden" id="origem" name="origem" value="<?php echo set_value('origem', $parms->origem); ?>"  />
			<input type="hidden" id="destino" name="destino" value="<?php echo set_value('destino', $parms->destino); ?>"  />
			<input type="hidden" id="altura" name="altura" value="<?php echo set_value('altura', $parms->altura); ?>"  />
			<input type="hidden" id="largura" name="largura" value="<?php echo set_value('largura', $parms->largura); ?>"  />
			<ul>
				<li>
					<label>Imagem: </label>
					<input id="foto" type="file" valida="true"  text_valida="Imagem" name="foto" size="50" />
				</li>  
				<li>    
					<label></label>     
					<div style="text-align:right;"><button type="submit" class="btn btn-primary">Enviar</button></div>
				</li>   
			</ul>
		</form>
	</div>
</div>

<!-- CSS -->
<link href="<?php echo base_url('assets/css/bootstrap.css'); ?>" rel="stylesheet">
<link href="<?php echo base_url('assets/css/modal.css'); ?>" rel="stylesheet">

<!-- JS -->
<script src="<?php echo base_url('assets/js/jquery.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/bootstrap.js'); ?>"></script> 
<script src="<?php echo base_url('assets/js/jcrop/js/jquery.Jcrop.js'); ?>" type="text/javascript"></script>
<script charset="UTF-8" src="<?php echo base_url('assets/js/valida_form.js'); ?>" type="text/javascript"></script>
