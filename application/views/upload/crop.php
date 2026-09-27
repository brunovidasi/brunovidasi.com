<!-- CSS -->
<link href="<?php echo base_url('assets/css/bootstrap.css'); ?>" rel="stylesheet">
<link href="<?php echo base_url('assets/css/modal.css'); ?>" rel="stylesheet">
<link href="<?php echo base_url('assets/js/jcrop/css/jquery.Jcrop.css'); ?>" rel="stylesheet" type="text/css" />

<!-- JS -->
<script src="<?php echo base_url('assets/js/jquery.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/bootstrap.js'); ?>"></script> 
<script src="<?php echo base_url('assets/js/jcrop/js/jquery.Jcrop.js'); ?>" type="text/javascript"></script>
<script charset="UTF-8" src="<?php echo base_url('assets/js/valida_form.js'); ?>" type="text/javascript"></script>

<script language="Javascript">

jQuery(window).load(function(){
	jQuery('#crop').Jcrop({
			setSelect:   [ <?php echo "100, 50, ".$medidas->tam_w.", ".$medidas->tam_h;?> ],
			onChange: updateCoords,
			onSelect: updateCoords,
			aspectRatio: <?php echo $medidas->raito;?>
	});
});

function updateCoords(coords){

		if (parseInt(coords.w) > 0)
		{
				var rx = <?php echo $medidas->tam_w;?> / coords.w;
				var ry = <?php echo $medidas->tam_h;?> / coords.h;

	$('#x').val(coords.x);
	$('#y').val(coords.y);
	$('#w').val(coords.w);
	$('#h').val(coords.h);

				jQuery('#preview').css({
						width: Math.round(rx * <?php echo $medidas->imgL; ?>) + 'px',
						height: Math.round(ry * <?php echo $medidas->imgA; ?>) + 'px',
						marginLeft: '-' + Math.round(rx * coords.x) + 'px',
						marginTop: '-' + Math.round(ry * coords.y) + 'px'
				});
		}
}
</script>

<style>
#uplcontemx {
	<?php if($medidas->imgL < 730){ echo "width:730;";} else{ ?>
    width:<?php echo (70+$medidas->imgL);?>px!important;
	<?php }?>
	<?php if($medidas->imgA < 400){ echo "height:560px;";} else{ ?>
    height:<?php echo (250+ $medidas->imgA); ?>px!important;
	<?php } ?>
	text-align:left;
	background:#fff;
}

#uplcontem {
	width:730px;
	text-align:left;
	background:#fff;
}

#uplareainfox {
	background:url("backinfo.jpg") no-repeat scroll 0 0 transparent;
	height:45px;
	margin:7px 7px 0!important;
	text-align:center;
	<?php if($medidas->imgL < 690){ echo "width:690;";} else{ ?>
    width:<?php echo ($medidas->imgL-40);?>px!important;
	<?php } ?>
}

#uplareainfo {
	background:url("bginfo.jpg") no-repeat scroll 0 0 transparent;
	height:45px;
	margin:7px 7px 0!important;
	text-align:center;
	width:690px!important;
}


#upltxtinfo {
	margin:7px 7px 0!important;
	text-align:left;
	color:#fff;
	font-family:"Trebuchet MS", Verdana, Arial;
}

#uplareaprever{
	margin:7px 7px 0!important;
	text-align:center;
	width:700px;
	height:400px;
	margin-top:5px;
	margin-bottom:0px;
}

#upload_area_crop {
    background:none repeat scroll 0 0 #DFDFDF;
    border:2px solid #FFFFFF;
    float:left;
    height:<?php echo $medidas->imgA; ?>px!important;
    width:<?php echo $medidas->imgL; ?>px!important;
	text-align:left;
}

#uplrodape{
	width:695px;
	height:57px;
	margin:0 auto;
	text-align:center;
}	

</style>

<div id="modal">
	<div class="header">
        <h4>Recortar Imagem</h4>
        <div class="clear"></div>
    </div>
    <form action="<?php echo base_url('upload/upload_crop'); ?>" method="post" id="form_upload" enctype="multipart/form-data">
    <table class="form">
        <tr>
            <td>
				<div id="upload_area_cropethumb">
				<div id="upload_area_crop" >
					<div id="aimagemcrop">
						<img src="<?php echo base_url(str_replace(".", "/", $parms->origem).$nome_arquivo); ?>" id="crop" width=<?= $medidas->imgL ?> height=<?= $medidas->imgA ?> />
					</div>
				</div>                 
				</div>

				<input type="hidden" name="tipo_imagem" value="<?php echo $medidas->tipo_imagem;?>" />
				<input type="hidden" name="tipo" value="<?php echo $medidas->proporcao;?>" />
				<input type="hidden" name="nome_arquivo" value="<?php echo $nome_arquivo;?>" />
				
				<input type="hidden" id="origem" name="origem" value="<?php echo set_value('origem', $parms->origem); ?>"  />
				<input type="hidden" id="destino" name="destino" value="<?php echo set_value('destino', $parms->destino); ?>"  />
				<input type="hidden" id="altura" name="altura" value="<?php echo set_value('altura', $parms->altura); ?>"  />
				<input type="hidden" id="largura" name="largura" value="<?php echo set_value('largura', $parms->largura); ?>"  />
				<input type="hidden" id="nome_original" name="nome_original" value="<?php echo $nome_arquivo_original; ?>"  />

                <input type="hidden" id="x" name="ax"  />
				<input type="hidden" id="y" name="ay"  />
				<input type="hidden" id="w" name="w" />
				<input type="hidden" id="h" name="h"  />

            </td>
        </tr>
        <tr>               
            <td><br><div style="text-align:left;"><button type="submit" class="btn btn-primary">Concluir</button></div></td>
        </tr>
    </table>
    </form>
</div>

