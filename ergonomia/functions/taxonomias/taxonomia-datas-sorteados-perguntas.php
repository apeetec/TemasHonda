<?php
// Criando datas para o sorteados
add_action( 'init', 'custom_taxonomy_sorteados', 0 );
function custom_taxonomy_sorteados() { 
$labels = array(
    'name' => 'Datas sorteados',
    'singular_name' => 'Datas sorteados',
    'search_items' => 'Buscar Data',
    'all_items' => 'Todas as Datas',
    'edit_item' => 'Editar Data', 
    'update_item' => 'Atualizar Data',
    'add_new_item' => 'Adicionar Data',
    'new_item_name' => 'Nova categoria',
    'menu_name' => 'Datas sorteados',
);    

register_taxonomy('datas_sorteados',array('perguntas'), array(
    'hierarchical' => true,
    'labels' => $labels,
    'show_ui' => true,
    'show_admin_column' => true,
    'query_var' => true,
    'rewrite' => array(
        'slug' => 'datas_sorteados', // This controls the base slug that will display before each term
        'with_front' => true, // Don't display the category base before "/locations/"
        'hierarchical' => true // This will allow URL's like "/locations/boston/cambridge/"
    ),
));
}

add_action( 'cmb2_admin_init', 'yourprefix_register_taxonomy_metabox_sorteados' );
?>