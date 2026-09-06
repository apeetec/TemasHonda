<?php

/* Template Name: Desenhos Pedagoga */

get_header(); ?>

	<style>
	#download { display: block;
background-color: green;
color: #fff;
text-align: center;
padding: 10px;
margin: 10px; } 
	</style>
	
	<div class="header space">
	    <div class="container">
	          <h1 class="center"><?php the_title(); ?></h1>
	    </div>
	</div>
	<div class="post-page">
		<div class="body">
			<div class="container space">
				<div class="post-content pedagoga">
				<?php

            //  LOOP PELA CATEGORIA
              $args = array(
                'taxonomy'   => "desenhos_cat",
                'orderby'    => 'title',
                'order'      => 'ASC',
                'hide_empty' => true,
              );
              $product_categories = get_terms($args);

              foreach( $product_categories as $cat ) {
              // FIM LOOP PELA CATEGORIA               

                ############### LOOP DOS DESENHOS DOS OUTROS ###############
              $args = array(
                  "post_type" => 'desenhos',
                  "posts_per_page" => -1,
                  'orderby' => 'rand',
                  // 'post__in' => array(926,924,922,914,910,906,904,898,890,888,876,868,862,858,854,840,838,832,830,828,810,806,800,798,790,788,780,770,764,754,742,736,732,730,726,724,720,716,710,708,706,685,679,671,667,663,661,653,645,629,627,625,609,597,595,593,591,573,557,551,543,537,531,529,521,517,515,509,507,503,491,487,483,475,467,457,447,445,441,439,435,419,417,403,397,395,393,391,381,369,363,359,349,341,339,337,325,319,317,315,307,301,299,297,289,285,248,249,235,215,203,197,195,189,187,181,177,175,173,169,167,165,163,155,153,145,140,138,928),
              );
               
              $loop = new WP_Query( $args );  

                if ($loop->have_posts()) {

                    while ( $loop->have_posts() ) {
                      $loop->the_post();

                      $desenho = get_post_meta(get_the_ID(),'desenhos_box_desenho',true);
                      $desenho = str_replace('-150x150', '', $desenho);                    

                      $protocolo = substr(get_the_title(), strpos(get_the_title(), "#") + 1);
                      $protocolo = str_replace('8211;', '', $protocolo);
                      $protocolo = str_replace('#', '', $protocolo);
                      $protocolo = str_replace(' ', '', $protocolo);

                      $categorias = get_the_terms(get_the_ID(), 'desenhos_cat');
                      $categorias = array_reverse($categorias);

                      echo '<a id="download" href="'.$desenho.'" download="';

                      foreach($categorias as $cat){
                      	echo $cat->slug . '-';
                      }
                      echo $protocolo;

                      echo '">Download: ';
       
                      foreach($categorias as $cat){
                      	echo $cat->slug . '-';
                      }
                    	echo $protocolo . '</a>';
                    }
                    }
                }
              ############### LOOP DOS DESENHOS DOS FILHOS ###############
              
              /* LOOP PELA CATEGORIA } */
              ?>
				</div>
			</div>
		</div>
	</div>

<?php get_footer(); ?>