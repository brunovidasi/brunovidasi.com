<?php

$mensagem_sucesso = $this->session->flashdata('mensagem_sucesso');
$mensagem_erro = $this->session->flashdata('mensagem_erro');
$mensagem_atencao = $this->session->flashdata('mensagem_atencao');

if (!empty($mensagem_sucesso)) {
    echo '<div class="alert alert-success"><button type="button" class="close" data-dismiss="alert">&times;</button>' . $mensagem_sucesso . '</div>';
} else if (!empty($mensagem_erro)) {
    echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>' . $mensagem_erro . '</div>';
} else if (!empty($mensagem_atencao)) {
    echo '<div class="alert alert-warning"><button type="button" class="close" data-dismiss="alert">&times;</button>' . $mensagem_atencao . '</div>';
}
?>	