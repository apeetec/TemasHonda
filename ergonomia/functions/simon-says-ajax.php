<?php
/**
 * ============================================================================
 * AJAX Handler: Simon Says Game
 * ============================================================================
 * 
 * Endpoints AJAX para o jogo Simon Says:
 * 
 * 1. simon_get_status   → Retorna status do jogador (tentativas restantes, etc.)
 * 2. simon_save_game    → Salva resultado da partida (user meta + log CPT)
 * 
 * Segurança: todos os endpoints usam verificação de nonce e autenticação.
 * 
 * Arquivo: functions/simon-says-ajax.php
 * Usado em: functions.php (require_once)
 * ============================================================================
 */

// ============================================================================
// HELPER: Obtém o IP real do usuário
// ============================================================================
function simon_get_user_ip()
{
    // Verifica proxies e cabeçalhos comuns
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // HTTP_X_FORWARDED_FOR pode conter múltiplos IPs separados por vírgula
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ip[0]);
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return sanitize_text_field($ip);
}

// ============================================================================
// HELPER: Recalcula posição no ranking para todos os usuários com pontuação
// ============================================================================
function simon_recalculate_rankings()
{
    // Busca todos os usuários que possuem pontuação > 0
    $users = get_users(array(
        'meta_query' => array(
            array(
                'key' => 'pontuacao_obtida',
                'value' => '0',
                'compare' => '>',
                'type' => 'NUMERIC',
            ),
        ),
    ));

    // Monta array com dados para ordenação
    $ranking_data = array();
    foreach ($users as $user) {
        $pontuacao = intval(get_user_meta($user->ID, 'pontuacao_obtida', true));
        $tempo = get_user_meta($user->ID, 'tempo_partida', true);
        $data = get_user_meta($user->ID, 'data_de_partida', true);
        $horario = get_user_meta($user->ID, 'horario', true);

        // Só inclui quem tem pontuação > 0
        if ($pontuacao > 0) {
            $ranking_data[] = array(
                'user_id' => $user->ID,
                'pontuacao' => $pontuacao,
                'tempo' => $tempo,
                'data' => $data,
                'horario' => $horario,
                // Converte tempo (MM:SS) para segundos para ordenação
                'tempo_seg' => simon_time_to_seconds($tempo),
                // Timestamp para desempate por data/hora
                'timestamp' => strtotime($data . ' ' . $horario),
            );
        }
    }

    // Ordena: maior pontuação > menor tempo > data/hora mais antiga (quem fez primeiro)
    usort($ranking_data, function ($a, $b) {
        // 1. Maior pontuação primeiro
        if ($a['pontuacao'] !== $b['pontuacao']) {
            return $b['pontuacao'] - $a['pontuacao'];
        }
        // 2. Menor tempo primeiro (desempate)
        if ($a['tempo_seg'] !== $b['tempo_seg']) {
            return $a['tempo_seg'] - $b['tempo_seg'];
        }
        // 3. Quem jogou primeiro (timestamp mais antigo)
        return $a['timestamp'] - $b['timestamp'];
    });

    // Atualiza posição no ranking para cada usuário
    foreach ($ranking_data as $pos => $data) {
        update_user_meta($data['user_id'], 'simon_posicao_ranking', $pos + 1);
    }
}

// ============================================================================
// HELPER: Converte tempo no formato MM:SS para segundos
// ============================================================================
function simon_time_to_seconds($time_str)
{
    if (empty($time_str))
        return 0;
    $parts = explode(':', $time_str);
    if (count($parts) === 2) {
        return (intval($parts[0]) * 60) + intval($parts[1]);
    }
    return 0;
}

// ============================================================================
// HELPER: Verifica e reseta tentativas diárias se necessário
// Retorna o número de tentativas restantes atualizado
// ============================================================================
function simon_check_daily_reset($user_id)
{
    $hoje = date('Y-m-d');
    $data_ultima = get_user_meta($user_id, 'simon_data_ultima_tentativa', true);
    $tentativas_rest = get_user_meta($user_id, 'simon_tentativas_restantes_hoje', true);

    // Se não tem data registrada ou a data é diferente de hoje → reseta para 3
    if (empty($data_ultima) || $data_ultima !== $hoje) {
        update_user_meta($user_id, 'simon_tentativas_restantes_hoje', 3);
        update_user_meta($user_id, 'simon_data_ultima_tentativa', $hoje);
        update_user_meta($user_id, 'numero_tentativas', 0);
        return 3;
    }

    // Se não tem valor definido, assume 3
    if ($tentativas_rest === '' || $tentativas_rest === false) {
        update_user_meta($user_id, 'simon_tentativas_restantes_hoje', 3);
        return 3;
    }

    return intval($tentativas_rest);
}

