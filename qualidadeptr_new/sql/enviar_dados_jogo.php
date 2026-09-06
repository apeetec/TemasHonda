<?php

    // $path = preg_replace('/wp-content(?!.*wp-content).*/','',__DIR__);
    // require_once($path.'wp-load.php');
    // $user_id = get_current_user_id();
    // // Pegue os parâmetros enviados pelo Ajax
    // $param1 = isset($_POST['tempo_levado']) ? sanitize_text_field($_POST['tempo_levado']) : '';
    // $param2 = isset($_POST['acertos']) ? sanitize_text_field($_POST['acertos']) : '';
    // $user_id = get_current_user_id();

    // // Sua lógica de processamento aqui, por exemplo, um retorno simples:
    // // $response = array(
    // //     'success' => true,
    // //     'message' => 'Recebido com sucesso: ' . $param1 . ' e ' . $param2
    // // );
    // update_user_meta($user_id, 'game_time_left_end_game',$param1.' '.'segundos');
    // update_user_meta($user_id, 'game_clicks', $param2);
    
    
    // wp_send_json($response); // Retorna a resposta no formato JSON


// Defina o caminho para carregar o WordPress
$path = preg_replace('/wp-content(?!.*wp-content).*/','',__DIR__);
require_once($path.'wp-load.php');

// Pegue o ID do usuário atual
$user_id = get_current_user_id();

// Pegue os parâmetros enviados pelo Ajax
$param1 = isset($_POST['tempo_levado']) ? sanitize_text_field($_POST['tempo_levado']) : '';
$param2 = isset($_POST['acertos']) ? sanitize_text_field($_POST['acertos']) : '';

// Verifique se o usuário está logado
if ($user_id === 0) {
    // Se não estiver logado, retorne um erro
    wp_send_json(array(
        'success' => false,
        'message' => 'Usuário não está logado'
    ));
    exit;
}

// Atualiza os metadados do usuário
$update_time = update_user_meta($user_id, 'game_time_left_end_game', $param1 . ' segundos');
$update_clicks = update_user_meta($user_id, 'game_clicks', $param2);

// Verifica se os meta já existiam (update_user_meta retorna false se valor não mudou)
$saved_time = get_user_meta($user_id, 'game_time_left_end_game', true);
$saved_clicks = get_user_meta($user_id, 'game_clicks', true);

if ($saved_time !== '' && $saved_clicks !== '') {
    wp_send_json(array(
        'success' => true,
        'message' => 'Dados salvos com sucesso'
    ));
    exit;
} else {
    // Caso algum update tenha falhado, retorne um erro
    wp_send_json(array(
        'success' => false,
        'message' => 'Falha ao atualizar os dados do usuário'
    ));
    exit;
}

// Se o código chegou até aqui, significa que não houve sucesso na lógica de logout
$response = array(
    'success' => true,
    'message' => 'Dados atualizados com sucesso e usuário deslogado!'
);

wp_send_json($response); // Retorna a resposta no formato JSON
?>

