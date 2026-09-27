<p class="titulo_pagina" style="">Limpador de Arquivos Inutilizáveis do Sistema</p> <br>

<div style="width: 350px; border-right: 1px solid #0077bb; display:inline-block; padding:10px; float:left;">

<p class="titulo_pagina" style="color:#0077bb ; margin-top:-20px">Pasta de Imagens Temporárias</p>

<?php

$path = 'assets/images/temp/';
 
$diretorio = dir($path); 
 
$arquivos = "Lista dos Arquivos do diretório '<strong>".$path."</strong>':<br /><br />"; 

$contador = 0;

while($arquivo = $diretorio -> read()){ 
	if($arquivo != '..' && $arquivo != '.' && $arquivo != 'index.html' && $arquivo != 'none.png' && $arquivo != 'Thumbs.db'){
		$arquivos .= "<a href='". base_url($path.$arquivo) ."' target='_blank'>".$arquivo."</a><br />";
		$contador++;
	}
} 

$diretorio -> close(); 

echo 'Foram encontrados <strong>' . $contador . "</strong> arquivos na pasta. <br /><br />";

?>

<button class="btn btn-primary" style="width:100%;">Limpar arquivos da pasta temp</button><br><br>

<?php

echo $arquivos;

?>

</div>

<div style="width: 350px; border-right: 1px solid #0077bb; display:inline-block; padding:10px; float:left;">

<p class="titulo_pagina" style="color:#0077bb; margin-top:-20px">Pasta de Todas as Imagens de Usuários</p>

<?php

$path = 'assets/images/usuarios/';
 
$diretorio = dir($path); 
 
$arquivos = "Lista dos Arquivos do diretório '<strong>".$path."</strong>':<br /><br />"; 

$contador = 0;
//array_diff()
while($arquivo = $diretorio -> read()){
	if($arquivo != '..' && $arquivo != '.' && $arquivo != 'index.html' && $arquivo != 'none.png' && $arquivo != 'Thumbs.db'){
		$arquivos .= "<a href='". base_url($path.$arquivo) ."' target='_blank'>".$arquivo."</a><br />";
		$contador++;
	}
} 

$diretorio -> close(); 

echo 'Foram encontrados <strong>' . $contador . "</strong> arquivos na pasta. <br /><br />";

?>

<button class="btn btn-primary disabled" style="width:100%;">Limpar arquivos da pasta usuários</button><br><br>

<?php

echo $arquivos;

?>

</div>

<div style="width: 350px; border-right: 1px solid #0077bb; display:inline-block; padding:10px; float:left;">

<p class="titulo_pagina" style="color:#0077bb; margin-top:-20px">Pasta de Imagens de Usuários não mais utilizadas</p>

<?php

$path = 'assets/images/usuarios/';
 
$diretorio = dir($path); 
 
$arquivos = "Lista dos Arquivos do diretório '<strong>".$path."</strong>':<br /><br />"; 

$contador = 0;
//array_diff()
while($arquivo = $diretorio -> read()){
	if($arquivo != '..' && $arquivo != '.' && $arquivo != 'index.html' && $arquivo != 'none.png' && $arquivo != 'Thumbs.db'){
		//$arquivos .= "<a href='". base_url($path.$arquivo) ."' target='_blank'>".$arquivo."</a><br />";
		//$contador++;
		$array_diretorio[] = $arquivo;
	}
}

foreach($imagens->result() as $img){
	$array_banco[] = $img->imagem;
}

$array_diferenca = array_diff($array_diretorio, $array_banco);

foreach($array_diferenca as $dif){
	//$arquivos .= "<a href='". base_url($path.$arquivo) ."' target='_blank'>".$arquivo."</a><br />";
}

$diretorio -> close(); 

echo 'Foram encontrados <strong>' . $contador . "</strong> arquivos na pasta. <br /><br />";

?>

<button class="btn btn-primary" style="width:100%;">Limpar arquivos da pasta usuários</button><br><br>

<?php

echo $arquivos;

?>

</div>


