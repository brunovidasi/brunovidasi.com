<!-- CSS -->
<link href="<?php echo base_url('assets/css/bootstrap.css'); ?>" rel="stylesheet">
<link href="<?php echo base_url('assets/css/modal.css'); ?>" rel="stylesheet">
<link href="<?php echo base_url('assets/js/jcrop/css/jquery.Jcrop.css'); ?>" rel="stylesheet" type="text/css" />

<!-- JS -->
<script src="<?php echo base_url('assets/js/jquery.js'); ?>" type="text/javascript"></script>
<script src="<?php echo base_url('assets/js/bootstrap.js'); ?>"></script> 
<script src="<?php echo base_url('assets/js/jcrop/js/jquery.Jcrop.js'); ?>" type="text/javascript"></script>
<script charset="UTF-8" src="<?php echo base_url('assets/js/validate_form.js'); ?>" type="text/javascript"></script>

<script language="Javascript">

jQuery(window).load(function(){
	jQuery('#crop').Jcrop({
			setSelect:   [ <?php echo "100, 50, ".$dimensions->target_w.", ".$dimensions->target_h;?> ],
			onChange: updateCoords,
			onSelect: updateCoords,
			aspectRatio: <?php echo $dimensions->aspect_ratio;?>
	});
});

function updateCoords(coords){

		if (parseInt(coords.w) > 0)
		{
				var rx = <?php echo $dimensions->target_w;?> / coords.w;
				var ry = <?php echo $dimensions->target_h;?> / coords.h;

	$('#x').val(coords.x);
	$('#y').val(coords.y);
	$('#w').val(coords.w);
	$('#h').val(coords.h);

				jQuery('#preview').css({
						width: Math.round(rx * <?php echo $dimensions->imgW; ?>) + 'px',
						height: Math.round(ry * <?php echo $dimensions->imgH; ?>) + 'px',
						marginLeft: '-' + Math.round(rx * coords.x) + 'px',
						marginTop: '-' + Math.round(ry * coords.y) + 'px'
				});
		}
}
</script>

<style>
#uplcontemx {
	<?php if($dimensions->imgW < 730){ echo "width:730;";} else{ ?>
    width:<?php echo (70+$dimensions->imgW);?>px!important;
	<?php }?>
	<?php if($dimensions->imgH < 400){ echo "height:560px;";} else{ ?>
    height:<?php echo (250+ $dimensions->imgH); ?>px!important;
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
	<?php if($dimensions->imgW < 690){ echo "width:690;";} else{ ?>
    width:<?php echo ($dimensions->imgW-40);?>px!important;
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
    height:<?php echo $dimensions->imgH; ?>px!important;
    width:<?php echo $dimensions->imgW; ?>px!important;
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
        <h4>Crop Image</h4>
        <div class="clear"></div>
    </div>
    <form action="<?php echo base_url('upload/upload_crop'); ?>" method="post" id="form_upload" enctype="multipart/form-data">
    <table class="form">
        <tr>
            <td>
				<div id="upload_area_cropethumb">
				<div id="upload_area_crop" >
					<div id="aimagecrop">
						<img src="<?php echo base_url(str_replace(".", "/", $parms->source).$file_name); ?>" id="crop" width=<?= $dimensions->imgW ?> height=<?= $dimensions->imgH ?> />
					</div>
				</div>                 
				</div>

				<input type="hidden" name="image_type" value="<?php echo $dimensions->image_type;?>" />
				<input type="hidden" name="scale" value="<?php echo $dimensions->scale;?>" />
				<input type="hidden" name="file_name" value="<?php echo $file_name;?>" />
				
				<input type="hidden" id="source" name="source" value="<?php echo set_value('source', $parms->source); ?>"  />
				<input type="hidden" id="destination" name="destination" value="<?php echo set_value('destination', $parms->destination); ?>"  />
				<input type="hidden" id="height" name="height" value="<?php echo set_value('height', $parms->height); ?>"  />
				<input type="hidden" id="width" name="width" value="<?php echo set_value('width', $parms->width); ?>"  />
				<input type="hidden" id="original_name" name="original_name" value="<?php echo $original_file_name; ?>"  />

                <input type="hidden" id="x" name="ax"  />
				<input type="hidden" id="y" name="ay"  />
				<input type="hidden" id="w" name="w" />
				<input type="hidden" id="h" name="h"  />

            </td>
        </tr>
        <tr>               
            <td><br><div style="text-align:left;"><button type="submit" class="btn btn-primary">Done</button></div></td>
        </tr>
    </table>
    </form>
</div>

