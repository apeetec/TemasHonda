<?php
/**
 * Sistema de Sorteador e Ganhadores da SIPAT
 * Tema SIPAT Honda
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. Registro do Custom Post Type 'sorteios_sipat' e da Taxonomia 'categorias_sorteios'
 */
function sipat_register_cpt_and_tax_sorteios() {
    
    // Labels do CPT
    $labels_cpt = array(
        'name'                  => _x('Sorteios SIPAT', 'Post Type General Name', 'sipat'),
        'singular_name'         => _x('Sorteio SIPAT', 'Post Type Singular Name', 'sipat'),
        'menu_name'             => __('Sorteios', 'sipat'),
        'all_items'             => __('Todos os Sorteios', 'sipat'),
        'add_new_item'          => __('Adicionar Novo Sorteio', 'sipat'),
        'add_new'               => __('Novo Sorteio', 'sipat'),
        'edit_item'             => __('Editar Sorteio', 'sipat'),
        'update_item'           => __('Atualizar Sorteio', 'sipat'),
        'search_items'          => __('Buscar Sorteio', 'sipat'),
        'not_found'             => __('Nenhum sorteio encontrado', 'sipat'),
        'not_found_in_trash'    => __('Nenhum sorteio na lixeira', 'sipat'),
    );

    $args_cpt = array(
        'label'                 => __('Sorteio SIPAT', 'sipat'),
        'labels'                => $labels_cpt,
        'supports'              => array('title', 'thumbnail', 'custom-fields'),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => false,
        'show_in_nav_menus'     => true,
        'has_archive'           => true,
        'publicly_queryable'    => true,
        'rewrite'               => array('slug' => 'sorteio', 'with_front' => false),
        'capability_type'       => 'post',
        'map_meta_cap'          => true,
    );

    register_post_type('sorteios_sipat', $args_cpt);

    // Labels da Taxonomia de Sorteios
    $labels_tax = array(
        'name'                       => _x('Categorias de Sorteios', 'Taxonomy General Name', 'sipat'),
        'singular_name'              => _x('Categoria de Sorteio', 'Taxonomy Singular Name', 'sipat'),
        'menu_name'                  => __('Categorias de Sorteios', 'sipat'),
        'all_items'                  => __('Todas as Categorias', 'sipat'),
        'parent_item'                => __('Categoria Pai', 'sipat'),
        'parent_item_colon'          => __('Categoria Pai:', 'sipat'),
        'new_item_name'              => __('Nova Categoria de Sorteio', 'sipat'),
        'add_new_item'               => __('Adicionar Categoria de Sorteio', 'sipat'),
        'edit_item'                  => __('Editar Categoria de Sorteio', 'sipat'),
        'update_item'                => __('Atualizar Categoria de Sorteio', 'sipat'),
        'view_item'                  => __('Ver Categoria', 'sipat'),
        'separate_items_with_commas' => __('Separe com vírgulas', 'sipat'),
        'add_or_remove_items'        => __('Adicionar ou remover categorias', 'sipat'),
        'choose_from_most_used'      => __('Mais usadas', 'sipat'),
        'popular_items'              => __('Categorias Populares', 'sipat'),
        'search_items'               => __('Buscar Categorias', 'sipat'),
        'not_found'                  => __('Nenhuma categoria encontrada', 'sipat'),
        'no_terms'                   => __('Sem categorias', 'sipat'),
    );

    $args_tax = array(
        'labels'                     => $labels_tax,
        'hierarchical'               => true,
        'public'                     => true,
        'show_ui'                    => true,
        'show_admin_column'          => true,
        'show_in_nav_menus'          => true,
        'show_tagcloud'              => true,
        'query_var'                  => true,
        'rewrite'                    => array(
            'slug'         => 'sorteios',
            'with_front'   => false,
            'hierarchical' => true
        ),
    );

    register_taxonomy('categorias_sorteios', array('sorteios_sipat'), $args_tax);
}
add_action('init', 'sipat_register_cpt_and_tax_sorteios', 0);

/**
 * 2. Regras de Rewrite e Roteamento para /sorteios/
 */
function sipat_sorteios_rewrite_rules() {
    add_rewrite_rule('^sorteios/?$', 'index.php?sipat_pagina_sorteios=1', 'top');
}
add_action('init', 'sipat_sorteios_rewrite_rules');

function sipat_sorteios_query_vars($vars) {
    $vars[] = 'sipat_pagina_sorteios';
    return $vars;
}
add_filter('query_vars', 'sipat_sorteios_query_vars');

/**
 * Previne redirecionamento canônico indevido para /sorteios/
 */
