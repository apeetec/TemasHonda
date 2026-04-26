<?php
/**
 * ============================================================================
 * CMB2 Metabox: Simon Says Log Fields
 * ============================================================================
 * 
 * Define os campos CMB2 para o Custom Post Type "simon_log".
 * Cada log registra informações detalhadas de uma partida do Simon Says.
 * 
 * Campos registrados:
 * - log_user_id          → ID do usuário WordPress
 * - log_user_name        → Nome do usuário
 * - log_unidade          → Unidade/empresa do usuário
 * - log_pontuacao        → Pontuação obtida na partida
 * - log_tempo_total      → Tempo total da partida (MM:SS)
 * - log_data             → Data da partida (dd/mm/aaaa)
 * - log_horario          → Horário da partida (HH:MM:SS)
 * - log_numero_tentativa → Número da tentativa do dia (1, 2 ou 3)
 * - log_ip_acesso        → IP de acesso do usuário
 * 
 * Arquivo: functions/cmb2/cmb2-simon-log.php
 * Usado em: functions.php (require_once)
 * ============================================================================
 */

add_action('cmb2_init', 'cmb2_simon_log_fields');
function cmb2_simon_log_fields()
{

    // Cria o metabox para o CPT simon_log
    $cmb_log = new_cmb2_box(array(
        'id' => 'simon_log_metabox',
        'title' => 'Detalhes da Partida',
        'object_types' => array('simon_log'),   // Vinculado ao CPT simon_log
        'context' => 'normal',
        'priority' => 'high',
        'show_names' => true,
    ));

    // Campo: ID do usuário
    $cmb_log->add_field(array(
        'name' => 'ID do Usuário',
        'desc' => 'ID do usuário WordPress que jogou',
        'id' => 'log_user_id',
        'type' => 'text',
        'column' => array(
            'position' => 2,
            'name' => 'User ID',
        ),
    ));

    // Campo: Nome do usuário
    $cmb_log->add_field(array(
        'name' => 'Nome do Usuário',
        'desc' => 'Nome completo do jogador',
        'id' => 'log_user_name',
        'type' => 'text',
        'column' => array(
            'position' => 3,
            'name' => 'Nome',
        ),
    ));

    // Campo: Unidade do usuário
    $cmb_log->add_field(array(
        'name' => 'Unidade',
        'desc' => 'Unidade/empresa do usuário',
        'id' => 'log_unidade',
        'type' => 'text',
        'column' => array(
            'position' => 4,
            'name' => 'Unidade',
        ),
    ));

    // Campo: Pontuação obtida
    $cmb_log->add_field(array(
        'name' => 'Pontuação',
        'desc' => 'Pontuação obtida na partida (número de sequências corretas)',
        'id' => 'log_pontuacao',
        'type' => 'text',
        'column' => array(
            'position' => 5,
            'name' => 'Pontuação',
        ),
    ));

    // Campo: Tempo total da partida
    $cmb_log->add_field(array(
        'name' => 'Tempo Total',
        'desc' => 'Tempo total da partida (MM:SS)',
        'id' => 'log_tempo_total',
        'type' => 'text',
        'column' => array(
            'position' => 6,
            'name' => 'Tempo',
        ),
    ));

    // Campo: Data da partida
    $cmb_log->add_field(array(
        'name' => 'Data',
        'desc' => 'Data em que a partida foi jogada',
        'id' => 'log_data',
        'type' => 'text',
    ));

    // Campo: Horário da partida
    $cmb_log->add_field(array(
        'name' => 'Horário',
        'desc' => 'Horário em que a partida foi jogada',
        'id' => 'log_horario',
        'type' => 'text',
    ));

    // Campo: Número da tentativa do dia
    $cmb_log->add_field(array(
        'name' => 'Nº da Tentativa',
        'desc' => 'Número da tentativa do dia (1, 2 ou 3)',
        'id' => 'log_numero_tentativa',
        'type' => 'text',
    ));

    // Campo: IP de acesso
    $cmb_log->add_field(array(
        'name' => 'IP de Acesso',
        'desc' => 'Endereço IP do usuário durante a partida',
        'id' => 'log_ip_acesso',
        'type' => 'text',
    ));
}
?>