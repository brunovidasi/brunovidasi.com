//Pega Horas
$(document).ready(function(){  
    $("#data").mask("99/99/9999"); 
	$("#horainicio").mask("99:99"); 
	$("#horafinal").mask("99:99");  
 });

function pegadata() {
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
	DiaAtual = ((dd < 10) ? "0" + dd + "/" : dd + "/");
	DiaAtual += (mm + "/");
	DiaAtual += (yyyy);
	document.form1.data.value=DiaAtual;
}

function pegahorainicio() {
	Hr = new Date()
	hh = Hr.getHours()
	min = Hr.getMinutes()
	seg = Hr.getSeconds()
	HoraAtual = ((hh < 10) ? "0" + hh + ":" : hh + ":")
	HoraAtual += ((min < 10) ? "0" + min + "" : min + "")
	document.form1.inicio.value=HoraAtual
}

function pegahorafinal() {
	Hr = new Date()
	hh = Hr.getHours()
	min = Hr.getMinutes()
	seg = Hr.getSeconds()
	HoraAtual = ((hh < 10) ? "0" + hh + ":" : hh + ":")
	HoraAtual += ((min < 10) ? "0" + min + "" : min + "")
	document.form1.fim.value=HoraAtual
}