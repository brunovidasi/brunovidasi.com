<link href="<?php echo base_url('assets/js/alertify/themes/alertify.ptime.css'); ?>" rel="stylesheet" type="text/css" id="toggleCSS"/>

<p class="titulo_pagina" style="float:left;"><?php echo lang('titulo_lista_clientes'); ?></p> 

<div class="" style="float:right; margin-bottom:10px; width:50%;">
	
	<form action="<?php print base_url('cliente/lista'); ?>" method="post" name="formfiltrotermo" id="formfiltrotermo" class="form-inline" style="display:inline; float:right; margin-top:26px; width:628px;">
		
		<input type="text" name="termo" placeholder="<?php echo lang('placeholder_filtro_cliente'); ?>" class="form-control" value="<?php print $this->session->userdata('c_termo'); ?>" style="width:500px; float:left;"/>
		<a href="<?php echo base_url('cliente/cadastrar'); ?>" title="<?php echo lang('alt_cadastrar_cliente'); ?>" class="btn btn-primary" style="float:right; margin-right: 0px;"><i class='glyphicon glyphicon-plus'></i> <?php echo lang('btn_novo'); ?></a>
		<button type="submit" title="<?php echo lang('alt_btn_cadastrar_cliente'); ?>" class="btn btn-primary" style="float:right; margin-right: 5px;"><i class='glyphicon glyphicon-search'></i></button>
	
	</form>
	
</div> <br><br><br><br>

<?php
	$cadastra_cliente = $this->session->userdata('cadastra_cliente');
	$edita_cliente = $this->session->userdata('edita_cliente');

	require('application/views/includes/mensagem.php');
	if(validation_errors() != '')
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
?>