// ============================================================================
// ENDPOINT: simon_get_status
// Retorna status atual do jogador (tentativas restantes, pontuação, ranking)
// ============================================================================
add_action('wp_ajax_simon_get_status', 'simon_get_status_callback');
function simon_get_status_callback()
{
    // Verifica nonce de segurança
    check_ajax_referer('simon_says_nonce', 'nonce');

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(array('message' => 'Usuário não autenticado.'));
    }

    // Verifica reset diário e obtém tentativas restantes
    $tentativas_restantes = simon_check_daily_reset($user_id);
    $tentativas_usadas = intval(get_user_meta($user_id, 'numero_tentativas', true));
    $pontuacao_max = get_user_meta($user_id, 'pontuacao_obtida', true);
    $posicao_ranking = get_user_meta($user_id, 'simon_posicao_ranking', true);

    wp_send_json_success(array(
        'tentativas_restantes' => $tentativas_restantes,
        'tentativas_usadas' => $tentativas_usadas,
        'pontuacao_maxima' => intval($pontuacao_max),
        'posicao_ranking' => intval($posicao_ranking),
    ));
}

// ============================================================================
// ENDPOINT: simon_start_game
// Consome UMA tentativa no momento em que o jogo é iniciado.
// Isso impede que o usuário recarregue a página para recuperar tentativas.
// ============================================================================
add_action('wp_ajax_simon_start_game', 'simon_start_game_callback');
function simon_start_game_callback()
{
    check_ajax_referer('simon_says_nonce', 'nonce');

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(array('message' => 'Usuário não autenticado.'));
    }

    // Verifica reset diário e obtém tentativas restantes
    $tentativas_restantes = simon_check_daily_reset($user_id);

    if ($tentativas_restantes <= 0) {
        wp_send_json_error(array('message' => 'Você já esgotou suas 3 tentativas de hoje. Volte amanhã!'));
    }

    // Decrementa tentativas IMEDIATAMENTE ao iniciar (antes de qualquer refresh)
    $novas_tentativas   = $tentativas_restantes - 1;
    $tentativas_usadas  = intval(get_user_meta($user_id, 'numero_tentativas', true)) + 1;

    update_user_meta($user_id, 'simon_tentativas_restantes_hoje', $novas_tentativas);
    update_user_meta($user_id, 'numero_tentativas', $tentativas_usadas);
    update_user_meta($user_id, 'simon_data_ultima_tentativa', date('Y-m-d'));

    wp_send_json_success(array(
        'tentativas_restantes' => $novas_tentativas,
        'tentativas_usadas'    => $tentativas_usadas,
    ));
}

