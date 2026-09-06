<?php
/**
 * Functions and definitions
 * Tema ESG - Sistema de Perguntas e Respostas
 */

// Previne acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Inclui funções auxiliares
require_once get_template_directory() . '/includes/form-helpers.php';
require_once get_template_directory() . '/includes/debug-system.php';

// ============================================================
// [MÉDIO-06] LOG DE SEGURANÇA AUDITÁVEL — GITSP Master #33, #35, #61
// Declarado no TOPO de functions.php para estar disponível em qualquer lugar
// ============================================================

function connectrh_security_log( $event, $user_id = 0, $details = '' ) {
    $ip        = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' );
    $timestamp = current_time( 'mysql' );
    $user_data = $user_id ? get_userdata( $user_id ) : null;
    $username  = $user_data ? $user_data->user_login : 'anon';
    $ua        = sanitize_text_field( substr( $_SERVER['HTTP_USER_AGENT'] ?? '', 0, 150 ) );

    $entry = sprintf(
        '[%s] [%s] user=%s uid=%d ip=%s details="%s" ua="%s"' . PHP_EOL,
        $timestamp, strtoupper( $event ),
        $username, $user_id, $ip, $details, $ua
    );

    $dir  = WP_CONTENT_DIR . '/security-logs';
    $file = $dir . '/connectrh-' . date( 'Y-m' ) . '.log';

    if ( ! is_dir( $dir ) ) {
        wp_mkdir_p( $dir );
        file_put_contents( $dir . '/.htaccess', "Require all denied\n" );
        file_put_contents( $dir . '/index.php', '<?php // Silence is golden' );
    }

    error_log( $entry, 3, $file );
}

// [MÉDIO-06] Logar logout automaticamente
add_action( 'wp_logout', function ( $user_id ) {
    connectrh_security_log( 'LOGOUT', $user_id );
} );

// ============================================================
// [ALTO-03] RATE LIMITING DE LOGIN — Bloqueio após 5 tentativas
// ============================================================