function sipat_disable_sorteios_canonical_redirect($redirect_url, $requested_url) {
    if (strpos($requested_url, '/sorteios') !== false) {
        return false;
    }
    return $redirect_url;
}
add_filter('redirect_canonical', 'sipat_disable_sorteios_canonical_redirect', 10, 2);

/**
 * Carrega o template apropriado para taxonomia ou rota /sorteios/
 */
function sipat_sorteios_template_include($template) {
    if (get_query_var('sipat_pagina_sorteios') || is_tax('categorias_sorteios') || is_tax('sorteios') || is_post_type_archive('sorteios_sipat')) {
        $tax_template = get_template_directory() . '/taxonomy-categorias_sorteios.php';
        if (file_exists($tax_template)) {
            return $tax_template;
        }
    }
    return $template;
}
add_filter('template_include', 'sipat_sorteios_template_include');

/**
 * 3. Registro do Menu Administrativo 'Sorteador SIPAT'
 */
function sipat_add_sorteador_admin_menu() {
    add_menu_page(
        __('Sorteador SIPAT', 'sipat'),
        __('🎰 Sorteador SIPAT', 'sipat'),
        'manage_options',
        'sorteador-sipat',
        'sipat_render_sorteador_admin_page',
        'dashicons-awards',
        6
    );

    add_submenu_page(
        'sorteador-sipat',
        __('Categorias de Sorteios', 'sipat'),
        __('Categorias / Datas', 'sipat'),
        'manage_options',
        'edit-tags.php?taxonomy=categorias_sorteios&post_type=sorteios_sipat'
    );
}
add_action('admin_menu', 'sipat_add_sorteador_admin_menu');

/**
 * 4. Renderização da Página Administrativa
 */
function sipat_render_sorteador_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Você não tem permissão para acessar esta página.'));
    }
    require_once get_template_directory() . '/includes/admin-sorteador-view.php';
}

/**
 * 5. Carregamento de Scripts e Estilos no Admin para a página do sorteador
 */
