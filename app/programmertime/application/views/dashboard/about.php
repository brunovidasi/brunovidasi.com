<div class="col-lg-9 col-md-8 col-sm-12">
	<br><br><br><br><br><br><div id="programmer_time" class="visible-lg visible-md visible-sm" style="text-align:center; font-size:90px;">
		P<span style="color:#CCC;" >rogrammer</span> Time <span style="color:#CCC;">_ <br></span> <?php echo $version; ?>
	</div>
</div>

<div class="col-lg-3 col-md-4 col-sm-12">
	<!--<strong style="color:#CCC; font-size: 25px;"><span style="color:#0077bb">P</span>rogrammer <span style="color:#0077bb">Time</span> _ 
		<?php echo $version; ?></strong>--><br><br>

	<strong>Version:</strong> <?php echo $info->version; ?><br>
	<strong>PHP:</strong> <?php echo $info->php; ?><br>
	<strong>Released:</strong> <?php echo $release_date; ?><br><br>
	<strong><a href="http://<?php echo $info->site; ?>"><?php echo $info->site; ?></a></strong><br><br>
	<strong>&copy; <?php echo date('Y'); ?> - All Rights Reserved</strong>

	<hr>

	<strong>Your PHP Version: </strong> <?php echo phpversion(); ?><br>

	<?php if(phpversion() != $info->php){ ?>
		<br><div class="alert alert-warning" style="text-align:center;">
			Programmer Time may not be fully compatible with this PHP version (<strong><?php echo phpversion(); ?></strong>). 
			Version <strong><?php echo $info->php; ?></strong> is recommended. If you run into version errors, contact the administrator.
		</div>
	<?php } ?>

	<strong>Your Browser: </strong> <?php echo $this->session->userdata('browser_info')->browser_version; ?>
	<?php if($browser_info != 'Google Chrome'){ ?>
	<br><br><div class="alert alert-warning" style="text-align:center;">
		The developers recommend Google Chrome for Programmer Time, 
		but feel free to use any browser that supports HTML5 and CSS3.
	</div>
	<?php } ?>

	<hr>

	<strong style="color:#0077bb">Web Developer: </strong><br>
	<strong><?php echo $info->developer; ?></strong> <br>
	<a href="mailto:<?php echo $info->developer_email; ?>"><?php echo $info->developer_email; ?></a><br>
	<a href="http://www.brunovidasi.com/">www.brunovidasi.com</a><br><br>

	<strong style="color:#0077bb">Android Developer: </strong><br>
	<strong>Filipe Moreira</strong><br>
	<a href="mailto:filipe@programmertime.com">filipe@programmertime.com</a><br>
</div>