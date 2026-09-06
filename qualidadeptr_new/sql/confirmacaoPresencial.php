<?php

$path = preg_replace('/wp-content(?!.*wp-content).*/','',__DIR__);
require_once($path.'wp-load.php');
$id_user = get_current_user_id();
// Verifica se o valor foi enviado via POST
if(isset($_POST['confirmacao'])){
    update_user_meta($id_user, 'presencial_sexta-feira', 'Presencial');
}
?>
