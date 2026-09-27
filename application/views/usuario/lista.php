<p class="titulo_pagina" style="float:left;"><?php echo lang('titulo_lista_usuarios'); ?></p> 

<div class="" style="float:right; margin-bottom:10px; width:50%;">
	
	<form action="<?php print base_url('usuario/lista'); ?>" method="post" name="formfiltrotermo" id="formfiltrotermo" class="form-inline" style="display:inline; float:right; margin-top:26px; width:628px;">
		<input type="text" name="termo" placeholder="<?php echo lang('placeholder_filtro_usuario'); ?>" class="form-control" value="<?php print $this->session->userdata('termo'); ?>" style="width:500px; float:left;"/>
		<a href="<?php echo base_url('usuario/cadastrar'); ?>" title="<?php echo lang('alt_cadastrar_usuario'); ?>" class="btn btn-primary" style="float:right; margin-right: 0px;"><i class='glyphicon glyphicon-plus'></i> <?php echo lang('btn_novo'); ?></a>
		<button type="submit" title="<?php echo lang('alt_btn_cadastrar_usuario'); ?>" class="btn btn-primary" style="float:right; margin-right: 5px;"><i class='glyphicon glyphicon-search'></i></button>
	</form>
	
</div> <br><br><br><br>

<?php

	$cadastra_usuario = $this->session->userdata('cadastra_usuario');
	$edita_usuario = $this->session->userdata('edita_usuario');

	require('application/views/includes/mensagem.php');
	if(validation_errors() != ''){
	echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	}
?>