function connectrh_check_login_attempts( $username ) {
    $key = 'login_fail_' . md5( $username . ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
    return (int) get_transient( $key );
}

function connectrh_increment_login_fail( $username ) {
    $key      = 'login_fail_' . md5( $username . ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
    $attempts = (int) get_transient( $key ) + 1;
    // Bloqueia por 15 minutos após acúmulo de tentativas
    set_transient( $key, $attempts, 15 * MINUTE_IN_SECONDS );
    return $attempts;
}

function connectrh_clear_login_attempts( $username ) {
    $key = 'login_fail_' . md5( $username . ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
    delete_transient( $key );
}

// Previne problemas com headers já enviados
add_action('init', 'esg_prevent_header_issues', 1);
function esg_prevent_header_issues() {
    // Remove qualquer output que possa ter sido enviado
    if (ob_get_level() == 0) {
        ob_start();
    }
}

// Cleanup no final da página
add_action('wp_footer', 'esg_cleanup_output', 999);
function esg_cleanup_output() {
    // Limpa qualquer output buffer se necessário
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
}

// REDIRECT 404
add_action('template_redirect','redirect_404');
function redirect_404() {
    if(is_404()) {
        wp_redirect(home_url());
    }
}
// FIM REDIRECT 404

/**
 * Enfileira FontAwesome (CDN) para ícones do tema
 */
function connectrh_enqueue_fontawesome() {
    wp_enqueue_style(
        'connectrh-fontawesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
        array(),
        '6.4.0'
    );
}
add_action('wp_enqueue_scripts', 'connectrh_enqueue_fontawesome');
add_action('admin_enqueue_scripts', 'connectrh_enqueue_fontawesome');

add_action( 'template_redirect', 'attachment_page_redirect', 10 );
function attachment_page_redirect() {
    if( is_attachment() ) {
        $url = wp_get_attachment_url( get_queried_object_id() );
        wp_redirect( home_url(), 301 );
    }
    return;
}

// Disable use XML-RPC
add_filter( 'xmlrpc_enabled', '__return_false' );
// Disable X-Pingback to header
add_filter( 'wp_headers', 'disable_x_pingback' );
function disable_x_pingback( $headers ) {
    unset( $headers['X-Pingback'] );
return $headers;
}

// Disable comments
function disable_comments() {
    $post_types = get_post_types();
    foreach ($post_types as $post_type) {
        if(post_type_supports($post_type,'comments')) {
            remove_post_type_support($post_type,'comments');
            remove_post_type_support($post_type,'trackbacks');
        }
    }
}
add_action('admin_init','disable_comments');

function remove_admin_login_header() {
    remove_action('wp_head', '_admin_bar_bump_cb');
}
add_action('get_header', 'remove_admin_login_header');
add_theme_support( 'post-thumbnails' );
show_admin_bar(false);

// [ALTO-05] Correção: security headers HTTP completos — substitui add_header_xframeoptions
add_action( 'send_headers', 'connectrh_security_headers' );
function connectrh_security_headers() {

    // [CRÍTICO] Cache-Control: no-store nas páginas sensíveis (login, dashboard, dados de usuário)
    // Impede que o browser ou proxies armazenem HTML com dados pessoais/sessão
    $sensitive_pages = array(
        'login', 'nova-senha', 'dashboard', 'usuarios', 'inserir',
        'resultado', 'categorias-dos-videos', 'criar-categorias',
        'criar-video', 'regulamento',
    );
    if ( is_page( $sensitive_pages ) || is_singular( 'videos' ) || is_tax( 'categoria_videos' ) ) {
        header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );
    }

    // Anti-clickjacking (mantido do original)
    header( 'X-Frame-Options: SAMEORIGIN' );

    // [ALTO-05] HSTS — força HTTPS por 1 ano (ativar somente após confirmar HTTPS no servidor)
    if ( is_ssl() ) {
        header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
    }

    // [ALTO-05] Previne MIME-type sniffing
    header( 'X-Content-Type-Options: nosniff' );

    // [ALTO-05] Controla informações de referrer
    header( 'Referrer-Policy: strict-origin-when-cross-origin' );

    // [ALTO-05] Desabilita recursos sensíveis do browser
    header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );

    // [ALTO-05] Content Security Policy
    // Ajuste os domínios conforme os CDNs usados (FontAwesome, SweetAlert2, GSAP)
    $csp = implode( '; ', array(
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
        "style-src  'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com https://cdn.datatables.net",
        "font-src   'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com",
        "img-src    'self' data: blob: https://hondaconectarh.com.br",
        "connect-src 'self'",
        "worker-src 'self' blob:",
        "frame-ancestors 'self'",
        "form-action 'self'",
    ) );
    header( "Content-Security-Policy: $csp" );
}

// [MÉDIO-04] Correção: cookies seguros — Secure + HttpOnly + SameSite
add_filter( 'wp_cookie_params', 'connectrh_secure_cookies' );
function connectrh_secure_cookies( $params ) {
    $params['secure']   = is_ssl(); // Apenas HTTPS
    $params['httponly'] = true;     // Inacessível via JavaScript
    $params['samesite'] = 'Strict'; // Proteção CSRF adicional
    return $params;
}


////////// Disable some endpoints for unauthenticated users
add_filter( 'rest_endpoints', 'disable_default_endpoints' );
function disable_default_endpoints( $endpoints ) {
    $endpoints_to_remove = array(
        '/oembed/1.0',
        '/wp/v2',
        '/wp/v2/media',
        '/wp/v2/types',
        '/wp/v2/statuses',
        '/wp/v2/taxonomies',
        '/wp/v2/tags',
        '/wp/v2/users',
        '/wp/v2/comments',
        '/wp/v2/settings',
        '/wp/v2/themes',
        '/wp/v2/blocks',
        '/wp/v2/oembed',
        '/wp/v2/posts',
        '/wp/v2/pages',
        '/wp/v2/block-renderer',
        '/wp/v2/search',
        '/wp/v2/categories'
    );

    if ( ! is_user_logged_in() ) {
        foreach ( $endpoints_to_remove as $rem_endpoint ) {
            // $base_endpoint = "/wp/v2/{$rem_endpoint}";
            foreach ( $endpoints as $maybe_endpoint => $object ) {
                if ( stripos( $maybe_endpoint, $rem_endpoint ) !== false ) {
                    unset( $endpoints[ $maybe_endpoint ] );
                }
            }
        }
    }
    return $endpoints;
}



add_theme_support( 'menus' ); // Menus
function wp_menu_custom_limit_one( $hook ) {
// Prevent child menu items
$menu_ids_block_child = array(3); // Menu ids to be limited by 1 child menu item
if(!empty($_GET['menu'])) {
    if ( $hook == 'nav-menus.php' && !in_array($_GET['menu'], $menu_ids_block_child) ) return;
    // override default value right after 'nav-menu' JS
    wp_add_inline_script( 'nav-menu', 'wpNavMenu.options.globalMaxDepth = 1;', 'after' );
    }
}
add_action( 'admin_enqueue_scripts', 'wp_menu_custom_limit_one' );




// Categoria para Usuários
function custom_taxonomy() {
    $labels = array(
    'name'                       => _x( 'Unidades', 'Unidades', 'text_domain' ),
    'singular_name'              => _x( 'Unidade', 'Unidade', 'text_domain' ),
    'menu_name'                  => __( 'Unidades', 'text_domain' ),
    'all_items'                  => __( 'Todas as unidades', 'text_domain' ),
    'parent_item'                => __( 'Parent Department', 'text_domain' ),
    'parent_item_colon'          => __( 'Parent Department:', 'text_domain' ),
    'new_item_name'              => __( 'Novo nome de unidade', 'text_domain' ),
    'add_new_item'               => __( 'Adicionar unidade', 'text_domain' ),
    'edit_item'                  => __( 'Editar unidade', 'text_domain' ),
    'update_item'                => __( 'Atualizar unidade', 'text_domain' ),
    'view_item'                  => __( 'View Department', 'text_domain' ),
    'separate_items_with_commas' => __( 'Separate department with commas', 'text_domain' ),
    'add_or_remove_items'        => __( 'Adicionar ou remover unidades', 'text_domain' ),
    'choose_from_most_used'      => __( 'Choose from the most used', 'text_domain' ),
    'popular_items'              => __( 'Unidades mais populares', 'text_domain' ),
    'search_items'               => __( 'Procurar unidades', 'text_domain' ),
    'not_found'                  => __( 'Nada encontrado', 'text_domain' ),
    'no_terms'                   => __( 'Sem unidades', 'text_domain' ),
    'items_list'                 => __( 'Lista de unidades', 'text_domain' ),
    'items_list_navigation'      => __( 'Departments list navigation', 'text_domain' ),
    );
    $args = array(
    'labels'                     => $labels,
    'hierarchical'               => true,
    'public'                     => true,
    'show_ui'                    => true,
    'show_admin_column'          => true,
    'show_in_nav_menus'          => true,
    'show_tagcloud'              => true,
    );
    register_taxonomy( 'unidades', 'user', $args );
}
add_action( 'init', 'custom_taxonomy', 0 );

function cb_add_departments_taxonomy_admin_page() {
    $tax = get_taxonomy( 'unidades' ); 
    add_users_page(
    esc_attr( $tax->labels->menu_name ),
    esc_attr( $tax->labels->menu_name ),
    $tax->cap->manage_terms,
    'edit-tags.php?taxonomy=' . $tax->name
    );   
}
add_action( 'admin_menu', 'cb_add_departments_taxonomy_admin_page' );

function user_infos_empresas_coluna( $field_args, $field ) {  
    echo strtoupper($field->escaped_value());
  }
  function user_taxonomy_empresas() {
    $terms = get_terms( 'unidades', array('hide_empty' => false) );
    $list_empresas = array();
    foreach($terms as $term) {
      // $list_empresas[$term->term_id] = $term->name; // Pega o termo e utiliza o ID
      $list_empresas[$term->slug] = $term->name;
    }
    return $list_empresas;
  }
#################################### CAMPOS DO USUARIO ####################################
add_action( 'cmb2_init', 'custom_user_fields_cmb2' );
    function custom_user_fields_cmb2() {
    // Opções de acesso
    $cmb_user = new_cmb2_box( array(
        'id'               => 'user_field_box',
        'title'            => 'Informações do colaborador',
        'object_types'     => array( 'user' ),
        'show_names'       => true,
        // 'new_user_section' => 'add-new-user', // where form will show on new user page. 'add-existing-user' is only other valid option.
    ) );
    $cmb_user->add_field( array(
    'name' => 'Opções de acesso',
    'id'   => 'user_field_title_acesso',
    'type' => 'title',
    'classes' => 'cmb2-divisor',
    ) );
    $cmb_user->add_field( array(
        'name' => 'Senha já foi alterada?',
        'id'   => 'user_field_senha_alterada',
        'type' => 'select',
        'column' => true,
        'default' => 'Não',
        'options' => array(
            'Não' => 'Não',
            'Sim' => 'Sim',
        ),
        ) );
        $cmb_user->add_field( array(
            'name'     => apply_filters('str_from_lang','Unidade','Unidade'),
            'id'       => 'user_infos_empresas',
            'type' => 'pw_select',
            'options_cb' => 'user_taxonomy_empresas',
            'column' => array(
              'position' => 2,
              'name'     => apply_filters('str_from_lang','Unidade','Unidade'),
            ),
            'display_cb' => 'user_infos_empresas_coluna'
            // 'type'     => 'taxonomy_select', // Or `taxonomy_select_hierarchical`
            // 'taxonomy' => 'user_empresa', // Taxonomy Slug
          ) );
          $cmb_user->add_field( array(
            'name'    => 'Unidade',
            'desc'    => 'Esse é um backup de segurança caso algo dê errado com a unidade principal para conseguir separar os usuários depois',
            'default' => '',
            'id'      => 'unidade_usuario',
            'type'    => 'text',
        ) );
        $cmb_user->add_field( array(
            'name'    => 'Empresa',
            'desc'    => '',
            'default' => '',
            'id'      => 'empresa_usuario',
            'type'    => 'text',
        ) );
    $cmb_user->add_field( array(
        'name'    => 'Comentários',
        'desc'    => '',
        'id'      => 'comentario',
        'type'    => 'wysiwyg',
        'options' => array(),
    ) );

    // Busca todas as datas (taxonomias) primeiro
    $terms = get_terms( array(
        'taxonomy'   => 'datas_perguntas',
        'hide_empty' => false,
    ) );

    // Processa cada data individualmente
    foreach( $terms as $term ){
        $slug_data = $term->slug;
        $data_termo = $term->name;
        
        // Busca perguntas específicas para esta data
        $args = array(
            'post_type' => 'perguntas',
            'posts_per_page' => -1,
            'order' => 'ASC',
            'tax_query' => array(
                array(
                    'taxonomy' => 'datas_perguntas',
                    'field' => 'term_id',
                    'terms' => $term->term_id,
                    'include_children' => false,
                )
            )
        );
        
        $perguntas = get_posts( $args );
        
        // Se não há perguntas para esta data, pula
        if (empty($perguntas)) {
            continue;
        }
        
        // Cria um box CMB2 para esta data específica
        $cmb2 = new_cmb2_box( array(
            'id'               => 'user_field_box_'.$slug_data,
            'title'            => 'Perguntas de '.$data_termo,
            'object_types'     => array( 'user' ),
            'show_names'       => true,
        ) );
        
        // Adiciona campos de cabeçalho para esta data
        $cmb2->add_field( array(
            'name' => 'Perguntas '.$data_termo,
            'desc' => '',
            'type' => 'title',
            'id'   => 'separador_perguntas_'.$slug_data
        ) );
        
        $cmb2->add_field( array(
            'name'             => 'Presencial',
            'desc'             => 'Acompanhou a palestra presencialmente de '.$data_termo,
            'id'               => 'presencial_'.$slug_data,
            'type'             => 'select',
            'show_option_none' => true,
            'default'          => 'custom',
            'options'          => array(
                'Presencial' => 'Presencial',
                'Não presencial'   => 'Não presencial',
            ),
        ) );
        
        $cmb2->add_field( array(
            'name' => 'Assistiu o video todo da data de'.' '.$data_termo.' '.'?',
            'desc' => '',
            'id'   => 'video_concluido_'.$slug_data,
            'type' => 'checkbox',
        ) );
        
        $cmb2->add_field( array(
            'name' => 'Respondeu todas as questões de'.' '.$data_termo.' '.'?',
            'desc' => '',
            'id'   => 'todas_alternativa_'.$slug_data,
            'type' => 'checkbox',
        ) );
        
        $cmb2->add_field( array(
            'name' => 'Acertou todas as questões de'.' '.$data_termo.' '.'?',
            'desc' => '',
            'id'   => 'acertou_todas_alternativas_'.$slug_data,
            'type' => 'checkbox',
        ));
        
        $cmb2->add_field( array(
            'name' => 'Acessou na data de'.' '.$data_termo.' '.'?',
            'desc' => '',
            'id'   => 'acessou_'.$slug_data,
            'type' => 'checkbox',
        ) );
        
        $cmb2->add_field( array(
            'name' => 'Horário que acessou',
            'id'   => 'horario_da_data_de_'.$slug_data,
            'type' => 'text_datetime_timestamp',
        ));
        
        $cmb2->add_field( array(
            'name'    => 'Classificado para a data de'.' '.$data_termo,
            'desc'             => '',
            'id'      => 'classificado_'.$slug_data,
            'type'             => 'select',
            'show_option_none' => true,
            'default'          => 'custom',
            'options'          => array(
                'Classificado' => __( 'Classificado', 'cmb2' ),
                'Desclassificado'   => __( 'Desclassificado', 'cmb2' ),
            ),
        ));

        // Agora processa as perguntas desta data
        foreach ($perguntas as $pergunta) {
            $post_id = $pergunta->ID;
            $nome = get_the_title($post_id);
            
            // Recupera as alternativas desta pergunta específica
            $entradas = get_post_meta( $post_id, 'grupo_de_respostas', true );
            $options = [];
            $tem_sugestao = false;
            
            // Verifica se há alternativas
            if ( ! empty( $entradas ) && is_array($entradas) ) {
                foreach ( $entradas as $group_item ) {
                    if (isset($group_item['alternativa']) && !empty($group_item['alternativa'])) {
                        $valor_campo = $group_item['alternativa'];
                        $options[ $valor_campo ] = $valor_campo;
                    }
                    
                    // Verifica se esta pergunta tem campo de sugestão
                    if (isset($group_item['sugestao']) && !empty($group_item['sugestao'])) {
                        $tem_sugestao = true;
                    }
                }
            }

            // Adiciona campo da pergunta apenas se houver alternativas
            if (!empty($options)) {
                $cmb2->add_field( array(
                    'name' => '('.$data_termo.')'.' '.$nome,
                    'id'   => 'user_field_'. $slug_data.'_'.$post_id,
                    'desc' =>  $nome,
                    'column'   => true,
                    'type' => 'select',
                    'show_option_none' => true,
                    'options'=> $options,
                ) );

                // Adiciona campo de sugestão se necessário
                if ($tem_sugestao) {
                    $cmb2->add_field( array(
                        'name'    => $nome . ' - Sugestão/Comentário',
                        'desc'    => '',
                        'default' => '',
                        'id'      => 'sugestao_pergunta_'.$slug_data.'_'.$post_id,
                        'type' => 'textarea'
                    ) );
                }
            }
        }
    }
          
    }

// // Post type de Perguntas
function custom_post_type_perguntas() {
    $labels = array(
        'name'                  => _x( 'Perguntas', 'Post Type General Name', 'text_domain' ),
        'singular_name'         => _x( 'Perguntas', 'Post Type Singular Name', 'text_domain' ),
        'menu_name'             => __( 'Perguntas', 'text_domain' ),
        'name_admin_bar'        => __( 'Perguntas', 'text_domain' ),
        'archives'              => __( 'Item Archives', 'text_domain' ),
        'attributes'            => __( 'Item Attributes', 'text_domain' ),
        'parent_item_colon'     => __( 'Parent Item:', 'text_domain' ),
        'all_items'             => __( 'Todos', 'text_domain' ),
        'add_new_item'          => __( 'Novo', 'text_domain' ),
        'add_new'               => __( 'Novo', 'text_domain' ),
        'new_item'              => __( 'Novo Item', 'text_domain' ),
        'edit_item'             => __( 'Editar Item', 'text_domain' ),
        'update_item'           => __( 'Atualizar Item', 'text_domain' ),
        'view_item'             => __( 'Visualizar', 'text_domain' ),
        'view_items'            => __( 'Visualizar Items', 'text_domain' ),
        'search_items'          => __( 'Buscar', 'text_domain' ),
        'not_found'             => __( 'Not found', 'text_domain' ),
        'not_found_in_trash'    => __( 'Not found in Trash', 'text_domain' ),
        'featured_image'        => __( 'Featured Image', 'text_domain' ),
        'set_featured_image'    => __( 'Set featured image', 'text_domain' ),
        'remove_featured_image' => __( 'Remove featured image', 'text_domain' ),
        'use_featured_image'    => __( 'Use as featured image', 'text_domain' ),
        'insert_into_item'      => __( 'Insert into item', 'text_domain' ),
        'uploaded_to_this_item' => __( 'Uploaded to this item', 'text_domain' ),
        'items_list'            => __( 'Items list', 'text_domain' ),
        'items_list_navigation' => __( 'Items list navigation', 'text_domain' ),
        'filter_items_list'     => __( 'Filter items list', 'text_domain' ),
    );
    $args = array(
        'label'                 => __( 'Post Type', 'text_domain' ),
        'description'           => __( 'Post Type Description', 'text_domain' ),
        'labels'                => $labels,
        'supports'              => array('title',),
        'hierarchical'          => true,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'page',
    );
    register_post_type( 'perguntas', $args );
}
add_action( 'init', 'custom_post_type_perguntas', 0 );

//// Criando datas para as respostas corretas
add_action( 'init', 'custom_taxonomy_projeto', 0 );
function custom_taxonomy_projeto() { 
$labels = array(
    'name' => 'Datas',
    'singular_name' => 'Datas',
    'search_items' => 'Buscar Data',
    'all_items' => 'Todas as Datas',
    'edit_item' => 'Editar Data', 
    'update_item' => 'Atualizar Data',
    'add_new_item' => 'Adicionar Data',
    'new_item_name' => 'Nova categoria',
    'menu_name' => 'Datas',
);    

register_taxonomy('datas_perguntas',array('perguntas'), array(
    'hierarchical' => true,
    'labels' => $labels,
    'show_ui' => true,
    'show_admin_column' => true,
    'query_var' => true,
    'rewrite' => array(
        'slug' => 'datas_perguntas', // This controls the base slug that will display before each term
        'with_front' => true, // Don't display the category base before "/locations/"
        'hierarchical' => true // This will allow URL's like "/locations/boston/cambridge/"
    ),
));
}
// Box dos grupos de alternativas
add_action( 'cmb2_admin_init', 'post_type_grupo_perguntas' );
    function post_type_grupo_perguntas() {
    // CBM2 padrão documentos
        $cmb = new_cmb2_box( array(
            'id'            => 'perguntas_infos',
            'title'         => 'Alternativas',
            'object_types'  => array( 'perguntas' ), // Post type
            // 'show_on_cb' => 'yourprefix_show_if_front_page', // function should return a bool value
            // 'context'    => 'normal',
            // 'priority'   => 'high',
            // 'show_names' => true, // Show field names on the left
            // 'cmb_styles' => false, // false to disable the CMB stylesheet
            'closed'     => false, // true to keep the metabox closed by default
            // 'classes'    => 'extra-class', // Extra cmb2-wrap classes
            // 'classes_cb' => 'yourprefix_add_some_classes', // Add classes through a callback.
        ) );
        $group_field_id = $cmb->add_field( array(
            'id'          => 'grupo_de_respostas',
            'type'        => 'group',
            'description' => __( 'Crie uma alternativa', 'cmb2' ),
            // 'repeatable'  => false, // use false if you want non-repeatable group
            'options'     => array(
                'group_title'       => __( 'Alternativa {#}', 'cmb2' ), // since version 1.1.4, {#} gets replaced by row number
                'add_button'        => __( 'Adicionar alternativa', 'cmb2' ),
                'remove_button'     => __( 'Remover alternativa', 'cmb2' ),
                'sortable'          => true,
                // 'closed'         => true, // true to have the groups closed by default
                // 'remove_confirm' => esc_html__( 'Are you sure you want to remove?', 'cmb2' ), // Performs confirmation before removing group.
            ),
        ) );
        $cmb->add_group_field( $group_field_id, array(
            'name' => 'Alternativa',
            'id'   => 'alternativa',
            'type' => 'text',
            'repeatable' => false, // Repeatable fields are supported w/in repeatable groups (for most types)
        ) );
        $cmb->add_group_field( $group_field_id, array(
            'name' => 'Alternativa correta',
            'desc' => 'Marque aqui caso essa seja a altenativa correta',
            'id'   => 'alternativa_correta',
            'type' => 'checkbox',
        ) );
        $cmb->add_group_field( $group_field_id, array(
            'name' => 'Texto para sugestão do colaborador',
            'desc' => '',
            'default' => '',
            'id' => 'sugestao',
            'type' => 'textarea'
        ) );


    }
add_action( 'cmb2_admin_init', 'yourprefix_register_taxonomy_metabox' );
/**
 * Hook in and add a metabox to add fields to taxonomy terms
 */
function yourprefix_register_taxonomy_metabox() {
	$prefix = 'campo_';

	/**
	 * Metabox to add fields to categories and tags
	 */
	$cmb_term = new_cmb2_box( array(
		'id'               => $prefix . 'edit',
		'title'            => esc_html__( 'Category Metabox', 'cmb2' ), // Doesn't output for term boxes
		'object_types'     => array( 'term' ), // Tells CMB2 to use term_meta vs post_meta
		'taxonomies'       => array( 'datas_perguntas', 'post_tag' ), // Tells CMB2 which taxonomies should have these fields
		// 'new_term_section' => true, // Will display in the "Add New Category" section
	) );
    $cmb_term->add_field( array(
        'name'    => 'Atração',
        'desc'    => '',
        'default' => '',
        'id'      => 'atracao',
        'type'    => 'text_medium'
    ) );
    $cmb_term->add_field( array(
        'name'    => 'Tema',
        'desc'    => '',
        'default' => '',
        'id'      => 'tema',
        'type'    => 'text_medium'
    ) );
    $cmb_term->add_field( array(
        'name'    => 'Código presencial',
        'desc'    => '',
        'default' => '',
        'id'      => 'codigo',
        'type'    => 'text_medium'
    ) );
    $cmb_term->add_field( array(
        'name' => 'Time zone',
        'id'   => 'wiki_test_timezone',
        'type' => 'select_timezone',
    ) );
    $cmb_term->add_field( array(
        'name' => 'Insira o horário que você deseja que inicie a pesquisa',
        'id'   => 'horario_inicio',
        'type' => 'text_datetime_timestamp',
        // 'timezone_meta_key' => 'wiki_test_timezone',
        // 'date_format' => 'Y-m-d', // Formato da data para o admin
        // 'time_format' => 'H:i',   // Formato da hora para o admin
    ) );
    $cmb_term->add_field( array(
        'name' => 'Insira o horário que você deseja que finalize a pesquisa',
        'id'   => 'horario_fim',
        'type' => 'text_datetime_timestamp',
    ) );
    $cmb_term->add_field( array(
        'name'    => 'Video',
        'desc'    => '',
        'id'      => 'video_categoria',
        'type'    => 'file',
        // Optional:
        'options' => array(
            'url' => true, // Hide the text input for the url
        ),
        'text'    => array(
            'add_upload_file_text' => 'Adicionar video' // Change upload button text. Default: "Add or Upload File"
        ),

    ) );
    $cmb_term->add_field( array(
        'name' => 'Iframe do video',
        'desc' => 'Cole aqui o iframe do video',
        'default' => '',
        'id' => 'iframe_video_termo',
        'type' => 'textarea_code'
    ) );

}



// Vídeos
// // Post type de Vídeos
// Taxonomia para Vídeos
add_action( 'init', 'custom_taxonomy_videos', 0 );
function custom_taxonomy_videos() {
    $labels = array(
        'name'              => _x( 'Categorias de Vídeos', 'taxonomy general name', 'text_domain' ),
        'singular_name'     => _x( 'Categoria de Vídeo', 'taxonomy singular name', 'text_domain' ),
        'search_items'      => __( 'Buscar Categorias', 'text_domain' ),
        'all_items'         => __( 'Todas as Categorias', 'text_domain' ),
        'parent_item'       => __( 'Categoria Pai', 'text_domain' ),
        'parent_item_colon' => __( 'Categoria Pai:', 'text_domain' ),
        'edit_item'         => __( 'Editar Categoria', 'text_domain' ),
        'update_item'       => __( 'Atualizar Categoria', 'text_domain' ),
        'add_new_item'      => __( 'Adicionar Nova Categoria', 'text_domain' ),
        'new_item_name'     => __( 'Nova Categoria', 'text_domain' ),
        'menu_name'         => __( 'Categorias de Vídeos', 'text_domain' ),
    );
    $args = array(
        'hierarchical'          => true,
        'labels'                => $labels,
        'show_ui'               => true,
        'show_admin_column'     => true,
        'show_in_nav_menus'     => true,
        'show_tagcloud'         => false,
        'public'                => true,
        'rewrite'               => array( 'slug' => 'categoria_videos', 'with_front' => true ),
    );
    register_taxonomy( 'categoria_videos', array( 'videos' ), $args );
}

    // CMB2: group repetível para seções de vídeo (título + upload de vídeo)
    add_action( 'cmb2_admin_init', 'cmb2_videos_metabox' );
    function cmb2_videos_metabox() {
        $prefix = 'video_';

        $cmb = new_cmb2_box( array(
            'id'           => $prefix . 'metabox',
            'title'        => __( 'Seções de Vídeo', 'text_domain' ),
            'object_types' => array( 'videos' ), // post type
            'context'      => 'normal',
            'priority'     => 'high',
            'show_names'   => true,
        ) );

        $group_field_id = $cmb->add_field( array(
            'id'          => $prefix . 'group',
            'type'        => 'group',
            'description' => __( 'Adicione seções de vídeo (título + arquivo).', 'text_domain' ),
            'options'     => array(
                'group_title'   => __( 'Seção {#}', 'text_domain' ),
                'add_button'    => __( 'Adicionar seção', 'text_domain' ),
                'remove_button' => __( 'Remover seção', 'text_domain' ),
                'sortable'      => true,
            ),
        ) );

        // Título da seção
        $cmb->add_group_field( $group_field_id, array(
            'name' => __( 'Título da seção', 'text_domain' ),
            'id'   => 'titulo_secao_do_video',
            'type' => 'text',
        ) );

        // Upload de vídeo (aceitar apenas vídeo no input; validação server-side também aplicada)
        $cmb->add_group_field( $group_field_id, array(
            'name'    => __( 'Upload de Vídeo', 'text_domain' ),
            'id'      => 'upload',
            'type'    => 'file',
            'options' => array(
                'url' => false, // armazena attachment ID quando possível
            ),
            'text'    => array(
                'add_upload_file_text' => __( 'Adicionar vídeo', 'text_domain' ),
            ),
            'attributes' => array(
                'accept' => 'video/*',
            ),
        ) );
    }

    // Validação server-side no momento do save: garante que cada upload do grupo seja do tipo video/*
    add_action( 'save_post', 'validate_video_group_uploads', 10, 2 );
    function validate_video_group_uploads( $post_id, $post ) {
        // Somente para o post type 'videos' e quando não for autosave/revision
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        if ( wp_is_post_revision( $post_id ) ) return;
        if ( $post->post_type !== 'videos' ) return;

        $meta_key = 'video_group';
        $group = get_post_meta( $post_id, $meta_key, true );

        if ( empty( $group ) || ! is_array( $group ) ) return;

        $invalid_count = 0;
        foreach ( $group as $index => $item ) {
            if ( empty( $item ) || empty( $item['upload'] ) ) continue;

            $value = $item['upload'];

            // If attachment ID
            if ( is_numeric( $value ) ) {
                $mime = get_post_mime_type( (int) $value );
                if ( ! $mime || strpos( $mime, 'video/' ) !== 0 ) {
                    unset( $group[ $index ] );
                    $invalid_count++;
                }
            } else {
                // URL - verify extension/mime
                $filetype = wp_check_filetype( wp_basename( $value ) );
                if ( empty( $filetype['type'] ) || strpos( $filetype['type'], 'video/' ) !== 0 ) {
                    unset( $group[ $index ] );
                    $invalid_count++;
                }
            }
        }

        if ( $invalid_count > 0 ) {
            // Reindex array and update meta
            $group = array_values( $group );
            update_post_meta( $post_id, $meta_key, $group );
            set_transient( 'video_upload_invalid_' . $post_id, $invalid_count, 30 );
        }
    }

    // Exibe aviso administrativo caso uploads inválidos tenham sido rejeitados
    add_action( 'admin_notices', 'video_upload_admin_notice' );
    function video_upload_admin_notice() {
        if ( ! function_exists( 'get_current_screen' ) ) return;
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'videos' ) return;

        $post_id = isset( $_GET['post'] ) ? intval( $_GET['post'] ) : 0;
        $count = $post_id ? get_transient( 'video_upload_invalid_' . $post_id ) : 0;
        if ( $post_id && $count ) {
            delete_transient( 'video_upload_invalid_' . $post_id );
            /* translators: %d = número de uploads inválidos removidos */
            echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( esc_html__( '%d upload(s) inválido(s) removido(s). Apenas arquivos de vídeo são aceitos.', 'text_domain' ), intval( $count ) ) . '</p></div>';
        }
    }

