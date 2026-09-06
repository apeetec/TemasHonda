<?php

// REDIRECT 404
add_action('template_redirect','redirect_404');
function redirect_404() {
    if(is_404()) {
        wp_redirect(home_url());
        exit;
    }
}
// FIM REDIRECT 404

add_action( 'template_redirect', 'attachment_page_redirect', 10 );
function attachment_page_redirect() {
    if( is_attachment() ) {
        $url = wp_get_attachment_url( get_queried_object_id() );
        wp_redirect( home_url(), 301 );
        exit;
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
add_action('admin_init','disable_comments');
function disable_comments() {
    $post_types = get_post_types();
    foreach ( $post_types as $post_type ) {
        if ( post_type_supports($post_type,'comments') ) {
            remove_post_type_support($post_type,'comments');
            remove_post_type_support($post_type,'trackbacks');
        }
    }
}

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
            'name'    => 'Setor',
            'desc'    => 'MMO,FND ou USI',
            'default' => '',
            'id'      => 'setor_usuario',
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
            'name'    => 'Nível',
            'desc'    => '',
            'default' => '',
            'id'      => 'nivel_usuario',
            'type'    => 'text',
        ) );

        $cmb_user->add_field( array(
            'name'    => 'Tempo gasto',
            'desc'    => 'Esse só é adicionado quando o usuário interrompe o jogo em andamento, quando o jogo  encerrado ele fica em 0',
            'default' => '',
            'id'      => 'game_time_left',
            'type'    => 'text',
        ) );
        $cmb_user->add_field( array(
            'name'    => 'Tempo gasto aps término do jogo',
            'desc'    => '',
            'default' => '',
            'id'      => 'game_time_left_end_game',
            'type'    => 'text',
        ) );
        $cmb_user->add_field( array(
            'name'    => 'Pontos marcados',
            'desc'    => '',
            'default' => '',
            'id'      => 'game_score',
            'type'    => 'text',
        ) );
        $cmb_user->add_field( array(
            'name'    => 'Pontos marcados(backup)',
            'desc'    => '',
            'default' => '',
            'id'      => 'game_clicks',
            'type'    => 'text',
        ) );
        $cmb_user->add_field( array(
            'name'    => 'Clique marcados',
            'desc'    => '',
            'default' => '',
            'id'      => 'game_markers',
            'type'    => 'text',
        ) );
        $cmb_user->add_field( array(
            'name'    => 'Tempo gasto',
            'desc'    => 'Tempo que levou no jogo de arrastar',
            'default' => '',
            'id'      => 'drag_left',
            'type'    => 'text',
        ) );
        $cmb_user->add_field( array(
            'name'    => 'Acertos',
            'desc'    => 'Acertos no jogo de arrastar',
            'default' => '',
            'id'      => 'score_drag',
            'type'    => 'text',
        ) );
        $cmb_user->add_field( array(
            'name'    => 'Iniciou o jogo de arrastar',
            'desc'    => '',
            'default' => '',
            'id'      => 'iniciou_jogo_arrastar',
            'type'    => 'text',
        ) );


    $cmb_user->add_field( array(
        'name'    => 'Comentários',
        'desc'    => '',
        'id'      => 'comentario',
        'type'    => 'wysiwyg',
        'options' => array(),
    ) );
    $cmb_user->add_field( array(
            'name'             => 'Presencial Segunda-feira',
            'desc'             => 'Acompanhou a palestra presencialmente de Segunda feira',
            'id'               => 'presencial_segunda-feira',
            'type'             => 'select',
            'show_option_none' => true,
            'default'          => 'custom',
            'options'          => array(
                'Presencial' => 'Presencial',
                'Não presencial'   => 'Não presencial',
            ),
    ) );
    $cmb_user->add_field( array(
            'name'             => 'Presencial Terça-feira',
            'desc'             => 'Acompanhou a palestra presencialmente de Terça feira',
            'id'               => 'presencial_terca-feira',
            'type'             => 'select',
            'show_option_none' => true,
            'default'          => 'custom',
            'options'          => array(
                'Presencial' => 'Presencial',
                'Não presencial'   => 'Não presencial',
            ),
    ) );
    $cmb_user->add_field( array(
            'name'             => 'Presencial Quarta-feira',
            'desc'             => 'Acompanhou a palestra presencialmente de Quarta feira',
            'id'               => 'presencial_quarta-feira',
            'type'             => 'select',
            'show_option_none' => true,
            'default'          => 'custom',
            'options'          => array(
                'Presencial' => 'Presencial',
                'Não presencial'   => 'Não presencial',
            ),
    ) );
    $cmb_user->add_field( array(
            'name'             => 'Presencial Quinta-feira',
            'desc'             => 'Acompanhou a palestra presencialmente de Quinta feira',
            'id'               => 'presencial_quinta-feira',
            'type'             => 'select',
            'show_option_none' => true,
            'default'          => 'custom',
            'options'          => array(
                'Presencial' => 'Presencial',
                'Não presencial'   => 'Não presencial',
            ),
    ) );

        $cmb_user->add_field( array(
    'name' => 'Acessou na data de Segunda-feira?',
    'desc' => '',
    'id'   => 'acessou_segunda-feira',
    'type' => 'checkbox',
    ) );



    $terms = get_terms( array(
        'taxonomy'   => 'datas_perguntas',
        'hide_empty' => false,
    ) );
    $datas = [];

    foreach( $terms as $term ){
        $slug = $term->slug;
        $datas[] = $slug;
    }

    $args = array(
        'post_type' => 'perguntas',
        'posts_per_page' => -1,
        'order'         => 'ASC',
        'tax_query' => array(
          array(
            'taxonomy' => 'datas_perguntas',
            'field' => 'slug',
            'terms' => $datas,
          )
        )
      );
      $perguntas = get_posts( $args );
      $cont = 0;
    //   Loop das alternativas
      for ($i=0; $i < count($perguntas); $i++) { 
        $post_id = $perguntas[$i]->ID;
        $nome = get_the_title($post_id);
        $prefix = sanitize_title($nome);
        $datas_pergunta = wp_get_object_terms( $post_id, 'datas_perguntas', array( 'fields' => 'names' ) );
        foreach($datas_pergunta as $data_pergunta){        
            $data_termo = $data_pergunta;          
            $slug_data = sanitize_title($data_termo);
        }  
            // Recupera os valores do outro groupbox 'meu_outro_groupbox'
            $entradas = get_post_meta( $post_id, 'grupo_de_respostas', true );
            // Inicializa um array para armazenar as opções
            $options = [];
            // Verifica se os valores foram retornados corretamente
            if ( ! empty( $entradas ) ) {
            // Itera sobre os valores do groupbox
            foreach ( $entradas as $group_item ) {
                // Aqui você pode acessar os campos dentro do groupbox, por exemplo:
                $valor_campo = $group_item['alternativa'];
                // Adiciona o valor ao array de opções
                $options[ $valor_campo ] = $valor_campo; // Você pode ajustar isso conforme necessário                  
                }
            }
            $cont++;
            $cmb2 = new_cmb2_box( array(
                'id'               => 'user_field_box_'.$slug_data,
                'title'            => 'Perguntas de '.$data_termo,
                'object_types'     => array( 'user' ),
                'show_names'       => true,
            ) ); 
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
                    'No presencial'   => 'Não presencial',
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
            // $cmb2->add_field( array(
            //     'name'    => 'Classificado para a data de'.' '.$data_termo,
            //     'desc'    => '',
            //     'default' => '',
            //     'id'      => 'classsificado_'.$slug_data,
            //     'type'    => 'text_medium'
            // ) );
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
            $cmb2->add_field( array(
                'name' => '('.$data_termo.')'.' '.$nome,
                'id'   => 'user_field_'. $slug_data.'_'.$post_id,
                'desc' =>  $nome,
                'column'   => true,
                'type' => 'select',
                'show_option_none' => true,
                // 'options_cb' => 'getAlt',
                'options'=> $options,
                ) );
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

// Ajax de tempo
function get_game_meta() {
    if (!is_user_logged_in()) {
        wp_send_json(null);
    }
    $allowed_keys = array('game_time_left', 'game_score', 'game_markers', 'game_clicks');
    $user_id = get_current_user_id();
    $key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
    if (!in_array($key, $allowed_keys, true)) {
        wp_send_json_error('Chave não permitida');
    }
    $value = get_user_meta($user_id, $key, true);
    wp_send_json($value);
}
add_action('wp_ajax_get_game_meta', 'get_game_meta');

function update_game_meta() {
    if (!is_user_logged_in()) {
        wp_send_json_error("Usuário não está logado");
    }
    $allowed_keys = array('game_time_left', 'game_score', 'game_markers', 'game_clicks');
    $user_id = get_current_user_id();
    $key = isset($_POST['key']) ? sanitize_text_field($_POST['key']) : '';
    $value = isset($_POST['value']) ? sanitize_text_field($_POST['value']) : '';
    if (!in_array($key, $allowed_keys, true)) {
        wp_send_json_error('Chave não permitida');
    }
    update_user_meta($user_id, $key, $value);
    wp_send_json_success("Dados atualizados");
}
add_action('wp_ajax_update_game_meta', 'update_game_meta');



// Jogo de arrastar
function save_game_results() {
    // Verifica se é uma requisição AJAX válida
    if (!isset($_POST['action']) || $_POST['action'] !== 'save_game_results') {
        wp_send_json_error(['message' => 'Requisição inválida!']);
        wp_die();
    }

    // Obtém os dados enviados pelo AJAX
    $score = isset($_POST['score']) ? intval($_POST['score']) : 0;
    $timeTaken = isset($_POST['timeTaken']) ? intval($_POST['timeTaken']) : 0;
    $user_id = get_current_user_id(); // Obtém o ID do usuário logado (se houver)


    update_user_meta($user_id, 'drag_left', $timeTaken);
    update_user_meta($user_id, 'score_drag', $score);
    update_user_meta($user_id, 'iniciou_jogo_arrastar', 'Sim');
    // Retorna uma resposta JSON de sucesso
    wp_send_json_success(['message' => 'Pontuação salva com sucesso!']);
    wp_die();
}

// Registra a função AJAX para usuários logados e não logados
add_action('wp_ajax_save_game_results', 'save_game_results');
add_action('wp_ajax_nopriv_save_game_results', 'save_game_results');


#################################### POST TYPE TREINAMENTOS ####################################

// Registro do Post Type Treinamentos
add_action( 'init', 'custom_post_type_treinamentos' );
function custom_post_type_treinamentos() {
    $labels = array(
        'name'                  => _x( 'Treinamentos', 'Post Type General Name', 'text_domain' ),
        'singular_name'         => _x( 'Treinamento', 'Post Type Singular Name', 'text_domain' ),
        'menu_name'             => __( 'Treinamentos', 'text_domain' ),
        'name_admin_bar'        => __( 'Treinamento', 'text_domain' ),
        'archives'              => __( 'Arquivo de Treinamentos', 'text_domain' ),
        'attributes'            => __( 'Atributos do Treinamento', 'text_domain' ),
        'parent_item_colon'     => __( 'Treinamento Pai:', 'text_domain' ),
        'all_items'             => __( 'Todos os Treinamentos', 'text_domain' ),
        'add_new_item'          => __( 'Adicionar Novo Treinamento', 'text_domain' ),
        'add_new'               => __( 'Adicionar Novo', 'text_domain' ),
        'new_item'              => __( 'Novo Treinamento', 'text_domain' ),
        'edit_item'             => __( 'Editar Treinamento', 'text_domain' ),
        'update_item'           => __( 'Atualizar Treinamento', 'text_domain' ),
        'view_item'             => __( 'Ver Treinamento', 'text_domain' ),
        'view_items'            => __( 'Ver Treinamentos', 'text_domain' ),
        'search_items'          => __( 'Buscar Treinamento', 'text_domain' ),
        'not_found'             => __( 'Não encontrado', 'text_domain' ),
        'not_found_in_trash'    => __( 'Não encontrado na lixeira', 'text_domain' ),
    );
    $args = array(
        'label'                 => __( 'Treinamento', 'text_domain' ),
        'description'           => __( 'Post type para gerenciar treinamentos', 'text_domain' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'thumbnail' ),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-welcome-learn-more',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
    );
    register_post_type( 'treinamentos', $args );
}

// Campos CMB2 do Post Type Treinamentos
add_action( 'cmb2_admin_init', 'cmb2_treinamentos_metaboxes' );
function cmb2_treinamentos_metaboxes() {

    $cmb = new_cmb2_box( array(
        'id'            => 'treinamentos_campos',
        'title'         => __( 'Informações do Treinamento', 'text_domain' ),
        'object_types'  => array( 'treinamentos' ),
        'closed'        => false,
    ) );

    // Produção
    $cmb->add_field( array(
        'name'    => __( 'Produção', 'text_domain' ),
        'desc'    => __( 'Selecione os níveis de Produção', 'text_domain' ),
        'id'      => 'treinamento_producao',
        'type'    => 'multicheck',
        'options' => array(
            'operacional'    => 'OPERACIONAL',
            'especializados' => 'ESPECIALIZADOS',
            'lideres'        => 'LÍDERES',
        ),
    ) );

    // Manutenção
    $cmb->add_field( array(
        'name'    => __( 'Manutenção', 'text_domain' ),
        'desc'    => __( 'Selecione os níveis de Manutenção', 'text_domain' ),
        'id'      => 'treinamento_manutencao',
        'type'    => 'multicheck',
        'options' => array(
            'operacional'    => 'OPERACIONAL',
            'especializados' => 'ESPECIALIZADOS',
            'lideres'        => 'LÍDERES',
        ),
    ) );

    // Qualidade
    $cmb->add_field( array(
        'name'    => __( 'Qualidade', 'text_domain' ),
        'desc'    => __( 'Selecione os níveis de Qualidade', 'text_domain' ),
        'id'      => 'treinamento_qualidade',
        'type'    => 'multicheck',
        'options' => array(
            'especializados'  => 'ESPECIALIZADOS',
            'lideres'         => 'LÍDERES',
            //'chefe_analistas' => 'CHEFE / ANALISTAS',
        ),
    ) );
	
	
	
	    $cmb->add_field( array(
        'name'    => __( 'Grupo tecnico', 'text_domain' ),
        'desc'    => __( 'Selecione os níveis de grupo tecnico', 'text_domain' ),
        'id'      => 'treinamento_grupo_tecnico',
        'type'    => 'multicheck',
        'options' => array(
            'especializados'  => 'ESPECIALIZADOS',
            'lideres'         => 'LÍDERES',
            'chefe_analistas' => 'CHEFE / ANALISTAS',
        ),
    ) );

    // Departamentos
    $cmb->add_field( array(
        'name'    => __( 'Departamentos', 'text_domain' ),
        'desc'    => __( 'Selecione os departamentos', 'text_domain' ),
        'id'      => 'treinamento_departamentos',
        'type'    => 'multicheck',
        'options' => array(
            'fnd' => 'FND',
            'usi' => 'USI',
            'mmo' => 'MMO',
        ),
    ) );

    // Agenda (Meses do ano)
    $cmb->add_field( array(
        'name'    => __( 'Agenda', 'text_domain' ),
        'desc'    => __( 'Selecione os meses', 'text_domain' ),
        'id'      => 'treinamento_agenda',
        'type'    => 'multicheck',
        'options' => array(
            'janeiro'   => 'Janeiro',
            'fevereiro' => 'Fevereiro',
            'marco'     => 'Março',
            'abril'     => 'Abril',
            'maio'      => 'Maio',
            'junho'     => 'Junho',
            'julho'     => 'Julho',
            'agosto'    => 'Agosto',
            'setembro'  => 'Setembro',
            'outubro'   => 'Outubro',
            'novembro'  => 'Novembro',
            'dezembro'  => 'Dezembro',
        ),
    ) );

    // Vagas
    $cmb->add_field( array(
        'name'       => __( 'Vagas', 'text_domain' ),
        'desc'       => __( 'Quantidade de vagas disponíveis para este treinamento', 'text_domain' ),
        'id'         => 'treinamento_vagas',
        'type'       => 'text',
        'attributes' => array(
            'type'    => 'number',
            'pattern' => '\d*',
        ),
    ) );
    // Nível
    $cmb->add_field( array(
        'name'       => __( 'Nível', 'text_domain' ),
        'desc'       => __( 'Selecione o nível do treinamento', 'text_domain' ),
        'id'         => 'treinamento_nivel',
        'type'       => 'text',
        'attributes' => array(
            'type'    => 'number',
            'pattern' => '\d*',
        ),
    ) );

}


#################################### AJAX INSCRIÇÃO TREINAMENTO ####################################

// Post type de Log de Inscrições (registro permanente)
add_action( 'init', 'custom_post_type_log_inscricoes' );
function custom_post_type_log_inscricoes() {
    $labels = array(
        'name'               => 'Log de Inscrições',
        'singular_name'      => 'Log de Inscrição',
        'menu_name'          => 'Log Inscrições',
        'all_items'          => 'Todos os Logs',
        'add_new'            => 'Adicionar Log',
        'add_new_item'       => 'Adicionar Novo Log',
        'edit_item'          => 'Editar Log',
        'view_item'          => 'Ver Log',
        'search_items'       => 'Buscar Logs',
        'not_found'          => 'Nenhum log encontrado',
        'not_found_in_trash' => 'Nenhum log na lixeira',
    );
    $args = array(
        'label'               => 'Log de Inscrições',
        'labels'              => $labels,
        'supports'            => array( 'title' ),
        'hierarchical'        => false,
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => 'edit.php?post_type=treinamentos',
        'menu_icon'           => 'dashicons-list-view',
        'show_in_admin_bar'   => false,
        'show_in_nav_menus'   => false,
        'can_export'          => true,
        'has_archive'         => false,
        'exclude_from_search' => true,
        'publicly_queryable'  => false,
        'capability_type'     => 'post',
    );
    register_post_type( 'log_inscricao', $args );
}

// CMB2 campos do Log de Inscrição
add_action( 'cmb2_admin_init', 'cmb2_log_inscricao_metaboxes' );
function cmb2_log_inscricao_metaboxes() {
    $cmb = new_cmb2_box( array(
        'id'            => 'log_inscricao_campos',
        'title'         => 'Detalhes da Inscrição',
        'object_types'  => array( 'log_inscricao' ),
        'closed'        => false,
    ) );
    $cmb->add_field( array(
        'name' => 'ID do Treinamento',
        'id'   => 'log_treinamento_id',
        'type' => 'text',
        'attributes' => array( 'readonly' => 'readonly' ),
    ) );
    $cmb->add_field( array(
        'name' => 'Nome do Treinamento',
        'id'   => 'log_treinamento_nome',
        'type' => 'text',
        'attributes' => array( 'readonly' => 'readonly' ),
    ) );
    $cmb->add_field( array(
        'name' => 'ID do Usuário',
        'id'   => 'log_user_id',
        'type' => 'text',
        'attributes' => array( 'readonly' => 'readonly' ),
    ) );
    $cmb->add_field( array(
        'name' => 'Matrícula',
        'id'   => 'log_matricula',
        'type' => 'text',
        'attributes' => array( 'readonly' => 'readonly' ),
    ) );
    $cmb->add_field( array(
        'name' => 'Nome do Colaborador',
        'id'   => 'log_nome_colaborador',
        'type' => 'text',
        'attributes' => array( 'readonly' => 'readonly' ),
    ) );
    $cmb->add_field( array(
        'name' => 'Função',
        'id'   => 'log_funcao',
        'type' => 'text',
        'attributes' => array( 'readonly' => 'readonly' ),
    ) );
    $cmb->add_field( array(
        'name' => 'Nível',
        'id'   => 'log_nivel',
        'type' => 'text',
        'attributes' => array( 'readonly' => 'readonly' ),
    ) );
    $cmb->add_field( array(
        'name' => 'Departamento',
        'id'   => 'log_departamento',
        'type' => 'text',
        'attributes' => array( 'readonly' => 'readonly' ),
    ) );
    $cmb->add_field( array(
        'name' => 'Mês',
        'id'   => 'log_mes',
        'type' => 'text',
        'attributes' => array( 'readonly' => 'readonly' ),
    ) );
    $cmb->add_field( array(
        'name' => 'Status',
        'id'   => 'log_status',
        'type' => 'text',
        'attributes' => array( 'readonly' => 'readonly' ),
    ) );
}

// CMB2 Groupbox de inscritos DENTRO do post type Treinamentos
add_action( 'cmb2_admin_init', 'cmb2_treinamentos_inscritos_metabox' );
function cmb2_treinamentos_inscritos_metabox() {
    $cmb = new_cmb2_box( array(
        'id'            => 'treinamentos_inscritos_box',
        'title'         => 'Colaboradores Inscritos',
        'object_types'  => array( 'treinamentos' ),
        'closed'        => false,
        'priority'      => 'low',
    ) );

    $group_id = $cmb->add_field( array(
        'id'          => 'treinamento_inscritos',
        'type'        => 'group',
        'description' => 'Lista de colaboradores inscritos neste treinamento',
        'repeatable'  => true,
        'options'     => array(
            'group_title'   => 'Inscrito {#}',
            'add_button'    => 'Adicionar inscrito',
            'remove_button' => 'Remover inscrito',
            'sortable'      => false,
            'closed'        => true,
        ),
    ) );
    $cmb->add_group_field( $group_id, array(
        'name' => 'ID do Usuário',
        'id'   => 'inscrito_user_id',
        'type' => 'text',
    ) );
    $cmb->add_group_field( $group_id, array(
        'name' => 'Matrícula',
        'id'   => 'inscrito_matricula',
        'type' => 'text',
    ) );
    $cmb->add_group_field( $group_id, array(
        'name' => 'Nome',
        'id'   => 'inscrito_nome',
        'type' => 'text',
    ) );
    $cmb->add_group_field( $group_id, array(
        'name' => 'Função',
        'id'   => 'inscrito_funcao',
        'type' => 'text',
    ) );
    $cmb->add_group_field( $group_id, array(
        'name' => 'Nível',
        'id'   => 'inscrito_nivel',
        'type' => 'text',
    ) );
    $cmb->add_group_field( $group_id, array(
        'name' => 'Departamento',
        'id'   => 'inscrito_departamento',
        'type' => 'text',
    ) );
    $cmb->add_group_field( $group_id, array(
        'name' => 'Mês',
        'id'   => 'inscrito_mes',
        'type' => 'text',
    ) );
    $cmb->add_group_field( $group_id, array(
        'name' => 'Data da Inscrição',
        'id'   => 'inscrito_data',
        'type' => 'text',
    ) );
}

// Inscrever usuário em um treinamento
add_action( 'wp_ajax_inscricao_treinamento', 'ajax_inscricao_treinamento' );
function ajax_inscricao_treinamento() {
    check_ajax_referer( 'inscricao_treinamento_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Usuário não autenticado.' ) );
    }

    $user_id        = get_current_user_id();
    $treinamento_id = intval( $_POST['treinamento_id'] );
    $funcao         = sanitize_text_field( $_POST['funcao'] );
    $nivel          = sanitize_text_field( $_POST['nivel'] );
    $departamento   = sanitize_text_field( $_POST['departamento'] );
    $mes            = sanitize_text_field( $_POST['mes'] );

    $current_user   = wp_get_current_user();
    $matricula      = $current_user->user_login;
    $nome_user      = $current_user->display_name;

    // Verificar se o treinamento existe
    $treinamento = get_post( $treinamento_id );
    if ( ! $treinamento || $treinamento->post_type !== 'treinamentos' ) {
        wp_send_json_error( array( 'message' => 'Treinamento não encontrado.' ) );
    }

    // Verificar vagas
    $vagas = intval( get_post_meta( $treinamento_id, 'treinamento_vagas', true ) );
    if ( $vagas <= 0 ) {
        wp_send_json_error( array( 'message' => 'Não há vagas disponíveis para este treinamento.' ) );
    }

    // Buscar inscrições existentes do usuário
    $inscricoes = get_user_meta( $user_id, 'inscricoes_treinamentos', true );
    if ( ! is_array( $inscricoes ) ) {
        $inscricoes = array();
    }

    // Verificar se já está inscrito neste treinamento
    foreach ( $inscricoes as $insc ) {
        if ( intval( $insc['treinamento_id'] ) === $treinamento_id ) {
            wp_send_json_error( array( 'message' => 'Você já está inscrito neste treinamento.' ) );
        }
    }

    $data_inscricao = current_time( 'mysql' );

    // ===== 1) Salvar no user_meta =====
    $inscricoes[] = array(
        'treinamento_id' => $treinamento_id,
        'funcao'         => $funcao,
        'nivel'          => $nivel,
        'departamento'   => $departamento,
        'mes'            => $mes,
        'data_inscricao' => $data_inscricao,
    );
    update_user_meta( $user_id, 'inscricoes_treinamentos', $inscricoes );

    // ===== 2) Salvar no groupbox do próprio treinamento =====
    $inscritos = get_post_meta( $treinamento_id, 'treinamento_inscritos', true );
    if ( ! is_array( $inscritos ) ) {
        $inscritos = array();
    }
    $inscritos[] = array(
        'inscrito_user_id'      => $user_id,
        'inscrito_matricula'    => $matricula,
        'inscrito_nome'         => $nome_user,
        'inscrito_funcao'       => $funcao,
        'inscrito_nivel'        => $nivel,
        'inscrito_departamento' => $departamento,
        'inscrito_mes'          => $mes,
        'inscrito_data'         => $data_inscricao,
    );
    update_post_meta( $treinamento_id, 'treinamento_inscritos', $inscritos );

    // ===== 3) Criar post de Log permanente =====
    $funcao_labels = array( 'producao' => 'Produção', 'manutencao' => 'Manutenção', 'qualidade' => 'Qualidade', 'grupo_tecnico' => 'Grupo Técnico' );
    $nivel_labels  = array( 'operacional' => 'OPERACIONAL', 'especializados' => 'ESPECIALIZADOS', 'lideres' => 'LÍDERES', 'chefe_analistas' => 'CHEFE / ANALISTAS' );
    $depto_labels  = array( 'fnd' => 'FND', 'usi' => 'USI', 'mmo' => 'MMO' );
    $meses_labels  = array( 'janeiro'=>'Janeiro','fevereiro'=>'Fevereiro','marco'=>'Março','abril'=>'Abril','maio'=>'Maio','junho'=>'Junho','julho'=>'Julho','agosto'=>'Agosto','setembro'=>'Setembro','outubro'=>'Outubro','novembro'=>'Novembro','dezembro'=>'Dezembro' );

    $log_title = $matricula . ' - ' . $treinamento->post_title . ' - ' . $data_inscricao;
    $log_post_id = wp_insert_post( array(
        'post_type'   => 'log_inscricao',
        'post_title'  => $log_title,
        'post_status' => 'publish',
    ) );

    if ( $log_post_id && ! is_wp_error( $log_post_id ) ) {
        update_post_meta( $log_post_id, 'log_treinamento_id',    $treinamento_id );
        update_post_meta( $log_post_id, 'log_treinamento_nome',  $treinamento->post_title );
        update_post_meta( $log_post_id, 'log_user_id',           $user_id );
        update_post_meta( $log_post_id, 'log_matricula',         $matricula );
        update_post_meta( $log_post_id, 'log_nome_colaborador',  $nome_user );
        update_post_meta( $log_post_id, 'log_funcao',            isset($funcao_labels[$funcao]) ? $funcao_labels[$funcao] : $funcao );
        update_post_meta( $log_post_id, 'log_nivel',             isset($nivel_labels[$nivel]) ? $nivel_labels[$nivel] : $nivel );
        update_post_meta( $log_post_id, 'log_departamento',      isset($depto_labels[$departamento]) ? $depto_labels[$departamento] : $departamento );
        update_post_meta( $log_post_id, 'log_mes',               isset($meses_labels[$mes]) ? $meses_labels[$mes] : $mes );
        update_post_meta( $log_post_id, 'log_status',            'Inscrito' );
    }

    // Decrementar vagas
    $novas_vagas = $vagas - 1;
    update_post_meta( $treinamento_id, 'treinamento_vagas', $novas_vagas );

    wp_send_json_success( array(
        'message'     => 'Inscrição realizada com sucesso!',
        'novas_vagas' => $novas_vagas,
    ) );
}

