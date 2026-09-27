<link href="<?php echo base_url('assets/js/icheck/skins/line/blue.css'); ?>" rel="stylesheet">
<script src="<?php echo base_url('assets/js/icheck/icheck.js'); ?>"></script>


<p class="titulo_pagina" style="float:left;">Cadastrar Nível de Acesso</p> <br>
<br><br>
<?php
	require('application/views/includes/mensagem.php');
	if(validation_errors() != '')
		echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
?>

<form action="<?php echo base_url('nivel_acesso/insert') ?>" method="post" name="form1" class="form1">

    <table width="100%" border="0" cellpadding="3" cellspacing="3" class="tabela_principal" style="padding:10px;">
		
		<tr>
			<td width="15%" valign="middle">
				<div class="inputs">
					<strong>Nome do Cargo:</strong> <span class="obrigatorio">*</span>
				</div>
			</td>
		
			<td width="85%" valign="middle">
				<div class="col-lg-4 col-md-8 inputs">
					<input name="cargo" type="text" class="form-control" id="" value="<?php echo set_value("cargo"); ?>" />
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td  valign="top">
				<div class="inputs">
					<strong>Cadastrar Projeto:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td  valign="middle">
				<div class="col-lg-10 col-md-10 inputs" id="cadastra_projeto">
					<input type="radio" name="cadastra_projeto" value="sim" <?php echo set_radio('cadastra_projeto', 'sim', TRUE); ?> /> Sim
					<input type="radio" name="cadastra_projeto" value="nao" <?php echo set_radio('cadastra_projeto', 'nao'); ?>  /> Não
				</div>
			</td>
		</tr>
		
		<tr>
			<td  valign="top">
				<div class="inputs">
					<strong>Editar Projeto:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td  valign="middle">
				<div class="col-lg-10 col-md-10 inputs" id="edita_projeto">
					<input type="radio" name="edita_projeto" value="sim" <?php echo set_radio('edita_projeto', 'sim', TRUE); ?> /> Sim
					<input type="radio" name="edita_projeto" value="nao" <?php echo set_radio('edita_projeto', 'nao'); ?>  /> Não
				</div>
			</td>
		</tr>
		
		<tr>
			<td  valign="top">
				<div class="inputs">
					<strong>Cadastrar Clientes:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td  valign="middle">
				<div class="col-lg-10 col-md-10 inputs" id="cadastra_cliente">
					<input type="radio" name="cadastra_cliente" value="sim" <?php echo set_radio('cadastra_cliente', 'sim', TRUE); ?> /> Sim
					<input type="radio" name="cadastra_cliente" value="nao" <?php echo set_radio('cadastra_cliente', 'nao'); ?>  /> Não
				</div>
			</td>
		</tr>
		
		<tr>
			<td  valign="top">
				<div class="inputs">
					<strong>Editar Clientes:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td  valign="middle">
				<div class="col-lg-10 col-md-10 inputs" id="edita_cliente">
					<input type="radio" name="edita_cliente" value="sim" <?php echo set_radio('edita_cliente', 'sim', TRUE); ?> /> Sim
					<input type="radio" name="edita_cliente" value="nao" <?php echo set_radio('edita_cliente', 'nao'); ?>  /> Não
				</div>
			</td>
		</tr>
		
		<tr>
			<td  valign="top">
				<div class="inputs">
					<strong>Lançar Etapas:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td  valign="middle">
				<div class="col-lg-10 col-md-10 inputs" id="lanca_etapa">
					<input type="radio" name="lanca_etapa" value="sim" <?php echo set_radio('lanca_etapa', 'sim', TRUE); ?> /> Sim
					<input type="radio" name="lanca_etapa" value="nao" <?php echo set_radio('lanca_etapa', 'nao'); ?>  /> Não
				</div>
			</td>
		</tr>
		
		<tr>
			<td  valign="top">
				<div class="inputs">
					<strong>Lançar Pagamentos:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td  valign="middle">
				<div class="col-lg-10 col-md-10 inputs" id="lanca_pagamento">
					<input type="radio" name="lanca_pagamento" value="sim" <?php echo set_radio('lanca_pagamento', 'sim', TRUE); ?> /> Sim
					<input type="radio" name="lanca_pagamento" value="nao" <?php echo set_radio('lanca_pagamento', 'nao'); ?>  /> Não
				</div>
			</td>
		</tr>
		
		<tr>
			<td  valign="top">
				<div class="inputs">
					<strong>Enviar Relatórios:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td  valign="middle">
				<div class="col-lg-10 col-md-10 inputs" id="envia_relatorio">
					<input type="radio" name="envia_relatorio" value="sim" <?php echo set_radio('envia_relatorio', 'sim', TRUE); ?> /> Sim
					<input type="radio" name="envia_relatorio" value="nao" <?php echo set_radio('envia_relatorio', 'nao'); ?>  /> Não
				</div>
			</td>
		</tr>
		
		<tr>
			<td  valign="top">
				<div class="inputs">
					<strong>Cadastrar Usuários:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td  valign="middle">
				<div class="col-lg-10 col-md-10 inputs" id="cadastra_usuario">
					<input type="radio" name="cadastra_usuario" value="sim" <?php echo set_radio('cadastra_usuario', 'sim', TRUE); ?> /> Sim
					<input type="radio" name="cadastra_usuario" value="nao" <?php echo set_radio('cadastra_usuario', 'nao'); ?>  /> Não
				</div>
			</td>
		</tr>
		
		<tr>
			<td  valign="top">
				<div class="inputs">
					<strong>Editar Usuários:</strong> <span class="obrigatorio"></span>
				</div>
			</td>
		
			<td  valign="middle">
				<div class="col-lg-10 col-md-10 inputs" id="edita_usuario">
					<input type="radio" name="edita_usuario" value="sim" <?php echo set_radio('edita_usuario', 'sim', TRUE); ?> /> Sim
					<input type="radio" name="edita_usuario" value="nao" <?php echo set_radio('edita_usuario', 'nao'); ?>  /> Não
				</div>
			</td>
		</tr>
		
		<tr><td><br></td><td></td></tr>
		
		<tr>
			<td valign="middle"></td>
			
			<td style="padding-left:14px">
				<button name="submit" type="submit" class="btn btn-primary" id="submit" />Cadastrar Nível de Acesso</button>
			</td>
		</tr>
		

	</table>
</form>
<br>

<script>
$(document).ready(function(){
  // $('input').each(function(){
  //   var self = $(this),
  //     label = self.next(),
  //     label_text = label.text();

  //   label.remove();
  //   self.iCheck({
  //     checkboxClass: 'icheckbox_line-blue',
  //     radioClass: 'iradio_line-blue',
  //     insert: '<div class="icheck_line-icon">' + label_text + '</div>'
  //   });
  // });
});

</script>