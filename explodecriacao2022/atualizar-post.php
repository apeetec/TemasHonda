<?php

/* Template Name: Atualizar categoria*/

get_header();



?>

<?php

// $args = array(
//     'role__in' => 'subscriber',
//     'number' => -1
//   );
//   $user_query = get_users( $args );

//   foreach($user_query as $user) {
//     $user_id = $user->ID;
//     $email = get_user_meta($user_id, "email", true);
//       $args = array(
//         'numberposts'      => -1,
//         'author'        => $user_id,
//         // 'include'          => array(),
//         // 'exclude'          => array(),
//         // 'meta_key'         => '',
//         // 'meta_value'       => '',
//         'post_type'        => 'desenhos',     
//         // 'suppress_filters' => true,
//     );
//     $posts = get_posts( $args);
//         foreach($posts as $post){
//         $post_id = $post->ID;
//         $terms = get_the_terms(get_the_ID(), 'desenhos_cat');
//     // Cria um array para armazenar os links dos termos
//     $term_links = array();

//     foreach ($terms as $term) {
//         $nome_termo = $term->name;
//         if($nome_termo == 'Categoria A' || $nome_termo == 'Categoria B' || $nome_termo == 'Categoria C'){
//             $term_id = $term->term_id;
//             // Adiciona o link do termo ao array
//             wp_set_post_terms($post_id,array($term_id),'desenhos_cat');
//         }
        
//     }
//         }
//   }








$args = array(
    'role__in' => 'subscriber',
    'number' => -1
  );
  $user_query = get_users( $args );

  foreach($user_query as $user) {
    $user_id = $user->ID;
    $args = array(
        'numberposts'      => -1,
        'author'        => $user_id,
        // 'include'          => array(),
        // 'exclude'          => array(),
        // 'meta_key'         => '',
        // 'meta_value'       => '',
        'post_type'        => 'desenhos',     
        // 'suppress_filters' => true,
    );
    $posts = get_posts( $args);
    $teste = [];
    foreach($posts as $post){
        $post_id = $post->ID;
        $terms = wp_get_post_terms($post_id, 'desenhos_cat');
        foreach ($terms as $term) {
            $id_term = $term->term_id; // Exibe o ID do termo
            $term_name = $term->name;
            $meta = get_user_meta($user_id,'user_field_funcionario_grupo',true);
            $taxonomy = get_term_by('name', $meta, 'desenhos_cat');
            $grupo_id = $taxonomy->term_id;
            $teste[] = $grupo_id.$id_term; 
        }
        echo implode(', ', $teste);
    }
}
?>                

<?php get_footer(); ?>