function custom_post_type_videos() {
    $labels = array(
        'name'                  => _x( 'Vídeos', 'Post Type General Name', 'text_domain' ),
        'singular_name'         => _x( 'Vídeos', 'Post Type Singular Name', 'text_domain' ),
        'menu_name'             => __( 'Vídeos', 'text_domain' ),
        'name_admin_bar'        => __( 'Vídeos', 'text_domain' ),
        'archives'              => __( 'Item Archives', 'text_domain' ),
        'attributes'            => __( 'Item Attributes', 'text_domain' ),
        'parent_item_colon'     => __( 'Parent Item:', 'text_domain' ),
        'all_items'             => __( 'Todos', 'text_domain' ),
        'add_new_item'          => __( 'Novo', 'text_domain' ),
        'add_new'               => __( 'Novo', 'text_domain' ),
        'new_item'              => __( 'Novo Item', 'text_domain' ),
        'edit_item'             => __( 'Editar Item', 'text_domain' ),
        'update_item'           => __( 'Atualizar Item', 'text_domain' ),
        'view_item'             => __( 'Visualizar', 'text_domain' ),
        'view_items'            => __( 'Visualizar Items', 'text_domain' ),
        'search_items'          => __( 'Buscar', 'text_domain' ),
        'not_found'             => __( 'Not found', 'text_domain' ),
        'not_found_in_trash'    => __( 'Not found in Trash', 'text_domain' ),
        'featured_image'        => __( 'Featured Image', 'text_domain' ),
        'set_featured_image'    => __( 'Set featured image', 'text_domain' ),
        'remove_featured_image' => __( 'Remove featured image', 'text_domain' ),
        'use_featured_image'    => __( 'Use as featured image', 'text_domain' ),
        'insert_into_item'      => __( 'Insert into item', 'text_domain' ),
        'uploaded_to_this_item' => __( 'Uploaded to this item', 'text_domain' ),
        'items_list'            => __( 'Items list', 'text_domain' ),
        'items_list_navigation' => __( 'Items list navigation', 'text_domain' ),
        'filter_items_list'     => __( 'Filter items list', 'text_domain' ),
    );
    $args = array(
        'label'                 => __( 'Post Type', 'text_domain' ),
        'description'           => __( 'Post Type Description', 'text_domain' ),
        'labels'                => $labels,
        'supports'              => array('title',),
        'hierarchical'          => true,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'page',
    );
    register_post_type( 'videos', $args );
}
add_action( 'init', 'custom_post_type_videos', 0 );