// Cancelar inscrição do usuário
add_action( 'wp_ajax_cancelar_inscricao_treinamento', 'ajax_cancelar_inscricao_treinamento' );
function ajax_cancelar_inscricao_treinamento() {
    check_ajax_referer( 'inscricao_treinamento_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Usuário não autenticado.' ) );
    }

    $user_id        = get_current_user_id();
    $index          = intval( $_POST['index'] );
    $treinamento_id = intval( $_POST['treinamento_id'] );

    $inscricoes = get_user_meta( $user_id, 'inscricoes_treinamentos', true );
    if ( ! is_array( $inscricoes ) || ! isset( $inscricoes[ $index ] ) ) {
        wp_send_json_error( array( 'message' => 'Inscrição não encontrada.' ) );
    }

    // Remover do user_meta
    array_splice( $inscricoes, $index, 1 );
    update_user_meta( $user_id, 'inscricoes_treinamentos', $inscricoes );

    // Remover do groupbox do treinamento
    $inscritos = get_post_meta( $treinamento_id, 'treinamento_inscritos', true );
    if ( is_array( $inscritos ) ) {
        foreach ( $inscritos as $key => $inscrito ) {
            if ( intval( $inscrito['inscrito_user_id'] ) === $user_id ) {
                array_splice( $inscritos, $key, 1 );
                break;
            }
        }
        update_post_meta( $treinamento_id, 'treinamento_inscritos', $inscritos );
    }

    // Criar log de cancelamento
    $current_user = wp_get_current_user();
    $treinamento  = get_post( $treinamento_id );
    $log_title    = $current_user->user_login . ' - CANCELAMENTO - ' . ($treinamento ? $treinamento->post_title : $treinamento_id) . ' - ' . current_time( 'mysql' );
    $log_post_id  = wp_insert_post( array(
        'post_type'   => 'log_inscricao',
        'post_title'  => $log_title,
        'post_status' => 'publish',
    ) );
    if ( $log_post_id && ! is_wp_error( $log_post_id ) ) {
        update_post_meta( $log_post_id, 'log_treinamento_id',   $treinamento_id );
        update_post_meta( $log_post_id, 'log_treinamento_nome', $treinamento ? $treinamento->post_title : '' );
        update_post_meta( $log_post_id, 'log_user_id',          $user_id );
        update_post_meta( $log_post_id, 'log_matricula',        $current_user->user_login );
        update_post_meta( $log_post_id, 'log_nome_colaborador', $current_user->display_name );
        update_post_meta( $log_post_id, 'log_status',           'Cancelado' );
    }

    // Incrementar vagas de volta
    $vagas = intval( get_post_meta( $treinamento_id, 'treinamento_vagas', true ) );
    update_post_meta( $treinamento_id, 'treinamento_vagas', $vagas + 1 );

    wp_send_json_success( array(
        'message' => 'Inscrição cancelada com sucesso!',
    ) );
}

