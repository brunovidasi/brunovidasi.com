<header>

	<style>
/*
		.header-principal #programmer_time{
			color: <?php echo $this->session->userdata('cor'); ?> !important;
		}

		.menu-principal .glyphicon{
			color: <?php echo $this->session->userdata('cor'); ?> !important;
		}
*/
		.off{
			visibility: hidden !important;
		}

		.gray{
			color:#CCC;
		}

	</style>

	<?php if($this->session->flashdata("logou")){ ?>

	<script>
	(function() {
	  jQuery(function($) {
	    var $characters, $claim, $claimCursor, claims, createElements, delay, drawFrame, frame, index, mode, pos;

	    claims = [
		'Programmer Time '];
		
	    index = 0;
	    frame = 0;
	    pos = 0;
	    mode = 0;
	    delay = 10;
	    $claim = $('#programmer_time');
	    $claimCursor = null;
	    $characters = null;
	    createElements = function() {
	      var c, _i, _len, _ref;

	      _ref = claims[index].split('');
	      for (_i = 0, _len = _ref.length; _i < _len; _i++) {
	        c = _ref[_i];

	        if(_i > 0 && _i < 10){
	        	$claim.append("<span id='i_"+_i+"' class='off gray'>" + c + "</span>");
	        }else{
	        	$claim.append("<span id='i_"+_i+"' class='off'>" + c + "</span>");
	        }
	      }
	      $claimCursor = $('<span id="claim_cursor" class="off gray"> _</span>');
	      $claim.append($claimCursor);
	      return $characters = $claim.children();
	    };
	    createElements();
	    drawFrame = function() {
	      var $character;

	      $character = $characters.eq(pos);
	      if ($character.hasClass('off')) {
	        $character.removeClass('off');
	      } else {
	        $character.addClass('off');
	      }
	      if (pos < claims[index].length) {
	        $claimCursor.addClass('off');
	      } else {
	        if (Math.floor(frame / 10) % 2 === 0) {
	          $claimCursor.addClass('off');
	        } else {
	          $claimCursor.removeClass('off');
	        }
	      }
	      if (mode === 0) {
	        if (pos < claims[index].length) {
	          pos++;
	        }
	      } else {
	        if (pos > 0) {
	          pos--;
	        } else {
	          mode = 1 - mode;
	          index++;
	          index %= claims.length;
	          $claim.empty();
	          createElements();
	        }
	      }
	      frame++;
	      // if (frame % delay === 0) {
	      //   mode = 1 - mode;
	      // }
	    };
	    return window.setInterval(drawFrame, 2500 / 25);
	  });

	}).call(this);
	</script>

	<?php } ?>

	<div id="header-principal">
		<a class="ptime_logo" href="<?php echo base_url(); ?>">
			<?php if($this->session->flashdata("logou")){ ?>
			<div id="programmer_time"></div>

			<?php }else{ ?>

			<div id="programmer_time" class="visible-lg visible-md visible-sm">P<span style="color:#CCC;" >rogrammer</span> Time <span style="color:#CCC;"><span id="texto"></span></span></div>
			<div id="programmer_time" class="hidden-lg hidden-md hidden-sm">P Time <span style="color:#CCC;"><span id="texto_mobile"></span></span></div>

			<?php } ?>
		</a>
		<div id='imagem_usuario_div'>
			<a href="<?php echo base_url('usuario/visualizar'); ?>">
				<img src="<?php echo base_url('assets/images/usuarios/'.$this->session->userdata('imagem')); ?>" class="img-thumbnail img-circle" id="imagem_usuario" style="background-color:<?php echo $this->session->userdata('cor'); ?>;" />
			</a>
		</div>

	</div>

	<!-- <button id="hide-header" rel="1">HIDE</button> -->

	<script>
	// $(document).ready(function(){
	// 	$("#hide-header").on('click', function(){
	// 			$("#header-principal").toggle(1000);
	// 	});
	// });
	</script>

	
	
	<div class="menu-principal">
	
	<ul class="nav nav-tabs">

		<li class="<?php if($this->session->userdata('dashboard')){echo 'active';} ?>" style="margin-left: 5px;">
			<a href="<?php echo base_url('dashboard/'); ?>" id="dashboard" title="<?php echo lang('pagina_inicial'); ?>">
				<i class='glyphicon glyphicon-home'></i>
			</a>
		</li>

		<li class="dropdown <?php if($this->session->userdata('clientes')){echo 'active';}?>">
			
			<a class="dropdown-toggle" data-toggle="dropdown" href="#" title="<?php echo lang('alt_cliente'); ?>">
				<i class='glyphicon glyphicon-user'></i> <?php echo lang('clientes'); ?> <span class="caret"></span>
			</a>
			
			<ul class="dropdown-menu">
				<li>
					<a tabindex="-1" href="<?php echo base_url('cliente/'); ?>">
						<i class='glyphicon glyphicon-user'></i> <?php echo lang('lista_clientes'); ?>
					</a>
				</li>

				<?php if($this->session->userdata('cadastra_cliente')){ ?>

					<li class="divider"></li>

					<li>
						<a tabindex="-1" href="<?php echo base_url('cliente/cadastrar'); ?>">
							<i class='glyphicon glyphicon-pencil'></i> <?php echo lang('cadastra_cliente'); ?>
						</a>
					</li>
				<?php } ?>
			</ul>
		</li>

		<li class="dropdown <?php if($this->session->userdata('projetos')){echo 'active';}?>">

			<a class="dropdown-toggle" data-toggle="dropdown" href="#" title="<?php echo lang('alt_projeto'); ?>">
				<i class='glyphicon glyphicon-file'></i> <?php echo lang('projetos'); ?> <span class="caret"></span>
			</a>
			
			<ul class="dropdown-menu">
				<li class="dropdown-header"><?php echo lang('painel'); ?></li>

				<!--<li><a tabindex="-1" href="<?php echo base_url('dashboard/'); ?>"><i class='glyphicon glyphicon-home'></i> Dashboard</a></li>-->

				<li>
					<a tabindex="-1" href="<?php echo base_url('dashboard/calendario/'); ?>">
						<i class='glyphicon glyphicon-calendar'></i> <?php echo lang('calendario'); ?>
					</a>
				</li>

				<li class="divider"></li>

				<li class="dropdown-header">
					<?php echo lang('projetos'); ?>
				</li>

					<li>
						<a tabindex="-1" href="<?php echo base_url('projeto/lista'); ?>">
							<i class='glyphicon glyphicon-folder-close'></i> <?php echo lang('lista_projetos'); ?></a></li>

							<?php if($this->session->userdata('cadastra_projeto')){ ?>
								<li>
									<a tabindex="-1" href="<?php echo base_url('projeto/cadastrar'); ?>">
										<i class='glyphicon glyphicon-pencil'></i> <?php echo lang('cadastra_projeto'); ?>
									</a>
								</li>
							<?php } ?>
				
					<?php if($this->session->userdata('lanca_etapa')){ ?>

						<li class="divider"></li>

						<li>
							<a tabindex="-1" href="<?php echo base_url('etapa/lista'); ?>">
								<i class='glyphicon glyphicon-time'></i> <?php echo lang('lista_etapas'); ?>
							</a>
						</li>

					<?php }
					if($this->session->userdata('lanca_pagamento')){ ?>
						<!--<li><a tabindex="-1" href="<?php echo base_url('financeiro/lista'); ?>"><i class='glyphicon glyphicon-usd'></i> Lista de Pagamentos</a></li>-->
					<?php } ?>
			</ul>
		</li>
		
		<?php if($this->session->userdata('confirmado') == 'sim'){ ?>


			<?php if($this->session->userdata('lanca_etapa')){ ?>

				<!--<li class="<?php if($this->session->userdata('tarefas')){echo 'active';} ?>">
					<a href="<?php echo base_url('tarefa/lista/'.$this->session->userdata('id')); ?>" title="<?php echo lang('alt_tarefa'); ?>">
						<i class='glyphicon glyphicon-list-alt'></i> <span class="visible-lg-in visible-md-in"><?php echo lang('tarefa'); ?></span>
						<?php 
							$numero_tarefas = $this->tarefa_model->get_tarefas(0, $this->session->userdata('id'), "ASC", 0, 0, "", 'numero', 'concluido');
							if($numero_tarefas > 0)echo '<span class="badge">'.$numero_tarefas.'</span>';
						?>
					</a>


				</li> -->

				<li class="dropdown <?php if($this->session->userdata('tarefas')){echo 'active';}?>">

					<a class="dropdown-toggle" data-toggle="dropdown" href="#" title="<?php echo lang('alt_tarefa'); ?>">
						<i class='glyphicon glyphicon-list-alt'></i> <?php echo lang('tarefa'); ?> 
						<?php 
							$numero_tarefas = $this->tarefa_model->get_tarefas(0, $this->session->userdata('id'), "ASC", 0, 0, "", 'numero', 'concluido');
							$numero_tarefas_totais = $this->tarefa_model->get_tarefas(0, 0, "ASC", 0, 0, "", 'numero', 'concluido');
							if($numero_tarefas > 0)echo '<span class="badge">'.$numero_tarefas.'</span>';
						?>
						<span class="caret"></span>
					</a>
					
					<ul class="dropdown-menu">

						<li>
							<a tabindex="-1" href="<?php echo base_url('tarefa/lista/'.$this->session->userdata('id')); ?>">
								<i class='glyphicon glyphicon-folder-close'></i> Minhas Tarefas 
								<?php if($numero_tarefas > 0) echo '<span class="badge">'.$numero_tarefas.'</span>'; ?>
							</a>
						</li>

						<li>
							<a tabindex="-1" href="<?php echo base_url('tarefa/lista/'); ?>">
								<i class='glyphicon glyphicon-folder-close'></i> Todas as Tarefas
								<?php if($numero_tarefas_totais > 0) echo '<span class="badge">'.$numero_tarefas_totais.'</span>'; ?>
							</a>
						</li>

						<li class="divider"></li>

						<li>
							<a tabindex="-1" href="<?php echo base_url('tarefa/cadastrar/'); ?>">
								<i class='glyphicon glyphicon-pencil'></i> Cadastrar Nova
							</a>
						</li>


						
					</ul>
				</li>

				<li class="<?php if($this->session->userdata('etapas')){echo 'active';} ?>">
					<a href="<?php echo base_url('etapa/'); ?>" id="lancar_etapa" title="<?php echo lang('alt_etapa'); ?>">
						<i class='glyphicon glyphicon-time'></i> <span class="visible-lg-in visible-md-in"><?php echo lang('etapa'); ?></span>
						<?php 
							$etapa_aberta = $this->etapa_model->etapa_aberta($this->session->userdata('id'));
							if($etapa_aberta->num_rows() > 0)echo '<span class="badge">1</span>';
						?>
					</a>
				</li>

			<?php } ?>
			
			<?php if($this->session->userdata('lanca_pagamento')){ ?>

				<li class="<?php if($this->session->userdata('financeiro')){echo 'active';} ?>" id="lancar_pagamento">
					<a href="<?php echo base_url('financeiro/'); ?>" title="<?php echo lang('alt_financeiro'); ?>">
						<i class='glyphicon glyphicon-usd'></i> <span class="visible-lg-in visible-md-in"><?php echo lang('financeiro'); ?></span>
					</a>
				</li>

			<?php } ?>
			
			<?php if($this->session->userdata('envia_relatorio')){ ?>

				<li class="<?php if($this->session->userdata('relatorios')){echo 'active';} ?>">
					<a href="<?php echo base_url('relatorio/'); ?>" id="relatorios" title="<?php echo lang('alt_relatorio'); ?>">
						<i class='glyphicon glyphicon-file'></i> <span class="visible-lg-in"><?php echo lang('relatorios'); ?></span>
					</a>
				</li>

			<?php } ?>
			
			<!--<li class="<?php if($this->session->userdata('mensagem')){echo 'active';} ?>">
				<a href="<?php echo base_url('mensagem/'); ?>" id="mensagens" title="Caixa de Entrada">
					<i class='glyphicon glyphicon-envelope'></i> <span class="visible-lg-in">Mensagens</span>
						<?php
							$mensagens = $this->mensagem_model->get_mensagens_nao_lidas();
							if($mensagens->num_rows() > 0) echo '<span class="badge">'. $mensagens->num_rows() .'</span>';
						?>
				</a>
			</li>-->
			
			<?php if($this->session->userdata('cadastra_usuario')){ ?>

				<li class="dropdown <?php if($this->session->userdata('usuarios')){echo 'active';}?>">

					<a class="dropdown-toggle" data-toggle="dropdown" href="#" title="<?php echo lang('alt_usuario'); ?>">
						<i class='glyphicon glyphicon-user'></i> <?php echo lang('usuarios'); ?> <span class="caret"></span>
					</a>
					
					<ul class="dropdown-menu">

						<li>
							<a tabindex="-1" href="<?php echo base_url('usuario/lista'); ?>">
								<i class='glyphicon glyphicon-user'></i> <?php echo lang('lista_usuarios'); ?>
							</a>
						</li>

						<li class="divider"></li>

						<li>
							<a tabindex="-1" href="<?php echo base_url('usuario/cadastrar'); ?>">
								<i class='glyphicon glyphicon-pencil'></i> <?php echo lang('cadastra_usuario'); ?>
							</a>
						</li>

						<li class="dropdown-header"><?php echo lang('niveis_acesso'); ?></li>

						<li>
							<a tabindex="-1" href="<?php echo base_url('nivel_acesso/cadastrar'); ?>">
								<i class='glyphicon glyphicon-pencil'></i> <?php echo lang('cadastra_cargo'); ?>
							</a>
						</li>

						<li>
							<a tabindex="-1" href="<?php echo base_url('nivel_acesso/'); ?>">
								<i class='glyphicon glyphicon-saved'></i> <?php echo lang('acesso_cargo'); ?>
							</a>
						</li>
					</ul>
				</li>

			<?php } ?>
		<?php } ?>
		
		<li class="navbar-right">
			<a href="<?php echo base_url('acesso/sair'); ?>" style="color: #CC0000;" title="<?php echo lang('sair'); ?>" class="sair"><i class='glyphicon glyphicon-off'></i></a>
		</li>
		
		<?php #if(($this->session->userdata('id') == '1') OR ($this->session->userdata('id') == '2')){ ?>
			<li class="dropdown <?php if($this->session->userdata('configuracao')){echo 'active';}?> navbar-right">
				
				<a class="dropdown-toggle" data-toggle="dropdown" href="#" title="<?php echo lang('configuracoes'); ?>">
					<i class='glyphicon glyphicon-cog'></i>
				</a>
				
				<ul class="dropdown-menu">

					<li class="dropdown-header"><?php echo lang('configuracoes_gerais'); ?></li>

					<li><a tabindex="-1" href="<?php echo base_url('nivel_acesso/'); ?>"> <?php echo lang('configuracoes_acesso'); ?></a></li>

					<li><a tabindex="-1" href="<?php echo base_url('usuario/editar'); ?>"> <?php echo lang('configuracoes_conta'); ?></a></li>

					<li><a tabindex="-1" href="<?php echo base_url('empresa/editar'); ?>"> <?php echo lang('editar_empresa'); ?></a></li>

					<!--<li><a tabindex="-1" href="<?php echo base_url('empresa/pagamento'); ?>"> Informações de Pagamento <i class='glyphicon glyphicon-usd'></i></a></li>-->
					
					<li class="divider"></li>

					<li><a tabindex="-1" href="<?php echo base_url('dashboard/sobre'); ?>"> <?php echo lang('sobre'); ?></a></li>

					<!--<li><a tabindex="-1" href="<?php echo base_url('ajuda/'); ?>"><i class='glyphicon glyphicon-info-sign'></i> Ajuda </a></li>
					<?php if(($this->session->userdata('id') == '1') OR ($this->session->userdata('id') == '2')){ ?>
						<li><a tabindex="-1" href="<?php echo base_url('ajuda/cadastrar'); ?>"><i class='glyphicon glyphicon-edit'></i> Cadastrar Ajuda </a></li>
					<?php } ?>-->
				</ul>
			</li>

		<?php #} ?>
		
		<li class="dropdown navbar-right <?php if($this->session->userdata('editar_usuario')){echo 'active';} ?>">

			<a class="dropdown-toggle" data-toggle="dropdown" href="#">
				<i class='glyphicon glyphicon-user'></i> <span class="visible-lg-in"><?php echo lang('ola'); ?><b><?php echo $this->session->userdata('nome_duplo'); ?></b>!</span> <span class="caret"></span>
			</a>
			
			<ul class="dropdown-menu">

				<li class="disabled">
					<a tabindex="-1" href="">
						<i class='glyphicon glyphicon-user'></i> <?php echo $this->session->userdata('nome_completo'); ?>
					</a>
				</li>

				<li class="disabled">
					<a tabindex="-1" href="">
						<?php echo $this->session->userdata('email'); ?>
					</a>
				</li>

				<li class="divider"></li>

				<li>
					<a tabindex="-1" href="<?php echo base_url('usuario/visualizar/'); ?>">
						<i class='glyphicon glyphicon-user'></i> <?php echo lang('visualizar_perfil'); ?>
					</a>
				</li>

				<li>
					<a tabindex="-1" href="<?php echo base_url('usuario/editar/'); ?>">
						<i class='glyphicon glyphicon-edit'></i> <?php echo lang('editar_perfil'); ?>
					</a>
				</li>

				<li>
					<a tabindex="-1" href="<?php echo base_url('usuario/desativar_usuario'); ?>" style="color:#CC0000;">
						<i class='glyphicon glyphicon-remove'></i> <?php echo lang('desativar_perfil'); ?>
					</a>
				</li>

				<li class="divider"></li>

				<li>
					<a tabindex="-1" id="bloquear_sessao_" href="<?php echo base_url('acesso/bloquear'); ?>">
						<i class='glyphicon glyphicon-ban-circle'></i> <?php echo lang('bloquear_sessao'); ?>
					</a>
				</li>

				<li>
					<a tabindex="-1" href="<?php echo base_url('acesso/sair'); ?>" class="sair">
						<i class='glyphicon glyphicon-off'></i> <?php echo lang('sair'); ?>
					</a>
				</li>
			</ul>
		</li>
	
	</ul>

	<?php if($this->session->userdata('confirmado') == 'nao'){ ?>
		<div class="alert alert-warning" style="text-align:center;">
			<?php echo lang('confirmar_conta'); ?><a href="<?php echo base_url('usuario/envia_email_confirmacao/'); ?>"><?php echo lang('clique_aqui'); ?>.</a>
		</div>
	<?php } ?>
	
	</div>

