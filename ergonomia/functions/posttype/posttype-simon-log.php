<?php
/**
 * ============================================================================
 * Custom Post Type: Simon Says Log (simon_log)
 * ============================================================================
 * 
 * Registra o CPT "simon_log" para armazenar registros (logs) de cada partida
 * do jogo Simon Says. Cada post representa uma tentativa/partida finalizada.
 * 
 * Visível apenas para administradores no painel WordPress.
 * 
 * Arquivo: functions/posttype/posttype-simon-log.php
 * Usado em: functions.php (require_once)
 * ============================================================================
 */

// Registra o Custom Post Type simon_log
function custom_post_type_simon_log()
{

    // Labels do CPT para exibição no painel administrativo
    $labels = array(
        'name' => _x('Simon Logs', 'Post Type General Name', 'text_domain'),
        'singular_name' => _x('Simon Log', 'Post Type Singular Name', 'text_domain'),
        'menu_name' => __('Simon Logs', 'text_domain'),
        'name_admin_bar' => __('Simon Log', 'text_domain'),
        'archives' => __('Arquivo de Logs', 'text_domain'),
        'attributes' => __('Atributos do Log', 'text_domain'),
        'parent_item_colon' => __('Log Pai:', 'text_domain'),
        'all_items' => __('Todos os Logs', 'text_domain'),
        'add_new_item' => __('Novo Log', 'text_domain'),
        'add_new' => __('Novo', 'text_domain'),
        'new_item' => __('Novo Log', 'text_domain'),
        'edit_item' => __('Editar Log', 'text_domain'),
        'update_item' => __('Atualizar Log', 'text_domain'),
        'view_item' => __('Visualizar Log', 'text_domain'),
        'view_items' => __('Visualizar Logs', 'text_domain'),
        'search_items' => __('Buscar Logs', 'text_domain'),
        'not_found' => __('Nenhum log encontrado', 'text_domain'),
        'not_found_in_trash' => __('Nenhum log na lixeira', 'text_domain'),
        'featured_image' => __('Imagem Destaque', 'text_domain'),
        'set_featured_image' => __('Definir imagem destaque', 'text_domain'),
        'remove_featured_image' => __('Remover imagem destaque', 'text_domain'),
        'use_featured_image' => __('Usar como imagem destaque', 'text_domain'),
        'insert_into_item' => __('Inserir no log', 'text_domain'),
        'uploaded_to_this_item' => __('Enviado para este log', 'text_domain'),
        'items_list' => __('Lista de logs', 'text_domain'),
        'items_list_navigation' => __('Navegação da lista', 'text_domain'),
        'filter_items_list' => __('Filtrar logs', 'text_domain'),
    );

    // Argumentos de configuração do CPT
    $args = array(
        'label' => __('Simon Logs', 'text_domain'),
        'description' => __('Registros de partidas do jogo Simon Says', 'text_domain'),
        'labels' => $labels,
        'supports' => array('title'),       // Suporta apenas título
        'hierarchical' => false,
        'public' => false,                   // Não é público no front-end
        'show_ui' => true,                    // Visível no painel admin
        'show_in_menu' => true,
        'menu_position' => 25,                      // Posição no menu admin
        'menu_icon' => 'dashicons-games',       // Ícone do menu
        'show_in_admin_bar' => false,
        'show_in_nav_menus' => false,
        'can_export' => true,
        'has_archive' => false,
        'exclude_from_search' => true,
        'publicly_queryable' => false,
        'capability_type' => 'post',
        // Restringe acesso ao CPT apenas para administradores
        'capabilities' => array(
            'edit_post' => 'manage_options',
            'read_post' => 'manage_options',
            'delete_post' => 'manage_options',
            'edit_posts' => 'manage_options',
            'edit_others_posts' => 'manage_options',
            'publish_posts' => 'manage_options',
            'read_private_posts' => 'manage_options',
        ),
    );

    // Registra o CPT
    register_post_type('simon_log', $args);
}
add_action('init', 'custom_post_type_simon_log', 0);
?>