function sipat_sorteador_admin_assets($hook) {
    if ($hook !== 'toplevel_page_sorteador-sipat') {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css', array(), '6.4.2');
    wp_enqueue_script('canvas-confetti', 'https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js', array(), '1.6.0', true);

    wp_localize_script('canvas-confetti', 'sipatSorteadorData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('sipat_sorteador_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'sipat_sorteador_admin_assets');

/**
 * 6. Helper: Obter todos os termos de datas_perguntas principais
 */
function sipat_get_all_event_dates() {
    $terms = get_terms(array(
        'taxonomy'   => 'datas_perguntas',
        'hide_empty' => false,
        'parent'     => 0,
        'orderby'    => 'term_id',
        'order'      => 'ASC'
    ));

    if (is_wp_error($terms) || empty($terms)) {
        return array();
    }

    $dates = array();
    foreach ($terms as $term) {
        $dates[] = array(
            'id'   => $term->term_id,
            'slug' => sanitize_title($term->name),
            'name' => $term->name,
        );
    }
    return $dates;
}

/**
 * 7. Helper: Obter todas as categorias de sorteios registradas
 */
function sipat_get_all_sorteios_categories() {
    $terms = get_terms(array(
        'taxonomy'   => 'categorias_sorteios',
        'hide_empty' => false,
        'orderby'    => 'term_id',
        'order'      => 'ASC'
    ));

    $categories = array();
    if (!is_wp_error($terms) && !empty($terms)) {
        foreach ($terms as $term) {
            $categories[] = array(
                'id'    => $term->term_id,
                'slug'  => $term->slug,
                'name'  => $term->name,
                'count' => $term->count,
                'link'  => get_term_link($term)
            );
        }
    }
    return $categories;
}

/**
 * 8. Helper: Obter todas as unidades cadastradas
 */
function sipat_get_all_unidades() {
    $terms = get_terms(array(
        'taxonomy'   => 'unidades',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC'
    ));

    $unidades = array();
    if (!is_wp_error($terms) && !empty($terms)) {
        foreach ($terms as $term) {
            $unidades[] = array(
                'id'   => $term->term_id,
                'slug' => $term->slug,
                'name' => $term->name,
            );
        }
    }
    return $unidades;
}

/**
 * 9. Helper: Obter lista de grupos já utilizados anteriormente
 */
function sipat_get_previously_used_groups() {
    global $wpdb;
    $groups = $wpdb->get_col("SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_sipat_nome_grupo' AND meta_value != '' ORDER BY meta_value ASC");
    
    $defaults = array('Grupo Produção', 'Grupo Administrativo', 'Grupo Geral', 'Grupo Concessionárias', 'Grupo Sumaré', 'Grupo Itirapina');
    if (empty($groups)) {
        return $defaults;
    }
    return array_unique(array_merge($defaults, $groups));
}

/**
 * 10. Helper: Obter IDs de usuários já sorteados em sorteios anteriores
 */
function sipat_get_previously_drawn_user_ids() {
    $posts = get_posts(array(
        'post_type'      => 'sorteios_sipat',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids'
    ));

    $drawn_ids = array();
    foreach ($posts as $post_id) {
        $ganhadores = get_post_meta($post_id, '_sipat_ganhadores', true);
        if (is_array($ganhadores)) {
            foreach ($ganhadores as $g) {
                if (!empty($g['user_id'])) {
                    $drawn_ids[] = (int) $g['user_id'];
                }
            }
        }
    }
    return array_unique($drawn_ids);
}

/**
 * 11. Helper: Obter lista de colaboradores elegíveis (100% de acertos)
 */
function sipat_fetch_eligible_users($date_slug, $selected_units = array(), $exclude_drawn = false) {
    $all_dates = sipat_get_all_event_dates();
    $date_slugs = wp_list_pluck($all_dates, 'slug');

    $users = get_users(array(
        'role'    => 'subscriber',
        'orderby' => 'display_name',
        'order'   => 'ASC'
    ));

    $already_drawn = $exclude_drawn ? sipat_get_previously_drawn_user_ids() : array();
    $eligible = array();

    foreach ($users as $user) {
        $user_id = $user->ID;

        if ($exclude_drawn && in_array($user_id, $already_drawn, true)) {
            continue;
        }

        $user_empresa_slug = get_user_meta($user_id, 'user_infos_empresas', true);
        $user_unidade_txt = get_user_meta($user_id, 'unidade_usuario', true);
        $user_empresa_txt = get_user_meta($user_id, 'empresa_usuario', true);
        $user_unit = !empty($user_unidade_txt) ? $user_unidade_txt : (!empty($user_empresa_slug) ? strtoupper($user_empresa_slug) : 'Não informada');

        if (!empty($selected_units) && !in_array('todas', $selected_units, true)) {
            $unit_match = false;
            foreach ($selected_units as $sel_unit) {
                if (
                    strcasecmp($sel_unit, $user_empresa_slug) === 0 ||
                    strcasecmp($sel_unit, $user_unidade_txt) === 0 ||
                    (strpos(strtoupper($user_unit), strtoupper($sel_unit)) !== false)
                ) {
                    $unit_match = true;
                    break;
                }
            }
            if (!$unit_match) {
                continue;
            }
        }

        $is_100_percent = false;

        if ($date_slug === 'todas') {
            $passed_all = true;
            if (empty($date_slugs)) {
                $passed_all = false;
            }
            foreach ($date_slugs as $d_slug) {
                $acertou_todas = get_user_meta($user_id, 'acertou_todas_alternativas_' . $d_slug, true);
                $percentual = get_user_meta($user_id, 'percentual_' . $d_slug, true);
                if ($acertou_todas !== 'on' && (float)$percentual < 100) {
                    $passed_all = false;
                    break;
                }
            }
            $is_100_percent = $passed_all;
        } else {
            $acertou_todas = get_user_meta($user_id, 'acertou_todas_alternativas_' . $date_slug, true);
            $percentual = get_user_meta($user_id, 'percentual_' . $date_slug, true);
            $is_100_percent = ($acertou_todas === 'on' || (float)$percentual >= 100);
        }

        if ($is_100_percent) {
            $nome = get_user_meta($user_id, 'first_name', true);
            if (empty($nome)) {
                $nome = $user->display_name;
            }

            $matricula = str_replace('.', '_', $user->user_login);

            $eligible[] = array(
                'user_id'   => $user_id,
                'matricula' => $matricula,
                'nome'      => $nome,
                'email'     => $user->user_email,
                'unidade'   => $user_unit,
                'empresa'   => !empty($user_empresa_txt) ? $user_empresa_txt : $user_unit,
            );
        }
    }

    return $eligible;
}

/**
 * 12. AJAX: Buscar Usuários Elegíveis
 */
function sipat_ajax_get_elegiveis() {
    check_ajax_referer('sipat_sorteador_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permissão negada.'));
    }

    $date_slug = isset($_POST['date_slug']) ? sanitize_text_field($_POST['date_slug']) : '';
    $selected_units = isset($_POST['units']) && is_array($_POST['units']) ? array_map('sanitize_text_field', $_POST['units']) : array();
    $exclude_drawn = isset($_POST['exclude_drawn']) && $_POST['exclude_drawn'] === 'true';

    if (empty($date_slug)) {
        wp_send_json_error(array('message' => 'Data da SIPAT não informada.'));
    }

    $eligible = sipat_fetch_eligible_users($date_slug, $selected_units, $exclude_drawn);

    wp_send_json_success(array(
        'total'    => count($eligible),
        'usuarios' => $eligible
    ));
}
add_action('wp_ajax_sipat_get_elegiveis', 'sipat_ajax_get_elegiveis');

