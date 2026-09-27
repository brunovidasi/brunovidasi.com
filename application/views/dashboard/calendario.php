<br>

<link rel='stylesheet' type='text/css' href='<?php echo base_url('assets/js/fullcalendar/fullcalendar.css'); ?>' />
<script type='text/javascript' src='<?php echo base_url('assets/js/fullcalendar/fullcalendar.js'); ?>'></script>

<div id='calendario'></div>

<div style="text-align:center;">
	<span class="label label-primary">Etapas Lançadas</span>
	<span class="label label-warning">Início de Projeto</span>
	<span class="label label-danger">Prazo de Projeto</span>
</div>

<script>
$(document).ready(function() {

    $('#calendario').fullCalendar({
		
		defaultView: 'agendaDay',
		
		header:{
			left:   'title',
			center: '',
			right:  'month agendaWeek agendaDay today prev next'
		},
		
		height: 700,
		
        monthNames: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
		monthNamesShort: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
		dayNames: ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'],
		dayNamesShort: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'],
		
		buttonText: {
			prev:     '&lsaquo;',
			next:     '&rsaquo;',
			prevYear: '&laquo;',
			nextYear: '&raquo;',
			today:    'Hoje',
			month:    'Mês',
			week:     'Semana',
			day:      'Dia'
		},
		
		timeFormat: 'H(:mm)',
		
		columnFormat: {
			month: 'dddd',
			week: 'ddd M/d',
			day: 'dddd M/d'
		},
		
		titleFormat: {
			month: "MMMM 'de' yyyy",
			week: "d[ 'de' MMMM][ 'de' yyyy]{ '&#8212;' d 'de' MMMM 'de' yyyy}",
			day: "dddd, d 'de' MMMM 'de' yyyy"
		},
		
		eventSources: [
		
		// Etapas Totais		
        {
            events: [
                <?php 
					foreach($etapas->result() as $etapa){
							
						echo  "
						{
							title  	: '". $etapa->fase . " - " . $etapa->nomeprojeto . " - " . $etapa->responsavel . "',
							start  	: '". $etapa->data . " " . $etapa->inicio . "',
							end    	: '". $etapa->data . " " . $etapa->fim . "',
							allDay 	: false,
							url		: '". base_url('projeto/visualizar/'.$etapa->idprojeto) ."'
						},
						";
					}
					?> {}
            ],
            color: '#428bca',
            textColor: 'white'
        },
		
		// Prazos de Projetos
        {
            events: [
                <?php 
					foreach($projetos->result() as $projeto){
						$prazo = fdatahora($projeto->prazo, "/");
						
						echo  "
						{
							title  	: 'Prazo: ". $projeto->nome ."',
							start  	: '". $projeto->prazo ."',
							end    	: '". $projeto->prazo ."',
							allDay 	: false,
							url		: '". base_url('projeto/visualizar/'.$projeto->idprojeto) ."'
						},
						";
					}
					?> {}
            ],
            color: '#d9534f',
            textColor: 'white'
        },
		
		// Início de Projeto
		{
            events: [
                <?php 
					foreach($projetos->result() as $projeto){
						
						echo  "
						{
							title  	: '". $projeto->nome ."',
							start  	: '". $projeto->data_inicio ."',
							end    	: '". $projeto->data_inicio ."',
							url		: '". base_url('projeto/visualizar/'.$projeto->idprojeto) ."'
						},
						";
					}
					?> {}
            ],
            color: '#f0ad4e',
            textColor: 'white'
        }

		]
    })

});
</script>