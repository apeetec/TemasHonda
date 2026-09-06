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
require_once get_template_directory() . '/includes/sorteios-system.php';

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
        exit; // CORREÇÃO: exit obrigatório após wp_redirect para parar a execução do PHP
    }
}
// FIM REDIRECT 404

add_action( 'template_redirect', 'attachment_page_redirect', 10 );
function attachment_page_redirect() {
    if( is_attachment() ) {
        $url = wp_get_attachment_url( get_queried_object_id() );
        wp_redirect( home_url(), 301 );
        exit; // CORREÇÃO: exit obrigatório após wp_redirect
    }
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

add_action( 'send_headers', 'add_header_xframeoptions' );
function add_header_xframeoptions() {
header( 'X-Frame-Options: SAMEORIGIN' );
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
            'url' => false, // Hide the text input for the url
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






?>