// Buscar inscrições atualizadas (para atualizar modal sem reload)
add_action( 'wp_ajax_buscar_inscricoes_treinamento', 'ajax_buscar_inscricoes_treinamento' );
function ajax_buscar_inscricoes_treinamento() {
    check_ajax_referer( 'inscricao_treinamento_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Usuário não autenticado.' ) );
    }

    $user_id    = get_current_user_id();
    $inscricoes = get_user_meta( $user_id, 'inscricoes_treinamentos', true );
    if ( ! is_array( $inscricoes ) ) {
        $inscricoes = array();
    }

    $meses_labels = array(
        'janeiro'=>'Janeiro','fevereiro'=>'Fevereiro','marco'=>'Março','abril'=>'Abril',
        'maio'=>'Maio','junho'=>'Junho','julho'=>'Julho','agosto'=>'Agosto',
        'setembro'=>'Setembro','outubro'=>'Outubro','novembro'=>'Novembro','dezembro'=>'Dezembro'
    );
    $nivel_labels = array(
        'operacional'=>'OPERACIONAL','especializados'=>'ESPECIALIZADOS',
        'lideres'=>'LÍDERES','chefe_analistas'=>'CHEFE / ANALISTAS'
    );
    $funcao_labels = array('producao'=>'Produção','manutencao'=>'Manutenção','qualidade'=>'Qualidade','grupo_tecnico'=>'Grupo Técnico');
    $depto_labels  = array('fnd'=>'FND','usi'=>'USI','mmo'=>'MMO');

    $resultado = array();
    foreach ( $inscricoes as $idx => $insc ) {
        $post_t = get_post( $insc['treinamento_id'] );
        if ( ! $post_t ) continue;

        $mes_val   = isset($insc['mes']) ? $insc['mes'] : '';
        $depto_val = isset($insc['departamento']) ? $insc['departamento'] : '';

        $resultado[] = array(
            'index'          => $idx,
            'treinamento'    => $post_t->post_title,
            'treinamento_id' => $insc['treinamento_id'],
            'funcao'         => isset($funcao_labels[$insc['funcao']]) ? $funcao_labels[$insc['funcao']] : $insc['funcao'],
            'nivel'          => isset($nivel_labels[$insc['nivel']]) ? $nivel_labels[$insc['nivel']] : $insc['nivel'],
            'departamento'   => isset($depto_labels[$depto_val]) ? $depto_labels[$depto_val] : $depto_val,
            'mes'            => isset($meses_labels[$mes_val]) ? $meses_labels[$mes_val] : $mes_val,
        );
    }

    wp_send_json_success( $resultado );
}


#################################### IMPORTAÇÃO DE TREINAMENTOS (CSV) ####################################

// Submenu em Treinamentos
add_action( 'admin_menu', 'treinamentos_importacao_menu' );
function treinamentos_importacao_menu() {
    add_submenu_page(
        'edit.php?post_type=treinamentos',
        'Importar Treinamentos',
        'Importar CSV',
        'manage_options',
        'importar-treinamentos',
        'treinamentos_importacao_page'
    );
}

// Enqueue CSS da página de importação
add_action( 'admin_enqueue_scripts', 'treinamentos_importacao_assets' );
function treinamentos_importacao_assets( $hook ) {
    if ( $hook !== 'treinamentos_page_importar-treinamentos' ) return;
    wp_enqueue_style(
        'importacao-treinamentos-css',
        get_template_directory_uri() . '/css/importacao-treinamentos.css',
        array(),
        time()
    );
}

