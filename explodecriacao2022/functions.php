<?php

/**
 * QUANTIDADE DE DEPENDENTES POR COLABORADOR
 *
 * Este e o unico lugar onde o numero de dependentes do tema e definido. Todos os
 * modulos (formulario de envio, importador de usuarios, corretor de categorias,
 * gerenciador de usuarios e regras dos grupos MAO) leem este valor, entao mudar o
 * numero aqui muda o sistema inteiro de uma vez, sem sobrar campo antigo em lugar
 * nenhum.
 *
 * Para alterar sem tocar no codigo, use o filtro:
 *     add_filter( 'explode_max_dependentes', function() { return 8; } );
 */
if ( ! defined( 'EXPLODE_MAX_DEPENDENTES' ) ) {
    // Numero de conjuntos de campos de dependente disponiveis por colaborador
    define( 'EXPLODE_MAX_DEPENDENTES', 6 );
}

/**
 * Devolve quantos dependentes o tema aceita por colaborador
 */
if ( ! function_exists( 'explode_max_dependentes' ) ) {
    function explode_max_dependentes() {
        // Permite ajustar o limite por filtro sem editar o tema
        $total = (int) apply_filters( 'explode_max_dependentes', EXPLODE_MAX_DEPENDENTES );
        // Nunca devolve menos de um slot, para nao quebrar os lasos que usam este valor
        return $total > 0 ? $total : 1;
    }
}

add_filter( 'wp_dropdown_users_args', 'add_subscribers_to_dropdown', 10, 2 );
function add_subscribers_to_dropdown( $query_args, $r ) {

    $query_args['who'] = '';
    return $query_args;

}

// Custom size
add_image_size( 'desenho_thumb', 250, 250, true );

// PAGINA 404
// O redirecionamento automatico de 404 para a home foi removido: ele fazia um link
// quebrado parecer bem-sucedido e escondia o problema. Alem disso o wp_redirect era
// chamado sem exit, entao o WordPress continuava renderizando a pagina depois de
// enviar o cabecalho Location. Agora o erro e exibido pelo template 404.php.
// FIM PAGINA 404

// BLOQUEIA LOGIN
// add_action('init','custom_login');
// function custom_login(){
//  global $pagenow;
//  if( 'wp-login.php' == $pagenow && !is_user_logged_in()) {
//   wp_redirect(home_url());
//   exit();
//  }
// }

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

add_action( 'send_headers', 'add_header_xframeoptions' );
function add_header_xframeoptions() {
header( 'X-Frame-Options: SAMEORIGIN' );
}

// ##################### SCRIPTS ##################### //
function custom_script() {

    // De-register the built in jQuery
    wp_deregister_script('jquery');
    // Register the CDN version
    wp_register_script('jquery', 'https://code.jquery.com/jquery-3.5.1.min.js', array(), null, false); 
    // Load new jquery
    wp_enqueue_script( 'jquery' );
    // Máscaras
    wp_enqueue_script('mask-scripts', get_template_directory_uri() .'/js/jquery.mask.js', array('jquery'), null, true); 
    // Extra scripts
    wp_enqueue_script('extra-scripts', get_template_directory_uri() .'/js/extra-scripts.js', array('jquery'), null, true);

}

add_action( 'wp_enqueue_scripts', 'custom_script' );
// ##################### END SCRIPTS ##################### //

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

// Remove Campos do usuario
if(is_admin()){
  remove_action("admin_color_scheme_picker", "admin_color_scheme_picker");
}

// Remove fields from Admin profile page

// Remove user fields

