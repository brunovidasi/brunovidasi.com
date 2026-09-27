<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$hook['post_controller_constructor'][] = array(
	'class' => '',
	'function' => 'acesso',
	'filename' => 'acesso.php',
	'filepath' => 'hooks',
	'params' => array()
);

$hook['post_controller_constructor'][] = array(
	'class' => '',
	'function' => 'logado',
	'filename' => 'logado.php',
	'filepath' => 'hooks',
	'params' => array()
);

$hook['post_controller_constructor'][] = array(
	'class' => '',
	'function' => 'bloqueado',
	'filename' => 'bloqueado.php',
	'filepath' => 'hooks',
	'params' => array()
);

$hook['post_controller_constructor'][] = array(
	'class' => '',
	'function' => 'recarregar',
	'filename' => 'recarregar.php',
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

