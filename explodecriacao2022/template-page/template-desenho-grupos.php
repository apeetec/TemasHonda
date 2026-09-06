<?php

/* Template Name: Todos os desenhos */

get_header();
if($_POST){
  $grupo = $_POST['grupos'];
  $cat_criancas = $_POST['categorias-criancas'];
  
  $term_selected_grupo = get_term_by('slug', $grupo, 'desenhos_cat');
  $term_selected_cat = get_term_by('slug', $cat_criancas, 'desenhos_cat');
  $term_name_grupo = $term_selected_grupo->name;
  $term_name_cat = $term_selected_cat->name;
  $menu =  $term_name_grupo.' - '.$term_name_cat;
  $args = array(
    "post_type" => 'desenhos',
    "posts_per_page" => -1,
    'orderby' => 'menu_order',
    'order' => 'ASC',
    'tax_query' => array(
        'relation' => 'AND', // Esta linha define a relação "OU" entre as taxonomias
        array(
            'taxonomy' => 'desenhos_cat',
            'field' => 'slug',
            'terms' => $grupo
        ),
        array(
            'taxonomy' => 'desenhos_cat',
            'field' => 'slug',
            'terms' => $cat_criancas
        )
        // Adicione mais arrays de taxonomias aqui, se necessário
    )
);
}
else{
  $args = array(
    "post_type" => 'desenhos',
    "posts_per_page" => -1,
    'orderby' => 'menu_order',
    'order' => 'ASC',
);
}
?>

  <div class="main">
    <div class="content">

 
    <?php
          ############### LOOP DOS DESENHOS DOS FILHOS ###############
        //   $args = array(
        //       "post_type" => 'desenhos',
        //       "posts_per_page" => -1,
              
        //   );


          $loop = new WP_Query( $args );  

          $dependentes_enviados = array();

          if ($loop->have_posts()) { ?>

             <div class="container">
                <h2>Desenhos mais votados</h2>
                <span>Desenhos exibidos: <strong><?php echo $menu;?></strong></span>
             </div>
             <div class="container">
               <div class="display-flex active">
                <form action="" method="POST" name="filtrar" class="filtro_adm">
                  <select name="grupos" id="grupos">    
                    <option value="">Grupo</option>         
                      <?php
                        $args2 = array(
                        'taxonomy'   => "desenhos_cat",
                        'orderby'    => 'title',
                        'order'      => 'ASC',
                        'exclude' => array(2,3,4) 
                      );
                    
                        $cats = get_terms($args2);         
                        foreach( $cats as $cat ) {
                      ?>
                  <option value="<?php echo $cat->slug;?>"><?php echo $cat->name;?></option>
                <?php
                }
                ?>
                    </select>
                    <select name="categorias-criancas" id="categorias-criancas">    
                    <option value="">Categoria</option>         
                      <?php
                        $args2 = array(
                        'taxonomy'   => "desenhos_cat",
                        'orderby'    => 'title',
                        'order'      => 'ASC',
                        'exclude' => array(32,33,34,37,38,39,40,41,42,36) 
                      );
                    
                        $cats = get_terms($args2);         
                        foreach( $cats as $cat ) {
                      ?>
                  <option value="<?php echo $cat->slug;?>"><?php echo $cat->name;?></option>
                <?php
                }
                ?>
                    </select>
                    <input type="submit" value="Mudar">
                  </form>
                <a class="download" onclick="generateZIP()">
                    Baixe todos
                </a>
                <button class="selectAll" id="selectAllButton">Selecionar todos</button>
                <button class="deselectAll" id="deselectAllButton">Remover seleção</button>
               </div>
             </div>
          <?php
          }
          ############### LOOP DOS DESENHOS DOS FILHOS ###############
        ?>   
      </div>
      <div class="container">
        <div class="all loop-drawings">
        <?php
         $top10Posts = array(); // Matriz para armazenar os 10 melhores posts

         if ($loop->have_posts()) {
             while ($loop->have_posts()) {
                 $loop->the_post();
                 $votos = get_post_meta(get_the_ID(), 'desenhos_box_votos', true);
                 $desenho = get_post_meta(get_the_ID(),'desenhos_box_desenho',true);
                 $author_id = get_post_field ('post_author', get_the_ID());
                 $display_name = get_the_author_meta( 'display_name' , $author_id ); 
                 // Ancora
                 $desenho_anchor = get_post_meta(get_the_ID(),'desenhos_box_desenho',true);
                 $desenho_anchor = str_replace('-150x150', '', $desenho);
                 $first_image_url_anchor = wp_get_attachment_image_src($thumb_id, 'medium'); 
     
                 if (!empty($votos)) {
                     // Adicione o post à matriz $top10Posts com base no número de votos
                     $top10Posts[] = array(
                         'post' => get_post(),
                         'votos' => $votos,
                         'desenhos' => $desenho,
                         'link' => $desenho_anchor,
                          'pai' => $display_name
                     );
         
                     // Classifique a matriz em ordem decrescente com base no número de votos
                     usort($top10Posts, function ($a, $b) {
                         return $b['votos'] - $a['votos'];
                     });
         
                     // Mantenha apenas os 10 melhores posts
                     if (count($top10Posts) > 10) {
                         array_pop($top10Posts);
                     }
                 }
             }
         
             // Exiba os 10 melhores posts com título e quantidade de votos
             foreach ($top10Posts as $postInfo) {
                //  echo '<h2>' . get_the_title($postInfo['post']) . '</h2>';
                //  echo '<p>Quantidade de votos: ' . $postInfo['votos'] . '</p>';
                //  echo '<p>desenho: ' . $postInfo['desenhos'] . '</p>';
                //  echo '<a>Link: ' . $postInfo['link'] . '</a>';
        ?>
        <div class="box_img">
          <figure>
           <?php
           echo '<h2>' . get_the_title($postInfo['post']) . '</h2>';
           ?>
          <h3>
              <?php echo $postInfo['pai']; ?>
          </h3>
          <span>Votos:&nbsp;&nbsp;<?php echo $postInfo['votos'] ?></span>
          <a data-toggle="lightbox" data-gallery="galeria_nova" href="<?php echo $postInfo['link']; ?>">
            <img class="thumb" src="<?php echo $postInfo['link']; ?>">
          </a>
          <a class="btn" href="<?php echo $postInfo['link']; ?>" download="<?php echo get_the_title($postInfo['post']); ?>">
                Baixar
          </a>
          </figure>
        </div>
        <?php
                     }
                    }
        ?>
        </div>
      </div>

</div>


<?php get_footer(); ?>
