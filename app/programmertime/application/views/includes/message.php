<?php

$message_success = $this->session->flashdata('message_success');
$message_error = $this->session->flashdata('message_error');
$message_warning = $this->session->flashdata('message_warning');

if (!empty($message_success)) {
    echo '<div class="alert alert-success"><button type="button" class="close" data-dismiss="alert">&times;</button>' . $message_success . '</div>';
} else if (!empty($message_error)) {
    echo '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button>' . $message_error . '</div>';
} else if (!empty($message_warning)) {
    echo '<div class="alert alert-warning"><button type="button" class="close" data-dismiss="alert">&times;</button>' . $message_warning . '</div>';
}
?>	