<table width="100%" class="table table-hover table-condensed" style="padding:10px;">

	<tr>
		<th width=""><?php echo lang('tabela_id'); ?></th>
		<th width="180px"><?php echo lang('tabela_login'); ?></th>
		<th width=""><?php echo lang('tabela_nome'); ?></th>
		<th width=""><?php echo lang('tabela_email'); ?></th>		
		<th width="200px"><?php echo lang('tabela_nivel_acesso'); ?></th>		
		<th width="125px"><?php echo lang('tabela_data_cadastro'); ?></th>		
		<th width="125px"><?php echo lang('tabela_ultimo_acesso'); ?></th>	
		<th width="80px" style="text-align:center;"><?php echo lang('tabela_confirmado'); ?></th>	
		<th width="100px"></th>		
	</tr>
	
	<?php 

	if($usuarios->num_rows() <= 1)
		echo '<tr><td colspan="9" align="center"><strong>'.lang('tabela_sem_resultado').'</strong></td></tr>';
	
	foreach($usuarios->result() as $usuario){

		if($usuario->idusuario == '1'){
			if(empty($termo)){
				echo '<tr class="" id="context_1">
						<td># 1</td>
						<td>admin</td>
						<td>'.lang('administrador').'</td>
						<td></td>
						<td>'.lang('administrador').'</td>
						<td></td>
						<td></td>
						<td align="center"></td>
						<td></td>
					</tr>';
			}
			continue;
		}
		
	?>
	
	<tr class="<?php echo ($usuario->status == 'inativo') ? 'danger' : ''; ?>" id="context_<?php echo $usuario->idusuario; ?>">

		
		<td><a href="<?php echo base_url('usuario/visualizar/'. $usuario->idusuario); ?>"># <?php echo $usuario->idusuario; ?></a></td>
		<td><?php echo $usuario->login; ?></td>
		<td><?php echo $usuario->nome; ?></td>
		<td><a href="mailto:<?php echo $usuario->email; ?>"><?php echo $usuario->email; ?></a></td>
		<td><?php echo $usuario->cargo; ?></td>
		<td><?php echo fdatetime($usuario->data_cadastro,"/"); ?></td>
		
		<td><?php
			if(!empty($usuario->ultimo_acesso))
				echo '<span title="'. $usuario->numero_acesso .' acessos">'. fdatetime($usuario->ultimo_acesso ,"/") . '</span>';
		?></td>
		
		<td align="center">
		<?php
			if($usuario->usuario_confirmado == 'sim')
				echo '<label class="label label-primary">'.lang('sim').'</label>';
			else
				echo '<label class="label label-danger">'.lang('nao').'</label>';			
		?>
		</td>
		
		<td align="right">
			<a class="btn btn-xs btn-primary" alt="<?php echo lang('btn_visualizar_usuario'); ?>" title="<?php echo lang('btn_visualizar_usuario'); ?>" href="<?php echo base_url('usuario/visualizar/'. $usuario->idusuario); ?>" id="ver_usuario">
				<i class='glyphicon glyphicon-user'></i>
			</a>
			
			<?php if($edita_usuario){ ?>
				<a class="btn btn-xs btn-warning" alt="<?php echo lang('btn_editar_usuario'); ?>" title="<?php echo lang('btn_editar_usuario'); ?>" href="<?php echo base_url('usuario/editar/'. $usuario->idusuario); ?>" id="edita_usuario">
					<i class='glyphicon glyphicon-edit'></i>
				</a>

				<?php if($usuario->status == 'ativo'){ ?>
					<span class="btn btn-xs btn-danger" alt="<?php echo lang('btn_desativar_usuario'); ?>" title="<?php echo lang('btn_desativar_usuario'); ?>" id="excluir_usuario_<?php echo $usuario->idusuario; ?>">
						<i class='glyphicon glyphicon-remove'></i>
					</span>
				<?php }else{ ?>
					<span class="btn btn-xs btn-success" alt="<?php echo lang('btn_reativar_usuario'); ?>" title="<?php echo lang('btn_reativar_usuario'); ?>" id="reativar_usuario_<?php echo $usuario->idusuario; ?>">
						<i class='glyphicon glyphicon-ok'></i>
					</span>
				<?php } ?>
			<?php } ?>
		</td>
		
		
	</tr>
	
	<script>	
	$(function(){
		$.contextMenu({
			selector: '#context_<?php echo $usuario->idusuario; ?>', 
			
			callback: function(key, options) {
				
				if(key == "ver"){
					var url = '<?php echo base_url("usuario/visualizar/".$usuario->idusuario); ?>';
					if (url) {
						window.location = url;
					}
				}

				<?php if($edita_usuario){ ?>
				
				if(key == "editar"){
					var url = '<?php echo base_url("usuario/editar/".$usuario->idusuario); ?>';
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "deletar"){
					if(validaForm()){
						var url = '<?php echo base_url("usuario/desativar/".$usuario->idusuario); ?>';
						if (url) {
							window.location = url;
						}
					}
				}

				<?php } ?>
				
			},
			
			items: {
				"ver": {name: "<?php echo lang('btn_visualizar_usuario'); ?>", icon: "paste"},
				<?php if($edita_usuario){ ?>
				"editar": {name: "<?php echo lang('btn_editar_usuario'); ?>", icon: "edit"},
				"deletar": {name: "<?php echo lang('btn_desativar_usuario'); ?>", icon: "delete"},
				<?php } ?>
			}
		});
	});
	
	reset = function () {
		//$("toggleCSS").href = "<?php echo base_url('assets/js/alertify/themes/alertify.ptime.css'); ?>";
		alertify.set({
			labels : {
				ok     : "<?php echo lang('sim'); ?>",
				cancel : "<?php echo lang('nao'); ?>"
			},
			delay : 5000,
			buttonReverse : false,
			buttonFocus   : "ok"
		});
	};
		
	$("#excluir_usuario_<?php echo $usuario->idusuario; ?>").click(function () {
		reset();
		alertify.confirm("<?php echo lang('usuario_confirma_exclusao', '', $usuario->nome); ?>", function (e) {
			if (e) {
				var url = '<?php echo base_url("usuario/muda_status/inativo/".$usuario->idusuario); ?>';
			
				if (url) {
					window.location = url;
				}
				
			} else {
				alertify.error("<?php echo lang('usuario_nao_desativado'); ?>");
			}
		});
		return false;
	});
	
	$("#reativar_usuario_<?php echo $usuario->idusuario; ?>").click(function () {
		reset();
		alertify.confirm("<?php echo lang('usuario_confirma_reativar'); ?>", function (e) {
			if (e) {
				var url = '<?php echo base_url("usuario/muda_status/ativo/".$usuario->idusuario); ?>';
			
				if (url) {
					window.location = url;
				}
				
			} else {
				alertify.error("<?php echo lang('usuario_nao_reativado'); ?>");
			}
		});
		return false;
	});
	</script>
	
	<?php } ?>

</table>
	
	<br><span style=""><?php print $paginacao; ?></span><br>

<script>
$(function(){
  $('.maximo').bind('change', function () {
	  var url = $(this).val();
	  if (url) {
		  window.location = url;
	  }
	  return false;
  });
});
</script>