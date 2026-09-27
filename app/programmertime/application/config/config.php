<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['base_url']				= pt_config('base_url');
$config['index_page'] 			= '';

$config['uri_protocol']			= 'AUTO';
$config['url_suffix'] 			= '';

// $config['language']				= 'english';
$config['language']				= 'brazilian_portuguese';

$config['charset'] 				= 'UTF-8';
$config['enable_hooks'] 		= TRUE;

$config['subclass_prefix'] 		= 'MY_';
$config['permitted_uri_chars'] 	= 'a-z 0-9~%.:_\-';

$config['allow_get_array']		= TRUE;
$config['enable_query_strings'] = FALSE;
$config['controller_trigger']	= 'c';
$config['function_trigger']		= 'm';
$config['directory_trigger']	= 'd';

$config['log_threshold'] 		= ENVIRONMENT === 'production' ? 1 : 4; # 0 = inativo | 1 = Erro | 2 = Debug | 3 = Info | 4 = Todas
$config['log_path'] 			= pt_config('log_path'); # instance logs/ on the server, application/logs/ locally
$config['log_date_format'] 		= 'Y-m-d H:i:s';

$config['cache_path'] 			= '';
// Used for both session-cookie integrity (Session.php) AND password hashing
// (cripto_helper.php), so it lives in the instance config, never in this
// public repo. Changing it logs everyone out and invalidates every password.
$config['encryption_key'] 		= pt_config('encryption_key');

$config['sess_cookie_name']		= 'programmer_time';
$config['sess_expiration']		= 0; # 3600*24*30*12*5 (5 anos)
$config['sess_expire_on_close']	= FALSE;
$config['sess_encrypt_cookie']	= FALSE;
$config['sess_use_database']	= FALSE;
$config['sess_table_name']		= 'usuario_sessao';
$config['sess_match_ip']		= FALSE;
$config['sess_match_useragent']	= TRUE;
$config['sess_time_to_update']	= 300;

$config['cookie_prefix']		= "";
$config['cookie_domain']		= "";
// Scoped to the app's own path so the cookie isn't sent to sibling apps on
// the same host (e.g. /bidwraith), and HTTPS-only in production.
$config['cookie_path']			= rtrim((string) parse_url(pt_config('base_url'), PHP_URL_PATH), '/').'/';
$config['cookie_secure']		= ENVIRONMENT === 'production';

$config['global_xss_filtering'] = FALSE;

$config['csrf_protection']	 	= FALSE;
$config['csrf_token_name'] 		= 'csrf_test_name';
$config['csrf_cookie_name'] 	= 'csrf_cookie_name';
$config['csrf_expire'] 			= 7200;

$config['compress_output'] 		= FALSE;
$config['time_reference'] 		= 'local';
$config['rewrite_short_tags'] 	= FALSE;
$config['proxy_ips'] 			= '';