// ============================================================================
// ENDPOINT: simon_save_game
// Salva resultado da partida: atualiza user_meta e cria post simon_log.
// NOTA: a tentativa já foi consumida em simon_start_game_callback (ao iniciar).
// ============================================================================
add_action('wp_ajax_simon_save_game', 'simon_save_game_callback');
function simon_save_game_callback()
{
    // Verifica nonce de segurança
    check_ajax_referer('simon_says_nonce', 'nonce');

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(array('message' => 'Usuário não autenticado.'));
    }

    // Recebe e sanitiza dados do jogo
    $pontuacao = intval($_POST['pontuacao']);
    $tempo = sanitize_text_field($_POST['tempo']);
    $data = date('d/m/Y');
    $horario = date('H:i:s');
    $ip = simon_get_user_ip();

    // Obtém tentativas restantes (já decrementadas em simon_start_game ao iniciar)
    $tentativas_restantes = simon_check_daily_reset($user_id);

    // Número da tentativa atual já foi incrementado em simon_start_game
    $tentativas_usadas = intval(get_user_meta($user_id, 'numero_tentativas', true));
    $tentativa_atual = $tentativas_usadas;

    // Dados do usuário para o log
    $user_info = get_userdata($user_id);
    $user_name = $user_info->first_name ? $user_info->first_name : $user_info->display_name;
    $unidade = get_user_meta($user_id, 'user_field_unidade', true);
    if (empty($unidade)) {
        $unidade = get_user_meta($user_id, 'unidade_usuario', true);
    }

    // ========================================================================
    // 1. CRIA POST DE LOG (simon_log)
    // ========================================================================
    $log_title = sprintf(
        'Simon Log — %s — %s — Tentativa %d',
        $user_name,
        $data,
        $tentativa_atual
    );

    $log_post_id = wp_insert_post(array(
        'post_type' => 'simon_log',
        'post_title' => $log_title,
        'post_status' => 'publish',
    ));

    if ($log_post_id && !is_wp_error($log_post_id)) {
        // Salva campos CMB2 do log
        update_post_meta($log_post_id, 'log_user_id', $user_id);
        update_post_meta($log_post_id, 'log_user_name', $user_name);
        update_post_meta($log_post_id, 'log_unidade', $unidade);
        update_post_meta($log_post_id, 'log_pontuacao', $pontuacao);
        update_post_meta($log_post_id, 'log_tempo_total', $tempo);
        update_post_meta($log_post_id, 'log_data', $data);
        update_post_meta($log_post_id, 'log_horario', $horario);
        update_post_meta($log_post_id, 'log_numero_tentativa', $tentativa_atual);
        update_post_meta($log_post_id, 'log_ip_acesso', $ip);
    }

    // ========================================================================
    // 2. ATUALIZA USER META
    // ========================================================================

    // Atualiza IP de acesso
    update_user_meta($user_id, 'ip_usuario', $ip);

    // Verifica se é nova pontuação máxima (best score)
    $pontuacao_atual_max = intval(get_user_meta($user_id, 'pontuacao_obtida', true));
    if ($pontuacao > $pontuacao_atual_max) {
        // Nova pontuação recorde — atualiza todos os campos relacionados
        update_user_meta($user_id, 'pontuacao_obtida', $pontuacao);
        update_user_meta($user_id, 'tempo_partida', $tempo);
        update_user_meta($user_id, 'data_de_partida', $data);
        update_user_meta($user_id, 'horario', $horario);
    }

    // Contadores de tentativas já foram atualizados em simon_start_game
    // (nenhum decremento adicional aqui)

    // ========================================================================
    // 3. RECALCULA RANKING
    // ========================================================================
    simon_recalculate_rankings();

    // Obtém ranking atualizado para retornar
    $ranking_top5 = simon_get_top5_ranking();

    wp_send_json_success(array(
        'message' => 'Partida salva com sucesso!',
        'pontuacao' => $pontuacao,
        'tempo' => $tempo,
        'tentativa' => $tentativa_atual,
        'tentativas_restantes' => $tentativas_restantes,
        'novo_recorde' => ($pontuacao > $pontuacao_atual_max),
        'ranking' => $ranking_top5,
    ));
}

// ============================================================================
// HELPER: Retorna array com Top 5 ranking
// ============================================================================
function simon_get_top5_ranking()
{
    // Busca todos os usuários que possuem pontuação > 0
    $users = get_users(array(
        'meta_query' => array(
            array(
                'key' => 'pontuacao_obtida',
                'value' => '0',
                'compare' => '>',
                'type' => 'NUMERIC',
            ),
        ),
    ));

    $ranking = array();
    foreach ($users as $user) {
        $pontuacao = intval(get_user_meta($user->ID, 'pontuacao_obtida', true));
        if ($pontuacao <= 0)
            continue;

        $tempo = get_user_meta($user->ID, 'tempo_partida', true);
        $data = get_user_meta($user->ID, 'data_de_partida', true);
        $horario = get_user_meta($user->ID, 'horario', true);
        $unidade = get_user_meta($user->ID, 'user_field_unidade', true);
        if (empty($unidade)) {
            $unidade = get_user_meta($user->ID, 'unidade_usuario', true);
        }

        $ranking[] = array(
            'nome' => $user->first_name ? $user->first_name : $user->display_name,
            'unidade' => $unidade,
            'pontuacao' => $pontuacao,
            'tempo' => $tempo,
            'data' => $data,
            'horario' => $horario,
            'tempo_seg' => simon_time_to_seconds($tempo),
            'timestamp' => strtotime(str_replace('/', '-', $data) . ' ' . $horario),
        );
    }

    // Ordena: maior pontuação > menor tempo > quem fez primeiro
    usort($ranking, function ($a, $b) {
        if ($a['pontuacao'] !== $b['pontuacao']) {
            return $b['pontuacao'] - $a['pontuacao'];
        }
        if ($a['tempo_seg'] !== $b['tempo_seg']) {
            return $a['tempo_seg'] - $b['tempo_seg'];
        }
        return $a['timestamp'] - $b['timestamp'];
    });

    // Retorna apenas Top 5
    return array_slice($ranking, 0, 5);
}
?>