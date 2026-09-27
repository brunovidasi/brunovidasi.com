<p class="titulo_pagina" style="float:left;">Usuários do Sistema</p> 

<?php

	$cadastra_usuario = $this->session->userdata('cadastra_usuario');
	$edita_usuario = $this->session->userdata('edita_usuario');

	require('application/views/includes/mensagem.php');
	if(validation_errors() != ''){
	echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert'>&times;</button><ul>".validation_errors('<li>', '</li>')."</ul></div> <br />";
	}

	$editar = '';
if($edita_usuario){ 
	$editar = '"editar": {name: "Editar Usuário", icon: "edit"},
				"deletar": {name: "Desativar Usuário", icon: "delete"},';
} 

foreach($usuarios->result() as $usuario){ 
	if($usuario->status == 'ativo' || $usuario->idusuario != 1){
	$nome = explode(" ", $usuario->nome);
	
	if(!isset($usuario_array[$usuario->nivel_acesso])){
		$usuario_array[$usuario->nivel_acesso] = "";
	}

	$usuario_array[$usuario->nivel_acesso] .= '<div class="col-lg-1 col-md-3 col-xs-5" style="text-align:center; padding-left: 0px;" id="context2_'. $usuario->idusuario .'">

		<a href="'. base_url('usuario/visualizar/'. $usuario->idusuario) .'" >
			<img src="'. base_url('assets/images/usuarios/'.$usuario->imagem) .'" class="img-thumbnail" style="width:100%;" />
		</a>

		<div class="descricao_usuario">
			<strong>'. $nome[0] . (isset($nome[1]) ? ' ' . $nome[1] : '').'</strong> <br />

			<small title="Último Acesso" style="font-size:12px;">
				<span title="'. $usuario->numero_acesso .' acessos">
					'. fdatetime($usuario->ultimo_acesso ,"/") . '
				</span>
			</small>
		</div>
	</div>

	<script>	
	$(function(){
		$.contextMenu({
			selector: "#context2_'. $usuario->idusuario .'", 
			
			callback: function(key, options) {
				
				if(key == "ver"){
					var url = '. base_url('usuario/visualizar/'.$usuario->idusuario).';
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "editar"){
					var url = '. base_url('usuario/editar/'.$usuario->idusuario) .';
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "deletar"){
					if(validaForm()){
						var url = '. base_url('usuario/desativar/'.$usuario->idusuario) .';
						if (url) {
							window.location = url;
						}
					}
				}
				
			},
			
			items: {
				"ver": {name: "Ver Usuário", icon: "paste"},
				'. $editar .'
			}
		});
	});
	</script>';

	}
} ?>

<div class="col-lg-12">

<?php 

// var_dump($usuario_array);
// die();

foreach($niveis_acesso->result() as $nivel_acesso){

	if(isset($usuario_array[$nivel_acesso->id])){
		if($this->session->userdata('nivel_acesso') == 1 && $nivel_acesso->id == 1){
			echo '<div class="col-lg-12 "><p class="titulo_pagina">' . $nivel_acesso->cargo . '</p><br />';
		}
		elseif($nivel_acesso->id != 1){
			echo '<div class="col-lg-12 "><p class="titulo_pagina">' . $nivel_acesso->cargo . '</p><br />';
		}

		foreach($usuario_array as $idcargo => $conteudo){

			if($nivel_acesso->id == $idcargo){

				if($this->session->userdata('nivel_acesso') == 1 && $nivel_acesso->id == 1){
					echo $usuario_array[$idcargo];
				}

				elseif($nivel_acesso->id != 1){
					echo $usuario_array[$idcargo];
				}


			}
		}

		echo '</div>';
	}


} 

?>
</div>


	
	
