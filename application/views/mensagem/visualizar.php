<script src="<?php echo base_url('assets/js/texteditor/jquery-te-1.4.0.min.js');?>" type="text/javascript"></script>
<link rel="stylesheet" href="<?php echo base_url('assets/js/texteditor/jquery-te-1.4.0.css');?>" type="text/css">
<script type="text/javascript" src="<?php echo base_url('assets/js/moment.js'); ?>"></script>

<br>

<?php
	require('application/views/includes/mensagem.php');
	if(validation_errors() != ''){
	echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	}
?>


	<div class="col-md-3 col-sm-4" style="margin-top:0px;">
		<div class="box-header"></div>
		
		<a class="btn btn-block btn-primary" href="<?php echo base_url('mensagem/nova'); ?>" ><i class="glyphicon glyphicon-pencil"></i> Nova Mensagem</a>
		<div style="margin-top: 15px;">

			

			<table class="table">
				<tr>
					<td style="text-align:center;" colspan="2">
						<i class="glyphicon <?php if($mensagem->favorito){ echo 'glyphicon-star'; }else{ echo 'glyphicon-star-empty'; };?>" style="color: #f39c12; cursor: pointer; font-size: 20px;"></i>
					</td>
				</tr>

				<tr><th>Data:</th><td><?php echo fdatetime($mensagem->data_envio, "/"); ?></td></tr>

				<tr><th>De:</th><td><?php echo $mensagem->id_usuario_from; ?></td></tr>

				<tr><th>Para:</th><td><?php echo $mensagem->idusuario_to; ?></td></tr>

				<tr>
					<th>Projeto:</th>
					<td>
						<a href="<?php echo base_url('projeto/visualizar/'.$mensagem->idprojeto); ?>"><?php echo '# '.$mensagem->idprojeto .' '.'nome do projeto'; ?></a>
					</td>
				</tr>

				<tr>
					<th>Respostas:</th><td><?php echo $msg_assoc->num_rows() .' respostas.'; ?></a></td>
				</tr>

				<tr>
					<td colspan="2" style="text-align:center;">
						<a href="<?php echo base_url('mensagem/marcar_como_lixo/'.$mensagem->idmensagem.'/1'); ?>" style="width:100%;" class="btn btn-danger">
							<i class="glyphicon glyphicon-trash"></i> Enviar para a lixeira
						</a>
					</td>
				</tr>

				<tr>
					<td colspan="2" style="text-align:center;">
						<a href="<?php echo base_url('mensagem/marcar_como_nao_lida/'.$mensagem->idmensagem.'/1'); ?>" style="width:100%;" style="width:100%;" class="btn btn-warning">
							Marcar como não lida
						</a>
					</td>
				</tr>


			</table>
		</div>
	</div>


