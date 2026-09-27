<!DOCTYPE html>
<html lang="en">
<head>
<title>Erro na Base de Dados</title>
<style type="text/css">
@font-face {
	font-family: 'Fjalla One';
	font-style: normal;
	font-weight: 400;
	src: local('Fjalla One'), local('FjallaOne-Regular'), url(http://tcc.brunovidasi.com/assets/fonts/Fjalla-One.woff) format('woff');
}

html{ 
	height:100%; 
}

body{ 
	height:100%; 
	text-align:center; 
}

*{ 
	margin:0; 
	padding:0; 
}
	
#box_erro{
	width: 500px;
	height: 368px;
	margin: 0 auto;
	position: absolute;
	top: 50%;
	left: 50%;
	margin-top: -184px;
	margin-left: -250px;
}

#mensagem{
	font-family: "Fjalla One", sans-serif;
	color: #0077bb;
	padding-top: 75px;
	font-size: 20px;
}

#programmer_time{
	font-family: 'Fjalla One', sans-serif;
	font-size:60px; 
	color:#0077bb;
	margin-left: 15px;
}

</style>
</head>
<body>
	<div style="display:none;">
		<h1></h1>
		
	</div>
	
	<div id='wrap'>
		
		<div id="box_erro">
			
			<div id="programmer_time" class="visible-lg visible-md">P<span style="color:#CCC;" >rogrammer</span> Time <span style="color:#CCC;">_</span></span></div>
			
			<div id="mensagem">
				<b><?php echo $heading; ?></b> - <?php echo $message; ?>
			</div>
		
		</div>
		
	</div>
</body>
</html>