// Download do modelo CSV
add_action( 'admin_init', 'treinamentos_download_modelo_csv' );
function treinamentos_download_modelo_csv() {
    if (
        isset( $_GET['acao_treinamentos'] ) &&
        $_GET['acao_treinamentos'] === 'download_modelo' &&
        current_user_can( 'manage_options' )
    ) {
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=modelo-treinamentos.csv' );
        $output = fopen( 'php://output', 'w' );
        // BOM para Excel reconhecer UTF-8
        fprintf( $output, chr(0xEF) . chr(0xBB) . chr(0xBF) );
        // Cabeçalho
        fputcsv( $output, array(
            'titulo',
            'producao',
            'manutencao',
            'qualidade',
            'grupo_tecnico',
            'departamentos',
            'agenda',
            'vagas',
            'nivel'
        ), ';' );
        // Linhas de exemplo
        fputcsv( $output, array(
            'NR-12 Segurança em Máquinas',
            'operacional|especializados',
            'operacional',
            '',
            '',
            'fnd|usi',
            'janeiro|fevereiro|marco',
            '30',
            '1'
        ), ';' );
        fputcsv( $output, array(
            'Gestão da Qualidade Total',
            '',
            '',
            'especializados|lideres',
            'especializados|lideres|chefe_analistas',
            'fnd|usi|mmo',
            'abril|maio|junho',
            '20',
            '2'
        ), ';' );
        fputcsv( $output, array(
            'Manutenção Preventiva Industrial',
            '',
            'operacional|especializados|lideres',
            '',
            '',
            'mmo',
            'julho|agosto',
            '15',
            '3'
        ), ';' );
        fclose( $output );
        exit;
    }
}

// Processar importação do CSV
add_action( 'admin_init', 'treinamentos_processar_importacao' );
function treinamentos_processar_importacao() {
    if (
        ! isset( $_POST['treinamentos_importar_csv'] ) ||
        ! current_user_can( 'manage_options' ) ||
        ! wp_verify_nonce( $_POST['_wpnonce_importar'], 'importar_treinamentos_csv' )
    ) {
        return;
    }

    if ( empty( $_FILES['csv_treinamentos']['tmp_name'] ) ) {
        add_settings_error( 'treinamentos_import', 'no_file', 'Nenhum arquivo selecionado.', 'error' );
        return;
    }

    $file = $_FILES['csv_treinamentos']['tmp_name'];

    // Opções válidas para validação
    $valid_producao      = array( 'operacional', 'especializados', 'lideres' );
    $valid_manutencao    = array( 'operacional', 'especializados', 'lideres' );
    $valid_qualidade     = array( 'especializados', 'lideres' );
    $valid_grupo_tecnico = array( 'especializados', 'lideres', 'chefe_analistas' );
    $valid_deptos        = array( 'fnd', 'usi', 'mmo' );
    $valid_meses      = array( 'janeiro','fevereiro','marco','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro' );

    $handle = fopen( $file, 'r' );
    if ( ! $handle ) {
        add_settings_error( 'treinamentos_import', 'file_error', 'Não foi possível ler o arquivo.', 'error' );
        return;
    }

    // Detectar BOM UTF-8 e pular
    $bom = fread( $handle, 3 );
    if ( $bom !== chr(0xEF) . chr(0xBB) . chr(0xBF) ) {
        rewind( $handle );
    }

    // Ler cabeçalho
    $header = fgetcsv( $handle, 0, ';' );
    if ( ! $header ) {
        fclose( $handle );
        add_settings_error( 'treinamentos_import', 'header_error', 'Arquivo CSV vazio ou sem cabeçalho.', 'error' );
        return;
    }

    // Normalizar cabeçalho
    $header = array_map( function( $h ) {
        return strtolower( trim( $h ) );
    }, $header );

    $required_cols = array( 'titulo', 'vagas' );
    foreach ( $required_cols as $col ) {
        if ( ! in_array( $col, $header ) ) {
            fclose( $handle );
            add_settings_error( 'treinamentos_import', 'missing_col', 'Coluna obrigatória ausente: ' . $col, 'error' );
            return;
        }
    }

    $importados = 0;
    $erros      = array();
    $linha      = 1; // cabeçalho é 1

    while ( ( $row = fgetcsv( $handle, 0, ';' ) ) !== false ) {
        $linha++;

        // Pular linhas completamente vazias
        if ( count( array_filter( $row ) ) === 0 ) continue;

        // Mapear colunas
        $dados = array();
        foreach ( $header as $i => $col_name ) {
            $dados[ $col_name ] = isset( $row[ $i ] ) ? trim( $row[ $i ] ) : '';
        }

        // Validar título
        if ( empty( $dados['titulo'] ) ) {
            $erros[] = "Linha {$linha}: Título vazio — ignorada.";
            continue;
        }

        // Validar vagas
        $vagas = intval( $dados['vagas'] );
        if ( $vagas <= 0 && $dados['vagas'] !== '0' ) {
            $erros[] = "Linha {$linha}: Vagas inválidas (\"{$dados['vagas']}\") — ignorada.";
            continue;
        }

        // Processar multichecks
        $producao      = treinamentos_parse_multi( $dados, 'producao',      $valid_producao,      $erros, $linha );
        $manutencao    = treinamentos_parse_multi( $dados, 'manutencao',    $valid_manutencao,    $erros, $linha );
        $qualidade     = treinamentos_parse_multi( $dados, 'qualidade',     $valid_qualidade,     $erros, $linha );
        $grupo_tecnico = treinamentos_parse_multi( $dados, 'grupo_tecnico', $valid_grupo_tecnico, $erros, $linha );
        $deptos        = treinamentos_parse_multi( $dados, 'departamentos', $valid_deptos,        $erros, $linha );
        $agenda        = treinamentos_parse_multi( $dados, 'agenda',        $valid_meses,         $erros, $linha );

        // Pelo menos uma função deve ser preenchida
        if ( empty( $producao ) && empty( $manutencao ) && empty( $qualidade ) && empty( $grupo_tecnico ) ) {
            $erros[] = "Linha {$linha}: Nenhuma função (produção/manutenção/qualidade/grupo técnico) foi informada — ignorada.";
            continue;
        }

        // Pelo menos um departamento
        if ( empty( $deptos ) ) {
            $erros[] = "Linha {$linha}: Nenhum departamento informado — ignorada.";
            continue;
        }

        // Criar o post
        $post_id = wp_insert_post( array(
            'post_type'   => 'treinamentos',
            'post_title'  => sanitize_text_field( $dados['titulo'] ),
            'post_status' => 'publish',
        ) );

        if ( is_wp_error( $post_id ) ) {
            $erros[] = "Linha {$linha}: Erro ao criar o post — " . $post_id->get_error_message();
            continue;
        }

        // Salvar meta fields
        if ( ! empty( $producao ) )      update_post_meta( $post_id, 'treinamento_producao',       $producao );
        if ( ! empty( $manutencao ) )    update_post_meta( $post_id, 'treinamento_manutencao',     $manutencao );
        if ( ! empty( $qualidade ) )     update_post_meta( $post_id, 'treinamento_qualidade',      $qualidade );
        if ( ! empty( $grupo_tecnico ) ) update_post_meta( $post_id, 'treinamento_grupo_tecnico',  $grupo_tecnico );
        if ( ! empty( $deptos ) )        update_post_meta( $post_id, 'treinamento_departamentos',  $deptos );
        if ( ! empty( $agenda ) )        update_post_meta( $post_id, 'treinamento_agenda',         $agenda );
        update_post_meta( $post_id, 'treinamento_vagas', $vagas );

        // Nível
        $nivel = isset( $dados['nivel'] ) ? intval( $dados['nivel'] ) : 0;
        if ( $nivel > 0 ) {
            update_post_meta( $post_id, 'treinamento_nivel', $nivel );
        }

        $importados++;
    }

    fclose( $handle );

    // Armazenar resultado em transient para exibir na tela
    set_transient( 'treinamentos_import_result', array(
        'importados' => $importados,
        'erros'      => $erros,
    ), 60 );

    // Redirect para evitar resubmissão
    wp_redirect( admin_url( 'edit.php?post_type=treinamentos&page=importar-treinamentos&importado=1' ) );
    exit;
}

// Helper: parsear campo multi separado por |
function treinamentos_parse_multi( $dados, $campo, $valid, &$erros, $linha ) {
    if ( empty( $dados[ $campo ] ) ) return array();
    $valores = array_map( 'trim', explode( '|', strtolower( $dados[ $campo ] ) ) );
    $result  = array();
    foreach ( $valores as $v ) {
        if ( $v === '' ) continue;
        if ( in_array( $v, $valid ) ) {
            $result[] = $v;
        } else {
            $erros[] = "Linha {$linha}: Valor inválido \"{$v}\" no campo \"{$campo}\" — ignorado.";
        }
    }
    return $result;
}


