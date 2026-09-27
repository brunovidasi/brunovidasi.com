</div>
</main>

<footer></footer>

<script type="text/javascript">
	$('.select2').select2({
	    theme: "bootstrap"
	});
</script>

<script>
	// Versão do Programmer Time
	console.log('Programmer Time v1.0.0.0');

	// Atalhos

	$(document).ready(function(){

		var pressedCtrl = false; 
		$(document).keyup(function (e) { 
			if(e.which == 17) 
				pressedCtrl=false; 
		}) 

		
		$(document).keydown(function (e) { 
			if(e.which == 17) 
				pressedCtrl = true; 

			// CTRL+S - Salvar um formulário
			if(e.which == 83 && pressedCtrl == true) { 
				//Aqui vai o código e chamadas de funções para o ctrl+s 
				alert("CTRL + S pressionados"); 
				//pressedCtrl=false;
			} 

			// CTRL+N - Novo Projeto
			if(e.which == 78 && pressedCtrl == true) {
				window.location.href = "<?php echo base_url('projeto/cadastrar'); ?>";
			} 

			// CTRL+E - Lançar Etapa
			if(e.which == 69 && pressedCtrl == true) {
				window.location.href = "<?php echo base_url('etapa/lancar'); ?>";
			} 

			// CTRL+F - Financeiro
			if(e.which == 69 && pressedCtrl == true) {
				window.location.href = "<?php echo base_url('financeiro'); ?>";
			} 
		}); 

		

	}); 

	/*



	*/

</script>