// ========================================
// DASHBOARD DE USUÁRIOS - SCRIPTS E AJAX
// ========================================

/**
 * Enfileira scripts e estilos para a página de usuários
 */
add_action('wp_enqueue_scripts', 'enqueue_dashboard_usuarios_scripts');
function enqueue_dashboard_usuarios_scripts() {
    // Apenas nas páginas de administração do tema (usuários ou criar categorias ou vídeos) ou na home
    if (!is_page_template('pages/template-page-usuarios.php') && !is_page_template('pages/template-page-criar-categorias.php') && !is_page_template('pages/template-page-categorias.php') && !is_page_template('pages/template-page-criar-video.php') && !is_page_template('pages/template-dashboard.php') && !is_front_page() && !is_home()) {
        return;
    }

    // SweetAlert2 (para páginas que usam modais)
    // [BAIXO-03] Correção: sem pin de versão na URL — reduz fingerprinting
    if (is_page_template('pages/template-page-usuarios.php') || is_page_template('pages/template-page-criar-categorias.php') || is_page_template('pages/template-page-criar-video.php') || is_page_template('pages/template-dashboard.php') || is_front_page() || is_home()) {
        wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@latest/dist/sweetalert2.all.min.js', array(), null, true);
    }
    
    // CSS do dashboard (comum a todas as páginas administrativas)
    wp_enqueue_style('admin-usuarios-css', get_template_directory_uri() . '/css/admin-usuarios.css', array(), '1.0.0');
    
    // Script específico para Dashboard Central
    if (is_page_template('pages/template-dashboard.php') || is_front_page() || is_home()) {
        wp_enqueue_script('dashboard-central-js', get_template_directory_uri() . '/js/functions/dashboard.js', array('jquery', 'sweetalert2'), '1.0.0', true);
        
        wp_localize_script('dashboard-central-js', 'dashboard_central_vars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dashboard_central_nonce')
        ));
        return; // Não carregar outros scripts
    }
    
    // JS do dashboard de usuários (APENAS na página de usuários)
    if (is_page_template('pages/template-page-usuarios.php')) {
        wp_enqueue_script('admin-usuarios-js', get_template_directory_uri() . '/js/functions/admin-usuarios.js', array('jquery', 'sweetalert2'), '1.0.0', true);
    }
    
    // Script específico para página de criação/edição de categorias de vídeo
    if (is_page_template('pages/template-page-criar-categorias.php')) {
        wp_enqueue_script('admin-categorias-js', get_template_directory_uri() . '/js/functions/admin-categorias-videos.js', array('jquery', 'sweetalert2'), '1.0.0', true);
    }

    // Script para página pública de categorias (cards)
    if (is_page_template('pages/template-page-categorias.php')) {
        // [BAIXO-03] Correção: sem pin de versão na URL
        wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@latest/dist/sweetalert2.all.min.js', array(), null, true);
        wp_enqueue_script('categorias-cards-js', get_template_directory_uri() . '/js/functions/categorias-cards.js', array('jquery', 'sweetalert2'), '1.0.0', true);
        
        wp_localize_script('categorias-cards-js', 'categorias_cards_vars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'is_admin' => current_user_can('manage_options'),
            'nonce' => current_user_can('manage_options') ? wp_create_nonce('dashboard_videos_nonce') : ''
        ));
    }

    // Script para página de administração de vídeos
    if (is_page_template('pages/template-page-criar-video.php')) {
        wp_enqueue_script('admin-videos-js', get_template_directory_uri() . '/js/functions/admin-videos.js', array('jquery', 'sweetalert2'), '1.0.0', true);
        
        // Buscar TODAS as categorias para passar ao JS (sem limite)
        $categorias = get_terms(array(
            'taxonomy' => 'categoria_videos',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
            'number' => 0  // 0 = sem limite, retorna todas
        ));
        
        // Debug: verificar se há erro na busca
        if (is_wp_error($categorias)) {
            error_log('Erro ao buscar categorias: ' . $categorias->get_error_message());
        } else {
            error_log('Categorias encontradas: ' . count($categorias));
        }
        
        $categorias_array = array();
        if (!empty($categorias) && !is_wp_error($categorias)) {
            foreach ($categorias as $categoria) {
                $categorias_array[] = array(
                    'id' => $categoria->term_id,
                    'name' => $categoria->name
                );
            }
            error_log('Array de categorias preparado: ' . count($categorias_array) . ' itens');
        }

        wp_localize_script('admin-videos-js', 'videos_vars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dashboard_videos_nonce'),
            'categorias' => $categorias_array
        ));
    }
    
    // Localização específica para página de usuários
    if (is_page_template('pages/template-page-usuarios.php')) {
        // Buscar unidades para passar ao JS
        $unidades = get_terms(array(
            'taxonomy' => 'unidades',
            'hide_empty' => false
        ));
        
        $unidades_array = array();
        if (!empty($unidades) && !is_wp_error($unidades)) {
            foreach ($unidades as $unidade) {
                $unidades_array[] = array(
                    'slug' => $unidade->slug,
                    'name' => $unidade->name
                );
            }
        }
        
        wp_localize_script('admin-usuarios-js', 'dashboard_vars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dashboard_usuarios_nonce'),
            'import_nonce' => wp_create_nonce('user_import_nonce'), // [ALTO-01] Nonce para importação CSV
            'unidades' => $unidades_array
        ));
    }

    // Localize para script de categorias se estiver carregado
    if (is_page_template('pages/template-page-criar-categorias.php')) {
        wp_localize_script('admin-categorias-js', 'categorias_vars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dashboard_videos_nonce')
        ));
    }
}