// ========== PÁGINA DE IMPORTAÇÃO ==========
function treinamentos_importacao_page() {
    $modelo_url = admin_url( 'edit.php?post_type=treinamentos&page=importar-treinamentos&acao_treinamentos=download_modelo' );

    // Resultado de importação (se houver)
    $result = get_transient( 'treinamentos_import_result' );
    if ( $result ) {
        delete_transient( 'treinamentos_import_result' );
    }
    ?>
    <div class="wrap importacao-treinamentos-wrap">
        <h1><span class="dashicons dashicons-upload"></span> Importar Treinamentos via CSV</h1>

        <?php if ( $result ) : ?>
            <div class="import-result <?php echo ( $result['importados'] > 0 ) ? 'result-success' : 'result-warning'; ?>">
                <div class="result-icon">
                    <?php if ( $result['importados'] > 0 ) : ?>
                        <span class="dashicons dashicons-yes-alt"></span>
                    <?php else : ?>
                        <span class="dashicons dashicons-warning"></span>
                    <?php endif; ?>
                </div>
                <div class="result-body">
                    <strong><?php echo intval( $result['importados'] ); ?> treinamento(s) importado(s) com sucesso!</strong>
                    <?php if ( ! empty( $result['erros'] ) ) : ?>
                        <details class="result-errors">
                            <summary><?php echo count( $result['erros'] ); ?> aviso(s) / erro(s)</summary>
                            <ul>
                                <?php foreach ( $result['erros'] as $e ) : ?>
                                    <li><?php echo esc_html( $e ); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </details>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- PASSO 1 -->
        <div class="import-card">
            <div class="card-step">1</div>
            <div class="card-content">
                <h2>Baixar o modelo CSV</h2>
                <p>Faça o download do arquivo modelo abaixo. Ele já contém o cabeçalho correto e algumas linhas de exemplo para guiá-lo.</p>
                <a href="<?php echo esc_url( $modelo_url ); ?>" class="button-import button-download">
                    <span class="dashicons dashicons-download"></span> Baixar Modelo CSV
                </a>
            </div>
        </div>

        <!-- PASSO 2 -->
        <div class="import-card">
            <div class="card-step">2</div>
            <div class="card-content">
                <h2>Preencher o CSV</h2>
                <p>Abra o arquivo no Excel, Google Sheets ou editor de texto e preencha cada linha com um treinamento. Veja abaixo como preencher:</p>

                <table class="import-ref-table">
                    <thead>
                        <tr>
                            <th>Coluna</th>
                            <th>Obrigatório</th>
                            <th>Descrição</th>
                            <th>Valores aceitos</th>
                            <th>Exemplo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>titulo</code></td>
                            <td><span class="badge-sim">Sim</span></td>
                            <td>Nome do treinamento</td>
                            <td>Texto livre</td>
                            <td>NR-12 Segurança em Máquinas</td>
                        </tr>
                        <tr>
                            <td><code>producao</code></td>
                            <td><span class="badge-cond">*</span></td>
                            <td>Níveis de Produção</td>
                            <td><code>operacional</code>, <code>especializados</code>, <code>lideres</code></td>
                            <td>operacional|especializados</td>
                        </tr>
                        <tr>
                            <td><code>manutencao</code></td>
                            <td><span class="badge-cond">*</span></td>
                            <td>Níveis de Manutenção</td>
                            <td><code>operacional</code>, <code>especializados</code>, <code>lideres</code></td>
                            <td>operacional</td>
                        </tr>
                        <tr>
                            <td><code>qualidade</code></td>
                            <td><span class="badge-cond">*</span></td>
                            <td>Níveis de Qualidade</td>
                            <td><code>especializados</code>, <code>lideres</code></td>
                            <td>especializados|lideres</td>
                        </tr>
                        <tr>
                            <td><code>grupo_tecnico</code></td>
                            <td><span class="badge-cond">*</span></td>
                            <td>Níveis de Grupo Técnico</td>
                            <td><code>especializados</code>, <code>lideres</code>, <code>chefe_analistas</code></td>
                            <td>lideres|chefe_analistas</td>
                        </tr>
                        <tr>
                            <td><code>departamentos</code></td>
                            <td><span class="badge-sim">Sim</span></td>
                            <td>Departamentos</td>
                            <td><code>fnd</code>, <code>usi</code>, <code>mmo</code></td>
                            <td>fnd|usi</td>
                        </tr>
                        <tr>
                            <td><code>agenda</code></td>
                            <td><span class="badge-nao">Não</span></td>
                            <td>Meses disponíveis</td>
                            <td><code>janeiro</code>, <code>fevereiro</code>, <code>marco</code>, <code>abril</code>, <code>maio</code>, <code>junho</code>, <code>julho</code>, <code>agosto</code>, <code>setembro</code>, <code>outubro</code>, <code>novembro</code>, <code>dezembro</code></td>
                            <td>janeiro|fevereiro|marco</td>
                        </tr>
                        <tr>
                            <td><code>vagas</code></td>
                            <td><span class="badge-sim">Sim</span></td>
                            <td>Número de vagas</td>
                            <td>Número inteiro</td>
                            <td>30</td>
                        </tr>
                        <tr>
                            <td><code>nivel</code></td>
                            <td><span class="badge-nao">Não</span></td>
                            <td>Nível do treinamento</td>
                            <td>Número inteiro</td>
                            <td>1</td>
                        </tr>
                    </tbody>
                </table>

                <div class="import-tips">
                    <h3><span class="dashicons dashicons-lightbulb"></span> Dicas importantes</h3>
                    <ul>
                        <li>O separador entre colunas é <strong>ponto e vírgula (;)</strong> — padrão do Excel em Português.</li>
                        <li>Para informar <strong>múltiplos valores</strong> em um campo, separe-os com <strong>barra vertical</strong> ( <code>|</code> ). Ex: <code>fnd|usi|mmo</code></li>
                        <li><span class="badge-cond">*</span> Pelo menos <strong>uma função</strong> (produção, manutenção, qualidade ou grupo técnico) deve ser preenchida por treinamento.</li>
                        <li>Se um campo de função não se aplica, deixe-o <strong>vazio</strong>.</li>
                        <li>O campo <strong>mês</strong> usa "marco" (sem acento) para Março.</li>
                        <li>Salve o arquivo com codificação <strong>UTF-8</strong>.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- PASSO 3 -->
        <div class="import-card">
            <div class="card-step">3</div>
            <div class="card-content">
                <h2>Enviar o arquivo</h2>
                <p>Selecione o CSV preenchido e clique em <strong>Importar</strong>. Treinamentos duplicados (mesmo título) não são verificados — cada importação cria novos registros.</p>

                <form method="POST" enctype="multipart/form-data" class="import-form">
                    <?php wp_nonce_field( 'importar_treinamentos_csv', '_wpnonce_importar' ); ?>
                    <input type="hidden" name="treinamentos_importar_csv" value="1">

                    <div class="import-file-area" id="drop-area">
                        <span class="dashicons dashicons-media-spreadsheet"></span>
                        <p>Arraste o arquivo CSV aqui ou clique para selecionar</p>
                        <input type="file" name="csv_treinamentos" id="csv_treinamentos" accept=".csv" required>
                        <span class="file-name" id="file-name-display"></span>
                    </div>

                    <button type="submit" class="button-import button-send">
                        <span class="dashicons dashicons-upload"></span> Importar Treinamentos
                    </button>
                </form>
            </div>
        </div>

    </div>

    <script>
    (function(){
        var dropArea  = document.getElementById('drop-area');
        var fileInput = document.getElementById('csv_treinamentos');
        var fileLabel = document.getElementById('file-name-display');

        // Click na área
        dropArea.addEventListener('click', function(e) {
            if (e.target !== fileInput) fileInput.click();
        });

        // Nome do arquivo selecionado
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                fileLabel.textContent = this.files[0].name;
                dropArea.classList.add('has-file');
            } else {
                fileLabel.textContent = '';
                dropArea.classList.remove('has-file');
            }
        });

        // Drag & drop
        ['dragenter','dragover'].forEach(function(ev){
            dropArea.addEventListener(ev, function(e){ e.preventDefault(); e.stopPropagation(); dropArea.classList.add('drag-over'); });
        });
        ['dragleave','drop'].forEach(function(ev){
            dropArea.addEventListener(ev, function(e){ e.preventDefault(); e.stopPropagation(); dropArea.classList.remove('drag-over'); });
        });
        dropArea.addEventListener('drop', function(e) {
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                fileLabel.textContent = e.dataTransfer.files[0].name;
                dropArea.classList.add('has-file');
            }
        });
    })();
    </script>

    <?php
}


#################################### GERENCIAR USUÁRIOS (Importação / Exclusão) ####################################

// Submenu em Usuários
add_action( 'admin_menu', 'usuarios_gerenciar_menu' );
function usuarios_gerenciar_menu() {
    add_submenu_page(
        'users.php',
        'Gerenciar Usuários',
        'Gerenciar Usuários',
        'manage_options',
        'gerenciar-usuarios',
        'usuarios_gerenciar_page'
    );
}

// Enqueue CSS da página
add_action( 'admin_enqueue_scripts', 'usuarios_gerenciar_assets' );
function usuarios_gerenciar_assets( $hook ) {
    if ( $hook !== 'users_page_gerenciar-usuarios' ) return;
    wp_enqueue_style(
        'gerenciar-usuarios-css',
        get_template_directory_uri() . '/css/gerenciar-usuarios.css',
        array(),
        time()
    );
}

// ========== DOWNLOAD DO MODELO CSV — IMPORTAÇÃO ==========
add_action( 'admin_init', 'usuarios_download_modelo_importacao_csv' );
function usuarios_download_modelo_importacao_csv() {
    if (
        ! isset( $_GET['acao_usuarios'] ) ||
        $_GET['acao_usuarios'] !== 'download_modelo_importacao' ||
        ! current_user_can( 'manage_options' )
    ) {
        return;
    }
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=modelo-importacao-usuarios.csv' );
    $output = fopen( 'php://output', 'w' );
    fprintf( $output, chr(0xEF) . chr(0xBB) . chr(0xBF) );
    fputcsv( $output, array(
        'user_login',
        'user_email',
        'user_pass',
        'user_nicename',
        'display_name',
        'first_name',
        'unidade_usuario',
        'empresa_usuario',
        'setor_usuario',
        'nivel_usuario'
    ), ';' );
    fputcsv( $output, array(
        'joao.silva',
        'joao.silva@empresa.com',
        'Senha@123',
        'joao-silva',
        'João Silva',
        'João',
        'Sumaré',
        'Honda',
        'FND',
        'operacional'
    ), ';' );
    fputcsv( $output, array(
        'maria.santos',
        'maria.santos@empresa.com',
        'Senha@456',
        'maria-santos',
        'Maria Santos',
        'Maria',
        'Itirapina',
        'Honda',
        'MMO',
        'especializados'
    ), ';' );
    fclose( $output );
    exit;
}

// ========== DOWNLOAD DO MODELO CSV — EXCLUSÃO ==========
add_action( 'admin_init', 'usuarios_download_modelo_exclusao_csv' );
function usuarios_download_modelo_exclusao_csv() {
    if (
        ! isset( $_GET['acao_usuarios'] ) ||
        $_GET['acao_usuarios'] !== 'download_modelo_exclusao' ||
        ! current_user_can( 'manage_options' )
    ) {
        return;
    }
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=modelo-exclusao-usuarios.csv' );
    $output = fopen( 'php://output', 'w' );
    fprintf( $output, chr(0xEF) . chr(0xBB) . chr(0xBF) );
    fputcsv( $output, array( 'user_login', 'user_email' ), ';' );
    fputcsv( $output, array( 'joao.silva', 'joao.silva@empresa.com' ), ';' );
    fputcsv( $output, array( 'maria.santos', '' ), ';' );
    fputcsv( $output, array( '', 'jose@empresa.com' ), ';' );
    fclose( $output );
    exit;
}

