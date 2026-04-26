<?php
/**
 * ============================================================================
 * AJAX Handler: Reset do Sistema (com processamento em lotes)
 * ============================================================================
 *
 * Processa requisições AJAX para resetar dados CMB2 dos usuários.
 * Usa sistema de lotes para evitar timeout e reportar progresso.
 *
 * Endpoints:
 * - reset_sistema_info  → Retorna contagens totais para calcular progresso
 * - reset_sistema_batch → Processa um lote e retorna progresso
 *
 * Arquivo: functions/reset-sistema-ajax.php
 * Usado em: functions.php (require_once)
 * ============================================================================
 */

define('RESET_BATCH_SIZE', 20); // Usuários por lote

// ============================================================================
// ENDPOINT: reset_sistema_info
// Retorna totais para cálculo de progresso antes de iniciar
// ============================================================================
add_action('wp_ajax_reset_sistema_info', 'reset_sistema_info_callback');
function reset_sistema_info_callback()
{
    check_ajax_referer('reset_sistema_nonce', 'nonce');
    if (!current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'Acesso negado.'));
    }

    $tipo = sanitize_text_field($_POST['tipo_reset']);
    $tipos_validos = array('pontuacao', 'simon_meta', 'simon_logs', 'perguntas', 'tudo');
    if (!in_array($tipo, $tipos_validos, true)) {
        wp_send_json_error(array('message' => 'Tipo de reset inválido.'));
    }

    // Conta subscribers
    $total_users = count(get_users(array('role' => 'subscriber', 'fields' => 'ID')));

    // Conta logs simon
    $total_logs = 0;
    if ($tipo === 'simon_logs' || $tipo === 'tudo') {
        $total_logs = count(get_posts(array(
            'post_type' => 'simon_log',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids',
        )));
    }

    // Determina quantos passos (steps) serão necessários
    $steps = reset_calculate_steps($tipo, $total_users, $total_logs);

    wp_send_json_success(array(
        'total_users' => $total_users,
        'total_logs' => $total_logs,
        'total_steps' => $steps,
        'batch_size' => RESET_BATCH_SIZE,
    ));
}

/**
 * Calcula o total de passos para um tipo de reset
 */
function reset_calculate_steps($tipo, $total_users, $total_logs)
{
    $steps = 0;
    $batches_users = max(1, ceil($total_users / RESET_BATCH_SIZE));

    if ($tipo === 'pontuacao' || $tipo === 'tudo') {
        $steps += $batches_users;
    }
    if ($tipo === 'simon_meta' || $tipo === 'tudo') {
        $steps += $batches_users;
    }
    if ($tipo === 'simon_logs' || $tipo === 'tudo') {
        $steps += max(1, ceil($total_logs / RESET_BATCH_SIZE));
    }
    if ($tipo === 'perguntas' || $tipo === 'tudo') {
        $steps += $batches_users;
    }
    return $steps;
}

// ============================================================================
// ENDPOINT: reset_sistema_batch
// Processa um lote de dados e retorna progresso
// ============================================================================
add_action('wp_ajax_reset_sistema_batch', 'reset_sistema_batch_callback');
function reset_sistema_batch_callback()
{
    check_ajax_referer('reset_sistema_nonce', 'nonce');
    if (!current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'Acesso negado.'));
    }

    @set_time_limit(120);

    $tipo = sanitize_text_field($_POST['tipo_reset']);
    $fase = sanitize_text_field($_POST['fase']);       // pontuacao, simon_meta, simon_logs, perguntas
    $offset = intval($_POST['offset']);
    $batch_size = RESET_BATCH_SIZE;

    $tipos_validos = array('pontuacao', 'simon_meta', 'simon_logs', 'perguntas', 'tudo');
    if (!in_array($tipo, $tipos_validos, true)) {
        wp_send_json_error(array('message' => 'Tipo de reset inválido.'));
    }

    $result = array(
        'processed' => 0,
        'done' => false,
        'detail' => '',
    );

    switch ($fase) {
        case 'pontuacao':
            $result = reset_batch_pontuacoes($offset, $batch_size);
            break;
        case 'simon_meta':
            $result = reset_batch_simon_meta($offset, $batch_size);
            break;
        case 'simon_logs':
            $result = reset_batch_simon_logs($offset, $batch_size);
            break;
        case 'perguntas':
            $result = reset_batch_perguntas($offset, $batch_size);
            break;
        default:
            wp_send_json_error(array('message' => 'Fase inválida: ' . $fase));
    }

    wp_send_json_success($result);
}

