<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

if (!function_exists('pr')) {
	 
    function pr($var = "", $label = "Teste Array - Objeto") {
        ?>
        <div>
            <?php
            echo "{$label}";
            if (!empty($var)) {
                echo ": <pre>";
                print_r($var);
                echo "</pre><br/>";
            }
            ?>
        </div>
        <?php
    }
}

if (!function_exists('pexit')) {

    function pexit($var, $label = "Teste Array - Objeto", $local = '') {
        pr($var, $label);
        echo '<hr />';
        exit($local);
    }
}