// ========== PROCESSAR IMPORTAÇÃO DE USUÁRIOS ==========
add_action( 'admin_init', 'usuarios_processar_importacao' );
function usuarios_processar_importacao() {
    if (
        ! isset( $_POST['usuarios_importar_csv'] ) ||
        ! current_user_can( 'manage_options' ) ||
        ! wp_verify_nonce( $_POST['_wpnonce_importar_usuarios'], 'importar_usuarios_csv' )
    ) {
        return;
    }

    if ( empty( $_FILES['csv_usuarios']['tmp_name'] ) ) {
        set_transient( 'usuarios_import_result', array(
            'criados'    => 0,
            'atualizados'=> 0,
            'erros'      => array( 'Nenhum arquivo selecionado.' ),
        ), 60 );
        wp_redirect( admin_url( 'users.php?page=gerenciar-usuarios&tab=importar&importado=1' ) );
        exit;
    }

    $file   = $_FILES['csv_usuarios']['tmp_name'];
    $handle = fopen( $file, 'r' );
    if ( ! $handle ) {
        set_transient( 'usuarios_import_result', array(
            'criados' => 0, 'atualizados' => 0,
            'erros'   => array( 'Não foi possível ler o arquivo.' ),
        ), 60 );
        wp_redirect( admin_url( 'users.php?page=gerenciar-usuarios&tab=importar&importado=1' ) );
        exit;
    }

    // BOM
    $bom = fread( $handle, 3 );
    if ( $bom !== chr(0xEF) . chr(0xBB) . chr(0xBF) ) {
        rewind( $handle );
    }

    $header = fgetcsv( $handle, 0, ';' );
    if ( ! $header ) {
        fclose( $handle );
        set_transient( 'usuarios_import_result', array(
            'criados' => 0, 'atualizados' => 0,
            'erros'   => array( 'Arquivo CSV vazio ou sem cabeçalho.' ),
        ), 60 );
        wp_redirect( admin_url( 'users.php?page=gerenciar-usuarios&tab=importar&importado=1' ) );
        exit;
    }

    $header = array_map( function( $h ) {
        return strtolower( trim( $h ) );
    }, $header );

    // Verificar colunas obrigatórias
    $required = array( 'user_login', 'user_email', 'user_pass' );
    foreach ( $required as $col ) {
        if ( ! in_array( $col, $header ) ) {
            fclose( $handle );
            set_transient( 'usuarios_import_result', array(
                'criados' => 0, 'atualizados' => 0,
                'erros'   => array( 'Coluna obrigatória ausente: ' . $col ),
            ), 60 );
            wp_redirect( admin_url( 'users.php?page=gerenciar-usuarios&tab=importar&importado=1' ) );
            exit;
        }
    }

    $criados      = 0;
    $atualizados  = 0;
    $erros        = array();
    $linha        = 1;

    // Campos que são meta do usuário (não nativos do wp_insert_user)
    $meta_fields = array( 'unidade_usuario', 'empresa_usuario', 'setor_usuario' );
    // Campos meta → chave real no banco
    $meta_map = array(
        'unidade_usuario' => 'user_infos_empresas',
        'empresa_usuario' => 'empresa_usuario',
        'setor_usuario'   => 'setor_usuario',
    );

    while ( ( $row = fgetcsv( $handle, 0, ';' ) ) !== false ) {
        $linha++;
        if ( count( array_filter( $row ) ) === 0 ) continue;

        $dados = array();
        foreach ( $header as $i => $col_name ) {
            $dados[ $col_name ] = isset( $row[ $i ] ) ? trim( $row[ $i ] ) : '';
        }

        // Validações
        if ( empty( $dados['user_login'] ) ) {
            $erros[] = "Linha {$linha}: user_login vazio — ignorada.";
            continue;
        }
        if ( empty( $dados['user_email'] ) || ! is_email( $dados['user_email'] ) ) {
            $erros[] = "Linha {$linha}: E-mail inválido (\"{$dados['user_email']}\") — ignorada.";
            continue;
        }
        if ( empty( $dados['user_pass'] ) ) {
            $erros[] = "Linha {$linha}: Senha vazia — ignorada.";
            continue;
        }

        // Processar unidade (taxonomy term)
        $unidade_slug = '';
        if ( ! empty( $dados['unidade_usuario'] ) ) {
            $unidade_slug = usuarios_process_term( $dados['unidade_usuario'], 'unidades' );
        }

        $existing = get_user_by( 'email', $dados['user_email'] );
        if ( ! $existing ) {
            $existing = get_user_by( 'login', $dados['user_login'] );
        }

        if ( $existing ) {
            // Atualizar usuário existente
            $update_data = array( 'ID' => $existing->ID );
            if ( ! empty( $dados['user_nicename'] ) )  $update_data['user_nicename'] = sanitize_text_field( $dados['user_nicename'] );
            if ( ! empty( $dados['display_name'] ) )    $update_data['display_name']  = sanitize_text_field( $dados['display_name'] );
            if ( ! empty( $dados['first_name'] ) )      $update_data['first_name']    = sanitize_text_field( $dados['first_name'] );

            $result = wp_update_user( $update_data );
            if ( is_wp_error( $result ) ) {
                $erros[] = "Linha {$linha}: Erro ao atualizar \"{$dados['user_login']}\" — " . $result->get_error_message();
                continue;
            }

            wp_set_password( $dados['user_pass'], $existing->ID );

            // Meta fields
            if ( $unidade_slug ) {
                update_user_meta( $existing->ID, 'user_infos_empresas', $unidade_slug );
            }
            if ( ! empty( $dados['empresa_usuario'] ) ) {
                update_user_meta( $existing->ID, 'empresa_usuario', sanitize_text_field( $dados['empresa_usuario'] ) );
            }
            if ( ! empty( $dados['setor_usuario'] ) ) {
                update_user_meta( $existing->ID, 'setor_usuario', sanitize_text_field( strtoupper( $dados['setor_usuario'] ) ) );
            }
            if ( ! empty( $dados['nivel_usuario'] ) ) {
                update_user_meta( $existing->ID, 'nivel_usuario', sanitize_text_field( $dados['nivel_usuario'] ) );
            }

            $atualizados++;
        } else {
            // Criar novo
            $user_data = array(
                'user_login'    => sanitize_user( $dados['user_login'] ),
                'user_email'    => sanitize_email( $dados['user_email'] ),
                'user_pass'     => $dados['user_pass'],
                'user_nicename' => ! empty( $dados['user_nicename'] ) ? sanitize_text_field( $dados['user_nicename'] ) : sanitize_title( $dados['user_login'] ),
                'display_name'  => ! empty( $dados['display_name'] )  ? sanitize_text_field( $dados['display_name'] )  : $dados['user_login'],
                'first_name'    => ! empty( $dados['first_name'] )    ? sanitize_text_field( $dados['first_name'] )    : '',
                'role'          => 'subscriber',
            );

            $user_id = wp_insert_user( $user_data );
            if ( is_wp_error( $user_id ) ) {
                $erros[] = "Linha {$linha}: Erro ao criar \"{$dados['user_login']}\" — " . $user_id->get_error_message();
                continue;
            }

            // Meta fields
            if ( $unidade_slug ) {
                update_user_meta( $user_id, 'user_infos_empresas', $unidade_slug );
            }
            if ( ! empty( $dados['empresa_usuario'] ) ) {
                update_user_meta( $user_id, 'empresa_usuario', sanitize_text_field( $dados['empresa_usuario'] ) );
            }
            if ( ! empty( $dados['setor_usuario'] ) ) {
                update_user_meta( $user_id, 'setor_usuario', sanitize_text_field( strtoupper( $dados['setor_usuario'] ) ) );
            }
            if ( ! empty( $dados['nivel_usuario'] ) ) {
                update_user_meta( $user_id, 'nivel_usuario', sanitize_text_field( $dados['nivel_usuario'] ) );
            }
            update_user_meta( $user_id, 'user_field_senha_alterada', 'Não' );

            $criados++;
        }
    }

    fclose( $handle );

    set_transient( 'usuarios_import_result', array(
        'criados'     => $criados,
        'atualizados' => $atualizados,
        'erros'       => $erros,
    ), 60 );

    wp_redirect( admin_url( 'users.php?page=gerenciar-usuarios&tab=importar&importado=1' ) );
    exit;
}

// Helper: processar termo de taxonomia (criar se não existir)
function usuarios_process_term( $term_name, $taxonomy ) {
    $term_name = trim( $term_name );
    if ( empty( $term_name ) ) return '';
    $term = get_term_by( 'name', $term_name, $taxonomy );
    if ( $term ) {
        return $term->slug;
    }
    $term = get_term_by( 'slug', sanitize_title( $term_name ), $taxonomy );
    if ( $term ) {
        return $term->slug;
    }
    $inserted = wp_insert_term( $term_name, $taxonomy );
    if ( ! is_wp_error( $inserted ) ) {
        $new_term = get_term( $inserted['term_id'], $taxonomy );
        return $new_term->slug;
    }
    return '';
}

// ========== PROCESSAR EXCLUSÃO POR CSV ==========
add_action( 'admin_init', 'usuarios_processar_exclusao_csv' );
function usuarios_processar_exclusao_csv() {
    if (
        ! isset( $_POST['usuarios_excluir_csv'] ) ||
        ! current_user_can( 'manage_options' ) ||
        ! wp_verify_nonce( $_POST['_wpnonce_excluir_usuarios'], 'excluir_usuarios_csv' )
    ) {
        return;
    }

    if ( empty( $_FILES['csv_exclusao']['tmp_name'] ) ) {
        set_transient( 'usuarios_delete_result', array(
            'excluidos' => 0,
            'erros'     => array( 'Nenhum arquivo selecionado.' ),
        ), 60 );
        wp_redirect( admin_url( 'users.php?page=gerenciar-usuarios&tab=excluir-csv&excluido=1' ) );
        exit;
    }

    $file   = $_FILES['csv_exclusao']['tmp_name'];
    $handle = fopen( $file, 'r' );
    if ( ! $handle ) {
        set_transient( 'usuarios_delete_result', array(
            'excluidos' => 0,
            'erros'     => array( 'Não foi possível ler o arquivo.' ),
        ), 60 );
        wp_redirect( admin_url( 'users.php?page=gerenciar-usuarios&tab=excluir-csv&excluido=1' ) );
        exit;
    }

    $bom = fread( $handle, 3 );
    if ( $bom !== chr(0xEF) . chr(0xBB) . chr(0xBF) ) {
        rewind( $handle );
    }

    $header = fgetcsv( $handle, 0, ';' );
    if ( ! $header ) {
        fclose( $handle );
        set_transient( 'usuarios_delete_result', array(
            'excluidos' => 0,
            'erros'     => array( 'Arquivo CSV vazio ou sem cabeçalho.' ),
        ), 60 );
        wp_redirect( admin_url( 'users.php?page=gerenciar-usuarios&tab=excluir-csv&excluido=1' ) );
        exit;
    }

    $header = array_map( function( $h ) {
        return strtolower( trim( $h ) );
    }, $header );

    $has_login = in_array( 'user_login', $header );
    $has_email = in_array( 'user_email', $header );
    if ( ! $has_login && ! $has_email ) {
        fclose( $handle );
        set_transient( 'usuarios_delete_result', array(
            'excluidos' => 0,
            'erros'     => array( 'O CSV deve conter pelo menos a coluna user_login ou user_email.' ),
        ), 60 );
        wp_redirect( admin_url( 'users.php?page=gerenciar-usuarios&tab=excluir-csv&excluido=1' ) );
        exit;
    }

    require_once( ABSPATH . 'wp-admin/includes/user.php' );
    $current_user_id = get_current_user_id();

    $excluidos = 0;
    $erros     = array();
    $linha     = 1;

    while ( ( $row = fgetcsv( $handle, 0, ';' ) ) !== false ) {
        $linha++;
        if ( count( array_filter( $row ) ) === 0 ) continue;

        $dados = array();
        foreach ( $header as $i => $col_name ) {
            $dados[ $col_name ] = isset( $row[ $i ] ) ? trim( $row[ $i ] ) : '';
        }

        $user = null;
        $identifier = '';

        if ( $has_login && ! empty( $dados['user_login'] ) ) {
            $user = get_user_by( 'login', $dados['user_login'] );
            $identifier = $dados['user_login'];
        }
        if ( ! $user && $has_email && ! empty( $dados['user_email'] ) ) {
            $user = get_user_by( 'email', $dados['user_email'] );
            $identifier = $dados['user_email'];
        }

        if ( ! $user ) {
            $erros[] = "Linha {$linha}: Usuário não encontrado (\"{$identifier}\") — ignorado.";
            continue;
        }

        // Nunca excluir o próprio admin logado
        if ( $user->ID === $current_user_id ) {
            $erros[] = "Linha {$linha}: Não é possível excluir o próprio usuário logado (\"{$user->user_login}\") — ignorado.";
            continue;
        }

        // Não excluir super admins
        if ( is_super_admin( $user->ID ) ) {
            $erros[] = "Linha {$linha}: Não é possível excluir um super administrador (\"{$user->user_login}\") — ignorado.";
            continue;
        }

        $deleted = wp_delete_user( $user->ID );
        if ( $deleted ) {
            $excluidos++;
        } else {
            $erros[] = "Linha {$linha}: Erro ao excluir \"{$user->user_login}\".";
        }
    }

    fclose( $handle );

    set_transient( 'usuarios_delete_result', array(
        'excluidos' => $excluidos,
        'erros'     => $erros,
    ), 60 );

    wp_redirect( admin_url( 'users.php?page=gerenciar-usuarios&tab=excluir-csv&excluido=1' ) );
    exit;
}

// ========== AJAX — PREVIEW EXCLUSÃO POR FILTROS ==========
add_action( 'wp_ajax_usuarios_preview_exclusao', 'usuarios_preview_exclusao_ajax' );
function usuarios_preview_exclusao_ajax() {
    check_ajax_referer( 'usuarios_filtros_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Sem permissão.' );
    }

    $date_from = ! empty( $_POST['date_from'] ) ? sanitize_text_field( $_POST['date_from'] ) : '';
    $date_to   = ! empty( $_POST['date_to'] )   ? sanitize_text_field( $_POST['date_to'] )   : '';
    $unidade   = ! empty( $_POST['unidade'] )    ? sanitize_text_field( $_POST['unidade'] )   : '';

    if ( empty( $date_from ) && empty( $date_to ) && empty( $unidade ) ) {
        wp_send_json_error( 'Selecione pelo menos um filtro.' );
    }

    global $wpdb;
    $current_user_id = get_current_user_id();

    // Construir query com meta e datas
    $args = array(
        'number'  => 500,
        'exclude' => array( $current_user_id ),
        'role__not_in' => array( 'administrator' ),
    );

    if ( $date_from || $date_to ) {
        $date_query = array();
        if ( $date_from ) {
            $date_query['after'] = $date_from;
        }
        if ( $date_to ) {
            $date_query['before'] = date( 'Y-m-d', strtotime( $date_to . ' +1 day' ) );
        }
        $date_query['inclusive'] = true;
        $args['date_query'] = array( $date_query );
    }

    if ( $unidade ) {
        $args['meta_query'] = array(
            array(
                'key'     => 'user_infos_empresas',
                'value'   => $unidade,
                'compare' => '=',
            ),
        );
    }

    $user_query = new WP_User_Query( $args );
    $users      = $user_query->get_results();
    $total      = $user_query->get_total();

    $list = array();
    foreach ( $users as $u ) {
        $list[] = array(
            'ID'           => $u->ID,
            'user_login'   => $u->user_login,
            'user_email'   => $u->user_email,
            'display_name' => $u->display_name,
            'registered'   => date( 'd/m/Y', strtotime( $u->user_registered ) ),
            'unidade'      => get_user_meta( $u->ID, 'user_infos_empresas', true ),
        );
    }

    wp_send_json_success( array(
        'total' => $total,
        'users' => $list,
    ) );
}

