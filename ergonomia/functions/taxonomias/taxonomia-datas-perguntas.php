<?php
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
?>