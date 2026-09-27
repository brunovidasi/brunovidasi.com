<br>

<link rel='stylesheet' type='text/css' href='<?php echo base_url('assets/js/fullcalendar/fullcalendar.css'); ?>' />
<script type='text/javascript' src='<?php echo base_url('assets/js/fullcalendar/fullcalendar.js'); ?>'></script>

<div id='calendar'></div>

<div style="text-align:center;">
	<span class="label label-primary">Logged Time</span>
	<span class="label label-warning">Project Start</span>
	<span class="label label-danger">Project Deadline</span>
</div>

<script>
$(document).ready(function() {

    $('#calendar').fullCalendar({
		
		defaultView: 'agendaDay',
		
		header:{
			left:   'title',
			center: '',
			right:  'month agendaWeek agendaDay today prev next'
		},
		
		height: 700,
		
        monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
		monthNamesShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
		dayNames: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
		dayNamesShort: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
		
		buttonText: {
			prev:     '&lsaquo;',
			next:     '&rsaquo;',
			prevYear: '&laquo;',
			nextYear: '&raquo;',
			today:    'Today',
			month:    'Month',
			week:     'Week',
			day:      'Day'
		},
		
		timeFormat: 'H(:mm)',
		
		columnFormat: {
			month: 'dddd',
			week: 'ddd d/M',
			day: 'dddd d/M'
		},
		
		titleFormat: {
			month: "MMMM yyyy",
			week: "d[ MMMM][ yyyy]{ '&#8212;' d MMMM yyyy}",
			day: "dddd, d MMMM yyyy"
		},
		
		eventSources: [
		
		// All time entries		
        {
            events: [
                <?php 
					foreach($time_entries->result() as $time_entry){
							
						echo  "
						{
							title  	: '". $time_entry->phase . " - " . $time_entry->project_name . " - " . $time_entry->owner . "',
							start  	: '". $time_entry->date . " " . $time_entry->start_time . "',
							end    	: '". $time_entry->date . " " . $time_entry->end_time . "',
							allDay 	: false,
							url		: '". base_url('project/view/'.$time_entry->project_id) ."'
						},
						";
					}
					?> {}
            ],
            color: '#428bca',
            textColor: 'white'
        },
		
		// Project deadlines
        {
            events: [
                <?php 
					foreach($projects->result() as $project){
						$deadline = fdatetime_parts($project->deadline, "/");
						
						echo  "
						{
							title  	: 'Deadline: ". $project->name ."',
							start  	: '". $project->deadline ."',
							end    	: '". $project->deadline ."',
							allDay 	: false,
							url		: '". base_url('project/view/'.$project->project_id) ."'
						},
						";
					}
					?> {}
            ],
            color: '#d9534f',
            textColor: 'white'
        },
		
		// Project start dates
		{
            events: [
                <?php 
					foreach($projects->result() as $project){
						
						echo  "
						{
							title  	: '". $project->name ."',
							start  	: '". $project->start_date ."',
							end    	: '". $project->start_date ."',
							url		: '". base_url('project/view/'.$project->project_id) ."'
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