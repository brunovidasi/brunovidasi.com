<link type="text/css" href="<?php echo base_url('assets/js/paginacao/paging.css'); ?>" rel="stylesheet" />
<script type="text/javascript" src="<?php echo base_url('assets/js/paginacao/paging.js'); ?>"></script>
<script src="<?php echo base_url('assets/js/texteditor/jquery-te-1.4.0.min.js');?>" type="text/javascript"></script>
<link rel="stylesheet" href="<?php echo base_url('assets/js/texteditor/jquery-te-1.4.0.css');?>" type="text/css">

<p class="titulo_pagina" style="">Imagens do Projeto <?php echo $imagem->nome_projeto; ?></p> 

<br>

<?php
	require('application/views/includes/mensagem.php');
	if(validation_errors() != ''){
	echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	}
?>

<div class="row">
	<div class="col-xs-4 col-md-4" >
		<a href="" class="thumbnail" data-toggle="modal" data-target=".bs-example-modal-lg">
			<img src="<?php echo base_url($caminho_imagem . $imagem->imagem);?>" alt="" />
		</a>
	</div>
	
	<div class="col-xs-8 col-md-8">		
		<table class="table">
			<tr>
				<th style="font-size:20px;"><?php echo $imagem->titulo; ?></th>
				<td style="text-align:right;">
					<button class="btn btn-primary" data-toggle="modal" data-target=".bs-example-modal-lg">Visualizar Imagem Grande</button>
					<a class="btn btn-danger"><i class="glyphicon glyphicon-remove"></i> Remover Imagem</a>
				</td>
			</tr>
			
			<tr>
				<td colspan="2"><?php echo $imagem->legenda; ?></td>
			</tr>
		</table>
		
		<table class="table">
			<tr>
				<th style="width: 150px;">Imagem do Projeto: </th>
				<td><a href="<?php echo base_url('projeto/visualizar/'. $imagem->idprojeto); ?>"><?php echo $imagem->nome_projeto; ?></a></td>
			</tr>
			
			<tr>
				<th>Postado por: </th>
				<td><a href="<?php echo base_url('usuario/visualizar/'. $imagem->idusuario); ?>"><?php echo $imagem->nome_usuario; ?></a></td>
			</tr>
			
			<tr>
				<th>Em: </th>
				<td><?php echo fdatetime($imagem->data, "/"); ?></td>
			</tr>
			
			<tr><td></td><td></td></tr>
		</table>
	</div>
	
	<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<img src="<?php echo base_url($caminho_imagem . $imagem->imagem);?>" />
			</div>
		</div>
	</div>
	
</div>

<hr>

<div class="comentarios">

	<table width="100%" id="tabela_comentarios">
		<tr>
			<th></th>
			<th></th>
			<th></th>
			<th></th>
		</tr>
		<?php foreach($comentarios->result() as $comentario){ ?>
		<tr>
			<td style="width:60px" valign="top">
				<a href="<?php echo base_url('usuario/visualizar/'. $comentario->idusuario); ?>" class="thumbnail" style="background:<?php echo $comentario->cor_usuario; ?>;">
					<img src="<?php echo base_url('assets/images/usuarios/'. $comentario->imagem_usuario); ?>" alt="">
				</a>
			</td>
			
			<td valign="top">
				<span>
					<strong style="margin-left:10px;"><a href="<?php echo base_url('usuario/visualizar/'. $comentario->idusuario); ?>"><?php echo $comentario->nome; ?></a>:</strong>
					<p style="margin-left:10px;"><?php echo $comentario->comentario; ?></p>
				</span>
			</td>
			
			<td valign="top" style="width:130px">
				<p><?php echo fdatetime($comentario->data, "/"); ?></p>
			</td>
			
			<td valign="top" style="width:30px">
				<?php if(($comentario->idusuario == $this->session->userdata('id')) OR ($this->session->userdata('cargo') == 'administrador') OR ($this->session->userdata('cargo') == 'gerente')){ ?>
				<button class="btn btn-danger btn-xs" id="excluir_comentario_<?php echo $comentario->idcomentario; ?>"><i class="glyphicon glyphicon-remove"></i></button>
				<?php } ?>
			</td>
		</tr>

		<?php if(($comentario->idusuario == $this->session->userdata('id')) OR ($this->session->userdata('cargo') == 'administrador') OR ($this->session->userdata('cargo') == 'gerente')){ ?>
		
		<script>
			$("#excluir_comentario_<?php echo $comentario->idcomentario; ?>").click(function () {
				reset();
				alertify.confirm("Tem certeza que deseja deletar este comentário?", function (e) {
					if (e) {
						var url = '<?php echo base_url('imagem/deletar_comentario/'. $comentario->idimagem .'/'.$comentario->idcomentario); ?>';
					
						if (url) {
							window.location = url;
						}
						
					} else {
						alertify.error("Projeto não removido.");
					}
				});
				return false;
			});
		</script>
		<?php } ?>

		<?php } ?>
	
	</table>
	
	<?php if($comentarios->num_rows() > 5){ ?>
		<div id="paginacao_comentarios" style="display:inline;"></div>

		<script>
		var pager = new Pager('tabela_comentarios', 5);
		pager.init();
		pager.showPageNav('pager', 'paginacao_comentarios');
		pager.showPage(1);
		</script>
	<?php } ?>
	
	
	<div class="formulario_comentario">
		<form  action="<?php echo base_url('imagem/inserir_comentario') ?>" method="post" name="form-comentario" class="form-comentario">
			<table width="100%">
				<tr>
					<td width="90%">
						<textarea class="form-control" rows="2" class="col-lg-12" name="comentario" id="comentario"><?php echo set_value('comentario'); ?></textarea>
						<input type="hidden" name="idimagem" value="<?php echo $imagem->idimagem ?>"/>
					</td>
					
					<td align="middle" width="10%">
						<button type="submit" class="btn btn-primary btn-lg">Comentar</button>
					</td>
				</tr>
			</table>
		</form>
	</div>
	
</div>

<div class="outras_imagens">
	<br><br>
</div>

<script>
	$("#comentario").jqte({ol: false, ul: false, format: false});
	
	reset = function () {
	$("toggleCSS").href = "<?php echo base_url('assets/js/alertify/themes/alertify.ptime.css'); ?>";
		alertify.set({
			labels : {
				ok     : "Deletar",
				cancel : "Não Deletar"
			},
			delay : 5000,
			buttonReverse : false,
			buttonFocus   : "ok"
		});
	};
</script>

