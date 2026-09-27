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
			
			<a class="btn btn-block btn-primary" href="<?php echo base_url('mensagem/nova'); ?>"><i class="glyphicon glyphicon-pencil"></i> Nova Mensagem</a>
			<div style="margin-top: 15px;">
				<ul class="nav nav-pills nav-stacked">
					<li class="header"></li>

					<li class="active">
						<a href="#"><i class="glyphicon glyphicon-inbox"></i> Caixa de Entrada 
						<?php
							if($mensagens_nao_lidas->num_rows() > 0){
								echo '<span class="badge">'. $mensagens_nao_lidas->num_rows() .'</span>';
							}
						?>
						</a>
					</li>

					<li>
						<a href="<?php echo base_url('mensagem/rascunhos'); ?>">
							<i class="glyphicon glyphicon-edit"></i> Rascunhos
						</a>
					</li>

					<li>
						<a href="<?php echo base_url('mensagem/enviadas'); ?>">
							<i class="glyphicon glyphicon-share-alt"></i> Enviadas
						</a>
					</li>

					<li>
						<a href="<?php echo base_url('mensagem/favoritas'); ?>">
							<i class="glyphicon glyphicon-star"></i> Favoritas
						</a>
					</li>

					<li>
						<a href="<?php echo base_url('mensagem/lixo'); ?>">
							<i class="glyphicon glyphicon-trash"></i> Lixeira
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
							Ação <span class="caret"></span>
						</button>
						<ul class="dropdown-menu" role="menu">
							<li><a href="#">Marcar como lida</a></li>
							<li><a href="#">Marcar como não lida</a></li>
							<li class="divider"></li>
							<li><a href="#">Mover para lixeira</a></li>
							<li class="divider"></li>
							<li><a href="#">Apagar</a></li>
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
					
					<?php foreach($mensagens->result() as $mensagem){ 
						$b = "";
						$end_b = "";

						if(!$mensagem->lida){
							$b = '<b>';
							$end_b = '</b>';
						}
					?>

					<tr class="<?php if($mensagem->lida) echo 'unread';?>">
					
						<td class="small-col">
							<input type="checkbox" style="position: absolute; opacity: 0;">
						</td>
						
						<td class="small-col">
							<i class="glyphicon <?php if($mensagem->favorito){ echo 'glyphicon-star'; }else{ echo 'glyphicon-star-empty'; };?>" favorito="<?php if($mensagem->favorito){ echo 'true'; }else{ echo 'false';}?>"></i>
						</td>
						
						<td class="subject">
							<?php echo $b; ?><a href="<?php echo base_url('mensagem/visualizar/'.$mensagem->idmensagem); ?>"><?php echo $mensagem->assunto; ?></a> <?php echo $end_b; ?>
						</td>

						<td class="name">
							<?php echo $b; ?><a href="<?php echo base_url('usuario/visualizar/'.$mensagem->id_usuario_from); ?>"><?php echo fnome($mensagem->nome); ?></a><?php echo $end_b; ?>
						</td>
						
						<td class="time">

							<?php 

							list($data, $hora) = explode(" ", $mensagem->data_envio);
							
							if($data == date('Y-m-d')){
								$hora = explode(":", $hora);
								$alt = $hora[0] . ":" . $hora[1];
							}else{
								$alt = fdatetime($mensagem->data_envio, "/");
							} ?>
							<strong><span id="mensagem_<?php echo $mensagem->idmensagem; ?>" title="<?php echo $alt; ?>"></span></strong>

						<script>
					    	var momento = moment("<?php echo $mensagem->data_envio; ?>", "YYYY/MM/DD HH:mm:ss").startOf("second").fromNow();
			 				$("#mensagem_<?php echo $mensagem->idmensagem; ?>").html(momento);
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

			var classe_atual = $(this).attr("favorito");
		    var pag = "<?php echo base_url('mensagem/marcar_como_favorita/'); ?>";
		    var idmensagem = "<?php echo isset($mensagem) ? $mensagem->idmensagem : ''; ?>";

		    if(classe_atual == "true"){
		        $(this).attr("class", "glyphicon glyphicon-star-empty");
		        $(this).attr("favorito", "false");
		        var booleano = false;
		    }else{
				$(this).attr("class", "glyphicon glyphicon-star");
				$(this).attr("favorito", "true");
				var booleano = true;
		    }
			
		    $.post(pag,{
		        idmensagem: idmensagem, 
		        booleano: booleano
		    });

		}

	});

});
</script>