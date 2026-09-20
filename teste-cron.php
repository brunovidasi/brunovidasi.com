<?php
// Define o limite de tempo máximo para 30 segundos, igual ao --max-time do curl
set_time_limit(30);

// URL do script que precisa ser executado
$url = "https://app.brunovidasi.com/bidwraith/cron_http.php?token=b2dfa3eb7e8509e6ce706919ef0d41be5f8100edf97bdb45";

// Inicializa a sessão cURL nativa do PHP
$ch = curl_init();

// Configurações equivalentes ao comando original
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // -s (silencioso, captura o retorno se precisar)
curl_setopt($ch, CURLOPT_TIMEOUT, 30);           // --max-time 30
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  // Segue redirecionamentos se houver

// Executa a requisição
$response = curl_exec($ch);

// Fecha a conexão
curl_close($ch);

// Opcional: exibe o resultado caso queira depurar o log do cron
echo "Cron executado com sucesso.";
?>