/**
 * Enfileira script para páginas de taxonomia `categoria_videos` (busca AJAX, ações admin)
 */
add_action('wp_enqueue_scripts', 'enqueue_taxonomy_videos_scripts');
function enqueue_taxonomy_videos_scripts() {
    if (!is_tax('categoria_videos')) return;

    // [BAIXO-03] Correção: sem pin de versão na URL
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@latest/dist/sweetalert2.all.min.js', array(), null, true);

    wp_enqueue_script('taxonomy-videos-js', get_template_directory_uri() . '/js/functions/taxonomia-videos.js', array('jquery', 'sweetalert2'), '1.0.0', true);

    wp_localize_script('taxonomy-videos-js', 'taxonomy_videos_vars', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('taxonomy_videos_nonce'),
        'dashboard_nonce' => wp_create_nonce('dashboard_videos_nonce'),
        'term_id' => get_queried_object() ? intval(get_queried_object()->term_id) : 0,
        'is_admin' => current_user_can('manage_options')
    ));
}

/**
 * AJAX público: buscar vídeos por taxonomia (suporta search)
 */
add_action('wp_ajax_get_videos_by_taxonomy', 'ajax_get_videos_by_taxonomy');
add_action('wp_ajax_nopriv_get_videos_by_taxonomy', 'ajax_get_videos_by_taxonomy');
function ajax_get_videos_by_taxonomy() {
    $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $paged = isset($_POST['paged']) ? absint($_POST['paged']) : 1;

    $args = array(
        'post_type' => 'videos',
        'posts_per_page' => 10,
        'paged' => $paged,
        'tax_query' => array(
            array(
                'taxonomy' => 'categoria_videos',
                'field' => 'term_id',
                'terms' => $term_id,
            ),
        ),
    );

    if (!empty($search)) {
        $args['s'] = $search;
    }

    $q = new WP_Query($args);
    $videos = array();
    if ($q->have_posts()) {
        while ($q->have_posts()) {
            $q->the_post();
            $id = get_the_ID();
            $thumb = has_post_thumbnail($id) ? get_the_post_thumbnail_url($id, 'medium') : '';
            $videos[] = array(
                'id' => $id,
                'title' => get_the_title(),
                'excerpt' => wp_trim_words(get_the_excerpt(), 20),
                'date' => get_the_date('d/m/Y', $id),
                'permalink' => get_permalink($id),
                'thumbnail' => $thumb,
            );
        }
        wp_reset_postdata();
    }

    wp_send_json_success(array('videos' => $videos, 'pages' => $q->max_num_pages));
}

/**
 * AJAX: Buscar usuários com filtros e paginação
 */
add_action('wp_ajax_get_users_dashboard', 'ajax_get_users_dashboard');
function ajax_get_users_dashboard() {
    // Verificação de permissão e nonce
    check_ajax_referer('dashboard_usuarios_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Sem permissão'));
    }

    // Parâmetros
    $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
    $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 25;
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $unidade_filter = isset($_POST['unidade']) ? sanitize_text_field($_POST['unidade']) : '';
    $sort_by = isset($_POST['sort_by']) ? sanitize_text_field($_POST['sort_by']) : 'data';
    $sort_order = isset($_POST['sort_order']) ? sanitize_text_field($_POST['sort_order']) : 'DESC';

    // Query args
    $args = array(
        'number' => $per_page,
        'offset' => ($page - 1) * $per_page,
        'orderby' => 'registered',
        'order' => $sort_order
    );

    // Busca por nome, email ou matrícula
    if (!empty($search)) {
        $args['search'] = '*' . esc_attr($search) . '*';
        $args['search_columns'] = array('user_login', 'user_email', 'display_name');
    }

    // Filtro por unidade (meta query)
    if (!empty($unidade_filter)) {
        $args['meta_query'] = array(
            array(
                'key' => 'user_infos_empresas',
                'value' => $unidade_filter,
                'compare' => '='
            )
        );
    }

    // Buscar usuários
    $user_query = new WP_User_Query($args);
    $users = $user_query->get_results();
    $total = $user_query->get_total();

    // Formatar dados dos usuários
    $users_data = array();
    foreach ($users as $user) {
        $unidade_slug = get_user_meta($user->ID, 'user_infos_empresas', true);
        $unidade_nome = '';
        if (!empty($unidade_slug)) {
            $term = get_term_by('slug', $unidade_slug, 'unidades');
            if ($term && !is_wp_error($term)) {
                $unidade_nome = $term->name;
            }
        }

        $users_data[] = array(
            'id' => $user->ID,
            'nome' => $user->display_name,
            'email' => $user->user_email,
            'matricula' => $user->user_login,
            'unidade' => $unidade_nome ?: 'N/A',
            'data_cadastro' => date_i18n('d/m/Y', strtotime($user->user_registered))
        );
    }

    wp_send_json_success(array(
        'users' => $users_data,
        'total' => $total,
        'pages' => ceil($total / $per_page)
    ));
}

/**
 * AJAX: Criar novo usuário
 */
add_action('wp_ajax_create_user_dashboard', 'ajax_create_user_dashboard');
function ajax_create_user_dashboard() {
    check_ajax_referer('dashboard_usuarios_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Sem permissão'));
    }

    // Validar dados
    $nome = isset($_POST['nome']) ? sanitize_text_field($_POST['nome']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $matricula = isset($_POST['matricula']) ? sanitize_text_field($_POST['matricula']) : '';
    $unidade = isset($_POST['unidade']) ? sanitize_text_field($_POST['unidade']) : '';
    $senha = isset($_POST['senha']) ? $_POST['senha'] : '';

    if (empty($nome) || empty($email) || empty($matricula) || empty($unidade) || empty($senha)) {
        wp_send_json_error(array('message' => 'Todos os campos são obrigatórios'));
    }

    // Validar email
    if (!is_email($email)) {
        wp_send_json_error(array('message' => 'Email inválido'));
    }

    // Verificar se matrícula já existe
    if (username_exists($matricula)) {
        wp_send_json_error(array('message' => 'Matrícula já cadastrada'));
    }

    // Verificar se email já existe
    if (email_exists($email)) {
        wp_send_json_error(array('message' => 'Email já cadastrado'));
    }

    // Criar usuário
    $user_id = wp_create_user($matricula, $senha, $email);
    
    if (is_wp_error($user_id)) {
        wp_send_json_error(array('message' => $user_id->get_error_message()));
    }

    // Atualizar dados do usuário
    wp_update_user(array(
        'ID' => $user_id,
        'display_name' => $nome,
        'first_name' => $nome,
        'role' => 'subscriber'
    ));

    // Salvar meta data
    update_user_meta($user_id, 'user_infos_empresas', $unidade);
    update_user_meta($user_id, 'unidade_usuario', $unidade);

    wp_send_json_success(array('message' => 'Usuário criado com sucesso!'));
}

/**
 * AJAX: Buscar dados de um usuário específico
 */
add_action('wp_ajax_get_user_data_dashboard', 'ajax_get_user_data_dashboard');
function ajax_get_user_data_dashboard() {
    check_ajax_referer('dashboard_usuarios_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Sem permissão'));
    }

    $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
    
    if (!$user_id) {
        wp_send_json_error(array('message' => 'ID de usuário inválido'));
    }

    $user = get_userdata($user_id);
    
    if (!$user) {
        wp_send_json_error(array('message' => 'Usuário não encontrado'));
    }

    $unidade_slug = get_user_meta($user_id, 'user_infos_empresas', true);

    wp_send_json_success(array(
        'id' => $user->ID,
        'nome' => $user->display_name,
        'email' => $user->user_email,
        'matricula' => $user->user_login,
        'unidade' => $unidade_slug
    ));
}

/**
 * AJAX: Atualizar usuário
 */
add_action('wp_ajax_update_user_dashboard', 'ajax_update_user_dashboard');
function ajax_update_user_dashboard() {
    check_ajax_referer('dashboard_usuarios_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Sem permissão'));
    }

    $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
    $nome = isset($_POST['nome']) ? sanitize_text_field($_POST['nome']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $unidade = isset($_POST['unidade']) ? sanitize_text_field($_POST['unidade']) : '';
    $senha = isset($_POST['senha']) ? $_POST['senha'] : '';

    if (!$user_id || empty($nome) || empty($email) || empty($unidade)) {
        wp_send_json_error(array('message' => 'Dados incompletos'));
    }

    // Validar email
    if (!is_email($email)) {
        wp_send_json_error(array('message' => 'Email inválido'));
    }

    // Verificar se email já existe em outro usuário
    $email_user = get_user_by('email', $email);
    if ($email_user && $email_user->ID != $user_id) {
        wp_send_json_error(array('message' => 'Email já cadastrado para outro usuário'));
    }

    // Atualizar dados
    $update_data = array(
        'ID' => $user_id,
        'display_name' => $nome,
        'first_name' => $nome,
        'user_email' => $email
    );

    // Adicionar senha se fornecida
    if (!empty($senha) && strlen($senha) >= 6) {
        $update_data['user_pass'] = $senha;
    }

    $result = wp_update_user($update_data);
    
    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }

    // Atualizar meta
    update_user_meta($user_id, 'user_infos_empresas', $unidade);
    update_user_meta($user_id, 'unidade_usuario', $unidade);

    wp_send_json_success(array('message' => 'Usuário atualizado com sucesso!'));
}

/**
 * AJAX: Excluir usuários
 */
