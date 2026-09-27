<?php

/*
 * ProgrammerTime settings. Copy this file to:
 *
 *   - config.php at the project root, for local development (gitignored), or
 *   - programmertime-instance/config.php above public_html, on the server.
 *
 * See application/config/instance.php for how the file is found.
 */

return array(

	// 'development' shows PHP errors and logs everything.
	// 'production' hides errors, logs errors only and marks cookies Secure.
	'env' => 'production',

	// Full URL the app is served from, with a trailing slash.
	'base_url' => 'https://app.brunovidasi.com/programmertime/',

	// Timezone used for every date and time the app shows and records.
	// Any PHP timezone name: https://www.php.net/manual/en/timezones.php
	'timezone' => 'Australia/Sydney',

	// Signs session cookies AND salts every stored password, so changing it logs
	// everyone out and invalidates every password. Generate one with:
	//   php -r 'echo bin2hex(random_bytes(32)), "\n";'
	'encryption_key' => '',

	// Password for the 'admin' account, used ONCE when the database is first
	// created. At least 8 characters. Remove it from this file after logging in.
	'admin_password' => '',

	// SQLite: a single file in data/ next to this config, created automatically
	// on first run from schema.sqlite.sql. Nothing to set up.
	'db' => array('driver' => 'sqlite'),

	// Or MySQL/MariaDB, loading schema.sql yourself:
	// 'db' => array(
	// 	'driver'   => 'mysql',
	// 	'hostname' => 'localhost',
	// 	'username' => '',
	// 	'password' => '',
	// 	'database' => '',
	// ),

);