##################### Listagem de usuarios
add_action('admin_head', 'custom_admin_css');
function custom_admin_css() { ?>
<style>
    /* CMB2 */
    .cmb-row.cmb2-divisor {border-top: 1px solid #c6c6c6;margin-top: 20px;padding-top: 20px;}

    /* USER COLUMNS */
    body.users-php form table.users th#user_field_fotos {width: 100px;}
    body.users-php form table.users th#username {width: 145px;}
    body.users-php form table.users th#name {width: 175px;}
    body.users-php form table.users th#email {width: 205px;}
    body.users-php form table.users th#role {width: 100px;}
    body.users-php form table.users .row-actions .resetpassword, body.users-php form table.users .row-actions .capabilities {display: none;}
    body.users-php .tablenav.top #ure_grant_roles, body.users-php .tablenav.top #ure_add_role, body.users-php .tablenav.top #ure_add_role_submit, body.users-php .tablenav.top #ure_revoke_role, body.users-php .tablenav.top #ure_revoke_role_submit {display: none !important;}

    /*! USER FILEDS !*/
    form#your-profile tr.user-rich-editing-wrap {display: none;}
    form#your-profile tr.user-comment-shortcuts-wrap {display: none;}
    form#your-profile tr.user-language-wrap {display: none;}
    form#your-profile tr.user-nickname-wrap {display: none;}
    form#your-profile #application-passwords-section {display: none;}
</style>';
<?php }

#################################### CAMPOS DO USUARIO ####################################
add_action( 'cmb2_admin_init', 'custom_user_fields_cmb2' );
function custom_user_fields_cmb2() {

    $cmb_user = new_cmb2_box( array(
        'id'               => 'user_field_box',
        'title'            => 'Informações do colaborador',
        'object_types'     => array( 'user' ),
        'show_names'       => true,
        // 'new_user_section' => 'add-new-user', // where form will show on new user page. 'add-existing-user' is only other valid option.
    ) );

    /*$cmb_user->add_field( array(
    'name' => 'Curtidas pendentes',
    'id'   => 'user_field_colaborador_idade',
    'type' => 'text',
    'save_field'  => false,
    'attributes' => array(
        'type' => 'number',
        'pattern' => '\d*',
        'readonly' => 'readonly',
        'disabled' => 'disabled',
    ),
    'sanitization_cb' => 'absint',
        'escape_cb'       => 'absint',
    ) ); */

    $cmb_user->add_field( array(
    'name' => 'Opções de acesso',
    'id'   => 'user_field_title_acesso',
    'type' => 'title',
    'classes' => 'cmb2-divisor',
    ) );

    $cmb_user->add_field( array(
    'name'     => 'Unidade',
    'id'       => 'user_field_unidade',
    'type' => 'select',
    'show_option_none' => true,
    'options_cb' => 'user_taxonomy_unidades',
    // 'taxonomy' => 'user_unidade'
    /*'options' => array(
        'Opção #1' => 'Opção #1',
        'Opção #2' => 'Opção #2'
    )*/
    ) );

    $cmb_user->add_field( array(
    'name' => 'Empresa',
    'id'   => 'user_field_empresa_honda',
    'type' => 'select',
    'show_option_none' => true,
    'options' => array(
        'HAB' => 'HAB',
        'HSA' => 'HSA',
        'MAO' => 'MAO',
    ),
    ) );

      function user_taxonomy_unidades() {
        $terms = get_terms( 'user_unidade', array('hide_empty' => false) );

        $list_unidades = array();

        foreach($terms as $term) {
          $list_unidades[$term->term_id] = $term->name; // Pega o termo e utiliza o ID
          // $list_unidades[$term->$term_id] = $term->name;
        }

        return $list_unidades;

        print_r($list_unidades);


      }

    $cmb_user->add_field( array(
    'name' => 'Senha já foi alterada?',
    'id'   => 'user_field_senha_alterada',
    'type' => 'select',
    'default' => 'Não',
    'options' => array(
        'Não' => 'Não',
        'Sim' => 'Sim',
    ),
    ) );

    $cmb_user->add_field( array(
    'name' => 'Senha já foi alterada?',
    'id'   => 'user_field_senha_alterada',
    'type' => 'select',
    'default' => 'Não',
    'options' => array(
        'Não' => 'Não',
        'Sim' => 'Sim',
    ),
    ) );

    $cmb_user->add_field( array(
    'name' => 'Leu o regulamento?',
    'id'   => 'user_field_leitura_reg',
    'type' => 'select',
    'default' => 'Não',
    'options' => array(
        'Não' => 'Não',
        'Sim' => 'Sim',
    ),
    ) );

    $cmb_user->add_field( array(
    'name' => 'Homeoffice?',
    'id'   => 'user_field_homeoffice',
    'type' => 'select',
    'show_option_none' => true,
    'default' => '',
    'options' => array(
        'Não' => 'Não',
        'Sim' => 'Sim',
    ),
    'column' => true
    ) );

    $cmb_user->add_field( array(
    'name' => 'Já finalizou a votação?',
    'id'   => 'user_field_votacao',
    'type' => 'select',
    'default' => 'Não',
    'options' => array(
        'Não' => 'Não',
        'Sim' => 'Sim',
    ),
    ) );

    $cmb_user->add_field( array(
    'name' => 'Comissão?',
    'id'   => 'user_field_comissao',
    'type' => 'select',
    'default' => 'Não',
    'options' => array(
        'Não' => 'Não',
        'Sim' => 'Sim',
        'Pedagoga' => 'Pedagoga'
    ),
    'column' => true
    ) );

    $cmb_user->add_field( array(
    'name' => 'Responsável pelo grupo...',
    'id'   => 'user_field_comissao_grupo',
    'type' => 'select',
    'default' => '',
    'options' => array(
        'grupo-1' => 'Grupo 1',
        'grupo-2' => 'Grupo 2',
        'grupo-3' => 'Grupo 3',
    ),
    ) );

    $cmb_user->add_field( array(
        'name' => 'Grupo do funcionario',
        'id'   => 'user_field_funcionario_grupo',
        'type' => 'text',
    ) );

    ######### DEPENDENTES #########
    // Os conjuntos de campos sao gerados em laco a partir de explode_max_dependentes(),
    // definido no topo deste arquivo. Antes existiam quatro blocos identicos copiados a
    // mao, o que fazia qualquer mudanca de limite exigir edicao em varios pontos.
    for ( $dep_slot = 1; $dep_slot <= explode_max_dependentes(); $dep_slot++ ) {

        // Divisor que identifica o dependente no perfil do colaborador
        $cmb_user->add_field( array(
            'name'    => 'Informações do dependente #' . $dep_slot,
            'id'      => 'user_field_title_dependente_' . $dep_slot,
            'type'    => 'title',
            'classes' => 'cmb2-divisor',
        ) );

        // Nome do dependente
        $cmb_user->add_field( array(
            'name'   => 'Nome',
            'id'     => 'user_field_dependente_' . $dep_slot . '_nome',
            'type'   => 'text',
            'column' => true,
        ) );

        // Idade: preenchida pela importacao ou pelo cadastro dos grupos MAO
        $cmb_user->add_field( array(
            'name'       => 'Idade',
            'id'         => 'user_field_dependente_' . $dep_slot . '_idade',
            'type'       => 'text',
            'save_field' => false,
            'desc'       => 'Nos grupos MAO guarda a faixa escolhida pelo colaborador (ex.: 4 a 6).',
            'attributes' => array(
                'readonly' => 'readonly',
                'disabled' => 'disabled',
            ),
        ) );

        // Categorias no formato "<id da categoria>.<id do grupo>"
        $cmb_user->add_field( array(
            'name'   => 'Categorias',
            'id'     => 'user_field_dependente_' . $dep_slot . '_cat',
            'type'   => 'text',
            'desc'   => 'IDs das categorias separadas por vírgulas',
            'column' => true,
        ) );

        // Marcador de desenho ja enviado por este dependente
        $cmb_user->add_field( array(
            'name'             => 'Desenho enviado?',
            'id'               => 'user_field_dependente_' . $dep_slot . '_desenho',
            'type'             => 'select',
            'show_option_none' => true,
            'default'          => '',
            'options'          => array(
                'Sim' => 'Sim',
            ),
            'column'           => true,
        ) );
    }
    ######### FIM DOS DEPENDENTES #########

}
#################################### FIM CAMPOS DO USUARIO ####################################

#################################### POST TYPE DESENHOS ####################################
add_action('init', 'post_type_desenhos');
 
    function post_type_desenhos() { 

        $labels = array(
            'name' => 'Desenhos',
            'singular_name' => 'Desenho',
            'add_new' => 'Adicionar desenho',
            'add_new_item' => 'Novo desenho',
            'edit_item' => 'Editar desenho',
            'new_item' => 'Novo desenho',
            'view_item' => 'Ver desenho',
            'search_items' => 'Procurar desenhos',
            'not_found' =>  'Nenhum desenho encontrado',
            'not_found_in_trash' => 'Nenhum desenho encontrado na lixeira',
            'parent_item_colon' => '',
            'menu_name' => 'Desenhos',
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'menu_icon' => 'dashicons-clipboard',
            'public_queryable' => true,
            'show_ui' => true,           
            'query_var' => true,
            'rewrite' => array( 'slug' => false, 'with_front' => false ),// true,
            //'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,    
            'menu_position' => 3,   
            'supports' => array('title','author'),
          );
 
register_post_type( 'desenhos' , $args );
flush_rewrite_rules();
}


#################################### FIM POST TYPE DESENHOS ####################################

#################################### TAXONOMY CATEGORIA ####################################
add_action( 'init', 'custom_taxonomy_categorias', 0 );
 
function custom_taxonomy_categorias() {
 
  $labels = array(
    'name' => 'Categorias',
    'singular_name' => 'Categoria',
    'search_items' => 'Buscar categoria',
    'all_items' => 'Todas as categorias',
    'edit_item' => 'Editar categoria', 
    'update_item' => 'Atualizar categoria',
    'add_new_item' => 'Adicionar categoria',
    'new_item_name' => 'Nova categoria',
    'menu_name' => 'Categorias',
  );    
 
  register_taxonomy('desenhos_cat',array('desenhos'), array(
    'hierarchical' => true,
    'labels' => $labels,
    'show_ui' => true,
    'show_admin_column' => true,
    'query_var' => true,
    'rewrite' => array( 'with_front' => false ),
  ));
 
}
#################################### FIM TAXONOMY CATEGORIA ####################################

#################################### CAMPOS DESENHOS ####################################
add_action( 'cmb2_admin_init', 'custom_desenhos_fields' );
function custom_desenhos_fields() {

    $cmb = new_cmb2_box( array(
        'id'            => 'desenhos_box',
        'title'         => 'Informações importantes',
        'object_types'  => array('desenhos'), // Post type
        // 'show_on_cb' => 'yourprefix_show_if_front_page', // function should return a bool value
        // 'context'    => 'normal',
        // 'priority'   => 'high',
        // 'show_names' => true, // Show field names on the left
        // 'cmb_styles' => false, // false to disable the CMB stylesheet
        // 'closed'     => true, // true to keep the metabox closed by default
        // 'classes'    => 'extra-class', // Extra cmb2-wrap classes
        // 'classes_cb' => 'yourprefix_add_some_classes', // Add classes through a callback.

        /*
         * The following parameter is any additional arguments passed as $callback_args
         * to add_meta_box, if/when applicable.
         *
         * CMB2 does not use these arguments in the add_meta_box callback, however, these args
         * are parsed for certain special properties, like determining Gutenberg/block-editor
         * compatibility.
         *
         * Examples:
         *
         * - Make sure default editor is used as metabox is not compatible with block editor
         *      [ '__block_editor_compatible_meta_box' => false/true ]
         *
         * - Or declare this box exists for backwards compatibility
         *      [ '__back_compat_meta_box' => false ]
         *
         * More: https://wordpress.org/gutenberg/handbook/extensibility/meta-box/
         */
        // 'mb_callback_args' => array( '__block_editor_compatible_meta_box' => false ),
    ) );

    // Opcoes do seletor de dependente, geradas a partir do limite do tema
    $opcoes_dependente = array();
    // Uma opcao para cada slot disponivel
    for ( $dep_slot = 1; $dep_slot <= explode_max_dependentes(); $dep_slot++ ) {
        $opcoes_dependente[ (string) $dep_slot ] = 'Dependente #' . $dep_slot;
    }

    $cmb->add_field( array(
        'name' => 'Dependente',
        'id'   => 'desenhos_box_dependente',
        'type' => 'select',
        // 'show_option_none' => true,
        'options' => $opcoes_dependente,
        'column' => array(
            'position' => 2
        )
    ) );

    $cmb->add_field( array(
        'name' => 'Protocolo do desenho',
        'id'   => 'desenhos_box_protocolo',
        'type' => 'text',
    ) );

    $cmb->add_field( array(
        'name' => 'Unidade',
        'id'   => 'desenhos_box_unidade',
        'type' => 'select',
        'show_option_none' => true,
        'desc' => 'Alterar a unidade afetará apenas o desenho <br>e não irá influenciar na unidade do colaborador',
        'options_cb' => 'user_list_unidades',
		'column' => array(
			'position' => 4
		),
        'display_cb' => 'desenho_get_unidade'
    ) );

    function desenho_get_unidade($field_args, $field) {

        $id = $field->escaped_value();
        echo get_term( $id )->name;

    }

    function user_list_unidades() {

        $users_sugar = get_users();

        $user_list = array();
        foreach($users_sugar as $user) {
            $unidade = get_user_meta($user->ID,'user_field_unidade',true);
            $user_list[$unidade] = $unidade;
        }

        return $user_list;

    }

    /* $cmb->add_field( array(
        'name' => 'PCD?',
        'id'   => 'desenhos_box_pcd',
        'type' => 'select',
        'show_option_none' => true,
        'options' => array(
            'sim' => 'Sim',
            'nao' => 'Não'
        )
    ) ); */

    /* $cmb->add_field( array(
        'name' => 'Responsável',
        'id'   => 'desenhos_box_responsavel',
        'type' => 'select',
        'show_option_none' => true,
        'options_cb' => 'user_list_select'
    ) ); */

    function user_list_select() {

        $users_sugar = get_users();

        $user_list = array();
        foreach($users_sugar as $user) {
            $user_list[$user->ID] = $user->first_name . ' ('.$user->user_login.')';
        }

        return $user_list;

    }

    $cmb->add_field( array(
    'name' => 'Desenho',
    'id'   => 'desenhos_box_desenho',
    'type' => 'file',
    'options' => array(
        'url' => false, // Hide the text input for the url
    ),
    'query_args' => array(
        'type' => array(
            'image/jpeg',
            'image/png',
        ),
    ),
    'preview_size' => array( 300, 300 ), // Default: array( 50, 50 )
    'text'    => array(
        'add_upload_file_text' => 'Adicionar desenho' // Change upload button text. Default: "Add or Upload File"
    ),
    ) );

    $cmb->add_field( array(
    'name' => 'Votos',
    'id'   => 'desenhos_box_votos',
    'type' => 'text',
    'save_field'  => false,
    'attributes' => array(
        'type' => 'number',
        'pattern' => '\d*',
        'readonly' => 'readonly',
        'disabled' => 'disabled',
    ),
    'sanitization_cb' => 'absint',
    'escape_cb'       => 'absint',
    'column' => array(
        'position' => 2
    )
    ) );

    $cmb->add_field( array(
    'name' => 'Votos comissao',
    'id'   => 'desenhos_box_votos_comissao',
    'type' => 'text',
    'save_field'  => false,
    'attributes' => array(
        'type' => 'number',
        'pattern' => '\d*',
        'readonly' => 'readonly',
        'disabled' => 'disabled',
    ),
    'sanitization_cb' => 'absint',
    'escape_cb'       => 'absint',
    'column' => array(
        'position' => 3
    )
    ) );

    $cmb->add_field( array(
    'name' => 'Votos pedagoga',
    'id'   => 'desenhos_box_votos_pedagoga',
    'type' => 'text',
    //'save_field'  => false,
    'attributes' => array(
        'type' => 'number',
        'pattern' => '\d*',
        //'readonly' => 'readonly',
        //'disabled' => 'disabled',
    ),
    'sanitization_cb' => 'absint',
    'escape_cb'       => 'absint',
    'column' => array(
        'position' => 4
    )
    ) );
	
	$cmb->add_field( array(
	'name' => 'Esta criança é PCD',
	'desc' => 'Caso a criança seja PCD, este campo estará marcado',
	'id'   => 'desenhos_box_pcd',
	'type' => 'checkbox',
) );

}

########## EXPORTAR DESENHOS ###########
function adicionar_botao_exportar_desenhos() {
    global $current_screen;
    if ( isset($current_screen->post_type) && $current_screen->post_type === 'desenhos' ) {
        $url = wp_nonce_url(
            admin_url('admin-ajax.php?action=exportar_desenhos_para_excel'),
            'exportar_desenhos_para_excel_nonce',
            '_exp_nonce'
        );
        echo '<a href="' . esc_url($url) . '" class="button button-primary">Exportar Desenhos para Excel</a>';
    }
}
add_action('restrict_manage_posts', 'adicionar_botao_exportar_desenhos');

function exportar_desenhos_para_excel_ajax() {

    // Permissão + nonce
    if ( ! current_user_can('edit_posts') ) {
        wp_die('Sem permissão.');
    }
    if ( empty($_GET['_exp_nonce']) || ! wp_verify_nonce($_GET['_exp_nonce'], 'exportar_desenhos_para_excel_nonce') ) {
        wp_die('Nonce inválido.');
    }

    @set_time_limit(0);
    @ini_set('memory_limit', '512M');

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=desenhos.csv');

    // BOM para Excel PT-BR
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');
    $delimiter = ';';

    // Cabeçalho (ajuste a ordem como preferir)
    fputcsv($out, array(
        'ID',
        'Título',
        'Autor',
        'Dependente',
        'Protocolo do desenho',
        'Unidade',
        'Desenho (URL)',
        'Votos',
        'Votos comissão',
        'Votos pedagoga',
        'PCD',
        'Categorias',
        'Data',
        'Status',
        'Slug',
        'Link'
    ), $delimiter);

    // Busca só IDs para performance
    $post_ids = get_posts(array(
        'post_type'      => 'desenhos',
        'post_status'    => array('publish','draft','pending','future','private'),
        'posts_per_page' => -1,
        'orderby'        => 'ID',
        'order'          => 'ASC',
        'fields'         => 'ids',
    ));

    if (empty($post_ids)) {
        fclose($out);
        exit;
    }

    // Mapa do select "Dependente", montado a partir do limite do tema
    $dependente_map = array();
    // Uma entrada para cada slot disponivel
    for ( $dep_slot = 1; $dep_slot <= explode_max_dependentes(); $dep_slot++ ) {
        $dependente_map[ (string) $dep_slot ] = 'Dependente #' . $dep_slot;
    }

    foreach ($post_ids as $pid) {

        $post = get_post($pid);

        // Core
        $titulo     = get_the_title($pid);
        $autor_id   = $post->post_author;
        $autor_nome = $autor_id ? get_the_author_meta('display_name', $autor_id) : '';
        $data       = get_the_date('Y-m-d H:i:s', $pid);
        $status     = $post->post_status;
        $slug       = $post->post_name;
        $link       = get_permalink($pid);

        // ===== Metas CMB2 =====
        $dependente_val = get_post_meta($pid, 'desenhos_box_dependente', true);
        $dependente     = isset($dependente_map[$dependente_val]) ? $dependente_map[$dependente_val] : $dependente_val;

        $protocolo      = get_post_meta($pid, 'desenhos_box_protocolo', true);

        // Unidade: seu display_cb usa get_term($id)->name, então tentamos resolver o nome do termo.
        $unidade_id     = get_post_meta($pid, 'desenhos_box_unidade', true);
        $unidade_nome   = '';
        if (!empty($unidade_id)) {
            $term = get_term($unidade_id);
            $unidade_nome = ( $term && !is_wp_error($term) ) ? $term->name : $unidade_id; // fallback: valor cru
        }

        // Desenho (file): com 'url'=>false o CMB2 salva o ID do anexo. Garantimos também suporte a array/url.
        $desenho_meta   = get_post_meta($pid, 'desenhos_box_desenho', true);
        $desenho_url    = '';
        if (is_numeric($desenho_meta)) {
            $desenho_url = wp_get_attachment_url(intval($desenho_meta));
        } elseif (is_array($desenho_meta)) {
            // Alguns setups salvam array ['id'=>..,'url'=>..]
            if (!empty($desenho_meta['url'])) {
                $desenho_url = $desenho_meta['url'];
            } elseif (!empty($desenho_meta['id'])) {
                $desenho_url = wp_get_attachment_url(intval($desenho_meta['id']));
            }
        } elseif (is_string($desenho_meta) && filter_var($desenho_meta, FILTER_VALIDATE_URL)) {
            $desenho_url = $desenho_meta;
        }

        // Votos / Votos comissão: no seu CMB2 esses campos têm 'save_field'=>false,
        // então normalmente NÃO ficam no banco. Tentamos pegar; se não houver, ficam vazios.
        $votos             = get_post_meta($pid, 'desenhos_box_votos', true);
        $votos_comissao    = get_post_meta($pid, 'desenhos_box_votos_comissao', true);

        // Votos pedagoga: este é salvo
        $votos_pedagoga    = get_post_meta($pid, 'desenhos_box_votos_pedagoga', true);

        // PCD (checkbox): retorna 'on' (CMB2) / '1' / '' dependendo do setup
        $pcd_raw = get_post_meta($pid, 'desenhos_box_pcd', true);
        $pcd     = (!empty($pcd_raw) && $pcd_raw !== '0') ? 'Sim' : 'Não';

        // Taxonomia 'desenhos_cat'
        $cats = get_the_terms($pid, 'desenhos_cat');
        $categorias = ( !is_wp_error($cats) && !empty($cats) )
            ? implode(', ', wp_list_pluck($cats, 'name'))
            : '';

        // Linha
        fputcsv($out, array(
            $pid,
            $titulo,
            $autor_nome,
            $dependente,
            $protocolo,
            $unidade_nome,
            $desenho_url,
            $votos,
            $votos_comissao,
            $votos_pedagoga,
            $pcd,
            $categorias,
            $data,
            $status,
            $slug,
            $link,
        ), $delimiter);
    }

    fclose($out);
    exit;
}
add_action('wp_ajax_exportar_desenhos_para_excel', 'exportar_desenhos_para_excel_ajax');

#################################### FIM CAMPOS DESENHOS ####################################

// REWRITE URL FILTRO
function wpd_query_vars( $query_vars ){
    $query_vars[] = 'unidade';
    $query_vars[] = 'categoria';
    return $query_vars;
}
add_filter( 'query_vars', 'wpd_query_vars' );

add_rewrite_rule('^filtros/([^/]*)/?$','index.php?page_id=100&unidade=$matches[1]','top');
add_rewrite_rule('^filtros/([^/]*)/([^/]*)/?$','index.php?page_id=100&unidade=$matches[1]&categoria=$matches[2]','top');

#################################### CMB2 - OPCOES GERAIS ####################################
add_action( 'cmb2_admin_init', 'yourprefix_register_theme_options_metabox' );
function yourprefix_register_theme_options_metabox() {

    /**
     * Registers options page menu item and form.
     */
    $cmb_options = new_cmb2_box( array(
        'id'           => 'opcoes_gerais_box',
        'title'        => 'Opções gerais',
        'object_types' => array( 'options-page' ),
        'option_key'      => 'opcoes_gerais_box', // The option key and admin menu page slug.
        'icon_url'        => 'dashicons-buddicons-buddypress-logo', // Menu icon. Only applicable if 'parent_slug' is left empty.
        'position'        => 2, // Menu position. Only applicable if 'parent_slug' is left empty.
    ) );

    // $cmb_options->add_field( array(
    //     'name'    => 'Fase atual',
    //     'id'      => 'opcoes_gerais_fase',
    //     'type'    => 'select',
    //     'options' => array(
    //         '1' => 'Fase #1 - Envio de desenhos',
    //         '2' => 'Fase #2- Votação',
    //         '3' => 'Fase #3 - Resultados',
    //         '4' => 'Aviso - Aviso',
    //     )
    // ) );
	
	  $cmb_options->add_field( array(
        'name'    => 'DESATIVADO - Fase atual da unidade SUM',
        'desc'    => 'Este campo não é mais lido pelo site. A etapa passou a ser definida por empresa em <strong>Empresas</strong>, no menu lateral.',
        'id'      => 'opcoes_gerais_fase_sum',
        'type'    => 'select',
        'options' => array(
            '1' => 'Fase #1 - Envio de desenhos',
            '2' => 'Fase #2- Votação',
            '3' => 'Fase #3 - Resultados',
            '4' => 'Aviso - Aviso',
        )
    ) );
    $cmb_options->add_field( array(
        'name'    => 'DESATIVADO - Fase atual da unidade SAO',
        'desc'    => 'Este campo não é mais lido pelo site. A etapa passou a ser definida por empresa em <strong>Empresas</strong>, no menu lateral.',
        'id'      => 'opcoes_gerais_fase_sao',
        'type'    => 'select',
        'options' => array(
            '1' => 'Fase #1 - Envio de desenhos',
            '2' => 'Fase #2- Votação',
            '3' => 'Fase #3 - Resultados',
            '4' => 'Aviso - Aviso',
        )
    ) );
    $cmb_options->add_field( array(
        'name'    => 'DESATIVADO - Fase atual da unidade MAO',
        'desc'    => 'Este campo não é mais lido pelo site. A etapa passou a ser definida por empresa em <strong>Empresas</strong>, no menu lateral.',
        'id'      => 'opcoes_gerais_fase_mao',
        'type'    => 'select',
        'options' => array(
            '1' => 'Fase #1 - Envio de desenhos',
            '2' => 'Fase #2- Votação',
            '3' => 'Fase #3 - Resultados',
            '4' => 'Aviso - Aviso',
        )
    ) );

    /* -----------------------------------------------------------------
     * REGRAS DOS GRUPOS MAO
     *
     * Estas tres regras existiam fixas no codigo e agora sao ligadas e
     * desligadas aqui, sem precisar de programador. Todas comecam em "Nao",
     * que e o comportamento pedido hoje; basta trocar para "Sim" quando a
     * empresa quiser a exigencia de volta.
     *
     * Quem le estes campos sao as funcoes explode_mao_cadastro_dependentes_ativo(),
     * explode_mao_exige_laudo() e explode_mao_exige_termo(), em inc/regras-mao.php.
     * ----------------------------------------------------------------- */

    $cmb_options->add_field( array(
        'name' => 'Regras dos grupos MAO (Manaus)',
        'desc' => 'Valem apenas para os colaboradores dos grupos MAO. Nenhum outro grupo é afetado.',
        'id'   => 'opcoes_mao_titulo_regras',
        'type' => 'title',
    ) );

    $cmb_options->add_field( array(
        'name'             => 'Colaborador MAO cadastra os próprios dependentes?',
        'desc'             => 'Quando <strong>Sim</strong>, o colaborador dos grupos MAO vê o bloco "Meus dependentes" na tela de envio e pode cadastrar nome e faixa de idade (a categoria é deduzida da idade). Quando <strong>Não</strong>, o bloco não aparece e os dependentes vêm apenas da importação de usuários.',
        'id'               => 'opcoes_mao_cadastro_dependentes',
        'type'             => 'select',
        'default'          => 'nao',
        'options'          => array(
            'nao' => 'Não - o bloco de cadastro fica oculto',
            'sim' => 'Sim - o colaborador cadastra os dependentes',
        ),
    ) );

    $cmb_options->add_field( array(
        'name'             => 'Exigir laudo para dependente PCD?',
        'desc'             => 'Quando <strong>Sim</strong>, o colaborador MAO que marcar o dependente como PCD só consegue enviar o desenho depois de anexar o laudo. Quando <strong>Não</strong>, basta marcar PCD e enviar; o campo de laudo nem é exibido.',
        'id'               => 'opcoes_mao_exigir_laudo',
        'type'             => 'select',
        'default'          => 'nao',
        'options'          => array(
            'nao' => 'Não - o laudo não é pedido',
            'sim' => 'Sim - o laudo é obrigatório para PCD',
        ),
    ) );

    $cmb_options->add_field( array(
        'name'             => 'Exigir aceite do Termo de Uso de Imagem e Voz para PCD?',
        'desc'             => 'Quando <strong>Sim</strong>, o colaborador MAO com dependente PCD precisa marcar o aceite do termo antes de enviar o desenho. Quando <strong>Não</strong>, o aceite não é pedido.',
        'id'               => 'opcoes_mao_exigir_termo',
        'type'             => 'select',
        'default'          => 'nao',
        'options'          => array(
            'nao' => 'Não - o aceite não é pedido',
            'sim' => 'Sim - o aceite do termo é obrigatório para PCD',
        ),
    ) );

}
#################################### CMB2 - FIM OPCOES GERAIS ####################################

// BLOCK DASHBOARD NON ADMINS
function ace_block_wp_admin() {
    if ( is_admin() && ! current_user_can( 'administrator' ) && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
        wp_safe_redirect( home_url() );
        exit;
    }
}
add_action( 'admin_init', 'ace_block_wp_admin' );

///////////////////// USER TAXONOMY - UNIDADE
// Tutorial user taxonomy
// https://codebriefly.com/how-to-create-taxonomy-for-users-in-wordpress/

// Register Custom Taxonomy
function register_user_taxonomy_unidades() {

  $labels = array(
    'name'                       => 'Unidade',
    'singular_name'              => 'Unidade',
    'menu_name'                  => 'Unidades',
    'all_items'                  => 'Todas unidades',
    'parent_item'                => 'Unidade pai',
    'parent_item_colon'          => 'Item pai',
    'new_item_name'              => 'Nova unidade',
    'add_new_item'               => 'Adicionar unidade',
    'edit_item'                  => 'Editar unidade',
    'update_item'                => 'Atualizar unidade',
    'view_item'                  => 'Ver unidade',
    'separate_items_with_commas' => 'Separar itens com vírgula',
    'add_or_remove_items'        => 'Adicionar ou remover unidades',
    'choose_from_most_used'      => 'Escolher as mais usadas',
    'popular_items'              => 'Unidades populares',
    'search_items'               => 'Buscar unidades',
    'not_found'                  => 'Não encontrado',
    'no_terms'                   => 'Não encontrado',
    'items_list'                 => 'Lista de unidades',
    'items_list_navigation'      => 'Lista de unidades',
  );
  $args = array(
    'labels'                     => $labels,
    'hierarchical'               => false,
    'public'                     => false,
    'show_ui'                    => true,
    'show_admin_column'          => false,
    'show_in_nav_menus'          => true,
    'show_tagcloud'              => true,
    'capabilities' => array(
          //'manage_terms' => 'manage_unidade',
          //'edit_terms' => 'edit_unidade',
          //'delete_terms' => 'delete_unidade',
          //'assign_terms' => 'assign_unidade',
      )
  );
  register_taxonomy( 'user_unidade', array( 'user' ), $args );

}
add_action( 'init', 'register_user_taxonomy_unidades', 0 );

##################### Adiciona a página para acrescentar/editar taxonomias
function cb_add_user_unidade_taxonomy_admin_page() {
  $tax = get_taxonomy( 'user_unidade' );
  add_users_page(
    esc_attr( $tax->labels->menu_name ),
    esc_attr( $tax->labels->menu_name ),
    $tax->cap->manage_terms,
    'edit-tags.php?taxonomy=' . $tax->name
  );
}
add_action( 'admin_menu', 'cb_add_user_unidade_taxonomy_admin_page' );

//////////// Remove a coluna posts e adiciona a usuários
function cb_manage_user_unidade_user_column( $columns ) {

  unset( $columns['posts'] );

  // $columns['users'] = __( 'Usuários' );

  return $columns;
}
add_filter( 'manage_edit-user_unidade_columns', 'cb_manage_user_unidade_user_column' );

//////////// Atualiza a contagem de membros naquela unidade
function cb_manage_user_unidade_column( $display, $column, $term_id ) {

  if ( 'users' === $column ) {
    $term = get_term( $term_id, 'user_unidade' );
    echo $term->count;
  }
}
add_filter( 'manage_user_unidade_custom_column', 'cb_manage_user_unidade_column', 10, 3 );
##################### END USER TAXONOMY

##################### EMAIL NOT MANDATORY
add_action('user_profile_update_errors', 'my_user_profile_update_errors', 10, 3 );
function my_user_profile_update_errors($errors, $update, $user) {
    $errors->remove('empty_email');
}

// This will remove javascript required validation for email input
// It will also remove the '(required)' text in the label
// Works for new user, user profile and edit user forms
add_action('user_new_form', 'my_user_new_form', 10, 1);
add_action('show_user_profile', 'my_user_new_form', 10, 1);
add_action('edit_user_profile', 'my_user_new_form', 10, 1);
function my_user_new_form($form_type) {
    ?>
    <script type="text/javascript">
        jQuery('#email').closest('tr').removeClass('form-required').find('.description').remove();
        // Uncheck send new user email option by default
        <?php if (isset($form_type) && $form_type === 'add-new-user') : ?>
            jQuery('#send_user_notification').removeAttr('checked');
        <?php endif; ?>
    </script>
    <?php
}
##################### EMAIL NOT MANDATORY

/////////////////// CUSTOM ADMIN LOGIN ///////////////////
add_action( 'login_enqueue_scripts', 'customize_admin_login' );
function customize_admin_login() { ?>

    <style>
        body{background-image: linear-gradient(#5b9b99, #dffdc2)!important;}
        .login h1 a {
            background-image: url(https://www.explodecriacao.com.br/wp-content/themes/explodecriacao2022/img/logo-explode.png)!important;
            background-image: none,url(https://www.explodecriacao.com.br/wp-content/themes/explodecriacao2022/img/logo-explode.png)!important;
            background-size: contain!important;width: 315px!important;}
        .login #loginform {background: #143240!important;color:#fff!important;}
        .login form {border-radius: 11px!important;padding: 20px!important;}

        .wp-core-ui .button-primary,.wp-core-ui .button, .wp-core-ui .button-secondary {background-image: linear-gradient(#5b9b99, #dffdc2)!important;border-color: #54c5cf!important;color: #fff!important;text-transform: uppercase!important;}
        .wp-core-ui .button-primary:hover,.wp-core-ui .button:hover, .wp-core-ui .button-secondary:hover{background-color: #54c5cf!important;border-color: #54c5cf!important}
        .login #backtoblog a, .login #nav a {color: #fff!important;}
        .login #login_error, .login .message, .login .success {border-left: 4px solid #54c5cf!important;background-color: #f0f0f1!important;border-radius: 11px;padding: 20px;}
    </style>
<?php }
/////////////////// END CUSTOM ADMIN LOGIN ///////////////////

/////////////////// GESTÃO E EXCLUSÃO DE USUÁRIOS (WP-ADMIN) ///////////////////
require_once get_template_directory() . '/inc/admin-gerenciador-usuarios.php';


/////////////////// IMPORTACAO INTELIGENTE DE USUARIOS VIA CSV (WP-ADMIN) ///////////////////
require_once get_template_directory() . '/inc/admin-importador-usuarios.php';

/////////////////// CORRETOR DE CATEGORIAS DOS DEPENDENTES (WP-ADMIN) ///////////////////
require_once get_template_directory() . '/inc/admin-corretor-categorias.php';

/////////////////// REGRAS DOS GRUPOS MAO (LAUDO PCD + CATEGORIA A SEM VOTO) ///////////////////
require_once get_template_directory() . '/inc/regras-mao.php';

/////////////////// EMPRESAS E ETAPAS (ROTEAMENTO CONFIGURAVEL) ///////////////////
require_once get_template_directory() . '/inc/empresas.php';

/////////////////// VISIBILIDADE DOS DESENHOS POR GRUPO ///////////////////
// Precisa vir depois de empresas.php, porque reaproveita a resolucao de grupos dele
require_once get_template_directory() . '/inc/visibilidade-grupos.php';


/**
 * Página em Usuários > Marcar senha alterada
 * Atualiza em massa o meta "user_field_senha_alterada" (usado pelo CMB2).
 *
 * Cole este bloco no final do functions.php (sem repetir a tag <?php).
 */
 
/////////////////// MARCAR "SENHA JÁ ALTERADA" EM MASSA ///////////////////
 
add_action( 'admin_menu', 'ec_menu_senha_alterada_massa' );
function ec_menu_senha_alterada_massa() {
    add_users_page(
        'Marcar senha alterada',   // título da página
        'Marcar senha alterada',   // título no menu
        'manage_options',          // só administradores
        'senha-alterada-massa',    // slug
        'ec_pagina_senha_alterada_massa'
    );
}
 
function ec_pagina_senha_alterada_massa() {
 
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Você não tem permissão para acessar esta página.' );
    }
 
    $meta_key = 'user_field_senha_alterada';
    $aviso    = '';
 
    // ---------- Processa o envio do formulário ----------
    if ( isset( $_POST['ec_senha_alterada_nonce'] )
        && wp_verify_nonce( $_POST['ec_senha_alterada_nonce'], 'ec_senha_alterada_massa' ) ) {
 
        $valor = ( isset( $_POST['ec_valor'] ) && $_POST['ec_valor'] === 'nao' ) ? 'Não' : 'Sim';
        $somente_vazios = ! empty( $_POST['ec_somente_vazios'] );
 
        $ids = get_users( array( 'fields' => 'ID' ) );
 
        $total = 0;
        foreach ( $ids as $user_id ) {
 
            if ( $somente_vazios ) {
                $atual = get_user_meta( $user_id, $meta_key, true );
                if ( $atual !== '' ) {
                    continue; // já tem valor gravado, não mexe
                }
            }
 
            update_user_meta( $user_id, $meta_key, $valor );
            $total++;
        }
 
        $aviso = sprintf(
            '%d usuário(s) atualizado(s) para "%s".',
            $total,
            $valor
        );
    }
 
    // ---------- Contagem atual, só para conferência ----------
    $total_usuarios = count( get_users( array( 'fields' => 'ID' ) ) );
 
    $com_sim = count( get_users( array(
        'fields'     => 'ID',
        'meta_key'   => $meta_key,
        'meta_value' => 'Sim',
    ) ) );
    ?>
 
    <div class="wrap">
        <h1>Marcar senha alterada</h1>
 
        <?php if ( $aviso ) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html( $aviso ); ?></p>
            </div>
        <?php endif; ?>
 
        <p>
            Total de usuários: <strong><?php echo (int) $total_usuarios; ?></strong><br>
            Marcados como <em>Sim</em> hoje: <strong><?php echo (int) $com_sim; ?></strong>
        </p>
 
        <form method="post">
            <?php wp_nonce_field( 'ec_senha_alterada_massa', 'ec_senha_alterada_nonce' ); ?>
 
            <table class="form-table">
                <tr>
                    <th scope="row">Definir o campo como</th>
                    <td>
                        <label><input type="radio" name="ec_valor" value="sim" checked> Sim</label><br>
                        <label><input type="radio" name="ec_valor" value="nao"> Não</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Alcance</th>
                    <td>
                        <label>
                            <input type="checkbox" name="ec_somente_vazios" value="1">
                            Aplicar apenas em quem ainda não tem valor gravado
                        </label>
                        <p class="description">
                            Se deixar desmarcado, o valor será sobrescrito para <strong>todos</strong> os usuários.
                        </p>
                    </td>
                </tr>
            </table>
 
            <?php submit_button(
                'Aplicar a todos os usuários',
                'primary',
                'submit',
                true,
                array( 'onclick' => "return confirm('Confirma alterar o campo de todos os usuários?');" )
            ); ?>
        </form>
    </div>
    <?php
}