add_action('wp_ajax_delete_users_dashboard', 'ajax_delete_users_dashboard');
function ajax_delete_users_dashboard() {
    check_ajax_referer('dashboard_usuarios_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Sem permissão'));
    }

    $user_ids = isset($_POST['user_ids']) ? array_map('absint', (array) $_POST['user_ids']) : array();
    
    if (empty($user_ids)) {
        wp_send_json_error(array('message' => 'Nenhum usuário selecionado'));
    }

    $deleted_count = 0;
    
    foreach ($user_ids as $user_id) {
        // Não permitir exclusão do próprio usuário
        if ($user_id == get_current_user_id()) {
            continue;
        }

        require_once(ABSPATH . 'wp-admin/includes/user.php');
        if (wp_delete_user($user_id)) {
            $deleted_count++;
        }
    }

    if ($deleted_count > 0) {
        wp_send_json_success(array('message' => sprintf('%d usuário(s) excluído(s) com sucesso!', $deleted_count)));
    } else {
        wp_send_json_error(array('message' => 'Nenhum usuário foi excluído'));
    }
}

/**
 * AJAX: Listar categorias de vídeos
 */
add_action('wp_ajax_get_categorias_videos', 'ajax_get_categorias_videos');
function ajax_get_categorias_videos() {
    check_ajax_referer('dashboard_videos_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Sem permissão'));
    }

    $terms = get_terms(array(
        'taxonomy' => 'categoria_videos',
        'hide_empty' => false
    ));

    $result = array();
    if (!empty($terms) && !is_wp_error($terms)) {
        foreach ($terms as $term) {
            $term_link = get_term_link($term);
            if (is_wp_error($term_link)) {
                $term_link = '';
            } else {
                $term_link = esc_url_raw($term_link);
            }

            $result[] = array(
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'description' => $term->description,
                'count' => $term->count,
                'link' => $term_link
            );
        }
    }

    wp_send_json_success(array('categories' => $result));
}

/**
 * AJAX público: Listar categorias de vídeos (para página pública de cards)
 */
add_action('wp_ajax_get_categorias_videos_public', 'ajax_get_categorias_videos_public');
add_action('wp_ajax_nopriv_get_categorias_videos_public', 'ajax_get_categorias_videos_public');
function ajax_get_categorias_videos_public() {
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

    $args = array(
        'taxonomy' => 'categoria_videos',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
    );

    if (!empty($search)) {
        $args['search'] = $search;
    }

    $terms = get_terms($args);
    $result = array();
    if (!empty($terms) && !is_wp_error($terms)) {
        foreach ($terms as $term) {
            $term_link = get_term_link($term);
            if (is_wp_error($term_link)) {
                $term_link = '';
            } else {
                $term_link = esc_url_raw($term_link);
            }

            $result[] = array(
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'description' => $term->description,
                'count' => $term->count,
                'link' => $term_link
            );
        }
    }

    wp_send_json_success(array('categories' => $result));
}

/**
 * AJAX: Criar categoria de vídeo
 */
add_action('wp_ajax_create_categoria_video', 'ajax_create_categoria_video');
function ajax_create_categoria_video() {
    check_ajax_referer('dashboard_videos_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Sem permissão'));
    }

    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $slug = isset($_POST['slug']) ? sanitize_title($_POST['slug']) : '';
    $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';

    if (empty($name)) {
        wp_send_json_error(array('message' => 'Nome é obrigatório'));
    }

    $args = array();
    if (!empty($slug)) $args['slug'] = $slug;
    if (!empty($description)) $args['description'] = $description;

    $insert = wp_insert_term($name, 'categoria_videos', $args);
    if (is_wp_error($insert)) {
        wp_send_json_error(array('message' => $insert->get_error_message()));
    }

    $term_id = intval($insert['term_id']);
    wp_send_json_success(array('message' => 'Categoria criada com sucesso', 'term_id' => $term_id));
}

/**
 * AJAX: Atualizar categoria de vídeo
 */
add_action('wp_ajax_update_categoria_video', 'ajax_update_categoria_video');
function ajax_update_categoria_video() {
    check_ajax_referer('dashboard_videos_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Sem permissão'));
    }

    $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $slug = isset($_POST['slug']) ? sanitize_title($_POST['slug']) : '';
    $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';

    if ($term_id <= 0 || empty($name)) {
        wp_send_json_error(array('message' => 'Dados inválidos'));
    }

    $args = array('name' => $name);
    if (!empty($slug)) $args['slug'] = $slug;
    if (!empty($description)) $args['description'] = $description;

    $update = wp_update_term($term_id, 'categoria_videos', $args);
    if (is_wp_error($update)) {
        wp_send_json_error(array('message' => $update->get_error_message()));
    }

    wp_send_json_success(array('message' => 'Categoria atualizada com sucesso'));
}

/**
 * AJAX: Excluir categorias de vídeo (array)
 */
add_action('wp_ajax_delete_categorias_videos', 'ajax_delete_categorias_videos');
function ajax_delete_categorias_videos() {
    check_ajax_referer('dashboard_videos_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Sem permissão'));
    }

    $term_ids = isset($_POST['term_ids']) ? array_map('absint', (array) $_POST['term_ids']) : array();
    if (empty($term_ids)) {
        wp_send_json_error(array('message' => 'Nenhuma categoria selecionada'));
    }

    $deleted = 0;
    foreach ($term_ids as $tid) {
        if (wp_delete_term($tid, 'categoria_videos')) {
            $deleted++;
        }
    }

    if ($deleted > 0) {
        wp_send_json_success(array('message' => sprintf('%d categoria(s) excluída(s)', $deleted)));
    }

    wp_send_json_error(array('message' => 'Nenhuma categoria foi excluída'));
}

/**
 * AJAX: Upload de arquivo de vídeo (retorna attachment ID)
 */
add_action('wp_ajax_upload_video_file_dashboard', 'ajax_upload_video_file_dashboard');
function ajax_upload_video_file_dashboard() {
    check_ajax_referer('dashboard_videos_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Sem permissão'));
    }

    if (empty($_FILES['video_file'])) {
        wp_send_json_error(array('message' => 'Nenhum arquivo enviado'));
    }

    $file = $_FILES['video_file'];
    
    // Verificar erros no upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = array(
            UPLOAD_ERR_INI_SIZE => 'O arquivo excede o tamanho máximo permitido pelo servidor (upload_max_filesize)',
            UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o tamanho máximo permitido',
            UPLOAD_ERR_PARTIAL => 'Upload parcial - o arquivo foi enviado apenas parcialmente',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi enviado',
            UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária ausente',
            UPLOAD_ERR_CANT_WRITE => 'Falha ao escrever arquivo no disco',
            UPLOAD_ERR_EXTENSION => 'Uma extensão PHP interrompeu o upload'
        );
        $error_msg = isset($error_messages[$file['error']]) ? $error_messages[$file['error']] : 'Erro desconhecido no upload';
        wp_send_json_error(array('message' => $error_msg));
    }
    
    // Validar tipo de arquivo usando WordPress
    $filetype = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
    
    // Se o WordPress não conseguir detectar, tentar detectar manualmente
    if (!$filetype['type']) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detected_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (strpos($detected_type, 'video/') === 0) {
            $filetype['type'] = $detected_type;
            $filetype['ext'] = pathinfo($file['name'], PATHINFO_EXTENSION);
        }
    }
    
    // Validar se é vídeo
    if (empty($filetype['type']) || strpos($filetype['type'], 'video/') !== 0) {
        wp_send_json_error(array('message' => 'Tipo de arquivo inválido. Apenas vídeos são permitidos. Tipo detectado: ' . ($filetype['type'] ?: 'desconhecido')));
    }

    // Fazer upload do arquivo
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    // Adicionar filtro temporário para permitir todos os tipos de vídeo
    add_filter('upload_mimes', 'allow_video_uploads_temp');
    
    $upload = wp_handle_upload($file, array('test_form' => false));
    
    // Remover filtro
    remove_filter('upload_mimes', 'allow_video_uploads_temp');
    
    if (isset($upload['error'])) {
        wp_send_json_error(array('message' => 'Erro no upload: ' . $upload['error']));
    }

    // Criar attachment no WordPress
    $attachment_data = array(
        'post_mime_type' => $upload['type'],
        'post_title'     => sanitize_file_name(pathinfo($file['name'], PATHINFO_FILENAME)),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );

    $attachment_id = wp_insert_attachment($attachment_data, $upload['file']);
    
    if (is_wp_error($attachment_id)) {
        wp_send_json_error(array('message' => 'Erro ao criar attachment: ' . $attachment_id->get_error_message()));
    }

    // Gerar metadados do attachment
    $attach_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
    wp_update_attachment_metadata($attachment_id, $attach_data);

    wp_send_json_success(array(
        'attachment_id' => $attachment_id,
        'url' => $upload['url'],
        'filename' => basename($upload['file'])
    ));
}

/**
 * Filtro temporário para permitir uploads de vídeo
 */
function allow_video_uploads_temp($mimes) {
    $mimes['mp4'] = 'video/mp4';
    $mimes['m4v'] = 'video/x-m4v';
    $mimes['mov'] = 'video/quicktime';
    $mimes['wmv'] = 'video/x-ms-wmv';
    $mimes['avi'] = 'video/x-msvideo';
    $mimes['mpg'] = 'video/mpeg';
    $mimes['mpeg'] = 'video/mpeg';
    $mimes['ogv'] = 'video/ogg';
    $mimes['webm'] = 'video/webm';
    $mimes['mkv'] = 'video/x-matroska';
    return $mimes;
}

/**
 * AJAX: Listar vídeos com paginação e filtros
 */
add_action('wp_ajax_get_videos_dashboard', 'ajax_get_videos_dashboard');
function ajax_get_videos_dashboard() {
    check_ajax_referer('dashboard_videos_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Sem permissão'));
    }

    $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
    $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 25;
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $categoria_filter = isset($_POST['categoria']) ? absint($_POST['categoria']) : 0;

    $args = array(
        'post_type' => 'videos',
        'posts_per_page' => $per_page,
        'paged' => $page,
        'orderby' => 'date',
        'order' => 'DESC'
    );

    if (!empty($search)) {
        $args['s'] = $search;
    }

    if ($categoria_filter > 0) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'categoria_videos',
                'field' => 'term_id',
                'terms' => $categoria_filter
            )
        );
    }

    $query = new WP_Query($args);
    $videos_data = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $categorias = wp_get_post_terms($post_id, 'categoria_videos');
            $cat_names = array();
            foreach ($categorias as $cat) {
                $cat_names[] = $cat->name;
            }

            // Contar seções de vídeo (video_group)
            $video_group = get_post_meta($post_id, 'video_group', true);
            $num_secoes = is_array($video_group) ? count($video_group) : 0;

            $videos_data[] = array(
                'id' => $post_id,
                'title' => get_the_title(),
                'categorias' => implode(', ', $cat_names),
                'num_secoes' => $num_secoes,
                'date' => get_the_date('d/m/Y H:i')
            );
        }
        wp_reset_postdata();
    }

    wp_send_json_success(array(
        'videos' => $videos_data,
        'total' => $query->found_posts,
        'pages' => $query->max_num_pages
    ));
}

