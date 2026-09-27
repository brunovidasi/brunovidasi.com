<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$active_group = 'default';
$active_record = TRUE;

# Connection settings come from the instance config
# (application/config/instance.php); never commit real credentials here.
#
# SQLite (the default) is a single file in the instance's data/ directory,
# created on first run. CodeIgniter 2's own sqlite driver needs the long-gone
# ext/sqlite, so it goes through the PDO driver instead. 'driver' => 'mysql'
# in the config uses MySQL/MariaDB with schema.sql.
$pt_db = pt_config('db');

if ($pt_db['driver'] === 'sqlite') {
	$db['default']['hostname'] = 'sqlite:'.$pt_db['path'];
	$db['default']['username'] = '';
	$db['default']['password'] = '';
	$db['default']['database'] = '';
	$db['default']['dbdriver'] = 'pdo';
} else {
	$db['default']['hostname'] = $pt_db['hostname'];
	$db['default']['username'] = $pt_db['username'];
	$db['default']['password'] = $pt_db['password'];
	$db['default']['database'] = $pt_db['database'];
	$db['default']['dbdriver'] = 'mysqli';
}


# Configura��es Gerais de Banco de Dados
$db['default']['dbprefix'] = '';
$db['default']['pconnect'] = FALSE;
$db['default']['db_debug'] = ENVIRONMENT !== 'production';
$db['default']['cache_on'] = FALSE;
$db['default']['cachedir'] = '';
$db['default']['char_set'] = 'utf8';
$db['default']['dbcollat'] = 'utf8_general_ci';
$db['default']['swap_pre'] = '';
$db['default']['autoinit'] = TRUE;
$db['default']['stricton'] = FALSE;