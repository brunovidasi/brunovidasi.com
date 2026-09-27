// Fills the date/time fields with the current date and time
$(document).ready(function(){  
    $("#date").mask("99/99/9999"); 
	$("#starttime").mask("99:99"); 
	$("#endtime").mask("99:99");  
 });

function fillDate() {
	Hr = new Date();
	dd = Hr.getDate();
	
	var month=new Array();
	month[0]="01";
	month[1]="02";
	month[2]="03";
	month[3]="04";
	month[4]="05";
	month[5]="06";
	month[6]="07";
	month[7]="08";
	month[8]="09";
	month[9]="10";
	month[10]="11";
	month[11]="12";
	var mm = month[Hr.getMonth()];
	
	yyyy = Hr.getFullYear();
	currentDate = ((dd < 10) ? "0" + dd + "/" : dd + "/");
	currentDate += (mm + "/");
	currentDate += (yyyy);
	document.form1.date.value=currentDate;
}

function fillStartTime() {
	Hr = new Date()
	hh = Hr.getHours()
	min = Hr.getMinutes()
	seg = Hr.getSeconds()
	currentTime = ((hh < 10) ? "0" + hh + ":" : hh + ":")
	currentTime += ((min < 10) ? "0" + min + "" : min + "")
	document.form1.start_time.value=currentTime
}

function fillEndTime() {
	Hr = new Date()
	hh = Hr.getHours()
	min = Hr.getMinutes()
	seg = Hr.getSeconds()
	currentTime = ((hh < 10) ? "0" + hh + ":" : hh + ":")
	currentTime += ((min < 10) ? "0" + min + "" : min + "")
	document.form1.end_time.value=currentTime
}