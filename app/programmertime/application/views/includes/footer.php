</div>
</main>

<footer></footer>

<script type="text/javascript">
	$('.select2').select2({
	    theme: "bootstrap"
	});
</script>

<script>
	// Programmer Time version
	console.log('Programmer Time v1.0.0.0');

	// Keyboard shortcuts

	$(document).ready(function(){

		var pressedCtrl = false; 
		$(document).keyup(function (e) { 
			if(e.which == 17) 
				pressedCtrl=false; 
		}) 

		
		$(document).keydown(function (e) { 
			if(e.which == 17) 
				pressedCtrl = true; 

			// CTRL+S - Save a form
			if(e.which == 83 && pressedCtrl == true) { 
				// Code and function calls for ctrl+s go here
				alert("CTRL + S pressed"); 
				//pressedCtrl=false;
			} 

			// CTRL+N - New project
			if(e.which == 78 && pressedCtrl == true) {
				window.location.href = "<?php echo base_url('project/create'); ?>";
			} 

			// CTRL+E - Log time
			if(e.which == 69 && pressedCtrl == true) {
				window.location.href = "<?php echo base_url('time_entry/start'); ?>";
			} 

			// CTRL+F - Finance
			if(e.which == 69 && pressedCtrl == true) {
				window.location.href = "<?php echo base_url('finance'); ?>";
			} 
		}); 

		

	}); 

	/*



	*/

</script>