<table width="100%" class="table table-hover table-condensed" style="padding:10px;">

	<tr>
		<th width=""><?php echo lang('tabela_id'); ?></th>
		<th width=""><?php echo lang('tabela_nome'); ?></th>
		<th width=""><?php echo lang('tabela_email'); ?></th>
		<th width="">Projetos</th>
		<th width="135px"><?php echo lang('tabela_data_cadastro'); ?></th>
		<th width="120px"></th>		
	</tr>
	
	<?php 
	if($clientes->num_rows() == 0)
		echo '<tr><td colspan="5" align="center"><strong>'.lang('tabela_sem_resultado').'</strong></td></tr>';
	
	foreach($clientes->result() as $cliente){
		$class_tr = "";
		$danger = "";
		
		if($cliente->status == 'inativo'){
			$class_tr = 'danger';
			$danger = 'danger';
		}
	?>
	
	<tr class="<?php echo $class_tr; ?>" id="context_<?php echo $cliente->idcliente; ?>">

		<td><a href="<?php echo base_url("cliente/visualizar/".$cliente->idcliente); ?>"># <?php echo $cliente->idcliente; ?></a></td>

		<td><a href="<?php echo base_url("cliente/visualizar/".$cliente->idcliente); ?>"><?php echo $cliente->nome; ?></a></td>

		<td><a href="mailto:<?php echo $cliente->email; ?>"><?php echo $cliente->email; ?></a></td>

		<td><?php echo $cliente->num_projetos; ?></td>

		<td><?php echo fdatetime($cliente->data_cadastro, "/"); ?></td>
		
		<td align="right">
			<a class="btn btn-xs btn-primary" alt="<?php echo lang('btn_visualizar_cliente'); ?>" title="<?php echo lang('btn_visualizar_cliente'); ?>" href="<?php echo base_url('cliente/visualizar/'. $cliente->idcliente); ?>" id="ver_cliente">
				<i class='glyphicon glyphicon-user'></i>
			</a>

			<?php if($cliente->email){ ?>
				<a class="btn btn-xs btn-info" alt="<?php echo lang('btn_enviar_email'); ?>" title="<?php echo lang('btn_enviar_email'); ?>" href="mailto:<?php echo $cliente->email; ?>" id="email_cliente">
					<i class='glyphicon glyphicon-envelope'></i>
				</a>
			<?php } ?>
			
			<?php if($edita_cliente){ ?>
				<a class="btn btn-xs btn-warning" alt="<?php echo lang('btn_editar_cliente'); ?>" title="<?php echo lang('btn_editar_cliente'); ?>" href="<?php echo base_url('cliente/editar/'. $cliente->idcliente); ?>" id="edita_cliente">
					<i class='glyphicon glyphicon-edit'></i>
				</a>

				<?php if($this->session->userdata('nivel_acesso') == 1 || $this->session->userdata('nivel_acesso') == 2){ ?>

					<?php if($cliente->status == 'ativo'){ ?>
						<span class="btn btn-xs btn-danger" alt="<?php echo lang('btn_desativar_cliente'); ?>" title="<?php echo lang('btn_desativar_cliente'); ?>" id="desativar_cliente_<?php echo $cliente->idcliente; ?>">
							<i class='glyphicon glyphicon-remove'></i>
						</span>
					<?php }else{ ?>
						<span class="btn btn-xs btn-success" alt="<?php echo lang('btn_reativar_cliente'); ?>" title="<?php echo lang('btn_reativar_cliente'); ?>" id="reativar_cliente_<?php echo $cliente->idcliente; ?>">
							<i class='glyphicon glyphicon-ok'></i>
						</span>
					<?php } ?>
					
				<?php } ?>
			<?php } ?>
		</td>
		
	</tr>
	
	<script>	
	$(function(){
		$.contextMenu({
			selector: '#context_<?php echo $cliente->idcliente; ?>', 
			
			callback: function(key, options) {
				
				if(key == "ver"){
					var url = '<?php echo base_url("cliente/visualizar/".$cliente->idcliente); ?>';
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "editar"){
					var url = '<?php echo base_url("cliente/editar/".$cliente->idcliente); ?>';
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "deletar"){
					reset();
					var texto = `<strong>Solicitação para desativação do cliente <?php echo $cliente->nome; ?></strong>
					<br /><br />
					Ao desativar um cliente, todos os seus projetos em desenvolvimento terão o status "Cancelado".
					<br /><br />

					Tem certeza que deseja desativar o cliente <?php echo $cliente->nome; ?>, de ID <?php echo $cliente->idcliente; ?>? <br /><br />
					`;

					alertify.confirm(texto, function (e){
						if (e) {
							var url = '<?php echo base_url("cliente/muda_status/inativo/".$cliente->idcliente); ?>';
						
							if(url){
								window.location = url;
							}
							
						} else {
							alertify.error("Cliente não desativado.");
						}
					});
					return false;
				}
				
			},
			
			items: {
				"ver": {name: "<?php echo lang('btn_visualizar_cliente'); ?>", icon: "paste"},
				<?php if($edita_cliente){ ?>
				"editar": {name: "<?php echo lang('btn_editar_cliente'); ?>", icon: "edit"},
				"deletar": {name: "<?php echo lang('btn_desativar_cliente'); ?>", icon: "delete"},
				<?php } ?>
			}
		});
	});
	
	reset = function () {
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
		
	$("#desativar_cliente_<?php echo $cliente->idcliente; ?>").click(function () {
		reset();
		var texto = `<strong>Solicitação para desativação do cliente <?php echo $cliente->nome; ?></strong>
		<br /><br />
		Ao desativar um cliente, todos os seus projetos em desenvolvimento terão o status "Cancelado".
		<br /><br />

		Tem certeza que deseja desativar o cliente <?php echo $cliente->nome; ?>, de ID <?php echo $cliente->idcliente; ?>? <br /><br />
		`;

		alertify.confirm(texto, function (e){
			if (e) {
				var url = '<?php echo base_url("cliente/muda_status/inativo/".$cliente->idcliente); ?>';
			
				if(url){
					window.location = url;
				}
				
			} else {
				alertify.error("<?php echo lang('cliente_nao_desativado');?>");
			}
		});
		return false;
	});
	
	$("#reativar_cliente_<?php echo $cliente->idcliente; ?>").click(function () {
		reset();
		alertify.confirm("<?php echo lang('cliente_confirma_reativar');?>", function (e) {
			if (e) {
				var url = '<?php echo base_url("cliente//muda_status/ativo/".$cliente->idcliente); ?>';
			
				if (url) {
					window.location = url;
				}
				
			} else {
				alertify.error("<?php echo lang('cliente_nao_reativado');?>");
			}
		});
		return false;
	});
	</script>
	
	<?php } ?>

</table>
	
	<br>
		<span style=""><?php print $paginacao; ?></span>
	<br>

<script>
$(function(){
	$('.maximo').bind('change', function () {
		var url = $(this).val();
		if(url){
			window.location = url;
		}
		return false;
	});
});
</script>