<script>	
	$(function(){
		$.contextMenu({
			selector: '#imagem_usuario_div', 
			
			callback: function(key, options) {
				
				if(key == "ver"){
					var url = '<?php echo base_url("usuario/visualizar/"); ?>';
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "editar"){
					var url = '<?php echo base_url("usuario/editar/".$this->session->userdata("id")); ?>';
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "sair"){
					if(validaForm()){
						var url = '<?php echo base_url("acesso/sair/"); ?>';
						if (url) {
							window.location = url;
						}
					}
				}
				
			},
			
			items: {
				"ver": {name: "<?php echo lang('visualizar_perfil'); ?>", icon: "paste"},
				"editar": {name: "<?php echo lang('editar_perfil'); ?>", icon: "edit"},
				"sep1": "---------",
				"sair": {name: "<?php echo lang('sair'); ?>", icon: "delete"},
			}
		});
	});
	
	$(".nav").on("click", "li ul li a, #lancar_etapa, #lancar_pagamento, #relatorios", function(){
		$("html").css("cursor", "progress");
	});
	
	$(document).on("click", "input[type='submit'], button[type='submit'], a .btn", function(){
		$("html").css("cursor", "progress");
	});

		reset = function () {
			$("toggleCSS").href = "<?php echo base_url('assets/js/alertify/themes/alertify.ptime.css'); ?>";
			alertify.set({
				labels : {
					ok     : "<?php echo lang('bloquear_sessao'); ?>",
					cancel : "<?php echo lang('cancelar_sessao'); ?>"
				},
				delay : 5000,
				buttonReverse : false,
				buttonFocus   : "ok"
			});
		};

	$(".sair").click(function () {
		$(".sair").html("<i class='glyphicon glyphicon-off'></i> <?php echo lang('saindo'); ?> ...");
	});

	// $("#bloquear_sessao").click(function () {
	// 	reset();
	// 	alertify.confirm("Hora de almoço? Não vai utilizar o PTime agora? É sempre bom bloquear a sessão! Mas tem certeza?", function (e) {
	// 		if (e) {
	// 			var url = '<?php echo base_url('acesso/bloquear'); ?>';
			
	// 			if (url) {
	// 				window.location = url;
	// 			}
				
	// 		} else {
	// 			alertify.info("Sessão não bloqueada.");
	// 		}
	// 	});
	// 	return false;
	// });
</script>

</header>

<main class="conteudo">
<div>