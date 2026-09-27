<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$hook['post_controller_constructor'][] = array(
	'class' => '',
	'function' => 'auth',
	'filename' => 'auth.php',
	'filepath' => 'hooks',
	'params' => array()
);

$hook['post_controller_constructor'][] = array(
	'class' => '',
	'function' => 'logged_in',
	'filename' => 'logged_in.php',
	'filepath' => 'hooks',
	'params' => array()
);

$hook['post_controller_constructor'][] = array(
	'class' => '',
	'function' => 'locked',
	'filename' => 'locked.php',
	'filepath' => 'hooks',
	'params' => array()
);

$hook['post_controller_constructor'][] = array(
	'class' => '',
	'function' => 'reload',
	'filename' => 'reload.php',
	'filepath' => 'hooks',
	'params' => array()
);

$hook['post_controller_constructor'][] = array(
	'class' => '',
	'function' => 'menu_header',
	'filename' => 'header.php',
	'filepath' => 'hooks',
	'params' => array()
);

