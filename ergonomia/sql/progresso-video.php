<?php

$path = preg_replace('/wp-content(?!.*wp-content).*/','',__DIR__);
require_once($path.'wp-load.php');

// Verifica se o valor foi enviado via POST
if (isset($_POST['Id_usuario'])) {
    // Captura o valor do input
    $id_user = $_POST['Id_usuario'];
    $categoria = $_POST['categoria_video'];
    
    update_user_meta($id_user, 'video_concluido_'.$categoria, 'on');
    echo $categoria;
}
?>