<div class="mensagens col-md-9 col-sm-8">

	<table width="100%" id="tabela_mensagem">
		<tr>
			<th></th>
			<th></th>
			<th></th>
			<th></th>
		</tr>

		<tr>
			<td style="width:60px" valign="top">
				<a href="<?php echo base_url('usuario/visualizar/'. $mensagem->id_usuario_from); ?>" class="thumbnail">
					<img src="<?php echo base_url('assets/images/usuarios/'. $mensagem->imagem); ?>" alt="">
				</a>
			</td>
			
			<td valign="top">
				<span>
					<strong style="margin-left:10px;"><a href="<?php echo base_url('usuario/visualizar/'. $mensagem->id_usuario_from); ?>"><?php echo fnome($mensagem->nome); ?></a>:</strong>
					<p style="margin-left:10px;"><?php echo $mensagem->mensagem; ?></p>
				</span>
			</td>
			
			<td valign="top" style="width:130px" title="<?php echo fdatetime($mensagem->data_envio, "/"); ?>">
				<b><p id="mensagem_<?php echo $mensagem->idmensagem; ?>"><?php echo fdatetime($mensagem->data_envio, "/"); ?></p></b>
			</td>
			
			<td valign="top" style="width:30px">
				<?php if(($mensagem->idusuario == $this->session->userdata('id')) OR ($this->session->userdata('cargo') == 'administrador') OR ($this->session->userdata('cargo') == 'gerente')){ ?>
				<button class="btn btn-danger btn-xs" id="excluir_mensagem_<?php echo $mensagem->idmensagem; ?>"><i class="glyphicon glyphicon-remove"></i></button>
				<?php } ?>
			</td>
		</tr>
		
		<script>
			$("#excluir_mensagem_<?php echo $mensagem->idmensagem; ?>").click(function () {
				reset();
				alertify.confirm("Tem certeza que deseja deletar esta mensagem?", function (e) {
					if (e) {
						var url = "<?php echo base_url('mensagem/delete/'. $mensagem->idmensagem .'/'.$mensagem->idmensagem); ?>";
					
						if (url) {
							window.location = url;
						}
						
					} else {
						alertify.error("Projeto não removido.");
					}
				});
				return false;
			});

	    	var momento = moment("<?php echo $mensagem->data_envio; ?>", "YYYY/MM/DD HH:mm:ss").startOf("second").fromNow();
			$("#<?php echo 'mensagem_'.$mensagem->idmensagem; ?>").html(momento);

		</script>

		<tr>
			<td colspan="4"><hr /></td>
		</tr>

	</table>

	<table width="100%" id="tabela_mensagens">

		<?php foreach($msg_assoc->result() as $msg){ ?>
		<tr>
			<td style="width:60px" valign="top">
				<a href="<?php echo base_url('usuario/visualizar/'. $msg->id_usuario_from); ?>" class="thumbnail">
					<img src="<?php echo base_url('assets/images/usuarios/'. $msg->imagem); ?>" alt="">
				</a>
			</td>
			
			<td valign="top">
				<span>
					<strong style="margin-left:10px;"><a href="<?php echo base_url('usuario/visualizar/'. $msg->id_usuario_from); ?>"><?php echo fnome($msg->nome); ?></a>:</strong>
					<p style="margin-left:10px;"><?php echo $msg->mensagem; ?></p>
				</span>
			</td>
			
			<td valign="top" style="width:130px" title="<?php echo fdatetime($msg->data_envio, "/"); ?>">
				<b><p id="mensagem_<?php echo $msg->idmensagem; ?>"><?php echo fdatetime($msg->data_envio, "/"); ?></p></b>
			</td>
			
			<!--td valign="top" style="width:30px">
				<?php if(($msg->idusuario == $this->session->userdata('id')) OR ($this->session->userdata('cargo') == 'administrador') OR ($this->session->userdata('cargo') == 'gerente')){ ?>
				<button class="btn btn-danger btn-xs" id="excluir_mensagem_<?php echo $msg->idmensagem; ?>"><i class="glyphicon glyphicon-remove"></i></button>
				<?php } ?>
			</td-->
			<td style="color:#ccc"><i class="glyphicon glyphicon-ok"></i> lida.</td>
		</tr>
		
		<script>
			$("#excluir_mensagem_<?php echo $msg->idmensagem; ?>").click(function () {
				reset();
				alertify.confirm("Tem certeza que deseja deletar esta mensagem?", function (e) {
					if (e) {
						var url = "<?php echo base_url('mensagem/delete/'. $msg->idmensagem .'/'.$mensagem->idmensagem); ?>";
					
						if (url) {
							window.location = url;
						}
						
					} else {
						alertify.error("Projeto não removido.");
					}
				});
				return false;
			});

			var momento = moment("<?php echo $msg->data_envio; ?>", "YYYY/MM/DD HH:mm:ss").startOf("second").fromNow();
			$("#<?php echo 'mensagem_'.$msg->idmensagem; ?>").html(momento);
		</script>
		<?php } ?>
	
	</table>
	
	<div class="formulario_nova_mensagem">
		<form  action="<?php echo base_url('mensagem/enviar_mensagem') ?>" method="post" name="form-comentario" class="form-comentario">
			<table width="100%">
				<tr>
					<td>
						<?php
							if($mensagem->id_usuario_from == $this->session->userdata('id')){
								$usuario_to = $mensagem->idusuario_to;
							}else{
								$usuario_to = $mensagem->id_usuario_from;
							}

							if(preg_match('/^RE:/', $mensagem->assunto)){
								$mensagem->assunto = $mensagem->assunto;
							}else{
								$mensagem->assunto = 'RE: '. $mensagem->assunto;
							}
						?>
						<textarea class="form-control" rows="2" class="col-lg-12" name="mensagem" id="nova_mensagem"><?php echo set_value('mensagem'); ?></textarea>
						<input type="hidden" name="resposta_de" value="<?php echo $mensagem->idmensagem; ?>"/>
						<input type="hidden" name="idprojeto" value="<?php echo $mensagem->idprojeto; ?>"/>
						<input type="hidden" name="id_usuario_to" value="<?php echo $usuario_to; ?>"/>
						<input type="hidden" name="assunto" value="<?php echo $mensagem->assunto; ?>"/>
						<input type="hidden" name="nova" value="0"/>
					</td>
				</tr>

				<tr><td><br /></td></tr>
				
				<tr>
					<td align="right">
						<button type="submit" class="btn btn-primary btn-lg"><i class="glyphicon glyphicon-share-alt"></i> Responder Mensagem</button>
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
	$("#nova_mensagem").jqte({ol: false, ul: false, format: false});
	
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

	$(".glyphicon-star, .glyphicon-star-empty").click(function(e) {
		e.preventDefault();

		var glyph = $(this).hasClass("glyphicon");

		if (glyph) {

			var classe_atual = $(this).attr("favorito");
		    var pag = "<?php echo base_url('mensagem/marcar_como_favorita/'); ?>";
		    var idmensagem = "<?php echo $mensagem->idmensagem; ?>";

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
</script>

