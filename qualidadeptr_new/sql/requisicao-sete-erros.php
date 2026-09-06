<?php

$path = preg_replace('/wp-content(?!.*wp-content).*/','',__DIR__);
require_once($path.'wp-load.php');
$id_user = get_current_user_id();// Id do usuário  
// Verifica se o valor foi enviado via POST

// if (isset($_POST['sete_erros'])) {
//     // Captura o valor do input 
//     update_user_meta($id_user, 'sete_erros_check', 'on');
// }
// else if(!isset($_POST['sete_erros'])) {
//     update_user_meta($id_user, 'ja_jogou_check', 'on');
// }
?>