/**
 * 13. AJAX: Executar Sorteio, Salvar no Banco e Associar à Taxonomia
 */
function sipat_ajax_executar_sorteio() {
    check_ajax_referer('sipat_sorteador_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permissão negada.'));
    }

    $premio_nome      = isset($_POST['premio_nome']) ? sanitize_text_field($_POST['premio_nome']) : '';
    $nome_grupo       = isset($_POST['nome_grupo']) ? sanitize_text_field($_POST['nome_grupo']) : '';
    $premio_imagem_id = isset($_POST['premio_imagem_id']) ? intval($_POST['premio_imagem_id']) : 0;
    $premio_imagem_url = isset($_POST['premio_imagem_url']) ? esc_url_raw($_POST['premio_imagem_url']) : '';
    $date_slug        = isset($_POST['date_slug']) ? sanitize_text_field($_POST['date_slug']) : '';
    $date_name        = isset($_POST['date_name']) ? sanitize_text_field($_POST['date_name']) : '';
    $selected_units   = isset($_POST['units']) && is_array($_POST['units']) ? array_map('sanitize_text_field', $_POST['units']) : array();
    $qtd_ganhadores   = isset($_POST['qtd_ganhadores']) ? max(1, intval($_POST['qtd_ganhadores'])) : 1;
    $exclude_drawn    = isset($_POST['exclude_drawn']) && $_POST['exclude_drawn'] === 'true';

    if (empty($premio_nome)) {
        wp_send_json_error(array('message' => 'Por favor, informe o nome do brinde/prêmio.'));
    }

    if (empty($date_slug)) {
        wp_send_json_error(array('message' => 'Por favor, selecione a data do sorteio.'));
    }

    if (empty($nome_grupo)) {
        if (in_array('todas', $selected_units, true) || empty($selected_units)) {
            $nome_grupo = 'Grupo Geral';
        } else {
            $nome_grupo = 'Grupo: ' . implode(', ', $selected_units);
        }
    }

    $eligible = sipat_fetch_eligible_users($date_slug, $selected_units, $exclude_drawn);
    $total_elegiveis = count($eligible);

    if ($total_elegiveis === 0) {
        wp_send_json_error(array('message' => 'Nenhum colaborador elegível encontrado com os critérios selecionados.'));
    }

    if ($qtd_ganhadores > $total_elegiveis) {
        $qtd_ganhadores = $total_elegiveis;
    }

    $pool = $eligible;
    $ganhadores = array();

    for ($i = 0; $i < $qtd_ganhadores; $i++) {
        $max_index = count($pool) - 1;
        $winner_index = random_int(0, $max_index);
        
        $winner = $pool[$winner_index];
        $winner['data_sorteio'] = current_time('mysql');
        $winner['grupo_sorteio'] = $nome_grupo;
        $ganhadores[] = $winner;

        array_splice($pool, $winner_index, 1);
    }

    $post_title = $premio_nome . ' - ' . $nome_grupo . ' (' . $date_name . ' - ' . current_time('d/m/Y H:i') . ')';

    $post_id = wp_insert_post(array(
        'post_title'   => $post_title,
        'post_type'    => 'sorteios_sipat',
        'post_status'  => 'publish',
        'post_author'  => get_current_user_id(),
    ));

    if (is_wp_error($post_id)) {
        wp_send_json_error(array('message' => 'Erro ao salvar sorteio: ' . $post_id->get_error_message()));
    }

    $term = get_term_by('slug', $date_slug, 'categorias_sorteios');
    if (!$term) {
        $inserted_term = wp_insert_term($date_name, 'categorias_sorteios', array(
            'slug' => $date_slug
        ));
        if (!is_wp_error($inserted_term)) {
            wp_set_object_terms($post_id, (int)$inserted_term['term_id'], 'categorias_sorteios');
        }
    } else {
        wp_set_object_terms($post_id, (int)$term->term_id, 'categorias_sorteios');
    }

    if ($premio_imagem_id > 0) {
        set_post_thumbnail($post_id, $premio_imagem_id);
    }

    update_post_meta($post_id, '_sipat_premio_nome', $premio_nome);
    update_post_meta($post_id, '_sipat_nome_grupo', $nome_grupo);
    update_post_meta($post_id, '_sipat_premio_imagem_url', $premio_imagem_url);
    update_post_meta($post_id, '_sipat_premio_imagem_id', $premio_imagem_id);
    update_post_meta($post_id, '_sipat_date_slug', $date_slug);
    update_post_meta($post_id, '_sipat_date_name', $date_name);
    update_post_meta($post_id, '_sipat_unidades', $selected_units);
    update_post_meta($post_id, '_sipat_qtd_ganhadores', $qtd_ganhadores);
    update_post_meta($post_id, '_sipat_ganhadores', $ganhadores);
    update_post_meta($post_id, '_sipat_total_elegiveis', $total_elegiveis);
    update_post_meta($post_id, '_sipat_data_hora_sorteio', current_time('mysql'));

    $term_link = $term ? get_term_link($term) : home_url('/sorteios/' . $date_slug . '/');

    wp_send_json_success(array(
        'sorteio_id'  => $post_id,
        'premio_nome' => $premio_nome,
        'nome_grupo'  => $nome_grupo,
        'date_name'   => $date_name,
        'ganhadores'  => $ganhadores,
        'total_pool'  => $total_elegiveis,
        'term_link'   => $term_link
    ));
}
add_action('wp_ajax_sipat_executar_sorteio', 'sipat_ajax_executar_sorteio');

