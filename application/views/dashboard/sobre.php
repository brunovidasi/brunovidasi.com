<div class="col-lg-9 col-md-8 col-sm-12">
	<br><br><br><br><br><br><div id="programmer_time" class="visible-lg visible-md visible-sm" style="text-align:center; font-size:90px;">
		P<span style="color:#CCC;" >rogrammer</span> Time <span style="color:#CCC;">_ <br></span> <?php echo $versao; ?>
	</div>
</div>

<div class="col-lg-3 col-md-4 col-sm-12">
	<!--<strong style="color:#CCC; font-size: 25px;"><span style="color:#0077bb">P</span>rogrammer <span style="color:#0077bb">Time</span> _ 
		<?php echo $versao; ?></strong>--><br><br>

	<strong>Versão:</strong> <?php echo $info->versao; ?><br>
	<strong>PHP:</strong> <?php echo $info->php; ?><br>
	<strong>Lançamento:</strong> <?php echo $data_lancamento; ?><br><br>
	<strong><a href="http://<?php echo $info->site; ?>"><?php echo $info->site; ?></a></strong><br><br>
	<strong>&copy; <?php echo date('Y'); ?> - Todos os Direitos Reservados</strong>

	<hr>

	<strong>Sua Versão PHP: </strong> <?php echo phpversion(); ?><br>

	<?php if(phpversion() != $info->php){ ?>
		<br><div class="alert alert-warning" style="text-align:center;">
			O Programmer Time pode ter incompatibilidade com esta versão do PHP (<strong><?php echo phpversion(); ?></strong>), 
			É recomendada a versão <strong><?php echo $info->php; ?></strong>. Se acontecer erros de versão no sistema, contate o administrador.
		</div>
	<?php } ?>

	<strong>Seu Navegador: </strong> <?php echo $this->session->userdata('navegador')->browser_version; ?>
	<?php if($navegador != 'Google Chrome'){ ?>
	<br><br><div class="alert alert-warning" style="text-align:center;">
		Os desenvolvedores aconselham o uso do navegador Google Chrome para utilizar o Programmer Time, 
		mas fique a vontade para o uso em qualquer navegador que suporte HTML5 e CSS3.
	</div>
	<?php } ?>

	<hr>

	<strong style="color:#0077bb">Desenvolvedor WEB: </strong><br>
	<strong><?php echo $info->desenvolvedor; ?></strong> <br>
	<a href="mailto:<?php echo $info->desenvolvedor_email; ?>"><?php echo $info->desenvolvedor_email; ?></a><br>
	<a href="http://www.brunovidasi.com/">www.brunovidasi.com</a><br><br>

	<strong style="color:#0077bb">Desenvolvedor ANDROID: </strong><br>
	<strong>Filipe Moreira</strong><br>
	<a href="mailto:filipe@programmertime.com">filipe@programmertime.com</a><br>
</div>