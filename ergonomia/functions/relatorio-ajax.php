<?php
/**
 * ============================================================================
 * AJAX Handler: Relatório de Usuários
 * ============================================================================
 *
 * Retorna dados dos subscribers para as tabelas de relatório.
 * Dois tipos: 'respostas' (perguntas por data) e 'pontuacoes' (Simon Says).
 *
 * Arquivo: functions/relatorio-ajax.php
 * Usado em: functions.php (require_once)
 * ============================================================================
 */

add_action('wp_ajax_relatorio_get_data', 'relatorio_get_data_callback');
function relatorio_get_data_callback()
{
    check_ajax_referer('relatorio_usuarios_nonce', 'nonce');

    if (!current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'Acesso negado.'));
    }

    $tipo = sanitize_text_field($_POST['tipo']);

    if ($tipo === 'pontuacoes') {
        wp_send_json_success(array('rows' => relatorio_get_pontuacoes()));
    } elseif ($tipo === 'respostas') {
        wp_send_json_success(array('rows' => relatorio_get_respostas()));
    } else {
        wp_send_json_error(array('message' => 'Tipo inválido.'));
    }
}

/**
 * Retorna dados de pontuações (Simon Says) de todos os subscribers
 */
function relatorio_get_pontuacoes()
{
    $users = get_users(array(
        'role' => 'subscriber',
        'orderby' => 'display_name',
        'order' => 'ASC',
    ));

    $rows = array();
    foreach ($users as $user) {
        $uid = $user->ID;
        $unidade = get_user_meta($uid, 'user_field_unidade', true);
        if (empty($unidade)) {
            $unidade = get_user_meta($uid, 'unidade_usuario', true);
        }

        $rows[] = array(
            'matricula' => $user->user_login,
            'nome' => $user->first_name ? $user->first_name : $user->display_name,
            'unidade' => $unidade,
            'pontuacao' => get_user_meta($uid, 'pontuacao_obtida', true),
            'tempo' => get_user_meta($uid, 'tempo_partida', true),
            'ranking' => get_user_meta($uid, 'simon_posicao_ranking', true),
            'tentativas_rest' => get_user_meta($uid, 'simon_tentativas_restantes_hoje', true),
            'tentativas_usadas' => get_user_meta($uid, 'numero_tentativas', true),
            'ultima_tentativa' => get_user_meta($uid, 'simon_data_ultima_tentativa', true),
            'data_partida' => get_user_meta($uid, 'data_de_partida', true),
            'horario' => get_user_meta($uid, 'horario', true),
            'ip' => get_user_meta($uid, 'ip_usuario', true),
        );
    }

    return $rows;
}

/**
 * Retorna dados de respostas de perguntas de todos os subscribers
 */
function relatorio_get_respostas()
{
    // Obtem as datas e perguntas
    $terms = get_terms(array(
        'taxonomy' => 'datas_perguntas',
        'hide_empty' => false,
    ));

    $datas_info = array();
    foreach ($terms as $term) {
        $slug = $term->slug;
        $perguntas = get_posts(array(
            'post_type' => 'perguntas',
            'posts_per_page' => -1,
            'order' => 'ASC',
            'tax_query' => array(
                array(
                    'taxonomy' => 'datas_perguntas',
                    'field' => 'slug',
                    'terms' => $slug,
                )
            )
        ));

        $perg_ids = array();
        foreach ($perguntas as $p) {
            $perg_ids[] = $p->ID;
        }

        $datas_info[] = array(
            'slug' => $slug,
            'perg_ids' => $perg_ids,
        );
    }

    $users = get_users(array(
        'role' => 'subscriber',
        'orderby' => 'display_name',
        'order' => 'ASC',
    ));

    $rows = array();
    foreach ($users as $user) {
        $uid = $user->ID;
        $unidade = get_user_meta($uid, 'user_field_unidade', true);
        if (empty($unidade)) {
            $unidade = get_user_meta($uid, 'unidade_usuario', true);
        }

        $datas = array();
        foreach ($datas_info as $di) {
            $slug = $di['slug'];
            $presencial = get_user_meta($uid, 'presencial_' . $slug, true);
            $video = get_user_meta($uid, 'video_concluido_' . $slug, true);
            $respondeu = get_user_meta($uid, 'todas_alternativa_' . $slug, true);
            $acertou = get_user_meta($uid, 'acertou_todas_alternativas_' . $slug, true);

            $respostas = array();
            foreach ($di['perg_ids'] as $pid) {
                $respostas[] = get_user_meta($uid, 'user_field_' . $slug . '_' . $pid, true);
            }

            $datas[] = array(
                'presencial' => $presencial,
                'video' => !empty($video),
                'respondeu' => !empty($respondeu),
                'acertou' => !empty($acertou),
                'respostas' => $respostas,
            );
        }

        $rows[] = array(
            'matricula' => $user->user_login,
            'nome' => $user->first_name ? $user->first_name : $user->display_name,
            'unidade' => $unidade,
            'datas' => $datas,
        );
    }

    return $rows;
}