/**
 * 14. AJAX: Excluir Sorteio
 */
function sipat_ajax_excluir_sorteio() {
    check_ajax_referer('sipat_sorteador_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permissão negada.'));
    }

    $sorteio_id = isset($_POST['sorteio_id']) ? intval($_POST['sorteio_id']) : 0;

    if ($sorteio_id <= 0) {
        wp_send_json_error(array('message' => 'ID do sorteio inválido.'));
    }

    $result = wp_delete_post($sorteio_id, true);

    if ($result) {
        wp_send_json_success(array('message' => 'Sorteio excluído com sucesso!'));
    } else {
        wp_send_json_error(array('message' => 'Erro ao excluir sorteio.'));
    }
}
add_action('wp_ajax_sipat_excluir_sorteio', 'sipat_ajax_excluir_sorteio');

/**
 * 15. Helper: Obter todos os sorteios formatados para visualização
 */
function sipat_get_all_sorteios_list($filter_term_slug = '') {
    $args = array(
        'post_type'      => 'sorteios_sipat',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC'
    );

    if (!empty($filter_term_slug) && $filter_term_slug !== 'todos') {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'categorias_sorteios',
                'field'    => 'slug',
                'terms'    => $filter_term_slug
            )
        );
    }

    $posts = get_posts($args);

    $sorteios = array();
    foreach ($posts as $post) {
        $post_id = $post->ID;
        $premio_nome = get_post_meta($post_id, '_sipat_premio_nome', true);
        $nome_grupo  = get_post_meta($post_id, '_sipat_nome_grupo', true);
        $date_slug   = get_post_meta($post_id, '_sipat_date_slug', true);
        $date_name   = get_post_meta($post_id, '_sipat_date_name', true);
        $unidades    = get_post_meta($post_id, '_sipat_unidades', true);
        $ganhadores  = get_post_meta($post_id, '_sipat_ganhadores', true);
        $total_pool  = get_post_meta($post_id, '_sipat_total_elegiveis', true);
        $data_hora   = get_post_meta($post_id, '_sipat_data_hora_sorteio', true);
        $img_url     = get_the_post_thumbnail_url($post_id, 'large');
        if (empty($img_url)) {
            $img_url = get_post_meta($post_id, '_sipat_premio_imagem_url', true);
        }

        $sorteios[] = array(
            'id'          => $post_id,
            'title'       => $post->post_title,
            'status'      => $post->post_status,
            'premio_nome' => !empty($premio_nome) ? $premio_nome : $post->post_title,
            'nome_grupo'  => !empty($nome_grupo) ? $nome_grupo : 'Grupo Geral',
            'img_url'     => $img_url,
            'date_slug'   => $date_slug,
            'date_name'   => !empty($date_name) ? $date_name : 'Geral',
            'unidades'    => is_array($unidades) ? $unidades : array(),
            'ganhadores'  => is_array($ganhadores) ? $ganhadores : array(),
            'total_pool'  => $total_pool ? $total_pool : 0,
            'data_hora'   => $data_hora ? $data_hora : $post->post_date
        );
    }
    return $sorteios;
}
