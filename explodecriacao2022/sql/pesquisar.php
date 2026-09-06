<?php
$grupo = $_REQUEST['G'];
// Achar as funções e pastas
    $path = preg_replace('/wp-content.*$/','',__DIR__);
    $path = preg_replace('/wp-content(?!.*wp-content).*/','',__DIR__);
    require_once($path.'wp-load.php');
if(empty($_POST['filtrar'])){
$args = array(
    "post_type" => 'desenhos',
    "posts_per_page" => -1,
    'orderby' => 'menu_order',
    'order' => 'ASC',
    'tax_query' => array(
  array(
     'taxonomy' => 'desenhos_cat',
     'field' => 'id',
     'terms' => $grupo
      )
 )
);
  $loop = new WP_Query( $args );  

  $dependentes_enviados = array();
?>



<?php
if ($loop->have_posts()) {
    while ( $loop->have_posts() ) {
    $loop->the_post();
    $pcd = false;

    $desenho = get_post_meta(get_the_ID(),'desenhos_box_desenho',true);
    // $desenho = str_replace('-150x150', '', $desenho);
    // $thumb_id = attachment_url_to_postid( $desenho );
    // $first_image_url = wp_get_attachment_image_src($thumb_id, 'desenho_thumb');
    // Pega a primeira imagem e utiliza a versão pequena
    // $desenho = $first_image_url[0];

    $categorias = get_the_terms(get_the_ID(), 'desenhos_cat');


    $post_id =  get_the_ID();
    $author_id = get_post_field ('post_author', $post_id);
    $display_name = get_the_author_meta( 'display_name' , $author_id ); 
// echo $display_name;

foreach($categorias as $info){
    if($info->slug == 'pcd') {
      $pcd = true;
    }
}

?>

    <div class="box_img">
        <figure>
            <h2>
                <?php the_title(); ?>
            </h2>
            <h3>
                <?php echo $display_name; ?>
            </h3>
            <span id="cat">
                <?php echo $categorias[0]->name; if($pcd == true) { echo ' (PCD)'; } ?>
            </span>
            <img class="thumb" src="<?php echo $desenho; ?>">
        </figure>
        <a href="<?php echo $desenho; ?>" download>
            Baixar
        </a>
    </div>


<?php

} 
?>

</div>

<?php
}
}
if(empty($_POST['limpar_pesquisa'])){

    $args = array(
        "post_type" => 'desenhos',
        "posts_per_page" => -1,
        'orderby' => 'menu_order',
        'order' => 'ASC',
        'tax_query' => array(
      array(
         'taxonomy' => 'desenhos_cat',
         'field' => 'id',
         'terms' => $grupo
          )
     )
    );
      $loop = new WP_Query( $args );  
    
      $dependentes_enviados = array();

?>   
<?php
}
?>


