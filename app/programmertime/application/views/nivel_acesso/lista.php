<p class="titulo_pagina" style="float:left;">Administrar Níveis de Acesso</p> <br>

<div style="float:right;">

<div class="btn-group" style="">
    <button type="button" class="btn btn-danger dropdown-toggle" data-toggle="dropdown">
		<i class='glyphicon glyphicon-remove'></i> Excluir
		<span class="caret"></span>
    </button>
    <ul class="dropdown-menu">
		<?php foreach($nivel_acesso->result() as $nivel){
					if($nivel->id != '1'){ ?>
					
		<li><a href="<?php echo base_url('nivel_acesso/delete/'.$nivel->id); ?>"><?php echo $nivel->cargo; ?></a></li>
		
		<?php 		} 
			   }
		?>
    </ul>
</div>
 
 <a href="<?php echo base_url('nivel_acesso/cadastrar'); ?>"><span class="btn btn-primary"><i class='glyphicon glyphicon-plus'></i> Adicionar Nível de Acesso</span></a>
</div>
<br><br>

<?php require('application/views/includes/mensagem.php'); ?>

    <table width="100%" border="0" cellpadding="3" cellspacing="3" class="table table-hover" style="padding:10px;">
		
		<tr>
			<th width="28%"><i class='glyphicon glyphicon-user'></i> Nível de Acesso</th>
			<th width="8%" style="text-align:center;"><i class='glyphicon glyphicon-pencil'></i> Cadastrar Projeto</th>
			<th width="8%" style="text-align:center;"><i class='glyphicon glyphicon-edit'></i> Editar Projeto</th>
			<th width="8%" style="text-align:center;"><i class='glyphicon glyphicon-user'></i> Cadastrar Cliente</th>
			<th width="8%" style="text-align:center;"><i class='glyphicon glyphicon-edit'></i> Editar Cliente</th>
			<th width="8%" style="text-align:center;"><i class='glyphicon glyphicon-time'></i> Lançar Etapa</th>
			<th width="8%" style="text-align:center;"><i class='glyphicon glyphicon-usd'></i> Lançar Pagamento</th> 
			<th width="8%" style="text-align:center;"><i class='glyphicon glyphicon-file'></i> Enviar Relatório</th> 
			<th width="8%" style="text-align:center;"><i class='glyphicon glyphicon-user'></i> Cadastrar Usuários</th> 
			<th width="8%" style="text-align:center;"><i class='glyphicon glyphicon-edit'></i> Editar Usuários</th> 
		</tr>
		
		<?php foreach($nivel_acesso->result() as $nivel){
				if($nivel->id != '1'){
		?>
		
			<tr>
			
				<td class="td_nivel" valign="middle"><?php echo $nivel->cargo; ?></td>
				
				<td align="center">
				
				<?php if($nivel->cadastra_projeto == 'sim'){ ?>
				
					<input type='image' width="20px" alt='sim' title='sim' src='<?php echo base_url('assets/images/sistema/sim.png'); ?>' onclick="nivel(this, '<?php echo base_url('assets/'); ?>', 'usuario_nivel_acesso', 'id', 'cadastra_projeto', '<?php echo $nivel->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $nivel->cargo; ?>', 'cadastra projeto');" />
				
				<?php }else{ ?>
				
					<input type='image' width="20px" alt='não' title='não' src='<?php echo base_url('assets/images/sistema/nao.png'); ?>' onclick="nivel(this, '<?php echo base_url('assets/'); ?>', 'usuario_nivel_acesso', 'id', 'cadastra_projeto', '<?php echo $nivel->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $nivel->cargo; ?>', 'cadastra projeto');" />
				
				<?php } ?>
				
				</td>
				
				<td align="center">
				
				<?php if($nivel->edita_projeto == 'sim'){ ?>
				
					<input type='image' width="20px" alt='sim' title='sim' src='<?php echo base_url('assets/images/sistema/sim.png'); ?>' onclick="nivel(this, '<?php echo base_url('assets/'); ?>', 'usuario_nivel_acesso', 'id', 'edita_projeto', '<?php echo $nivel->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $nivel->cargo; ?>', 'edita projeto');" />
				
				<?php }else{ ?>
				
					<input type='image' width="20px" alt='não' title='não' src='<?php echo base_url('assets/images/sistema/nao.png'); ?>' onclick="nivel(this, '<?php echo base_url('assets/'); ?>', 'usuario_nivel_acesso', 'id', 'edita_projeto', '<?php echo $nivel->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $nivel->cargo; ?>', 'edita projeto');" />
				
				<?php } ?>
				
				</td>
				
				<td align="center">
				
				<?php if($nivel->cadastra_cliente == 'sim'){ ?>
				
					<input type='image' width="20px" alt='sim' title='sim' src='<?php echo base_url('assets/images/sistema/sim.png'); ?>' onclick="nivel(this, '<?php echo base_url('assets/'); ?>', 'usuario_nivel_acesso', 'id', 'cadastra_cliente', '<?php echo $nivel->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $nivel->cargo; ?>', 'cadastra cliente');" />
				
				<?php }else{ ?>
				
					<input type='image' width="20px" alt='não' title='não' src='<?php echo base_url('assets/images/sistema/nao.png'); ?>' onclick="nivel(this, '<?php echo base_url('assets/'); ?>', 'usuario_nivel_acesso', 'id', 'cadastra_cliente', '<?php echo $nivel->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $nivel->cargo; ?>', 'cadastra cliente');" />
				
				<?php } ?>
				
				</td>
				
				<td align="center">
				
				<?php if($nivel->edita_cliente == 'sim'){ ?>
				
					<input type='image' width="20px" alt='sim' title='sim' src='<?php echo base_url('assets/images/sistema/sim.png'); ?>' onclick="nivel(this, '<?php echo base_url('assets/'); ?>', 'usuario_nivel_acesso', 'id', 'edita_cliente', '<?php echo $nivel->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $nivel->cargo; ?>', 'edita cliente');" />
				
				<?php }else{ ?>
				
					<input type='image' width="20px" alt='não' title='não' src='<?php echo base_url('assets/images/sistema/nao.png'); ?>' onclick="nivel(this, '<?php echo base_url('assets/'); ?>', 'usuario_nivel_acesso', 'id', 'edita_cliente', '<?php echo $nivel->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $nivel->cargo; ?>', 'edita cliente');" />
				
				<?php } ?>
				
				</td>
				
				<td align="center">
				
				<?php if($nivel->lanca_etapa == 'sim'){ ?>
				
					<input type='image' width="20px" alt='sim' title='sim' src='<?php echo base_url('assets/images/sistema/sim.png'); ?>' onclick="nivel(this, '<?php echo base_url('assets/'); ?>', 'usuario_nivel_acesso', 'id', 'lanca_etapa', '<?php echo $nivel->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $nivel->cargo; ?>', 'lança etapa');" />
				
				<?php }else{ ?>
				
					<input type='image' width="20px" alt='não' title='não' src='<?php echo base_url('assets/images/sistema/nao.png'); ?>' onclick="nivel(this, '<?php echo base_url('assets/'); ?>', 'usuario_nivel_acesso', 'id', 'lanca_etapa', '<?php echo $nivel->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $nivel->cargo; ?>', 'lanca etapa');" />
				
				<?php } ?>
				
				</td>
				
				<td align="center">
				
				<?php if($nivel->lanca_pagamento == 'sim'){ ?>
				
					<input type='image' width="20px" alt='sim' title='sim' src='<?php echo base_url('assets/images/sistema/sim.png'); ?>' onclick="nivel(this, '<?php echo base_url('assets/'); ?>', 'usuario_nivel_acesso', 'id', 'lanca_pagamento', '<?php echo $nivel->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $nivel->cargo; ?>', 'lança pagamento');" />
				
				<?php }else{ ?>
				
					<input type='image' width="20px" alt='não' title='não' src='<?php echo base_url('assets/images/sistema/nao.png'); ?>' onclick="nivel(this, '<?php echo base_url('assets/'); ?>', 'usuario_nivel_acesso', 'id', 'lanca_pagamento', '<?php echo $nivel->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $nivel->cargo; ?>', 'lança pagamento');" />
				
				<?php } ?>
				
				<td align="center">
				
				<?php if($nivel->envia_relatorio == 'sim'){ ?>
				
					<input type='image' width="20px" alt='sim' title='sim' src='<?php echo base_url('assets/images/sistema/sim.png'); ?>' onclick="nivel(this, '<?php echo base_url('assets/'); ?>', 'usuario_nivel_acesso', 'id', 'envia_relatorio', '<?php echo $nivel->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $nivel->cargo; ?>', 'envia relatório');" />
				
				<?php }else{ ?>
				
					<input type='image' width="20px" alt='não' title='não' src='<?php echo base_url('assets/images/sistema/nao.png'); ?>' onclick="nivel(this, '<?php echo base_url('assets/'); ?>', 'usuario_nivel_acesso', 'id', 'envia_relatorio', '<?php echo $nivel->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $nivel->cargo; ?>', 'envia relatório');" />
				
				<?php } ?>
				
				<td align="center">
				
				<?php if($nivel->cadastra_usuario == 'sim'){ ?>
				
					<input type='image' width="20px" alt='sim' title='sim' src='<?php echo base_url('assets/images/sistema/sim.png'); ?>' onclick="nivel(this, '<?php echo base_url('assets/'); ?>', 'usuario_nivel_acesso', 'id', 'cadastra_usuario', '<?php echo $nivel->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $nivel->cargo; ?>', 'cadastra usuário');" />
				
				<?php }else{ ?>
				
					<input type='image' width="20px" alt='não' title='não' src='<?php echo base_url('assets/images/sistema/nao.png'); ?>' onclick="nivel(this, '<?php echo base_url('assets/'); ?>', 'usuario_nivel_acesso', 'id', 'cadastra_usuario', '<?php echo $nivel->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $nivel->cargo; ?>', 'cadastra usuário');" />
				
				<?php } ?>
				
				<td align="center">
				
				<?php if($nivel->edita_usuario == 'sim'){ ?>
				
					<input type='image' width="20px" alt='sim' title='sim' src='<?php echo base_url('assets/images/sistema/sim.png'); ?>' onclick="nivel(this, '<?php echo base_url('assets/'); ?>', 'usuario_nivel_acesso', 'id', 'edita_usuario', '<?php echo $nivel->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $nivel->cargo; ?>', 'edita usuário');" />
				
				<?php }else{ ?>
				
					<input type='image' width="20px" alt='não' title='não' src='<?php echo base_url('assets/images/sistema/nao.png'); ?>' onclick="nivel(this, '<?php echo base_url('assets/'); ?>', 'usuario_nivel_acesso', 'id', 'edita_usuario', '<?php echo $nivel->id; ?>', '<?php echo $this->session->userdata('id'); ?>', '<?php echo $nivel->cargo; ?>', 'edita usuário');" />
				
				<?php } ?>
				
				</td>
				
			</tr>
			
		<?php }
		} ?>
		
		

	</table>
	
<br/>