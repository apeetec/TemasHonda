<?php


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
?>