/**
 * AJAX: Criar novo vídeo
 */
add_action('wp_ajax_create_video_dashboard', 'ajax_create_video_dashboard');
function ajax_create_video_dashboard() {
    check_ajax_referer('dashboard_videos_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Sem permissão'));
    }

    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $categoria_id = isset($_POST['categoria_id']) ? absint($_POST['categoria_id']) : 0;
    $secoes_json = isset($_POST['secoes']) ? $_POST['secoes'] : '';

    if (empty($title)) {
        wp_send_json_error(array('message' => 'Título é obrigatório'));
    }

    $post_id = wp_insert_post(array(
        'post_type' => 'videos',
        'post_title' => $title,
        'post_status' => 'publish'
    ));

    if (is_wp_error($post_id)) {
        wp_send_json_error(array('message' => $post_id->get_error_message()));
    }

    if ($categoria_id > 0) {
        wp_set_post_terms($post_id, array($categoria_id), 'categoria_videos');
    }

    // Salvar seções de vídeo (video_group)
    if (!empty($secoes_json)) {
        $secoes = json_decode(stripslashes($secoes_json), true);
        if (is_array($secoes) && !empty($secoes)) {
            $video_group = array();
            foreach ($secoes as $secao) {
                if (!empty($secao['titulo_secao_do_video']) && !empty($secao['upload'])) {
                    $attachment_id = absint($secao['upload']);
                    
                    // Vincular attachment ao post de vídeo
                    wp_update_post(array(
                        'ID' => $attachment_id,
                        'post_parent' => $post_id
                    ));
                    
                    // Obter URL do attachment (CMB2 com 'url' => true espera URL)
                    $attachment_url = wp_get_attachment_url($attachment_id);
                    
                    if ($attachment_url) {
                        $video_group[] = array(
                            'titulo_secao_do_video' => sanitize_text_field($secao['titulo_secao_do_video']),
                            'upload' => $attachment_url,  // URL para CMB2
                            'upload_id' => $attachment_id  // Manter ID para referência
                        );
                    }
                }
            }
            
            // Limpar meta anterior
            delete_post_meta($post_id, 'video_group');
            
            // Salvar com add_post_meta para garantir serialização correta
            add_post_meta($post_id, 'video_group', $video_group, true);
        }
    }

    wp_send_json_success(array('message' => 'Vídeo criado com sucesso', 'post_id' => $post_id));
}

/**
 * AJAX: Buscar dados de um vídeo
 */
add_action('wp_ajax_get_video_data_dashboard', 'ajax_get_video_data_dashboard');
function ajax_get_video_data_dashboard() {
    check_ajax_referer('dashboard_videos_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Sem permissão'));
    }

    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;

    if ($post_id <= 0) {
        wp_send_json_error(array('message' => 'ID inválido'));
    }

    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'videos') {
        wp_send_json_error(array('message' => 'Vídeo não encontrado'));
    }

    $categorias = wp_get_post_terms($post_id, 'categoria_videos');
    $categoria_id = !empty($categorias) ? $categorias[0]->term_id : 0;

    // Buscar seções de vídeo (video_group)
    $video_group = get_post_meta($post_id, 'video_group', true);
    $secoes = array();
    if (is_array($video_group)) {
        foreach ($video_group as $item) {
            $attachment_id = isset($item['upload']) ? absint($item['upload']) : 0;
            $secoes[] = array(
                'titulo_secao_do_video' => isset($item['titulo_secao_do_video']) ? $item['titulo_secao_do_video'] : '',
                'upload' => $attachment_id,
                'url' => $attachment_id ? wp_get_attachment_url($attachment_id) : '',
                'filename' => $attachment_id ? basename(get_attached_file($attachment_id)) : ''
            );
        }
    }

    wp_send_json_success(array(
        'id' => $post_id,
        'title' => $post->post_title,
        'categoria_id' => $categoria_id,
        'secoes' => $secoes
    ));
}

/**
 * AJAX: Atualizar vídeo
 */
add_action('wp_ajax_update_video_dashboard', 'ajax_update_video_dashboard');
function ajax_update_video_dashboard() {
    check_ajax_referer('dashboard_videos_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Sem permissão'));
    }

    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $categoria_id = isset($_POST['categoria_id']) ? absint($_POST['categoria_id']) : 0;
    $secoes_json = isset($_POST['secoes']) ? $_POST['secoes'] : '';

    if ($post_id <= 0 || empty($title)) {
        wp_send_json_error(array('message' => 'Dados inválidos'));
    }

    $result = wp_update_post(array(
        'ID' => $post_id,
        'post_title' => $title
    ));

    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }

    if ($categoria_id > 0) {
        wp_set_post_terms($post_id, array($categoria_id), 'categoria_videos');
    } else {
        wp_delete_object_term_relationships($post_id, 'categoria_videos');
    }

    // Atualizar seções de vídeo (video_group)
    if (!empty($secoes_json)) {
        $secoes = json_decode(stripslashes($secoes_json), true);
        if (is_array($secoes)) {
            $video_group = array();
            foreach ($secoes as $secao) {
                if (!empty($secao['titulo_secao_do_video']) && !empty($secao['upload'])) {
                    $attachment_id = absint($secao['upload']);
                    
                    // Vincular attachment ao post de vídeo (se ainda não estiver vinculado)
                    $current_parent = wp_get_post_parent_id($attachment_id);
                    if ($current_parent != $post_id) {
                        wp_update_post(array(
                            'ID' => $attachment_id,
                            'post_parent' => $post_id
                        ));
                    }
                    
                    // Obter URL do attachment (CMB2 com 'url' => true espera URL)
                    $attachment_url = wp_get_attachment_url($attachment_id);
                    
                    if ($attachment_url) {
                        $video_group[] = array(
                            'titulo_secao_do_video' => sanitize_text_field($secao['titulo_secao_do_video']),
                            'upload' => $attachment_url,  // URL para CMB2
                            'upload_id' => $attachment_id  // Manter ID para referência
                        );
                    }
                }
            }
            
            // Limpar e re-adicionar para garantir formato correto
            delete_post_meta($post_id, 'video_group');
            add_post_meta($post_id, 'video_group', $video_group, true);
        }
    } else {
        // Se não houver seções, limpar meta
        delete_post_meta($post_id, 'video_group');
    }

    wp_send_json_success(array('message' => 'Vídeo atualizado com sucesso'));
}

/**
 * AJAX: Excluir vídeos
 */
add_action('wp_ajax_delete_videos_dashboard', 'ajax_delete_videos_dashboard');
function ajax_delete_videos_dashboard() {
    check_ajax_referer('dashboard_videos_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Sem permissão'));
    }

    $post_ids = isset($_POST['post_ids']) ? array_map('absint', (array) $_POST['post_ids']) : array();
    
    if (empty($post_ids)) {
        wp_send_json_error(array('message' => 'Nenhum vídeo selecionado'));
    }

    $deleted = 0;
    foreach ($post_ids as $pid) {
        if (wp_delete_post($pid, true)) {
            $deleted++;
        }
    }

    if ($deleted > 0) {
        wp_send_json_success(array('message' => sprintf('%d vídeo(s) excluído(s)', $deleted)));
    }

    wp_send_json_error(array('message' => 'Nenhum vídeo foi excluído'));
}

// ========================================
// DASHBOARD CENTRAL - KPIs
// ========================================

/**
 * AJAX: Buscar KPIs para o Dashboard Central
 */
add_action('wp_ajax_get_dashboard_kpis', 'ajax_get_dashboard_kpis');
function ajax_get_dashboard_kpis() {
    // Verificação de permissão e nonce
    check_ajax_referer('dashboard_central_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Sem permissão'));
    }

    // 1. Total de usuários
    $total_users = count_users();
    $total_users_count = $total_users['total_users'];

    // 2. Total de vídeos (CPT 'videos')
    $videos_query = new WP_Query(array(
        'post_type' => 'videos',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    $total_videos = $videos_query->found_posts;
    wp_reset_postdata();

    // 3. Total de categorias (taxonomia 'categoria_videos')
    $categorias = get_terms(array(
        'taxonomy' => 'categoria_videos',
        'hide_empty' => false
    ));
    $total_categorias = is_array($categorias) ? count($categorias) : 0;

    // 4. Top 3 unidades com mais usuários
    $unidades = get_terms(array(
        'taxonomy' => 'unidades',
        'hide_empty' => false
    ));

    $top_unidades = array();
    if (!empty($unidades) && !is_wp_error($unidades)) {
        foreach ($unidades as $unidade) {
            // Contar usuários desta unidade
            $users = get_users(array(
                'meta_query' => array(
                    array(
                        'key' => 'user_infos_empresas',
                        'value' => $unidade->slug,
                        'compare' => '='
                    )
                ),
                'fields' => 'ID'
            ));
            
            $top_unidades[] = array(
                'name' => $unidade->name,
                'count' => count($users)
            );
        }

        // Ordenar por contagem descendente e pegar top 3
        usort($top_unidades, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        $top_unidades = array_slice($top_unidades, 0, 3);
    }

    // Enviar resposta
    wp_send_json_success(array(
        'total_users' => $total_users_count,
        'total_videos' => $total_videos,
        'total_categorias' => $total_categorias,
        'top_unidades' => $top_unidades
    ));
}

/**
 * AJAX: Buscar alertas para o Dashboard Central
 */
add_action('wp_ajax_get_dashboard_alerts', 'ajax_get_dashboard_alerts');
function ajax_get_dashboard_alerts() {
    check_ajax_referer('dashboard_central_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Sem permissão'));
    }

    // 1. Vídeos sem categoria
    $videos_sem_categoria = new WP_Query(array(
        'post_type' => 'videos',
        'post_status' => 'publish',
        'tax_query' => array(
            array(
                'taxonomy' => 'categoria_videos',
                'operator' => 'NOT EXISTS'
            )
        ),
        'fields' => 'ids',
        'posts_per_page' => -1
    ));
    $count_videos_sem_cat = $videos_sem_categoria->found_posts;
    wp_reset_postdata();

    // 2. Categorias vazias
    $todas_categorias = get_terms(array(
        'taxonomy' => 'categoria_videos',
        'hide_empty' => false
    ));
    
    $categorias_vazias = 0;
    if (!empty($todas_categorias) && !is_wp_error($todas_categorias)) {
        foreach ($todas_categorias as $cat) {
            if ($cat->count == 0) {
                $categorias_vazias++;
            }
        }
    }

    // 3. Usuários sem unidade
    $usuarios_sem_unidade = get_users(array(
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => 'user_infos_empresas',
                'compare' => 'NOT EXISTS'
            ),
            array(
                'key' => 'user_infos_empresas',
                'value' => '',
                'compare' => '='
            )
        ),
        'fields' => 'ID'
    ));

    wp_send_json_success(array(
        'videos_sem_categoria' => $count_videos_sem_cat,
        'categorias_vazias' => $categorias_vazias,
        'usuarios_sem_unidade' => count($usuarios_sem_unidade)
    ));
}

