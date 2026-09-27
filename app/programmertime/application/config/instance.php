<?php

/*
 * Loads this deployment's settings: environment, base URL, timezone, encryption
 * key and database credentials. Required by index.php before CodeIgniter boots,
 * and read by config.php and database.php via pt_config().
 *
 * On a server the real config lives in an "instance directory" OUTSIDE the
 * deployed tree, because this app ships inside a PUBLIC repo and the deployed
 * tree sits under public_html:
 *
 *     /home/<user>/domains/brunovidasi.com/
 *     ├── programmertime-instance/     <- created by hand, never deployed
 *     │   ├── config.php               <- copy of config.example.php
 *     │   ├── data/                    <- SQLite database (created automatically)
 *     │   └── logs/                    <- CodeIgniter logs (created automatically)
 *     └── public_html/app/programmertime/
 *
 * The SQLite database is created in <instance>/data/ on first run (or
 * data/ at the project root locally).
 *
 * It is found by walking up from this file looking for a directory named
 * 'programmertime-instance', so no absolute server path is hardcoded in the repo.
 * Set PROGRAMMERTIME_INSTANCE to override the location. Locally, with no
 * instance directory, config.php at the project root (gitignored) is used.
 */

function pt_instance_dir()
{
	static $dir = false;

	if ($dir !== false)
		return $dir;

	$from_env = getenv('PROGRAMMERTIME_INSTANCE');
	if ($from_env !== false && $from_env !== '' && is_dir($from_env))
		return $dir = rtrim($from_env, '/');

	$cursor = dirname(__DIR__, 2);
	for ($i = 0; $i < 6; $i++) {
		$parent = dirname($cursor);
		if ($parent === $cursor)
			break;
		$cursor = $parent;
		if (is_dir($cursor.'/programmertime-instance'))
			return $dir = $cursor.'/programmertime-instance';
	}

	return $dir = null;
}

function pt_config($key = null)
{
	static $config = null;

	if ($config === null) {
		$instance = pt_instance_dir();
		$path = $instance !== null ? $instance.'/config.php' : dirname(__DIR__, 2).'/config.php';

		if (!is_file($path)) {
			// Deliberately vague in the response: this fires on a public URL in the
			// window between a deploy and the config being created, and the absolute
			// path is not something to hand to whoever visits.
			error_log('ProgrammerTime: no config file at '.$path);
			http_response_code(503);
			exit(PHP_SAPI === 'cli' ? "Missing config file at $path\n" : 'This application is not configured yet.');
		}

		$config = array_replace_recursive(array(
			'env'            => 'production',
			'base_url'       => '',
			'timezone'       => 'Australia/Sydney',
			'encryption_key' => '',
			'admin_password' => '',
			'db'             => array('driver' => 'sqlite'),
		), require $path);

		if ($config['env'] === 'production' && strlen($config['encryption_key']) < 32)
			pt_not_configured('encryption_key must be at least 32 characters in production');

		$config['log_path'] = '';
		if ($instance !== null) {
			$logs = $instance.'/logs';
			if (!is_dir($logs))
				@mkdir($logs, 0755);
			$config['log_path'] = $logs.'/';
		}

		date_default_timezone_set($config['timezone']);

		if ($config['db']['driver'] === 'sqlite') {
			$config['db']['path'] = ($instance !== null ? $instance : dirname(__DIR__, 2)).'/data/programmertime.sqlite';
			if (!is_file($config['db']['path']))
				pt_create_database($config);
		}
	}

	return $key === null ? $config : $config[$key];
}

/*
 * Creates the SQLite database from schema.sqlite.sql the first time the app
 * runs, so there is no manual import step. The seeded admin account gets its
 * password from the config's admin_password: passwords are stored as
 * md5(encryption_key . password) (see hash_password_helper.php), so the hash can only
 * be made here, once the key is known. admin_password is not read again after
 * this and can be removed from the config file.
 */
function pt_create_database($config)
{
	$path = $config['db']['path'];
	$dir  = dirname($path);

	if (strlen($config['admin_password']) < 8)
		pt_not_configured('admin_password must be at least 8 characters to create the database');

	if (!is_dir($dir) && !@mkdir($dir, 0755, true))
		pt_not_configured('cannot create '.$dir);

	// Defence in depth, in case the data directory ever ends up web-reachable.
	if (!is_file($dir.'/.htaccess'))
		@file_put_contents($dir.'/.htaccess', "Require all denied\n");

	// Two first requests arriving together must not both build the database.
	$lock = fopen($dir.'/.create.lock', 'c');
	flock($lock, LOCK_EX);

	if (!is_file($path)) {
		// Built under a temporary name and renamed into place, so a failure
		// halfway never leaves a half-built database that looks finished.
		$tmp = $path.'.tmp';
		@unlink($tmp);

		try {
			$pdo = new PDO('sqlite:'.$tmp);
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$pdo->exec(file_get_contents(dirname(__DIR__, 2).'/schema.sqlite.sql'));

			$admin = $pdo->prepare('UPDATE user SET password = ?, created_at = ? WHERE user_id = 1');
			$admin->execute(array(md5($config['encryption_key'].$config['admin_password']), date('Y-m-d H:i:s')));
			$pdo = null;

			rename($tmp, $path);
		} catch (Exception $e) {
			@unlink($tmp);
			flock($lock, LOCK_UN);
			pt_not_configured('creating the database failed: '.$e->getMessage());
		}
	}

	flock($lock, LOCK_UN);
	fclose($lock);
}

function pt_not_configured($reason)
{
	// The detail goes to the error log only; this page is public.
	error_log('ProgrammerTime: '.$reason);
	http_response_code(503);
	exit(PHP_SAPI === 'cli' ? $reason."\n" : 'This application is not configured yet.');
}