/**
 * Processa um lote de reset de pontuações
 */
function reset_batch_pontuacoes($offset, $batch_size)
{
    $campos = array(
        'pontuacao_obtida',
        'tempo_partida',
        'horario',
        'numero_tentativas',
        'data_de_partida',
        'ip_usuario',
    );

    $users = get_users(array(
        'role' => 'subscriber',
        'fields' => 'ID',
        'number' => $batch_size,
        'offset' => $offset,
        'orderby' => 'ID',
        'order' => 'ASC',
    ));

    $count = 0;
    foreach ($users as $user_id) {
        foreach ($campos as $campo) {
            delete_user_meta($user_id, $campo);
        }
        $count++;
    }

    return array(
        'processed' => $count,
        'done' => ($count < $batch_size),
        'detail' => "Pontuações: {$count} usuários processados (offset {$offset})",
    );
}

/**
 * Processa um lote de reset Simon Says meta
 */
function reset_batch_simon_meta($offset, $batch_size)
{
    $campos = array(
        'simon_posicao_ranking',
        'simon_tentativas_restantes_hoje',
        'simon_data_ultima_tentativa',
    );

    $users = get_users(array(
        'role' => 'subscriber',
        'fields' => 'ID',
        'number' => $batch_size,
        'offset' => $offset,
        'orderby' => 'ID',
        'order' => 'ASC',
    ));

    $count = 0;
    foreach ($users as $user_id) {
        foreach ($campos as $campo) {
            delete_user_meta($user_id, $campo);
        }
        $count++;
    }

    return array(
        'processed' => $count,
        'done' => ($count < $batch_size),
        'detail' => "Simon Says meta: {$count} usuários processados (offset {$offset})",
    );
}

/**
 * Processa um lote de deleção de logs Simon Says
 */
function reset_batch_simon_logs($offset, $batch_size)
{
    // Sempre pega os primeiros N pois estamos deletando
    $logs = get_posts(array(
        'post_type' => 'simon_log',
        'posts_per_page' => $batch_size,
        'post_status' => 'any',
        'fields' => 'ids',
        'orderby' => 'ID',
        'order' => 'ASC',
    ));

    $count = 0;
    foreach ($logs as $log_id) {
        wp_delete_post($log_id, true);
        $count++;
    }

    return array(
        'processed' => $count,
        'done' => ($count < $batch_size),
        'detail' => "Logs Simon: {$count} registros deletados",
    );
}

/**
 * Processa um lote de reset de respostas de perguntas
 */
function reset_batch_perguntas($offset, $batch_size)
{
    // Obtém slugs das datas
    $terms = get_terms(array(
        'taxonomy' => 'datas_perguntas',
        'hide_empty' => false,
    ));
    $datas_slugs = array();
    foreach ($terms as $term) {
        $datas_slugs[] = $term->slug;
    }

    // Obtém IDs de perguntas
    $perguntas = get_posts(array(
        'post_type' => 'perguntas',
        'posts_per_page' => -1,
        'post_status' => 'any',
        'fields' => 'ids',
    ));

    $users = get_users(array(
        'role' => 'subscriber',
        'fields' => 'ID',
        'number' => $batch_size,
        'offset' => $offset,
        'orderby' => 'ID',
        'order' => 'ASC',
    ));

    $count_users = 0;
    $count_metas = 0;

    foreach ($users as $user_id) {
        foreach ($datas_slugs as $slug) {
            $campos_data = array(
                'presencial_' . $slug,
                'video_concluido_' . $slug,
                'todas_alternativa_' . $slug,
                'acertou_todas_alternativas_' . $slug,
                'acessou_' . $slug,
                'horario_da_data_de_' . $slug,
                'classificado_' . $slug,
            );

            foreach ($campos_data as $campo) {
                if (delete_user_meta($user_id, $campo)) {
                    $count_metas++;
                }
            }

            foreach ($perguntas as $pergunta_id) {
                $meta_key = 'user_field_' . $slug . '_' . $pergunta_id;
                if (delete_user_meta($user_id, $meta_key)) {
                    $count_metas++;
                }
            }
        }
        $count_users++;
    }

    return array(
        'processed' => $count_users,
        'done' => ($count_users < $batch_size),
        'detail' => "Perguntas: {$count_users} usuários, {$count_metas} campos (offset {$offset})",
    );
}