/**
 * AJAX: Buscar atividade recente para o Dashboard Central
 */
add_action('wp_ajax_get_dashboard_activity', 'ajax_get_dashboard_activity');
function ajax_get_dashboard_activity() {
    check_ajax_referer('dashboard_central_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Sem permissão'));
    }

    // 1. Últimos 5 usuários
    $recent_users = get_users(array(
        'number' => 5,
        'orderby' => 'registered',
        'order' => 'DESC'
    ));

    $users_data = array();
    foreach ($recent_users as $user) {
        $unidade_slug = get_user_meta($user->ID, 'user_infos_empresas', true);
        $unidade_name = '';
        
        if ($unidade_slug) {
            $unidade_term = get_term_by('slug', $unidade_slug, 'unidades');
            if ($unidade_term) {
                $unidade_name = $unidade_term->name;
            }
        }

        $users_data[] = array(
            'name' => $user->display_name,
            'unidade' => $unidade_name,
            'date' => date_i18n('d/m/Y', strtotime($user->user_registered))
        );
    }

    // 2. Últimos 5 vídeos
    $recent_videos = new WP_Query(array(
        'post_type' => 'videos',
        'post_status' => 'publish',
        'posts_per_page' => 5,
        'orderby' => 'date',
        'order' => 'DESC'
    ));

    $videos_data = array();
    if ($recent_videos->have_posts()) {
        while ($recent_videos->have_posts()) {
            $recent_videos->the_post();
            
            $categorias = get_the_terms(get_the_ID(), 'categoria_videos');
            $categoria_name = '';
            if ($categorias && !is_wp_error($categorias)) {
                $categoria_name = $categorias[0]->name;
            }

            $videos_data[] = array(
                'title' => get_the_title(),
                'categoria' => $categoria_name,
                'date' => get_the_date('d/m/Y')
            );
        }
    }
    wp_reset_postdata();

    // 3. Últimas 3 categorias criadas (por term_id DESC = mais recentes)
    $recent_categorias = get_terms(array(
        'taxonomy' => 'categoria_videos',
        'hide_empty' => false,
        'number' => 3,
        'orderby' => 'term_id',
        'order' => 'DESC'
    ));

    $categorias_data = array();
    if (!empty($recent_categorias) && !is_wp_error($recent_categorias)) {
        foreach ($recent_categorias as $cat) {
            $categorias_data[] = array(
                'name' => $cat->name,
                'count' => $cat->count
            );
        }
    }

    wp_send_json_success(array(
        'users' => $users_data,
        'videos' => $videos_data,
        'categorias' => $categorias_data
    ));
}

/**
 * AJAX: Buscar dados do gráfico para o Dashboard Central
 */
add_action('wp_ajax_get_dashboard_chart', 'ajax_get_dashboard_chart');
function ajax_get_dashboard_chart() {
    check_ajax_referer('dashboard_central_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Sem permissão'));
    }

    // Buscar todas as categorias com contagem
    $categorias = get_terms(array(
        'taxonomy' => 'categoria_videos',
        'hide_empty' => false,
        'orderby' => 'count',
        'order' => 'DESC'
    ));

    $chart_data = array();
    if (!empty($categorias) && !is_wp_error($categorias)) {
        foreach ($categorias as $cat) {
            $chart_data[] = array(
                'name' => $cat->name,
                'count' => $cat->count
            );
        }
    }

    wp_send_json_success($chart_data);
}

/**
 * AJAX: Buscar detalhes de alertas específicos
 */
add_action('wp_ajax_get_alert_details', 'ajax_get_alert_details');
function ajax_get_alert_details() {
    check_ajax_referer('dashboard_central_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Sem permissão'));
    }

    $alert_type = isset($_POST['alert_type']) ? sanitize_text_field($_POST['alert_type']) : '';
    
    if (empty($alert_type)) {
        wp_send_json_error(array('message' => 'Tipo de alerta não especificado'));
    }

    $items = array();

    switch($alert_type) {
        case 'videos_sem_categoria':
            // Buscar vídeos sem categoria
            $videos = new WP_Query(array(
                'post_type' => 'videos',
                'post_status' => 'publish',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'categoria_videos',
                        'operator' => 'NOT EXISTS'
                    )
                ),
                'posts_per_page' => -1,
                'orderby' => 'date',
                'order' => 'DESC'
            ));

            if ($videos->have_posts()) {
                while ($videos->have_posts()) {
                    $videos->the_post();
                    $items[] = array(
                        'id' => get_the_ID(),
                        'title' => get_the_title(),
                        'date' => get_the_date('d/m/Y')
                    );
                }
            }
            wp_reset_postdata();
            break;

        case 'categorias_vazias':
            // Buscar categorias vazias
            $categorias = get_terms(array(
                'taxonomy' => 'categoria_videos',
                'hide_empty' => false
            ));

            if (!empty($categorias) && !is_wp_error($categorias)) {
                foreach ($categorias as $cat) {
                    if ($cat->count == 0) {
                        $items[] = array(
                            'id' => $cat->term_id,
                            'name' => $cat->name,
                            'slug' => $cat->slug,
                            'description' => $cat->description
                        );
                    }
                }
            }
            break;

        case 'usuarios_sem_unidade':
            // Buscar usuários sem unidade
            $users = get_users(array(
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => 'user_infos_empresas',
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key' => 'user_infos_empresas',
                        'value' => '',
                        'compare' => '='
                    )
                ),
                'orderby' => 'display_name',
                'order' => 'ASC'
            ));

            foreach ($users as $user) {
                $items[] = array(
                    'id' => $user->ID,
                    'name' => $user->display_name,
                    'login' => $user->user_login,
                    'email' => $user->user_email
                );
            }
            break;

        default:
            wp_send_json_error(array('message' => 'Tipo de alerta inválido'));
    }

    wp_send_json_success(array('items' => $items));
}

// ============================================================
// [MÉDIO-01] SESSION TIMEOUT — Auto-logout após 30 min de inatividade
// ============================================================

add_action( 'wp_enqueue_scripts', 'connectrh_enqueue_session_timeout' );
function connectrh_enqueue_session_timeout() {
    if ( ! is_user_logged_in() ) return;
    wp_enqueue_script(
        'connectrh-session-timeout',
        get_template_directory_uri() . '/js/functions/session-timeout.js',
        array( 'jquery' ), '1.0.0', true
    );
    wp_localize_script( 'connectrh-session-timeout', 'session_cfg', array(
        'timeout_ms'      => 30 * 60 * 1000,
        'warning_ms'      => 25 * 60 * 1000,
        'logout_url'      => wp_logout_url( home_url( 'login' ) ),
        'keepalive_nonce' => wp_create_nonce( 'session_keepalive' ),
        'ajaxurl'         => admin_url( 'admin-ajax.php' ),
    ) );
}

// [MÉDIO-01] AJAX keep-alive de sessão
add_action( 'wp_ajax_session_keepalive', 'ajax_session_keepalive' );
function ajax_session_keepalive() {
    check_ajax_referer( 'session_keepalive', 'nonce' );
    if ( is_user_logged_in() ) {
        wp_send_json_success( array( 'status' => 'alive' ) );
    }
    wp_send_json_error( array( 'message' => 'Sessão expirada.' ) );
}

// ============================================================
// [CRÍTICO-04] Nonce e URL para o endpoint de progresso de vídeo
// Injeta connectrh_vars no footer antes do custom-scripts.js
// ============================================================

add_action( 'wp_footer', 'connectrh_output_custom_scripts_vars', 5 );
function connectrh_output_custom_scripts_vars() {
    if ( ! is_user_logged_in() ) return;
    $data = array(
        'progresso_url'   => esc_url( get_template_directory_uri() . '/sql/progresso-video.php' ),
        'progresso_nonce' => wp_create_nonce( 'progresso_video_nonce' ),
    );
    echo '<script>var connectrh_vars = ' . wp_json_encode( $data ) . ';</script>' . "\n";
}

// ============================================================
// [BAIXO-04] SRI — Subresource Integrity para assets de CDN externo
// IMPORTANTE: Gere os hashes reais em https://www.srihash.org/ para cada versão
// ============================================================

add_filter( 'style_loader_tag',  'connectrh_add_sri', 10, 2 );
add_filter( 'script_loader_tag', 'connectrh_add_sri', 10, 2 );

function connectrh_add_sri( $tag, $handle ) {
    $sri = array(
        // Substitua pelos hashes SHA-384 reais gerados em srihash.org:
        'connectrh-fontawesome' => 'GERE_O_HASH_REAL_EM_SRIHASH_ORG',
        'sweetalert2'           => 'GERE_O_HASH_REAL_EM_SRIHASH_ORG',
    );
    if ( isset( $sri[ $handle ] ) && strpos( $sri[ $handle ], 'GERE' ) === false ) {
        $tag = str_replace(
            ' />',
            ' integrity="sha384-' . $sri[ $handle ] . '" crossorigin="anonymous" />',
            $tag
        );
    }
    return $tag;
}

?>