// ========== AJAX — EXECUTAR EXCLUSÃO POR FILTROS ==========
add_action( 'wp_ajax_usuarios_executar_exclusao', 'usuarios_executar_exclusao_ajax' );
function usuarios_executar_exclusao_ajax() {
    check_ajax_referer( 'usuarios_filtros_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Sem permissão.' );
    }

    $ids = isset( $_POST['user_ids'] ) ? $_POST['user_ids'] : array();
    if ( empty( $ids ) || ! is_array( $ids ) ) {
        wp_send_json_error( 'Nenhum usuário selecionado.' );
    }

    require_once( ABSPATH . 'wp-admin/includes/user.php' );
    $current_user_id = get_current_user_id();

    $excluidos = 0;
    $erros     = array();

    foreach ( $ids as $id ) {
        $id = intval( $id );
        if ( $id <= 0 ) continue;

        if ( $id === $current_user_id ) {
            $erros[] = "Não é possível excluir o próprio usuário logado (ID {$id}).";
            continue;
        }

        $user = get_userdata( $id );
        if ( ! $user ) {
            $erros[] = "Usuário ID {$id} não encontrado.";
            continue;
        }

        if ( is_super_admin( $id ) ) {
            $erros[] = "Não é possível excluir o super administrador \"{$user->user_login}\".";
            continue;
        }

        if ( wp_delete_user( $id ) ) {
            $excluidos++;
        } else {
            $erros[] = "Erro ao excluir \"{$user->user_login}\" (ID {$id}).";
        }
    }

    wp_send_json_success( array(
        'excluidos' => $excluidos,
        'erros'     => $erros,
    ) );
}


