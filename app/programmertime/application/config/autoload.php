<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$autoload['libraries'] 	= array(
							'database', 
							'session', 
							'form_validation', 
							'pagination', 
							'upload', 
							'image_lib', 
							'email'
						);

$autoload['helper'] 	= array(
							'form', 
							'fdate', 
							'url', 
							'pr_helper', 
							'hash_password', 
							'generate_password', 
							'log', 
							'hours', 
							'currency',
							'download',
							'sql',
							'language',
							'user'
						);
						
$autoload['model'] 		= array(
							'image_model',
							'company_model',
							'help_model',
							'send_email',
							'download_model',
							'auth_model', 
							'project_model', 
							'user_model', 
							'task_model', 
							'time_entry_model', 
							'finance_model',
							'client_model',
							'message_model',
							'report_model'
						);

$autoload['language'] 	= array(
							'button',
							'controller',
							'project',
							'menu'
						);

$autoload['packages'] 	= array();
$autoload['config'] 	= array();
