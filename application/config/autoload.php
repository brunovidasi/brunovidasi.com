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
							'fdata', 
							'url', 
							'pr_helper', 
							'cripto', 
							'gera_senha', 
							'log', 
							'mascara', 
							'horas', 
							'cpf',
							'telefone',
							'moeda',
							'download',
							'sql',
							'language',
							'usuario'
						);
						
$autoload['model'] 		= array(
							'imagem_model',
							'empresa_model',
							'ajuda_model',
							'enviar_email',
							'download_model',
							'acesso_model', 
							'projeto_model', 
							'usuario_model', 
							'tarefa_model', 
							'etapa_model', 
							'financeiro_model',
							'cliente_model',
							'mensagem_model',
							'relatorio_model'
						);

$autoload['language'] 	= array(
							'button',
							'controller',
							'project',
							'menu'
						);

$autoload['packages'] 	= array();
$autoload['config'] 	= array();