// ========== PÁGINA ADMIN — GERENCIAR USUÁRIOS ==========
function usuarios_gerenciar_page() {
    $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'importar';

    $modelo_import_url  = admin_url( 'users.php?page=gerenciar-usuarios&acao_usuarios=download_modelo_importacao' );
    $modelo_delete_url  = admin_url( 'users.php?page=gerenciar-usuarios&acao_usuarios=download_modelo_exclusao' );

    // Resultados de importação
    $import_result = get_transient( 'usuarios_import_result' );
    if ( $import_result ) delete_transient( 'usuarios_import_result' );

    // Resultados de exclusão CSV
    $delete_result = get_transient( 'usuarios_delete_result' );
    if ( $delete_result ) delete_transient( 'usuarios_delete_result' );

    // Lista de unidades para o filtro
    $unidades = get_terms( array( 'taxonomy' => 'unidades', 'hide_empty' => false ) );
    ?>
    <div class="wrap gerenciar-usuarios-wrap">

        <h1><span class="dashicons dashicons-admin-users"></span> Gerenciar Usuários</h1>

        <!-- ABAS -->
        <div class="gu-tabs">
            <button type="button" class="gu-tab-btn <?php echo $active_tab === 'importar' ? 'active' : ''; ?>" data-tab="importar">
                <span class="dashicons dashicons-upload"></span> Importar CSV
            </button>
            <button type="button" class="gu-tab-btn <?php echo $active_tab === 'excluir-csv' ? 'active' : ''; ?>" data-tab="excluir-csv">
                <span class="dashicons dashicons-trash"></span> Excluir por CSV
            </button>
            <button type="button" class="gu-tab-btn <?php echo $active_tab === 'excluir-filtros' ? 'active' : ''; ?>" data-tab="excluir-filtros">
                <span class="dashicons dashicons-filter"></span> Excluir por Filtros
            </button>
        </div>

        <!-- ============================================================ -->
        <!-- TAB 1: IMPORTAR USUÁRIOS -->
        <!-- ============================================================ -->
        <div class="gu-tab-content <?php echo $active_tab === 'importar' ? 'active' : ''; ?>" id="tab-importar">

            <?php if ( $import_result ) : ?>
                <?php
                    $total = $import_result['criados'] + $import_result['atualizados'];
                    $cls   = $total > 0 ? 'result-success' : 'result-warning';
                    $icon  = $total > 0 ? 'yes-alt' : 'warning';
                ?>
                <div class="import-result <?php echo $cls; ?>">
                    <div class="result-icon"><span class="dashicons dashicons-<?php echo $icon; ?>"></span></div>
                    <div class="result-body">
                        <strong><?php echo intval( $import_result['criados'] ); ?> usuário(s) criado(s) e <?php echo intval( $import_result['atualizados'] ); ?> atualizado(s).</strong>
                        <?php if ( ! empty( $import_result['erros'] ) ) : ?>
                            <details class="result-errors">
                                <summary><?php echo count( $import_result['erros'] ); ?> aviso(s) / erro(s)</summary>
                                <ul>
                                    <?php foreach ( $import_result['erros'] as $e ) : ?>
                                        <li><?php echo esc_html( $e ); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </details>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Passo 1: Baixar modelo -->
            <div class="import-card">
                <div class="card-step">1</div>
                <div class="card-content">
                    <h2>Baixar o modelo CSV</h2>
                    <p>Faça o download do arquivo modelo. Ele já contém o cabeçalho correto e linhas de exemplo.</p>
                    <a href="<?php echo esc_url( $modelo_import_url ); ?>" class="button-import button-download">
                        <span class="dashicons dashicons-download"></span> Baixar Modelo CSV
                    </a>
                </div>
            </div>

            <!-- Passo 2: Referência -->
            <div class="import-card">
                <div class="card-step">2</div>
                <div class="card-content">
                    <h2>Preencher o CSV</h2>
                    <p>Abra o arquivo no Excel ou Google Sheets e preencha cada linha com um usuário. Veja a referência abaixo:</p>

                    <table class="import-ref-table">
                        <thead>
                            <tr>
                                <th>Coluna</th>
                                <th>Obrigatório</th>
                                <th>Descrição</th>
                                <th>Exemplo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>user_login</code></td>
                                <td><span class="badge-sim">Sim</span></td>
                                <td>Login do usuário (único)</td>
                                <td>joao.silva</td>
                            </tr>
                            <tr>
                                <td><code>user_email</code></td>
                                <td><span class="badge-sim">Sim</span></td>
                                <td>E-mail do usuário (único)</td>
                                <td>joao.silva@empresa.com</td>
                            </tr>
                            <tr>
                                <td><code>user_pass</code></td>
                                <td><span class="badge-sim">Sim</span></td>
                                <td>Senha do usuário</td>
                                <td>Senha@123</td>
                            </tr>
                            <tr>
                                <td><code>user_nicename</code></td>
                                <td><span class="badge-nao">Não</span></td>
                                <td>Slug amigável (se vazio, usa o login)</td>
                                <td>joao-silva</td>
                            </tr>
                            <tr>
                                <td><code>display_name</code></td>
                                <td><span class="badge-nao">Não</span></td>
                                <td>Nome de exibição</td>
                                <td>João Silva</td>
                            </tr>
                            <tr>
                                <td><code>first_name</code></td>
                                <td><span class="badge-nao">Não</span></td>
                                <td>Primeiro nome</td>
                                <td>João</td>
                            </tr>
                            <tr>
                                <td><code>unidade_usuario</code></td>
                                <td><span class="badge-nao">Não</span></td>
                                <td>Unidade / planta (cria o termo se não existir)</td>
                                <td>Sumaré</td>
                            </tr>
                            <tr>
                                <td><code>empresa_usuario</code></td>
                                <td><span class="badge-nao">Não</span></td>
                                <td>Nome da empresa</td>
                                <td>Honda</td>
                            </tr>
                            <tr>
                                <td><code>setor_usuario</code></td>
                                <td><span class="badge-nao">Não</span></td>
                                <td>Setor (será convertido para maiúsculas)</td>
                                <td>FND, MMO ou USI</td>
                            </tr>
                            <tr>
                                <td><code>nivel_usuario</code></td>
                                <td><span class="badge-nao">Não</span></td>
                                <td>Nível do usuário</td>
                                <td>operacional, especializados, etc.</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="import-tips">
                        <h3><span class="dashicons dashicons-lightbulb"></span> Dicas importantes</h3>
                        <ul>
                            <li>O separador entre colunas é <strong>ponto e vírgula (;)</strong> — padrão do Excel em Português.</li>
                            <li>Se o <strong>e-mail ou login</strong> já existir, o usuário será <strong>atualizado</strong> (dados e senha).</li>
                            <li>Novos usuários são criados com o papel <strong>Assinante (subscriber)</strong>.</li>
                            <li>O campo <strong>unidade_usuario</strong> cria automaticamente o termo na taxonomia se não existir.</li>
                            <li>Salve o arquivo com codificação <strong>UTF-8</strong>.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Passo 3: Upload -->
            <div class="import-card">
                <div class="card-step">3</div>
                <div class="card-content">
                    <h2>Enviar o arquivo</h2>
                    <p>Selecione o CSV preenchido e clique em <strong>Importar</strong>.</p>

                    <form method="POST" enctype="multipart/form-data">
                        <?php wp_nonce_field( 'importar_usuarios_csv', '_wpnonce_importar_usuarios' ); ?>
                        <input type="hidden" name="usuarios_importar_csv" value="1">

                        <div class="import-file-area" id="drop-area-import">
                            <span class="dashicons dashicons-media-spreadsheet"></span>
                            <p>Arraste o arquivo CSV aqui ou clique para selecionar</p>
                            <input type="file" name="csv_usuarios" id="csv_usuarios" accept=".csv" required>
                            <span class="file-name" id="file-name-import"></span>
                        </div>

                        <button type="submit" class="button-import button-send">
                            <span class="dashicons dashicons-upload"></span> Importar Usuários
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- TAB 2: EXCLUIR POR CSV -->
        <!-- ============================================================ -->
        <div class="gu-tab-content <?php echo $active_tab === 'excluir-csv' ? 'active' : ''; ?>" id="tab-excluir-csv">

            <?php if ( $delete_result ) : ?>
                <?php
                    $cls  = $delete_result['excluidos'] > 0 ? 'result-success' : 'result-warning';
                    $icon = $delete_result['excluidos'] > 0 ? 'yes-alt' : 'warning';
                ?>
                <div class="import-result <?php echo $cls; ?>">
                    <div class="result-icon"><span class="dashicons dashicons-<?php echo $icon; ?>"></span></div>
                    <div class="result-body">
                        <strong><?php echo intval( $delete_result['excluidos'] ); ?> usuário(s) excluído(s).</strong>
                        <?php if ( ! empty( $delete_result['erros'] ) ) : ?>
                            <details class="result-errors">
                                <summary><?php echo count( $delete_result['erros'] ); ?> aviso(s) / erro(s)</summary>
                                <ul>
                                    <?php foreach ( $delete_result['erros'] as $e ) : ?>
                                        <li><?php echo esc_html( $e ); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </details>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Passo 1: Baixar modelo exclusão -->
            <div class="import-card">
                <div class="card-step step-red">1</div>
                <div class="card-content">
                    <h2>Baixar o modelo CSV de exclusão</h2>
                    <p>O modelo contém as colunas <strong>user_login</strong> e <strong>user_email</strong>. Preencha pelo menos uma delas para identificar o usuário.</p>
                    <a href="<?php echo esc_url( $modelo_delete_url ); ?>" class="button-import button-download">
                        <span class="dashicons dashicons-download"></span> Baixar Modelo CSV
                    </a>
                </div>
            </div>

            <!-- Passo 2: Referência -->
            <div class="import-card">
                <div class="card-step step-red">2</div>
                <div class="card-content">
                    <h2>Preencher o CSV</h2>
                    <p>Informe o <strong>login</strong> e/ou <strong>e-mail</strong> dos usuários que deseja excluir. A busca prioriza o login; se não encontrar, busca pelo e-mail.</p>

                    <table class="import-ref-table">
                        <thead>
                            <tr>
                                <th>Coluna</th>
                                <th>Obrigatório</th>
                                <th>Descrição</th>
                                <th>Exemplo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>user_login</code></td>
                                <td><span class="badge-cond">*</span></td>
                                <td>Login do usuário</td>
                                <td>joao.silva</td>
                            </tr>
                            <tr>
                                <td><code>user_email</code></td>
                                <td><span class="badge-cond">*</span></td>
                                <td>E-mail do usuário</td>
                                <td>joao.silva@empresa.com</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="import-tips">
                        <h3><span class="dashicons dashicons-lightbulb"></span> Dicas importantes</h3>
                        <ul>
                            <li><span class="badge-cond">*</span> Pelo menos <strong>uma</strong> das colunas (login ou e-mail) deve ser preenchida por linha.</li>
                            <li>Administradores e o usuário logado <strong>nunca serão excluídos</strong>.</li>
                            <li>A exclusão é <strong>permanente</strong> — não é possível desfazer.</li>
                            <li>Separador: <strong>ponto e vírgula (;)</strong>. Codificação: <strong>UTF-8</strong>.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Passo 3: Upload exclusão -->
            <div class="import-card">
                <div class="card-step step-red">3</div>
                <div class="card-content">
                    <h2>Enviar o arquivo</h2>
                    <p>Selecione o CSV e clique em <strong>Excluir Usuários</strong>. <strong>Atenção:</strong> esta ação é irreversível!</p>

                    <form method="POST" enctype="multipart/form-data" onsubmit="return confirm('Tem certeza que deseja excluir os usuários listados no CSV? Esta ação não pode ser desfeita.');">
                        <?php wp_nonce_field( 'excluir_usuarios_csv', '_wpnonce_excluir_usuarios' ); ?>
                        <input type="hidden" name="usuarios_excluir_csv" value="1">

                        <div class="import-file-area" id="drop-area-delete">
                            <span class="dashicons dashicons-media-spreadsheet"></span>
                            <p>Arraste o arquivo CSV aqui ou clique para selecionar</p>
                            <input type="file" name="csv_exclusao" id="csv_exclusao" accept=".csv" required>
                            <span class="file-name" id="file-name-delete"></span>
                        </div>

                        <button type="submit" class="button-import button-delete">
                            <span class="dashicons dashicons-trash"></span> Excluir Usuários
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- TAB 3: EXCLUIR POR FILTROS -->
        <!-- ============================================================ -->
        <div class="gu-tab-content <?php echo $active_tab === 'excluir-filtros' ? 'active' : ''; ?>" id="tab-excluir-filtros">

            <div class="import-card">
                <div class="card-step step-red">
                    <span class="dashicons dashicons-filter" style="font-size:22px;width:22px;height:22px;"></span>
                </div>
                <div class="card-content">
                    <h2>Excluir usuários por filtros</h2>
                    <p>Selecione os filtros desejados e clique em <strong>Buscar Usuários</strong> para visualizar quais usuários serão afetados antes de confirmar a exclusão.</p>

                    <div class="gu-filters">
                        <div class="gu-filter-group">
                            <label for="filter-date-from">Data de registro (de)</label>
                            <input type="date" id="filter-date-from">
                        </div>
                        <div class="gu-filter-group">
                            <label for="filter-date-to">Data de registro (até)</label>
                            <input type="date" id="filter-date-to">
                        </div>
                        <div class="gu-filter-group">
                            <label for="filter-unidade">Unidade</label>
                            <select id="filter-unidade">
                                <option value="">— Todas —</option>
                                <?php if ( ! is_wp_error( $unidades ) && ! empty( $unidades ) ) : ?>
                                    <?php foreach ( $unidades as $term ) : ?>
                                        <option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <button type="button" class="button-import button-preview" id="btn-preview">
                        <span class="dashicons dashicons-search"></span> Buscar Usuários
                    </button>

                    <div class="gu-preview-area" id="preview-area" style="display:none;"></div>
                </div>
            </div>
        </div>

    </div>

    <script>
    (function(){
        /* ===== TABS ===== */
        var tabs = document.querySelectorAll('.gu-tab-btn');
        var contents = document.querySelectorAll('.gu-tab-content');
        tabs.forEach(function(btn){
            btn.addEventListener('click', function(){
                tabs.forEach(function(b){ b.classList.remove('active'); });
                contents.forEach(function(c){ c.classList.remove('active'); });
                btn.classList.add('active');
                document.getElementById('tab-' + btn.getAttribute('data-tab')).classList.add('active');
                // Atualizar URL sem recarregar
                var url = new URL(window.location);
                url.searchParams.set('tab', btn.getAttribute('data-tab'));
                window.history.replaceState({}, '', url);
            });
        });

        /* ===== DRAG & DROP helpers ===== */
        function setupDropArea(areaId, inputId, labelId) {
            var area  = document.getElementById(areaId);
            var input = document.getElementById(inputId);
            var label = document.getElementById(labelId);
            if (!area || !input) return;

            area.addEventListener('click', function(e){ if(e.target !== input) input.click(); });
            input.addEventListener('change', function(){
                if(this.files.length > 0){ label.textContent = this.files[0].name; area.classList.add('has-file'); }
                else { label.textContent = ''; area.classList.remove('has-file'); }
            });
            ['dragenter','dragover'].forEach(function(ev){
                area.addEventListener(ev, function(e){ e.preventDefault(); e.stopPropagation(); area.classList.add('drag-over'); });
            });
            ['dragleave','drop'].forEach(function(ev){
                area.addEventListener(ev, function(e){ e.preventDefault(); e.stopPropagation(); area.classList.remove('drag-over'); });
            });
            area.addEventListener('drop', function(e){
                if(e.dataTransfer.files.length > 0){ input.files = e.dataTransfer.files; label.textContent = e.dataTransfer.files[0].name; area.classList.add('has-file'); }
            });
        }
        setupDropArea('drop-area-import', 'csv_usuarios', 'file-name-import');
        setupDropArea('drop-area-delete', 'csv_exclusao', 'file-name-delete');

        /* ===== PREVIEW / EXCLUIR POR FILTROS ===== */
        var btnPreview  = document.getElementById('btn-preview');
        var previewArea = document.getElementById('preview-area');
        var ajaxUrl     = '<?php echo esc_url( admin_url( "admin-ajax.php" ) ); ?>';
        var nonce       = '<?php echo wp_create_nonce( "usuarios_filtros_nonce" ); ?>';

        btnPreview.addEventListener('click', function(){
            var dateFrom = document.getElementById('filter-date-from').value;
            var dateTo   = document.getElementById('filter-date-to').value;
            var unidade  = document.getElementById('filter-unidade').value;

            if (!dateFrom && !dateTo && !unidade) {
                alert('Selecione pelo menos um filtro.'); return;
            }

            previewArea.style.display = 'block';
            previewArea.innerHTML = '<div class="gu-loading"><span class="spinner is-active"></span> Buscando usuários...</div>';

            var fd = new FormData();
            fd.append('action', 'usuarios_preview_exclusao');
            fd.append('nonce', nonce);
            fd.append('date_from', dateFrom);
            fd.append('date_to', dateTo);
            fd.append('unidade', unidade);

            fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function(r){ return r.json(); })
                .then(function(res){
                    if (!res.success) {
                        previewArea.innerHTML = '<p style="color:#dc2626;">' + (res.data || 'Erro desconhecido.') + '</p>';
                        return;
                    }
                    var data  = res.data;
                    var users = data.users;
                    if (users.length === 0) {
                        previewArea.innerHTML = '<p>Nenhum usuário encontrado com os filtros selecionados.</p>';
                        return;
                    }

                    var html = '<p class="gu-preview-count">' + data.total + ' usuário(s) encontrado(s)</p>';
                    html += '<table class="gu-preview-table"><thead><tr><th>ID</th><th>Login</th><th>E-mail</th><th>Nome</th><th>Registro</th><th>Unidade</th></tr></thead><tbody>';
                    users.forEach(function(u){
                        html += '<tr><td>' + u.ID + '</td><td>' + escHtml(u.user_login) + '</td><td>' + escHtml(u.user_email) + '</td><td>' + escHtml(u.display_name) + '</td><td>' + u.registered + '</td><td>' + escHtml(u.unidade) + '</td></tr>';
                    });
                    html += '</tbody></table>';

                    html += '<div class="gu-confirm-box">';
                    html += '<p><strong>Atenção:</strong> Deseja realmente excluir ' + data.total + ' usuário(s)? Esta ação é irreversível!</p>';
                    html += '<button type="button" class="button-import button-delete" id="btn-confirm-delete"><span class="dashicons dashicons-trash"></span> Confirmar Exclusão</button>';
                    html += '</div>';

                    previewArea.innerHTML = html;

                    // Bind confirm button
                    document.getElementById('btn-confirm-delete').addEventListener('click', function(){
                        if (!confirm('ÚLTIMA CONFIRMAÇÃO: Excluir permanentemente ' + data.total + ' usuário(s)?')) return;

                        var ids = users.map(function(u){ return u.ID; });
                        var fd2 = new FormData();
                        fd2.append('action', 'usuarios_executar_exclusao');
                        fd2.append('nonce', nonce);
                        ids.forEach(function(id){ fd2.append('user_ids[]', id); });

                        previewArea.innerHTML = '<div class="gu-loading"><span class="spinner is-active"></span> Excluindo usuários...</div>';

                        fetch(ajaxUrl, { method: 'POST', body: fd2, credentials: 'same-origin' })
                            .then(function(r){ return r.json(); })
                            .then(function(res2){
                                if (!res2.success) {
                                    previewArea.innerHTML = '<p style="color:#dc2626;">' + (res2.data || 'Erro ao excluir.') + '</p>';
                                    return;
                                }
                                var d = res2.data;
                                var msg = '<div class="import-result ' + (d.excluidos > 0 ? 'result-success' : 'result-warning') + '">';
                                msg += '<div class="result-icon"><span class="dashicons dashicons-' + (d.excluidos > 0 ? 'yes-alt' : 'warning') + '"></span></div>';
                                msg += '<div class="result-body"><strong>' + d.excluidos + ' usuário(s) excluído(s) com sucesso!</strong>';
                                if (d.erros && d.erros.length > 0) {
                                    msg += '<details class="result-errors"><summary>' + d.erros.length + ' aviso(s)</summary><ul>';
                                    d.erros.forEach(function(e){ msg += '<li>' + escHtml(e) + '</li>'; });
                                    msg += '</ul></details>';
                                }
                                msg += '</div></div>';
                                previewArea.innerHTML = msg;
                            });
                    });
                });
        });

        function escHtml(str) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str || ''));
            return div.innerHTML;
        }
    })();
    </script>
    <?php
}

?>